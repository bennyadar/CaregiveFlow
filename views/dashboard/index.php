<?php require __DIR__ . '/../layout/header.php'; ?>
<h1 class="h4 mb-4">ראשי</h1>
<?php if (isset($stats) && $stats): ?>
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-lg-3">
    <div class="card text-center"><div class="card-body">
      <div class="display-6"><?= (int)$stats['total_employees'] ?></div>
      <div class="text-muted">עובדים</div>
    </div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card text-center"><div class="card-body">
      <div class="display-6"><?= (int)$stats['total_employers'] ?></div>
      <div class="text-muted">מעסיקים</div>
    </div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card text-center"><div class="card-body">
      <div class="display-6"><?= (int)$stats['total_placements'] ?? 0 ?></div>
      <div class="text-muted">שיבוצים</div>
    </div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card text-center"><div class="card-body">
      <div class="display-6"><?= (int)$stats['total_home_visits'] ?? 0 ?></div>
      <div class="text-muted">ביקורי בית</div>
    </div></div>
  </div>
</div>
<?php else: ?>
<p class="text-muted">לא נמצאו סטטיסטיקות (ודא שה־view v_dashboard_stats קיים ושיש נתונים).</p>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <h2 class="h6">שיבוצים פעילים לפי חודש (12 חודשים אחרונים)</h2>
    <canvas id="placementsMonthly" height="100"></canvas>
  </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<?php // מעביר את הנתונים מהקונטרולר ל-JS (ללא תלות ב-VIEWים) ?>
<script>window.placementsChartData = <?= json_encode($chartData ?? ['labels'=>[],'active'=>[]], JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="assets/js/chart-placements.js"></script>
<script src="assets/js/chart-placements.js?v=<?= filemtime(__DIR__ . '/../../assets/js/chart-placements.js') ?>"></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
