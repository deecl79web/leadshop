<?php
/**
 * Réglages généraux du site.
 * Ce dossier est protégé par .htaccess : personne ne peut ouvrir ce fichier
 * depuis un navigateur (PHP, lui, peut le lire normalement).
 */

// --- Boutique ---------------------------------------------------------
define('SHOP_NAME', 'Leadshop');
define('SHOP_CITY', 'Yaoundé');
define('SHOP_WHATSAPP', '237657327758'); // format international, sans + ni espaces

// --- Site -------------------------------------------------------------
// Chemin du site dans l'URL. Avec XAMPP : http://localhost/leadshop => '/leadshop'
// (mets '' si le site est installé à la racine d'un domaine)
define('BASE_URL', '/leadshop');

// Adresse de l'arcade de jeux. Laisse vide tant que l'arcade n'est pas
// branchée à ce site : le bouton "Arcade" s'affichera alors en grisé.
define('ARCADE_URL', '');

// --- Base de données (XAMPP par défaut : utilisateur root, sans mot de passe) ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'leadshop');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// true  = affiche les erreurs PHP à l'écran (pratique pendant le développement)
// false = les cache (à mettre quand le site est en ligne)
define('APP_DEBUG', true);
