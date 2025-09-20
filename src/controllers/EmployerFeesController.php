<?php
require_once __DIR__ . '/../models/EmployerFee.php';
require_once __DIR__ . '/../models/EmployerDocument.php';
require_once __DIR__ . '/../services/DocumentService.php';
require_once __DIR__ . '/../services/EmployerFeesService.php';

class EmployerFeesController
{
    public static function index(PDO $pdo)
    {
        require_login();
        $m = new EmployerFee($pdo);

        // גזירת חודש ממוקד (period_ym) והתחום היומי שלו
        $periodYm   = $_GET['period_ym'] ?? '';
        $periodStart = null; $periodEnd = null;
        if (preg_match('/^\d{4}-\d{2}$/', (string)$periodYm)) {
            $periodStart = $periodYm.'-01';
            $dt = DateTime::createFromFormat('Y-m-d', $periodStart);
            if ($dt) { $periodEnd = $dt->format('Y-m-t'); }
        }        

        $filters = [
            'employer_id'   => $_GET['employer_id'] ?? null,
            'status'        => $_GET['status'] ?? null,
            'q'             => $_GET['q'] ?? null,
            'period_from'   => $_GET['period_from'] ?? null,
            'period_to'     => $_GET['period_to'] ?? null,
            'paid_until'    => $_GET['paid_until'] ?? null,
            'unpaid'        => (($_GET['unpaid'] ?? '') === '1') ? '1' : null,
            'period_ym'     => $periodYm ?: null,
            'period_start'  => $periodStart,
            'period_end'    => $periodEnd,
            'fee_type_code' => $_GET['fee_type_code'] ?? null,
        ];

        $page  = max(1, (int)($_GET['page'] ?? 1));
        $limit = 25; $offset = ($page - 1) * $limit;

        // מצב מיוחד: מעסיקים שלא שילמו בחודש המבוקש
        $unpaidEmployersMode = ($filters['unpaid'] && $filters['period_ym']);

        if ($unpaidEmployersMode) {
            $items = $m->unpaidEmployersForPeriod($filters, $limit, $offset);
            $total = $m->countUnpaidEmployersForPeriod($filters);
            $pages = (int)ceil($total / $limit);

            require __DIR__.'/../../views/employer_fees/unpaid_employers.php';
            return;
        }        

        // מצב רגיל: רשימת חיובים
        $items = $m->all($filters, $limit, $offset);
        $total = $m->count($filters);
        $pages = (int)ceil($total / $limit);

        $employers      = self::employers_for_select($pdo);
        $status_codes   = self::status_codes($pdo);
        $type_codes     = self::type_codes($pdo);
        $payment_codes  = self::payment_method_codes($pdo);

        require __DIR__.'/../../views/employer_fees/index.php';
    }

    public static function create(PDO $pdo)
    {
        require_login();
        $m = new EmployerFee($pdo);

        $errors = [];
        $data = [
            'employer_id'         => $_GET['employer_id'] ?? '',
            'period_ym'           => '',
            'fee_type_code'       => '',
            'amount'              => '',
            'currency_code'       => 'ILS',
            'due_date'            => '',
            'payment_from_date'   => '',   // חדש
            'payment_to_date'     => '',   // חדש
            'payment_date'        => '',
            'status_code'         => '',
            'payment_method_code' => '',
            'reference_number'    => '',
            'notes'               => '',
        ];

        if (is_post()) {
            $data = [
                'employer_id'         => $_POST['employer_id'] ?? '',
                'period_ym'           => $_POST['period_ym'] ?? '',
                'fee_type_code'       => $_POST['fee_type_code'] ?? '',
                'amount'              => $_POST['amount'] ?? '',
                'currency_code'       => $_POST['currency_code'] ?? 'ILS',
                'due_date'            => $_POST['due_date'] ?? '',
                'payment_from_date'   => $_POST['payment_from_date'] ?? '',
                'payment_to_date'     => $_POST['payment_to_date'] ?? '',
                'payment_date'        => $_POST['payment_date'] ?? '',
                'status_code'         => $_POST['status_code'] ?? '',
                'payment_method_code' => $_POST['payment_method_code'] ?? '',
                'reference_number'    => $_POST['reference_number'] ?? '',
                'notes'               => $_POST['notes'] ?? '',
            ];

            $errors = EmployerFeesService::validate($data); // יש להתאים את ה-Service לשדות החדשים
            if (!$errors) {
                try {
                    $m->create($data);
                    flash('נוצר בהצלחה');
                    redirect('employer_fees');
                } catch (PDOException $e) {
                    $errors['general'] = 'שגיאת שמירה: '.$e->getMessage();
                }
            }
        }

        $employers      = self::employers_for_select($pdo);
        $status_codes   = self::status_codes($pdo);
        $type_codes     = self::type_codes($pdo);
        $payment_codes  = self::payment_method_codes($pdo);
        require __DIR__.'/../../views/employer_fees/form.php';
    }

