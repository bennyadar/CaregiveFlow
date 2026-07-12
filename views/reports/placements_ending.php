<?php
/**
 * דוח: שיבוצים מסתיימים (בחלון ימים)
 */

$sidebarActive = 'placements';
$days = (int)($_GET['days'] ?? 30);
if ($days <= 0 || $days > 365) { $days = 30; }
?>

<?php require __DIR__ . '/../layout/header.php'; ?>

<?php
$title = 'דוח – שיבוצים מסתיימים (' . $days . ' יום)';
include __DIR__ . '/../partials/page_header.php';

// חיפוש + פרמטר days (נשמר)
$routeValue = 'reports/placements_ending&days=' . $days;
$label = 'חיפוש (עובד/מעסיק/דרכון/ת"ז)';
$qValue = $_GET['q'] ?? '';
include __DIR__ . '/../partials/search_bar.php';
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <div class="text-muted small">חלון תאריכים: עד <?= e((new DateTime('today'))->modify('+' . $days . ' days')->format('Y-m-d')) ?></div>
  <div class="btn-group" role="group" aria-label="days">
    <?php foreach ([7, 14, 30, 60] as $d): ?>
      <a class="btn btn-sm <?= $d === $days ? 'btn-primary' : 'btn-outline-primary' ?>" href="index.php?r=reports/placements_ending&days=<?= (int)$d ?>"><?= (int)$d ?> ימים</a>
    <?php endforeach; ?>
  </div>
</div>

<div class="table-responsive">
  <table class="table table-striped table-hover align-middle">
    <thead>
      <tr>
        <th>#</th>
        <th>עובד</th>
        <th>מעסיק</th>
        <th>התחלה</th>
        <th>סיום</th>
        <th class="text-end">קישורים</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($rows ?? []) as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= e(($r['employee_name'] ?? '') . ' [' . ($r['passport_number'] ?? '') . ']') ?></td>
          <td><?= e(($r['employer_name'] ?? '') . ' [' . ($r['employer_id_number'] ?? '') . ']') ?></td>
          <td><?= e($r['start_date'] ?? '') ?></td>
          <td><span class="badge bg-warning text-dark"><?= e($r['end_date'] ?? '') ?></span></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-secondary" href="index.php?r=employees/show&id=<?= (int)$r['employee_id'] ?>">כרטיס עובד</a>
            <a class="btn btn-sm btn-outline-secondary" href="index.php?r=employers/show&id=<?= (int)$r['employer_id'] ?>">כרטיס מעסיק</a>
            <a class="btn btn-sm btn-outline-primary" href="index.php?r=placements/show&id=<?= (int)$r['id'] ?>">צפייה בשיבוץ</a>
          </td>
        </tr>
      <?php endforeach; ?>

      <?php if (empty($rows)): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">אין תוצאות</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
