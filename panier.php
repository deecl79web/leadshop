<?php
require_once __DIR__ . '/includes/cart.php';

// --- Actions sur le panier (formulaires POST) ---------------------------
if (is_post()) {
    csrf_check();
    $action = post_string('action');
    $productId = (int) post_string('product_id');

    if ($action === 'add') {
        $stmt = db()->prepare('SELECT id FROM products WHERE id = ? AND is_active = 1');
        $stmt->execute([$productId]);

        if ($stmt->fetchColumn()) {
            cart_add($productId);
            flash('success', 'Article ajouté au panier.');
        } else {
            flash('error', "Cet article n'est plus disponible.");
        }

        // Retour à la page d'où vient le clic (liste blanche, jamais une URL libre)
        $category = post_string('category');
        if (post_string('return') === 'index') {
            redirect('index.php');
        }
        if (post_string('return') === 'produit' && $productId > 0) {
            redirect('produit.php?id=' . $productId);
        }
        redirect('boutique.php' . ($category !== '' ? '?category=' . rawurlencode($category) : ''));
    }

    if ($action === 'update') {
        $quantities = (isset($_POST['qty']) && is_array($_POST['qty'])) ? $_POST['qty'] : [];
        foreach ($quantities as $id => $qty) {
            cart_set((int) $id, (int) $qty);
        }
        flash('success', 'Panier mis à jour.');
    }

    if ($action === 'remove') {
        cart_remove($productId);
        flash('success', 'Article retiré.');
    }

    if ($action === 'clear') {
        cart_clear();
        flash('success', 'Panier vidé.');
    }

    redirect('panier.php');
}

$pageTitle = 'Panier';
$items = cart_items();
$subtotal = cart_subtotal($items);

require __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container narrow">
        <h1 class="page-title">Panier</h1>

        <?php if (!$items): ?>
            <p class="muted">Ton panier est vide.</p>
            <p><a class="btn btn-primary" href="<?= e(url('boutique.php')) ?>">Voir la boutique</a></p>
        <?php else: ?>
            <form method="post" action="<?= e(url('panier.php')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">

                <div class="cart-list">
                    <?php foreach ($items as $item): ?>
                        <div class="cart-row">
                            <div class="cart-info">
                                <strong><?= e($item['name']) ?></strong>
                                <span class="muted small"><?= e(format_price($item['price'])) ?> l'unité</span>
                            </div>
                            <label class="cart-qty">
                                <span class="sr-only">Quantité</span>
                                <input type="number" name="qty[<?= (int) $item['id'] ?>]"
                                       value="<?= (int) $item['quantity'] ?>" min="0" max="<?= CART_MAX_QTY ?>">
                            </label>
                            <span class="cart-line"><?= e(format_price($item['line_total'])) ?></span>
                            <button type="submit" form="remove-<?= (int) $item['id'] ?>" class="link-danger">Retirer</button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary glass-card">
                    <div class="summary-row summary-total">
                        <span>Sous-total</span>
                        <span><?= e(format_price($subtotal)) ?></span>
                    </div>
                    <p class="muted small">Un code de réduction peut être appliqué à l'étape suivante.</p>
                </div>

                <div class="actions">
                    <button type="submit" class="btn btn-secondary">Mettre à jour les quantités</button>
                    <a class="btn btn-primary" href="<?= e(url('commande.php')) ?>">Passer la commande</a>
                </div>
            </form>

            <?php /* Formulaires "Retirer" séparés (on ne peut pas imbriquer des <form>) */ ?>
            <?php foreach ($items as $item): ?>
                <form method="post" action="<?= e(url('panier.php')) ?>" id="remove-<?= (int) $item['id'] ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>">
                </form>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
