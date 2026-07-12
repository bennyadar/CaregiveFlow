<?php
/**
 * ביקורי בית – מסך מרכזי
 * Tabs: רשימה / סטטוסים
 */

$sidebarActive = 'home_visits';
?>

<?php require __DIR__ . '/../layout/header.php'; ?>

<?php
$title = 'ביקורי בית';

$employeeId = (string)($filters['employee_id'] ?? ($_GET['employee_id'] ?? ''));

ob_start();
?>
  <a class="btn btn-success" href="index.php?r=home_visits/create&employee_id=<?= e($employeeId) ?>">+ ביקור חדש</a>
  <?php if ($employeeId !== ''): ?>
    <a class="btn btn-outline-secondary" href="index.php?r=employees/show&id=<?= e($employeeId) ?>">חזרה לכרטיס עובד</a>
  <?php endif; ?>
<?php
$rightHtml = ob_get_clean();
include __DIR__ . '/../partials/page_header.php';

$tab = (string)($tab ?? ($_GET['tab'] ?? 'list'));
if (!in_array($tab, ['list', 'statuses'], true)) {
  $tab = 'list';
}
?>

<ul class="nav nav-tabs mb-3">
  <li class="nav-item">
    <a class="nav-link <?= $tab === 'list' ? 'active' : '' ?>" href="index.php?r=home_visits&tab=list">רשימה</a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $tab === 'statuses' ? 'active' : '' ?>" href="index.php?r=home_visits&tab=statuses">סטטוסים</a>
  </li>
</ul>

