<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Global Application Configuration & Session Setup
 */

// Start session securely if not already started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Brand Information
define('APP_NAME', 'SHIVKRUPA ART JEWELLERY');
define('APP_TAGLINE', 'Timeless Elegance, Exquisite Craftsmanship');
define('APP_YEAR', date('Y'));
define('APP_PHONE', '+91 98765 43210');
define('APP_EMAIL', 'support@shivkrupa-jewellery.com');
define('APP_ADDRESS', '402, Zaveri Bazaar, Kalbadevi, Mumbai, Maharashtra 400002');

// Currency & Formatting
define('CURRENCY_CODE', 'INR');
define('CURRENCY_SYMBOL', '₹');

// Razorpay Test Mode Configuration
// Prefers environment variables, with safe fallback placeholders for academic presentations
define('RAZORPAY_KEY_ID', getenv('RAZORPAY_KEY_ID') ?: 'rzp_test_shivkrupaMock99');
define('RAZORPAY_KEY_SECRET', getenv('RAZORPAY_KEY_SECRET') ?: 'test_secret_shivkrupaSecure99');
define('RAZORPAY_TEST_MODE', true);

// Dynamic Base URL Detection
$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
    (!empty($_SERVER['HTTP_CF_VISITOR']) && strpos($_SERVER['HTTP_CF_VISITOR'], '"scheme":"https"') !== false)
);
$protocol = $isHttps ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// If within /admin, trim /admin from base URL
$baseDir = preg_replace('#/admin$#', '', $scriptDir);
$baseUrl = rtrim($protocol . $host . $baseDir, '/');

define('BASE_URL', $baseUrl);
define('ADMIN_URL', $baseUrl . '/admin');

// Core paths
define('ROOT_PATH', dirname(__DIR__));
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('UPLOAD_DIR', ROOT_PATH . '/storage/uploads/products/');
define('UPLOAD_URL', BASE_URL . '/storage/uploads/products/');
