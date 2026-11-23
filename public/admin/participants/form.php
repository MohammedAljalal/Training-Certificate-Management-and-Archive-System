<?php
/**
 * Participant Create / Edit Form — FR-17, FR-18, FR-19, FR-22, FR-24
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../src/helpers.php';

session_boot();
require_admin();

require_once __DIR__ . '/../../../src/models/ParticipantModel.php';

$model   = new ParticipantModel();
$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$participant = $id ? $model->find($id) : null;
$isEdit  = (bool)$participant;
$errors  = [];
$data    = $participant ?: ['full_name'=>'','email'=>'','phone'=>'','date_of_birth'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $data   = $_POST;
    $errors = $model->validate($data, !$isEdit, $isEdit ? $id : 0);

    if (empty($errors)) {
        if ($isEdit) {
            $model->update($id, $data);
            set_flash('success', 'Participant updated successfully.');
            redirect("/admin/participants/view.php?id=$id");
        } else {
            $newId = $model->create($data);
            set_flash('success', 'Participant account created. They can now log in.');
            redirect("/admin/participants/view.php?id=$newId");
        }
    }
}

$pageTitle = $isEdit ? 'Edit Participant' : 'New Participant';
$userRole  = 'admin';
$userName  = $_SESSION['user_name'] ?? '';

include __DIR__ . '/../../../src/views/layout/header.php';
?>

<div class="card" style="max-width:720px;margin:0 auto;">
    <div class="card__header">
        <span class="card__title">
            <i class="fa-solid fa-<?= $isEdit ? 'user-pen' : 'user-plus' ?>"></i>
            <?= h($pageTitle) ?>
        </span>
        <a href="/admin/participants/index.php" class="btn btn--outline btn--sm">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>

    <form method="POST" action="/admin/participants/form.php<?= $isEdit ? "?id=$id" : '' ?>" novalidate>
        <?= csrf_field() ?>

        <div class="form-group">
            <label class="form-label" for="full_name">Full Name <span class="required">*</span></label>
            <div class="input-group">
                <i class="fa-solid fa-user input-group__icon"></i>
                <input type="text" id="full_name" name="full_name"
                    class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                    value="<?= h($data['full_name'] ?? '') ?>"
                    maxlength="150" data-maxlength="150" required placeholder="Participant Full Name">
            </div>
            <?php if (isset($errors['full_name'])): ?>
                <span class="form-error"><i class="fa-solid fa-circle-exclamation"></i> <?= h($errors['full_name']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="email">Email Address <span class="required">*</span></label>
                <div class="input-group">
                    <i class="fa-solid fa-envelope input-group__icon"></i>
                    <input type="email" id="email" name="email"
                        class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                        value="<?= h($data['email'] ?? '') ?>" required placeholder="participant@example.com">
                </div>
                <?php if (isset($errors['email'])): ?>
                    <span class="form-error"><i class="fa-solid fa-circle-exclamation"></i> <?= h($errors['email']) ?></span>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="phone">Phone Number</label>
                <div class="input-group">
                    <i class="fa-solid fa-phone input-group__icon"></i>
                    <input type="tel" id="phone" name="phone"
                        class="form-control"
                        value="<?= h($data['phone'] ?? '') ?>"
                        placeholder="e.g. +966 50 000 0000">
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="date_of_birth">Date of Birth</label>
                <input type="date" id="date_of_birth" name="date_of_birth"
                    class="form-control <?= isset($errors['date_of_birth']) ? 'is-invalid' : '' ?>"
                    value="<?= h($data['date_of_birth'] ?? '') ?>"
                    max="<?= date('Y-m-d') ?>">
                <?php if (isset($errors['date_of_birth'])): ?>
                    <span class="form-error"><i class="fa-solid fa-circle-exclamation"></i> <?= h($errors['date_of_birth']) ?></span>
                <?php endif; ?>
                <span class="form-hint"><i class="fa-solid fa-circle-info"></i> Optional. Must be a past date.</span>
            </div>

            <?php if (!$isEdit): ?>
            <div class="form-group">
                <label class="form-label" for="password">Initial Password <span class="required">*</span></label>
                <div class="input-group">
                    <i class="fa-solid fa-lock input-group__icon"></i>
                    <input type="password" id="password" name="password"
                        class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                        autocomplete="new-password" placeholder="At least 8 characters">
                </div>
                <?php if (isset($errors['password'])): ?>
                    <span class="form-error"><i class="fa-solid fa-circle-exclamation"></i> <?= h($errors['password']) ?></span>
                <?php endif; ?>
                <span class="form-hint"><i class="fa-solid fa-shield-halved"></i> Minimum 8 characters. Stored as secure bcrypt hash.</span>
            </div>
            <?php endif; ?>
        </div>

        <div class="d-flex gap-2 mt-2">
            <button type="submit" class="btn btn--primary">
                <i class="fa-solid fa-<?= $isEdit ? 'floppy-disk' : 'user-plus' ?>"></i>
                <?= $isEdit ? 'Save Changes' : 'Create Participant' ?>
            </button>
            <a href="/admin/participants/index.php" class="btn btn--outline">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../../src/views/layout/footer.php'; ?>
