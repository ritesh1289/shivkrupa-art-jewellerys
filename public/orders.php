<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Customer Order History Page
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/middleware/auth.php';

requireLogin(BASE_URL . '/orders.php');

$currentUser = getCurrentUser();
$db = getDB();

// Fetch orders with item count
$stmt = $db->prepare("
    SELECT o.*, COUNT(oi.id) as total_items 
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.user_id = ? 
    GROUP BY o.id 
    ORDER BY o.id DESC
");
$stmt->execute([$currentUser['id']]);
$orders = $stmt->fetchAll();

$pageTitle = "My Orders | " . APP_NAME;
require_once __DIR__ . '/../app/components/header.php';
?>

<div class="container section">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <span class="section-subtitle">Account History</span>
            <h1 style="font-size: 2.4rem; margin-bottom: 0.35rem;">My Jewellery Orders</h1>
            <p style="color: var(--text-muted);">Track your past orders, delivery dispatches, and download official invoices.</p>
        </div>
        <div>
            <a href="<?= BASE_URL; ?>/profile.php" class="btn btn-outline btn-sm">
                👤 My Profile
            </a>
            <a href="<?= BASE_URL; ?>/products.php" class="btn btn-primary btn-sm">
                Shop New Arrivals &rarr;
            </a>
        </div>
    </div>

    <?php if (empty($orders)): ?>
        <div style="background: var(--bg-surface); border: 1px dashed var(--border-color); border-radius: var(--radius-md); padding: 5rem 2rem; text-align: center; max-width: 600px; margin: 0 auto;">
            <div style="font-size: 3.5rem; margin-bottom: 1.25rem;">📦</div>
            <h3 style="font-size: 1.6rem; margin-bottom: 0.5rem;">No Orders Placed Yet</h3>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">
                You haven't ordered any fine jewellery pieces with us yet. Begin your journey with our iconic bridal and solitaire collections.
            </p>
            <a href="<?= BASE_URL; ?>/products.php" class="btn btn-primary">
                Browse Jewellery Catalogue &rarr;
            </a>
        </div>
    <?php else: ?>
        <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden; box-shadow: var(--shadow-sm);">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Order Number</th>
                        <th>Date</th>
                        <th>Pieces</th>
                        <th>Total Amount</th>
                        <th>Payment Status</th>
                        <th>Order Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $ord): ?>
                        <tr>
                            <td>
                                <strong><?= e($ord['order_number']); ?></strong>
                                <?php if (!empty($ord['razorpay_payment_id'])): ?>
                                    <div style="font-size: 0.75rem; color: var(--text-light);">
                                        Ref: <?= e($ord['razorpay_payment_id']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td style="font-size: 0.9rem; color: var(--text-muted);">
                                <?= date('d M Y, h:i A', strtotime($ord['created_at'])); ?>
                            </td>

                            <td style="font-size: 0.9rem;">
                                <?= (int)$ord['total_items']; ?> item(s)
                            </td>

                            <td>
                                <strong><?= formatPrice($ord['total_amount']); ?></strong>
                            </td>

                            <td>
                                <?php 
                                    $pStatus = $ord['payment_status'];
                                    $pBadgeStyle = match($pStatus) {
                                        'paid'     => 'background:var(--success-bg); color:var(--success); border:1px solid rgba(21,128,61,0.3);',
                                        'failed'   => 'background:var(--danger-bg); color:var(--danger); border:1px solid rgba(185,28,28,0.3);',
                                        'refunded' => 'background:#E0F2FE; color:#0369A1; border:1px solid rgba(3,105,161,0.3);',
                                        default    => 'background:var(--warning-bg); color:var(--warning); border:1px solid rgba(180,83,9,0.3);'
                                    };
                                ?>
                                <span style="display:inline-block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; padding: 0.2rem 0.6rem; border-radius: var(--radius-sm); <?= $pBadgeStyle; ?>">
                                    <?= ucfirst($pStatus); ?>
                                </span>
                            </td>

                            <td>
                                <?php
                                    $oStatus = $ord['order_status'];
                                    $oBadgeStyle = match($oStatus) {
                                        'delivered'  => 'background:var(--success-bg); color:var(--success);',
                                        'shipped'    => 'background:#EDE9FE; color:#6D28D9;',
                                        'processing' => 'background:#FEF3C7; color:#B45309;',
                                        'confirmed'  => 'background:var(--success-bg); color:var(--success);',
                                        'cancelled'  => 'background:var(--danger-bg); color:var(--danger);',
                                        default      => 'background:var(--bg-subtle); color:var(--text-muted);'
                                    };
                                ?>
                                <span style="display:inline-block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; padding: 0.2rem 0.6rem; border-radius: var(--radius-sm); <?= $oBadgeStyle; ?>">
                                    <?= ucfirst($oStatus); ?>
                                </span>
                            </td>

                            <td style="text-align: right;">
                                <a href="<?= BASE_URL; ?>/order-details.php?id=<?= $ord['id']; ?>" class="btn btn-outline btn-sm">
                                    Invoice &amp; Details &rarr;
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../app/components/footer.php'; ?>
