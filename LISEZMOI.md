# Leadshop — boutique en PHP pur (sans framework)

Nécessite PHP 7.4 ou plus et MySQL/MariaDB (XAMPP récent : OK).

## Installation (XAMPP)

1. Ouvre le panneau XAMPP et démarre **Apache** et **MySQL**.
2. Place le dossier `leadshop` dans `C:\xampp\htdocs\` (il doit s'appeler `leadshop`,
   sinon change `BASE_URL` dans `config/config.php`).
3. Importe la base. Dans PowerShell :

   ```powershell
   & "C:\xampp\mysql\bin\mysql.exe" -u root --default-character-set=utf8mb4 -e "source C:/xampp/htdocs/leadshop/database/schema.sql"
   & "C:\xampp\mysql\bin\mysql.exe" -u root --default-character-set=utf8mb4 -e "source C:/xampp/htdocs/leadshop/database/seed.sql"
   ```

   Ou avec phpMyAdmin (http://localhost/phpmyadmin) : onglet **Importer**, choisis
   `database/schema.sql`, puis `database/seed.sql`.

   ⚠️ `schema.sql` SUPPRIME les tables puis les recrée : à lancer une seule fois.
   `seed.sql` ajoute tes produits : à lancer une seule fois aussi, sinon ils seront en double.

4. Ouvre http://localhost/leadshop/

## Espace administrateur

http://localhost/leadshop/admin/login.php
Première visite : le site te demande de **créer ton compte** (10 caractères minimum).
Ensuite tu gères produits, commandes, tickets de réduction et demandes de site.

## Tester une commande avec réduction

Ajoute des articles au panier, puis au moment de commander entre le code `TEST10`
(10 %) ou `TEST500` (500 FCFA). Chaque code ne marche qu'une fois ; tu peux en
créer d'autres dans Admin > Tickets.

## Ce que tu peux changer facilement

Tout est dans `config/config.php` : nom de la boutique, numéro WhatsApp, ville,
`ARCADE_URL` (quand l'arcade sera branchée, mets son adresse : le bouton se dégrisera).
Les couleurs sont tout en haut de `assets/css/style.css` (variables `--accent`, `--bg`...).

## Comment une page fonctionne (pour t'y retrouver)

    boutique.php
      1. charge includes/cart.php  -> qui charge includes/functions.php -> qui charge config/
      2. lit les produits dans MySQL avec une requête préparée (PDO)
      3. inclut includes/header.php, affiche les cartes (includes/product_card.php), puis footer.php

Les formulaires envoient les données en POST vers la même page (ou panier.php),
qui vérifie le jeton CSRF, valide les champs, agit, puis REDIRIGE (pour qu'un
rafraîchissement de page ne renvoie pas le formulaire deux fois).

## Avant de mettre le site en ligne

- `APP_DEBUG` à `false` dans config/config.php
- Supprimer les codes `TEST10` / `TEST500` (Admin > Tickets > Annuler)
- Créer ton compte admin tout de suite après l'installation
- Mettre un mot de passe sur l'utilisateur MySQL (root sans mot de passe = local uniquement)
- Activer HTTPS

## Pas encore fait

(Photos des produits, reçu PDF et code de vérification : voir LISEZMOI-PHOTOS.md)

- Comptes clients et historique de commandes
- L'arcade en PHP : la table `tickets` est prête, l'arcade n'aura qu'à y insérer des lignes
- Paiement en ligne (mobile money)
