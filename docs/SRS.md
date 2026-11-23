# Software Requirements Specification (SRS)
## Training Certificate Management and Archive System

| Field | Value |
|---|---|
| Document ID | SRS-001 |
| Version | 1.3 |
| Status | Approved for Implementation |
| Course | Advanced Web Technology (CIT1303) |
| Date | 2026-11-21 |
| Revised | 2026-11-22 |
| Author | Development Team |

---

## Revision History

| Version | Date | Changes |
|---|---|---|
| 1.0 | 2026-11-21 | Initial draft. |
| 1.1 | 2026-11-21 | Corrections: account creation policy (admin-only); public search result cap and PDF download restriction; certificate number generation updated to `random_bytes()`; PDF issuance sequence and atomicity steps expanded; completion date upper bound (≤ course end date) added; standardised all status values; session logout procedure corrected (destroy + expire cookie, no post-logout regeneration); PDF library standardised to Dompdf via Composer; storage path updated to outside web root; NFR-05 corrected; NFR-30 added for Dompdf; ASM-02 updated. |
| 1.2 | 2026-11-21 | Consistency corrections: §4.0 Identifier Convention Note added explaining compound IDs (FR-10-AUTH, FR-51-PV, FR-52-PV, NFR-09-SEC). §2.1, §8.3, and NFR-09-SEC softened regarding PDF storage (storage outside document root preferred wherever hosting environment permits; .htaccess deny rules acknowledged as fallback). §5.6 Data Integrity updated with NFR-31 (UNIQUE constraint on users.email). §6.1 Step 7 and AC-08 updated to canonical 7-step sequence with full collision retry (render new PDF containing new number, up to 5 attempts), commit-failure final-file cleanup, and cross-resource risk recovery check noted. AC-05 aligned to three-tier participant deletion policy. |
| 1.3 | 2026-11-22 | Final alignment: FR-23 updated to explicitly describe all three participant deletion cases (has certs → Inactive; enrollments without certs → deletion blocked; neither → hard delete with confirmation), fully harmonising with BR-10, UC-09, and AC-05. |

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [System Scope](#2-system-scope)
3. [Stakeholders and User Roles](#3-stakeholders-and-user-roles)
4. [Functional Requirements](#4-functional-requirements)
5. [Non-Functional Requirements](#5-non-functional-requirements)
6. [System Workflows](#6-system-workflows)
7. [Acceptance Criteria](#7-acceptance-criteria)
8. [Assumptions and Constraints](#8-assumptions-and-constraints)

---

## 1. Project Overview

### 1.1 Project Title

**Training Certificate Management and Archive System**

### 1.2 Project Description

The Training Certificate Management and Archive System is a web-based application built with PHP, MySQL, HTML5, CSS3, and JavaScript. It enables an educational or training organisation to manage training courses, register and enroll participants, track enrollment progress, generate PDF certificates upon course completion, archive those certificates, and provide both participants and public visitors with the ability to search for and verify certificates.

The system is delivered as a single-server PHP/MySQL application served through Apache (XAMPP) and requires no external SaaS integrations.

### 1.3 Problem Statement

Training organisations that issue completion certificates face several recurring operational problems:

- Certificate records are kept in spreadsheets or paper files, making them difficult to search, verify, or audit.
- There is no standardised, tamper-evident certificate numbering system.
- Participants have no self-service way to access or download their own certificates.
- Public employers or institutions that need to verify a certificate must contact the organisation directly, causing delays.
- Revoked or invalid certificates are not clearly distinguished from valid ones in existing manual archives.

This system addresses all of the above problems within a secure, role-based web application.

### 1.4 Project Objectives

| ID | Objective |
|---|---|
| OBJ-01 | Provide administrators with a centralised dashboard to manage courses, participants, enrollments, and certificates. |
| OBJ-02 | Automate the generation of uniquely numbered, downloadable PDF certificates upon course completion. |
| OBJ-03 | Maintain a permanent, searchable archive of all issued and revoked certificates. |
| OBJ-04 | Allow participants to log in and self-serve their own certificate records. |
| OBJ-05 | Allow any public visitor to verify the authenticity of a certificate without registering. |
| OBJ-06 | Enforce strict role-based access control so each user can only access what their role permits. |
| OBJ-07 | Satisfy all mandatory university project requirements for CIT1303. |

### 1.5 Expected Benefits

- Elimination of paper-based certificate records.
- Instant searchable archive accessible 24/7.
- Reduced administrative workload through automated PDF generation.
- Increased trust through publicly verifiable, uniquely numbered certificates.
- Secure, auditable history that cannot be accidentally deleted.

---

## 2. System Scope

### 2.1 In-Scope Functionality

The following capabilities are within scope and must be implemented:

- Administrator-managed user accounts (login, logout, session management). No public self-registration.
- Role-based access control for two roles: Administrator and Participant.
- Full CRUD management of training courses.
- Full CRUD management of participant records (created by administrators only).
- Enrollment of participants in courses.
- Enrollment status tracking: Enrolled → In Progress → Completed.
- Certificate issuance for completed enrollments.
- Automatic generation of a unique certificate number per issued certificate using cryptographically secure randomness.
- PDF certificate generation (via Dompdf) containing participant name, course title, completion date, and certificate number.
- PDF files stored outside the publicly accessible web root directory wherever the hosting environment permits; `.htaccess` access denial rules used as a fallback when a separate storage location is unavailable.
- Certificate archive with status indicators: Valid / Revoked.
- Certificate revocation by administrators, with reason and date recorded.
- Participant dashboard (profile, enrolled courses, certificate list, certificate download).
- Administrative dashboard with summary statistics.
- Public certificate verification page (search by certificate number or participant full name; limited result set; no PDF download).
- Input validation on all forms (server-side mandatory; client-side progressive enhancement).
- Responsive, mobile-friendly user interface.

### 2.2 Out-of-Scope Functionality

The following are explicitly excluded from this project:

- Public self-registration for any user role.
- Email notification system (no SMTP integration).
- Payment processing of any kind.
- Online examination or assessment functionality.
- AI-driven features or external API integrations.
- Multi-language (i18n) support.
- Mobile native applications (iOS or Android).
- Real-time features such as WebSockets.
- Multi-tenancy (the system serves one organisation).
- Digital signature or blockchain-based certificate verification.
- File uploads for participant profile images.
- Public download of PDF certificates (public visitors may view certificate details only).

### 2.3 System Boundaries

```
┌─────────────────────────────────────────────────────────────────────┐
│                        Web Browser (Client)                         │
│          HTML5 / CSS3 / JavaScript (vanilla, no SPA framework)      │
└──────────────────────────┬──────────────────────────────────────────┘
                           │ HTTP / HTTPS
┌──────────────────────────▼──────────────────────────────────────────┐
│                     Apache Web Server (XAMPP)                       │
│                     PHP 8.2.4 Application Layer                     │
│       Session management · PDO · Business logic · Dompdf           │
└──────────────────────────┬──────────────────────────────────────────┘
                           │ PDO / pdo_mysql
┌──────────────────────────▼──────────────────────────────────────────┐
│                  MariaDB 10.4.28 Database Server                    │
│                  (XAMPP, localhost:3306)                            │
└─────────────────────────────────────────────────────────────────────┘

Storage (outside web root):
  storage/certificates/   ← Generated PDF files (not URL-accessible)
```

**External dependencies:** None. The system operates entirely within the local or hosted XAMPP environment. Dompdf is managed via Composer and runs on the local server.

---

## 3. Stakeholders and User Roles

### 3.1 Stakeholder Overview

| Stakeholder | Interest |
|---|---|
| Organisation Administrator | Needs efficient management of courses, participants, and certificates. |
| Training Participant | Needs secure, self-service access to personal certificate records. |
| Public Visitor / Employer | Needs to verify the authenticity of a presented certificate. |
| University Assessor | Evaluates the system against CIT1303 project requirements. |
| Developer | Builds and maintains the system. |

### 3.2 Administrator Role

**Description:** A trusted staff member of the training organisation. The initial administrator account is created by the database seed SQL script. Additional administrator accounts, if any, require direct database access; there is no in-application UI for creating administrators.

**Access Level:** Full system access over all managed entities. Cannot escalate privileges to manage other administrators through the application UI.

**Permissions:**

| Area | Permissions |
|---|---|
| Dashboard | View summary statistics |
| Course Management | Create, Read, Update, Delete courses |
| Participant Management | Create, Read, Update, Deactivate / Delete participant accounts |
| Enrollment Management | Enroll participants, change enrollment status, record completion dates |
| Certificate Management | Issue certificates, revoke certificates, download PDFs, view archive |
| Public Verification | Access (all roles and visitors have this) |

**Account creation policy:** Participant accounts are created exclusively by administrators through the participant management module. No public registration form exists.

### 3.3 Participant Role

**Description:** A person who has attended or is attending a training course and whose account has been created by an administrator.

**Access Level:** Read-only access limited strictly to their own records.

**Account status values:** Active / Inactive.

**Permissions:**

| Area | Permissions |
|---|---|
| Dashboard | View personal dashboard |
| Profile | View own profile |
| Courses | View own enrolled/completed courses |
| Certificates | View own issued certificates and details |
| Certificate Download | Download own PDF certificates |
| Public Verification | Access (all roles and visitors have this) |

**Explicit Restrictions:**
- Cannot view other participants' records.
- Cannot manage courses, enrollments, or other participants.
- Cannot issue or revoke certificates.
- Cannot create their own account (created by administrators only).

### 3.4 Public Visitor (Unauthenticated)

**Description:** Any internet user who accesses the public certificate verification page without logging in.

**Permissions:**
- Access the certificate search/verification page without authentication.
- Search by certificate number or participant full name.
- View public-safe certificate details: certificate number, participant full name, course title, completion date, issuance date, and validity status.
- View a maximum of 20 results per name search.

**Explicit Restrictions:**
- Cannot access the participant's email address, phone number, date of birth, or any other private information.
- Cannot download certificate PDF files.
- Cannot access any authenticated area of the system.

---

## 4. Functional Requirements

### 4.0 Identifier Convention Note

The requirements numbering throughout this specification predominantly uses sequential identifiers (`FR-01` through `FR-60`, `NFR-01` through `NFR-30`, etc.). Where specific requirements share a numerical base with an existing requirement in another section, compound identifiers are used (specifically `FR-10-AUTH`, `FR-51-PV`, `FR-52-PV`, and `NFR-09-SEC`). These compound IDs preserve the existing numbering scheme and prevent disruptive renumbering. They will be consolidated into a unified sequential scheme during a future formal schema revision if approved.

### 4.1 Authentication Module

| ID | Requirement | Priority |
|---|---|---|
| FR-01 | The system shall provide a login page with an email address and password form. | Must Have |
| FR-02 | The system shall authenticate users by comparing the submitted password against a bcrypt hash stored in the database using `password_verify()`. | Must Have |
| FR-03 | The system shall redirect authenticated Administrators to the administrator dashboard upon successful login. | Must Have |
| FR-04 | The system shall redirect authenticated Participants to the participant dashboard upon successful login. | Must Have |
| FR-05 | The system shall display a generic error message on login failure without revealing which field (email or password) is incorrect. | Must Have |
| FR-06 | On logout, the system shall: (1) clear all session data with `session_unset()`; (2) destroy the server-side session with `session_destroy()`; (3) expire the session cookie by overwriting it with a past expiry time using the same cookie parameters; then redirect to the login page. | Must Have |
| FR-07 | The system shall protect all authenticated pages by checking for a valid session before serving any content. Unauthenticated requests shall be redirected to the login page. | Must Have |
| FR-08 | The system shall enforce role separation: an authenticated Participant must be blocked from accessing any administrator page, and vice versa. | Must Have |
| FR-09 | The system shall regenerate the PHP session ID upon successful login to mitigate session fixation attacks. Session ID regeneration is not performed on logout; logout performs session destruction instead. | Must Have |
| FR-10-AUTH | No public self-registration form shall exist. Participant accounts are created exclusively by administrators. The initial administrator account is provided by the database seed script. | Must Have |

> **Note:** FR-10-AUTH uses a compound ID to avoid renumbering FR-10 (Course Management). It will be consolidated into the authentication module in the next schema revision if IDs are refactored.

### 4.2 Course Management Module

| ID | Requirement | Priority |
|---|---|---|
| FR-10 | The system shall allow an Administrator to create a new training course by providing a course title, description, start date, end date, and location. | Must Have |
| FR-11 | The system shall display a list of all training courses with their title, dates, and status (Active / Inactive) to the Administrator. | Must Have |
| FR-12 | The system shall allow an Administrator to view the full details of a single course, including its participant enrollments. | Must Have |
| FR-13 | The system shall allow an Administrator to edit any field of an existing course. | Must Have |
| FR-14 | The system shall allow an Administrator to delete a course only if it has no enrollments. A course with existing enrollments shall not be deletable to preserve data integrity. | Must Have |
| FR-15 | The system shall validate all course form inputs: title is required (3–255 characters); start date must be a valid calendar date; end date must be on or after start date; description is optional (max 2000 characters); location is optional (max 255 characters). | Must Have |
| FR-16 | The system shall display the total number of enrolled participants on the course list and detail views. | Should Have |

**Course status values:** Active / Inactive.

### 4.3 Participant Management Module

| ID | Requirement | Priority |
|---|---|---|
| FR-17 | The system shall allow an Administrator to create a participant account by providing: full name, email address, phone number (optional), date of birth (optional), and an initial password. No public registration form exists. | Must Have |
| FR-18 | The system shall store all passwords as bcrypt hashes. Plain-text passwords shall never be stored or logged. | Must Have |
| FR-19 | The system shall enforce unique email addresses across all users (administrators and participants). | Must Have |
| FR-20 | The system shall display a paginated list of all participants with their name, email, account status, and registration date to the Administrator. | Must Have |
| FR-21 | The system shall allow an Administrator to view a participant's full profile, including their enrollment history and certificate list. | Must Have |
| FR-22 | The system shall allow an Administrator to edit a participant's name, email, and phone number. | Must Have |
| FR-23 | The system shall allow an Administrator to remove a participant record following a three-tier policy: (1) if the participant has one or more issued certificates (regardless of status), the account shall be deactivated (account status set to Inactive) and never permanently deleted; (2) if the participant has one or more enrollment records but no issued certificates, permanent deletion shall be blocked to preserve enrollment history, with an option to deactivate the account instead; (3) if the participant has neither certificates nor enrollments, the record may be permanently deleted after administrator confirmation. | Must Have |
| FR-24 | The system shall validate all participant form inputs: full name required (max 150 characters); email must be a valid RFC 5322 format; phone optional; date of birth, if provided, must be a past date. | Must Have |

**Participant account status values:** Active / Inactive.

### 4.4 Enrollment Management Module

| ID | Requirement | Priority |
|---|---|---|
| FR-25 | The system shall allow an Administrator to enroll a participant in a training course by selecting the participant and course from managed lists. | Must Have |
| FR-26 | The system shall reject an enrollment attempt if the participant is already enrolled in the selected course (see BR-01). | Must Have |
| FR-27 | The system shall assign the enrollment status "Enrolled" at the time of enrollment creation. | Must Have |
| FR-28 | The system shall allow an Administrator to change an enrollment status through the following ordered states only: Enrolled → In Progress → Completed. | Must Have |
| FR-29 | The system shall require a Completion Date to be recorded when an enrollment is marked as "Completed". | Must Have |
| FR-30 | The system shall not allow a Completed enrollment's status to be changed back to Enrolled or In Progress once a certificate has been issued for that enrollment. | Must Have |
| FR-31 | The system shall display a list of all enrollments per course, showing participant name, status, and completion date if applicable. | Must Have |

**Enrollment status values:** Enrolled / In Progress / Completed.

### 4.5 Certificate Generation Module

| ID | Requirement | Priority |
|---|---|---|
| FR-32 | The system shall allow an Administrator to issue a certificate only for enrollments whose status is "Completed". | Must Have |
| FR-33 | The system shall reject a certificate issuance request if a certificate has already been issued for the same enrollment (see BR-03). | Must Have |
| FR-34 | The system shall automatically generate a unique certificate number at the time of issuance using the format `CERT-YYYY-XXXXXXXX`, where YYYY is the four-digit issuance year and XXXXXXXX is an 8-character uppercase alphanumeric string derived from PHP's `random_bytes()`. The database UNIQUE constraint on the `certificate_number` column is the definitive uniqueness guarantee. If a collision is detected, the system shall retry generation up to 5 times before failing with an error. | Must Have |
| FR-35 | The system shall generate a PDF certificate using Dompdf, containing: the participant's full name, the course title, the course completion date, the certificate number, and the organisation name. Unicode font support must be enabled in Dompdf to correctly render participant names containing non-ASCII characters. | Must Have |
| FR-36 | The system shall store the generated PDF file in a server-side directory located outside the publicly accessible web root (e.g. `storage/certificates/`). The file must not be accessible via a predictable direct URL. | Must Have |
| FR-37 | The system shall store the certificate record in the database including: certificate number, enrollment ID, issuance date, internal storage file path, and initial status "Valid". | Must Have |
| FR-38 | The system shall display a success confirmation with the generated certificate number after successful issuance. | Must Have |

**Certificate status values:** Valid / Revoked.

### 4.6 Certificate Archive Module

| ID | Requirement | Priority |
|---|---|---|
| FR-39 | The system shall maintain a certificate archive accessible to Administrators, displaying all issued certificates with their number, participant name, course title, issuance date, and status (Valid / Revoked). | Must Have |
| FR-40 | The system shall allow an Administrator to view the full details of any archived certificate. | Must Have |
| FR-41 | The system shall allow an Administrator to download the PDF file of any archived certificate, served through an authenticated PHP controller that validates the file path. | Must Have |
| FR-42 | The system shall allow an Administrator to revoke a certificate. Revocation must record a mandatory revocation reason and the date of revocation. | Must Have |
| FR-43 | The system shall retain revoked certificates in the archive and display them with a clear "Revoked" status indicator. The PDF must remain accessible to administrators. | Must Have |
| FR-44 | The system shall allow Administrators to filter the archive by status (All / Valid / Revoked) and to search by certificate number or participant name. | Should Have |

### 4.7 Certificate Search and Public Verification Module

| ID | Requirement | Priority |
|---|---|---|
| FR-45 | The system shall provide a publicly accessible certificate verification page that does not require login or account creation. | Must Have |
| FR-46 | The system shall allow a public visitor to search for a certificate by entering either a certificate number (exact match) or a participant's full name (exact match). | Must Have |
| FR-47 | On a successful search, the system shall display: certificate number, participant full name, course title, completion date, issuance date, and validity status (Valid / Revoked). | Must Have |
| FR-48 | The system shall not display the participant's email address, phone number, date of birth, internal user ID, or any other private account information on the public verification page. | Must Have |
| FR-49 | The system shall clearly display "REVOKED — This certificate is no longer valid" for revoked certificates on the public verification page. | Must Have |
| FR-50 | The system shall display a "No certificate found" message when a search yields no matching result, without revealing whether the input was close to a valid record. | Must Have |
| FR-51-PV | For name-based searches that return multiple results, the system shall display a maximum of 20 matching certificate records per search. | Must Have |
| FR-52-PV | The public verification page must not provide any link, button, or mechanism to download the PDF certificate file. PDF access is restricted to authenticated users only. | Must Have |

> **Note:** FR-51-PV and FR-52-PV use compound IDs to avoid renumbering FR-51 (Admin Dashboard). They will be consolidated in the next schema revision.

### 4.8 Administrator Dashboard Module

| ID | Requirement | Priority |
|---|---|---|
| FR-51 | The system shall display an administrator dashboard immediately after login, containing summary statistics. | Must Have |
| FR-52 | The dashboard shall display the total number of: active courses, registered participants (Active), total enrollments, total issued certificates (Valid count and Revoked count). | Must Have |
| FR-53 | The dashboard shall display a list of recent activity such as the last 10 enrollments or certificate issuances. | Should Have |
| FR-54 | The dashboard shall provide navigation links to all administrative modules: Courses, Participants, Enrollments, Certificates. | Must Have |

### 4.9 Participant Dashboard Module

| ID | Requirement | Priority |
|---|---|---|
| FR-55 | The system shall display a participant dashboard immediately after the participant logs in. | Must Have |
| FR-56 | The participant dashboard shall display the participant's full name and profile information. | Must Have |
| FR-57 | The participant dashboard shall list all courses the participant is enrolled in, with their current enrollment status (Enrolled / In Progress / Completed). | Must Have |
| FR-58 | The participant dashboard shall list all certificates issued to the participant with their certificate number, course title, issuance date, and status (Valid / Revoked). | Must Have |
| FR-59 | The system shall allow the participant to download their own PDF certificate from the participant dashboard, served through an authenticated PHP controller that verifies ownership. | Must Have |
| FR-60 | The participant shall not be able to view or access another participant's data through any URL manipulation or parameter tampering. All data queries for a participant must use the session `user_id` as the sole identifier. | Must Have |

---

## 5. Non-Functional Requirements

### 5.1 Security

| ID | Requirement |
|---|---|
| NFR-01 | All database queries must use PDO prepared statements with bound parameters. No raw SQL string concatenation with user input is permitted. |
| NFR-02 | All output rendered to HTML must be escaped using `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` or an equivalent wrapper to prevent Cross-Site Scripting (XSS). |
| NFR-03 | All state-changing forms (POST requests) must include and validate a synchroniser CSRF token. Requests with missing or invalid tokens must be rejected with HTTP 403. |
| NFR-04 | Passwords must be hashed using PHP's `password_hash()` with `PASSWORD_BCRYPT` and verified with `password_verify()`. |
| NFR-05 | The PHP session ID must be regenerated (using `session_regenerate_id(true)`) upon successful login. On logout, the session is destroyed and the session cookie is expired; session ID regeneration is not performed as a substitute for destruction. |
| NFR-06 | Direct URL access to PHP logic files located outside the `public/` directory must be prevented by Apache configuration (e.g. `.htaccess` deny rules or placing files above the document root). |
| NFR-07 | All server-side file path resolution for PDF downloads must validate that the resolved absolute path begins with the expected `storage/certificates/` directory prefix to prevent path traversal attacks. |
| NFR-08 | The database credentials must be stored in `config/db.php`, which is located outside the web-accessible `public/` directory. |
| NFR-09-SEC | The `storage/certificates/` directory must not be web-accessible; this is enforced by locating it outside the document root wherever the hosting environment supports it, with `.htaccess` deny rules as a fallback. Certificate PDFs are served exclusively through authenticated PHP download controllers. |

### 5.2 Performance

| ID | Requirement |
|---|---|
| NFR-09 | Pages with lists of participants or certificates must implement pagination, displaying no more than 25 records per page. |
| NFR-10 | Database tables must have appropriate indexes on foreign key columns and frequently searched columns (e.g. `certificate_number`, `email`). |
| NFR-11 | The application should return a page response within 2 seconds under normal single-user load on the local XAMPP environment. |

### 5.3 Usability

| ID | Requirement |
|---|---|
| NFR-12 | The user interface must be responsive and render correctly on screen widths between 375px (mobile) and 1920px (desktop). |
| NFR-13 | All forms must display clear, field-specific validation error messages adjacent to the offending input field. |
| NFR-14 | All destructive actions (delete, deactivate, revoke) must require a confirmation step before execution. |
| NFR-15 | The navigation menu must clearly indicate the currently active page. |
| NFR-16 | The public verification page must function correctly with JavaScript disabled (progressive enhancement). |

### 5.4 Maintainability

| ID | Requirement |
|---|---|
| NFR-17 | The application must follow a consistent file and folder structure separating configuration, models, actions, public pages, assets, and storage. |
| NFR-18 | PHP code must use PDO-based model classes to separate data access logic from presentation logic. |
| NFR-19 | Shared HTML components (header, footer, navigation) must be implemented as PHP include files and reused across pages. |
| NFR-20 | No hardcoded database credentials may appear in any file other than `config/db.php`. |

### 5.5 Reliability

| ID | Requirement |
|---|---|
| NFR-21 | The PDO connection must be configured with `PDO::ERRMODE_EXCEPTION` so database errors are caught and handled rather than silently ignored. |
| NFR-22 | Certificate issuance must follow the atomic sequence defined in BR-06. If any step fails, all database changes must be rolled back and any partially generated files must be cleaned up. The process must handle unexpected PHP process termination as a known risk and document a recovery/consistency check procedure. |
| NFR-23 | The system must display a user-friendly error page (not a PHP stack trace) when an unhandled exception occurs in the production configuration (`display_errors = Off`). |

### 5.6 Data Integrity

| ID | Requirement |
|---|---|
| NFR-24 | All relational foreign key constraints must be enforced at the database level (InnoDB engine with FOREIGN KEY constraints and appropriate ON DELETE actions). |
| NFR-25 | `certificate_number` must have a UNIQUE constraint at the database level. |
| NFR-26 | The `enrollment_id` column in the `certificates` table must have a UNIQUE constraint at the database level to enforce the one-certificate-per-enrollment rule. |
| NFR-27 | The `(participant_id, course_id)` pair in the `enrollments` table must have a composite UNIQUE constraint at the database level. |
| NFR-31 | The `email` column in the `users` table must have a UNIQUE constraint at the database level to enforce email address uniqueness across all user accounts. |

### 5.7 Deployment

| ID | Requirement |
|---|---|
| NFR-28 | The application must be deployable to a shared web hosting environment that supports PHP 8.x, Composer, and MySQL/MariaDB. |
| NFR-29 | The database schema must be provided as a single executable SQL file (`database/schema.sql`) that creates all tables, indexes, constraints, and an initial administrator account with a hashed password. |

### 5.8 PDF Generation

| ID | Requirement |
|---|---|
| NFR-30 | The application must use **Dompdf** (installed via Composer) for PDF certificate generation. No external SaaS PDF API is permitted. Dompdf must be configured to enable Unicode/UTF-8 font support (`isRemoteEnabled` as appropriate for fonts) so that participant names containing non-ASCII characters render correctly. |

---

## 6. System Workflows

### 6.1 Core Certificate Issuance Workflow

**Step 1 — Course Creation**
An Administrator logs in and navigates to Course Management. The Administrator creates a new course, entering the course title, description, start date, end date, and location. The system validates the input, saves the course with status "Active", and assigns it a unique course ID.

**Step 2 — Participant Registration**
The Administrator navigates to Participant Management and creates a participant account by entering the participant's details and an initial password. The system validates the details, hashes the password with bcrypt, stores the record with account status "Active", and the participant can now log in. No public registration path exists.

**Step 3 — Enrollment**
The Administrator navigates to the course detail page and enrolls the participant. The system checks that the participant is not already enrolled in this course (BR-01). If valid, the system creates an enrollment record with status "Enrolled".

**Step 4 — Progress Tracking**
As the training progresses, the Administrator updates the enrollment status to "In Progress".

**Step 5 — Marking Completion**
Once training is finished, the Administrator marks the enrollment as "Completed" and enters the completion date. The system validates the completion date: it must be a valid calendar date, not in the future, not before the course start date, and not after the course end date.

**Step 6 — Certificate Issuance**
The Administrator navigates to the enrollment record and clicks "Issue Certificate". The system verifies:
- The enrollment status is "Completed" and a completion date is recorded.
- No certificate has already been issued for this enrollment (BR-03).

**Step 7 — Atomic Issuance Sequence (see BR-06)**
1. The system verifies enrollment eligibility (status is "Completed" with completion date) and confirms no certificate exists for this enrollment.
2. The system generates a candidate certificate number using `random_bytes()`: `CERT-YYYY-XXXXXXXX`.
3. The system begins a database transaction with `$pdo->beginTransaction()`.
4. Dompdf renders the certificate HTML template (with candidate certificate number, participant name, course title, completion date) to a temporary PDF file in a temp directory.
5. The system inserts the certificate record into the `certificates` table with status "Valid". If a UNIQUE collision occurs on `certificate_number`, the transaction is rolled back, the temp file is deleted, a new candidate certificate number is generated, a new PDF containing that new number is rendered, and the sequence retries (up to 5 attempts total). If any other error occurs, the transaction is rolled back, the temp file is deleted, and the process aborts.
6. The system moves the temp PDF file to its final protected location in `storage/certificates/{certificate_number}.pdf`.
7. The database transaction is committed. If the commit fails after the PDF has been moved to its final location, the system attempts rollback and deletes the final file if it exists.
8. Process termination risk: Unexpected PHP process termination between file movement (step 6) and database commit (step 7) is a known cross-resource consistency risk. A documented recovery check procedure (comparing database records against files on disk in `storage/certificates/`) allows administrators to identify and resolve any orphaned files.

**Step 8 — Participant Access**
The participant logs in and navigates to their dashboard. They see the new certificate listed under "My Certificates" with status "Valid". They click "Download" and receive the PDF through the authenticated download controller.

**Step 9 — Public Verification**
An employer visits the public verification page and enters the certificate number. The system looks up the certificate and displays: participant full name, course title, completion date, issuance date, certificate number, and validity status. Private information is not shown. No PDF download link is provided.

### 6.2 Certificate Revocation Workflow

1. The Administrator navigates to the Certificate Archive.
2. The Administrator locates the certificate and clicks "Revoke".
3. The system displays a confirmation modal with a required "Reason for Revocation" text field.
4. The Administrator enters a reason and confirms.
5. The system updates the certificate: `status = Revoked`, `revoked_at = current timestamp`, `revocation_reason = entered reason`.
6. The certificate remains in the archive with a clear "Revoked" badge.
7. The participant's dashboard still lists the certificate, now marked "Revoked".
8. The public verification page, if queried, displays the certificate as "REVOKED — This certificate is no longer valid".

### 6.3 Participant Self-Service Workflow

1. The participant navigates to the login page.
2. The participant enters their email and password.
3. The system authenticates, regenerates the session ID, stores the session, and redirects to the participant dashboard.
4. The participant views their enrolled courses and statuses.
5. The participant navigates to "My Certificates".
6. The participant clicks "View" to see certificate details.
7. The participant clicks "Download" to receive their PDF via the authenticated download controller.

---

## 7. Acceptance Criteria

| ID | Feature | Acceptance Criterion |
|---|---|---|
| AC-01 | Login | A valid administrator email and password must result in redirection to the administrator dashboard and an active session with a regenerated session ID. An invalid credential must display a generic error without a stack trace. |
| AC-02 | Logout | Clicking logout must clear session data, destroy the server-side session, and expire the session cookie. The browser Back button after logout must not restore access to a protected page. |
| AC-03 | Role Separation | An authenticated participant attempting to access any `/admin/` URL must be redirected to the participant dashboard with an "Access Denied" message. An authenticated administrator attempting to access any `/participant/` URL must be redirected to the administrator dashboard. |
| AC-04 | Course CRUD | An administrator must be able to create, read, update, and delete a course. Deleting a course with enrollments must fail with a clear error message. Status values used must be "Active" and "Inactive" only. |
| AC-05 | Participant CRUD | An administrator must be able to create, read, update, and deactivate/delete a participant. A participant with issued certificates must be set to "Inactive", not permanently deleted; a participant with enrollments but no certificates is blocked from deletion; a participant with neither may be permanently deleted after confirmation. No public registration link exists. |
| AC-06 | Enrollment | An administrator must be able to enroll a participant in a course. Attempting to enroll the same participant in the same course twice must display a duplicate-enrollment error. |
| AC-07 | Enrollment Status | An administrator must be able to transition an enrollment from Enrolled → In Progress → Completed, providing a completion date at the final step. The completion date must be validated against: not future; not before course start date; not after course end date. |
| AC-08 | Certificate Issuance | For a Completed enrollment without a prior certificate, issuing a certificate must generate a unique `CERT-YYYY-XXXXXXXX` number using `random_bytes()`, produce a Dompdf PDF with all required fields, store the PDF in `storage/certificates/`, and persist the certificate record. |
| AC-09 | Duplicate Certificate | Attempting to issue a second certificate for the same enrollment must fail with an appropriate error message. |
| AC-10 | Certificate Archive | The administrator archive must list all certificates. Filtering by "Revoked" must return only revoked certificates. Filtering by "Valid" must return only valid certificates. |
| AC-11 | Revocation | Revoking a certificate must change its status to "Revoked" in the database with a recorded reason and timestamp. The certificate must still be viewable in the archive. The public verification page must display "REVOKED — This certificate is no longer valid". |
| AC-12 | Public Verification — Valid | Searching by a valid certificate number must return the participant name, course title, completion date, issuance date, and "Valid" status. No email, phone, or PDF download link must appear. |
| AC-13 | Public Verification — Not Found | Searching for a non-existent certificate number must display "No certificate found" and must not throw an error. |
| AC-14 | Participant Dashboard | A logged-in participant must see only their own enrollments and certificates. Attempting to access another participant's data by modifying URL parameters must be blocked (HTTP 403 or redirect). |
| AC-15 | PDF Download (Participant) | A participant must be able to download their own certificate PDF through the authenticated controller. Attempting to access another participant's PDF must be blocked. |
| AC-16 | PDF Not Publicly Accessible | Attempting to access `storage/certificates/CERT-*.pdf` via a direct browser URL must return a 403 or 404 response, confirming the directory is not web-accessible. |
| AC-17 | Input Validation | Submitting a course creation form with an end date before the start date must display a validation error and must not save the record. Submitting a completion date after the course end date must display a validation error. |
| AC-18 | SQL Injection | Entering `' OR '1'='1` in the certificate search field must not return any unauthorised results and must not cause a database error. |
| AC-19 | XSS Prevention | Entering `<script>alert(1)</script>` as a participant name must display the escaped text, not execute the script. |
| AC-20 | CSRF Protection | Submitting a POST form without a valid CSRF token must be rejected with HTTP 403. |
| AC-21 | Responsiveness | The administrator dashboard and participant dashboard must render correctly on a 375px wide mobile viewport without horizontal overflow. |
| AC-22 | Public Search Result Cap | A name-based public search matching more than 20 certificates must return at most 20 results, with a note indicating the limit was reached. |

---

## 8. Assumptions and Constraints

### 8.1 Confirmed Requirements (from Assignment Brief)

- PHP, MySQL (MariaDB), HTML5, CSS3, and JavaScript must be used.
- No Laravel or heavyweight framework may be used.
- Two user roles are required: Administrator and Participant.
- CRUD must be implemented for at least two entities.
- At least three related database tables are required.
- An administrative dashboard must be provided.
- Input validation and error handling are mandatory.
- The system must be deployable to a web host.

### 8.2 Reasonable Implementation Assumptions

| ID | Assumption | Justification |
|---|---|---|
| ASM-01 | The initial administrator account is created by the SQL seed script. There is no self-registration for any user role. | Prevents unauthorised escalation. Consistent with university assignment scope. Confirmed policy. |
| ASM-02 | PDF generation uses **Dompdf** installed via Composer. Unicode font support is enabled in the Dompdf configuration. | Dompdf is a widely used, well-documented PHP PDF library. It requires no external service. FPDF and TCPDF are excluded as alternatives; Dompdf is the confirmed choice. |
| ASM-03 | Public verification searches use exact matches only (no fuzzy or partial matching). A name search matches the full name field exactly (case-insensitive). | Prevents enumeration attacks and simplifies implementation. |
| ASM-04 | The certificate number format `CERT-YYYY-XXXXXXXX` is acceptable to the university assessor. | No specific format was mandated by the brief. |
| ASM-05 | "Deployment to a web host" refers to a shared PHP hosting environment (e.g. cPanel), not a cloud provider. | Consistent with typical CIT1303 requirements. |
| ASM-06 | The system will not send any emails. Password resets are handled by an administrator (manual password update or direct database reset). | Email (SMTP) is listed as out-of-scope and was not mandated in the brief. |
| ASM-07 | A "soft delete" for participants means setting account status to "Inactive" rather than removing the row. The term "Inactive" is used consistently instead of `is_active = 0` in user-facing documentation. | Preserves certificate data integrity and referential consistency. |
| ASM-08 | Unexpected PHP process termination during certificate issuance (e.g. server crash between file move and transaction commit) is a known risk. A periodic consistency check script (outside project scope) or manual check can identify orphaned files or incomplete records. | Fully atomic recovery across a filesystem operation and a database transaction requires infrastructure beyond the scope of a university assignment. |

### 8.3 Known Constraints

| Constraint | Detail |
|---|---|
| Technology | Must use PHP, MySQL, HTML5, CSS3, JavaScript. No framework above Composer-installed utility libraries (e.g. Dompdf). |
| Deployment environment | XAMPP (Apache + MariaDB) for development; shared web host for final submission. |
| Team size | University project — single developer or small team. |
| Timeline | Academic semester deadline applies. |
| No external services | No SMTP, no cloud storage, no payment gateway, no third-party API. |
| PDF storage | PDF files stored outside publicly accessible web root wherever supported by the hosting environment; `.htaccess` deny fallback used where separate storage is unavailable. |

---

*End of SRS Document — Version 1.3*
