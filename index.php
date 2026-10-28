<?php
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/products.php';

$pageTitle = 'Accueil';
$bodyClass = 'page-home';

// Les 4 derniers articles ajoutés au catalogue
$featured = db()->query(
    'SELECT id, name, description, price, category, image_path
     FROM products WHERE is_active = 1 ORDER BY id DESC LIMIT 4'
)->fetchAll();

$imagesByProduct = get_images_for_products(array_column($featured, 'id'));

require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container">
        <span class="pill-badge">Nouveau à <?= e(SHOP_CITY) ?></span>
        <h1>Des articles qui vous plaisent, des réductions que vous gagnez</h1>
        <p class="lead">
            <?= e(SHOP_NAME) ?> réunit une vraie boutique et une arcade de jeux :
            plus tu joues, plus tu débloques de réductions sur tes prochains achats.
        </p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="<?= e(url('boutique.php')) ?>">Voir la boutique</a>
            <?php if (ARCADE_URL !== ''): ?>
                <a class="btn btn-secondary" href="<?= e(ARCADE_URL) ?>"><img class="btn-icon" src="<?= e(url('assets/icones/gamepad.svg')) ?>" alt="" aria-hidden="true"> Jouer et gagner des réductions</a>
            <?php else: ?>
                <span class="btn btn-secondary is-disabled" aria-disabled="true"><img class="btn-icon" src="<?= e(url('assets/icones/gamepad.svg')) ?>" alt="" aria-hidden="true"> Arcade bientôt disponible</span>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container grid-3">
        <div class="glass-card">
            <img class="card-icon" src="<?= e(url('assets/icones/comment.svg')) ?>" alt="" aria-hidden="true">
            <h3>Commande sans compte</h3>
            <p class="muted">Choisis tes articles, laisse tes coordonnées, c'est tout. Aucune inscription requise.</p>
        </div>
        <div class="glass-card">
            <img class="card-icon" src="<?= e(url('assets/icones/handshake.svg')) ?>" alt="" aria-hidden="true">
            <h3>Paiement à la livraison</h3>
            <p class="muted">Tu payes quand tu reçois ton colis en main propre, pas avant.</p>
        </div>
        <div class="glass-card">
            <img class="card-icon" src="<?= e(url('assets/icones/gamepad.svg')) ?>" alt="" aria-hidden="true">
            <h3>Réductions gagnées en jouant</h3>
            <p class="muted">Joue à l'arcade, gagne des pièces, échange-les contre de vraies réductions ici.</p>
        </div>
    </div>
</section>

<?php if ($featured): ?>
<section class="section">
    <div class="container">
        <h2 class="section-title">Nos articles du moment</h2>
        <div class="product-grid">
            <?php foreach ($featured as $product): ?>
                <?php $returnPage = 'index'; $returnCategory = ''; $images = $imagesByProduct[(int) $product['id']] ?? []; ?>
                <?php include __DIR__ . '/includes/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
        <p class="text-center mt-2">
            <a class="btn btn-secondary" href="<?= e(url('boutique.php')) ?>">Voir tout le catalogue</a>
        </p>
    </div>
</section>
<?php endif; ?>

<section class="section section-alt">
    <div class="container narrow">
        <h2 class="section-title">Pourquoi commander chez <?= e(SHOP_NAME) ?></h2>
        <ul class="values">
            <li><img class="value-icon" src="<?= e(url('assets/icones/money-check-edit.svg')) ?>" alt="" aria-hidden="true"> Produits sélectionnés un par un, pas un catalogue générique</li>
            <li><img class="value-icon" src="<?= e(url('assets/icones/handshake.svg')) ?>" alt="" aria-hidden="true"> Livraison organisée directement avec toi, à <?= e(SHOP_CITY) ?></li>
            <li><img class="value-icon" src="<?= e(url('assets/icones/comment.svg')) ?>" alt="" aria-hidden="true"> Boutique qui démarre tout juste : chaque commande compte</li>
        </ul>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
