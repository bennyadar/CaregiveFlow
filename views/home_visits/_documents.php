<?php /**
 * Home Visits — Attached Documents partial (like other modules)
 *
 * Expected vars:
 * @var array $item        The home visit row (must include 'id')
 * @var array $documents   Array of existing documents with: id, file_name, file_type_name_he, created_at, size_kb
 */ ?>
<div class="mt-4" dir="rtl">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div><i class="bi bi-paperclip"></i> קבצים מצורפים</div>
      <div class="text-muted small">ביקור #<?= e($item['id']) ?></div>
    </div>

    <div class="card-body">
      <form class="row g-2 align-items-end" method="post" action="?r=home_visits/upload_document" enctype="multipart/form-data">
        <?php if (function_exists('csrf_field')) csrf_field(); ?>
        <input type="hidden" name="home_visit_id" value="<?= e($item['id']) ?>">
        <div class="col-sm-4">
          <label class="form-label">סוג מסמך</label>
          <select name="doc_type_code" class="form-select">
            <?php if (!empty($doc_types)) foreach ($doc_types as $dt): ?>
              <option value="<?= e($dt['doc_type_code']) ?>"><?= e($dt['name_he']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-6">
          <label class="form-label">בחר קובץ</label>
          <input type="file" name="file" class="form-control" required>
        </div>
        <div class="col-sm-2 d-grid">
          <button class="btn btn-outline-primary"><i class="bi bi-upload"></i> העלה</button>
        </div>
      </form>
    </div>

    <div class="table-responsive">
      <table class="table table-striped align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>שם קובץ</th>
            <th>סוג</th>
            <th>נוצר בתאריך</th>
            <th>גודל (KB)</th>
            <th class="text-center" style="width: 120px;">פעולות</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!empty($documents)): foreach ($documents as $d): ?>
          <tr>
            <td><?= e($d['id']) ?></td>
            <td><a href="?r=home_visits/download_document&id=<?= e($d['id']) ?>"><?= e($d['file_name']) ?></a></td>
            <td><?= e($d['file_type_name_he'] ?? '') ?></td>
            <td><?= e($d['created_at'] ?? '') ?></td>
            <td><?= e($d['size_kb'] ?? '') ?></td>
            <td class="text-center">
              <div class="btn-group" role="group">
                <a class="btn btn-sm btn-outline-secondary" href="?r=home_visits/download_document&id=<?= e($d['id']) ?>" title="הורדה"><i class="bi bi-download"></i></a>
                <form method="post" action="?r=home_visits/delete_document" onsubmit="return confirm('למחוק את הקובץ?');">
                  <?php if (function_exists('csrf_field')) csrf_field(); ?>
                  <input type="hidden" name="doc_id" value="<?= e($d['id']) ?>">
                  <input type="hidden" name="home_visit_id" value="<?= e($item['id']) ?>">
                  <button class="btn btn-sm btn-outline-danger" title="מחיקה"><i class="bi bi-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="6" class="text-center text-muted py-4">אין קבצים מצורפים.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
