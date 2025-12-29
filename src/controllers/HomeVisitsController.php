<?php
require_once __DIR__ . '/../models/HomeVisit.php';
require_once __DIR__ . '/../services/HomeVisitService.php';

require_once __DIR__ . '/../models/EmployeeDocument.php';
require_once __DIR__ . '/../services/DocumentService.php';

class HomeVisitsController
{
    public static function index(PDO $pdo)
    {
        require_login();
        $m = new HomeVisit($pdo);

        $filters = [
            'employee_id'   => $_GET['employee_id'] ?? null,
            'status'        => $_GET['status'] ?? null,        // מספרי או 'overdue'
            'type'          => $_GET['type'] ?? null,
            'stage'         => $_GET['stage'] ?? null,
            'q'             => $_GET['q'] ?? null,
            'due_until'     => $_GET['due_until'] ?? null,
            'date_from'     => $_GET['date_from'] ?? null,
            'date_to'       => $_GET['date_to'] ?? null,
            'followup_only' => $_GET['followup_only'] ?? null, // 1/0
        ];

        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = min(100, max(5, (int)($_GET['per_page'] ?? 25)));
        $offset = max(0, ($page - 1) * $limit);

        $rows  = $m->all($filters, $limit, $offset);
        $total = $m->count($filters);
        $pages = (int)ceil($total / $limit);

        $employees            = self::employees_for_select($pdo);
        $status_codes         = self::status_codes($pdo);
        $type_codes           = self::type_codes($pdo);
        $stage_codes          = self::stage_codes($pdo);
        $placement_type_codes = self::placement_type_codes($pdo);

        require __DIR__ . '/../../views/home_visits/index.php';
    }

    public static function create(PDO $pdo)
    {
        require_login();
        $m = new HomeVisit($pdo);

        $errors = [];
        $data = [
            'employee_id'           => $_GET['employee_id'] ?? '',
            'placement_id'          => '',
            'visit_date'            => '',
            'visit_type_code'       => '',
            'status_code'           => '',
            'home_visit_stage_code' => '',
            'placement_type_code'   => '',
            'visited_by_user_id'    => '',
            'summary'               => '',
            'findings'              => '',
            'followup_required'     => 0,
            'next_visit_due'        => '',
        ];

        if (is_post()) {
            $data = [
                'employee_id'           => $_POST['employee_id'] ?? '',
                'placement_id'          => $_POST['placement_id'] ?? '',
                'visit_date'            => $_POST['visit_date'] ?? '',
                'visit_type_code'       => $_POST['visit_type_code'] ?? '',
                'status_code'           => $_POST['status_code'] ?? '',
                'home_visit_stage_code' => $_POST['home_visit_stage_code'] ?? '',
                'placement_type_code'   => $_POST['placement_type_code'] ?? '',
                'visited_by_user_id'    => $_POST['visited_by_user_id'] ?? '',
                'summary'               => $_POST['summary'] ?? '',
                'findings'              => $_POST['findings'] ?? '',
                'followup_required'     => $_POST['followup_required'] ?? 0,
                'next_visit_due'        => $_POST['next_visit_due'] ?? '',
            ];

            $errors = HomeVisitService::validate($data);
            if (empty($errors)) {
                try {
                    $data = HomeVisitService::normalize($data);
                    $m->create($data);
                    flash('נוצר בהצלחה.');
                    redirect('home_visits');
                } catch (PDOException $e) {
                    $errors['general'] = 'שגיאה בשמירה: ' . $e->getMessage();
                }
            }
        }

        $employees            = self::employees_for_select($pdo);
        $status_codes         = self::status_codes($pdo);
        $type_codes           = self::type_codes($pdo);
        $stage_codes          = self::stage_codes($pdo);
        $placement_type_codes = self::placement_type_codes($pdo);

        require __DIR__ . '/../../views/home_visits/form.php';
    }

