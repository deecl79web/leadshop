<?php
/**
 * Téléchargement du reçu par le client.
 * Sans compte client, seule la commande qu'on VIENT de passer (gardée en session)
 * peut être téléchargée : impossible de lire la commande d'un autre en devinant un numéro.
 */
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/invoice.php';

$orderId = (int) ($_SESSION['last_order_id'] ?? 0);
$order = $orderId > 0 ? get_order($orderId) : null;

if (!$order) {
    flash('error', 'Aucune commande récente à télécharger.');
    redirect('boutique.php');
}

send_order_pdf($order);
