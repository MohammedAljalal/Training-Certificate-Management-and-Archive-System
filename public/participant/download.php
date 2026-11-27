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
require_once __DIR__ . '/../../src/certificate_pdf.php';

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

serveCertificatePdf($cert);
