<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Admin Authentication & Back-Office Guard Middleware
 */

require_once __DIR__ . '/../helpers/functions.php';

/**
 * Check if an administrator is currently logged in
 *
 * @return bool
 */
function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Retrieve current logged-in admin user record
 *
 * @return array|null
 */
function getCurrentAdmin(): ?array {
    if (!isAdminLoggedIn()) {
        return null;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, username, email, full_name, created_at FROM admins WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();
        return $admin ?: null;
    } catch (PDOException $e) {
        error_log("Error retrieving admin user: " . $e->getMessage());
        return null;
    }
}

/**
 * Enforce admin authentication on back-office routes
 */
function requireAdminLogin(): void {
    if (!isAdminLoggedIn()) {
        setFlash('error', 'Administrator authentication required.');
        redirect(ADMIN_URL . '/login.php');
    }
}

/**
 * Log in administrator and regenerate session ID
 *
 * @param array $admin
 */
function loginAdmin(array $admin): void {
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int)$admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_name'] = $admin['full_name'];
}

/**
 * Log out administrator
 */
function logoutAdmin(): void {
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_name']);
    session_regenerate_id(true);
}
