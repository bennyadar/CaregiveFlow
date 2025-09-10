<?php /** @var array $item, $emp; @var int|string|null $derived_status_code; @var int|null $days_left; @var array $status_codes, $type_codes */ ?>
<?php require __DIR__ . '/../layout/header.php'; ?>
<div class="container" dir="rtl" style="max-width: 860px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="m-0">פרטי ביטוח #<?= e($item['id']) ?></h3>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="?r=insurances/edit&id=<?= e($item['id']) ?>&employee_id=<?= e($_GET['employee_id']) ?>">עריכה</a>
            <a class="btn btn-outline-secondary" href="?r=insurances&employee_id=<?= e($_GET['employee_id']) ?>">חזרה לרשימת ביטוחים</a>
            <a class="btn btn-outline-secondary" href="?r=employees/show&id=<?= e($emp['id']) ?>">חזרה לכרטיס עובד</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div><strong>עובד:</strong> <?= e($emp ? ($emp['last_name'].' '.$emp['first_name'].' ('.$emp['passport_number'].')') : '—') ?></div>
                    <div><strong>מס׳ פוליסה:</strong> <?= e($item['policy_number']) ?></div>
                    <div><strong>מבטח:</strong> <?= e($item['insurer_name']) ?></div>
                    <div><strong>סוג ביטוח:</strong> <?= e($type_codes[(int)($item['insurance_type_code'] ?? -1)] ?? '—') ?></div>
                </div>
                <div class="col-md-6">
                    <div><strong>תאריך בקשה:</strong> <?= e($item['request_date']) ?></div>
                    <div><strong>תאריך הנפקה:</strong> <?= e($item['issue_date']) ?></div>
                    <div><strong>תאריך פקיעה:</strong> <?= e($item['expiry_date']) ?></div>
                    <div><strong>סטטוס:</strong> <?= $derived_status_code === 'expired' ? 'פג' : e($status_codes[(int)($item['status_code'] ?? -1)] ?? '—') ?></div>
                    <div><strong>נותרו ימים:</strong> <?= is_null($days_left)?'—':e($days_left) ?></div>
                </div>
                <div class="col-12">
                    <div><strong>הערות:</strong><br><?= nl2br(e($item['notes'])) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
