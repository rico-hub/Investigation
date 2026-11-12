<?php
// =====================================================================
// register.php - nieuw account (altijd rol 'user')
// =====================================================================
require_once 'includes/init.php';

if (is_logged_in()) {
    redirect('index.php');
}

$name = '';
$email = '';

if (is_post()) {
    $name = post_param('name');
    $email = post_param('email');

    $new_id = register_user($conn, $name, $email, post_raw('password'), post_raw('password_repeat'));

    if ($new_id !== false) {
        log_action($conn, $new_id, 'register', 'Nieuw account: ' . $email);
        set_flash('success', 'Account aangemaakt. Je kunt nu inloggen.');
        redirect('login.php');
    }
}

$page_title = 'Registreren';
require 'includes/header.php';
?>

<form method="post" action="register.php" class="card form form-small">
    <h1>Registreren</h1>

    <label for="name">Naam</label>
    <input type="text" id="name" name="name" maxlength="100" required value="<?= e($name) ?>">

    <label for="email">E-mail</label>
    <input type="email" id="email" name="email" maxlength="150" required value="<?= e($email) ?>">

    <label for="password">Wachtwoord (min. 8 tekens)</label>
    <input type="password" id="password" name="password" minlength="8" required>

    <label for="password_repeat">Herhaal wachtwoord</label>
    <input type="password" id="password_repeat" name="password_repeat" minlength="8" required>

    <button type="submit" class="btn">Account maken</button>
    <p>Al een account? <a href="login.php">Log in</a></p>
</form>

<?php require 'includes/footer.php'; ?>
