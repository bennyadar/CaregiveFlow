<?php /**
 * טופס יצירה/עריכה לדרכון מעסיק
 * קלטים מה-Controller: $data, $errors, $employers, $status_codes, $type_codes, $country_codes
 */ ?>
<?php require __DIR__ . '/../layout/header.php'; ?>
<div class="container" dir="rtl">
    <?php $is_edit = isset($data['id']); ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="m-0"><?= $is_edit ? 'עריכת דרכון (מעסיק)' : 'דרכון חדש (מעסיק)' ?></h3>
        <div>
            <a class="btn btn-outline-secondary" href="?r=employer_passports">חזרה לרשימה</a>
        </div>
    </div>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger"><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <form method="post" class="row g-3">
        <!-- מעסיק -->
        <div class="col-md-4">
            <label class="form-label">מעסיק <span class="text-danger">*</span></label>
            <select name="employer_id" class="form-select <?= !empty($errors['employer_id']) ? 'is-invalid' : '' ?>">
                <option value="">— בחר/י —</option>
                <?php foreach ($employers as $r): ?>
                    <?php $sel = ((string)$r['id'] === (string)($data['employer_id'] ?? '')) ? 'selected' : ''; ?>
                    <option value="<?= e($r['id']) ?>" <?= $sel ?>>
                        <?= e($r['last_name'].' '.$r['first_name'].' ('.($r['id_number'] ?? $r['passport_number']).')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['employer_id'])): ?><div class="invalid-feedback d-block"><?= e($errors['employer_id']) ?></div><?php endif; ?>
        </div>

        <!-- מס׳ דרכון -->
        <div class="col-md-4">
            <label class="form-label">מס׳ דרכון <span class="text-danger">*</span></label>
            <input type="text" name="passport_number" maxlength="20" class="form-control <?= !empty($errors['passport_number']) ? 'is-invalid' : '' ?>" value="<?= e($data['passport_number'] ?? '') ?>">
            <?php if (!empty($errors['passport_number'])): ?><div class="invalid-feedback d-block"><?= e($errors['passport_number']) ?></div><?php endif; ?>
        </div>

        <!-- סוג דרכון -->
        <div class="col-md-4">
            <label class="form-label">סוג דרכון</label>
            <select name="passport_type_code" class="form-select">
                <option value="">— ללא —</option>
                <?php foreach ($type_codes as $code=>$name): ?>
                    <?php $sel = ((string)$code === (string)($data['passport_type_code'] ?? '')) ? 'selected' : ''; ?>
                    <option value="<?= e($code) ?>" <?= $sel ?>><?= e($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- לאום -->
        <div class="col-md-4">
            <label class="form-label">לאום</label>
            <select name="country_code" class="form-select">
                <option value="">— ללא —</option>
                <?php foreach ($country_codes as $code=>$name): ?>
                    <?php $sel = ((string)$code === (string)($data['country_code'] ?? '')) ? 'selected' : ''; ?>
                    <option value="<?= e($code) ?>" <?= $sel ?>><?= e($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- תאריכים -->
        <div class="col-md-4">
            <label class="form-label">תאריך הנפקה</label>
            <input type="date" name="issue_date" class="form-control <?= !empty($errors['date_range']) ? 'is-invalid' : '' ?>" value="<?= e($data['issue_date'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">תאריך פקיעה</label>
            <input type="date" name="expiry_date" class="form-control <?= !empty($errors['date_range']) ? 'is-invalid' : '' ?>" value="<?= e($data['expiry_date'] ?? '') ?>">
            <?php if (!empty($errors['date_range'])): ?><div class="invalid-feedback d-block"><?= e($errors['date_range']) ?></div><?php endif; ?>
        </div>

        <!-- ראשי -->
        <div class="col-md-4 d-flex align-items-end">
            <div class="form-check">
                <?php $checked = !empty($data['is_primary']) ? 'checked' : ''; ?>
                <input class="form-check-input" type="checkbox" name="is_primary" id="is_primary" value="1" <?= $checked ?>>
                <label class="form-check-label" for="is_primary">סמן כדרכון ראשי</label>
            </div>
        </div>

        <!-- מקום הוצאה -->
        <div class="col-md-4">
            <label class="form-label">מקום הוצאה</label>
            <input type="text" name="issue_place" class="form-control" value="<?= e($data['issue_place'] ?? '') ?>">
        </div>

        <!-- סטטוס (קוד) -->
        <div class="col-md-4">
            <label class="form-label">סטטוס</label>
            <select name="status_code" class="form-select">
                <option value="">— ללא —</option>
                <?php foreach ($status_codes as $code=>$name): ?>
                    <?php $sel = ((string)$code === (string)($data['status_code'] ?? '')) ? 'selected' : ''; ?>
                    <option value="<?= e($code) ?>" <?= $sel ?>><?= e($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- הערות -->
        <div class="col-12">
            <label class="form-label">הערות</label>
            <textarea name="notes" class="form-control" rows="3"><?= e($data['notes'] ?? '') ?></textarea>
        </div>

        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">שמירה</button>
            <a class="btn btn-light" href="?r=employer_passports">ביטול</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
