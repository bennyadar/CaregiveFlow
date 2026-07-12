<?php /** @var array $item, $documents, $docTypes */ ?>
<div class="card mt-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong>קבצים מצורפים</strong>
    <small class="text-muted">לדרכון מעסיק #<?= e($item['id']) ?> של מעסיק #<?= e($item['employer_id']) ?></small>
  </div>
  <div class="card-body">
    <form method="post" enctype="multipart/form-data" class="row gy-2 gx-2 align-items-end" action="?r=employer_passports/upload_document">
      <?php if (function_exists('csrf_field')) csrf_field(); ?>
      <input type="hidden" name="passport_id" value="<?= e($item['id']) ?>">

      <div class="col-md-3">
        <label class="form-label">סוג קובץ</label>
        <select name="doc_type" class="form-select">
          <option value="employer_passport" selected>דרכון מעסיק</option>
          <?php foreach ($docTypes as $code => $name): if ($code==='employer_passport') continue; ?>
            <option value="<?= e($code) ?>"><?= e($name) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label">קובץ</label>
        <input type="file" name="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp" required>
      </div>

      <div class="col-md-2">
        <label class="form-label">תאריך הנפקה</label>
        <input type="date" name="issued_at" class="form-control">
      </div>

      <div class="col-md-2">
        <label class="form-label">תאריך תפוגה</label>
        <input type="date" name="expires_at" class="form-control">
      </div>

      <div class="col-12">
        <label class="form-label">הערות</label>
        <textarea name="notes" class="form-control" rows="2" placeholder="הקלד הערות חופשיות (לא חובה)"></textarea>
      </div>

      <div class="col-md-1 d-grid">
        <button class="btn btn-primary">העלה</button>
      </div>
    </form>

    <div class="table-responsive mt-3">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th style="width:14%">סוג</th>
            <th>קובץ</th>
            <th style="width:16%">תוקף</th>
            <th style="width:28%">הערות</th>
            <th style="width:12%" class="text-center">פעולות</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$documents): ?>
          <tr><td colspan="5" class="text-muted">לא הועלו קבצים עדיין.</td></tr>
        <?php else: foreach ($documents as $d): ?>
          <tr>
            <td><?= e($docTypes[$d['doc_type']] ?? $d['doc_type']) ?></td>
            <td>
              <?php if (!empty($d['file_path'])): ?>
                <a href="<?= e($d['file_path']) ?>" target="_blank">פתח</a>
                <div class="small text-muted"><?= e(basename($d['file_path'])) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($d['issued_at'] || $d['expires_at']): ?>
                <div class="small">הנפקה: <?= e($d['issued_at'] ?? '—') ?></div>
                <div class="small">תפוגה: <?= e($d['expires_at'] ?? '—') ?></div>
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
            <td><?= nl2br(e($d['notes'] ?? '')) ?></td>
            <td class="text-center">
              <form method="post" action="?r=employer_passports/delete_document" onsubmit="return confirm('למחוק את הקובץ?');">
                <?php if (function_exists('csrf_field')) csrf_field(); ?>
                <input type="hidden" name="doc_id" value="<?= e($d['id']) ?>">
                <input type="hidden" name="passport_id" value="<?= e($item['id']) ?>">
                <button class="btn btn-sm btn-outline-danger">מחיקה</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>