<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Staff & Administrator Login Page
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/middleware/csrf.php';
require_once __DIR__ . '/../app/middleware/admin-auth.php';

// If already signed in, redirect to dashboard
if (isAdminLoggedIn()) {
    redirect(ADMIN_URL . '/dashboard.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Security session expired. Please try submitting again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Please enter both administrator username and password.';
        } else {
            try {
                $db = getDB();
                $stmt = $db->prepare("SELECT id, username, email, full_name, password_hash FROM admins WHERE username = ? OR email = ? LIMIT 1");
                $stmt->execute([$username, $username]);
                $admin = $stmt->fetch();

                if ($admin && password_verify($password, $admin['password_hash'])) {
                    loginAdmin($admin);
                    setFlash('success', 'Authenticated as ' . e($admin['full_name']));
                    redirect(ADMIN_URL . '/dashboard.php');
                } else {
                    $error = 'Invalid administrator credentials.';
                }
            } catch (PDOException $e) {
                error_log("Admin auth error: " . $e->getMessage());
                $error = 'Authentication service error. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Authentication | <?= APP_NAME; ?></title>
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/style.css">
    <style>
        body {
            background: #181512;
            color: #E2DDD5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
        }
        .admin-login-box {
            background: #24201C;
            border: 1px solid rgba(197, 160, 89, 0.3);
            border-radius: var(--radius-md);
            padding: 3rem 2.5rem;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.5);
        }
    </style>
</head>
<body>

<div class="admin-login-box">
    <div style="text-align: center; margin-bottom: 2rem;">
        <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--gold-primary); letter-spacing: 0.2em; font-weight: 700;">Executive Portal</span>
        <h1 style="color: #FFFFFF; font-size: 2rem; margin: 0.35rem 0;">SHIVKRUPA</h1>
        <p style="color: #A39B91; font-size: 0.9rem;">Back-Office Catalogue &amp; Orders Management</p>
        <div class="gold-divider"></div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error" style="background: rgba(185, 28, 28, 0.2); color: #FCA5A5; border-color: rgba(185, 28, 28, 0.4);">
            <span class="alert-icon">✕</span>
            <div class="alert-content"><?= e($error); ?></div>
        </div>
    <?php endif; ?>

    <!-- Admin Demo Quick Fill -->
    <div style="background: rgba(197, 160, 89, 0.1); border: 1px dashed var(--gold-primary); padding: 0.85rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; font-size: 0.82rem; color: #D4AF6A;">
        <strong>👑 Store Administrator Account:</strong><br>
        Username: <code>admin</code> | Password: <code>Admin@123</code>
        <div style="margin-top: 0.5rem;">
            <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('username').value='admin';document.getElementById('password').value='Admin@123';" style="padding: 0.2rem 0.6rem; font-size: 0.75rem; border-color: var(--gold-primary); color: #FFF;">
                Autofill Admin
            </button>
        </div>
    </div>

    <form action="<?= ADMIN_URL; ?>/login.php" method="POST">
        <?= csrfField(); ?>

        <div class="form-group">
            <label class="form-label" for="username" style="color: #E2DDD5;">Admin Username or Email</label>
            <input type="text" id="username" name="username" class="form-control" style="background: #181512; border-color: #3D3730; color: #FFF;" value="<?= e($username); ?>" required autofocus>
        </div>

        <div class="form-group">
            <label class="form-label" for="password" style="color: #E2DDD5;">Secret Password</label>
            <input type="password" id="password" name="password" class="form-control" style="background: #181512; border-color: #3D3730; color: #FFF;" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 1.75rem;">
            Authenticate Access &rarr;
        </button>
    </form>

    <div style="text-align: center; margin-top: 2rem; font-size: 0.85rem; color: #78716A;">
        <a href="<?= BASE_URL; ?>/index.php" style="color: var(--gold-light);">&larr; Return to Customer Storefront</a>
    </div>
</div>

</body>
</html>
