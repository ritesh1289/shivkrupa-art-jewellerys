<?php
/**
 * SHIVKRUPA ART JEWELLERY — Admin Category Management
 */

$adminPageTitle = "Categories Management | Admin Portal";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../app/middleware/csrf.php';

$db = getDB();
$errors = [];

// Handle Category Actions (Add / Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $errors[] = 'Security token invalid.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $name        = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if (empty($name)) {
                $errors[] = 'Category name is required.';
            } else {
                $slug = slugify($name);
                // Check unique slug
                $chk = $db->prepare("SELECT id FROM categories WHERE slug = ? LIMIT 1");
                $chk->execute([$slug]);
                if ($chk->fetch()) {
                    $errors[] = 'A category with this name or slug already exists.';
                } else {
                    $imageName = 'category-rings.jpg'; // default fallback image
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES['image'];
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                            $imageName = 'cat_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                            move_uploaded_file($file['tmp_name'], ROOT_PATH . '/assets/images/' . $imageName);
                        }
                    }

                    $ins = $db->prepare("INSERT INTO categories (name, slug, description, image, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $ins->execute([$name, $slug, $description, $imageName]);
                    setFlash('success', "Category '{$name}' created.");
                    redirect(ADMIN_URL . '/categories.php');
                }
            }
        } elseif ($action === 'delete') {
            $catId = (int)($_POST['category_id'] ?? 0);
            
            // Referential Integrity Check: ensure no products belong to this category
            $chkProd = $db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
            $chkProd->execute([$catId]);
            $prodCount = (int)$chkProd->fetchColumn();

            if ($prodCount > 0) {
                setFlash('error', "Cannot delete category: {$prodCount} product(s) currently belong to it. Please reassign or remove the products first.");
            } else {
                $del = $db->prepare("DELETE FROM categories WHERE id = ?");
                $del->execute([$catId]);
                setFlash('success', 'Category deleted successfully.');
            }
            redirect(ADMIN_URL . '/categories.php');
        }
    }
}

// Fetch all categories with product counts
$categories = $db->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.name ASC
")->fetchAll();
?>

<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 2.2rem; margin-bottom: 0.25rem;">Jewellery Categories</h1>
    <p style="color: var(--admin-muted);">Organize product suites, bridal categories, and collection imagery.</p>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error" role="alert">
        <span class="alert-icon">✕</span>
        <div class="alert-content">
            <ul style="list-style: disc; padding-left: 1.25rem; margin: 0;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1.2fr 1.8fr; gap: 2.5rem; align-items: start;">
    
    <!-- Add Category Form -->
    <div class="admin-card" style="padding: 2rem;">
        <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--admin-border); padding-bottom: 0.75rem;">
            Create New Category
        </h3>

        <form action="<?= ADMIN_URL; ?>/categories.php" method="POST" enctype="multipart/form-data">
            <?= csrfField(); ?>
            <input type="hidden" name="action" value="add">

            <div class="form-group">
                <label class="form-label" for="name">Category Name <span style="color:var(--danger);">*</span></label>
                <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Mangalsutras, Anklets" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Short Collection Summary</label>
                <textarea id="description" name="description" class="form-control" placeholder="Brief overview for the collection page..."></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="image">Collection Cover Photo (Optional)</label>
                <input type="file" id="image" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                ➕ Create Category
            </button>
        </form>
    </div>

    <!-- Category List Table -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Active Collections (<?= count($categories); ?>)</h3>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Slug</th>
                    <th>Products</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $c): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.85rem;">
                                <img src="<?= BASE_URL; ?>/assets/images/<?= e($c['image']); ?>" alt="" class="admin-table-thumb" style="width: 44px; height: 44px;">
                                <div>
                                    <strong><?= e($c['name']); ?></strong>
                                </div>
                            </div>
                        </td>

                        <td style="font-family: monospace; font-size: 0.85rem; color: var(--admin-muted);">
                            <?= e($c['slug']); ?>
                        </td>

                        <td>
                            <span style="font-weight: 600;"><?= (int)$c['product_count']; ?></span> pieces
                        </td>

                        <td style="text-align: right;">
                            <form action="<?= ADMIN_URL; ?>/categories.php" method="POST" onsubmit="return confirm('Delete this category?');" style="display:inline;">
                                <?= csrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="category_id" value="<?= $c['id']; ?>">
                                <button type="submit" class="btn btn-sm" style="color: var(--danger); border-color: var(--admin-border);" title="Delete Category">
                                    🗑️
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
