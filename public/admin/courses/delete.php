<?php
/**
 * Course Delete Action — FR-14, BR-11
 * POST only. Redirects back to course list.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../src/helpers.php';

session_boot();
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/courses/index.php');
}

csrf_verify();

require_once __DIR__ . '/../../../src/models/CourseModel.php';

$id    = (int)($_POST['id'] ?? 0);
$model = new CourseModel();

if (!$id || !$model->find($id)) {
    set_flash('error', 'Course not found.');
    redirect('/admin/courses/index.php');
}

// FR-14 / BR-11: block deletion if enrollments exist
if ($model->delete($id)) {
    set_flash('success', 'Course deleted successfully.');
} else {
    set_flash('error', 'Cannot delete this course — it has existing enrollments. (FR-14)');
}

redirect('/admin/courses/index.php');
