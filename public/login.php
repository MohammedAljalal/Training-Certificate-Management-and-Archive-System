<?php
/**
 * Login page — FR-01, FR-02, FR-03, FR-04, FR-05, FR-09
 * Clean Modern Edition v5.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../config/db.php';

session_boot();

// Already logged in → redirect
if (is_logged_in()) {
    if (current_user_role() === 'admin') {
        redirect('/admin/dashboard.php');
    } else {
        redirect('/participant/dashboard.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Email address and password are both required.';
    } else {
        require_once __DIR__ . '/../src/models/ParticipantModel.php';
        $model = new ParticipantModel();
        $user  = $model->findByEmail($email);

        if ($user && password_verify($password, $user['password']) && $user['status'] === 'Active') {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['full_name'];

            if ($user['role'] === 'admin') {
                redirect('/admin/dashboard.php');
            } else {
                redirect('/participant/dashboard.php');
            }
        } else {
            $error = 'Invalid email or password. Please verify your credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Training Certificate System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
          crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="login-wrapper">

    <!-- ══════════════ Left Visual Panel ══════════════ -->
    <div class="login-panel">
        <div class="login-panel__grid"></div>

        <div class="login-panel__top">

            <!-- Brand -->
            <div class="lp-brand">
                <div class="lp-brand__icon">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <div class="lp-brand__text">
                    <span class="lp-brand__name">University of Tabuk</span>
                    <span class="lp-brand__dept">Faculty of Computers &amp; IT</span>
                </div>
            </div>

            <!-- Headline -->
            <h1 class="lp-headline">
                Certificate<br>Management<br><span>System</span>
            </h1>

            <!-- Stats row -->
            <div class="lp-stats">
                <div class="lp-stat">
                    <div class="lp-stat__num">27+</div>
                    <div class="lp-stat__lbl">Certificates</div>
                </div>
                <div class="lp-stat-divider"></div>
                <div class="lp-stat">
                    <div class="lp-stat__num">13</div>
                    <div class="lp-stat__lbl">Courses</div>
                </div>
                <div class="lp-stat-divider"></div>
                <div class="lp-stat">
                    <div class="lp-stat__num">25+</div>
                    <div class="lp-stat__lbl">Participants</div>
                </div>
            </div>

            <!-- Certificate preview card -->
            <div class="lp-cert-card">
                <div class="lp-cert-card__header">
                    <div class="lp-cert-card__badge">
                        <i class="fa-solid fa-certificate"></i>
                        Certificate of Completion
                    </div>
                    <span class="lp-cert-card__status">
                        <i class="fa-solid fa-circle-check"></i> Valid
                    </span>
                </div>
                <div class="lp-cert-card__name">Sarah Al-Ghamdi</div>
                <div class="lp-cert-card__course">Advanced Web Development with PHP &amp; MySQL</div>
                <div class="lp-cert-card__footer">
                    <span><i class="fa-regular fa-calendar"></i> 20 Feb 2026</span>
                    <span class="lp-cert-card__id">CERT-2026-89B4C12E</span>
                </div>
            </div>

        </div>

        <!-- Panel Footer -->
        <div class="lp-panel-footer">
            <span>CIT1303 &nbsp;·&nbsp; Advanced Web Technology</span>
            <span>2026</span>
        </div>
    </div>

    <!-- ══════════════ Right Form Panel ══════════════ -->
    <div class="login-form-panel">
        <div class="login-card">

            <!-- Icon mark -->
            <div class="lf-logo-mark">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>

            <h2 class="login-card__title">Welcome back</h2>
            <p class="login-card__subtitle">Sign in to your account to continue.</p>

            <!-- Demo Role Switcher -->
            <div class="login-role-tabs">
                <button type="button" class="role-tab-btn active" id="btnRoleAdmin" onclick="setDemoRole('admin')">
                    <i class="fa-solid fa-shield-halved"></i> Administrator
                </button>
                <button type="button" class="role-tab-btn" id="btnRoleParticipant" onclick="setDemoRole('participant')">
                    <i class="fa-solid fa-user-graduate"></i> Participant
                </button>
            </div>

            <?php if ($error): ?>
                <div class="alert alert--error" data-auto-dismiss>
                    <i class="fa-solid fa-circle-xmark"></i>
                    <?= h($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/login.php" id="loginForm" novalidate>
                <?= csrf_field() ?>

                <!-- Email -->
                <div class="login-field-wrap">
                    <label class="login-label" for="email">Email Address</label>
                    <div class="login-input-box">
                        <i class="fa-regular fa-envelope login-input-icon"></i>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="login-input"
                            value="<?= h($_POST['email'] ?? '') ?>"
                            required
                            autocomplete="email"
                            placeholder="you@example.com"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div class="login-field-wrap">
                    <label class="login-label" for="password">Password</label>
                    <div class="login-input-box">
                        <i class="fa-solid fa-lock login-input-icon"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="login-input"
                            value=""
                            required
                            autocomplete="current-password"
                            placeholder="Enter your password"
                        >
                        <button type="button" class="login-eye-btn" id="togglePasswordBtn" aria-label="Toggle password visibility">
                            <i class="fa-regular fa-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember + link -->
                <div class="login-meta-row">
                    <label class="remember-label">
                        <input type="checkbox" name="remember" class="remember-check" checked>
                        <span>Remember me</span>
                    </label>
                    <a href="/verify.php" class="lf-verify-link">
                        <i class="fa-solid fa-magnifying-glass"></i> Verify certificate
                    </a>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn--login-submit" id="submitBtn">
                    <span>Sign In</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <!-- Divider -->
            <div class="lf-divider">or</div>

            <!-- Verify portal link -->
            <a href="/verify.php" class="btn--verify-link">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Certificate Verification Portal</span>
                <span class="badge badge--success" style="font-size:.65rem;margin-left:auto;">Public</span>
            </a>

            <div class="lf-footer-note">
                University of Tabuk &nbsp;·&nbsp; Training Certificate Management System
            </div>

        </div>
    </div>

</div>

<script>
function setDemoRole(role) {
    const emailInput = document.getElementById('email');
    const passInput  = document.getElementById('password');
    const btnAdmin   = document.getElementById('btnRoleAdmin');
    const btnPart    = document.getElementById('btnRoleParticipant');

    if (role === 'admin') {
        btnAdmin.classList.add('active');
        btnPart.classList.remove('active');
        emailInput.value = 'admin@tcmas.local';
        passInput.value  = 'Admin@1234';
    } else {
        btnPart.classList.add('active');
        btnAdmin.classList.remove('active');
        emailInput.value = 'm.otaibi@example.com';
        passInput.value  = 'Password@123';
    }
    emailInput.style.borderColor = '#4f46e5';
    passInput.style.borderColor  = '#4f46e5';
    setTimeout(() => {
        emailInput.style.borderColor = '';
        passInput.style.borderColor  = '';
    }, 500);
}

// Password visibility toggle
const toggleBtn  = document.getElementById('togglePasswordBtn');
const passInput  = document.getElementById('password');
const toggleIcon = document.getElementById('togglePasswordIcon');

toggleBtn.addEventListener('click', function() {
    if (passInput.type === 'password') {
        passInput.type = 'text';
        toggleIcon.className = 'fa-regular fa-eye-slash';
    } else {
        passInput.type = 'password';
        toggleIcon.className = 'fa-regular fa-eye';
    }
});
</script>
<script src="/assets/js/main.js"></script>
</body>
</html>
