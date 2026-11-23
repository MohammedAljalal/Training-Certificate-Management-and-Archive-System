# Requirements Traceability Matrix (RTM)
## Training Certificate Management and Archive System

| Field | Value |
|---|---|
| Document ID | RTM-001 |
| Version | 1.3 |
| Related SRS | SRS-001 v1.3 |
| Date | 2026-11-21 |
| Revised | 2026-11-22 |

---

## Revision History

| Version | Date | Changes |
|---|---|---|
| 1.0 | 2026-11-21 | Initial version. |
| 1.1 | 2026-11-21 | New requirements traced: FR-10-AUTH (admin-only account creation), FR-51-PV (public search cap), FR-52-PV (no public PDF download), NFR-05 corrected (login-only regeneration), NFR-09-SEC (storage/ not web-accessible), NFR-30 (Dompdf). New business rules traced: BR-18-PV, BR-19-PV, BR-25. AC-16/AC-17 updated (completion date upper bound, course end date). AC-16 (PDF not publicly accessible) added as AC-16; previous AC-16 renumbered to AC-17; new AC-22 (public search cap). UC-06 BR reference corrected (BR-07→BR-11). FR-11 updated (course status). FR-34 updated (random_bytes). FR-36 updated (storage path). NFR-05 description corrected. All count totals updated. |
| 1.2 | 2026-11-21 | Traceability consistency corrections: NFR-31 added (UNIQUE constraint on users.email); BR-12 mapped to NFR-31 (replacing incorrect mapping to NFR-25); BR-10 summary updated to reflect three-tier participant deletion policy; BR-06 summary updated for canonical sequence (number gen before transaction), collision retry (render new PDF with new number, up to 5 attempts), commit-failure final-file cleanup, and process-termination risk; NFR-09-SEC softened regarding hosting environment constraints (.htaccess fallback); Phase 1 deliverables updated with NFR-31; requirement counts updated (NFR count: 32). Identifier Convention Note referenced. |
| 1.3 | 2026-11-22 | Final alignment: FR-23 summary and UR-02 coverage updated to explicitly detail all three participant deletion cases; Phase 3 storage deliverable updated to specify storage outside public document root where supported, with .htaccess denial rules as fallback. |

---

## Table of Contents

