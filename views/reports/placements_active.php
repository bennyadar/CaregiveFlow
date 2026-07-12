<?php
/**
 * דוח: שיבוצים פעילים
 */

$sidebarActive = 'placements';
?>

<?php require __DIR__ . '/../layout/header.php'; ?>

<?php
$title = 'דוח – שיבוצים פעילים';
include __DIR__ . '/../partials/page_header.php';

$routeValue = 'reports/placements_active';
$label = 'חיפוש (עובד/מעסיק/דרכון/ת"ז)';
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
          <td><?= e($r['end_date'] ?? '—') ?></td>
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
