<?php
/**
 * Page PUBLIQUE de vérification d'un reçu.
 * Le QR code du reçu mène ici. La page affiche ce que la BASE DE DONNÉES contient pour ce code :
 * si le reçu a été modifié (montant, article...), la différence saute aux yeux.
 * Par respect de la vie privée, ni téléphone ni adresse : seulement le nom masqué.
 */
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/orders.php';

/** "Jean Test" => "J••• T•••" */
function mask_name(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name)) ?: [];
    $masked = [];
    foreach ($parts as $part) {
        $masked[] = mb_substr($part, 0, 1) . str_repeat('•', min(max(mb_strlen($part) - 1, 0), 5));
    }
    return implode(' ', $masked);
}

$input = '';
if (isset($_GET['code']) && is_string($_GET['code'])) {
    $input = trim($_GET['code']);
} elseif (is_post()) {
    csrf_check();
    $input = post_string('code');
}

$order = null;
$checked = false;
$tooMany = false;

if ($input !== '') {
    // Limite les essais : 10 codes faux par 10 minutes et par visiteur
    $now = time();
    $fails = array_filter($_SESSION['verify_fails'] ?? [], function ($t) use ($now) { return $t > $now - 600; });

    if (count($fails) >= 10) {
        $tooMany = true;
    } else {
        $checked = true;
        $order = find_order_by_verification_code($input);

        if (!$order) {
            $fails[] = $now;
            usleep(300000);
        }
    }
    $_SESSION['verify_fails'] = array_values($fails);
}

$pageTitle = 'Vérifier un reçu';
$noindex = true;
require __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container narrow">
        <h1 class="page-title">Vérifier un reçu</h1>
        <p class="muted">Saisis le code de vérification imprimé sur le reçu (ou scanne son QR code).</p>

        <form method="post" action="<?= e(url('verifier.php')) ?>" class="form">
            <?= csrf_field() ?>
            <label>Code de vérification
                <input type="text" name="code" value="<?= e($input) ?>" placeholder="XXXX-XXXX-XXXX"
                       maxlength="20" autocapitalize="characters" autocomplete="off" required>
            </label>
            <button type="submit" class="btn btn-primary">Vérifier</button>
        </form>

        <?php if ($tooMany): ?>
            <div class="alert alert-error mt-2" role="alert">Trop d'essais. Réessaie dans quelques minutes.</div>

        <?php elseif ($checked && !$order): ?>
            <div class="alert alert-error mt-2" role="alert">
                <strong>Aucun reçu ne correspond à ce code.</strong><br>
                Vérifie que tu l'as bien recopié. Si le code est correct, ce document n'est pas authentique : ne lui fais pas confiance.
            </div>

        <?php elseif ($order): ?>
            <?php $cancelled = $order['status'] === 'cancelled'; ?>
            <div class="alert <?= $cancelled ? 'alert-error' : 'alert-success' ?> mt-2" role="status">
                <?php if ($cancelled): ?>
                    <strong>Reçu authentique, mais la commande a été annulée.</strong>
                <?php else: ?>
                    <strong>✔ Reçu authentique.</strong> Compare les informations ci-dessous avec ton document.
                <?php endif; ?>
            </div>

            <div class="summary glass-card">
                <div class="summary-row"><span class="muted">Numéro</span><span><?= e(order_document_number($order)) ?></span></div>
                <div class="summary-row"><span class="muted">Date</span><span><?= e(date('d/m/Y à H:i', strtotime($order['created_at']))) ?></span></div>
                <div class="summary-row"><span class="muted">Client</span><span><?= e(mask_name($order['customer_name'])) ?></span></div>
                <div class="summary-row"><span class="muted">Statut</span><span><?= e(status_label($order['status'])) ?></span></div>
                <hr class="sep">
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
                    <span>Total</span>
                    <span><?= e(format_price((int) $order['total_amount'])) ?></span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
