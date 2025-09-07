<?php require __DIR__ . '/../layout/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 m-0">שיבוצים</h1>
  <?php if ((current_user()['role'] ?? '') !== 'viewer'): ?>
    <a class="btn btn-success" href="index.php?r=placements/create">+ שיבוץ חדש</a>
  <?php endif; ?>
</div>
<form class="row g-2 mb-3" method="get">
  <input type="hidden" name="r" value="placements/index">
  <div class="col-auto">
    <input class="form-control" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="חיפוש: עובד/מעסיק/דרכון">
  </div>
  <div class="col-auto">
    <button class="btn btn-outline-primary">חפש</button>
  </div>
</form>
<div class="table-responsive">
<table class="table table-striped table-hover align-middle">
  <thead><tr>
    <th>#</th><th>עובד</th><th>מעסיק</th><th>התחלה</th><th>סיום</th><th>סטטוס</th><th></th>
  </tr></thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
      <?php
        $start = $r[$cols['start']] ?? null;
        $end   = $r[$cols['end']] ?? null;
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
          <a class="btn btn-sm btn-outline-secondary" href="index.php?r=placements/show&id=<?= (int)$r[$cols['id']] ?>">צפייה</a>
          <a class="btn btn-sm btn-primary" href="index.php?r=placements/edit&id=<?= (int)$r[$cols['id']] ?>">עריכה</a>
          <?php if ((current_user()['role'] ?? '') === 'admin'): ?>
          <form class="d-inline m-0 p-0" method="post" action="index.php?r=placements/delete" onsubmit="return confirm('למחוק את השיבוץ?');">
            <input type="hidden" name="id" value="<?= (int)$r[$cols['id']] ?>">
            <button class="btn btn-sm btn-danger">מחיקה</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
