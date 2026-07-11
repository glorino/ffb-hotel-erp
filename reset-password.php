<?php
require_once __DIR__ . '/includes/public-header.php';
$page_title = 'Reset Password - ' . APP_NAME;

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$token = $_GET['token'] ?? '';
if ($token === '') {
    set_flash('danger', 'Invalid password reset link.');
    header('Location: login.php');
    exit;
}
?>

<div class="auth-wrapper">
    <div class="auth-background"></div>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-card-inner">
                <a href="index.php" class="auth-logo">
                    <span class="auth-logo-text">FFB HOTEL</span>
                    <span class="auth-logo-sub">Hospitality ERP</span>
                </a>

                <div class="text-center mb-3">
                    <div class="auth-icon-circle">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                </div>

                <h1 class="auth-title">Set New Password</h1>
                <p class="auth-subtitle">Choose a strong password for your account</p>

                <?php flash(); ?>

                <form action="auth/password-reset-confirm.php" method="POST" class="auth-form" novalidate>
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="New Password" required minlength="8">
                        <label for="password"><i class="bi bi-lock-fill me-2"></i>New Password</label>
                        <div class="invalid-feedback">Password must be at least 8 characters.</div>
                    </div>

                    <div class="form-floating mb-4">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                               placeholder="Confirm New Password" required>
                        <label for="confirm_password"><i class="bi bi-lock-fill me-2"></i>Confirm New Password</label>
                        <div class="invalid-feedback">Passwords must match.</div>
                    </div>

                    <div class="password-requirements mb-4">
                        <p class="requirements-title">Password must contain:</p>
                        <ul class="requirements-list">
                            <li id="req-length"><i class="bi bi-circle"></i> At least 8 characters</li>
                            <li id="req-uppercase"><i class="bi bi-circle"></i> At least 1 uppercase letter</li>
                            <li id="req-lowercase"><i class="bi bi-circle"></i> At least 1 lowercase letter</li>
                            <li id="req-number"><i class="bi bi-circle"></i> At least 1 number</li>
                        </ul>
                    </div>

                    <button type="submit" class="btn btn-auth btn-gold w-100 mb-3">
                        <i class="bi bi-check-circle me-2"></i>Reset Password
                    </button>
                </form>

                <p class="auth-footer-text">
                    <a href="login.php" class="auth-link">
                        <i class="bi bi-arrow-left me-1"></i>Back to Sign In
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

<style>
.auth-wrapper {
    position: relative;
    min-height: calc(100vh - 160px);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    padding: 2rem 1rem;
}

.auth-background {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse at 20% 50%, rgba(212, 175, 55, 0.08) 0%, transparent 50%),
        radial-gradient(ellipse at 80% 50%, rgba(212, 175, 55, 0.05) 0%, transparent 50%),
        linear-gradient(135deg, #0a0a1a 0%, #1a1a2e 50%, #0a0a1a 100%);
    z-index: 0;
}

.auth-background::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        radial-gradient(1px 1px at 10% 20%, rgba(212, 175, 55, 0.3) 0%, transparent 0),
        radial-gradient(1px 1px at 30% 70%, rgba(212, 175, 55, 0.2) 0%, transparent 0),
        radial-gradient(1px 1px at 50% 10%, rgba(255, 255, 255, 0.1) 0%, transparent 0),
        radial-gradient(1px 1px at 70% 80%, rgba(212, 175, 55, 0.2) 0%, transparent 0),
        radial-gradient(1px 1px at 90% 40%, rgba(255, 255, 255, 0.1) 0%, transparent 0);
    background-size: 200px 200px;
}

.auth-container {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 440px;
}

.auth-card {
    background: rgba(26, 26, 46, 0.6);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(212, 175, 55, 0.15);
    border-radius: 24px;
    box-shadow:
        0 8px 32px rgba(0, 0, 0, 0.4),
        inset 0 1px 0 rgba(212, 175, 55, 0.1);
    overflow: hidden;
}

.auth-card-inner {
    padding: 2.5rem 2rem;
}

.auth-logo {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none;
    margin-bottom: 1.75rem;
}

.auth-logo-text {
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(135deg, #d4af37, #f5d76e, #d4af37);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    letter-spacing: 4px;
}

.auth-logo-sub {
    font-family: 'Inter', sans-serif;
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.4);
    text-transform: uppercase;
    letter-spacing: 6px;
    margin-top: 2px;
}

.auth-icon-circle {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(212, 175, 55, 0.1);
    border: 2px solid rgba(212, 175, 55, 0.25);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.5rem;
}

