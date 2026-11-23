<?php
/**
 * Public Certificate Verification Page v2.0
 * FR-45..FR-52-PV, ASM-03, NFR-01, NFR-02, NFR-16
 *
 * Professional verification portal with institutional diploma styling,
 * trust indicators, and quick-search chips.
 * No authentication required. Works with JavaScript disabled (NFR-16).
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/models/CertificateModel.php';

session_boot();

$results = null;
$query   = '';
$capped  = false;
$error   = '';

if (isset($_GET['q'])) {
    $query = trim($_GET['q']);

    if (strlen($query) < 2) {
        $error = 'Please enter at least 2 characters to perform a search.';
    } else {
        $model   = new CertificateModel();
        $results = $model->publicSearch($query, 20);
        $capped  = count($results) >= 20;

        if (empty($results)) {
            $error = 'No authentic certificate records were found matching your search query.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Verification Portal — Training Certificate Management and Archive System</title>
    <!-- Font Awesome 6 Free CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
          crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="v-page">

    <!-- Top Navigation Bar -->
    <header class="v-navbar">
        <a href="/verify.php" class="v-navbar__brand">
            <div class="v-navbar__icon">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div class="v-navbar__titles">
                <h1>TCMAS Credential Registry</h1>
                <span>University of Tabuk &nbsp;·&nbsp; CIT1303</span>
            </div>
        </a>
        <div class="v-navbar__actions">
            <?php if (is_logged_in()): ?>
                <a href="<?= current_user_role() === 'admin' ? '/admin/dashboard.php' : '/participant/dashboard.php' ?>" class="btn btn--primary btn--sm">
                    <i class="fa-solid fa-gauge-high"></i> My Dashboard
                </a>
            <?php else: ?>
                <a href="/login.php" class="btn btn--outline btn--sm" style="color:#fff;border-color:rgba(255,255,255,0.25);">
                    <i class="fa-solid fa-right-to-bracket"></i> Sign In to Portal
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="v-hero">
        <div class="v-hero__pill">
            <i class="fa-solid fa-shield-halved"></i>
            Official Online Credential Registry
        </div>
        <h1 class="v-hero__title">
            Verify Certificate Authenticity
        </h1>
        <p class="v-hero__subtitle">
            Validate training certificates and professional credentials issued by the institution. Search by unique Certificate ID or participant full name.
        </p>
    </section>

    <!-- Floating Search Container -->
    <div class="v-search-container">
        <div class="v-search-card">
            <form method="GET" action="/verify.php" class="v-search-form" novalidate>
                <label class="form-label" for="q" style="font-size:0.875rem;font-weight:700;color:#1e293b;">
                    Enter Certificate Number or Participant Name
                </label>
                <div class="v-search-row">
                    <div class="v-input-group">
                        <input
                            type="text"
                            id="q"
                            name="q"
                            class="v-search-input"
                            value="<?= h($query) ?>"
                            placeholder="e.g. CERT-2026-039EDFD6 or Mohammed Al-Otaibi"
                            autocomplete="off"
                            minlength="2"
                            required
                        >
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                    <button type="submit" class="v-search-btn">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Verify Record</span>
                    </button>
                </div>

                <!-- Quick search suggestions -->
                <div class="v-chips">
                    <span class="v-chips__label">Try sample:</span>
                    <a href="/verify.php?q=CERT-2026-039EDFD6" class="v-chip">
                        <i class="fa-solid fa-hashtag"></i> CERT-2026-039EDFD6
                    </a>
                    <a href="/verify.php?q=Mohammed+Al-Otaibi" class="v-chip">
                        <i class="fa-solid fa-user"></i> Mohammed Al-Otaibi
                    </a>
                    <a href="/verify.php?q=Sarah+Al-Ghamdi" class="v-chip">
                        <i class="fa-solid fa-user"></i> Sarah Al-Ghamdi
                    </a>
                    <a href="/verify.php?q=Khalid+Al-Harbi" class="v-chip v-chip--red" title="Demonstrates Revoked Certificate State">
                        <i class="fa-solid fa-ban"></i> Khalid Al-Harbi (Revoked test)
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Trust Indicators Bar -->
    <div class="v-trust-grid">
        <div class="v-trust-item">
            <div class="v-trust-icon">
                <i class="fa-solid fa-fingerprint"></i>
            </div>
            <div class="v-trust-content">
                <h3>Cryptographic ID</h3>
                <p>High-entropy unique identifiers generated with secure random bytes.</p>
            </div>
        </div>
        <div class="v-trust-item">
            <div class="v-trust-icon">
                <i class="fa-solid fa-box-archive"></i>
            </div>
            <div class="v-trust-content">
                <h3>Permanent Archive</h3>
                <p>Immutable record retention. Issued certificates are never deleted.</p>
            </div>
        </div>
        <div class="v-trust-item">
            <div class="v-trust-icon">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div class="v-trust-content">
                <h3>Privacy Protected</h3>
                <p>Personal contacts, passwords, and private account data are never exposed.</p>
            </div>
        </div>
    </div>

    <!-- Results Area -->
    <main class="v-results-container">

        <!-- Error Notification -->
        <?php if ($error): ?>
            <div class="alert alert--warning" style="margin-bottom:2rem;padding:1.25rem 1.5rem;box-shadow:var(--shadow-sm);">
                <i class="fa-solid fa-circle-exclamation" style="font-size:1.35rem;margin-top:.1rem;"></i>
                <div>
                    <strong style="display:block;margin-bottom:.2rem;font-size:.95rem;">Search Notice</strong>
                    <span><?= h($error) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Search Results -->
        <?php if ($results !== null && !empty($results)): ?>

            <div class="v-results-header">
                <h2>
                    <i class="fa-solid fa-clipboard-check" style="color:var(--clr-primary);"></i>
                    Verification Results (<?= count($results) ?>)
                </h2>
                <a href="/verify.php" class="btn btn--outline btn--sm">
                    <i class="fa-solid fa-xmark"></i> Clear Search
                </a>
            </div>

            <?php if ($capped): ?>
                <div class="alert alert--info" style="margin-bottom:1.5rem;">
                    <i class="fa-solid fa-circle-info"></i>
                    <div>Displaying the first 20 matching records. Please refine your query for a narrower result.</div>
                </div>
            <?php endif; ?>

            <?php foreach ($results as $r): ?>
                <?php $isValid = ($r['status'] === 'Valid'); ?>
                <article class="v-cert-card <?= $isValid ? 'v-cert-card--valid' : 'v-cert-card--revoked' ?>">

                    <!-- Top Status Ribbon -->
                    <div class="v-cert-card__banner">
                        <div class="v-cert-card__status">
                            <i class="fa-solid fa-<?= $isValid ? 'circle-check' : 'ban' ?>"></i>
                            <span><?= $isValid ? 'OFFICIALLY VERIFIED &amp; VALID' : 'REVOKED CREDENTIAL — INVALID' ?></span>
                        </div>
                        <div class="v-cert-card__id-pill">
                            <?= h($r['certificate_number']) ?>
                        </div>
                    </div>

                    <!-- Certificate Card Body (Diploma Style) -->
                    <div class="v-cert-card__body">
                        <i class="fa-solid fa-award v-cert-card__watermark"></i>

                        <div class="v-cert-card__org">
                            Training Certificate Management and Archive System &nbsp;·&nbsp; Official Registry
                        </div>

                        <div class="v-cert-card__recipient-label">This credential certifies that</div>
                        <div class="v-cert-card__recipient-name">
                            <?= h($r['participant_name']) ?>
                        </div>

                        <div class="v-cert-card__course-label">has successfully completed the professional course</div>
                        <div class="v-cert-card__course-title">
                            <?= h($r['course_title']) ?>
                        </div>

                        <!-- Metadata Grid -->
                        <div class="v-cert-card__meta-grid">
                            <div class="v-meta-item">
                                <span class="v-meta-item__label">
                                    <i class="fa-solid fa-calendar-check"></i> Completion Date
                                </span>
                                <span class="v-meta-item__value">
                                    <?= h(fmt_date($r['completion_date'])) ?>
                                </span>
                            </div>
                            <div class="v-meta-item">
                                <span class="v-meta-item__label">
                                    <i class="fa-solid fa-calendar-plus"></i> Date of Issuance
                                </span>
                                <span class="v-meta-item__value">
                                    <?= h(fmt_date($r['issued_at'])) ?>
                                </span>
                            </div>
                            <div class="v-meta-item">
                                <span class="v-meta-item__label">
                                    <i class="fa-solid fa-shield"></i> Status
                                </span>
                                <span class="v-meta-item__value">
                                    <span class="badge badge--<?= $isValid ? 'success' : 'danger' ?>">
                                        <i class="fa-solid fa-<?= $isValid ? 'circle-check' : 'ban' ?>"></i>
                                        <?= h($r['status']) ?>
                                    </span>
                                </span>
                            </div>
                        </div>

                        <!-- Revocation Notice if Revoked -->
                        <?php if (!$isValid): ?>
                            <div class="v-revocation-alert">
                                <h4>
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    Official Revocation Notice
                                </h4>
                                <p>
                                    This certificate was permanently invalidated by institutional authority.
                                    It does not constitute a valid credential or recognized completion.
                                    <?php if (!empty($r['revoked_at'])): ?>
                                        <br><strong>Revocation Date:</strong> <?= h(fmt_date($r['revoked_at'])) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>

                    </div>

                    <!-- Certificate Card Footer -->
                    <div class="v-cert-card__footer">
                        <div>
                            <i class="fa-solid fa-certificate"></i>
                            Verified against institutional records at University of Tabuk.
                        </div>
                        <div>
                            <span class="text-muted">Public Search View &nbsp;·&nbsp; PDF downloads restricted to account holders</span>
                        </div>
                    </div>

                </article>
            <?php endforeach; ?>

        <?php elseif ($results === null && empty($error)): ?>

            <!-- Initial Explanatory Section (Empty State) -->
            <div class="v-steps-container">
                <div class="v-steps-header">
                    <h2>How Online Credential Verification Works</h2>
                    <p>Our 3-step verification process validates credentials directly against the institution archive.</p>
                </div>
                <div class="v-steps-grid">
                    <div class="v-step-card">
                        <div class="v-step-number">1</div>
                        <div class="v-step-icon">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </div>
                        <h3>Enter Credential</h3>
                        <p>Locate the Certificate Number on the bottom of the diploma (e.g. CERT-2026-...) or enter the full name.</p>
                    </div>
                    <div class="v-step-card">
                        <div class="v-step-number">2</div>
                        <div class="v-step-icon">
                            <i class="fa-solid fa-database"></i>
                        </div>
                        <h3>Query Archive</h3>
                        <p>The system queries the central secure MariaDB registry for an exact cryptographic match.</p>
                    </div>
                    <div class="v-step-card">
                        <div class="v-step-number">3</div>
                        <div class="v-step-icon">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <h3>Instant Authenticity</h3>
                        <p>View the participant details, course title, completion date, and official validity status in real-time.</p>
                    </div>
                </div>
            </div>

        <?php endif; ?>

    </main>

    <!-- Institutional Footer -->
    <footer class="v-footer">
        <div class="v-footer-content">
            <div class="v-footer-brand">
                <h4>Training Certificate Management and Archive System (TCMAS)</h4>
                <p>University of Tabuk &nbsp;·&nbsp; College of Computers &amp; Information Technology &nbsp;·&nbsp; Course CIT1303</p>
            </div>
            <div class="v-footer-links">
                <a href="/login.php"><i class="fa-solid fa-right-to-bracket"></i> Sign In</a>
                <a href="/verify.php"><i class="fa-solid fa-magnifying-glass"></i> Verification</a>
                <a href="#top"><i class="fa-solid fa-arrow-up"></i> Top</a>
            </div>
        </div>
    </footer>

    <script src="/assets/js/main.js"></script>
</body>
</html>
