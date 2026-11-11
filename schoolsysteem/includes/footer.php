</main>

<footer class="footer">
    <?= e(APP_NAME) ?> &middot; <?= date('Y') ?>
</footer>

</body>
</html>
<?php
// Netjes afsluiten: database verbinding dicht
$conn->close();
