<?php
require_once __DIR__ . '/../models/Insurance.php';
require_once __DIR__ . '/../models/EmployeeDocument.php';
require_once __DIR__ . '/../services/DocumentService.php';
require_once __DIR__ . '/../services/InsuranceService.php';

class InsurancesController
{
    public static function index(PDO $pdo)
    {
        require_login();
        $m = new Insurance($pdo);

        // סינון
        $filters = [
            'employee_id'   => $_GET['employee_id'] ?? null,
            // שימו לב: יכול להיות ערך מספרי מקוד הסטטוסים, או המחרוזת 'expired'
            'status'        => $_GET['status'] ?? null,
            'q'             => $_GET['q'] ?? null,
            'expires_until' => $_GET['expires_until'] ?? null,
        ];

        // פאג'ינציה בסיסית
        $page  = max(1, (int)($_GET['page'] ?? 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $items = $m->all($filters, $limit, $offset);
        $total = $m->count($filters);
        $pages = (int)ceil($total / $limit);

        // רשימת עובדים לבחירה (id + שם)
        $employees = self::employees_for_select($pdo);

        // קודי סטטוס/סוג לבחירה
        $status_codes = self::status_codes($pdo);     // [code => name]
        $type_codes   = self::type_codes($pdo);       // [code => name]

        require __DIR__.'/../../views/insurances/index.php';
    }

    public static function create(PDO $pdo)
    {
        require_login();
        $m = new Insurance($pdo);

        $errors = [];
        $data = [
            'employee_id'         => $_GET['employee_id'] ?? '', // פתיחה מקונטקסט עובד
            'policy_number'       => '',
            'insurer_name'        => '',
            'insurance_type_code' => '',
            'request_date'        => '',
            'issue_date'          => '',
            'expiry_date'         => '',
            'status'              => '', // לשדה סינון בלבד; בטופס נעבוד עם status_code
            'status_code'         => '',
            'notes'               => '',
        ];

        if (is_post()) {
            $data = [
                'employee_id'         => $_POST['employee_id'] ?? '',
                'policy_number'       => $_POST['policy_number'] ?? '',
                'insurer_name'        => $_POST['insurer_name'] ?? '',
                'insurance_type_code' => $_POST['insurance_type_code'] ?? '',
                'request_date'        => $_POST['request_date'] ?? '',
                'issue_date'          => $_POST['issue_date'] ?? '',
                'expiry_date'         => $_POST['expiry_date'] ?? '',
                'status_code'         => $_POST['status_code'] ?? '',
                'notes'               => $_POST['notes'] ?? '',
            ];

            $errors = InsuranceService::validate($data);
            if (!$errors) {
                try {
                    $id = $m->create($data);
                    flash('נוצר בהצלחה.');
                    redirect('insurances');
                } catch (PDOException $e) {
                    $errors['general'] = 'שגיאה בשמירה: '.$e->getMessage();
                }
            }
        }

        $employees    = self::employees_for_select($pdo);
        $status_codes = self::status_codes($pdo);
        $type_codes   = self::type_codes($pdo);
        require __DIR__.'/../../views/insurances/form.php';
    }

    public static function edit(PDO $pdo)
    {
        require_login();
        $m = new Insurance($pdo);
        $id = (int)($_GET['id'] ?? 0);
        $item = $m->find($id);
        if (!$item) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        $errors = [];
        $data = $item; // ערכים התחלתיים

        if (is_post()) {
            $data = [
                'employee_id'         => $_POST['employee_id'] ?? $item['employee_id'],
                'policy_number'       => $_POST['policy_number'] ?? $item['policy_number'],
                'insurer_name'        => $_POST['insurer_name'] ?? $item['insurer_name'],
                'insurance_type_code' => $_POST['insurance_type_code'] ?? $item['insurance_type_code'],
                'request_date'        => $_POST['request_date'] ?? $item['request_date'],
                'issue_date'          => $_POST['issue_date'] ?? $item['issue_date'],
                'expiry_date'         => $_POST['expiry_date'] ?? $item['expiry_date'],
                'status_code'         => $_POST['status_code'] ?? $item['status_code'],
                'notes'               => $_POST['notes'] ?? $item['notes'],
            ];

            $errors = InsuranceService::validate($data);
            if (!$errors) {
                try {
                    $m->update($id, $data);
                    flash('עודכן בהצלחה.');
                    redirect('insurances');
                } catch (PDOException $e) {
                    $errors['general'] = 'שגיאה בעדכון: '.$e->getMessage();
                }
            }
        }

        $employees    = self::employees_for_select($pdo);
        $status_codes = self::status_codes($pdo);
        $type_codes   = self::type_codes($pdo);
        require __DIR__.'/../../views/insurances/form.php';
    }

    public static function view(PDO $pdo)
    {
        require_login();
        $m = new Insurance($pdo);
        $id = (int)($_GET['id'] ?? 0);
        $item = $m->find($id);
        if (!$item) { flash('ביטוח לא נמצא.', 'danger'); redirect('insurances'); }

        $emp = null;
        if (!empty($item['employee_id'])) {
            $st = $pdo->prepare('SELECT id, first_name, last_name FROM employees WHERE id = ?');
            $st->execute([(int)$item['employee_id']]);
            $emp = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        $docModel  = new EmployeeDocument($pdo);
        $documents = $docModel->listFor((int)$item['employee_id'], 'employee_insurances', (int)$item['id']);

        $docTypes = self::document_types($pdo, 'insurances');

        $data = $item;        

        // נגזרים לתצוגה
        $derived_status_code = InsuranceService::derivedStatusCode($item['status_code'] ?? null, $item['expiry_date'] ?? null);
        $days_left = InsuranceService::daysUntilExpiry($item['expiry_date'] ?? null);

        $emp = self::employee_brief($pdo, (int)$item['employee_id']);
        $status_name = self::status_codes($pdo)[$item['status_code']] ?? null;
        $type_name   = $item['insurance_type_code'] !== null ? (self::type_codes($pdo)[$item['insurance_type_code']] ?? null) : null;

        require __DIR__.'/../../views/insurances/view.php';
    }

    public static function delete(PDO $pdo)
    {
        require_login();
        $m = new Insurance($pdo);
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            $m->delete($id);
            flash('נמחק בהצלחה.');
        }
        redirect('insurances');
    }

    public static function upload_document(PDO $pdo)
    {
        require_login();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('insurances'); }

        $insuranceId = (int)($_POST['insurance_id'] ?? 0);
        $employeeId  = (int)($_POST['employee_id'] ?? 0);
        $docType     = trim($_POST['doc_type'] ?? '');
        if ($docType === '') { $docType = 'insurance'; } // ברירת מחדל למודול הביטוחים

        try {
            if ($insuranceId <= 0 || $employeeId <= 0) { throw new RuntimeException('נתונים חסרים.'); }

            $svc = new DocumentService($pdo);
            $relativePath = $svc->storeUploadedFile($_FILES['file'] ?? [], $employeeId, 'insurances', $insuranceId, $docType);

            $docModel = new EmployeeDocument($pdo);
            $docModel->create([
                'employee_id'   => $employeeId,
                'related_table' => 'employee_insurances',
                'related_id'    => $insuranceId,
                'doc_type'      => $docType,
                'file_path'     => $relativePath,
                'issued_at'     => $_POST['issued_at']  ?: null,
                'expires_at'    => $_POST['expires_at'] ?: null,
                'notes'         => $_POST['notes']      ?: null,
                'uploaded_by'   => (int)($_SESSION['user']['id'] ?? 0),
            ]);

            flash('הקובץ הועלה בהצלחה.', 'success');
        } catch (Throwable $e) {
            flash('שגיאה בהעלאת הקובץ: ' . $e->getMessage(), 'danger');
        }
        redirect('insurances/view&id=' . $insuranceId . '&employee_id=' . $employeeId);
    }

    public static function delete_document(PDO $pdo)
    {
        require_login();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('insurances'); }

        $docId       = (int)($_POST['doc_id'] ?? 0);
        $insuranceId = (int)($_POST['insurance_id'] ?? 0);

        try {
            $docModel = new EmployeeDocument($pdo);
            $row = $docModel->find($docId);
            if (!$row || (int)$row['related_id'] !== $insuranceId || $row['related_table'] !== 'employee_insurances') {
                throw new RuntimeException('מסמך לא נמצא או לא שייך לרשומת ביטוח זו.');
            }
            $svc = new DocumentService($pdo);
            if (!empty($row['file_path'])) { $svc->deletePhysical($row['file_path']); }
            $docModel->delete($docId);
            flash('הקובץ נמחק.', 'success');
        } catch (Throwable $e) {
            flash('שגיאה במחיקה: ' . $e->getMessage(), 'danger');
        }
        redirect('insurances/view&id=' . $insuranceId . '&employee_id=' . ($row['employee_id'] ?? 0));
    }