.auth-icon-circle i {
    font-size: 1.5rem;
    color: #d4af37;
}

.auth-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.75rem;
    font-weight: 600;
    color: #fff;
    text-align: center;
    margin-bottom: 0.25rem;
}

.auth-subtitle {
    color: rgba(255, 255, 255, 0.5);
    text-align: center;
    font-size: 0.9rem;
    margin-bottom: 1.75rem;
}

.auth-form .form-floating > .form-control:focus ~ label i,
.auth-form .form-floating > .form-control:not(:placeholder-shown) ~ label i {
    color: #d4af37;
}

.auth-form .form-control {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(212, 175, 55, 0.15);
    border-radius: 12px;
    color: #fff;
    height: 56px;
    padding: 1rem 0.75rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.auth-form .form-control:focus {
    background: rgba(255, 255, 255, 0.08);
    border-color: #d4af37;
    box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15);
    color: #fff;
}

.auth-form .form-control::placeholder {
    color: transparent;
}

.auth-form .form-floating > label {
    color: rgba(255, 255, 255, 0.45);
    padding: 1rem 0.75rem;
    font-size: 0.9rem;
}

.auth-form .form-floating > .form-control:focus ~ label,
.auth-form .form-floating > .form-control:not(:placeholder-shown) ~ label {
    color: #d4af37;
    opacity: 0.85;
    transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
}

.auth-form .form-floating > .form-control:-webkit-autofill {
    -webkit-box-shadow: 0 0 0 1000px rgba(26, 26, 46, 1) inset !important;
    -webkit-text-fill-color: #fff !important;
    border-color: #d4af37 !important;
}

.auth-link {
    color: #d4af37;
    text-decoration: none;
    font-size: 0.85rem;
    transition: color 0.3s;
}

.auth-link:hover {
    color: #f5d76e;
    text-decoration: underline;
}

.btn-auth {
    height: 52px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn-gold {
    background: linear-gradient(135deg, #d4af37, #f5d76e);
    border: none;
    color: #1a1a2e;
}

.btn-gold:hover {
    background: linear-gradient(135deg, #f5d76e, #d4af37);
    color: #1a1a2e;
    transform: translateY(-1px);
    box-shadow: 0 8px 25px rgba(212, 175, 55, 0.35);
}

.btn-gold:active {
    transform: translateY(0);
}

.auth-footer-text {
    text-align: center;
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.85rem;
    margin-bottom: 0;
}

.auth-footer-text .auth-link {
    font-weight: 600;
}

.password-requirements {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(212, 175, 55, 0.1);
    border-radius: 10px;
    padding: 1rem;
}

.requirements-title {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.8rem;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.requirements-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.requirements-list li {
    color: rgba(255, 255, 255, 0.4);
    font-size: 0.8rem;
    padding: 2px 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: color 0.3s;
}

.requirements-list li i {
    font-size: 0.5rem;
    color: rgba(255, 255, 255, 0.2);
    transition: color 0.3s;
}

.requirements-list li.valid {
    color: #28a745;
}

.requirements-list li.valid i {
    color: #28a745;
}

@media (max-width: 480px) {
    .auth-card-inner {
        padding: 1.75rem 1.25rem;
    }
    .auth-title {
        font-size: 1.5rem;
    }
}

.site-header .navbar {
    display: none;
}

.site-main {
    padding-top: 0;
}
</style>

<script>
(function() {
    const password = document.getElementById('password');
    const reqLength = document.getElementById('req-length');
    const reqUppercase = document.getElementById('req-uppercase');
    const reqLowercase = document.getElementById('req-lowercase');
    const reqNumber = document.getElementById('req-number');

    password.addEventListener('input', function() {
        const val = this.value;

        reqLength.className = val.length >= 8 ? 'valid' : '';
        reqLength.querySelector('i').className = val.length >= 8 ? 'bi bi-check-circle-fill' : 'bi bi-circle';

        reqUppercase.className = /[A-Z]/.test(val) ? 'valid' : '';
        reqUppercase.querySelector('i').className = /[A-Z]/.test(val) ? 'bi bi-check-circle-fill' : 'bi bi-circle';

        reqLowercase.className = /[a-z]/.test(val) ? 'valid' : '';
        reqLowercase.querySelector('i').className = /[a-z]/.test(val) ? 'bi bi-check-circle-fill' : 'bi bi-circle';

        reqNumber.className = /[0-9]/.test(val) ? 'valid' : '';
        reqNumber.querySelector('i').className = /[0-9]/.test(val) ? 'bi bi-check-circle-fill' : 'bi bi-circle';
    });
})();
</script>
<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
