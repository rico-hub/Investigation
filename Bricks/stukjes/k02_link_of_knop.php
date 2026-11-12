<!-- Alleen kijken / naar een andere pagina = LINK (GET, verandert niets) -->
<a href="tonen.php?id=<?= e($item['id']) ?>" class="btn">&#128065; Bekijken</a>
<a href="opslaan.php" class="btn">&#10133; Nieuw item</a>
<a href="index.php" class="btn btn-grey">&#11013; Terug</a>

<!-- Iets veranderen in de database = FORMULIER met POST + button (zie blok 1) -->
