<?php
/**
 * צפייה בפרטי היתר העסקה (מעסיק)
 * קלטים מה-Controller: $item, $emp, $status_codes, $type_codes, $derived_status_code, $days_left
 * $item כולל: id, employer_id, permit_number, permit_type_code, request_date, issue_date, expiry_date, status_code, notes
 * $emp כולל: first_name, last_name, id_number, passport_number (תקציר מעסיק)
 */
?>
<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="container" dir="rtl">
    <!-- כותרת + פעולות -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="m-0">פרטי היתר #<?= e($item['id']) ?></h3>
        <div>
            <a class="btn btn-outline-secondary" href="?r=employment_permits/index&employer_id=<?= e($item['id']) ?>">חזרה לרשימה</a>
            <a class="btn btn-primary" href="?r=employment_permits/edit&id=<?= e($item['id']) ?>">עריכה</a>
            <a class="btn btn-outline-secondary" href="?r=employment_permits/index&employer_id=<?= e($_GET['employer_id']) ?>">חזרה לרשימה</a>
            <a class="btn btn-outline-secondary" href="?r=employers/show&id=<?= e($emp['id']) ?>">חזרה לכרטיס מעסיק</a>
        </div>
    </div>

    <div class="row g-3">
        <!-- כרטיס: מעסיק -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">מעסיק</div>
                <div class="card-body">
                    <?php if (!empty($emp)): ?>
                        <div class="mb-1"><strong>שם:</strong> <?= e($emp['last_name'] . ' ' . $emp['first_name']) ?></div>
                        <div class="mb-0"><strong>מזהה:</strong> <?= e($emp['id_number'] ?? $emp['passport_number'] ?? '') ?></div>
                    <?php else: ?>
                        <div class="text-muted">—</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- כרטיס: מצב -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">מצב</div>
                <div class="card-body">
                    <div class="mb-1">
                        <strong>סטטוס:</strong>
                        <?= e($status_name ?? '—') ?>
                    </div>

                    <div class="mb-1">
                        <strong>סטטוס מחושב:</strong>
                        <?php if ($derived_status_code === 'expired'): ?>
                            <span class="badge bg-danger">פג</span>
                        <?php else: ?>
                            <span class="badge bg-success">בתוקף</span>
                        <?php endif; ?>
                    </div>

                    <div class="mb-0">
                        <strong>ימים עד פקיעה:</strong>
                        <?php if (is_null($days_left)): ?>
                            —
                        <?php elseif ($days_left < 0): ?>
                            <span class="text-danger"><?= e($days_left) ?></span>
                        <?php elseif ($days_left <= 30): ?>
                            <span class="text-warning"><?= e($days_left) ?></span>
                        <?php else: ?>
                            <?= e($days_left) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- כרטיס: פרטי היתר -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">פרטי היתר</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-1"><strong>מס׳ היתר:</strong> <?= e($item['permit_number']) ?></div>
                            <div class="mb-0">
                                <strong>סוג היתר:</strong>
                                <?= e($type_name ?? '—') ?>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-1"><strong>תאריך בקשה:</strong> <?= e($item['request_date'] ?? '') ?></div>
                            <div class="mb-0"><strong>תאריך הנפקה:</strong> <?= e($item['issue_date'] ?? '') ?></div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-1"><strong>תאריך פקיעה:</strong> <?= e($item['expiry_date'] ?? '') ?></div>
                        </div>

                        <div class="col-12">
                            <div class="mb-0"><strong>הערות:</strong><br><?= nl2br(e($item['notes'] ?? '')) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
