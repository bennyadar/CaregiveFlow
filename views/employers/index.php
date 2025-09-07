<?php require __DIR__ . '/../layout/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 m-0">מעסיקים</h1>
  <a class="btn btn-success" href="index.php?r=employers/create">+ מעסיק חדש</a>
</div>
<form class="row g-2 mb-3" method="get">
  <input type="hidden" name="r" value="employers/index">
  <div class="col-auto">
    <input class="form-control" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="חיפוש: ת"ז / שם">
  </div>
  <div class="col-auto">
    <button class="btn btn-outline-primary">חפש</button>
  </div>
</form>
<div class="table-responsive">
<table class="table table-striped table-hover align-middle">
  <thead><tr>
    <th>#</th><th>שם</th><th>ת"ז</th><th>טלפון</th><th>אימייל</th><th></th>
  </tr></thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= e($r['last_name'] . ' ' . $r['first_name']) ?></td>
        <td><?= e($r['id_number']) ?></td>
        <td><?= e($r['phone']) ?></td>
        <td><?= e($r['email']) ?></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-secondary" href="index.php?r=employers/show&id=<?= (int)$r['id'] ?>">צפייה</a>
          <a class="btn btn-sm btn-primary" href="index.php?r=employers/edit&id=<?= (int)$r['id'] ?>">עריכה</a>
          <?php if ((current_user()['role'] ?? '') === 'admin'): ?>
          <form class="d-inline" method="post" action="index.php?r=employers/delete" onsubmit="return confirm('למחוק את המעסיק?');">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
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
