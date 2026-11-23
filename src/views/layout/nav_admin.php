<?php
/**
 * Shared admin sidebar navigation.
 * NFR-15: Active page is highlighted.
 * NFR-19: Shared component via include.
 * Uses Font Awesome 6 real icons.
 */

declare(strict_types=1);

$path = $_SERVER['REQUEST_URI'];

function nav_active(string $pattern, string $current_path): string {
    return str_contains($current_path, $pattern) ? ' active' : '';
}
?>
<nav class="sidebar__nav">
    <div class="sidebar__section-label">Main Menu</div>

    <a href="/admin/dashboard.php" class="<?= nav_active('/admin/dashboard', $path) ?>">
        <i class="fa-solid fa-gauge-high nav-icon"></i>
        Dashboard
    </a>

    <div class="sidebar__section-label">Management</div>

    <a href="/admin/courses/index.php" class="<?= nav_active('/admin/courses', $path) ?>">
        <i class="fa-solid fa-book-open nav-icon"></i>
        Courses
    </a>
    <a href="/admin/participants/index.php" class="<?= nav_active('/admin/participants', $path) ?>">
        <i class="fa-solid fa-users nav-icon"></i>
        Participants
    </a>
    <a href="/admin/enrollments/index.php" class="<?= nav_active('/admin/enrollments', $path) ?>">
        <i class="fa-solid fa-clipboard-list nav-icon"></i>
        Enrollments
    </a>
    <a href="/admin/certificates/index.php" class="<?= nav_active('/admin/certificates', $path) ?>">
        <i class="fa-solid fa-award nav-icon"></i>
        Certificates
    </a>

    <div class="sidebar__section-label">Public</div>

    <a href="/verify.php" class="<?= nav_active('/verify', $path) ?>">
        <i class="fa-solid fa-magnifying-glass nav-icon"></i>
        Verify Certificate
    </a>
</nav>
