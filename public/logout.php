<?php
/**
 * Logout — FR-06, NFR-05
 * Clears session data, destroys session, expires cookie, redirects to login.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/helpers.php';

session_boot();

// 1. Clear all session data
session_unset();

// 2. Destroy the server-side session
session_destroy();

// 3. Expire the session cookie (overwrite with past expiry)
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// 4. Redirect to login — no session ID regeneration on logout (NFR-05)
header('Location: /login.php');
exit;
