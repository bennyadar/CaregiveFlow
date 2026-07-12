<?php
/**
 * Partial: Search Bar (חיפוש + סינון)
 *
 * Required:
 *   - $routeValue (string)  למשל: 'employees' או 'employees/index'
 * Optional:
 *   - $label (string)       תווית מעל שדה החיפוש
 *   - $qValue (string)      ערך החיפוש הנוכחי
 *   - $submitLabel (string) טקסט כפתור
 *   - $extraFieldsHtml (string) HTML נוסף לשדות פילטר (עמודות נוספות בשורה)
 *   - $extraButtonsHtml (string) HTML נוסף לכפתורים (למשל "נקה")
 *   - $qColClass (string)      מחלקת Bootstrap לעמודת החיפוש (ברירת מחדל: col-md-4)
 *   - $submitColClass (string) מחלקת Bootstrap לעמודת כפתור "סינון" (ברירת מחדל: col-md-2)
 */
$routeValue = $routeValue ?? '';
$label = $label ?? 'חיפוש';
$qValue = $qValue ?? '';
$submitLabel = $submitLabel ?? 'סינון';
$extraFieldsHtml = $extraFieldsHtml ?? '';
$extraButtonsHtml = $extraButtonsHtml ?? '';
$qColClass = $qColClass ?? 'col-md-4';
$submitColClass = $submitColClass ?? 'col-md-2';
?>

<form class="row g-2 mb-3" method="get" action="">
  <input type="hidden" name="r" value="<?= e($routeValue) ?>">

  <div class="<?= e($qColClass) ?>">
    <label class="form-label"><?= e($label) ?></label>
    <input type="text" name="q" class="form-control" value="<?= e($qValue) ?>">
  </div>

  <?= $extraFieldsHtml ?>

  <div class="<?= e($submitColClass) ?> d-flex align-items-end">
    <button class="btn btn-outline-secondary w-100"><?= e($submitLabel) ?></button>
  </div>

  <?= $extraButtonsHtml ?>
</form>
