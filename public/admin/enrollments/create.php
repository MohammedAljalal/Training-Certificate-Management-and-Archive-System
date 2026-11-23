<?php
/**
 * Enrollment Create — FR-25, FR-26, FR-27
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../src/helpers.php';

session_boot();
require_admin();

require_once __DIR__ . '/../../../src/models/EnrollmentModel.php';
require_once __DIR__ . '/../../../src/models/CourseModel.php';
require_once __DIR__ . '/../../../src/models/ParticipantModel.php';

$cModel = new CourseModel();
$pModel = new ParticipantModel();
$eModel = new EnrollmentModel();

$courses      = $cModel->listActive();
$participants = $pModel->listActive();

// Pre-select if coming from course/participant page
$preUserId   = (int)($_GET['user_id']   ?? 0);
$preCourseId = (int)($_GET['course_id'] ?? 0);

$errors = [];
$data   = ['user_id' => $preUserId, 'course_id' => $preCourseId];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $data     = $_POST;
    $userId   = (int)($data['user_id']   ?? 0);
    $courseId = (int)($data['course_id'] ?? 0);

    if (!$userId)   $errors['user_id']   = 'Please select a participant.';
    if (!$courseId) $errors['course_id'] = 'Please select a course.';

    if (empty($errors)) {
        // FR-26: reject duplicate enrollment (BR-01)
        if ($eModel->exists($userId, $courseId)) {
            $errors['user_id'] = 'This participant is already enrolled in the selected course. (BR-01)';
        }
    }

    if (empty($errors)) {
        $newId = $eModel->create($userId, $courseId);
        set_flash('success', 'Participant enrolled successfully.');
        redirect("/admin/courses/view.php?id=$courseId");
    }
}

$pageTitle = 'New Enrollment';
$userRole  = 'admin';
$userName  = $_SESSION['user_name'] ?? '';

include __DIR__ . '/../../../src/views/layout/header.php';
?>

<div class="card" style="max-width:640px;margin:0 auto;">
    <div class="card__header">
        <span class="card__title">
            <i class="fa-solid fa-user-plus"></i>
            Enroll a Participant
        </span>
        <a href="/admin/courses/index.php" class="btn btn--outline btn--sm">
            <i class="fa-solid fa-arrow-left"></i> Courses
        </a>
    </div>

    <form method="POST" action="/admin/enrollments/create.php" novalidate>
        <?= csrf_field() ?>

        <div class="form-group">
            <label class="form-label" for="user_id">Participant <span class="required">*</span></label>
            <div class="input-group">
                <i class="fa-solid fa-user input-group__icon"></i>
                <select id="user_id" name="user_id"
                    class="form-control <?= isset($errors['user_id']) ? 'is-invalid' : '' ?>">
                    <option value="">— Select Participant —</option>
                    <?php foreach ($participants as $p): ?>
                        <option value="<?= h($p['id']) ?>" <?= (int)($data['user_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>>
                            <?= h($p['full_name']) ?> (<?= h($p['email']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (isset($errors['user_id'])): ?>
                <span class="form-error"><i class="fa-solid fa-circle-exclamation"></i> <?= h($errors['user_id']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="course_id">Course <span class="required">*</span></label>
            <div class="input-group">
                <i class="fa-solid fa-book-open input-group__icon"></i>
                <select id="course_id" name="course_id"
                    class="form-control <?= isset($errors['course_id']) ? 'is-invalid' : '' ?>">
                    <option value="">— Select Course —</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= h($c['id']) ?>" <?= (int)($data['course_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
                            <?= h($c['title']) ?> (<?= h(fmt_date($c['start_date'])) ?> → <?= h(fmt_date($c['end_date'])) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (isset($errors['course_id'])): ?>
                <span class="form-error"><i class="fa-solid fa-circle-exclamation"></i> <?= h($errors['course_id']) ?></span>
            <?php endif; ?>
        </div>

        <div class="d-flex gap-2 mt-2">
            <button type="submit" class="btn btn--primary">
                <i class="fa-solid fa-check"></i> Enroll Participant
            </button>
            <a href="/admin/courses/index.php" class="btn btn--outline">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../../src/views/layout/footer.php'; ?>
