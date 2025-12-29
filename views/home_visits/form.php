<?php /** @var array $data, $errors, $employees, $status_codes, $type_codes, $stage_codes, $placement_type_codes */ ?>
<?php require __DIR__ . '/../layout/header.php'; ?>
<div class="container" dir="rtl" style="max-width: 860px;">
    <h3 class="mb-3"><?= isset($data['id']) ? 'עריכת ביקור בית' : 'ביקור בית חדש' ?></h3>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger"><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <form method="post">
        <?php if (function_exists('csrf_field')) csrf_field(); ?>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">עובד</label>
                <select name="employee_id" class="form-select <?= isset($errors['employee_id']) ? 'is-invalid' : '' ?>">
                    <option value="">בחר...</option>
                    <?php foreach ($employees as $emp): ?>
                        <?php $sel = ((string)$emp['id'] === (string)($data['employee_id'] ?? '')) ? 'selected' : ''; ?>
                        <option value="<?= e($emp['id']) ?>" <?= $sel ?>>
                            <?= e($emp['last_name'] . ' ' . $emp['first_name'] . ' (' . $emp['passport_number'] . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['employee_id'])): ?><div class="invalid-feedback"><?= e($errors['employee_id']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label">תאריך ביקור</label>
                <input type="date" name="visit_date" value="<?= e($data['visit_date'] ?? '') ?>" class="form-control <?= isset($errors['visit_date']) ? 'is-invalid' : '' ?>">
                <?php if (isset($errors['visit_date'])): ?><div class="invalid-feedback"><?= e($errors['visit_date']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label class="form-label">סוג ביקור</label>
                <select name="visit_type_code" class="form-select <?= isset($errors['visit_type_code']) ? 'is-invalid' : '' ?>">
                    <option value="">בחר...</option>
                    <?php foreach ($type_codes as $code => $name): ?>
                        <?php $sel = ((string)$code === (string)($data['visit_type_code'] ?? '')) ? 'selected' : ''; ?>
                        <option value="<?= e($code) ?>" <?= $sel ?>><?= e($name) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['visit_type_code'])): ?><div class="invalid-feedback"><?= e($errors['visit_type_code']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label class="form-label">סטטוס</label>
                <select name="status_code" class="form-select <?= isset($errors['status_code']) ? 'is-invalid' : '' ?>">
                    <option value="">בחר...</option>
                    <?php foreach ($status_codes as $code => $name): ?>
                        <?php if ((string)$code === 'overdue') continue; ?>
                        <?php $sel = ((string)$code === (string)($data['status_code'] ?? '')) ? 'selected' : ''; ?>
                        <option value="<?= e($code) ?>" <?= $sel ?>><?= e($name) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['status_code'])): ?><div class="invalid-feedback"><?= e($errors['status_code']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label class="form-label">שלב</label>
                <select name="home_visit_stage_code" class="form-select <?= isset($errors['home_visit_stage_code']) ? 'is-invalid' : '' ?>">
                    <option value="">בחר...</option>
                    <?php foreach ($stage_codes as $code => $name): ?>
                        <?php $sel = ((string)$code === (string)($data['home_visit_stage_code'] ?? '')) ? 'selected' : ''; ?>
                        <option value="<?= e($code) ?>" <?= $sel ?>><?= e($name) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['home_visit_stage_code'])): ?><div class="invalid-feedback"><?= e($errors['home_visit_stage_code']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label">סוג השמה</label>
                <select name="placement_type_code" class="form-select <?= isset($errors['placement_type_code']) ? 'is-invalid' : '' ?>">
                    <option value="">—</option>
                    <?php foreach ($placement_type_codes as $code => $name): ?>
                        <?php $sel = ((string)$code === (string)($data['placement_type_code'] ?? '')) ? 'selected' : ''; ?>
                        <option value="<?= e($code) ?>" <?= $sel ?>><?= e($name) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['placement_type_code'])): ?><div class="invalid-feedback"><?= e($errors['placement_type_code']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label">Placement ID (אופציונלי)</label>
                <input type="text" name="placement_id" value="<?= e($data['placement_id'] ?? '') ?>" class="form-control <?= isset($errors['placement_id']) ? 'is-invalid' : '' ?>">
                <?php if (isset($errors['placement_id'])): ?><div class="invalid-feedback"><?= e($errors['placement_id']) ?></div><?php endif; ?>
            </div>

            <div class="col-12">
                <label class="form-label">סיכום</label>
                <textarea name="summary" class="form-control" rows="3"><?= e($data['summary'] ?? '') ?></textarea>
            </div>

            <div class="col-12">
                <label class="form-label">ממצאים</label>
                <textarea name="findings" class="form-control" rows="3"><?= e($data['findings'] ?? '') ?></textarea>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <?php $checked = (!empty($data['followup_required']) && (string)$data['followup_required'] !== '0') ? 'checked' : ''; ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="followup_required" value="1" id="followup_required" <?= $checked ?>>
                    <label class="form-check-label" for="followup_required">נדרש מעקב</label>
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label">תאריך ביקור הבא</label>
                <input type="date" name="next_visit_due" value="<?= e($data['next_visit_due'] ?? '') ?>" class="form-control <?= isset($errors['next_visit_due']) ? 'is-invalid' : '' ?>">
                <?php if (isset($errors['next_visit_due'])): ?><div class="invalid-feedback"><?= e($errors['next_visit_due']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label class="form-label">Visited By (User ID)</label>
                <input type="text" name="visited_by_user_id" value="<?= e($data['visited_by_user_id'] ?? '') ?>" class="form-control <?= isset($errors['visited_by_user_id']) ? 'is-invalid' : '' ?>">
                <?php if (isset($errors['visited_by_user_id'])): ?><div class="invalid-feedback"><?= e($errors['visited_by_user_id']) ?></div><?php endif; ?>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary">שמירה</button>
            <a class="btn btn-outline-secondary" href="?r=home_visits">ביטול</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
