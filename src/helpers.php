<?php
function app_config() {
    static $cfg = null;
    if (!$cfg) $cfg = require __DIR__ . '/config.php';
    return $cfg;
}
function start_session() {
    $cfg = app_config();
    if (session_status() === PHP_SESSION_NONE) {
        session_name($cfg['app']['session_name']);
        session_start();
    }
}
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'; }
function e($str): string { return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
// function redirect(string $route, array $params = []) {
//     $q = http_build_query(array_merge(['r' => $route], $params));
//     header('Location: index.php?' . $q);
//     exit;
// }
function redirect(string $route, array $params = []): void {
    // היה: http_build_query(['r' => $route] + $params)  -> גורם ל- exports%2Fpiba
    $qs = $params ? '&' . http_build_query($params, '', '&', PHP_QUERY_RFC3986) : '';
    header('Location: index.php?r=' . $route . $qs);
    exit;
}
function flash(?string $msg = null, string $type = 'success') {
    start_session();
    if ($msg !== null) {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
        return;
    }
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}
function require_login(?string $role = null) {
    start_session();
    if (empty($_SESSION['user'])) {
        flash('יש להתחבר למערכת.', 'danger');
        redirect('auth/login');
    }
    if ($role && ($_SESSION['user']['role'] ?? null) !== $role) {
        // allow admin to pass any role check
        if (($_SESSION['user']['role'] ?? null) !== 'admin') {
            flash('אין לך הרשאה לפעולה זו.', 'danger');
            redirect('dashboard/index');
        }
    }
}
function current_user() {
    start_session();
    return $_SESSION['user'] ?? null;
}
// ייצור/שליפת טוקן CSRF לשימוש בטופס
function csrf_field(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

// בדיקת CSRF בבקשות POST (כבר קראת לה בקונטרולר: csrf();)
function csrf(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') return;

    $sent = $_POST['csrf'] ?? '';
    $sess = $_SESSION['csrf'] ?? '';
    if (!$sent || !$sess || !hash_equals($sess, $sent)) {
        http_response_code(400);
        throw new RuntimeException('Invalid CSRF token');
    }
}

function date_to_string(?string $date): string {
    if (!$date) return '';
    $d = new DateTime($date);
    return $d->format('d/m/Y');
}

function pre_print($v, $exit = 0): void {
    echo '<pre>' . print_r($v, true) . '</pre>';
}   

/**
 * עדכון פרמטרים ב-query string תוך שמירה על הקיימים.
 */
function update_query(array $params = []): string {
    $current = $_GET ?? [];
    $merged = array_merge($current, $params);
    return '?' . http_build_query($merged, '', '&', PHP_QUERY_RFC3986);
}

/**
 * Breadcrumbs – AdminLTE-style (Home / Module / Page)
 *
 * שימוש:
 *   $breadcrumbs = cgf_breadcrumbs($title);
 *   (ברירת מחדל – page_header.php קורא לזה אוטומטית אם לא הוגדר)
 */
function cgf_route_parts(): array {
    $route = (string)($_GET['r'] ?? 'dashboard/index');
    $parts = explode('/', $route, 2);
    $ctrl = $parts[0] ?: 'dashboard';
    $action = $parts[1] ?? 'index';
    $action = $action ?: 'index';
    return [$ctrl, $action];
}

function cgf_module_label(string $ctrl): string {
    $map = [
        'dashboard'       => 'ראשי',
        'agency_settings' => 'פרטי לשכה',
        'employees'       => 'עובדים',
        'employers'       => 'מעסיקים',
        'placements'      => 'שיבוצים',
        'home_visits'     => 'ביקורי בית',
        'reports'         => 'דו"חות',
        'exports'         => 'ייצואים',
        'auth'            => 'אימות',
    ];
    return $map[$ctrl] ?? $ctrl;
}

function cgf_module_href(string $ctrl): ?string {
    $map = [
        'dashboard'       => 'index.php?r=dashboard/index',
        'agency_settings' => 'index.php?r=agency_settings/index',
        'employees'       => 'index.php?r=employees/index',
        'employers'       => 'index.php?r=employers/index',
        'placements'      => 'index.php?r=placements/index',
        'home_visits'     => 'index.php?r=home_visits',
        'reports'         => 'index.php?r=reports/placements_active',
        'exports'         => 'index.php?r=exports/history',
    ];
    return $map[$ctrl] ?? null;
}

function cgf_action_label(string $ctrl, string $action): string {
    $action = strtolower($action ?: 'index');

    // מיפוי ספציפי לדוחות
    if ($ctrl === 'reports') {
        $map = [
            'placements_active'  => 'שיבוצים פעילים',
            'placements_ending'  => 'שיבוצים מסתיימים',
            'placements_history' => 'היסטוריית שיבוצים',
        ];
        return $map[$action] ?? 'דו"חות';
    }

    // מיפוי כללי
    $map = [
        'index'  => cgf_module_label($ctrl),
        'create' => 'חדש',
        'edit'   => 'עריכה',
        'show'   => 'פרטים',
        'view'   => 'פרטים',
        'history'=> 'היסטוריה',
        'login'  => 'כניסה',
    ];
    return $map[$action] ?? $action;
}

function cgf_breadcrumbs(?string $title = null): array {
    [$ctrl, $action] = cgf_route_parts();
    $ctrl = (string)$ctrl;
    $action = (string)$action;

    $crumbs = [];
    $crumbs[] = ['label' => 'ראשי', 'href' => 'index.php?r=dashboard/index'];

    if ($ctrl !== 'dashboard') {
        $crumbs[] = ['label' => cgf_module_label($ctrl), 'href' => cgf_module_href($ctrl)];
    }

    $pageLabel = trim((string)($title ?? ''));
    if ($pageLabel === '') {
        $pageLabel = cgf_action_label($ctrl, $action);
    }

    // אם התווית זהה למודול – לא מוסיפים קרח נפרד
    $moduleLabel = cgf_module_label($ctrl);
    if ($ctrl === 'dashboard' && $pageLabel === 'ראשי') {
        return [['label' => 'ראשי', 'href' => null]];
    }
    if ($ctrl !== 'dashboard' && $pageLabel === $moduleLabel) {
        // מודול בלבד (למשל Index)
        $crumbs[count($crumbs) - 1]['href'] = null;
        return $crumbs;
    }

    $crumbs[] = ['label' => $pageLabel, 'href' => null];
    return $crumbs;
}