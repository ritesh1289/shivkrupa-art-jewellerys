<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Shopping Cart Controller & Page
 * Supports both AJAX requests (?ajax=1) and standard HTTP POST/GET
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/services/cart.php';
require_once __DIR__ . '/../app/middleware/csrf.php';

// Check if this is an AJAX request
$isAjax = isset($_GET['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

// Handle Cart Actions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Security token expired. Please refresh the page.']);
            exit;
        } else {
            setFlash('error', 'Security session expired. Please refresh and try again.');
            redirect(BASE_URL . '/cart.php');
        }
    }

    $action = $_POST['action'] ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);

    $response = ['success' => false, 'message' => 'Invalid action.'];

    switch ($action) {
        case 'add':
            $response = addToCart($productId, $quantity);
            break;

        case 'update':
            $response = updateCartQuantity($productId, $quantity);
            break;

        case 'remove':
            $response = removeFromCart($productId);
            break;

        case 'clear':
            clearCart();
            $response = ['success' => true, 'message' => 'Your shopping bag has been cleared.'];
            break;
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        $cartDetails = getCartDetails();
        $response['cartCount'] = $cartDetails['count'];
        $response['cartTotal'] = formatPrice($cartDetails['total']);
        echo json_encode($response);
        exit;
    } else {
        if ($response['success']) {
            setFlash('success', $response['message']);
        } else {
            setFlash('error', $response['message']);
        }
        redirect(BASE_URL . '/cart.php');
    }
}

// Hydrate cart items from MySQL with validated stock & prices
$cartData = getCartDetails();
$cartItems = $cartData['items'];
$subtotal = $cartData['subtotal'];
$discount = $cartData['discount'];
$total = $cartData['total'];
$itemCount = $cartData['count'];

$pageTitle = "Shopping Bag (" . $itemCount . ") | " . APP_NAME;
require_once __DIR__ . '/../app/components/header.php';
?>

