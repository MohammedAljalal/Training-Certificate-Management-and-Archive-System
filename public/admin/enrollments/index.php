<?php
/**
 * Enrollment List (all enrollments, admin overview)
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../src/helpers.php';

session_boot();
require_admin();

require_once __DIR__ . '/../../../config/db.php';

$pdo  = get_pdo();
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = 25;

$total = (int)$pdo->query('SELECT COUNT(*) FROM enrollments')->fetchColumn();
$pg    = paginate($total, $per);

$sql = '
    SELECT e.*,
           u.full_name AS participant_name,
           c.title     AS course_title,
           c.start_date AS course_start,
           c.end_date   AS course_end
    FROM enrollments e
    JOIN users u   ON u.id = e.user_id
    JOIN courses c ON c.id = e.course_id
    ORDER BY e.created_at DESC
    LIMIT :limit OFFSET :offset
';

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit',  $pg['perPage'], PDO::PARAM_INT);
$stmt->bindValue(':offset', $pg['offset'],  PDO::PARAM_INT);
$stmt->execute();
$enrollments = $stmt->fetchAll();

$pageTitle = 'Enrollments';
$userRole  = 'admin';
$userName  = $_SESSION['user_name'] ?? '';

include __DIR__ . '/../../../src/views/layout/header.php';
?>

<div class="card">
    <div class="card__header">
        <span class="card__title">
            <i class="fa-solid fa-clipboard-list"></i>
            All Enrollments (<?= h($total) ?>)
        </span>
        <a href="/admin/enrollments/create.php" class="btn btn--success">
            <i class="fa-solid fa-plus"></i> New Enrollment
        </a>
    </div>

    <?php if (empty($enrollments)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-clipboard-list"></i>
            <p>No enrollments registered yet.</p>
            <a href="/admin/enrollments/create.php" class="btn btn--success btn--sm">
                <i class="fa-solid fa-plus"></i> Enroll first participant
            </a>
        </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Participant</th>
                    <th>Course</th>
                    <th>Status</th>
                    <th>Completion Date</th>
                    <th>Enrolled On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($enrollments as $e): ?>
                <?php $bmap = ['Enrolled'=>'enrolled','In Progress'=>'progress','Completed'=>'complete']; ?>
                <tr>
                    <td>
                        <a href="/admin/participants/view.php?id=<?= h($e['user_id']) ?>" class="fw-600">
                            <?= h($e['participant_name']) ?>
                        </a>
                    </td>
                    <td>
                        <a href="/admin/courses/view.php?id=<?= h($e['course_id']) ?>">
                            <?= h($e['course_title']) ?>
                        </a>
                    </td>
                    <td>
                        <span class="badge badge--<?= $bmap[$e['status']] ?? 'gray' ?>">
                            <i class="fa-solid fa-circle-dot"></i>
                            <?= h($e['status']) ?>
                        </span>
                    </td>
                    <td><?= $e['completion_date'] ? h(fmt_date($e['completion_date'])) : '<span class="text-muted">—</span>' ?></td>
                    <td><?= h(fmt_date($e['created_at'])) ?></td>
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
    <?php if ($pg['totalPages'] > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $pg['totalPages']; $i++): ?>
            <?= $i === $pg['page'] ? "<span class=\"current\">$i</span>" : "<a href=\"?page=$i\">$i</a>" ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../src/views/layout/footer.php'; ?>
