<?php
/**
 * Participant Profile View — FR-21, FR-23 (3-tier delete)
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../src/helpers.php';

session_boot();
require_admin();

require_once __DIR__ . '/../../../src/models/ParticipantModel.php';

$id    = (int)($_GET['id'] ?? 0);
$model = new ParticipantModel();
$p     = $model->findWithHistory($id);

if (!$p) {
    set_flash('error', 'Participant not found.');
    redirect('/admin/participants/index.php');
}

$certCount  = $model->certificateCount($id);
$enrollCount= $model->enrollmentCount($id);

$pageTitle  = h($p['full_name']);
$userRole   = 'admin';
$userName   = $_SESSION['user_name'] ?? '';

include __DIR__ . '/../../../src/views/layout/header.php';
?>

<div class="d-flex gap-2 mb-2 flex-wrap">
    <a href="/admin/participants/index.php" class="btn btn--outline btn--sm">
        <i class="fa-solid fa-arrow-left"></i> All Participants
    </a>
    <a href="/admin/participants/form.php?id=<?= h($id) ?>" class="btn btn--primary btn--sm">
        <i class="fa-solid fa-user-pen"></i> Edit Profile
    </a>
    <a href="/admin/enrollments/create.php?user_id=<?= h($id) ?>" class="btn btn--success btn--sm">
        <i class="fa-solid fa-plus"></i> Enroll in Course
    </a>
</div>

<!-- Profile card -->
<div class="card">
    <div class="card__header">
        <span class="card__title">
            <i class="fa-solid fa-user"></i>
            <?= h($p['full_name']) ?>
        </span>
        <span class="badge badge--<?= $p['status'] === 'Active' ? 'success' : 'danger' ?>">
            <i class="fa-solid fa-<?= $p['status'] === 'Active' ? 'circle-check' : 'circle-xmark' ?>"></i>
            <?= h($p['status']) ?>
        </span>
    </div>

    <div class="detail-list">
        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-envelope"></i> Email</div>
            <div class="detail-value"><strong><?= h($p['email']) ?></strong></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-phone"></i> Phone</div>
            <div class="detail-value"><?= $p['phone'] ? h($p['phone']) : '<span class="text-muted">—</span>' ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-cake-candles"></i> Date of Birth</div>
            <div class="detail-value"><?= $p['date_of_birth'] ? h(fmt_date($p['date_of_birth'])) : '<span class="text-muted">—</span>' ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-clock"></i> Registered</div>
            <div class="detail-value"><?= h(fmt_date($p['created_at'])) ?></div>
        </div>
    </div>

    <!-- FR-23: 3-tier delete / deactivate actions -->
    <div class="mt-2" style="border-top:1px solid var(--clr-gray-200);padding-top:1.25rem;">
        <h4 style="font-size:.875rem;margin-bottom:.75rem;color:var(--clr-gray-700);">Account Management</h4>
        <div class="d-flex gap-2 align-center flex-wrap">

            <?php if ($certCount > 0): ?>
                <!-- Tier 1: has certs → deactivate only -->
                <div class="alert alert--warning" style="margin-bottom:0;flex:1;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>This participant has <strong><?= h($certCount) ?></strong> issued certificate(s) and cannot be permanently deleted.</span>
                </div>
                <?php if ($p['status'] === 'Active'): ?>
                <form method="POST" action="/admin/participants/delete.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id"     value="<?= h($id) ?>">
                    <input type="hidden" name="action" value="deactivate">
                    <button type="button" class="btn btn--warning btn--sm"
                        data-confirm-modal="#modal-deactivate">
                        <i class="fa-solid fa-user-slash"></i> Deactivate Account
                    </button>
                </form>
                <?php else: ?>
                    <span class="badge badge--danger"><i class="fa-solid fa-ban"></i> Inactive</span>
                <?php endif; ?>

            <?php elseif ($enrollCount > 0): ?>
                <!-- Tier 2: has enrollments but no certs → deletion blocked -->
                <div class="alert alert--warning" style="margin-bottom:0;flex:1;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>This participant has <strong><?= h($enrollCount) ?></strong> enrollment(s). Permanent deletion is blocked to preserve history.</span>
                </div>
                <?php if ($p['status'] === 'Active'): ?>
                <form method="POST" action="/admin/participants/delete.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id"     value="<?= h($id) ?>">
                    <input type="hidden" name="action" value="deactivate">
                    <button type="button" class="btn btn--warning btn--sm"
                        data-confirm-modal="#modal-deactivate">
                        <i class="fa-solid fa-user-slash"></i> Deactivate Instead
                    </button>
                </form>
                <?php endif; ?>

            <?php else: ?>
                <!-- Tier 3: no certs, no enrollments → hard delete allowed -->
                <form method="POST" action="/admin/participants/delete.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id"     value="<?= h($id) ?>">
                    <input type="hidden" name="action" value="delete">
                    <button type="button" class="btn btn--danger btn--sm"
                        data-confirm-modal="#modal-delete">
                        <i class="fa-solid fa-trash-can"></i> Delete Account
                    </button>
                </form>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Enrollment history -->
<div class="card">
    <div class="card__header">
        <span class="card__title">
            <i class="fa-solid fa-clock-rotate-left"></i>
            Enrollment History (<?= count($p['enrollments']) ?>)
        </span>
    </div>
    <?php if (empty($p['enrollments'])): ?>
        <p class="text-muted">No enrollments yet.</p>
    <?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Enrolled</th>
                    <th>Status</th>
                    <th>Completion Date</th>
                    <th>Certificate</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($p['enrollments'] as $e): ?>
                <tr>
                    <td><strong><?= h($e['course_title']) ?></strong></td>
                    <td><?= h(fmt_date($e['created_at'])) ?></td>
                    <td>
                        <?php $bmap = ['Enrolled'=>'enrolled','In Progress'=>'progress','Completed'=>'complete']; ?>
                        <span class="badge badge--<?= $bmap[$e['status']] ?? 'gray' ?>"><?= h($e['status']) ?></span>
                    </td>
                    <td><?= $e['completion_date'] ? h(fmt_date($e['completion_date'])) : '—' ?></td>
                    <td>
                        <?php if ($e['certificate_id']): ?>
                            <a href="/admin/certificates/view.php?id=<?= h($e['certificate_id']) ?>"
                               class="badge badge--<?= $e['cert_status'] === 'Valid' ? 'success' : 'danger' ?>">
                                <i class="fa-solid fa-award"></i>
                                <?= h($e['certificate_number']) ?>
                            </a>
                        <?php elseif ($e['status'] === 'Completed'): ?>
                            <a href="/admin/certificates/issue.php?enrollment_id=<?= h($e['id']) ?>"
                               class="btn btn--success btn--sm">
                                <i class="fa-solid fa-certificate"></i> Issue
                            </a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Deactivate modal -->
<div class="modal-backdrop" id="modal-deactivate" aria-hidden="true">
    <div class="modal">
        <div class="modal__icon modal__icon--warning">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h2 class="modal__title">Deactivate Account</h2>
        <p class="modal__body">This will set the participant's account status to <strong>Inactive</strong>. They will no longer be able to log in. This action can be undone manually.</p>
        <div class="modal__actions">
            <button type="button" class="btn btn--outline" data-modal-close>Cancel</button>
            <form method="POST" action="/admin/participants/delete.php">
                <?= csrf_field() ?>
                <input type="hidden" name="id"     value="<?= h($id) ?>">
                <input type="hidden" name="action" value="deactivate">
                <button type="submit" class="btn btn--warning">
                    <i class="fa-solid fa-user-slash"></i> Deactivate
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Delete modal -->
<div class="modal-backdrop" id="modal-delete" aria-hidden="true">
    <div class="modal">
        <div class="modal__icon modal__icon--danger">
            <i class="fa-solid fa-trash-can"></i>
        </div>
        <h2 class="modal__title">Delete Participant</h2>
        <p class="modal__body">This will <strong>permanently delete</strong> the participant account. This action cannot be undone.</p>
        <div class="modal__actions">
            <button type="button" class="btn btn--outline" data-modal-close>Cancel</button>
            <form method="POST" action="/admin/participants/delete.php">
                <?= csrf_field() ?>
                <input type="hidden" name="id"     value="<?= h($id) ?>">
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="btn btn--danger">
                    <i class="fa-solid fa-trash-can"></i> Yes, Delete Permanently
                </button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../src/views/layout/footer.php'; ?>
