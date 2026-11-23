<?php
/**
 * CourseModel — Data access for training courses
 * Training Certificate Management and Archive System
 *
 * NFR-01: All queries use PDO prepared statements.
 * NFR-18: Model class separates data access from presentation.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';

class CourseModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = get_pdo();
    }

    // ── Retrieval ────────────────────────────────────────────────────────────

    /** Count total courses (for pagination). */
    public function count(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM courses');
        return (int)$stmt->fetchColumn();
    }

    /** Paginated list of all courses with enrollment count. */
    public function list(int $limit, int $offset): array
    {
        $sql = '
            SELECT c.*,
                   COUNT(e.id) AS enrollment_count
            FROM courses c
            LEFT JOIN enrollments e ON e.course_id = c.id
            GROUP BY c.id
            ORDER BY c.created_at DESC
            LIMIT :limit OFFSET :offset
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Find a single course by ID. Returns false if not found. */
    public function find(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM courses WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /** Find a course with its enrollment list. */
    public function findWithEnrollments(int $id): array|false
    {
        $course = $this->find($id);
        if (!$course) {
            return false;
        }

        $sql = '
            SELECT e.*,
                   u.full_name AS participant_name,
                   u.email     AS participant_email
            FROM enrollments e
            JOIN users u ON u.id = e.user_id
            WHERE e.course_id = :course_id
            ORDER BY e.created_at DESC
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':course_id' => $id]);
        $course['enrollments'] = $stmt->fetchAll();
        return $course;
    }

    /** Count enrollments for a course (used before deletion check). */
    public function enrollmentCount(int $courseId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM enrollments WHERE course_id = :id'
        );
        $stmt->execute([':id' => $courseId]);
        return (int)$stmt->fetchColumn();
    }

    /** List active courses (for enrollment dropdowns). */
    public function listActive(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, title, start_date, end_date FROM courses WHERE status = 'Active' ORDER BY title"
        );
        return $stmt->fetchAll();
    }

    // ── Mutations ────────────────────────────────────────────────────────────

    /** Create a new course. Returns the new course ID. */
    public function create(array $data): int
    {
        $sql = '
            INSERT INTO courses (title, description, start_date, end_date, location, status)
            VALUES (:title, :description, :start_date, :end_date, :location, :status)
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':title'       => $data['title'],
            ':description' => $data['description'] ?? null,
            ':start_date'  => $data['start_date'],
            ':end_date'    => $data['end_date'],
            ':location'    => $data['location'] ?? null,
            ':status'      => $data['status'] ?? 'Active',
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Update an existing course. */
    public function update(int $id, array $data): bool
    {
        $sql = '
            UPDATE courses
               SET title       = :title,
                   description = :description,
                   start_date  = :start_date,
                   end_date    = :end_date,
                   location    = :location,
                   status      = :status
             WHERE id = :id
        ';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id'          => $id,
            ':title'       => $data['title'],
            ':description' => $data['description'] ?? null,
            ':start_date'  => $data['start_date'],
            ':end_date'    => $data['end_date'],
            ':location'    => $data['location'] ?? null,
            ':status'      => $data['status'] ?? 'Active',
        ]);
    }

    /**
     * Delete a course. Only permitted when no enrollments exist. (FR-14, BR-11)
     * Returns true on success, false if blocked by FK constraint.
     */
    public function delete(int $id): bool
    {
        if ($this->enrollmentCount($id) > 0) {
            return false;
        }
        $stmt = $this->pdo->prepare('DELETE FROM courses WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    // ── Validation ───────────────────────────────────────────────────────────

    /**
     * Validate course form input.
     * Returns an array of error messages (empty = valid).
     */
    public function validate(array $data): array
    {
        $errors = [];

        $title = trim($data['title'] ?? '');
        if (strlen($title) < 3) {
            $errors['title'] = 'Course title must be at least 3 characters.';
        } elseif (strlen($title) > 255) {
            $errors['title'] = 'Course title must not exceed 255 characters.';
        }

        $desc = trim($data['description'] ?? '');
        if (strlen($desc) > 2000) {
            $errors['description'] = 'Description must not exceed 2000 characters.';
        }

        $location = trim($data['location'] ?? '');
        if (strlen($location) > 255) {
            $errors['location'] = 'Location must not exceed 255 characters.';
        }

        $start = $data['start_date'] ?? '';
        if (!$start || !strtotime($start)) {
            $errors['start_date'] = 'Start date must be a valid date.';
        }

        $end = $data['end_date'] ?? '';
        if (!$end || !strtotime($end)) {
            $errors['end_date'] = 'End date must be a valid date.';
        } elseif (!empty($start) && strtotime($start) && strtotime($end) < strtotime($start)) {
            $errors['end_date'] = 'End date must be on or after the start date. (BR-18)';
        }

        return $errors;
    }
}
