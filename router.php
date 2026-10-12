<?php
/**
 * SHIVKRUPA ART JEWELLERY — Development Server Router
 * Mirrors Apache .htaccess security rules when using PHP built-in server (`php -S`)
 */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$path = urldecode($uri);

// 1. Block access to sensitive files and dotfiles
if (preg_match('/\.(sql|env|log|md|ini|bak|swp|git.*)$/i', $path) ||
    basename($path) === 'database.sql' ||
    str_starts_with(basename($path), '.env')) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "403 Forbidden: Direct access to sensitive file is prohibited.";
    exit;
}

// 2. Block direct web access to internal PHP libraries and private directories
if (preg_match('#^/(app|config|database|tests|scripts|docs)/#i', $path) ||
    preg_match('#^/storage/uploads/.*\.php$#i', $path)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "403 Forbidden: Direct access to internal directory is prohibited.";
    exit;
}

// 3. Admin requests route directly to /admin
if (str_starts_with($path, '/admin')) {
    $adminFile = __DIR__ . $path;
    if (is_file($adminFile)) {
        return false;
    }
    if (is_dir($adminFile) && is_file($adminFile . '/index.php')) {
        return false;
    }
}

// 4. Serve static assets from public/ (CSS, JS, images, icons)
$publicAsset = __DIR__ . '/public' . $path;
if (is_file($publicAsset) && pathinfo($publicAsset, PATHINFO_EXTENSION) !== 'php') {
    $mimeTypes = [
        'css'  => 'text/css; charset=UTF-8',
        'js'   => 'application/javascript; charset=UTF-8',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf',
    ];
    $ext = strtolower(pathinfo($publicAsset, PATHINFO_EXTENSION));
    $mime = $mimeTypes[$ext] ?? mime_content_type($publicAsset) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    readfile($publicAsset);
    return true;
}

// 5. Route customer-facing requests to public/
$publicCandidate = __DIR__ . '/public' . ($path === '/' ? '/index.php' : $path);
if (is_file($publicCandidate)) {
    if (pathinfo($publicCandidate, PATHINFO_EXTENSION) === 'php') {
        require_once $publicCandidate;
        return true;
    }
    return false;
}

// Default fallback
return false;
