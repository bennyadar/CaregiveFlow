<?php
require_once __DIR__ . '/../models/EmploymentPermit.php';
require_once __DIR__ . '/../models/EmployerDocument.php';
require_once __DIR__ . '/../services/DocumentService.php';
require_once __DIR__ . '/../services/EmploymentPermitService.php';

class EmploymentPermitsController
{
    public static function index(PDO $pdo)
    {
        require_login();
        $m = new EmploymentPermit($pdo);

        // סינון
        $filters = [
            'employer_id'   => $_GET['employer_id'] ?? null,
            // יכול להיות ערך מספרי או המחרוזת 'expired'
            'status'        => $_GET['status'] ?? null,
            'q'             => $_GET['q'] ?? null,
            'expires_until' => $_GET['expires_until'] ?? null,
        ];

        // פאג'ינציה
        $page  = max(1, (int)($_GET['page'] ?? 1));
        $limit = 25; $offset = ($page - 1) * $limit;

        $items = $m->all($filters, $limit, $offset);
        $total = $m->count($filters);
        $pages = (int)ceil($total / $limit);

        // רשימות לבחירה
        $employers    = self::employers_for_select($pdo);
        $status_codes = self::status_codes($pdo);
        $type_codes   = self::type_codes($pdo);

        require __DIR__.'/../../views/employment_permits/index.php';
    }

    public static function create(PDO $pdo)
    {
        require_login();
        $m = new EmploymentPermit($pdo);

        $errors = [];
        $data = [
            'employer_id'      => $_GET['employer_id'] ?? '',
            'permit_number'    => '',
            'permit_type_code' => '',
            'request_date'     => '',
            'issue_date'       => '',
            'expiry_date'      => '',
            'status_code'      => '',
            'notes'            => '',
        ];

        if (is_post()) {
            $data = [
                'employer_id'      => $_POST['employer_id'] ?? '',
                'permit_number'    => $_POST['permit_number'] ?? '',
                'permit_type_code' => $_POST['permit_type_code'] ?? '',
                'request_date'     => $_POST['request_date'] ?? '',
                'issue_date'       => $_POST['issue_date'] ?? '',
                'expiry_date'      => $_POST['expiry_date'] ?? '',
                'status_code'      => $_POST['status_code'] ?? '',
                'notes'            => $_POST['notes'] ?? '',
            ];

            $errors = EmploymentPermitService::validate($data);
            if (!$errors) {
                try {
                    $m->create($data);
                    flash('נוצר בהצלחה.');
                    redirect('employment_permits');
                } catch (PDOException $e) {
                    $errors['general'] = 'שגיאה בשמירה: '.$e->getMessage();
                }
            }
        }

        $employers    = self::employers_for_select($pdo);
        $status_codes = self::status_codes($pdo);
        $type_codes   = self::type_codes($pdo);
        require __DIR__.'/../../views/employment_permits/form.php';
    }

    public static function edit(PDO $pdo)
    {
        require_login();
        $m = new EmploymentPermit($pdo);
        $id = (int)($_GET['id'] ?? 0);
        $item = $m->find($id);
        if (!$item) { http_response_code(404); echo 'Not found'; return; }

        $errors = [];
        $data = $item;

        if (is_post()) {
            $data = [
                'employer_id'      => $_POST['employer_id'] ?? $item['employer_id'],
                'permit_number'    => $_POST['permit_number'] ?? $item['permit_number'],
                'permit_type_code' => $_POST['permit_type_code'] ?? $item['permit_type_code'],
                'request_date'     => $_POST['request_date'] ?? $item['request_date'],
                'issue_date'       => $_POST['issue_date'] ?? $item['issue_date'],
                'expiry_date'      => $_POST['expiry_date'] ?? $item['expiry_date'],
                'status_code'      => $_POST['status_code'] ?? $item['status_code'],
                'notes'            => $_POST['notes'] ?? $item['notes'],
            ];

            $errors = EmploymentPermitService::validate($data);
            if (!$errors) {
                try {
                    $m->update($id, $data);
                    flash('עודכן בהצלחה.');
                    redirect('employment_permits');
                } catch (PDOException $e) {
                    $errors['general'] = 'שגיאה בעדכון: '.$e->getMessage();
                }
            }
        }

        $employers    = self::employers_for_select($pdo);
        $status_codes = self::status_codes($pdo);
        $type_codes   = self::type_codes($pdo);
        require __DIR__.'/../../views/employment_permits/form.php';
    }

