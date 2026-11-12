<!-- Leeg of NULL -> streepje, anders de tekst. nl2br = enters behouden -->
<?php if ($item['description'] === null || $item['description'] === ''): ?>
    -
<?php else: ?>
    <?= nl2br(e($item['description'])) ?>
<?php endif; ?>
