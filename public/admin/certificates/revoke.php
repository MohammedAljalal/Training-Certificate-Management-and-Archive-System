<?php
/**
 * Certificate Revoke Action — FR-42, BR-07
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../src/helpers.php';

session_boot();
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/certificates/index.php');
}

csrf_verify();

require_once __DIR__ . '/../../../src/models/CertificateModel.php';

$id     = (int)($_POST['id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');
$model  = new CertificateModel();
$cert   = $model->find($id);

if (!$cert) {
    set_flash('error', 'Certificate not found.');
    redirect('/admin/certificates/index.php');
}

if ($cert['status'] !== 'Valid') {
    set_flash('error', 'Only Valid certificates can be revoked.');
    redirect("/admin/certificates/view.php?id=$id");
}

if (empty($reason)) {
    set_flash('error', 'Revocation reason is required. (BR-07, FR-42)');
    redirect("/admin/certificates/view.php?id=$id");
}

$model->revoke($id, $reason);
set_flash('success', 'Certificate revoked successfully.');
redirect("/admin/certificates/view.php?id=$id");
