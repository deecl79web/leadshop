<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/products.php';
require_admin();

// Si les fichiers dépassent la limite de PHP (post_max_size), $_POST et $_FILES arrivent
// complètement vides : on l'explique plutôt que d'afficher "formulaire expiré".
if (is_post() && empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    flash('error', 'Les photos sont trop volumineuses pour le serveur (limite : '
        . ini_get('post_max_size') . ' par envoi). Envoie moins de photos à la fois.');
    redirect('admin/products.php');
}

$errors = [];
$editing = null;

// --- Enregistrer (ajout ou modification) + photos ---------------------------
if (is_post() && post_string('action') === 'save') {
    csrf_check();

    $id          = (int) post_string('id');
    $name        = post_string('name');
    $description = post_string('description');
    $priceRaw    = post_string('price');
    $category    = strtolower(post_string('category'));
    $isActive    = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '' || mb_strlen($name) > 255) {
        $errors[] = 'Le nom est obligatoire (255 caractères maximum).';
    }
    if (!ctype_digit($priceRaw) || (int) $priceRaw < 1 || (int) $priceRaw > 10000000) {
        $errors[] = 'Le prix doit être un nombre entier de FCFA (ex : 2500).';
    }
    if (mb_strlen($category) > 100) {
        $errors[] = 'La catégorie est trop longue.';
    }
    if (mb_strlen($description) > 2000) {
        $errors[] = 'La description est trop longue (2000 caractères maximum).';
    }

    if (!$errors) {
        $params = [
            $name,
            $description !== '' ? $description : null,
            (int) $priceRaw,
            $category !== '' ? $category : null,
            $isActive,
        ];

        if ($id > 0) {
            $stmt = db()->prepare(
                'UPDATE products SET name = ?, description = ?, price = ?, category = ?, is_active = ? WHERE id = ?'
            );
            $stmt->execute([...$params, $id]);
            $productId = $id;
            $message = 'Produit modifié';
        } else {
            $stmt = db()->prepare(
                'INSERT INTO products (name, description, price, category, is_active) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute($params);
            $productId = (int) db()->lastInsertId();
            $message = 'Produit ajouté';
        }

        // Photos (facultatives)
        $photoErrors = [];
        if (isset($_FILES['images'])) {
            $photos = save_product_images($productId, $_FILES['images']);
            $photoErrors = $photos['errors'];
            if ($photos['saved'] > 0) {
                $message .= ' · ' . $photos['saved'] . ' photo' . ($photos['saved'] > 1 ? 's' : '') . ' ajoutée' . ($photos['saved'] > 1 ? 's' : '');
            }
        }

        flash('success', $message . '.');
        foreach ($photoErrors as $photoError) {
            flash('error', $photoError);
        }

        // En cas de problème avec une photo, on revient sur le produit pour réessayer
        redirect($photoErrors ? 'admin/products.php?edit=' . $productId : 'admin/products.php');
    }

    // En cas d'erreur, on réaffiche ce qui a été saisi
    $editing = [
        'id' => $id, 'name' => $name, 'description' => $description,
        'price' => $priceRaw, 'category' => $category, 'is_active' => $isActive,
    ];
}

// --- Activer / désactiver --------------------------------------------
if (is_post() && post_string('action') === 'toggle') {
    csrf_check();
    $stmt = db()->prepare('UPDATE products SET is_active = 1 - is_active WHERE id = ?');
    $stmt->execute([(int) post_string('id')]);
    flash('success', 'Visibilité du produit modifiée.');
    redirect('admin/products.php');
}

// --- Supprimer une photo / la mettre en première -------------------------
if (is_post() && in_array(post_string('action'), ['delete_image', 'make_first'], true)) {
    csrf_check();
    $imageId   = (int) post_string('image_id');
    $productId = (int) post_string('product_id');

    if (post_string('action') === 'delete_image') {
        $deleted = delete_product_image($imageId, $productId);
        flash($deleted ? 'success' : 'error', $deleted ? 'Photo supprimée.' : 'Photo introuvable.');
    } else {
        make_image_first($imageId, $productId);
        flash('success', 'Photo placée en première position.');
    }
    redirect('admin/products.php?edit=' . $productId);
}

// --- Produit à modifier (?edit=ID) ------------------------------------
if ($editing === null && isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch() ?: null;
}

$products = db()->query('SELECT * FROM products ORDER BY id DESC')->fetchAll();

// Photos : celles du produit en cours de modification + une miniature par ligne de la liste
$imagesByProduct = get_images_for_products(array_column($products, 'id'));
$editingId = (int) ($editing['id'] ?? 0);
$editingImages = $editingId > 0 ? ($imagesByProduct[$editingId] ?? []) : [];

