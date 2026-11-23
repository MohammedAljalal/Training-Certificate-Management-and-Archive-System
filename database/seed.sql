-- =============================================================================
-- Training Certificate Management and Archive System
-- Database Seed — Initial Data
-- =============================================================================
--
-- Document Version : 1.0
-- Date             : 2026-11-22
-- Target DBMS      : MariaDB 10.4.28
--
-- PURPOSE:
--   Creates the initial administrator account required by NFR-29 and BR-25.
--   The schema (database/schema.sql) must be executed BEFORE this file.
--
-- USAGE:
--   mysql -u root -p tcmas < database/seed.sql
--
-- CREDENTIALS (change before production use):
--   Email    : admin@tcmas.local
--   Password : Admin@1234
--   Hash     : bcrypt generated via password_hash('Admin@1234', PASSWORD_BCRYPT)
--
-- WARNING: Change the administrator password immediately after first login.
-- This seed file is for development and initial deployment only.
-- =============================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- Initial Administrator Account
-- BR-25: Administrator accounts are provisioned exclusively through the
--        initial seed SQL script. No in-application UI exists for creating
--        administrator accounts.
-- NFR-04: Passwords stored as bcrypt hashes only. Plain text never stored.
-- ---------------------------------------------------------------------------

INSERT INTO `users`
    (`full_name`, `email`, `password`, `role`, `status`)
VALUES
    (
        'System Administrator',
        'admin@tcmas.local',
        '$2y$10$7.1HDKTJ3tFEMik4gd.lxO6jgZQSHxRRDAnEFOq5tbnUBdJbk/rHS',
        'admin',
        'Active'
    );

-- =============================================================================
-- End of seed.sql
-- =============================================================================
