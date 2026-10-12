<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Shopping Cart Engine (Session-Backed with Database Inventory Validation)
 */

require_once __DIR__ . '/../helpers/functions.php';

/**
 * Get raw cart session array: [product_id => quantity]
 *
 * @return array
 */
function getCart(): array {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

/**
 * Add a product to the cart with real-time stock validation
 *
 * @param int $productId
 * @param int $quantity
 * @return array ['success' => bool, 'message' => string]
 */
function addToCart(int $productId, int $quantity = 1): array {
    if ($productId <= 0 || $quantity <= 0) {
        return ['success' => false, 'message' => 'Invalid product or quantity specified.'];
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, price, stock_quantity, status FROM products WHERE id = ? LIMIT 1");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        return ['success' => false, 'message' => 'Product not found in our catalogue.'];
    }

    if ($product['status'] !== 'active') {
        return ['success' => false, 'message' => 'This jewellery item is currently unavailable.'];
    }

    if ($product['stock_quantity'] <= 0) {
        return ['success' => false, 'message' => 'Sorry, this piece is currently out of stock.'];
    }

    $cart = getCart();
    $currentQty = $cart[$productId] ?? 0;
    $newQty = $currentQty + $quantity;

    if ($newQty > $product['stock_quantity']) {
        return [
            'success' => false,
            'message' => "Only {$product['stock_quantity']} unit(s) available in stock. You already have {$currentQty} in your cart."
        ];
    }

    $_SESSION['cart'][$productId] = $newQty;

    return [
        'success' => true,
        'message' => "Added " . e($product['name']) . " to your shopping bag.",
        'cartCount' => getCartCount()
    ];
}

/**
 * Update the quantity of an existing item in the cart
 *
 * @param int $productId
 * @param int $quantity
 * @return array ['success' => bool, 'message' => string]
 */
function updateCartQuantity(int $productId, int $quantity): array {
    $cart = getCart();

    if (!isset($cart[$productId])) {
        return ['success' => false, 'message' => 'Item is not in your cart.'];
    }

    if ($quantity <= 0) {
        return removeFromCart($productId);
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, stock_quantity, status FROM products WHERE id = ? LIMIT 1");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product || $product['status'] !== 'active') {
        unset($_SESSION['cart'][$productId]);
        return ['success' => false, 'message' => 'This product is no longer available and was removed from your cart.'];
    }

    if ($quantity > $product['stock_quantity']) {
        $_SESSION['cart'][$productId] = (int)$product['stock_quantity'];
        return [
            'success' => false,
            'message' => "Maximum available stock is {$product['stock_quantity']}. Quantity adjusted."
        ];
    }

    $_SESSION['cart'][$productId] = $quantity;
    return ['success' => true, 'message' => 'Shopping bag updated.'];
}

/**
 * Remove an item from the cart
 *
 * @param int $productId
 * @return array ['success' => bool, 'message' => string]
 */
function removeFromCart(int $productId): array {
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        return ['success' => true, 'message' => 'Item removed from your shopping bag.'];
    }
    return ['success' => false, 'message' => 'Item was not found in your shopping bag.'];
}

/**
 * Clear the entire cart
 */
function clearCart(): void {
    $_SESSION['cart'] = [];
}

/**
 * Get total count of distinct items in cart
 *
 * @return int
 */
function getCartCount(): int {
    $cart = getCart();
    $totalCount = 0;
    foreach ($cart as $qty) {
        $totalCount += (int)$qty;
    }
    return $totalCount;
}

/**
 * Get complete cart details by hydrating product data from MySQL.
 * Never trusts prices from client session.
 *
 * @return array ['items' => array, 'subtotal' => float, 'discount' => float, 'total' => float, 'count' => int]
 */
function getCartDetails(): array {
    $cart = getCart();
    $details = [
        'items' => [],
        'subtotal' => 0.0,
        'discount' => 0.0,
        'total' => 0.0,
        'count' => 0
    ];

    if (empty($cart)) {
        return $details;
    }

    $productIds = array_keys($cart);
    if (empty($productIds)) {
        return $details;
    }

    $db = getDB();
    // Build parameterized IN clause: (?, ?, ?)
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stmt = $db->prepare("
        SELECT p.id, p.name, p.slug, p.sku, p.price, p.discount_price, p.stock_quantity, p.image, p.status, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id IN ($placeholders)
    ");
    $stmt->execute($productIds);
    $products = $stmt->fetchAll();

    $keyedProducts = [];
    foreach ($products as $p) {
        $keyedProducts[$p['id']] = $p;
    }

    foreach ($cart as $productId => $qty) {
        if (!isset($keyedProducts[$productId])) {
            // Product no longer exists in DB, clean up session
            unset($_SESSION['cart'][$productId]);
            continue;
        }

        $prod = $keyedProducts[$productId];
        $qty = (int)$qty;

        // Auto-correct if cart qty exceeds current inventory
        if ($qty > $prod['stock_quantity']) {
            $qty = max(1, (int)$prod['stock_quantity']);
            $_SESSION['cart'][$productId] = $qty;
        }

        // Determine effective active price
        $originalPrice = (float)$prod['price'];
        $effectivePrice = ($prod['discount_price'] !== null && $prod['discount_price'] > 0)
            ? (float)$prod['discount_price']
            : $originalPrice;

        $lineTotal = $effectivePrice * $qty;
        $lineOriginal = $originalPrice * $qty;
        $lineSavings = $lineOriginal - $lineTotal;

        $details['items'][] = [
            'product' => $prod,
            'quantity' => $qty,
            'original_price' => $originalPrice,
            'effective_price' => $effectivePrice,
            'line_total' => $lineTotal,
            'line_savings' => $lineSavings
        ];

        $details['subtotal'] += $lineOriginal;
        $details['discount'] += $lineSavings;
        $details['total'] += $lineTotal;
        $details['count'] += $qty;
    }

    return $details;
}
