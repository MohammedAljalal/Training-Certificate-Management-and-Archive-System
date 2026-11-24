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

// ── Load Dompdf ──────────────────────────────────────────────────────────────
$vendorAutoload = APP_ROOT . '/vendor/autoload.php';
if (!file_exists($vendorAutoload)) {
    set_flash('error', 'Dompdf not installed. Run: composer install (NFR-30)');
    redirect("/admin/courses/view.php?id={$enrollment['course_id']}");
}

require_once $vendorAutoload;

use Dompdf\Dompdf;
use Dompdf\Options;

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
    $html       = renderCertificateHtml(
        $enrollment['participant_name'],
        $enrollment['course_title'],
        $enrollment['completion_date'],
        $certNumber,
        $issuedDate
    );

    $opts = new Options();
    $opts->setIsRemoteEnabled(false);
    $opts->setIsPhpEnabled(false);
    $opts->setDefaultFont('DejaVu Sans'); // Unicode support (NFR-30)

    $dompdf = new Dompdf($opts);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    $tempFile = tempnam(sys_get_temp_dir(), 'tcmas_cert_') . '.pdf';
    file_put_contents($tempFile, $dompdf->output());

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


// ── Helper: render certificate HTML ─────────────────────────────────────────

function renderCertificateHtml(
    string $participantName,
    string $courseTitle,
    string $completionDate,
    string $certNumber,
    string $issuedDate
): string {
    $name       = htmlspecialchars($participantName, ENT_QUOTES, 'UTF-8');
    $course     = htmlspecialchars($courseTitle,     ENT_QUOTES, 'UTF-8');
    $completion = date('d F Y', strtotime($completionDate));
    $issued     = date('d F Y', strtotime($issuedDate));
    $certNo     = htmlspecialchars($certNumber, ENT_QUOTES, 'UTF-8');

    $verifyHost = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
    $isHttps    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $verifyProto = $isHttps ? 'https' : 'http';
    $verifyUrl = "{$verifyProto}://{$verifyHost}/verify.php";

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  @page {
    size: 297mm 210mm;
    margin: 8mm 10mm;
  }
  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }
  body {
    margin: 0;
    padding: 0;
    background: #ffffff;
    font-family: 'DejaVu Sans', sans-serif;
    color: #1e293b;
    line-height: 1.35;
  }
  .cert-outer {
    border: 4px solid #0f2952;
    padding: 3mm;
    background: #ffffff;
  }
  .cert-inner {
    border: 1.5px solid #c59b27;
    padding: 7mm 12mm 6mm;
    text-align: center;
    background: #ffffff;
  }

  /* Header Branding */
  .header-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 2mm;
  }
  .header-left, .header-right {
    width: 25%;
    font-size: 7.5pt;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    line-height: 1.4;
  }
  .header-left { text-align: left; }
  .header-right { text-align: right; }
  .header-center {
    width: 50%;
    text-align: center;
  }
  .org-title {
    font-size: 11pt;
    font-weight: bold;
    color: #0f2952;
    letter-spacing: 1.5px;
    text-transform: uppercase;
  }
  .org-sub {
    font-size: 7.5pt;
    color: #64748b;
    margin-top: 1mm;
    letter-spacing: 0.8px;
    text-transform: uppercase;
  }

  /* Main Title Area */
  .title-area {
    margin: 3mm 0 2.5mm;
  }
  .main-title {
    font-size: 24pt;
    font-weight: bold;
    color: #0f2952;
    letter-spacing: 3.5px;
    text-transform: uppercase;
  }
  .gold-divider {
    width: 60mm;
    height: 2px;
    background: #c59b27;
    margin: 2mm auto 0;
  }

  /* Presentation text */
  .cert-present {
    font-size: 9.5pt;
    color: #64748b;
    font-style: italic;
    margin: 2.5mm 0 2mm;
  }

  /* Recipient Name */
  .recipient-wrap {
    margin: 1.5mm 0 2.5mm;
  }
  .recipient-name {
    font-size: 23pt;
    font-weight: bold;
    color: #0f2952;
    padding-bottom: 2mm;
    border-bottom: 2px solid #0f2952;
    display: inline-block;
    min-width: 95mm;
  }

  /* Course info */
  .course-lead {
    font-size: 9pt;
    color: #64748b;
    margin-bottom: 1.5mm;
  }
  .course-title {
    font-size: 15pt;
    font-weight: bold;
    color: #1e3a8a;
    margin-bottom: 5mm;
  }

  /* Bottom Row Table */
  .cert-footer-table {
    width: 100%;
    table-layout: fixed;
    margin-top: 2mm;
    border-collapse: collapse;
  }
  .cert-footer-table td {
    vertical-align: middle;
  }
  .col-dates {
    width: 33%;
    text-align: left;
    font-size: 8pt;
    color: #334155;
    line-height: 1.6;
  }
  .col-seal {
    width: 34%;
    text-align: center;
  }
  .col-sig {
    width: 33%;
    text-align: right;
  }

  /* Seal badge */
  .seal-outer-badge {
    display: inline-block;
    width: 27mm;
    height: 27mm;
    border: 2px solid #c59b27;
    border-radius: 13.5mm;
    background: #ffffff;
    text-align: center;
    box-sizing: border-box;
    padding: 1.5mm;
  }
  .seal-inner-badge {
    border: 1px dashed #c59b27;
    border-radius: 12mm;
    height: 22mm;
    box-sizing: border-box;
    padding-top: 3mm;
    text-align: center;
  }
  .seal-stars {
    font-size: 7pt;
    color: #c59b27;
    line-height: 1;
    margin-bottom: 1mm;
  }
  .seal-text-main {
    font-size: 6pt;
    font-weight: bold;
    color: #0f2952;
    text-transform: uppercase;
    line-height: 1.25;
    letter-spacing: 0.5px;
  }
  .seal-text-sub {
    font-size: 5pt;
    color: #c59b27;
    font-weight: bold;
    text-transform: uppercase;
    margin-top: 0.8mm;
    letter-spacing: 0.5px;
  }

  /* Signature line */
  .sig-block {
    display: inline-block;
    text-align: center;
    width: 58mm;
  }
  .sig-line-rule {
    border-top: 1.5px solid #0f2952;
    width: 100%;
    margin-bottom: 2mm;
  }
  .sig-author {
    font-size: 8.5pt;
    font-weight: bold;
    color: #0f2952;
  }
  .sig-title {
    font-size: 7pt;
    color: #64748b;
    margin-top: 0.5mm;
  }

  /* Bottom Credential Bar */
  .serial-bar {
    margin-top: 4.5mm;
    padding-top: 2mm;
    border-top: 1px solid #e2e8f0;
    font-size: 7.5pt;
    color: #64748b;
    text-align: center;
  }
  .serial-bar strong {
    color: #0f2952;
  }
