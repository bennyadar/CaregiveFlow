<?php /** @var array $data, $errors, $employers, $status_codes, $type_codes */ ?>
<?php require __DIR__ . '/../layout/header.php'; ?>
<div class="container" dir="rtl" style="max-width: 860px;">
    <h3 class="mb-3"><?= isset($data['id']) ? 'עריכת היתר' : 'היתר חדש' ?></h3>

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
                        <option value="<?= e($r['id']) ?>" <?= $sel ?>><?= e($r['last_name'].' '.$r['first_name'].' ('.($r['id_number'] ?? $r['passport_number']).')') ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['employer_id'])): ?><div class="invalid-feedback"><?= e($errors['employer_id']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label">מס׳ היתר</label>
                <input type="text" name="permit_number" value="<?= e($data['permit_number'] ?? '') ?>" class="form-control <?= isset($errors['permit_number'])?'is-invalid':'' ?>">
                <?php if (isset($errors['permit_number'])): ?><div class="invalid-feedback"><?= e($errors['permit_number']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label">סוג היתר</label>
                <select name="permit_type_code" class="form-select <?= isset($errors['permit_type_code'])?'is-invalid':'' ?>">
                    <option value="">— בחר —</option>
                    <?php foreach ($type_codes as $code=>$name): ?>
                        <?php $sel = ((string)$code === (string)($data['permit_type_code'] ?? '')) ? 'selected' : ''; ?>
                        <option value="<?= e($code) ?>" <?= $sel ?>><?= e($name) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['permit_type_code'])): ?><div class="invalid-feedback"><?= e($errors['permit_type_code']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label class="form-label">תאריך בקשה</label>
                <input type="date" name="request_date" value="<?= e($data['request_date'] ?? '') ?>" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">תאריך הנפקה</label>
                <input type="date" name="issue_date" value="<?= e($data['issue_date'] ?? '') ?>" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">תאריך פקיעה</label>
                <input type="date" name="expiry_date" value="<?= e($data['expiry_date'] ?? '') ?>" class="form-control <?= isset($errors['expiry_date'])?'is-invalid':'' ?>">
                <?php if (isset($errors['expiry_date'])): ?><div class="invalid-feedback"><?= e($errors['expiry_date']) ?></div><?php endif; ?>
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

            <div class="col-12">
                <label class="form-label">הערות</label>
                <textarea name="notes" rows="3" class="form-control"><?= e($data['notes'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-success">שמירה</button>
            <a class="btn btn-outline-secondary" href="?r=employment_permits">חזרה לרשימה</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
