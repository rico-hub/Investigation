<?php
// includes/header.php - zet $page_title VOOR je deze include doet.
if (!isset($page_title)) {
    $page_title = APP_NAME;
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="topbar">
    <a href="index.php" class="logo"><?= e(APP_NAME) ?></a>
    <nav>
        <a href="index.php">Boeken</a>
        <a href="info.php">Info</a>
        <?php if (is_logged_in()): ?>
            <a href="cart.php">Winkelmand <span class="badge"><?= cart_count() ?></span></a>
            <?php if (is_admin()): ?>
                <a href="loans.php">Alle leningen</a>
                <a href="book_create.php">+ Boek</a>
                <a href="book_trash.php">Prullenbak</a>
                <a href="logs.php">Logboek</a>
            <?php else: ?>
                <a href="loans.php">Mijn leningen</a>
            <?php endif; ?>
            <span class="user"><?= e($_SESSION['name']) ?> (<?= e($_SESSION['role']) ?>)</span>
            <a href="logout.php" class="btn btn-small btn-grey">Uitloggen</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-small">Inloggen</a>
            <a href="register.php">Registreren</a>
        <?php endif; ?>
    </nav>
</header>

<main class="container">
<?php show_flash(); ?>
