<?php
require_once __DIR__ . '/includes/public-header.php';
$page_title = 'Create Account - ' . APP_NAME;

if (isset($_SESSION['user_id'])) {
    header('Location: customer/dashboard.php');
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
                    <span class="auth-logo-sub">Hotel ERP</span>
                </a>

                <h1 class="auth-title">Create Account</h1>
                <p class="auth-subtitle">Join FFB Hotel today</p>

                <?php flash(); ?>

                <form action="auth/register-handler.php" method="POST" class="auth-form" novalidate>
                    <?php echo csrf_field(); ?>

                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="full_name" name="full_name"
                               placeholder="John Doe" required
                               value="<?php echo htmlspecialchars($_POST['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <label for="full_name"><i class="bi bi-person-fill me-2"></i>Full Name</label>
                        <div class="invalid-feedback">Please enter your full name.</div>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email"
                               placeholder="name@example.com" required
                               value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <label for="email"><i class="bi bi-envelope-fill me-2"></i>Email Address</label>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="tel" class="form-control" id="phone" name="phone"
                               placeholder="+1 234 567 8900"
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <label for="phone"><i class="bi bi-telephone-fill me-2"></i>Phone Number (Optional)</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Password" required minlength="8">
                        <label for="password"><i class="bi bi-lock-fill me-2"></i>Password</label>
                        <div class="invalid-feedback">Password must be at least 8 characters.</div>
                    </div>

                    <div class="form-floating mb-4">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                               placeholder="Confirm Password" required>
                        <label for="confirm_password"><i class="bi bi-lock-fill me-2"></i>Confirm Password</label>
                        <div class="invalid-feedback">Passwords must match.</div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the
                                <a href="terms.php" class="auth-link" target="_blank">Terms of Service</a>
                                and
                                <a href="privacy.php" class="auth-link" target="_blank">Privacy Policy</a>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-auth btn-gold w-100 mb-3">
                        <i class="bi bi-person-plus me-2"></i>Create Account
                    </button>
                </form>

                <p class="auth-footer-text">
                    Already have an account?
                    <a href="login.php" class="auth-link">Sign In</a>
                </p>

                <div class="auth-divider">
                    <span>Secure Registration</span>
                </div>

                <div class="auth-security-badges">
                    <span><i class="bi bi-shield-check"></i> SSL Encrypted</span>
                    <span><i class="bi bi-lock"></i> Secure</span>
                    <span><i class="bi bi-person-badge"></i> Verified</span>
                </div>
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

.form-check-input {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(212, 175, 55, 0.3);
    cursor: pointer;
}

.form-check-input:checked {
    background-color: #d4af37;
    border-color: #d4af37;
}

.form-check-label {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.85rem;
    cursor: pointer;
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
    margin-bottom: 1.5rem;
}

.auth-footer-text .auth-link {
    font-weight: 600;
}

.auth-divider {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.auth-divider::before,
.auth-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(212, 175, 55, 0.3), transparent);
}

.auth-divider span {
    color: rgba(255, 255, 255, 0.35);
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 2px;
    white-space: nowrap;
}

.auth-security-badges {
    display: flex;
    justify-content: center;
    gap: 1.25rem;
    flex-wrap: wrap;
}

.auth-security-badges span {
    color: rgba(255, 255, 255, 0.3);
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.auth-security-badges span i {
    color: #d4af37;
    font-size: 0.7rem;
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
<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
