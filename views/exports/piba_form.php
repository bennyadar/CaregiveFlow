<?php /* views/exports/piba_form.php */ ?>
<?php require __DIR__ . '/../layout/header.php'; ?>
<?php
// לשחזר עובד שנבחר קודם (אם ה-controller לא העביר אותו כבר)
if (!isset($selected_employee_id)) {
    $selected_employee_id = $_SESSION['old']['employee_id'] ?? null;
    unset($_SESSION['old']['employee_id']);
}

// נתיב “חזרה לעובד” (עדכן אם ההצגה אצלך במסלול אחר)
$employeeShowRoute = 'employees/show';
$backHref = $selected_employee_id
    ? 'index.php?r=' . $employeeShowRoute . '&id=' . urlencode((string)$selected_employee_id)
    : 'index.php?r=employees/index';
?>
<?php if (!empty($_SESSION['flash'])): ?>
  <?php $cls = $_SESSION['flash_type'] ?? 'danger'; ?>
  <div class="alert alert-<?= e($cls) ?>" dir="rtl">
    <?= e($_SESSION['flash']); ?>
  </div>
  <?php unset($_SESSION['flash'], $_SESSION['flash_type']); ?>
<?php endif; ?>

<div class="container" dir="rtl">
  <h1 class="my-3">ייצוא XML – רשות האוכלוסין (PIBA)</h1>

  <?php if (!empty($_SESSION['flash'])): ?>
    <div class="alert alert-info"><?= e($_SESSION['flash']); ?></div>
    <?php unset($_SESSION['flash']); ?>
  <?php endif; ?>

<div class="d-flex justify-content-end mb-3 gap-2">
  <a id="backToEmployee"
     href="<?= e($backHref) ?>"
     class="btn btn-outline-secondary"
     <?= empty($selected_employee_id) ? 'style="display:none;"' : '' ?>>
    חזרה לעובד
  </a>
  <a class="btn btn-outline-secondary" href="index.php?r=employees/index">חזרה לרשימת עובדים</a>
</div>

  <form method="post" action="?r=exports/piba_export" class="card p-3">
    <div class="mb-3">
      <label class="form-label">בחר עובד</label>
      <select name="employee_id" class="form-select" required>
        <option value="">— בחר —</option>
        <?php foreach ($employees as $emp): ?>
          <option value="<?= (int)$emp['id'] ?>"
            <?= (isset($selected_employee_id) && (string)$emp['id'] === (string)$selected_employee_id)
        ? 'selected="selected"' : '' ?>>
            <?= e('#'.$emp['id'].' • '.$emp['last_name_he'].' '.$emp['first_name_he'].' • '.$emp['passport_number']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="d-flex justify-content-end mt-4">
        <button class="btn btn-primary">ייצור והורדה</button>
      </div>
    </div>
  </form>
</div>

<script>
(function () {
  var sel = document.getElementById('employeeSelect');
  var btn = document.getElementById('backToEmployee');
  if (!sel || !btn) return;

  var baseShow = 'index.php?r=<?= addslashes($employeeShowRoute) ?>&id=';

  function syncBack() {
    var v = sel.value;
    if (v) {
      btn.href = baseShow + encodeURIComponent(v);
      btn.style.display = '';
    } else {
      btn.style.display = 'none';
    }
  }

  syncBack();                  // טעינה ראשונית
  sel.addEventListener('change', syncBack);
})();
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
