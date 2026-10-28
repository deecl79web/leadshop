<?php
require_once __DIR__ . '/../includes/functions.php';

// Déconnexion uniquement par formulaire POST (avec jeton CSRF)
if (is_post()) {
    csrf_check();
    unset($_SESSION['admin_id'], $_SESSION['admin_name']);
    session_regenerate_id(true);
    flash('success', 'Tu es déconnecté.');
    redirect('admin/login.php');
}

redirect('admin/index.php');
