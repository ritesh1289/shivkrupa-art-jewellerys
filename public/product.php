<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Single Product Detail Page
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';
require_once dirname(__DIR__) . '/app/services/cart.php';
require_once dirname(__DIR__) . '/app/middleware/csrf.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
    setFlash('error', 'Jewellery item not found.');
    redirect(BASE_URL . '/products.php');
}

$db = getDB();
$stmt = $db->prepare("
    SELECT p.*, c.name as category_name, c.slug as category_slug 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ? AND p.status = 'active' 
    LIMIT 1
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('error', 'The requested jewellery piece is unavailable.');
    redirect(BASE_URL . '/products.php');
}

// Handle Buy Now or Direct Form Add
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Session expired. Please try again.');
    } else {
        $action = $_POST['action'] ?? 'add';
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        $res = addToCart($product['id'], $quantity);
        if ($res['success']) {
            if ($action === 'buy_now') {
                redirect(BASE_URL . '/checkout.php');
            } else {
                setFlash('success', $res['message']);
                redirect(BASE_URL . '/product.php?id=' . $product['id']);
            }
        } else {
            setFlash('error', $res['message']);
        }
    }
}

// Pricing
$hasDiscount = (!empty($product['discount_price']) && $product['discount_price'] > 0);
$displayPrice = $hasDiscount ? (float)$product['discount_price'] : (float)$product['price'];
$origPrice = (float)$product['price'];
$discountPercent = $hasDiscount ? calculateDiscountPercent($origPrice, $displayPrice) : 0;

