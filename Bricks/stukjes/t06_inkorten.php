<!-- Lange tekst inkorten tot 50 tekens. (string) want NULL mag niet in mb_strlen -->
<?php $text = (string) $item['description']; ?>
<?php if (mb_strlen($text) > 50): ?>
    <?= e(mb_substr($text, 0, 50)) ?>...
<?php else: ?>
    <?= e($text) ?>
<?php endif; ?>
