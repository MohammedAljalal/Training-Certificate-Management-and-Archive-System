<?php
/**
 * Admin Dashboard — FR-51, FR-52, FR-53, FR-54
 * Modern Redesign v3.5 with Interactive Statistical Charts & Visualizations
 */

declare(strict_types=1);

require_once __DIR__ . '/../../src/helpers.php';

session_boot();
require_admin();

require_once __DIR__ . '/../../src/models/DashboardModel.php';

$model     = new DashboardModel();
$stats     = $model->getSummary();
$pageTitle = 'Dashboard';
$userRole  = 'admin';
$userName  = $_SESSION['user_name'] ?? 'Administrator';

// Prepare chart datasets for JavaScript
$monthlyLabels = [];
$monthlyData   = [];
foreach ($stats['monthly_issuance'] ?? [] as $m) {
    $monthlyLabels[] = $m['month'];
    $monthlyData[]   = (int)$m['count'];
}

$enrollmentBreakdown = $stats['enrollment_breakdown'] ?? [];
$enrCompleted  = (int)($enrollmentBreakdown['Completed'] ?? 0);
$enrInProgress = (int)($enrollmentBreakdown['In Progress'] ?? 0);
$enrEnrolled   = (int)($enrollmentBreakdown['Enrolled'] ?? 0);

include __DIR__ . '/../../src/views/layout/header.php';
?>

<!-- Welcome Banner -->
<div class="dashboard-banner">
    <div style="position:relative;z-index:1;">
        <div class="dashboard-banner__title">
            Welcome back, <?= h(explode(' ', $userName)[0]) ?> 👋
        </div>
        <div class="dashboard-banner__sub">
            Real-time credential management and academic performance archive.
        </div>
    </div>
    <div style="display:flex;gap:.625rem;position:relative;z-index:1;flex-wrap:wrap;align-items:center;">
        <span class="badge" style="background:rgba(255,255,255,.15);color:#fff;border-color:rgba(255,255,255,.2);padding:.4rem .8rem;font-size:.78rem;">
            <i class="fa-solid fa-calendar-day"></i> <?= date('l, d F Y') ?>
        </span>
        <a href="/admin/courses/form.php" class="btn btn--sm" style="background:rgba(255,255,255,.18);color:#fff;border-color:rgba(255,255,255,.3);backdrop-filter:blur(4px);">
            <i class="fa-solid fa-circle-plus"></i> New Course
        </a>
        <a href="/admin/participants/form.php" class="btn btn--sm" style="background:rgba(255,255,255,.18);color:#fff;border-color:rgba(255,255,255,.3);backdrop-filter:blur(4px);">
            <i class="fa-solid fa-user-plus"></i> New Participant
        </a>
    </div>
    <i class="fa-solid fa-certificate dashboard-banner__icon"></i>
</div>

<!-- Stats grid (FR-52) -->
<div class="stats-grid">
    <div class="stat-card stat-card--blue">
        <div class="stat-card__icon stat-card__icon--blue">
            <i class="fa-solid fa-book-open"></i>
        </div>
        <div class="stat-card__body">
            <div class="stat-card__value"><?= h($stats['active_courses']) ?></div>
            <div class="stat-card__label">Active Courses</div>
        </div>
    </div>
    <div class="stat-card stat-card--purple">
        <div class="stat-card__icon stat-card__icon--purple">
            <i class="fa-solid fa-users"></i>
        </div>
        <div class="stat-card__body">
            <div class="stat-card__value"><?= h($stats['active_participants']) ?></div>
            <div class="stat-card__label">Active Participants</div>
        </div>
    </div>
    <div class="stat-card stat-card--orange">
        <div class="stat-card__icon stat-card__icon--orange">
            <i class="fa-solid fa-clipboard-list"></i>
        </div>
        <div class="stat-card__body">
            <div class="stat-card__value"><?= h($stats['total_enrollments']) ?></div>
            <div class="stat-card__label">Total Enrollments</div>
        </div>
    </div>
    <div class="stat-card stat-card--green">
        <div class="stat-card__icon stat-card__icon--green">
            <i class="fa-solid fa-award"></i>
        </div>
        <div class="stat-card__body">
            <div class="stat-card__value" style="color:var(--clr-success)"><?= h($stats['valid_certificates']) ?></div>
            <div class="stat-card__label">Valid Certificates</div>
        </div>
    </div>
    <div class="stat-card stat-card--red">
        <div class="stat-card__icon stat-card__icon--red">
            <i class="fa-solid fa-ban"></i>
        </div>
        <div class="stat-card__body">
            <div class="stat-card__value" style="color:var(--clr-danger)"><?= h($stats['revoked_certificates']) ?></div>
            <div class="stat-card__label">Revoked Credentials</div>
        </div>
    </div>
</div>

