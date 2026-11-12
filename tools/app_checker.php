<?php
// =====================================================================
// tools/app_checker.php - App checker: controleert een app en lost fouten op
// Open: http://localhost/Investigation/tools/app_checker.php
// Werkt offline (geen internet, geen AI). Alleen voor lokaal gebruik!
//
// 1. Vul een map in (bv. dierenasiel) of een pad naar 1 bestand.
// 2. Klik Controleren: je ziet per groep wat goed is en wat niet.
// 3. Bij sommige fouten staat een knop om het automatisch op te lossen.
//    Voor elke fix wordt eerst een reservekopie gemaakt in tools/backups/.
// =====================================================================
require_once __DIR__ . '/functions/checker_functions.php';

$target = '';
$resolved = null;
$groups = array();
$done = '';
$rename = array('ding_s' => '', 'ding_p' => '', 'lijst_s' => '', 'lijst_p' => '', 'actie_s' => '', 'actie_p' => '', 'app_name' => '', 'db_name' => '');

if (isset($_GET['target']) && is_string($_GET['target'])) {
    $target = $_GET['target'];
}
if (isset($_POST['target']) && is_string($_POST['target'])) {
    $target = $_POST['target'];
}

if ($target !== '') {
    $resolved = ac_resolve($target);
}

// Een fix-knop is ingedrukt (alleen via POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $resolved !== null && $resolved['error'] === '' && isset($_POST['fix']) && is_string($_POST['fix'])) {
    $stamp = date('Ymd-His');
    $fix = $_POST['fix'];
    if ($fix === 'cssjs') {
        $done = ac_fix_cssjs($resolved['root'], $stamp);
    } elseif ($fix === 'dbname') {
        $done = ac_fix_dbname($resolved['root'], $stamp);
    } elseif ($fix === 'import') {
        $done = ac_fix_import($resolved['root']);
    } elseif ($fix === 'rename') {
        foreach (array_keys($rename) as $key) {
            if (isset($_POST[$key]) && is_string($_POST[$key])) {
                $rename[$key] = trim($_POST[$key]);
            }
        }
        $done = ac_fix_rename($resolved['root'], $rename, $stamp);
        // Was het gekozen bestand hernoemd? Dan de hele app controleren.
        if ($resolved['file'] !== '' && !is_file($resolved['file'])) {
            $target = basename($resolved['root']);
            $resolved = ac_resolve($target);
        }
    } else {
        $done = 'Onbekende fix.';
    }
}

if ($resolved !== null && $resolved['error'] === '') {
    $groups = ac_run_all($resolved['root'], $resolved['file']);
}

