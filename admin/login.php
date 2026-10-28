<?php
require_once __DIR__ . '/../includes/functions.php';

if (admin_logged_in()) {
    redirect('admin/index.php');
}

// Tant qu'aucun administrateur n'existe, cette page sert à créer le premier.
// IMPORTANT : sur un site en ligne, crée ton compte immédiatement après l'installation.
$setupMode = ((int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn()) === 0;
$error = null;

function admin_start_session(int $id, string $username): void
{
    session_regenerate_id(true); // nouvel identifiant de session après connexion
    $_SESSION['admin_id'] = $id;
    $_SESSION['admin_name'] = $username;
    redirect('admin/index.php');
}

if (is_post()) {
    csrf_check();
    $username = post_string('username');
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

    if ($setupMode) {
        $confirm = isset($_POST['password_confirm']) && is_string($_POST['password_confirm']) ? $_POST['password_confirm'] : '';

        if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
            $error = "Nom d'utilisateur : 3 à 50 caractères (lettres, chiffres, . _ -).";
        } elseif (strlen($password) < 10) {
            $error = 'Le mot de passe doit contenir au moins 10 caractères.';
        } elseif ($password !== $confirm) {
            $error = 'Les deux mots de passe ne correspondent pas.';
        } else {
            $stmt = db()->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            admin_start_session((int) db()->lastInsertId(), $username);
        }
    } else {
        $stmt = db()->prepare('SELECT id, username, password_hash FROM admins WHERE username = ?');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            admin_start_session((int) $admin['id'], $admin['username']);
        }

        usleep(500000); // ralentit les essais en série de mots de passe
        $error = 'Identifiants incorrects.';
    }
}

$pageTitle = $setupMode ? 'Créer le compte administrateur' : 'Connexion';
$layout = 'public';
require __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="container narrow">
        <h1 class="page-title"><?= $setupMode ? 'Créer le compte administrateur' : 'Connexion admin' ?></h1>

        <?php if ($setupMode): ?>
            <p class="muted">Aucun administrateur n'existe encore : crée le premier compte.</p>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('admin/login.php')) ?>" class="form">
            <?= csrf_field() ?>
            <label>Nom d'utilisateur
                <input type="text" name="username" required maxlength="50" autocomplete="username">
            </label>
            <label>Mot de passe<?= $setupMode ? ' (10 caractères minimum)' : '' ?>
                <input type="password" name="password" required autocomplete="<?= $setupMode ? 'new-password' : 'current-password' ?>">
            </label>
            <?php if ($setupMode): ?>
                <label>Confirmer le mot de passe
                    <input type="password" name="password_confirm" required autocomplete="new-password">
                </label>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary btn-block"><?= $setupMode ? 'Créer le compte' : 'Se connecter' ?></button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
