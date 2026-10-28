# Mise à jour : photos des produits + code de vérification des reçus

## 1. Installation

Décompresse par-dessus ton projet (ton `config/config.php` n'est PAS touché) :

    Expand-Archive -Path "$env:USERPROFILE\Downloads\leadshop-photos.zip" -DestinationPath "C:\xampp\htdocs" -Force

Puis mets ta base à jour (aucune donnée n'est supprimée) :

    & "C:\xampp\mysql\bin\mysql.exe" -u root --default-character-set=utf8mb4 -e "source C:/xampp/htdocs/leadshop/database/migration_photos_verification.sql"

À lancer UNE SEULE FOIS. (Ne lance surtout pas schema.sql : il efface tout.)
Tes anciennes commandes reçoivent leur code de vérification automatiquement la première
fois que leur reçu est téléchargé.

## 2. Photos des produits

Admin > Produits : le formulaire a maintenant un champ « Photos » (plusieurs à la fois).
- 8 photos maximum par produit, JPEG / PNG / WebP, 8 Mo maximum par fichier.
- Elles sont redimensionnées (grande version 1200 px + miniature 480 px pour que la
  boutique reste rapide sur mobile) et redressées si le téléphone les a prises couchées.
- Les données cachées des photos (position GPS, appareil) sont supprimées.
- En modifiant un produit : « Mettre en première » (photo principale) et « Supprimer ».
- Côté client : la boutique et la page de chaque article (produit.php) affichent une galerie
  qu'on balaye du doigt (ou ‹ › / molette / flèches du clavier sur ordinateur).

Si le message « extension GD » apparaît : ouvre C:\xampp\php\php.ini, retire le « ; » devant
`extension=gd`, puis redémarre Apache.

Hébergement : le dossier uploads/ est protégé par un .htaccess (Apache) qui interdit d'y
exécuter des scripts. Si ton hébergeur utilise Nginx, ce fichier est ignoré : demande
l'équivalent à l'hébergeur (interdire l'exécution de PHP dans uploads/).

## 3. Code de vérification des reçus (contre la falsification)

Chaque commande reçoit un code unique de 12 caractères, imprimé sur le reçu PDF avec un QR code.
Le QR code ouvre verifier.php, qui affiche ce que la BASE contient pour ce code : numéro,
date, articles, total, statut et le nom du client masqué (J••• T•••), JAMAIS le téléphone
ni l'adresse. Si quelqu'un modifie un montant sur son PDF, la page de vérification montre
le vrai montant.

Limites à connaître :
- Ça protège seulement si la personne qui reçoit le reçu le VÉRIFIE réellement.
  Un faux PDF sans code, ou avec un faux code, est détecté ; encore faut-il regarder.
- Le code ne prouve pas que la commande a été payée.
- Une commande annulée reste « authentique » mais la page signale qu'elle est annulée.

IMPORTANT avant la mise en ligne : ajoute dans config/config.php l'adresse OFFICIELLE du site,
sinon le QR code des reçus pointera vers http://localhost :

    define('SITE_URL', 'https://www.tondomaine.com');   // sans slash final

Cette adresse est volontairement lue dans la configuration et non déduite de la requête du
visiteur : sinon un pirate pourrait faire imprimer un reçu dont le QR mène vers un faux site.

Réglages facultatifs (même fichier) : DOC_TITLE, DOC_PREFIX, SHOP_ADDRESS, SHOP_LEGAL_LINES
(voir includes/invoice.php).
