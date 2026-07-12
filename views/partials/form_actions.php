<?php
/**
 * Partial: Form Actions (שמירה/ביטול)
 *
 * Required:
 *   - $formId (string)    ה-id של ה-form שאליו כפתור השמירה צריך לשלוח submit
 *   - $cancelUrl (string) כתובת לביטול
 * Optional:
 *   - $saveLabel (string)
 *   - $cancelLabel (string)
 *   - $wrapperClass (string) מחלקת מעטפת (לשימוש בראש/סוף טופס)
 */
$formId = $formId ?? '';
$cancelUrl = $cancelUrl ?? '#';
$saveLabel = $saveLabel ?? 'שמירה';
$cancelLabel = $cancelLabel ?? 'ביטול';
$wrapperClass = $wrapperClass ?? 'd-flex gap-2';
?>

<div class="<?= e($wrapperClass) ?>">
  <button type="submit" form="<?= e($formId) ?>" class="btn btn-primary"><?= e($saveLabel) ?></button>
  <a class="btn btn-outline-secondary" href="<?= e($cancelUrl) ?>"><?= e($cancelLabel) ?></a>
</div>
