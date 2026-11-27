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
require_once __DIR__ . '/../../../src/certificate_pdf.php';

$id    = (int)($_GET['id'] ?? 0);
$model = new CertificateModel();
$cert  = $model->find($id);

if (!$cert) {
    http_response_code(404);
    exit('Certificate not found.');
}

serveCertificatePdf($cert);
