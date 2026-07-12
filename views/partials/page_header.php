<?php
/**
 * Partial: Page Header (כותרת עמוד + פעולות)
 *
 * Required:
 *   - $title (string)
 * Optional:
 *   - $subtitle (string)
 *   - $rightHtml (string) HTML מוכן (כפתורים/קישורים) להצגה באזור הפעולות.
 *   - $breadcrumbs (array) מערך Breadcrumbs בפורמט:
 *       [ ['label' => 'ראשי', 'href' => 'index.php?...'|null], ... ]
 *   - $showBreadcrumbs (bool) ברירת מחדל true
 */
$title = $title ?? '';
$subtitle = $subtitle ?? '';
$rightHtml = $rightHtml ?? '';
$showBreadcrumbs = (bool)($showBreadcrumbs ?? true);

// אם לא הוגדר – בונים אוטומטית לפי route + title
$breadcrumbs = $breadcrumbs ?? ($showBreadcrumbs ? cgf_breadcrumbs($title) : []);
?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
  <div>
    <h1 class="h4 mb-0"><?= e($title) ?></h1>
    <?php if ($subtitle !== ''): ?>
      <div class="text-muted small mt-1"><?= e($subtitle) ?></div>
    <?php endif; ?>
  </div>

  <!-- ב-RTL: Title מימין, Breadcrumbs/Actions משמאל (כמו AdminLTE RTL) -->
  <div class="d-flex flex-column align-items-end gap-2 text-end">
    <?php if ($showBreadcrumbs && !empty($breadcrumbs)): ?>
      <nav aria-label="breadcrumb" class="cf-breadcrumb">
        <ol class="breadcrumb mb-0 small">
          <?php foreach ($breadcrumbs as $i => $bc): ?>
            <?php
              $isLast = ($i === (count($breadcrumbs) - 1));
              $label = (string)($bc['label'] ?? '');
              $href = $bc['href'] ?? null;
            ?>
            <?php if ($isLast || !$href): ?>
              <li class="breadcrumb-item active" aria-current="page"><?= e($label) ?></li>
            <?php else: ?>
              <li class="breadcrumb-item"><a href="<?= e((string)$href) ?>"><?= e($label) ?></a></li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ol>
      </nav>
    <?php endif; ?>

    <?php if ($rightHtml !== ''): ?>
      <div class="d-flex gap-2 flex-wrap justify-content-end">
        <?= $rightHtml ?>
      </div>
    <?php endif; ?>
  </div>
</div>
