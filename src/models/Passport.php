<?php

/**
 * מודל דרכונים לעובד (employee_passports)
 * שימוש בטבלת countries עבור קוד לאום (country_code) במקום nationality_codes
 */
class Passport
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ===== CRUD =====

    public function create(array $data): int
    {
        $sql = "INSERT INTO employee_passports (
                    employee_id,
                    passport_number,
                    passport_type_code,
                    country_code,
                    issue_date,
                    expiry_date,
                    issue_place,
                    status_code,
                    is_primary,
                    primary_employee_id,
                    notes
                ) VALUES (
                    :employee_id,
                    :passport_number,
                    :passport_type_code,
                    :country_code,
                    :issue_date,
                    :expiry_date,
                    :issue_place,
                    :status_code,
                    :is_primary,
                    :primary_employee_id,
                    :notes
                )";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':employee_id'         => (int)($data['employee_id'] ?? 0),
            ':passport_number'     => trim((string)($data['passport_number'] ?? '')),
            ':passport_type_code'  => $data['passport_type_code'] !== '' ? (int)$data['passport_type_code'] : null,
            ':country_code'        => $data['country_code'] !== '' ? (int)$data['country_code'] : null,
            ':issue_date'          => ($data['issue_date'] ?? '') ?: null,
            ':expiry_date'         => ($data['expiry_date'] ?? '') ?: null,
            ':issue_place'         => ($data['issue_place'] ?? '') ?: null,
            ':status_code'         => $data['status_code'] !== '' ? (int)$data['status_code'] : null,
            ':is_primary'          => !empty($data['is_primary']) ? 1 : 0,
            ':primary_employee_id' => isset($data['primary_employee_id']) && $data['primary_employee_id'] !== '' ? (int)$data['primary_employee_id'] : null,
            ':notes'               => ($data['notes'] ?? '') ?: null,
        ]);
        $newId = (int)$this->pdo->lastInsertId();
        // אם מסומן כראשי – לנקות ראשי מרשומות אחרות של אותו עובד
        if (!empty($data['is_primary'])) {
            $stm2 = $this->pdo->prepare("UPDATE employee_passports SET is_primary = 0 WHERE employee_id = :emp AND id <> :id");
            $stm2->execute([':emp' => (int)($data['employee_id'] ?? 0), ':id' => $newId]);
        }
        return $newId;
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE employee_passports
                   SET employee_id = :employee_id,
                       passport_number = :passport_number,
                       passport_type_code = :passport_type_code,
                       country_code = :country_code,
                       issue_date = :issue_date,
                       expiry_date = :expiry_date,
                       issue_place = :issue_place,
                       status_code = :status_code,
                       is_primary = :is_primary,
                       primary_employee_id = :primary_employee_id,
                       notes = :notes
                 WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id'                  => $id,
            ':employee_id'         => (int)($data['employee_id'] ?? 0),
            ':passport_number'     => trim((string)($data['passport_number'] ?? '')),
            ':passport_type_code'  => $data['passport_type_code'] !== '' ? (int)$data['passport_type_code'] : null,
            ':country_code'        => $data['country_code'] !== '' ? (int)$data['country_code'] : null,
            ':issue_date'          => ($data['issue_date'] ?? '') ?: null,
            ':expiry_date'         => ($data['expiry_date'] ?? '') ?: null,
            ':issue_place'         => ($data['issue_place'] ?? '') ?: null,
            ':status_code'         => $data['status_code'] !== '' ? (int)$data['status_code'] : null,
            ':is_primary'          => !empty($data['is_primary']) ? 1 : 0,
            ':primary_employee_id' => isset($data['primary_employee_id']) && $data['primary_employee_id'] !== '' ? (int)$data['primary_employee_id'] : null,
            ':notes'               => ($data['notes'] ?? '') ?: null,
        ]);
        // אם מסומן כראשי – לנקות ראשי מרשומות אחרות של אותו עובד
        if (!empty($data['is_primary'])) {
            $stm2 = $this->pdo->prepare("UPDATE employee_passports SET is_primary = 0 WHERE employee_id = :emp AND id <> :id");
            $stm2->execute([':emp' => (int)($data['employee_id'] ?? 0), ':id' => (int)$id]);
        }
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM employee_passports WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM employee_passports WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // ===== רשימות עם סינון + פאג'ינציה =====

    /** פילטרים: employee_id, status (או 'expired'), q, expires_until */
    public function all(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['employee_id'])) {
            $where[] = 'p.employee_id = :employee_id';
            $params[':employee_id'] = (int)$filters['employee_id'];
        }
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'expired') {
                $where[] = 'p.expiry_date IS NOT NULL AND p.expiry_date < CURRENT_DATE()';
            } else {
                $where[] = 'p.status_code = :status_code';
                $params[':status_code'] = (int)$filters['status'];
            }
        }
        if (!empty($filters['q'])) {
            $where[] = '(p.passport_number LIKE :q OR p.issue_place LIKE :q OR p.notes LIKE :q)';
            $params[':q'] = '%' . trim($filters['q']) . '%';
        }
        if (!empty($filters['expires_until'])) {
            $where[] = 'p.expiry_date IS NOT NULL AND p.expiry_date <= :expires_until';
            $params[':expires_until'] = $filters['expires_until'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "SELECT p*, e.first_name, e.last_name, e.passport_number AS employee_passport,
                       sc.name_he AS status_name,
                       tc.name_he AS type_name,
                       c.name_he  AS country_name
                  FROM employee_passports p
             LEFT JOIN employees e ON e.id = p.employee_id
             LEFT JOIN passport_status_codes  sc ON sc.passport_status_code  = p.status_code
             LEFT JOIN passport_type_codes    tc ON tc.passport_type_code    = p.passport_type_code
             LEFT JOIN countries              c  ON c.country_code           = p.country_code
                  $whereSql
              ORDER BY p.is_primary DESC, COALESCE(p.expiry_date, '9999-12-31') ASC, p.id DESC
                 LIMIT :limit OFFSET :offset";

        // תיקון טעות כתיב אפשרית ב-SELECT
        $sql = str_replace('SELECT p*', 'SELECT p.*', $sql);

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count(array $filters = []): int
    {
        $where = [];
        $params = [];

        if (!empty($filters['employee_id'])) {
            $where[] = 'employee_id = :employee_id';
            $params[':employee_id'] = (int)$filters['employee_id'];
        }
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'expired') {
                $where[] = 'expiry_date IS NOT NULL AND expiry_date < CURRENT_DATE()';
            } else {
                $where[] = 'status_code = :status_code';
                $params[':status_code'] = (int)$filters['status'];
            }
        }
        if (!empty($filters['q'])) {
            $where[] = '(passport_number LIKE :q OR issue_place LIKE :q OR notes LIKE :q)';
            $params[':q'] = '%' . trim($filters['q']) . '%';
        }
        if (!empty($filters['expires_until'])) {
            $where[] = 'expiry_date IS NOT NULL AND expiry_date <= :expires_until';
            $params[':expires_until'] = $filters['expires_until'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM employee_passports $whereSql");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
}
