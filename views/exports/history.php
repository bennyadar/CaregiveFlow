<?php /** @var array $rows */ ?>
<?php require __DIR__ . '/../layout/header.php'; ?>
<div class="container my-4">
  <h1 class="h4 mb-3">היסטוריית ייצוא למת"ש</h1>

  <table class="table table-sm align-middle">
    <thead>
      <tr>
        <th>#Job</th>
        <th>נוצר</th>
        <th>עובד</th>
        <th>קובץ</th>
        <th>שורות</th>
        <th>SHA-256</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?php echo (int)$r['id']; ?></td>
          <td><?php echo e($r['created_at']); ?></td>
          <td><?php echo $r['employee_id'] ? (int)$r['employee_id'] : ''; ?></td>
          <td><?php echo e($r['filename']); ?></td>
          <td><?php echo $r['rows_count'] !== null ? (int)$r['rows_count'] : ''; ?></td>
          <td class="text-truncate" style="max-width:240px;"><?php echo e($r['sha256']); ?></td>
          <td>
            <?php if (!empty($r['file_id'])): ?>
              <a class="btn btn-sm btn-outline-primary" href="?r=exports/download&id=<?php echo (int)$r['file_id']; ?>">הורד</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <a href="?r=exports/bafi" class="btn btn-secondary">חזרה לייצוא BAFI</a>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>