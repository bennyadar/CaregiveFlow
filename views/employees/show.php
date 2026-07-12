<?php
require __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../src/models/FileType.php';
require_once __DIR__ . '/../../src/models/File.php';

// ===== Maps אופציונליים לשמות קוד→תווית (אם הועברו מהקונטרולר) =====
$countriesMap = isset($countries) ? array_column($countries, 'name_he', 'country_code') : [];
$gendersMap   = isset($genders)   ? array_column($genders,   'name_he', 'gender_code')   : [];
$martialsMap  = isset($maritals)  ? array_column($maritals,  'name_he', 'marital_status_code') : [];

// פונקציות עזר לתצוגת תוויות מתוך קוד
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
$maritalLabel = function($code) use ($martialsMap) {
    $code = (string)($code ?? '');
    $name = ($code !== '' && isset($martialsMap[$code])) ? $martialsMap[$code] : null;
    return $name ? e($name) . ' (' . e($code) . ')' : e($code);
};

// ===== partial להעלאת קבצים =====
// משתנים נדרשים: $module, $record_id, $fileTypes
$fileTypes = (new FileType($pdo))->allActive();
$module    = 'employees';
$record_id = (int)($item['id'] ?? 0);

// ===== כותרת מסך + פעולות =====
$title = 'פרטי עובד';
$id = (int)($item['id'] ?? 0);

ob_start();
?>
  <a class="btn btn-primary" href="index.php?r=employees/edit&id=<?= $id ?>">עריכה</a>
  <a class="btn btn-outline-secondary" href="index.php?r=passports&employee_id=<?= $id ?>">דרכונים</a>
  <a class="btn btn-outline-secondary" href="index.php?r=visas&employee_id=<?= $id ?>">ויזות</a>
  <a class="btn btn-outline-secondary" href="index.php?r=insurances&employee_id=<?= $id ?>">ביטוחים</a>
  <a class="btn btn-outline-secondary" href="index.php?r=home_visits&employee_id=<?= $id ?>">ביקורי בית</a>

  <div class="btn-group" role="group">
    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
      ייצוא
    </button>
    <ul class="dropdown-menu dropdown-menu-end" dir="rtl">
      <li>
        <a class="dropdown-item" href="index.php?r=exports/piba&employee_id=<?= $id ?>">לרשות האוכלוסין (PIBA)</a>
      </li>
      <li>
        <a class="dropdown-item" href="index.php?r=exports/bafi&employee_id=<?= $id ?>">למת״ש (BAFI)</a>
      </li>
    </ul>
  </div>

  <a class="btn btn-outline-secondary" href="?r=exports/history">היסטוריית ייצוא</a>
  <a class="btn btn-outline-secondary" href="index.php?r=employees/index">חזרה</a>
<?php
$rightHtml = ob_get_clean();
include __DIR__ . '/../partials/page_header.php';
?>

<?php
// ===== כרטיסיות סטטוס (תוקפים מרכזיים) =====
$exp = $summary_expiry ?? [];
$status = static function($d): array {
    if (!$d) return ['label' => 'ללא', 'class' => 'text-muted'];
    $today = date('Y-m-d');
    if ($d < $today) return ['label' => 'פג', 'class' => 'text-danger'];
    $diff = (new DateTime($today))->diff(new DateTime($d))->days;
    if ($diff <= 30) return ['label' => 'קרוב', 'class' => 'text-warning'];
    return ['label' => 'בתוקף', 'class' => 'text-success'];
};

$kpis = [
    ['value' => (string)($exp['passport_expiry_date'] ?? '—'), 'label' => 'פקיעת דרכון', 'class' => ($status($exp['passport_expiry_date'] ?? null)['class'] ?? '')],
    ['value' => (string)($exp['visa_expiry_date'] ?? '—'),     'label' => 'פקיעת ויזה',  'class' => ($status($exp['visa_expiry_date'] ?? null)['class'] ?? '')],
    ['value' => (string)($exp['insurance_expiry_date'] ?? '—'), 'label' => 'פקיעת ביטוח', 'class' => ($status($exp['insurance_expiry_date'] ?? null)['class'] ?? '')],
    ['value' => (int)($item['is_active'] ?? 0) ? 'פעיל' : 'לא פעיל', 'label' => 'סטטוס עובד', 'class' => ((int)($item['is_active'] ?? 0) ? 'text-success' : 'text-muted')],
];
include __DIR__ . '/../partials/kpi_cards.php';
?>

