<?php require __DIR__ . '/../layout/header.php'; ?>
<h1 class="h4 mb-3"><?= isset($item['id']) ? 'עריכת מעסיק #' . (int)$item['id'] : 'מעסיק חדש' ?></h1>
<form method="post" class="row g-3">
  <div class="col-md-3">
    <label class="form-label">סוג תעודה *</label>
    <select name="id_type_code" class="form-select" required>
      <option value="">-- בחר/י --</option>
      <?php foreach ($id_types as $t): ?>
        <option value="<?= (int)$t['id_type_code'] ?>" <?= (!empty($item['id_type_code']) && (int)$item['id_type_code']===(int)$t['id_type_code'])?'selected':'' ?>>
          <?= e($t['name_he']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">מספר תעודה *</label>
    <input type="text" name="id_number" class="form-control" required value="<?= e($item['id_number'] ?? '') ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">שם פרטי *</label>
    <input type="text" name="first_name" class="form-control" required value="<?= e($item['first_name'] ?? '') ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">שם משפחה *</label>
    <input type="text" name="last_name" class="form-control" required value="<?= e($item['last_name'] ?? '') ?>">
  </div>

  <div class="col-md-2">
    <label class="form-label">מגדר</label>
    <select name="gender_code" class="form-select">
      <option value="">-- בחר/י --</option>
      <?php if (!empty($genders)) foreach ($genders as $g): ?>
        <option value="<?= (int)$g['gender_code'] ?>"
          <?= (!empty($item['gender_code']) && (int)$item['gender_code']===(int)$g['gender_code'])?'selected':'' ?>>
          <?= e($g['name_he']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-1">
    <label class="form-label">שנת לידה</label>
    <input type="number" name="birth_year" class="form-control" min="1900" max="<?= date('Y') ?>" value="<?= e($item['birth_year'] ?? '') ?>">
  </div>

  <!-- ================= טלפון ישראלי ================= -->
    <div class="col-md-1">
      <label class="form-label">קידומת</label>
      <input type="text" name="phone_prefix_il" class="form-control" maxlength="4" value="<?= e($item['phone_prefix_il'] ?? '') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">מס' טלפון</label>
      <input type="text" name="phone_number_il" class="form-control" maxlength="10" value="<?= e($item['phone_number_il'] ?? '') ?>">
    </div>


  <div class="col-md-5">
    <label class="form-label">אימייל</label>
    <input type="email" name="email" class="form-control" value="<?= e($item['email'] ?? '') ?>">
  </div>

  <div class="col-md-3">
    <label class="form-label">יישוב</label>
    <select name="city_code" class="form-select" data-city-select>
      <option value="">-- בחר/י --</option>
      <?php foreach ($cities as $c): ?>
        <option value="<?= (int)$c['city_code'] ?>" <?= (!empty($item['city_code']) && (int)$item['city_code']===(int)$c['city_code'])?'selected':'' ?>>
          <?= e($c['name_he']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">רחוב</label>
    <select name="street_code" class="form-select" data-street-select>
      <option value="">-- בחר/י רחוב --</option>
      <?php foreach ($streets as $s): ?>
        <option value="<?= (int)$s['street_code'] ?>" <?= (!empty($item['street_code']) && (int)$item['street_code']===(int)$s['street_code'])?'selected':'' ?>>
          <?= e($s['street_name_he']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2">
    <label class="form-label">מס' בית</label>
    <input type="text" name="house_no" class="form-control" value="<?= e($item['house_no'] ?? '') ?>">
  </div>
  <div class="col-md-2">
    <label class="form-label">דירה</label>
    <input type="text" name="apartment" class="form-control" value="<?= e($item['apartment'] ?? '') ?>">
  </div>
  <div class="col-md-2">
    <label class="form-label">מיקוד</label>
    <input type="text" name="zipcode" class="form-control" value="<?= e($item['zipcode'] ?? '') ?>">
  </div>

  <!-- ===== Passports quick-add (Employer) ===== -->
  <div class="card mt-4">
    <div class="card-header fw-semibold">דרכון (הוספה מהירה)</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">מס׳ דרכון</label>
          <input type="text" name="rp_passport_number" class="form-control" maxlength="20" value="">
          <div class="form-text">השאר ריק אם אינך רוצה להוסיף דרכון עכשיו.</div>
        </div>
        <div class="col-md-3">
          <label class="form-label">ארץ מנפיקה (MoI)</label>
          <input type="number" name="rp_issuing_country_code" class="form-control" value="">
        </div>
        <div class="col-md-2">
          <label class="form-label">תאריך הנפקה</label>
          <input type="date" name="rp_issue_date" class="form-control" value="">
        </div>
        <div class="col-md-2">
          <label class="form-label">תוקף</label>
          <input type="date" name="rp_expiry_date" class="form-control" value="">
        </div>
        <div class="col-md-1 form-check mt-4">
          <input class="form-check-input" type="checkbox" name="rp_is_primary" id="rp_is_primary"
                <?= empty($item['id']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="rp_is_primary">ראשי</label>
        </div>
      </div>
      <div class="form-text mt-2">
        לעדכון/ניהול דרכונים קיימים עבור המעסיק: 
        <a href="index.php?r=employer_passports/index&employer_id=<?= isset($item['id']) ? (int)$item['id'] : 0 ?>" target="_blank">ניהול דרכונים</a>
      </div>
    </div>
  </div>
  <!-- ===== /Passports quick-add ===== -->

  <div class="col-12">
    <label class="form-label">הערות</label>
    <textarea name="notes" class="form-control" rows="3"><?= e($item['notes'] ?? '') ?></textarea>
  </div>

  <div class="col-12 d-flex gap-2">
    <button class="btn btn-primary">שמירה</button>
    <a class="btn btn-outline-secondary" href="index.php?r=employers/index">חזרה</a>
  </div>
</form>
<?php require __DIR__ . '/../layout/footer.php'; ?>
