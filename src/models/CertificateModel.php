<?php
/**
 * CertificateModel — Data access for certificate records
 * Training Certificate Management and Archive System
 *
 * NFR-01: All queries use PDO prepared statements.
 * BR-07: Certificates are never deleted, only revoked.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';

class CertificateModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = get_pdo();
    }

    // ── Retrieval ────────────────────────────────────────────────────────────

    /** Find a certificate by ID with full join data. */
    public function find(int $id): array|false
    {
        return $this->buildDetailQuery('cert.id = :param', $id);
    }

    /** Find a certificate by certificate_number. (Public search, FR-46) */
    public function findByNumber(string $number): array|false
    {
        return $this->buildDetailQuery('cert.certificate_number = :param', $number);
    }

    /** Find a certificate by enrollment_id. */
    public function findByEnrollment(int $enrollmentId): array|false
    {
        return $this->buildDetailQuery('cert.enrollment_id = :param', $enrollmentId);
    }

    /**
     * Public search: by certificate number (exact) or participant name (exact, case-insensitive).
     * Returns at most $limit results. (FR-46, FR-51-PV, ASM-03)
     * Returns only public-safe fields. (FR-48)
     */
    public function publicSearch(string $query, int $limit = 20): array
    {
        $sql = '
            SELECT cert.certificate_number,
                   cert.issued_at,
                   cert.status,
                   u.full_name       AS participant_name,
                   c.title           AS course_title,
                   e.completion_date
            FROM certificates cert
            JOIN enrollments e ON e.id = cert.enrollment_id
            JOIN users u       ON u.id = e.user_id
            JOIN courses c     ON c.id = e.course_id
            WHERE cert.certificate_number = :exact_num
               OR LOWER(u.full_name) = LOWER(:exact_name)
            ORDER BY cert.issued_at DESC
            LIMIT :limit
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':exact_num',  $query,  PDO::PARAM_STR);
        $stmt->bindValue(':exact_name', $query,  PDO::PARAM_STR);
        $stmt->bindValue(':limit',      $limit,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Count total certificates for archive pagination. */
    public function count(string $statusFilter = 'All'): int
    {
        $where = ($statusFilter !== 'All') ? "WHERE cert.status = :status" : '';
        $sql   = "SELECT COUNT(*) FROM certificates cert $where";
        $stmt  = $this->pdo->prepare($sql);
        if ($statusFilter !== 'All') {
            $stmt->bindValue(':status', $statusFilter);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Paginated certificate archive with filter and search. (FR-39, FR-44)
     */
    public function archive(
        int $limit,
        int $offset,
        string $statusFilter = 'All',
        string $search = ''
    ): array {
        $conditions = [];
        $params     = [];

        if ($statusFilter !== 'All') {
            $conditions[] = 'cert.status = :status';
            $params[':status'] = $statusFilter;
        }

        if ($search !== '') {
            $conditions[] = '(cert.certificate_number = :search OR LOWER(u.full_name) LIKE :search_like)';
            $params[':search']      = $search;
            $params[':search_like'] = '%' . strtolower($search) . '%';
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "
            SELECT cert.*,
                   u.full_name  AS participant_name,
                   c.title      AS course_title,
                   e.completion_date
            FROM certificates cert
            JOIN enrollments e ON e.id = cert.enrollment_id
            JOIN users u       ON u.id = e.user_id
            JOIN courses c     ON c.id = e.course_id
            $where
            ORDER BY cert.issued_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $params[':limit']  = $limit;
        $params[':offset'] = $offset;

        $stmt = $this->pdo->prepare($sql);
        // Bind ints explicitly for LIMIT/OFFSET
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            if (in_array($key, [':limit', ':offset'], true)) {
                continue;
            }
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** List certificates for a participant (their own only). (FR-58, FR-60) */
    public function listForParticipant(int $userId): array
    {
        $sql = '
            SELECT cert.*,
                   c.title      AS course_title,
                   e.completion_date
            FROM certificates cert
            JOIN enrollments e ON e.id = cert.enrollment_id
            JOIN courses c     ON c.id = e.course_id
            WHERE e.user_id = :user_id
            ORDER BY cert.issued_at DESC
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Verify that a certificate belongs to the given participant.
     * Used by the participant download controller. (FR-59, FR-60)
     */
    public function belongsToParticipant(int $certId, int $userId): bool
    {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*)
            FROM certificates cert
            JOIN enrollments e ON e.id = cert.enrollment_id
            WHERE cert.id = :cert_id AND e.user_id = :user_id
        ');
        $stmt->execute([':cert_id' => $certId, ':user_id' => $userId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    // ── Mutations ────────────────────────────────────────────────────────────

    /**
     * Insert a certificate record inside an open transaction.
     * Called from the issuance action after beginTransaction(). (BR-06)
     * Returns the new certificate ID.
     */
    public function insert(
        int $enrollmentId,
        string $certNumber,
        string $issuedAt,
        string $filePath
    ): int {
        $sql = '
            INSERT INTO certificates
                (enrollment_id, certificate_number, issued_at, file_path, status)
            VALUES
                (:enrollment_id, :certificate_number, :issued_at, :file_path, :status)
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':enrollment_id'     => $enrollmentId,
            ':certificate_number' => $certNumber,
            ':issued_at'         => $issuedAt,
            ':file_path'         => $filePath,
            ':status'            => 'Valid',
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Revoke a certificate. (FR-42, BR-07)
     * Records reason and timestamp. Never deletes the record.
     */
    public function revoke(int $id, string $reason): bool
    {
        $sql = '
            UPDATE certificates
               SET status            = :status,
                   revoked_at        = :revoked_at,
                   revocation_reason = :reason
             WHERE id = :id AND status = :current_status
        ';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id'             => $id,
            ':status'         => 'Revoked',
            ':revoked_at'     => date('Y-m-d H:i:s'),
            ':reason'         => $reason,
            ':current_status' => 'Valid',
        ]);
    }

    // ── Internal helpers ─────────────────────────────────────────────────────

    private function buildDetailQuery(string $condition, mixed $param): array|false
    {
        $sql = "
            SELECT cert.*,
                   u.full_name       AS participant_name,
                   u.email           AS participant_email,
                   c.title           AS course_title,
                   c.start_date      AS course_start,
                   c.end_date        AS course_end,
                   e.completion_date,
                   e.user_id         AS participant_id
            FROM certificates cert
            JOIN enrollments e ON e.id = cert.enrollment_id
            JOIN users u       ON u.id = e.user_id
            JOIN courses c     ON c.id = e.course_id
            WHERE $condition
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':param' => $param]);
        return $stmt->fetch();
    }
}
