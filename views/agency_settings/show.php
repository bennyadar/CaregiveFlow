<?php require __DIR__ . '/../layout/header.php'; ?>
<h1 class="h4 mb-3">פרטי לשכה #<?= (int)$item['id'] ?></h1>

<div class="card">
  <div class="card-body">
    <dl class="row mb-0">
      <dt class="col-sm-2">שם לשכה</dt><dd class="col-sm-10"><?= e($item['agency_name']) ?></dd>
      <dt class="col-sm-2">ח.פ</dt><dd class="col-sm-10"><?= e($item['CorporateNumber'] ?? '') ?></dd>      
      <dt class="col-sm-2">מס׳ לשכה</dt><dd class="col-sm-10"><?= e($item['bureau_number']) ?></dd>
      <dt class="col-sm-2">מס׳ רישיון</dt><dd class="col-sm-10"><?= e($item['LicenseNumber'] ?? '') ?></dd>
      <dt class="col-sm-2">שם בעל הרישיון</dt><dd class="col-sm-10"><?= e($item['contact_person']) ?></dd>
      <dt class="col-sm-2">ת.ז בעל הרישיון</dt><dd class="col-sm-10"><?= e($item['OwnerID'] ?? '') ?></dd>
      <dt class="col-sm-2">טלפון משרד</dt><dd class="col-sm-10"><?= e($item['phone']) ?></dd>
      <dt class="col-sm-2">נייד</dt><dd class="col-sm-10"><?= e($item['CellNumber'] ?? '') ?></dd>
      <dt class="col-sm-2">אימייל</dt><dd class="col-sm-10"><?= e($item['email']) ?></dd>
      <dt class="col-sm-2">כתובת</dt><dd class="col-sm-10"><?= e($item['Address'] ?? '') ?></dd>
      <!-- <dt class="col-sm-2">כתובת</dt>
      <dd class="col-sm-10">
        קוד יישוב <?//= e($item['city_code']) ?>,
        קוד רחוב <?//= e($item['street_code']) ?>,
        מס׳ בית <?//= e($item['house_no']) ?>,
        מיקוד <?//= e($item['zipcode']) ?>
      </dd> -->
      <dt class="col-sm-2">עודכן</dt><dd class="col-sm-10"><?= e($item['updated_at']) ?></dd>
      <dt class="col-sm-2">הערות</dt><dd class="col-sm-10"><?= nl2br(e($item['notes'])) ?></dd>
    </dl>
  </div>
</div>
<p class="mt-3">
  <a class="btn btn-primary" href="index.php?r=agency_settings/edit&id=<?= (int)$item['id'] ?>">עריכה</a>
  <a class="btn btn-secondary" href="index.php?r=agency_settings/index">חזרה</a>
</p>
<?php require __DIR__ . '/../layout/footer.php'; ?>
