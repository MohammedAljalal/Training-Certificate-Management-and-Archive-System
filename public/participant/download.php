<?php
/**
 * PDF Download Controller (Participant) — FR-59, FR-60, NFR-07, NFR-09-SEC
 * Verifies ownership before serving the file.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../src/helpers.php';

session_boot();
require_participant();

require_once __DIR__ . '/../../src/models/CertificateModel.php';

$certId = (int)($_GET['id'] ?? 0);
$userId = current_user_id();
$model  = new CertificateModel();
$cert   = $model->find($certId);

if (!$cert) {
    http_response_code(404);
    exit('Certificate not found.');
}

// FR-60: verify ownership — session user_id must match enrollment's user_id
if (!$model->belongsToParticipant($certId, $userId)) {
    http_response_code(403);
    exit('403 Forbidden — this certificate does not belong to your account.');
}

// NFR-07: validate resolved path begins with expected storage prefix
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

$filename = basename($filePath);
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, no-cache');
readfile($filePath);
exit;
