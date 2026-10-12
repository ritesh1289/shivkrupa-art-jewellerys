<?php
/**
 * SHIVKRUPA ART JEWELLERY — Admin Add Product
 */

$adminPageTitle = "Add New Jewellery Piece | Admin Portal";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../app/middleware/csrf.php';

$db = getDB();
$errors = [];

// Fetch categories for select dropdown
$categories = $db->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

$name = '';
$categoryId = '';
$sku = 'SKJ-' . strtoupper(substr(uniqid(), -6));
$material = '22K Gold';
$description = '';
$price = '';
$discountPrice = '';
$stock = '10';
$status = 'active';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $errors[] = 'Security token invalid. Please retry.';
    } else {
        $name          = trim($_POST['name'] ?? '');
        $categoryId    = (int)($_POST['category_id'] ?? 0);
        $sku           = trim($_POST['sku'] ?? '');
        $material      = trim($_POST['material'] ?? '');
        $description   = trim($_POST['description'] ?? '');
        $price         = (float)($_POST['price'] ?? 0);
        $discountPrice = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null;
        $stock         = (int)($_POST['stock_quantity'] ?? 0);
        $status        = in_array($_POST['status'] ?? '', ['active', 'inactive', 'out_of_stock']) ? $_POST['status'] : 'active';

        // Validations
        if (empty($name) || mb_strlen($name) < 3) {
            $errors[] = 'Product name must be at least 3 characters.';
        }

        if ($categoryId <= 0) {
            $errors[] = 'Please select a valid jewellery category.';
        }

        if (empty($sku)) {
            $errors[] = 'SKU is required.';
        } else {
            // Check unique SKU
            $chkSku = $db->prepare("SELECT id FROM products WHERE sku = ? LIMIT 1");
            $chkSku->execute([$sku]);
            if ($chkSku->fetch()) {
                $errors[] = 'This SKU is already assigned to another piece.';
            }
        }

        if ($price <= 0) {
            $errors[] = 'Please enter a valid retail price.';
        }

        if ($discountPrice !== null && $discountPrice >= $price) {
            $errors[] = 'Discount price must be less than the retail price.';
        }

        if (empty($description)) {
            $errors[] = 'Please provide an artisan description.';
        }

        // Image Upload Validation
        $imageName = '';
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'A product image photograph is required.';
        } else {
            $file = $_FILES['image'];
            $maxBytes = 2 * 1024 * 1024; // 2MB
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if ($file['size'] > $maxBytes) {
                $errors[] = 'Image size must be less than 2MB.';
            } elseif (!in_array($fileExt, $allowedExts)) {
                $errors[] = 'Invalid file extension. Only JPG, PNG, and WEBP images are allowed.';
            } else {
                // Verify actual MIME type using fileinfo
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if (!in_array($mime, $allowedMimes)) {
                    $errors[] = 'The uploaded file is not a valid image.';
                } else {
                    $imageName = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
                    $targetPath = UPLOAD_DIR . $imageName;

                    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $errors[] = 'Failed to save product image to upload directory.';
                    }
                }
            }
        }

        // Insert into Database
        if (empty($errors)) {
            try {
                $slug = slugify($name);
                // Ensure unique slug
                $slugStmt = $db->prepare("SELECT id FROM products WHERE slug = ? LIMIT 1");
                $slugStmt->execute([$slug]);
                if ($slugStmt->fetch()) {
                    $slug .= '-' . time();
                }

                $stmt = $db->prepare("
                    INSERT INTO products (
                        category_id, name, slug, sku, description, material, price, discount_price, 
                        stock_quantity, image, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $categoryId,
                    $name,
                    $slug,
                    $sku,
                    $description,
                    $material,
                    $price,
                    $discountPrice,
                    $stock,
                    $imageName,
                    $status
                ]);

                setFlash('success', "Jewellery piece '" . e($name) . "' has been added to the catalog.");
                redirect(ADMIN_URL . '/products.php');
            } catch (PDOException $e) {
                error_log("Add product error: " . $e->getMessage());
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}
?>

<div style="margin-bottom: 2rem;">
    <a href="<?= ADMIN_URL; ?>/products.php" style="color: var(--gold-dark); font-size: 0.88rem;">&larr; Back to Products</a>
    <h1 style="font-size: 2.2rem; margin-top: 0.35rem;">Add New Jewellery Piece</h1>
    <p style="color: var(--admin-muted);">Register a new handcrafted piece with validated specifications, pure metal details, and certified photography.</p>
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

<div class="admin-card" style="padding: 2.5rem; max-width: 860px;">
    <form action="<?= ADMIN_URL; ?>/add-product.php" method="POST" enctype="multipart/form-data">
        <?= csrfField(); ?>

        <div class="input-row">
            <div class="form-group">
                <label class="form-label" for="name">Product Title <span style="color:var(--danger);">*</span></label>
                <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Royal Emerald Choker Necklace" value="<?= e($name); ?>" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="category_id">Jewellery Category <span style="color:var(--danger);">*</span></label>
                <select id="category_id" name="category_id" class="form-control" required>
                    <option value="">-- Choose Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id']; ?>" <?= $categoryId == $cat['id'] ? 'selected' : ''; ?>>
                            <?= e($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="input-row">
            <div class="form-group">
                <label class="form-label" for="sku">Inventory SKU <span style="color:var(--danger);">*</span></label>
                <input type="text" id="sku" name="sku" class="form-control" value="<?= e($sku); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="material">Metal Purity &amp; Material <span style="color:var(--danger);">*</span></label>
                <input type="text" id="material" name="material" class="form-control" placeholder="e.g. 22K Gold &amp; Polki Diamonds" value="<?= e($material); ?>" required>
            </div>
        </div>

        <div class="input-row">
            <div class="form-group">
                <label class="form-label" for="price">Retail Price (₹) <span style="color:var(--danger);">*</span></label>
                <input type="number" step="0.01" id="price" name="price" class="form-control" placeholder="e.g. 45000.00" value="<?= e($price); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="discount_price">Special Offer Price (₹, Optional)</label>
                <input type="number" step="0.01" id="discount_price" name="discount_price" class="form-control" placeholder="e.g. 39999.00" value="<?= e($discountPrice); ?>">
            </div>
        </div>

        <div class="input-row">
            <div class="form-group">
                <label class="form-label" for="stock_quantity">Initial Stock Quantity <span style="color:var(--danger);">*</span></label>
                <input type="number" id="stock_quantity" name="stock_quantity" class="form-control" value="<?= e($stock); ?>" min="0" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="status">Catalogue Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="active" <?= $status === 'active' ? 'selected' : ''; ?>>Active (Visible in Store)</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : ''; ?>>Inactive (Hidden)</option>
                    <option value="out_of_stock" <?= $status === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Artisan Craftsmanship Description <span style="color:var(--danger);">*</span></label>
            <textarea id="description" name="description" class="form-control" rows="4" placeholder="Detailed history, karat purity, stone cuts, clasp mechanism..." required><?= e($description); ?></textarea>
        </div>

        <div class="form-group">
            <label class="form-label" for="image">Piece Photography <span style="color:var(--danger);">*</span></label>
            <input type="file" id="image" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
            <span style="font-size: 0.8rem; color: var(--admin-muted);">Max file size: 2MB. Allowed: JPG, PNG, WEBP.</span>
        </div>

        <div style="margin-top: 2rem; display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-primary btn-lg">
                Publish Jewellery Piece &rarr;
            </button>
            <a href="<?= ADMIN_URL; ?>/products.php" class="btn btn-outline btn-lg">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
