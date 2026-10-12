<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Cross-Site Request Forgery (CSRF) Protection
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate or retrieve the existing CSRF token
 *
 * @return string
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden CSRF input field for HTML forms
 *
 * @return string
 */
function csrfField(): string {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verify CSRF token from a POST request
 *
 * @param string|null $token
 * @return bool
 */
function verifyCsrfToken(?string $token = null): bool {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }

    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Require valid CSRF token, terminate with error if invalid
 */
function validateCsrfOrDie(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrfToken()) {
            http_response_code(403);
            die("
                <div style='font-family:sans-serif;max-width:500px;margin:50px auto;padding:20px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:6px;'>
                    <h3>Security Validation Failed (403 Forbidden)</h3>
                    <p>Invalid or expired CSRF security token. Please refresh the page and try submitting again.</p>
                    <a href='javascript:history.back()' style='color:#842029;font-weight:bold;'>Go Back</a>
                </div>
            ");
        }
    }
}
