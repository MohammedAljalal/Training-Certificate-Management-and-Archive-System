<?php
/**
 * Course Detail View — FR-12, FR-16, FR-31
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../src/helpers.php';

session_boot();
require_admin();

require_once __DIR__ . '/../../../src/models/CourseModel.php';
require_once __DIR__ . '/../../../src/models/EnrollmentModel.php';

$id     = (int)($_GET['id'] ?? 0);
$model  = new CourseModel();
$course = $model->findWithEnrollments($id);

if (!$course) {
    http_response_code(404);
    set_flash('error', 'Course not found.');
    redirect('/admin/courses/index.php');
}

$pageTitle = h($course['title']);
$userRole  = 'admin';
$userName  = $_SESSION['user_name'] ?? '';

include __DIR__ . '/../../../src/views/layout/header.php';
?>

<div class="d-flex gap-2 mb-2 flex-wrap">
    <a href="/admin/courses/index.php" class="btn btn--outline btn--sm">
        <i class="fa-solid fa-arrow-left"></i> All Courses
    </a>
    <a href="/admin/courses/form.php?id=<?= h($id) ?>" class="btn btn--primary btn--sm">
        <i class="fa-solid fa-pen-to-square"></i> Edit Course
    </a>
    <a href="/admin/enrollments/create.php?course_id=<?= h($id) ?>" class="btn btn--success btn--sm">
        <i class="fa-solid fa-user-plus"></i> Enroll Participant
    </a>
</div>

<!-- Course details card -->
<div class="card">
    <div class="card__header">
        <span class="card__title">
            <i class="fa-solid fa-book-open"></i>
            <?= h($course['title']) ?>
        </span>
        <span class="badge badge--<?= $course['status'] === 'Active' ? 'success' : 'gray' ?>">
            <i class="fa-solid fa-<?= $course['status'] === 'Active' ? 'circle-check' : 'circle-pause' ?>"></i>
            <?= h($course['status']) ?>
        </span>
    </div>

    <div class="detail-list">
        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-calendar-day"></i> Start Date</div>
            <div class="detail-value"><?= h(fmt_date($course['start_date'])) ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-calendar-check"></i> End Date</div>
            <div class="detail-value"><?= h(fmt_date($course['end_date'])) ?></div>
        </div>
        <?php if ($course['location']): ?>
        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-location-dot"></i> Location</div>
            <div class="detail-value"><?= h($course['location']) ?></div>
        </div>
        <?php endif; ?>
        <?php if ($course['description']): ?>
        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-align-left"></i> Description</div>
            <div class="detail-value"><?= nl2br(h($course['description'])) ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Enrollment list (FR-31) -->
<div class="card">
    <div class="card__header">
        <span class="card__title">
            <i class="fa-solid fa-clipboard-list"></i>
            Enrollments (<?= count($course['enrollments']) ?>)
        </span>
        <a href="/admin/enrollments/create.php?course_id=<?= h($id) ?>" class="btn btn--success btn--sm">
            <i class="fa-solid fa-user-plus"></i> Enroll Participant
        </a>
    </div>

    <?php if (empty($course['enrollments'])): ?>
        <div class="empty-state">
            <i class="fa-solid fa-users-slash"></i>
            <p>No participants enrolled in this course yet.</p>
        </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Participant</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Completion Date</th>
                    <th>Certificate</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($course['enrollments'] as $e): ?>
                <tr>
                    <td>
                        <a href="/admin/participants/view.php?id=<?= h($e['user_id']) ?>" class="fw-600">
                            <?= h($e['participant_name']) ?>
                        </a>
                    </td>
                    <td><?= h($e['participant_email']) ?></td>
                    <td>
                        <?php
                        $badgeMap = ['Enrolled' => 'enrolled', 'In Progress' => 'progress', 'Completed' => 'complete'];
                        $badgeCls = $badgeMap[$e['status']] ?? 'gray';
                        ?>
                        <span class="badge badge--<?= $badgeCls ?>">
                            <i class="fa-solid fa-circle-dot"></i>
                            <?= h($e['status']) ?>
                        </span>
                    </td>
                    <td><?= $e['completion_date'] ? h(fmt_date($e['completion_date'])) : '—' ?></td>
                    <td>
                        <?php if ($e['certificate_id']): ?>
                            <a href="/admin/certificates/view.php?id=<?= h($e['certificate_id']) ?>"
                               class="badge badge--success">
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
                    <td>
                        <a href="/admin/enrollments/update.php?id=<?= h($e['id']) ?>"
                           class="btn btn--outline btn--sm">
                            <i class="fa-solid fa-pen-to-square"></i> Status
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../src/views/layout/footer.php'; ?>