<div class="container section">
    <div style="margin-bottom: 2.5rem;">
        <span class="section-subtitle">Bag Review</span>
        <h1 style="font-size: 2.4rem; margin-bottom: 0.35rem;">Your Shopping Bag</h1>
        <p style="color: var(--text-muted);">Review your selected jewellery pieces before securing your insured checkout.</p>
    </div>

    <?php if (empty($cartItems)): ?>
        <!-- Empty Cart State -->
        <div style="background: var(--bg-surface); border: 1px dashed var(--border-color); border-radius: var(--radius-md); padding: 5rem 2rem; text-align: center; max-width: 650px; margin: 0 auto;">
            <div style="font-size: 3.5rem; margin-bottom: 1.25rem;">🛍️</div>
            <h2 style="font-size: 1.8rem; margin-bottom: 0.5rem;">Your Shopping Bag is Empty</h2>
            <p style="color: var(--text-muted); margin-bottom: 2rem; font-size: 1.05rem;">
                Explore our handcrafted gold and diamond collections to find the perfect heirloom for your milestone.
            </p>
            <a href="<?= BASE_URL; ?>/products.php" class="btn btn-primary btn-lg">
                Explore Collections &rarr;
            </a>
        </div>
    <?php else: ?>
        <!-- Cart Layout: Items Table + Order Summary -->
        <div class="cart-layout">
            
            <!-- Items Table -->
            <div>
                <div class="cart-table-wrapper">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Jewellery Piece</th>
                                <th>Unit Price</th>
                                <th style="text-align: center;">Quantity</th>
                                <th style="text-align: right;">Subtotal</th>
                                <th style="text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <?php 
                                    $p = $item['product']; 
                                    $qty = $item['quantity'];
                                    $unitPrice = $item['effective_price'];
                                    $origPrice = $item['original_price'];
                                    $lineTotal = $item['line_total'];
                                    $stockMax = (int)$p['stock_quantity'];
                                ?>
                                <tr>
                                    <!-- Product Details Cell -->
                                    <td>
                                        <div class="cart-product-cell">
                                            <a href="<?= BASE_URL; ?>/product.php?id=<?= $p['id']; ?>">
                                                <img src="<?= UPLOAD_URL . e($p['image']); ?>" alt="<?= e($p['name']); ?>" class="cart-thumb">
                                            </a>
                                            <div>
                                                <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--gold-dark); font-weight: 600; letter-spacing: 0.05em;">
                                                    <?= e($p['category_name']); ?>
                                                </span>
                                                <h4 style="font-size: 1.1rem; margin: 0.15rem 0 0.25rem 0;">
                                                    <a href="<?= BASE_URL; ?>/product.php?id=<?= $p['id']; ?>">
                                                        <?= e($p['name']); ?>
                                                    </a>
                                                </h4>
                                                <span style="font-size: 0.8rem; color: var(--text-light);">
                                                    SKU: <?= e($p['sku']); ?> &bull; <?= e($p['material'] ?? ''); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Unit Price -->
                                    <td>
                                        <strong><?= formatPrice($unitPrice); ?></strong>
                                        <?php if ($origPrice > $unitPrice): ?>
                                            <div style="font-size: 0.75rem; color: var(--text-light); text-decoration: line-through;">
                                                <?= formatPrice($origPrice); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Quantity Controls with Real-time Stock Boundaries -->
                                    <td style="text-align: center;">
                                        <form action="<?= BASE_URL; ?>/cart.php" method="POST" class="qty-control" style="display:inline-flex;">
                                            <?= csrfField(); ?>
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="product_id" value="<?= $p['id']; ?>">
                                            
                                            <!-- Decrease Button -->
                                            <button type="submit" name="quantity" value="<?= $qty - 1; ?>" class="qty-btn" title="Decrease quantity" <?= $qty <= 1 ? 'disabled style="opacity:0.4;cursor:not-allowed;"' : ''; ?>>
                                                &minus;
                                            </button>

                                            <!-- Display Quantity -->
                                            <input type="text" class="qty-input" value="<?= $qty; ?>" readonly>

                                            <!-- Increase Button -->
                                            <button type="submit" name="quantity" value="<?= $qty + 1; ?>" class="qty-btn" title="Increase quantity" <?= $qty >= $stockMax ? 'disabled style="opacity:0.4;cursor:not-allowed;"' : ''; ?>>
                                                &plus;
                                            </button>
                                        </form>
                                        
                                        <?php if ($stockMax <= 3): ?>
                                            <div style="font-size: 0.7rem; color: #B45309; margin-top: 0.3rem;">
                                                Only <?= $stockMax; ?> in stock
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Line Total -->
                                    <td style="text-align: right; font-weight: 700; font-size: 1.05rem;">
                                        <?= formatPrice($lineTotal); ?>
                                    </td>

                                    <!-- Remove Button -->
                                    <td style="text-align: center;">
                                        <form action="<?= BASE_URL; ?>/cart.php" method="POST" onsubmit="return confirm('Remove this piece from your bag?');">
                                            <?= csrfField(); ?>
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="product_id" value="<?= $p['id']; ?>">
                                            <button type="submit" style="background: none; border: none; font-size: 1.25rem; color: var(--danger); cursor: pointer;" title="Remove Item">
                                                🗑️
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <a href="<?= BASE_URL; ?>/products.php" class="btn btn-outline btn-sm">
                        &larr; Continue Shopping
                    </a>

                    <form action="<?= BASE_URL; ?>/cart.php" method="POST" onsubmit="return confirm('Clear your entire shopping bag?');">
                        <?= csrfField(); ?>
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn btn-sm" style="color: var(--danger); border-color: var(--border-color);">
                            Clear Bag
                        </button>
                    </form>
                </div>
            </div>

            <!-- Order Summary Card -->
            <div class="order-summary-card">
                <h3 class="summary-title">Order Summary</h3>

                <div class="summary-row">
                    <span>Retail Subtotal (<?= $itemCount; ?> <?= $itemCount === 1 ? 'piece' : 'pieces'; ?>)</span>
                    <span><?= formatPrice($subtotal); ?></span>
                </div>

                <?php if ($discount > 0): ?>
                    <div class="summary-row" style="color: var(--success);">
                        <span>Festive Savings</span>
                        <span>-<?= formatPrice($discount); ?></span>
                    </div>
                <?php endif; ?>

                <div class="summary-row">
                    <span>Insured Courier Transit</span>
                    <span style="color: var(--success); font-weight: 600;">FREE</span>
                </div>

                <div class="summary-row">
                    <span>Applicable GST (3%)</span>
                    <span style="font-size: 0.85rem; color: var(--text-light);">Inclusive</span>
                </div>

                <div class="summary-row total">
                    <span>Total Amount</span>
                    <span style="color: var(--gold-dark);"><?= formatPrice($total); ?></span>
                </div>

                <div style="margin: 1.5rem 0;">
                    <a href="<?= BASE_URL; ?>/checkout.php" class="btn btn-primary btn-block btn-lg">
                        Proceed to Checkout &rarr;
                    </a>
                </div>

                <div style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.6; border-top: 1px solid var(--border-color); padding-top: 1.25rem;">
                    <div>🔒 <strong>100% Secure Checkout:</strong> Powered by Razorpay SSL Encryption.</div>
                    <div style="margin-top: 0.4rem;">📦 <strong>Tamper-Proof Box:</strong> Delivered by armed transit logistics.</div>
                </div>
            </div>

        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../app/components/footer.php'; ?>
