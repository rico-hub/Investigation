<?php
// =====================================================================
// tools/hash_reader.php - hashes uitlezen en wachtwoorden testen
// Open: http://localhost/Investigation/tools/hash_reader.php
// Alleen voor lokaal gebruik (toont e-mails en hashes uit je database)!
//
// Een hash kun je NIET terugrekenen naar het wachtwoord (one-way).
// Deze tool laat zien WAT er in een hash staat en TEST of een wachtwoord klopt.
// =====================================================================
require_once __DIR__ . '/functions/hash_functions.php';

$mode = hash_post('mode');

// --- Deel 1: 1 hash analyseren ---------------------------------------
$single_hash = '';
$single_password = '';
$single = null;
$single_check = 'skip';
$single_error = '';

// --- Deel 2: hashes uit database -------------------------------------
$database = '';
$db_password = '';
$users = array();
$db_error = '';
$db_done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'single') {
        $single_hash = trim(hash_post('hash'));   // spaties van kopiëren eraf
        $single_password = hash_post('password'); // wachtwoord NIET trimmen

        if ($single_hash === '') {
            $single_error = 'Error-code: ' . ERR_FUNCTION . ' - Plak eerst een hash.';
        } else {
            $single = hash_analyse($single_hash);
            if ($single['status'] === 'ok') {
                $single_check = hash_check_password($single_password, $single_hash);
            }
        }
    } elseif ($mode === 'database') {
        $database = hash_post('database');
        $db_password = hash_post('password');

        $result = hash_read_users($database);
        if (is_string($result)) {
            $db_error = $result;          // foutmelding (420 of 410)
        } else {
            $users = $result;
            $db_done = true;
        }
    } else {
        $single_error = 'Error-code: ' . ERR_FUNCTION . ' - Onbekende actie.';
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hash reader</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f3f4f6; color: #1f2937; margin: 0; padding: 32px 16px; }
        .wrap { max-width: 1000px; margin: 0 auto; }
        .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); margin-bottom: 20px; }
        h1 { margin-top: 0; }
        label { display: block; font-weight: bold; margin: 12px 0 4px; }
        input[type="text"], select { width: 100%; padding: 8px 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 14px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); box-sizing: border-box; }
        input.mono { font-family: Consolas, monospace; }
        button { margin-top: 14px; padding: 8px 16px; border: 0; border-radius: 6px; background: #2563eb; color: #fff; font-size: 14px; cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
        button:hover { background: #1d4ed8; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 14px; vertical-align: top; }
        thead th { background: #eef2ff; }
        code { font-family: Consolas, monospace; background: #f3f4f6; padding: 1px 4px; border-radius: 4px; word-break: break-all; }
        .hint { color: #6b7280; font-size: 13px; }
        .flash { padding: 12px 16px; border-radius: 6px; margin-top: 14px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
        .flash-error { background: #fee2e2; color: #991b1b; }
        .flash-ok { background: #dcfce7; color: #166534; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; background: #e5e7eb; }
        .badge-ok { background: #dcfce7; color: #16a34a; }
        .badge-bad { background: #fee2e2; color: #dc2626; }
        .badge-warn { background: #fef3c7; color: #d97706; }
        .parts { font-family: Consolas, monospace; font-size: 15px; word-break: break-all; }
        .p-algo { color: #2563eb; } .p-cost { color: #d97706; } .p-salt { color: #16a34a; } .p-sum { color: #7c3aed; }
        a { color: #2563eb; }
    </style>
</head>
<body>
<div class="wrap">

    <div class="card">
        <h1>Hash reader</h1>
        <p>Een wachtwoord-hash kun je <strong>niet terugrekenen</strong> naar het wachtwoord: <code>password_hash()</code> werkt maar 1 kant op.
           Wat deze tool wel doet: laten zien <strong>wat er in de hash staat</strong> en <strong>testen</strong> of een wachtwoord erbij hoort
           (<code>password_verify()</code>, precies wat je login doet).</p>
        <p class="hint">Nieuwe hash nodig? <a href="make_hash.php">make_hash.php</a></p>
    </div>

    <!-- ================= DEEL 1: 1 hash ================= -->
    <form method="post" action="hash_reader.php" class="card">
        <h2>1. Hash uitlezen</h2>
        <input type="hidden" name="mode" value="single">

        <label for="hash">Hash (bv. uit phpMyAdmin, kolom <code>password</code>)</label>
        <input type="text" id="hash" name="hash" class="mono" value="<?= hash_e($single_hash) ?>" placeholder="$2y$10$...">

        <label for="password">Wachtwoord om te testen (optioneel)</label>
        <input type="text" id="password" name="password" value="<?= hash_e($single_password) ?>" placeholder="password">

        <button type="submit">Uitlezen</button>

        <?php if ($single_error !== ''): ?>
            <div class="flash flash-error"><?= hash_e($single_error) ?></div>
        <?php endif; ?>

        <?php if ($single !== null): ?>
            <?php if ($single['status'] === 'ok'): ?>
                <div class="flash flash-ok"><?= hash_e($single['message']) ?></div>
            <?php else: ?>
                <div class="flash flash-error">Error-code: <?= ERR_FUNCTION ?> - <?= hash_e($single['message']) ?></div>
            <?php endif; ?>

            <?php if ($single['status'] === 'ok' && $single['algo'] === 'bcrypt'): ?>
                <?php $pieces = explode('$', $single_hash); ?>
                <p class="parts">
                    $<span class="p-algo"><?= hash_e($pieces[1]) ?></span>$<span class="p-cost"><?= hash_e($pieces[2]) ?></span>$<span class="p-salt"><?= hash_e($single['salt']) ?></span><span class="p-sum"><?= hash_e($single['checksum']) ?></span>
                </p>
            <?php endif; ?>

            <table>
                <tbody>
                    <tr><th>Algoritme</th><td><span class="p-algo"><?= hash_e($single['algo']) ?></span></td></tr>
                    <tr><th>Cost / opties</th><td><span class="p-cost"><?= hash_e($single['cost']) ?></span>
                        <?php if ($single['algo'] === 'bcrypt'): ?>
                            <span class="hint">(2^<?= hash_e($single['cost']) ?> = <?= number_format(pow(2, (int) $single['cost']), 0, ',', '.') ?> rondes: hoe hoger, hoe trager te kraken)</span>
                        <?php endif; ?>
                    </td></tr>
                    <tr><th>Salt</th><td><code class="p-salt"><?= hash_e($single['salt']) ?></code>
                        <span class="hint">willekeurig per hash: daarom geeft hetzelfde wachtwoord steeds een andere hash</span></td></tr>
                    <tr><th>Hash-deel</th><td><code class="p-sum"><?= hash_e($single['checksum']) ?></code></td></tr>
                    <tr><th>Lengte</th><td><?= hash_e($single['length']) ?> tekens
                        <?php if ($single['algo'] === 'bcrypt' && $single['length'] !== 60): ?>
                            <span class="badge badge-bad">moet 60 zijn</span>
                        <?php endif; ?>
                    </td></tr>
                    <tr><th>Opnieuw hashen nodig?</th><td>
                        <?php if ($single['status'] !== 'ok'): ?>
                            -
                        <?php elseif ($single['rehash']): ?>
                            <span class="badge badge-warn">ja</span> <span class="hint">password_needs_rehash(): PHP heeft nu een sterkere standaard</span>
                        <?php else: ?>
                            <span class="badge badge-ok">nee</span>
                        <?php endif; ?>
                    </td></tr>
                    <tr><th>Wachtwoord klopt?</th><td>
                        <?php if ($single_check === 'match'): ?>
                            <span class="badge badge-ok">JA</span> password_verify() = true &rarr; inloggen met dit wachtwoord werkt
                        <?php elseif ($single_check === 'nomatch'): ?>
                            <span class="badge badge-bad">NEE</span> password_verify() = false
                        <?php else: ?>
                            <span class="hint">vul een wachtwoord in om te testen</span>
                        <?php endif; ?>
                    </td></tr>
                </tbody>
            </table>
        <?php endif; ?>
    </form>

    <!-- ================= DEEL 2: database ================= -->
    <form method="post" action="hash_reader.php" class="card">
        <h2>2. Hashes uit een database lezen</h2>
        <p class="hint">Leest de tabel <code>users</code> en controleert elke hash. Handig als inloggen niet lukt.</p>
        <input type="hidden" name="mode" value="database">

        <label for="database">Database</label>
        <select id="database" name="database">
            <option value="">-- Kies een database --</option>
            <?php foreach (hash_allowed_databases() as $name): ?>
                <?php if ($database === $name): ?>
                    <option value="<?= hash_e($name) ?>" selected><?= hash_e($name) ?></option>
                <?php else: ?>
                    <option value="<?= hash_e($name) ?>"><?= hash_e($name) ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>

        <label for="db_password">Wachtwoord om bij iedereen te testen (optioneel)</label>
        <input type="text" id="db_password" name="password" value="<?= hash_e($db_password) ?>" placeholder="password">

        <button type="submit">Lezen</button>

        <?php if ($db_error !== ''): ?>
            <div class="flash flash-error"><?= hash_e($db_error) ?></div>
        <?php endif; ?>

        <?php if ($db_done): ?>
            <p><strong><?= count($users) ?></strong> gebruiker(s) in <code><?= hash_e($database) ?></code></p>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Naam</th>
                        <th>E-mail</th>
                        <th>Rol</th>
                        <th>Hash</th>
                        <th>Algoritme</th>
                        <th>Status</th>
                        <th>Wachtwoord klopt?</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) === 0): ?>
                        <tr><td colspan="8" class="hint">Geen gebruikers.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <?php
                            $info = hash_analyse((string) $user['password']);
                            $check = 'skip';
                            if ($info['status'] === 'ok') {
                                $check = hash_check_password($db_password, $user['password']);
                            }
                            ?>
                            <tr>
                                <td><?= hash_e($user['id']) ?></td>
                                <td><?= hash_e($user['name']) ?></td>
                                <td><?= hash_e($user['email']) ?></td>
                                <td><?= hash_e($user['role']) ?></td>
                                <td><code><?= hash_e(hash_short((string) $user['password'])) ?></code></td>
                                <td><?= hash_e($info['algo']) ?> <?php if ($info['algo'] === 'bcrypt'): ?><span class="hint">cost <?= hash_e($info['cost']) ?></span><?php endif; ?></td>
                                <td>
                                    <?php if ($info['status'] === 'ok'): ?>
                                        <span class="badge badge-ok">geldig</span>
                                    <?php elseif ($info['status'] === 'plain'): ?>
                                        <span class="badge badge-bad">plain text!</span>
                                    <?php elseif ($info['status'] === 'empty'): ?>
                                        <span class="badge badge-bad">leeg</span>
                                    <?php else: ?>
                                        <span class="badge badge-bad">ongeldig</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($check === 'match'): ?>
                                        <span class="badge badge-ok">JA</span>
                                    <?php elseif ($check === 'nomatch'): ?>
                                        <span class="badge badge-bad">nee</span>
                                    <?php else: ?>
                                        <span class="hint">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </form>

</div>
</body>
</html>
