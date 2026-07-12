<?php
/**
 * רשימת עובדים – עמודות מותאמות
 * מציג: שם, דרכון, טלפון, מעסיק נוכחי, תוקפים (דרכון/ויזה/ביטוח), פעולות.
 *
 * נדרש מה-Controller להזרים לכל שורה את המפתחות:
 *   id, first_name, last_name, passport_number, phone, email,
 *   passport_expiry_date, visa_expiry_date, insurance_expiry_date,
 *   current_employer_name
 */
?>

<?php require __DIR__ . '/../layout/header.php'; ?>

<?php
// ===== כותרת מסך + כפתורי פעולה =====
$title = 'עובדים';
$rightHtml = '<a class="btn btn-success" href="?r=employees/create">+ עובד חדש</a>';
include __DIR__ . '/../partials/page_header.php';

// ===== Tabs (לפי המוקאף) =====
$tab = $tab ?? ($_GET['tab'] ?? 'list');
?>

<ul class="nav nav-pills mb-3">
  <li class="nav-item">
    <a class="nav-link <?= ($tab === 'list') ? 'active' : '' ?>" href="<?= e(update_query(['tab' => 'list', 'page' => 1])) ?>">רשימת עובדים</a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= ($tab === 'stats') ? 'active' : '' ?>" href="<?= e(update_query(['tab' => 'stats', 'page' => 1])) ?>">סטטיסטיקה</a>
  </li>
</ul>

<?php

// ===== חיפוש + סינון מתקדם (רק בלשונית "רשימה") =====
if ($tab === 'list') {
    $routeValue = 'employees';
    $label = 'חיפוש (שם/דרכון/טלפון/מעסיק)';
    $qValue = $_GET['q'] ?? '';

    // שדות פילטרים (Dropdowns) – לפי המוקאף
    $isActive = (string)($_GET['is_active'] ?? '');
    $placement = (string)($_GET['placement'] ?? 'any');
    $employerId = (int)($_GET['employer_id'] ?? 0);
    $expiry = (string)($_GET['expiry'] ?? '');

    ob_start();
    ?>
      <input type="hidden" name="tab" value="<?= e($tab) ?>">

      <div class="col-md-2">
        <label class="form-label">סטטוס עובד</label>
        <select class="form-select" name="is_active">
          <option value="" <?= $isActive === '' ? 'selected' : '' ?>>הכל</option>
          <option value="1" <?= $isActive === '1' ? 'selected' : '' ?>>פעיל</option>
          <option value="0" <?= $isActive === '0' ? 'selected' : '' ?>>לא פעיל</option>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label">שיבוץ</label>
        <select class="form-select" name="placement">
          <option value="any" <?= $placement === 'any' ? 'selected' : '' ?>>הכל</option>
          <option value="active" <?= $placement === 'active' ? 'selected' : '' ?>>בשיבוץ פעיל</option>
          <option value="none" <?= $placement === 'none' ? 'selected' : '' ?>>ללא שיבוץ פעיל</option>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label">מעסיק (שיבוץ פעיל)</label>
        <select class="form-select" name="employer_id">
          <option value="0">הכל</option>
          <?php foreach (($employersOptions ?? []) as $opt): ?>
            <?php $oid = (int)($opt['id'] ?? 0); ?>
            <option value="<?= $oid ?>" <?= ($oid === $employerId) ? 'selected' : '' ?>><?= e($opt['name'] ?? '') ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label">תוקפים</label>
        <select class="form-select" name="expiry">
          <option value="" <?= $expiry === '' ? 'selected' : '' ?>>הכל</option>
          <option value="passport_expired" <?= $expiry === 'passport_expired' ? 'selected' : '' ?>>דרכון פג תוקף</option>
          <option value="passport_soon" <?= $expiry === 'passport_soon' ? 'selected' : '' ?>>דרכון יפוג ב־30 יום</option>
          <option value="visa_expired" <?= $expiry === 'visa_expired' ? 'selected' : '' ?>>ויזה פג תוקף</option>
          <option value="visa_soon" <?= $expiry === 'visa_soon' ? 'selected' : '' ?>>ויזה יפוג ב־30 יום</option>
          <option value="insurance_expired" <?= $expiry === 'insurance_expired' ? 'selected' : '' ?>>ביטוח פג תוקף</option>
          <option value="insurance_soon" <?= $expiry === 'insurance_soon' ? 'selected' : '' ?>>ביטוח יפוג ב־30 יום</option>
        </select>
      </div>
    <?php
    $extraFieldsHtml = ob_get_clean();

    $clearUrl = '?r=employees&tab=' . urlencode($tab);
    $extraButtonsHtml = '<div class="col-md-2 d-flex align-items-end"><a class="btn btn-outline-secondary w-100" href="' . e($clearUrl) . '">נקה</a></div>';

    include __DIR__ . '/../partials/search_bar.php';
}

