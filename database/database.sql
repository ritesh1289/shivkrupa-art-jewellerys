-- ==========================================================
-- SHIVKRUPA ART JEWELLERY — Database Schema & Initial Seed
-- Database: shivkrupa_db
-- Engine: InnoDB | Character Set: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `shivkrupa_db`
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `shivkrupa_db`;

-- --------------------------------------------------------
-- Table: users
-- Stores registered customer accounts & default shipping details
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(20) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `address` TEXT NULL,
    `city` VARCHAR(80) NULL,
    `state` VARCHAR(80) NULL,
    `pincode` VARCHAR(20) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: admins
-- Stores back-office administrative users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(60) NOT NULL UNIQUE,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(120) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: categories
-- Jewellery product classification (Rings, Earrings, etc.)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(120) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `image` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: products
-- Stores jewellery inventory, specs, pricing, and stock
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `name` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(220) NOT NULL UNIQUE,
    `sku` VARCHAR(50) NOT NULL UNIQUE,
    `description` TEXT NOT NULL,
    `material` VARCHAR(100) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `discount_price` DECIMAL(10,2) NULL,
    `stock_quantity` INT NOT NULL DEFAULT 0,
    `image` VARCHAR(255) NOT NULL,
    `status` ENUM('active', 'inactive', 'out_of_stock') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX `idx_products_category` (`category_id`),
    INDEX `idx_products_status` (`status`),
    INDEX `idx_products_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: orders
-- Customer order master table with shipping & Razorpay tracking
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(50) NOT NULL UNIQUE,
    `user_id` INT NOT NULL,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `shipping_name` VARCHAR(120) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `address` TEXT NOT NULL,
    `city` VARCHAR(80) NOT NULL,
    `state` VARCHAR(80) NOT NULL,
    `pincode` VARCHAR(20) NOT NULL,
    `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    `order_status` ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    `razorpay_order_id` VARCHAR(100) NULL,
    `razorpay_payment_id` VARCHAR(100) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_orders_user` (`user_id`),
    INDEX `idx_orders_status` (`order_status`),
    INDEX `idx_orders_payment_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: order_items
