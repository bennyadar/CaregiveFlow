<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 m-0">פרטי לשכה</h1>
  <a class="btn btn-success" href="index.php?r=agency_settings/create">+ חדש</a>
</div>

<div class="table-responsive">
  <table class="table table-striped">
    <thead>
      <tr>
        <th>ID</th>
        <th>שם לשכה</th>
        <th>ח.פ</th>
        <th>מס׳ לשכה</th>
        <th>מס׳ רישיון</th>
        <th>שם בעל הרישיון</th>
        <th>ת.ז בעל הרישיון</th>
        <th>טלפון משרד</th>
        <th>נייד</th>
        <th class="text-end">פעולות</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= e($r['id']) ?></td>
        <td><?= e($r['agency_name']) ?></td>
        <td><?= e($r['CorporateNumber'] ?? '') ?></td>
        <td><?= e($r['bureau_number']) ?></td>
        <td><?= e($r['LicenseNumber'] ?? '') ?></td>
        <td><?= e($r['contact_person']) ?></td>
        <td><?= e($r['OwnerID']) ?></td>
        <td><?= e($r['phone']) ?></td>
        <td><?= e($r['CellNumber']) ?></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-secondary" href="index.php?r=agency_settings/show&id=<?= (int)$r['id'] ?>">צפייה</a>
          <a class="btn btn-sm btn-primary" href="index.php?r=agency_settings/edit&id=<?= (int)$r['id'] ?>">עריכה</a>
          <form method="post" action="index.php?r=agency_settings/destroy" class="d-inline" onsubmit="return confirm('למחוק?')">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn btn-sm btn-danger">מחיקה</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
