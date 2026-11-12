<!-- Geld: 1234.5 -> 1.234,50  (2 decimalen, komma, punt voor duizendtallen) -->
&euro; <?= e(number_format((float) $item['price'], 2, ',', '.')) ?>
