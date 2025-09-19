<?php /**
 * צפייה בפרטי דרכון למעסיק
 * קלטים מה-Controller: $item, $emp, $status_name, $type_name, $country_name,
 *                       $derived_status_code, $days_left
 */ ?>
<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="container" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="m-0">פרטי דרכון (מעסיק)</h3>
        <div>
            <a class="btn btn-primary" href="?r=employer_passports/edit&id=<?= e($item['id']) ?>">עריכה</a>
            <a class="btn btn-outline-secondary" href="?r=employer_passports/index&employer_id=<?= e($_GET['employer_id']) ?>">חזרה לרשימה</a>
            <a class="btn btn-outline-secondary" href="?r=employers/show&id=<?= e($emp['id']) ?>">חזרה לכרטיס מעסיק</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">מעסיק</div>
                <div class="card-body">
                    <?php if ($emp): ?>
                        <div><strong>שם:</strong> <?= e($emp['last_name'].' '.$emp['first_name']) ?></div>
                        <div><strong>מזהה:</strong> <?= e($emp['id_number'] ?? $emp['passport_number'] ?? '') ?></div>
                    <?php else: ?>
                        <div class="text-muted">—</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">מצב</div>
                <div class="card-body">
                    <div class="mb-1"><strong>סטטוס (קוד):</strong> <?= e($status_name ?? '—') ?></div>
                    <div class="mb-1"><strong>סטטוס מחושב:</strong>
                        <?php if ($derived_status_code === 'expired'): ?>
                            <span class="badge bg-danger">פג</span>
                        <?php else: ?>
                            <span class="badge bg-success">בתוקף</span>
                        <?php endif; ?>
                    </div>
                    <div class="mb-1"><strong>ימים עד פקיעה:</strong>
                        <?php if (is_null($days_left)): ?>—
                        <?php elseif ($days_left < 0): ?><span class="text-danger"><?= e($days_left) ?></span>
                        <?php elseif ($days_left <= 30): ?><span class="text-warning"><?= e($days_left) ?></span>
                        <?php else: ?><?= e($days_left) ?>
                        <?php endif; ?>
                    </div>
                    <div class="mb-0"><strong>ראשי:</strong> <?= !empty($item['is_primary']) ? 'כן' : 'לא' ?></div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">פרטי דרכון</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><strong>מס׳ דרכון:</strong> <?= e($item['passport_number'] ?? '') ?></div>
                        <div class="col-md-4"><strong>לאום:</strong> <?= e($country_name ?? '') ?></div>
                        <div class="col-md-4"><strong>סוג דרכון:</strong> <?= e($type_name ?? '') ?></div>
                        <div class="col-md-4"><strong>תאריך הנפקה:</strong> <?= e($item['issue_date'] ?? '') ?></div>
                        <div class="col-md-4"><strong>תאריך פקיעה:</strong> <?= e($item['expiry_date'] ?? '') ?></div>
                        <div class="col-md-4"><strong>מקום הוצאה:</strong> <?= e($item['issue_place'] ?? '') ?></div>
                        <div class="col-12"><strong>הערות:</strong><br><?= nl2br(e($item['notes'] ?? '')) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php require __DIR__ . '/_documents.php'; ?>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
