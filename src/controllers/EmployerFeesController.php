<?php
require_once __DIR__ . '/../models/EmployerFee.php';
require_once __DIR__ . '/../services/EmployerFeesService.php';

class EmployerFeesController
{
    public static function index(PDO $pdo)
    {
        require_login();
        $m = new EmployerFee($pdo);

        $filters = [
            'employer_id' => $_GET['employer_id'] ?? null,
            'status'      => $_GET['status'] ?? null,
            'q'           => $_GET['q'] ?? null,
            'period_from' => $_GET['period_from'] ?? null,
            'period_to'   => $_GET['period_to'] ?? null,
            'paid_until'  => $_GET['paid_until'] ?? null,
        ];

        $page  = max(1, (int)($_GET['page'] ?? 1));
        $limit = 25; $offset = ($page - 1) * $limit;

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
                'payment_date'        => $_POST['payment_date'] ?? '',
                'status_code'         => $_POST['status_code'] ?? '',
                'payment_method_code' => $_POST['payment_method_code'] ?? '',
                'reference_number'    => $_POST['reference_number'] ?? '',
                'notes'               => $_POST['notes'] ?? '',
            ];

            $errors = EmployerFeesService::validate($data);
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
        if (!$item) { http_response_code(404); echo 'Not found'; return; }

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
