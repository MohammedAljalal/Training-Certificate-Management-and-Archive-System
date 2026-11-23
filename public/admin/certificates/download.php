<?php
/**
 * PDF Download Controller (Admin) — FR-41, NFR-07, NFR-09-SEC
 * Validates file path to prevent path traversal.
 * Serves the PDF through PHP — file is NOT web-accessible directly.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../src/helpers.php';

session_boot();
require_admin();

require_once __DIR__ . '/../../../src/models/CertificateModel.php';

$id    = (int)($_GET['id'] ?? 0);
$model = new CertificateModel();
$cert  = $model->find($id);

if (!$cert) {
    http_response_code(404);
    exit('Certificate not found.');
}

// NFR-07: validate resolved path begins with expected prefix
$baseDir  = realpath(APP_ROOT . '/storage/certificates/');
$filePath = realpath(APP_ROOT . '/' . ltrim($cert['file_path'], '/\\'));

if (!$filePath || !$baseDir || !str_starts_with($filePath, $baseDir)) {
    http_response_code(403);
    exit('403 Forbidden — invalid file path.');
}

if (!file_exists($filePath)) {
    http_response_code(404);
    exit('PDF file not found on server.');
}

// Serve PDF
$filename = basename($filePath);
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, no-cache');
readfile($filePath);
exit;
