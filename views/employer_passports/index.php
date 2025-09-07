<?php /**
 * רשימת דרכונים למעסיקים - View
 *
 * מציג טופס סינון + טבלה עם תוצאות, כולל חישובי סטטוס/ימים עד פקיעה
 * קלטים מה-Controller: $items, $employers, $status_codes, $type_codes, $country_codes
 * פרמטרים לפאגינציה: $page, $pages, $total
 */ ?>
<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="container" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="m-0">דרכונים (מעסיק)</h3>
        <a class="btn btn-primary" href="?r=employer_passports/create">+ דרכון חדש</a>
    </div>

    <!-- סינון -->
    <form class="row g-2 mb-3" method="get" action="">
        <input type="hidden" name="r" value="employer_passports">

        <!-- בחירת מעסיק -->
        <div class="col-md-3">
            <label class="form-label">מעסיק</label>
            <select name="employer_id" class="form-select">
                <option value="">— הכל —</option>
                <?php foreach ($employers as $r): ?>
                    <?php $selected = ((string)$r['id'] === (string)($_GET['employer_id'] ?? '')) ? 'selected' : ''; ?>
                    <option value="<?= e($r['id']) ?>" <?= $selected ?>>
                        <?= e($r['last_name'].' '.$r['first_name'].' ('.($r['id_number'] ?? $r['passport_number']).')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- סטטוס: קודים + "פג" מחושב -->
        <div class="col-md-3">
            <label class="form-label">סטטוס</label>
            <select name="status" class="form-select">
                <option value="">— הכל —</option>
                <?php foreach ($status_codes as $code=>$name): ?>
                    <?php $sel = ((string)$code === (string)($_GET['status'] ?? '')) ? 'selected' : ''; ?>
                    <option value="<?= e($code) ?>" <?= $sel ?>><?= e($name) ?></option>
                <?php endforeach; ?>
                <?php $selExp = ((string)($_GET['status'] ?? '') === 'expired') ? 'selected' : ''; ?>
                <option value="expired" <?= $selExp ?>>פג</option>
            </select>
        </div>

        <!-- טקסט חופשי -->
        <div class="col-md-3">
            <label class="form-label">חיפוש (מס׳ דרכון/הערות/מקום הוצאה)</label>
            <input type="text" name="q" class="form-control" value="<?= e($_GET['q'] ?? '') ?>">
        </div>

        <!-- עד תאריך פקיעה -->
        <div class="col-md-2">
            <label class="form-label">פקיעה עד</label>
            <input type="date" name="expires_until" class="form-control" value="<?= e($_GET['expires_until'] ?? '') ?>">
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
                    <th>מס׳ דרכון</th>
                    <th>לאום</th>
                    <th>סוג</th>
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
                    // חישובי עזר לתצוגה
                    $derived = EmployerPassportService::derivedStatusCode($row['status_code'] ?? null, $row['expiry_date'] ?? null);
                    $days    = EmployerPassportService::daysUntilExpiry($row['expiry_date'] ?? null);
                ?>
                <tr>
                    <td><?= e($row['id']) ?></td>
                    <td>
                        <?= e($row['last_name'].' '.$row['first_name']) ?><br>
                        <small class="text-muted"><?= e($row['id_number'] ?? $row['passport_number'] ?? '') ?></small>
                    </td>
                    <td><?= e($row['passport_number']) ?></td>
                    <td><?= e($row['country_name'] ?? ($row['country_code']!==null?($country_codes[(int)$row['country_code']]??''):'')) ?></td>
                    <td><?= e($row['type_name'] ?? ($row['passport_type_code']!==null?($type_codes[(int)$row['passport_type_code']]??''):'')) ?></td>
                    <td><?= e($row['issue_date']) ?></td>
                    <td><?= e($row['expiry_date']) ?></td>
                    <td>
                        <?php if ($derived === 'expired'): ?>
                            <span class="badge bg-danger">פג</span>
                        <?php else: ?>
                            <?= e($row['status_name'] ?? ($status_codes[(int)($row['status_code'] ?? 0)] ?? '')) ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (is_null($days)): ?>—
                        <?php elseif ($days < 0): ?><span class="text-danger"><?= e($days) ?></span>
                        <?php elseif ($days <= 30): ?><span class="text-warning"><?= e($days) ?></span>
                        <?php else: ?><?= e($days) ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-secondary" href="?r=employer_passports/view&id=<?= e($row['id']) ?>">צפייה</a>
                        <a class="btn btn-sm btn-outline-primary" href="?r=employer_passports/edit&id=<?= e($row['id']) ?>">עריכה</a>
                        <a class="btn btn-sm btn-outline-danger" href="?r=employer_passports/delete&id=<?= e($row['id']) ?>" onclick="return confirm('למחוק?');">מחיקה</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- פאגינציה -->
    <?php if (($pages ?? 1) > 1): ?>
        <nav>
            <ul class="pagination">
                <?php for ($p=1; $p <= $pages; $p++): ?>
                    <?php $active = ($p === ($page ?? 1)) ? 'active' : ''; ?>
                    <li class="page-item <?= $active ?>">
                        <a class="page-link" href="<?= e(update_query(['page'=>$p])) ?>"><?= e($p) ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
