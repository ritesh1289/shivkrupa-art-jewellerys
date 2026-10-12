# SHIVKRUPA ART JEWELLERY — Fine Jewellery E-Commerce Platform

> **A Full-Stack Fine Jewellery E-Commerce Web Application built with pure PHP 8.2+, MySQL (PDO), Vanilla JavaScript, CSS3, and Razorpay Payment Gateway (Test Mode).**

---

## 💎 Table of Contents
1. [Project Overview](#project-overview)
2. [Key Features](#key-features)
3. [Technology Stack](#technology-stack)
4. [Architecture & Security Principles](#architecture--security-principles)
5. [Default Credentials (Demo Access)](#default-credentials-demo-access)
6. [Step-by-Step Installation & XAMPP Setup](#step-by-step-installation--xampp-setup)
7. [Razorpay Payment Gateway Setup](#razorpay-payment-gateway-setup)
8. [Directory & File Structure](#directory--file-structure)
9. [Automated Testing & Security Audit](#automated-testing--security-audit)
10. [Technical Architecture & FAQ Reference](#technical-architecture--faq-reference)

---

## 1. Project Overview
**SHIVKRUPA ART JEWELLERY** is a complete, production-grade e-commerce web platform for handcrafted Indian fine jewellery. 

It allows retail customers to browse handcrafted Indian heritage jewellery (rings, earrings, bridal necklaces, bangles, pendants, chains, suites), search and filter by metal purity and price, manage a shopping bag with inventory validation, securely checkout, make test payments via Razorpay, and track order fulfillment. An independent administrative back-office provides store managers with live KPI dashboards, product catalog CRUD, image uploads, and order dispatch tracking.

---

## 2. Key Features

### 🛍️ Customer Storefront
- **Luxury Aesthetic:** Cream canvas (`#FAF8F5`), antique gold gradients (`#C5A059`), deep charcoal typography, and certified BIS 916 hallmarks.
- **Dynamic Catalogue:** Search by keyword, filter by category slug and metal material, sort by price (Low to High / High to Low) and newest arrivals.
- **Single Product Showcase:** High-resolution product imagery, purity specs, stock badges ("In Stock" / "Low Stock" / "Out of Stock"), interactive quantity controls, and "Buy Now" triggers.
- **Inventory-Validated Shopping Bag:** Session-backed shopping bag with real-time stock boundary checks. Server-side price hydration prevents client-side price tampering.
- **Razorpay Payment Gateway (Test Mode):** End-to-end checkout flow with server-side HMAC-SHA256 cryptographic signature verification, atomic stock decrement, and custom receipt screens.
- **Customer Portal:** Secure registration, login, session regeneration, address book updates, order history, and printable tax invoices.

### 🛡️ Back-Office Admin Portal (`/admin/`)
- **Executive Operations Dashboard:** Real-time database KPI metric cards (Total Paid Revenue, Total Orders, Active Products, Registered Users, Low-Stock Warnings, Recent Activity).
- **Product Inventory Management:** Add, edit, activate/deactivate, and safely delete jewellery items.
- **Secure File Uploads:** Uploaded photography is verified against file size (<= 2MB), file extensions (`.jpg`, `.jpeg`, `.png`, `.webp`), and genuine binary MIME-types (`finfo_file`), saved with randomized names and protected by an uploads `.htaccess` execution blocker.
- **Category Management:** Manage collections and view product distribution per category.
- **Orders & Fulfillment Dispatch:** Search, filter, inspect line items, and transition orders through `pending` &rarr; `confirmed` &rarr; `processing` &rarr; `shipped` &rarr; `delivered`.
- **Payment Audit Ledger:** Detailed log of all captured Razorpay transaction IDs, payment methods, and timestamps.
- **Customer Directory:** Track registered customer contacts, order frequencies, and lifetime values.

---

## 3. Technology Stack

| Layer | Technologies Used | Architectural Rationale |
|---|---|---|
| **Frontend** | HTML5, CSS3, Vanilla ES6+ JS | Zero external dependencies; lightweight and lightning-fast. |
| **Backend** | PHP 8.2+ (Procedural + OOP) | Native session management, PDO database access layer. |
| **Database** | MySQL 8.0 / MariaDB 10.4 (InnoDB) | Foreign keys, cascading actions, transaction support (`BEGIN`, `COMMIT`, `ROLLBACK`). |
| **Payment Gateway** | Razorpay Standard Checkout | Official test mode JS checkout with server-side signature verification. |
| **Security** | BCrypt, CSRF tokens, `htmlspecialchars` | Protection against SQL Injection, XSS, CSRF, and IDOR attacks. |
| **Environment** | XAMPP / Apache / PHP CLI | Universal standard for local development and shared hosting environments. |

---

## 4. Architecture & Security Principles

1. **Zero SQL Injection:** 100% of SQL queries utilize PDO prepared statements with parameter placeholders (`?`). No variables are ever concatenated into raw query strings.
2. **Cross-Site Scripting (XSS) Prevention:** All dynamic outputs rendered into HTML are filtered through `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`.
3. **Cross-Site Request Forgery (CSRF):** Cryptographic 32-byte session tokens are generated and verified on all state-altering POST forms.
4. **Password Hashing:** Native `password_hash($pass, PASSWORD_DEFAULT)` and `password_verify()` using salted BCrypt.
5. **Session Fixation Defense:** `session_regenerate_id(true)` is executed upon user and administrator login.
6. **Insecure Direct Object Reference (IDOR) Defense:** In `order-details.php`, order retrieval queries strictly include `AND user_id = ?` to prevent users from inspecting other customers' orders.
7. **Idempotent Stock Decrement:** Stock is only reduced once upon verified payment signature inside an atomic database transaction.

---

## 5. Default Credentials (Demo Access)

| Role | Username / Email | Password | Access URL |
|---|---|---|---|
| **Store Administrator** | `admin` *(or `admin@shivkrupa.com`)* | `Admin@123` | `http://localhost:8000/admin/login.php` |
| **Test Customer** | `pooja@example.com` | `User@123` | `http://localhost:8000/login.php` |

> *Both login pages include a convenient "Autofill" button for quick review and evaluation.*

---

## 6. Step-by-Step Installation & XAMPP Setup

### Option 1: Running via PHP Built-in Server (Fastest Local Development)
1. Ensure MySQL / MariaDB is started in **XAMPP Control Panel** (or your local MySQL service).
2. Open terminal in the project directory:
   ```bash
   cd C:\Users\JAGRUTI\.gemini\antigravity-ide\scratch\shivkrupa-art-jewellery
   ```
3. Initialize the database and load seed data:
   ```bash
   php database/setup-database.php
   ```
4. Start the development server using the custom security router:
   ```bash
   php -S 127.0.0.1:8000 router.php
   ```
5. Open your browser and navigate to:
   - **Customer Storefront:** [http://127.0.0.1:8000/](http://127.0.0.1:8000/)
   - **Admin Portal:** [http://127.0.0.1:8000/admin/login.php](http://127.0.0.1:8000/admin/login.php)

---

### Option 2: Running via XAMPP Apache (`htdocs`)
1. Copy or symlink the project folder into your XAMPP `htdocs` directory:
   ```powershell
   Copy-Item -Recurse "C:\Users\JAGRUTI\.gemini\antigravity-ide\scratch\shivkrupa-art-jewellery" "C:\xampp\htdocs\shivkrupa-art-jewellery"
   ```
2. Start **Apache** and **MySQL** in XAMPP Control Panel.
3. If database is not yet set up, run `php database/setup-database.php` or import `database/database.sql` in phpMyAdmin (`http://localhost/phpmyadmin/`).
4. Access the store at: **`http://localhost/shivkrupa-art-jewellery/`**
5. Access the admin console at: **`http://localhost/shivkrupa-art-jewellery/admin/`**

> **Note on Port Auto-Detection:** The database layer in `config/database.php` automatically checks **Port 3307** (XAMPP MariaDB) and **Port 3306** (Standard MySQL). If your computer runs both MySQL 8.0 and XAMPP, the application connects without manual port configuration.

---

## 7. Razorpay Payment Gateway Setup

The application is pre-configured with safe test placeholders in `config/config.php`:
```php
define('RAZORPAY_KEY_ID', 'rzp_test_shivkrupaMock99');
define('RAZORPAY_KEY_SECRET', 'test_secret_shivkrupaSecure99');
define('RAZORPAY_TEST_MODE', true);
```

### To use your own Razorpay Test Account:
1. Sign up for free at [https://dashboard.razorpay.com/](https://dashboard.razorpay.com/).
2. Switch to **Test Mode** in the dashboard.
3. Go to **Settings &rarr; API Keys** and click **Generate Test Key**.
4. Paste your `Key ID` and `Key Secret` into `config/config.php`.

### Fast-Track Payment Simulator:
For development, preview, and automated test environments where live external payment gateways are unavailable or sandboxed, the checkout screen includes an instant testing trigger: **"⚡ Fast-Track Test Payment (Instant Confirmation)"**, which executes the exact same server-side verification, transaction recording, and stock reduction flow!

---

## 8. Directory & File Structure

```
shivkrupa-art-jewellery/
├── admin/                     # Administrative Back-Office Portal
│   ├── includes/              # Admin-specific UI header and footer
│   ├── add-product.php        # Product registration with image validation
│   ├── categories.php         # Category classification & integrity check
│   ├── dashboard.php          # Live sales metrics, order counts, stock warnings
│   ├── edit-product.php       # Product update and optional image replacement
│   ├── login.php              # Dedicated staff authentication
│   ├── logout.php             # Staff session termination
│   ├── order-details.php      # Order inspection & fulfillment status control
│   ├── orders.php             # Master order dispatch list with filters
│   ├── payments.php           # Audit ledger of all Razorpay transactions
│   ├── products.php           # Inventory list with status toggles & safe delete
│   └── users.php              # Customer directory with lifetime expenditure
│
├── app/                       # Private Application Core Logic
│   ├── components/            # Reusable UI views (header, footer, navbar, product-card)
│   ├── helpers/               # Global utility functions & formatting
│   ├── middleware/            # Security filters (auth, admin-auth, csrf)
│   └── services/              # Business logic (cart calculations, stock checks)
│
├── config/
│   ├── config.php             # App constants, dynamic base URL, Razorpay keys
│   └── database.php           # Resilient PDO connection with auto-port detection
│
├── database/
│   ├── database.sql           # Normalized MySQL schema & 16 seed products
│   └── setup-database.php     # CLI database installer & seed verification
│
├── docs/                      # Technical Documentation & Architecture Guides
│   ├── architecture.md        # System architecture, data flow & security policies
│   └── technical-reference.md # Technical architecture & developer reference guide
│
├── public/                    # Public Web Document Root & Storefront Entry Points
│   ├── assets/                # Public stylesheets, JS, and collection imagery
│   ├── index.php              # Luxury landing page & hero showcases
│   ├── products.php           # Product catalog with search, filters & sorting
│   ├── product.php            # Detailed product showcase & buy-now triggers
│   ├── cart.php               # Shopping bag review & quantity updates
│   ├── checkout.php           # Order review & Razorpay modal integration
│   ├── payment-verify.php     # Server-side cryptographic signature verification
│   └── ...                    # Customer auth & order management entry points
│
├── scripts/
│   └── generate-assets.php    # Visual asset & placeholder generation script
│
├── storage/                   # Authoritative Media & Persistent Uploads
│   └── uploads/
│       ├── .htaccess          # Disables script execution in uploads directory
│       └── products/          # Validated jewellery product photographs
│
├── tests/
│   ├── security_audit.php     # Token-level static analyzer for SQLi & XSS
│   └── test_order_flow.php    # Automated end-to-end checkout & stock test
│
├── .env.example               # Safe configuration template for version control
├── .gitignore                 # Excludes environment secrets and logs
├── .htaccess                  # Apache security hardening & routing config
├── README.md                  # Complete technical documentation and setup guide
└── router.php                 # Development server routing & access control
```

---

## 9. Automated Testing & Security Audit

You can run the built-in automated test suite anytime:

### 1. Run Static Security & Code Quality Audit
```bash
php tests/security_audit.php
```
*Scans all 41 PHP files to ensure zero raw SQL concatenations, no weak hashes, and proper output escaping.*

### 2. Run End-to-End Order Flow & Inventory Test (Production-Safe Isolation)
To guarantee zero corruption of live catalogue items, `test_order_flow.php` uses safety gates (aborts on `APP_ENV=production` and non-test databases) and operates exclusively against an isolated test database using disposable records and automated teardown:

**Step A — Initialize the isolated test database (one-time setup):**
```bash
# PowerShell (Windows):
$env:DB_NAME="shivkrupa_test_db"; php database/setup-database.php

# Windows CMD:
set DB_NAME=shivkrupa_test_db&& php database/setup-database.php

# Bash (Linux/macOS):
DB_NAME=shivkrupa_test_db php database/setup-database.php
```

**Step B — Execute the end-to-end test:**
```bash
# PowerShell (Windows):
$env:DB_NAME="shivkrupa_test_db"; php tests/test_order_flow.php

# Windows CMD:
set DB_NAME=shivkrupa_test_db&& php tests/test_order_flow.php

# Bash (Linux/macOS):
DB_NAME=shivkrupa_test_db php tests/test_order_flow.php
```
*Simulates cart addition, price hydration, MySQL order placement, Razorpay payment verification, and verifies exact atomic stock reduction on disposable records before cleaning them up.*

---

## 10. Technical Architecture & FAQ Reference

Frequently referenced technical questions and architectural decisions:

### Q1: Why did you choose PDO over MySQLi?
**Answer:** PDO (PHP Data Objects) offers an object-oriented, database-agnostic interface supporting 12 different database drivers. It enforces prepared statements with parameter binding, natively preventing SQL Injection. It also allows configuring exception handling (`PDO::ERRMODE_EXCEPTION`) and setting default fetch modes (`PDO::FETCH_ASSOC`).

### Q2: How does the application prevent SQL Injection?
**Answer:** We never concatenate user inputs directly into query strings. Every query uses parameterized prepared statements with placeholders (e.g., `SELECT * FROM users WHERE email = ?`). The MySQL server compiles the SQL template first and treats user parameters strictly as data values, neutralizing SQL injection.

### Q3: How is Razorpay payment verified on the server?
**Answer:** When payment succeeds in the frontend, Razorpay returns a `razorpay_payment_id`, `razorpay_order_id`, and a cryptographic `razorpay_signature`. On our server (`payment-verify.php`), we generate an HMAC-SHA256 hash of `razorpay_order_id . '|' . razorpay_payment_id` using our secret key (`RAZORPAY_KEY_SECRET`). We then use `hash_equals()` to compare the signatures. If they match, the payment is confirmed authentic.

### Q4: How is product price tampering prevented in the shopping cart?
**Answer:** The client-side session only stores the product ID and quantity (`$_SESSION['cart'][$productId] = $qty`). When calculating totals in `cart.php` or `checkout.php`, the server executes a query against the MySQL `products` table to fetch the real price. Any price sent by the browser is ignored.

### Q5: How is user password security handled?
**Answer:** Passwords are never stored in plain text. We use PHP's native `password_hash($password, PASSWORD_DEFAULT)`, which uses the strong BCrypt algorithm with an automatically generated cryptographic salt. Verification is performed using `password_verify($inputPassword, $storedHash)`.

### Q6: What is CSRF and how is it mitigated here?
**Answer:** Cross-Site Request Forgery occurs when an unauthorized command is transmitted from a trusted user. We generate a 32-byte pseudo-random token in `$_SESSION['csrf_token']` and embed it in forms via `csrfField()`. Upon form submission, `verifyCsrfToken()` validates the token using `hash_equals()`.

### Q7: Why do we regenerate the session ID on login?
**Answer:** `session_regenerate_id(true)` prevents **Session Fixation Attacks**, where an attacker forces a victim to use a known session ID and then hijacks the authenticated session once the user logs in.

---

*Shivkrupa Art Jewellery &bull; Online Jewellery Store &bull; All Rights Reserved.*
