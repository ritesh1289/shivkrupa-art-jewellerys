<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * About Us & Heritage Page
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/components/header.php';

$pageTitle = "Our Heritage & Craftsmanship | " . APP_NAME;
?>

<!-- Hero Header -->
<section style="background: linear-gradient(135deg, #1A1715 0%, #12100E 100%); color: #FFF; padding: 4.5rem 0; text-align: center;">
    <div class="container" style="max-width: 800px;">
        <span class="section-subtitle" style="color: var(--gold-light);">Established in 1998</span>
        <h1 style="color: #FFF; font-size: 3rem; margin-bottom: 1rem;">The Art of Indian Goldsmithing</h1>
        <div class="gold-divider"></div>
        <p style="color: #D3C9BE; font-size: 1.15rem; line-height: 1.8;">
            For over two decades, Shivkrupa Art Jewellery has stood at the crossroads of royal tradition and modern refinement, creating treasured heirlooms that celebrate the sacred milestones of life.
        </p>
    </div>
</section>

<!-- Story Content -->
<div class="container section">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: center; margin-bottom: 5rem;">
        <div>
            <span class="section-subtitle">Our Heritage</span>
            <h2 style="font-size: 2.2rem; margin-bottom: 1.25rem;">Born in Mumbai's Historic Zaveri Bazaar</h2>
            <p style="color: var(--text-muted); line-height: 1.8; margin-bottom: 1.25rem;">
                Shivkrupa was established with a singular vision: to preserve the intricate regional craftsmanship of India while providing contemporary Indian women with certified, trustworthy fine jewellery.
            </p>
            <p style="color: var(--text-muted); line-height: 1.8;">
                From the royal courtly traditions of Rajasthan's Jadau and Kundan to the sacred motifs of South Indian temple jewellery, each creation is meticulously shaped by hereditary karigars (master craftsmen) whose skills have been passed down through generations.
            </p>
        </div>
        <div style="border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-lg); border: 1px solid var(--border-color); aspect-ratio: 4/3;">
            <img src="<?= BASE_URL; ?>/assets/images/hero-jewellery.jpg" alt="Artisan Goldsmithing" style="width:100%; height:100%; object-fit:cover;">
        </div>
    </div>

    <!-- Purity Pillars -->
    <div class="section-header">
        <span class="section-subtitle">Our Guarantees</span>
        <h2 class="section-title">The Four Pillars of Shivkrupa</h2>
        <div class="gold-divider"></div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
        <div style="background: var(--bg-surface); padding: 2rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); text-align: center;">
            <div style="font-size: 2.5rem; margin-bottom: 1rem;">👑</div>
            <h3 style="font-size: 1.3rem; margin-bottom: 0.5rem;">BIS 916 Hallmarked</h3>
            <p style="font-size: 0.9rem; color: var(--text-muted);">Every piece of gold jewellery carries the official Government of India hallmarking certifying purity.</p>
        </div>

        <div style="background: var(--bg-surface); padding: 2rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); text-align: center;">
            <div style="font-size: 2.5rem; margin-bottom: 1rem;">💎</div>
            <h3 style="font-size: 1.3rem; margin-bottom: 0.5rem;">Certified Diamonds</h3>
            <p style="font-size: 0.9rem; color: var(--text-muted);">All natural solitaires and pavé diamonds are backed by rigorous international gemological certification.</p>
        </div>

        <div style="background: var(--bg-surface); padding: 2rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); text-align: center;">
            <div style="font-size: 2.5rem; margin-bottom: 1rem;">🎨</div>
            <h3 style="font-size: 1.3rem; margin-bottom: 0.5rem;">Ethical Sourcing</h3>
            <p style="font-size: 0.9rem; color: var(--text-muted);">Committed to conflict-free metals, fair trade artisan partnerships, and environmentally conscious refining.</p>
        </div>

        <div style="background: var(--bg-surface); padding: 2rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); text-align: center;">
            <div style="font-size: 2.5rem; margin-bottom: 1rem;">🛡️</div>
            <h3 style="font-size: 1.3rem; margin-bottom: 0.5rem;">Lifetime Assurance</h3>
            <p style="font-size: 0.9rem; color: var(--text-muted);">Complimentary lifetime ultrasonic cleaning, prong inspection, and buyback valuation guarantee.</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../app/components/footer.php'; ?>
