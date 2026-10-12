<?php
/**
 * SHIVKRUPA ART JEWELLERY — Admin Executive Dashboard
 * Real-time SQL metrics, sales analysis, low-stock warnings, and order activity
 */

$adminPageTitle = "Executive Dashboard | Admin Portal";
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// 1. Total Registered Users
$totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();

// 2. Total Products & Active Products
$prodStats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN stock_quantity <= 3 AND status = 'active' THEN 1 ELSE 0 END) as low_stock
    FROM products
")->fetch();

// 3. Order Statistics & Revenue
$orderStats = $db->query("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN order_status IN ('confirmed', 'processing', 'shipped', 'delivered') THEN 1 ELSE 0 END) as completed_orders,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) as total_revenue
    FROM orders
")->fetch();

// 4. Low Stock Products List (Stock <= 3)
$lowStockStmt = $db->query("
    SELECT p.id, p.name, p.sku, p.stock_quantity, p.price, p.image, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.stock_quantity <= 3 AND p.status = 'active' 
    ORDER BY p.stock_quantity ASC 
    LIMIT 5
");
$lowStockProducts = $lowStockStmt->fetchAll();

// 5. Recent 8 Orders
$recentOrdersStmt = $db->query("
    SELECT o.*, u.full_name as customer_name, u.email as customer_email 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    ORDER BY o.id DESC 
    LIMIT 8
");
$recentOrders = $recentOrdersStmt->fetchAll();
?>

<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 2.2rem; margin-bottom: 0.35rem;">Store Operations Dashboard</h1>
    <p style="color: var(--admin-muted);">Live overview of Shivkrupa jewellery sales, order processing, and vault inventory.</p>
</div>

<!-- Key Performance Metric Cards -->
<div class="metric-grid">
    <div class="metric-card">
        <div>
            <div class="metric-label">Total Revenue (Paid)</div>
            <div class="metric-value"><?= formatPrice($orderStats['total_revenue']); ?></div>
            <span style="font-size: 0.8rem; color: var(--success);">● Live Verified Payments</span>
        </div>
        <div class="metric-icon-box">💰</div>
    </div>

    <div class="metric-card" style="border-left-color: #3B82F6;">
        <div>
            <div class="metric-label">Total Orders Placed</div>
            <div class="metric-value"><?= (int)$orderStats['total_orders']; ?></div>
            <span style="font-size: 0.8rem; color: var(--admin-muted);"><?= (int)$orderStats['completed_orders']; ?> Confirmed/Delivered</span>
        </div>
        <div class="metric-icon-box" style="color: #3B82F6; background: #EFF6FF;">📦</div>
    </div>

    <div class="metric-card" style="border-left-color: #F59E0B;">
        <div>
            <div class="metric-label">Pending Orders</div>
            <div class="metric-value"><?= (int)$orderStats['pending_orders']; ?></div>
            <span style="font-size: 0.8rem; color: #D97706;">Awaiting Payment / Review</span>
        </div>
        <div class="metric-icon-box" style="color: #D97706; background: #FFFBEB;">⏳</div>
    </div>

    <div class="metric-card" style="border-left-color: #10B981;">
        <div>
            <div class="metric-label">Catalogue Products</div>
            <div class="metric-value"><?= (int)$prodStats['total']; ?></div>
            <span style="font-size: 0.8rem; color: var(--success);"><?= (int)$prodStats['active']; ?> Active Designs</span>
        </div>
        <div class="metric-icon-box" style="color: #10B981; background: #ECFDF5;">💎</div>
    </div>

    <div class="metric-card" style="border-left-color: #8B5CF6;">
        <div>
            <div class="metric-label">Registered Customers</div>
            <div class="metric-value"><?= $totalUsers; ?></div>
            <span style="font-size: 0.8rem; color: #8B5CF6;">Verified User Profiles</span>
        </div>
        <div class="metric-icon-box" style="color: #8B5CF6; background: #F5F3FF;">👥</div>
    </div>

    <div class="metric-card" style="border-left-color: #EF4444;">
        <div>
            <div class="metric-label">Low Stock Alerts</div>
            <div class="metric-value" style="color: <?= (int)$prodStats['low_stock'] > 0 ? 'var(--danger)' : 'inherit'; ?>;">
                <?= (int)$prodStats['low_stock']; ?>
            </div>
            <span style="font-size: 0.8rem; color: var(--danger);">≤ 3 Units Remaining</span>
        </div>
        <div class="metric-icon-box" style="color: #EF4444; background: #FEF2F2;">⚠️</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: start;">
    
    <!-- Recent Orders Table -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Recent Customer Orders</h3>
            <a href="<?= ADMIN_URL; ?>/orders.php" style="color: var(--gold-dark); font-size: 0.85rem; font-weight: 600;">
                View All Orders &rarr;
            </a>
        </div>

        <?php if (empty($recentOrders)): ?>
            <div style="padding: 3rem; text-align: center; color: var(--admin-muted);">
                No customer orders placed yet.
            </div>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $ro): ?>
                        <tr>
                            <td>
                                <strong><?= e($ro['order_number']); ?></strong>
                                <div style="font-size: 0.75rem; color: var(--admin-muted);">
                                    <?= date('d M, h:i A', strtotime($ro['created_at'])); ?>
                                </div>
                            </td>

                            <td>
                                <?= e($ro['shipping_name']); ?>
                                <div style="font-size: 0.75rem; color: var(--admin-muted);"><?= e($ro['phone']); ?></div>
                            </td>

                            <td>
                                <strong><?= formatPrice($ro['total_amount']); ?></strong>
                            </td>

                            <td>
                                <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; padding: 0.2rem 0.5rem; border-radius: var(--radius-sm); <?= $ro['payment_status'] === 'paid' ? 'background:var(--success-bg); color:var(--success);' : 'background:var(--warning-bg); color:var(--warning);'; ?>">
                                    <?= e($ro['payment_status']); ?>
                                </span>
                            </td>

                            <td>
                                <span style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--admin-muted);">
                                    <?= e($ro['order_status']); ?>
                                </span>
                            </td>

                            <td style="text-align: right;">
                                <a href="<?= ADMIN_URL; ?>/order-details.php?id=<?= $ro['id']; ?>" class="btn btn-outline btn-sm" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;">
                                    Inspect
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Low Stock Inventory Alerts -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Inventory Restock Needed</h3>
        </div>

        <?php if (empty($lowStockProducts)): ?>
            <div style="padding: 2.5rem; text-align: center; color: var(--success);">
                ✓ All active catalogue items have healthy stock levels.
            </div>
        <?php else: ?>
            <div style="padding: 1rem 1.25rem;">
                <?php foreach ($lowStockProducts as $ls): ?>
                    <div style="display: flex; gap: 1rem; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--admin-border);">
                        <img src="<?= UPLOAD_URL . e($ls['image']); ?>" alt="" class="admin-table-thumb">
                        <div style="flex: 1;">
                            <strong style="font-size: 0.9rem; display:block;"><?= e($ls['name']); ?></strong>
                            <span style="font-size: 0.78rem; color: var(--admin-muted);"><?= e($ls['category_name']); ?> &bull; <?= formatPrice($ls['price']); ?></span>
                        </div>
                        <div style="text-align: right;">
                            <span style="display:inline-block; font-size: 0.75rem; font-weight: 700; color: #B91C1C; background: #FEE2E2; padding: 0.2rem 0.5rem; border-radius: var(--radius-sm);">
                                <?= (int)$ls['stock_quantity']; ?> left
                            </span>
                            <div style="margin-top: 0.3rem;">
                                <a href="<?= ADMIN_URL; ?>/edit-product.php?id=<?= $ls['id']; ?>" style="font-size: 0.75rem; color: var(--gold-dark); text-decoration: underline;">
                                    Add Stock
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
