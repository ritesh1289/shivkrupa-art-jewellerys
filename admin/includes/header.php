<?php
/**
 * SHIVKRUPA ART JEWELLERY — Admin Header & Navigation
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/helpers/functions.php';
require_once __DIR__ . '/../../app/middleware/admin-auth.php';

requireAdminLogin();

$currentAdmin = getCurrentAdmin();
$activePage = basename($_SERVER['PHP_SELF']);
$pageTitle = $adminPageTitle ?? 'Admin Portal | ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle); ?></title>
    
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/admin.css">
</head>
<body class="admin-body">

<!-- Sidebar Navigation -->
<aside class="admin-sidebar">
    <div class="admin-sidebar-brand">
        <h2>SHIVKRUPA</h2>
        <span>ADMIN CONSOLE</span>
    </div>

    <nav class="admin-nav">
        <a href="<?= ADMIN_URL; ?>/dashboard.php" class="admin-nav-item <?= $activePage === 'dashboard.php' ? 'active' : ''; ?>">
            <span class="admin-nav-icon">📊</span>
            <span>Dashboard</span>
        </a>

        <a href="<?= ADMIN_URL; ?>/products.php" class="admin-nav-item <?= in_array($activePage, ['products.php', 'edit-product.php']) ? 'active' : ''; ?>">
            <span class="admin-nav-icon">💍</span>
            <span>All Products</span>
        </a>

        <a href="<?= ADMIN_URL; ?>/add-product.php" class="admin-nav-item <?= $activePage === 'add-product.php' ? 'active' : ''; ?>">
            <span class="admin-nav-icon">➕</span>
            <span>Add New Product</span>
        </a>

        <a href="<?= ADMIN_URL; ?>/categories.php" class="admin-nav-item <?= $activePage === 'categories.php' ? 'active' : ''; ?>">
            <span class="admin-nav-icon">📁</span>
            <span>Categories</span>
        </a>

        <a href="<?= ADMIN_URL; ?>/orders.php" class="admin-nav-item <?= in_array($activePage, ['orders.php', 'order-details.php']) ? 'active' : ''; ?>">
            <span class="admin-nav-icon">📦</span>
            <span>Orders Management</span>
        </a>

        <a href="<?= ADMIN_URL; ?>/payments.php" class="admin-nav-item <?= $activePage === 'payments.php' ? 'active' : ''; ?>">
            <span class="admin-nav-icon">💳</span>
            <span>Razorpay Ledger</span>
        </a>

        <a href="<?= ADMIN_URL; ?>/users.php" class="admin-nav-item <?= $activePage === 'users.php' ? 'active' : ''; ?>">
            <span class="admin-nav-icon">👥</span>
            <span>Customers Directory</span>
        </a>

        <div style="border-top: 1px solid rgba(255,255,255,0.08); margin: 0.75rem 0;"></div>

        <a href="<?= BASE_URL; ?>/index.php" target="_blank" class="admin-nav-item">
            <span class="admin-nav-icon">🌐</span>
            <span>View Live Store &nearr;</span>
        </a>

        <a href="<?= ADMIN_URL; ?>/logout.php" class="admin-nav-item" style="color: #F87171;">
            <span class="admin-nav-icon">🚪</span>
            <span>Sign Out</span>
        </a>
    </nav>

    <div class="admin-sidebar-footer">
        Signed in as: <strong style="color: #FFF; display:block;"><?= e($currentAdmin['full_name'] ?? 'Administrator'); ?></strong>
        <span>Role: Super Admin</span>
    </div>
</aside>

<!-- Main Admin Wrapper -->
<div class="admin-main">
    <header class="admin-topbar">
        <div style="font-weight: 600; font-size: 1.1rem; color: var(--gold-dark);">
            ✨ Shivkrupa Management Dashboard
        </div>

        <div style="display: flex; align-items: center; gap: 1rem;">
            <span style="font-size: 0.88rem; color: var(--admin-muted);">
                <?= date('l, d F Y'); ?>
            </span>
            <a href="<?= ADMIN_URL; ?>/logout.php" class="btn btn-sm" style="color: var(--danger); border-color: var(--admin-border);">
                Logout
            </a>
        </div>
    </header>

    <div class="admin-content">
        <?php displayFlash(); ?>
