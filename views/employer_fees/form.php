<?php /** @var array $data, $errors, $employers, $status_codes, $type_codes, $payment_codes */ ?>
<?php require __DIR__ . '/../layout/header.php'; ?>
<div class="container" dir="rtl" style="max-width: 860px;">
    <h3 class="mb-3"><?= isset($data['id']) ? 'עריכת חיוב' : 'חיוב חדש' ?></h3>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger"><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <form method="post">
        <?php if (function_exists('csrf_field')) csrf_field(); ?>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">מעסיק</label>
                <select name="employer_id" class="form-select <?= isset($errors['employer_id'])?'is-invalid':'' ?>">
                    <option value="">— בחר —</option>
                    <?php foreach ($employers as $r): ?>
                        <?php $sel = ((string)$r['id'] === (string)($data['employer_id'] ?? '')) ? 'selected' : ''; ?>
                        <option value="<?= e($r['id']) ?>" <?= $sel ?>><?= e($r['last_name'].' '.$r['first_name'].' ('.$r['id_number'].')') ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['employer_id'])): ?><div class="invalid-feedback"><?= e($errors['employer_id']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-3">
                <label class="form-label">חודש חיוב</label>
                <input type="month" name="period_ym" value="<?= e($data['period_ym'] ?? '') ?>" class="form-control <?= isset($errors['period_ym'])?'is-invalid':'' ?>">
                <?php if (isset($errors['period_ym'])): ?><div class="invalid-feedback"><?= e($errors['period_ym']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-3">
                <label class="form-label">סוג חיוב</label>
                <select name="fee_type_code" class="form-select <?= isset($errors['fee_type_code'])?'is-invalid':'' ?>">
                    <option value="">— בחר —</option>
                    <?php foreach ($type_codes as $code=>$name): ?>
                        <?php $sel = ((string)$code === (string)($data['fee_type_code'] ?? '')) ? 'selected' : ''; ?>
                        <option value="<?= e($code) ?>" <?= $sel ?>><?= e($name) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['fee_type_code'])): ?><div class="invalid-feedback"><?= e($errors['fee_type_code']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label class="form-label">סכום</label>
                <div class="input-group">
                    <input type="number" step="0.01" name="amount" value="<?= e($data['amount'] ?? '') ?>" class="form-control <?= isset($errors['amount'])?'is-invalid':'' ?>">
                    <select name="currency_code" class="form-select" style="max-width: 120px">
                        <?php $curr = $data['currency_code'] ?? 'ILS'; ?>
                        <?php foreach (['ILS'=>'₪ ILS','USD'=>'$ USD','EUR'=>'€ EUR'] as $c=>$lbl): ?>
                            <option value="<?= e($c) ?>" <?= ($curr===$c?'selected':'') ?>><?= e($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (isset($errors['amount'])): ?><div class="invalid-feedback"><?= e($errors['amount']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label class="form-label">מועד חיוב</label>
                <input type="date" name="due_date" value="<?= e($data['due_date'] ?? '') ?>" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">תאריך תשלום</label>
                <input type="date" name="payment_date" value="<?= e($data['payment_date'] ?? '') ?>" class="form-control <?= isset($errors['payment_date'])?'is-invalid':'' ?>">
                <?php if (isset($errors['payment_date'])): ?><div class="invalid-feedback"><?= e($errors['payment_date']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label class="form-label">סטטוס</label>
                <select name="status_code" class="form-select <?= isset($errors['status_code'])?'is-invalid':'' ?>">
                    <option value="">— בחר —</option>
                    <?php foreach ($status_codes as $code=>$name): ?>
                        <?php $sel = ((string)$code === (string)($data['status_code'] ?? '')) ? 'selected' : ''; ?>
                        <option value="<?= e($code) ?>" <?= $sel ?>><?= e($name) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['status_code'])): ?><div class="invalid-feedback"><?= e($errors['status_code']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label class="form-label">אמצעי תשלום</label>
                <select name="payment_method_code" class="form-select <?= isset($errors['payment_method_code'])?'is-invalid':'' ?>">
                    <option value="">— בחר —</option>
                    <?php foreach ($payment_codes as $code=>$name): ?>
                        <?php $sel = ((string)$code === (string)($data['payment_method_code'] ?? '')) ? 'selected' : ''; ?>
                        <option value="<?= e($code) ?>" <?= $sel ?>><?= e($name) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['payment_method_code'])): ?><div class="invalid-feedback"><?= e($errors['payment_method_code']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label class="form-label">אסמכתא/קבלה</label>
                <input type="text" name="reference_number" value="<?= e($data['reference_number'] ?? '') ?>" class="form-control">
            </div>

            <div class="col-12">
                <label class="form-label">הערות</label>
                <textarea name="notes" rows="3" class="form-control"><?= e($data['notes'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-success">שמירה</button>
            <a class="btn btn-outline-secondary" href="?r=employer_fees">חזרה לרשימה</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
