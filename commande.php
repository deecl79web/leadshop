<?php
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/orders.php';

$items = cart_items();
if (!$items) {
    flash('error', 'Ton panier est vide.');
    redirect('boutique.php');
}
$subtotal = cart_subtotal($items);

$errors = [];
$values = ['name' => '', 'phone' => '', 'address' => '', 'ticket' => ''];

if (is_post()) {
    csrf_check();

    $values = [
        'name'    => post_string('customer_name'),
        'phone'   => post_string('customer_phone'),
        'address' => post_string('customer_address'),
        'ticket'  => strtoupper(post_string('ticket_code')),
    ];

    // --- Validation ---
    $nameLength = mb_strlen($values['name']);
    if ($nameLength < 2 || $nameLength > 100) {
        $errors[] = 'Indique ton nom (entre 2 et 100 caractères).';
    }
    if (!preg_match('/^\+?[0-9][0-9\s().-]{6,19}$/', $values['phone'])) {
        $errors[] = 'Indique un numéro de téléphone valide.';
    }
    $addressLength = mb_strlen($values['address']);
    if ($addressLength < 5 || $addressLength > 500) {
        $errors[] = "Indique une adresse de livraison (entre 5 et 500 caractères).";
    }
    if (!preg_match('/^[A-Z0-9]{0,12}$/', $values['ticket'])) {
        $errors[] = 'Le code de réduction est invalide.';
    }

    if (!$errors) {
        try {
            $orderId = create_order(
                ['name' => $values['name'], 'phone' => $values['phone'], 'address' => $values['address']],
                $items,
                $values['ticket']
            );

            cart_clear();
            // On garde le numéro en session : seul celui qui vient de commander
            // peut voir la page de confirmation (pas d'accès par numéro).
            $_SESSION['last_order_id'] = $orderId;
            redirect('confirmation.php');
        } catch (OrderException $e) {
            $errors[] = $e->getMessage();
        } catch (Throwable $e) {
            error_log('Erreur commande : ' . $e->getMessage());
            $errors[] = 'Une erreur est survenue, ta commande n\'a pas été enregistrée. Réessaie dans un instant.';
        }
    }
}

$pageTitle = 'Finaliser la commande';
require __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container narrow">
        <h1 class="page-title">Finaliser la commande</h1>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php endforeach; ?>

        <div class="summary glass-card">
            <?php foreach ($items as $item): ?>
                <div class="summary-row">
                    <span><?= e($item['name']) ?> × <?= (int) $item['quantity'] ?></span>
                    <span><?= e(format_price($item['line_total'])) ?></span>
                </div>
            <?php endforeach; ?>
            <div class="summary-row summary-total">
                <span>Sous-total</span>
                <span><?= e(format_price($subtotal)) ?></span>
            </div>
            <p class="muted small">La réduction exacte est calculée à la validation de la commande.</p>
        </div>

        <form method="post" action="<?= e(url('commande.php')) ?>" class="form">
            <?= csrf_field() ?>

            <label>Nom complet
                <input type="text" name="customer_name" value="<?= e($values['name']) ?>" required maxlength="100" autocomplete="name">
            </label>
            <label>Téléphone
                <input type="tel" name="customer_phone" value="<?= e($values['phone']) ?>" required maxlength="20"
                       placeholder="+237 6XX XXX XXX" autocomplete="tel">
            </label>
            <label>Adresse de livraison
                <textarea name="customer_address" rows="3" required maxlength="500"><?= e($values['address']) ?></textarea>
            </label>
            <label>Code de réduction (optionnel)
                <input type="text" name="ticket_code" value="<?= e($values['ticket']) ?>" maxlength="12"
                       placeholder="Gagné à l'arcade" autocapitalize="characters">
            </label>

            <button type="submit" class="btn btn-primary btn-block">Confirmer la commande</button>
            <p class="muted small">Tu payes à la livraison. On te recontacte pour convenir du rendez-vous.</p>
        </form>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
