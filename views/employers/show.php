<?php require __DIR__ . '/../layout/header.php'; ?>
<?php
// Maps אופציונליים
$countriesMap = isset($countries) ? array_column($countries, 'name_he', 'country_code') : [];
$gendersMap   = isset($genders)   ? array_column($genders,   'name_he', 'gender_code')   : [];
$countryLabel = function($code) use ($countriesMap) {
    $code = (string)($code ?? '');
    $name = ($code !== '' && isset($countriesMap[$code])) ? $countriesMap[$code] : null;
    return $name ? e($name) . ' (' . e($code) . ')' : e($code);
};
$genderLabel = function($code) use ($gendersMap) {
    $code = (string)($code ?? '');
    $name = ($code !== '' && isset($gendersMap[$code])) ? $gendersMap[$code] : null;
    return $name ? e($name) . ' (' . e($code) . ')' : e($code);
};
?>

<div class="container my-4">

  <!-- כותרת + קישורים -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">פרטי מעסיק</h1>
    <div class="d-flex gap-2">
      <a class="btn btn-sm btn-outline-secondary" href="index.php?r=employer_permits/index&employer_id=<?= (int)$item['id'] ?>">היתרי העסקה</a>
      <a class="btn btn-sm btn-outline-secondary" href="index.php?r=employer_fees/index&employer_id=<?= (int)$item['id'] ?>">דמי תאגיד</a>
    </div>
  </div>

  <!-- ===== דרכונים ===== -->
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span class="fw-semibold">דרכונים</span>
      <div class="d-flex gap-2">
        <a class="btn btn-sm btn-success"
          href="index.php?r=employer_passports/create&employer_id=<?= (int)$item['id'] ?>">+ הוסף דרכון</a>
        <a class="btn btn-sm btn-outline-primary"
          href="index.php?r=employer_passports/index&employer_id=<?= (int)$item['id'] ?>">ניהול דרכונים</a>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-striped mb-0">
          <thead>
            <tr>
              <th>#</th><th>מס׳ דרכון</th><th>ארץ מנפיקה (MoI)</th><th>הנפקה</th><th>תוקף</th><th>ראשי</th><th class="text-end">פעולות</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!empty($employer_passports)): foreach ($employer_passports as $p): ?>
            <tr>
              <td><?= (int)$p['id'] ?></td>
              <td><?= e($p['passport_number']) ?></td>
              <td><?= e($p['issuing_country_code']) ?></td>
              <td><?= e($p['issue_date']) ?></td>
              <td><?= e($p['expiry_date']) ?></td>
              <td><?= !empty($p['is_primary']) ? '✔' : '' ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-secondary" href="index.php?r=employer_passports/edit&id=<?= (int)$p['id'] ?>">עריכה</a>
                <form method="post" action="index.php?r=employer_passports/destroy" class="d-inline" onsubmit="return confirm('למחוק את הדרכון?')">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger">מחיקה</button>
                </form>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="7" class="text-center text-muted py-3">אין דרכונים למעסיק זה</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ===== מזהים ושמות ===== -->
  <div class="card mb-3 shadow-sm">
    <div class="card-header fw-semibold">מזהים ושמות</div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <tbody>
          <tr>
            <th class="w-25">סוג מזהה (קוד)</th>
            <td><?= e($item['id_type_code'] ?? '') ?></td>
            <th class="w-25">מס' מזהה</th>
            <td><?= e($item['id_number'] ?? '') ?></td>
          </tr>
          <tr>
            <th>דרכון (אם קיים)</th>
            <td><?= e($item['passport_number'] ?? '') ?></td>
            <th>שם משפחה</th>
            <td><?= e($item['last_name'] ?? '') ?></td>
          </tr>
          <tr>
            <th>שם פרטי</th>
            <td><?= e($item['first_name'] ?? '') ?></td>
            <th>מגדר</th>
            <td><?= $genderLabel($item['gender_code'] ?? '') ?></td>
          </tr>
          <tr>
            <th>שנת לידה</th>
            <td><?= e($item['birth_year'] ?? '') ?></td>
            <th>דוא"ל</th>
            <td><?= e($item['email'] ?? '') ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ===== כתובת בישראל ===== -->
  <div class="card mb-3 shadow-sm">
    <div class="card-header fw-semibold">כתובת בישראל</div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <tbody>
          <tr>
            <th class="w-25">קוד יישוב</th>
            <td><?= e($item['city_code'] ?? '') ?></td>
            <th class="w-25">שם רחוב (עברית)</th>
            <td><?= e($item['street_name_he'] ?? '') ?></td>
          </tr>
          <tr>
            <th>מס' בית</th>
            <td><?= e($item['house_no'] ?? '') ?></td>
            <th>מיקוד</th>
            <td><?= e($item['zipcode'] ?? '') ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ===== טלפונים ===== -->
  <div class="card mb-3 shadow-sm">
    <div class="card-header fw-semibold">טלפונים</div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <tbody>
          <tr>
            <th class="w-25">טלפון (IL)</th>
            <td>
              <?= e($item['phone_prefix_il'] ?? '') ?><?= $item['phone_prefix_il'] ? '-' : '' ?><?= e($item['phone_number_il'] ?? '') ?>
            </td>
            <th class="w-25">טלפון נוסף</th>
            <td><?= e($item['phone'] ?? '') ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ===== BAFI: סמל ארץ של מעסיק זר (715–717) ===== -->
  <div class="card mb-4 shadow-sm">
    <div class="card-header fw-semibold">BAFI — סמל ארץ (מעסיק זר)</div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <tbody>
          <tr>
            <th class="w-25">סמל ארץ (MoI)</th>
            <td><?= $countryLabel($item['foreign_country_code'] ?? '') ?></td>
            <th class="w-25"></th>
            <td></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</div>



<h1 class="h4 mb-3">פרטי מעסיק #<?= (int)$item['id'] ?></h1>
<div class="card">
  <div class="card-body">
    <dl class="row mb-0">
      <dt class="col-sm-2">שם</dt><dd class="col-sm-10"><?= e($item['last_name'] . ' ' . $item['first_name']) ?></dd>
      <dt class="col-sm-2">ת"ז</dt><dd class="col-sm-10"><?= e($item['id_number']) ?></dd>
      <dt class="col-sm-2">טלפון</dt><dd class="col-sm-10"><?= e($item['phone']) ?></dd>
      <dt class="col-sm-2">אימייל</dt><dd class="col-sm-10"><?= e($item['email']) ?></dd>
      <dt class="col-sm-2">הערות</dt><dd class="col-sm-10"><?= nl2br(e($item['notes'] ?? '')) ?></dd>
    </dl>
  </div>
</div>
<p class="mt-3">
  <a class="btn btn-primary" href="index.php?r=employers/edit&id=<?= (int)$item['id'] ?>">עריכה</a>
  <a class="btn btn-outline-secondary" href="index.php?r=employers/index">חזרה</a>
</p>

<?php require __DIR__ . '/../layout/footer.php'; ?>
