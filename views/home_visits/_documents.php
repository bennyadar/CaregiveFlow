<?php /** @var array $item, $documents, $docTypes */ ?>
<div class="card mt-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong>קבצים מצורפים</strong>
    <small class="text-muted">לביקור #<?= e($item['id']) ?> של העובד #<?= e($item['employee_id']) ?></small>
  </div>
  <div class="card-body">
    <form method="post" enctype="multipart/form-data" class="row gy-2 gx-2 align-items-end" action="?r=home_visits/upload_document">
      <?php if (function_exists('csrf_field')) csrf_field(); ?>
      <input type="hidden" name="visit_id" value="<?= e($item['id']) ?>">
      <input type="hidden" name="employee_id" value="<?= e($item['employee_id']) ?>">

      <div class="col-md-3">
        <label class="form-label">סוג קובץ</label>
        <select name="doc_type" class="form-select">
          <option value="">בחר...</option>
          <?php foreach ($docTypes as $dt): ?>
            <option value="<?= e($dt['code']) ?>"><?= e($dt['name_he']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label">קובץ</label>
        <input type="file" name="file" class="form-control" required>
      </div>

      <div class="col-md-2">
        <label class="form-label">תאריך הנפקה</label>
        <input type="date" name="issued_at" class="form-control">
      </div>

      <div class="col-md-2">
        <label class="form-label">תאריך תפוגה</label>
        <input type="date" name="expires_at" class="form-control">
      </div>

      <div class="col-md-1 d-grid">
        <button class="btn btn-primary">העלה</button>
      </div>

      <div class="col-md-12">
        <label class="form-label mt-2">הערות</label>
        <textarea name="notes" class="form-control" rows="2" placeholder="הקלד הערות חופשיות (לא חובה)"></textarea>
      </div>
    </form>

    <div class="table-responsive mt-3">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th style="width:14%">סוג</th>
            <th>קובץ</th>
            <th style="width:12%">הנפקה</th>
            <th style="width:12%">תפוגה</th>
            <th>הערות</th>
            <th style="width: 120px;"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($documents ?? []) as $d): ?>
            <tr>
              <td><?= e($d['doc_type_name'] ?? ($d['doc_type'] ?? '')) ?></td>
              <td>
                <?php if (!empty($d['file_path'])): ?>
                  <a href="<?= e($d['file_path']) ?>" target="_blank"><?= e(basename($d['file_path'])) ?></a>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
              <td><?= e($d['issued_at'] ?? '—') ?></td>
              <td><?= e($d['expires_at'] ?? '—') ?></td>
              <td class="text-muted"><?= e($d['notes'] ?? '') ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-danger"
                   href="?r=home_visits/delete_document&doc_id=<?= e($d['id']) ?>&visit_id=<?= e($item['id']) ?>"
                   onclick="return confirm('למחוק מסמך?');">מחיקה</a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($documents)): ?>
            <tr><td colspan="6" class="text-center text-muted py-3">אין מסמכים</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
