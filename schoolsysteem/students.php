<?php
// students.php - leerlingenlijst (docent) + selecteren voor bulk-invoer
require_once 'includes/init.php';
require_admin();

$class_id = get_param('class_id');
$search = get_param('search');

if (is_post()) {
    $action = post_param('action');

    if ($action === 'add_to_cart') {
        if (cart_add($conn, post_param('student_id'))) {
            set_flash('success', 'Leerling toegevoegd aan je selectie.');
        }
    } elseif ($action === 'add_class') {
        $added = cart_add_class($conn, post_param('class_id'));
        if ($added !== false) {
            set_flash('success', $added . ' leerling(en) toegevoegd aan je selectie.');
        }
    }
    redirect('students.php?' . current_query());
}

$students = students_get_all($conn, $class_id, $search);
if ($students === false) {
    $students = array();
}

$classes = classes_get_all($conn);
if ($classes === false) {
    $classes = array();
}

$cart = cart_get();
$current_query = http_build_query(array('class_id' => $class_id, 'search' => $search));

$page_title = 'Leerlingen';
require 'includes/header.php';
?>

<h1>Leerlingen</h1>

<form method="get" action="students.php" class="card filter-bar">
    <input type="text" name="search" placeholder="Zoek op naam of e-mail..." value="<?= e($search) ?>">
    <select name="class_id">
        <option value="">-- Alle klassen --</option>
        <?php foreach ($classes as $class): ?>
            <option value="<?= e($class['id']) ?>" <?= selected($class_id, $class['id']) ?>><?= e($class['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">Filteren</button>
    <a href="students.php" class="btn btn-grey">Filters wissen</a>
</form>

<?php if (is_valid_id($class_id)): ?>
    <form method="post" action="students.php?<?= e($current_query) ?>" class="inline">
        <input type="hidden" name="action" value="add_class">
        <input type="hidden" name="class_id" value="<?= e($class_id) ?>">
        <button type="submit" class="btn">Hele klas selecteren</button>
    </form>
<?php endif; ?>

<p class="count"><strong><?= count($students) ?></strong> leerling(en) gevonden &middot; <?= count($cart) ?> geselecteerd</p>

<table class="table">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th>Naam</th>
            <th>E-mail</th>
            <th>Klas</th>
            <th>Cijfers</th>
            <th>Acties</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($students) === 0): ?>
            <tr><td colspan="6" class="empty">Geen leerlingen gevonden.</td></tr>
        <?php else: ?>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td class="col-id"><?= e($student['id']) ?></td>
                    <td><?= e($student['name']) ?></td>
                    <td><?= e($student['email']) ?></td>
                    <td><?= e($student['class_name']) ?></td>
                    <td><?= e($student['grade_count']) ?></td>
                    <td class="actions">
                        <?php if (in_array((int) $student['id'], $cart, true)): ?>
                            <span class="status status-actief">geselecteerd</span>
                        <?php else: ?>
                            <form method="post" action="students.php?<?= e($current_query) ?>" class="inline">
                                <input type="hidden" name="action" value="add_to_cart">
                                <input type="hidden" name="student_id" value="<?= e($student['id']) ?>">
                                <button type="submit" class="btn btn-small">+ Selectie</button>
                            </form>
                        <?php endif; ?>
                        <a href="report.php?student_id=<?= e($student['id']) ?>" class="btn btn-small btn-grey">Rapport</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require 'includes/footer.php'; ?>
