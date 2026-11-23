<?php
/**
 * Update Enrollment Status — FR-28, FR-29, FR-30, BR-05
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../src/helpers.php';

session_boot();
require_admin();

require_once __DIR__ . '/../../../src/models/EnrollmentModel.php';

$model      = new EnrollmentModel();
$id         = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$enrollment = $model->find($id);

if (!$enrollment) {
    set_flash('error', 'Enrollment not found.');
    redirect('/admin/enrollments/index.php');
}

$errors = [];
$data   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $newStatus      = trim($_POST['status']          ?? '');
    $completionDate = trim($_POST['completion_date'] ?? '');
    $data           = $_POST;

    // Validate status is a known forward value
    if (!in_array($newStatus, EnrollmentModel::STATUS_ORDER, true)) {
        $errors['status'] = 'Invalid status value.';
    }

    // FR-29: completion date required when marking Completed
    if ($newStatus === 'Completed') {
        if (empty($completionDate)) {
            $errors['completion_date'] = 'Completion date is required when marking as Completed.';
        } else {
            $dateErrors = $model->validateCompletionDate(
                $completionDate,
                $enrollment['course_start'],
                $enrollment['course_end']
            );
            $errors = array_merge($errors, $dateErrors);
        }
    }

    // FR-30: prevent downgrade if certificate already issued
    if ($enrollment['certificate_id'] && empty($errors)) {
        $currentIdx = array_search($enrollment['status'], EnrollmentModel::STATUS_ORDER);
        $newIdx     = array_search($newStatus, EnrollmentModel::STATUS_ORDER);
        if ($newIdx < $currentIdx) {
            $errors['status'] = 'Cannot downgrade status — a certificate has already been issued for this enrollment. (FR-30)';
        }
    }

    if (empty($errors)) {
        $ok = $model->updateStatus($id, $newStatus, $completionDate ?: null);
        if ($ok) {
            set_flash('success', 'Enrollment status updated to "' . $newStatus . '".');
        } else {
            set_flash('error', 'Status update failed — invalid transition or no change made.');
        }
        redirect("/admin/courses/view.php?id={$enrollment['course_id']}");
    }
}

$pageTitle = 'Update Enrollment';
$userRole  = 'admin';
$userName  = $_SESSION['user_name'] ?? '';

include __DIR__ . '/../../../src/views/layout/header.php';
?>

<div class="card" style="max-width:620px;margin:0 auto;">
    <div class="card__header">
        <span class="card__title">
            <i class="fa-solid fa-pen-to-square"></i>
            Update Enrollment Status
        </span>
        <a href="/admin/courses/view.php?id=<?= h($enrollment['course_id']) ?>"
           class="btn btn--outline btn--sm">
            <i class="fa-solid fa-arrow-left"></i> Course
        </a>
    </div>

    <!-- Current info -->
    <div class="detail-list mb-2" style="background:var(--clr-gray-50);padding:1.25rem;border-radius:var(--radius);border:1px solid var(--clr-gray-200);">
        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-user"></i> Participant</div>
            <div class="detail-value fw-600"><?= h($enrollment['participant_name']) ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-book-open"></i> Course</div>
            <div class="detail-value"><?= h($enrollment['course_title']) ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-calendar"></i> Course Dates</div>
            <div class="detail-value"><?= h(fmt_date($enrollment['course_start'])) ?> → <?= h(fmt_date($enrollment['course_end'])) ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-circle-dot"></i> Current Status</div>
            <div class="detail-value">
                <?php $bmap = ['Enrolled'=>'enrolled','In Progress'=>'progress','Completed'=>'complete']; ?>
                <span class="badge badge--<?= $bmap[$enrollment['status']] ?? 'gray' ?>">
                    <?= h($enrollment['status']) ?>
                </span>
            </div>
        </div>
    </div>

    <form method="POST" action="/admin/enrollments/update.php" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= h($id) ?>">

        <div class="form-group">
            <label class="form-label" for="status">New Status <span class="required">*</span></label>
            <select id="status" name="status"
                class="form-control <?= isset($errors['status']) ? 'is-invalid' : '' ?>">
                <?php foreach (EnrollmentModel::STATUS_ORDER as $s): ?>
                    <option value="<?= h($s) ?>"
                        <?= ($data['status'] ?? $enrollment['status']) === $s ? 'selected' : '' ?>>
                        <?= h($s) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['status'])): ?>
                <span class="form-error"><i class="fa-solid fa-circle-exclamation"></i> <?= h($errors['status']) ?></span>
            <?php endif; ?>
            <span class="form-hint"><i class="fa-solid fa-arrow-right"></i> Transitions are forward-only: Enrolled → In Progress → Completed (FR-28)</span>
        </div>

        <div class="form-group" id="completion-date-group">
            <label class="form-label" for="completion_date">
                Completion Date <span class="required">*</span>
            </label>
            <input type="date" id="completion_date" name="completion_date"
                class="form-control <?= isset($errors['completion_date']) ? 'is-invalid' : '' ?>"
                value="<?= h($data['completion_date'] ?? $enrollment['completion_date'] ?? '') ?>"
                min="<?= h($enrollment['course_start']) ?>"
                max="<?= h(min($enrollment['course_end'], date('Y-m-d'))) ?>">
            <?php if (isset($errors['completion_date'])): ?>
                <span class="form-error"><i class="fa-solid fa-circle-exclamation"></i> <?= h($errors['completion_date']) ?></span>
            <?php endif; ?>
            <span class="form-hint"><i class="fa-solid fa-circle-info"></i> Must be between course dates and not in the future.</span>
        </div>

        <div class="d-flex gap-2 mt-2">
            <button type="submit" class="btn btn--primary">
                <i class="fa-solid fa-floppy-disk"></i> Update Status
            </button>
            <a href="/admin/courses/view.php?id=<?= h($enrollment['course_id']) ?>"
               class="btn btn--outline">Cancel</a>
        </div>
    </form>
</div>

<script>
const statusSel  = document.getElementById('status');
const cdGroup    = document.getElementById('completion-date-group');

function toggleCompletionDate() {
    cdGroup.style.display = statusSel.value === 'Completed' ? 'block' : 'none';
}

statusSel.addEventListener('change', toggleCompletionDate);
toggleCompletionDate();
</script>

<?php include __DIR__ . '/../../../src/views/layout/footer.php'; ?>
