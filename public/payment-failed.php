<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Payment Failure Page
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/middleware/auth.php';

requireLogin();

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$pageTitle = "Payment Unsuccessful | " . APP_NAME;
require_once __DIR__ . '/../app/components/header.php';
?>

<div class="container section" style="max-width: 600px;">
    <div style="background: var(--bg-surface); border: 1px solid var(--danger-bg); border-radius: var(--radius-lg); padding: 3.5rem 2rem; text-align: center; box-shadow: var(--shadow-md);">
        
        <div style="width: 75px; height: 75px; background: var(--danger-bg); border-radius: var(--radius-full); display: inline-flex; align-items: center; justify-content: center; font-size: 2.2rem; color: var(--danger); margin-bottom: 1.5rem;">
            ✕
        </div>

        <span class="section-subtitle" style="color: var(--danger);">Transaction Interrupted</span>
        <h1 style="font-size: 2.2rem; margin-bottom: 0.75rem;">Payment Could Not Be Verified</h1>

        <p style="color: var(--text-muted); font-size: 1rem; margin-bottom: 2rem; line-height: 1.6;">
            Your transaction was declined, cancelled, or could not be verified by the banking network. If any money was deducted from your account, Razorpay will automatically reverse it within 48-72 hours.
        </p>

        <div style="background: var(--bg-subtle); padding: 1.25rem; border-radius: var(--radius-sm); margin-bottom: 2rem; font-size: 0.88rem; text-align: left;">
            <strong>Common reasons for failure:</strong>
            <ul style="list-style: disc; padding-left: 1.25rem; margin-top: 0.4rem; color: var(--text-muted);">
                <li>Insufficient balance or daily transaction limit exceeded</li>
                <li>Bank OTP timeout or incorrect verification code</li>
                <li>Payment window closed prematurely</li>
            </ul>
        </div>

        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="<?= BASE_URL; ?>/checkout.php" class="btn btn-primary btn-lg">
                🔄 Retry Checkout Payment
            </a>
            <a href="<?= BASE_URL; ?>/cart.php" class="btn btn-outline btn-lg">
                Return to Shopping Bag
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../app/components/footer.php'; ?>
