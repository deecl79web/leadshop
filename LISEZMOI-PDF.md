# Mise à jour : reçu de commande en PDF (TCPDF)

## Installation

Décompresse cette archive par-dessus ton projet (elle contient le dossier `leadshop`) :

    Expand-Archive -Path "$env:USERPROFILE\Downloads\leadshop-pdf.zip" -DestinationPath "C:\xampp\htdocs" -Force

Fichiers NOUVEAUX : includes/invoice.php, recu.php, admin/recu.php, lib/ (TCPDF + .htaccess)
Fichiers REMPLACÉS : includes/orders.php, confirmation.php, admin/orders.php
(aucun changement de base de données, rien à réimporter)

## Utilisation

- Client : bouton « Télécharger mon reçu (PDF) » sur la page de confirmation
  (uniquement pour la commande qu'il vient de passer).
- Admin : bouton « Reçu PDF » sur chaque commande dans Admin > Commandes.

## Réglages facultatifs (config/config.php)

Ajoute ces lignes à la fin de ton config.php si tu veux personnaliser le document.
Sans elles, des valeurs par défaut sont utilisées.

    define('DOC_TITLE',  'Reçu de commande');   // à changer en 'Facture' seulement quand tu es en règle
    define('DOC_PREFIX', 'RC');                  // numéro : RC-2026-000001
    define('SHOP_ADDRESS', 'Bastos');            // affiché avant la ville
    define('SHOP_LEGAL_LINES', []);              // ex : ['NIU : ...', 'RCCM : ...'] quand tu les auras

## Pourquoi « Reçu » et pas « Facture »

Une vraie facture a des mentions obligatoires (identifiant fiscal, entreprise enregistrée...)
qui dépendent de ta situation. Vérifie-les auprès d'un comptable ou des impôts avant de
passer à « Facture ». Le reçu contient déjà tout ce dont le client a besoin.

## TCPDF

Bibliothèque libre sous licence LGPL-3.0 (lib/tcpdf/LICENSE.TXT : ne la supprime pas).
Le dossier lib/ est interdit d'accès depuis le navigateur (.htaccess).
