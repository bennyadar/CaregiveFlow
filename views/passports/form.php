<?php /** @var array $data, $errors, $employees, $status_codes, $type_codes, $country_codes */ ?>
<?php require __DIR__ . '/../layout/header.php'; ?>
<div class="container" dir="rtl" style="max-width: 860px;">
    <h3 class="mb-3"><?= isset($data['id']) ? 'עריכת דרכון' : 'דרכון חדש' ?></h3>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger"><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <form method="post">
        <?php if (function_exists('csrf_field')) csrf_field(); ?>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">עובד</label>
                <select name="employee_id" class="form-select <?= isset($errors['employee_id'])?'is-invalid':'' ?>">
                    <option value="">— בחר —</option>
                    <?php foreach ($employees as $emp): ?>
                        <?php $sel = ((string)$emp['id'] === (string)($data['employee_id'] ?? '')) ? 'selected' : ''; ?>
                        <option value="<?= e($emp['id']) ?>" <?= $sel ?>><?= e($emp['last_name'].' '.$emp['first_name'].' ('.$emp['passport_number'].')') ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['employee_id'])): ?><div class="invalid-feedback"><?= e($errors['employee_id']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label">מס׳ דרכון</label>
                <input type="text" name="passport_number" value="<?= e($data['passport_number'] ?? '') ?>" class="form-control <?= isset($errors['passport_number'])?'is-invalid':'' ?>">
                <?php if (isset($errors['passport_number'])): ?><div class="invalid-feedback"><?= e($errors['passport_number']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label class="form-label">סוג דרכון</label>
                <select name="passport_type_code" class="form-select <?= isset($errors['passport_type_code'])?'is-invalid':'' ?>">
                    <option value="">— בחר —</option>
                    <?php foreach ($type_codes as $code=>$name): ?>
                        <?php $sel = ((string)$code === (string)($data['passport_type_code'] ?? '')) ? 'selected' : ''; ?>
                        <option value="<?= e($code) ?>" <?= $sel ?>><?= e($name) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['passport_type_code'])): ?><div class="invalid-feedback"><?= e($errors['passport_type_code']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label class="form-label">לאום</label>
                <select name="country_code" class="form-select <?= isset($errors['country_code'])?'is-invalid':'' ?>">
                    <option value="">— בחר —</option>
                    <?php foreach ($country_codes as $code=>$name): ?>
                        <?php $sel = ((string)$code === (string)($data['country_code'] ?? '')) ? 'selected' : ''; ?>
                        <option value="<?= e($code) ?>" <?= $sel ?>><?= e($code) .' - '. e($name) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['country_code'])): ?><div class="invalid-feedback"><?= e($errors['country_code']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label class="form-label">מקום הוצאה</label>
                <input type="text" name="issue_place" value="<?= e($data['issue_place'] ?? '') ?>" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label">תאריך הנפקה</label>
                <input type="date" name="issue_date" value="<?= e($data['issue_date'] ?? '') ?>" class="form-control">
            </div>
            <div class="col-md-6">
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

            <!-- שדות חדשים: is_primary + primary_employee_id (קריאה בלבד) -->
            <div class="col-12">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="is_primary" id="is_primary" value="1" <?= !empty($data['is_primary']) ? 'checked' : '' ?> />
                    <label class="form-check-label" for="is_primary">דרכון ראשי</label>
                </div>
            </div> 

            <div class="col-12">
                <label class="form-label">הערות</label>
                <textarea name="notes" rows="3" class="form-control"><?= e($data['notes'] ?? '') ?></textarea>
            </div>          
        </div>

        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-success">שמירה</button>
            <a class="btn btn-outline-secondary" href="?r=passports&employee_id=<?= e($_GET['employee_id']) ?>">חזרה לרשימת דרכונים</a>
            <!-- <a class="btn btn-outline-secondary" href="?r=employees">חזרה לרשימת עובדים</a> -->
            <a class="btn btn-outline-secondary" href="?r=employees/show&id=<?= e($_GET['employee_id']) ?>">חזרה לכרטיס עובד</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
