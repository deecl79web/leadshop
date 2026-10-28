-- =====================================================================
-- Leadshop : structure de la base de données (MySQL / MariaDB de XAMPP)
-- À importer UNE FOIS (phpMyAdmin > Importer, ou ligne de commande).
-- ATTENTION : ce fichier SUPPRIME les tables existantes avant de les recréer.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS leadshop
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE leadshop;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS tickets;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS site_requests;
DROP TABLE IF EXISTS admins;
SET FOREIGN_KEY_CHECKS = 1;

-- Catalogue. Les prix sont des ENTIERS en FCFA (jamais de décimales).
CREATE TABLE products (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    description TEXT NULL,
    price       INT UNSIGNED NOT NULL,
    category    VARCHAR(100) NULL,
    image_path  VARCHAR(255) NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Photos des produits (plusieurs par produit ; la première = image principale).
CREATE TABLE product_images (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    file_name  VARCHAR(64) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_images_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_images_product (product_id, sort_order)
) ENGINE=InnoDB;

-- Tickets de réduction. Plus tard, c'est l'arcade qui les créera quand un
-- joueur échange ses pièces (user_id sera alors rempli).
CREATE TABLE tickets (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code           VARCHAR(12) NOT NULL UNIQUE,
    discount_type  ENUM('percentage', 'fixed_amount') NOT NULL,
    discount_value INT UNSIGNED NOT NULL,
    status         ENUM('issued', 'redeemed', 'expired', 'cancelled') NOT NULL DEFAULT 'issued',
    user_id        INT UNSIGNED NULL,
    issued_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    redeemed_at    DATETIME NULL
) ENGINE=InnoDB;

-- Commandes "invité" : pas de compte client pour l'instant.
CREATE TABLE orders (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    customer_name    VARCHAR(255) NOT NULL,
    customer_phone   VARCHAR(30) NOT NULL,
    customer_address TEXT NOT NULL,
    subtotal_amount  INT UNSIGNED NOT NULL,
    discount_amount  INT UNSIGNED NOT NULL DEFAULT 0,
    total_amount     INT UNSIGNED NOT NULL,
    ticket_id        INT UNSIGNED NULL,
    -- Code unique imprimé sur le reçu : permet de vérifier qu'il n'a pas été falsifié.
    verification_code VARCHAR(16) NULL,
    status           ENUM('pending', 'confirmed', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_orders_verification (verification_code),
    CONSTRAINT fk_orders_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Lignes d'une commande. product_name et unit_price sont une COPIE du moment :
-- changer un prix plus tard ne modifie pas les anciennes commandes.
CREATE TABLE order_items (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id     INT UNSIGNED NOT NULL,
    product_id   INT UNSIGNED NULL,
    product_name VARCHAR(255) NOT NULL,
    unit_price   INT UNSIGNED NOT NULL,
    quantity     INT UNSIGNED NOT NULL,
    line_total   INT UNSIGNED NOT NULL,
    CONSTRAINT fk_items_order   FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Demandes "Je veux aussi un site".
CREATE TABLE site_requests (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    phone      VARCHAR(30) NOT NULL,
    email      VARCHAR(255) NULL,
    message    TEXT NULL,
    status     ENUM('new', 'contacted', 'closed') NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Comptes administrateur. Le premier se crée depuis admin/login.php.
CREATE TABLE admins (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
