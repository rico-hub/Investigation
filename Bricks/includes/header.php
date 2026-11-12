<?php
// =====================================================================
// includes/header.php - UNIVERSEEL: in elke app exact hetzelfde bestand.
// NIET AANPASSEN. Alles wat per app anders is staat in config/site.php
// (menu, logo, account-menu, winkelmand).
// Zet $page_title VOOR je deze include doet.
// =====================================================================
if (!isset($page_title)) {
    $page_title = APP_NAME;
}

// Welke pagina is open? (die link in het menu wordt actief)
$current_file = basename($_SERVER['PHP_SELF']);

// Vult: $menu, $account_menu, $site_logo, $site_tagline, $site_user_name,
//       $site_role_label, $cart_file, $cart_title, $cart_icon, $cart_number
require __DIR__ . '/../config/site.php';
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
                <svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?= $site_logo ?></svg>
                <?= e(APP_NAME) ?>
            </a>

            <div class="header-actions">
                <?php if ($site_role_label !== ''): ?>
                    <span class="role-pill"><?= e($site_role_label) ?></span>
                <?php endif; ?>

                <?php if (count($account_menu) > 0): ?>
                    <details class="account">
                        <summary class="icon-btn" title="Account">
                            <svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>
                        </summary>
                        <div class="account-menu">
                            <?php if ($site_user_name !== ''): ?>
                                <div class="who"><?= e($site_user_name) ?><small><?= e($site_role_label) ?></small></div>
                            <?php endif; ?>
                            <?php foreach ($account_menu as $file => $label): ?>
                                <a href="<?= e($file) ?>"><?= e($label) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </details>
                <?php endif; ?>

                <?php if ($cart_file !== ''): ?>
                    <a href="<?= e($cart_file) ?>" class="icon-btn" title="<?= e($cart_title) ?>">
                        <svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?= $cart_icon ?></svg>
                        <?php if ($cart_number > 0): ?>
                            <span class="dot"><?= e($cart_number) ?></span>
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
