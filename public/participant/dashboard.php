<?php
/**
 * Participant Dashboard — FR-55, FR-56, FR-57, FR-58
 * Modern redesign v3.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../../src/helpers.php';

session_boot();
require_participant();

require_once __DIR__ . '/../../src/models/EnrollmentModel.php';
require_once __DIR__ . '/../../src/models/CertificateModel.php';
require_once __DIR__ . '/../../config/db.php';

$userId      = current_user_id();
$userName    = $_SESSION['user_name'] ?? 'Participant';

$eModel      = new EnrollmentModel();
$cModel      = new CertificateModel();
$enrollments = $eModel->listByParticipant($userId);
$certs       = $cModel->listForParticipant($userId);

// Profile data
$pdo  = get_pdo();
$stmt = $pdo->prepare('SELECT full_name, email, phone, date_of_birth, created_at FROM users WHERE id = :id');
$stmt->execute([':id' => $userId]);
$profile = $stmt->fetch();

// Compute stats
$completedCount = count(array_filter($enrollments, fn($e) => $e['status'] === 'Completed'));
$validCertCount = count(array_filter($certs, fn($c) => $c['status'] === 'Valid'));

$pageTitle = 'My Dashboard';
$userRole  = 'participant';

include __DIR__ . '/../../src/views/layout/header.php';
?>

<!-- Welcome banner -->
<div class="dashboard-banner" style="background:linear-gradient(135deg,#059669 0%,#047857 50%,#065f46 100%);">
    <div style="position:relative;z-index:1;">
        <div class="dashboard-banner__title">
            Welcome, <?= h(explode(' ', $userName)[0]) ?> 🎓
        </div>
        <div class="dashboard-banner__sub">
            You have <strong><?= $validCertCount ?></strong> certificate<?= $validCertCount !== 1 ? 's' : '' ?> and <strong><?= $completedCount ?></strong> completed course<?= $completedCount !== 1 ? 's' : '' ?>.
        </div>
    </div>
    <div style="display:flex;gap:.625rem;position:relative;z-index:1;flex-wrap:wrap;">
        <a href="/verify.php" class="btn btn--sm" style="background:rgba(255,255,255,.15);color:#fff;border-color:rgba(255,255,255,.25);">
            <i class="fa-solid fa-magnifying-glass"></i> Verify Certificate
        </a>
    </div>
    <i class="fa-solid fa-award dashboard-banner__icon"></i>
</div>

<!-- Stats grid -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(170px,1fr));">
    <div class="stat-card stat-card--blue">
        <div class="stat-card__icon stat-card__icon--blue">
            <i class="fa-solid fa-book-open"></i>
        </div>
        <div class="stat-card__body">
            <div class="stat-card__value"><?= count($enrollments) ?></div>
            <div class="stat-card__label">Total Courses</div>
        </div>
    </div>
    <div class="stat-card stat-card--green">
        <div class="stat-card__icon stat-card__icon--green">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="stat-card__body">
            <div class="stat-card__value" style="color:var(--clr-success)"><?= $completedCount ?></div>
            <div class="stat-card__label">Completed</div>
        </div>
    </div>
    <div class="stat-card stat-card--purple">
        <div class="stat-card__icon stat-card__icon--purple">
            <i class="fa-solid fa-award"></i>
        </div>
        <div class="stat-card__body">
            <div class="stat-card__value" style="color:var(--clr-primary)"><?= $validCertCount ?></div>
            <div class="stat-card__label">Certificates</div>
        </div>
    </div>
</div>

<!-- Profile card -->
<div class="card">
    <div class="card__header">
        <span class="card__title">
            <i class="fa-solid fa-user-circle"></i>
            My Profile
        </span>
        <span class="badge badge--success">
            <i class="fa-solid fa-check-circle"></i> Active Participant
        </span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.5rem;">
        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-user"></i> Full Name</div>
            <div class="detail-value fw-600"><?= h($profile['full_name']) ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-envelope"></i> Email</div>
            <div class="detail-value"><?= h($profile['email']) ?></div>
        </div>
        <?php if ($profile['phone']): ?>
        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-phone"></i> Phone</div>
            <div class="detail-value"><?= h($profile['phone']) ?></div>
        </div>
        <?php endif; ?>
        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-calendar-plus"></i> Member Since</div>
            <div class="detail-value"><?= h(fmt_date($profile['created_at'])) ?></div>
        </div>
    </div>
</div>

<!-- Enrolled courses (FR-57) -->
<div class="card">
    <div class="card__header">
        <span class="card__title">
            <i class="fa-solid fa-book-open"></i>
            My Courses (<?= count($enrollments) ?>)
        </span>
        <a href="/participant/courses.php" class="btn btn--outline btn--sm">
            <i class="fa-solid fa-arrow-right"></i> View All
        </a>
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
                <tr><th>Course</th><th>Status</th><th>Completion</th><th>Certificate</th></tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($enrollments, 0, 6) as $e): ?>
                <?php $bmap = ['Enrolled'=>'enrolled','In Progress'=>'progress','Completed'=>'complete']; ?>
                <tr>
                    <td class="fw-600"><?= h($e['course_title']) ?></td>
                    <td><span class="badge badge--<?= $bmap[$e['status']] ?? 'gray' ?>"><?= h($e['status']) ?></span></td>
                    <td class="text-muted"><?= $e['completion_date'] ? h(fmt_date($e['completion_date'])) : '<span style="color:var(--clr-gray-400)">—</span>' ?></td>
                    <td>
                        <?php if ($e['certificate_id']): ?>
                            <a href="/participant/download.php?id=<?= h($e['certificate_id']) ?>"
                               class="btn btn--success btn--sm">
                                <i class="fa-solid fa-download"></i> Download
                            </a>
                        <?php else: ?><span style="color:var(--clr-gray-400);font-size:.8rem;">Not issued</span><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Certificates (FR-58) -->
<div class="card">
    <div class="card__header">
        <span class="card__title">
            <i class="fa-solid fa-award"></i>
            My Certificates (<?= count($certs) ?>)
        </span>
        <a href="/participant/certificates.php" class="btn btn--outline btn--sm">
            <i class="fa-solid fa-arrow-right"></i> View All
        </a>
    </div>
    <?php if (empty($certs)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-award"></i>
            <p>No certificates issued yet. Complete your courses to earn certificates.</p>
        </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>Certificate #</th><th>Course</th><th>Issued</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($certs as $c): ?>
                <tr>
                    <td><code style="font-size:.75rem;color:var(--clr-primary);background:var(--clr-primary-lt);padding:.15rem .4rem;border-radius:.25rem;"><?= h($c['certificate_number']) ?></code></td>
                    <td class="fw-600"><?= h($c['course_title']) ?></td>
                    <td class="text-muted"><?= h(fmt_date($c['issued_at'])) ?></td>
                    <td>
                        <span class="badge badge--<?= $c['status'] === 'Valid' ? 'success' : 'danger' ?>">
                            <i class="fa-solid fa-<?= $c['status'] === 'Valid' ? 'circle-check' : 'ban' ?>"></i>
                            <?= h($c['status']) ?>
                        </span>
                    </td>
                    <td>
                        <a href="/participant/download.php?id=<?= h($c['id']) ?>"
                           class="btn btn--primary btn--sm">
                            <i class="fa-solid fa-download"></i> PDF
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../src/views/layout/footer.php'; ?>
