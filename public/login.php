<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Customer Login Page
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/middleware/csrf.php';
require_once __DIR__ . '/../app/middleware/auth.php';

// If already signed in, redirect to profile
requireGuest();

$redirectTarget = $_GET['redirect'] ?? BASE_URL . '/profile.php';
// Prevent open redirects: ensure relative or same domain
if (!str_starts_with($redirectTarget, '/') && !str_starts_with($redirectTarget, BASE_URL)) {
    $redirectTarget = BASE_URL . '/profile.php';
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $redirect = $_POST['redirect'] ?? $redirectTarget;

        if (empty($email) || empty($password)) {
            $error = 'Please enter both your email address and password.';
        } else {
            try {
                $db = getDB();
                $stmt = $db->prepare("SELECT id, full_name, email, phone, password_hash FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    // Successful login
                    loginUser($user);
                    setFlash('success', 'Welcome back, ' . e($user['full_name']) . '!');
                    redirect($redirect);
                } else {
                    $error = 'Invalid email address or password combination.';
                }
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                $error = 'Authentication service temporarily unavailable. Please try again.';
            }
        }
    }
}

$pageTitle = "Sign In | " . APP_NAME;
require_once __DIR__ . '/../app/components/header.php';
?>

<div class="container section" style="max-width: 500px;">
    <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 2.5rem; box-shadow: var(--shadow-sm);">
        <div style="text-align: center; margin-bottom: 2rem;">
            <span class="section-subtitle">Client Portal</span>
            <h1 style="font-size: 2.2rem; margin-bottom: 0.5rem;">Sign In</h1>
            <p style="font-size: 0.95rem;">Access your jewellery orders, saved pieces, and delivery tracking.</p>
            <div class="gold-divider"></div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error" role="alert">
                <span class="alert-icon">✕</span>
                <div class="alert-content"><?= e($error); ?></div>
            </div>
        <?php endif; ?>

        <!-- Store Demo Customer Helper -->
        <div style="background: #F8F5EE; border: 1px dashed var(--gold-primary); padding: 0.85rem 1rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; font-size: 0.85rem;">
            <strong>💎 Demo Customer Account:</strong><br>
            Email: <code>pooja@example.com</code> | Password: <code>User@123</code>
            <div style="margin-top: 0.5rem;">
                <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('email').value='pooja@example.com';document.getElementById('password').value='User@123';" style="padding: 0.2rem 0.6rem; font-size: 0.75rem;">
                    Autofill Demo Customer
                </button>
            </div>
        </div>

        <form action="<?= BASE_URL; ?>/login.php" method="POST" autocomplete="off">
            <?= csrfField(); ?>
            <input type="hidden" name="redirect" value="<?= e($redirectTarget); ?>">

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="pooja@example.com" value="<?= e($email); ?>" required autofocus>
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.45rem;">
                    <label class="form-label" for="password" style="margin-bottom: 0;">Password</label>
                </div>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                    <button type="button" class="toggle-password-btn" data-target="password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; font-size: 0.8rem; color: var(--gold-dark); cursor: pointer;">Show</button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 1.5rem;">
                Sign In &rarr;
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid var(--border-color); font-size: 0.92rem;">
            New to Shivkrupa Jewellery? <a href="<?= BASE_URL; ?>/register.php" style="color: var(--gold-dark); font-weight: 600;">Create an Account</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../app/components/footer.php'; ?>
