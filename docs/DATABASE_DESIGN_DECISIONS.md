# Database Design Decisions
## Training Certificate Management and Archive System

| Field | Value |
|---|---|
| Document ID | DDD-001 |
| Version | 1.0 |
| Related SRS | SRS-001 v1.3 |
| Related BR | BR-001 v1.3 |
| Related ERD | ERD-001 v1.0 |
| Related DD | DD-001 v1.0 |
| Date | 2026-11-22 |
| Status | Approved for Implementation |

---

## Table of Contents

1. [Table Structure Decisions](#1-table-structure-decisions)
2. [Relationship Decisions](#2-relationship-decisions)
3. [Normalization Approach](#3-normalization-approach)
4. [Historical Record Preservation](#4-historical-record-preservation)
5. [Participant Deletion Rule Representation](#5-participant-deletion-rule-representation)
6. [Certificate Issuance and Revocation Representation](#6-certificate-issuance-and-revocation-representation)
7. [Concurrency and Duplicate Protection](#7-concurrency-and-duplicate-protection)
8. [Index Strategy](#8-index-strategy)
9. [Requirements Not Directly Representable in Database](#9-requirements-not-directly-representable-in-database)
10. [Validation Checklist](#10-validation-checklist)

---

## 1. Table Structure Decisions

### 1.1 Why Four Tables (Not More, Not Fewer)

The minimum required by the university assignment is three related tables. The design produces four: `users`, `courses`, `enrollments`, `certificates`. This is the minimum necessary to represent all required functionality without data duplication or logical compromise.

| Table | Why It Exists |
|---|---|
| `users` | Stores all account holders — both Administrators and Participants. Authentication credentials and profile data are stored here. No separate "trainees" or "admins" tables are needed. |
| `courses` | Stores training course definitions. Courses are independent entities that exist before and after participant enrollment. |
| `enrollments` | Bridges users and courses. Stores status, progress, and completion dates. This is the participation record — the fact that a specific person attended a specific course. |
| `certificates` | Stores issued certificate records. Linked to a specific enrollment. Contains the certificate number, PDF path, status, and revocation data. Certificate records are permanent. |

No additional tables (e.g. `roles`, `permissions`, `audit_logs`, `settings`) are introduced. These are not required by the approved functional requirements and would add unnecessary complexity for a university-scoped project.

### 1.2 Single `users` Table for Two Roles

**Decision:** Both Administrators and Participants are stored in a single `users` table with a `role` ENUM column.

**Alternatives Considered:**

| Alternative | Reason Rejected |
|---|---|
| Separate `admins` and `participants` tables | Creates duplicate authentication logic, complicates login queries, violates DRY. Both roles use identical authentication. |
| `users` + `roles` junction table | Overkill for two static, mutually exclusive roles. A many-to-many roles system is not required by any approved requirement. |
| `users` + `user_profiles` extension table | Participant-specific optional fields (`phone`, `date_of_birth`) are sparse but structurally identical to what an admin might also carry. No join benefits outweigh the added complexity. |

**Justification:** The two roles are structurally identical at the data model level. They differ only in what the application permits them to do, which is an application-layer concern (session role check), not a database design concern. A single table with a `role` discriminator is clean, normalized, and correctly models the domain.

**Constraint note:** The `role` ENUM has no default value. The INSERT statement must always specify the role explicitly. This prevents silent role assignment errors.

### 1.3 `full_name` as a Single Column (Not `first_name` / `last_name`)

**Decision:** `full_name VARCHAR(100)` stores the participant's complete name as a single field.

**Justification:**
- The SRS specifies "participant full name" (FR-17, FR-21, FR-35, FR-47). No requirement exists for separate first/last name fields.
- The certificate PDF displays "participant's full name" (FR-35).
- Public verification returns "participant full name" (FR-47).
- Splitting into `first_name`/`last_name` would require concatenation on every output and complicate case-insensitive name matching in public search (ASM-03).
- Many international names do not conform cleanly to a first/last split.

### 1.4 `file_path` Stored as Server-Side Path

**Decision:** `certificates.file_path VARCHAR(500)` stores the server-side path to the PDF, not a URL.

**Justification:**
- PDF files are stored outside the web root (FR-36, NFR-09-SEC). They have no public URL.
- The PHP download controller resolves the path and validates it against the expected prefix to prevent path traversal (NFR-07).
- Storing the path (rather than just the filename) decouples the database from assumptions about directory structure on different hosting environments.

---

## 2. Relationship Decisions

### 2.1 `enrollments.user_id` → `users.id` ON DELETE RESTRICT

**Decision:** Foreign key with RESTRICT behavior.

**Justification:**
- BR-10 tier 2: A participant with enrollment records cannot be hard-deleted.
- The database RESTRICT constraint enforces this at the data layer independently of application code.
- If the application were to have a bug allowing a deletion attempt, the database would block it, preventing orphaned enrollment records.

### 2.2 `enrollments.course_id` → `courses.id` ON DELETE RESTRICT

**Decision:** Foreign key with RESTRICT behavior.

**Justification:**
- BR-11: A course with enrollment records cannot be deleted.
- Same rationale as above — the database enforces the business rule at the storage layer.

### 2.3 `certificates.enrollment_id` → `enrollments.id` ON DELETE RESTRICT

**Decision:** Foreign key with RESTRICT behavior.

**Justification:**
- BR-09: Certificate records must never be permanently deleted.
- Cascading a deletion of an enrollment into the certificates table would destroy permanent records. RESTRICT ensures this cannot happen even if the application incorrectly attempts it.

### 2.4 Why No `ON DELETE CASCADE` Anywhere

**Decision:** `ON DELETE CASCADE` is not used on any foreign key.

**Justification:** The fundamental design philosophy of this system is that historical records (enrollments, certificates) must be **permanently preserved**. Cascade deletion would silently destroy this history if a parent record were deleted. RESTRICT is the only safe default for a system whose integrity depends on record permanence.

### 2.5 ON UPDATE CASCADE

**Decision:** All foreign keys use `ON UPDATE CASCADE`.

**Justification:** If a primary key value is updated (which is rare with AUTO_INCREMENT surrogate keys but theoretically possible through administrative action), dependent rows should follow automatically to maintain referential integrity. This is safe because primary key updates do not carry business meaning in this design.

---

## 3. Normalization Approach

### 3.1 Normalization Level: Third Normal Form (3NF)

The design is normalized to **Third Normal Form (3NF)**. No compromise was made.

**First Normal Form (1NF):** All tables have a single-valued primary key, and all columns contain atomic values. No repeating groups. ✅

**Second Normal Form (2NF):** No partial dependencies on composite keys. The only composite unique key is `(user_id, course_id)` in `enrollments`, which is a UNIQUE constraint, not the primary key. The surrogate `id` is the primary key for all tables, so 2NF is trivially satisfied. ✅

**Third Normal Form (3NF):** No transitive dependencies. Each non-key column depends only on the primary key of its own table.

| Table | Transitive dependency check |
|---|---|
| `users` | All columns (`full_name`, `email`, `role`, etc.) describe the user. No column describes another column. ✅ |
| `courses` | All columns (`title`, `start_date`, `end_date`, etc.) describe the course. ✅ |
| `enrollments` | `status` and `completion_date` describe the enrollment relationship, not the user or the course independently. ✅ |
| `certificates` | `certificate_number`, `issued_at`, `file_path`, `status`, `revoked_at`, `revocation_reason` all describe the certificate (linked to one enrollment). ✅ |

**Derived data not stored:** No derived fields are stored. For example:
- Participant full name on a certificate is not stored in `certificates` — it is derived at query time from `users.full_name` via the enrollment join.
- Course title on a certificate is not stored in `certificates` — derived via join.
- This avoids update anomalies (e.g. if a course title changes, certificate display would not be stale).

### 3.2 Intentional Denormalization Decisions

**None.** No denormalization was introduced. The join path to retrieve all data needed for a certificate (users → enrollments → courses ← certificates) is a 3-table join, which is well within the performance envelope for this application's scale (university project, single-user to small multi-user concurrent load).

---

## 4. Historical Record Preservation

### 4.1 Certificate Records are Permanent (BR-07, BR-09)

Certificates are never deleted. The design enforces this through:
1. `ON DELETE RESTRICT` on `certificates.enrollment_id` — no cascade path removes certificates.
2. No `DELETE` route exists in the application UI for certificates (BR-09).
3. Revoked certificates retain their full record and PDF; only the `status` field changes.

### 4.2 Enrollment Records are Permanent When Certificates Exist

If an enrollment has a certificate, neither the enrollment nor the certificate can be deleted:
- Deleting the certificate is blocked (BR-09, `ON DELETE RESTRICT` chain).
- Deleting the enrollment would cascade-block the certificate deletion attempt.
- Deleting the user (participant) is blocked by `ON DELETE RESTRICT` on `enrollments.user_id`.

### 4.3 Enrollment Records Preserved Even Without Certificates (BR-10 Tier 2)

Even if a participant has no certificates, their enrollment records prevent hard deletion:
- `ON DELETE RESTRICT` on `enrollments.user_id` blocks deletion of the user row if any enrollment exists.
- Application pre-checks the enrollment count and informs the administrator (FR-23 tier 2).

### 4.4 Participant Deactivation vs. Deletion

When a participant cannot be hard-deleted:
- Their `users.status` is set to `'Inactive'`.
- The row remains in the database.
- All enrollment and certificate records remain intact and fully queryable.
- The participant cannot log in (`status = 'Inactive'` check on login — UC-02).

---

## 5. Participant Deletion Rule Representation

The three-tier participant deletion policy (BR-10, FR-23) is represented as follows:

| Tier | Condition | Database State After Action |
|---|---|---|
| Tier 1: Has certificates | `COUNT(c.id) > 0` via enrollment join | `users.status` SET TO `'Inactive'` — row retained |
| Tier 2: Has enrollments, no certificates | `COUNT(e.id) > 0` for the user | No change — `ON DELETE RESTRICT` on FK blocks any DELETE attempt |
| Tier 3: Neither certificates nor enrollments | `COUNT(e.id) = 0` | `DELETE FROM users WHERE id = ?` permitted |

**How the database enforces tiers 1 and 2:**
- If the application attempts a `DELETE FROM users WHERE id = ?` for a participant who has enrollments, the FK `ON DELETE RESTRICT` on `enrollments.user_id` will cause the DELETE to fail with a constraint error. This provides a safety net independent of application logic.
- Tier 1 (deactivation) is a pure application-layer decision based on a certificate count query. The database does not automatically choose deactivation — the application must route correctly.

---

## 6. Certificate Issuance and Revocation Representation

### 6.1 Certificate Issuance Data Model

A certificate row in the `certificates` table captures:

| Data | Column | Notes |
|---|---|---|
| Which enrollment | `enrollment_id` | UNIQUE — one per enrollment |
| Unique identifier | `certificate_number` | UNIQUE — `CERT-YYYY-XXXXXXXX` |
| When issued | `issued_at` | DATE — the issuance date |
| PDF location | `file_path` | Server path — authenticated download only |
| Current status | `status` | `'Valid'` on creation |

The certificate number is generated outside the transaction (step 2 of BR-06 sequence). The INSERT into `certificates` is performed inside the transaction. If a UNIQUE collision occurs on `certificate_number`, the transaction is rolled back and the process retries.

### 6.2 Revocation Data Model

Revocation does not add a new row or new table. It modifies the existing certificate row:

| Column | Before Revocation | After Revocation |
|---|---|---|
| `status` | `'Valid'` | `'Revoked'` |
| `revoked_at` | NULL | Current timestamp |
| `revocation_reason` | NULL | Mandatory non-empty text |

This design keeps the complete certificate lifecycle in one row, making it trivial to query a certificate's history. No separate `revocations` table is needed because the requirement (BR-07) specifies only one state change per certificate (Valid → Revoked), with no reinstatement possible.

### 6.3 Why Not a Separate `certificate_revocations` Table

**Rejected** because:
- BR-07 specifies that revocation is a one-time state change with one reason and one timestamp.
- No requirement exists for multiple revocation events or revocation history.
- A separate table would add a join to every certificate status query without providing any benefit.

---

## 7. Concurrency and Duplicate Protection

### 7.1 Certificate Number Collision Handling

**Risk:** Two simultaneous issuance processes could generate the same `CERT-YYYY-XXXXXXXX` random number.

**Database protection:** `UNIQUE KEY uq_certificates_number (certificate_number)`. The database will reject the second INSERT with a constraint violation error.

**Application response:** The 7-step atomic issuance sequence (BR-06) handles this explicitly:
1. The certificate number is generated before the transaction begins.
2. If the INSERT fails with a unique constraint error, the transaction is rolled back.
3. A new number is generated.
4. A new PDF containing the new number is rendered.
5. The process retries up to 5 times.

**Statistical probability:** 8-character uppercase alphanumeric from `random_bytes()` = 36^8 ≈ 2.8 trillion combinations. Collision probability is negligible in practice, but the database constraint + retry logic ensures correctness regardless.

### 7.2 Duplicate Enrollment Protection

**Risk:** Two concurrent requests enrolling the same participant in the same course.

**Database protection:** `UNIQUE KEY uq_enrollments_user_course (user_id, course_id)`. Both concurrent INSERTs cannot both succeed; the second one will fail with a constraint violation.

**Application response:** Pre-insert check (application layer) + constraint violation handling returns a user-friendly "already enrolled" message.

### 7.3 Duplicate Certificate Issuance

**Risk:** Administrator issues certificate twice for the same enrollment (double-click or concurrent tab).

**Database protection:** `UNIQUE KEY uq_certificates_enrollment (enrollment_id)`. The second INSERT will fail.

**Application response:** Pre-insert existence check + constraint violation handling returns "A certificate has already been issued for this enrollment."

---

## 8. Index Strategy

### 8.1 Automatic Indexes (Created by MySQL for UNIQUE and FK Constraints)

MySQL InnoDB automatically creates B-tree indexes for:
- Every PRIMARY KEY column
- Every UNIQUE KEY constraint
- Every FOREIGN KEY column (MySQL creates an index on the referencing column)

This covers the most critical lookup paths:
- Login: `users.email` (UNIQUE index)
- Certificate lookup: `certificates.certificate_number` (UNIQUE index)
- Enrollment lookup by user: `enrollments.user_id` (FK index)
- Enrollment lookup by course: `enrollments.course_id` (FK index)
- Certificate lookup by enrollment: `certificates.enrollment_id` (UNIQUE FK index)

### 8.2 Additional Explicit Indexes

Three additional indexes are added for filtering operations that are not covered by UNIQUE/FK indexes:

| Index | Justification |
|---|---|
| `idx_users_role` on `users(role)` | Dashboard queries count users by role (FR-52). Without an index, full table scan on users table. |
| `idx_users_status` on `users(status)` | Dashboard counts active participants. Admin lists filter by status. |
| `idx_courses_status` on `courses(status)` | Course list filters by Active/Inactive. Dashboard counts active courses. |
| `idx_enrollments_status` on `enrollments(status)` | Filtering completed enrollments before certificate issuance. Dashboard stats. |
| `idx_certificates_status` on `certificates(status)` | Certificate archive filtering by Valid/Revoked (FR-44). Dashboard counts. |

### 8.3 Indexes Intentionally Not Added

| Absent Index | Reason |
|---|---|
| `courses.title` full-text | Public verification searches by participant name, not course title. Admin searches use the archive UI which does not require a text index for MVP. |
| `users.full_name` | Public verification name search uses a JOIN with `users.full_name` via the certificates path. The result set is small (capped at 20). An application-level `LIKE` or exact match query on a non-indexed column is acceptable for this scale. If performance becomes an issue, a full-text index can be added in a future revision. |

---

## 9. Requirements Not Directly Representable in Database

### 9.1 Three-Tier Deletion Logic (Partial)

The database can enforce tiers 1 and 2 of the participant deletion policy via FK RESTRICT, but it cannot automatically choose the right tier action (deactivate vs. block vs. allow delete). The application must query certificate and enrollment counts and route the action accordingly. The database provides safety nets; the routing is an application responsibility.

**Risk:** If the application code has a bug that skips the count checks and issues a DELETE directly, the FK RESTRICT will prevent data loss for tiers 2 (has enrollments). Tier 1 (has certificates — should deactivate) is not enforced at database level and depends entirely on the application correctly checking certificate counts before attempting deletion.

**Mitigation:** The application model for participant deletion must implement the three-tier check as an atomic pre-delete evaluation before any state change.

### 9.2 Enrollment Status Transition Order (Enrolled → In Progress → Completed)

The database stores the current status value but cannot enforce that status transitions must follow the prescribed order (FR-28). MySQL ENUM constrains valid values but not transition sequences.

**Application responsibility:** The enrollment update controller must validate that:
- Status can only advance (not revert)
- `Completed` cannot be set without a completion date
- Status cannot be changed back after a certificate is issued

### 9.3 Completion Date Validation Rules (BR-05)

Four conditions on `completion_date` (valid date, ≤ today, ≥ course start date, ≤ course end date) cannot be enforced by a simple column constraint. MySQL CHECK constraints could partially address conditions 1 and 2, but conditions 3 and 4 require cross-table comparison (enrollments vs courses).

**Application responsibility:** Server-side validation at the enrollment update action.

### 9.4 Role-Based Access Control (BR-13, BR-14)

The database cannot enforce that only participants' own records are accessed. This is entirely an application-layer concern (session user_id comparison in every query).

**Application responsibility:** All participant data queries must use `WHERE user_id = session_user_id` without exception.

### 9.5 PDF File Atomicity (BR-06 Process Termination Risk)

The database transaction guarantees atomicity of database writes. The filesystem operation (moving the PDF to final storage) is outside the database transaction scope. If the PHP process terminates between file move and transaction commit, an orphaned PDF file may exist.

**Known risk (documented in SRS §8.2 ASM-08):** A periodic consistency check procedure comparing `certificates.file_path` values against files on disk can identify orphaned files. This is an operational maintenance procedure, not a database design issue.

---

## 10. Validation Checklist

| Check | Result |
|---|---|
| Every relevant data requirement is represented | ✅ All FR, NFR, and BR data requirements traced to table columns |
| Every entity has an appropriate primary key | ✅ All four tables use INT UNSIGNED AUTO_INCREMENT surrogate PKs |
| Every relationship has the correct foreign key | ✅ Three FK relationships with appropriate ON DELETE RESTRICT |
| No orphan records can be created | ✅ All FKs prevent orphaned records; RESTRICT prevents parent deletion while children exist |
| Unique constraints match the business rules | ✅ email (BR-12/NFR-31), (user_id, course_id) (BR-01/NFR-27), enrollment_id (BR-03/NFR-26), certificate_number (BR-04/NFR-25) |
| Status values match the approved documents exactly | ✅ ENUM values match Status Value Glossary in BUSINESS_RULES.md verbatim |
| Participant deletion rules preserve historical records | ✅ ON DELETE RESTRICT on FK chain prevents deletion of users with enrollments |
| Certificate records remain traceable after revocation | ✅ Revocation is UPDATE only; records never deleted; revocation reason and timestamp stored |
| Database supports public verification without exposing private data | ✅ Public query uses explicit column subset (name, cert number, course, dates, status only) |
| Design supports all required CRUD operations | ✅ Verified against each FR module |
| Design is compatible with MySQL/MariaDB and PHP PDO | ✅ Standard InnoDB, ENUM, FK, UNIQUE — all natively supported |
| Design is normalized to 3NF | ✅ No partial or transitive dependencies |
| No new functional requirements invented | ✅ Design strictly follows approved SRS v1.3 |
| No SQL or application code created by this task | ✅ Confirmed — three design documents only |

---

## 11. Summary of Proposed Tables

| Table | Columns | PKs | FKs | UNIQUEs | Indexes |
|---|---|---|---|---|---|
| `users` | 9 | 1 | 0 | 1 (email) | 3 |
| `courses` | 8 | 1 | 0 | 0 | 2 |
| `enrollments` | 7 | 1 | 2 | 1 (user_id,course_id) | 4 |
| `certificates` | 10 | 1 | 1 | 2 (enrollment_id, cert_number) | 4 |
| **Total** | **34** | **4** | **3** | **4** | **13** |

---

*End of Database Design Decisions Document — Version 1.0*
