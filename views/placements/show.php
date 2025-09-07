<?php require __DIR__ . '/../layout/header.php'; ?>
<?php
$curId = $item[$cols['id']] ?? null;
$startVal = $item[$cols['start']] ?? '';
$endVal = $item[$cols['end']] ?? '';
$empSel = $item[$cols['employee_id']] ?? '';
$emprSel = $item[$cols['employer_id']] ?? '';
$active = ($startVal && $startVal <= date('Y-m-d') && (empty($endVal) || $endVal >= date('Y-m-d')));
?>
<h1 class="h4 mb-3">פרטי שיבוץ #<?= (int)$curId ?></h1>
<div class="card">
  <div class="card-body">
    <dl class="row mb-0">
      <dt class="col-sm-2">עובד</dt><dd class="col-sm-10">#<?= (int)$empSel ?></dd>
      <dt class="col-sm-2">מעסיק</dt><dd class="col-sm-10">#<?= (int)$emprSel ?></dd>
      <dt class="col-sm-2">התחלה</dt><dd class="col-sm-10"><?= e($startVal) ?></dd>
      <dt class="col-sm-2">סיום</dt><dd class="col-sm-10"><?= e($endVal ?: '—') ?></dd>
      <dt class="col-sm-2">סטטוס</dt><dd class="col-sm-10"><?= $active ? 'פעיל' : 'סגור' ?></dd>
      <?php if (!empty($item['notes']) || !empty($item['remarks']) || !empty($item['comment'])): ?>
        <dt class="col-sm-2">הערות</dt><dd class="col-sm-10"><?= nl2br(e($item['notes'] ?? ($item['remarks'] ?? ($item['comment'] ?? '')))) ?></dd>
      <?php endif; ?>
    </dl>
  </div>
</div>
<p class="mt-3">
  <a class="btn btn-primary" href="index.php?r=placements/edit&id=<?= (int)$curId ?>">עריכה</a>
  <a class="btn btn-outline-secondary" href="index.php?r=placements/index">חזרה</a>
</p>
<?php require __DIR__ . '/../layout/footer.php'; ?>
