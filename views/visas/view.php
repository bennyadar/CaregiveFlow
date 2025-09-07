<?php /** @var array $item, $emp; @var string $derived_status; @var int|null $days_left */ ?>
<?php require __DIR__ . '/../layout/header.php'; ?>
<div class="container" dir="rtl" style="max-width: 860px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="m-0">פרטי ויזה #<?= e($item['id']) ?></h3>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="?r=visas/edit&id=<?= e($item['id']) ?>">עריכה</a>
            <a class="btn btn-outline-secondary" href="?r=visas">חזרה לרשימה</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div><strong>עובד:</strong> <?= e($emp ? ($emp['last_name'].' '.$emp['first_name'].' ('.$emp['passport_number'].')') : '—') ?></div>
                    <div><strong>מס׳ ויזה:</strong> <?= e($item['visa_number']) ?></div>
                    <div><strong>סטטוס:</strong> <?php $labels=['requested'=>'ממתין','approved'=>'מאושר','denied'=>'נדחה','expired'=>'פג']; echo e($labels[$derived_status] ?? $derived_status); ?></div>
                    <div><strong>נותרו ימים:</strong> <?= is_null($days_left)?'—':e($days_left) ?></div>
                </div>
                <div class="col-md-6">
                    <div><strong>תאריך בקשה:</strong> <?= e($item['request_date']) ?></div>
                    <div><strong>תאריך הנפקה:</strong> <?= e($item['issue_date']) ?></div>
                    <div><strong>תאריך פקיעה:</strong> <?= e($item['expiry_date']) ?></div>
                </div>
                <div class="col-12">
                    <div><strong>הערות:</strong><br><?= nl2br(e($item['notes'])) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>