<div class="card mb-3 shadow-sm">
  <div class="card-header fw-semibold">פרטים כלליים</div>
  <div class="card-body p-0">
    <table class="table table-sm mb-0">
      <tbody>
        <tr>
          <th class="w-25">דרכון</th>
          <td><?= e($item['passport_number'] ?? '') ?></td>
          <th class="w-25">קוד ארץ</th>
          <td><?= $countryLabel($item['country_of_citizenship'] ?? '') ?></td>
        </tr>
        <tr>
          <th>שם משפחה (לטיני)</th>
          <td><?= e($item['last_name'] ?? '') ?></td>
          <th>שם פרטי (לטיני)</th>
          <td><?= e($item['first_name'] ?? '') ?></td>
        </tr>
        <tr>
          <th>שם משפחה (עברית)</th>
          <td><?= e($item['last_name_he'] ?? '') ?></td>
          <th>שם פרטי (עברית)</th>
          <td><?= e($item['first_name_he'] ?? '') ?></td>
        </tr>
        <tr>
          <th>שם האב (לטיני)</th>
          <td><?= e($item['father_name_en'] ?? '') ?></td>
          <th>מין</th>
          <td><?= $genderLabel($item['gender_code'] ?? '') ?></td>
        </tr>
        <tr>
          <th>תאריך לידה</th>
          <td><?= e(date_to_string($item['birth_date']) ?? '') ?></td>
          <th>מצב משפחתי</th>
          <td><?= $maritalLabel($item['marital_status_code'] ?? '') ?></td>
        </tr>
        <tr>
          <th>דוא"ל</th>
          <td><?= e($item['email'] ?? '') ?></td>
          <th>טלפון (IL)</th>
          <td>
            <?= e($item['phone_prefix_il'] ?? '') ?><?= ($item['phone_prefix_il'] ?? '') ? '-' : '' ?><?= e($item['phone_number_il'] ?? '') ?>
          </td>
        </tr>
        <tr>
          <th>שם אם (לטיני)</th>
          <td><?= e($item['mother_name_en'] ?? '') ?></td>
          <th>שם בן/בת זוג</th>
          <td><?= e($item['spouse_name_en'] ?? '') ?></td>
        </tr>
        <tr>
          <th>בן/בת הזוג בישראל</th>
          <td>
            <?php
              $sp = $item['spouse_in_israel'] ?? null;
              if ($sp === '' || $sp === null) {
                echo '<span class="badge bg-secondary">לא ידוע</span>';
              } elseif ($sp) {
                echo '<span class="badge bg-success">כן</span>';
              } else {
                echo '<span class="badge bg-danger">לא</span>';
              }
            ?>
          </td>
          <th>שם נציג בחו"ל</th>
          <td><?= e($item['representative_abroad_name'] ?? '') ?></td>
        </tr>
        <tr>
          <th>ביטוח רפואי – תאריך הפקה</th>
          <td><?= e(date_to_string($item['health_ins_issue_date']) ?? '') ?></td>
          <th>ביטוח רפואי – תאריך פקיעה</th>
          <td>
            <?php $exp = $item['health_ins_expiry'] ?? null; $isExpired = ($exp && $exp < date('Y-m-d')); ?>
            <span class="<?= $isExpired ? 'text-danger fw-semibold' : '' ?>"><?= e(date_to_string($exp) ?? '') ?></span>
          </td>
        </tr>
        <tr>
          <th>מת"ש – מספר מנה</th>
          <td><?= e($item['metash_mana_number'] ?? '') ?></td>
          <th>מת"ש – תאריך רישום</th>
          <td><?= e(date_to_string($item['metash_registration_date']) ?? '') ?></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- ===== כתובת עובד בחו״ל (BAFI 355–430) ===== -->
<div class="card mb-3 shadow-sm">
  <div class="card-header fw-semibold">כתובת עובד בחו״ל</div>
  <div class="card-body p-0">
    <table class="table table-sm mb-0">
      <tbody>
        <tr>
          <th class="w-25">עיר</th>
          <td><?= e($item['abroad_city'] ?? '') ?></td>
          <th class="w-25">רחוב</th>
          <td><?= e($item['abroad_street'] ?? '') ?></td>
        </tr>
        <tr>
          <th>מס' בית</th>
          <td><?= e($item['abroad_house_no'] ?? '') ?></td>
          <th>מיקוד</th>
          <td><?= e($item['abroad_postal_code'] ?? '') ?></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- ===== פרטי בנק בחו״ל + מוטב (BAFI 431–704) ===== -->
<div class="card mb-3 shadow-sm">
  <div class="card-header fw-semibold">פרטי בנק בחו״ל + מוטב</div>
  <div class="card-body p-0">
    <table class="table table-sm mb-0">
      <tbody>
        <tr>
          <th class="w-25">קוד ארץ בנק</th>
          <td><?= $countryLabel($item['bank_foreign_country_code'] ?? '') ?></td>
          <th class="w-25">SWIFT</th>
          <td><?= e($item['bank_swift'] ?? '') ?></td>
        </tr>
        <tr>
          <th>IBAN</th>
          <td><?= e($item['bank_iban'] ?? '') ?></td>
          <th>מס' חשבון</th>
          <td><?= e($item['bank_account'] ?? '') ?></td>
        </tr>
        <tr>
          <th>עיר בנק</th>
          <td><?= e($item['bank_city_foreign'] ?? '') ?></td>
          <th>רחוב בנק</th>
          <td><?= e($item['bank_street_foreign'] ?? '') ?></td>
        </tr>
        <tr>
          <th>מס' בית (בנק)</th>
          <td><?= e($item['bank_house_no_foreign'] ?? '') ?></td>
          <th>סמל בנק / שם בנק</th>
          <td>
            <?= e($item['bank_code_foreign'] ?? '') ?>
            <?php if (!empty($item['bank_name_foreign'])): ?>
              — <?= e($item['bank_name_foreign']) ?>
            <?php endif; ?>
          </td>
        </tr>
        <tr>
          <th>סמל סניף / שם סניף</th>
          <td>
            <?= e($item['bank_branch_code_foreign'] ?? '') ?>
            <?php if (!empty($item['bank_branch_name_foreign'])): ?>
              — <?= e($item['bank_branch_name_foreign']) ?>
            <?php endif; ?>
          </td>
          <th>מוטב (שם מלא)</th>
          <td><?= e(trim(($item['beneficiary_last_name'] ?? '') . ' ' . ($item['beneficiary_first_name'] ?? ''))) ?></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
