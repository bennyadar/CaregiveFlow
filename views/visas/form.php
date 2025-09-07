<?php /** @var array $data, $errors, $employees */ ?>
<?php require __DIR__ . '/../layout/header.php'; ?>
<div class="container" dir="rtl" style="max-width: 860px;">
    <h3 class="mb-3"><?= isset($data['id']) ? 'עריכת ויזה' : 'ויזה חדשה' ?></h3>

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
                        <option value="<?= e($emp['id']) ?>" <?= $sel ?>>
                            <?= e($emp['last_name'].' '.$emp['first_name'].' ('.$emp['passport_number'].')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['employee_id'])): ?><div class="invalid-feedback"><?= e($errors['employee_id']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label">מס׳ ויזה/סימוכין</label>
                <input type="text" name="visa_number" value="<?= e($data['visa_number'] ?? '') ?>" class="form-control <?= isset($errors['visa_number'])?'is-invalid':'' ?>">
                <?php if (isset($errors['visa_number'])): ?><div class="invalid-feedback"><?= e($errors['visa_number']) ?></div><?php endif; ?>
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
                <?php $statuses = ['requested'=>'ממתין','approved'=>'מאושר','denied'=>'נדחה','expired'=>'פג']; ?>
                <select name="status" class="form-select <?= isset($errors['status'])?'is-invalid':'' ?>">
                    <?php foreach ($statuses as $k=>$v): ?>
                        <?php $sel = ((string)$k === (string)($data['status'] ?? 'requested')) ? 'selected' : ''; ?>
                        <option value="<?= e($k) ?>" <?= $sel ?>><?= e($v) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['status'])): ?><div class="invalid-feedback"><?= e($errors['status']) ?></div><?php endif; ?>
            </div>

            <div class="col-12">
                <label class="form-label">הערות</label>
                <textarea name="notes" rows="3" class="form-control"><?= e($data['notes'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-success">שמירה</button>
            <a class="btn btn-outline-secondary" href="?r=visas">חזרה לרשימה</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>