    public static function view(PDO $pdo)
    {
        require_login();
        $m = new HomeVisit($pdo);

        $id = (int)($_GET['id'] ?? 0);
        $item = $m->find($id);
        if (!$item) { flash('ביקור בית לא נמצא.', 'danger'); redirect('home_visits'); }

        // עובד לתצוגה
        $emp = self::employee_brief($pdo, (int)$item['employee_id']);

        // קבצים מצורפים
        $docModel  = new EmployeeDocument($pdo);
        $documents = $docModel->listFor((int)$item['employee_id'], 'home_visits', (int)$item['id']);

        // סוגי מסמכים (מסנן לפי מודול = home_visits; אם אין – כל הפעילים)
        $docTypes = self::document_types($pdo, 'home_visits');

        $status_codes         = self::status_codes($pdo);
        $type_codes           = self::type_codes($pdo);
        $stage_codes          = self::stage_codes($pdo);
        $placement_type_codes = self::placement_type_codes($pdo);

        // סטטוס נגזר + ימים עד "יעד"
        $derived_status = HomeVisitService::derivedStatusCode($item['status_code'], $item['next_visit_due']);
        $days_left      = HomeVisitService::daysUntilExpiry($item['next_visit_due']);

        require __DIR__ . '/../../views/home_visits/view.php';
    }

    public static function edit(PDO $pdo)
    {
        require_login();
        $m = new HomeVisit($pdo);

        $id = (int)($_GET['id'] ?? 0);
        $item = $m->find($id);
        if (!$item) { flash('ביקור בית לא נמצא.', 'danger'); redirect('home_visits'); }

        $errors = [];
        $data   = $item;

        if (is_post()) {
            $data = [
                'employee_id'           => $_POST['employee_id'] ?? $item['employee_id'],
                'placement_id'          => $_POST['placement_id'] ?? $item['placement_id'],
                'visit_date'            => $_POST['visit_date'] ?? $item['visit_date'],
                'visit_type_code'       => $_POST['visit_type_code'] ?? $item['visit_type_code'],
                'status_code'           => $_POST['status_code'] ?? $item['status_code'],
                'home_visit_stage_code' => $_POST['home_visit_stage_code'] ?? $item['home_visit_stage_code'],
                'placement_type_code'   => $_POST['placement_type_code'] ?? $item['placement_type_code'],
                'visited_by_user_id'    => $_POST['visited_by_user_id'] ?? $item['visited_by_user_id'],
                'summary'               => $_POST['summary'] ?? $item['summary'],
                'findings'              => $_POST['findings'] ?? $item['findings'],
                'followup_required'     => $_POST['followup_required'] ?? $item['followup_required'],
                'next_visit_due'        => $_POST['next_visit_due'] ?? $item['next_visit_due'],
            ];

            $errors = HomeVisitService::validate($data);
            if (empty($errors)) {
                try {
                    $data = HomeVisitService::normalize($data);
                    $m->update($id, $data);
                    flash('עודכן בהצלחה.');
                    redirect('home_visits');
                } catch (PDOException $e) {
                    $errors['general'] = 'שגיאה בעדכון: ' . $e->getMessage();
                }
            }
        }

        $employees            = self::employees_for_select($pdo);
        $status_codes         = self::status_codes($pdo);
        $type_codes           = self::type_codes($pdo);
        $stage_codes          = self::stage_codes($pdo);
        $placement_type_codes = self::placement_type_codes($pdo);

        require __DIR__ . '/../../views/home_visits/form.php';
    }

    public static function delete(PDO $pdo)
    {
        require_login();
        $m = new HomeVisit($pdo);

        $id = (int)($_GET['id'] ?? 0);
        $item = $m->find($id);
        if (!$item) { http_response_code(404); echo 'Not found'; return; }

        try {
            $m->delete($id);
            flash('נמחק בהצלחה.');
        } catch (PDOException $e) {
            flash('שגיאה במחיקה: ' . $e->getMessage(), 'danger');
        }
        redirect('home_visits');
    }