// Tellen
$errors = 0;
$warns = 0;
$fixes = array();
foreach ($groups as $issues) {
    foreach ($issues as $issue) {
        if ($issue['level'] === 'error') {
            $errors++;
        } else {
            $warns++;
        }
        if ($issue['fix'] !== '') {
            $fixes[$issue['fix']] = true;
        }
    }
}
$apps = ac_list_apps();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>App checker</title>
<style>
    body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f4f4f5; color: #2b2b2b; line-height: 1.5; }
    .wrap { max-width: 1000px; margin: 0 auto; padding: 24px 16px 64px; }
    h1 { margin: 0 0 4px; font-size: 28px; }
    .sub { color: #6b6b6b; margin: 0 0 20px; }
    .card { background: #fff; border-radius: 14px; padding: 20px 24px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); margin-bottom: 16px; }
    label { font-weight: bold; display: block; margin-bottom: 6px; }
    input[type=text] { width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid #ccc; border-radius: 999px; font-size: 15px; }
    .row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-top: 10px; }
    .btn { background: #16a34a; color: #fff; border: 0; border-radius: 999px; padding: 10px 22px; font-size: 15px; cursor: pointer; text-decoration: none; display: inline-block; }
    .btn-grey { background: #3d3d3d; }
    .btn-small { padding: 6px 14px; font-size: 13px; }
    .chip { background: #eef6e3; color: #15803d; border-radius: 999px; padding: 4px 12px; font-size: 13px; text-decoration: none; }
    .sum { display: flex; gap: 12px; flex-wrap: wrap; }
    .pill { border-radius: 999px; padding: 6px 16px; font-weight: bold; }
    .p-err { background: #fdeae5; color: #b42318; }
    .p-warn { background: #fef3c7; color: #92400e; }
    .p-ok { background: #eef6e3; color: #15803d; }
    .group h2 { font-size: 18px; margin: 0 0 10px; display: flex; gap: 8px; align-items: center; }
    .issue { border-left: 4px solid #e8461e; padding: 8px 14px; margin: 8px 0; background: #fffaf8; }
    .issue.warn { border-left-color: #f59e0b; background: #fffbeb; }
    .where { font-family: Consolas, monospace; font-size: 13px; color: #555; }
    .how { color: #3f4a43; font-size: 14px; }
    .done { background: #eef6e3; border: 1px solid #15803d; color: #15803d; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px; }
    .bad { background: #fdeae5; border: 1px solid #b42318; color: #b42318; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px; }
    .grid { display: grid; grid-template-columns: 140px 1fr 1fr; gap: 8px; align-items: center; }
    .grid input { border-radius: 8px; }
    .hint { color: #6b6b6b; font-size: 13px; }
    code { background: #eef0ee; padding: 1px 5px; border-radius: 4px; }
</style>
</head>
<body>
<div class="wrap">
    <h1>App checker</h1>
    <p class="sub">Controleert je app op de bekende fouten, en lost een deel ervan zelf op. Werkt offline, zonder AI.</p>

    <form method="get" action="app_checker.php" class="card">
        <label for="target">Welke app of welk bestand?</label>
        <input type="text" id="target" name="target" value="<?= ac_h($target) ?>" placeholder="dierenasiel   of   S:\Investigation\dierenasiel\functions\animals.php">
        <div class="row">
            <button type="submit" class="btn">Controleren</button>
            <span class="hint">Snel kiezen:</span>
            <?php foreach ($apps as $app): ?>
                <a class="chip" href="app_checker.php?target=<?= urlencode($app) ?>"><?= ac_h($app) ?></a>
            <?php endforeach; ?>
        </div>
        <p class="hint">Je mag ook een adres plakken, bv. http://localhost/Investigation/dierenasiel/index.php</p>
    </form>

    <?php if ($done !== '' && strpos($done, 'Niet gedaan') === 0): ?>
        <div class="bad"><?= ac_h($done) ?></div>
    <?php elseif ($done !== '' && strpos($done, 'Importeren') === 0): ?>
        <div class="bad"><?= ac_h($done) ?></div>
    <?php elseif ($done !== ''): ?>
        <div class="done"><b>Gedaan:</b> <?= ac_h($done) ?></div>
    <?php endif; ?>

    <?php if ($resolved !== null && $resolved['error'] !== ''): ?>
        <div class="bad"><?= ac_h($resolved['error']) ?></div>
    <?php endif; ?>

    <?php if ($resolved !== null && $resolved['error'] === ''): ?>
        <div class="card">
            <p style="margin:0 0 10px">App: <code><?= ac_h($resolved['root']) ?></code>
            <?php if ($resolved['file'] !== ''): ?>
                <br>Alleen meldingen over: <code><?= ac_h(ac_rel($resolved['root'], $resolved['file'])) ?></code>
                · <a href="app_checker.php?target=<?= urlencode(basename($resolved['root'])) ?>">hele app controleren</a>
            <?php endif; ?>
            </p>
            <div class="sum">
                <?php if ($errors > 0): ?><span class="pill p-err"><?= $errors ?> fout(en)</span><?php endif; ?>
                <?php if ($warns > 0): ?><span class="pill p-warn"><?= $warns ?> let op</span><?php endif; ?>
                <?php if ($errors === 0 && $warns === 0): ?><span class="pill p-ok">Alles goed</span><?php endif; ?>
            </div>
        </div>

        <?php foreach ($groups as $name => $issues): ?>
            <div class="card group">
                <?php if (count($issues) === 0): ?>
                    <h2><span class="pill p-ok">Goed</span> <?= ac_h($name) ?></h2>
                <?php else: ?>
                    <?php
                    $group_class = 'p-warn';
                    foreach ($issues as $issue) {
                        if ($issue['level'] === 'error') {
                            $group_class = 'p-err';
                        }
                    }
                    ?>
                    <h2><span class="pill <?= $group_class ?>"><?= count($issues) ?></span> <?= ac_h($name) ?></h2>
                    <?php foreach ($issues as $issue): ?>
                        <?php if ($issue['level'] === 'warn'): ?>
                            <div class="issue warn">
                        <?php else: ?>
                            <div class="issue">
                        <?php endif; ?>
                            <div class="where">
                                <?php if ($issue['level'] === 'warn'): ?>LET OP<?php else: ?>FOUT<?php endif; ?>
                                · <?= ac_h($issue['file']) ?><?php if ($issue['line'] > 0): ?> · regel <?= ac_h($issue['line']) ?><?php endif; ?>
                            </div>
                            <div><?= ac_h($issue['msg']) ?></div>
                            <div class="how"><b>Oplossing:</b> <?= ac_h($issue['how']) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if (count($fixes) > 0): ?>
            <div class="card">
                <h2 style="font-size:18px;margin:0 0 10px">Automatisch oplossen</h2>
                <p class="hint">Voor elke fix wordt eerst een reservekopie gemaakt in <code>tools/backups/<?= ac_h(basename($resolved['root'])) ?>/</code>.</p>

                <?php if (isset($fixes['cssjs'])): ?>
                    <form method="post" action="app_checker.php" class="row">
                        <input type="hidden" name="target" value="<?= ac_h($target) ?>">
                        <input type="hidden" name="fix" value="cssjs">
                        <button type="submit" class="btn btn-small">CSS en JS terugzetten uit de kit</button>
                    </form>
                <?php endif; ?>

                <?php if (isset($fixes['dbname'])): ?>
                    <form method="post" action="app_checker.php" class="row">
                        <input type="hidden" name="target" value="<?= ac_h($target) ?>">
                        <input type="hidden" name="fix" value="dbname">
                        <button type="submit" class="btn btn-small">database.sql gelijk zetten aan DB_NAME</button>
                    </form>
                <?php endif; ?>

                <?php if (isset($fixes['import'])): ?>
                    <form method="post" action="app_checker.php" class="row">
                        <input type="hidden" name="target" value="<?= ac_h($target) ?>">
                        <input type="hidden" name="fix" value="import">
                        <button type="submit" class="btn btn-small">database.sql importeren</button>
                        <span class="hint">De database wordt opnieuw gemaakt: alle testdata gaat terug naar de dummy data.</span>
                    </form>
                <?php endif; ?>

                <?php if (isset($fixes['rename'])): ?>
                    <form method="post" action="app_checker.php" style="margin-top:16px">
                        <input type="hidden" name="target" value="<?= ac_h($target) ?>">
                        <input type="hidden" name="fix" value="rename">
                        <p style="margin:0 0 8px"><b>Hernoemen</b> (zelfde als Fase 2). Vervangt alleen in <code>*.php</code> en <code>*.sql</code>, en hernoemt de bestanden. Een rij leeg laten = die rij overslaan.</p>
                        <div class="grid">
                            <span></span><span class="hint">enkelvoud</span><span class="hint">meervoud</span>
                            <span>Het ding (item)</span><input type="text" name="ding_s" value="<?= ac_h($rename['ding_s']) ?>" placeholder="animal"><input type="text" name="ding_p" value="<?= ac_h($rename['ding_p']) ?>" placeholder="animals">
                            <span>De lijst (category)</span><input type="text" name="lijst_s" value="<?= ac_h($rename['lijst_s']) ?>" placeholder="animal_type"><input type="text" name="lijst_p" value="<?= ac_h($rename['lijst_p']) ?>" placeholder="animal_types">
                            <span>User-actie (order)</span><input type="text" name="actie_s" value="<?= ac_h($rename['actie_s']) ?>" placeholder="appointment"><input type="text" name="actie_p" value="<?= ac_h($rename['actie_p']) ?>" placeholder="appointments">
                            <span>App-naam</span><input type="text" name="app_name" value="<?= ac_h($rename['app_name']) ?>" placeholder="Dierenasiel Pootjes"><span class="hint">mag leeg</span>
                            <span>Databasenaam</span><input type="text" name="db_name" value="<?= ac_h($rename['db_name']) ?>" placeholder="dierenasiel"><span class="hint">mag leeg</span>
                        </div>
                        <div class="row"><button type="submit" class="btn btn-small">Hernoemen</button></div>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
