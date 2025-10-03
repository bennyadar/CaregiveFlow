<?php /** @var array $rows */ /** @var int $pages */ /** @var int $page */ ?>
<!-- CaregiveFlow — Home Visits Module -->
<!-- File: views/home_visits/index.php -->
<!-- HE: מסך רשימת ביקורי בית עם סינון, עימוד ופעולות -->
<!-- EN: Home Visits list with filtering, pagination and actions -->
<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="container-fluid" dir="rtl">
  <h2 class="mt-3 mb-3"><i class="bi bi-clipboard2-pulse"></i> ביקורי בית</h2>

  <form class="row g-2 mb-3" method="get">
    <input type="hidden" name="controller" value="home_visits">
    <input type="hidden" name="action" value="index">

    <div class="col-auto">
      <label class="form-label">עובד</label>
      <input type="number" name="employee_id" class="form-control" value="<?= e($_GET['employee_id'] ?? '') ?>" placeholder="Employee ID">
    </div>

    <div class="col-auto">
      <label class="form-label">מתאריך</label>
      <input type="date" name="date_from" class="form-control" value="<?= e($_GET['date_from'] ?? '') ?>">
    </div>

    <div class="col-auto">
      <label class="form-label">עד תאריך</label>
      <input type="date" name="date_to" class="form-control" value="<?= e($_GET['date_to'] ?? '') ?>">
    </div>

    <div class="col-auto">
      <label class="form-label">סטטוסים</label>
      <select name="status_codes[]" class="form-select" multiple>
        <option value="1" <?= in_array('1', (array)($_GET['status_codes'] ?? []), true) ? 'selected' : '' ?>>מתוכנן</option>
        <option value="2" <?= in_array('2', (array)($_GET['status_codes'] ?? []), true) ? 'selected' : '' ?>>בוצע</option>
        <option value="3" <?= in_array('3', (array)($_GET['status_codes'] ?? []), true) ? 'selected' : '' ?>>בוטל</option>
        <option value="4" <?= in_array('4', (array)($_GET['status_codes'] ?? []), true) ? 'selected' : '' ?>>הוחמצה פגישה/לא התאפשר</option>
      </select>
    </div>

    <div class="col-auto">
      <label class="form-label">סוגים</label>
      <select name="type_codes[]" class="form-select" multiple>
        <option value="1" <?= in_array('1', (array)($_GET['type_codes'] ?? []), true) ? 'selected' : '' ?>>ראשוני</option>
        <option value="2" <?= in_array('2', (array)($_GET['type_codes'] ?? []), true) ? 'selected' : '' ?>>תקופתי (רבעוני)</option>
        <option value="3" <?= in_array('3', (array)($_GET['type_codes'] ?? []), true) ? 'selected' : '' ?>>תקופתי (חצי-שנתי)</option>
        <option value="4" <?= in_array('4', (array)($_GET['type_codes'] ?? []), true) ? 'selected' : '' ?>>מעקב</option>
        <option value="5" <?= in_array('5', (array)($_GET['type_codes'] ?? []), true) ? 'selected' : '' ?>>אירוע חריג/תלונה</option>
      </select>
    </div>

    <div class="col-auto">
      <label class="form-label">שלבים</label>
      <select name="stage_codes[]" class="form-select" multiple>
        <option value="1" <?= in_array('1', (array)($_GET['stage_codes'] ?? []), true) ? 'selected' : '' ?>>טרום השמה</option>
        <option value="2" <?= in_array('2', (array)($_GET['stage_codes'] ?? []), true) ? 'selected' : '' ?>>לאחר השמה</option>
        <option value="3" <?= in_array('3', (array)($_GET['stage_codes'] ?? []), true) ? 'selected' : '' ?>>שוטף</option>
      </select>
    </div>

    <div class="col-auto">
      <label class="form-label">סוג השמה</label>
      <select name="placement_type_codes[]" class="form-select" multiple>
        <option value="1" <?= in_array('1', (array)($_GET['placement_type_codes'] ?? []), true) ? 'selected' : '' ?>>חזרה מאינטרויזה</option>
        <option value="2" <?= in_array('2', (array)($_GET['placement_type_codes'] ?? []), true) ? 'selected' : '' ?>>עובד מחו"ל</option>
        <option value="3" <?= in_array('3', (array)($_GET['placement_type_codes'] ?? []), true) ? 'selected' : '' ?>>עובד ממאגר</option>
        <option value="4" <?= in_array('4', (array)($_GET['placement_type_codes'] ?? []), true) ? 'selected' : '' ?>>רישום לתאגיד</option>
      </select>
    </div>

    <div class="col-auto">
      <label class="form-label">נדרש מעקב</label>
      <select name="followup_required" class="form-select">
        <option value="">הכול</option>
        <option value="1" <?= (($_GET['followup_required'] ?? '') === '1') ? 'selected' : '' ?>>כן</option>
        <option value="0" <?= (($_GET['followup_required'] ?? '') === '0') ? 'selected' : '' ?>>לא</option>
      </select>
    </div>

    <div class="col-auto align-self-end">
      <button class="btn btn-primary"><i class="bi bi-search"></i> חפש</button>
      <a class="btn btn-outline-secondary" href="index.php?r=home_visits/create<?php if(!empty($_GET['employee_id'])) echo '&employee_id='.urlencode($_GET['employee_id']);?>"><i class="bi bi-plus-circle"></i> חדש</a>
      <a class="btn btn-success" href="index.php?r=exports/home_visits_xlsx&<?= http_build_query($_GET) ?>"><i class="bi bi-file-earmark-spreadsheet"></i> ייצוא</a>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>תאריך ביקור</th>
          <th>עובד</th>
          <th>סוג</th>
          <th>שלב</th>
          <th>סטטוס</th>
          <th>סוג השמה</th>
          <th>נדרש מעקב</th>
          <th>ביקור הבא</th>
          <th class="text-center">פעולות</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= e($r['id']) ?></td>
            <td><?= e($r['visit_date']) ?></td>
            <td><?= e(($r['employee_last_name'] ?? '') . ' ' . ($r['employee_first_name'] ?? '')) ?></td>
            <td><?= e($r['type_name'] ?? '') ?></td>
            <td><?= e($r['stage_name'] ?? '') ?></td>
            <td><?= e($r['status_name'] ?? '') ?></td>
            <td><?= e($r['placement_type_name'] ?? '') ?></td>
            <td><?= !empty($r['followup_required']) ? '✔' : '' ?></td>
            <td><?= e($r['next_visit_due'] ?? '') ?></td>
            <td class="text-center">
              <a class="btn btn-sm btn-outline-secondary" title="צפייה" href="index.php?r=home_visits/view&id=<?= e($r['id']) ?>">
                <i class="bi bi-eye"></i>
              </a>
              <a class="btn btn-sm btn-outline-primary" title="עריכה" href="index.php?r=home_visits/edit&id=<?= e($r['id']) ?>">
                <i class="bi bi-pencil-square"></i>
              </a>
              <form class="d-inline" method="post" action="index.php?r=home_visits/delete" onsubmit="return confirm('למחוק רשומה זו?');">
                <input type="hidden" name="id" value="<?= e($r['id']) ?>">
                <button class="btn btn-sm btn-outline-danger" title="מחיקה"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if (($pages ?? 1) > 1): ?>
    <nav>
      <ul class="pagination">
        <?php for ($p = 1; $p <= $pages; $p++): $q = $_GET; $q['page'] = $p; ?>
          <li class="page-item <?= $p == ($page ?? 1) ? 'active' : '' ?>">
            <a class="page-link" href="?<?= http_build_query($q) ?>"><?= $p ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
  <?php endif; ?>
</div>
