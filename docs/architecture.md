# SHIVKRUPA ART JEWELLERY — System Architecture & Technical Specifications

This document outlines the professional architectural design, directory roles, security controls, and data flow of **SHIVKRUPA ART JEWELLERY**.

---

## 1. Architectural Overview

The application follows a **Modular Layered Architecture** with strict separation between public entry points, private application logic, administration portals, configuration, and data persistence:

```
[ HTTP Request ]
       │
       ▼
[ Web Server: Apache / Nginx / Built-in CLI Router ]
       │
       ├─► /admin/*            ──► admin/ (Back-Office Portal)
       ├─► /storage/uploads/*  ──► storage/uploads/products/ (Authoritative Media)
       ├─► /app, /config, etc. ──► 403 Forbidden (Blocked)
       └─► /*                  ──► public/* (Customer-Facing Storefront)
                                      │
                                      ▼
                        [ app/ - Application Core ]
                        ├─ app/components/  (UI Views & Templates)
                        ├─ app/middleware/  (Auth, Admin-Auth, CSRF)
                        ├─ app/services/    (Cart, Stock Validation)
                        └─ app/helpers/     (Sanitization, Formatting)
                                      │
                                      ▼
                        [ config/ - Environment & Secrets ]
                        ├─ config/config.php   (Constants, Base URLs)
                        └─ config/database.php (PDO Connection & Failover)
                                      │
                                      ▼
                        [ MariaDB / MySQL Database ]
```

---

## 2. Directory Responsibilities

| Directory | Layer / Responsibility | Security Policy |
| :--- | :--- | :--- |
| `public/` | Public DocumentRoot containing all customer storefront entry points (`index.php`, `products.php`, `cart.php`, `checkout.php`, etc.) and public static assets (`public/assets/css`, `js`, `images`). | Direct HTTP access allowed. Only customer-facing scripts and static assets. |
| `admin/` | Dedicated management portal for store administrators (`dashboard.php`, `products.php`, `orders.php`, etc.). | Protected by `admin-auth.php` session middleware. Direct access permitted via web server alias or rewrite. |
| `app/` | Core application business logic and components: <br>• `components/`: `header.php`, `footer.php`, `navbar.php`, `product-card.php`<br>• `helpers/`: `functions.php`<br>• `middleware/`: `auth.php`, `admin-auth.php`, `csrf.php`<br>• `services/`: `cart.php` | **Strictly Private.** Web server rules (Apache `.htaccess` and `router.php`) return **`403 Forbidden`** on direct HTTP requests. |
| `config/` | Application bootstrapping and database credentials: `config.php`, `database.php`. | **Strictly Private (`403 Forbidden`).** |
| `database/` | SQL schema definitions (`database.sql`) and CLI installation scripts (`setup-database.php`). | **Strictly Private (`403 Forbidden`).** Setup script validates `DB_NAME` via `^[a-zA-Z0-9_]{1,64}$`. |
| `storage/` | Authoritative persistent storage: `storage/uploads/products/` contains uploaded product photographs. | Media served under `/storage/uploads/`. `.htaccess` inside `storage/uploads/` disables PHP execution. |
| `tests/` | Automated security audits (`security_audit.php`) and end-to-end integration tests (`test_order_flow.php`). | **Strictly Private (`403 Forbidden`).** Tests require explicit `shivkrupa_test_db` allowlist before running. |
| `scripts/` | Developer CLI tools, e.g. `generate-assets.php`. | **Strictly Private (`403 Forbidden`).** |
| `docs/` | Technical documentation and architecture specifications. | Documentation only. |

---

## 3. Storage & Upload Architecture

- **Authoritative Storage Path:** `storage/uploads/products/`
- **Configuration Constants:**
  - `STORAGE_PATH`: `<ROOT_PATH>/storage`
  - `UPLOAD_DIR`: `<ROOT_PATH>/storage/uploads/products/`
  - `UPLOAD_URL`: `<BASE_URL>/storage/uploads/products/`
- **Upload Security:**
  1. Maximum file size: `2 MB`.
  2. Strict extension check: `.jpg`, `.jpeg`, `.png`, `.webp`.
  3. Binary MIME inspection via PHP `finfo_file(FILEINFO_MIME_TYPE)`.
  4. Script execution disabled via `.htaccess` in `storage/uploads/`:
     ```apache
     <FilesMatch "\.(php|phtml|php3|php4|php5|php7|phps|cgi|pl|exe)$">
         Require all denied
     </FilesMatch>
     ```

---

## 4. Multi-Environment Web Server Configuration

### A. PHP Built-In Development Server
When running locally with `php -S 127.0.0.1:8000 router.php`:
- `router.php` inspects the request URI.
- Direct accesses to `app/`, `config/`, `database/`, `tests/`, `scripts/`, or dotfiles return HTTP `403`.
- Customer requests (`/`, `/products.php`, etc.) route directly to `public/`.
- Admin requests (`/admin/...`) route to `admin/`.
- Media requests (`/storage/uploads/...`) serve directly from `storage/uploads/`.

### B. Apache / XAMPP VirtualHost & .htaccess
When deploying to an Apache/XAMPP server:
1. Set the Apache `DocumentRoot` to the project root, or access via `http://localhost/shivkrupa-art-jewellery/`.
2. The root `.htaccess` handles customer routing to `public/`, leaves `/admin` accessible, and protects private directories:
```apache
RewriteEngine On
RewriteRule ^(app|config|database|tests|scripts|docs)/.*$ - [F,L]
RewriteRule ^admin(/.*)?$ - [L]
RewriteRule ^public(/.*)?$ - [L]
RewriteRule ^$ public/index.php [L]
RewriteRule ^((?!public/|admin/).*)$ public/$1 [L]
```
3. In production virtual hosts where `DocumentRoot` is set directly to `/path/to/public`, an Apache `Alias /admin /path/to/admin` and `Alias /storage/uploads /path/to/storage/uploads` can be used.

---

## 5. Security & Test Protection Controls

1. **Test Database Allowlist:**
   - Tests in `tests/test_order_flow.php` strictly require `$targetDb === 'shivkrupa_test_db'`.
   - Any attempt to execute mutating tests against `shivkrupa_db` or production fails fast with exit code 1.
2. **Database Identifier Sanitization:**
   - In `database/setup-database.php`, `DB_NAME` is validated against `^[a-zA-Z0-9_]{1,64}$`.
   - Special characters, SQL comments, or statement injection strings abort execution immediately.
3. **Session & CSRF Protection:**
   - Session cookies configured with `HttpOnly`, `SameSite=Lax`, and `use_only_cookies`.
   - All state-altering POST forms require `csrfField()` and validate via `verifyCsrfToken()` with `hash_equals()`.
