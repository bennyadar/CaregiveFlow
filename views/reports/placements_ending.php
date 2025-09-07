<?php require __DIR__ . '/../layout/header.php'; ?>
<h1 class="h4 mb-3">דו"ח — שיבוצים שמסתיימים בקרוב</h1>
<form class="row g-2 mb-3" method="get">
  <input type="hidden" name="r" value="reports/placements_ending">
  <div class="col-md-3">
    <label class="form-label">ימים קדימה</label>
    <input class="form-control" type="number" name="days" value="<?= e($_GET['days'] ?? 30) ?>">
  </div>
  <div class="col-md-2 d-flex align-items-end">
    <button class="btn btn-primary">סינון</button>
  </div>
</form>

<div class="table-responsive">
<table class="table table-striped align-middle">
  <thead><tr>
    <th>#</th><th>עובד</th><th>מעסיק</th><th>תאריך סיום</th>
  </tr></thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r[$cols['id']] ?></td>
        <td><?= e(($r['emp_last'] ?? '') . ' ' . ($r['emp_first'] ?? '') . ' [' . ($r['passport_number'] ?? '') . ']') ?></td>
        <td><?= e(($r['employer_last'] ?? '') . ' ' . ($r['employer_first'] ?? '') . ' [' . ($r['id_number'] ?? '') . ']') ?></td>
        <td><?= e($r[$cols['end']] ?? '') ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
