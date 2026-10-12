<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Global HTML Head & Topbar Partials
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../middleware/csrf.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../services/cart.php';

$pageTitle = $pageTitle ?? APP_NAME . ' | ' . APP_TAGLINE;
$cartItemCount = getCartCount();
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= generateCsrfToken(); ?>">
    <title><?= e($pageTitle); ?></title>
    <meta name="description" content="Shivkrupa Art Jewellery - Handcrafted Gold, Polki, Kundan, and Diamond Jewellery for bridal and festive elegance.">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/style.css">
    
    <!-- Favicon placeholder / SVG -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💎</text></svg>">
</head>
<body>

<!-- Announcement Bar -->
<div class="top-bar">
    <span>✨ Complimentary Insured Shipping Across India &bull; Certified BIS 916 Hallmarked Jewellery ✨</span>
</div>

<?php require_once __DIR__ . '/navbar.php'; ?>

<main id="main-content">
<div class="container" style="margin-top: 1.5rem;">
    <?php displayFlash(); ?>
</div>
