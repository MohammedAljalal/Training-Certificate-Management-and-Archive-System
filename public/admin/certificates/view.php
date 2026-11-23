<?php
/**
 * Certificate Detail View — FR-40, FR-43
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../src/helpers.php';

session_boot();
require_admin();

require_once __DIR__ . '/../../../src/models/CertificateModel.php';

$id    = (int)($_GET['id'] ?? 0);
$model = new CertificateModel();
$cert  = $model->find($id);

if (!$cert) {
    set_flash('error', 'Certificate not found.');
    redirect('/admin/certificates/index.php');
}

$pageTitle = 'Certificate: ' . $cert['certificate_number'];
$userRole  = 'admin';
$userName  = $_SESSION['user_name'] ?? '';

include __DIR__ . '/../../../src/views/layout/header.php';
?>

<div class="d-flex gap-2 mb-2 flex-wrap">
    <a href="/admin/certificates/index.php" class="btn btn--outline btn--sm">
        <i class="fa-solid fa-arrow-left"></i> Archive
    </a>
    <a href="/admin/certificates/download.php?id=<?= h($id) ?>" class="btn btn--primary btn--sm">
        <i class="fa-solid fa-download"></i> Download PDF
    </a>
    <?php if ($cert['status'] === 'Valid'): ?>
        <button type="button" class="btn btn--danger btn--sm"
            data-confirm-modal="#modal-revoke">
            <i class="fa-solid fa-ban"></i> Revoke
        </button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card__header">
        <span class="card__title">
            <i class="fa-solid fa-award"></i>
            <?= h($cert['certificate_number']) ?>
        </span>
        <span class="badge badge--<?= $cert['status'] === 'Valid' ? 'success' : 'danger' ?>" style="font-size:.85rem;">
            <i class="fa-solid fa-<?= $cert['status'] === 'Valid' ? 'circle-check' : 'ban' ?>"></i>
            <?= h($cert['status']) ?>
        </span>
    </div>

    <?php if ($cert['status'] === 'Revoked'): ?>
    <div class="alert alert--error">
        <i class="fa-solid fa-ban"></i>
        <div>
            <strong>REVOKED</strong> — This certificate is no longer valid.<br>
            Revoked on: <?= h(fmt_date($cert['revoked_at'], 'd M Y H:i')) ?><br>
            Reason: <?= h($cert['revocation_reason']) ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="detail-list">
        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-user"></i> Participant</div>
            <div class="detail-value fw-600">
                <a href="/admin/participants/view.php?id=<?= h($cert['participant_id']) ?>">
                    <?= h($cert['participant_name']) ?>
                </a>
            </div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-book-open"></i> Course</div>
            <div class="detail-value"><?= h($cert['course_title']) ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-calendar-check"></i> Completion Date</div>
            <div class="detail-value"><?= h(fmt_date($cert['completion_date'])) ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-calendar-day"></i> Issued On</div>
            <div class="detail-value"><?= h(fmt_date($cert['issued_at'])) ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-clock"></i> Generated At</div>
            <div class="detail-value"><?= h(fmt_date($cert['created_at'], 'd M Y H:i')) ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-folder"></i> File Storage</div>
            <div class="detail-value"><code><?= h($cert['file_path']) ?></code></div>
        </div>
    </div>
</div>

<!-- Revoke modal -->
<div class="modal-backdrop" id="modal-revoke" aria-hidden="true">
    <div class="modal">
        <div class="modal__icon modal__icon--danger">
            <i class="fa-solid fa-ban"></i>
        </div>
        <h2 class="modal__title">Revoke Certificate</h2>
        <form method="POST" action="/admin/certificates/revoke.php">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= h($id) ?>">
            <div class="form-group">
                <label class="form-label" for="reason">Reason <span class="required">*</span></label>
                <textarea id="reason" name="reason" class="form-control" rows="3" required placeholder="Provide revocation justification..."></textarea>
            </div>
            <div class="modal__actions">
                <button type="button" class="btn btn--outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn--danger">
                    <i class="fa-solid fa-ban"></i> Confirm Revocation
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../../src/views/layout/footer.php'; ?>
