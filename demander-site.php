<?php
require_once __DIR__ . '/includes/cart.php';

$errors = [];
$values = ['name' => '', 'phone' => '', 'email' => '', 'message' => ''];

if (is_post()) {
    csrf_check();

    // Champ piège : invisible pour un humain, rempli par les robots.
    // Si il est rempli, on fait semblant que tout va bien sans rien enregistrer.
    if (post_string('website') !== '') {
        flash('success', 'Merci ! Ta demande a bien été envoyée.');
        redirect('demander-site.php');
    }

    $values = [
        'name'    => post_string('name'),
        'phone'   => post_string('phone'),
        'email'   => post_string('email'),
        'message' => post_string('message'),
    ];

    $nameLength = mb_strlen($values['name']);
    if ($nameLength < 2 || $nameLength > 100) {
        $errors[] = 'Indique ton nom (entre 2 et 100 caractères).';
    }
    if (!preg_match('/^\+?[0-9][0-9\s().-]{6,19}$/', $values['phone'])) {
        $errors[] = 'Indique un numéro de téléphone valide.';
    }
    if ($values['email'] !== '' && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse e-mail n'est pas valide.";
    }
    if (mb_strlen($values['message']) > 1000) {
        $errors[] = 'Ton message est trop long (1000 caractères maximum).';
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'INSERT INTO site_requests (name, phone, email, message) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $values['name'],
            $values['phone'],
            $values['email'] !== '' ? $values['email'] : null,
            $values['message'] !== '' ? $values['message'] : null,
        ]);

        flash('success', 'Merci ! Ta demande a bien été envoyée, on te recontacte bientôt.');
        redirect('demander-site.php');
    }
}

$pageTitle = 'Votre site';
require __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container narrow">
        <h1 class="page-title">Vous aussi, un site comme celui-ci ?</h1>
        <p class="muted">Laisse tes coordonnées, on te recontacte pour en discuter.</p>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php endforeach; ?>

        <form method="post" action="<?= e(url('demander-site.php')) ?>" class="form">
            <?= csrf_field() ?>

            <label>Nom
                <input type="text" name="name" value="<?= e($values['name']) ?>" required maxlength="100" autocomplete="name">
            </label>
            <label>Téléphone
                <input type="tel" name="phone" value="<?= e($values['phone']) ?>" required maxlength="20"
                       placeholder="+237 6XX XXX XXX" autocomplete="tel">
            </label>
            <label>E-mail (optionnel)
                <input type="email" name="email" value="<?= e($values['email']) ?>" maxlength="255" autocomplete="email">
            </label>
            <label>Parle-moi de ton projet (optionnel)
                <textarea name="message" rows="4" maxlength="1000"><?= e($values['message']) ?></textarea>
            </label>

            <!-- Champ piège anti-robots : ne pas remplir -->
            <div class="hp" aria-hidden="true">
                <label>Site web <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Envoyer ma demande</button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
