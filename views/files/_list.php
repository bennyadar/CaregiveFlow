<?php
/** שימוש:
 *   $module, $record_id מוגדרים מחוץ
 *   $files = (new File($db))->forRecord($module, $record_id)
 */
?>
<div class="card mb-3" dir="rtl">
  <div class="card-header">קבצים מצורפים</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped table-hover m-0 align-middle">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>סוג</th>
            <th>שם מקורי</th>
            <th>גודל</th>
            <th>הועלה</th>
            <th>הערות</th>
            <th class="text-end">פעולות</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($files)): ?>
            <tr><td colspan="7" class="text-center text-muted">אין קבצים</td></tr>
          <?php else: ?>
            <?php foreach ($files as $f): ?>
              <tr>
                <td><?= (int)$f['file_id'] ?></td>
                <td><?= e($f['type_name']) ?></td>
                <td><?= e($f['original_name']) ?></td>
                <td><?= number_format((int)$f['size_bytes']) ?> bytes</td>
                <td><?= e($f['uploaded_at']) ?></td>
                <td><?= e($f['notes'] ?? '') ?></td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-secondary" href="index.php?r=files/download&id=<?= (int)$f['file_id'] ?>" title="הורדה">⬇</a>
                  <form method="post" action="index.php?r=files/delete" class="d-inline" onsubmit="return confirm('למחוק את הקובץ?');">
                    <input type="hidden" name="id" value="<?= (int)$f['file_id'] ?>">
                    <button class="btn btn-sm btn-outline-danger" title="מחיקה">🗑</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>