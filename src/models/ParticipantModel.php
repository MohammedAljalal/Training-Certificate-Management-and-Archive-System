<?php
/**
 * ParticipantModel — Data access for participant accounts
 * Training Certificate Management and Archive System
 *
 * NFR-01: All queries use PDO prepared statements.
 * NFR-04: Passwords hashed with PASSWORD_BCRYPT.
 * NFR-18: Model separates data access from presentation.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';

class ParticipantModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = get_pdo();
    }

    // ── Retrieval ────────────────────────────────────────────────────────────

    /** Count all participants (for pagination). */
    public function count(): int
    {
        $stmt = $this->pdo->query(
            "SELECT COUNT(*) FROM users WHERE role = 'participant'"
        );
        return (int)$stmt->fetchColumn();
    }

    /** Paginated list of participants. */
    public function list(int $limit, int $offset): array
    {
        $sql = "
            SELECT id, full_name, email, phone, status, created_at
            FROM users
            WHERE role = 'participant'
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Find a participant by ID. Returns false if not found or if admin. */
    public function find(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM users WHERE id = :id AND role = 'participant'"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /** Find any user (admin or participant) by email — used for login. */
    public function findByEmail(string $email): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    /** Find participant with their full enrollment and certificate history. */
    public function findWithHistory(int $id): array|false
    {
        $participant = $this->find($id);
        if (!$participant) {
            return false;
        }

        // Enrollments with course info
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
        $stmt->execute([':user_id' => $id]);
        $participant['enrollments'] = $stmt->fetchAll();

        return $participant;
    }

    /** Count enrollments for a participant. */
    public function enrollmentCount(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM enrollments WHERE user_id = :id'
        );
        $stmt->execute([':id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    /** Count issued certificates for a participant (via enrollment join). */
    public function certificateCount(int $userId): int
    {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(cert.id)
            FROM certificates cert
            JOIN enrollments e ON e.id = cert.enrollment_id
            WHERE e.user_id = :user_id
        ');
        $stmt->execute([':user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    /** List all participants for enrollment dropdowns (active only). */
    public function listActive(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, full_name, email FROM users WHERE role = 'participant' AND status = 'Active' ORDER BY full_name"
        );
        return $stmt->fetchAll();
    }

    // ── Mutations ────────────────────────────────────────────────────────────

    /** Create a participant account. Returns new user ID. (FR-17, NFR-04) */
    public function create(array $data): int
    {
        $sql = '
            INSERT INTO users (full_name, email, password, role, phone, date_of_birth, status)
            VALUES (:full_name, :email, :password, :role, :phone, :dob, :status)
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':full_name' => $data['full_name'],
            ':email'     => $data['email'],
            ':password'  => password_hash($data['password'], PASSWORD_BCRYPT),
            ':role'      => 'participant',
            ':phone'     => $data['phone'] ?? null,
            ':dob'       => $data['date_of_birth'] ?? null,
            ':status'    => 'Active',
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Update participant profile fields. (FR-22) */
    public function update(int $id, array $data): bool
    {
        $sql = '
            UPDATE users
               SET full_name     = :full_name,
                   email         = :email,
                   phone         = :phone,
                   date_of_birth = :dob
             WHERE id = :id AND role = :role
        ';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id'        => $id,
            ':full_name' => $data['full_name'],
            ':email'     => $data['email'],
            ':phone'     => $data['phone'] ?? null,
            ':dob'       => $data['date_of_birth'] ?? null,
            ':role'      => 'participant',
        ]);
    }

    /** Set participant status to Inactive (soft-delete, tier 1). (BR-10) */
    public function deactivate(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET status = 'Inactive' WHERE id = :id AND role = 'participant'"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Hard-delete a participant. Only permitted when neither enrollments nor
     * certificates exist for this participant (BR-10 tier 3).
     * Returns true on success.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM users WHERE id = :id AND role = 'participant'"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /** Check whether an email is already in use by another user. */
    public function emailExists(string $email, int $excludeId = 0): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM users WHERE email = :email AND id != :exclude'
        );
        $stmt->execute([':email' => $email, ':exclude' => $excludeId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    // ── Validation ───────────────────────────────────────────────────────────

    /**
     * Validate participant create/edit form input.
     * $isCreate = true adds password field validation.
     * Returns array of error messages (empty = valid).
     */
    public function validate(array $data, bool $isCreate = true, int $editId = 0): array
    {
        $errors = [];

        $name = trim($data['full_name'] ?? '');
        if (empty($name)) {
            $errors['full_name'] = 'Full name is required.';
        } elseif (strlen($name) > 150) {
            $errors['full_name'] = 'Full name must not exceed 150 characters. (BR-21)';
        }

        $email = trim($data['email'] ?? '');
        if (empty($email)) {
            $errors['email'] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email address must be a valid RFC 5322 format.';
        } elseif ($this->emailExists($email, $editId)) {
            $errors['email'] = 'This email address is already registered. (BR-12)';
        }

        if ($isCreate) {
            $pass = $data['password'] ?? '';
            if (strlen($pass) < 8) {
                $errors['password'] = 'Password must be at least 8 characters.';
            }
        }

        $dob = trim($data['date_of_birth'] ?? '');
        if ($dob !== '' && (!strtotime($dob) || strtotime($dob) >= time())) {
            $errors['date_of_birth'] = 'Date of birth must be a past date.';
        }

        return $errors;
    }
}
