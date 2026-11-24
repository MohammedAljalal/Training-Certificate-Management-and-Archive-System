<?php
/**
 * Core Helpers
 * Training Certificate Management and Archive System
 *
 * Provides: session management, CSRF protection, output escaping,
 * authentication guards, flash messages, and redirect utilities.
 *
 * NFR-02: All HTML output uses h() (htmlspecialchars wrapper).
 * NFR-03: CSRF token generated, stored, and validated here.
 * NFR-05: Session ID regenerated on login; destroyed on logout.
 */

declare(strict_types=1);

// ── Application root path (project root, one level above src/) ───────────────
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// ── Session bootstrap ────────────────────────────────────────────────────────

/**
 * Start the PHP session if not already started.
 * Sets secure cookie parameters.
 */
function session_boot(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

// ── Output escaping ──────────────────────────────────────────────────────────

/**
 * Escape a value for safe HTML output. (NFR-02)
 *
 * @param mixed $value
 * @return string
 */
function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ── CSRF protection ──────────────────────────────────────────────────────────

/**
 * Return the current CSRF token, generating one if necessary. (NFR-03)
 *
 * @return string
 */
function csrf_token(): string
{
    session_boot();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden CSRF input field for use in forms.
 *
 * @return string HTML input element
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

/**
 * Validate the CSRF token submitted with a POST request.
 * Terminates with HTTP 403 on failure. (NFR-03)
 */
function csrf_verify(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    $expected  = $_SESSION['csrf_token'] ?? '';

    if (!hash_equals($expected, $submitted)) {
        http_response_code(403);
        exit('403 Forbidden — invalid CSRF token.');
    }
}

// ── Authentication guards ────────────────────────────────────────────────────

/**
 * Check if a user is logged in.
 *
 * @return bool
 */
function is_logged_in(): bool
{
    session_boot();
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_role']);
}

/**
 * Require an authenticated session. Redirects to login if not logged in. (FR-07)
 */
function require_login(): void
{
    session_boot();
    if (!is_logged_in()) {
        redirect('/login.php');
    }
}

/**
 * Require the logged-in user to be an administrator. (FR-08, BR-14)
 * Redirects to participant dashboard with Access Denied if role mismatch.
 */
function require_admin(): void
{
    require_login();
    if ($_SESSION['user_role'] !== 'admin') {
        set_flash('error', 'Access Denied — Administrator access required.');
        redirect('/participant/dashboard.php');
    }
}

/**
 * Require the logged-in user to be a participant. (FR-08)
 * Redirects to admin dashboard with Access Denied if role mismatch.
 */
function require_participant(): void
{
    require_login();
    if ($_SESSION['user_role'] !== 'participant') {
        set_flash('error', 'Access Denied — Participant access required.');
        redirect('/admin/dashboard.php');
    }
}

/**
 * Return the logged-in user's ID from session.
 *
 * @return int|null
 */
function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Return the logged-in user's role from session.
 *
 * @return string|null 'admin' | 'participant' | null
 */
function current_user_role(): ?string
{
    return $_SESSION['user_role'] ?? null;
}

// ── Flash messages ───────────────────────────────────────────────────────────

/**
 * Store a flash message in the session.
 *
 * @param string $type  'success' | 'error' | 'warning' | 'info'
 * @param string $message
 */
function set_flash(string $type, string $message): void
{
    session_boot();
    $_SESSION['flash'][$type][] = $message;
}

/**
 * Retrieve and clear all flash messages of a given type.
 *
 * @param string $type
 * @return array
 */
function get_flash(string $type): array
{
    session_boot();
    $messages = $_SESSION['flash'][$type] ?? [];
    unset($_SESSION['flash'][$type]);
    return $messages;
}

/**
 * Render all flash messages as HTML alert divs.
 * Clears messages after rendering.
 *
 * @return string HTML
 */
function render_flash(): string
{
    $types = ['success', 'error', 'warning', 'info'];
    $html  = '';

    foreach ($types as $type) {
        foreach (get_flash($type) as $msg) {
            $html .= sprintf(
                '<div class="alert alert--%s" role="alert">%s</div>' . "\n",
                h($type),
                h($msg)
            );
        }
    }

    return $html;
}

// ── Redirect ─────────────────────────────────────────────────────────────────

/**
 * Redirect to a path relative to the public root and exit.
 *
 * @param string $path  e.g. '/admin/dashboard.php' or '/login.php'
 */
function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

// ── Pagination ───────────────────────────────────────────────────────────────

/**
 * Calculate pagination offset and return page metadata.
 *
 * @param int $total      Total record count
 * @param int $perPage    Records per page (default 25, per NFR-09)
 * @return array{page: int, perPage: int, offset: int, totalPages: int}
 */
function paginate(int $total, int $perPage = 25): array
{
    $page       = max(1, (int)($_GET['page'] ?? 1));
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page       = min($page, $totalPages);
    $offset     = ($page - 1) * $perPage;

    return [
        'page'       => $page,
        'perPage'    => $perPage,
        'offset'     => $offset,
        'totalPages' => $totalPages,
    ];
}

// ── Date helpers ─────────────────────────────────────────────────────────────

/**
 * Format a date string for display.
 *
 * @param string|null $date  MySQL DATE string
 * @param string $format     PHP date format (default: d M Y)
 * @return string
 */
function fmt_date(?string $date, string $format = 'd M Y'): string
{
    if (empty($date) || $date === '0000-00-00') {
        return '—';
    }
    return date($format, strtotime($date));
}
