<?php
// =====================================================================
// report.php - RAPPORT: gewogen gemiddelde per vak
//   leerling = eigen rapport
//   docent   = kiest een leerling in de dropdown (?student_id=2)
// =====================================================================
require_once 'includes/init.php';
require_login();

// Welke leerling? Leerling: altijd zichzelf (NOOIT uit de URL!). Docent: uit de dropdown.
$student_id = '';
if (is_admin()) {
    $student_id = get_param('student_id');
} else {
    $student_id = (string) current_user_id();
}

$student = null;
$report = array();

if (is_valid_id($student_id)) {
    $student = student_find($conn, (int) $student_id);
    if ($student === null) {
        function_error('Deze leerling bestaat niet.');
    } elseif ($student !== false) {
        $report = report_get($conn, (int) $student_id);
        if ($report === false) {
            $report = array();
        }
    }
}

// Totalen uitrekenen
$subject_count = 0;
$average_total = 0;
$fails = 0;
foreach ($report as $row) {
    if ($row['average'] !== null) {
        $subject_count = $subject_count + 1;
        $average_total = $average_total + (float) $row['average'];
        if ((float) $row['average'] < PASS_GRADE) {
            $fails = $fails + 1;
        }
    }
}
$overall = null;
if ($subject_count > 0) {
    $overall = round($average_total / $subject_count, 1);
}

$students = array();
if (is_admin()) {
    $students = students_get_all($conn, '', '');
    if ($students === false) {
        $students = array();
    }
}

$page_title = 'Rapport';
require 'includes/header.php';
?>

<h1>Rapport</h1>

<?php if (is_admin()): ?>
    <form method="get" action="report.php" class="card filter-bar">
        <select name="student_id">
            <option value="">-- Kies een leerling --</option>
            <?php foreach ($students as $s): ?>
                <option value="<?= e($s['id']) ?>" <?= selected($student_id, $s['id']) ?>><?= e($s['class_name']) ?> - <?= e($s['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn">Toon rapport</button>
        <a href="report.php" class="btn btn-grey">Wissen</a>
    </form>
<?php endif; ?>

<?php if ($student === null || $student === false): ?>
    <div class="card empty">Kies een leerling om het rapport te bekijken.</div>
<?php else: ?>
    <div class="card">
        <h2><?= e($student['name']) ?></h2>
        <p>
            Klas: <strong><?= e($student['class_name']) ?></strong> &middot;
            Gemiddelde over alle vakken: <strong><?= e(format_grade($overall)) ?></strong> &middot;
            Onvoldoendes: <strong><?= $fails ?></strong>
        </p>
        <?php if ($subject_count === 0): ?>
            <p class="hint">Nog geen cijfers.</p>
        <?php elseif ($fails === 0): ?>
            <span class="status status-voldoende">Alles voldoende</span>
        <?php elseif ($fails <= 2): ?>
            <span class="status status-in_behandeling">Bespreekgeval</span>
        <?php else: ?>
            <span class="status status-onvoldoende">Te veel onvoldoendes</span>
        <?php endif; ?>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Vak</th>
                <th>Aantal cijfers</th>
                <th>Laagste</th>
                <th>Hoogste</th>
                <th>Gemiddelde (gewogen)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($report as $row): ?>
                <tr>
                    <td><?= e($row['code']) ?></td>
                    <td><?= e($row['name']) ?></td>
                    <td><?= e($row['grade_count']) ?></td>
                    <td><?= e(format_grade($row['lowest'])) ?></td>
                    <td><?= e(format_grade($row['highest'])) ?></td>
                    <td>
                        <?php if ($row['average'] === null): ?>
                            -
                        <?php else: ?>
                            <span class="status status-<?= e(grade_status($row['average'])) ?>"><?= e(format_grade($row['average'])) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (is_admin()): ?>
        <a href="grade_create.php?student_id=<?= e($student['id']) ?>" class="btn">+ Cijfer voor <?= e($student['name']) ?></a>
    <?php endif; ?>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
