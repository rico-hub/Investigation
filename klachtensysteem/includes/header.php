<?php
if (!isset($page_title)) {
    $page_title = APP_NAME;
}

// Welke pagina is open? (voor het actieve menu-item)
$current_file = basename($_SERVER['PHP_SELF']);

// Menu (tweede rij). Verschilt per rol, daarom simpele if / else.
$menu = array();
if (is_logged_in()) {
    if (is_admin()) {
        $menu['index.php'] = 'Alle klachten';
        $menu['complaint_trash.php'] = 'Prullenbak';
        $menu['logs.php'] = 'Logboek';
    } else {
        $menu['index.php'] = 'Mijn klachten';
        $menu['complaint_create.php'] = '+ Nieuwe klacht';
    }
} else {
    $menu['login.php'] = 'Inloggen';
    $menu['register.php'] = 'Registreren';
}

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> - <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- ?v=... = nieuwe versie van de CSS, zodat de browser geen oude kopie gebruikt -->
    <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/../css/style.css') ?>">
</head>
<body>

<header class="site-header">
    <div class="topbar">
        <div class="topbar-row">
            <a href="index.php" class="logo">
                <svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M12 7v2"/><path d="M12 13h.01"/></svg>
                <?= e(APP_NAME) ?>
            </a>

            <div class="header-actions">
                <?php if (is_logged_in()): ?>
                    <span class="role-pill"><?= e($_SESSION['role']) ?></span>
                <?php endif; ?>

                <details class="account">
                    <summary class="icon-btn" title="Account">
                        <svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>
                    </summary>
                    <div class="account-menu">
                        <?php if (is_logged_in()): ?>
                            <div class="who"><?= e($_SESSION['name']) ?><small><?= e($_SESSION['role']) ?></small></div>
                            <a href="index.php">Mijn klachten</a>
                            <a href="logout.php">Uitloggen</a>
                        <?php else: ?>
                            <a href="login.php">Inloggen</a>
                            <a href="register.php">Registreren</a>
                        <?php endif; ?>
                    </div>
                </details>

                <?php if (is_admin()): ?>
                    <a href="cart.php" class="icon-btn" title="Werklijst">
                        <svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>
                        <?php if (cart_count() > 0): ?>
                            <span class="dot"><?= cart_count() ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <nav class="main-nav">
            <?php foreach ($menu as $file => $label): ?>
                <?php if ($file === $current_file): ?>
                    <a href="<?= e($file) ?>" class="active"><?= e($label) ?></a>
                <?php else: ?>
                    <a href="<?= e($file) ?>"><?= e($label) ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </div>
</header>

<main class="container">
<?php show_flash(); ?>
