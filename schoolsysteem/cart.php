<?php
// =====================================================================
// cart.php - SELECTIE: 1 toets invoeren voor alle geselecteerde leerlingen
// =====================================================================
require_once 'includes/init.php';
require_admin();

$data = grade_empty_data();
$entered = array(); // student_id => ingevuld cijfer (bewaren bij een fout)

if (is_post()) {
    $action = post_param('action');

    if ($action === 'remove') {
        cart_remove(post_param('student_id'));
        redirect('cart.php');
    } elseif ($action === 'clear') {
        cart_clear();
        set_flash('success', 'Selectie is leeg gemaakt.');
        redirect('cart.php');
    } elseif ($action === 'save') {
        $data = grade_form_data();

        // Cijfers komen binnen als grades[2] = '7,5', grades[3] = '6'
        // Alleen leerlingen die in de selectie zitten tellen mee.
        foreach (cart_get() as $student_id) {
            $entered[$student_id] = '';
            if (isset($_POST['grades'][$student_id]) && is_string($_POST['grades'][$student_id])) {
                $entered[$student_id] = trim($_POST['grades'][$student_id]);
            }
        }

        $saved = grades_bulk_create($conn, $data, $entered, current_user_id());
        if ($saved !== false) {
            log_action($conn, current_user_id(), 'grade_bulk', $saved . ' cijfers: ' . $data['description']);
            cart_clear(); // klaar = selectie uit de sessie
            set_flash('success', $saved . ' cijfer(s) opgeslagen voor "' . $data['description'] . '".');
            redirect('index.php');
        }
        // fout: formulier opnieuw tonen met alle ingevulde waarden
    } else {
        function_error('Onbekende actie.');
        redirect('cart.php');
    }
}

$students = cart_get_students($conn);
if ($students === false) {
    $students = array();
}
$subjects = subjects_get_all($conn);
if ($subjects === false) {
    $subjects = array();
}

$page_title = 'Selectie';
require 'includes/header.php';
?>

<h1>Toets invoeren voor selectie</h1>
<p class="count"><strong><?= count($students) ?></strong> leerling(en) geselecteerd. <a href="students.php">Meer selecteren</a></p>

<?php if (count($students) === 0): ?>
    <div class="card empty">Je selectie is leeg. Ga naar <a href="students.php">Leerlingen</a> en klik op "+ Selectie".</div>
<?php else: ?>
    <form method="post" action="cart.php" class="card">
        <input type="hidden" name="action" value="save">

        <table class="form-table">
            <tr>
                <th><label for="subject_id">Vak *</label></th>
                <td>
                    <select id="subject_id" name="subject_id" required>
                        <option value="">-- Kies een vak --</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?= e($subject['id']) ?>" <?= selected($data['subject_id'], $subject['id']) ?>><?= e($subject['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="description">Omschrijving *</label></th>
                <td><input type="text" id="description" name="description" maxlength="100" required value="<?= e($data['description']) ?>"></td>
            </tr>
            <tr>
                <th><label for="weight">Weging</label></th>
                <td>
                    <select id="weight" name="weight">
                        <?php for ($w = 1; $w <= 5; $w++): ?>
                            <option value="<?= $w ?>" <?= selected($data['weight'], $w) ?>><?= $w ?>x</option>
                        <?php endfor; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="test_date">Toetsdatum</label></th>
                <td><input type="date" id="test_date" name="test_date" max="<?= date('Y-m-d') ?>" required value="<?= e($data['test_date']) ?>"></td>
            </tr>
        </table>

        <!-- Tabel om data toe te voegen: 1 invulvakje per leerling -->
        <table class="table">
            <thead>
                <tr>
                    <th class="col-id">ID</th>
                    <th>Leerling</th>
                    <th>Klas</th>
                    <th>Cijfer (leeg = overslaan)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <?php
                    $value = '';
                    if (isset($entered[(int) $student['id']])) {
                        $value = $entered[(int) $student['id']];
                    }
                    ?>
                    <tr>
                        <td class="col-id"><?= e($student['id']) ?></td>
                        <td><?= e($student['name']) ?></td>
                        <td><?= e($student['class_name']) ?></td>
                        <td><input type="text" name="grades[<?= e($student['id']) ?>]" class="grade-input" placeholder="7,5" value="<?= e($value) ?>"></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <button type="submit" class="btn">Alle cijfers opslaan</button>
    </form>

    <h2>Selectie aanpassen</h2>
    <div class="card">
        <?php foreach ($students as $student): ?>
            <form method="post" action="cart.php" class="inline">
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="student_id" value="<?= e($student['id']) ?>">
                <button type="submit" class="btn btn-small btn-grey">&times; <?= e($student['name']) ?></button>
            </form>
        <?php endforeach; ?>
        <form method="post" action="cart.php" class="inline">
            <input type="hidden" name="action" value="clear">
            <button type="submit" class="btn btn-small btn-red">Selectie legen</button>
        </form>
    </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
