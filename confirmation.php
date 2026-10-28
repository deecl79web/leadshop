<?php
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/orders.php';

// Seule la commande qu'on vient de passer est consultable (via la session)
$orderId = (int) ($_SESSION['last_order_id'] ?? 0);
$order = $orderId > 0 ? get_order($orderId) : null;

if (!$order) {
    redirect('boutique.php');
}

$pageTitle = 'Commande confirmée';
require __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container narrow">
        <h1 class="page-title">Commande n°<?= (int) $order['id'] ?> confirmée 🎉</h1>
        <p class="muted">On te recontacte bientôt au <?= e($order['customer_phone']) ?> pour organiser la livraison et le paiement.</p>

        <div class="summary glass-card">
            <?php foreach ($order['items'] as $item): ?>
                <div class="summary-row">
                    <span><?= e($item['product_name']) ?> × <?= (int) $item['quantity'] ?></span>
                    <span><?= e(format_price((int) $item['line_total'])) ?></span>
                </div>
            <?php endforeach; ?>

            <?php if ((int) $order['discount_amount'] > 0): ?>
                <div class="summary-row summary-discount">
                    <span>Réduction</span>
                    <span>− <?= e(format_price((int) $order['discount_amount'])) ?></span>
                </div>
            <?php endif; ?>

            <div class="summary-row summary-total">
                <span>Total à payer</span>
                <span><?= e(format_price((int) $order['total_amount'])) ?></span>
            </div>
        </div>

        <div class="actions">
            <a class="btn btn-primary" href="<?= e(url('recu.php')) ?>">📄 Télécharger mon reçu (PDF)</a>
            <a class="btn btn-secondary" target="_blank" rel="noopener"
               href="<?= e(wa_link('Bonjour, je viens de passer la commande n°' . $order['id'] . '.')) ?>">
                💬 Nous écrire sur WhatsApp
            </a>
            <a class="btn btn-secondary" href="<?= e(url('boutique.php')) ?>">Retour à la boutique</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
