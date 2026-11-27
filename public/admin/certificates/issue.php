<?php
/**
 * Certificate Issuance — FR-32..FR-38, BR-02..BR-06, NFR-22, NFR-30
 *
 * Implements the 7-step atomic issuance sequence from SRS §6.1 / BR-06.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../src/helpers.php';

session_boot();
require_admin();

require_once __DIR__ . '/../../../src/models/EnrollmentModel.php';
require_once __DIR__ . '/../../../src/models/CertificateModel.php';

// ── Determine enrollment ─────────────────────────────────────────────────────
$enrollmentId = (int)(
    $_GET['enrollment_id'] ??
    $_POST['enrollment_id'] ??
    0
);

$eModel     = new EnrollmentModel();
$enrollment = $eModel->find($enrollmentId);

if (!$enrollment) {
    set_flash('error', 'Enrollment not found.');
    redirect('/admin/enrollments/index.php');
}

// Step 1: Verify eligibility — status must be Completed (BR-02)
if ($enrollment['status'] !== 'Completed') {
    set_flash('error', 'Certificate can only be issued for a Completed enrollment. (BR-02)');
    redirect("/admin/courses/view.php?id={$enrollment['course_id']}");
}

// Step 1: Confirm no certificate exists yet (BR-03, FR-33)
$cModel = new CertificateModel();
if ($cModel->findByEnrollment($enrollmentId)) {
    set_flash('error', 'A certificate has already been issued for this enrollment. (BR-03, FR-33)');
    redirect("/admin/courses/view.php?id={$enrollment['course_id']}");
}

// ── Confirmation GET ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $pageTitle = 'Issue Certificate';
    $userRole  = 'admin';
    $userName  = $_SESSION['user_name'] ?? '';
    include __DIR__ . '/../../../src/views/layout/header.php';
    ?>
    <div class="card" style="max-width:600px;margin:0 auto;">
        <div class="card__header">
            <span class="card__title">
                <i class="fa-solid fa-certificate"></i>
                Issue Certificate
            </span>
            <a href="/admin/courses/view.php?id=<?= h($enrollment['course_id']) ?>" class="btn btn--outline btn--sm">
                <i class="fa-solid fa-arrow-left"></i> Back
            </a>
        </div>

        <div class="detail-list mb-2" style="background:var(--clr-gray-50);padding:1.25rem;border-radius:var(--radius);border:1px solid var(--clr-gray-200);">
            <div class="detail-row">
                <div class="detail-label"><i class="fa-solid fa-user"></i> Participant</div>
                <div class="detail-value fw-600"><?= h($enrollment['participant_name']) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label"><i class="fa-solid fa-book-open"></i> Course</div>
                <div class="detail-value"><?= h($enrollment['course_title']) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label"><i class="fa-solid fa-calendar-check"></i> Completed</div>
                <div class="detail-value"><?= h(fmt_date($enrollment['completion_date'])) ?></div>
            </div>
        </div>

        <p class="mb-2 text-muted" style="font-size:.875rem;">
            <i class="fa-solid fa-circle-info"></i>
            This will generate a cryptographically unique PDF certificate and record it in the permanent archive.
        </p>

        <form method="POST" action="/admin/certificates/issue.php">
            <?= csrf_field() ?>
            <input type="hidden" name="enrollment_id" value="<?= h($enrollmentId) ?>">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn--primary">
                    <i class="fa-solid fa-certificate"></i> Generate &amp; Issue Certificate
                </button>
                <a href="/admin/courses/view.php?id=<?= h($enrollment['course_id']) ?>" class="btn btn--outline">Cancel</a>
            </div>
        </form>
    </div>
    <?php
    include __DIR__ . '/../../../src/views/layout/footer.php';
    exit;
}

// ── POST: Execute atomic issuance sequence ───────────────────────────────────
csrf_verify();

// Re-check eligibility now that we're in POST path (race safety)
$enrollment = $eModel->find($enrollmentId);
if (!$enrollment || $enrollment['status'] !== 'Completed') {
    set_flash('error', 'Enrollment is no longer eligible.');
    redirect("/admin/courses/view.php?id={$enrollment['course_id']}");
}
if ($cModel->findByEnrollment($enrollmentId)) {
    set_flash('error', 'Certificate was already issued (concurrent request detected).');
    redirect("/admin/courses/view.php?id={$enrollment['course_id']}");
}

// ── Load shared PDF generator ────────────────────────────────────────────────
$vendorAutoload = APP_ROOT . '/vendor/autoload.php';
if (!file_exists($vendorAutoload)) {
    set_flash('error', 'Dompdf not installed. Run: composer install (NFR-30)');
    redirect("/admin/courses/view.php?id={$enrollment['course_id']}");
}

require_once __DIR__ . '/../../../src/certificate_pdf.php';

// Storage directory (outside web root)
$storageDir = APP_ROOT . '/storage/certificates/';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0750, true);
}

$pdo = get_pdo();

// ── 7-Step Atomic Issuance ───────────────────────────────────────────────────
$maxAttempts = 5;
$success     = false;
$certNumber  = '';
$tempFile    = '';
$finalFile   = '';

for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {

    // Step 2: Generate candidate certificate number (FR-34, BR-04)
    $year       = date('Y');
    $randPart   = strtoupper(bin2hex(random_bytes(4))); // 8 hex chars
    $certNumber = "CERT-$year-$randPart";

    // Step 4: Render PDF to temp file
    $issuedDate = date('Y-m-d');
    $pdfBytes   = generateCertificatePdfBytes(
        $enrollment['participant_name'],
        $enrollment['course_title'],
        $enrollment['completion_date'],
        $certNumber,
        $issuedDate
    );

    $tempFile = tempnam(sys_get_temp_dir(), 'tcmas_cert_') . '.pdf';
    file_put_contents($tempFile, $pdfBytes);

    $filePath  = "storage/certificates/{$certNumber}.pdf";
    $finalFile = $storageDir . "{$certNumber}.pdf";

    // Step 3: Begin transaction
    $pdo->beginTransaction();

    try {
        // Step 5: INSERT certificate record
        $cModel->insert($enrollmentId, $certNumber, $issuedDate, $filePath);

        // Step 6: Move temp file to final location (fallback to copy+unlink across Railway volume mounts)
        if (!@rename($tempFile, $finalFile)) {
            if (!copy($tempFile, $finalFile)) {
                throw new RuntimeException('Failed to move PDF to storage directory.');
            }
            @unlink($tempFile);
        }
        $tempFile = ''; // prevent double-cleanup

        // Step 7: Commit
        $pdo->commit();
        $success = true;
        break;

    } catch (PDOException $e) {
        $pdo->rollBack();

        // Clean up temp file if still exists
        if ($tempFile && file_exists($tempFile)) {
            @unlink($tempFile);
        }
        // Clean up final file if commit failed after move
        if ($finalFile && file_exists($finalFile)) {
            @unlink($finalFile);
        }

        // Duplicate cert number → retry (NFR-25, FR-34)
        if (str_contains($e->getMessage(), '1062') || str_contains($e->getMessage(), 'Duplicate')) {
            continue;
        }

        // Any other DB error → abort
        set_flash('error', 'Certificate issuance failed: database error. Please try again.');
        redirect("/admin/courses/view.php?id={$enrollment['course_id']}");

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($tempFile  && file_exists($tempFile))  @unlink($tempFile);
        if ($finalFile && file_exists($finalFile))  @unlink($finalFile);

        set_flash('error', 'Certificate issuance failed: ' . h($e->getMessage()));
        redirect("/admin/courses/view.php?id={$enrollment['course_id']}");
    }
}

if (!$success) {
    set_flash('error', "Certificate number generation failed after $maxAttempts attempts. Please try again.");
    redirect("/admin/courses/view.php?id={$enrollment['course_id']}");
}

// FR-38: success confirmation
set_flash('success', "Certificate issued successfully! Certificate Number: $certNumber");
redirect("/admin/courses/view.php?id={$enrollment['course_id']}");
