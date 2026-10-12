<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Global Helper Functions & Utilities
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * Sanitize string data against XSS
 *
 * @param mixed $data
 * @return string
 */
function sanitize($data): string {
    if (is_array($data)) {
        return '';
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Safe output helper for templates
 *
 * @param mixed $data
 * @return string
 */
function e($data): string {
    return sanitize($data);
}

/**
 * Format currency in Indian Rupees format (e.g., ₹24,999.00)
 *
 * @param float|int|string $amount
 * @return string
 */
function formatPrice($amount): string {
    $num = (float)$amount;
    return CURRENCY_SYMBOL . ' ' . number_format($num, 2);
}

/**
 * Generate a URL-friendly slug
 *
 * @param string $text
 * @return string
 */
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

/**
 * Set a session flash message
 *
 * @param string $type ('success', 'error', 'warning', 'info')
 * @param string $message
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear the active flash message
 *
 * @return array|null
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render flash message as an HTML alert banner
 */
function displayFlash(): void {
    $flash = getFlash();
    if ($flash) {
        $type = e($flash['type']);
        $msg = e($flash['message']);
        $icon = match($type) {
            'success' => '✓',
            'error'   => '✕',
            'warning' => '⚠',
            default   => 'ℹ'
        };
        echo "
        <div class='alert alert-{$type} fade-in' role='alert'>
            <span class='alert-icon'>{$icon}</span>
            <div class='alert-content'>{$msg}</div>
            <button type='button' class='alert-close' onclick='this.parentElement.remove()'>&times;</button>
        </div>";
    }
}

/**
 * Safe redirect to a URL
 *
 * @param string $url
 */
function redirect(string $url): void {
    header("Location: " . $url);
    exit;
}

/**
 * Truncate long text with ellipsis
 *
 * @param string $text
 * @param int $length
 * @return string
 */
function truncate(string $text, int $length = 100): string {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . '...';
}

/**
 * Calculate percentage discount
 *
 * @param float $originalPrice
 * @param float $discountPrice
 * @return int
 */
function calculateDiscountPercent(float $originalPrice, float $discountPrice): int {
    if ($originalPrice <= 0 || $discountPrice >= $originalPrice) {
        return 0;
    }
    return (int)round((($originalPrice - $discountPrice) / $originalPrice) * 100);
}
