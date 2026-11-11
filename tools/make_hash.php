<?php
// =====================================================================
// tools/make_hash.php - maak een wachtwoord-hash voor je dummy data SQL
// Open: http://localhost/Investigation/tools/make_hash.php
// Alleen voor lokaal gebruik!
// =====================================================================

$password = '';
$hash = '';
$check = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password']) && is_string($_POST['password'])) {
    $password = $_POST['password'];

    if ($password === '') {
        $check = 'Error-code: 420 - Vul een wachtwoord in.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Direct controleren of de hash werkt
        if (password_verify($password, $hash)) {
            $check = 'password_verify() = OK';
        } else {
            $check = 'Error-code: 420 - Hash controle mislukt.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Hash maken</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f3f4f6; padding: 40px; }
        .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); max-width: 700px; }
        input, button { padding: 8px; font-size: 14px; }
        code { display: block; background: #eef2ff; padding: 10px; margin-top: 10px; word-break: break-all; }
    </style>
</head>
<body>
<div class="card">
    <h1>Wachtwoord hash maken</h1>
    <p>Hash controleren of uitlezen? <a href="hash_reader.php">hash_reader.php</a></p>
    <form method="post">
        <input type="text" name="password" placeholder="wachtwoord" value="<?= htmlspecialchars($password) ?>">
        <button type="submit">Maak hash</button>
    </form>

    <?php if ($hash !== ''): ?>
        <p>Hash (plak in je INSERT INTO users):</p>
        <code><?= htmlspecialchars($hash) ?></code>
        <code>INSERT INTO users (name, email, password, role) VALUES ('Naam', 'naam@test.nl', '<?= htmlspecialchars($hash) ?>', 'user');</code>
    <?php endif; ?>

    <?php if ($check !== ''): ?>
        <p><strong><?= htmlspecialchars($check) ?></strong></p>
    <?php endif; ?>
</div>
</body>
</html>
