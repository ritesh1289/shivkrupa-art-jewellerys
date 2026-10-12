<?php
/**
 * SHIVKRUPA ART JEWELLERY — Admin Order Detailed Inspection & Dispatch Control
 */

$adminPageTitle = "Order Inspection | Admin Portal";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../app/middleware/csrf.php';

$db = getDB();
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId <= 0) {
    setFlash('error', 'Invalid order specified.');
    redirect(ADMIN_URL . '/orders.php');
}

// Handle Order Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Security token invalid.');
    } else {
        // Pre-fetch current order state
        $chkOrder = $db->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
        $chkOrder->execute([$orderId]);
        $currOrder = $chkOrder->fetch();

        if (!$currOrder) {
            setFlash('error', 'Order not found.');
            redirect(ADMIN_URL . '/orders.php');
        }

        $action = $_POST['action'] ?? '';

        if ($action === 'update_status') {
            $newStatus = trim($_POST['order_status'] ?? '');
            $allowed = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
            if (in_array($newStatus, $allowed)) {
                try {
                    $db->beginTransaction();

                    // Check if cancelling an order whose stock was previously deducted
                    $wasDeducted = ($currOrder['payment_status'] === 'paid' && in_array($currOrder['order_status'], ['confirmed', 'processing', 'shipped']));
                    $isNowCancelling = ($newStatus === 'cancelled' && $currOrder['order_status'] !== 'cancelled');

                    if ($wasDeducted && $isNowCancelling) {
                        // Restock items safely
                        $itemsStmt = $db->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                        $itemsStmt->execute([$orderId]);
                        $cancelledItems = $itemsStmt->fetchAll();

                        $restockStmt = $db->prepare("
                            UPDATE products 
                            SET stock_quantity = stock_quantity + ?,
                                status = CASE WHEN status = 'out_of_stock' THEN 'active' ELSE status END,
                                updated_at = NOW()
                            WHERE id = ?
                        ");

                        foreach ($cancelledItems as $it) {
                            $qty = (int)$it['quantity'];
                            $restockStmt->execute([$qty, $it['product_id']]);
                        }
                    }

                    $upd = $db->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?");
                    $upd->execute([$newStatus, $orderId]);

                    $db->commit();
                    $msg = "Order status changed to '" . ucfirst($newStatus) . "'.";
                    if ($wasDeducted && $isNowCancelling) {
                        $msg .= " Reserved inventory has been safely restored to stock.";
                    }
                    setFlash('success', $msg);
                } catch (Exception $e) {
                    $db->rollBack();
                    error_log("Update order status error: " . $e->getMessage());
                    setFlash('error', 'Failed to update order status: ' . $e->getMessage());
                }
            }
        } elseif ($action === 'manual_payment_confirm') {
            // Controlled override mechanism: only allowed if accompanied by bank payment reference
            $txnNote = trim($_POST['txn_note'] ?? '');
            if (empty($txnNote)) {
                setFlash('error', 'A bank transaction reference / verification note is strictly required to manually confirm payment.');
            } elseif ($currOrder['payment_status'] === 'paid') {
                setFlash('warning', 'Order is already marked as paid. Inventory stock has already been deducted.');
            } else {
                try {
                    $db->beginTransaction();

                    $upd = $db->prepare("
                        UPDATE orders 
                        SET payment_status = 'paid', order_status = 'confirmed', 
                            razorpay_payment_id = ?, updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $upd->execute(['MANUAL_OVERRIDE_' . $txnNote, $orderId]);

                    // Also log to payments table
                    $insPay = $db->prepare("
                        INSERT INTO payments (order_id, payment_method, razorpay_order_id, razorpay_payment_id, razorpay_signature, amount, status, created_at)
                        SELECT id, 'manual_admin_verified', COALESCE(razorpay_order_id, 'manual'), ?, 'admin_verified_signature', total_amount, 'paid', NOW()
                        FROM orders WHERE id = ?
                    ");
                    $insPay->execute(['MANUAL_OVERRIDE_' . $txnNote, $orderId]);

                    // Decrement product stock atomically
                    $itemsStmt = $db->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                    $itemsStmt->execute([$orderId]);
                    $orderedItems = $itemsStmt->fetchAll();

                    $stockStmt = $db->prepare("
                        UPDATE products 
                        SET stock_quantity = GREATEST(0, stock_quantity - ?),
                            status = CASE WHEN stock_quantity - ? <= 0 THEN 'out_of_stock' ELSE status END,
                            updated_at = NOW()
                        WHERE id = ?
                    ");

                    foreach ($orderedItems as $it) {
                        $qty = (int)$it['quantity'];
                        $stockStmt->execute([$qty, $qty, $it['product_id']]);
                    }

                    $db->commit();
                    setFlash('success', "Controlled override applied: Order #{$orderId} marked as paid and inventory stock decremented.");
                } catch (Exception $e) {
                    $db->rollBack();
                    error_log("Manual payment confirm error: " . $e->getMessage());
                    setFlash('error', 'Failed to update order payment: ' . $e->getMessage());
                }
            }
        }
    }
    redirect(ADMIN_URL . '/order-details.php?id=' . $orderId);
}

// Fetch Order
$stmt = $db->prepare("
    SELECT o.*, u.full_name as customer_account_name, u.email as customer_account_email
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
    LIMIT 1
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Order record not found.');
    redirect(ADMIN_URL . '/orders.php');
}

// Line Items
$itemsStmt = $db->prepare("
    SELECT oi.*, p.image, p.sku, p.material
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$itemsStmt->execute([$orderId]);
$orderItems = $itemsStmt->fetchAll();

// Payment Transactions Log
$payStmt = $db->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC");
$payStmt->execute([$orderId]);
$payments = $payStmt->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <a href="<?= ADMIN_URL; ?>/orders.php" style="color: var(--gold-dark); font-size: 0.88rem;">&larr; Back to All Orders</a>
        <h1 style="font-size: 2.2rem; margin-top: 0.35rem;">Order <?= e($order['order_number']); ?></h1>
        <span style="font-size: 0.85rem; color: var(--admin-muted);">
            Created: <?= date('d M Y, h:i A', strtotime($order['created_at'])); ?> &bull; Last Updated: <?= date('d M Y, h:i A', strtotime($order['updated_at'])); ?>
        </span>
    </div>

    <div>
        <button onclick="window.print()" class="btn btn-outline btn-sm">
            🖨️ Print Dispatch Slip
        </button>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: start;">
    
    <!-- Items & Payment Audit -->
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        
        <!-- Ordered Items Table -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3>Order Line Items</h3>
            </div>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Jewellery Piece</th>
                        <th>Unit Price</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $it): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <?php if (!empty($it['image'])): ?>
                                        <img src="<?= UPLOAD_URL . e($it['image']); ?>" alt="" class="admin-table-thumb">
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= e($it['product_name']); ?></strong>
                                        <div style="font-size: 0.78rem; color: var(--admin-muted);">
                                            SKU: <?= e($it['sku']); ?> &bull; <?= e($it['material'] ?? ''); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td><?= formatPrice($it['price']); ?></td>
                            <td style="text-align: center;"><?= (int)$it['quantity']; ?></td>
                            <td style="text-align: right; font-weight: 700;"><?= formatPrice($it['subtotal']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right; font-weight: 600;">Total Order Amount:</td>
                        <td style="text-align: right; font-weight: 700; font-size: 1.25rem; color: var(--gold-dark);">
                            <?= formatPrice($order['total_amount']); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Payment Audit Log -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3>Payment Transactions &amp; Gateway Audit</h3>
            </div>

            <?php if (empty($payments)): ?>
                <div style="padding: 2rem; text-align: center; color: var(--admin-muted);">
                    No payment gateway transaction captured for this order yet.
                </div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Gateway Method</th>
                            <th>Payment ID</th>
                            <th>Order ID</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $py): ?>
                            <tr>
                                <td><?= strtoupper(e($py['payment_method'])); ?></td>
                                <td><code><?= e($py['razorpay_payment_id']); ?></code></td>
                                <td><code><?= e($py['razorpay_order_id']); ?></code></td>
                                <td><strong><?= formatPrice($py['amount']); ?></strong></td>
                                <td>
                                    <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; padding: 0.2rem 0.5rem; border-radius: var(--radius-sm); <?= $py['status'] === 'paid' ? 'background:var(--success-bg); color:var(--success);' : 'background:var(--warning-bg); color:var(--warning);'; ?>">
                                        <?= e($py['status']); ?>
                                    </span>
                                </td>
                                <td style="font-size: 0.82rem; color: var(--admin-muted);">
                                    <?= date('d M Y, h:i A', strtotime($py['created_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar Management Tools -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- Status Management Card -->
        <div class="admin-card" style="padding: 1.5rem;">
            <h4 style="margin-bottom: 1rem; color: var(--gold-dark); border-bottom: 1px solid var(--admin-border); padding-bottom: 0.5rem;">
                🚚 Fulfillment Control
            </h4>

            <form action="<?= ADMIN_URL; ?>/order-details.php?id=<?= $order['id']; ?>" method="POST">
                <?= csrfField(); ?>
                <input type="hidden" name="action" value="update_status">

                <div class="form-group">
                    <label class="form-label" for="order_status">Order Dispatch Stage</label>
                    <select id="order_status" name="order_status" class="form-control" style="font-size: 0.95rem;">
                        <option value="pending" <?= $order['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending Inspection</option>
                        <option value="confirmed" <?= $order['order_status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed (Payment Verified)</option>
                        <option value="processing" <?= $order['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing in Vault</option>
                        <option value="shipped" <?= $order['order_status'] === 'shipped' ? 'selected' : ''; ?>>Dispatched via Armed Transit</option>
                        <option value="delivered" <?= $order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered to Client</option>
                        <option value="cancelled" <?= $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    Update Dispatch Status
                </button>
            </form>

            <?php if ($order['payment_status'] !== 'paid'): ?>
                <!-- Controlled mechanism for manual payment verification -->
                <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px dashed var(--admin-border);">
                    <span style="font-size: 0.78rem; text-transform: uppercase; color: #D97706; font-weight: 700;">Admin Controlled Action</span>
                    <form action="<?= ADMIN_URL; ?>/order-details.php?id=<?= $order['id']; ?>" method="POST" onsubmit="return confirm('Ensure bank credit has been verified in store account before proceeding.');" style="margin-top: 0.5rem;">
                        <?= csrfField(); ?>
                        <input type="hidden" name="action" value="manual_payment_confirm">
                        
                        <div class="form-group">
                            <label class="form-label" style="font-size: 0.8rem;">Bank UTR / Cash Voucher Ref <span style="color:var(--danger);">*</span></label>
                            <input type="text" name="txn_note" class="form-control" placeholder="e.g. HDFC-NEFT-991208" required style="font-size: 0.85rem;">
                        </div>

                        <button type="submit" class="btn btn-sm btn-block" style="background: #FEF3C7; color: #92400E; border: 1px solid #F59E0B; font-weight: 600;">
                            ✓ Verify &amp; Mark as Paid
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- Customer & Delivery Destination Card -->
        <div class="admin-card" style="padding: 1.5rem;">
            <h4 style="margin-bottom: 1rem; color: var(--gold-dark); border-bottom: 1px solid var(--admin-border); padding-bottom: 0.5rem;">
                👤 Customer Information
            </h4>

            <div style="font-size: 0.9rem; line-height: 1.7; color: var(--admin-text);">
                <div><strong>Account:</strong> <?= e($order['customer_account_name'] ?? 'N/A'); ?></div>
                <div><strong>Email:</strong> <?= e($order['customer_account_email'] ?? 'N/A'); ?></div>
                <div style="margin-top: 0.75rem; border-top: 1px dashed var(--admin-border); padding-top: 0.75rem;">
                    <strong>Recipient:</strong> <?= e($order['shipping_name']); ?><br>
                    <strong>Phone:</strong> <?= e($order['phone']); ?><br>
                    <strong>Street:</strong> <?= nl2br(e($order['address'])); ?><br>
                    <strong>City:</strong> <?= e($order['city']); ?>, <?= e($order['state']); ?> &minus; <?= e($order['pincode']); ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
