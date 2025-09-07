<?php /** @var array $item, $emp; @var array $status_codes, $type_codes, $payment_codes */ ?>
<?php require __DIR__ . '/../layout/header.php'; ?>
<div class="container" dir="rtl" style="max-width: 860px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="m-0">פרטי חיוב #<?= e($item['id']) ?></h3>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="?r=employer_fees/edit&id=<?= e($item['id']) ?>">עריכה</a>
            <a class="btn btn-outline-secondary" href="?r=employer_fees">חזרה לרשימה</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div><strong>מעסיק:</strong> <?= e($emp ? ($emp['last_name'].' '.$emp['first_name'].' ('.$emp['id_number'].')') : '—') ?></div>
                    <div><strong>חודש:</strong> <?= e($item['period_ym']) ?></div>
                    <div><strong>סוג:</strong> <?= e($type_codes[(int)($item['fee_type_code'] ?? -1)] ?? '—') ?></div>
                    <div><strong>סכום:</strong> <?= e(number_format((float)$item['amount'], 2)) ?> <?= e($item['currency_code']) ?></div>
                </div>
                <div class="col-md-6">
                    <div><strong>מועד חיוב:</strong> <?= e($item['due_date']) ?></div>
                    <div><strong>תאריך תשלום:</strong> <?= e($item['payment_date']) ?></div>
                    <div><strong>סטטוס:</strong> <?= e($status_codes[(int)($item['status_code'] ?? -1)] ?? '—') ?></div>
                    <div><strong>אמצעי תשלום:</strong> <?= e($payment_codes[(int)($item['payment_method_code'] ?? -1)] ?? '—') ?></div>
                </div>
                <div class="col-12">
                    <div><strong>אסמכתא/קבלה:</strong> <?= e($item['reference_number']) ?></div>
                    <div class="mt-2"><strong>הערות:</strong><br><?= nl2br(e($item['notes'])) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