<?php if ($tab === 'list'): ?>

  <form class="row g-2 mb-3" method="get" action="">
      <input type="hidden" name="r" value="home_visits">
      <input type="hidden" name="tab" value="list">

      <div class="col-md-3">
          <label class="form-label">עובד</label>
          <select name="employee_id" class="form-select">
              <option value="">— הכל —</option>
              <?php foreach ($employees as $emp): ?>
                  <?php $selected = ((string)$emp['id'] === (string)($_GET['employee_id'] ?? '')) ? 'selected' : ''; ?>
                  <option value="<?= e($emp['id']) ?>" <?= $selected ?>>
                      <?= e($emp['last_name'] . ' ' . $emp['first_name'] . ' (' . $emp['passport_number'] . ')') ?>
                  </option>
              <?php endforeach; ?>
          </select>
      </div>

      <div class="col-md-2">
          <label class="form-label">סטטוס</label>
          <select name="status" class="form-select">
              <option value="">— הכל —</option>
              <option value="overdue" <?= ((string)($_GET['status'] ?? '') === 'overdue') ? 'selected' : '' ?>>איחור יעד</option>
              <?php foreach ($status_codes as $code => $name): ?>
                  <?php $sel = ((string)$code === (string)($_GET['status'] ?? '')) ? 'selected' : ''; ?>
                  <option value="<?= e($code) ?>" <?= $sel ?>><?= e($name) ?></option>
              <?php endforeach; ?>
          </select>
      </div>

      <div class="col-md-2">
          <label class="form-label">סוג ביקור</label>
          <select name="type" class="form-select">
              <option value="">— הכל —</option>
              <?php foreach ($type_codes as $code => $name): ?>
                  <?php $sel = ((string)$code === (string)($_GET['type'] ?? '')) ? 'selected' : ''; ?>
                  <option value="<?= e($code) ?>" <?= $sel ?>><?= e($name) ?></option>
              <?php endforeach; ?>
          </select>
      </div>

      <div class="col-md-2">
          <label class="form-label">שלב</label>
          <select name="stage" class="form-select">
              <option value="">— הכל —</option>
              <?php foreach ($stage_codes as $code => $name): ?>
                  <?php $sel = ((string)$code === (string)($_GET['stage'] ?? '')) ? 'selected' : ''; ?>
                  <option value="<?= e($code) ?>" <?= $sel ?>><?= e($name) ?></option>
              <?php endforeach; ?>
          </select>
      </div>

      <div class="col-md-3">
          <label class="form-label">חיפוש</label>
          <input type="text" name="q" class="form-control" value="<?= e($_GET['q'] ?? '') ?>" placeholder="שם עובד / סיכום / ממצאים">
      </div>

      <div class="col-md-2">
          <label class="form-label">מתאריך</label>
          <input type="date" name="date_from" class="form-control" value="<?= e($_GET['date_from'] ?? '') ?>">
      </div>

      <div class="col-md-2">
          <label class="form-label">עד תאריך</label>
          <input type="date" name="date_to" class="form-control" value="<?= e($_GET['date_to'] ?? '') ?>">
      </div>

      <div class="col-md-2">
          <label class="form-label">יעד מעקב עד</label>
          <input type="date" name="due_until" class="form-control" value="<?= e($_GET['due_until'] ?? '') ?>">
      </div>

      <div class="col-md-2 d-flex align-items-end">
          <div class="form-check">
              <?php $checked = ((string)($_GET['followup_only'] ?? '') === '1') ? 'checked' : ''; ?>
              <input class="form-check-input" type="checkbox" name="followup_only" value="1" id="followup_only" <?= $checked ?>>
              <label class="form-check-label" for="followup_only">מעקב בלבד</label>
          </div>
      </div>

      <div class="col-md-2 d-grid align-items-end">
          <button class="btn btn-outline-secondary">סינון</button>
      </div>
  </form>

  <div class="table-responsive">
      <table class="table table-sm align-middle">
          <thead>
              <tr>
                  <th>#</th>
                  <th>תאריך ביקור</th>
                  <th>עובד</th>
                  <th>סוג</th>
                  <th>סטטוס</th>
                  <th>שלב</th>
                  <th>מעקב</th>
                  <th>יעד</th>
                  <th style="width: 200px;"></th>
              </tr>
          </thead>
          <tbody>
              <?php foreach ($rows as $row): ?>
                  <tr>
                      <td><?= e($row['id']) ?></td>
                      <td><?= e($row['visit_date']) ?></td>
                      <td><?= e(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?></td>
                      <td><?= e($row['type_name'] ?? '—') ?></td>
                      <td><?= e($row['status_name'] ?? '—') ?></td>
                      <td><?= e($row['stage_name'] ?? '—') ?></td>
                      <td><?= !empty($row['followup_required']) ? 'כן' : 'לא' ?></td>
                      <td><?= e($row['next_visit_due'] ?? '—') ?></td>
                      <td class="text-end">
                          <a class="btn btn-sm btn-outline-secondary" href="index.php?r=home_visits/view&id=<?= e($row['id']) ?>">צפייה</a>
                          <a class="btn btn-sm btn-outline-primary" href="index.php?r=home_visits/edit&id=<?= e($row['id']) ?>">עריכה</a>
                          <a class="btn btn-sm btn-outline-danger" href="index.php?r=home_visits/delete&id=<?= e($row['id']) ?>" onclick="return confirm('למחוק?');">מחיקה</a>
                      </td>
                  </tr>
              <?php endforeach; ?>
              <?php if (empty($rows)): ?>
                  <tr><td colspan="9" class="text-center text-muted py-4">אין תוצאות</td></tr>
              <?php endif; ?>
          </tbody>
      </table>
  </div>

  <?php if ($pages > 1): ?>
      <nav class="mt-3">
          <ul class="pagination">
              <?php for ($p = 1; $p <= $pages; $p++): ?>
                  <?php
                      $qs = $_GET;
                      $qs['page'] = $p;
                      $url = 'index.php?' . http_build_query($qs);
                      $active = ($p === (int)($_GET['page'] ?? 1)) ? 'active' : '';
                  ?>
                  <li class="page-item <?= $active ?>"><a class="page-link" href="<?= e($url) ?>"><?= e($p) ?></a></li>
              <?php endfor; ?>
          </ul>
      </nav>
  <?php endif; ?>

<?php else: ?>

  <?php
    // KPI סטטוסים (קישורים לסינון אמיתי ב-SQL דרך ה-GET filters)
    $dueSoonDate = (new DateTime('today'))->modify('+7 days')->format('Y-m-d');
    $kpis = [
      ['value' => (int)($kpi['total'] ?? 0),    'label' => 'סה"כ ביקורי בית'],
      ['value' => (int)($kpi['followup'] ?? 0), 'label' => 'דורשים מעקב', 'class' => 'text-warning', 'href' => 'index.php?r=home_visits&tab=list&followup_only=1'],
      ['value' => (int)($kpi['overdue'] ?? 0),  'label' => 'איחור יעד (מעקב)', 'class' => 'text-danger', 'href' => 'index.php?r=home_visits&tab=list&status=overdue&followup_only=1'],
      ['value' => (int)($kpi['due_soon'] ?? 0), 'label' => 'יעד ב-7 ימים (מעקב)', 'class' => 'text-warning', 'href' => 'index.php?r=home_visits&tab=list&followup_only=1&due_until=' . $dueSoonDate],
    ];
    include __DIR__ . '/../partials/kpi_cards.php';
  ?>

  <div class="card">
    <div class="card-body">
      <h2 class="h6 mb-3">קישורים מהירים</h2>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-primary" href="index.php?r=home_visits&tab=list&followup_only=1">מעקב בלבד</a>
        <a class="btn btn-outline-danger" href="index.php?r=home_visits&tab=list&status=overdue&followup_only=1">איחור יעד (מעקב)</a>
        <a class="btn btn-outline-secondary" href="index.php?r=home_visits&tab=list&due_until=<?= e($dueSoonDate) ?>&followup_only=1">יעד עד <?= e($dueSoonDate) ?></a>
      </div>
      <div class="text-muted small mt-2">הקישורים מיישמים סינון אמיתי ב-SQL לפי הפרמטרים שבמסך.</div>
    </div>
  </div>

<?php endif; ?>

<?php require __DIR__ . '/../layout/footer.php'; ?>
