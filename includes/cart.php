<?php
/**
 * Le panier est stocké dans la session : [id_produit => quantité].
 * On ne stocke JAMAIS les prix en session : ils sont relus dans la base à chaque
 * fois, pour qu'un visiteur ne puisse pas les modifier.
 */
require_once __DIR__ . '/functions.php';

const CART_MAX_QTY = 50;

function cart_raw(): array
{
    return (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) ? $_SESSION['cart'] : [];
}

function cart_add(int $productId, int $qty = 1): void
{
    $cart = cart_raw();
    $cart[$productId] = min(($cart[$productId] ?? 0) + $qty, CART_MAX_QTY);
    $_SESSION['cart'] = $cart;
}

function cart_set(int $productId, int $qty): void
{
    $cart = cart_raw();
    if ($qty <= 0) {
        unset($cart[$productId]);
    } elseif (isset($cart[$productId])) {
        $cart[$productId] = min($qty, CART_MAX_QTY);
    }
    $_SESSION['cart'] = $cart;
}

function cart_remove(int $productId): void
{
    $cart = cart_raw();
    unset($cart[$productId]);
    $_SESSION['cart'] = $cart;
}

function cart_clear(): void
{
    unset($_SESSION['cart']);
}

/** Nombre total d'articles (pour la pastille du menu). */
function cart_count(): int
{
    return (int) array_sum(cart_raw());
}

/**
 * Les lignes du panier, avec les prix actuels lus dans la base.
 * Retire au passage les produits qui n'existent plus ou ne sont plus en vente.
 */
function cart_items(): array
{
    $cart = cart_raw();
    if (!$cart) {
        return [];
    }

    $ids = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = db()->prepare(
        "SELECT id, name, price, category FROM products
         WHERE is_active = 1 AND id IN ($placeholders)
         ORDER BY name"
    );
    $stmt->execute($ids);

    $items = [];
    foreach ($stmt as $product) {
        $id = (int) $product['id'];
        $price = (int) $product['price'];
        $quantity = (int) $cart[$id];

        $items[] = [
            'id'         => $id,
            'name'       => $product['name'],
            'category'   => $product['category'],
            'price'      => $price,
            'quantity'   => $quantity,
            'line_total' => $price * $quantity,
        ];
    }

    // Nettoyage : on garde en session seulement les produits trouvés
    $found = array_column($items, 'id');
    foreach ($ids as $id) {
        if (!in_array($id, $found, true)) {
            unset($cart[$id]);
        }
    }
    $_SESSION['cart'] = $cart;

    return $items;
}

function cart_subtotal(array $items): int
{
    return (int) array_sum(array_column($items, 'line_total'));
}
