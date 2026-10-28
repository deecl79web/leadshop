<?php
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/products.php';

$pageTitle = 'Boutique';

// Catégories disponibles (pour les boutons de filtre)
$categories = db()->query(
    "SELECT DISTINCT category FROM products
     WHERE is_active = 1 AND category IS NOT NULL AND category <> ''
     ORDER BY category"
)->fetchAll(PDO::FETCH_COLUMN);

// Catégorie choisie : acceptée seulement si elle existe vraiment
$category = (isset($_GET['category']) && is_string($_GET['category'])) ? trim($_GET['category']) : '';
if (!in_array($category, $categories, true)) {
    $category = '';
}

if ($category !== '') {
    $stmt = db()->prepare(
        'SELECT id, name, description, price, category, image_path
         FROM products WHERE is_active = 1 AND category = ? ORDER BY name'
    );
    $stmt->execute([$category]);
    $products = $stmt->fetchAll();
} else {
    $products = db()->query(
        'SELECT id, name, description, price, category, image_path
         FROM products WHERE is_active = 1 ORDER BY name'
    )->fetchAll();
}

$imagesByProduct = get_images_for_products(array_column($products, 'id'));

require __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Boutique</h1>

        <?php if ($categories): ?>
            <nav class="chips" aria-label="Catégories">
                <a class="chip <?= $category === '' ? 'is-active' : '' ?>" href="<?= e(url('boutique.php')) ?>">Tout</a>
                <?php foreach ($categories as $name): ?>
                    <a class="chip <?= $category === $name ? 'is-active' : '' ?>"
                       href="<?= e(url('boutique.php?category=' . rawurlencode($name))) ?>">
                        <?= category_icon($name) ?> <?= e(ucfirst($name)) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <?php if (!$products): ?>
            <p class="muted">Aucun article disponible pour le moment.</p>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <?php $returnPage = 'boutique'; $returnCategory = $category; $images = $imagesByProduct[(int) $product['id']] ?? []; ?>
                    <?php include __DIR__ . '/includes/product_card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
