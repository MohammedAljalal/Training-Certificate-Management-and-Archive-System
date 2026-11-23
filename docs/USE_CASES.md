# Use Case Specification
## Training Certificate Management and Archive System

| Field | Value |
|---|---|
| Document ID | UC-001 |
| Version | 1.3 |
| Related SRS | SRS-001 v1.3 |
| Date | 2026-11-21 |
| Revised | 2026-11-22 |

---

## Revision History

| Version | Date | Changes |
|---|---|---|
| 1.0 | 2026-11-21 | Initial version. |
| 1.1 | 2026-11-21 | UC-01/02: Inactive account check added; session ID regeneration on login only clarified. UC-03: Logout sequence updated — session_unset + session_destroy + cookie expiry (no post-logout regeneration). UC-07: Self-registration precondition removed; admin-only account creation made explicit; account status "Active" used. UC-09: "is_active = 0" replaced with account status "Inactive". UC-11: Completion date upper bound (≤ course end date) added to validation step and AF-11A. UC-12: Issuance sequence updated to 7-step atomic flow with temp file, BR-06 reference, random_bytes(), and process-termination risk note; Dompdf referenced; storage path updated to storage/certificates/. UC-16 BR reference corrected. UC-17: Result cap (20), no PDF download, issuance date in result fields; AF-17C updated. All UC-06 BR reference corrected from BR-07 to BR-11. Status values standardised throughout. |
| 1.2 | 2026-11-21 | UC-09 updated: three-tier participant deletion logic formally implemented with new alternative flow AF-09C (blocking permanent deletion when enrollments exist without certificates to preserve history). UC-12 updated: canonical 7-step atomic issuance sequence aligned (number generation before transaction begin, Dompdf re-rendering new PDF with new number on collision retry up to 5 attempts, commit-failure cleanup of final PDF file in Step 8 and new AF-12F, cross-resource risk recovery check procedure referenced). Identifier Convention Note referenced. |
| 1.3 | 2026-11-22 | Final alignment: Verified full alignment with SRS v1.3 (FR-23 three-tier deletion policy) and cross-document consistency across all use cases. |

---

## Table of Contents

