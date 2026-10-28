<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

function count_rows(string $sql): int
{
    return (int) db()->query($sql)->fetchColumn();
}

$stats = [
    ['Commandes en attente',   count_rows("SELECT COUNT(*) FROM orders WHERE status = 'pending'"),         'admin/orders.php'],
    ['Nouvelles demandes de site', count_rows("SELECT COUNT(*) FROM site_requests WHERE status = 'new'"), 'admin/site-requests.php'],
    ['Produits en vente',      count_rows('SELECT COUNT(*) FROM products WHERE is_active = 1'),            'admin/products.php'],
    ['Tickets disponibles',    count_rows("SELECT COUNT(*) FROM tickets WHERE status = 'issued'"),         'admin/tickets.php'],
];

$pageTitle = 'Tableau de bord';
$layout = 'admin';
require __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Tableau de bord</h1>
        <p class="muted">Connecté en tant que <?= e($_SESSION['admin_name'] ?? '') ?>.</p>

        <div class="grid-4">
            <?php foreach ($stats as [$label, $value, $link]): ?>
                <a class="glass-card stat-card" href="<?= e(url($link)) ?>">
                    <span class="stat-value"><?= (int) $value ?></span>
                    <span class="muted"><?= e($label) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
