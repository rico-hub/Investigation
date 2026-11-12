<!-- Zelfde name="action", andere value: PHP ziet welke knop gedrukt is -->
<form method="post" action="knoppen.php">
    <input type="hidden" name="item_id" value="<?= e($item['id']) ?>">
    <button type="submit" name="action" value="approve" class="btn">&#9989; Goedkeuren (actief)</button>
    <button type="submit" name="action" value="reject" class="btn btn-red">&#10060; Afwijzen (inactief)</button>
</form>
