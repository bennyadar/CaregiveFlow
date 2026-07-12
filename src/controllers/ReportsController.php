<?php
declare(strict_types=1);

/**
 * ReportsController
 *
 * דוחות לקריאה בלבד (View). אין עדכון/מחיקה דרך דוחות.
 */
class ReportsController
{
    public static function placements_active(PDO $pdo): void
    {
        require_login();

        $q = trim((string)($_GET['q'] ?? ''));
        $params = [];

        $sql = "
            SELECT
              p.id,
              p.start_date,
              p.end_date,
              e.id AS employee_id,
              CONCAT(e.last_name_en, ' ', e.first_name_en) AS employee_name,
              e.passport_number,
              r.id AS employer_id,
              COALESCE(NULLIF(r.company_name,''), TRIM(CONCAT(r.last_name,' ',r.first_name))) AS employer_name,
              r.id_number AS employer_id_number
            FROM placements p
            JOIN employees e ON e.id = p.employee_id
            JOIN employers r ON r.id = p.employer_id
            WHERE p.start_date <= CURDATE()
              AND (p.end_date IS NULL OR p.end_date >= CURDATE())
        ";

        if ($q !== '') {
            $sql .= " AND (e.passport_number LIKE :q OR e.last_name_en LIKE :q OR e.first_name_en LIKE :q
                        OR r.id_number LIKE :q OR r.company_name LIKE :q OR r.last_name LIKE :q OR r.first_name LIKE :q)";
            $params[':q'] = '%' . $q . '%';
        }

        $sql .= " ORDER BY COALESCE(p.end_date,'9999-12-31') DESC, p.start_date DESC, p.id DESC";

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        require __DIR__ . '/../../views/reports/placements_active.php';
    }

    public static function placements_ending(PDO $pdo): void
    {
        require_login();

        $days = (int)($_GET['days'] ?? 30);
        if ($days <= 0 || $days > 365) {
            $days = 30;
        }
        $q = trim((string)($_GET['q'] ?? ''));
        $params = [':days' => $days];

        $sql = "
            SELECT
              p.id,
              p.start_date,
              p.end_date,
              e.id AS employee_id,
              CONCAT(e.last_name_en, ' ', e.first_name_en) AS employee_name,
              e.passport_number,
              r.id AS employer_id,
              COALESCE(NULLIF(r.company_name,''), TRIM(CONCAT(r.last_name,' ',r.first_name))) AS employer_name,
              r.id_number AS employer_id_number
            FROM placements p
            JOIN employees e ON e.id = p.employee_id
            JOIN employers r ON r.id = p.employer_id
            WHERE p.end_date IS NOT NULL
              AND p.end_date >= CURDATE()
              AND p.end_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
        ";

        if ($q !== '') {
            $sql .= " AND (e.passport_number LIKE :q OR e.last_name_en LIKE :q OR e.first_name_en LIKE :q
                        OR r.id_number LIKE :q OR r.company_name LIKE :q OR r.last_name LIKE :q OR r.first_name LIKE :q)";
            $params[':q'] = '%' . $q . '%';
        }

        $sql .= " ORDER BY p.end_date ASC, p.start_date DESC, p.id DESC";

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        require __DIR__ . '/../../views/reports/placements_ending.php';
    }

    public static function placements_history(PDO $pdo): void
    {
        require_login();

        $employeeId = (int)($_GET['employee_id'] ?? 0);

        // רשימת עובדים לבחירה
        $empOptions = [];
        try {
            $stmt = $pdo->query("SELECT id, CONCAT(last_name_en,' ',first_name_en,' (',passport_number,')') AS name FROM employees ORDER BY last_name_en, first_name_en");
            $empOptions = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            $empOptions = [];
        }

        $rows = [];
        if ($employeeId > 0) {
            $sql = "
                SELECT
                  p.id,
                  p.start_date,
                  p.end_date,
                  r.id AS employer_id,
                  COALESCE(NULLIF(r.company_name,''), TRIM(CONCAT(r.last_name,' ',r.first_name))) AS employer_name,
                  r.id_number AS employer_id_number
                FROM placements p
                JOIN employers r ON r.id = p.employer_id
                WHERE p.employee_id = :eid
                ORDER BY p.start_date DESC, p.id DESC
            ";
            $st = $pdo->prepare($sql);
            $st->execute([':eid' => $employeeId]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        require __DIR__ . '/../../views/reports/placements_history.php';
    }
}
