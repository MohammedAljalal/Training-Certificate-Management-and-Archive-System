<?php
/**
 * Course Create / Edit Form — FR-10, FR-13, FR-15
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../src/helpers.php';

session_boot();
require_admin();

require_once __DIR__ . '/../../../src/models/CourseModel.php';

$model  = new CourseModel();
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$course = $id ? $model->find($id) : null;
$isEdit = (bool)$course;
$errors = [];
$data   = $course ?: ['title'=>'','description'=>'','start_date'=>'','end_date'=>'','location'=>'','status'=>'Active'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $data   = $_POST;
    $errors = $model->validate($data);

    if (empty($errors)) {
        if ($isEdit) {
            $model->update($id, $data);
            set_flash('success', 'Course updated successfully.');
        } else {
            $newId = $model->create($data);
            set_flash('success', 'Course created successfully.');
            redirect("/admin/courses/view.php?id=$newId");
        }
        redirect('/admin/courses/index.php');
    }
}

$pageTitle = $isEdit ? 'Edit Course' : 'New Course';
$userRole  = 'admin';
$userName  = $_SESSION['user_name'] ?? '';

include __DIR__ . '/../../../src/views/layout/header.php';
?>

<div class="card" style="max-width:720px;margin:0 auto;">
    <div class="card__header">
        <span class="card__title">
            <i class="fa-solid fa-<?= $isEdit ? 'pen-to-square' : 'circle-plus' ?>"></i>
            <?= h($pageTitle) ?>
        </span>
        <a href="/admin/courses/index.php" class="btn btn--outline btn--sm">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>

    <form method="POST" action="/admin/courses/form.php<?= $isEdit ? "?id=$id" : '' ?>" novalidate>
        <?= csrf_field() ?>

        <div class="form-group">
            <label class="form-label" for="title">Course Title <span class="required">*</span></label>
            <input type="text" id="title" name="title"
                class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                value="<?= h($data['title'] ?? '') ?>"
                maxlength="255" data-maxlength="255" required placeholder="e.g. Advanced Web Development with PHP">
            <?php if (isset($errors['title'])): ?>
                <span class="form-error"><i class="fa-solid fa-circle-exclamation"></i> <?= h($errors['title']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Description</label>
            <textarea id="description" name="description"
                class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                rows="4" data-maxlength="2000" placeholder="Course outline, prerequisites, and learning objectives..."><?= h($data['description'] ?? '') ?></textarea>
            <?php if (isset($errors['description'])): ?>
                <span class="form-error"><i class="fa-solid fa-circle-exclamation"></i> <?= h($errors['description']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="start_date">Start Date <span class="required">*</span></label>
                <input type="date" id="start_date" name="start_date"
                    class="form-control <?= isset($errors['start_date']) ? 'is-invalid' : '' ?>"
                    value="<?= h($data['start_date'] ?? '') ?>" required>
                <?php if (isset($errors['start_date'])): ?>
                    <span class="form-error"><i class="fa-solid fa-circle-exclamation"></i> <?= h($errors['start_date']) ?></span>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="end_date">End Date <span class="required">*</span></label>
                <input type="date" id="end_date" name="end_date"
                    class="form-control <?= isset($errors['end_date']) ? 'is-invalid' : '' ?>"
                    value="<?= h($data['end_date'] ?? '') ?>" required>
                <?php if (isset($errors['end_date'])): ?>
                    <span class="form-error"><i class="fa-solid fa-circle-exclamation"></i> <?= h($errors['end_date']) ?></span>
                <?php endif; ?>
                <span class="form-hint"><i class="fa-solid fa-circle-info"></i> Must be on or after start date</span>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="location">Location</label>
                <input type="text" id="location" name="location"
                    class="form-control"
                    value="<?= h($data['location'] ?? '') ?>"
                    maxlength="255" placeholder="Lab A / Online / Main Hall">
            </div>
            <div class="form-group">
                <label class="form-label" for="status">Status <span class="required">*</span></label>
                <select id="status" name="status" class="form-control">
                    <option value="Active"   <?= ($data['status'] ?? '') === 'Active'   ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= ($data['status'] ?? '') === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>

        <div class="d-flex gap-2 mt-2">
            <button type="submit" class="btn btn--primary">
                <i class="fa-solid fa-<?= $isEdit ? 'floppy-disk' : 'plus' ?>"></i>
                <?= $isEdit ? 'Save Changes' : 'Create Course' ?>
            </button>
            <a href="/admin/courses/index.php" class="btn btn--outline">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../../src/views/layout/footer.php'; ?>
