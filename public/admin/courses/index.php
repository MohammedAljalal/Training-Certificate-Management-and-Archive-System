<?php
/**
 * Course List — FR-11, FR-16
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../src/helpers.php';

session_boot();
require_admin();

require_once __DIR__ . '/../../../src/models/CourseModel.php';

$model     = new CourseModel();
$total     = $model->count();
$pg        = paginate($total);
$courses   = $model->list($pg['perPage'], $pg['offset']);
$pageTitle = 'Courses';
$userRole  = 'admin';
$userName  = $_SESSION['user_name'] ?? '';

include __DIR__ . '/../../../src/views/layout/header.php';
?>

<div class="card">
    <div class="card__header">
        <span class="card__title">
            <i class="fa-solid fa-book-open"></i>
            Training Courses (<?= h($total) ?>)
        </span>
        <a href="/admin/courses/form.php" class="btn btn--primary">
            <i class="fa-solid fa-plus"></i> New Course
        </a>
    </div>

    <?php if (empty($courses)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-book-open"></i>
            <p>No courses found.</p>
            <a href="/admin/courses/form.php" class="btn btn--primary btn--sm">
                <i class="fa-solid fa-plus"></i> Create the first course
            </a>
        </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Enrollments</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($courses as $c): ?>
                <tr>
                    <td><?= h($c['id']) ?></td>
                    <td>
                        <a href="/admin/courses/view.php?id=<?= h($c['id']) ?>" class="fw-600">
                            <?= h($c['title']) ?>
                        </a>
                    </td>
                    <td><?= h(fmt_date($c['start_date'])) ?></td>
                    <td><?= h(fmt_date($c['end_date'])) ?></td>
                    <td>
                        <span class="badge badge--<?= $c['status'] === 'Active' ? 'success' : 'gray' ?>">
                            <i class="fa-solid fa-<?= $c['status'] === 'Active' ? 'circle-check' : 'circle-pause' ?>"></i>
                            <?= h($c['status']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge--info">
                            <i class="fa-solid fa-users"></i>
                            <?= h($c['enrollment_count']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="/admin/courses/view.php?id=<?= h($c['id']) ?>" class="btn btn--outline btn--sm" title="View details">
                                <i class="fa-solid fa-eye"></i> View
                            </a>
                            <a href="/admin/courses/form.php?id=<?= h($c['id']) ?>" class="btn btn--outline btn--sm" title="Edit course">
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </a>
                            <?php if ((int)$c['enrollment_count'] === 0): ?>
                            <form method="POST" action="/admin/courses/delete.php" style="display:inline;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= h($c['id']) ?>">
                                <button type="button"
                                    class="btn btn--danger btn--sm"
                                    data-confirm-modal="#modal-delete"
                                    data-id="<?= h($c['id']) ?>"
                                    title="Delete course">
                                    <i class="fa-solid fa-trash-can"></i> Delete
                                </button>
                            </form>
                            <?php endif; ?>
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

<!-- Delete confirmation modal -->
<div class="modal-backdrop" id="modal-delete" aria-hidden="true">
    <div class="modal">
        <div class="modal__icon modal__icon--danger">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h2 class="modal__title">Delete Course</h2>
        <p class="modal__body">Are you sure you want to permanently delete this course? This action cannot be undone.</p>
        <div class="modal__actions">
            <button type="button" class="btn btn--outline" data-modal-close>Cancel</button>
            <form method="POST" action="/admin/courses/delete.php">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="">
                <button type="submit" class="btn btn--danger">
                    <i class="fa-solid fa-trash-can"></i> Yes, Delete
                </button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../src/views/layout/footer.php'; ?>
