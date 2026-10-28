<?php
/** Téléchargement du reçu de n'importe quelle commande, réservé à l'administrateur. */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/invoice.php';
require_admin();

$order = get_order((int) ($_GET['id'] ?? 0));

if (!$order) {
    http_response_code(404);
    exit('Commande introuvable.');
}

send_order_pdf($order);
