<?php
/**
 * Carte d'un produit. Variables attendues :
 *   $product        : ligne de la table products
 *   $images         : ses photos (peut être vide)
 *   $returnPage     : 'boutique' ou 'index' (page où revenir après l'ajout)
 *   $returnCategory : catégorie à conserver dans le filtre (optionnel)
 */
$images = $images ?? [];
$detailUrl = url('produit.php?id=' . (int) $product['id']);
?>
<article class="product-card">
    <div class="product-media">
        <?php if ($images): ?>
            <?php $galleryAlt = $product['name']; $gallerySize = 'thumb'; $galleryLink = $detailUrl; ?>
            <?php include __DIR__ . '/gallery.php'; ?>
        <?php else: ?>
            <a class="product-thumb" href="<?= e($detailUrl) ?>" tabindex="-1" aria-hidden="true">
                <span><?= category_icon($product['category'] ?? null) ?></span>
            </a>
        <?php endif; ?>
    </div>

    <h3 class="product-name"><a href="<?= e($detailUrl) ?>"><?= e($product['name']) ?></a></h3>
    <?php if (!empty($product['description'])): ?>
        <p class="muted small product-desc"><?= e($product['description']) ?></p>
    <?php endif; ?>
    <p class="product-price"><?= e(format_price((int) $product['price'])) ?></p>

    <form method="post" action="<?= e(url('panier.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
        <input type="hidden" name="return" value="<?= e($returnPage ?? 'boutique') ?>">
        <input type="hidden" name="category" value="<?= e($returnCategory ?? '') ?>">
        <button type="submit" class="btn btn-primary btn-block">Ajouter au panier</button>
    </form>
</article>
