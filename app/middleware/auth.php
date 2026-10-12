<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Customer Authentication & Route Protection Middleware
 */

require_once __DIR__ . '/../helpers/functions.php';

/**
 * Check if a customer is currently logged in
 *
 * @return bool
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Retrieve the current logged-in customer's details from database
 *
 * @return array|null
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, full_name, email, phone, address, city, state, pincode, created_at FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        return $user ?: null;
    } catch (PDOException $e) {
        error_log("Error retrieving user: " . $e->getMessage());
        return null;
    }
}

/**
 * Require login to access a page. Redirects to login with return URL.
 *
 * @param string|null $redirectUrl
 */
function requireLogin(?string $redirectUrl = null): void {
    if (!isLoggedIn()) {
        $target = $redirectUrl ?? $_SERVER['REQUEST_URI'] ?? 'index.php';
        setFlash('warning', 'Please sign in to your account to continue.');
        redirect(BASE_URL . '/login.php?redirect=' . urlencode($target));
    }
}

/**
 * Require guest state (redirect to home or profile if already logged in)
 */
function requireGuest(): void {
    if (isLoggedIn()) {
        redirect(BASE_URL . '/profile.php');
    }
}

/**
 * Perform secure login action: stores session & regenerates session ID
 *
 * @param array $user
 */
function loginUser(array $user): void {
    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
}

/**
 * Perform secure customer logout
 */
function logoutUser(): void {
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_email']);
    // Clean session but preserve cart if desired, or destroy completely
    session_regenerate_id(true);
}
