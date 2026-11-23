# Business Rules Specification
## Training Certificate Management and Archive System

| Field | Value |
|---|---|
| Document ID | BR-001 |
| Version | 1.3 |
| Related SRS | SRS-001 v1.3 |
| Date | 2026-11-21 |
| Revised | 2026-11-22 |

---

## Revision History

| Version | Date | Changes |
|---|---|---|
| 1.0 | 2026-11-21 | Initial version. |
| 1.1 | 2026-11-21 | BR-04 updated: `random_bytes()` specified as CSPRNG source; "guaranteed unique" language corrected; DB UNIQUE constraint identified as the definitive guarantee. BR-05 updated: added upper bound — completion date must not be after course end date. BR-06 updated: full 7-step atomic issuance sequence with temp-file strategy, rollback, and process-termination risk documented. BR-10 updated: "is_active = 0" language aligned to standardised status value "Inactive". BR-20 extended to cover administrator accounts. BR-25 added: account creation policy (admin-only). Status value glossary added. |
| 1.2 | 2026-11-21 | BR-06 updated: canonical 7-step sequence reordered (candidate number generation before transaction start), collision retry clarified (render new PDF with new number, up to 5 attempts), commit-failure cleanup of final PDF file documented, process-termination risk and recovery check procedure documented. BR-10 updated: three-tier participant deletion policy formally defined (has certs → Inactive; enrollments without certs → deletion blocked; neither → hard delete). BR-19-PV softened: storage outside document root preferred where supported by hosting environment, .htaccess fallback acknowledged. Identifier Convention Note added. |
| 1.3 | 2026-11-22 | Final alignment: Verified full alignment with SRS v1.3 (FR-23 three-tier deletion policy) and cross-document consistency across all business rules. |

---

## Status Value Glossary

The following status values are canonical and must be used consistently in all code, database values, and UI labels:

| Entity | Allowed Status Values |
|---|---|
| Enrollment | `Enrolled`, `In Progress`, `Completed` |
| Certificate | `Valid`, `Revoked` |
| Participant Account | `Active`, `Inactive` |
| Course | `Active`, `Inactive` |

---

## Identifier Convention Note

This specification and its companion documents use a few compound requirement and rule identifiers (such as `FR-10-AUTH`, `FR-51-PV`, `FR-52-PV`, and `NFR-09-SEC`). These compound IDs were introduced when a specific requirement shares a numerical base with an existing requirement in another functional area. They do not replace or alter existing sequential IDs (which remain completely preserved). Compound IDs will be consolidated into a unified sequential scheme during a future formal schema revision if approved.

---

## Table of Contents

