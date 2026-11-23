<?php
/**
 * Participant — My Certificates — FR-58, FR-59
 */

declare(strict_types=1);

require_once __DIR__ . '/../../src/helpers.php';

session_boot();
require_participant();

require_once __DIR__ . '/../../src/models/CertificateModel.php';

$userId = current_user_id();
$model  = new CertificateModel();
$certs  = $model->listForParticipant($userId);

$pageTitle = 'My Certificates';
$userRole  = 'participant';
$userName  = $_SESSION['user_name'] ?? '';

include __DIR__ . '/../../src/views/layout/header.php';
?>

<div class="card">
    <div class="card__header">
        <span class="card__title">
            <i class="fa-solid fa-award"></i>
            My Certificates (<?= count($certs) ?>)
        </span>
    </div>

    <?php if (empty($certs)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-award"></i>
            <p>No certificates issued yet. Complete a course to receive your official certificate.</p>
        </div>
    <?php else: ?>
    <?php foreach ($certs as $c): ?>
    <div class="card" style="border-left:4px solid <?= $c['status'] === 'Valid' ? 'var(--clr-success)' : 'var(--clr-danger)' ?>;margin-bottom:1.25rem;">
        <div class="card__header">
            <span class="card__title">
                <i class="fa-solid fa-<?= $c['status'] === 'Valid' ? 'circle-check text-success' : 'ban text-danger' ?>"></i>
                <?= h($c['certificate_number']) ?>
            </span>
            <span class="badge badge--<?= $c['status'] === 'Valid' ? 'success' : 'danger' ?>">
                <i class="fa-solid fa-<?= $c['status'] === 'Valid' ? 'circle-check' : 'ban' ?>"></i>
                <?= h($c['status']) ?>
            </span>
        </div>

        <div class="detail-list mb-2">
            <div class="detail-row">
                <div class="detail-label"><i class="fa-solid fa-book-open"></i> Course</div>
                <div class="detail-value fw-600"><?= h($c['course_title']) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label"><i class="fa-solid fa-calendar-check"></i> Completed On</div>
                <div class="detail-value"><?= h(fmt_date($c['completion_date'])) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label"><i class="fa-solid fa-calendar-day"></i> Issued On</div>
                <div class="detail-value"><?= h(fmt_date($c['issued_at'])) ?></div>
            </div>
        </div>

        <?php if ($c['status'] === 'Revoked'): ?>
            <div class="alert alert--error mt-1 mb-2">
                <i class="fa-solid fa-ban"></i>
                <div>This certificate has been revoked and is no longer valid.</div>
            </div>
        <?php endif; ?>

        <div>
            <a href="/participant/download.php?id=<?= h($c['id']) ?>"
               class="btn btn--primary btn--sm">
                <i class="fa-solid fa-download"></i> Download PDF Certificate
            </a>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../src/views/layout/footer.php'; ?>
