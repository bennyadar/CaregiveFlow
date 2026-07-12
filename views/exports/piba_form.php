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
  <h1 class="my-3">ייצוא JSON – רשות האוכלוסין (PIBA) | הארכת רישיון לעו"ז בסיעוד</h1>

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
      <select id="employeeSelect" name="employee_id" class="form-select" required>
        <option value="">— בחר —</option>
        <?php foreach ($employees as $emp): ?>
          <option value="<?= (int)$emp['id'] ?>"
            <?= (isset($selected_employee_id) && (string)$emp['id'] === (string)$selected_employee_id)
        ? 'selected="selected"' : '' ?>>
            <?= e('#'.$emp['id'].' • '.$emp['last_name_he'].' '.$emp['first_name_he'].' • '.$emp['passport_number']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <div class="d-flex justify-content-end mt-4 gap-2">
        <button id="btnPreview" type="button" class="btn btn-outline-primary">
          תצוגה מקדימה
        </button>
        <button id="btnDownload" class="btn btn-primary">ייצור והורדה</button>
      </div>
    </div>
  </form>

  <div class="card p-3 mt-3">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <h5 class="mb-0">תצוגה מקדימה + ולידציה</h5>
      <div class="small text-muted" id="pibaPreviewMeta" style="min-height:1em;"></div>
    </div>

    <div id="pibaPreviewError" class="alert alert-danger" style="display:none;"></div>

    <div id="pibaPreviewMissing" class="alert alert-warning" style="display:none;">
      <div class="fw-bold mb-2">שדות חובה חסרים / לא תקינים</div>
      <ul class="mb-0" id="pibaMissingList"></ul>
    </div>

    <div id="pibaPreviewOk" class="alert alert-success" style="display:none;">
      הכל נראה תקין. אפשר להוריד את הקובץ.
    </div>

    <label class="form-label">JSON שייוצר</label>
    <textarea id="pibaPreviewJson" class="form-control" rows="18" readonly style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;"></textarea>
    <div class="form-text">הערה: <code>dataText</code> נשלח ריק (לפי האפיון).</div>
  </div>
</div>

<script>
(function () {
  var sel = document.getElementById('employeeSelect');
  var btn = document.getElementById('backToEmployee');
  var btnPreview = document.getElementById('btnPreview');
  var btnDownload = document.getElementById('btnDownload');
  var elErr = document.getElementById('pibaPreviewError');
  var elMissingBox = document.getElementById('pibaPreviewMissing');
  var elMissingList = document.getElementById('pibaMissingList');
  var elOk = document.getElementById('pibaPreviewOk');
  var elJson = document.getElementById('pibaPreviewJson');
  var elMeta = document.getElementById('pibaPreviewMeta');
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

  function setLoading(isLoading) {
    if (btnPreview) btnPreview.disabled = isLoading;
    if (btnDownload) btnDownload.disabled = isLoading;
    if (elMeta) elMeta.textContent = isLoading ? 'טוען תצוגה מקדימה…' : '';
  }

  function hideAll() {
    if (elErr) elErr.style.display = 'none';
    if (elMissingBox) elMissingBox.style.display = 'none';
    if (elOk) elOk.style.display = 'none';
  }

  async function runPreview() {
    var v = sel.value;
    hideAll();
    if (!v) {
      if (elJson) elJson.value = '';
      if (btnDownload) btnDownload.disabled = true;
      return;
    }

    setLoading(true);

    try {
      var url = 'index.php?r=exports/piba_preview&employee_id=' + encodeURIComponent(v);
      var resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
      var data = await resp.json();

      if (!data || !data.ok) {
        throw new Error((data && data.error) ? data.error : 'Preview failed');
      }

      if (elMeta) elMeta.textContent = 'שם קובץ: ' + (data.filename || '');
      if (elJson) elJson.value = data.json || '';

      var missing = Array.isArray(data.missing) ? data.missing : [];
      if (missing.length > 0) {
        if (elMissingList) {
          elMissingList.innerHTML = '';
          missing.forEach(function (m) {
            var li = document.createElement('li');
            li.textContent = m;
            elMissingList.appendChild(li);
          });
        }
        if (elMissingBox) elMissingBox.style.display = '';
        if (btnDownload) btnDownload.disabled = true;
      } else {
        if (elOk) elOk.style.display = '';
        if (btnDownload) btnDownload.disabled = false;
      }
    } catch (e) {
      if (elErr) {
        elErr.textContent = 'שגיאה בתצוגה מקדימה: ' + (e && e.message ? e.message : e);
        elErr.style.display = '';
      }
      if (btnDownload) btnDownload.disabled = true;
    } finally {
      setLoading(false);
    }
  }

  // תצוגה מקדימה בלחיצה + אוטומטית בעת החלפת עובד
  if (btnPreview) btnPreview.addEventListener('click', runPreview);
  sel.addEventListener('change', runPreview);

  // מצב התחלתי
  if (btnDownload) btnDownload.disabled = true;
  runPreview();
})();
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
