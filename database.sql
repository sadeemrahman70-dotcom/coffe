-- ============================================================
--  Brew & Bean  —  Complete Database Setup
--  1. افتح phpMyAdmin
--  2. اختر قاعدة البيانات brew_bean (أو أنشئها)
--  3. اضغط SQL وانسخ هذا الملف كله وشغّله
-- ============================================================

CREATE DATABASE IF NOT EXISTS `brew_bean`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `brew_bean`;

-- ─────────────────────────────────────────────
--  1. CATEGORIES
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id`   INT          NOT NULL AUTO_INCREMENT,
  `category_name` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- بيانات أساسية (لن تتكرر إذا موجودة)
INSERT IGNORE INTO `categories` (`category_id`, `category_name`) VALUES
(1, 'Beans'),
(2, 'Capsules'),
(3, 'Tools');


-- ─────────────────────────────────────────────
--  2. PRODUCTS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `products` (
  `product_id`     INT            NOT NULL AUTO_INCREMENT,
  `product_name`   VARCHAR(255)   NOT NULL,
  `category_id`    INT            DEFAULT NULL,
  `brewing_method` VARCHAR(100)   DEFAULT NULL,
  `price`          DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `stock`          INT            NOT NULL DEFAULT 0,
  `description`    TEXT           DEFAULT NULL,
  `image`          VARCHAR(255)   DEFAULT NULL,
  PRIMARY KEY (`product_id`),
  KEY `fk_products_category` (`category_id`),
  CONSTRAINT `fk_products_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ─────────────────────────────────────────────
--  3. ADMIN
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `admin` (
  `admin_id`  INT          NOT NULL AUTO_INCREMENT,
  `email`     VARCHAR(150) NOT NULL,
  `password`  VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `uq_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- حساب الأدمن الافتراضي  (email: admin@brew.com  password: admin123)
INSERT IGNORE INTO `admin` (`admin_id`, `email`, `password`, `full_name`) VALUES
(1, 'admin@brew.com', 'admin123', 'Store Admin');


-- ─────────────────────────────────────────────
--  4. CUSTOMERS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `customers` (
  `customer_id` INT          NOT NULL AUTO_INCREMENT,
  `full_name`   VARCHAR(150) NOT NULL,
  `email`       VARCHAR(150) NOT NULL,
  `phone`       VARCHAR(20)  DEFAULT NULL,
  `address`     TEXT         DEFAULT NULL,
  `password`    VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `uq_customer_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ─────────────────────────────────────────────
--  5. CART  ★ هذا الجدول كان ناقصاً — أضفناه
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `cart` (
  `id`           INT            NOT NULL AUTO_INCREMENT,
  `product_name` VARCHAR(255)   NOT NULL,
  `price`        DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `quantity`     INT            NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cart_product` (`product_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ─────────────────────────────────────────────
--  6. ORDERS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id`     INT            NOT NULL AUTO_INCREMENT,
  `customer_id`  INT            DEFAULT NULL,
  `total_amount` DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `status`       VARCHAR(50)    NOT NULL DEFAULT 'pending',
  `order_date`   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes`        TEXT           DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  KEY `fk_orders_customer` (`customer_id`),
  CONSTRAINT `fk_orders_customer`
    FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ─────────────────────────────────────────────
--  7. ORDER_DETAILS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `order_details` (
  `detail_id`  INT            NOT NULL AUTO_INCREMENT,
  `order_id`   INT            NOT NULL,
  `product_id` INT            DEFAULT NULL,
  `quantity`   INT            NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `subtotal`   DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`detail_id`),
  KEY `fk_details_order` (`order_id`),
  CONSTRAINT `fk_details_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ─────────────────────────────────────────────
--  تحقق سريع بعد التشغيل
-- ─────────────────────────────────────────────
-- SELECT TABLE_NAME, TABLE_ROWS
-- FROM information_schema.TABLES
-- WHERE TABLE_SCHEMA = 'brew_bean'
-- ORDER BY TABLE_NAME;
