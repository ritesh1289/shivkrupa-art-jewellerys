<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Global Navbar Component
 */
$currentScript = basename($_SERVER['PHP_SELF']);
?>
<header class="site-header">
    <div class="container">
        <div class="nav-wrapper">
            <!-- Mobile Menu Toggle Button -->
            <button class="mobile-menu-toggle" aria-label="Toggle navigation menu" aria-expanded="false">&#9776;</button>

            <!-- Brand Logo -->
            <a href="<?= BASE_URL; ?>/index.php" class="brand-logo">
                <span class="brand-name">SHIVKRUPA</span>
                <span class="brand-tag">ART JEWELLERY</span>
            </a>

            <!-- Navigation Links -->
            <nav class="nav-links" id="primary-navigation">
                <a href="<?= BASE_URL; ?>/index.php" class="nav-item <?= $currentScript === 'index.php' ? 'active' : ''; ?>">Home</a>
                <a href="<?= BASE_URL; ?>/products.php" class="nav-item <?= $currentScript === 'products.php' ? 'active' : ''; ?>">All Jewellery</a>
                <a href="<?= BASE_URL; ?>/categories.php" class="nav-item <?= $currentScript === 'categories.php' ? 'active' : ''; ?>">Collections</a>
                <a href="<?= BASE_URL; ?>/about.php" class="nav-item <?= $currentScript === 'about.php' ? 'active' : ''; ?>">Our Story</a>
                <a href="<?= BASE_URL; ?>/contact.php" class="nav-item <?= $currentScript === 'contact.php' ? 'active' : ''; ?>">Contact</a>
            </nav>

            <!-- Navigation Actions (Search, Cart, User) -->
            <div class="nav-actions">
                <!-- Search Button / Quick Link -->
                <a href="<?= BASE_URL; ?>/products.php" class="nav-action-btn" title="Search Jewellery" aria-label="Search Catalogue">
                    🔍
                </a>

                <!-- Shopping Cart Icon with Live Item Counter -->
                <a href="<?= BASE_URL; ?>/cart.php" class="nav-action-btn" title="Shopping Bag" aria-label="View Shopping Bag">
                    🛍️
                    <span class="cart-badge" style="<?= ($cartItemCount ?? 0) > 0 ? '' : 'display:none;'; ?>">
                        <?= $cartItemCount ?? 0; ?>
                    </span>
                </a>

                <!-- User Profile & Authentication Dropdown -->
                <div class="user-dropdown">
                    <button class="nav-action-btn user-menu-trigger" type="button" aria-label="User Account">
                        👤
                    </button>
                    <div class="user-dropdown-menu">
                        <?php if (isLoggedIn() && $currentUser): ?>
                            <div class="user-dropdown-header">
                                <span>Welcome,</span>
                                <strong><?= e($currentUser['full_name']); ?></strong>
                            </div>
                            <a href="<?= BASE_URL; ?>/profile.php" class="user-dropdown-item">👤 My Profile</a>
                            <a href="<?= BASE_URL; ?>/orders.php" class="user-dropdown-item">📦 My Orders</a>
                            <div style="border-top: 1px solid var(--border-color); margin: 0.35rem 0;"></div>
                            <a href="<?= BASE_URL; ?>/logout.php" class="user-dropdown-item" style="color: var(--danger);">🚪 Sign Out</a>
                        <?php else: ?>
                            <div class="user-dropdown-header">
                                <strong>Customer Account</strong>
                            </div>
                            <a href="<?= BASE_URL; ?>/login.php" class="user-dropdown-item">🔑 Sign In</a>
                            <a href="<?= BASE_URL; ?>/register.php" class="user-dropdown-item">✨ Create Account</a>
                        <?php endif; ?>
                        <div style="border-top: 1px solid var(--border-color); margin: 0.35rem 0;"></div>
                        <a href="<?= ADMIN_URL; ?>/login.php" class="user-dropdown-item" style="font-size: 0.82rem; color: var(--gold-dark);" target="_blank">
                            🛡️ Staff Admin Portal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
