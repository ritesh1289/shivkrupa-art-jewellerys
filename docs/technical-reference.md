# SHIVKRUPA ART JEWELLERY — Technical Architecture & Developer Reference

A comprehensive technical reference for developers, code reviewers, and system architects.

---

## 1. System Synopsis

### Q1: What is SHIVKRUPA ART JEWELLERY?
> **Answer:** It is a full-stack, responsive Indian heritage jewellery e-commerce application built using PHP 8.2+, MySQL/MariaDB (InnoDB), Vanilla JavaScript, CSS3, and Razorpay Standard Checkout in test mode. It provides customer catalogue browsing, inventory-validated checkout, and an administrative control panel for sales tracking and inventory management.

### Q2: Why did you choose pure PHP without frameworks like Laravel or CodeIgniter?
> **Answer:** 
> 1. To demonstrate core mastery of the fundamentals: session management, HTTP headers, PDO prepared statements, transaction lifecycles, and cryptographic signature verification.
> 2. To minimize external abstraction overhead and eliminate third-party package dependency vulnerabilities.
> 3. Every line of code is transparent, auditable, and self-contained.

---

## 2. Security Questions

### Q3: How do you prevent SQL Injection?
> **Answer:** 
> 100% of database queries use PDO prepared statements with parameterized placeholders (`?`). The SQL query statement template is compiled on the database server before user parameters are bound, ensuring user input is strictly treated as literal data, never executable SQL commands.

### Q4: How is Cross-Site Scripting (XSS) mitigated?
> **Answer:** 
> All dynamic output rendered into the HTML DOM passes through the `e()` helper function, which applies `htmlspecialchars($string, ENT_QUOTES, 'UTF-8')`. This converts characters like `<`, `>`, `&`, and `"` into HTML character entities (`&lt;`, `&gt;`, `&quot;`).

### Q5: How does your CSRF protection mechanism work?
> **Answer:** 
> When a session begins, a cryptographically secure 32-byte pseudo-random token is generated using `bin2hex(random_bytes(32))` and stored in `$_SESSION['csrf_token']`. Every POST form includes this token as a hidden field. Upon submission, `verifyCsrfToken()` compares the received token with the session token using `hash_equals()` to prevent timing attacks.

### Q6: How are passwords stored securely?
> **Answer:** 
> Passwords are never stored in plaintext. They are hashed using PHP’s `password_hash($password, PASSWORD_DEFAULT)`, which implements the salted BCrypt algorithm with an adaptive cost factor. Verification uses `password_verify()`.

---

## 3. Database & Transaction Questions

### Q7: What storage engine do you use, and why?
> **Answer:** 
> MySQL/MariaDB **InnoDB** engine. Unlike MyISAM, InnoDB supports ACID transactions (`beginTransaction()`, `commit()`, `rollBack()`), row-level locking, and foreign key integrity constraints with cascading relationships (`ON DELETE CASCADE` / `ON DELETE RESTRICT`).

### Q8: How do you prevent race conditions during checkout (Overselling / Stock Depletion)?
> **Answer:** 
> Stock validation is checked server-side before initiating payment. In `payment-verify.php`, when payment signature is confirmed, the order creation and stock decrements are wrapped inside a database transaction:
> ```php
> $db->beginTransaction();
> // Insert order & line items
> // Decrement product stock: UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?
> $db->commit();
> ```
> If any query fails, `$db->rollBack()` restores stock and data integrity.

### Q9: What safety measures prevent tests from modifying your live database?
> **Answer:** 
> 1. `tests/test_order_flow.php` implements an exact string allowlist that checks the effective connected database name (`SELECT DATABASE()`). If the database is not exactly `shivkrupa_test_db`, execution immediately halts before any writes.
> 2. `database/setup-database.php` validates the `DB_NAME` input against `^[a-zA-Z0-9_]{1,64}$`, rejecting malicious names or SQL injection payloads before connecting.

---

## 4. Payment Gateway (Razorpay)

### Q10: How does Razorpay payment signature verification work?
> **Answer:** 
> 1. The client opens Razorpay Checkout modal with the Order ID and Amount.
> 2. Upon successful card/UPI approval in test mode, Razorpay returns `razorpay_order_id`, `razorpay_payment_id`, and `razorpay_signature`.
> 3. On the backend, we construct the verification string:
>    `$payload = $razorpayOrderId . '|' . $razorpayPaymentId;`
> 4. We calculate our own expected signature using HMAC-SHA256:
>    `$expectedSignature = hash_hmac('sha256', $payload, RAZORPAY_KEY_SECRET);`
> 5. We verify equality via `hash_equals($expectedSignature, $razorpaySignature)`. Only when valid is the order marked `paid`.

### Q11: What if there is an offline or simulated environment without internet connectivity for Razorpay?
> **Answer:** 
> The checkout screen includes a built-in **"⚡ Fast-Track Test Simulator"** button that submits a simulated test response executing the exact same server-side verification logic and stock decrement flow.

---

## 5. Architectural & Folder Restructuring

### Q12: Why is the project organized with `public/`, `app/`, `admin/`, and `storage/`?
> **Answer:** 
> This follows industry standard web application separation of concerns:
> 1. **`public/`** serves as the web server's DocumentRoot, preventing direct HTTP exposure of private PHP libraries, database credentials, tests, or application internals.
> 2. **`app/`** contains reusable private modules (`components/`, `middleware/`, `services/`, `helpers/`).
> 3. **`admin/`** isolates back-office operations under dedicated authentication middleware.
> 4. **`storage/`** maintains uploaded media outside code source trees, with execution blockers preventing uploaded web shells.
