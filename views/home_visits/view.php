<?php /** @var array $row */ ?>
<!-- CaregiveFlow — Home Visits Module -->
<!-- File: views/home_visits/view.php -->
<!-- HE: מסך צפייה בפרטי ביקור -->
<!-- EN: Read view for a single Home Visit -->
<?php require __DIR__ . '/../layout/header.php'; ?>
<div class="container" dir="rtl">
  <h2 class="mt-3 mb-3"><i class="bi bi-clipboard2-pulse"></i> פרטי ביקור #<?= e($row['id']) ?></h2>

  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3"><strong>עובד:</strong> <?= e(($row['employee_last_name'] ?? '') . ' ' . ($row['employee_first_name'] ?? '') . ' (ID ' . $row['employee_id'] . ')') ?></div>
        <div class="col-md-3"><strong>תאריך ביקור:</strong> <?= e($row['visit_date']) ?></div>
        <div class="col-md-3"><strong>סוג:</strong> <?= e($row['type_name'] ?? '') ?></div>
        <div class="col-md-3"><strong>שלב:</strong> <?= e($row['stage_name'] ?? '') ?></div>
        <div class="col-md-3"><strong>סטטוס:</strong> <?= e($row['status_name'] ?? '') ?></div>
        <div class="col-md-3"><strong>סוג השמה:</strong> <?= e($row['placement_type_name'] ?? '') ?></div>
        <div class="col-md-3"><strong>נדרש מעקב:</strong> <?= !empty($row['followup_required']) ? '✔' : '' ?></div>
        <div class="col-md-3"><strong>ביקור הבא:</strong> <?= e($row['next_visit_due'] ?? '') ?></div>
        <div class="col-md-3"><strong>שיבוץ:</strong> <?= e($row['placement_id'] ?? '') ?></div>
      </div>
      <hr>
      <div class="mb-2"><strong>סיכום:</strong><br><?= nl2br(e($row['summary'] ?? '')) ?></div>
      <div class="mb-0"><strong>ממצאים:</strong><br><?= nl2br(e($row['findings'] ?? '')) ?></div>
    </div>
  </div>

  <!-- Files block: reuse your existing file-upload partial for entity_type='home_visit' & entity_id=$row['id'] -->
  <div class="card">
    <div class="card-header"><i class="bi bi-paperclip"></i> קבצים מצורפים</div>
    <div class="card-body">
      <div class="alert alert-info">להטמעה: שימוש במודול הקבצים הקיים עם entity_type='home_visit' + entity_id=<?= e($row['id']) ?>.</div>
    </div>
  </div>

  <div class="mt-3">
    <a class="btn btn-outline-primary" href="index.php?r=home_visits/edit&id=<?= e($row['id']) ?>"><i class="bi bi-pencil-square"></i> עריכה</a>
    <a class="btn btn-secondary" href="index.php?r=home_visits/index"><i class="bi bi-arrow-right"></i> חזרה</a>
  </div>
</div>