    public static function view(PDO $pdo)
    {
        require_login();
        $m  = new EmploymentPermit($pdo);
        $id = (int)($_GET['id'] ?? 0);
        $item = $m->find($id);
        if (!$item) { flash('היתר העסקה לא נמצא.', 'danger'); redirect('employment_permits'); }

        $docModel  = new EmployerDocument($pdo);
        $documents = $docModel->listFor((int)$item['employer_id'], 'employment_permits', (int)$item['id']);
        $docTypes  = self::document_types($pdo, 'employment_permits');

        $data = $item;

        // פרטי מעסיק (לתצוגה בכרטיס הימני)
        $emp = self::employer_brief($pdo, (int)$item['employer_id']);

        // שם סטטוס ושם סוג היתר (לקוד->שם)
        $status_name = self::status_codes($pdo)[$item['status_code']] ?? null;
        $type_name   = $item['permit_type_code'] !== null
            ? (self::type_codes($pdo)[$item['permit_type_code']] ?? null)
            : null;

        // חישובי מצב: סטטוס נגזר + ימים עד פקיעה
        $derived_status_code = EmploymentPermitService::derivedStatusCode(
            $item['status_code'] ?? null,
            $item['expiry_date'] ?? null
        );
        $days_left = EmploymentPermitService::daysUntilExpiry($item['expiry_date'] ?? null);

        require __DIR__ . '/../../views/employment_permits/view.php';
    }

    public static function delete(PDO $pdo)
    {
        require_login();
        $m = new EmploymentPermit($pdo);
        $id = (int)($_GET['id'] ?? 0);
        if ($id) { $m->delete($id); flash('נמחק בהצלחה.'); }
        redirect('employment_permits');
    }

    public static function upload_document(PDO $pdo)
    {
        require_login();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('employment_permits'); }

        $permitId  = (int)($_POST['permit_id'] ?? 0);
        $employerId= (int)($_POST['employer_id'] ?? 0);
        $docType   = trim($_POST['doc_type'] ?? '');
        if ($docType === '') { $docType = 'employment_permit'; }

        try {
            if ($permitId <= 0 || $employerId <= 0) { throw new RuntimeException('נתונים חסרים.'); }

            $svc = new DocumentService($pdo);
            $relativePath = $svc->storeUploadedFile($_FILES['file'] ?? [], $employerId, 'employment_permits', $permitId, $docType);

            $docModel = new EmployerDocument($pdo);
            $docModel->create([
                'employee_id'   => $employerId,
                'related_table' => 'employment_permits',
                'related_id'    => $permitId,
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
        redirect('employment_permits/view&id=' . $permitId . '&employer_id=' . $employerId);
    }

    public static function delete_document(PDO $pdo)
    {
        require_login();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('employment_permits'); }

        $docId    = (int)($_POST['doc_id'] ?? 0);
        $permitId = (int)($_POST['permit_id'] ?? 0);

        try {
            $docModel = new EmployerDocument($pdo);
            $row = $docModel->find($docId);
            if (!$row || (int)$row['related_id'] !== $permitId || $row['related_table'] !== 'employment_permits') {
                throw new RuntimeException('מסמך לא נמצא או לא שייך להיתר זה.');    
            }
            $svc = new DocumentService($pdo);
            if (!empty($row['file_path'])) { $svc->deletePhysical($row['file_path']); }
            $docModel->delete($docId);
            flash('הקובץ נמחק.', 'success');
        } catch (Throwable $e) {
            flash('שגיאה במחיקה: ' . $e->getMessage(), 'danger');
        }
        redirect('employment_permits/view&id=' . $permitId . '&employer_id=' . ($row['employer_id'] ?? 0));
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

    private static function employers_for_select(PDO $pdo): array
    {
        $sql = "SELECT id, first_name, last_name, id_number, passport_number
                  FROM employers
                 ORDER BY last_name, first_name";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** קודי סטטוס לטפסים/פילטרים */
    private static function status_codes(PDO $pdo): array
    {
        $rows = $pdo->query("SELECT employment_permit_status_code AS code, name_he FROM employment_permit_status_codes ORDER BY code")
                    ->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) { $out[(int)$r['code']] = $r['name_he']; }
        return $out;
    }

    /** קודי סוג היתר לטפסים */
    private static function type_codes(PDO $pdo): array
    {
        $rows = $pdo->query("SELECT employment_permit_type_code AS code, name_he FROM employment_permit_type_codes ORDER BY code")
                    ->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) { $out[(int)$r['code']] = $r['name_he']; }
        return $out;
    }

    private static function employer_brief(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, id_number, passport_number FROM employers WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
