<!-- Datum + tijd: 2026-11-10 14:30:00 -> 10-11-2026 14:30 -->
<?php if ($item['created_at'] === null): ?>
    -
<?php else: ?>
    <?= e(date('d-m-Y H:i', strtotime($item['created_at']))) ?>
<?php endif; ?>
