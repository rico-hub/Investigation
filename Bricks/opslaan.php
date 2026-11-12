<?php
require_once 'includes/init.php';
// In je echte app hier: require_admin();

// ===== 1. VERWERKEN =====
$data = item_empty_data();

if (is_post()) {
    // STUKJE 2: inlezen (zelfde names als in de HTML hieronder)
    $data = item_form_data();

    // STUKJE 3: checken (420) en opslaan (410)
    $new_id = item_create($conn, $data);
    if ($new_id !== false) {
        set_flash('success', 'Item #' . $new_id . ' opgeslagen. Je ziet hem onderaan de tabel.');
        redirect('tabel.php');
    }
    // STUKJE 4: mislukt = GEEN redirect, dus het formulier toont de fout met alles nog ingevuld
}

// ===== 2. DATA OPHALEN =====
$categories = categories_get_all($conn);
if ($categories === false) {
    $categories = array();
}
$tags = tags_get_all($conn);
if ($tags === false) {
    $tags = array();
}

// ===== 3. TONEN =====
$page_title = 'Alles samen: opslaan';
require 'includes/header.php';
?>
<h1>Alles samen: echt opslaan</h1>
<p class="lead">Hier komen de bouwstenen samen in 1 formulier dat <b>echt in de database</b> opslaat (tabel <code>items</code> en <code>item_tags</code>). Probeer eerst een fout (naam leeg, geen categorie): alles blijft staan. Daarna goed invullen: je gaat naar de tabel.</p>

<div class="card puzzle">
    <b>De 4 puzzelstukjes van elk veld:</b>
    <ol>
        <li><b>HTML</b>: het veld heeft een <code>name</code> (hieronder)</li>
        <li><b>Inlezen</b>: <code>item_form_data()</code> leest met dezelfde name</li>
        <li><b>Checken + opslaan</b>: <code>item_validate()</code> (420) en <code>item_create()</code> (410)</li>
        <li><b>Terugzetten</b>: <code>value</code>, <code>selected()</code>, <code>checked()</code> als het mislukt</li>
    </ol>
</div>

<form method="post" action="opslaan.php" class="card">
    <table class="form-table">
        <tr>
            <th><label for="name">Naam *</label></th>
            <td><input type="text" id="name" name="name" maxlength="150" value="<?= e($data['name']) ?>"></td>
        </tr>
        <tr>
            <th><label for="category_id">Categorie * <span class="hint">(dropdown uit database)</span></label></th>
            <td>
                <select id="category_id" name="category_id">
                    <option value="">-- Kies een categorie --</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category['id']) ?>" <?= selected($data['category_id'], $category['id']) ?>><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th>Prioriteit <span class="hint">(radio, key =&gt; label)</span></th>
            <td>
                <?php foreach (priority_labels() as $value => $label): ?>
                    <label class="choice"><input type="radio" name="priority" value="<?= e($value) ?>" <?= checked($data['priority'], $value) ?>> <?= e($label) ?></label>
                <?php endforeach; ?>
            </td>
        </tr>
        <tr>
            <th><label for="is_active">Actief <span class="hint">(ja/nee)</span></label></th>
            <td>
                <select id="is_active" name="is_active">
                    <option value="1" <?= selected($data['is_active'], '1') ?>>Ja</option>
                    <option value="0" <?= selected($data['is_active'], '0') ?>>Nee</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="price">Prijs *</label></th>
            <td><input type="number" id="price" name="price" min="0" step="0.01" value="<?= e($data['price']) ?>"></td>
        </tr>
        <tr>
            <th><label for="stock">Voorraad</label></th>
            <td><input type="number" id="stock" name="stock" min="0" max="9999" value="<?= e($data['stock']) ?>"></td>
        </tr>
        <tr>
            <th><label for="start_date">Startdatum <span class="hint">(mag leeg)</span></label></th>
            <td><input type="date" id="start_date" name="start_date" value="<?= e($data['start_date']) ?>"></td>
        </tr>
        <tr>
            <th>Tags <span class="hint">(meerdere checkboxes)</span></th>
            <td>
                <?php foreach ($tags as $tag): ?>
                    <label class="choice"><input type="checkbox" name="tags[]" value="<?= e($tag['id']) ?>" <?= checked_in($data['tags'], $tag['id']) ?>> <?= e($tag['name']) ?></label>
                <?php endforeach; ?>
            </td>
        </tr>
        <tr>
            <th><label for="description">Omschrijving</label></th>
            <td><textarea id="description" name="description"><?= e($data['description']) ?></textarea></td>
        </tr>
        <tr>
            <th></th>
            <td>
                <button type="submit" class="btn">&#128190; Opslaan</button>
                <a href="tabel.php" class="btn btn-grey">&#8617; Annuleren</a>
            </td>
        </tr>
    </table>
</form>

<section class="card brick">
    <h2>De code</h2>
    <p class="uitleg">&#129513; De pagina zelf (verwerken, ophalen, tonen) en de functies (<code>item_form_data</code>, <code>item_validate</code>, <code>item_create</code> staan onderaan het bestand).</p>
    <?php show_code('opslaan.php'); ?>
    <?php show_code('functions/bricks.php'); ?>
</section>

<?php require 'includes/footer.php'; ?>