$pageTitle = 'Produits';
$layout = 'admin';
require __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Produits</h1>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php endforeach; ?>

        <div class="glass-card">
            <h2 class="card-title"><?= $editingId > 0 ? 'Modifier le produit' : 'Ajouter un produit' ?></h2>
            <form method="post" action="<?= e(url('admin/products.php')) ?>" enctype="multipart/form-data" class="form form-wide">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= $editingId ?>">

                <label>Nom
                    <input type="text" name="name" value="<?= e($editing['name'] ?? '') ?>" required maxlength="255">
                </label>
                <label>Prix (FCFA, nombre entier)
                    <input type="number" name="price" value="<?= e($editing['price'] ?? '') ?>" required min="1" step="1">
                </label>
                <label>Catégorie (ex : parfum, bijoux, maison)
                    <input type="text" name="category" value="<?= e($editing['category'] ?? '') ?>" maxlength="100">
                </label>
                <label>Description
                    <textarea name="description" rows="3" maxlength="2000"><?= e($editing['description'] ?? '') ?></textarea>
                </label>
                <label>Photos (<?= $editingId > 0 ? 'ajouter d\'autres photos' : 'une ou plusieurs' ?>)
                    <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
                    <span class="small">JPEG, PNG ou WebP · <?= PRODUCT_IMAGES_MAX ?> photos maximum par produit · redimensionnées automatiquement.
                        La première photo est celle qui s'affiche en premier.</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="is_active" value="1" <?= !isset($editing) || !empty($editing['is_active']) ? 'checked' : '' ?>>
                    En vente (visible dans la boutique)
                </label>

                <div class="actions">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <?php if ($editing): ?>
                        <a class="btn btn-secondary" href="<?= e(url('admin/products.php')) ?>">Annuler</a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ($editingImages): ?>
                <h3 class="card-title mt-2">Photos actuelles (<?= count($editingImages) ?>/<?= PRODUCT_IMAGES_MAX ?>)</h3>
                <div class="admin-photos">
                    <?php foreach ($editingImages as $index => $image): ?>
                        <figure class="admin-photo">
                            <img src="<?= e(product_image_url($image['file_name'], true)) ?>" alt="Photo <?= $index + 1 ?>" loading="lazy">
                            <figcaption>
                                <?php if ($index === 0): ?>
                                    <span class="badge badge-issued">Principale</span>
                                <?php else: ?>
                                    <form method="post" action="<?= e(url('admin/products.php')) ?>" class="inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="make_first">
                                        <input type="hidden" name="image_id" value="<?= (int) $image['id'] ?>">
                                        <input type="hidden" name="product_id" value="<?= $editingId ?>">
                                        <button type="submit" class="link-button">Mettre en première</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="<?= e(url('admin/products.php')) ?>" class="inline-form" data-confirm="Supprimer cette photo ?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_image">
                                    <input type="hidden" name="image_id" value="<?= (int) $image['id'] ?>">
                                    <input type="hidden" name="product_id" value="<?= $editingId ?>">
                                    <button type="submit" class="link-danger">Supprimer</button>
                                </form>
                            </figcaption>
                        </figure>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="table-wrap mt-2">
            <table>
                <thead>
                    <tr><th></th><th>Produit</th><th>Catégorie</th><th>Prix</th><th>Statut</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($products as $product): ?>
                    <?php $first = $imagesByProduct[(int) $product['id']][0] ?? null; ?>
                    <tr>
                        <td>
                            <?php if (!empty($first['file_name'])): ?>
                                <img class="thumb-sm" src="<?= e(product_image_url($first['file_name'], true)) ?>" alt="">
                            <?php else: ?>
                                <span class="thumb-sm thumb-empty" aria-label="Aucune image" title="Aucune image">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($product['name']) ?></td>
                        <td><?= e($product['category'] ?? '—') ?></td>
                        <td><?= e(format_price((int) $product['price'])) ?></td>
                        <td>
                            <span class="badge <?= $product['is_active'] ? 'badge-issued' : 'badge-cancelled' ?>">
                                <?= $product['is_active'] ? 'En vente' : 'Masqué' ?>
                            </span>
                        </td>
                        <td class="row-actions">
                            <a href="<?= e(url('admin/products.php?edit=' . (int) $product['id'])) ?>">Modifier</a>
                            <form method="post" action="<?= e(url('admin/products.php')) ?>" class="inline-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                <button type="submit" class="link-button"><?= $product['is_active'] ? 'Masquer' : 'Remettre en vente' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
