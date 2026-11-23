-- =============================================================================
-- Training Certificate Management and Archive System
-- Database Schema
-- =============================================================================
--
-- Document Version : 1.0
-- Date             : 2026-11-22
-- Target DBMS      : MariaDB 10.4.28 (XAMPP)
-- Character Set    : utf8mb4
-- Collation        : utf8mb4_unicode_ci
-- Engine           : InnoDB (all tables)
--
-- Requirements Baseline : v1.3
--   - docs/SRS.md                       v1.3
--   - docs/BUSINESS_RULES.md            v1.3
--   - docs/USE_CASES.md                 v1.3
--   - docs/REQUIREMENTS_TRACEABILITY.md v1.3
--
-- Design Documents (Approved for Implementation):
--   - docs/DATABASE_ERD.md              v1.0 (ERD-001)
--   - docs/DATA_DICTIONARY.md           v1.0 (DD-001)
--   - docs/DATABASE_DESIGN_DECISIONS.md v1.0 (DDD-001)
--
-- Usage:
--   mysql -u root -p tcmas < database/schema.sql
--   (Create the target database first; this file does not create or drop it.)
--
-- IMPORTANT: This file creates tables only. It does NOT insert any data,
-- seed records, administrator accounts, or test fixtures.
-- Seed data is maintained in a separate file: database/seed.sql
--
-- =============================================================================


-- ---------------------------------------------------------------------------
-- Session-level settings
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Disable FK checks during table creation to allow any declaration order.
-- Re-enabled at the end of the file.
SET FOREIGN_KEY_CHECKS = 0;

-- Use strict mode to catch truncation and type errors during development.
SET sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION';


