-- =====================================================================
-- Données de départ : ton catalogue + 2 codes de réduction DE TEST.
-- À importer UNE SEULE FOIS, après schema.sql (sinon les produits seront
-- en double). Supprime les codes de test avant de mettre le site en ligne.
-- =====================================================================
USE leadshop;

INSERT INTO products (name, description, price, category) VALUES
('Diffuseur de parfum lumineux (bois)',  'Lumière LED d''ambiance, télécommande incluse.', 2500, 'maison'),
('Diffuseur de parfum lumineux (verre)', 'Forme verre, brume fine et lumière douce.',      3500, 'maison'),
('Parfum Aventos Blue For Him',          'Eau de parfum 100 ml.',                          2000, 'parfum'),
('Parfum 24K',                           'Eau de parfum, senteur durable.',                2500, 'parfum'),
('Mini ventilateur portable',            'Compact et rechargeable.',                       2000, 'accessoire'),
('Boucles d''oreilles fleuries',         'Plusieurs coloris disponibles.',                 1250, 'bijoux'),
('Lait Vaseline Cocoa Radiant',          'Hydratation intense 48 h.',                      2750, 'cosmetique');

-- Codes de TEST : 10 % et 500 FCFA de réduction
INSERT INTO tickets (code, discount_type, discount_value) VALUES
('TEST10',  'percentage',   10),
('TEST500', 'fixed_amount', 500);