    public static function edit(PDO $pdo)
    {
        require_login();
        $m = new EmployerFee($pdo);
        $id = (int)($_GET['id'] ?? 0);
        $item = $m->find($id);
        if (!$item) { http_response_code(404); echo 'Not found'; return; }

        $errors = [];
        $data = $item;

        if (is_post()) {
            $data = [
                'employer_id'         => $_POST['employer_id'] ?? $item['employer_id'],
                'period_ym'           => $_POST['period_ym'] ?? $item['period_ym'],
                'fee_type_code'       => $_POST['fee_type_code'] ?? $item['fee_type_code'],
                'amount'              => $_POST['amount'] ?? $item['amount'],
                'currency_code'       => $_POST['currency_code'] ?? $item['currency_code'],
                'due_date'            => $_POST['due_date'] ?? $item['due_date'],
                'payment_from_date'   => $_POST['payment_from_date'] ?? $item['payment_from_date'],
                'payment_to_date'     => $_POST['payment_to_date'] ?? $item['payment_to_date'],
                'payment_date'        => $_POST['payment_date'] ?? $item['payment_date'],
                'status_code'         => $_POST['status_code'] ?? $item['status_code'],
                'payment_method_code' => $_POST['payment_method_code'] ?? $item['payment_method_code'],
                'reference_number'    => $_POST['reference_number'] ?? $item['reference_number'],
                'notes'               => $_POST['notes'] ?? $item['notes'],
            ];

            $errors = EmployerFeesService::validate($data);
            if (!$errors) {
                try {
                    $m->update($id, $data);
                    flash('עודכן בהצלחה.');
                    redirect('employer_fees');
                } catch (PDOException $e) {
                    $errors['general'] = 'שגיאת עדכון: '.$e->getMessage();
                }
            }
        }

        $employers      = self::employers_for_select($pdo);
        $status_codes   = self::status_codes($pdo);
        $type_codes     = self::type_codes($pdo);
        $payment_codes  = self::payment_method_codes($pdo);
        require __DIR__.'/../../views/employer_fees/form.php';
    }

    public static function view(PDO $pdo)
    {
        require_login();
        $m = new EmployerFee($pdo);
        $id = (int)($_GET['id'] ?? 0);
        $item = $m->find($id);
        if (!$item) { flash('רשומת דמי תאגיד לא נמצאה.', 'danger'); redirect('employer_fees'); }

        $docModel  = new EmployerDocument($pdo);
        $documents = $docModel->listFor((int)$item['employer_id'], 'employer_fees', (int)$item['id']);
        $docTypes  = self::document_types($pdo, 'employer_fees');

        $data = $item;

        $emp   = self::employer_brief($pdo, (int)$item['employer_id']);
        $status_name  = self::status_codes($pdo)[$item['status_code']] ?? null;
        $type_name    = $item['fee_type_code'] !== null ? (self::type_codes($pdo)[$item['fee_type_code']] ?? null) : null;
        $payment_name = $item['payment_method_code'] !== null ? (self::payment_method_codes($pdo)[$item['payment_method_code']] ?? null) : null;

        require __DIR__.'/../../views/employer_fees/view.php';
    }