1. [University Requirement Coverage Matrix](#1-university-requirement-coverage-matrix)
2. [Functional Requirements Traceability](#2-functional-requirements-traceability)
3. [Non-Functional Requirements Traceability](#3-non-functional-requirements-traceability)
4. [Business Rules Traceability](#4-business-rules-traceability)
5. [Implementation Phase Plan](#5-implementation-phase-plan)
6. [Requirement Count Summary](#6-requirement-count-summary)

---

## Identifier Convention Note

Requirements throughout this matrix follow the numbering convention described in SRS §4.0. Compound identifiers (`FR-10-AUTH`, `FR-51-PV`, `FR-52-PV`, `NFR-09-SEC`) are employed where a requirement shares a numerical base with an existing requirement in another section, maintaining sequential continuity across all documents.

---

## 1. University Requirement Coverage Matrix

This table maps every mandatory university assignment requirement to its corresponding system specification and acceptance test.

| UR-ID | University Requirement | SRS Coverage | Implementation Module | Phase | Acceptance Test |
|---|---|---|---|---|---|
| UR-01 | Training Course Management: Add, edit, view, delete courses (title, dates, descriptions) | FR-10, FR-11, FR-12, FR-13, FR-14, FR-15 | Course Management | Phase 2 | AC-04: Admin can create, view, edit, and delete a course. Delete blocked when enrollments exist. Status values "Active"/"Inactive" used. |
| UR-02 | Participant Management: Register participants and manage their information | FR-17, FR-18, FR-19, FR-20, FR-21, FR-22, FR-23, FR-24, FR-10-AUTH | Participant Management | Phase 2 | AC-05: Admin can create, view, edit, and deactivate/delete a participant. No public registration. Three-tier deletion: (1) has certs → Inactive; (2) has enrollments without certs → deletion blocked; (3) neither → hard delete with confirmation. |
| UR-03 | Certificate Generation: Generate downloadable PDF certificates with participant name, course details, completion date, and unique certificate number | FR-32, FR-33, FR-34, FR-35, FR-36, FR-37, FR-38 | Certificate Generation | Phase 3 | AC-08: Issuing a certificate for a completed enrollment generates a unique CERT-YYYY-XXXXXXXX number via random_bytes(), produces a Dompdf PDF with all required fields, stored in storage/certificates/. |
| UR-04 | Certificate Search and Verification: Users can search by certificate number or participant name and view certificate details | FR-45, FR-46, FR-47, FR-48, FR-49, FR-50, FR-51-PV, FR-52-PV | Public Verification | Phase 3 | AC-12, AC-13, AC-22: Valid search returns correct details without private data or PDF link; invalid search returns "Not found"; name search capped at 20 results. |
| UR-05 | User authentication (login and logout) | FR-01, FR-02, FR-03, FR-04, FR-05, FR-06, FR-09 | Authentication | Phase 1 | AC-01, AC-02: Correct credentials log in with session ID regeneration; logout destroys session and expires cookie. |
| UR-06 | At least two user roles with different permissions | FR-07, FR-08; Roles: Administrator, Participant | Authentication / RBAC | Phase 1 | AC-03: Participant cannot access admin pages. Admin cannot access participant-only pages. |
| UR-07 | CRUD operations on at least two major functions | FR-10 to FR-16 (Courses), FR-17 to FR-24 (Participants) | Course Mgmt, Participant Mgmt | Phase 2 | AC-04 (Courses CRUD), AC-05 (Participant CRUD) |
| UR-08 | Relational database with at least three related tables | NFR-24, NFR-25, NFR-26, NFR-27; Tables: users, courses, enrollments, certificates (minimum 4; trainees merged into users) | Database Design | Phase 1 | Database schema contains ≥ 4 related tables with InnoDB FK constraints. |
| UR-09 | Administrative dashboard | FR-51, FR-52, FR-53, FR-54 | Admin Dashboard | Phase 2 | Dashboard displays summary statistics (using standardised status values) and navigation to all modules. |
| UR-10 | Input validation and error handling | FR-15, FR-24, FR-29, NFR-13, NFR-21, NFR-23 | All modules | All Phases | AC-17, AC-18, AC-19: Validation errors shown field-adjacent; SQL injection rejected; XSS escaped; completion date upper bound enforced. |
| UR-11 | Complete functional workflow | SRS §6.1 (Core Certificate Workflow) | All modules | Phase 3 | End-to-end: Create course → Admin registers participant → Enroll → Update to Completed (with valid date) → Issue certificate (Dompdf, random_bytes) → Participant downloads → Public verifies (no PDF). |
| UR-12 | Responsive and user-friendly interface | NFR-12, NFR-13, NFR-14, NFR-15 | UI / Assets | Phase 4 | AC-21: Dashboard renders correctly on 375px mobile viewport without overflow. |
| UR-13 | Deployment to a web host | NFR-28, NFR-29 | Deployment | Phase 5 | Application accessible from a public URL with database schema imported and storage/ directory configured outside web root. |

**Coverage Status: ✅ All 13 university requirements are fully covered.**

---

## 2. Functional Requirements Traceability

### 2.1 Authentication Module

| FR-ID | Requirement Summary | Use Case | Business Rule | Phase | Acceptance Test |
|---|---|---|---|---|---|
| FR-01 | Login page with email and password | UC-01, UC-02 | — | 1 | AC-01 |
| FR-02 | bcrypt password verification | UC-01, UC-02 | BR-20 | 1 | AC-01 |
| FR-03 | Redirect admin to dashboard on login | UC-01 | — | 1 | AC-01 |
| FR-04 | Redirect participant to dashboard on login | UC-02 | — | 1 | AC-01 |
| FR-05 | Generic login error message | UC-01, UC-02 | — | 1 | AC-01 |
| FR-06 | Logout: unset + destroy + expire cookie | UC-03 | — | 1 | AC-02 |
| FR-07 | Session guard on all protected pages | UC-01–UC-19 | BR-14 | 1 | AC-02, AC-03 |
| FR-08 | Role-based page access enforcement | UC-01–UC-19 | BR-14 | 1 | AC-03 |
| FR-09 | Session ID regenerated on login only | UC-01, UC-02 | — | 1 | AC-01 |
| FR-10-AUTH | Admin-only account creation; no public registration; seed admin | UC-07 | BR-25, BR-15 | 1 | AC-05 |

### 2.2 Course Management Module

| FR-ID | Requirement Summary | Use Case | Business Rule | Phase | Acceptance Test |
|---|---|---|---|---|---|
| FR-10 | Create training course | UC-04 | BR-18, BR-19 | 2 | AC-04 |
| FR-11 | List all courses with status (Active/Inactive) | UC-04, UC-18 | — | 2 | AC-04 |
| FR-12 | View single course detail | UC-04, UC-05 | — | 2 | AC-04 |
| FR-13 | Edit course | UC-05 | BR-18, BR-19 | 2 | AC-04 |
| FR-14 | Delete course (only if no enrollments) | UC-06 | BR-11 | 2 | AC-04 |
| FR-15 | Course form validation (incl. date constraints) | UC-04, UC-05 | BR-18, BR-19 | 2 | AC-17 |
| FR-16 | Display enrolled participant count | UC-04, UC-18 | — | 2 | — |

### 2.3 Participant Management Module

| FR-ID | Requirement Summary | Use Case | Business Rule | Phase | Acceptance Test |
|---|---|---|---|---|---|
| FR-17 | Admin creates participant account (no self-registration) | UC-07 | BR-20, BR-21, BR-25 | 2 | AC-05 |
| FR-18 | Hash all passwords with bcrypt | UC-07 | BR-20 | 2 | AC-05 |
| FR-19 | Unique email address | UC-07, UC-08 | BR-12 | 2 | AC-05 |
| FR-20 | Paginated participant list with status | UC-07 | — | 2 | — |
| FR-21 | View participant full profile | UC-08 | — | 2 | AC-05 |
| FR-22 | Edit participant details | UC-08 | BR-21 | 2 | AC-05 |
| FR-23 | Three-tier participant deletion: (1) has certs → Inactive; (2) has enrollments without certs → deletion blocked; (3) neither → hard delete with confirmation | UC-09 | BR-10 | 2 | AC-05 |
| FR-24 | Participant form validation | UC-07, UC-08 | BR-21 | 2 | AC-17 |

### 2.4 Enrollment Management Module

| FR-ID | Requirement Summary | Use Case | Business Rule | Phase | Acceptance Test |
|---|---|---|---|---|---|
| FR-25 | Enroll participant in course | UC-10 | BR-01 | 2 | AC-06 |
| FR-26 | Reject duplicate enrollment | UC-10 | BR-01 | 2 | AC-06 |
| FR-27 | Initial status = "Enrolled" | UC-10 | — | 2 | AC-06 |
| FR-28 | Update enrollment status (Enrolled → In Progress → Completed) | UC-11 | BR-02 | 2 | AC-07 |
| FR-29 | Completion date required at "Completed" (4 validation rules) | UC-11 | BR-05 | 2 | AC-07 |
| FR-30 | Prevent status downgrade after certificate issued | UC-11 | BR-03 | 2 | AC-07 |
| FR-31 | Enrollment list per course | UC-10, UC-11 | — | 2 | AC-06 |

### 2.5 Certificate Generation Module

| FR-ID | Requirement Summary | Use Case | Business Rule | Phase | Acceptance Test |
|---|---|---|---|---|---|
| FR-32 | Issuance only for "Completed" enrollments | UC-12 | BR-02 | 3 | AC-08 |
| FR-33 | Reject duplicate certificate issuance | UC-12 | BR-03 | 3 | AC-09 |
| FR-34 | Generate unique certificate number via random_bytes() + DB UNIQUE | UC-12 | BR-04 | 3 | AC-08 |
| FR-35 | Generate PDF via Dompdf with Unicode support | UC-12 | BR-06 | 3 | AC-08, AC-15 |
| FR-36 | Store PDF in storage/certificates/ (outside web root) | UC-12 | BR-06 | 3 | AC-08, AC-16 |
| FR-37 | Store certificate record in database (status "Valid") | UC-12 | BR-04, BR-06 | 3 | AC-08 |
| FR-38 | Display success with certificate number | UC-12 | — | 3 | AC-08 |

### 2.6 Certificate Archive Module

| FR-ID | Requirement Summary | Use Case | Business Rule | Phase | Acceptance Test |
|---|---|---|---|---|---|
| FR-39 | Certificate archive list for admin (Valid/Revoked) | UC-19 | BR-07 | 3 | AC-10 |
| FR-40 | View certificate detail (admin) | UC-19 | — | 3 | AC-10 |
| FR-41 | Download PDF (admin) via authenticated controller | UC-14 | BR-09 | 3 | AC-15 |
| FR-42 | Revoke certificate with mandatory reason | UC-13 | BR-07, BR-08 | 3 | AC-11 |
| FR-43 | Revoked certificates retained in archive | UC-13 | BR-07 | 3 | AC-11 |
| FR-44 | Filter and search archive (All/Valid/Revoked) | UC-19 | — | 3 | AC-10 |

### 2.7 Public Certificate Verification Module

| FR-ID | Requirement Summary | Use Case | Business Rule | Phase | Acceptance Test |
|---|---|---|---|---|---|
| FR-45 | Public page requires no login or account | UC-17 | BR-17 | 3 | AC-12 |
| FR-46 | Search by certificate number (exact) or full name (exact, case-insensitive) | UC-17 | — | 3 | AC-12 |
| FR-47 | Display safe certificate details (number, name, course, dates, status) | UC-17 | BR-16 | 3 | AC-12 |
| FR-48 | No private data exposed (email, phone, DOB, ID) | UC-17 | BR-16 | 3 | AC-12 |
| FR-49 | Revoked certificate clearly labelled | UC-17 | BR-08 | 3 | AC-11, AC-12 |
| FR-50 | "No certificate found" message when no result | UC-17 | — | 3 | AC-13 |
| FR-51-PV | Name-based search capped at 20 results | UC-17 | BR-18-PV | 3 | AC-22 |
| FR-52-PV | No PDF download link on public verification page | UC-17 | BR-19-PV | 3 | AC-12, AC-16 |

### 2.8 Administrator Dashboard Module

| FR-ID | Requirement Summary | Use Case | Business Rule | Phase | Acceptance Test |
|---|---|---|---|---|---|
| FR-51 | Admin dashboard page | UC-18 | — | 2 | UR-09 |
| FR-52 | Summary statistics (counts by standardised status) | UC-18 | — | 2 | UR-09 |
| FR-53 | Recent activity list on dashboard | UC-18 | — | 2 | UR-09 |
| FR-54 | Navigation links on dashboard | UC-18 | — | 2 | UR-09 |

### 2.9 Participant Dashboard Module

| FR-ID | Requirement Summary | Use Case | Business Rule | Phase | Acceptance Test |
|---|---|---|---|---|---|
| FR-55 | Participant dashboard page | UC-15 | — | 2 | AC-14 |
| FR-56 | Participant profile display | UC-15 | — | 2 | AC-14 |
| FR-57 | Enrolled course list with status (Enrolled/In Progress/Completed) | UC-15 | BR-13 | 2 | AC-14 |
| FR-58 | Certificate list with status (Valid/Revoked) and download links | UC-15, UC-16 | BR-13 | 3 | AC-14, AC-15 |
| FR-59 | Participant PDF download via authenticated controller | UC-16 | BR-13 | 3 | AC-15 |
| FR-60 | Block access to other participants' data (session-only ID) | UC-16 | BR-13 | 2 | AC-14 |

---

## 3. Non-Functional Requirements Traceability

| NFR-ID | Requirement Summary | Category | Phase | Verification Method |
|---|---|---|---|---|
| NFR-01 | PDO prepared statements only | Security | 1 | Code review: no raw SQL concatenation. |
| NFR-02 | HTML output escaped with htmlspecialchars | Security | 1 | AC-19: XSS attempt displays escaped text. |
| NFR-03 | CSRF synchroniser token on all POST forms; reject with HTTP 403 | Security | 1 | AC-20: POST without token returns HTTP 403. |
| NFR-04 | bcrypt password hashing for all accounts | Security | 1 | Inspect DB: password column starts with `$2y$`. |
| NFR-05 | Session ID regenerated on login only; logout uses session_destroy + cookie expiry | Security | 1 | AC-02: Post-logout Back button does not restore session. Code review: session_regenerate_id(true) is in login handler only. |
| NFR-06 | PHP files outside public/ not URL-accessible | Security | 1 | Direct URL to config/db.php returns 403 or 404. |
| NFR-07 | File path validation for downloads (path traversal check) | Security | 3 | AC-14 AF-14B: Path traversal string in download URL is blocked with HTTP 400. |
| NFR-08 | DB credentials in config/db.php only (outside public/) | Security | 1 | Code review: no credentials in public/ files. |
| NFR-09-SEC | storage/certificates/ not web-accessible (outside docroot preferred; .htaccess fallback) | Security | 3 | AC-16: Direct URL to storage/certificates/*.pdf returns 403 or 404. |
| NFR-09 | Pagination on lists (≤ 25 per page) | Performance | 2 | Test with 50+ records: pages render at 25 per page. |
| NFR-10 | Database indexes on FK and search columns | Performance | 1 | EXPLAIN on common queries shows index use. |
| NFR-11 | Page response < 2 seconds | Performance | 4 | Manual timing test on XAMPP localhost. |
| NFR-12 | Responsive on 375px–1920px | Usability | 4 | AC-21: 375px mobile viewport renders without overflow. |
| NFR-13 | Field-specific validation error messages | Usability | 2 | AC-17: Error appears adjacent to failing field. |
| NFR-14 | Confirmation step for destructive actions | Usability | 2 | Delete/deactivate/revoke shows confirmation before execution. |
| NFR-15 | Active page highlighted in navigation | Usability | 4 | Visual inspection: current page link is styled as active. |
| NFR-16 | Verification page works without JavaScript | Usability | 3 | Test verify.php with JavaScript disabled: search and results function. |
| NFR-17 | Consistent file/folder structure | Maintainability | 1 | Directory structure matches SRS §2.3 recommended layout. |
| NFR-18 | PDO model classes for data access | Maintainability | 2 | Code review: no raw PDO calls in view/template files. |
| NFR-19 | Shared header/footer includes | Maintainability | 2 | Code review: no repeated HTML boilerplate across pages. |
| NFR-20 | No hardcoded credentials outside config/db.php | Maintainability | 1 | Code review: grep for credentials strings outside config/. |
| NFR-21 | PDO ERRMODE_EXCEPTION | Reliability | 1 | Intentional bad query triggers caught exception, not silent failure. |
| NFR-22 | Certificate issuance 7-step atomic sequence; rollback and cleanup on failure | Reliability | 3 | Simulate write failure: no partial DB record remains; temp file cleaned. |
| NFR-23 | User-friendly error page (no stack traces in production) | Reliability | 4 | Trigger an error with display_errors=Off: custom error page shown. |
| NFR-24 | InnoDB with FK constraints | Data Integrity | 1 | SHOW CREATE TABLE shows ENGINE=InnoDB with FOREIGN KEY. |
| NFR-25 | UNIQUE on certificate_number | Data Integrity | 1 | SHOW CREATE TABLE shows UNIQUE KEY on certificate_number. |
| NFR-26 | UNIQUE on certificates.enrollment_id | Data Integrity | 1 | SHOW CREATE TABLE shows UNIQUE KEY on enrollment_id. |
| NFR-27 | UNIQUE on (participant_id, course_id) in enrollments | Data Integrity | 1 | SHOW CREATE TABLE shows composite UNIQUE KEY. |
| NFR-31 | UNIQUE on users.email | Data Integrity | 1 | SHOW CREATE TABLE shows UNIQUE KEY on email. |
| NFR-28 | Deployable to shared PHP hosting (PHP 8.x, Composer, MySQL) | Deployment | 5 | Application runs on remote server via public URL. |
| NFR-29 | Single schema.sql for setup (tables + seed admin) | Deployment | 1 | Running schema.sql creates all tables and seed admin with hashed password. |
| NFR-30 | Dompdf via Composer; Unicode/UTF-8 support enabled | PDF Generation | 3 | AC-08: Generated PDF contains correct participant name including non-ASCII chars. |

---

## 4. Business Rules Traceability

| BR-ID | Rule Summary | FR Coverage | NFR Coverage | UC Triggered | Phase |
|---|---|---|---|---|---|
| BR-01 | No duplicate enrollment | FR-26 | NFR-27 | UC-10 | 2 |
| BR-02 | Completion required before certificate | FR-32 | — | UC-12 | 3 |
| BR-03 | One certificate per enrollment | FR-33 | NFR-26 | UC-12 | 3 |
| BR-04 | Unique cert number via random_bytes() + DB UNIQUE guarantee | FR-34 | NFR-25 | UC-12 | 3 |
| BR-05 | Valid completion date (4 conditions incl. ≤ course end date) | FR-29 | — | UC-11 | 2 |
| BR-06 | 7-step atomic issuance (candidate number gen before transaction, render new PDF on collision retry, rollback + cleanup, commit-failure final-file cleanup, process termination risk) | FR-35, FR-36, FR-37 | NFR-22 | UC-12 | 3 |
| BR-07 | Revoked certificates retained in archive | FR-43 | — | UC-13 | 3 |
| BR-08 | Revoked shown as REVOKED on verification | FR-49 | — | UC-13, UC-17 | 3 |
| BR-09 | No deletion of certificate records | FR-39 | — | — | 3 |
| BR-10 | Three-tier participant deletion: (1) has certs → Inactive; (2) has enrollments without certs → deletion blocked; (3) neither → hard delete | FR-23 | — | UC-09 | 2 |
| BR-11 | No course deletion with enrollments | FR-14 | NFR-24 | UC-06 | 2 |
| BR-12 | Email address uniqueness | FR-19 | NFR-31 | UC-07, UC-08 | 2 |
| BR-13 | Participants access own records only (session-only ID) | FR-60 | NFR-07 | UC-15, UC-16 | 2 |
| BR-14 | Role separation enforced on every page load | FR-08 | — | All UCs | 1 |
| BR-15 | Admins cannot manage other admins via UI | FR-10-AUTH | — | UC-07 | 1 |
| BR-16 | No private data on public verification | FR-48 | — | UC-17 | 3 |
| BR-17 | Verification requires no login or account | FR-45 | — | UC-17 | 3 |
| BR-18 | Course end date ≥ start date | FR-15 | — | UC-04, UC-05 | 2 |
| BR-18-PV | Public name search capped at 20 results | FR-51-PV | — | UC-17 | 3 |
| BR-19 | Course title required (3–255 chars) | FR-15 | — | UC-04, UC-05 | 2 |
| BR-19-PV | No PDF download on public verification | FR-52-PV | NFR-09-SEC | UC-17 | 3 |
| BR-20 | All passwords hashed (admin and participant) | FR-18 | NFR-04 | UC-01, UC-02, UC-07 | 1 |
| BR-21 | Participant full name required (≤ 150 chars) | FR-24 | — | UC-07, UC-08 | 2 |
| BR-22 | CSRF token on all POST forms; HTTP 403 on failure | — | NFR-03 | All form UCs | 1 |
| BR-23 | Prepared statements for all queries with user input | — | NFR-01 | All UCs | 1 |
| BR-24 | All HTML output escaped | — | NFR-02 | All UCs | 1 |
| BR-25 | Account creation restricted to administrators; seed admin via SQL | FR-10-AUTH, FR-17 | — | UC-07 | 1 |

---

## 5. Implementation Phase Plan

### Phase 1 — Foundation and Security Infrastructure
*Goal: Establish the project skeleton, database schema, session security, and account creation policy.*

| Deliverable | FRs | NFRs | BRs |
|---|---|---|---|
| Project folder structure (public/, config/, models/, actions/, storage/, assets/) | — | NFR-17 | — |
| `database/schema.sql` with all tables, indexes, FK constraints, and seed admin (hashed password) | — | NFR-24–27, NFR-29, NFR-31 | BR-12 |
| `config/db.php` PDO connection with ERRMODE_EXCEPTION | — | NFR-20, NFR-21 | — |
| Apache `.htaccess` restricting access to config/, storage/, models/ | — | NFR-06, NFR-09-SEC | — |
| Authentication pages: login.php, logout.php (session_unset + destroy + cookie expiry) | FR-01–09, FR-10-AUTH | NFR-04, NFR-05 | BR-20, BR-14, BR-25 |
| Session guard helper (role-based redirect) | FR-07, FR-08 | — | BR-14 |
| CSRF token generation and validation helper | — | NFR-03 | BR-22 |
| Global HTML escape helper `h()` | — | NFR-02 | BR-24 |
| Shared header, footer, navigation includes | — | NFR-19 | — |
| Base CSS stylesheet (responsive grid) | — | NFR-12 | — |
| Composer setup and Dompdf installation | — | NFR-30 | — |

### Phase 2 — Core Administrative Features
*Goal: Complete CRUD modules for courses, participants, and enrollments.*

| Deliverable | FRs | NFRs | BRs |
|---|---|---|---|
| Course CRUD pages and actions | FR-10–16 | NFR-13, NFR-14 | BR-11, BR-18, BR-19 |
| Participant CRUD pages and actions (admin-only creation) | FR-17–24, FR-10-AUTH | NFR-09 | BR-10, BR-12, BR-20, BR-21, BR-25 |
| Enrollment pages and status update (4-condition date validation) | FR-25–31 | — | BR-01, BR-02, BR-05 |
| Administrator dashboard | FR-51–54 | — | — |
| Participant dashboard (profile + enrolled courses) | FR-55–57, FR-60 | — | BR-13 |
| Pagination on participant and enrollment lists | — | NFR-09 | — |

### Phase 3 — Certificate Features and Public Verification
*Goal: Implement the full certificate lifecycle and public access.*

| Deliverable | FRs | NFRs | BRs |
|---|---|---|---|
| Certificate issuance (7-step atomic flow, random_bytes, Dompdf, storage/) | FR-32–38 | NFR-22, NFR-30 | BR-02, BR-03, BR-04, BR-06 |
| storage/certificates/ directory creation (outside public document root where supported; .htaccess deny rules as fallback) | FR-36 | NFR-09-SEC | BR-19-PV |
| Certificate archive with filter/search and status display | FR-39–44 | NFR-10 | BR-07, BR-09 |
| Certificate revocation workflow (mandatory reason) | FR-42, FR-43 | — | BR-07, BR-08 |
| Participant certificate list and authenticated download | FR-58, FR-59 | NFR-07 | BR-13 |
| Public certificate verification page (capped results, no PDF link) | FR-45–50, FR-51-PV, FR-52-PV | — | BR-16, BR-17, BR-18-PV, BR-19-PV |

### Phase 4 — UI Polish and Quality
*Goal: Responsive layout, accessibility, error handling, and usability.*

| Deliverable | FRs | NFRs | BRs |
|---|---|---|---|
| Responsive CSS refinement | — | NFR-12 | — |
| JavaScript client-side validation on all forms | — | NFR-13 | — |
| Custom error page (display_errors=Off, no stack traces) | — | NFR-23 | — |
| Active navigation item highlighting | — | NFR-15 | — |
| Performance review (page load timing) | — | NFR-11 | — |

### Phase 5 — Deployment and Final Testing
*Goal: Deploy the system to a web host and perform end-to-end testing.*

| Deliverable | FRs | NFRs | BRs |
|---|---|---|---|
| Upload application to shared web host | — | NFR-28 | — |
| Import schema.sql on host database | — | NFR-29 | — |
| Update config/db.php with host credentials | — | NFR-20 | — |
| Verify storage/certificates/ is outside web root on host | — | NFR-09-SEC | BR-19-PV |
| End-to-end functional test of all use cases | All FRs | — | All BRs |
| Security test: SQL injection, XSS, CSRF, IDOR, path traversal | — | NFR-01–07, NFR-09-SEC | BR-13, BR-14 |

---

## 6. Requirement Count Summary

| Category | Count |
|---|---|
| Functional Requirements (FR-01–FR-60 + FR-10-AUTH + FR-51-PV + FR-52-PV) | **63** |
| Non-Functional Requirements (NFR-01–NFR-31 + NFR-09-SEC) | **32** |
| Business Rules (BR-01–BR-25 + BR-18-PV + BR-19-PV) | **27** |
| Use Cases (UC-01–UC-19) | **19** |
| Acceptance Criteria (AC-01–AC-22) | **22** |
| University Requirements Covered | **13 / 13** |

---

*End of Requirements Traceability Matrix — Version 1.3*
