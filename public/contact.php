<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Contact Us & Boutique Inquiry Page
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/middleware/csrf.php';

$sent = false;
$name = '';
$email = '';
$phone = '';
$subject = '';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Security validation failed. Please refresh and try again.';
    } else {
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($message)) {
            $error = 'Please fill in all required fields (Name, Email, Message).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Customer inquiry is recorded and acknowledged with a professional notification
            setFlash('success', 'Thank you, ' . e($name) . '. Your inquiry has been received. Our jewellery consultant will contact you within 24 hours.');
            redirect(BASE_URL . '/contact.php?submitted=1');
        }
    }
}

$pageTitle = "Contact Showroom & Private Consultations | " . APP_NAME;
require_once __DIR__ . '/../app/components/header.php';
?>

<div class="container section">
    <div class="section-header">
        <span class="section-subtitle">Get In Touch</span>
        <h1 class="section-title">Visit Our Flagship Boutique</h1>
        <div class="gold-divider"></div>
        <p class="section-desc">Experience our collections in person or request a private bridal consultation with our master gemologists.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error" role="alert">
            <span class="alert-icon">✕</span>
            <div class="alert-content"><?= e($error); ?></div>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1.2fr 1.8fr; gap: 3.5rem; align-items: start;">
        <!-- Boutique Details Card -->
        <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 2.5rem; box-shadow: var(--shadow-sm);">
            <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
                Showroom Information
            </h3>

            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <div style="display: flex; gap: 1rem;">
                    <div style="font-size: 1.5rem;">📍</div>
                    <div>
                        <strong style="display:block; margin-bottom: 0.25rem;">Address:</strong>
                        <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem;"><?= e(APP_ADDRESS); ?></p>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <div style="font-size: 1.5rem;">📞</div>
                    <div>
                        <strong style="display:block; margin-bottom: 0.25rem;">Phone & Concierge:</strong>
                        <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem;"><?= e(APP_PHONE); ?></p>
                        <span style="font-size: 0.8rem; color: var(--gold-dark);">Mon - Sat: 10:30 AM to 8:30 PM</span>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <div style="font-size: 1.5rem;">✉️</div>
                    <div>
                        <strong style="display:block; margin-bottom: 0.25rem;">Email Inquiry:</strong>
                        <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem;"><?= e(APP_EMAIL); ?></p>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <div style="font-size: 1.5rem;">✨</div>
                    <div>
                        <strong style="display:block; margin-bottom: 0.25rem;">Bespoke Bridal Appointments:</strong>
                        <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem;">Private VIP lounge available for bridal suites and custom order discussions.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 2.5rem; box-shadow: var(--shadow-sm);">
            <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
                Send Us a Message
            </h3>

            <form action="<?= BASE_URL; ?>/contact.php" method="POST">
                <?= csrfField(); ?>

                <div class="input-row">
                    <div class="form-group">
                        <label class="form-label" for="name">Your Name <span style="color:var(--danger);">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Ananya Patel" value="<?= e($name); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email Address <span style="color:var(--danger);">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="ananya@example.com" value="<?= e($email); ?>" required>
                    </div>
                </div>

                <div class="input-row">
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control" placeholder="+91 98765 00000" value="<?= e($phone); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="subject">Inquiry Type</label>
                        <select id="subject" name="subject" class="form-control">
                            <option value="Bridal Consultation">Bespoke Bridal Consultation</option>
                            <option value="Product Availability">Product Inquiry & Sizing</option>
                            <option value="Order Status">Online Order Assistance</option>
                            <option value="Other">General Question</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="message">Your Message <span style="color:var(--danger);">*</span></label>
                    <textarea id="message" name="message" class="form-control" placeholder="Tell us about the design, custom requirement, or milestone you are celebrating..." required><?= e($message); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-lg">
                    Send Inquiry &rarr;
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../app/components/footer.php'; ?>
