<?php
/**
 * Custom Error Page — NFR-23
 * Displays a styled error page for HTTP 403, 404, and 500 errors.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/helpers.php';

$allowedCodes = [403, 404, 500];
$code = (int)($_GET['code'] ?? 404);
if (!in_array($code, $allowedCodes, true)) {
    $code = 404;
}

http_response_code($code);

$messages = [
    403 => [
        'icon'  => 'shield-halved',
        'title' => '403 — Access Forbidden',
        'body'  => 'You do not have permission to access this resource or page.'
    ],
    404 => [
        'icon'  => 'compass',
        'title' => '404 — Page Not Found',
        'body'  => 'The page you are looking for does not exist or has been moved.'
    ],
    500 => [
        'icon'  => 'triangle-exclamation',
        'title' => '500 — Internal Server Error',
        'body'  => 'An unexpected server error occurred. Please try again later.'
    ],
];

$info = $messages[$code];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($info['title']) ?> — TCMAS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
          crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;background:var(--clr-gray-100);padding:1.5rem;">
<div class="card text-center" style="max-width:480px;width:100%;padding:3rem 2rem;">
    <div style="width:72px;height:72px;background:var(--clr-primary-lt);color:var(--clr-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 1.5rem;">
        <i class="fa-solid fa-<?= h($info['icon']) ?>"></i>
    </div>
    <div style="font-size:3.5rem;font-weight:800;color:var(--clr-gray-800);line-height:1;margin-bottom:.5rem;">
        <?= h($code) ?>
    </div>
    <h1 style="font-size:1.25rem;font-weight:700;color:var(--clr-gray-800);margin-bottom:.75rem;">
        <?= h($info['title']) ?>
    </h1>
    <p class="text-muted mb-3" style="line-height:1.6;">
        <?= h($info['body']) ?>
    </p>
    <div class="d-flex gap-2" style="justify-content:center;">
        <a href="javascript:history.back()" class="btn btn--outline">
            <i class="fa-solid fa-arrow-left"></i> Go Back
        </a>
        <a href="/login.php" class="btn btn--primary">
            <i class="fa-solid fa-house"></i> Home
        </a>
    </div>
</div>
</body>
</html>
