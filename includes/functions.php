<?php
/**
 * Fonctions utilitaires utilisées partout dans le site.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// --- Erreurs : visibles en développement, cachées en production -----------
ini_set('display_errors', APP_DEBUG ? '1' : '0');
error_reporting(E_ALL);

// --- Session --------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    // Nom et chemin propres à ce site, pour ne pas mélanger la session avec
    // tes autres projets (megaique-app...) qui tournent aussi sur localhost.
    session_name('leadshop_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_URL === '' ? '/' : BASE_URL,
        'httponly' => true,   // le JavaScript ne peut pas lire le cookie
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

// --- En-têtes de sécurité -----------------------------------------------
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// --- Affichage et URLs --------------------------------------------------

/** Protège un texte avant de l'afficher en HTML (évite les failles XSS). */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/** 2500 => "2 500 FCFA" */
function format_price(int $amount): string
{
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

/** url('boutique.php') => "/leadshop/boutique.php" */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** Adresse d'un fichier du dossier assets, avec un numéro de version anti-cache. */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = __DIR__ . '/../assets/' . $path;
    $version = is_file($file) ? filemtime($file) : 1;
    return url('assets/' . $path) . '?v=' . $version;
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

/** Lit un champ de formulaire texte, sans les espaces autour. */
function post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

// --- Messages flash (affichés une seule fois, après une redirection) ----
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_pull(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

// --- Protection CSRF : empêche un autre site de poster un formulaire à ta place ---
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? '';
    $expected = $_SESSION['csrf'] ?? '';

    if ($expected === '' || !is_string($sent) || !hash_equals($expected, $sent)) {
        http_response_code(400);
        exit('Formulaire expiré ou invalide. Recharge la page et réessaie.');
    }
}

// --- Administration ---------------------------------------------------
function admin_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!admin_logged_in()) {
        redirect('admin/login.php');
    }
}

// --- WhatsApp -----------------------------------------------------------
function wa_link(string $message = ''): string
{
    $link = 'https://wa.me/' . SHOP_WHATSAPP;
    return $message === '' ? $link : $link . '?text=' . rawurlencode($message);
}

/** Lien WhatsApp vers un client (ajoute l'indicatif 237 aux numéros à 9 chiffres). */
function customer_wa_link(string $phone): string
{
    $digits = preg_replace('/\D/', '', $phone);
    if (strlen($digits) === 9) {
        $digits = '237' . $digits;
    }
    return 'https://wa.me/' . $digits;
}

// --- Libellés ---------------------------------------------------------
function category_icon(?string $category): string
{
    $icons = [
        'maison'     => '🕯️',
        'parfum'     => '🌸',
        'accessoire' => '💨',
        'bijoux'     => '💍',
        'cosmetique' => '🧴',
    ];
    return $icons[$category ?? ''] ?? '🛍️';
}

function status_label(string $status): string
{
    $labels = [
        'pending'   => 'En attente',
        'confirmed' => 'Confirmée',
        'delivered' => 'Livrée',
        'cancelled' => 'Annulé(e)',
        'issued'    => 'Disponible',
        'redeemed'  => 'Utilisé',
        'expired'   => 'Expiré',
        'new'       => 'Nouvelle',
        'contacted' => 'Contactée',
        'closed'    => 'Clôturée',
    ];
    return $labels[$status] ?? $status;
}