1. [Enrollment Rules](#1-enrollment-rules)
2. [Certificate Issuance Rules](#2-certificate-issuance-rules)
3. [Certificate Archive and Revocation Rules](#3-certificate-archive-and-revocation-rules)
4. [Data Integrity Rules](#4-data-integrity-rules)
5. [Access Control Rules](#5-access-control-rules)
6. [Public Verification Rules](#6-public-verification-rules)
7. [Course Management Rules](#7-course-management-rules)
8. [Participant Management Rules](#8-participant-management-rules)
9. [System Operational Rules](#9-system-operational-rules)

---

## 1. Enrollment Rules

### BR-01 — No Duplicate Enrollment

**Rule:** A participant cannot be enrolled in the same training course more than once.

**Trigger:** Enrollment creation.

**Condition:** When an administrator attempts to enroll a participant in a course, the system must check for an existing enrollment record with the same `(participant_id, course_id)` pair in the `enrollments` table, regardless of status.

**Enforcement mechanism:**
- Database: Composite UNIQUE constraint on `(participant_id, course_id)` in the `enrollments` table.
- Application: Pre-insert query check with an appropriate error message returned to the user.

**Violation consequence:** The enrollment is rejected and the administrator is shown the message: "This participant is already enrolled in this course."

**Source:** Mandatory university requirement / core business logic.

---

### BR-02 — Completion Required Before Certificate

**Rule:** A certificate cannot be issued for an enrollment that does not have the status "Completed".

**Trigger:** Certificate issuance request.

**Condition:** At the time of certificate issuance, the system must verify that the target enrollment's `status` column equals `Completed`. An enrollment with status `Enrolled` or `In Progress` is ineligible.

**Enforcement mechanism:**
- Application: Status check before the certificate issuance action is processed.

**Violation consequence:** The issuance is rejected with the message: "Certificate can only be issued for completed enrollments."

**Source:** Mandatory university requirement.

---

### BR-03 — One Certificate Per Enrollment

**Rule:** Each enrollment can have at most one certificate. A second certificate cannot be issued for an enrollment that already has a certificate.

**Trigger:** Certificate issuance request.

**Condition:** At the time of issuance, the system must check whether a certificate record with the target `enrollment_id` already exists in the `certificates` table.

**Enforcement mechanism:**
- Database: UNIQUE constraint on `enrollment_id` in the `certificates` table.
- Application: Pre-insert existence check.

**Violation consequence:** The issuance is rejected with the message: "A certificate has already been issued for this enrollment."

**Source:** Mandatory university requirement.

---

## 2. Certificate Issuance Rules

### BR-04 — Unique Certificate Number

**Rule:** Every issued certificate must have a certificate number that is unique across the entire system for all time.

**Trigger:** Certificate number generation during issuance.

**Format:** `CERT-{YYYY}-{XXXXXXXX}` where:
- `YYYY` = four-digit year of issuance.
- `XXXXXXXX` = an 8-character uppercase alphanumeric string derived from PHP's `random_bytes()` function (a cryptographically secure pseudorandom number generator).

**Important:** Cryptographic randomness alone does not mathematically guarantee uniqueness. The database UNIQUE constraint on the `certificate_number` column is the definitive uniqueness guarantee. Random generation makes collisions statistically negligible, but the application must still handle them.

**Enforcement mechanism:**
- Database: UNIQUE constraint on the `certificate_number` column in the `certificates` table.
- Application: After generating a candidate number, attempt the database insert. If the INSERT fails with a unique constraint violation, roll back the transaction, delete the temporary file, generate a new number, render a new PDF containing the new number, and retry. The system retries up to 5 times before failing with an error message.

**Source:** Mandatory university requirement.

---

### BR-05 — Valid Completion Date

**Rule:** The completion date recorded for an enrollment must satisfy all of the following conditions:

1. It must be a valid calendar date.
2. It must not be a future date (must be ≤ today's date at the time of submission).
3. It must not be before the associated course's start date.
4. It must not be after the associated course's end date.

**Trigger:** Enrollment status update to "Completed".

**Enforcement mechanism:**
- Application: Server-side validation checks all four conditions before the enrollment update is committed. Each failing condition produces a distinct, field-adjacent error message.

**Violation consequence:** The status update is rejected with a specific error message identifying which condition was violated.

**Source:** Core business logic to prevent backdating or future-dating completions.

---

### BR-06 — Certificate Issuance Atomicity

**Rule:** Certificate issuance must follow a defined atomic sequence. Either all steps succeed completely, or all database changes are rolled back and all generated files are cleaned up.

**Trigger:** Certificate issuance action.

**Required sequence:**

1. **Eligibility verification:** Confirm enrollment status is "Completed" and no certificate exists for this enrollment. If either check fails, abort immediately (no database transaction is started).
2. **Generate candidate certificate number:** Produce the candidate `CERT-YYYY-XXXXXXXX` number using PHP's `random_bytes()`.
3. **Begin database transaction:** Open a PDO transaction with `$pdo->beginTransaction()`.
4. **Render PDF to temporary file:** Dompdf renders the certificate HTML template (containing the candidate certificate number, participant name, course title, and completion date) to a temporary file path (e.g. in `sys_get_temp_dir()`). If Dompdf fails, roll back the transaction, delete any partial temp file, and abort.
5. **Insert certificate record:** Insert the certificate row (certificate number, enrollment ID, issuance date, final file path, status "Valid") into the database. If the INSERT fails due to a UNIQUE constraint collision on `certificate_number`, roll back the transaction, delete the temporary file, generate a new candidate certificate number, render a new PDF containing that new number, and retry (up to 5 attempts total). If the INSERT fails for any other reason, roll back the transaction, delete the temp file, and abort.
6. **Move temporary file to final location:** Move the temporary PDF file to its final protected storage location (`storage/certificates/{certificate_number}.pdf`). If the move fails, roll back the transaction, delete the temp file, and abort.
7. **Commit transaction:** Call `$pdo->commit()`. If the commit fails after the PDF has been moved to its final location, the application must attempt a transaction rollback and must remove the final file if it exists.

**Process termination risk:** If the PHP process terminates unexpectedly between step 6 (file move) and step 7 (commit), an orphaned PDF file may exist without a corresponding database record, or vice versa. This is a known cross-resource consistency risk inherent in coordinating filesystem and relational database operations. A periodic recovery check procedure (comparing database records against files in `storage/certificates/`) can identify and resolve such discrepancies. This maintenance procedure is documented for operational support.

**Enforcement mechanism:**
- Application: The issuance controller implements the above sequence using structured try-catch blocks. Any caught exception triggers rollback and file cleanup.

**Source:** NFR-22 reliability requirement.

---

## 3. Certificate Archive and Revocation Rules

### BR-07 — Revoked Certificates Retained in Archive

**Rule:** A revoked certificate must be retained permanently in the system archive. Revoked certificates must not be deleted.

**Trigger:** Certificate revocation.

**Condition:** Revocation changes the certificate's `status` from `Valid` to `Revoked` and records `revoked_at` (current timestamp) and `revocation_reason` (required text, not empty). The certificate record and PDF file remain in the system unchanged.

**Enforcement mechanism:**
- Application: The revoke action only issues an UPDATE statement. No DELETE is performed. No delete option is presented in the UI for any certificate.
- Database: No `ON DELETE CASCADE` behaviour removes certificate records.

**Source:** Mandatory university requirement / data integrity.

---

### BR-08 — Revoked Certificate Visible on Public Verification

**Rule:** When a revoked certificate is queried on the public verification page, the system must clearly communicate that the certificate is no longer valid. It must not present a revoked certificate as "Valid".

**Trigger:** Public certificate search result display.

**Condition:** If `certificates.status = Revoked`, the display must show a prominent "REVOKED — This certificate is no longer valid" indicator above the certificate details.

**Enforcement mechanism:**
- Application: The verification result display logic checks the status field and conditionally renders the revoked banner.

**Source:** Mandatory university requirement.

---

## 4. Data Integrity Rules

### BR-09 — No Deletion of Certificates by Any User

**Rule:** Certificate records must never be permanently deleted from the database by any system actor, including administrators.

**Rationale:** Certificate records serve as a permanent, auditable archive. Even erroneous issuances are corrected by revocation, not deletion.

**Enforcement mechanism:**
- Application: No delete action exists for certificates in any administrative UI.
- Database: Consider restricting DELETE permission on the `certificates` table from the application database user.

---

### BR-10 — Soft Delete for Participants with Certificates

**Rule:** Participant deletion follows a three-tier enforcement policy:

1. **Issued certificates exist:** If the participant has one or more issued certificates (regardless of certificate status), the account cannot be permanently deleted from the database. The participant account must be deactivated by setting the account status to "Inactive".
2. **Enrollments exist without certificates:** If the participant has no issued certificates but has one or more enrollment records, permanent deletion must be blocked to preserve historical enrollment integrity. The administrator is informed that deletion is not permitted while enrollment records exist.
3. **No certificates and no enrollments:** If the participant has neither issued certificates nor enrollment records, permanent deletion is permitted after administrator confirmation.

**Trigger:** Administrator attempts to delete a participant.

**Condition & Enforcement:**
- Before deletion, the system evaluates:
  - If `COUNT(certificates.id) > 0` for the participant (via enrollment join), deactivation ("Inactive") is applied: the system sets account status to "Inactive" and informs the administrator: "This participant has issued certificates and cannot be permanently deleted. Their account has been deactivated instead."
  - Else if `COUNT(enrollments.id) > 0` for the participant, deletion is blocked: the system rejects deletion and informs the administrator: "This participant has enrollment history that must be preserved. Their account cannot be deleted while enrollment records exist. You may deactivate the account instead."
  - Else (0 certificates and 0 enrollments), the system prompts for confirmation and permanently deletes the record upon confirmation.

**Source:** Data integrity — certificate records and enrollment history reference participant data.

---

### BR-11 — No Course Deletion with Enrollments

**Rule:** A training course that has one or more enrollment records cannot be deleted.

**Trigger:** Administrator attempts to delete a course.

**Condition:** If `COUNT(enrollments.id) > 0` for the course, deletion is blocked.

**Enforcement mechanism:**
- Application: Before deletion, the system counts the course's enrollments. If > 0, deletion is refused with the message: "This course cannot be deleted because it has existing enrollment records."
- Database: `ON DELETE RESTRICT` on the `enrollments.course_id` foreign key enforces this at the database level.

**Source:** FR-14 / data integrity.

---

### BR-12 — Email Address Uniqueness

**Rule:** No two user accounts (administrators or participants) may share the same email address.

**Trigger:** Account creation or email update.

**Enforcement mechanism:**
- Database: UNIQUE constraint on the `email` column in the `users` table.
- Application: Duplicate check query before insert or update with the message: "This email address is already registered."

---

## 5. Access Control Rules

### BR-13 — Participants Access Only Their Own Records

**Rule:** An authenticated participant must only be able to access their own profile, enrollments, and certificates. Accessing another participant's records via URL manipulation (Insecure Direct Object Reference / IDOR) must be prevented.

**Trigger:** Any request by a participant to view data.

**Enforcement mechanism:**
- Application: All participant data queries use `WHERE participant_id = session.user_id` (or equivalent join). URL-supplied ID parameters are ignored or validated against the session user ID before any query is executed.

**Source:** FR-60 / NFR-07.

---

### BR-14 — Role Separation Strictly Enforced

**Rule:** An authenticated participant must not access any administrator-only page, and an authenticated administrator must not access participant-only pages by URL.

**Trigger:** Every page load.

**Enforcement mechanism:**
- Application: Every protected page begins with a session check that validates both authentication and the required role. If the role does not match, the user is redirected to their own dashboard with an "Access Denied" message.

**Source:** FR-08.

---

### BR-15 — Administrators Cannot Manage Other Administrators

**Rule:** An administrator account cannot create, edit, or delete another administrator account through the standard application UI. Administrator accounts are provisioned exclusively through the initial seed SQL script or by direct authorised database access.

**Rationale:** Prevents unintended privilege escalation within the application.

**Enforcement mechanism:**
- Application: The participant management UI only creates and manages users with `role = participant`. No administrator creation form exists in the application.

---

## 6. Public Verification Rules

### BR-16 — Private Data Excluded from Public Verification

**Rule:** The public certificate verification page must never expose: participant email address, phone number, date of birth, internal user ID, or any other personally identifiable information beyond participant full name, course title, completion date, issuance date, certificate number, and certificate status.

**Trigger:** Public verification query result display.

**Enforcement mechanism:**
- Application: The SQL query for the verification page explicitly selects only the permitted columns. No `SELECT *` is used for verification queries.
- Code review: The verification controller must be reviewed to confirm no additional fields are added inadvertently.

**Source:** Mandatory university requirement / privacy.

---

### BR-17 — No Account Required for Certificate Verification

**Rule:** The certificate verification page must be accessible to any visitor without requiring login, registration, or any form of authentication.

**Trigger:** Navigation to `verify.php`.

**Enforcement mechanism:**
- Application: `verify.php` does not include the authentication middleware. No session validation is required for this page.

**Source:** Mandatory university requirement.

---

### BR-18-PV — Public Search Result Limit

**Rule:** A name-based public certificate search that matches more than 20 records must return only the first 20 results (ordered by issuance date descending) and must display a note informing the visitor that the result set has been truncated.

**Trigger:** Public name-search query returning > 20 results.

**Enforcement mechanism:**
- Application: The verification query uses `LIMIT 20`. A count query determines if the total exceeds 20 and triggers the truncation notice.

**Source:** FR-51-PV.

---

### BR-19-PV — No PDF Download on Public Verification

**Rule:** The public certificate verification page must not provide any mechanism for downloading the PDF certificate file. PDF access is restricted to authenticated users (administrators and the owning participant) only.

**Trigger:** Public verification result display.

**Enforcement mechanism:**
- Application: No download button, link, or URL to the PDF is included in the public verification response.
- Server: The `storage/certificates/` directory is stored outside the publicly accessible document root where supported by the hosting environment; `.htaccess` access denial rules serve as a fallback when storage above document root is unavailable.

**Source:** FR-52-PV.

---

## 7. Course Management Rules

### BR-18 — Course End Date Must Be On or After Start Date

**Rule:** A training course's end date must be on or after its start date. A course cannot end before it begins.

**Trigger:** Course creation or edit.

**Enforcement mechanism:**
- Application: Server-side validation before INSERT or UPDATE.

---

### BR-19 — Course Title Required and Limited in Length

**Rule:** A course title is mandatory and must be between 3 and 255 characters in length.

**Trigger:** Course creation or edit.

**Enforcement mechanism:**
- Application: Server-side validation.
- Database: `NOT NULL` and `VARCHAR(255)` constraints on the `title` column.

---

## 8. Participant Management Rules

### BR-20 — All Passwords Must Be Hashed

**Rule:** Passwords for all user accounts (both administrators and participants) must never be stored in plain text. All passwords must be stored as bcrypt hashes generated by PHP's `password_hash()` function and verified with `password_verify()`.

**Trigger:** Account creation; password change (if implemented in a later version).

**Enforcement mechanism:**
- Application: Hashing is applied before any INSERT or UPDATE to the `password` column.
- Code review: No plain text value may appear in a SQL statement touching the `password` column.

**Source:** FR-18 / NFR-04.

---

### BR-21 — Participant Full Name Required

**Rule:** A participant's full name is mandatory and must not be empty. Maximum length is 150 characters.

**Trigger:** Account creation or edit.

**Enforcement mechanism:**
- Application: Server-side validation.
- Database: `NOT NULL` on `full_name` column.

---

### BR-25 — Account Creation Restricted to Administrators

**Rule:** Participant accounts may only be created by an authenticated administrator through the participant management module. No public registration form exists. The initial administrator account is created by the database seed SQL script.

**Trigger:** Any attempt to access or submit a registration form.

**Enforcement mechanism:**
- Application: No public registration route or page exists. The participant creation form is located within the administrator-authenticated section of the application.
- Application: Every page in the `admin/` path requires an active administrator session (see BR-14).

**Source:** FR-10-AUTH / ASM-01.

---

## 9. System Operational Rules

### BR-22 — CSRF Token Required on All POST Forms

**Rule:** Every HTML form that performs a state-changing POST request must include a hidden CSRF synchroniser token field. The server must validate the token before processing the form. Requests with missing or invalid tokens must be rejected with HTTP 403.

**Trigger:** Any POST form submission.

**Enforcement mechanism:**
- Application: A CSRF token is generated per session and stored in `$_SESSION['csrf_token']`. Each form renders a hidden `<input type="hidden" name="csrf_token" value="...">`. Every action handler validates the token before processing.

**Source:** NFR-03.

---

### BR-23 — All User Inputs Used via Prepared Statements

**Rule:** All user-supplied input that is used in database queries must be passed as a bound parameter in a PDO prepared statement. No string concatenation of user input into SQL is permitted.

**Trigger:** Any database query that uses user input.

**Enforcement mechanism:**
- Application: Use `$pdo->prepare()` and `$stmt->execute([...])` or `$stmt->bindParam()` for every query that incorporates external data.

**Source:** NFR-01.

---

### BR-24 — All Output HTML-Escaped

**Rule:** All dynamic data rendered into HTML templates must be escaped with `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` or an equivalent wrapper function before output.

**Trigger:** Any PHP variable rendered into HTML.

**Enforcement mechanism:**
- Application: A global `h()` helper function wraps `htmlspecialchars()`. No `echo $variable` without passing through `h()` or equivalent is permitted in view files.

**Source:** NFR-02.

---

*End of Business Rules Specification — Version 1.3*
