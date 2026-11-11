<?php
// =====================================================================
// register.php - nieuwe leerling (rol 'user') met klas-dropdown
// =====================================================================
require_once 'includes/init.php';

if (is_logged_in()) {
    redirect('index.php');
}

$name = '';
$email = '';
$class_id = '';

if (is_post()) {
    $name = post_param('name');
    $email = post_param('email');
    $class_id = post_param('class_id'); // dropdown-waarde bewaren voor als er iets fout gaat

    $new_id = register_user($conn, $name, $email, post_raw('password'), post_raw('password_repeat'), $class_id);

    if ($new_id !== false) {
        log_action($conn, $new_id, 'register', 'Nieuwe leerling: ' . $email);
        set_flash('success', 'Account aangemaakt. Je kunt nu inloggen.');
        redirect('login.php');
    }
}

$classes = classes_get_all($conn);
if ($classes === false) {
    $classes = array();
}

$page_title = 'Registreren';
require 'includes/header.php';
?>

<form method="post" action="register.php" class="card form form-small">
    <h1>Registreren als leerling</h1>

    <label for="name">Naam</label>
    <input type="text" id="name" name="name" maxlength="100" required value="<?= e($name) ?>">

    <label for="email">E-mail</label>
    <input type="email" id="email" name="email" maxlength="150" required value="<?= e($email) ?>">

    <label for="class_id">Klas</label>
    <select id="class_id" name="class_id" required>
        <option value="">-- Kies je klas --</option>
        <?php foreach ($classes as $class): ?>
            <option value="<?= e($class['id']) ?>" <?= selected($class_id, $class['id']) ?>><?= e($class['name']) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="password">Wachtwoord (min. 8 tekens)</label>
    <input type="password" id="password" name="password" minlength="8" required>

    <label for="password_repeat">Herhaal wachtwoord</label>
    <input type="password" id="password_repeat" name="password_repeat" minlength="8" required>

    <button type="submit" class="btn">Account maken</button>
    <p>Al een account? <a href="login.php">Log in</a></p>
</form>

<?php require 'includes/footer.php'; ?>
