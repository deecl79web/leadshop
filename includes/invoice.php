<?php
/**
 * Génération du reçu de commande en PDF (bibliothèque TCPDF, dans lib/tcpdf).
 *
 * Réglages facultatifs à ajouter dans config/config.php (valeurs par défaut sinon) :
 *   define('DOC_TITLE',  'Reçu de commande');     // plus tard : 'Facture'
 *   define('DOC_PREFIX', 'RC');                    // numéro : RC-2026-000001
 *   define('SHOP_ADDRESS', 'Bastos, Yaoundé');
 *   define('SHOP_LEGAL_LINES', ['NIU : ...', 'RCCM : ...']); // mentions légales, quand tu les auras
 *   define('SITE_URL', 'https://www.tondomaine.com');  // adresse OFFICIELLE du site (voir site_url())
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/orders.php';
require_once __DIR__ . '/../lib/tcpdf/tcpdf.php';

/** Lit un réglage facultatif de config.php, avec une valeur par défaut. */
function doc_setting(string $name, $default)
{
    return defined($name) ? constant($name) : $default;
}

/**
 * Adresse complète du site, utilisée dans le QR code du reçu.
 * Volontairement lue dans la configuration (SITE_URL) et JAMAIS déduite de la requête du
 * visiteur : sinon quelqu'un pourrait faire imprimer un reçu dont le QR renvoie vers un
 * faux site de vérification. Sans SITE_URL, on suppose le site local de XAMPP.
 */
function site_url(string $path = ''): string
{
    $base = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : 'http://localhost' . BASE_URL;
    return $base . '/' . ltrim($path, '/');
}

/** Prix pour le PDF : espaces insécables, pour que "2 500 FCFA" ne soit jamais coupé en deux. */
function pdf_price(int $amount): string
{
    return str_replace(' ', "\xC2\xA0", format_price($amount));
}

