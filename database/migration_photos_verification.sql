-- =====================================================================
-- MISE À JOUR de ta base existante (aucune donnée n'est supprimée) :
--   1. photos des produits  (table product_images)
--   2. code de vérification unique des reçus (colonne orders.verification_code)
--
-- À lancer UNE SEULE FOIS. Une deuxième fois afficherait une erreur
-- "Duplicate column" / "already exists" : sans gravité, rien n'est modifié.
-- (N'utilise PAS schema.sql pour ça : il efface les tables.)
-- =====================================================================
USE leadshop;

-- Photos : plusieurs par produit, la première (sort_order le plus bas) est l'image principale.
-- file_name = nom aléatoire sans extension (le fichier réel est dans uploads/products/).
CREATE TABLE product_images (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    file_name  VARCHAR(64) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_images_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_images_product (product_id, sort_order)
) ENGINE=InnoDB;

-- Code de vérification des reçus : 12 caractères aléatoires, uniques.
-- Les anciennes commandes (NULL) reçoivent leur code automatiquement la première
-- fois que leur reçu PDF est généré.
ALTER TABLE orders ADD COLUMN verification_code VARCHAR(16) NULL AFTER ticket_id;
ALTER TABLE orders ADD UNIQUE KEY uq_orders_verification (verification_code);
