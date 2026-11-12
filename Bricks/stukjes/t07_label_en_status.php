<!-- Code -> tekst (priority_label) en status met kleur (class status-<waarde>) -->
<?= e(priority_label($item['priority'])) ?>
<span class="status status-<?= e($item['status']) ?>"><?= e($item['status']) ?></span>
