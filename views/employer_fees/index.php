<?php /** @var array $items, $employers, $status_codes, $type_codes, $payment_codes; @var int $page,$pages,$total; */ ?>
<?php require __DIR__ . '/../layout/header.php'; ?>
<div class="container" dir="rtl">

    <!-- כותרת + כפתורים -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">דמי תאגיד</h1>
        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="?r=employer_fees/create">+ חיוב חדש</a>
            <a class="btn btn-outline-secondary" href="?r=employers/show&id=<?= e($_GET['employer_id']) ?>">חזרה לכרטיס מעסיק</a>
        </div>
    </div>

    <!-- סינון -->
    <form class="row g-2 mb-3" method="get" action="">
        <input type="hidden" name="r" value="employer_fees">

        <div class="col-md-3">
            <label class="form-label">מעסיק</label>
            <select name="employer_id" class="form-select">
                <option value="">— הכל —</option>
                <?php foreach ($employers as $r): ?>
                    <?php $selected = ((string)$r['id'] === (string)($_GET['employer_id'] ?? '')) ? 'selected' : ''; ?>
                    <option value="<?= e($r['id']) ?>" <?= $selected ?>>
                        <?= e($r['last_name'].' '.$r['first_name'].' ('.$r['id_number'].')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label">סטטוס</label>
            <select name="status" class="form-select">
                <option value="">— הכל —</option>
                <?php foreach ($status_codes as $code=>$name): ?>
                    <?php $sel = ((string)$code === (string)($_GET['status'] ?? '')) ? 'selected' : ''; ?>
                    <option value="<?= e($code) ?>" <?= $sel ?>><?= e($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">חיפוש (אסמכתא/הערות)</label>
            <input type="text" name="q" class="form-control" value="<?= e($_GET['q'] ?? '') ?>">
        </div>

        <div class="col-md-2">
            <label class="form-label">מתאריך חודש</label>
            <input type="month" name="period_from" class="form-control" value="<?= e($_GET['period_from'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">עד חודש</label>
            <input type="month" name="period_to" class="form-control" value="<?= e($_GET['period_to'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">שולם עד</label>
            <input type="date" name="paid_until" class="form-control" value="<?= e($_GET['paid_until'] ?? '') ?>">
        </div>

        <div class="col-md-1 d-flex align-items-end">
            <button class="btn btn-outline-secondary w-100">סינון</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>מעסיק</th>
                    <th>חודש</th>
                    <th>סוג</th>
                    <th>סכום</th>
                    <th>מועד חיוב</th>
                    <th>תאריך תשלום</th>
                    <th>סטטוס</th>
                    <th>אמצעי תשלום</th>
                    <th>אסמכתא</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $row): ?>
                <tr>
                    <td><?= e($row['id']) ?></td>
                    <td><?= e($row['last_name'].' '.$row['first_name']) ?><br><small class="text-muted">ת.ז <?= e($row['id_number']) ?></small></td>
                    <td><?= e($row['period_ym']) ?></td>
                    <td><?= e($row['fee_type_name'] ?? ($row['fee_type_code']!==null?($type_codes[(int)$row['fee_type_code']]??''):'')) ?></td>
                    <td><?= e(number_format((float)$row['amount'], 2)) ?> <?= e($row['currency_code']) ?></td>
                    <td><?= e($row['due_date']) ?></td>
                    <td><?= e($row['payment_date']) ?></td>
                    <td><?= e($row['status_name'] ?? ($status_codes[(int)$row['status_code']] ?? '')) ?></td>
                    <td><?= e($row['payment_method_name'] ?? ($row['payment_method_code']!==null?($payment_codes[(int)$row['payment_method_code']]??''):'')) ?></td>
                    <td><?= e($row['reference_number']) ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-secondary" href="?r=employer_fees/view&id=<?= e($row['id']) ?>">צפייה</a>
                        <a class="btn btn-sm btn-outline-primary" href="?r=employer_fees/edit&id=<?= e($row['id']) ?>">עריכה</a>
                        <a class="btn btn-sm btn-outline-danger" href="?r=employer_fees/delete&id=<?= e($row['id']) ?>" onclick="return confirm('למחוק?');">מחיקה</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
        <nav>
            <ul class="pagination">
                <?php for ($p=1; $p <= $pages; $p++): ?>
                    <?php $active = ($p === $page) ? 'active' : ''; ?>
                    <li class="page-item <?= $active ?>">
                        <a class="page-link" href="<?= e(update_query(['page'=>$p])) ?>"><?= e($p) ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
