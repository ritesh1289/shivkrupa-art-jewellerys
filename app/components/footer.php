<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Global Footer Component
 */
?>
</main> <!-- End #main-content -->

<!-- Trust & Assurances Strip -->
<section class="features-strip">
    <div class="container">
        <div class="features-grid">
            <div class="feature-box">
                <div class="feature-icon">✨</div>
                <div class="feature-text">
                    <h4>100% Certified</h4>
                    <p>BIS 916 Hallmarked Gold and IGI Certified Diamonds.</p>
                </div>
            </div>
            <div class="feature-box">
                <div class="feature-icon">🔒</div>
                <div class="feature-text">
                    <h4>Secure Payments</h4>
                    <p>Encrypted Razorpay Checkout with instant confirmation.</p>
                </div>
            </div>
            <div class="feature-box">
                <div class="feature-icon">🚚</div>
                <div class="feature-text">
                    <h4>Insured Shipping</h4>
                    <p>Complimentary transit insurance on every domestic order.</p>
                </div>
            </div>
            <div class="feature-box">
                <div class="feature-icon">💎</div>
                <div class="feature-text">
                    <h4>Lifetime Polish</h4>
                    <p>Free lifetime ultrasonic cleaning and polishing service.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <h3>SHIVKRUPA ART JEWELLERY</h3>
                <div class="footer-tagline">Timeless Elegance &bull; Exquisite Craftsmanship</div>
                <p>Celebrating over two decades of fine heritage jewellery, bespoke bridal sets, and handcrafted diamond jewels inspired by royal Indian traditions.</p>
                <div style="margin-top: 1.25rem; font-size: 0.9rem;">
                    <p>📍 <?= e(APP_ADDRESS); ?></p>
                    <p>📞 <?= e(APP_PHONE); ?> &bull; ✉️ <?= e(APP_EMAIL); ?></p>
                </div>
            </div>

            <div class="footer-col">
                <h4>Collections</h4>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL; ?>/products.php?category=rings">Rings & Solitaires</a></li>
                    <li><a href="<?= BASE_URL; ?>/products.php?category=earrings">Earrings & Jhumkas</a></li>
                    <li><a href="<?= BASE_URL; ?>/products.php?category=necklaces">Bridal Necklaces</a></li>
                    <li><a href="<?= BASE_URL; ?>/products.php?category=bangles">Bangles & Kadas</a></li>
                    <li><a href="<?= BASE_URL; ?>/products.php?category=jewellery-sets">Complete Sets</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Customer Care</h4>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL; ?>/orders.php">Track Order Status</a></li>
                    <li><a href="<?= BASE_URL; ?>/profile.php">My Account</a></li>
                    <li><a href="<?= BASE_URL; ?>/cart.php">Shopping Bag</a></li>
                    <li><a href="<?= BASE_URL; ?>/about.php">Our Artisans & Story</a></li>
                    <li><a href="<?= BASE_URL; ?>/contact.php">Contact & Showroom</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Authenticity & Trust</h4>
                <p style="font-size: 0.88rem; color: #AFA69D; margin-bottom: 1rem;">
                    Every handcrafted ornament comes with genuine BIS 916 hallmarking and guaranteed purity certification.
                </p>
                <div style="background: rgba(255,255,255,0.06); padding: 1rem; border-radius: var(--radius-sm); border-left: 3px solid var(--gold-primary); font-size: 0.82rem;">
                    <strong>Customer Assurance:</strong> 100% Certified Gold &bull; Secure Payments &bull; Insured Delivery
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div>
                &copy; <?= date('Y'); ?> <?= e(APP_NAME); ?>. All rights reserved.
            </div>
            <div>
                <span>Handcrafted Indian Luxury &bull; Heritage Jewellery Boutique</span>
            </div>
        </div>
    </div>
</footer>

<!-- Toast Notifications Container -->
<div id="toast-container"></div>

<!-- Master JavaScript -->
<script src="<?= BASE_URL; ?>/assets/js/main.js"></script>
<?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
