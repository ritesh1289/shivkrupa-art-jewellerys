<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Homepage — Luxury Jewellery Showcase
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/components/header.php';

$db = getDB();

// 1. Fetch Categories
$catStmt = $db->query("SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active' GROUP BY c.id ORDER BY c.id ASC LIMIT 8");
$categories = $catStmt->fetchAll();

// 2. Fetch Featured Jewellery (High value / Flagship pieces)
$featStmt = $db->query("SELECT p.*, c.name as category_name, c.slug as category_slug FROM products p JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' ORDER BY p.price DESC LIMIT 4");
$featuredProducts = $featStmt->fetchAll();

// 3. Fetch New Arrivals (Latest added)
$newStmt = $db->query("SELECT p.*, c.name as category_name, c.slug as category_slug FROM products p JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' ORDER BY p.id DESC LIMIT 4");
$newArrivals = $newStmt->fetchAll();

// 4. Fetch Best Sellers / Trending
$trendStmt = $db->query("SELECT p.*, c.name as category_name, c.slug as category_slug FROM products p JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' ORDER BY p.discount_price IS NOT NULL DESC, p.stock_quantity ASC LIMIT 4");
$trendingProducts = $trendStmt->fetchAll();
?>

<!-- Hero Banner Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-grid">
            <div class="hero-content">
                <span class="hero-tag">✨ Festive & Bridal Collection 2026</span>
                <h1 class="hero-title">Timeless Elegance, Handcrafted in <span>Pure Gold</span></h1>
                <p class="hero-text">
                    Immerse yourself in authentic Indian heritage jewellery. From exquisite polki chokers to sparkling solitaire rings, each creation is certified BIS 916 hallmarked for generations of celebration.
                </p>
                <div class="hero-actions">
                    <a href="<?= BASE_URL; ?>/products.php?category=necklaces" class="btn btn-primary btn-lg">
                        Explore Bridal Sets &rarr;
                    </a>
                    <a href="<?= BASE_URL; ?>/categories.php" class="btn btn-outline btn-lg">
                        View All Collections
                    </a>
                </div>
            </div>

            <div class="hero-image-wrapper">
                <img src="<?= BASE_URL; ?>/assets/images/hero-jewellery.jpg" alt="Exquisite Gold & Diamond Bridal Suite" loading="eager">
                <div class="hero-badge-float">
                    <div class="hero-badge-icon">👑</div>
                    <div>
                        <strong style="display:block; font-size: 0.95rem;">Royal Nizam Edition</strong>
                        <span style="font-size: 0.8rem; color: var(--gold-dark); font-weight: 600;">Certified 22K Hallmarked</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Jewellery Collections / Categories Grid -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <span class="section-subtitle">Curated Categories</span>
            <h2 class="section-title">Discover By Collection</h2>
            <div class="gold-divider"></div>
            <p class="section-desc">Handcrafted jewels organized for the modern connoisseur of classical luxury.</p>
        </div>

        <div class="category-grid">
            <?php foreach ($categories as $cat): ?>
                <a href="<?= BASE_URL; ?>/products.php?category=<?= urlencode($cat['slug']); ?>" class="category-card">
                    <img src="<?= BASE_URL; ?>/assets/images/<?= e($cat['image']); ?>" alt="<?= e($cat['name']); ?>" loading="lazy">
                    <div class="category-overlay">
                        <h3 class="category-title"><?= e($cat['name']); ?></h3>
                        <span class="category-count"><?= (int)$cat['product_count']; ?> Designs</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Masterpieces -->
<section class="section" style="background: var(--bg-surface);">
    <div class="container">
        <div class="section-header">
            <span class="section-subtitle">Heritage Suite</span>
            <h2 class="section-title">Featured Masterpieces</h2>
            <div class="gold-divider"></div>
            <p class="section-desc">Our most celebrated royal ornaments, crafted with rare uncut diamonds and precious gems.</p>
        </div>

        <div class="products-grid">
            <?php foreach ($featuredProducts as $prod): ?>
                <?php include __DIR__ . '/../app/components/product-card.php'; ?>
            <?php endforeach; ?>
        </div>

        <div style="text-align: center; margin-top: 3.5rem;">
            <a href="<?= BASE_URL; ?>/products.php" class="btn btn-outline btn-lg">
                View Entire Fine Jewellery Catalog &rarr;
            </a>
        </div>
    </div>
