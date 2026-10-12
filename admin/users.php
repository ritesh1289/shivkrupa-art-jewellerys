<?php
/**
 * SHIVKRUPA ART JEWELLERY — Admin Registered Customers Directory
 */

$adminPageTitle = "Registered Customers | Admin Portal";
require_once __DIR__ . '/includes/header.php';

$db = getDB();

$stmt = $db->query("
    SELECT 
        u.*, 
        COUNT(o.id) as total_orders, 
        COALESCE(SUM(CASE WHEN o.payment_status = 'paid' THEN o.total_amount ELSE 0 END), 0) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    GROUP BY u.id
    ORDER BY u.id DESC
");
$users = $stmt->fetchAll();
?>

<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 2.2rem; margin-bottom: 0.25rem;">Customers Directory</h1>
    <p style="color: var(--admin-muted);">Registered customer accounts, contact details, and client purchase activity.</p>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Registered Clients (<?= count($users); ?>)</h3>
    </div>

    <?php if (empty($users)): ?>
        <div style="padding: 3rem; text-align: center; color: var(--admin-muted);">
            No customer accounts registered yet.
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Client ID</th>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>Phone</th>
                    <th>Location</th>
                    <th>Orders</th>
                    <th>Total Spend</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td style="font-family: monospace; font-size: 0.85rem; color: var(--gold-dark); font-weight: 700;">
                            #SKJ-<?= str_pad($u['id'], 4, '0', STR_PAD_LEFT); ?>
                        </td>

                        <td>
                            <strong><?= e($u['full_name']); ?></strong>
                        </td>

                        <td>
                            <a href="mailto:<?= e($u['email']); ?>" style="color: var(--admin-text); text-decoration: underline;">
                                <?= e($u['email']); ?>
                            </a>
                        </td>

                        <td>
                            <?= e($u['phone']); ?>
                        </td>

                        <td style="font-size: 0.85rem; color: var(--admin-muted);">
                            <?php if (!empty($u['city']) || !empty($u['state'])): ?>
                                <?= e($u['city'] ?? ''); ?><?= (!empty($u['city']) && !empty($u['state'])) ? ', ' : ''; ?><?= e($u['state'] ?? ''); ?>
                            <?php else: ?>
                                <span style="font-style: italic;">Not provided</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <span style="font-weight: 600;"><?= (int)$u['total_orders']; ?></span> orders
                        </td>

                        <td>
                            <strong><?= formatPrice($u['total_spent']); ?></strong>
                        </td>

                        <td style="font-size: 0.82rem; color: var(--admin-muted);">
                            <?= date('d M Y', strtotime($u['created_at'])); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