1. [Use Case Diagram Overview](#1-use-case-diagram-overview)
2. [UC-01: Administrator Login](#uc-01-administrator-login)
3. [UC-02: Participant Login](#uc-02-participant-login)
4. [UC-03: Logout](#uc-03-logout)
5. [UC-04: Create Training Course](#uc-04-create-training-course)
6. [UC-05: Edit Training Course](#uc-05-edit-training-course)
7. [UC-06: Delete Training Course](#uc-06-delete-training-course)
8. [UC-07: Create Participant Account](#uc-07-create-participant-account)
9. [UC-08: Edit Participant Record](#uc-08-edit-participant-record)
10. [UC-09: Deactivate Participant](#uc-09-deactivate-participant)
11. [UC-10: Enroll Participant in Course](#uc-10-enroll-participant-in-course)
12. [UC-11: Update Enrollment Status](#uc-11-update-enrollment-status)
13. [UC-12: Issue Certificate](#uc-12-issue-certificate)
14. [UC-13: Revoke Certificate](#uc-13-revoke-certificate)
15. [UC-14: Download Certificate PDF (Administrator)](#uc-14-download-certificate-pdf-administrator)
16. [UC-15: View Participant Dashboard](#uc-15-view-participant-dashboard)
17. [UC-16: Download Certificate PDF (Participant)](#uc-16-download-certificate-pdf-participant)
18. [UC-17: Public Certificate Verification](#uc-17-public-certificate-verification)
19. [UC-18: View Administrator Dashboard](#uc-18-view-administrator-dashboard)
20. [UC-19: Search Certificate Archive](#uc-19-search-certificate-archive)

---

## 1. Use Case Diagram Overview

```
Actors:
  [Administrator]  → Manages the entire system
  [Participant]    → Self-service access to own records (account created by Administrator)
  [Public Visitor] → Unauthenticated certificate verification (read-only, no PDF download)

Use Case Groups:
  Authentication:         UC-01, UC-02, UC-03
  Course Management:      UC-04, UC-05, UC-06
  Participant Mgmt:       UC-07, UC-08, UC-09
  Enrollment Management:  UC-10, UC-11
  Certificate Mgmt:       UC-12, UC-13, UC-14
  Participant Self-Svc:   UC-15, UC-16
  Public Access:          UC-17
  Dashboards:             UC-18
  Archive:                UC-19

Account Creation Policy:
  - Administrator accounts: created by database seed script only.
  - Participant accounts: created by an Administrator through UC-07 only.
  - No public self-registration exists.

Identifier Convention Note:
  - Requirements referenced across use cases follow the convention documented in SRS §4.0. Compound identifiers (e.g. FR-10-AUTH, FR-51-PV, FR-52-PV, NFR-09-SEC) are used where a requirement shares a numerical base with an existing requirement in another section, preserving sequential numbering.
```

---

## UC-01: Administrator Login

| Field | Detail |
|---|---|
| **Use Case ID** | UC-01 |
| **Name** | Administrator Login |
| **Primary Actor** | Administrator |
| **Secondary Actors** | None |
| **Related FRs** | FR-01, FR-02, FR-03, FR-05, FR-07, FR-09, FR-10-AUTH |
| **Priority** | Must Have |

**Preconditions:**
- The administrator account exists in the database with a hashed password.
- The user is not currently logged in.

**Main Flow:**
1. The Administrator navigates to `login.php`.
2. The system displays the login form with email and password fields.
3. The Administrator enters a valid email address and password and submits the form.
4. The system retrieves the user record matching the email from the `users` table.
5. The system verifies the submitted password against the stored bcrypt hash using `password_verify()`.
6. The system regenerates the PHP session ID using `session_regenerate_id(true)`.
7. The system stores the user ID, role, and full name in the session.
8. The system redirects the Administrator to `admin/dashboard.php`.

**Alternative Flows:**

*AF-01A — Invalid Credentials:*
- At step 5, if the password does not match (or no user record exists for the email), the system displays: "Invalid email or password. Please try again." without indicating which field is wrong.
- The system does not create a session.
- The form is redisplayed with the email field pre-filled (password cleared).

*AF-01B — Already Logged In:*
- At step 1, if a valid administrator session already exists, the system redirects directly to `admin/dashboard.php` without redisplaying the login form.

**Postconditions (Success):**
- A valid PHP session exists with `user_id`, `role = admin`, and `full_name`.
- The session ID was regenerated at login.
- The Administrator is on the administrator dashboard.

**Postconditions (Failure):**
- No session is created.
- The user remains on the login page.

---

## UC-02: Participant Login

| Field | Detail |
|---|---|
| **Use Case ID** | UC-02 |
| **Name** | Participant Login |
| **Primary Actor** | Participant |
| **Secondary Actors** | None |
| **Related FRs** | FR-01, FR-02, FR-04, FR-05, FR-07, FR-09 |
| **Priority** | Must Have |

**Preconditions:**
- The participant account exists and has account status "Active".
- The participant account was created by an administrator (no public registration).
- The user is not currently logged in.

**Main Flow:**
1. The Participant navigates to `login.php`.
2. The system displays the login form.
3. The Participant enters a valid email and password and submits.
4. The system retrieves the user record and checks account status is "Active".
5. The system verifies the password against the bcrypt hash using `password_verify()`.
6. The system regenerates the PHP session ID using `session_regenerate_id(true)`.
7. The system stores the user ID, role, and full name in the session.
8. The system redirects the Participant to `participant/dashboard.php`.

**Alternative Flows:**

*AF-02A — Inactive Account:*
- At step 4, if the account has status "Inactive", the system displays: "Your account has been deactivated. Please contact the administrator." No session is created.

*AF-02B — Invalid Credentials:*
- Same as AF-01A.

*AF-02C — Already Logged In:*
- If a valid participant session already exists, the system redirects directly to `participant/dashboard.php`.

**Postconditions (Success):**
- A valid session exists with `user_id`, `role = participant`, and `full_name`.
- The session ID was regenerated at login.
- The Participant is on the participant dashboard.

---

## UC-03: Logout

| Field | Detail |
|---|---|
| **Use Case ID** | UC-03 |
| **Name** | Logout |
| **Primary Actor** | Administrator or Participant |
| **Secondary Actors** | None |
| **Related FRs** | FR-06 |
| **Priority** | Must Have |

**Preconditions:**
- The user has an active session.

**Main Flow:**
1. The user clicks the "Logout" link in the navigation bar.
2. The system calls `session_unset()` to clear all session variables.
3. The system calls `session_destroy()` to destroy the server-side session.
4. The system expires the session cookie by calling `setcookie()` with the same name and path parameters but with an expiry time in the past, so the browser removes the cookie.
5. The system redirects to `login.php` with a flash message: "You have been logged out successfully."

**Note:** The session ID is NOT regenerated after logout. Session destruction and cookie expiry are the correct logout mechanism. Session ID regeneration is performed only at login (see UC-01, UC-02).

**Alternative Flows:**

*AF-03A — Session Already Expired:*
- If the session has already expired before the user clicks logout, the system redirects to `login.php` without an error message.

**Postconditions (Success):**
- No server-side session exists.
- The session cookie is expired on the client.
- The user is on the login page.
- A Back button press does not restore access to any protected page (session is fully destroyed).

---

## UC-04: Create Training Course

| Field | Detail |
|---|---|
| **Use Case ID** | UC-04 |
| **Name** | Create Training Course |
| **Primary Actor** | Administrator |
| **Secondary Actors** | None |
| **Related FRs** | FR-10, FR-15 |
| **Related BRs** | BR-18, BR-19 |
| **Priority** | Must Have |

**Preconditions:**
- The Administrator is logged in with an active session.
- The Administrator is on the course management page.

**Main Flow:**
1. The Administrator clicks "Add New Course".
2. The system displays the course creation form.
3. The Administrator enters: course title, description (optional), start date, end date, and location (optional).
4. The Administrator submits the form.
5. The system performs server-side validation (see FR-15, BR-18, BR-19).
6. The system inserts the course record into the `courses` table with status "Active".
7. The system redirects to the course list with a success message: "Course created successfully."

**Alternative Flows:**

*AF-04A — Validation Failure:*
- At step 5, if any validation rule is violated (e.g. end date before start date, or title < 3 characters), the system redisplays the form with specific error messages adjacent to the offending fields. No record is inserted.

**Postconditions (Success):**
- A new course record exists in the database with status "Active".
- The Administrator sees the updated course list.

---

## UC-05: Edit Training Course

| Field | Detail |
|---|---|
| **Use Case ID** | UC-05 |
| **Name** | Edit Training Course |
| **Primary Actor** | Administrator |
| **Related FRs** | FR-13, FR-15 |
| **Related BRs** | BR-18, BR-19 |
| **Priority** | Must Have |

**Preconditions:**
- The Administrator is logged in.
- At least one course exists.

**Main Flow:**
1. The Administrator selects a course from the list and clicks "Edit".
2. The system displays the course edit form pre-populated with current values.
3. The Administrator modifies one or more fields and submits.
4. The system validates the input (same rules as FR-15, BR-18, BR-19).
5. The system updates the course record.
6. The system redirects to the course list with a success message.

**Alternative Flows:**

*AF-05A — Validation Failure:*
- Same as AF-04A.

*AF-05B — Course Not Found:*
- If the course ID does not exist in the database, the system displays: "Course not found." and redirects to the course list.

**Postconditions (Success):**
- The course record is updated in the database.

---

## UC-06: Delete Training Course

| Field | Detail |
|---|---|
| **Use Case ID** | UC-06 |
| **Name** | Delete Training Course |
| **Primary Actor** | Administrator |
| **Related FRs** | FR-14 |
| **Related BRs** | BR-11 |
| **Priority** | Must Have |

**Preconditions:**
- The Administrator is logged in.
- The course has zero enrollments.

**Main Flow:**
1. The Administrator selects a course from the list and clicks "Delete".
2. The system displays a confirmation prompt: "Are you sure you want to delete this course? This action cannot be undone."
3. The Administrator confirms.
4. The system checks that the course has no enrollment records.
5. The system deletes the course record from the database.
6. The system redirects to the course list with a success message.

**Alternative Flows:**

*AF-06A — Course Has Enrollments:*
- At step 4, if the course has one or more enrollments, the system displays: "This course cannot be deleted because it has existing enrollment records." No deletion occurs.

*AF-06B — Administrator Cancels:*
- At step 3, if the Administrator cancels, no action is taken and the user returns to the course list.

**Postconditions (Success):**
- The course record is removed from the database.

---

## UC-07: Create Participant Account

| Field | Detail |
|---|---|
| **Use Case ID** | UC-07 |
| **Name** | Create Participant Account |
| **Primary Actor** | Administrator |
| **Related FRs** | FR-17, FR-18, FR-19, FR-24, FR-10-AUTH |
| **Related BRs** | BR-20, BR-21, BR-25 |
| **Priority** | Must Have |

**Preconditions:**
- The Administrator is logged in.
- **No public registration form exists.** This use case is the only way a participant account is created.

**Main Flow:**
1. The Administrator navigates to Participant Management and clicks "Add Participant".
2. The system displays the participant creation form (accessible to administrators only).
3. The Administrator enters: full name, email address, phone number (optional), date of birth (optional), and an initial password.
4. The Administrator submits the form.
5. The system validates all inputs (see FR-24, BR-21). It checks that the email is not already registered (BR-12).
6. The system hashes the password using `password_hash()` with `PASSWORD_BCRYPT`.
7. The system inserts the participant record with `role = participant` and account status "Active".
8. The system redirects to the participant list with a success message: "Participant account created successfully."

**Alternative Flows:**

*AF-07A — Duplicate Email:*
- At step 5, if the email already exists in the `users` table, the system displays: "This email address is already registered." No record is created.

*AF-07B — Validation Failure:*
- If any validation rule fails, the form is redisplayed with specific field-adjacent error messages.

**Postconditions (Success):**
- A participant record exists in the database with account status "Active" and a hashed password.
- The participant can now log in using the assigned email and initial password.

---

## UC-08: Edit Participant Record

| Field | Detail |
|---|---|
| **Use Case ID** | UC-08 |
| **Name** | Edit Participant Record |
| **Primary Actor** | Administrator |
| **Related FRs** | FR-22, FR-24 |
| **Related BRs** | BR-12, BR-21 |
| **Priority** | Must Have |

**Preconditions:**
- The Administrator is logged in.
- The participant record exists.

**Main Flow:**
1. The Administrator opens the participant list and clicks "Edit" for a participant.
2. The system displays the edit form pre-populated with the participant's current data.
3. The Administrator edits the name, email, and/or phone number and submits.
4. The system validates the input. If the email was changed, it checks for uniqueness against all other users.
5. The system updates the participant record.
6. The system redirects with a success message: "Participant record updated successfully."

**Alternative Flows:**

*AF-08A — Duplicate Email:*
- If the new email is already in use by another user, the system displays: "This email address is already registered to another account."

**Postconditions (Success):**
- The participant record is updated in the database.

---

## UC-09: Deactivate Participant

| Field | Detail |
|---|---|
| **Use Case ID** | UC-09 |
| **Name** | Deactivate or Delete Participant |
| **Primary Actor** | Administrator |
| **Related FRs** | FR-23 |
| **Related BRs** | BR-10 |
| **Priority** | Must Have |

**Preconditions:**
- The Administrator is logged in.
- The participant record exists.

**Main Flow (Participant has issued certificates — Tier 1):**
1. The Administrator selects a participant and clicks "Delete/Deactivate".
2. The system checks whether the participant has at least one issued certificate (across any enrollment) and whether enrollment records exist.
3. The participant has certificates. The system displays: "This participant has issued certificates and cannot be permanently deleted. Their account will be deactivated instead."
4. The Administrator confirms.
5. The system sets the participant account status to "Inactive".
6. The system confirms: "Participant account has been deactivated."

**Alternative Flows:**

*AF-09A — Participant Has No Certificates and No Enrollments (Tier 3):*
- At step 2, if the participant has no issued certificates and no enrollment records, the system displays a hard-delete confirmation: "Are you sure you want to permanently delete this participant? This action cannot be undone."
- Upon confirmation, the participant record is permanently deleted from the database.

*AF-09B — Administrator Cancels:*
- At any confirmation step, if the Administrator cancels, no action is taken.

*AF-09C — Participant Has Enrollments But No Certificates (Tier 2):*
- At step 2, if the participant has no issued certificates but has at least one enrollment record, the system blocks deletion and displays: "This participant has enrollment history that must be preserved. Their account cannot be deleted while enrollment records exist. You may deactivate the account instead."
- The administrator is offered the option to deactivate the account (set status to "Inactive") or cancel.

**Postconditions (Success — Deactivation):**
- The participant account has status "Inactive". The participant cannot log in.
- All enrollment and certificate records remain intact in the database.

**Postconditions (Success — Hard Delete):**
- The participant record is permanently removed from the database.

---

## UC-10: Enroll Participant in Course

| Field | Detail |
|---|---|
| **Use Case ID** | UC-10 |
| **Name** | Enroll Participant in Course |
| **Primary Actor** | Administrator |
| **Related FRs** | FR-25, FR-26, FR-27 |
| **Related BRs** | BR-01 |
| **Priority** | Must Have |

**Preconditions:**
- The Administrator is logged in.
- At least one participant with account status "Active" and one course with status "Active" exist.

**Main Flow:**
1. The Administrator navigates to the course detail page or the enrollment section.
2. The Administrator selects a participant from the participant dropdown.
3. The Administrator clicks "Enroll".
4. The system checks the `enrollments` table for an existing record with the same `(participant_id, course_id)` pair.
5. No duplicate exists. The system inserts an enrollment record with status "Enrolled" and `enrolled_at = current timestamp`.
6. The system redirects with a success message: "Participant enrolled successfully."

**Alternative Flows:**

*AF-10A — Duplicate Enrollment:*
- At step 4, a matching record is found. The system displays: "This participant is already enrolled in this course." No record is created.

**Postconditions (Success):**
- An enrollment record exists for the participant-course pair with status "Enrolled".

---

## UC-11: Update Enrollment Status

| Field | Detail |
|---|---|
| **Use Case ID** | UC-11 |
| **Name** | Update Enrollment Status |
| **Primary Actor** | Administrator |
| **Related FRs** | FR-28, FR-29, FR-30 |
| **Related BRs** | BR-05 |
| **Priority** | Must Have |

**Preconditions:**
- The Administrator is logged in.
- An enrollment record exists with status "Enrolled" or "In Progress".

**Main Flow:**
1. The Administrator opens the enrollment detail page.
2. The Administrator selects the new status from the status dropdown (Enrolled → In Progress → Completed; forward only).
3. If the selected status is "Completed", the system requires a completion date input.
4. The Administrator enters the completion date and submits.
5. The system validates the completion date:
   - Must be a valid calendar date.
   - Must not be in the future (≤ today).
   - Must not be before the course start date.
   - Must not be after the course end date.
6. The system updates the enrollment record with the new status and, if applicable, the completion date.
7. The system redirects with a success message.

**Alternative Flows:**

*AF-11A — Completion Date Invalid:*
- At step 5, if any validation condition fails, the system displays a specific error message:
  - Future date: "Completion date cannot be in the future."
  - Before course start: "Completion date cannot be before the course start date."
  - After course end: "Completion date cannot be after the course end date."
  - Invalid format: "Please enter a valid date."
- The form is redisplayed. No update is made.

*AF-11B — Certificate Already Issued (Status Downgrade Attempt):*
- At step 2, if a certificate has already been issued for this enrollment, any attempt to change the status is blocked. The system displays: "This enrollment already has an issued certificate. The status cannot be changed."

**Postconditions (Success):**
- The enrollment record has the updated status and, if Completed, a recorded completion date.

---

## UC-12: Issue Certificate

| Field | Detail |
|---|---|
| **Use Case ID** | UC-12 |
| **Name** | Issue Certificate |
| **Primary Actor** | Administrator |
| **Related FRs** | FR-32, FR-33, FR-34, FR-35, FR-36, FR-37, FR-38 |
| **Related BRs** | BR-02, BR-03, BR-04, BR-06 |
| **Priority** | Must Have |

**Preconditions:**
- The Administrator is logged in.
- The enrollment status is "Completed" with a recorded completion date.
- No certificate has been previously issued for this enrollment.

**Main Flow:**
1. The Administrator opens the enrollment detail page and clicks "Issue Certificate".
2. The system verifies eligibility: enrollment status is "Completed" and no certificate record exists for this enrollment.
3. The system generates a candidate certificate number: `CERT-{YEAR}-{XXXXXXXX}` using PHP's `random_bytes()` encoded to uppercase alphanumeric.
4. The system begins a PDO database transaction with `$pdo->beginTransaction()`.
5. Dompdf renders the certificate HTML template (containing participant full name, course title, completion date, certificate number, and organisation name) to a temporary PDF file in a system temp directory. Unicode font support is enabled.
6. The system inserts the certificate record into the `certificates` table (certificate number, enrollment ID, issuance date, final file path, status "Valid"). If a UNIQUE constraint violation occurs on `certificate_number`, the transaction is rolled back, the temp file is deleted, a new candidate certificate number is generated, a new PDF containing that new number is rendered, and the process retries (up to 5 attempts total).
7. The system moves the temporary PDF file to its final protected location: `storage/certificates/{certificate_number}.pdf`. If the move fails, the transaction is rolled back and the temp file is deleted.
8. The system commits the database transaction (`$pdo->commit()`). If the commit fails after the PDF has been moved to final storage, the application attempts rollback and deletes the final file from disk if it exists.
9. The system displays a success message: "Certificate issued successfully. Certificate Number: CERT-YYYY-XXXXXXXX."

**Process Termination Risk:** If the PHP process terminates unexpectedly between step 7 (file move) and step 8 (commit), an orphaned PDF file or incomplete database record may result. This is a known cross-resource consistency risk. A documented recovery check procedure (comparing database records against files on disk in `storage/certificates/`) allows administrators to identify and resolve any orphaned files.

**Alternative Flows:**

*AF-12A — Enrollment Not Completed:*
- At step 2, if the enrollment status is not "Completed", the system displays: "Certificate can only be issued for completed enrollments." No action is taken.

*AF-12B — Certificate Already Exists:*
- At step 2, if a certificate record already exists for this enrollment, the system displays: "A certificate has already been issued for this enrollment."

*AF-12C — PDF Generation Failure (Dompdf Error):*
- At step 5, if Dompdf fails to render the PDF, the transaction is rolled back (no INSERT was attempted). The system displays: "Certificate PDF generation failed. Please check server configuration and try again." No database record is created.

*AF-12D — File Move Failure:*
- At step 7, if moving the temp file to final storage fails (e.g. permission error), the transaction is rolled back, the INSERT is undone, and the temp file is deleted. The system displays: "Certificate file could not be saved. Please check storage directory permissions."

*AF-12E — Maximum Retry Exhausted:*
- If 5 consecutive certificate number generation attempts all result in UNIQUE constraint violations, the system rolls back, cleans up temporary files, and displays: "Certificate issuance failed due to a number generation conflict. Please try again."

*AF-12F — Commit Failure Cleanup:*
- At step 8, if `$pdo->commit()` fails after the file has been moved to its final storage location, the system attempts a database rollback and removes the final PDF file from `storage/certificates/` if present. The system displays: "Certificate record could not be finalized. Changes rolled back."

**Postconditions (Success):**
- A certificate record exists in the `certificates` table with a unique number and status "Valid".
- A PDF file exists at `storage/certificates/{certificate_number}.pdf`, not publicly accessible via URL.

---

## UC-13: Revoke Certificate

| Field | Detail |
|---|---|
| **Use Case ID** | UC-13 |
| **Name** | Revoke Certificate |
| **Primary Actor** | Administrator |
| **Related FRs** | FR-42, FR-43 |
| **Related BRs** | BR-07, BR-08 |
| **Priority** | Must Have |

**Preconditions:**
- The Administrator is logged in.
- The certificate exists and has status "Valid".

**Main Flow:**
1. The Administrator navigates to the Certificate Archive and locates the certificate.
2. The Administrator clicks "Revoke".
3. The system displays a confirmation modal with a required "Reason for Revocation" text field.
4. The Administrator enters a non-empty reason and confirms.
5. The system updates the certificate record: `status = Revoked`, `revoked_at = current timestamp`, `revocation_reason = entered reason`.
6. The system redirects with a message: "Certificate has been revoked."

**Alternative Flows:**

*AF-13A — Administrator Cancels:*
- At step 4, the Administrator dismisses the modal. No changes are made.

*AF-13B — Empty Revocation Reason:*
- At step 4, if the revocation reason field is empty, the system displays: "A reason for revocation is required." The modal remains open.

**Postconditions (Success):**
- The certificate record has `status = Revoked`, `revoked_at`, and `revocation_reason` populated.
- The certificate is still visible in the archive with a "Revoked" status indicator.
- The public verification page shows the certificate as "REVOKED — This certificate is no longer valid".

---

## UC-14: Download Certificate PDF (Administrator)

| Field | Detail |
|---|---|
| **Use Case ID** | UC-14 |
| **Name** | Download Certificate PDF (Administrator) |
| **Primary Actor** | Administrator |
| **Related FRs** | FR-41 |
| **Related BRs** | BR-09, BR-19-PV |
| **Priority** | Must Have |

**Preconditions:**
- The Administrator is logged in.
- The certificate record exists with a valid stored file path.

**Main Flow:**
1. The Administrator views a certificate detail page and clicks "Download PDF".
2. The system validates the administrator session and role.
3. The system looks up the certificate's stored file path from the database.
4. The system validates that the resolved absolute path begins with the expected `storage/certificates/` prefix (path traversal check).
5. The system confirms the file exists on disk.
6. The system serves the PDF file with HTTP headers: `Content-Type: application/pdf` and `Content-Disposition: attachment; filename="{certificate_number}.pdf"`.

**Alternative Flows:**

*AF-14A — File Not Found on Disk:*
- At step 5, if the PDF file does not exist on the server, the system displays: "Certificate file not found. Please contact support."

*AF-14B — Path Traversal Attempt:*
- At step 4, if the resolved path does not begin with the expected prefix, the system returns HTTP 400 Bad Request and logs the attempt.

**Postconditions (Success):**
- The Administrator receives the PDF file as a download.

---

## UC-15: View Participant Dashboard

| Field | Detail |
|---|---|
| **Use Case ID** | UC-15 |
| **Name** | View Participant Dashboard |
| **Primary Actor** | Participant |
| **Related FRs** | FR-55, FR-56, FR-57, FR-58 |
| **Related BRs** | BR-13 |
| **Priority** | Must Have |

**Preconditions:**
- The Participant is logged in with an active session and account status "Active".

**Main Flow:**
1. The system retrieves `user_id` exclusively from the session (never from URL parameters).
2. The system queries `enrollments` for all enrollments where `participant_id = session.user_id`.
3. The system queries `certificates` (via enrollment join) for all certificates issued to this participant.
4. The system renders the participant dashboard: profile summary (name, email), enrolled courses with statuses (Enrolled / In Progress / Completed), and certificate list with certificate number, course title, issuance date, status (Valid / Revoked), and download link.

**Alternative Flows:**

*AF-15A — URL Tampering Attempt:*
- All queries use `session.user_id` as the sole filter. Any `participant_id` or `user_id` parameter supplied in the URL is ignored. No IDOR exposure exists.

**Postconditions (Success):**
- The Participant sees only their own records. No other participant's data is accessible.

---

## UC-16: Download Certificate PDF (Participant)

| Field | Detail |
|---|---|
| **Use Case ID** | UC-16 |
| **Name** | Download Certificate PDF (Participant) |
| **Primary Actor** | Participant |
| **Related FRs** | FR-59, FR-60 |
| **Related BRs** | BR-13, BR-19-PV |
| **Priority** | Must Have |

**Preconditions:**
- The Participant is logged in.
- The requested certificate belongs to the logged-in participant.

**Main Flow:**
1. The Participant clicks "Download" on a certificate listed in their dashboard.
2. The system receives the certificate ID from the request.
3. The system queries the `certificates` table joining through `enrollments` to verify: `enrollments.participant_id = session.user_id`. This is the ownership check.
4. Ownership confirmed. The system validates the file path (path traversal check, same as UC-14 step 4).
5. The system confirms the file exists on disk.
6. The system serves the PDF with appropriate download headers.

**Alternative Flows:**

*AF-16A — Certificate Belongs to Another Participant:*
- At step 3, if the join returns no row, the system returns HTTP 403: "You are not authorised to access this certificate."

*AF-16B — File Not Found:*
- At step 5, if the file does not exist on disk, the system displays: "Certificate file not found. Please contact support."

**Postconditions (Success):**
- The Participant receives their own PDF certificate download.

---

## UC-17: Public Certificate Verification

| Field | Detail |
|---|---|
| **Use Case ID** | UC-17 |
| **Name** | Public Certificate Verification |
| **Primary Actor** | Public Visitor |
| **Related FRs** | FR-45, FR-46, FR-47, FR-48, FR-49, FR-50, FR-51-PV, FR-52-PV |
| **Related BRs** | BR-16, BR-17, BR-18-PV, BR-19-PV, BR-08 |
| **Priority** | Must Have |

**Preconditions:**
- The public verification page is accessible without login.
- No authentication is required.

**Main Flow:**
1. The Public Visitor navigates to `verify.php` (no login required, no account needed).
2. The system displays a search form with a single input field labelled: "Enter certificate number or participant full name".
3. The visitor enters a search term and submits.
4. The system validates the input: not empty; length between 2 and 200 characters.
5. The system searches the `certificates` table joined with `enrollments`, `users`, and `courses` using an exact match (case-insensitive) against either `certificate_number` or `users.full_name`.
6. For each matching certificate, the system returns: certificate number, participant full name, course title, completion date, issuance date, and certificate status (Valid / Revoked).
   - Private fields (email, phone, date of birth, user ID) are never included in the query result or response.
   - No PDF download link or button is included in the response.
7. If the certificate status is "Revoked", a prominent "REVOKED — This certificate is no longer valid" banner is displayed above that certificate's details.
8. For name searches returning more than 20 results, only the first 20 (ordered by issuance date descending) are displayed, with a note: "Showing 20 of {total} results. Please refine your search for more specific results."

**Alternative Flows:**

*AF-17A — No Result Found:*
- At step 5, if no matching certificate is found, the system displays: "No certificate was found matching the provided information." No error is thrown.

*AF-17B — Empty or Too-Short Input:*
- At step 4, if the input is empty or fewer than 2 characters, the system redisplays the form with: "Please enter a certificate number or at least 2 characters of a participant name."

*AF-17C — Multiple Results (name search, ≤ 20):*
- If a name search returns between 2 and 20 certificates, all are listed with their certificate number, course title, completion date, issuance date, and status. No download link is shown for any result.

**Postconditions (Success):**
- The visitor sees the certificate verification result(s).
- No private data is included in the response.
- No PDF download is accessible from this page.

---

## UC-18: View Administrator Dashboard

| Field | Detail |
|---|---|
| **Use Case ID** | UC-18 |
| **Name** | View Administrator Dashboard |
| **Primary Actor** | Administrator |
| **Related FRs** | FR-51, FR-52, FR-53, FR-54 |
| **Priority** | Must Have |

**Preconditions:**
- The Administrator is logged in.

**Main Flow:**
1. The system retrieves summary statistics:
   - Total courses with status "Active"
   - Total participants with account status "Active"
   - Total enrollment records
   - Total certificates with status "Valid"
   - Total certificates with status "Revoked"
2. The system retrieves the 10 most recent enrollments and certificate issuances (ordered by created date descending).
3. The system renders the dashboard with statistics cards and the recent activity table.
4. The navigation bar provides links to: Courses, Participants, Enrollments, Certificates.

**Postconditions (Success):**
- The Administrator has a real-time overview of system activity.

---

## UC-19: Search Certificate Archive

| Field | Detail |
|---|---|
| **Use Case ID** | UC-19 |
| **Name** | Search and Filter Certificate Archive (Administrator) |
| **Primary Actor** | Administrator |
| **Related FRs** | FR-39, FR-40, FR-44 |
| **Priority** | Should Have |

**Preconditions:**
- The Administrator is logged in.

**Main Flow:**
1. The Administrator navigates to the Certificate Archive.
2. The system displays all certificates with pagination (25 per page), ordered by issuance date descending.
3. The Administrator optionally applies a status filter (All / Valid / Revoked) or enters a search term (certificate number or participant name) in the search box.
4. The system re-queries the database with the filter and search term applied using prepared statements.
5. The system renders the filtered, paginated results showing: certificate number, participant name, course title, issuance date, and status badge.

**Alternative Flows:**

*AF-19A — No Results:*
- If no records match the filter/search, the system displays: "No certificates found matching the criteria."

**Postconditions (Success):**
- The Administrator sees the filtered archive results with correct status values (Valid / Revoked only).

---

*End of Use Case Specification — Version 1.3*
