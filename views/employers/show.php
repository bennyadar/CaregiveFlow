<?php require __DIR__ . '/../layout/header.php'; ?>
<?php
// Maps אופציונליים
$countriesMap = isset($countries) ? array_column($countries, 'name_he', 'country_code') : [];
$citiesMap   = isset($cities)   ? array_column($cities,   'name_he', 'city_code')   : [];
$streesMap   = isset($streets)   ? array_column($streets,   'street_name_he', 'street_code')   : [];
$gendersMap   = isset($genders)   ? array_column($genders,   'name_he', 'gender_code')   : [];
$employerIdTypesMap = isset($id_types) ? array_column($id_types, 'name_he', 'id_type_code') : [];
$employerPassportsMap = isset($employer_passports) ? array_column($employer_passports, 'passport_number', 'employer_id') : [];
$countryLabel = function($code) use ($countriesMap) {
    $code = (string)($code ?? '');
    $name = ($code !== '' && isset($countriesMap[$code])) ? $countriesMap[$code] : null;
    return $name ? e($name) . ' (' . e($code) . ')' : e($code);
};
$citiesLabel = function($code) use ($citiesMap) {
    $code = (string)($code ?? '');
    $name = ($code !== '' && isset($citiesMap[$code])) ? $citiesMap[$code] : null;
    return $name ? e($name) . ' (' . e($code) . ')' : e($code);
};
$streetsLabel = function($code) use ($streesMap) {
    $code = (string)($code ?? '');
    $name = ($code !== '' && isset($streesMap[$code])) ? $streesMap[$code] : null;
    return $name ? e($name) . ' (' . e($code) . ')' : e($code);
};
$genderLabel = function($code) use ($gendersMap) {
    $code = (string)($code ?? '');
    $name = ($code !== '' && isset($gendersMap[$code])) ? $gendersMap[$code] : null;
    return $name ? e($name) . ' (' . e($code) . ')' : e($code);
};
$idTypeLabel = function($code) use ($employerIdTypesMap) {
    $code = (string)($code ?? '');
    $name = ($code !== '' && isset($employerIdTypesMap[$code])) ? $employerIdTypesMap[$code] : null;
    return $name ? e($name) . ' (' . e($code) . ')' : e($code);
};
$employerPassportsLabel = function($employer_id) use ($employerPassportsMap) {
    $employer_id = (int)($employer_id ?? 0);
    $name = ($employer_id !== 0 && isset($employerPassportsMap[$employer_id])) ? $employerPassportsMap[$employer_id] : null;
    return $name ? e($name) : '';
};
?>

<div class="container my-4">

  <!-- כותרת + קישורים -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">פרטי מעסיק</h1>
    <div class="d-flex gap-2">
      <a class="btn btn-primary" href="index.php?r=employers/edit&id=<?= (int)$item['id'] ?>">עריכה</a>
      <a class="btn btn-outline-secondary" href="index.php?r=employer_passports/index&employer_id=<?= (int)$item['id'] ?>">דרכונים</a>
      <a class="btn btn-outline-secondary" href="index.php?r=employment_permits/index&employer_id=<?= (int)$item['id'] ?>">היתרי העסקה</a>
      <a class="btn btn-outline-secondary" href="index.php?r=employer_fees/index&employer_id=<?= (int)$item['id'] ?>">דמי תאגיד</a>
      <a class="btn btn-outline-secondary" href="index.php?r=employers/index">חזרה</a>
    </div>
  </div>

  <!-- ===== מזהים ושמות ===== -->
  <div class="card mb-3 shadow-sm">
    <div class="card-header fw-semibold">פרטים כלליים</div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <tbody>
          <tr>
            <th class="w-25">שם פרטי</th>
            <td><?= e($item['first_name'] ?? '') ?></td>              
            <th class="w-25">סוג מזהה (קוד)</th>
            <td><?= $idTypeLabel($item['id_type_code'] ?? '') ?></td>
          </tr>
          <tr>
            <th>שם משפחה</th>
            <td><?= e($item['last_name'] ?? '') ?></td>
            <th>מס' מזהה</th>
            <td><?= e($item['id_number'] ?? '') ?></td>            
          </tr>
          <tr>
            <th>מין</th>
            <td><?= $genderLabel($item['gender_code'] ?? '') ?></td>
            <th>דרכון (אם קיים)</th>
            <td><?= $employerPassportsLabel($item['id'] ?? '') ?></td>        
          </tr>
          <tr>
            <th>שנת לידה</th>
            <td><?= e($item['birth_year'] ?? '') ?></td>                
            <th>טלפון</th>
            <td><?= e($item['phone_prefix_il'] ?? '') ?><?= $item['phone_prefix_il'] ? '-' : '' ?><?= e($item['phone_number_il'] ?? '') ?></td>
          </tr>
          <tr>
            <th>דוא"ל</th>
            <td colspan="3"><?= e($item['email'] ?? '') ?></td>
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
            <td><?= $citiesLabel($item['city_code'] ?? '') ?></td>
            <th class="w-25">שם רחוב (עברית)</th>
            <td><?= $streetsLabel($item['street_code'] ?? '') ?></td>
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

  <!-- ===== פרטי איש קשר ===== -->
  <div class="card mb-3 shadow-sm">
    <div class="card-header fw-semibold">פרטי איש קשר</div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <tbody>
          <tr>
            <th class="w-25">שם איש קשר</th>
            <td><?= e($item['contact_name'] ?? '') ?></td>
            <th class="w-25">טלפון איש קשר</th>
            <td><?= e($item['contact_phone'] ?? '') ?></td>
          </tr>
          <tr>
            <th>דוא"ל איש קשר</th>
            <td colspan="3"><?= e($item['contact_email'] ?? '') ?></td>
          </tr>          
        </tbody>
      </table>
    </div>
  </div>

  <!-- ===== הערות ===== -->
  <div class="card mb-4 shadow-sm">
    <div class="card-header fw-semibold">הערות</div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <tbody>
          <tr>
            <td><?= e($item['notes'] ?? '') ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</div>

<p class="mt-3">
  <a class="btn btn-primary" href="index.php?r=employers/edit&id=<?= (int)$item['id'] ?>">עריכה</a>
  <a class="btn btn-outline-secondary" href="index.php?r=employers/index">חזרה</a>
</p>

<?php require __DIR__ . '/../layout/footer.php'; ?>
