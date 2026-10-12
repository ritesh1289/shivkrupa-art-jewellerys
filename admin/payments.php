<?php
/**
 * SHIVKRUPA ART JEWELLERY — Admin Payment Transactions Ledger
 */

$adminPageTitle = "Payment Transactions | Admin Portal";
require_once __DIR__ . '/includes/header.php';

$db = getDB();

$stmt = $db->query("
    SELECT p.*, o.order_number, o.shipping_name, o.phone, u.email as user_email
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY p.id DESC
");
$payments = $stmt->fetchAll();

$totalCollected = $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'paid'")->fetchColumn();
?>

<div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 2.2rem; margin-bottom: 0.25rem;">Payment Transactions Ledger</h1>
        <p style="color: var(--admin-muted);">Official audit trail of all Razorpay payments and gateway callback signatures.</p>
    </div>
    <div style="background: var(--admin-card-bg); padding: 1rem 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-gold); text-align: right;">
        <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--gold-dark); font-weight: 700;">Total Settled Revenue</span>
        <h2 style="font-size: 1.8rem; margin: 0; color: var(--admin-text);"><?= formatPrice($totalCollected); ?></h2>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Captured Transactions (<?= count($payments); ?>)</h3>
    </div>

    <?php if (empty($payments)): ?>
        <div style="padding: 3rem; text-align: center; color: var(--admin-muted);">
            No payments captured in the system yet.
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Txn ID</th>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Method</th>
                    <th>Razorpay Payment ID</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $py): ?>
                    <tr>
                        <td style="font-family: monospace; font-size: 0.85rem;">
                            #<?= $py['id']; ?>
                        </td>

                        <td>
                            <a href="<?= ADMIN_URL; ?>/order-details.php?id=<?= $py['order_id']; ?>" style="color: var(--gold-dark); font-weight: 600;">
                                <?= e($py['order_number']); ?>
                            </a>
                        </td>

                        <td>
                            <strong><?= e($py['shipping_name']); ?></strong>
                            <div style="font-size: 0.78rem; color: var(--admin-muted);"><?= e($py['user_email'] ?? $py['phone']); ?></div>
                        </td>

                        <td>
                            <span style="font-size: 0.8rem; background: var(--bg-subtle); padding: 0.2rem 0.5rem; border-radius: var(--radius-sm); border: 1px solid var(--admin-border); text-transform: uppercase;">
                                <?= e($py['payment_method']); ?>
                            </span>
                        </td>

                        <td>
                            <code style="font-size: 0.82rem; color: var(--admin-muted);">
                                <?= e($py['razorpay_payment_id']); ?>
                            </code>
                        </td>

                        <td>
                            <strong style="font-size: 1rem;"><?= formatPrice($py['amount']); ?></strong>
                        </td>

                        <td>
                            <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; padding: 0.2rem 0.5rem; border-radius: var(--radius-sm); <?= $py['status'] === 'paid' ? 'background:var(--success-bg); color:var(--success);' : 'background:var(--warning-bg); color:var(--warning);'; ?>">
                                <?= e($py['status']); ?>
                            </span>
                        </td>

                        <td style="font-size: 0.85rem; color: var(--admin-muted);">
                            <?= date('d M Y, h:i A', strtotime($py['created_at'])); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
