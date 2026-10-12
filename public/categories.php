<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Collections Directory Page
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/components/header.php';

$db = getDB();
$stmt = $db->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active' 
    GROUP BY c.id 
    ORDER BY c.name ASC
");
$categories = $stmt->fetchAll();

$pageTitle = "Jewellery Collections | " . APP_NAME;
?>

<div class="container section">
    <div class="section-header">
        <span class="section-subtitle">Royal Suites</span>
        <h1 class="section-title">Explore Our Collections</h1>
        <div class="gold-divider"></div>
        <p class="section-desc">Each collection is curated around classical Indian motifs, timeless wedding heirlooms, and contemporary diamond suites.</p>
    </div>

    <div class="category-grid">
        <?php foreach ($categories as $cat): ?>
            <a href="<?= BASE_URL; ?>/products.php?category=<?= urlencode($cat['slug']); ?>" class="category-card" style="aspect-ratio: 1/1.2;">
                <img src="<?= BASE_URL; ?>/assets/images/<?= e($cat['image']); ?>" alt="<?= e($cat['name']); ?>" loading="lazy">
                <div class="category-overlay">
                    <h2 class="category-title" style="font-size: 1.6rem;"><?= e($cat['name']); ?></h2>
                    <p style="font-size: 0.85rem; color: #E5C384; margin-bottom: 0.5rem; line-height: 1.4;">
                        <?= e($cat['description']); ?>
                    </p>
                    <span class="category-count"><?= (int)$cat['product_count']; ?> Exclusive Pieces &rarr;</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../app/components/footer.php'; ?>
