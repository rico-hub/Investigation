<!-- maxlength = zelfde getal als VARCHAR(n) in de database -->
<label>Naam <input type="text" name="name" maxlength="150" value="<?= e($data['name']) ?>"></label>
<label>E-mail <input type="email" name="email" maxlength="150" value="<?= e($data['email']) ?>"></label>

<!-- Wachtwoord: NOOIT een value terugzetten -->
<label>Wachtwoord <input type="password" name="password" minlength="8"></label>

<!-- Textarea: waarde TUSSEN de tags, niet in value -->
<label>Omschrijving <textarea name="description"><?= e($data['description']) ?></textarea></label>

<!-- Verborgen: wordt meegestuurd, niet zichtbaar -->
<input type="hidden" name="secret_id" value="<?= e($data['secret_id']) ?>">
