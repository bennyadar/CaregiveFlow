<?php require __DIR__ . '/../layout/header.php'; ?>
<?php
$curId = $item[$cols['id']] ?? null;
$startVal = $item[$cols['start']] ?? '';
$endVal = $item[$cols['end']] ?? '';
$empSel = $item[$cols['employee_id']] ?? '';
$emprSel = $item[$cols['employer_id']] ?? '';
$active = ($startVal && $startVal <= date('Y-m-d') && (empty($endVal) || $endVal >= date('Y-m-d')));

// ===== כותרת מסך + פעולות (מיושר ל-UI conventions) =====
$title = 'פרטי שיבוץ #' . (int)$curId;
$rightHtml = '';
if ((current_user()['role'] ?? '') !== 'viewer') {
  $rightHtml .= '<a class="btn btn-primary" href="?r=placements/edit&id='.(int)$curId.'">עריכה</a>';
}
$rightHtml .= '<a class="btn btn-outline-secondary" href="?r=placements/index">חזרה</a>';
include __DIR__ . '/../partials/page_header.php';
?>
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
<?php require __DIR__ . '/../layout/footer.php'; ?>
