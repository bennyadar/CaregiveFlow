<?php
/**
 * KPI cards – תצוגה דשבורדית קלה (יישור למוקאפים)
 *
 * קלט:
 *   $kpis: מערך של כרטיסים, כל כרטיס:
 *     [
 *       'value' => ...,
 *       'label' => ...,
 *       'class' => 'text-success|text-warning|text-danger|... (אופציונלי)',
 *       'href'  => 'index.php?... (אופציונלי - הופך את הכרטיס ללחיץ)',
 *       'icon'  => 'fa-solid fa-house|... (אופציונלי)'
 *     ]
 */

$kpis = $kpis ?? [];
if (empty($kpis)) { return; }
?>

<div class="row g-3 mb-4">
  <?php foreach ($kpis as $card): ?>
    <?php
      $value = $card['value'] ?? '—';
      $label = $card['label'] ?? '';
      $class = $card['class'] ?? '';
      $href  = $card['href'] ?? '';
      $icon  = $card['icon'] ?? '';
    ?>
    <div class="col-sm-6 col-xl-3">
      <div class="card kpi-card h-100">
        <?php if ($href !== ''): ?>
          <a href="<?= e($href) ?>" class="text-decoration-none text-reset d-block h-100">
        <?php endif; ?>

          <div class="card-body cf-kpi-body">
            <div class="cf-kpi-stat text-center flex-grow-1">
              <div class="kpi-value <?= e($class) ?>"><?= e((string)$value) ?></div>
              <div class="kpi-label"><?= e($label) ?></div>
              <div class="cf-kpi-accent"></div>
            </div>

            <?php if ($icon !== ''): ?>
              <div class="cf-kpi-icon" aria-hidden="true">
                <i class="<?= e($icon) ?>"></i>
              </div>
            <?php endif; ?>
          </div>

        <?php if ($href !== ''): ?>
          </a>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
