<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$allowedStatuses = ['new', 'contacted', 'closed'];

if (is_post()) {
    csrf_check();
    $status = post_string('status');

    if (in_array($status, $allowedStatuses, true)) {
        $stmt = db()->prepare('UPDATE site_requests SET status = ? WHERE id = ?');
        $stmt->execute([$status, (int) post_string('id')]);
        flash('success', 'Demande mise à jour.');
    }
    redirect('admin/site-requests.php');
}

$requests = db()->query('SELECT * FROM site_requests ORDER BY id DESC LIMIT 200')->fetchAll();

$pageTitle = 'Demandes de site';
$layout = 'admin';
require __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Demandes de site</h1>

        <?php if (!$requests): ?>
            <p class="muted">Aucune demande pour le moment.</p>
        <?php endif; ?>

        <?php foreach ($requests as $request): ?>
            <article class="glass-card order-card">
                <header class="order-head">
                    <strong><?= e($request['name']) ?></strong>
                    <span class="muted small"><?= e($request['created_at']) ?></span>
                    <span class="badge badge-<?= e($request['status']) ?>"><?= e(status_label($request['status'])) ?></span>
                </header>
                <p>
                    <a href="<?= e(customer_wa_link($request['phone'])) ?>" target="_blank" rel="noopener"><?= e($request['phone']) ?> (WhatsApp)</a>
                    <?php if ($request['email']): ?> · <?= e($request['email']) ?><?php endif; ?>
                </p>
                <?php if ($request['message']): ?>
                    <p class="muted"><?= nl2br(e($request['message'])) ?></p>
                <?php endif; ?>

                <form method="post" action="<?= e(url('admin/site-requests.php')) ?>" class="inline-form status-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                    <select name="status" aria-label="Statut">
                        <?php foreach ($allowedStatuses as $status): ?>
                            <option value="<?= e($status) ?>" <?= $request['status'] === $status ? 'selected' : '' ?>>
                                <?= e(status_label($status)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-secondary btn-small">Mettre à jour</button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
