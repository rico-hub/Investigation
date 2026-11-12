<!-- min = vandaag (browser). PHP checkt het OOK, want HTML kun je omzeilen -->
<input type="date" name="start_date" min="<?= e(date('Y-m-d')) ?>" value="<?= e($data['start_date']) ?>">
