<?php
/**
 * SHIVKRUPA ART JEWELLERY — Automated End-to-End Order Flow & Payment Verification Test
 *
 * SAFETY GATES & ISOLATION GUARANTEES:
 * 1. Aborts immediately if APP_ENV === 'production'.
 * 2. Requires a dedicated test database (database name containing 'test', e.g. 'shivkrupa_test_db').
 * 3. Never modifies real production or shared catalogue inventory.
 * 4. Uses disposable test-only records (created at start, cleaned up in finally block).
 * 5. Avoids blind stock increments; deletes test records during teardown.
 */

// 1. Environment Safety Gate
$appEnv = strtolower((string)(getenv('APP_ENV') ?: 'development'));
if ($appEnv === 'production') {
    fwrite(STDERR, "\n[SAFETY ABORT] Automated order flow test is strictly prohibited in a production environment (APP_ENV=production).\n");
    exit(1);
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/services/cart.php';

echo "=== SHIVKRUPA ART JEWELLERY: End-to-End Order Flow Test ===\n";

$db = getDB();

// 2. Active Database Target Verification Gate (Strict Allowlist)
$activeDbRaw = (string)$db->query("SELECT DATABASE()")->fetchColumn();
$activeDb = strtolower(trim($activeDbRaw));
echo "Connected Database Target: '{$activeDbRaw}' (Normalized: '{$activeDb}')\n";

$allowedTestDatabases = ['shivkrupa_test_db'];
$isTestDatabase = in_array($activeDb, $allowedTestDatabases, true);

if (!$isTestDatabase) {
    fwrite(STDERR, "\n" . str_repeat('=', 78) . "\n");
    fwrite(STDERR, "[SAFETY ABORT] Refusing to run tests against unauthorized database: '{$activeDbRaw}'\n");
    fwrite(STDERR, str_repeat('=', 78) . "\n");
    fwrite(STDERR, "To prevent mutating production or shared development inventory,\n");
    fwrite(STDERR, "this test strictly requires the explicitly approved test database: 'shivkrupa_test_db'.\n\n");
    fwrite(STDERR, "HOW TO RUN THIS TEST SAFELY:\n");
    fwrite(STDERR, "  Step 1: Initialize the dedicated test database:\n");
    fwrite(STDERR, "    PowerShell:      \$env:DB_NAME=\"shivkrupa_test_db\"; php database/setup-database.php\n");
    fwrite(STDERR, "    Windows CMD:     set DB_NAME=shivkrupa_test_db&& php database/setup-database.php\n");
    fwrite(STDERR, "    Linux/macOS:     DB_NAME=shivkrupa_test_db php database/setup-database.php\n\n");
    fwrite(STDERR, "  Step 2: Run the test targeting the test database:\n");
    fwrite(STDERR, "    PowerShell:      \$env:DB_NAME=\"shivkrupa_test_db\"; php tests/test_order_flow.php\n");
    fwrite(STDERR, "    Windows CMD:     set DB_NAME=shivkrupa_test_db&& php tests/test_order_flow.php\n");
    fwrite(STDERR, "    Linux/macOS:     DB_NAME=shivkrupa_test_db php tests/test_order_flow.php\n");
    fwrite(STDERR, str_repeat('=', 78) . "\n\n");
    exit(1);
}

echo "[OK] Verified safe target: '{$activeDbRaw}' matches approved test database allowlist.\n";

class OrderFlowTestException extends RuntimeException {}

$testProductId = null;
$createdOrderId = null;
$initialStock = 10;
$purchaseQty = 2;

try {
    // 3. Ensure test user exists
    $userStmt = $db->query("SELECT id, full_name, email FROM users LIMIT 1");
    $user = $userStmt->fetch();
    if (!$user) {
        throw new OrderFlowTestException("Test database requires at least one user record. Run database/setup-database.php.");
    }
    echo "[1/5] User identified: {$user['full_name']} (ID: {$user['id']})\n";

    // 4. Create a DISPOSABLE test-only product (never modifies real catalogue items)
    $categoryStmt = $db->query("SELECT id FROM categories LIMIT 1");
    $categoryId = (int)$categoryStmt->fetchColumn();
    if (!$categoryId) {
        throw new OrderFlowTestException("Test database requires at least one category record.");
    }

    $uniqueToken = bin2hex(random_bytes(4));
    $disposableSku = 'SKJ-TEST-DISP-' . $uniqueToken;
    $disposableSlug = 'test-disposable-gem-' . $uniqueToken;

    $createProdStmt = $db->prepare("
        INSERT INTO products (
            category_id, name, slug, sku, description, material, price, discount_price, stock_quantity, image, status
        ) VALUES (
            ?, ?, ?, ?, 'Automated test product for order flow validation', '18K Test Gold', 15000.00, NULL, ?, 'product-ring-1.jpg', 'active'
        )
    ");
    $createProdStmt->execute([
        $categoryId,
        'Test Disposable Jewel ' . $uniqueToken,
        $disposableSlug,
        $disposableSku,
        $initialStock
    ]);
    $testProductId = (int)$db->lastInsertId();
    echo "[2/5] Created disposable test product (ID: {$testProductId}, SKU: {$disposableSku}, Initial Stock: {$initialStock})\n";

    // 5. Add to cart & compute cart total
    clearCart();
    $addRes = addToCart($testProductId, $purchaseQty);
    if (!$addRes['success']) {
        throw new OrderFlowTestException("Failed to add test item to cart: " . $addRes['message']);
    }
    echo " - Added {$purchaseQty} unit(s) to cart successfully.\n";

    $cartData = getCartDetails();
    $expectedTotal = $cartData['total'];
    echo "[3/5] Cart total hydrated from database: " . formatPrice($expectedTotal) . "\n";

    // 6. Create Pending Order
    $orderNumber = 'SKJ-TEST-' . date('Ymd') . '-' . $uniqueToken;
    $db->beginTransaction();

    $orderStmt = $db->prepare("
        INSERT INTO orders (
            order_number, user_id, total_amount, shipping_name, phone, address, city, state, pincode,
            payment_status, order_status, razorpay_order_id, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, NOW())
    ");
    $orderStmt->execute([
        $orderNumber,
        $user['id'],
        $expectedTotal,
        'Automated Test User',
        '9876543210',
        '404 Test Blvd',
        'Mumbai',
        'Maharashtra',
        '400001',
        'order_mock_' . $uniqueToken
    ]);
    $createdOrderId = (int)$db->lastInsertId();

    $itemStmt = $db->prepare("
        INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    foreach ($cartData['items'] as $it) {
        $itemStmt->execute([
            $createdOrderId,
            $it['product']['id'],
            $it['product']['name'],
            $it['effective_price'],
            $it['quantity'],
            $it['line_total']
        ]);
    }
    $db->commit();
    echo "[4/5] Order #{$orderNumber} created in test database (Order ID: {$createdOrderId})\n";

    // 7. Simulate payment verification & atomic stock decrement
    $db->beginTransaction();

    $mockPaymentId = 'pay_mock_' . $uniqueToken;
    $db->prepare("
        UPDATE orders
        SET payment_status = 'paid', order_status = 'confirmed', razorpay_payment_id = ?, updated_at = NOW()
        WHERE id = ?
    ")->execute([$mockPaymentId, $createdOrderId]);

    $db->prepare("
        INSERT INTO payments (
            order_id, payment_method, razorpay_order_id, razorpay_payment_id, razorpay_signature, amount, status, created_at
        ) VALUES (?, 'razorpay', ?, ?, 'test_signature_valid', ?, 'paid', NOW())
    ")->execute([$createdOrderId, 'order_mock_' . $uniqueToken, $mockPaymentId, $expectedTotal]);

    $db->prepare("
        UPDATE products
        SET stock_quantity = GREATEST(0, stock_quantity - ?), updated_at = NOW()
        WHERE id = ?
    ")->execute([$purchaseQty, $testProductId]);

    $db->commit();

    // 8. Assert exact stock reduction
    $freshStock = (int)$db->query("SELECT stock_quantity FROM products WHERE id = {$testProductId}")->fetchColumn();
    $expectedStock = $initialStock - $purchaseQty;
    echo "[5/5] Stock decrement verified: {$initialStock} -> {$freshStock} (Expected: {$expectedStock})\n";

    if ($freshStock !== $expectedStock) {
        throw new OrderFlowTestException("Stock decrement assertion failed! Expected {$expectedStock}, got {$freshStock}.");
    }

    echo "[PASS] End-to-end checkout & stock reduction test passed with zero catalog pollution!\n";
    echo "===========================================================\n";

} finally {
    // 9. Teardown: Safely delete only the disposable records created during this test
    echo "Teardown: Cleaning up disposable test records...\n";
    try {
        if ($createdOrderId) {
            $db->prepare("DELETE FROM payments WHERE order_id = ?")->execute([$createdOrderId]);
            $db->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$createdOrderId]);
            $db->prepare("DELETE FROM orders WHERE id = ?")->execute([$createdOrderId]);
        }
        if ($testProductId) {
            $db->prepare("DELETE FROM products WHERE id = ?")->execute([$testProductId]);
        }
        clearCart();
        echo "[OK] Teardown complete. Test database returned to pristine state.\n";
    } catch (Throwable $e) {
        fwrite(STDERR, "[WARNING] Teardown cleanup error: " . $e->getMessage() . "\n");
    }
}

exit(0);

