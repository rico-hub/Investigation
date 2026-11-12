<?php
// =====================================================================
// tools/hash_maker_reader.php - hash MAKEN en meteen UITLEZEN
// Open: http://localhost/Investigation/tools/hash_maker_reader.php
// Alleen voor lokaal gebruik!
//
// Vul een wachtwoord in -> je krijgt de hash (voor je dummy data SQL)
// EN de ontleding van die hash (algoritme, cost, salt, hash-deel...).
//
// LET OP: een hash kun je NIET terugrekenen naar de tekst (one-way).
// =====================================================================
require_once __DIR__ . '/functions/hash_functions.php';

$make_password = '';
$make_hash = '';
$make_check = '';
$single = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $make_password = hash_post('password');

    if ($make_password === '') {
        $make_check = 'Error-code: ' . ERR_FUNCTION . ' - Vul een wachtwoord in.';
    } else {
        $make_hash = password_hash($make_password, PASSWORD_DEFAULT);

        // Direct controleren of de hash werkt
        if (password_verify($make_password, $make_hash)) {
            $make_check = 'password_verify() = OK';
        } else {
            $make_check = 'Error-code: ' . ERR_FUNCTION . ' - Hash controle mislukt.';
        }

        // Meteen de gemaakte hash uitlezen/ontleden
        $single = hash_analyse($make_hash);
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hash maker / reader</title>
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
        code.block { display: block; background: #eef2ff; padding: 10px; margin-top: 10px; }
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
        <h1>Hash maker / reader</h1>
        <p>Vul een wachtwoord in: je krijgt de <strong>hash</strong> voor je dummy data
           <strong>en</strong> de ontleding ervan (algoritme, cost, salt, hash-deel).
           Een hash kun je <strong>niet terugrekenen</strong> naar de tekst:
           <code>password_hash()</code> werkt maar 1 kant op.</p>
    </div>

    <!-- ================= Hash maken + meteen uitlezen ================= -->
    <form method="post" action="hash_maker_reader.php" class="card">
        <h2>Hash maken</h2>
        <p class="hint">Voor in je <code>INSERT INTO users</code> dummy data.</p>

        <label for="make_password">Wachtwoord</label>
        <input type="text" id="make_password" name="password" value="<?= hash_e($make_password) ?>" placeholder="wachtwoord">

        <button type="submit">Maak hash</button>

        <?php if ($make_hash !== ''): ?>
            <p>Hash (plak in je INSERT INTO users):</p>
            <code class="block"><?= hash_e($make_hash) ?></code>
            <code class="block">INSERT INTO users (name, email, password, role) VALUES ('Naam', 'naam@test.sr', '<?= hash_e($make_hash) ?>', 'user');</code>
        <?php endif; ?>

        <?php if ($make_check !== ''): ?>
            <?php $make_ok = strpos($make_check, 'Error-code') === false; ?>
            <div class="flash <?= $make_ok ? 'flash-ok' : 'flash-error' ?>"><?= hash_e($make_check) ?></div>
        <?php endif; ?>

        <?php if ($single !== null && $single['status'] === 'ok'): ?>
            <?php if ($single['algo'] === 'bcrypt'): ?>
                <?php $pieces = explode('$', $make_hash); ?>
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
                        <span class="hint">willekeurig per hash: daarom geeft dezelfde tekst steeds een andere hash</span></td></tr>
                    <tr><th>Hash-deel</th><td><code class="p-sum"><?= hash_e($single['checksum']) ?></code></td></tr>
                    <tr><th>Lengte</th><td><?= hash_e($single['length']) ?> tekens
                        <?php if ($single['algo'] === 'bcrypt' && $single['length'] !== 60): ?>
                            <span class="badge badge-bad">moet 60 zijn</span>
                        <?php endif; ?>
                    </td></tr>
                    <tr><th>Opnieuw hashen nodig?</th><td>
                        <?php if ($single['rehash']): ?>
                            <span class="badge badge-warn">ja</span> <span class="hint">password_needs_rehash(): PHP heeft nu een sterkere standaard</span>
                        <?php else: ?>
                            <span class="badge badge-ok">nee</span>
                        <?php endif; ?>
                    </td></tr>
                </tbody>
            </table>
        <?php endif; ?>
    </form>

</div>
</body>
</html>
