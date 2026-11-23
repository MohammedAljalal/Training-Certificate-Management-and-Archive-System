<?php
/**
 * Certificate Archive — FR-39, FR-44
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../src/helpers.php';

session_boot();
require_admin();

require_once __DIR__ . '/../../../src/models/CertificateModel.php';

$model        = new CertificateModel();
$statusFilter = $_GET['status'] ?? 'All';
$search       = trim($_GET['search'] ?? '');

$validStatuses = ['All', 'Valid', 'Revoked'];
if (!in_array($statusFilter, $validStatuses, true)) {
    $statusFilter = 'All';
}

$total  = $model->count($statusFilter);
$pg     = paginate($total);
$certs  = $model->archive($pg['perPage'], $pg['offset'], $statusFilter, $search);

$pageTitle = 'Certificate Archive';
$userRole  = 'admin';
$userName  = $_SESSION['user_name'] ?? '';

include __DIR__ . '/../../../src/views/layout/header.php';
?>

<div class="card">
    <div class="card__header">
        <span class="card__title">
            <i class="fa-solid fa-award"></i>
            Certificate Archive (<?= h($total) ?>)
        </span>
    </div>

    <!-- Filter and search (FR-44) -->
    <form method="GET" action="/admin/certificates/index.php" class="search-bar mb-2">
        <select name="status" class="form-control" style="max-width:150px;" onchange="this.form.submit()">
            <?php foreach ($validStatuses as $s): ?>
                <option value="<?= h($s) ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= h($s) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="input-group" style="flex:1;max-width:340px;">
            <i class="fa-solid fa-magnifying-glass input-group__icon"></i>
            <input type="text" name="search" class="form-control"
                placeholder="Search by cert # or participant"
                value="<?= h($search) ?>">
        </div>
        <button type="submit" class="btn btn--primary btn--sm">
            <i class="fa-solid fa-magnifying-glass"></i> Search
        </button>
        <?php if ($search || $statusFilter !== 'All'): ?>
            <a href="/admin/certificates/index.php" class="btn btn--outline btn--sm">
                <i class="fa-solid fa-xmark"></i> Clear
            </a>
        <?php endif; ?>
    </form>

    <?php if (empty($certs)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-award"></i>
            <p>No certificates found.</p>
        </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Certificate #</th>
                    <th>Participant</th>
                    <th>Course</th>
                    <th>Completed</th>
                    <th>Issued</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($certs as $c): ?>
                <tr>
                    <td><code><?= h($c['certificate_number']) ?></code></td>
                    <td>
                        <a href="/admin/participants/view.php?id=<?= h($c['participant_id']) ?>" class="fw-600">
                            <?= h($c['participant_name']) ?>
                        </a>
                    </td>
                    <td><?= h($c['course_title']) ?></td>
                    <td><?= h(fmt_date($c['completion_date'])) ?></td>
                    <td><?= h(fmt_date($c['issued_at'])) ?></td>
                    <td>
                        <span class="badge badge--<?= $c['status'] === 'Valid' ? 'success' : 'danger' ?>">
                            <i class="fa-solid fa-<?= $c['status'] === 'Valid' ? 'circle-check' : 'ban' ?>"></i>
                            <?= h($c['status']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="/admin/certificates/view.php?id=<?= h($c['id']) ?>"
                               class="btn btn--outline btn--sm" title="View details">
                                <i class="fa-solid fa-eye"></i> View
                            </a>
                            <a href="/admin/certificates/download.php?id=<?= h($c['id']) ?>"
                               class="btn btn--primary btn--sm" title="Download PDF">
                                <i class="fa-solid fa-download"></i> PDF
                            </a>
                            <?php if ($c['status'] === 'Valid'): ?>
                            <button type="button" class="btn btn--danger btn--sm"
                                data-confirm-modal="#modal-revoke"
                                data-id="<?= h($c['id']) ?>"
                                title="Revoke Certificate">
                                <i class="fa-solid fa-ban"></i> Revoke
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pg['totalPages'] > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $pg['totalPages']; $i++): ?>
            <?= $i === $pg['page'] ? "<span class=\"current\">$i</span>" : "<a href=\"?page=$i&status=" . urlencode($statusFilter) . "&search=" . urlencode($search) . "\">$i</a>" ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Revoke modal (FR-42, NFR-14) -->
<div class="modal-backdrop" id="modal-revoke" aria-hidden="true">
    <div class="modal">
        <div class="modal__icon modal__icon--danger">
            <i class="fa-solid fa-ban"></i>
        </div>
        <h2 class="modal__title">Revoke Certificate</h2>
        <p class="modal__body">Please provide a reason for revocation. This action will permanently invalidate the certificate.</p>
        <form method="POST" action="/admin/certificates/revoke.php">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="">
            <div class="form-group">
                <label class="form-label" for="reason">Reason <span class="required">*</span></label>
                <textarea id="reason" name="reason" class="form-control"
                    rows="3" required placeholder="Enter detailed reason for revocation..."></textarea>
            </div>
            <div class="modal__actions">
                <button type="button" class="btn btn--outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn--danger">
                    <i class="fa-solid fa-ban"></i> Revoke Certificate
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../../src/views/layout/footer.php'; ?>
