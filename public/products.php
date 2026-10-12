<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Product Catalog with Search, Category Filtering, and Sorting
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';
require_once dirname(__DIR__) . '/app/components/header.php';

$db = getDB();

// 1. Fetch all categories for filter sidebar
$allCats = $db->query("SELECT id, name, slug FROM categories ORDER BY name ASC")->fetchAll();

// 2. Fetch all distinct materials for filter sidebar
$allMaterials = $db->query("SELECT DISTINCT material FROM products WHERE status = 'active' ORDER BY material ASC")->fetchAll(PDO::FETCH_COLUMN);

// 3. Filter Inputs
$searchParam   = trim($_GET['search'] ?? '');
$categoryParam = trim($_GET['category'] ?? '');
$materialParam = trim($_GET['material'] ?? '');
$sortParam     = trim($_GET['sort'] ?? 'newest');
$minPriceParam = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$maxPriceParam = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : null;

// 4. Build Dynamic Query with PDO Prepared Statements
$whereClauses = ["p.status = 'active'"];
$params = [];

if (!empty($searchParam)) {
    $whereClauses[] = "(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)";
    $searchWildcard = "%{$searchParam}%";
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
}

if (!empty($categoryParam)) {
    $whereClauses[] = "c.slug = ?";
    $params[] = $categoryParam;
}

if (!empty($materialParam)) {
    $whereClauses[] = "p.material = ?";
    $params[] = $materialParam;
}

if ($minPriceParam !== null) {
    $whereClauses[] = "COALESCE(p.discount_price, p.price) >= ?";
    $params[] = $minPriceParam;
}

if ($maxPriceParam !== null) {
    $whereClauses[] = "COALESCE(p.discount_price, p.price) <= ?";
    $params[] = $maxPriceParam;
}

$whereSql = implode(" AND ", $whereClauses);

// Sorting
$orderBy = match($sortParam) {
    'price_asc'  => 'COALESCE(p.discount_price, p.price) ASC',
    'price_desc' => 'COALESCE(p.discount_price, p.price) DESC',
    'name_asc'   => 'p.name ASC',
    default      => 'p.id DESC'
};

$sql = "
    SELECT p.*, c.name as category_name, c.slug as category_slug 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE {$whereSql} 
    ORDER BY {$orderBy}
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$pageTitle = "Fine Jewellery Catalogue | " . APP_NAME;
?>

<div class="container section">
    <!-- Breadcrumb & Header -->
    <div style="margin-bottom: 2rem;">
        <div style="font-size: 0.85rem; color: var(--text-light); margin-bottom: 0.5rem;">
            <a href="<?= BASE_URL; ?>/index.php">Home</a> &bull; 
            <span style="color: var(--gold-dark); font-weight: 500;">All Jewellery</span>
            <?php if (!empty($categoryParam)): ?>
                &bull; <span><?= e(ucwords(str_replace('-', ' ', $categoryParam))); ?></span>
            <?php endif; ?>
        </div>
        <h1 style="font-size: 2.4rem; margin-bottom: 0.35rem;">Fine Jewellery Catalogue</h1>
        <p style="color: var(--text-muted);">Explore our complete collection of certified hallmarked gold, diamond, and antique jewels.</p>
    </div>

    <!-- Catalog Layout (Sidebar Filters + Products Grid) -->
    <div class="catalog-layout">
        
        <!-- Sidebar Filters -->
        <aside class="catalog-sidebar">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
                <h3 style="font-size: 1.25rem; margin: 0;">Filters</h3>
                <?php if (!empty($searchParam) || !empty($categoryParam) || !empty($materialParam) || $minPriceParam !== null || $maxPriceParam !== null): ?>
                    <a href="<?= BASE_URL; ?>/products.php" style="font-size: 0.8rem; color: var(--danger); text-decoration: underline;">Reset All</a>
                <?php endif; ?>
            </div>

            <form action="<?= BASE_URL; ?>/products.php" method="GET" id="filter-form">
                <!-- Search Input -->
                <div class="form-group">
                    <label class="form-label" for="search">Keyword Search</label>
                    <input type="text" id="search" name="search" class="form-control" placeholder="Search ring, polki, gold..." value="<?= e($searchParam); ?>">
                </div>

                <!-- Category Filter -->
                <div class="form-group">
                    <label class="form-label" for="category">Category</label>
                    <select id="category" name="category" class="form-control" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Collections</option>
                        <?php foreach ($allCats as $cat): ?>
                            <option value="<?= e($cat['slug']); ?>" <?= $categoryParam === $cat['slug'] ? 'selected' : ''; ?>>
                                <?= e($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Material Filter -->
                <div class="form-group">
                    <label class="form-label" for="material">Material / Purity</label>
                    <select id="material" name="material" class="form-control" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Materials</option>
                        <?php foreach ($allMaterials as $mat): ?>
                            <option value="<?= e($mat); ?>" <?= $materialParam === $mat ? 'selected' : ''; ?>>
                                <?= e($mat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Price Range -->
                <div class="form-group">
                    <label class="form-label">Price Range (₹)</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                        <input type="number" name="min_price" class="form-control" placeholder="Min ₹" value="<?= $minPriceParam !== null ? e($minPriceParam) : ''; ?>">
                        <input type="number" name="max_price" class="form-control" placeholder="Max ₹" value="<?= $maxPriceParam !== null ? e($maxPriceParam) : ''; ?>">
                    </div>
                </div>

                <!-- Sorting -->
                <div class="form-group">
                    <label class="form-label" for="sort">Sort By</label>
                    <select id="sort" name="sort" class="form-control" onchange="document.getElementById('filter-form').submit()">
                        <option value="newest" <?= $sortParam === 'newest' ? 'selected' : ''; ?>>Newest Arrivals</option>
                        <option value="price_asc" <?= $sortParam === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?= $sortParam === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name_asc" <?= $sortParam === 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-sm" style="margin-top: 1rem;">
                    Apply Filters
                </button>
            </form>
        </aside>

        <!-- Main Product Area -->
        <main>
            <!-- Results Counter & Active Pills -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                <div style="font-size: 0.95rem; color: var(--text-muted);">
                    Showing <strong><?= count($products); ?></strong> bespoke pieces
                    <?php if (!empty($searchParam)): ?>
                        for "<strong><?= e($searchParam); ?></strong>"
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($products)): ?>
                <div style="text-align: center; padding: 4rem 2rem; background: var(--bg-surface); border: 1px dashed var(--border-color); border-radius: var(--radius-md);">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">💎</div>
                    <h3 style="font-size: 1.6rem; margin-bottom: 0.5rem;">No Jewellery Matches Your Criteria</h3>
                    <p style="color: var(--text-muted); max-width: 450px; margin: 0 auto 1.5rem auto;">
                        Try resetting your search query or broadening your price filter to discover our available collections.
                    </p>
                    <a href="<?= BASE_URL; ?>/products.php" class="btn btn-primary">
                        View All Collections
                    </a>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $prod): ?>
                        <?php include dirname(__DIR__) . '/app/components/product-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/app/components/footer.php'; ?>
