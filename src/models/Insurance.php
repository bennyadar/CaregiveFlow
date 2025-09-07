<?php

/**
 * מודל ביטוחים לעובד (employee_insurances)
 *
 * לוגיקה ב-PHP בלבד; ה-DB לאחסון/שליפה/עדכון/מחיקה.
 * המבנה והסגנון זהים למודול הוויזות.
 */
class Insurance
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ===== CRUD =====

    /** יצירה */
    public function create(array $data): int
    {
        $sql = "INSERT INTO employee_insurances (
                    employee_id,
                    policy_number,
                    insurer_name,
                    insurance_type_code,
                    request_date,
                    issue_date,
                    expiry_date,
                    status_code,
                    notes
                ) VALUES (
                    :employee_id,
                    :policy_number,
                    :insurer_name,
                    :insurance_type_code,
                    :request_date,
                    :issue_date,
                    :expiry_date,
                    :status_code,
                    :notes
                )";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':employee_id'         => (int)($data['employee_id'] ?? 0),
            ':policy_number'       => trim((string)($data['policy_number'] ?? '')),
            ':insurer_name'        => trim((string)($data['insurer_name'] ?? '')),
            ':insurance_type_code' => $data['insurance_type_code'] !== '' ? (int)$data['insurance_type_code'] : null,
            ':request_date'        => ($data['request_date'] ?? '') ?: null,
            ':issue_date'          => ($data['issue_date'] ?? '') ?: null,
            ':expiry_date'         => ($data['expiry_date'] ?? '') ?: null,
            ':status_code'         => $data['status_code'] !== '' ? (int)$data['status_code'] : null,
            ':notes'               => ($data['notes'] ?? '') ?: null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /** עדכון */
    public function update(int $id, array $data): void
    {
        $sql = "UPDATE employee_insurances
                   SET employee_id = :employee_id,
                       policy_number = :policy_number,
                       insurer_name = :insurer_name,
                       insurance_type_code = :insurance_type_code,
                       request_date = :request_date,
                       issue_date = :issue_date,
                       expiry_date = :expiry_date,
                       status_code = :status_code,
                       notes = :notes
                 WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id'                  => $id,
            ':employee_id'         => (int)($data['employee_id'] ?? 0),
            ':policy_number'       => trim((string)($data['policy_number'] ?? '')),
            ':insurer_name'        => trim((string)($data['insurer_name'] ?? '')),
            ':insurance_type_code' => $data['insurance_type_code'] !== '' ? (int)$data['insurance_type_code'] : null,
            ':request_date'        => ($data['request_date'] ?? '') ?: null,
            ':issue_date'          => ($data['issue_date'] ?? '') ?: null,
            ':expiry_date'         => ($data['expiry_date'] ?? '') ?: null,
            ':status_code'         => $data['status_code'] !== '' ? (int)$data['status_code'] : null,
            ':notes'               => ($data['notes'] ?? '') ?: null,
        ]);
    }

    /** מחיקה */
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM employee_insurances WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    /** שליפה לפי מזהה */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM employee_insurances WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // ===== רשימות עם סינון + פאג'ינציה =====

    /**
     * סינון נתמך:
     * - employee_id: מספר עובד
     * - status: ערך מספרי מקוד הסטטוסים, או המחרוזת 'expired' כדי להציג פוליסות שפגו
     * - q: חיפוש במס׳ פוליסה או שם המבטח
     * - expires_until: YYYY-MM-DD — ביטוחים שפגים עד תאריך זה (כולל)
     */
    public function all(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['employee_id'])) {
            $where[] = 'i.employee_id = :employee_id';
            $params[':employee_id'] = (int)$filters['employee_id'];
        }
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'expired') {
                $where[] = 'i.expiry_date IS NOT NULL AND i.expiry_date < CURRENT_DATE()';
            } else {
                $where[] = 'i.status_code = :status_code';
                $params[':status_code'] = (int)$filters['status'];
            }
        }
        if (!empty($filters['q'])) {
            $where[] = '(i.policy_number LIKE :q OR i.insurer_name LIKE :q)';
            $params[':q'] = '%' . trim($filters['q']) . '%';
        }
        if (!empty($filters['expires_until'])) {
            $where[] = 'i.expiry_date IS NOT NULL AND i.expiry_date <= :expires_until';
            $params[':expires_until'] = $filters['expires_until'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "SELECT i.*, e.first_name, e.last_name, e.passport_number,
                       sc.name_he AS status_name,
                       tc.name_he AS type_name
                  FROM employee_insurances i
             LEFT JOIN employees e ON e.id = i.employee_id
             LEFT JOIN insurance_status_codes sc ON sc.insurance_status_code = i.status_code
             LEFT JOIN insurance_type_codes   tc ON tc.insurance_type_code   = i.insurance_type_code
                  $whereSql
              ORDER BY COALESCE(i.expiry_date, '9999-12-31') ASC, i.id DESC
                 LIMIT :limit OFFSET :offset";

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
            $where[] = '(policy_number LIKE :q OR insurer_name LIKE :q)';
            $params[':q'] = '%' . trim($filters['q']) . '%';
        }
        if (!empty($filters['expires_until'])) {
            $where[] = 'expiry_date IS NOT NULL AND expiry_date <= :expires_until';
            $params[':expires_until'] = $filters['expires_until'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM employee_insurances $whereSql");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
}
