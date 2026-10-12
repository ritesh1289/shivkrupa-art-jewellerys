<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Payment Success & Order Confirmation Page
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/middleware/auth.php';

requireLogin();

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$currentUser = getCurrentUser();

if ($orderId <= 0) {
    redirect(BASE_URL . '/orders.php');
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$orderId, $currentUser['id']]);
$order = $stmt->fetch();

if (!$order) {
    redirect(BASE_URL . '/orders.php');
}

$pageTitle = "Order Confirmed | " . APP_NAME;
require_once __DIR__ . '/../app/components/header.php';
?>

<div class="container section" style="max-width: 680px;">
    <div style="background: var(--bg-surface); border: 1px solid var(--border-gold); border-radius: var(--radius-lg); padding: 3.5rem 2.5rem; text-align: center; box-shadow: var(--shadow-lg);">
        
        <!-- Animated Success Badge -->
        <div style="width: 80px; height: 80px; background: var(--success-bg); border: 2px solid var(--success); border-radius: var(--radius-full); display: inline-flex; align-items: center; justify-content: center; font-size: 2.5rem; color: var(--success); margin-bottom: 1.5rem;">
            ✓
        </div>

        <span class="section-subtitle" style="color: var(--success);">Payment Verified &bull; Insured Dispatch Pending</span>
        <h1 style="font-size: 2.4rem; margin-bottom: 0.75rem;">Thank You for Your Order</h1>
        
        <p style="color: var(--text-muted); font-size: 1.05rem; margin-bottom: 2rem;">
            We have received your payment of <strong style="color: var(--text-main);"><?= formatPrice($order['total_amount']); ?></strong>. Your fine jewellery piece has been reserved and is now entering our vault for quality inspection and hallmarking verification.
        </p>

        <!-- Order Summary Receipt Box -->
        <div style="background: var(--bg-subtle); border-radius: var(--radius-md); padding: 1.5rem; margin-bottom: 2.5rem; text-align: left; font-size: 0.92rem; border: 1px solid var(--border-color);">
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.65rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.65rem;">
                <span style="color: var(--text-muted);">Order Reference Number:</span>
                <strong><?= e($order['order_number']); ?></strong>
            </div>

            <div style="display: flex; justify-content: space-between; margin-bottom: 0.65rem;">
                <span style="color: var(--text-muted);">Razorpay Transaction ID:</span>
                <code style="font-size: 0.85rem; color: var(--gold-dark);"><?= e($order['razorpay_payment_id'] ?? 'N/A'); ?></code>
            </div>

            <div style="display: flex; justify-content: space-between; margin-bottom: 0.65rem;">
                <span style="color: var(--text-muted);">Recipient:</span>
                <span><?= e($order['shipping_name']); ?></span>
            </div>

            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Delivery Address:</span>
                <span style="text-align: right; max-width: 320px;"><?= e($order['city']); ?>, <?= e($order['state']); ?> (<?= e($order['pincode']); ?>)</span>
            </div>
        </div>

        <!-- Action Links -->
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="<?= BASE_URL; ?>/order-details.php?id=<?= $order['id']; ?>" class="btn btn-primary btn-lg">
                View Tax Invoice &amp; Track Status &rarr;
            </a>
            <a href="<?= BASE_URL; ?>/products.php" class="btn btn-outline btn-lg">
                Continue Shopping
            </a>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/app/components/footer.php'; ?>