// Related Products in the same category
$relStmt = $db->prepare("
    SELECT p.*, c.name as category_name, c.slug as category_slug 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.category_id = ? AND p.id != ? AND p.status = 'active' 
    ORDER BY RAND() LIMIT 4
");
$relStmt->execute([$product['category_id'], $product['id']]);
$relatedProducts = $relStmt->fetchAll();

$pageTitle = e($product['name']) . " | " . APP_NAME;
require_once dirname(__DIR__) . '/app/components/header.php';
?>

<div class="container section">
    <!-- Breadcrumb -->
    <div style="font-size: 0.85rem; color: var(--text-light); margin-bottom: 2rem;">
        <a href="<?= BASE_URL; ?>/index.php">Home</a> &bull; 
        <a href="<?= BASE_URL; ?>/products.php">All Jewellery</a> &bull; 
        <a href="<?= BASE_URL; ?>/products.php?category=<?= urlencode($product['category_slug']); ?>"><?= e($product['category_name']); ?></a> &bull; 
        <span style="color: var(--gold-dark); font-weight: 500;"><?= e($product['name']); ?></span>
    </div>

    <!-- Product Showcase Grid -->
    <div style="display: grid; grid-template-columns: 1.1fr 1fr; gap: 3.5rem; align-items: start;">
        
        <!-- Large Image Showcase -->
        <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm); aspect-ratio: 1/1; position: relative;">
            <img src="<?= UPLOAD_URL . e($product['image']); ?>" alt="<?= e($product['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
            
            <?php if ($hasDiscount): ?>
                <span class="product-badge sale" style="top: 20px; left: 20px; font-size: 0.85rem; padding: 0.35rem 0.85rem;">
                    Special Offer &bull; Save <?= $discountPercent; ?>%
                </span>
            <?php endif; ?>
        </div>

        <!-- Details & Order Actions -->
        <div style="display: flex; flex-direction: column;">
            <span class="product-category" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                <?= e($product['category_name']); ?> &bull; SKU: <?= e($product['sku']); ?>
            </span>

            <h1 style="font-size: 2.3rem; line-height: 1.25; margin-bottom: 0.75rem;">
                <?= e($product['name']); ?>
            </h1>

            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                <span style="font-size: 0.9rem; color: var(--text-muted); background: var(--bg-subtle); padding: 0.3rem 0.75rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                    ✨ <?= e($product['material']); ?>
                </span>
                
                <?php if ($product['stock_quantity'] > 0): ?>
                    <span style="color: var(--success); font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 0.35rem;">
                        ● In Stock (<?= (int)$product['stock_quantity']; ?> units available)
                    </span>
                <?php else: ?>
                    <span style="color: var(--danger); font-weight: 600; font-size: 0.9rem;">
                        ● Currently Out of Stock
                    </span>
                <?php endif; ?>
            </div>

            <!-- Price Row -->
            <div style="background: var(--bg-subtle); border-radius: var(--radius-md); padding: 1.25rem 1.5rem; margin-bottom: 1.75rem; border: 1px solid var(--border-gold);">
                <div style="display: flex; align-items: baseline; gap: 1rem;">
                    <span style="font-size: 2.2rem; font-weight: 700; color: var(--text-main);">
                        <?= formatPrice($displayPrice); ?>
                    </span>
                    <?php if ($hasDiscount): ?>
                        <span style="font-size: 1.25rem; color: var(--text-light); text-decoration: line-through;">
                            <?= formatPrice($origPrice); ?>
                        </span>
                        <span class="product-discount-pill" style="font-size: 0.85rem; padding: 0.25rem 0.6rem;">
                            Save <?= formatPrice($origPrice - $displayPrice); ?> (<?= $discountPercent; ?>% Off)
                        </span>
                    <?php endif; ?>
                </div>
                <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.35rem;">
                    Inclusive of all applicable taxes &bull; Complimentary insured doorstep delivery.
                </div>
            </div>

            <!-- Description -->
            <div style="margin-bottom: 2rem;">
                <h4 style="font-size: 1.15rem; margin-bottom: 0.65rem; color: var(--text-main);">Artisan's Description</h4>
                <p style="color: var(--text-muted); font-size: 1rem; line-height: 1.7;">
                    <?= nl2br(e($product['description'])); ?>
                </p>
            </div>

            <!-- Quantity & Actions Form -->
            <?php if ($product['stock_quantity'] > 0): ?>
                <form action="<?= BASE_URL; ?>/product.php?id=<?= $product['id']; ?>" method="POST" style="margin-bottom: 2rem;">
                    <?= csrfField(); ?>

                    <div style="display: flex; align-items: center; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <label for="quantity" style="font-weight: 600; font-size: 0.95rem;">Select Quantity:</label>
                        <div class="qty-control">
                            <button type="button" class="qty-btn" onclick="let q = document.getElementById('quantity'); if(q.value > 1) q.value--;">&minus;</button>
                            <input type="number" id="quantity" name="quantity" class="qty-input" value="1" min="1" max="<?= (int)$product['stock_quantity']; ?>" readonly>
                            <button type="button" class="qty-btn" onclick="let q = document.getElementById('quantity'); if(q.value < <?= (int)$product['stock_quantity']; ?>) q.value++;">&plus;</button>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <button type="submit" name="action" value="add" class="btn btn-outline btn-lg">
                            🛍️ Add to Bag
                        </button>
                        <button type="submit" name="action" value="buy_now" class="btn btn-primary btn-lg">
                            ⚡ Buy Now &rarr;
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div style="padding: 1rem; background: var(--danger-bg); color: var(--danger); border-radius: var(--radius-sm); margin-bottom: 2rem;">
                    This masterpiece is currently sold out. Please contact our showroom for bespoke made-to-order inquiries.
                </div>
            <?php endif; ?>

            <!-- Trust Features Accordion / Badges -->
            <div style="border-top: 1px solid var(--border-color); padding-top: 1.5rem; display: flex; flex-direction: column; gap: 0.85rem; font-size: 0.9rem; color: var(--text-muted);">
                <div>✅ <strong>BIS Hallmarked:</strong> 100% certified authentic gold and conflict-free gemstones.</div>
                <div>🛡️ <strong>Insured Transit:</strong> Fully insured courier dispatch in tamper-evident security vault packaging.</div>
                <div>🔄 <strong>15-Day Exchange:</strong> Hassle-free inspection and exchange guarantee.</div>
            </div>
        </div>
    </div>

    <!-- Related Jewellery Recommendations -->
    <?php if (!empty($relatedProducts)): ?>
        <div style="margin-top: 5rem;">
            <div class="section-header">
                <span class="section-subtitle">Complementary Creations</span>
                <h2 class="section-title">You May Also Admire</h2>
                <div class="gold-divider"></div>
            </div>

            <div class="products-grid">
                <?php foreach ($relatedProducts as $prod): ?>
                    <?php include dirname(__DIR__) . '/app/components/product-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/app/components/footer.php'; ?>