    private static function document_types(PDO $pdo, ?string $moduleKey = null): array
    {
        if ($moduleKey) {
            $st = $pdo->prepare("SELECT code, name_he FROM document_types WHERE is_active=1 AND (module_key=:m OR module_key IS NULL) ORDER BY name_he");
            $st->execute([':m' => $moduleKey]);
        } else {
            $st = $pdo->query("SELECT code, name_he FROM document_types WHERE is_active=1 ORDER BY name_he");
        }
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($rows as $r) { $out[(string)$r['code']] = $r['name_he']; }
        return $out;
    }    

    // ======================== עזר פנימי ========================

    private static function employees_for_select(PDO $pdo): array
    {
        $sql = "SELECT id, first_name, last_name, passport_number
                  FROM employees
                 ORDER BY last_name, first_name";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** קודי סטטוס לטפסים/פילטרים */
    private static function status_codes(PDO $pdo): array
    {
        $rows = $pdo->query("SELECT insurance_status_code AS code, name_he FROM insurance_status_codes ORDER BY code")
                    ->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) { $out[(int)$r['code']] = $r['name_he']; }
        return $out;
    }

    /** קודי סוג ביטוח לטפסים */
    private static function type_codes(PDO $pdo): array
    {
        $rows = $pdo->query("SELECT insurance_type_code AS code, name_he FROM insurance_type_codes ORDER BY code")
                    ->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) { $out[(int)$r['code']] = $r['name_he']; }
        return $out;
    }

    private static function employee_brief(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, passport_number FROM employees WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
