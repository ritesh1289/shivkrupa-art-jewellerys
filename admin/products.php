<?php
/**
 * SHIVKRUPA ART JEWELLERY — Admin Product Inventory Management
 */

$adminPageTitle = "Products Inventory | Admin Portal";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../app/middleware/csrf.php';

$db = getDB();

// Handle Safe Delete / Status Toggle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Security validation failed.');
    } else {
        $action = $_POST['action'] ?? '';
        $targetId = (int)($_POST['product_id'] ?? 0);

        if ($action === 'toggle_status') {
            $stmt = $db->prepare("UPDATE products SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE id = ?");
            $stmt->execute([$targetId]);
            setFlash('success', 'Product visibility status toggled successfully.');
        } elseif ($action === 'delete') {
            // Check if product is in any existing orders
            $chk = $db->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
            $chk->execute([$targetId]);
            $orderCount = (int)$chk->fetchColumn();

            if ($orderCount > 0) {
                // Safe handling: do not break historical customer order records! Mark inactive instead.
                $stmt = $db->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
                $stmt->execute([$targetId]);
                setFlash('warning', "This piece belongs to {$orderCount} past customer order(s). It has been safely deactivated instead of deleted to protect order history.");
            } else {
                // Fetch image before deletion
                $imgStmt = $db->prepare("SELECT image FROM products WHERE id = ? LIMIT 1");
                $imgStmt->execute([$targetId]);
                $delImage = $imgStmt->fetchColumn();

                $del = $db->prepare("DELETE FROM products WHERE id = ?");
                $del->execute([$targetId]);

                // If no other product uses this image, safely remove from uploads
                if (!empty($delImage)) {
                    $chkOther = $db->prepare("SELECT COUNT(*) FROM products WHERE image = ?");
                    $chkOther->execute([$delImage]);
                    if ((int)$chkOther->fetchColumn() === 0) {
                        $imgPath = UPLOAD_DIR . $delImage;
                        if (file_exists($imgPath) && is_file($imgPath)) {
                            @unlink($imgPath);
                        }
                    }
                }

                setFlash('success', 'Product deleted from database.');
            }
        }
    }
    redirect(ADMIN_URL . '/products.php');
}

// Filtering
$catFilter = trim($_GET['category'] ?? '');
$searchFilter = trim($_GET['search'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($catFilter)) {
    $where[] = "c.slug = ?";
    $params[] = $catFilter;
}

if (!empty($searchFilter)) {
    $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.material LIKE ?)";
    $params[] = "%{$searchFilter}%";
    $params[] = "%{$searchFilter}%";
    $params[] = "%{$searchFilter}%";
}

$whereSql = implode(" AND ", $where);

$stmt = $db->prepare("
    SELECT p.*, c.name as category_name, c.slug as category_slug 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE {$whereSql} 
    ORDER BY p.id DESC
");
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $db->query("SELECT id, name, slug FROM categories ORDER BY name ASC")->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 2.2rem; margin-bottom: 0.25rem;">Jewellery Products</h1>
        <p style="color: var(--admin-muted);">Manage inventory, prices, pure metal specs, and catalog visibility.</p>
    </div>
    <a href="<?= ADMIN_URL; ?>/add-product.php" class="btn btn-primary">
        ➕ Add New Jewellery Piece
    </a>
</div>

<!-- Filters Bar -->
<div class="admin-card" style="padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
    <form action="<?= ADMIN_URL; ?>/products.php" method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 240px;">
            <input type="text" name="search" class="form-control" placeholder="Search by piece title, SKU, metal..." value="<?= e($searchFilter); ?>">
        </div>

        <div style="min-width: 200px;">
            <select name="category" class="form-control" onchange="this.form.submit()">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat['slug']); ?>" <?= $catFilter === $cat['slug'] ? 'selected' : ''; ?>>
                        <?= e($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <?php if (!empty($catFilter) || !empty($searchFilter)): ?>
            <a href="<?= ADMIN_URL; ?>/products.php" class="btn btn-outline btn-sm">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Products Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3>Catalogue Items (<?= count($products); ?>)</h3>
    </div>

    <?php if (empty($products)): ?>
        <div style="padding: 3rem; text-align: center; color: var(--admin-muted);">
            No products match the selected criteria.
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Piece</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <img src="<?= UPLOAD_URL . e($p['image']); ?>" alt="" class="admin-table-thumb">
                                <div>
                                    <strong style="font-size: 0.95rem; display:block;"><?= e($p['name']); ?></strong>
                                    <span style="font-size: 0.8rem; color: var(--admin-muted);"><?= e($p['material']); ?></span>
                                </div>
                            </div>
                        </td>

                        <td style="font-family: monospace; font-size: 0.85rem;">
                            <?= e($p['sku']); ?>
                        </td>

                        <td>
                            <?= e($p['category_name']); ?>
                        </td>

                        <td>
                            <strong><?= formatPrice($p['price']); ?></strong>
                            <?php if (!empty($p['discount_price']) && $p['discount_price'] > 0): ?>
                                <div style="font-size: 0.78rem; color: var(--success);">
                                    Sale: <?= formatPrice($p['discount_price']); ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php $stock = (int)$p['stock_quantity']; ?>
                            <span style="font-weight: 700; <?= $stock <= 3 ? 'color: var(--danger);' : ''; ?>">
                                <?= $stock; ?> units
                            </span>
                        </td>

                        <td>
                            <form action="<?= ADMIN_URL; ?>/products.php" method="POST" style="display:inline;">
                                <?= csrfField(); ?>
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="product_id" value="<?= $p['id']; ?>">
                                
                                <button type="submit" style="background:none; border:none; cursor:pointer;" title="Click to toggle status">
                                    <span style="display:inline-block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; padding: 0.2rem 0.55rem; border-radius: var(--radius-sm); <?= $p['status'] === 'active' ? 'background:var(--success-bg); color:var(--success);' : 'background:var(--danger-bg); color:var(--danger);'; ?>">
                                        <?= ucfirst($p['status']); ?>
                                    </span>
                                </button>
                            </form>
                        </td>

                        <td style="text-align: right;">
                            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                <a href="<?= ADMIN_URL; ?>/edit-product.php?id=<?= $p['id']; ?>" class="btn btn-outline btn-sm" title="Edit Piece">
                                    ✏️ Edit
                                </a>

                                <form action="<?= ADMIN_URL; ?>/products.php" method="POST" onsubmit="return confirm('Are you sure you want to delete or deactivate this product?');" style="display:inline;">
                                    <?= csrfField(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="product_id" value="<?= $p['id']; ?>">
                                    <button type="submit" class="btn btn-sm" style="color: var(--danger); border-color: var(--admin-border);" title="Delete / Deactivate">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
