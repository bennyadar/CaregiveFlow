<?php
/**
 * Right Sidebar – AdminLTE-inspired
 *
 * מטרות:
 * - Sidebar כהה, נקי ומודרני בסגנון AdminLTE.
 * - RTL מלא (כיוון ותיחום).
 * - פעיל (active) לפי הנתיב הנוכחי.
 * - חיפוש בתפריט (client-side) ללא תלות בשרת.
 */

$route = (string)($_GET['r'] ?? 'dashboard/index');
$base = explode('/', $route)[0] ?: 'dashboard';

// מאפשר לדפים לדרוס active ידנית
$active = (string)($sidebarActive ?? $base);

// $u מגיע מ-header.php (include) – נשתמש בו אם קיים
$displayName = (string)($u['display_name'] ?? $u['username'] ?? '');
$role = (string)($u['role'] ?? '');

$items = [
  ['key' => 'dashboard',      'label' => 'ראשי',       'href' => 'index.php?r=dashboard/index',      'icon' => 'fa-solid fa-house'],
  ['key' => 'agency_settings','label' => 'פרטי לשכה', 'href' => 'index.php?r=agency_settings/index','icon' => 'fa-solid fa-building'],
  ['key' => 'employees',      'label' => 'עובדים',     'href' => 'index.php?r=employees/index',      'icon' => 'fa-solid fa-user-nurse'],
  ['key' => 'employers',      'label' => 'מעסיקים',    'href' => 'index.php?r=employers/index',      'icon' => 'fa-solid fa-users'],
  ['key' => 'placements',     'label' => 'שיבוצים',    'href' => 'index.php?r=placements/index',     'icon' => 'fa-solid fa-handshake'],
  ['key' => 'home_visits',    'label' => 'ביקורי בית', 'href' => 'index.php?r=home_visits',          'icon' => 'fa-solid fa-house-chimney-medical'],
  ['key' => 'exports',        'label' => 'ייצואים',    'href' => 'index.php?r=exports/history',      'icon' => 'fa-solid fa-file-export'],
];
?>

<div class="cf-sidebar-inner">
  <div class="cf-sidebar-brand">
    <a class="cf-brand-link" href="index.php?r=dashboard/index" aria-label="CaregiveFlow">
      <img src="assets/img/logo.png" alt="CaregiveFlow" class="cf-brand-logo">
      <span class="cf-brand-text"></span>
    </a>
  </div>

  <div class="cf-sidebar-user">
    <div class="cf-user-avatar" aria-hidden="true">
      <i class="fa-solid fa-user"></i>
    </div>
    <div class="cf-user-meta">
      <div class="cf-user-name"><?= e($displayName ?: 'admin') ?></div>
      <?php if ($role): ?>
        <div class="cf-user-role"><?= e($role) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="cf-sidebar-search">
    <div class="input-group input-group-sm">
      <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
      <input type="text" class="form-control" placeholder="חיפוש בתפריט" data-cgf-sidebar-search>
    </div>
  </div>

  <div class="cf-sidebar-section-title">תפריט</div>
  <nav class="cf-sidebar-nav" aria-label="Sidebar navigation">
    <?php foreach ($items as $it): ?>
      <?php $isActive = ($active === $it['key']); ?>
      <a href="<?= e($it['href']) ?>"
         class="cf-nav-item <?= $isActive ? 'is-active' : '' ?>"
         title="<?= e($it['label']) ?>"
         data-cgf-sidebar-item>
        <span class="cf-nav-icon"><i class="<?= e($it['icon']) ?>"></i></span>
        <span class="cf-nav-text"><?= e($it['label']) ?></span>
        <span class="cf-nav-arrow" aria-hidden="true"><i class="fa-solid fa-angle-left"></i></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="cf-sidebar-section-title">קיצורי דרך</div>
  <div class="cf-sidebar-actions">
    <a class="btn btn-sm cf-btn-soft w-100" href="index.php?r=employees/create"><i class="fa-solid fa-user-plus"></i><span class="cf-btn-text">עובד חדש</span></a>
    <a class="btn btn-sm cf-btn-soft w-100" href="index.php?r=employers/create"><i class="fa-solid fa-user-group"></i><span class="cf-btn-text">מעסיק חדש</span></a>
    <a class="btn btn-sm cf-btn-soft w-100" href="index.php?r=placements/create"><i class="fa-solid fa-handshake-angle"></i><span class="cf-btn-text">שיבוץ חדש</span></a>
    <a class="btn btn-sm cf-btn-soft w-100" href="index.php?r=home_visits/create"><i class="fa-solid fa-house-medical"></i><span class="cf-btn-text">ביקור חדש</span></a>
  </div>

  <div class="cf-sidebar-footer">
    <a class="btn btn-sm cf-btn-logout w-100" href="index.php?r=auth/logout"><i class="fa-solid fa-right-from-bracket"></i><span class="cf-btn-text">יציאה</span></a>
  </div>
</div>
