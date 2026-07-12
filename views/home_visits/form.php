<?php /** @var array $data, $errors, $employees, $status_codes, $type_codes, $stage_codes, $placement_type_codes */ ?>
<?php require __DIR__ . '/../layout/header.php'; ?>

<?php
// ===== כותרת מסך + פעולות (שמירה/ביטול כמו "עובד חדש") =====
$isEdit = isset($data['id']) && (string)$data['id'] !== '';
$title = $isEdit ? 'עריכת ביקור בית' : 'ביקור בית חדש';

$formId = 'home-visit-form';
$employeeId = (string)($data['employee_id'] ?? '');

$cancelQs = ['r' => 'home_visits'];
if ($employeeId !== '') {
    $cancelQs['employee_id'] = $employeeId;
}
$cancelUrl = 'index.php?' . http_build_query($cancelQs);

ob_start();
$formId_for_partial = $formId;
$cancelUrl_for_partial = $cancelUrl;

// שמירה/ביטול בראש המסך (כפתורים מחוץ ל-form אבל עושים submit באמצעות form="id")
$formId = $formId_for_partial;
$cancelUrl = $cancelUrl_for_partial;
include __DIR__ . '/../partials/form_actions.php';
$rightHtml = ob_get_clean();

include __DIR__ . '/../partials/page_header.php';
?>

<?php if (!empty($errors['general'])): ?>
  <div class="alert alert-danger"><?= e($errors['general']) ?></div>
<?php endif; ?>

<div class="mx-auto" style="max-width: 860px;">
  <form id="<?= e($formId) ?>" method="post">
    <?php if (function_exists('csrf_field')) csrf_field(); ?>

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">עובד</label>
        <select name="employee_id" class="form-select <?= isset($errors['employee_id']) ? 'is-invalid' : '' ?>">
          <option value="">-- בחר/י --</option>
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
          <option value="">-- בחר/י --</option>
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
          <option value="">-- בחר/י --</option>
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
          <option value="">-- בחר/י --</option>
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
          <option value="">-- בחר/י --</option>
          <?php foreach ($placement_type_codes as $code => $name): ?>
            <?php $sel = ((string)$code === (string)($data['placement_type_code'] ?? '')) ? 'selected' : ''; ?>
            <option value="<?= e($code) ?>" <?= $sel ?>><?= e($name) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['placement_type_code'])): ?><div class="invalid-feedback"><?= e($errors['placement_type_code']) ?></div><?php endif; ?>
      </div>

      <div class="col-md-6">
        <label class="form-label">מזהה שיבוץ (אופציונלי)</label>
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
        <label class="form-label">בוצע ע"י (מזהה משתמש)</label>
        <input type="text" name="visited_by_user_id" value="<?= e($data['visited_by_user_id'] ?? '') ?>" class="form-control <?= isset($errors['visited_by_user_id']) ? 'is-invalid' : '' ?>">
        <?php if (isset($errors['visited_by_user_id'])): ?><div class="invalid-feedback"><?= e($errors['visited_by_user_id']) ?></div><?php endif; ?>
      </div>
    </div>

    <?php
      // כפתורי שמירה/ביטול בתחתית הטופס (כמו בטופסי Employees/Employers)
      $formId = $formId;
      $cancelUrl = $cancelUrl;
      $wrapperClass = 'd-flex gap-2 mt-4';
      include __DIR__ . '/../partials/form_actions.php';
    ?>
  </form>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
