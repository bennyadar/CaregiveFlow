<?php
// CaregiveFlow — Home Visits Module
// File: src/controllers/HomeVisitsController.php
// HE: בקר ביקורי בית — חתימות/מבנה אחד-לאחד עם המודולים הקיימים
// EN: Controller for Home Visits — aligned 1:1 with existing modules

require_once __DIR__ . '/../models/HomeVisit.php';
require_once __DIR__ . '/../services/HomeVisitService.php';

class HomeVisitsController {
    public static function index(PDO $db) {
        $filters = [
            'employee_id' => $_GET['employee_id'] ?? null,
            'status_codes' => !empty($_GET['status_codes']) ? (array)$_GET['status_codes'] : [],
            'type_codes' => !empty($_GET['type_codes']) ? (array)$_GET['type_codes'] : [],
            'stage_codes' => !empty($_GET['stage_codes']) ? (array)$_GET['stage_codes'] : [],
            'placement_type_codes' => !empty($_GET['placement_type_codes']) ? (array)$_GET['placement_type_codes'] : [],
            'followup_required' => isset($_GET['followup_required']) ? $_GET['followup_required'] : '',
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
        ];
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 25; $offset = ($page-1) * $limit;
        $rows = HomeVisit::all($db, $filters, $limit, $offset);
        $total = HomeVisit::count($db, $filters);
        $pages = (int)ceil($total / $limit);
        require __DIR__ . '/../../views/home_visits/index.php';
    }

    public static function create(PDO $db) {
        $employee_id = $_GET['employee_id'] ?? null; // prefill from Employee card
        $row = [
            'employee_id' => $employee_id,
            'visit_date' => date('Y-m-d'),
            'visit_type_code' => null,
            'status_code' => 1, // מתוכנן
            'home_visit_stage_code' => null,
            'placement_type_code' => null,
            'visited_by_user_id' => null,
            'summary' => '', 'findings' => '',
            'followup_required' => 0, 'next_visit_due' => null,
        ];
        require __DIR__ . '/../../views/home_visits/form.php';
    }

    public static function store(PDO $db) {
        try {
            $data = $_POST;
            HomeVisitService::validate($data);
            if (empty($data['placement_id'])) {
                $data['placement_id'] = HomeVisitService::resolvePlacementId($db, (int)$data['employee_id'], $data['visit_date']);
            }
            $id = HomeVisit::create($db, $data);
            header('Location: index.php?r=home_visits/view&id=' . (int)$id);
        } catch (Throwable $e) {
            $error = $e->getMessage();
            $row = $_POST; // re-fill form
            require __DIR__ . '/../../views/home_visits/form.php';
        }
    }

    public static function edit(PDO $db) {
        $id = (int)($_GET['id'] ?? 0);
        $row = HomeVisit::find($db, $id);
        if (!$row) { http_response_code(404); echo 'Not Found'; return; }
        require __DIR__ . '/../../views/home_visits/form.php';
    }

    public static function update(PDO $db) {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $data = $_POST;
            HomeVisitService::validate($data);
            if (empty($data['placement_id'])) {
                $data['placement_id'] = HomeVisitService::resolvePlacementId($db, (int)$data['employee_id'], $data['visit_date']);
            }
            HomeVisit::update($db, $id, $data);
            header('Location: index.php?r=home_visits/view&id=' . (int)$id);
        } catch (Throwable $e) {
            $error = $e->getMessage();
            $row = $_POST; $row['id'] = $id;
            require __DIR__ . '/../../views/home_visits/form.php';
        }
    }

    public static function view(PDO $db) {
        $id = (int)($_GET['id'] ?? 0);
        $row = HomeVisit::find($db, $id);
        if (!$row) { http_response_code(404); echo 'Not Found'; return; }
        require __DIR__ . '/../../views/home_visits/view.php';
    }

    public static function delete(PDO $db) {
        $id = (int)($_POST['id'] ?? 0);
        HomeVisit::delete($db, $id);
        header('Location: index.php?r=home_visits/index');
    }

    // Report (CSV v1) — UTF-8 BOM for Excel
    public static function export(PDO $db) {
        $filters = [
            'employee_id' => $_GET['employee_id'] ?? null,
            'status_codes' => !empty($_GET['status_codes']) ? (array)$_GET['status_codes'] : [],
            'type_codes' => !empty($_GET['type_codes']) ? (array)$_GET['type_codes'] : [],
            'stage_codes' => !empty($_GET['stage_codes']) ? (array)$_GET['stage_codes'] : [],
            'placement_type_codes' => !empty($_GET['placement_type_codes']) ? (array)$_GET['placement_type_codes'] : [],
            'followup_required' => isset($_GET['followup_required']) ? $_GET['followup_required'] : '',
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
        ];
        $limit = (int)($_GET['limit'] ?? 50000);
        $rows = HomeVisit::all($db, $filters, $limit, 0);

        $dir = __DIR__ . '/../../exports/' . date('Y/m');
        if (!is_dir($dir)) { mkdir($dir, 0775, true); }
        $file = $dir . '/home_visits_report_' . date('Ymd_His') . '_user.csv';

        $fp = fopen($file, 'w');
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, [
            'מזהה מטופל/מעסיק', 'שם משפחה (מטופל)', 'שם פרטי (מטופל)', 'יישוב', 'רחוב', 'טלפון (מטופל/בן משפחה)',
            'שם משפחה (עובד)', 'שם פרטי (עובד)', 'פלאפון (עובד)', 'מספר דרכון', 'ארץ מוצא', 'תאריך הביקור',
            'סוג ביקור', 'שלב', 'סטטוס', 'סוג השמה', 'נדרש מעקב', 'ביקור הבא'
        ]);
        foreach ($rows as $r) {
            $employeePhone = trim(($r['phone_prefix_il'] ?? '') . ($r['phone_number_il'] ?? ''));
            fputcsv($fp, [
                '', '', '', '', '', '', // subject/employer fields left blank in v1
                $r['employee_last_name'] ?? '',
                $r['employee_first_name'] ?? '',
                $employeePhone,
                $r['passport_number'] ?? '',
                $r['country_of_citizenship'] ?? '',
                $r['visit_date'],
                $r['type_name'] ?? '',
                $r['stage_name'] ?? '',
                $r['status_name'] ?? '',
                $r['placement_type_name'] ?? '',
                !empty($r['followup_required']) ? '✔' : '',
                $r['next_visit_due'] ?? ''
            ]);
        }
        fclose($fp);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . basename($file));
        readfile($file);
        exit;
    }
}