// ===== KPI Cards (סטטוסים) =====
// Controller מזרים $expiryKpis (passport/visa/insurance: expired/soon)
$kpis = [];
if (isset($expiryKpis) && is_array($expiryKpis)) {
    $days = (int)($expiryKpis['soonDays'] ?? 30);
    $kpis = [
        ['value' => (int)($total ?? 0), 'label' => 'סה"כ עובדים', 'class' => 'text-dark'],
        ['value' => (int)($expiryKpis['passport']['expired'] ?? 0), 'label' => 'דרכונים פגי תוקף', 'class' => 'text-danger'],
        ['value' => (int)($expiryKpis['visa']['expired'] ?? 0), 'label' => 'ויזות פגי תוקף', 'class' => 'text-danger'],
        ['value' => (int)($expiryKpis['insurance']['soon'] ?? 0), 'label' => "ביטוחים עד {$days} יום", 'class' => 'text-warning'],
    ];
}
// KPI cards מוצגים בכל לשונית (לפי המוקאף)
include __DIR__ . '/../partials/kpi_cards.php';

// ===== עזר לתצוגת תוקף (פג/קרוב/בתוקף) =====
$fmtExpiry = static function($d) {
    if (!$d) return '<span class="text-muted">—</span>';
    $today = date('Y-m-d');
    if ($d < $today) return '<span class="text-danger fw-semibold">'.e($d).'</span>'; // פג
    $days = (new DateTime($today))->diff(new DateTime($d))->days;
    if ($days <= 30) return '<span class="text-warning fw-semibold">'.e($d).'</span>'; // בקרוב
    return '<span class="text-success fw-semibold">'.e($d).'</span>'; // בתוקף
};
?>

<?php if ($tab === 'list'): ?>

<div class="table-responsive overflow-visible">
  <table class="table table-striped align-middle">
    <thead>
      <tr>
        <th>שם עובד</th>
        <th>דרכון</th>
        <th>טלפון</th>
        <th>מעסיק נוכחי</th>
        <th>דרכון - תוקף</th>
        <th>ויזה - תוקף</th>
        <th>ביטוח - תוקף</th>
        <th class="text-end">פעולות</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($rows ?? []) as $row): ?>
        <?php
          $full_name = trim(($row['last_name'] ?? '').' '.($row['first_name'] ?? ''));
          $phone     = $row['phone'] ?? '';
          $pass_num  = $row['passport_number'] ?? '';
          $employer_name = $row['current_employer_name'] ?? '';

          $pass_exp  = $row['passport_expiry_date']   ?? null;
          $visa_exp  = $row['visa_expiry_date']       ?? null;
          $ins_exp   = $row['insurance_expiry_date']  ?? null;
          $id        = (int)($row['id'] ?? 0);
        ?>
        <tr>
          <td><?= e($full_name ?: '—') ?></td>
          <td><?= e($pass_num ?: '—') ?></td>
          <td><?= e($phone ?: '—') ?></td>
          <td><?= e($employer_name !== '' ? $employer_name : '—') ?></td>
          <td><?= $fmtExpiry($pass_exp) ?></td>
          <td><?= $fmtExpiry($visa_exp) ?></td>
          <td><?= $fmtExpiry($ins_exp) ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="?r=employees/show&id=<?= $id ?>">צפייה</a>
            <a class="btn btn-sm btn-outline-primary" href="?r=employees/edit&id=<?= $id ?>">עריכה</a>

            <div class="btn-group btn-group-sm" role="group">
              <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                ייצוא
              </button>
              <ul class="dropdown-menu dropdown-menu-end" dir="rtl">
                <li>
                  <a class="dropdown-item" href="index.php?r=exports/piba&employee_id=<?= $id ?>">
                    לרשות האוכלוסין (PIBA)
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" href="index.php?r=exports/bafi&employee_id=<?= $id ?>">
                    למת״ש (BAFI)
                  </a>
                </li>
              </ul>
            </div>

            <a class="btn btn-sm btn-outline-primary" href="?r=placements/create&employee_id=<?= $id ?>">שיבוץ</a>
            <a class="btn btn-sm btn-outline-danger" href="?r=employees/delete&id=<?= $id ?>" onclick="return confirm('למחוק?');">מחיקה</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php endif; ?>

<?php if ($tab === 'list' && !empty($pages) && $pages > 1): ?>
  <nav>
    <ul class="pagination">
      <?php for ($p=1; $p <= $pages; $p++): ?>
        <?php $active = ($p === ($page ?? 1)) ? 'active' : ''; ?>
        <li class="page-item <?= $active ?>">
          <a class="page-link" href="<?= e(update_query(['page'=>$p])) ?>"><?= e($p) ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
<?php endif; ?>

<?php if ($tab === 'stats'): ?>
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">סטטיסטיקה</h5>
      <p class="text-muted mb-0">בשלב זה מוצגים כרטיסי סטטוס (KPI). נוכל להוסיף בהמשך גרף פעילות/שיבוצים לפי חודש בדיוק כפי שמופיע במוקאף.</p>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../layout/footer.php'; ?>
