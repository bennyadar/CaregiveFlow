<?php
require_once __DIR__ . '/../models/Passport.php';
require_once __DIR__ . '/../services/PassportService.php';

class PassportsController
{
    public static function index(PDO $pdo)
    {
        require_login();
        $m = new Passport($pdo);

        $filters = [
            'employee_id'   => $_GET['employee_id'] ?? null,
            'status'        => $_GET['status'] ?? null, // מספרי או 'expired'
            'q'             => $_GET['q'] ?? null,
            'expires_until' => $_GET['expires_until'] ?? null,
        ];

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $limit   = min(100, max(5, (int)($_GET['per_page'] ?? 25)));
        
        $offset  = max(0, ($page - 1) * $limit);
        $rows    = $m->all($filters, $limit, $offset);
        $total   = $m->count($filters);
        $pages   = (int)ceil($total / $limit);

        $employees     = self::employees_for_select($pdo);
        $status_codes  = self::status_codes($pdo);
        $type_codes    = self::type_codes($pdo);
        $country_codes = self::country_codes($pdo);

        require __DIR__.'/../../views/passports/index.php';
    }

    public static function create(PDO $pdo)
    {
        require_login();
        $m = new Passport($pdo);

        $errors = [];
        $data = [
            'employee_id'        => $_GET['employee_id'] ?? '',
            'passport_number'    => '',
            'passport_type_code' => '',
            'country_code'       => '',
            'issue_date'         => '',
            'expiry_date'        => '',
            'issue_place'        => '',
            'status_code'        => '',
            'notes'              => '',
            // חדשים
            'is_primary'         => 0,
            'primary_employee_id'=> '',
        ];

        if (is_post()) {
            $data = [
                'employee_id'        => $_POST['employee_id'] ?? '',
                'passport_number'    => $_POST['passport_number'] ?? '',
                'passport_type_code' => $_POST['passport_type_code'] ?? '',
                'country_code'       => $_POST['country_code'] ?? '',
                'issue_date'         => $_POST['issue_date'] ?? '',
                'expiry_date'        => $_POST['expiry_date'] ?? '',
                'issue_place'        => $_POST['issue_place'] ?? '',
                'status_code'        => $_POST['status_code'] ?? '',
                'notes'              => $_POST['notes'] ?? '',
                // חדשים
                'is_primary'         => isset($_POST['is_primary']) ? 1 : 0,
                'primary_employee_id'=> $_POST['primary_employee_id'] ?? '',
            ];

            $errors = PassportService::validate($data);
            if (!$errors) {
                try {
                    $m->create($data);
                    flash('נוצר בהצלחה.');
                    redirect('passports');
                } catch (PDOException $e) {
                    $errors['general'] = 'שגיאה בשמירה: '.$e->getMessage();
                }
            }
        }

        $employees     = self::employees_for_select($pdo);
        $status_codes  = self::status_codes($pdo);
        $type_codes    = self::type_codes($pdo);
        $country_codes = self::country_codes($pdo);
        require __DIR__.'/../../views/passports/form.php';
    }

    public static function view(PDO $pdo)
    {
        require_login();
        $m = new Passport($pdo);
        $id = (int)($_GET['id'] ?? 0);
        $item = $m->find($id);
        if (!$item) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        // סטטוס נגזר + ימים עד פקיעה לתצוגה
        $derived_status = PassportService::derivedStatusCode($item['status_code'], $item['expiry_date']);
        $days_left = PassportService::daysUntilExpiry($item['expiry_date']);

        // פרטי עובד בסיסיים לתצוגה
        $emp = self::employee_brief($pdo, (int)$item['employee_id']);

        // סוג דרכון לתצוגה
        $passport_type_code = self::passport_type_brief($pdo, (int)$item['passport_type_code']);

        require __DIR__.'/../../views/passports/view.php';
    }

    public static function edit(PDO $pdo)
    {
        require_login();
        $m = new Passport($pdo);
        $id = (int)($_GET['id'] ?? 0);
        $item = $m->find($id);
        if (!$item) { http_response_code(404); echo 'Not found'; return; }

        $errors = [];
        $data = $item;

        if (is_post()) {
            $data = [
                'employee_id'        => $_POST['employee_id'] ?? $item['employee_id'],
                'passport_number'    => $_POST['passport_number'] ?? $item['passport_number'],
                'passport_type_code' => $_POST['passport_type_code'] ?? $item['passport_type_code'],
                'country_code'       => $_POST['country_code'] ?? $item['country_code'],
                'issue_date'         => $_POST['issue_date'] ?? $item['issue_date'],
                'expiry_date'        => $_POST['expiry_date'] ?? $item['expiry_date'],
                'issue_place'        => $_POST['issue_place'] ?? $item['issue_place'],
                'status_code'        => $_POST['status_code'] ?? $item['status_code'],
                'notes'              => $_POST['notes'] ?? $item['notes'],
                // חדשים
                'is_primary'         => isset($_POST['is_primary']) ? 1 : (int)($item['is_primary'] ?? 0),
                'primary_employee_id'=> $_POST['primary_employee_id'] ?? ($item['primary_employee_id'] ?? ''),
            ];

            $errors = PassportService::validate($data);
            if (!$errors) {
                try {
                    $m->update($id, $data);
                    flash('עודכן בהצלחה.');
                    redirect('passports');
                } catch (PDOException $e) {
                    $errors['general'] = 'שגיאה בעדכון: '.$e->getMessage();
                }
            }
        }

        $employees     = self::employees_for_select($pdo);
        $status_codes  = self::status_codes($pdo);
        $type_codes    = self::type_codes($pdo);
        $country_codes = self::country_codes($pdo);
        require __DIR__.'/../../views/passports/form.php';
    }

    public static function delete(PDO $pdo)
    {
        require_login();
        $m = new Passport($pdo);
        $id = (int)($_GET['id'] ?? 0);
        $item = $m->find($id);
        if (!$item) { http_response_code(404); echo 'Not found'; return; }

        try {
            $m->delete($id);
            flash('נמחק בהצלחה.');
        } catch (PDOException $e) {
            flash('שגיאה במחיקה: '.$e->getMessage(), 'danger');
        }
        redirect('passports');
    }

    // ======================== עזר פנימי ========================

    private static function employees_for_select(PDO $pdo): array
    {
        $sql = "SELECT id, first_name, last_name, passport_number FROM employees ORDER BY last_name, first_name";
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

    private static function employee_brief(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, passport_number FROM employees WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private static function passport_type_brief(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM passport_type_codes WHERE passport_type_code = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
