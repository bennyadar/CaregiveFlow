<?php
require_once __DIR__ . '/../models/EmployerPassport.php';
require_once __DIR__ . '/../services/EmployerPassportService.php';

class EmployerPassportsController
{
    public static function index(PDO $pdo)
    {
        require_login();
        $m = new EmployerPassport($pdo);

        $filters = [
            'employer_id'   => $_GET['employer_id'] ?? null,
            'status'        => $_GET['status'] ?? null, // מספרי או 'expired'
            'q'             => $_GET['q'] ?? null,
            'expires_until' => $_GET['expires_until'] ?? null,
        ];

        $page  = max(1, (int)($_GET['page'] ?? 1));
        $limit = 25; $offset = ($page - 1) * $limit;

        $items = $m->all($filters, $limit, $offset);
        $total = $m->count($filters);
        $pages = (int)ceil($total / $limit);

        $employers     = self::employers_for_select($pdo);
        $status_codes  = self::status_codes($pdo);
        $type_codes    = self::type_codes($pdo);
        $country_codes = self::country_codes($pdo);

        require __DIR__.'/../../views/employer_passports/index.php';
    }

    public static function create(PDO $pdo)
    {
        require_login();
        $m = new EmployerPassport($pdo);

        $errors = [];
        $data = [
            'employer_id'        => $_GET['employer_id'] ?? '',
            'passport_number'    => '',
            'passport_type_code' => '',
            'country_code'       => '',
            'issue_date'         => '',
            'expiry_date'        => '',
            'is_primary'         => '0',
            'issue_place'        => '',
            'status_code'        => '',
            'notes'              => '',
        ];

        if (is_post()) {
            $data = [
                'employer_id'        => $_POST['employer_id']        ?? '',
                'passport_number'    => $_POST['passport_number']    ?? '',
                'passport_type_code' => $_POST['passport_type_code'] ?? '',
                'country_code'       => $_POST['country_code']       ?? '',
                'issue_date'         => $_POST['issue_date']         ?? '',
                'expiry_date'        => $_POST['expiry_date']        ?? '',
                'is_primary'         => isset($_POST['is_primary']) ? '1' : '0',
                'issue_place'        => $_POST['issue_place']        ?? '',
                'status_code'        => $_POST['status_code']        ?? '',
                'notes'              => $_POST['notes']              ?? '',
            ];

            $errors = EmployerPassportService::validate($data);
            if (!$errors) {
                try {
                    $m->create($data);
                    flash('נוצר  בהצלחה.');
                    redirect('employer_passports');
                } catch (PDOException $e) {
                    $errors['general'] = 'שגיאה בשמירה: '.$e->getMessage();
                }
            }
        }

        $employers     = self::employers_for_select($pdo);
        $status_codes  = self::status_codes($pdo);
        $type_codes    = self::type_codes($pdo);
        $country_codes = self::country_codes($pdo);
        require __DIR__.'/../../views/employer_passports/form.php';
    }

    public static function edit(PDO $pdo)
    {
        require_login();
        $m = new EmployerPassport($pdo);
        $id = (int)($_GET['id'] ?? 0);
        $item = $m->find($id);
        if (!$item) { http_response_code(404); echo 'Not found'; return; }

        $errors = [];
        $data = $item;

        if (is_post()) {
            $data = [
                'employer_id'        => $_POST['employer_id']        ?? $item['employer_id'],
                'passport_number'    => $_POST['passport_number']    ?? $item['passport_number'],
                'passport_type_code' => $_POST['passport_type_code'] ?? (string)$item['passport_type_code'],
                'country_code'       => $_POST['country_code']       ?? (string)$item['country_code'],
                'issue_date'         => $_POST['issue_date']         ?? $item['issue_date'],
                'expiry_date'        => $_POST['expiry_date']        ?? $item['expiry_date'],
                'is_primary'         => isset($_POST['is_primary']) ? '1' : '0',
                'issue_place'        => $_POST['issue_place']        ?? $item['issue_place'],
                'status_code'        => $_POST['status_code']        ?? (string)$item['status_code'],
                'notes'              => $_POST['notes']              ?? $item['notes'],
            ];
            $errors = EmployerPassportService::validate($data);
            if (!$errors) {
                try {
                    $m->update($id, $data);
                    flash('עודכן בהצלחה.');
                    redirect('employer_passports');
                } catch (PDOException $e) {
                    $errors['general'] = 'שגיאה בעדכון: '.$e->getMessage();
                }
            }
        }

        $employers     = self::employers_for_select($pdo);
        $status_codes  = self::status_codes($pdo);
        $type_codes    = self::type_codes($pdo);
        $country_codes = self::country_codes($pdo);
        require __DIR__.'/../../views/employer_passports/form.php';
    }

    public static function view(PDO $pdo)
    {
        require_login();
        $m = new EmployerPassport($pdo);
        $id = (int)($_GET['id'] ?? 0);
        $item = $m->find($id);
        if (!$item) { http_response_code(404); echo 'Not found'; return; }

        $emp = self::employer_brief($pdo, (int)$item['employer_id']);
        $status_name = self::status_codes($pdo)[$item['status_code']] ?? null;
        $type_name   = $item['passport_type_code'] !== null ? (self::type_codes($pdo)[$item['passport_type_code']] ?? null) : null;
        $country_name = $item['country_code'] !== null ? (self::country_codes($pdo)[$item['country_code']] ?? null) : null;

        $derived_status_code = EmployerPassportService::derivedStatusCode($item['status_code'] ?? null, $item['expiry_date'] ?? null);
        $days_left = EmployerPassportService::daysUntilExpiry($item['expiry_date'] ?? null);

        require __DIR__.'/../../views/employer_passports/view.php';
    }

    public static function delete(PDO $pdo)
    {
        require_login();
        $m = new EmployerPassport($pdo);
        $id = (int)($_GET['id'] ?? 0);
        if ($id) { $m->delete($id); flash('נמחק בהצלחה.'); }
        redirect('employer_passports');
    }

    // ======================== עזר פנימי ========================

    private static function employers_for_select(PDO $pdo): array
    {
        $sql = "SELECT id, first_name, last_name, id_number, passport_number FROM employers ORDER BY last_name, first_name";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function status_codes(PDO $pdo): array
    {
        $rows = $pdo->query("SELECT passport_status_code AS code, name_he FROM passport_status_codes ORDER BY code")
                    ->fetchAll(PDO::FETCH_ASSOC);
        $out = []; foreach ($rows as $r) { $out[(int)$r['code']] = $r['name_he']; } return $out;
    }

    private static function type_codes(PDO $pdo): array
    {
        $rows = $pdo->query("SELECT passport_type_code AS code, name_he FROM passport_type_codes ORDER BY code")
                    ->fetchAll(PDO::FETCH_ASSOC);
        $out = []; foreach ($rows as $r) { $out[(int)$r['code']] = $r['name_he']; } return $out;
    }

    private static function country_codes(PDO $pdo): array
    {
        $rows = $pdo->query("SELECT country_code AS code, name_he FROM countries ORDER BY name_he")
                    ->fetchAll(PDO::FETCH_ASSOC);
        $out = []; foreach ($rows as $r) { $out[(int)$r['code']] = $r['name_he']; } return $out;
    }

    private static function employer_brief(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, id_number, passport_number FROM employers WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
