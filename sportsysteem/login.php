<?php
// =====================================================================
// login.php
// =====================================================================
require_once 'includes/init.php';

// Al ingelogd? Dan heeft deze pagina geen zin.
if (is_logged_in()) {
    redirect('index.php');
}

$email = '';

if (is_post()) {
    $email = post_param('email');
    $password = post_raw('password');

    if (login_user($conn, $email, $password)) {
        set_flash('success', 'Welkom, ' . $_SESSION['name'] . '!');
        redirect('index.php');
    }
    // Mislukt: e-mail blijft ingevuld, wachtwoord niet (nooit wachtwoord terugsturen)
}

$page_title = 'Inloggen';
require 'includes/header.php';
?>

<?php if (get_param('logged_out') === '1'): ?>
    <div class="flash flash-success">Je bent uitgelogd. Je sessie is gewist.</div>
<?php elseif (get_param('expired') === '1'): ?>
    <div class="flash flash-error">Je sessie is verlopen (te lang niets gedaan). Log opnieuw in.</div>
<?php endif; ?>

<form method="post" action="login.php" class="card form form-small">
    <h1>Inloggen</h1>

    <label for="email">E-mail</label>
    <input type="email" id="email" name="email" required value="<?= e($email) ?>">

    <label for="password">Wachtwoord</label>
    <input type="password" id="password" name="password" required>

    <button type="submit" class="btn">Inloggen</button>
    <p>Nog geen account? <a href="register.php">Registreer hier</a></p>
    <p class="hint">Test: admin@test.nl / bram@test.nl &ndash; wachtwoord: password</p>
</form>

<?php require 'includes/footer.php'; ?>