</section>

<!-- Brand Story Showcase -->
<section class="section" style="background: linear-gradient(135deg, #1A1715 0%, #12100E 100%); color: #F5EFEB;">
    <div class="container">
        <div class="legacy-grid">
            <div>
                <span style="display:inline-block; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.15em; color: var(--gold-light); margin-bottom: 0.75rem; font-weight: 600;">The Shivkrupa Legacy</span>
                <h2 class="legacy-title">A Quarter Century of Uncompromising Purity</h2>
                <p style="color: #C7BFB5; font-size: 1.05rem; margin-bottom: 1.5rem; line-height: 1.7;">
                    Founded in the historic lanes of Mumbai's Zaveri Bazaar, Shivkrupa Art Jewellery was born from an unwavering devotion to the ancient art of Indian goldsmithing. Every curve of filigree and every setting of polki represents hundreds of hours of patient artisan craftsmanship.
                </p>
                <div class="legacy-stats">
                    <div style="border-left: 2px solid var(--gold-primary); padding-left: 1rem;">
                        <h4 style="color: #FFFFFF; font-size: 1.4rem;">100%</h4>
                        <span style="font-size: 0.85rem; color: #A89F95;">BIS 916 Hallmarking</span>
                    </div>
                    <div style="border-left: 2px solid var(--gold-primary); padding-left: 1rem;">
                        <h4 style="color: #FFFFFF; font-size: 1.4rem;">25,000+</h4>
                        <span style="font-size: 0.85rem; color: #A89F95;">Happy Families Adorned</span>
                    </div>
                </div>
                <a href="<?= BASE_URL; ?>/about.php" class="btn btn-primary">
                    Discover Our Story &rarr;
                </a>
            </div>

            <div style="position: relative; border-radius: var(--radius-lg); overflow: hidden; border: 1px solid rgba(197, 160, 89, 0.3); aspect-ratio: 4/3;">
                <img src="<?= BASE_URL; ?>/assets/images/hero-jewellery.jpg" alt="Artisan Craftsmanship" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        </div>
    </div>
</section>

<!-- New Arrivals Grid -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <span class="section-subtitle">Freshly Crafted</span>
            <h2 class="section-title">New Arrivals</h2>
            <div class="gold-divider"></div>
            <p class="section-desc">The newest expressions of contemporary and heritage fine jewellery.</p>
        </div>

        <div class="products-grid">
            <?php foreach ($newArrivals as $prod): ?>
                <?php include __DIR__ . '/../app/components/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Call to Action / VIP Club -->
<section class="section" style="background: linear-gradient(180deg, #F5EFEB 0%, #FAF8F5 100%); text-align: center;">
    <div class="container" style="max-width: 700px;">
        <span class="section-subtitle">The Connoisseur's Club</span>
        <h2 style="font-size: 2.4rem; margin-bottom: 1rem;">Receive Private Previews & Gold Rate Insights</h2>
        <p style="color: var(--text-muted); margin-bottom: 2rem;">
            Subscribe for invitations to exclusive bridal exhibitions, private designer showcases, and bespoke bespoke consultation appointments.
        </p>
        <form onsubmit="event.preventDefault(); showToast('Thank you for subscribing to Shivkrupa Privilege Club.', 'success'); this.reset();" style="display: flex; gap: 0.75rem; max-width: 500px; margin: 0 auto;">
            <input type="email" placeholder="Enter your email address" class="form-control" required style="background:#FFF;">
            <button type="submit" class="btn btn-primary" style="flex-shrink: 0;">Subscribe</button>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/../app/components/footer.php'; ?>