<!-- Statistical Charts Row (NEW) -->
<div class="charts-grid" style="display:grid;grid-template-columns:1.6fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">

    <!-- Chart 1: Certificate Issuance Timeline -->
    <div class="card" style="margin-bottom:0;display:flex;flex-direction:column;">
        <div class="card__header">
            <div>
                <span class="card__title">
                    <i class="fa-solid fa-chart-line"></i>
                    Certificate Issuance Velocity
                </span>
                <span style="font-size:.75rem;color:var(--clr-gray-500);display:block;margin-top:.2rem;">
                    Validated credential distribution over past academic months
                </span>
            </div>
            <span class="badge badge--success">
                <i class="fa-solid fa-arrow-trend-up"></i> Active Archive
            </span>
        </div>
        <div style="flex:1;min-height:240px;position:relative;padding:.5rem 0;">
            <canvas id="issuanceTrendChart" style="max-height:260px;width:100%;"></canvas>
            <div id="chartFallback" class="hidden" style="padding:1.5rem;text-align:center;">
                <p class="text-muted">Interactive chart loaded.</p>
            </div>
        </div>
    </div>

    <!-- Chart 2: Enrollment Distribution (Doughnut) -->
    <div class="card" style="margin-bottom:0;display:flex;flex-direction:column;">
        <div class="card__header">
            <div>
                <span class="card__title">
                    <i class="fa-solid fa-chart-pie"></i>
                    Enrollment Status
                </span>
                <span style="font-size:.75rem;color:var(--clr-gray-500);display:block;margin-top:.2rem;">
                    Progression breakdown of all registered students
                </span>
            </div>
        </div>
        <div style="flex:1;min-height:200px;display:flex;align-items:center;justify-content:center;position:relative;padding:.5rem 0;">
            <div style="width:180px;height:180px;position:relative;">
                <canvas id="enrollmentStatusChart"></canvas>
            </div>
        </div>
        <!-- Custom Legend with KPIs -->
        <div style="border-top:1px solid var(--clr-gray-100);padding-top:.875rem;margin-top:.5rem;display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem;text-align:center;">
            <div>
                <div style="font-size:.7rem;color:var(--clr-gray-500);font-weight:600;display:flex;align-items:center;justify-content:center;gap:.3rem;">
                    <span style="width:8px;height:8px;border-radius:50%;background:#10b981;display:inline-block;"></span> Completed
                </div>
                <div style="font-size:1.1rem;font-weight:700;color:var(--clr-gray-800);margin-top:.2rem;"><?= $enrCompleted ?></div>
            </div>
            <div>
                <div style="font-size:.7rem;color:var(--clr-gray-500);font-weight:600;display:flex;align-items:center;justify-content:center;gap:.3rem;">
                    <span style="width:8px;height:8px;border-radius:50%;background:#f59e0b;display:inline-block;"></span> In Progress
                </div>
                <div style="font-size:1.1rem;font-weight:700;color:var(--clr-gray-800);margin-top:.2rem;"><?= $enrInProgress ?></div>
            </div>
            <div>
                <div style="font-size:.7rem;color:var(--clr-gray-500);font-weight:600;display:flex;align-items:center;justify-content:center;gap:.3rem;">
                    <span style="width:8px;height:8px;border-radius:50%;background:#0ea5e9;display:inline-block;"></span> Enrolled
                </div>
                <div style="font-size:1.1rem;font-weight:700;color:var(--clr-gray-800);margin-top:.2rem;"><?= $enrEnrolled ?></div>
            </div>
        </div>
    </div>

</div>

<!-- Row 3: Quick Actions + Top Demand Courses -->
<div class="two-col-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">

    <!-- Quick actions (FR-54) -->
    <div class="card" style="margin-bottom:0;">
        <div class="card__header">
            <span class="card__title">
                <i class="fa-solid fa-bolt"></i>
                Administrative Shortcuts
            </span>
        </div>
        <div class="quick-actions">
            <a href="/admin/courses/form.php" class="quick-action-card">
                <i class="fa-solid fa-circle-plus"></i>
                <span>New Course</span>
            </a>
            <a href="/admin/participants/form.php" class="quick-action-card">
                <i class="fa-solid fa-user-plus"></i>
                <span>New Participant</span>
            </a>
            <a href="/admin/enrollments/index.php" class="quick-action-card">
                <i class="fa-solid fa-clipboard-list"></i>
                <span>Enrollments</span>
            </a>
            <a href="/admin/certificates/index.php" class="quick-action-card">
                <i class="fa-solid fa-folder-open"></i>
                <span>Archive</span>
            </a>
            <a href="/verify.php" class="quick-action-card">
                <i class="fa-solid fa-magnifying-glass"></i>
                <span>Verify Credential</span>
            </a>
        </div>
    </div>

    <!-- Top Performing Courses -->
    <div class="card" style="margin-bottom:0;">
        <div class="card__header">
            <span class="card__title">
                <i class="fa-solid fa-fire"></i>
                Top Enrolled Programs
            </span>
            <a href="/admin/courses/index.php" class="btn btn--outline btn--sm">
                <i class="fa-solid fa-arrow-right"></i> View All
            </a>
        </div>
        <div style="display:flex;flex-direction:column;gap:.9rem;">
            <?php
            $topCourses = $stats['course_distribution'] ?? [];
            $maxEnrollments = !empty($topCourses) ? max(array_column($topCourses, 'count')) : 1;
            if ($maxEnrollments <= 0) $maxEnrollments = 1;
            ?>
            <?php foreach ($topCourses as $tc): ?>
                <?php $pct = round(($tc['count'] / $maxEnrollments) * 100); ?>
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.35rem;">
                        <span style="font-size:.8rem;font-weight:600;color:var(--clr-gray-700);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:280px;" title="<?= h($tc['course']) ?>">
                            <i class="fa-solid fa-graduation-cap" style="color:var(--clr-primary);margin-right:.3rem;"></i>
                            <?= h($tc['course']) ?>
                        </span>
                        <span class="badge badge--info" style="font-size:.68rem;">
                            <?= h($tc['count']) ?> enrolled
                        </span>
                    </div>
                    <div class="progress-bar-wrap">
                        <div class="progress-bar-fill" style="width:<?= $pct ?>%;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<!-- Recent activity (FR-53) -->