-- Line items for each order
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `product_name` VARCHAR(200) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `quantity` INT NOT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX `idx_order_items_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: payments
-- Detailed audit ledger for all Razorpay transaction events
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `payment_method` VARCHAR(50) NOT NULL DEFAULT 'razorpay',
    `razorpay_order_id` VARCHAR(100) NOT NULL,
    `razorpay_payment_id` VARCHAR(100) NOT NULL,
    `razorpay_signature` VARCHAR(255) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `status` ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'paid',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_payments_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- SEED DATA
-- ==========================================================

-- Default Administrator (Password: Admin@123)
INSERT INTO `admins` (`id`, `username`, `email`, `password_hash`, `full_name`) VALUES
(1, 'admin', 'admin@shivkrupa.com', '$2y$10$04iRkWNe0n85zpeb.WuJPurYYVX9a.1doBCymXme/MW1zw36awaCS', 'Store Administrator')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- Default Test Customer (Password: User@123)
INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `address`, `city`, `state`, `pincode`) VALUES
(1, 'Pooja Sharma', 'pooja@example.com', '9876543210', '$2y$10$OSz6dnpzc/p673SRVRoN9.YgURWWMmQiuKOt55vvA5LSfc2QSIm2i', 'Flat 402, Lotus Residency, MG Road', 'Mumbai', 'Maharashtra', '400001')
ON DUPLICATE KEY UPDATE `email`=`email`;

-- 8 Core Jewellery Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `image`) VALUES
(1, 'Rings', 'rings', 'Handcrafted gold, diamond, and antique cocktail rings for every occasion.', 'category-rings.jpg'),
(2, 'Earrings', 'earrings', 'Exquisite jhumkas, chandbalis, drop earrings, and sparkling diamond studs.', 'category-earrings.jpg'),
(3, 'Necklaces', 'necklaces', 'Royal choker necklaces, temple necklaces, and traditional haar collection.', 'category-necklaces.jpg'),
(4, 'Bracelets', 'bracelets', 'Sophisticated diamond tennis bracelets and delicate gold chain bracelets.', 'category-bracelets.jpg'),
(5, 'Bangles', 'bangles', 'Traditional handcrafted kada bangles, bridal chuda, and sleek gold kadas.', 'category-bangles.jpg'),
(6, 'Pendants', 'pendants', 'Delicate solitaire diamond pendants and spiritual auspicious pendants.', 'category-pendants.jpg'),
(7, 'Chains', 'chains', 'Classic 22K yellow gold chains, rope chains, and sleek silver links.', 'category-chains.jpg'),
(8, 'Jewellery Sets', 'jewellery-sets', 'Bridal and festive complete coordinated necklace, earring, and maang tikka sets.', 'category-sets.jpg')
ON DUPLICATE KEY UPDATE `name`=`name`;

-- 16 Realistic Jewellery Products
INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `sku`, `description`, `material`, `price`, `discount_price`, `stock_quantity`, `image`, `status`) VALUES
(1, 1, 'Mayura Royal Diamond Solitaire Ring', 'mayura-royal-diamond-solitaire-ring', 'SKJ-RNG-001', 'An ethereal 18K yellow gold band crowned with a brilliant-cut certified solitaire diamond, encircled by peacock feather filigree motifs.', '18K Yellow Gold & Diamond', 48500.00, 44999.00, 8, 'product-ring-1.jpg', 'active'),
(2, 1, 'Kundan Heritage Cocktail Ring', 'kundan-heritage-cocktail-ring', 'SKJ-RNG-002', 'Grand statement cocktail ring crafted in 22K gold featuring hand-cut uncut polki stones and radiant ruby cluster accents.', '22K Gold & Polki Kundan', 28900.00, 26500.00, 12, 'product-ring-2.jpg', 'active'),
(3, 2, 'Kashmiri Chandbali Pearl Earrings', 'kashmiri-chandbali-pearl-earrings', 'SKJ-EAR-001', 'Regal crescent moon chandbali earrings adorned with basra pearls, delicate enamel meenakari, and hanging micro-bead drops.', '22K Yellow Gold & Basra Pearls', 36200.00, 32999.00, 10, 'product-earring-1.jpg', 'active'),
(4, 2, 'Temple Floral Jhumka Earrings', 'temple-floral-jhumka-earrings', 'SKJ-EAR-002', 'Traditional South Indian temple jewellery jhumkas featuring Goddess Lakshmi engravings and auspicious bell drops with ruby stones.', '22K Antique Gold', 42800.00, NULL, 6, 'product-earring-2.jpg', 'active'),
(5, 3, 'Padmavati Bridal Choker Necklace', 'padmavati-bridal-choker-necklace', 'SKJ-NCK-001', 'Fit for royalty, this grand handcrafted bridal choker is studded with pristine polki uncut diamonds and emerald green bead hangings.', '22K Gold, Polki & Emeralds', 125000.00, 115000.00, 4, 'product-necklace-1.jpg', 'active'),
(6, 3, 'Aura Minimalist Diamond Necklace', 'aura-minimalist-diamond-necklace', 'SKJ-NCK-002', 'Contemporary elegance in 18K rose gold featuring a graduated cascade of bezel-set sparkling diamonds suitable for western & festive wear.', '18K Rose Gold & Diamond', 68000.00, 62500.00, 9, 'product-necklace-2.jpg', 'active'),
(7, 4, 'Celestial Solitaire Tennis Bracelet', 'celestial-solitaire-tennis-bracelet', 'SKJ-BRC-001', 'Continuous line of masterfully matched round brilliant diamonds set in an ultra-secure 18K white gold four-prong setting.', '18K White Gold & Diamond', 89000.00, 84500.00, 5, 'product-bracelet-1.jpg', 'active'),
(8, 4, 'Heritage Filigree Cuff Bracelet', 'heritage-filigree-cuff-bracelet', 'SKJ-BRC-002', 'Fine openwork gold filigree cuff bangle with floral scroll motifs and a safety latch closure, inspired by royal Jaipur artistry.', '22K Yellow Gold', 54000.00, 49999.00, 7, 'product-bracelet-2.jpg', 'active'),
(9, 5, 'Rajputana Meenakari Kada Bangles (Pair)', 'rajputana-meenakari-kada-bangles-pair', 'SKJ-BNG-001', 'A stunning pair of heavy antique kadas with lion-head terminals (Vyaghramukha) finished with exquisite reverse meenakari enameling.', '22K Gold with Enamel', 98000.00, 92000.00, 5, 'product-bangle-1.jpg', 'active'),
(10, 5, 'Sleek Diamond Eternity Bangles (Pair)', 'sleek-diamond-eternity-bangles-pair', 'SKJ-BNG-002', 'Subtle everyday luxury: pair of slender 18K yellow gold bangles studded with pavé-set shimmering natural diamonds.', '18K Gold & Natural Diamond', 76000.00, NULL, 6, 'product-bangle-2.jpg', 'active'),
(11, 6, 'Ganeshji Diamond & Ruby Spiritual Pendant', 'ganeshji-diamond-ruby-spiritual-pendant', 'SKJ-PND-001', 'Auspicious Lord Ganesha pendant artistically crafted in dual-tone 18K gold with brilliant diamond accents and ruby eye detail.', '18K Dual Tone Gold & Diamond', 22500.00, 19999.00, 15, 'product-pendant-1.jpg', 'active'),
(12, 6, 'Emerald Drop Solitaire Pendant', 'emerald-drop-solitaire-pendant', 'SKJ-PND-002', 'Captivating oval Zambian emerald enveloped in a sparkling halo of round diamonds, suspended on a fine gold loop.', '18K Yellow Gold, Emerald & Diamond', 34000.00, 31200.00, 11, 'product-pendant-2.jpg', 'active'),
(13, 7, 'Royal Rope 22K Gold Chain', 'royal-rope-22k-gold-chain', 'SKJ-CHN-001', 'Solid 22-karat hallmarked gold rope twist chain offering superior shine and enduring durability for daily or special wear.', '22K Hallmarked Gold (15 grams)', 82000.00, 78500.00, 8, 'product-chain-1.jpg', 'active'),
(14, 7, 'Milano Diamond-Cut Curb Chain', 'milano-diamond-cut-curb-chain', 'SKJ-CHN-002', 'Sleek Italian-inspired curb chain with precision diamond-cut faceted links that catch and reflect light with every movement.', '18K Yellow Gold', 49500.00, 46000.00, 10, 'product-chain-2.jpg', 'active'),
(15, 8, 'Nizam-e-Khaas Grand Bridal Set', 'nizam-e-khaas-grand-bridal-set', 'SKJ-SET-001', 'The quintessential bridal masterpiece: comprehensive set comprising an elaborate multi-tiered necklace, matching chandelier earrings, and maang tikka.', '22K Antique Gold, Polki & Rubies', 245000.00, 229000.00, 3, 'product-set-1.jpg', 'active'),
(16, 8, 'Gulmohar Pastel Meenakari Festive Set', 'gulmohar-pastel-meenakari-festive-set', 'SKJ-SET-002', 'Modern pastel mint and pink meenakari choker necklace accompanied by matching cluster drop earrings, perfect for sangeet and receptions.', '22K Gold with Pastel Enamel', 88000.00, 79999.00, 5, 'product-set-2.jpg', 'active')
ON DUPLICATE KEY UPDATE `name`=`name`;