</style>
</head>
<body>
<div class="cert-outer">
  <div class="cert-inner">

    <table class="header-table">
      <tr>
        <td class="header-left">
          Kingdom of Saudi Arabia<br>
          Ministry of Education
        </td>
        <td class="header-center">
          <div class="org-title">Training Certificate Management and Archive System</div>
          <div class="org-sub">University of Tabuk &nbsp;·&nbsp; Faculty of Computers &amp; IT</div>
        </td>
        <td class="header-right">
          Accredited Training<br>
          Deanship of Academic Affairs
        </td>
      </tr>
    </table>

    <div class="title-area">
      <div class="main-title">Certificate of Completion</div>
      <div class="gold-divider"></div>
    </div>

    <div class="cert-present">This is to officially certify that</div>

    <div class="recipient-wrap">
      <span class="recipient-name">$name</span>
    </div>

    <div class="course-lead">has successfully completed the prescribed curriculum and assessment requirements for the training course</div>

    <div class="course-title">$course</div>

    <table class="cert-footer-table">
      <tr>
        <td class="col-dates">
          <div>Completion Date: <strong>$completion</strong></div>
          <div>Date of Issuance: <strong>$issued</strong></div>
          <div style="color:#64748b;margin-top:1mm;font-size:7pt;">Institutional Credential Record</div>
        </td>
        <td class="col-seal">
          <div class="seal-outer-badge">
            <div class="seal-inner-badge">
              <div class="seal-stars">★ ★ ★</div>
              <div class="seal-text-main">Official Credential</div>
              <div class="seal-text-sub">Authenticated</div>
            </div>
          </div>
        </td>
        <td class="col-sig">
          <div class="sig-block">
            <div class="sig-line-rule"></div>
            <div class="sig-author">Dean of Faculty</div>
            <div class="sig-title">Faculty of Computers &amp; Information Technology</div>
          </div>
        </td>
      </tr>
    </table>

    <div class="serial-bar">
      Certificate Identifier: <strong>$certNo</strong> &nbsp;|&nbsp;
      Verify Online: <strong>$verifyUrl</strong> &nbsp;|&nbsp;
      Tamper-evident Credential Record
    </div>

  </div>
</div>
</body>
</html>
HTML;
}
