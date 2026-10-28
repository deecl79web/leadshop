<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/orders.php';
require_admin();

$allowedStatuses = ['pending', 'confirmed', 'delivered', 'cancelled'];

if (is_post()) {
    csrf_check();
    $status = post_string('status');

    if (in_array($status, $allowedStatuses, true)) {
        $stmt = db()->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $stmt->execute([$status, (int) post_string('order_id')]);
        flash('success', 'Statut de la commande mis à jour.');
    }
    redirect('admin/orders.php');
}

$orders = db()->query(
    'SELECT o.*, t.code AS ticket_code
     FROM orders o LEFT JOIN tickets t ON t.id = o.ticket_id
     ORDER BY o.id DESC LIMIT 100'
)->fetchAll();

// Toutes les lignes des commandes affichées, en une seule requête
$itemsByOrder = [];
if ($orders) {
    $ids = array_map('intval', array_column($orders, 'id'));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT * FROM order_items WHERE order_id IN ($placeholders) ORDER BY id");
    $stmt->execute($ids);
    foreach ($stmt as $item) {
        $itemsByOrder[(int) $item['order_id']][] = $item;
    }
}

$pageTitle = 'Commandes';
$layout = 'admin';
require __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Commandes</h1>

        <?php if (!$orders): ?>
            <p class="muted">Aucune commande pour le moment.</p>
        <?php endif; ?>

        <?php foreach ($orders as $order): ?>
            <article class="glass-card order-card">
                <header class="order-head">
                    <strong>Commande n°<?= (int) $order['id'] ?></strong>
                    <span class="muted small"><?= e($order['created_at']) ?></span>
                    <span class="badge badge-<?= e($order['status']) ?>"><?= e(status_label($order['status'])) ?></span>
                    <?php if (!empty($order['verification_code'])): ?>
                        <span class="muted small">Code reçu : <code><?= e(format_verification_code($order['verification_code'])) ?></code></span>
                    <?php endif; ?>
                </header>

                <p>
                    <?= e($order['customer_name']) ?> ·
                    <a href="<?= e(customer_wa_link($order['customer_phone'])) ?>" target="_blank" rel="noopener">
                        <?= e($order['customer_phone']) ?> (WhatsApp)
                    </a><br>
                    <span class="muted"><?= nl2br(e($order['customer_address'])) ?></span>
                </p>

                <ul class="order-items">
                    <?php foreach ($itemsByOrder[(int) $order['id']] ?? [] as $item): ?>
                        <li><?= e($item['product_name']) ?> × <?= (int) $item['quantity'] ?> — <?= e(format_price((int) $item['line_total'])) ?></li>
                    <?php endforeach; ?>
                </ul>

                <p>
                    <?php if ((int) $order['discount_amount'] > 0): ?>
                        <span class="muted">Réduction (code <?= e($order['ticket_code'] ?? '?') ?>) : − <?= e(format_price((int) $order['discount_amount'])) ?></span><br>
                    <?php endif; ?>
                    <strong>Total : <?= e(format_price((int) $order['total_amount'])) ?></strong>
                </p>

                <form method="post" action="<?= e(url('admin/orders.php')) ?>" class="inline-form status-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                    <select name="status" aria-label="Statut">
                        <?php foreach ($allowedStatuses as $status): ?>
                            <option value="<?= e($status) ?>" <?= $order['status'] === $status ? 'selected' : '' ?>>
                                <?= e(status_label($status)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-secondary btn-small">Mettre à jour</button>
                    <a class="btn btn-secondary btn-small" href="<?= e(url('admin/recu.php?id=' . (int) $order['id'])) ?>">📄 Reçu PDF</a>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
