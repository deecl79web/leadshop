<?php
/**
 * En-tête commun. Avant de l'inclure, une page peut définir :
 *   $pageTitle  : titre de l'onglet
 *   $layout     : 'public' (défaut) ou 'admin'
 *   $bodyClass  : classe CSS ajoutée à <body>
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/cart.php';

$pageTitle = $pageTitle ?? '';
$layout    = $layout ?? 'public';
$bodyClass = $bodyClass ?? '';
$noindex   = $noindex ?? false;
$messages  = flash_pull();
$fullTitle = $pageTitle !== '' ? $pageTitle . ' — ' . SHOP_NAME : SHOP_NAME;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($fullTitle) ?></title>
    <meta name="theme-color" content="#0B0F17">
    <?php if ($noindex): ?><meta name="robots" content="noindex, nofollow"><?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">

<header class="site-header">
    <div class="container header-inner">
        <?php if ($layout === 'admin'): ?>
            <a class="brand" href="<?= e(url('admin/index.php')) ?>"><?= e(SHOP_NAME) ?> <span class="muted">· admin</span></a>
            <nav class="nav" aria-label="Administration">
                <a href="<?= e(url('admin/products.php')) ?>">Produits</a>
                <a href="<?= e(url('admin/orders.php')) ?>">Commandes</a>
                <a href="<?= e(url('admin/tickets.php')) ?>">Tickets</a>
                <a href="<?= e(url('admin/site-requests.php')) ?>">Demandes de site</a>
                <a href="<?= e(url('index.php')) ?>">Voir le site</a>
                <form method="post" action="<?= e(url('admin/logout.php')) ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="nav-button">Déconnexion</button>
                </form>
            </nav>
        <?php else: ?>
            <a class="brand" href="<?= e(url('index.php')) ?>"><?= e(SHOP_NAME) ?></a>
            <nav class="nav" aria-label="Navigation principale">
                <a href="<?= e(url('boutique.php')) ?>">Boutique</a>
                <?php if (ARCADE_URL !== ''): ?>
                    <a href="<?= e(ARCADE_URL) ?>">Arcade</a>
                <?php endif; ?>
                <a href="<?= e(url('demander-site.php')) ?>">Votre site</a>
                <a href="<?= e(url('panier.php')) ?>" class="nav-cart">
                    Panier <span class="cart-badge"><?= cart_count() ?></span>
                </a>
            </nav>
        <?php endif; ?>
    </div>
</header>

<main>
<?php if ($messages): ?>
    <div class="container flash-zone">
        <?php foreach ($messages as $message): ?>
            <div class="alert alert-<?= e($message['type']) ?>" role="status"><?= e($message['message']) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