    public static function upload_document(PDO $pdo)
    {
        require_login();

        $visitId    = (int)($_POST['visit_id'] ?? 0);
        $employeeId = (int)($_POST['employee_id'] ?? 0);
        $docType    = trim($_POST['doc_type'] ?? '');

        try {
            if ($visitId <= 0 || $employeeId <= 0) {
                throw new RuntimeException('נתונים חסרים.');
            }
            if ($docType === '') {
                throw new RuntimeException('בחר סוג קובץ.');
            }

            $svc = new DocumentService($pdo);

            // חשוב: להעביר array ולא null, ולהעביר docType לפי החתימה אצלך
            $relativePath = $svc->storeUploadedFile(
                $_FILES['file'] ?? [],
                $employeeId,
                'home_visits',
                $visitId,
                $docType
            );

            $docModel = new EmployeeDocument($pdo);
            $docModel->create([
                'employee_id'   => $employeeId,
                'related_table' => 'home_visits',
                'related_id'    => $visitId,
                'doc_type'      => $docType,
                'file_path'     => $relativePath,
                'issued_at'     => $_POST['issued_at'] ?? null,
                'expires_at'    => $_POST['expires_at'] ?? null,
                'notes'         => $_POST['notes'] ?? null,
                'uploaded_by'   => $_SESSION['user']['id'] ?? null,
            ]);

            flash('הקובץ הועלה בהצלחה.');
        } catch (Throwable $e) {
            flash('שגיאה בהעלאה: ' . $e->getMessage(), 'danger');
        }

        redirect('home_visits/view&id=' . $visitId);
    }

    public static function delete_document(PDO $pdo)
    {
        require_login();

        $docId   = (int)($_GET['doc_id'] ?? 0);
        $visitId = (int)($_GET['visit_id'] ?? 0);

        try {
            if ($docId <= 0 || $visitId <= 0) {
                throw new RuntimeException('נתונים חסרים.');
            }

            $docModel = new EmployeeDocument($pdo);
            $doc = $docModel->find($docId);
            if (!$doc) {
                throw new RuntimeException('מסמך לא נמצא.');
            }

            if (($doc['related_table'] ?? '') !== 'home_visits' || (int)($doc['related_id'] ?? 0) !== $visitId) {
                throw new RuntimeException('אין הרשאה למחיקת מסמך זה.');
            }

            $svc = new DocumentService($pdo);

            // אצלך קיימת deletePhysical()
            if (!empty($doc['file_path'])) {
                $svc->deletePhysical($doc['file_path']);
            }

            $docModel->delete($docId);
            flash('המסמך נמחק.');
        } catch (Throwable $e) {
            flash('שגיאה במחיקה: ' . $e->getMessage(), 'danger');
        }

        redirect('home_visits/view&id=' . $visitId);
    }


    private static function document_types(PDO $pdo, string $moduleKey): array
    {
        // זהה ל־PassportsController: מסנן לפי module_key, ואם אין – מחזיר כל הפעילים
        $st = $pdo->prepare('SELECT code, name_he FROM document_types WHERE is_active=1 AND (module_key=? OR module_key IS NULL) ORDER BY name_he');
        $st->execute([$moduleKey]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private static function employees_for_select(PDO $pdo): array
    {
        $st = $pdo->query("SELECT id, first_name, last_name, passport_number FROM employees ORDER BY last_name, first_name");
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private static function status_codes(PDO $pdo): array
    {
        $st = $pdo->query("SELECT home_visit_status_code AS code, name_he FROM home_visit_status_codes ORDER BY home_visit_status_code");
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($rows as $r) { $out[(int)$r['code']] = $r['name_he']; }

        // כמו passports עם 'expired' – כאן 'overdue'
        $out['overdue'] = 'באיחור';
        return $out;
    }

    private static function type_codes(PDO $pdo): array
    {
        $st = $pdo->query("SELECT home_visit_type_code AS code, name_he FROM home_visit_type_codes ORDER BY home_visit_type_code");
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($rows as $r) { $out[(int)$r['code']] = $r['name_he']; }
        return $out;
    }

    private static function stage_codes(PDO $pdo): array
    {
        $st = $pdo->query("SELECT home_visit_stage_code AS code, name_he FROM home_visit_stage_codes ORDER BY home_visit_stage_code");
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($rows as $r) { $out[(int)$r['code']] = $r['name_he']; }
        return $out;
    }

    private static function placement_type_codes(PDO $pdo): array
    {
        $st = $pdo->query("SELECT placement_type_code AS code, name_he FROM placement_type_codes ORDER BY placement_type_code");
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($rows as $r) { $out[(int)$r['code']] = $r['name_he']; }
        return $out;
    }

    private static function employee_brief(PDO $pdo, int $employeeId): ?array
    {
        if ($employeeId <= 0) return null;
        $st = $pdo->prepare('SELECT id, first_name, last_name FROM employees WHERE id=?');
        $st->execute([$employeeId]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
