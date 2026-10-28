<?php
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/products.php';

$productId = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT id, name, description, price, category FROM products WHERE id = ? AND is_active = 1');
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Article introuvable';
    require __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container narrow"><h1 class="page-title">Article introuvable</h1>'
        . '<p class="muted">Cet article n\'existe pas ou n\'est plus en vente.</p>'
        . '<p><a class="btn btn-primary" href="' . e(url('boutique.php')) . '">Voir la boutique</a></p></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$images = get_images_for_products([$productId])[$productId] ?? [];
$pageTitle = $product['name'];
require __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container">
        <p class="small"><a href="<?= e(url('boutique.php')) ?>">← Retour à la boutique</a></p>

        <div class="product-detail">
            <div class="product-detail-media">
                <?php if ($images): ?>
                    <?php $galleryAlt = $product['name']; $gallerySize = 'full'; $galleryLink = ''; ?>
                    <?php include __DIR__ . '/includes/gallery.php'; ?>
                <?php else: ?>
                    <div class="product-thumb product-thumb-large" aria-hidden="true">
                        <span><?= category_icon($product['category'] ?? null) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="product-detail-info">
                <?php if (!empty($product['category'])): ?>
                    <span class="pill-badge"><?= category_icon($product['category']) ?> <?= e(ucfirst($product['category'])) ?></span>
                <?php endif; ?>
                <h1 class="page-title"><?= e($product['name']) ?></h1>
                <p class="product-price product-price-large"><?= e(format_price((int) $product['price'])) ?></p>
                <?php if (!empty($product['description'])): ?>
                    <p class="muted"><?= nl2br(e($product['description'])) ?></p>
                <?php endif; ?>

                <form method="post" action="<?= e(url('panier.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                    <input type="hidden" name="return" value="produit">
                    <button type="submit" class="btn btn-primary btn-block">Ajouter au panier</button>
                </form>
                <p class="muted small">Paiement à la livraison.</p>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
