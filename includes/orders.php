<?php
/**
 * Logique des commandes et des tickets de réduction.
 */
require_once __DIR__ . '/functions.php';

/** Erreur dont le message peut être montré tel quel au client. */
class OrderException extends RuntimeException
{
}

/** Montant de la réduction d'un ticket sur un sous-total (toujours un entier). */
function ticket_discount(array $ticket, int $subtotal): int
{
    $value = (int) $ticket['discount_value'];

    if ($ticket['discount_type'] === 'percentage') {
        return min(intdiv($subtotal * $value, 100), $subtotal);
    }

    // Réduction fixe : jamais plus que le sous-total lui-même
    return min($value, $subtotal);
}

/**
 * Enregistre une commande. Tout se passe dans UNE transaction : si une étape
 * échoue, rien n'est enregistré (pas de commande sans lignes, pas de ticket
 * "brûlé" sans commande).
 *
 * @param array  $customer   ['name' => ..., 'phone' => ..., 'address' => ...]
 * @param array  $items      lignes retournées par cart_items()
 * @param string $ticketCode code de réduction (peut être vide)
 * @return int   numéro de la commande créée
 */
function create_order(array $customer, array $items, string $ticketCode = ''): int
{
    if (!$items) {
        throw new OrderException('Ton panier est vide.');
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $subtotal = (int) array_sum(array_column($items, 'line_total'));
        $discount = 0;
        $ticketId = null;

        $code = strtoupper(trim($ticketCode));
        if ($code !== '') {
            // FOR UPDATE : verrouille la ligne, deux clients ne peuvent pas
            // utiliser le même code en même temps.
            $stmt = $pdo->prepare('SELECT * FROM tickets WHERE code = ? FOR UPDATE');
            $stmt->execute([$code]);
            $ticket = $stmt->fetch();

            if (!$ticket) {
                throw new OrderException('Code de réduction introuvable.');
            }
            if ($ticket['status'] !== 'issued') {
                throw new OrderException("Ce code a déjà été utilisé ou n'est plus valable.");
            }

            $discount = ticket_discount($ticket, $subtotal);
            $ticketId = (int) $ticket['id'];
        }

        $total = max($subtotal - $discount, 0);

        $stmt = $pdo->prepare(
            'INSERT INTO orders
                (customer_name, customer_phone, customer_address,
                 subtotal_amount, discount_amount, total_amount, ticket_id, verification_code, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'pending\')'
        );
        $stmt->execute([
            $customer['name'],
            $customer['phone'],
            $customer['address'],
            $subtotal,
            $discount,
            $total,
            $ticketId,
            generate_verification_code(),
        ]);
        $orderId = (int) $pdo->lastInsertId();

        // On copie le nom et le prix du moment dans la commande : si tu changes
        // tes prix plus tard, les anciennes commandes ne bougent pas.
        $stmt = $pdo->prepare(
            'INSERT INTO order_items
                (order_id, product_id, product_name, unit_price, quantity, line_total)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        foreach ($items as $item) {
            $stmt->execute([
                $orderId,
                $item['id'],
                $item['name'],
                $item['price'],
                $item['quantity'],
                $item['line_total'],
            ]);
        }

        if ($ticketId !== null) {
            $stmt = $pdo->prepare(
                "UPDATE tickets SET status = 'redeemed', redeemed_at = NOW()
                 WHERE id = ? AND status = 'issued'"
            );
            $stmt->execute([$ticketId]);
            if ($stmt->rowCount() !== 1) {
                throw new OrderException("Ce code vient d'être utilisé.");
            }
        }

        $pdo->commit();
        return $orderId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/** Une commande avec ses lignes, ou null si elle n'existe pas. */
function get_order(int $orderId): ?array
{
    $stmt = db()->prepare(
        'SELECT o.*, t.code AS ticket_code
         FROM orders o LEFT JOIN tickets t ON t.id = o.ticket_id
         WHERE o.id = ?'
    );
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) {
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
    $stmt->execute([$orderId]);
    $order['items'] = $stmt->fetchAll();

    return $order;
}

/** Code de ticket de 8 caractères, sans caractères ambigus (O/0, I/1). */
function generate_ticket_code(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $max = strlen($alphabet) - 1;

    do {
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }
        $stmt = db()->prepare('SELECT 1 FROM tickets WHERE code = ?');
        $stmt->execute([$code]);
    } while ($stmt->fetchColumn());

    return $code;
}


// --- Code de vérification des reçus -----------------------------------------

/**
 * Code unique de 12 caractères imprimé sur le reçu (et dans son QR code).
 * 32 symboles ^ 12 positions = environ 1 000 000 000 000 000 000 combinaisons :
 * impossible à deviner. Sans caractères ambigus (pas de O/0 ni I/1).
 */
function generate_verification_code(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $max = strlen($alphabet) - 1;

    do {
        $code = '';
        for ($i = 0; $i < 12; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }
        $stmt = db()->prepare('SELECT 1 FROM orders WHERE verification_code = ?');
        $stmt->execute([$code]);
    } while ($stmt->fetchColumn());

    return $code;
}

/** K7M2Q9XP4TWD => K7M2-Q9XP-4TWD (affichage plus lisible) */
function format_verification_code(string $code): string
{
    return implode('-', str_split($code, 4));
}

/**
 * Retourne le code de vérification d'une commande. Les commandes passées AVANT
 * l'ajout de cette fonction n'en ont pas : on leur en crée un à la volée.
 */
function ensure_verification_code(array $order): string
{
    if (!empty($order['verification_code'])) {
        return $order['verification_code'];
    }

    // "AND verification_code IS NULL" : si deux personnes déclenchent la création en même
    // temps, le premier code enregistré gagne et on relit celui-là.
    $stmt = db()->prepare('UPDATE orders SET verification_code = ? WHERE id = ? AND verification_code IS NULL');
    $stmt->execute([generate_verification_code(), (int) $order['id']]);

    $stmt = db()->prepare('SELECT verification_code FROM orders WHERE id = ?');
    $stmt->execute([(int) $order['id']]);
    return (string) $stmt->fetchColumn();
}

/** La commande qui correspond à un code (avec ses lignes), ou null. */
function find_order_by_verification_code(string $input): ?array
{
    // Accepte "k7m2-q9xp-4twd", avec espaces, en minuscules...
    $code = preg_replace('/[^A-Z0-9]/', '', strtoupper($input));
    if (!preg_match('/^[A-Z0-9]{12}$/', $code)) {
        return null;
    }

    $stmt = db()->prepare('SELECT id FROM orders WHERE verification_code = ?');
    $stmt->execute([$code]);
    $id = $stmt->fetchColumn();

    return $id ? get_order((int) $id) : null;
}

/** Numéro du reçu, ex : RC-2026-000042 (préfixe réglable avec DOC_PREFIX dans config.php). */
function order_document_number(array $order): string
{
    $prefix = defined('DOC_PREFIX') ? (string) DOC_PREFIX : 'RC';
    $year = date('Y', strtotime($order['created_at']));
    return $prefix . '-' . $year . '-' . str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT);
}
