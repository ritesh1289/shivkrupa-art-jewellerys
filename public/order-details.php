<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Customer Order Details & Printable Tax Invoice
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/middleware/auth.php';

requireLogin(BASE_URL . '/orders.php');

$currentUser = getCurrentUser();
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId <= 0) {
    setFlash('error', 'Invalid order reference.');
    redirect(BASE_URL . '/orders.php');
}

$db = getDB();

// 1. Query order with strict user_id check (IDOR Protection)
$orderStmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1");
$orderStmt->execute([$orderId, $currentUser['id']]);
$order = $orderStmt->fetch();

if (!$order) {
    setFlash('error', 'Order not found or you do not have permission to view it.');
    redirect(BASE_URL . '/orders.php');
}

// 2. Query order line items
$itemsStmt = $db->prepare("
    SELECT oi.*, p.image, p.sku, p.material, c.name as category_name
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE oi.order_id = ?
");
$itemsStmt->execute([$orderId]);
$orderItems = $itemsStmt->fetchAll();

// 3. Query payment details if available
$payStmt = $db->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1");
$payStmt->execute([$orderId]);
$payment = $payStmt->fetch();

$pageTitle = "Order #" . e($order['order_number']) . " | " . APP_NAME;
require_once __DIR__ . '/../app/components/header.php';
?>

<div class="container section">
    <!-- Action Bar / Back & Print -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <a href="<?= BASE_URL; ?>/orders.php" style="color: var(--gold-dark); font-weight: 500; font-size: 0.9rem;">
                &larr; Back to Order History
            </a>
            <h1 style="font-size: 2.2rem; margin-top: 0.35rem;">Order <?= e($order['order_number']); ?></h1>
            <span style="font-size: 0.88rem; color: var(--text-muted);">
                Placed on <?= date('F d, Y \a\t h:i A', strtotime($order['created_at'])); ?>
            </span>
        </div>

        <div>
            <button onclick="window.print()" class="btn btn-outline btn-sm">
                🖨️ Print Tax Invoice
            </button>
        </div>
    </div>

    <!-- Status Tracking Timeline -->
    <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 2rem; margin-bottom: 2.5rem; box-shadow: var(--shadow-sm);">
        <h4 style="margin-bottom: 1.5rem; color: var(--text-main);">Order Fulfillment Progress</h4>
        
        <?php
            $statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
            $currentStatus = $order['order_status'];
            $isCancelled = ($currentStatus === 'cancelled');
            $statusIndex = array_search($currentStatus, $statuses);
            if ($statusIndex === false) $statusIndex = 0;
        ?>

        <?php if ($isCancelled): ?>
            <div style="padding: 1rem; background: var(--danger-bg); color: var(--danger); border-radius: var(--radius-sm); font-weight: 600;">
                ⚠️ This order was cancelled. Any processed payment will be refunded within 5-7 business days.
            </div>
        <?php else: ?>
            <div style="display: flex; justify-content: space-between; position: relative; gap: 1rem; flex-wrap: wrap;">
                <?php foreach ($statuses as $idx => $st): ?>
                    <?php 
                        $isCompleted = ($idx <= $statusIndex);
                        $isCurrent = ($idx === $statusIndex);
                    ?>
                    <div style="text-align: center; flex: 1; min-width: 90px;">
                        <div style="width: 38px; height: 38px; border-radius: var(--radius-full); margin: 0 auto 0.5rem auto; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; <?= $isCompleted ? 'background:var(--gold-gradient); color:#FFF; box-shadow:var(--shadow-gold);' : 'background:var(--bg-subtle); color:var(--text-light); border:1px solid var(--border-color);'; ?>">
                            <?= $isCompleted ? '✓' : ($idx + 1); ?>
                        </div>
                        <span style="font-size: 0.82rem; font-weight: <?= $isCurrent ? '700' : '500'; ?>; color: <?= $isCurrent ? 'var(--gold-dark)' : ($isCompleted ? 'var(--text-main)' : 'var(--text-light)'); ?>; text-transform: uppercase; letter-spacing: 0.05em;">
                            <?= ucfirst($st); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Order Breakdown & Addresses Grid -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2.5rem; align-items: start;">
        
        <!-- Itemized Products Table -->
        <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden; box-shadow: var(--shadow-sm);">
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color); background: var(--bg-subtle);">
                <h3 style="font-size: 1.2rem; margin: 0;">Ordered Jewellery Items</h3>
            </div>

            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Jewellery Item</th>
                        <th>Price</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $it): ?>
                        <tr>
                            <td>
                                <div class="cart-product-cell">
                                    <?php if (!empty($it['image'])): ?>
                                        <img src="<?= UPLOAD_URL . e($it['image']); ?>" alt="<?= e($it['product_name']); ?>" class="cart-thumb">
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= e($it['product_name']); ?></strong>
                                        <?php if (!empty($it['sku'])): ?>
                                            <div style="font-size: 0.8rem; color: var(--text-light);">SKU: <?= e($it['sku']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?= formatPrice($it['price']); ?></td>
                            <td style="text-align: center;"><?= (int)$it['quantity']; ?></td>
                            <td style="text-align: right; font-weight: 600;"><?= formatPrice($it['subtotal']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right; font-weight: 600;">Grand Total Amount:</td>
                        <td style="text-align: right; font-weight: 700; font-size: 1.25rem; color: var(--gold-dark);">
                            <?= formatPrice($order['total_amount']); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Shipping & Payment Information Sidebar -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Shipping Card -->
            <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.75rem; box-shadow: var(--shadow-sm);">
                <h4 style="margin-bottom: 1rem; color: var(--gold-dark); border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                    📦 Delivery Destination
                </h4>
                <div style="font-size: 0.92rem; line-height: 1.7; color: var(--text-main);">
                    <strong><?= e($order['shipping_name']); ?></strong><br>
                    <?= nl2br(e($order['address'])); ?><br>
                    <?= e($order['city']); ?>, <?= e($order['state']); ?> &minus; <?= e($order['pincode']); ?><br>
                    📞 Phone: <?= e($order['phone']); ?>
                </div>
            </div>

            <!-- Payment Summary Card -->
            <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.75rem; box-shadow: var(--shadow-sm);">
                <h4 style="margin-bottom: 1rem; color: var(--gold-dark); border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                    💳 Payment Details
                </h4>
                
                <div style="font-size: 0.88rem; line-height: 1.8; color: var(--text-main);">
                    <div><strong>Payment Method:</strong> Razorpay Gateway</div>
                    <div><strong>Payment Status:</strong> 
                        <span style="font-weight: 700; color: <?= $order['payment_status'] === 'paid' ? 'var(--success)' : 'var(--warning)'; ?>;">
                            <?= strtoupper($order['payment_status']); ?>
                        </span>
                    </div>
                    <?php if (!empty($order['razorpay_payment_id'])): ?>
                        <div><strong>Razorpay Payment ID:</strong> <code><?= e($order['razorpay_payment_id']); ?></code></div>
                    <?php endif; ?>
                    <?php if (!empty($order['razorpay_order_id'])): ?>
                        <div><strong>Razorpay Order ID:</strong> <code><?= e($order['razorpay_order_id']); ?></code></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../app/components/footer.php'; ?>
