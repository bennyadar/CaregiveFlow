<?php
/**
 * דוח: היסטוריית שיבוצים לפי עובד
 */

$sidebarActive = 'placements';
$employeeId = (int)($_GET['employee_id'] ?? 0);
?>

<?php require __DIR__ . '/../layout/header.php'; ?>

<?php
$title = 'דוח – היסטוריית שיבוצים (לפי עובד)';
include __DIR__ . '/../partials/page_header.php';
?>

<form class="row g-2 mb-3" method="get" action="">
  <input type="hidden" name="r" value="reports/placements_history">
  <div class="col-md-6">
    <label class="form-label">בחר עובד</label>
    <select name="employee_id" class="form-select">
      <option value="">— בחר —</option>
      <?php foreach (($empOptions ?? []) as $e): ?>
        <?php $sel = ((int)$e['id'] === $employeeId) ? 'selected' : ''; ?>
        <option value="<?= (int)$e['id'] ?>" <?= $sel ?>><?= e($e['name'] ?? '') ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2 d-grid align-items-end">
    <button class="btn btn-outline-secondary">הצג</button>
  </div>
</form>

<?php if ($employeeId > 0): ?>
  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
      <thead>
        <tr>
          <th>#</th>
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
            <td><?= e(($r['employer_name'] ?? '') . ' [' . ($r['employer_id_number'] ?? '') . ']') ?></td>
            <td><?= e($r['start_date'] ?? '') ?></td>
            <td><?= e($r['end_date'] ?? '—') ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-secondary" href="index.php?r=employers/show&id=<?= (int)$r['employer_id'] ?>">כרטיס מעסיק</a>
              <a class="btn btn-sm btn-outline-primary" href="index.php?r=placements/show&id=<?= (int)$r['id'] ?>">צפייה בשיבוץ</a>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($rows)): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">אין נתונים לעובד שנבחר</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <div class="alert alert-info">בחר עובד כדי להציג את היסטוריית השיבוצים.</div>
<?php endif; ?>

<?php require __DIR__ . '/../layout/footer.php'; ?>
