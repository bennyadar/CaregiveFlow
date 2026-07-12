<?php
/**
 * שיבוצים – מסך מרכזי
 * Tabs: רשימה / סטטוסים
 */

$sidebarActive = 'placements';
?>

<?php require __DIR__ . '/../layout/header.php'; ?>

<?php
$title = 'שיבוצים';
$rightHtml = '';
if ((current_user()['role'] ?? '') !== 'viewer') {
  $rightHtml = '<a class="btn btn-success" href="index.php?r=placements/create">+ שיבוץ חדש</a>';
}
include __DIR__ . '/../partials/page_header.php';

$tab = (string)($tab ?? ($_GET['tab'] ?? 'list'));
if (!in_array($tab, ['list', 'statuses'], true)) {
  $tab = 'list';
}
?>

<ul class="nav nav-tabs mb-3">
  <li class="nav-item">
    <a class="nav-link <?= $tab === 'list' ? 'active' : '' ?>" href="index.php?r=placements/index&tab=list">רשימה</a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $tab === 'statuses' ? 'active' : '' ?>" href="index.php?r=placements/index&tab=statuses">סטטוסים</a>
  </li>
</ul>

<?php if ($tab === 'list'): ?>

  <?php
  // חיפוש בסיסי (שומר על UI קיים)
  $routeValue = 'placements';
  $label = 'חיפוש (עובד/מעסיק/דרכון)';
  $qValue = $_GET['q'] ?? '';
  include __DIR__ . '/../partials/search_bar.php';
  ?>

  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
      <thead>
        <tr>
          <th>#</th>
          <th>עובד</th>
          <th>מעסיק</th>
          <th>התחלה</th>
          <th>סיום</th>
          <th>סטטוס</th>
          <th class="text-end">פעולות</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (($rows ?? []) as $r): ?>
          <?php
            $start  = $r[$cols['start']] ?? null;
            $end    = $r[$cols['end']] ?? null;
            $active = ($start && $start <= date('Y-m-d') && (empty($end) || $end >= date('Y-m-d')));
          ?>
          <tr>
            <td><?= (int)$r[$cols['id']] ?></td>
            <td><?= e(($r['emp_last'] ?? '') . ' ' . ($r['emp_first'] ?? '') . ' [' . ($r['emp_passport'] ?? '') . ']') ?></td>
            <td><?= e(($r['employer_last'] ?? '') . ' ' . ($r['employer_first'] ?? '') . ' [' . ($r['employer_idnum'] ?? '') . ']') ?></td>
            <td><?= e($start) ?></td>
            <td><?= e($end ?: '—') ?></td>
            <td>
              <?php if ($active): ?>
                <span class="badge bg-success">פעיל</span>
              <?php else: ?>
                <span class="badge bg-secondary">סגור</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="index.php?r=placements/show&id=<?= (int)$r[$cols['id']] ?>">צפייה</a>
              <a class="btn btn-sm btn-outline-primary" href="index.php?r=placements/edit&id=<?= (int)$r[$cols['id']] ?>">עריכה</a>
              <?php if ((current_user()['role'] ?? '') === 'admin'): ?>
                <form class="d-inline m-0 p-0" method="post" action="index.php?r=placements/delete" onsubmit="return confirm('למחוק את השיבוץ?');">
                  <input type="hidden" name="id" value="<?= (int)$r[$cols['id']] ?>">
                  <button class="btn btn-sm btn-outline-danger">מחיקה</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($rows)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">אין תוצאות</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

<?php else: ?>

  <?php
  // KPI (כמות כוללת/פעילים/מסתיימים)
  $kpis = [
    ['value' => (int)($kpi['total'] ?? 0),       'label' => 'סה"כ שיבוצים'],
    ['value' => (int)($kpi['active'] ?? 0),      'label' => 'שיבוצים פעילים',        'class' => 'text-success', 'href' => 'index.php?r=reports/placements_active'],
    ['value' => (int)($kpi['ending_soon'] ?? 0), 'label' => 'מסתיימים ב-30 יום',      'class' => 'text-warning', 'href' => 'index.php?r=reports/placements_ending'],
    ['value' => '—',                             'label' => 'דוח היסטוריה',          'href' => 'index.php?r=reports/placements_history'],
  ];
  include __DIR__ . '/../partials/kpi_cards.php';
  ?>

  <div class="card">
    <div class="card-body">
      <h2 class="h6 mb-3">דוחות</h2>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-primary" href="index.php?r=reports/placements_active">שיבוצים פעילים</a>
        <a class="btn btn-outline-primary" href="index.php?r=reports/placements_ending">שיבוצים מסתיימים (30 יום)</a>
        <a class="btn btn-outline-secondary" href="index.php?r=reports/placements_history">היסטוריית שיבוצים (לפי עובד)</a>
      </div>
      <div class="text-muted small mt-2">הדוחות נועדו לצפייה/ניתוח. עריכה מתבצעת במסך "רשימה".</div>
    </div>
  </div>

<?php endif; ?>

<?php require __DIR__ . '/../layout/footer.php'; ?>
