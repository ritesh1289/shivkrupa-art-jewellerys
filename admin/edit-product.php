<?php
/**
 * SHIVKRUPA ART JEWELLERY — Admin Edit Product
 */

$adminPageTitle = "Edit Jewellery Piece | Admin Portal";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../app/middleware/csrf.php';

$db = getDB();
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    setFlash('error', 'Invalid product ID.');
    redirect(ADMIN_URL . '/products.php');
}

$stmt = $db->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('error', 'Product not found.');
    redirect(ADMIN_URL . '/products.php');
}

$categories = $db->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$errors = [];

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

        if (empty($name) || mb_strlen($name) < 3) {
            $errors[] = 'Product name must be at least 3 characters.';
        }

        if ($categoryId <= 0) {
            $errors[] = 'Please select a valid jewellery category.';
        }

        if ($price <= 0) {
            $errors[] = 'Please enter a valid retail price.';
        }

        if ($discountPrice !== null && $discountPrice >= $price) {
            $errors[] = 'Discount price must be less than the retail price.';
        }

        // Image Replacement (Optional)
        $imageName = $product['image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            $maxBytes = 2 * 1024 * 1024;
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if ($file['size'] > $maxBytes) {
                $errors[] = 'Image size must be less than 2MB.';
            } elseif (!in_array($fileExt, $allowedExts)) {
                $errors[] = 'Invalid file extension. Only JPG, PNG, and WEBP images are allowed.';
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if (!in_array($mime, $allowedMimes)) {
                    $errors[] = 'The uploaded file is not a valid image.';
                } else {
                    $newImageName = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
                    $targetPath = UPLOAD_DIR . $newImageName;

                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $imageName = $newImageName;
                    } else {
                        $errors[] = 'Failed to upload replacement image.';
                    }
                }
            }
        }

        // Update database
        if (empty($errors)) {
            try {
                $updStmt = $db->prepare("
                    UPDATE products SET 
                        category_id = ?, name = ?, sku = ?, description = ?, material = ?, 
                        price = ?, discount_price = ?, stock_quantity = ?, image = ?, status = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $updStmt->execute([
                    $categoryId,
                    $name,
                    $sku,
                    $description,
                    $material,
                    $price,
                    $discountPrice,
                    $stock,
                    $imageName,
                    $status,
                    $productId
                ]);

                // Safely remove old orphaned image if replacement was successful
                if ($imageName !== $product['image'] && !empty($product['image'])) {
                    $oldImg = $product['image'];
                    $chkOther = $db->prepare("SELECT COUNT(*) FROM products WHERE image = ? AND id != ?");
                    $chkOther->execute([$oldImg, $productId]);
                    if ((int)$chkOther->fetchColumn() === 0) {
                        $oldFilePath = UPLOAD_DIR . $oldImg;
                        if (file_exists($oldFilePath) && is_file($oldFilePath)) {
                            @unlink($oldFilePath);
                        }
                    }
                }

                setFlash('success', "Product '" . e($name) . "' updated successfully.");
                redirect(ADMIN_URL . '/products.php');
            } catch (PDOException $e) {
                error_log("Update product error: " . $e->getMessage());
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}
?>

<div style="margin-bottom: 2rem;">
    <a href="<?= ADMIN_URL; ?>/products.php" style="color: var(--gold-dark); font-size: 0.88rem;">&larr; Back to Products</a>
    <h1 style="font-size: 2.2rem; margin-top: 0.35rem;">Edit Jewellery Piece #<?= $product['id']; ?></h1>
    <p style="color: var(--admin-muted);">Update specifications, price points, stock availability, or imagery.</p>
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
    <form action="<?= ADMIN_URL; ?>/edit-product.php?id=<?= $product['id']; ?>" method="POST" enctype="multipart/form-data">
        <?= csrfField(); ?>

        <div class="input-row">
            <div class="form-group">
                <label class="form-label" for="name">Product Title <span style="color:var(--danger);">*</span></label>
                <input type="text" id="name" name="name" class="form-control" value="<?= e($product['name']); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="category_id">Category <span style="color:var(--danger);">*</span></label>
                <select id="category_id" name="category_id" class="form-control" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id']; ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                            <?= e($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="input-row">
            <div class="form-group">
                <label class="form-label" for="sku">Inventory SKU <span style="color:var(--danger);">*</span></label>
                <input type="text" id="sku" name="sku" class="form-control" value="<?= e($product['sku']); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="material">Metal Purity &amp; Material <span style="color:var(--danger);">*</span></label>
                <input type="text" id="material" name="material" class="form-control" value="<?= e($product['material']); ?>" required>
            </div>
        </div>

        <div class="input-row">
            <div class="form-group">
                <label class="form-label" for="price">Retail Price (₹) <span style="color:var(--danger);">*</span></label>
                <input type="number" step="0.01" id="price" name="price" class="form-control" value="<?= e($product['price']); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="discount_price">Special Offer Price (₹)</label>
                <input type="number" step="0.01" id="discount_price" name="discount_price" class="form-control" value="<?= e($product['discount_price'] ?? ''); ?>">
            </div>
        </div>

        <div class="input-row">
            <div class="form-group">
                <label class="form-label" for="stock_quantity">Available Inventory Stock <span style="color:var(--danger);">*</span></label>
                <input type="number" id="stock_quantity" name="stock_quantity" class="form-control" value="<?= e($product['stock_quantity']); ?>" min="0" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="status">Catalogue Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="active" <?= $product['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="out_of_stock" <?= $product['status'] === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Artisan Description <span style="color:var(--danger);">*</span></label>
            <textarea id="description" name="description" class="form-control" rows="4" required><?= e($product['description']); ?></textarea>
        </div>

        <!-- Current & Replacement Image -->
        <div class="form-group">
            <label class="form-label">Current Photograph</label>
            <div style="display: flex; gap: 1.5rem; align-items: center; margin-bottom: 1rem;">
                <img src="<?= UPLOAD_URL . e($product['image']); ?>" alt="" style="width: 90px; height: 90px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--admin-border);">
                <div>
                    <label class="form-label" for="image">Replace Photograph (Optional)</label>
                    <input type="file" id="image" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                    <span style="font-size: 0.8rem; color: var(--admin-muted);">Leave empty to preserve the current image.</span>
                </div>
            </div>
        </div>

        <div style="margin-top: 2rem; display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-primary btn-lg">
                Save Changes &rarr;
            </button>
            <a href="<?= ADMIN_URL; ?>/products.php" class="btn btn-outline btn-lg">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
