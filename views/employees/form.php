<?php require __DIR__ . '/../layout/header.php'; ?>
<h1 class="h4 mb-3"><?= isset($item['id']) ? 'עריכת עובד #' . (int)$item['id'] : 'עובד חדש' ?></h1>
<form method="post" class="row g-3">
  <h2 class="h6">פרטים כלליים ופרטי עובד</h2>
  <div class="col-md-2">
    <label class="form-label">שם פרטי (לועזי) *</label>
    <input type="text" name="first_name" class="form-control" required value="<?= e($item['first_name'] ?? '') ?>">
  </div>
  <div class="col-md-2">
    <label class="form-label">שם משפחה (לועזי) *</label>
    <input type="text" name="last_name" class="form-control" required value="<?= e($item['last_name'] ?? '') ?>">
  </div>
  <div class="col-md-2">
    <label class="form-label">שם פרטי (עברית)</label>
    <input type="text" name="first_name_he" class="form-control" value="<?= e($item['first_name_he'] ?? '') ?>">
  </div>  
  <div class="col-md-2">
    <label class="form-label">שם משפחה (עברית)</label>
    <input type="text" name="last_name_he" class="form-control" value="<?= e($item['last_name_he'] ?? '') ?>">
  </div>
    <div class="col-md-2">
    <label class="form-label">שם האב (לועזי)</label>
    <input type="text" name="father_name_en" class="form-control" value="<?= e($item['father_name_en'] ?? '') ?>">
  </div>  
    <!-- שם אם (לועזי) -->
  <div class="col-md-2">
    <label class="form-label">שם אם (לועזי)</label>
    <input type="text" name="mother_name_en" maxlength="20" class="form-control"
          value="<?= e($item['mother_name_en'] ?? '') ?>">
  </div>
  <div class="col-md-2">
    <label class="form-label">סמל ארץ / אזרחות</label>
    <select name="country_of_citizenship" class="form-select">
      <option value="">-- בחר/י --</option>
      <?php foreach ($countries as $c): ?>
        <option value="<?= (int)$c['country_code'] ?>" <?= (!empty($item['country_of_citizenship']) && (int)$item['country_of_citizenship']===(int)$c['country_code'])?'selected':'' ?>>
          <?= e($c['country_code']). ' - ' .e($c['name_he']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div> 

  <div class="col-md-2">
    <label class="form-label">מין</label>
    <select name="gender_code" class="form-select">
      <option value="">-- בחר/י --</option>
      <?php foreach ($genders as $g): ?>
        <option value="<?= (int)$g['gender_code'] ?>" <?= (!empty($item['gender_code']) && (int)$item['gender_code']===(int)$g['gender_code'])?'selected':'' ?>>
          <?= e($g['name_he']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-2">
    <label class="form-label">מצב משפחתי</label>
    <select name="marital_status_code" class="form-select">
      <option value="">-- בחר/י --</option>
      <?php foreach ($maritals as $m): ?>
        <option value="<?= (int)$m['marital_status_code'] ?>" <?= (!empty($item['marital_status_code']) && (int)$item['marital_status_code']===(int)$m['marital_status_code'])?'selected':'' ?>>
          <?= e($m['name_he']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>



  <!-- שם בן/בת זוג -->
  <div class="col-md-2">
    <label class="form-label">שם בן/בת זוג</label>
    <input type="text" name="spouse_name_en" maxlength="20" class="form-control"
          value="<?= e($item['spouse_name_en'] ?? '') ?>">
  </div>

  <!-- בן/בת הזוג נמצא בישראל (SELECT) -->
  <div class="col-md-2">
    <label class="form-label">בן/בת הזוג נמצא בישראל</label>
    <select name="spouse_in_israel" class="form-select">
      <option value=""  <?= !isset($item['spouse_in_israel']) ? 'selected' : '' ?>>לא ידוע</option>
      <option value="1" <?= (isset($item['spouse_in_israel']) && $item['spouse_in_israel']=='1')?'selected':'' ?>>כן</option>
      <option value="0" <?= (isset($item['spouse_in_israel']) && $item['spouse_in_israel']=='0')?'selected':'' ?>>לא</option>
    </select>
  </div>

  <!-- שם נציג בחו"ל -->
  <div class="col-md-2">
    <label class="form-label">שם נציג בחו"ל</label>
    <input type="text" name="representative_abroad_name" maxlength="20" class="form-control"
          value="<?= e($item['representative_abroad_name'] ?? '') ?>">
  </div>

  <div class="col-md-2">
    <label class="form-label">תאריך לידה</label>
    <input type="date" name="birth_date" class="form-control" value="<?= e($item['birth_date'] ?? '') ?>">
  </div>

  <!-- ================= טלפון ישראלי ================= -->
  <div class="col-md-2">
    <label class="form-label">קידומת טלפון (IL)</label>
    <input type="text" name="phone_prefix_il" class="form-control" maxlength="3" value="<?= e($item['phone_prefix_il'] ?? '') ?>">
  </div>
  <div class="col-md-2">
    <label class="form-label">מס' טלפון (IL)</label>
    <input type="text" name="phone_number_il" class="form-control" maxlength="7" value="<?= e($item['phone_number_il'] ?? '') ?>">
  </div>

  <div class="col-md-4">
    <label class="form-label">אימייל</label>
    <input type="email" name="email" class="form-control" value="<?= e($item['email'] ?? '') ?>">
  </div>

  <div class="col-md-2">
    <label class="form-label">תאריך כניסה לישראל</label>
    <input type="date" name="entry_date" class="form-control" value="<?= e($item['entry_date'] ?? '') ?>">
  </div>

  <hr class="mt-4">
  <h2 class="h6">פרטי מגורים של העובד</h2>
  <div class="row g-3">
    <div class="col-md-2">
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
    <div class="col-md-2">
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
  </div>

  <hr class="mt-4">
  <!-- ===== Passports quick-add (Employee) ===== -->
  <h2 class="h6">דרכון (הוספה מהירה)</h2>
    <div class="row g-3">
      <div class="col-md-2">
        <label class="form-label">מס׳ דרכון</label>
        <input type="text" name="pp_passport_number" class="form-control" maxlength="20" value="">
        <div class="form-text">השאר ריק אם אינך רוצה להוסיף דרכון עכשיו.</div>
      </div>
      <div class="col-md-2">
          <label class="form-label">סוג דרכון</label>
          <select name="pp_passport_type_code" class="form-select">
              <option value="">— בחר —</option>
              <?php foreach ($passport_type_codes as $type_code): ?>
                <option value="<?= (int)$typ_code['passport_type_code'] ?>">
                  <?= e($type_code['name_he']) ?>
              </option>
              <?php endforeach; ?>
          </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">ארץ מנפיקה</label>
        <select name="pp_issuing_country_code" class="form-select">
          <option value="">-- בחר/י --</option>
          <?php foreach ($countries as $c): ?>
            <option value="<?= (int)$c['country_code'] ?>">
              <?= e($c['country_code']). ' - ' .e($c['name_he']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">תאריך הנפקה</label>
        <input type="date" name="pp_issue_date" class="form-control" value="">
      </div>
      <div class="col-md-2">
        <label class="form-label">תוקף</label>
        <input type="date" name="pp_expiry_date" class="form-control" value="">
      </div>
      <div class="col-md-2 form-check mt-5">
        <input class="form-check-input" type="checkbox" name="pp_is_primary" id="pp_is_primary"
              <?= empty($item['id']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="pp_is_primary">ראשי</label>
      </div>
    </div>
    <div class="form-text mt-2">
      לעדכון/ניהול דרכונים קיימים עבור העובד: 
      <a href="index.php?r=passports/index&employee_id=<?= isset($item['id']) ? (int)$item['id'] : 0 ?>" target="_blank">ניהול דרכונים</a>
    </div>
  </div>
  <!-- ===== /Passports quick-add ===== -->

  <hr class="mt-4">
  <!-- ביטוח רפואי - תאריך הפקה -->
  <div class="col-md-2">
    <label class="form-label">ביטוח רפואי - תאריך הפקה</label>
    <input type="date" name="health_ins_issue_date" class="form-control"
          value="<?= e($item['health_ins_issue_date'] ?? '') ?>">
  </div>

  <!-- ביטוח רפואי - תאריך פקיעה -->
  <div class="col-md-2">
    <label class="form-label">ביטוח רפואי - תאריך פקיעה</label>
    <input type="date" name="health_ins_expiry" class="form-control"
          value="<?= e($item['health_ins_expiry'] ?? '') ?>">
  </div>

  <!-- מת"ש - מספר מנה -->
  <div class="col-md-2">
    <label class="form-label">מת"ש - מספר מנה</label>
    <input type="text" name="metash_mana_number" maxlength="20" class="form-control"
          value="<?= e($item['metash_mana_number'] ?? '') ?>">
  </div>

  <!-- מת"ש - תאריך רישום -->
  <div class="col-md-2">
    <label class="form-label">מת"ש - תאריך רישום</label>
    <input type="date" name="metash_registration_date" class="form-control"
          value="<?= e($item['metash_registration_date'] ?? '') ?>">
  </div>



  <div class="col-md-2">
    <label class="form-label">טלפון</label>
    <input type="text" name="phone" class="form-control" value="<?= e($item['phone'] ?? '') ?>">
  </div>
  <div class="col-md-2">
    <label class="form-label">טלפון נוסף</label>
    <input type="text" name="phone_alt" class="form-control" value="<?= e($item['phone_alt'] ?? '') ?>">
  </div>




  <div class="col-md-2">
    <label class="form-label">סוג ויזה</label>
    <input type="text" name="visa_type" class="form-control" value="<?= e($item['visa_type'] ?? '') ?>">
  </div>
  <div class="col-md-2">
    <label class="form-label">תוקף ויזה</label>
    <input type="date" name="visa_expiry" class="form-control" value="<?= e($item['visa_expiry'] ?? '') ?>">
  </div>
  <div class="col-md-2">
    <label class="form-label">מס' היתר עבודה</label>
    <input type="text" name="work_permit_number" class="form-control" value="<?= e($item['work_permit_number'] ?? '') ?>">
  </div>
  <div class="col-md-2">
    <label class="form-label">תוקף היתר עבודה</label>
    <input type="date" name="work_permit_expiry" class="form-control" value="<?= e($item['work_permit_expiry'] ?? '') ?>">
  </div>
  <div class="col-md-2 d-flex align-items-end">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= !empty($item['is_active']) ? 'checked' : '' ?>>
      <label class="form-check-label" for="is_active">פעיל</label>
    </div>
  </div>

  <!-- ================= BAFI: שמות בעברית ומידע נוסף ================= -->
  <hr class="mt-4">
  <h2 class="h6">BAFI — שמות בעברית ומידע נוסף</h2>
  <div class="row g-3">

    <div class="col-md-2">
      <label class="form-label">קוד ארץ (MoI)</label>
      <select name="country_symbol_moi" class="form-select">
        <option value="">-- בחר/י --</option>
        <?php if (!empty($countries)) foreach ($countries as $c): ?>
          <option value="<?= (int)$c['country_code'] ?>"
            <?= (!empty($item['country_symbol_moi']) && (int)$item['country_symbol_moi']===(int)$c['country_code'])?'selected':'' ?>>
            <?= e($c['name_he']) ?> (<?= (int)$c['country_code'] ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>



  <!-- ================= כתובת עובד בחו״ל (355–430) ================= -->
  <hr class="mt-4">
  <h2 class="h6">כתובת עובד בחו״ל (BAFI 355–430)</h2>
  <div class="row g-3">
    <div class="col-md-2">
      <label class="form-label">עיר (חו״ל)</label>
      <input type="text" name="abroad_city" class="form-control" value="<?= e($item['abroad_city'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">רחוב (חו״ל)</label>
      <input type="text" name="abroad_street" class="form-control" value="<?= e($item['abroad_street'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">מס' בית (חו״ל)</label>
      <input type="text" name="abroad_house_no" class="form-control" value="<?= e($item['abroad_house_no'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">מיקוד (חו״ל)</label>
      <input type="text" name="abroad_postal_code" class="form-control" value="<?= e($item['abroad_postal_code'] ?? '') ?>">
    </div>
  </div>

  <!-- ================= פרטי בנק בחו״ל (431–604) + מוטב (645–704) ================= -->
  <hr class="mt-4">
  <h2 class="h6">פרטי בנק בחו״ל + מוטב (BAFI 431–704)</h2>
  <div class="row g-3">
    <div class="col-md-2">
      <label class="form-label">קוד ארץ בנק (MoI)</label>
      <select name="bank_foreign_country_code" class="form-select">
        <option value="">-- בחר/י --</option>
        <?php if (!empty($countries)) foreach ($countries as $c): ?>
          <option value="<?= (int)$c['country_code'] ?>"
            <?= (!empty($item['bank_foreign_country_code']) && (int)$item['bank_foreign_country_code']===(int)$c['country_code'])?'selected':'' ?>>
            <?= e($c['name_he']) ?> (<?= (int)$c['country_code'] ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">עיר בנק</label>
      <input type="text" name="bank_city_foreign" class="form-control" value="<?= e($item['bank_city_foreign'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">רחוב בנק</label>
      <input type="text" name="bank_street_foreign" class="form-control" value="<?= e($item['bank_street_foreign'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">מס' בית (בנק)</label>
      <input type="text" name="bank_house_no_foreign" class="form-control" value="<?= e($item['bank_house_no_foreign'] ?? '') ?>">
    </div>

    <div class="col-md-2">
      <label class="form-label">סמל בנק</label>
      <input type="text" name="bank_code_foreign" class="form-control" value="<?= e($item['bank_code_foreign'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">שם בנק</label>
      <input type="text" name="bank_name_foreign" class="form-control" value="<?= e($item['bank_name_foreign'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">סמל סניף</label>
      <input type="text" name="bank_branch_code_foreign" class="form-control" value="<?= e($item['bank_branch_code_foreign'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">שם סניף</label>
      <input type="text" name="bank_branch_name_foreign" class="form-control" value="<?= e($item['bank_branch_name_foreign'] ?? '') ?>">
    </div>

    <div class="col-md-2">
      <label class="form-label">SWIFT</label>
      <input type="text" name="bank_swift" class="form-control" value="<?= e($item['bank_swift'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">IBAN</label>
      <input type="text" name="bank_iban" class="form-control" value="<?= e($item['bank_iban'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">מס' חשבון (אם אין IBAN)</label>
      <input type="text" name="bank_account" class="form-control" value="<?= e($item['bank_account'] ?? '') ?>">
    </div>

    <div class="col-md-2">
      <label class="form-label">מוטב — שם משפחה</label>
      <input type="text" name="beneficiary_last_name" class="form-control" value="<?= e($item['beneficiary_last_name'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">מוטב — שם פרטי</label>
      <input type="text" name="beneficiary_first_name" class="form-control" value="<?= e($item['beneficiary_first_name'] ?? '') ?>">
    </div>
  </div>

  <!-- ================= BAFI: היתר ================= -->
  <hr class="mt-4">
  <h2 class="h6">BAFI — מספר היתר</h2>
  <div class="row g-3">
    <div class="col-md-2">
      <label class="form-label">מספר היתר (BAFI)</label>
      <input type="text" name="permit_number_bafi" class="form-control" maxlength="15" value="<?= e($item['permit_number_bafi'] ?? '') ?>">
    </div>
  </div>



  <div class="col-12">
    <label class="form-label">הערות</label>
    <textarea name="notes" class="form-control" rows="3"><?= e($item['notes'] ?? '') ?></textarea>
  </div>

  <div class="col-12 d-flex gap-2">
    <button class="btn btn-primary">שמירה</button>
    <a class="btn btn-outline-secondary" href="index.php?r=employees/index">חזרה</a>
  </div>
</form>
<?php require __DIR__ . '/../layout/footer.php'; ?>
