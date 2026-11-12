<?php
// includes/footer.php - UNIVERSEEL: in elke app exact hetzelfde bestand. NIET AANPASSEN.
// Gebruikt dezelfde variabelen als header.php (uit config/site.php).
?>
</main>

<footer class="site-footer">
    <div class="footer">
        <div class="footer-brand">
            <a href="index.php" class="logo">
                <svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?= $site_logo ?></svg>
                <?= e(APP_NAME) ?>
            </a>
            <p><?= e($site_tagline) ?> &copy; <?= date('Y') ?></p>
            <!-- Social icons: wel klikbaar, maar gaan (nog) nergens heen -->
            <div class="socials">
                <button type="button" aria-label="Instagram"><svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="20" x="2" y="2" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><path d="M17.5 6.5h.01"/></svg></button>
                <button type="button" aria-label="YouTube"><svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 17a24 24 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.6 49.6 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24 24 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.6 49.6 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/><path d="m10 15 5-3-5-3z"/></svg></button>
                <button type="button" aria-label="Facebook"><svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></button>
                <button type="button" aria-label="Twitter"><svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/></svg></button>
                <button type="button" aria-label="LinkedIn"><svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"/><rect width="4" height="12" x="2" y="9"/><circle cx="4" cy="4" r="2"/></svg></button>
            </div>
        </div>

        <div class="footer-cols">
            <div>
                <h4>Menu</h4>
                <?php foreach ($menu as $file => $label): ?>
                    <a href="<?= e($file) ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </div>
            <?php if (count($account_menu) > 0): ?>
                <div>
                    <h4>Account</h4>
                    <?php foreach ($account_menu as $file => $label): ?>
                        <a href="<?= e($file) ?>"><?= e($label) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</footer>

<!-- Filter-pijltjes naast de kolomnamen van elke tabel -->
<script src="js/column-filter.js?v=<?= filemtime(__DIR__ . '/../js/column-filter.js') ?>"></script>
<!-- Filterbalk: zoekrij + chips met dropdown -->
<script src="js/filter-bar.js?v=<?= filemtime(__DIR__ . '/../js/filter-bar.js') ?>"></script>
</body>
</html>
<?php
// Netjes afsluiten: database verbinding dicht
$conn->close();
