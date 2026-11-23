<?php
/**
 * Participant — My Courses — FR-57
 */

declare(strict_types=1);

require_once __DIR__ . '/../../src/helpers.php';

session_boot();
require_participant();

require_once __DIR__ . '/../../src/models/EnrollmentModel.php';

$userId      = current_user_id();
$eModel      = new EnrollmentModel();
$enrollments = $eModel->listByParticipant($userId);

$pageTitle   = 'My Courses';
$userRole    = 'participant';
$userName    = $_SESSION['user_name'] ?? '';

include __DIR__ . '/../../src/views/layout/header.php';
?>

<div class="card">
    <div class="card__header">
        <span class="card__title">
            <i class="fa-solid fa-book-open"></i>
            My Enrolled Courses (<?= count($enrollments) ?>)
        </span>
    </div>

    <?php if (empty($enrollments)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-book-open"></i>
            <p>You are not enrolled in any courses yet.</p>
        </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Completed On</th>
                    <th>Certificate</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($enrollments as $e): ?>
                <?php $bmap = ['Enrolled'=>'enrolled','In Progress'=>'progress','Completed'=>'complete']; ?>
                <tr>
                    <td class="fw-600"><?= h($e['course_title']) ?></td>
                    <td><?= h(fmt_date($e['course_start'])) ?></td>
                    <td><?= h(fmt_date($e['course_end'])) ?></td>
                    <td>
                        <span class="badge badge--<?= $bmap[$e['status']] ?? 'gray' ?>">
                            <i class="fa-solid fa-circle-dot"></i>
                            <?= h($e['status']) ?>
                        </span>
                    </td>
                    <td><?= $e['completion_date'] ? h(fmt_date($e['completion_date'])) : '<span class="text-muted">—</span>' ?></td>
                    <td>
                        <?php if ($e['certificate_id']): ?>
                            <a href="/participant/download.php?id=<?= h($e['certificate_id']) ?>"
                               class="btn btn--success btn--sm">
                                <i class="fa-solid fa-download"></i> Download PDF
                            </a>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../src/views/layout/footer.php'; ?>
