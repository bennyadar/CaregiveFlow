<?php /** @var array $row */ /** @var string $error */ ?>
<!-- CaregiveFlow — Home Visits Module -->
<!-- File: views/home_visits/form.php -->
<!-- HE: טופס יצירה/עריכה של ביקור בית -->
<!-- EN: Create/Edit form for Home Visit -->
<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="container" dir="rtl">
  <h2 class="mt-3 mb-3"><i class="bi bi-clipboard2-pulse"></i> <?= !empty($row['id']) ? 'עריכת ביקור' : 'ביקור חדש' ?></h2>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="index.php?r=home_visits/<?= !empty($row['id']) ? 'update' : 'store' ?>">
    <?php if (!empty($row['id'])): ?>
      <input type="hidden" name="id" value="<?= e($row['id']) ?>">
    <?php endif; ?>

    <div class="row g-3">
      <div class="col-md-2">
        <label class="form-label">עובד*</label>
        <input class="form-control" type="number" name="employee_id" value="<?= e($row['employee_id'] ?? '') ?>" <?= !empty($_GET['employee_id']) ? 'readonly' : '' ?> required>
        <div class="form-text">מזהה עובד (ID)</div>
      </div>

      <div class="col-md-2">
        <label class="form-label">תאריך ביקור*</label>
        <input class="form-control" type="date" name="visit_date" value="<?= e($row['visit_date'] ?? date('Y-m-d')) ?>" required>
      </div>

      <div class="col-md-2">
        <label class="form-label">סוג*</label>
        <select class="form-select" name="visit_type_code" required>
          <option value="">בחר…</option>
          <option value="1" <?= (int)($row['visit_type_code'] ?? 0) === 1 ? 'selected' : '' ?>>ראשוני</option>
          <option value="2" <?= (int)($row['visit_type_code'] ?? 0) === 2 ? 'selected' : '' ?>>תקופתי (רבעוני)</option>
          <option value="3" <?= (int)($row['visit_type_code'] ?? 0) === 3 ? 'selected' : '' ?>>תקופתי (חצי-שנתי)</option>
          <option value="4" <?= (int)($row['visit_type_code'] ?? 0) === 4 ? 'selected' : '' ?>>מעקב</option>
          <option value="5" <?= (int)($row['visit_type_code'] ?? 0) === 5 ? 'selected' : '' ?>>אירוע חריג/תלונה</option>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label">שלב*</label>
        <select class="form-select" name="home_visit_stage_code" required>
          <option value="">בחר…</option>
          <option value="1" <?= (int)($row['home_visit_stage_code'] ?? 0) === 1 ? 'selected' : '' ?>>טרום השמה</option>
          <option value="2" <?= (int)($row['home_visit_stage_code'] ?? 0) === 2 ? 'selected' : '' ?>>לאחר השמה</option>
          <option value="3" <?= (int)($row['home_visit_stage_code'] ?? 0) === 3 ? 'selected' : '' ?>>שוטף</option>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label">סטטוס*</label>
        <select class="form-select" name="status_code" required>
          <option value="1" <?= (int)($row['status_code'] ?? 1) === 1 ? 'selected' : '' ?>>מתוכנן</option>
          <option value="2" <?= (int)($row['status_code'] ?? 0) === 2 ? 'selected' : '' ?>>בוצע</option>
          <option value="3" <?= (int)($row['status_code'] ?? 0) === 3 ? 'selected' : '' ?>>בוטל</option>
          <option value="4" <?= (int)($row['status_code'] ?? 0) === 4 ? 'selected' : '' ?>>הוחמצה פגישה/לא התאפשר</option>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label">סוג השמה</label>
        <select class="form-select" name="placement_type_code">
          <option value="">לא צוין</option>
          <option value="1" <?= (int)($row['placement_type_code'] ?? 0) === 1 ? 'selected' : '' ?>>חזרה מאינטרויזה</option>
          <option value="2" <?= (int)($row['placement_type_code'] ?? 0) === 2 ? 'selected' : '' ?>>עובד מחו"ל</option>
          <option value="3" <?= (int)($row['placement_type_code'] ?? 0) === 3 ? 'selected' : '' ?>>עובד ממאגר</option>
          <option value="4" <?= (int)($row['placement_type_code'] ?? 0) === 4 ? 'selected' : '' ?>>רישום לתאגיד</option>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label">מבקר (אופציונלי)</label>
        <input class="form-control" type="number" name="visited_by_user_id" value="<?= e($row['visited_by_user_id'] ?? '') ?>">
      </div>

      <div class="col-12">
        <label class="form-label">סיכום</label>
        <textarea class="form-control" name="summary" rows="2"><?= e($row['summary'] ?? '') ?></textarea>
      </div>

      <div class="col-12">
        <label class="form-label">ממצאים</label>
        <textarea class="form-control" name="findings" rows="3"><?= e($row['findings'] ?? '') ?></textarea>
      </div>

      <div class="col-md-2">
        <label class="form-label">נדרש מעקב</label>
        <select class="form-select" name="followup_required">
          <option value="0" <?= !empty($row['followup_required']) ? '' : 'selected' ?>>לא</option>
          <option value="1" <?= !empty($row['followup_required']) ? 'selected' : '' ?>>כן</option>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label">ביקור הבא</label>
        <input class="form-control" type="date" name="next_visit_due" value="<?= e($row['next_visit_due'] ?? '') ?>">
      </div>

      <div class="col-md-3">
        <label class="form-label">שיבוץ (אופציונלי)</label>
        <input class="form-control" type="number" name="placement_id" value="<?= e($row['placement_id'] ?? '') ?>">
        <div class="form-text">אם ריק — ייגזר אוטומטית משיבוץ פעיל ביום הביקור.</div>
      </div>
    </div>

    <div class="mt-3">
      <button class="btn btn-primary"><i class="bi bi-save"></i> שמירה</button>
      <a class="btn btn-secondary" href="index.php?home_visits&index"><i class="bi bi-arrow-right"></i> חזרה</a>
    </div>
  </form>
</div>
