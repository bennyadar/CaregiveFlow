<?php
/**
 * Dashboard – ראשי
 * כולל Tabs: סקירה / סטטוסים
 */

$sidebarActive = 'dashboard';
?>

<?php require __DIR__ . '/../layout/header.php'; ?>

<?php
$title = 'ראשי';
$showBreadcrumbs = false;
include __DIR__ . '/../partials/page_header.php';

$tab = (string)($tab ?? ($_GET['tab'] ?? 'overview'));
if (!in_array($tab, ['overview', 'statuses'], true)) {
    $tab = 'overview';
}

$welcomeName = trim((string)($u['display_name'] ?? $u['username'] ?? ''));
if ($welcomeName === '') {
    $welcomeName = 'משתמש';
}
?>

<?php if ($tab === 'overview'): ?>

  <div class="row g-3 align-items-stretch mb-4 cf-dashboard-hero-row">
    <div class="col-xl-4">
      <div class="cf-dashboard-tabs-wrap h-100 d-flex flex-column align-items-xl-start justify-content-center">
        <ul class="nav cf-dashboard-tabs">
          <li class="nav-item">
            <a class="nav-link <?= $tab === 'overview' ? 'active' : '' ?>" href="index.php?r=dashboard/index&tab=overview">סקירה</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $tab === 'statuses' ? 'active' : '' ?>" href="index.php?r=dashboard/index&tab=statuses">סטטוסים</a>
          </li>
        </ul>
      </div>
    </div>

<!--     <div class="col-xl-8">
      <div class="card cf-welcome-card h-100">
        <div class="card-body d-flex flex-column justify-content-center">
          <h2 class="cf-welcome-title mb-2">👋 שלום, <?= e($welcomeName) ?>!</h2>
          <p class="cf-welcome-subtitle mb-0">ברוך שובך למערכת</p>
        </div>
      </div>
    </div>
  </div> -->

  <?php
  // KPI ראשיים (כמות רשומות בכל מודול) + קישור מהיר
  $kpis = [
    ['value' => (int)($stats['total_employees'] ?? 0),   'label' => 'עובדים',     'href' => 'index.php?r=employees/index',      'icon' => 'fa-solid fa-user'],
    ['value' => (int)($stats['total_employers'] ?? 0),   'label' => 'מעסיקים',    'href' => 'index.php?r=employers/index',     'icon' => 'fa-solid fa-briefcase'],
    ['value' => (int)($stats['total_placements'] ?? 0),  'label' => 'שיבוצים',    'href' => 'index.php?r=placements/index',    'icon' => 'fa-solid fa-calendar-days'],
    ['value' => (int)($stats['total_home_visits'] ?? 0), 'label' => 'ביקורי בית', 'href' => 'index.php?r=home_visits',         'icon' => 'fa-solid fa-house'],
  ];
  include __DIR__ . '/../partials/kpi_cards.php';
  ?>

  <div class="card">
    <div class="card-body">
      <h2 class="h6 mb-3">שיבוצים פעילים לפי חודש (12 חודשים אחרונים)</h2>
      <canvas id="placementsMonthly" height="100"></canvas>
    </div>
  </div>

  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <?php // מעביר נתונים מהקונטרולר ל-JS ?>
  <script>window.placementsChartData = <?= json_encode($chartData ?? ['labels'=>[],'active'=>[]], JSON_UNESCAPED_UNICODE) ?>;</script>
  <script src="assets/js/chart-placements.js?v=<?= filemtime(__DIR__ . '/../../public/assets/js/chart-placements.js') ?>"></script>

<?php else: ?>

  <div class="row g-3 align-items-stretch mb-4 cf-dashboard-hero-row">
    <div class="col-xl-4">
      <div class="cf-dashboard-tabs-wrap h-100 d-flex flex-column align-items-xl-start justify-content-center">
        <ul class="nav cf-dashboard-tabs">
          <li class="nav-item">
            <a class="nav-link <?= $tab === 'overview' ? 'active' : '' ?>" href="index.php?r=dashboard/index&tab=overview">סקירה</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $tab === 'statuses' ? 'active' : '' ?>" href="index.php?r=dashboard/index&tab=statuses">סטטוסים</a>
          </li>
        </ul>
      </div>
    </div>

  <?php
  // KPI סטטוסים/התראות (ל-30 יום)
  $kpis = [
    ['value' => (int)($expiry['passport']['expired'] ?? 0),  'label' => 'דרכונים שפגו',     'class' => 'text-danger',  'href' => 'index.php?r=employees/index&expiry=passport_expired'],
    ['value' => (int)($expiry['passport']['soon'] ?? 0),     'label' => 'דרכונים פג תוקף בקרוב', 'class' => 'text-warning', 'href' => 'index.php?r=employees/index&expiry=passport_soon'],
    ['value' => (int)($expiry['visa']['expired'] ?? 0),      'label' => 'ויזות שפגו',        'class' => 'text-danger',  'href' => 'index.php?r=employees/index&expiry=visa_expired'],
    ['value' => (int)($expiry['visa']['soon'] ?? 0),         'label' => 'ויזות פג תוקף בקרוב', 'class' => 'text-warning', 'href' => 'index.php?r=employees/index&expiry=visa_soon'],
    ['value' => (int)($expiry['insurance']['expired'] ?? 0), 'label' => 'ביטוחים שפגו',      'class' => 'text-danger',  'href' => 'index.php?r=employees/index&expiry=insurance_expired'],
    ['value' => (int)($expiry['insurance']['soon'] ?? 0),    'label' => 'ביטוחים פג תוקף בקרוב', 'class' => 'text-warning', 'href' => 'index.php?r=employees/index&expiry=insurance_soon'],
    ['value' => (int)($plKpi['ending_soon'] ?? 0),           'label' => 'שיבוצים מסתיימים ב-30 יום', 'class' => 'text-warning', 'href' => 'index.php?r=reports/placements_ending'],
    ['value' => (int)($hvKpi['overdue'] ?? 0),               'label' => 'ביקורי בית באיחור (מעקב)', 'class' => 'text-danger',  'href' => 'index.php?r=home_visits&status=overdue&followup_only=1'],
  ];
  include __DIR__ . '/../partials/kpi_cards.php';
  ?>

  <div class="card">
    <div class="card-body">
      <h2 class="h6 mb-3">קישורים מהירים</h2>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-primary" href="index.php?r=employees/index&tab=stats">סטטוסים עובדים</a>
        <a class="btn btn-outline-primary" href="index.php?r=placements/index&tab=statuses">סטטוסים שיבוצים</a>
        <a class="btn btn-outline-primary" href="index.php?r=home_visits&tab=statuses">סטטוסים ביקורי בית</a>
      </div>
    </div>
  </div>

<?php endif; ?>

<?php require __DIR__ . '/../layout/footer.php'; ?>
