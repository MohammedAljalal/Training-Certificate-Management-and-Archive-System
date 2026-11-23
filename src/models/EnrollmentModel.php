<?php
/**
 * EnrollmentModel — Data access for enrollment records
 * Training Certificate Management and Archive System
 *
 * NFR-01: All queries use PDO prepared statements.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';

class EnrollmentModel
{
    private PDO $pdo;

    // Valid status transitions (forward-only: FR-28)
    public const STATUS_ORDER = ['Enrolled', 'In Progress', 'Completed'];

    public function __construct()
    {
        $this->pdo = get_pdo();
    }

    // ── Retrieval ────────────────────────────────────────────────────────────

    /** Find an enrollment by ID. */
    public function find(int $id): array|false
    {
        $sql = '
            SELECT e.*,
                   u.full_name  AS participant_name,
                   u.email      AS participant_email,
                   c.title      AS course_title,
                   c.start_date AS course_start,
                   c.end_date   AS course_end,
                   cert.id      AS certificate_id,
                   cert.certificate_number
            FROM enrollments e
            JOIN users u    ON u.id = e.user_id
            JOIN courses c  ON c.id = e.course_id
            LEFT JOIN certificates cert ON cert.enrollment_id = e.id
            WHERE e.id = :id
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /** List enrollments for a specific course. (FR-31) */
    public function listByCourse(int $courseId): array
    {
        $sql = '
            SELECT e.*,
                   u.full_name AS participant_name,
                   u.email     AS participant_email,
                   cert.id     AS certificate_id,
                   cert.certificate_number,
                   cert.status AS cert_status
            FROM enrollments e
            JOIN users u ON u.id = e.user_id
            LEFT JOIN certificates cert ON cert.enrollment_id = e.id
            WHERE e.course_id = :course_id
            ORDER BY e.created_at DESC
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':course_id' => $courseId]);
        return $stmt->fetchAll();
    }

    /** List enrollments for a participant (participant dashboard). (FR-57) */
    public function listByParticipant(int $userId): array
    {
        $sql = '
            SELECT e.*,
                   c.title      AS course_title,
                   c.start_date AS course_start,
                   c.end_date   AS course_end,
                   cert.id      AS certificate_id,
                   cert.certificate_number,
                   cert.status  AS cert_status,
                   cert.issued_at
            FROM enrollments e
            JOIN courses c ON c.id = e.course_id
            LEFT JOIN certificates cert ON cert.enrollment_id = e.id
            WHERE e.user_id = :user_id
            ORDER BY e.created_at DESC
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /** Check if a participant is already enrolled in a course. (BR-01) */
    public function exists(int $userId, int $courseId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM enrollments WHERE user_id = :uid AND course_id = :cid'
        );
        $stmt->execute([':uid' => $userId, ':cid' => $courseId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    // ── Mutations ────────────────────────────────────────────────────────────

    /** Create a new enrollment. Returns new enrollment ID. (FR-25, FR-27) */
    public function create(int $userId, int $courseId): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO enrollments (user_id, course_id, status) VALUES (:uid, :cid, 'Enrolled')"
        );
        $stmt->execute([':uid' => $userId, ':cid' => $courseId]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update enrollment status (forward-only: FR-28).
     * Sets completion_date when transitioning to Completed. (FR-29)
     * Returns true on success, false if transition is invalid.
     */
    public function updateStatus(int $id, string $newStatus, ?string $completionDate = null): bool
    {
        $enrollment = $this->find($id);
        if (!$enrollment) {
            return false;
        }

        // Enforce forward-only transition
        $currentIdx = array_search($enrollment['status'], self::STATUS_ORDER);
        $newIdx     = array_search($newStatus, self::STATUS_ORDER);
        if ($newIdx === false || $newIdx <= $currentIdx) {
            return false;
        }

        $sql = '
            UPDATE enrollments
               SET status          = :status,
                   completion_date = :completion_date
             WHERE id = :id
        ';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id'              => $id,
            ':status'          => $newStatus,
            ':completion_date' => ($newStatus === 'Completed') ? $completionDate : null,
        ]);
    }

    // ── Validation ───────────────────────────────────────────────────────────

    /**
     * Validate a completion date against the four BR-05 conditions.
     * Requires the course start and end dates.
     * Returns array of error messages (empty = valid).
     */
    public function validateCompletionDate(
        string $date,
        string $courseStart,
        string $courseEnd
    ): array {
        $errors = [];

        $ts = strtotime($date);
        if (!$ts) {
            $errors['completion_date'] = 'Completion date must be a valid date.';
            return $errors;
        }

        if ($ts > time()) {
            $errors['completion_date'] = 'Completion date cannot be in the future. (BR-05)';
        }
        if ($ts < strtotime($courseStart)) {
            $errors['completion_date'] = 'Completion date cannot be before the course start date. (BR-05)';
        }
        if ($ts > strtotime($courseEnd)) {
            $errors['completion_date'] = 'Completion date cannot be after the course end date. (BR-05)';
        }

        return $errors;
    }
}
