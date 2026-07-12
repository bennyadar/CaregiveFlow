<?php
start_session();
$u = current_user();
$role = $u['role'] ?? null;

// ===== Layout: Right Sidebar (AdminLTE-like) =====
// כאשר משתמש מחובר – מציגים Sidebar ימני.
// ניתן לכבות בדף ספציפי ע"י הגדרת $disableRightSidebar = true לפני include של header.php
$__hasUser = (bool)$u;
$__disableRightSidebar = (bool)($disableRightSidebar ?? false);
$__showRightSidebar = $__hasUser && !$__disableRightSidebar;

// שומר לפוטר כדי לסגור תגיות בצורה בטוחה
$GLOBALS['__cgf_has_right_sidebar'] = $__showRightSidebar;
?>
<!doctype html>
<html lang="he" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
<title>CaregiveFlow</title>
</head>
<body class="<?= $__showRightSidebar ? 'has-cf-sidebar' : '' ?>">
<nav class="navbar navbar-expand-lg navbar-light navbar-soft border-bottom">
  <div class="container-fluid">
<!--
    Logo example:
  <a class="navbar-brand d-flex align-items-center" href="index.php?r=dashboard/index">
      <img src="assets/img/logo.jpg" alt="CaregiveFlow" class="brand-logo me-2">
    </a>
-->
    <?php if ($__showRightSidebar): ?>
      <!-- AdminLTE-style: Sidebar toggle (Mini/Expand) -->
      <button
        class="btn btn-sm btn-outline-secondary ms-2 d-none d-lg-inline-flex align-items-center cf-sidebar-toggle"
        type="button"
        aria-label="צמצום/הרחבה תפריט"
        aria-pressed="false"
        data-cgf-sidebar-toggle>
        <i class="fa-solid fa-bars"></i>
      </button>
    <?php endif; ?>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbars" aria-controls="navbars" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbars">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php if ($u): ?>
          <li class="nav-item"><a class="nav-link" href="index.php?r=dashboard/index">ראשי</a></li>
          <li class="nav-item"><a class="nav-link" href="index.php?r=agency_settings/index">פרטי לשכה</a></li>
          <li class="nav-item"><a class="nav-link" href="index.php?r=employees/index">עובדים</a></li>
          <li class="nav-item"><a class="nav-link" href="index.php?r=employers/index">מעסיקים</a></li>
          <li class="nav-item"><a class="nav-link" href="index.php?r=placements/index">שיבוצים</a></li>
          <li class="nav-item"><a class="nav-link" href="index.php?r=home_visits">ביקורי בית</a></li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">דו"חות</a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="index.php?r=reports/placements_active">שיבוצים פעילים</a></li>
              <li><a class="dropdown-item" href="index.php?r=reports/placements_ending">סיום קרוב</a></li>
              <li><a class="dropdown-item" href="index.php?r=reports/placements_history">היסטוריית שיבוצים לעובד</a></li>
            </ul>
          </li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav ms-auto">
        <?php if ($u): ?>
          <li class="nav-item">
            <span class="navbar-text me-3 text-secondary">
              <?= e($u['display_name'] ?? $u['username']) ?>
              <span class="badge bg-secondary-subtle text-dark border badge-role"><?= e($u['role']) ?></span>
            </span>
          </li>
          <li class="nav-item"><a class="nav-link" href="index.php?r=auth/logout">יציאה</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="index.php?r=auth/login">כניסה</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<?php if ($__showRightSidebar): ?>
  <!--
    AdminLTE-like layout:
    - Sidebar קבוע (fixed) בצד ימין לאורך כל הגובה
    - התוכן מקבל margin-right בהתאם לרוחב הסרגל
  -->
  <div class="cf-app">
    <aside class="cf-sidebar" aria-label="Right sidebar" data-cgf-sidebar>
      <?php include __DIR__ . '/../partials/right_sidebar.php'; ?>
    </aside>

    <main class="cf-main">
      <div class="cf-content container-fluid my-4">
        <?php if ($f = flash()): ?>
          <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
        <?php endif; ?>

<?php else: ?>
  <main class="container my-4">
    <?php if ($f = flash()): ?>
      <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
    <?php endif; ?>
<?php endif; ?>
