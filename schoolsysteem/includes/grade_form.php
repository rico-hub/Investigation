<?php
// includes/grade_form.php - Verwacht: $data, $students, $subjects, $form_action, $button_text
?>
<form method="post" action="<?= e($form_action) ?>" class="card form">
    <table class="form-table">
        <tr>
            <th><label for="student_id">Leerling *</label></th>
            <td>
                <select id="student_id" name="student_id" required>
                    <option value="">-- Kies een leerling --</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?= e($student['id']) ?>" <?= selected($data['student_id'], $student['id']) ?>>
                            <?= e($student['class_name']) ?> - <?= e($student['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
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
            <td><input type="text" id="description" name="description" maxlength="100" placeholder="bv. Toets H3" required value="<?= e($data['description']) ?>"></td>
        </tr>
        <tr>
            <th><label for="grade">Cijfer * (1,0 - 10,0)</label></th>
            <td><input type="text" id="grade" name="grade" class="grade-input" placeholder="7,5" required value="<?= e($data['grade']) ?>"></td>
        </tr>
        <tr>
            <th><label for="weight">Weging *</label></th>
            <td>
                <select id="weight" name="weight">
                    <?php for ($w = 1; $w <= 5; $w++): ?>
                        <option value="<?= $w ?>" <?= selected($data['weight'], $w) ?>><?= $w ?>x</option>
                    <?php endfor; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="test_date">Toetsdatum *</label></th>
            <td><input type="date" id="test_date" name="test_date" max="<?= date('Y-m-d') ?>" required value="<?= e($data['test_date']) ?>"></td>
        </tr>
        <tr>
            <th></th>
            <td>
                <button type="submit" class="btn"><?= e($button_text) ?></button>
                <a href="index.php" class="btn btn-grey">Annuleren</a>
            </td>
        </tr>
    </table>
</form>
