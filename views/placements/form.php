<?php require __DIR__ . '/../layout/header.php'; ?>
<?php
$curId = $item[$cols['id']] ?? null;
$startVal = $item[$cols['start']] ?? '';
$endVal = $item[$cols['end']] ?? '';
$empSel = $item[$cols['employee_id']] ?? '';
$empSel = $selected_employee_id ?? $item[$cols['employee_id']]  ?? '';
$emprSel = $item[$cols['employer_id']] ?? '';
?>
<h1 class="h4 mb-3"><?= $curId ? 'עריכת שיבוץ #' . (int)$curId : 'שיבוץ חדש' ?></h1>
<form method="post" class="row g-3">
  <div class="col-md-4">
    <label class="form-label">עובד *</label>
    <select name="employee_id" class="form-select" required>
      <option value="">-- בחר/י עובד --</option>
      <?php foreach ($employees as $e): ?>
        <option value="<?= (int)$e['id'] ?>" <?= ((int)$empSel === (int)$e['id'])?'selected':'' ?>>
          <?= e($e['last_name'] . ' ' . $e['first_name'] . ' [' . $e['passport_number'] . ']') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label">מעסיק *</label>
    <select name="employer_id" class="form-select" required>
      <option value="">-- בחר/י מעסיק --</option>
      <?php foreach ($employers as $er): ?>
        <option value="<?= (int)$er['id'] ?>" <?= ((int)$emprSel === (int)$er['id'])?'selected':'' ?>>
          <?= e($er['last_name'] . ' ' . $er['first_name'] . ' [' . $er['id_number'] . ']') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2">
    <label class="form-label">תאריך התחלה *</label>
    <input type="date" name="start_date" class="form-control" required value="<?= e($startVal) ?>">
  </div>
  <div class="col-md-2">
    <label class="form-label">תאריך סיום</label>
    <input type="date" name="end_date" class="form-control" value="<?= e($endVal) ?>">
  </div>
  <div class="col-12">
    <label class="form-label">הערות</label>
    <textarea name="notes" class="form-control" rows="3"><?= e($item['notes'] ?? ($item['remarks'] ?? ($item['comment'] ?? ''))) ?></textarea>
  </div>
  <div class="col-12 d-flex gap-2">
    <button class="btn btn-primary">שמירה</button>
    <a class="btn btn-outline-secondary" href="index.php?r=placements/index">חזרה</a>
  </div>
</form>
<?php require __DIR__ . '/../layout/footer.php'; ?>
