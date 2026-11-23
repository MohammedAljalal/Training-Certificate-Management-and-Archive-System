<?php
/**
 * Participant List — FR-20
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../src/helpers.php';

session_boot();
require_admin();

require_once __DIR__ . '/../../../src/models/ParticipantModel.php';

$model        = new ParticipantModel();
$total        = $model->count();
$pg           = paginate($total);
$participants = $model->list($pg['perPage'], $pg['offset']);
$pageTitle    = 'Participants';
$userRole     = 'admin';
$userName     = $_SESSION['user_name'] ?? '';

include __DIR__ . '/../../../src/views/layout/header.php';
?>

<div class="card">
    <div class="card__header">
        <span class="card__title">
            <i class="fa-solid fa-users"></i>
            Participants (<?= h($total) ?>)
        </span>
        <a href="/admin/participants/form.php" class="btn btn--primary">
            <i class="fa-solid fa-user-plus"></i> New Participant
        </a>
    </div>

    <?php if (empty($participants)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-users"></i>
            <p>No participants registered yet.</p>
            <a href="/admin/participants/form.php" class="btn btn--primary btn--sm">
                <i class="fa-solid fa-user-plus"></i> Register first participant
            </a>
        </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($participants as $p): ?>
                <tr>
                    <td><?= h($p['id']) ?></td>
                    <td>
                        <a href="/admin/participants/view.php?id=<?= h($p['id']) ?>" class="fw-600">
                            <?= h($p['full_name']) ?>
                        </a>
                    </td>
                    <td><?= h($p['email']) ?></td>
                    <td><?= $p['phone'] ? h($p['phone']) : '<span class="text-muted">—</span>' ?></td>
                    <td>
                        <span class="badge badge--<?= $p['status'] === 'Active' ? 'success' : 'danger' ?>">
                            <i class="fa-solid fa-<?= $p['status'] === 'Active' ? 'circle-check' : 'circle-xmark' ?>"></i>
                            <?= h($p['status']) ?>
                        </span>
                    </td>
                    <td><?= h(fmt_date($p['created_at'])) ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="/admin/participants/view.php?id=<?= h($p['id']) ?>" class="btn btn--outline btn--sm" title="View Profile">
                                <i class="fa-solid fa-eye"></i> View
                            </a>
                            <a href="/admin/participants/form.php?id=<?= h($p['id']) ?>" class="btn btn--outline btn--sm" title="Edit Profile">
                                <i class="fa-solid fa-user-pen"></i> Edit
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pg['totalPages'] > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $pg['totalPages']; $i++): ?>
            <?php if ($i === $pg['page']): ?>
                <span class="current"><?= $i ?></span>
            <?php else: ?>
                <a href="?page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../src/views/layout/footer.php'; ?>
