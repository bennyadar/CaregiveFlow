<?php require __DIR__ . '/../layout/header.php'; ?>
<h1 class="h4 mb-3">דו"ח — היסטוריית שיבוצים לעובד</h1>
<form class="row g-2 mb-3" method="get">
  <input type="hidden" name="r" value="reports/placements_history">
  <div class="col-md-4">
    <label class="form-label">עובד</label>
    <select class="form-select" name="employee_id" required>
      <option value="">-- בחר/י עובד --</option>
      <?php foreach ($employees as $e): ?>
        <option value="<?= (int)$e['id'] ?>" <?= ((int)($_GET['employee_id'] ?? 0) === (int)$e['id'])?'selected':'' ?>>
          <?= e($e['last_name'] . ' ' . $e['first_name'] . ' [' . $e['passport_number'] . ']') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2 d-flex align-items-end">
    <button class="btn btn-primary">הצג</button>
  </div>
</form>

<?php if (!empty($rows)): ?>
<div class="table-responsive">
<table class="table table-striped align-middle">
  <thead><tr>
    <th>#</th><th>מעסיק</th><th>התחלה</th><th>סיום</th>
  </tr></thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r[$cols['id']] ?></td>
        <td><?= e(($r['employer_last'] ?? '') . ' ' . ($r['employer_first'] ?? '') . ' [' . ($r['id_number'] ?? '') . ']') ?></td>
        <td><?= e($r[$cols['start']] ?? '') ?></td>
        <td><?= e(($r[$cols['end']] ?? '') ?: '—') ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../layout/footer.php'; ?>