    public static function delete(PDO $pdo)
    {
        require_login();
        $m = new EmployerFee($pdo);
        $id = (int)($_GET['id'] ?? 0);
        if ($id) { $m->delete($id); }
        redirect('employer_fees');
    }

    public static function upload_document(PDO $pdo)
    {
        require_login();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('employer_fees'); }

        $feeId     = (int)($_POST['fee_id'] ?? 0);
        $docType   = trim($_POST['doc_type'] ?? '');
        if ($docType === '') { $docType = 'employer_fee_receipt'; }

        try {
            if ($feeId <= 0) { throw new RuntimeException('נתונים חסרים.'); }

            // מקור אמת ל-employer_id
            $fee = (new EmployerFee($pdo))->find($feeId);
            if (!$fee || empty($fee['employer_id'])) {
                throw new RuntimeException('לא נמצאה רשומת דמי תאגיד/מעסיק.');
            }
            $employerId = (int)$fee['employer_id'];

            $chk = $pdo->prepare('SELECT 1 FROM employers WHERE id = ?');
            $chk->execute([$employerId]);
            if (!$chk->fetchColumn()) { throw new RuntimeException('מעסיק לא קיים.'); }

            $svc = new DocumentService($pdo);
            $relativePath = $svc->storeUploadedFile($_FILES['file'] ?? [], $employerId, 'employer_fees', $feeId, $docType);

            $docModel = new EmployerDocument($pdo);
            $docModel->create([
                'employer_id'   => $employerId,
                'related_table' => 'employer_fees',
                'related_id'    => $feeId,
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
        redirect('employer_fees/view&id=' . $feeId . '&employer_id=' . $employerId);
    }

    public static function delete_document(PDO $pdo)
    {
        require_login();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('employer_fees'); }

        $docId  = (int)($_POST['doc_id'] ?? 0);
        $feeId  = (int)($_POST['fee_id'] ?? 0);

        try {
            $docModel = new EmployerDocument($pdo);
            $row = $docModel->find($docId);
            if (!$row || (int)$row['related_id'] !== $feeId || $row['related_table'] !== 'employer_fees') {
                throw new RuntimeException('מסמך לא נמצא או לא שייך לרשומת דמי תאגיד זו.');
            }
            $svc = new DocumentService($pdo);
            if (!empty($row['file_path'])) { $svc->deletePhysical($row['file_path']); }
            $docModel->delete($docId);
            flash('הקובץ נמחק.', 'success');
        } catch (Throwable $e) {
            flash('שגיאה במחיקה: ' . $e->getMessage(), 'danger');
        }
        redirect('employer_fees/view&id=' . $feeId . '&employer_id=' . ($row['employer_id'] ?? 0));
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
        $sql = "SELECT id, first_name, last_name, passport_number, id_number
                  FROM employers
                 ORDER BY last_name, first_name";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function status_codes(PDO $pdo): array
    {
        $rows = $pdo->query("SELECT corporate_fee_status_code AS code, name_he FROM corporate_fee_status_codes ORDER BY code")
                    ->fetchAll(PDO::FETCH_ASSOC);
        $out = []; foreach ($rows as $r) { $out[(int)$r['code']] = $r['name_he']; } return $out;
    }

    private static function type_codes(PDO $pdo): array
    {
        $rows = $pdo->query("SELECT corporate_fee_type_code AS code, name_he FROM corporate_fee_type_codes ORDER BY code")
                    ->fetchAll(PDO::FETCH_ASSOC);
        $out = []; foreach ($rows as $r) { $out[(int)$r['code']] = $r['name_he']; } return $out;
    }

    private static function payment_method_codes(PDO $pdo): array
    {
        $rows = $pdo->query("SELECT payment_method_code AS code, name_he FROM payment_method_codes ORDER BY code")
                    ->fetchAll(PDO::FETCH_ASSOC);
        $out = []; foreach ($rows as $r) { $out[(int)$r['code']] = $r['name_he']; } return $out;
    }

    private static function employer_brief(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, passport_number, id_number FROM employers WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
