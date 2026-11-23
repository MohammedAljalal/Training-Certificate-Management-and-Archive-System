<?php
/**
 * Shared participant navigation.
 * NFR-15: Active page highlighted.
 * Uses Font Awesome 6 real icons.
 */

declare(strict_types=1);

$path = $_SERVER['REQUEST_URI'];

function pnav_active(string $pattern, string $current_path): string {
    return str_contains($current_path, $pattern) ? ' active' : '';
}
?>
<nav class="sidebar__nav">
    <div class="sidebar__section-label">My Portal</div>

    <a href="/participant/dashboard.php" class="<?= pnav_active('/participant/dashboard', $path) ?>">
        <i class="fa-solid fa-house nav-icon"></i>
        Dashboard
    </a>
    <a href="/participant/courses.php" class="<?= pnav_active('/participant/courses', $path) ?>">
        <i class="fa-solid fa-book-open nav-icon"></i>
        My Courses
    </a>
    <a href="/participant/certificates.php" class="<?= pnav_active('/participant/certificates', $path) ?>">
        <i class="fa-solid fa-award nav-icon"></i>
        My Certificates
    </a>

    <div class="sidebar__section-label">Tools</div>

    <a href="/verify.php" class="<?= pnav_active('/verify', $path) ?>">
        <i class="fa-solid fa-magnifying-glass nav-icon"></i>
        Verify Certificate
    </a>
</nav>
