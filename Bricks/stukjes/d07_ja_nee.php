<!-- 7. Ja / Nee voor een TINYINT(1) kolom: 1 of 0 -->
<select name="is_active">
    <option value="1" <?= selected($data['is_active'], '1') ?>>Ja</option>
    <option value="0" <?= selected($data['is_active'], '0') ?>>Nee</option>
</select>
