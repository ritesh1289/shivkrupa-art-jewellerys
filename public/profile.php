<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Customer Profile & Address Book Management
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';
require_once dirname(__DIR__) . '/app/middleware/csrf.php';
require_once dirname(__DIR__) . '/app/middleware/auth.php';

requireLogin();

$user = getCurrentUser();
if (!$user) {
    logoutUser();
    redirect(BASE_URL . '/login.php');
}

$db = getDB();
$errors = [];

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $errors[] = 'Security validation failed. Please refresh and try again.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        $city     = trim($_POST['city'] ?? '');
        $state    = trim($_POST['state'] ?? '');
        $pincode  = trim($_POST['pincode'] ?? '');

        if (empty($fullName) || mb_strlen($fullName) < 3) {
            $errors[] = 'Full name must be at least 3 characters.';
        }

        if (empty($phone) || !preg_match('/^[0-9+\-\s]{10,15}$/', $phone)) {
            $errors[] = 'Please enter a valid mobile number.';
        }

        if (empty($errors)) {
            try {
                $stmt = $db->prepare("
                    UPDATE users 
                    SET full_name = ?, phone = ?, address = ?, city = ?, state = ?, pincode = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$fullName, $phone, $address, $city, $state, $pincode, $user['id']]);

                $_SESSION['user_name'] = $fullName;
                setFlash('success', 'Your profile and default shipping address have been updated.');
                redirect(BASE_URL . '/profile.php');
            } catch (PDOException $e) {
                error_log("Profile update error: " . $e->getMessage());
                $errors[] = 'Failed to update profile. Please try again.';
            }
        }
    }
}

// Fetch order statistics for this user
$orderCountStmt = $db->prepare("SELECT COUNT(*) as total_orders, COALESCE(SUM(total_amount), 0) as total_spent FROM orders WHERE user_id = ? AND payment_status = 'paid'");
$orderCountStmt->execute([$user['id']]);
$userStats = $orderCountStmt->fetch();

$pageTitle = "My Profile & Delivery Address | " . APP_NAME;
require_once dirname(__DIR__) . '/app/components/header.php';
?>

<div class="container section">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <span class="section-subtitle">Client Portal</span>
            <h1 style="font-size: 2.4rem; margin-bottom: 0.35rem;">My Account</h1>
            <p style="color: var(--text-muted);">Manage your personal details, default shipping address, and view order history.</p>
        </div>
        <div>
            <a href="<?= BASE_URL; ?>/orders.php" class="btn btn-outline">
                📦 View Order History
            </a>
            <a href="<?= BASE_URL; ?>/logout.php" class="btn btn-sm" style="color: var(--danger); border-color: var(--border-color); margin-left: 0.5rem;">
                Sign Out
            </a>
        </div>
    </div>

    <!-- Account Overview KPI Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
        <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.5rem; border-left: 4px solid var(--gold-primary);">
            <span style="font-size: 0.8rem; text-transform: uppercase; color: var(--gold-dark); font-weight: 600;">Client ID</span>
            <h3 style="font-size: 1.6rem; margin-top: 0.25rem;">#SKJ-<?= str_pad($user['id'], 4, '0', STR_PAD_LEFT); ?></h3>
            <span style="font-size: 0.82rem; color: var(--text-muted);">Member since <?= date('M Y', strtotime($user['created_at'])); ?></span>
        </div>

        <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.5rem; border-left: 4px solid var(--success);">
            <span style="font-size: 0.8rem; text-transform: uppercase; color: var(--success); font-weight: 600;">Completed Orders</span>
            <h3 style="font-size: 1.6rem; margin-top: 0.25rem;"><?= (int)$userStats['total_orders']; ?> Orders</h3>
            <span style="font-size: 0.82rem; color: var(--text-muted);"><a href="<?= BASE_URL; ?>/orders.php" style="color: var(--gold-dark); text-decoration: underline;">View Order History</a></span>
        </div>

        <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.5rem; border-left: 4px solid var(--gold-dark);">
            <span style="font-size: 0.8rem; text-transform: uppercase; color: var(--gold-dark); font-weight: 600;">Total Investment</span>
            <h3 style="font-size: 1.6rem; margin-top: 0.25rem;"><?= formatPrice($userStats['total_spent']); ?></h3>
            <span style="font-size: 0.82rem; color: var(--text-muted);">Certified Jewellery Purchases</span>
        </div>
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

    <div style="display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 2.5rem; align-items: start;">
        <!-- Edit Profile Form -->
        <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 2rem; box-shadow: var(--shadow-sm);">
            <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
                Personal & Delivery Details
            </h3>

            <form action="<?= BASE_URL; ?>/profile.php" method="POST">
                <?= csrfField(); ?>

                <div class="input-row">
                    <div class="form-group">
                        <label class="form-label" for="full_name">Full Name <span style="color:var(--danger);">*</span></label>
                        <input type="text" id="full_name" name="full_name" class="form-control" value="<?= e($user['full_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" id="email" class="form-control" value="<?= e($user['email']); ?>" disabled style="background: var(--bg-subtle); cursor: not-allowed;" title="Email cannot be changed directly for security.">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Mobile Phone Number <span style="color:var(--danger);">*</span></label>
                    <input type="tel" id="phone" name="phone" class="form-control" value="<?= e($user['phone']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="address">Default Shipping Street Address</label>
                    <textarea id="address" name="address" class="form-control" placeholder="House/Flat No., Apartment, Street, Landmark"><?= e($user['address'] ?? ''); ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label" for="city">City</label>
                        <input type="text" id="city" name="city" class="form-control" placeholder="e.g. Mumbai" value="<?= e($user['city'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="state">State</label>
                        <input type="text" id="state" name="state" class="form-control" placeholder="e.g. Maharashtra" value="<?= e($user['state'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="pincode">Pincode</label>
                        <input type="text" id="pincode" name="pincode" class="form-control" placeholder="e.g. 400001" value="<?= e($user['pincode'] ?? ''); ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem;">
                    Save Profile Changes
                </button>
            </form>
        </div>

        <!-- Security & Quick Summary Sidebar -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.75rem; box-shadow: var(--shadow-sm);">
                <h4 style="margin-bottom: 0.75rem; color: var(--gold-dark);">🔐 Security Information</h4>
                <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1rem;">
                    Your account is secured with salted password hashing and protected by CSRF validation on all requests.
                </p>
                <div style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.7;">
                    <div>&bull; Password Hashing: <code>BCrypt (Default)</code></div>
                    <div>&bull; Session Integrity: <code>Regenerated on Login</code></div>
                    <div>&bull; Role: <code>Verified Customer</code></div>
                </div>
            </div>

            <div style="background: linear-gradient(135deg, #FAF6EE 0%, #F5EEDD 100%); border: 1px solid var(--border-gold); border-radius: var(--radius-md); padding: 1.75rem;">
                <h4 style="margin-bottom: 0.5rem; color: var(--gold-dark);">✨ Insured Delivery Guarantee</h4>
                <p style="font-size: 0.88rem; color: var(--text-main); margin-bottom: 1rem;">
                    Your saved address is automatically used during checkout for tamper-evident transit courier dispatch.
                </p>
                <a href="<?= BASE_URL; ?>/products.php" class="btn btn-outline btn-sm">
                    Browse New Arrivals &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/app/components/footer.php'; ?>
