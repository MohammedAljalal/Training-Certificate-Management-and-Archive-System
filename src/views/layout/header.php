<?php
/**
 * Shared HTML header / sidebar layout opener.
 * NFR-19: Shared component via include.
 */

declare(strict_types=1);

if (!function_exists('h')) {
    require_once dirname(__DIR__, 3) . '/src/helpers.php';
}

$pageTitle = $pageTitle ?? 'TCMAS';
$userRole  = $userRole  ?? 'admin';
$userName  = $userName  ?? '';

// Generate avatar initials
$initials = '';
foreach (explode(' ', trim($userName)) as $word) {
    $initials .= strtoupper(mb_substr($word, 0, 1));
    if (strlen($initials) >= 2) break;
}
if (!$initials) $initials = 'U';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?> — Training Certificate System</title>
    <!-- Font Awesome 6 Free CDN — Real icons (not emoji) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
          crossorigin="anonymous" referrerpolicy="no-referrer">
    <!-- Chart.js for modern dashboard analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="app-wrapper">

    <!-- Mobile overlay -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar__logo">
            <div class="sidebar__logo-icon">
                <i class="fa-solid fa-certificate"></i>
            </div>
            <div class="sidebar__logo-text">
                <strong>TCMAS</strong>
                <span><?= $userRole === 'admin' ? 'Admin Portal' : 'Participant Portal' ?></span>
            </div>
        </div>

        <?php if ($userRole === 'admin'): ?>
            <?php include __DIR__ . '/nav_admin.php'; ?>
        <?php else: ?>
            <?php include __DIR__ . '/nav_participant.php'; ?>
        <?php endif; ?>

        <div class="sidebar__footer">
            <i class="fa-solid fa-graduation-cap"></i>
            CIT1303 — Advanced Web Technology
        </div>
    </aside>

    <!-- Main content -->
    <div class="main-content">

        <!-- Top bar -->
        <header class="topbar">
            <div class="topbar__left">
                <button class="menu-toggle" id="menu-toggle" aria-label="Toggle menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div style="display:flex;align-items:center;gap:.5rem;">
                    <span class="topbar__title"><?= h($pageTitle) ?></span>
                </div>
            </div>
            <div class="topbar__right">
                <div class="topbar__user-info">
                    <div class="topbar__user-avatar"><?= h($initials) ?></div>
                    <span><?= h($userName) ?></span>
                    <span class="badge badge--<?= $userRole === 'admin' ? 'purple' : 'success' ?>">
                        <i class="fa-solid fa-<?= $userRole === 'admin' ? 'shield-halved' : 'user' ?>"></i>
                        <?= h(ucfirst($userRole)) ?>
                    </span>
                </div>
                <a href="/logout.php" class="btn btn--outline btn--sm" title="Sign out">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span class="d-none-sm">Logout</span>
                </a>
            </div>
        </header>

        <!-- Flash messages -->
        <div style="padding:0 1.75rem;">
            <?= render_flash() ?>
        </div>

        <!-- Page body -->
        <main class="page-body">
