<?php
/**
 * SHIVKRUPA ART JEWELLERY — Admin Order Management
 */

$adminPageTitle = "Customer Orders Management | Admin Portal";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../app/middleware/csrf.php';

$db = getDB();

// Handle Order Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Security token invalid.');
    } else {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $newStatus = trim($_POST['order_status'] ?? '');
        $allowedStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];

        if ($orderId > 0 && in_array($newStatus, $allowedStatuses)) {
            $stmt = $db->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $orderId]);
            setFlash('success', "Order #{$orderId} status updated to '" . ucfirst($newStatus) . "'.");
        }
    }
    redirect(ADMIN_URL . '/orders.php');
}

// Filters
$statusFilter = trim($_GET['status'] ?? '');
$paymentFilter = trim($_GET['payment'] ?? '');
$searchFilter = trim($_GET['search'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($statusFilter)) {
    $where[] = "o.order_status = ?";
    $params[] = $statusFilter;
}

if (!empty($paymentFilter)) {
    $where[] = "o.payment_status = ?";
    $params[] = $paymentFilter;
}

if (!empty($searchFilter)) {
    $where[] = "(o.order_number LIKE ? OR o.shipping_name LIKE ? OR o.phone LIKE ?)";
    $params[] = "%{$searchFilter}%";
    $params[] = "%{$searchFilter}%";
    $params[] = "%{$searchFilter}%";
}

$whereSql = implode(" AND ", $where);

$stmt = $db->prepare("
    SELECT o.*, u.full_name as customer_name, u.email as customer_email, COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE {$whereSql}
    GROUP BY o.id
    ORDER BY o.id DESC
");
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 2.2rem; margin-bottom: 0.25rem;">Orders Fulfillment &amp; Dispatch</h1>
    <p style="color: var(--admin-muted);">Track customer orders, verify verified Razorpay payments, and advance dispatch status.</p>
</div>

<!-- Filters Bar -->
<div class="admin-card" style="padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
    <form action="<?= ADMIN_URL; ?>/orders.php" method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 220px;">
            <input type="text" name="search" class="form-control" placeholder="Search order #, customer name, phone..." value="<?= e($searchFilter); ?>">
        </div>

        <div style="min-width: 170px;">
            <select name="status" class="form-control" onchange="this.form.submit()">
                <option value="">All Order Statuses</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                <option value="shipped" <?= $statusFilter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>

        <div style="min-width: 170px;">
            <select name="payment" class="form-control" onchange="this.form.submit()">
                <option value="">All Payment Statuses</option>
                <option value="paid" <?= $paymentFilter === 'paid' ? 'selected' : ''; ?>>Paid (Razorpay)</option>
                <option value="pending" <?= $paymentFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="failed" <?= $paymentFilter === 'failed' ? 'selected' : ''; ?>>Failed</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <?php if (!empty($statusFilter) || !empty($paymentFilter) || !empty($searchFilter)): ?>
            <a href="<?= ADMIN_URL; ?>/orders.php" class="btn btn-outline btn-sm">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Orders Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3>Orders (<?= count($orders); ?>)</h3>
    </div>

    <?php if (empty($orders)): ?>
        <div style="padding: 3rem; text-align: center; color: var(--admin-muted);">
            No orders match the selected filters.
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Pieces</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Dispatch Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td>
                            <strong><?= e($o['order_number']); ?></strong>
                            <div style="font-size: 0.75rem; color: var(--admin-muted);">
                                <?= date('d M Y, h:i A', strtotime($o['created_at'])); ?>
                            </div>
                        </td>

                        <td>
                            <strong><?= e($o['shipping_name']); ?></strong>
                            <div style="font-size: 0.78rem; color: var(--admin-muted);"><?= e($o['city']); ?>, <?= e($o['state']); ?> &bull; 📞 <?= e($o['phone']); ?></div>
                        </td>

                        <td>
                            <?= (int)$o['item_count']; ?> item(s)
                        </td>

                        <td>
                            <strong><?= formatPrice($o['total_amount']); ?></strong>
                        </td>

                        <td>
                            <span style="display:inline-block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; padding: 0.2rem 0.55rem; border-radius: var(--radius-sm); <?= $o['payment_status'] === 'paid' ? 'background:var(--success-bg); color:var(--success);' : 'background:var(--warning-bg); color:var(--warning);'; ?>">
                                <?= e($o['payment_status']); ?>
                            </span>
                            <?php if (!empty($o['razorpay_payment_id'])): ?>
                                <div style="font-size: 0.7rem; font-family: monospace; color: var(--admin-muted);">
                                    <?= e(substr($o['razorpay_payment_id'], 0, 16)); ?>...
                                </div>
                            <?php endif; ?>
                        </td>

                        <td>
                            <form action="<?= ADMIN_URL; ?>/orders.php" method="POST" style="display:inline-flex; gap: 0.4rem; align-items: center;">
                                <?= csrfField(); ?>
                                <input type="hidden" name="order_id" value="<?= $o['id']; ?>">
                                <select name="order_status" onchange="this.form.submit()" style="font-size: 0.8rem; padding: 0.3rem 0.5rem; border-radius: var(--radius-sm); border: 1px solid var(--admin-border);">
                                    <option value="pending" <?= $o['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?= $o['order_status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="processing" <?= $o['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="shipped" <?= $o['order_status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="delivered" <?= $o['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="cancelled" <?= $o['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </form>
                        </td>

                        <td style="text-align: right;">
                            <a href="<?= ADMIN_URL; ?>/order-details.php?id=<?= $o['id']; ?>" class="btn btn-outline btn-sm">
                                View Invoice &rarr;
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
