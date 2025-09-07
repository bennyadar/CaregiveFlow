<?php /** @var array $employees */ ?>
<?php require __DIR__ . '/../layout/header.php'; ?>
<div class="container my-4">
  <h1 class="h4 mb-3">ייצוא מת"ש לפי עובד</h1>

  <?php if (!empty($_SESSION['flash'])): ?>
    <div class="alert alert-warning"><?php echo e($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
  <?php endif; ?>

  <form method="post" action="?r=exports/run" class="row g-3">
      
    <!-- עובד לבחירה + Preselect -->
    <div class="col-12 col-md-6">
      <label class="form-label">עובד</label>
      <select name="employee_id" class="form-select" required>
        <option value="">בחר...</option>
        <?php foreach ($employees as $emp):
              $id = (int)$emp['id'];
              $selected = (!empty($prefill['employee_id']) && $prefill['employee_id'] === $id) ? 'selected' : '';
        ?>
          <option value="<?= $id ?>" <?= $selected ?>>
            <?= e(($emp['id_number'] ?? '') . ' — ' . ($emp['last_name'] ?? '') . ' ' . ($emp['first_name'] ?? '')) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- שורת פרמטרים בסיסיים: סוג קובץ / מס’ לשכה / ענף -->
    <div class="col-6 col-md-2">
      <label class="form-label">סוג קובץ</label>
      <input class="form-control" name="file_type" value="<?= e($prefill['file_type']) ?>" maxlength="2">
    </div>
    <div class="col-6 col-md-4">
      <label class="form-label">מספר לשכה</label>
      <input class="form-control" name="bureau_number"
            value="<?= e($prefill['bureau_number']) ?>" maxlength="10"
            placeholder="אם ריק – יילקח מ־agency_settings">
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label">ענף</label>
      <input class="form-control" name="sector_code" value="<?= e($prefill['sector_code']) ?>" maxlength="2">
    </div>

    <!-- סוג רשומה -->
    <div class="col-6 col-md-4">
      <label class="form-label">סוג רשומה</label>
      <select class="form-select" name="record_type" required>
        <option value="">בחר...</option>
        <?php foreach ($record_type_codes as $rtc):
              $val = (string)$rtc['record_type_code'];
              $sel = ($prefill['record_type'] !== '' && $prefill['record_type'] === $val) ? 'selected' : '';
        ?>
          <option value="<?= e($val) ?>" <?= $sel ?>>
            <?= e($val.' – '.($rtc['name_he'] ?? '')) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- סוג מנה -->
    <div class="col-6 col-md-4">
      <label class="form-label">סוג מנה</label>
      <select class="form-select" name="mana_type" required>
        <option value="">בחר...</option>
        <?php foreach ($mana_type_codes as $mtc):
              $val = (string)$mtc['mana_type_code'];
              $sel = ($prefill['mana_type'] !== '' && $prefill['mana_type'] === $val) ? 'selected' : '';
        ?>
          <option value="<?= e($val) ?>" <?= $sel ?>>
            <?= e($val.' – '.($mtc['name_he'] ?? '')) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- סטטוס / סיבת סיום -->
    <div class="col-12 col-md-8">
      <label class="form-label">סטטוס / קוד סיבת סיום</label>
      <select class="form-select" name="status_code" required>
        <option value="00" <?= ($prefill['status_code']==='00' ? 'selected' : '') ?>>00 – עובד חדש / רישום ראשוני / הברקה / עבר לשכה</option>
        <option value="03" <?= ($prefill['status_code']==='03' ? 'selected' : '') ?>>03 – שינוי מעסיק באותה לשכה</option>
        <?php if (!empty($end_reasons)): ?>
          <optgroup label="סיבות סיום (אחר)">
            <?php foreach ($end_reasons as $er):
                  $val = (string)$er['end_reason_code'];
                  $sel = ($prefill['status_code'] === $val) ? 'selected' : '';
            ?>
              <option value="<?= e($val) ?>" <?= $sel ?>>
                <?= e($val.' – '.($er['name_he'] ?? '')) ?>
              </option>
            <?php endforeach; ?>
          </optgroup>
        <?php endif; ?>
      </select>
      <div class="small text-muted mt-1">
        אם תשאיר את <strong>מספר הלשכה</strong> ריק – השרת ישלים מ־<code>agency_settings.id=1</code> (אם קיים).
      </div>
    </div>

    <!-- תאריך שינוי סטטוס -->
    <div class="col-12 col-md-4">
      <label class="form-label">תאריך שינוי סטטוס</label>
      <input type="date" class="form-control" name="status_date"
            value="<?= e($prefill['status_date']) ?>">
    </div>

    <!-- שמור בהיסטוריה -->
    <div class="col-12">
      <input type="hidden" name="save_history" value="0">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" id="save_history"
              name="save_history" value="1" <?= (!empty($prefill['save_history']) ? 'checked' : '') ?>>
        <label class="form-check-label" for="save_history">שמור בהיסטוריית ייצוא</label>
      </div>
    </div>

    <!-- פעולות -->
    <div class="col-12">
      <button type="submit" class="btn btn-primary">ייצא והורד</button>
      <a href="?r=exports/history" class="btn btn-outline-secondary">היסטוריית ייצוא</a>
    </div>
</form>

</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
