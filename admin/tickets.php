<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/orders.php';
require_admin();

$errors = [];

// --- Créer un ticket ----------------------------------------------------
if (is_post() && post_string('action') === 'create') {
    csrf_check();
    $type = post_string('discount_type');
    $valueRaw = post_string('discount_value');

    if (!in_array($type, ['percentage', 'fixed_amount'], true)) {
        $errors[] = 'Type de réduction invalide.';
    }
    if (!ctype_digit($valueRaw) || (int) $valueRaw < 1) {
        $errors[] = 'La valeur doit être un nombre entier positif.';
    } elseif ($type === 'percentage' && (int) $valueRaw > 100) {
        $errors[] = 'Un pourcentage ne peut pas dépasser 100.';
    } elseif ($type === 'fixed_amount' && (int) $valueRaw > 1000000) {
        $errors[] = 'Montant trop élevé.';
    }

    if (!$errors) {
        $code = generate_ticket_code();
        $stmt = db()->prepare('INSERT INTO tickets (code, discount_type, discount_value) VALUES (?, ?, ?)');
        $stmt->execute([$code, $type, (int) $valueRaw]);
        flash('success', 'Ticket créé : ' . $code);
        redirect('admin/tickets.php');
    }
}

// --- Annuler un ticket encore disponible ------------------------------
if (is_post() && post_string('action') === 'cancel') {
    csrf_check();
    $stmt = db()->prepare("UPDATE tickets SET status = 'cancelled' WHERE id = ? AND status = 'issued'");
    $stmt->execute([(int) post_string('id')]);
    flash('success', 'Ticket annulé.');
    redirect('admin/tickets.php');
}

$tickets = db()->query('SELECT * FROM tickets ORDER BY id DESC LIMIT 200')->fetchAll();

$pageTitle = 'Tickets de réduction';
$layout = 'admin';
require __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Tickets de réduction</h1>
        <p class="muted">Pour l'instant tu les crées ici à la main. Plus tard, l'arcade les créera quand un joueur échangera ses pièces.</p>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php endforeach; ?>

        <div class="glass-card">
            <h2 class="card-title">Créer un ticket</h2>
            <form method="post" action="<?= e(url('admin/tickets.php')) ?>" class="form form-wide">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <label>Type
                    <select name="discount_type">
                        <option value="percentage">Pourcentage (%)</option>
                        <option value="fixed_amount">Montant fixe (FCFA)</option>
                    </select>
                </label>
                <label>Valeur
                    <input type="number" name="discount_value" required min="1" step="1">
                </label>
                <div class="actions"><button type="submit" class="btn btn-primary">Créer</button></div>
            </form>
        </div>

        <div class="table-wrap mt-2">
            <table>
                <thead><tr><th>Code</th><th>Réduction</th><th>Statut</th><th>Créé le</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td><code><?= e($ticket['code']) ?></code></td>
                        <td>
                            <?= $ticket['discount_type'] === 'percentage'
                                ? (int) $ticket['discount_value'] . ' %'
                                : e(format_price((int) $ticket['discount_value'])) ?>
                        </td>
                        <td><span class="badge badge-<?= e($ticket['status']) ?>"><?= e(status_label($ticket['status'])) ?></span></td>
                        <td class="small muted"><?= e($ticket['issued_at']) ?></td>
                        <td>
                            <?php if ($ticket['status'] === 'issued'): ?>
                                <form method="post" action="<?= e(url('admin/tickets.php')) ?>" class="inline-form" data-confirm="Annuler ce ticket ?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="id" value="<?= (int) $ticket['id'] ?>">
                                    <button type="submit" class="link-danger">Annuler</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
