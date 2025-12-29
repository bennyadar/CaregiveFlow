<?php /** @var array $rows, $employees, $status_codes, $type_codes, $stage_codes, $placement_type_codes; @var int $page,$pages,$total; */ ?>
<?php require __DIR__ . '/../layout/header.php'; ?>
<div class="container" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">ביקורי בית</h1>
        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="?r=home_visits/create&employee_id=<?= e($filters['employee_id'] ?? '') ?>">+ ביקור חדש</a>
            <?php if (!empty($filters['employee_id'])): ?>
                <a class="btn btn-outline-secondary" href="?r=employees/show&id=<?= e($filters['employee_id']) ?>">חזרה לכרטיס עובד</a>
            <?php endif; ?>
        </div>
    </div>

    <form class="row g-2 mb-3" method="get" action="">
        <input type="hidden" name="r" value="home_visits">

        <div class="col-md-3">
            <label class="form-label">עובד</label>
            <select name="employee_id" class="form-select">
                <option value="">— הכל —</option>
                <?php foreach ($employees as $emp): ?>
                    <?php $selected = ((string)$emp['id'] === (string)($_GET['employee_id'] ?? '')) ? 'selected' : ''; ?>
                    <option value="<?= e($emp['id']) ?>" <?= $selected ?>>
                        <?= e($emp['last_name'] . ' ' . $emp['first_name'] . ' (' . $emp['passport_number'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label">סטטוס</label>
            <select name="status" class="form-select">
                <option value="">— הכל —</option>
                <?php foreach ($status_codes as $code => $name): ?>
                    <?php $sel = ((string)$code === (string)($_GET['status'] ?? '')) ? 'selected' : ''; ?>
                    <option value="<?= e($code) ?>" <?= $sel ?>><?= e($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label">סוג ביקור</label>
            <select name="type" class="form-select">
                <option value="">— הכל —</option>
                <?php foreach ($type_codes as $code => $name): ?>
                    <?php $sel = ((string)$code === (string)($_GET['type'] ?? '')) ? 'selected' : ''; ?>
                    <option value="<?= e($code) ?>" <?= $sel ?>><?= e($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label">שלב</label>
            <select name="stage" class="form-select">
                <option value="">— הכל —</option>
                <?php foreach ($stage_codes as $code => $name): ?>
                    <?php $sel = ((string)$code === (string)($_GET['stage'] ?? '')) ? 'selected' : ''; ?>
                    <option value="<?= e($code) ?>" <?= $sel ?>><?= e($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">חיפוש</label>
            <input type="text" name="q" class="form-control" value="<?= e($_GET['q'] ?? '') ?>" placeholder="שם עובד / סיכום / ממצאים">
        </div>

        <div class="col-md-2">
            <label class="form-label">מתאריך</label>
            <input type="date" name="date_from" class="form-control" value="<?= e($_GET['date_from'] ?? '') ?>">
        </div>

        <div class="col-md-2">
            <label class="form-label">עד תאריך</label>
            <input type="date" name="date_to" class="form-control" value="<?= e($_GET['date_to'] ?? '') ?>">
        </div>

        <div class="col-md-2">
            <label class="form-label">יעד מעקב עד</label>
            <input type="date" name="due_until" class="form-control" value="<?= e($_GET['due_until'] ?? '') ?>">
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <div class="form-check">
                <?php $checked = ((string)($_GET['followup_only'] ?? '') === '1') ? 'checked' : ''; ?>
                <input class="form-check-input" type="checkbox" name="followup_only" value="1" id="followup_only" <?= $checked ?>>
                <label class="form-check-label" for="followup_only">מעקב בלבד</label>
            </div>
        </div>

        <div class="col-md-2 d-grid align-items-end">
            <button class="btn btn-outline-primary">סנן</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>תאריך ביקור</th>
                    <th>עובד</th>
                    <th>סוג</th>
                    <th>סטטוס</th>
                    <th>שלב</th>
                    <th>מעקב</th>
                    <th>יעד</th>
                    <th style="width: 200px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['id']) ?></td>
                        <td><?= e($row['visit_date']) ?></td>
                        <td><?= e(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?></td>
                        <td><?= e($row['type_name'] ?? '—') ?></td>
                        <td><?= e($row['status_name'] ?? '—') ?></td>
                        <td><?= e($row['stage_name'] ?? '—') ?></td>
                        <td><?= !empty($row['followup_required']) ? 'כן' : 'לא' ?></td>
                        <td><?= e($row['next_visit_due'] ?? '—') ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="?r=home_visits/view&id=<?= e($row['id']) ?>">צפייה</a>
                            <a class="btn btn-sm btn-outline-primary" href="?r=home_visits/edit&id=<?= e($row['id']) ?>">עריכה</a>
                            <a class="btn btn-sm btn-outline-danger" href="?r=home_visits/delete&id=<?= e($row['id']) ?>" onclick="return confirm('למחוק?');">מחיקה</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">אין תוצאות</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="mt-3">
            <ul class="pagination">
                <?php for ($p = 1; $p <= $pages; $p++): ?>
                    <?php
                        $qs = $_GET;
                        $qs['page'] = $p;
                        $url = '?' . http_build_query($qs);
                        $active = ($p === (int)($_GET['page'] ?? 1)) ? 'active' : '';
                    ?>
                    <li class="page-item <?= $active ?>"><a class="page-link" href="<?= e($url) ?>"><?= e($p) ?></a></li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