/** Protège un texte avant de l'insérer dans le HTML que TCPDF va lire. */
function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Construit le PDF d'une commande (retourne l'objet TCPDF, sans rien envoyer). */
function build_order_pdf(array $order): TCPDF
{
    $title   = (string) doc_setting('DOC_TITLE', 'Reçu de commande');
    $address = (string) doc_setting('SHOP_ADDRESS', '');
    $legal   = (array) doc_setting('SHOP_LEGAL_LINES', []);
    $number  = order_document_number($order);
    $date    = date('d/m/Y à H:i', strtotime($order['created_at']));
    $blue    = '#2563EB';

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator(SHOP_NAME);
    $pdf->SetAuthor(SHOP_NAME);
    $pdf->SetTitle($title . ' ' . $number);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->SetFont('dejavusans', '', 9);
    $pdf->AddPage();

    // --- En-tête : boutique à gauche, document à droite ---------------------
    $shopLines = '<span style="color:#64748B;">' . h(SHOP_CITY);
    if ($address !== '') {
        $shopLines = '<span style="color:#64748B;">' . h($address) . ', ' . h(SHOP_CITY);
    }
    $shopLines .= '<br>WhatsApp : +' . h(SHOP_WHATSAPP) . '</span>';
    foreach ($legal as $line) {
        $shopLines .= '<br><span style="color:#64748B;">' . h($line) . '</span>';
    }

    $pdf->writeHTML(
        '<table cellpadding="0" cellspacing="0"><tr>'
        . '<td width="55%"><span style="font-size:22pt;font-weight:bold;color:' . $blue . ';">' . h(SHOP_NAME) . '</span><br>' . $shopLines . '</td>'
        . '<td width="45%" align="right"><span style="font-size:15pt;font-weight:bold;">' . h($title) . '</span><br>'
        . '<span style="color:#64748B;">N° ' . h($number) . '<br>Date : ' . h($date) . '</span></td>'
        . '</tr></table><br><br>',
        true, false, true, false, ''
    );

    // --- Client --------------------------------------------------------
    $pdf->writeHTML(
        '<table cellpadding="8" cellspacing="0" border="0" style="background-color:#F1F5F9;"><tr><td>'
        . '<span style="color:#64748B;font-size:8pt;">CLIENT</span><br>'
        . '<b>' . h($order['customer_name']) . '</b><br>'
        . 'Tél : ' . h($order['customer_phone']) . '<br>'
        . nl2br(h($order['customer_address']))
        . '</td></tr></table><br><br>',
        true, false, true, false, ''
    );

    // --- Lignes de la commande -----------------------------------------
    $rows = '';
    foreach ($order['items'] as $item) {
        $rows .= '<tr>'
            . '<td width="50%" style="border-bottom:1px solid #E2E8F0;">' . h($item['product_name']) . '</td>'
            . '<td width="10%" align="center" style="border-bottom:1px solid #E2E8F0;">' . (int) $item['quantity'] . '</td>'
            . '<td width="20%" align="right" style="border-bottom:1px solid #E2E8F0;">' . h(pdf_price((int) $item['unit_price'])) . '</td>'
            . '<td width="20%" align="right" style="border-bottom:1px solid #E2E8F0;">' . h(pdf_price((int) $item['line_total'])) . '</td>'
            . '</tr>';
    }

    $pdf->writeHTML(
        '<table cellpadding="6" cellspacing="0">'
        . '<thead><tr style="background-color:' . $blue . ';color:#FFFFFF;font-weight:bold;">'
        . '<td width="50%">Désignation</td>'
        . '<td width="10%" align="center">Qté</td>'
        . '<td width="20%" align="right">Prix unitaire</td>'
        . '<td width="20%" align="right">Montant</td>'
        . '</tr></thead><tbody>' . $rows . '</tbody></table><br>',
        true, false, true, false, ''
    );

    // --- Totaux ------------------------------------------------------
    $totals = '<tr><td width="40%"></td><td width="40%" align="right">Sous-total</td>'
        . '<td width="20%" align="right">' . h(pdf_price((int) $order['subtotal_amount'])) . '</td></tr>';

    if ((int) $order['discount_amount'] > 0) {
        $code = !empty($order['ticket_code']) ? ' (code ' . h($order['ticket_code']) . ')' : '';
        $totals .= '<tr><td width="40%"></td><td width="40%" align="right" style="color:#059669;">Réduction' . $code . '</td>'
            . '<td width="20%" align="right" style="color:#059669;">– ' . h(pdf_price((int) $order['discount_amount'])) . '</td></tr>';
    }

    $totals .= '<tr><td width="40%"></td>'
        . '<td width="60%" colspan="2" align="right" style="background-color:#EFF6FF;font-size:12pt;font-weight:bold;color:' . $blue . ';">'
        . 'Total à payer : ' . h(pdf_price((int) $order['total_amount'])) . '</td></tr>';

    $pdf->writeHTML('<table cellpadding="4" cellspacing="0">' . $totals . '</table><br><br>', true, false, true, false, '');

    // --- Paiement et remerciement -----------------------------------
    $pdf->writeHTML(
        '<span style="color:#64748B;">Mode de paiement : à la livraison.<br>'
        . 'Merci pour votre commande ! Pour toute question, écrivez-nous sur WhatsApp au +' . h(SHOP_WHATSAPP) . '.</span><br>',
        true, false, true, false, ''
    );

    // --- Code de vérification + QR code (contre la falsification) -------------
    $code = ensure_verification_code($order);
    $verifyBase = site_url('verifier.php');
    $verifyUrl = $verifyBase . '?code=' . $code;

    $pdf->Ln(2);
    // Pas assez de place sous le tableau pour le bloc (36 mm) : on le passe sur une nouvelle page
    if ($pdf->GetY() + 42 > $pdf->getPageHeight() - $pdf->getBreakMargin()) {
        $pdf->AddPage();
    }
    $top = $pdf->GetY();

    $pdf->SetFillColor(241, 245, 249);
    $pdf->RoundedRect(15, $top, 180, 36, 3, '1111', 'F');
    $pdf->writeHTMLCell(
        138, 30, 19, $top + 3,
        '<span style="color:#64748B;font-size:8pt;">CODE DE VÉRIFICATION</span><br>'
        . '<span style="font-size:16pt;font-weight:bold;color:' . $blue . ';">' . h(format_verification_code($code)) . '</span><br>'
        . '<span style="font-size:4pt;">&nbsp;</span><br>'
        . '<span style="color:#64748B;font-size:7.5pt;">Pour vérifier que ce reçu est authentique et n\'a pas été modifié, '
        . 'scannez le QR code ou ouvrez ' . h($verifyBase) . ' et saisissez ce code. '
        . 'Les informations affichées doivent être identiques à celles de ce document.</span>',
        0, 0, false, true, 'L', true
    );
    $pdf->write2DBarcode($verifyUrl, 'QRCODE,M', 163, $top + 4, 28, 28, [
        'border' => 0, 'vpadding' => 0, 'hpadding' => 0,
        'fgcolor' => [15, 23, 42], 'bgcolor' => false,
        'module_width' => 1, 'module_height' => 1,
    ], 'N');
    $pdf->SetY($top + 38);

    return $pdf;
}

/** Envoie le PDF d'une commande au navigateur (téléchargement) et arrête le script. */
function send_order_pdf(array $order): void
{
    // Une erreur PHP affichée à l'écran corromprait le fichier PDF : on l'écrit dans le journal à la place.
    ini_set('display_errors', '0');

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $pdf = build_order_pdf($order);
    $filename = 'recu-' . order_document_number($order) . '.pdf';
    $pdf->Output($filename, 'D');
    exit;
}
