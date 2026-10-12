/**
 * SHIVKRUPA ART JEWELLERY — Master JavaScript
 * Vanilla ES6+ interactions for navigation, cart, toasts, and UI helpers
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Menu Drawer Toggle
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const navLinks = document.querySelector('.nav-links');

    if (mobileToggle && navLinks) {
        mobileToggle.addEventListener('click', () => {
            const isOpen = navLinks.classList.contains('mobile-active');
            if (isOpen) {
                navLinks.classList.remove('mobile-active');
                mobileToggle.setAttribute('aria-expanded', 'false');
                mobileToggle.innerHTML = '&#9776;'; // hamburger icon
            } else {
                navLinks.classList.add('mobile-active');
                mobileToggle.setAttribute('aria-expanded', 'true');
                mobileToggle.innerHTML = '&times;'; // close icon
            }
        });
    }

    // 2. User Profile Dropdown for Touch / Click
    const userDropdown = document.querySelector('.user-dropdown');
    if (userDropdown) {
        const toggleBtn = userDropdown.querySelector('.user-menu-trigger');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                userDropdown.classList.toggle('open');
            });
            document.addEventListener('click', () => {
                userDropdown.classList.remove('open');
            });
        }
    }

    // 3. Password Visibility Toggles
    const togglePasswordBtns = document.querySelectorAll('.toggle-password-btn');
    togglePasswordBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (input) {
                if (input.type === 'password') {
                    input.type = 'text';
                    btn.textContent = 'Hide';
                } else {
                    input.type = 'password';
                    btn.textContent = 'Show';
                }
            }
        });
    });

    // 4. Auto-dismiss alerts after 6 seconds
    const autoAlerts = document.querySelectorAll('.alert');
    autoAlerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 6000);
    });
});

/**
 * Global Toast Notification Dispatcher
 *
 * @param {string} message 
 * @param {string} type ('success' | 'error' | 'warning' | 'info')
 */
function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };

    toast.innerHTML = `
        <span style="font-weight:bold;font-size:1.1rem;">${icons[type] || 'ℹ'}</span>
        <div style="flex:1;">${message}</div>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.transition = 'all 0.3s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

/**
 * Asynchronous Add To Cart function
 *
 * @param {number} productId 
 * @param {number} quantity 
 * @param {string} baseUrl
 */
async function handleAddToCart(productId, quantity = 1, baseUrl = '') {
    try {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', productId);
        formData.append('quantity', quantity);
        if (csrfToken) {
            formData.append('csrf_token', csrfToken);
        }

        const response = await fetch(`${baseUrl}/cart.php?ajax=1`, {
            method: 'POST',
            headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');
            // Update cart badges
            const badges = document.querySelectorAll('.cart-badge');
            badges.forEach(b => {
                b.textContent = data.cartCount;
                b.style.display = data.cartCount > 0 ? 'flex' : 'none';
            });
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) {
        console.error("Cart error:", err);
        showToast("Could not update shopping bag. Please try again.", "error");
    }
}
