<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Razorpay Payment Callback & Server-Side Signature Verification Handler
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';
require_once dirname(__DIR__) . '/app/middleware/csrf.php';
require_once dirname(__DIR__) . '/app/middleware/auth.php';
require_once dirname(__DIR__) . '/app/services/cart.php';

// Enforce login
requireLogin();

$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/index.php');
}

// Enforce CSRF validation
if (!verifyCsrfToken()) {
    setFlash('error', 'Security validation token expired. Please return to checkout.');
    redirect(BASE_URL . '/checkout.php');
}

$orderId           = (int)($_POST['order_id'] ?? 0);
$razorpayOrderId   = trim($_POST['razorpay_order_id'] ?? '');
$razorpayPaymentId = trim($_POST['razorpay_payment_id'] ?? '');
$razorpaySignature = trim($_POST['razorpay_signature'] ?? '');

if ($orderId <= 0 || empty($razorpayOrderId) || empty($razorpayPaymentId)) {
    setFlash('error', 'Incomplete payment response received.');
    redirect(BASE_URL . '/checkout.php');
}

$db = getDB();

// 1. Fetch the order record (Ensure ownership)
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$orderId, $currentUser['id']]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Order not found.');
    redirect(BASE_URL . '/orders.php');
}

// 2. Idempotency Check: if order is already marked paid, avoid duplicate stock decrement
if ($order['payment_status'] === 'paid') {
    clearCart();
    redirect(BASE_URL . '/payment-success.php?order_id=' . $orderId);
}

// 3. Cryptographic Signature Verification
// Official Razorpay logic: SHA256 HMAC of (order_id + "|" + payment_id) with key_secret
$expectedSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, RAZORPAY_KEY_SECRET);

$isValid = false;

// Check standard HMAC signature using timing-attack-safe hash_equals
if (!empty($razorpaySignature) && hash_equals($expectedSignature, $razorpaySignature)) {
    $isValid = true;
}

// Support test mode simulated button for automated testing and sandbox previews
if (RAZORPAY_TEST_MODE && $razorpaySignature === 'simulated_valid_test_signature') {
    $isValid = true;
}

if ($isValid) {
    // ==========================================================
    // PAYMENT VERIFIED: Process Order & Decrement Stock Atomically
    // ==========================================================
    try {
        $db->beginTransaction();

        // A. Update Order Status
        $updOrder = $db->prepare("
            UPDATE orders 
            SET payment_status = 'paid', order_status = 'confirmed', 
                razorpay_order_id = ?, razorpay_payment_id = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $updOrder->execute([$razorpayOrderId, $razorpayPaymentId, $orderId]);

        // B. Log Audit Transaction in payments Table
        $insPay = $db->prepare("
            INSERT INTO payments (
                order_id, payment_method, razorpay_order_id, razorpay_payment_id, 
                razorpay_signature, amount, status, created_at
            ) VALUES (?, 'razorpay', ?, ?, ?, ?, 'paid', NOW())
        ");
        $insPay->execute([
            $orderId,
            $razorpayOrderId,
            $razorpayPaymentId,
            $razorpaySignature,
            $order['total_amount']
        ]);

        // C. Decrement Product Stock Atomically
        $itemsStmt = $db->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $itemsStmt->execute([$orderId]);
        $orderedItems = $itemsStmt->fetchAll();

        $stockStmt = $db->prepare("
            UPDATE products 
            SET stock_quantity = GREATEST(0, stock_quantity - ?),
                status = CASE WHEN stock_quantity - ? <= 0 THEN 'out_of_stock' ELSE status END,
                updated_at = NOW()
            WHERE id = ?
        ");

        foreach ($orderedItems as $it) {
            $qty = (int)$it['quantity'];
            $stockStmt->execute([$qty, $qty, $it['product_id']]);
        }

        $db->commit();

        // D. Clear Customer's Cart Session
        clearCart();
        unset($_SESSION['checkout_order_id']);
        unset($_SESSION['checkout_order_number']);

        // Redirect to confirmation screen
        redirect(BASE_URL . '/payment-success.php?order_id=' . $orderId);

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Order settlement error: " . $e->getMessage());
        setFlash('error', 'Payment succeeded but error settling order. Please contact support.');
        redirect(BASE_URL . '/orders.php');
    }

} else {
    // Signature failed or was forged
    error_log("Razorpay signature verification mismatch for order #{$orderId}.");
    
    $failStmt = $db->prepare("UPDATE orders SET payment_status = 'failed', updated_at = NOW() WHERE id = ?");
    $failStmt->execute([$orderId]);

    redirect(BASE_URL . '/payment-failed.php?order_id=' . $orderId);
}
