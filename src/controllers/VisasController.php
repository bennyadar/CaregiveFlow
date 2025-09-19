<?php
require_once __DIR__ . '/../models/Visa.php';
require_once __DIR__ . '/../models/EmployeeDocument.php';
require_once __DIR__ . '/../services/VisaService.php';
require_once __DIR__ . '/../services/DocumentService.php';

class VisasController
{
    public static function index(PDO $pdo)
    {
        require_login();
        $m = new Visa($pdo);

        // סינון
        $filters = [
            'employee_id'  => $_GET['employee_id'] ?? null,
            'status'       => $_GET['status'] ?? null,
            'q'            => $_GET['q'] ?? null,
            'expires_until'=> $_GET['expires_until'] ?? null,
        ];

        // פאג'ינציה בסיסית
        $page  = max(1, (int)($_GET['page'] ?? 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $items = $m->all($filters, $limit, $offset);
        $total = $m->count($filters);
        $pages = (int)ceil($total / $limit);

        // רשימת עובדים לבחירה (בחירה קצרה: id + שם)
        $employees = self::employees_for_select($pdo);

        require __DIR__.'/../../views/visas/index.php';
    }

    public static function create(PDO $pdo)
    {
        require_login();
        $m = new Visa($pdo);

        $errors = [];
        $data = [
            'employee_id' => $_GET['employee_id'] ?? '', // מאפשר פתיחה מקונטקסט עובד
            'visa_number' => '',
            'request_date'=> '',
            'issue_date'  => '',
            'expiry_date' => '',
            'status'      => 'requested',
            'notes'       => '',
        ];

        if (is_post()) {
            $data = [
                'employee_id' => $_POST['employee_id'] ?? '',
                'visa_number' => $_POST['visa_number'] ?? '',
                'request_date'=> $_POST['request_date'] ?? '',
                'issue_date'  => $_POST['issue_date'] ?? '',
                'expiry_date' => $_POST['expiry_date'] ?? '',
                'status'      => $_POST['status'] ?? 'requested',
                'notes'       => $_POST['notes'] ?? '',
            ];

            $errors = VisaService::validate($data);
            if (!$errors) {
                try {
                    $id = $m->create($data);
                    flash('נוצר בהצלחה.');
                    redirect('visas');
                } catch (PDOException $e) {
                    // טיפול בשגיאת unique index וכו'
                    $errors['general'] = 'שגיאה בשמירה: '.$e->getMessage();
                }
            }
        }

        $employees = self::employees_for_select($pdo);
        require __DIR__.'/../../views/visas/form.php';
    }

    public static function edit(PDO $pdo)
    {
        require_login();
        $m = new Visa($pdo);
        $id = (int)($_GET['id'] ?? 0);
        $item = $m->find($id);
        if (!$item) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        $errors = [];
        $data = $item; // ערכים התחלתיים בטופס

        if (is_post()) {
            $data = [
                'employee_id' => $_POST['employee_id'] ?? $item['employee_id'],
                'visa_number' => $_POST['visa_number'] ?? $item['visa_number'],
                'request_date'=> $_POST['request_date'] ?? $item['request_date'],
                'issue_date'  => $_POST['issue_date'] ?? $item['issue_date'],
                'expiry_date' => $_POST['expiry_date'] ?? $item['expiry_date'],
                'status'      => $_POST['status'] ?? $item['status'],
                'notes'       => $_POST['notes'] ?? $item['notes'],
            ];

            $errors = VisaService::validate($data);
            if (!$errors) {
                try {
                    $m->update($id, $data);
                    flash('עודכן בהצלחה.');
                    redirect('visas');
                } catch (PDOException $e) {
                    $errors['general'] = 'שגיאה בעדכון: '.$e->getMessage();
                }
            }
        }

        $employees = self::employees_for_select($pdo);
        require __DIR__.'/../../views/visas/form.php';
    }

    public static function view(PDO $pdo)
    {
        require_login();
        $m = new Visa($pdo);
        $id = (int)($_GET['id'] ?? 0);
        $item = $m->find($id);
        if (!$item) { flash('ויזה לא נמצאה.', 'danger'); redirect('visas'); }

        $emp = null;
        if (!empty($item['employee_id'])) {
            $st = $pdo->prepare('SELECT id, first_name, last_name FROM employees WHERE id = ?');
            $st->execute([(int)$item['employee_id']]);
            $emp = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        $docModel  = new EmployeeDocument($pdo);
        $documents = $docModel->listFor((int)$item['employee_id'], 'employee_visas', (int)$item['id']);

        // סוגי מסמכים לפי מודול ויזות
        $docTypes = self::document_types($pdo, 'visas');

        // עצמים קיימים אצלך (לדוגמה): status/type/country וכו׳ במידה ויש
        $data = $item;

        // סטטוס נגזר + ימים עד פקיעה לתצוגה
        $derived_status = VisaService::derivedStatus($item['status'], $item['expiry_date']);
        $days_left = VisaService::daysUntilExpiry($item['expiry_date']);

        // פרטי עובד בסיסיים לתצוגה
        $emp = self::employee_brief($pdo, (int)$item['employee_id']);

        require __DIR__.'/../../views/visas/view.php';
    }

    public static function delete(PDO $pdo)
    {
        require_login();
        $m = new Visa($pdo);
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            $m->delete($id);
            flash('נמחק בהצלחה.');
        }
        redirect('visas');
    }

    public static function upload_document(PDO $pdo)
    {
        require_login();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('visas'); }

        $visaId    = (int)($_POST['visa_id'] ?? 0);
        $employeeId= (int)($_POST['employee_id'] ?? 0);
        $docType   = trim($_POST['doc_type'] ?? '');
        if ($docType === '') { $docType = 'visa'; } // ברירת מחדל למודול הויזות

        try {
            if ($visaId <= 0 || $employeeId <= 0) { throw new RuntimeException('נתונים חסרים.'); }

            $svc = new DocumentService($pdo);
            $relativePath = $svc->storeUploadedFile($_FILES['file'] ?? [], $employeeId, 'visas', $visaId, $docType);

            $docModel = new EmployeeDocument($pdo);
            $docModel->create([
                'employee_id'   => $employeeId,
                'related_table' => 'employee_visas',
                'related_id'    => $visaId,
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
        redirect('visas/view&id=' . $visaId . '&employee_id=' . $employeeId);
    }

    public static function delete_document(PDO $pdo)
    {
        require_login();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('visas'); }

        $docId   = (int)($_POST['doc_id'] ?? 0);
        $visaId  = (int)($_POST['visa_id'] ?? 0);

        try {
            $docModel = new EmployeeDocument($pdo);
            $row = $docModel->find($docId);
            if (!$row || (int)$row['related_id'] !== $visaId || $row['related_table'] !== 'employee_visas') {
                throw new RuntimeException('מסמך לא נמצא או לא שייך לויזה זו.');
            }
            $svc = new DocumentService($pdo);
            if (!empty($row['file_path'])) { $svc->deletePhysical($row['file_path']); }
            $docModel->delete($docId);
            flash('הקובץ נמחק.', 'success');
        } catch (Throwable $e) {
            flash('שגיאה במחיקה: ' . $e->getMessage(), 'danger');
        }
        redirect('visas/view&id=' . $visaId . '&employee_id=' . ($row['employee_id'] ?? 0));
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

    private static function employee_brief(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, passport_number FROM employees WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

?>