-- =============================================================================
-- TABLE: users
-- =============================================================================
--
-- Stores all system accounts: Administrators and Participants.
-- Both roles share identical authentication attributes and use the same login
-- mechanism. The `role` column acts as a discriminator.
--
-- No public self-registration exists. Only Administrators may create
-- participant accounts (SRS §3.1, BR-13).
--
-- Related requirements: BR-12, BR-13, BR-20, BR-21, NFR-04, NFR-31
-- Related design decision: DDD-001 §1.2 (Single users table for two roles)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `users` (
    -- Primary key
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,

    -- Identity
    -- VARCHAR(150): BR-21 and FR-24 specify max 150 characters for full_name.
    -- Note: DATA_DICTIONARY v1.0 shows VARCHAR(100) but the requirements
    -- baseline (BR-21) is authoritative — 150 is the correct storage size.
    `full_name`     VARCHAR(150)    NOT NULL,

    -- Login credential — must be globally unique across all roles (BR-12, NFR-31)
    `email`         VARCHAR(255)    NOT NULL,

    -- Bcrypt hash only. Never plaintext. Generated via password_hash(..., PASSWORD_BCRYPT).
    -- VARCHAR(255) accommodates the 60-char bcrypt output with future-proof headroom (NFR-04).
    `password`      VARCHAR(255)    NOT NULL,

    -- Role discriminator. No default: the INSERT must always specify role explicitly.
    -- This prevents silent role-assignment errors (DDD-001 §1.2).
    `role`          ENUM('admin','participant') NOT NULL,

    -- Optional contact fields
    `phone`         VARCHAR(20)     NULL DEFAULT NULL,
    `date_of_birth` DATE            NULL DEFAULT NULL,

    -- Account operational status.
    -- 'Inactive' is set for soft-deleted participants (BR-10 tier 1).
    -- Administrators are never deactivated through the application UI.
    `status`        ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',

    -- Audit timestamps (managed automatically by MariaDB)
    `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Constraints
    PRIMARY KEY (`id`),

    -- Email uniqueness: login identity, duplicate-account prevention (BR-12, NFR-31)
    UNIQUE KEY `uq_users_email` (`email`),

    -- Additional indexes for common dashboard filter queries
    INDEX `idx_users_role`   (`role`),    -- filter participants vs admins
    INDEX `idx_users_status` (`status`)   -- filter active/inactive participants

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='All system accounts — administrators and participants (DDD-001 §1.2)';


-- =============================================================================
-- TABLE: courses
-- =============================================================================
--
-- Stores all training course definitions. Administrators create, edit, and
-- deactivate courses. A course cannot be hard-deleted once it has enrollments
-- (enforced by ON DELETE RESTRICT on enrollments.course_id — see below).
--
-- Related requirements: BR-11, BR-18, BR-19, FR-10, FR-11, FR-15
-- =============================================================================

CREATE TABLE IF NOT EXISTS `courses` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,

    -- BR-19: title is required, 3–255 characters. Min-length enforced at app level.
    `title`       VARCHAR(255)  NOT NULL,

    -- Optional description. Max 2000 chars enforced at application level (FR-15).
    `description` TEXT          NULL DEFAULT NULL,

    -- BR-18: end_date >= start_date enforced at application level.
    -- BR-05: start_date and end_date are used in completion_date validation.
    `start_date`  DATE          NOT NULL,
    `end_date`    DATE          NOT NULL,

    -- Optional venue information
    `location`    VARCHAR(255)  NULL DEFAULT NULL,

    -- Soft-disable: 'Inactive' hides the course from enrollment without deletion.
    `status`      ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',

    `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    INDEX `idx_courses_status` (`status`)   -- filter active courses for enrollment (FR-11)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Training course records. Deletion blocked when enrollments exist (BR-11).';


-- =============================================================================
-- TABLE: enrollments
-- =============================================================================
--
-- Bridges users (participants) and courses (many-to-many). Each row is one
-- participant's enrollment in one course. Tracks status progression through
-- three stages: Enrolled → In Progress → Completed.
--
-- This table is the central link in the data model:
--   - Certificates reference enrollments (one-to-one).
--   - Participant deletion rules evaluate enrollment existence (BR-10).
--   - Course deletion is blocked if enrollments exist (BR-11).
--
-- Related requirements: BR-01, BR-02, BR-05, BR-10, BR-11, NFR-27
-- =============================================================================

CREATE TABLE IF NOT EXISTS `enrollments` (
    `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,

    -- FK to users.id — the enrolled participant.
    -- Only role='participant' rows should be referenced (enforced at app level).
    -- ON DELETE RESTRICT: a participant cannot be hard-deleted while enrollment
    -- records exist (BR-10 tier 2).
    `user_id`         INT UNSIGNED  NOT NULL,

    -- FK to courses.id — the course being attended.
    -- ON DELETE RESTRICT: a course cannot be deleted while enrollments exist (BR-11).
    `course_id`       INT UNSIGNED  NOT NULL,

    -- Enrollment workflow status.
    -- Allowed forward-only transitions (FR-28): Enrolled → In Progress → Completed.
    -- Transition order is enforced at application level, not database level.
    -- 'Completed' is a prerequisite for certificate issuance (BR-02).
    `status`          ENUM('Enrolled','In Progress','Completed') NOT NULL DEFAULT 'Enrolled',

    -- Set when status = 'Completed'. Validated by four rules (BR-05):
    --   1. Must be a valid date (enforced by DATE type).
    --   2. Must be <= today's date (application level).
    --   3. Must be >= courses.start_date (application level).
    --   4. Must be <= courses.end_date (application level).
    -- NULL for enrollments not yet completed (FR-29).
    `completion_date` DATE          NULL DEFAULT NULL,

    `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Constraints
    PRIMARY KEY (`id`),

    -- Composite unique: one enrollment per participant per course (BR-01, NFR-27).
    UNIQUE KEY `uq_enrollments_user_course` (`user_id`, `course_id`),

    -- Additional index for status-based filtering (e.g., find eligible completions)
    INDEX `idx_enrollments_status` (`status`),

    -- Foreign keys
    -- ON UPDATE CASCADE: propagates primary key updates (belt-and-suspenders; PKs
    -- are immutable AUTO_INCREMENT integers, so this never fires in practice).
    CONSTRAINT `fk_enrollments_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT `fk_enrollments_course`
        FOREIGN KEY (`course_id`)
        REFERENCES `courses` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Participant-course enrollment records. Central link for certificates and deletion rules.';


-- =============================================================================
-- TABLE: certificates
-- =============================================================================
--
-- Stores all issued certificate records. Each certificate is linked one-to-one
-- with a completed enrollment. Certificate records are PERMANENT — they are
-- never deleted, only revoked (BR-07, BR-09).
--
-- Certificate issuance sequence (SRS §6.1, BR-04, BR-06):
--   1. Verify enrollment.status = 'Completed' and no existing certificate.
--   2. Generate candidate certificate_number via random_bytes() (PHP).
--   3. BEGIN TRANSACTION.
--   4. Render PDF to a temporary file using Dompdf.
--   5. INSERT this row (UNIQUE on certificate_number catches races).
--   6. Move temp PDF to storage/certificates/.
--   7. COMMIT.
--   8. On any failure: ROLLBACK and delete temp file.
--
-- Related requirements: BR-02, BR-03, BR-04, BR-06, BR-07, BR-09, NFR-25, NFR-26
-- =============================================================================

CREATE TABLE IF NOT EXISTS `certificates` (
    `id`                 INT UNSIGNED   NOT NULL AUTO_INCREMENT,

    -- One-to-one with enrollments. UNIQUE enforces one certificate per enrollment
    -- (BR-03, NFR-26). ON DELETE RESTRICT: certificate records must never be lost
    -- even if the linked enrollment row were somehow targeted for deletion (BR-09).
    `enrollment_id`      INT UNSIGNED   NOT NULL,

    -- Human-readable, globally unique certificate identifier.
    -- Format: CERT-YYYY-XXXXXXXX (13 chars typical; VARCHAR(30) gives margin).
    -- Generated by PHP random_bytes() before the DB transaction (BR-04).
    -- UNIQUE constraint is the definitive race-safe uniqueness guarantee (NFR-25).
    `certificate_number` VARCHAR(30)    NOT NULL,

    -- The calendar date the certificate was issued (displayed in FR-47).
    `issued_at`          DATE           NOT NULL,

    -- Server-side relative path to the generated PDF file.
    -- Example: storage/certificates/CERT-2026-ABCDEF12.pdf
    -- NEVER exposed directly to users. Download controller validates this path
    -- against the expected prefix to prevent path traversal (NFR-07).
    `file_path`          VARCHAR(500)   NOT NULL,

    -- Certificate lifecycle status.
    -- 'Valid'   — active, downloadable certificate.
    -- 'Revoked' — invalidated by administrator; retained permanently (BR-07).
    `status`             ENUM('Valid','Revoked') NOT NULL DEFAULT 'Valid',

    -- Revocation audit fields. NULL for 'Valid' certificates.
    -- revoked_at: set to NOW() at revocation time (BR-07).
    -- revocation_reason: mandatory non-empty text supplied by administrator (BR-07, FR-42).
    --   Required at application level; NULL is only valid when status = 'Valid'.
    `revoked_at`         TIMESTAMP      NULL DEFAULT NULL,
    `revocation_reason`  TEXT           NULL DEFAULT NULL,

    `created_at`         TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Constraints
    PRIMARY KEY (`id`),

    -- One certificate per enrollment (BR-03, NFR-26)
    UNIQUE KEY `uq_certificates_enrollment` (`enrollment_id`),

    -- Globally unique certificate number (BR-04, NFR-25)
    UNIQUE KEY `uq_certificates_number` (`certificate_number`),

    -- Additional index for archive and status filtering (FR-39, FR-44)
    INDEX `idx_certificates_status` (`status`),

    -- Foreign key
    CONSTRAINT `fk_certificates_enrollment`
        FOREIGN KEY (`enrollment_id`)
        REFERENCES `enrollments` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Issued certificate records. Permanent: never deleted, only revoked (BR-07, BR-09).';


-- ---------------------------------------------------------------------------
-- Re-enable foreign key checks
-- ---------------------------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 1;


-- =============================================================================
-- APPLICATION-LAYER ENFORCEMENT NOTES
-- =============================================================================
--
-- The following business rules cannot be fully enforced at the database layer
-- and MUST be enforced by PHP application code:
--
-- 1. ENROLLMENT STATUS TRANSITIONS (BR, FR-28)
--    Allowed: Enrolled → In Progress → Completed (forward-only).
--    The database accepts any ENUM value at any time. The application must
--    validate the transition before every UPDATE to enrollments.status.
--
-- 2. COMPLETION DATE VALIDATION (BR-05)
--    When setting status = 'Completed', the application must verify:
--      a) completion_date is a valid date (PHP Date validation).
--      b) completion_date <= today's date.
--      c) completion_date >= courses.start_date (requires JOIN query).
--      d) completion_date <= courses.end_date (requires JOIN query).
--
-- 3. THREE-TIER PARTICIPANT DELETION POLICY (BR-10)
--    Tier 1 (soft-delete / deactivate): Application sets users.status = 'Inactive'.
--              No DB enforcement; pure application logic.
--    Tier 2 (hard-delete, no enrollments): Application checks for enrollment rows
--              first. If none exist, DELETE is permitted.
--              DB enforcement: ON DELETE RESTRICT on enrollments.user_id blocks
--              hard-delete if rows exist.
--    Tier 3 (enrolled participant with certificate): Application prevents deletion
--              and offers only Tier 1 (deactivation).
--              DB enforcement: ON DELETE RESTRICT on certificates.enrollment_id
--              blocks enrollment deletion, which blocks user deletion.
--
-- 4. ROLE-BASED ACCESS CONTROL (SRS §3.1)
--    The database does not restrict which PHP code may read or write any table.
--    The application must check session role on every protected page and action.
--
-- 5. CERTIFICATE ISSUANCE ELIGIBILITY (BR-02, BR-03, BR-06)
--    Before calling BEGIN TRANSACTION for certificate issuance, the application
--    must verify:
--      a) enrollment.status = 'Completed'.
--      b) No certificate row exists for this enrollment_id (SELECT FOR UPDATE).
--    The UNIQUE constraint on enrollment_id is the final race-safe guard.
--
-- 6. REVOCATION REASON REQUIRED (BR-07, FR-42)
--    When an administrator revokes a certificate, the application must require
--    revocation_reason to be non-empty before updating the row.
--    The DB column is nullable (NULL = certificate is Valid).
--
-- 7. ADMINISTRATOR ACCOUNTS (BR-13, SRS §3.1)
--    No public registration form exists. The initial admin account must be
--    created via the seed SQL file (database/seed.sql), which is a separate
--    deliverable from this schema file.
--
-- =============================================================================
-- End of schema.sql — Training Certificate Management and Archive System v1.0
-- =============================================================================
