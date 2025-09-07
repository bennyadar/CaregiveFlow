<?php require __DIR__ . '/../layout/header.php'; ?>
<h1 class="h4 mb-3"><?= $item ? 'עריכת לשכה #' . (int)$item['id'] : 'יצירת לשכה' ?></h1>

<form method="post" class="row g-3" action="index.php?r=agency_settings/<?= $item ? 'update' : 'create' ?>">
  <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
  <div class="col-md-6">
    <label class="form-label">שם לשכה</label>
    <input class="form-control" name="agency_name" value="<?= e($item['agency_name'] ?? '') ?>">
  </div>
  <div class="col-md-4">
    <label class="form-label">ח.פ</label>
    <input class="form-control" name="CorporateNumber" value="<?= e($item['CorporateNumber'] ?? '') ?>">
  </div>
  <div class="col-md-6">
    <label class="form-label">מספר לשכה</label>
    <input class="form-control" name="bureau_number" maxlength="20" value="<?= e($item['bureau_number'] ?? '') ?>">
    <div class="form-text">ספרות בלבד (עד 20 תווים).</div>
  </div>
  <div class="col-md-4">
    <label class="form-label">מס׳ רישיון</label>
    <input class="form-control" name="LicenseNumber" value="<?= e($item['LicenseNumber'] ?? '') ?>">
  </div>
  <div class="col-md-4">
    <label class="form-label">שם בעל הרישיון</label>
    <input class="form-control" name="contact_person" value="<?= e($item['contact_person'] ?? '') ?>">
  </div>  
  <div class="col-md-4">
    <label class="form-label">ת.ז בעל הרישיון</label>
    <input class="form-control" name="OwnerID" maxlength="9" value="<?= e($item['OwnerID'] ?? '') ?>">
  </div>  
  <div class="col-md-4">
    <label class="form-label">טלפון משרד</label>
    <input class="form-control" name="phone" value="<?= e($item['phone'] ?? '') ?>">
  </div>
  <div class="col-md-4">
    <label class="form-label">טלפון נייד</label>
    <input class="form-control" name="CellNumber" value="<?= e($item['CellNumber'] ?? '') ?>">
  </div>
  <div class="col-md-4">
    <label class="form-label">אימייל</label>
    <input class="form-control" type="email" name="email" value="<?= e($item['email'] ?? '') ?>">
  </div>  
  <div class="col-md-8">
    <label class="form-label">כתובת</label>
    <input class="form-control" name="Address" value="<?= e($item['Address'] ?? '') ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">מיקוד</label>
    <input class="form-control" name="zipcode" value="<?= e($item['zipcode'] ?? '') ?>">
  </div>
  <div class="col-12">
    <label class="form-label">הערות</label>
    <textarea class="form-control" name="notes" rows="3"><?= e($item['notes'] ?? '') ?></textarea>
  </div>
  <div class="mt-3">
    <button class="btn btn-primary"><?= $item ? 'עדכון' : 'שמירה' ?></button>
    <a class="btn btn-secondary" href="index.php?r=agency_settings/index">ביטול</a>
  </div>
</form>
<?php require __DIR__ . '/../layout/footer.php'; ?>
