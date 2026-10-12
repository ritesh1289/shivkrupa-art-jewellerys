<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Reusable Product Card Partial
 * Expected input: $prod array
 */

$hasDiscount = (!empty($prod['discount_price']) && $prod['discount_price'] > 0);
$displayPrice = $hasDiscount ? (float)$prod['discount_price'] : (float)$prod['price'];
$origPrice = (float)$prod['price'];
$discountPercent = $hasDiscount ? calculateDiscountPercent($origPrice, $displayPrice) : 0;
$productUrl = BASE_URL . '/product.php?id=' . (int)$prod['id'];
?>
<div class="product-card" id="product-card-<?= (int)$prod['id']; ?>">
    <div class="product-thumb">
        <a href="<?= $productUrl; ?>">
            <img src="<?= UPLOAD_URL . e($prod['image']); ?>" alt="<?= e($prod['name']); ?>" loading="lazy">
        </a>
        
        <?php if ($hasDiscount): ?>
            <span class="product-badge sale"><?= $discountPercent; ?>% Off</span>
        <?php elseif ($prod['stock_quantity'] <= 3 && $prod['stock_quantity'] > 0): ?>
            <span class="product-badge" style="background:#B45309;">Only <?= (int)$prod['stock_quantity']; ?> Left</span>
        <?php endif; ?>
    </div>

    <div class="product-info">
        <div class="product-category"><?= e($prod['category_name'] ?? 'Fine Jewellery'); ?></div>
        
        <h3 class="product-title">
            <a href="<?= $productUrl; ?>"><?= e($prod['name']); ?></a>
        </h3>
        
        <div class="product-material"><?= e($prod['material']); ?></div>

        <div class="product-price-row">
            <span class="product-price"><?= formatPrice($displayPrice); ?></span>
            <?php if ($hasDiscount): ?>
                <span class="product-old-price"><?= formatPrice($origPrice); ?></span>
                <span class="product-discount-pill">Save <?= formatPrice($origPrice - $displayPrice); ?></span>
            <?php endif; ?>
        </div>

        <div class="product-actions">
            <?php if ($prod['stock_quantity'] > 0): ?>
                <button type="button" class="btn btn-primary btn-block btn-sm" onclick="handleAddToCart(<?= (int)$prod['id']; ?>, 1, '<?= BASE_URL; ?>')">
                    🛍️ Add to Bag
                </button>
            <?php else: ?>
                <button type="button" class="btn btn-block btn-sm" disabled style="background:var(--bg-subtle); color:var(--text-light); cursor:not-allowed;">
                    Out of Stock
                </button>
            <?php endif; ?>
            <a href="<?= $productUrl; ?>" class="btn btn-outline btn-sm" title="View Details">
                👁️
            </a>
        </div>
    </div>
</div>