<div class="card">
    <div class="card__header">
        <span class="card__title">
            <i class="fa-solid fa-clock-rotate-left"></i>
            Recent Operational Activity
        </span>
        <?php if (!empty($stats['recent_activity'])): ?>
        <span style="font-size:.72rem;color:var(--clr-gray-500);font-weight:500;">
            Showing last <?= count($stats['recent_activity']) ?> authenticated events
        </span>
        <?php endif; ?>
    </div>
    <?php if (empty($stats['recent_activity'])): ?>
        <div class="empty-state">
            <i class="fa-solid fa-inbox"></i>
            <p>No activity recorded yet. Start by creating a course or adding a participant.</p>
            <a href="/admin/courses/form.php" class="btn btn--primary btn--sm">
                <i class="fa-solid fa-circle-plus"></i> New Course
            </a>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Event Type</th>
                        <th>Participant</th>
                        <th>Associated Course</th>
                        <th>Certificate Identifier</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['recent_activity'] as $row): ?>
                    <tr>
                        <td>
                            <?php if ($row['type'] === 'enrollment'): ?>
                                <span class="badge badge--info">
                                    <i class="fa-solid fa-clipboard-list"></i> Enrollment
                                </span>
                            <?php else: ?>
                                <span class="badge badge--success">
                                    <i class="fa-solid fa-award"></i> Certificate
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-600"><?= h($row['participant_name']) ?></td>
                        <td style="color:var(--clr-gray-600)"><?= h($row['course_title']) ?></td>
                        <td>
                            <?= $row['certificate_number']
                                ? '<code style="font-size:.75rem;color:var(--clr-primary);background:var(--clr-primary-lt);padding:.18rem .45rem;border-radius:.25rem;font-weight:600;">' . h($row['certificate_number']) . '</code>'
                                : '<span class="text-muted">—</span>' ?>
                        </td>
                        <td class="text-muted"><?= h(fmt_date($row['activity_date'], 'd M Y')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
@media (max-width: 900px) {
    .charts-grid { grid-template-columns: 1fr !important; }
    .two-col-grid { grid-template-columns: 1fr !important; }
}
</style>

<!-- Chart.js Initialization -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js not loaded from CDN, skipping canvas charts.');
        return;
    }

    // Chart 1: Line Area Chart for Certificate Issuance Velocity
    const ctxTrend = document.getElementById('issuanceTrendChart');
    if (ctxTrend) {
        const labels = <?= json_encode($monthlyLabels) ?>;
        const data   = <?= json_encode($monthlyData) ?>;

        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Issued Certificates',
                    data: data,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#4f46e5',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { family: 'Inter', size: 12, weight: 'bold' },
                        bodyFont: { family: 'Inter', size: 12 },
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' certificates issued';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Inter', size: 11 }, color: '#64748b' }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            font: { family: 'Inter', size: 11 },
                            color: '#64748b'
                        },
                        grid: { color: 'rgba(226, 232, 240, 0.8)' }
                    }
                }
            }
        });
    }

    // Chart 2: Doughnut Chart for Enrollment Status
    const ctxDonut = document.getElementById('enrollmentStatusChart');
    if (ctxDonut) {
        new Chart(ctxDonut, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'In Progress', 'Enrolled'],
                datasets: [{
                    data: [<?= $enrCompleted ?>, <?= $enrInProgress ?>, <?= $enrEnrolled ?>],
                    backgroundColor: ['#10b981', '#f59e0b', '#0ea5e9'],
                    borderWidth: 3,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { family: 'Inter', size: 12, weight: 'bold' },
                        bodyFont: { family: 'Inter', size: 12 },
                        padding: 10,
                        cornerRadius: 8
                    }
                }
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../../src/views/layout/footer.php'; ?>
