<?php
/** שימוש:
 * include __DIR__ . '/../files/_uploader.php';
 * יש להגדיר לפני ההכללה את המשתנים:
 *   $module (string) – שם המודול, לדוגמה 'employees' / 'employers' / 'visas' וכו'
 *   $record_id (int) – מזהה הרשומה בטבלת המודול
 *   $fileTypes (array) – מתקבל מ- (new FileType($db))->allActive()
 */
?>
<div class="card mb-3" dir="rtl">
  <div class="card-header">הוספת קובץ</div>
  <div class="card-body">
    <form method="post" action="index.php?r=files/upload" enctype="multipart/form-data">
      <input type="hidden" name="module" value="<?= e($module) ?>">
      <input type="hidden" name="record_id" value="<?= (int)$record_id ?>">

      <div class="row g-2">
        <div class="col-md-3">
          <label class="form-label">סוג קובץ</label>
          <select name="file_type_code" class="form-select" required>
            <option value="">בחר...</option>
            <?php foreach ($fileTypes as $t): ?>
              <option value="<?= e($t['file_type_code']) ?>"><?= e($t['name_he']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-5">
          <label class="form-label">קובץ</label>
          <input type="file" name="attachment" class="form-control" required>
        </div>

        <div class="col-md-3">
          <label class="form-label">הערות (לא חובה)</label>
          <input type="text" name="notes" class="form-control" placeholder="לדוגמה: סריקת דרכון בצבע">
        </div>

        <div class="col-md-1 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100">העלה</button>
        </div>
      </div>
    </form>
  </div>
</div>
