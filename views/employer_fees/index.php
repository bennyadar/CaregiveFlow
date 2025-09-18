<?php /** @var array $items, $employers, $status_codes, $type_codes, $payment_codes; @var int $page,$pages,$total; */ ?>
<?php require __DIR__ . '/../layout/header.php'; ?>
<div class="container" dir="rtl">

    <!-- כותרת + כפתורים -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">דמי תאגיד</h1>
        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="?r=employer_fees/create<?= isset($_GET['employer_id']) ? '&employer_id='.urlencode((string)$_GET['employer_id']) : '' ?>">+ חיוב חדש</a>
            <?php if (!empty($_GET['employer_id'])): ?>
                <a class="btn btn-outline-secondary" href="?r=employers/show&id=<?= e($_GET['employer_id']) ?>">חזרה לכרטיס מעסיק</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- סינון -->
    <form class="row g-2 mb-3" method="get" action="">
        <input type="hidden" name="r" value="employer_fees">

        <div class="col-md-4">
            <label class="form-label">מעסיק</label>
            <select name="employer_id" class="form-select">
                <option value="">— הכל —</option>
                <?php foreach ($employers as $er): ?>
                    <?php $sel = ((string)$er['id'] === (string)($_GET['employer_id'] ?? '')) ? 'selected' : ''; ?>
                    <option value="<?= (int)$er['id'] ?>" <?= $sel ?>><?= e($er['last_name'].' '.$er['first_name']).' ('.e($er['id_number'] ?? '').')' ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label">סוג חיוב</label>
            <select name="fee_type_code" class="form-select">
                <option value="">— הכל —</option>
                <?php foreach ($type_codes as $code=>$name): ?>
                    <?php $sel = ((string)$code === (string)($_GET['fee_type_code'] ?? '')) ? 'selected' : ''; ?>
                    <option value="<?= e($code) ?>" <?= $sel ?>><?= e($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label">אמצעי תשלום</label>
            <select name="payment_method_code" class="form-select">
                <option value="">— הכל —</option>
                <?php foreach ($payment_codes as $code=>$name): ?>
                    <?php $sel = ((string)$code === (string)($_GET['payment_method_code'] ?? '')) ? 'selected' : ''; ?>
                    <option value="<?= e($code) ?>" <?= $sel ?>><?= e($name) ?></option>
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

        <div class="col-md-2">
            <label class="form-label">חודש (period_ym)</label>
            <input type="month" name="period_ym" class="form-control" value="<?= e($_GET['period_ym'] ?? '') ?>">
        </div>        

        <div class="col-md-3">
            <label class="form-label">חיפוש (אסמכתא/הערות)</label>
            <input type="text" name="q" class="form-control" value="<?= e($_GET['q'] ?? '') ?>" placeholder="מס׳ אסמכתא / טקסט חופשי">
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="unpaid" name="unpaid" value="1" <?= (($_GET['unpaid'] ?? '')==='1') ? 'checked' : '' ?>>
                <label class="form-check-label" for="unpaid">לא שולם בלבד</label>
            </div>
        </div>

        <div class="col-md-7 mt-4 text-end">
            <button class="btn btn-outline-primary">סנן</button>
            <a class="btn btn-outline-secondary" href="?r=employer_fees<?= !empty($_GET['employer_id']) ? '&employer_id='.urlencode((string)$_GET['employer_id']) : '' ?>">איפוס</a>
        </div>
    </form>

    <!-- טבלה -->
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>מעסיק</th>
                    <th>סוג</th>
                    <th>סכום</th>
                    <th>מועד חיוב</th>
                    <th>תקופת תשלום</th>
                    <th>שולם בפועל</th>
                    <th>סטטוס</th>
                    <th>פעולות</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $row): ?>
                <tr>
                    <td><?= (int)$row['id'] ?></td>
                    <td><?= e(($row['last_name'] ?? '').' '.($row['first_name'] ?? '')) ?> (<?= e($row['id_number'] ?? '') ?>)</td>
                    <td><?= e($row['fee_type_name'] ?? '') ?></td>
                    <td><?= e($row['currency_code']) ?> <?= number_format((float)$row['amount'], 2) ?></td>
                    <td><?= e($row['due_date'] ?? '') ?></td>
                    <td>
                        <?php if (!empty($row['payment_from_date']) || !empty($row['payment_to_date'])): ?>
                            <?= e($row['payment_from_date'] ?? '') ?> — <?= e($row['payment_to_date'] ?? '') ?>
                        <?php endif; ?>
                    </td>
                    <td><?= e($row['payment_date'] ?? '') ?></td>
                    <td><?= e($row['status_name'] ?? '') ?></td>
                    <td class="text-center">
                        <a class="btn btn-sm btn-outline-secondary" href="?r=employer_fees/view&id=<?= e($row['id']) ?>&employer_id=<?= e($filters['employer_id']) ?>">צפייה</a>
                        <a class="btn btn-sm btn-outline-primary" href="?r=employer_fees/edit&id=<?= e($row['id']) ?>&employer_id=<?= e($filters['employer_id']) ?>">עריכה</a>                        
                        <a class="btn btn-sm btn-outline-danger" href="?r=employer_fees/delete&id=<?= e($row['id']) ?>" onclick="return confirm('למחוק?');">מחיקה</a>                        
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- פאג'ינציה -->
    <div class="d-flex align-items-center justify-content-between mt-3">
        <div class="small text-muted">סה"כ <?= (int)$total ?> רשומות</div>
        <?php if (($pages ?? 1) > 1): ?>
            <nav>
                <ul class="pagination mb-0">
                    <?php for ($p = 1; $p <= $pages; $p++): ?>
                        <?php $active = ($p === ($page ?? 1)) ? 'active' : ''; ?>
                        <li class="page-item <?= $active ?>">
                            <a class="page-link" href="<?= e(update_query(['page' => $p])) ?>"><?= e($p) ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
