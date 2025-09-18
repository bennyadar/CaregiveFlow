<?php /** @var array $items; @var int $page,$pages,$total; */ ?>
<?php require __DIR__.'/../layout/header.php'; ?>
<div class="container" dir="rtl">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5 mb-0">מעסיקים שלא שילמו לחודש <?= e($_GET['period_ym'] ?? '') ?></h1>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="?r=employer_fees<?= isset($_GET['period_ym']) ? '&period_ym='.urlencode((string)$_GET['period_ym']). '&employer_id='.urlencode((string)$_GET['employer_id']) : '' ?>">חזרה לרשימת חיובים</a>    
    </div>
  </div>

  <div class="alert alert-info">
    מוצגים מעסיקים שאין עבורם <strong>רשומת תשלום</strong> שחופפת לתקופה הנבחרת (לפי <code>period_ym</code> או לפי <code>payment_from_date/payment_to_date</code>), עם <code>payment_date</code> לא-ריק.
  </div>

  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th style="width:70px">#</th>
          <th>שם משפחה</th>
          <th>שם פרטי</th>
          <th>ת"ז</th>
          <th style="width:160px" class="text-center">פעולות</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $er): ?>
        <tr>
          <td><?= (int)$er['id'] ?></td>
          <td><?= e($er['last_name'] ?? '') ?></td>
          <td><?= e($er['first_name'] ?? '') ?></td>
          <td><?= e($er['id_number'] ?? '') ?></td>
          <td class="text-center">
            <a class="btn btn-sm btn-outline-primary" href="?r=employer_fees&employer_id=<?= (int)$er['id'] ?>&period_ym=<?= e($_GET['period_ym'] ?? '') ?>">הצג חיובים</a>
            <a class="btn btn-sm btn-outline-secondary" href="?r=employers/show&id=<?= (int)$er['id'] ?>">כרטיס מעסיק</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="d-flex align-items-center justify-content-between mt-3">
    <div class="small text-muted">סה"כ <?= (int)$total ?> מעסיקים</div>
    <?php if (($pages ?? 1) > 1): ?>
      <nav>
        <ul class="pagination mb-0">
          <?php for ($p=1; $p<=$pages; $p++): $active = ($p === ($page ?? 1)) ? 'active' : ''; ?>
            <li class="page-item <?= $active ?>">
              <a class="page-link" href="<?= e(update_query(['page'=>$p])) ?>"><?= e($p) ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__.'/../layout/footer.php'; ?>
