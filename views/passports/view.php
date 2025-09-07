<?php /** @var array $item, $emp; @var int|string|null $derived_...eft; @var array $status_codes, $type_codes, $country_codes */ ?>
<?php require __DIR__ . '/../layout/header.php'; ?>
<div class="container" dir="rtl" style="max-width: 860px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="m-0">פרטי דרכון #<?= e($item['id']) ?></h3>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="?r=passports/edit&id=<?= e($item['id']) ?>">עריכה</a>
            <a class="btn btn-outline-secondary" href="?r=passports">חזרה לרשימה</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div><strong>עובד:</strong> <?= e($emp ? ($emp['first_name'].' '.$emp['last_name']) : '—') ?></div>
                    <div><strong>מס׳ דרכון:</strong> <?= e($item['passport_number']) ?></div>
                    <div><strong>סוג דרכון:</strong> <?= e($passport_type_code['name_he'] ?? '—') ?></div>
                </div>
                <div class="col-md-6">
                    <div><strong>מדינת הנפקה:</strong> <?= e($country_codes[(int)($item['country_code'] ?? -1)] ?? '—') ?></div>
                    <div><strong>תאריך הנפקה:</strong> <?= e($item['issue_date']) ?></div>
                    <div><strong>תאריך פקיעה:</strong> <?= e($item['expiry_date']) ?></div>
                    <div><strong>סטטוס:</strong> <?= ($derived_status_codes[(int)($item['status_code'] ?? -1)] ?? '—') ?></div>
                    <!-- הוסף: שדות ראשי -->
                    <div><strong>ראשי:</strong> <?= !empty($item['is_primary']) ? '✔' : '' ?></div>
                    <div><strong>ID דרכון ראשי:</strong> <?= e($item['primary_employee_id'] ?? '') ?: '—' ?></div>
                    <div><strong>נותרו ימים:</strong> <?= is_null($days_left)?'—':e($days_left) ?></div>
                </div>
                <div class="col-12">
                    <div><strong>מקום הוצאה:</strong> <?= e($item['issue_place']) ?></div>
                    <div class="mt-2"><strong>הערות:</strong><br><?= nl2br(e($item['notes'])) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
