<?php
/**
 * DashboardModel — Statistics for the admin dashboard
 * Training Certificate Management and Archive System
 *
 * NFR-01: All queries use PDO prepared statements.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';

class DashboardModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = get_pdo();
    }

    /**
     * Return all summary statistics for the admin dashboard. (FR-52)
     *
     * @return array{
     *   active_courses: int,
     *   active_participants: int,
     *   total_enrollments: int,
     *   valid_certificates: int,
     *   revoked_certificates: int,
     *   recent_activity: array
     * }
     */
    public function getSummary(): array
    {
        return [
            'active_courses'        => $this->countActiveCourses(),
            'active_participants'   => $this->countActiveParticipants(),
            'total_enrollments'     => $this->countEnrollments(),
            'valid_certificates'    => $this->countCertificates('Valid'),
            'revoked_certificates'  => $this->countCertificates('Revoked'),
            'recent_activity'       => $this->getRecentActivity(),
            'monthly_issuance'      => $this->getMonthlyIssuanceStats(),
            'course_distribution'   => $this->getCourseDistributionStats(),
            'enrollment_breakdown'  => $this->getEnrollmentStatusStats(),
        ];
    }

    private function countActiveCourses(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM courses WHERE status = 'Active'");
        return (int)$stmt->fetchColumn();
    }

    private function countActiveParticipants(): int
    {
        $stmt = $this->pdo->query(
            "SELECT COUNT(*) FROM users WHERE role = 'participant' AND status = 'Active'"
        );
        return (int)$stmt->fetchColumn();
    }

    private function countEnrollments(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM enrollments');
        return (int)$stmt->fetchColumn();
    }

    private function countCertificates(string $status): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM certificates WHERE status = :status');
        $stmt->execute([':status' => $status]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Recent activity — last 10 enrollments and certificate issuances. (FR-53)
     */
    private function getRecentActivity(): array
    {
        $sql = '
            (
                SELECT
                    "enrollment" AS type,
                    e.created_at AS activity_date,
                    u.full_name  AS participant_name,
                    c.title      AS course_title,
                    NULL         AS certificate_number
                FROM enrollments e
                JOIN users u   ON u.id = e.user_id
                JOIN courses c ON c.id = e.course_id
                ORDER BY e.created_at DESC
                LIMIT 10
            )
            UNION ALL
            (
                SELECT
                    "certificate" AS type,
                    cert.created_at AS activity_date,
                    u.full_name     AS participant_name,
                    c.title         AS course_title,
                    cert.certificate_number
                FROM certificates cert
                JOIN enrollments e ON e.id = cert.enrollment_id
                JOIN users u       ON u.id = e.user_id
                JOIN courses c     ON c.id = e.course_id
                ORDER BY cert.created_at DESC
                LIMIT 10
            )
            ORDER BY activity_date DESC
            LIMIT 10
        ';
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Monthly certificate issuance statistics for charts.
     *
     * @return array<array{month: string, count: int}>
     */
    public function getMonthlyIssuanceStats(): array
    {
        $sql = "
            SELECT
                DATE_FORMAT(issued_at, '%b %Y') as month,
                DATE_FORMAT(issued_at, '%Y-%m') as sort_key,
                COUNT(*) as count
            FROM certificates
            WHERE status = 'Valid'
            GROUP BY month, sort_key
            ORDER BY sort_key ASC
            LIMIT 12
        ";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Top courses distribution by enrollments.
     *
     * @return array<array{course: string, count: int}>
     */
    public function getCourseDistributionStats(): array
    {
        $sql = "
            SELECT
                c.title as course,
                COUNT(e.id) as count
            FROM courses c
            LEFT JOIN enrollments e ON e.course_id = c.id
            GROUP BY c.id, c.title
            ORDER BY count DESC
            LIMIT 5
        ";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Enrollment status breakdown for funnel/donut chart.
     *
     * @return array<string, int>
     */
    public function getEnrollmentStatusStats(): array
    {
        $sql = "SELECT status, COUNT(*) as count FROM enrollments GROUP BY status";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
