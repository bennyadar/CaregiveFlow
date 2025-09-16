<?php /** @var array $items, $employees; @var int $page,$pages,$total; */ ?>
<?php require __DIR__ . '/../layout/header.php'; ?>
<div class="container" dir="rtl">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">ויזות לעובד</h1>
        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="?r=visas/create&employee_id=<?= e($filters['employee_id']) ?>">+ ויזה חדשה</a>
            <a class="btn btn-outline-secondary" href="?r=employees/show&id=<?= e($filters['employee_id']) ?>">חזרה לכרטיס עובד</a>
        </div>
    </div>

    <!-- סינון -->
    <form class="row g-2 mb-3" method="get" action="">
        <input type="hidden" name="r" value="visas">

        <div class="col-md-3">
            <label class="form-label">עובד</label>
            <select name="employee_id" class="form-select">
                <option value="">— הכל —</option>
                <?php foreach ($employees as $emp): ?>
                    <?php $selected = ((string)$emp['id'] === (string)($_GET['employee_id'] ?? '')) ? 'selected' : ''; ?>
                    <option value="<?= e($emp['id']) ?>" <?= $selected ?>>
                        <?= e($emp['last_name'].' '.$emp['first_name'].' ('.$emp['passport_number'].')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label">סטטוס</label>
            <?php $statuses = ['requested'=>'ממתין','approved'=>'מאושר','denied'=>'נדחה','expired'=>'פג']; ?>
            <select name="status" class="form-select">
                <option value="">— הכל —</option>
                <?php foreach ($statuses as $k=>$v): ?>
                    <?php $sel = ((string)$k === (string)($_GET['status'] ?? '')) ? 'selected' : ''; ?>
                    <option value="<?= e($k) ?>" <?= $sel ?>><?= e($v) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label">חיפוש (מס׳ ויזה)</label>
            <input type="text" name="q" class="form-control" value="<?= e($_GET['q'] ?? '') ?>">
        </div>

        <div class="col-md-3">
            <label class="form-label">פקיעה עד תאריך</label>
            <input type="date" name="expires_until" class="form-control" value="<?= e($_GET['expires_until'] ?? '') ?>">
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-outline-secondary w-100">סינון</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>עובד</th>
                    <th>מס׳ ויזה</th>
                    <th>ת. בקשה</th>
                    <th>ת. הנפקה</th>
                    <th>ת. פקיעה</th>
                    <th>סטטוס</th>
                    <th>נותרו ימים</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $row): ?>
                <?php 
                    $derived = VisaService::derivedStatus($row['status'], $row['expiry_date']);
                    $days = VisaService::daysUntilExpiry($row['expiry_date']);
                ?>
                <tr>
                    <td><?= e($row['id']) ?></td>
                    <td>
                        <?= e($row['last_name'].' '.$row['first_name']) ?><br>
                        <small class="text-muted">פס׳: <?= e($row['passport_number']) ?></small>
                    </td>
                    <td><?= e($row['visa_number']) ?></td>
                    <td><?= e($row['request_date']) ?></td>
                    <td><?= e($row['issue_date']) ?></td>
                    <td><?= e($row['expiry_date']) ?></td>
                    <td>
                        <?php
                        $labels = ['requested'=>'ממתין','approved'=>'מאושר','denied'=>'נדחה','expired'=>'פג'];
                        echo e($labels[$derived] ?? $derived);
                        ?>
                    </td>
                    <td><?= is_null($days)?'—':e($days) ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-secondary" href="?r=visas/view&id=<?= e($row['id']) ?>&employee_id=<?= e($filters['employee_id']) ?>">צפייה</a>
                        <a class="btn btn-sm btn-outline-primary" href="?r=visas/edit&id=<?= e($row['id']) ?>&employee_id=<?= e($filters['employee_id']) ?>">עריכה</a>
                        <a class="btn btn-sm btn-outline-danger" href="?r=visas/delete&id=<?= e($row['id']) ?>" onclick="return confirm('למחוק?');">מחיקה</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
        <?php /* אם יש לכם רכיב פאג׳ינציה משותף: require __DIR__ . '/../layout/pagination.php'; */ ?>
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