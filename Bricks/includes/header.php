<?php
if (!isset($page_title)) {
    $page_title = APP_NAME;
}

// Welke pagina is open? (voor het actieve menu-item)
$current_file = basename($_SERVER['PHP_SELF']);
$menu = array(
    'index.php' => 'Overzicht',
    'dropdowns.php' => 'Dropdowns',
    'velden.php' => 'Velden',
    'knoppen.php' => 'Knoppen',
    'tonen.php' => 'Netjes tonen',
    'tabel.php' => 'Tabel + filter',
    'opslaan.php' => 'Alles samen: opslaan',
);
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
                <svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="14" y="3" rx="1"/><path d="M10 21V8a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-5a1 1 0 0 0-1-1H3"/></svg>
                <?= e(APP_NAME) ?>
            </a>

            <div class="header-actions">
                <span class="role-pill">Notion pagina 7</span>
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
