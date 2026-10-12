<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Customer Registration Page
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';
require_once dirname(__DIR__) . '/app/middleware/csrf.php';
require_once dirname(__DIR__) . '/app/middleware/auth.php';

// If already logged in, redirect to profile
requireGuest();

$errors = [];
$fullName = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    if (!verifyCsrfToken()) {
        $errors[] = 'Security token expired. Please try submitting again.';
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // 2. Validate Inputs
    if (empty($fullName) || mb_strlen($fullName) < 3) {
        $errors[] = 'Please enter your full name (minimum 3 characters).';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($phone) || !preg_match('/^[0-9+\-\s]{10,15}$/', $phone)) {
        $errors[] = 'Please enter a valid 10-digit mobile phone number.';
    }

    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    // 3. Check for Duplicate Email in Database
    if (empty($errors)) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with this email address already exists. Please sign in instead.';
            } else {
                // 4. Secure Password Hashing
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $insertStmt = $db->prepare("
                    INSERT INTO users (full_name, email, phone, password_hash, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $insertStmt->execute([$fullName, $email, $phone, $passwordHash]);
                $newUserId = $db->lastInsertId();

                setFlash('success', 'Registration successful! Please sign in with your email and password.');
                redirect(BASE_URL . '/login.php');
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $errors[] = 'Unable to create account right now. Please try again later.';
        }
    }
}

$pageTitle = "Create Your Account | " . APP_NAME;
require_once dirname(__DIR__) . '/app/components/header.php';
?>

<div class="container section" style="max-width: 580px;">
    <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 2.5rem; box-shadow: var(--shadow-sm);">
        <div style="text-align: center; margin-bottom: 2rem;">
            <span class="section-subtitle">Privilege Club</span>
            <h1 style="font-size: 2.2rem; margin-bottom: 0.5rem;">Join Shivkrupa</h1>
            <p style="font-size: 0.95rem;">Experience privileged access to bespoke bridal jewels, private showcases, and insured order delivery.</p>
            <div class="gold-divider"></div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error" role="alert">
                <span class="alert-icon">✕</span>
                <div class="alert-content">
                    <ul style="list-style: disc; padding-left: 1.25rem; margin: 0;">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL; ?>/register.php" method="POST" autocomplete="off">
            <?= csrfField(); ?>

            <div class="form-group">
                <label class="form-label" for="full_name">Full Name <span style="color:var(--danger);">*</span></label>
                <input type="text" id="full_name" name="full_name" class="form-control" placeholder="e.g. Pooja Sharma" value="<?= e($fullName); ?>" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address <span style="color:var(--danger);">*</span></label>
                <input type="email" id="email" name="email" class="form-control" placeholder="name@example.com" value="<?= e($email); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Mobile Phone Number <span style="color:var(--danger);">*</span></label>
                <input type="tel" id="phone" name="phone" class="form-control" placeholder="10-digit mobile number" value="<?= e($phone); ?>" required>
            </div>

            <div class="input-row">
                <div class="form-group">
                    <label class="form-label" for="password">Password <span style="color:var(--danger);">*</span></label>
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
                        <button type="button" class="toggle-password-btn" data-target="password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; font-size: 0.8rem; color: var(--gold-dark); cursor: pointer;">Show</button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password <span style="color:var(--danger);">*</span></label>
                    <div style="position: relative;">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-type password" required>
                        <button type="button" class="toggle-password-btn" data-target="confirm_password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; font-size: 0.8rem; color: var(--gold-dark); cursor: pointer;">Show</button>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 1.5rem; font-size: 0.85rem; color: var(--text-muted);">
                By registering, you agree to our Terms of Fine Jewellery Purchase, Certified Hallmarking Authenticity, and Privacy Policy.
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">
                Create Account &rarr;
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid var(--border-color); font-size: 0.92rem;">
            Already have an account? <a href="<?= BASE_URL; ?>/login.php" style="color: var(--gold-dark); font-weight: 600;">Sign In Here</a>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/app/components/footer.php'; ?>
