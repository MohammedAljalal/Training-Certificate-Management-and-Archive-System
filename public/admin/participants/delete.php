<?php
/**
 * Participant Delete / Deactivate Action — FR-23, BR-10
 * Implements the 3-tier policy.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../src/helpers.php';

session_boot();
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/participants/index.php');
}

csrf_verify();

require_once __DIR__ . '/../../../src/models/ParticipantModel.php';

$id     = (int)($_POST['id']     ?? 0);
$action = trim($_POST['action']  ?? '');
$model  = new ParticipantModel();
$p      = $model->find($id);

if (!$p) {
    set_flash('error', 'Participant not found.');
    redirect('/admin/participants/index.php');
}

$certCount  = $model->certificateCount($id);
$enrollCount= $model->enrollmentCount($id);

if ($action === 'deactivate') {
    $model->deactivate($id);
    set_flash('success', 'Participant account deactivated (Inactive).');
    redirect("/admin/participants/view.php?id=$id");
}

if ($action === 'delete') {
    // Tier 1: has certs → never delete
    if ($certCount > 0) {
        set_flash('error', 'Cannot delete: participant has issued certificates. Account deactivated instead.');
        $model->deactivate($id);
        redirect("/admin/participants/view.php?id=$id");
    }

    // Tier 2: has enrollments but no certs → block
    if ($enrollCount > 0) {
        set_flash('error', 'Cannot delete: participant has enrollment history. Deactivate the account instead.');
        redirect("/admin/participants/view.php?id=$id");
    }

    // Tier 3: clean — hard delete
    $model->delete($id);
    set_flash('success', 'Participant account permanently deleted.');
    redirect('/admin/participants/index.php');
}

set_flash('error', 'Invalid action.');
redirect("/admin/participants/view.php?id=$id");
