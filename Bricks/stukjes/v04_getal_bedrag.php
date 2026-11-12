<!-- Heel getal en bedrag. step="0.01" = komma-getal toegestaan -->
<label>Voorraad <input type="number" name="stock" min="0" max="9999" value="<?= e($data['stock']) ?>"></label>
<label>Prijs <input type="number" name="price" min="0" step="0.01" value="<?= e($data['price']) ?>"></label>
