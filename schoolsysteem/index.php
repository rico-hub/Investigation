<?php
// =====================================================================
// index.php - cijferlijst
//   docent (admin) = alle cijfers + filters op klas/vak/status
//   leerling (user) = alleen eigen cijfers
// =====================================================================
require_once 'includes/init.php';
require_login();

$filters = grades_read_filters();

$only_student_id = current_user_id();
if (is_admin()) {
    $only_student_id = null;
}

$grades = grades_get_all($conn, $filters, $only_student_id);
if ($grades === false) {
    $grades = array();
}

// TotalCount + gemiddelde van wat er nu getoond wordt
$average = grades_weighted_average($grades);

$classes = classes_get_all($conn);
if ($classes === false) {
    $classes = array();
}
$subjects = subjects_get_all($conn);
if ($subjects === false) {
    $subjects = array();
}

$page_title = 'Cijfers';
require 'includes/header.php';
?>

<?php if (is_admin()): ?>
    <h1>Alle cijfers</h1>
<?php else: ?>
    <h1>Mijn cijfers</h1>
<?php endif; ?>

<form method="get" action="index.php" id="filters" class="card filter-bar">
    <input type="text" name="search" placeholder="Zoek op leerling, omschrijving of vak..." value="<?= e($filters['search']) ?>">

    <?php if (is_admin()): ?>
        <select name="class_id">
            <option value="">-- Alle klassen --</option>
            <?php foreach ($classes as $class): ?>
                <option value="<?= e($class['id']) ?>" <?= selected($filters['class_id'], $class['id']) ?>><?= e($class['name']) ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>

    <select name="subject_id">
        <option value="">-- Alle vakken --</option>
        <?php foreach ($subjects as $subject): ?>
            <option value="<?= e($subject['id']) ?>" <?= selected($filters['subject_id'], $subject['id']) ?>><?= e($subject['name']) ?></option>
        <?php endforeach; ?>
    </select>

    <select name="status">
        <option value="">-- Voldoende + onvoldoende --</option>
        <option value="voldoende" <?= selected($filters['status'], 'voldoende') ?>>Alleen voldoendes</option>
        <option value="onvoldoende" <?= selected($filters['status'], 'onvoldoende') ?>>Alleen onvoldoendes</option>
    </select>

    <input type="hidden" name="sort" value="<?= e($filters['sort']) ?>">
    <input type="hidden" name="dir" value="<?= e($filters['dir']) ?>">

    <button type="submit" class="btn">Filteren</button>
    <a href="index.php" class="btn btn-grey">Filters wissen</a>
</form>

<p class="count">
    <strong><?= count($grades) ?></strong> cijfer(s) gevonden
    &middot; gewogen gemiddelde: <strong><?= e(format_grade($average)) ?></strong>
</p>

<table class="table">
    <thead>
        <tr>
            <th class="col-id"><?= sort_link('ID', 'id', $filters) ?></th>
            <?php if (is_admin()): ?>
                <th><?= sort_link('Leerling', 'student', $filters) ?></th>
                <th><?= sort_link('Klas', 'class', $filters) ?></th>
            <?php endif; ?>
            <th><?= sort_link('Vak', 'subject', $filters) ?></th>
            <th><?= sort_link('Omschrijving', 'description', $filters) ?></th>
            <th><?= sort_link('Cijfer', 'grade', $filters) ?></th>
            <th>Weging</th>
            <th><?= sort_link('Datum', 'date', $filters) ?></th>
            <?php if (is_admin()): ?>
                <th>Acties</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php if (count($grades) === 0): ?>
            <tr><td colspan="9" class="empty">Geen cijfers gevonden.</td></tr>
        <?php else: ?>
            <?php foreach ($grades as $grade): ?>
                <tr>
                    <td class="col-id"><?= e($grade['id']) ?></td>
                    <?php if (is_admin()): ?>
                        <td><?= e($grade['student_name']) ?></td>
                        <td><?= e($grade['class_name']) ?></td>
                    <?php endif; ?>
                    <td><?= e($grade['subject_name']) ?></td>
                    <td><?= e($grade['description']) ?></td>
                    <td><span class="status status-<?= e(grade_status($grade['grade'])) ?>"><?= e(format_grade($grade['grade'])) ?></span></td>
                    <td><?= e($grade['weight']) ?>x</td>
                    <td><?= e(format_date($grade['test_date'], false)) ?></td>
                    <?php if (is_admin()): ?>
                        <td class="actions">
                            <a href="grade_edit.php?id=<?= e($grade['id']) ?>" class="btn btn-small btn-grey">Wijzigen</a>
                            <a href="grade_delete.php?id=<?= e($grade['id']) ?>" class="btn btn-small btn-red">Verwijderen</a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require 'includes/footer.php'; ?>
