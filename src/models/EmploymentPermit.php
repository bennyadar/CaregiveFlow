<?php

/**
 * מודל היתרי העסקה למעסיק (employment_permits)
 *
 * לוגיקה ב-PHP בלבד; ה-DB לאחסון/שליפה/עדכון/מחיקה.
 * המבנה והסגנון זהים למודול הוויזות.
 */
class EmploymentPermit
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
        $sql = "INSERT INTO employment_permits (
                    employer_id,
                    permit_number,
                    permit_type_code,
                    request_date,
                    issue_date,
                    expiry_date,
                    status_code,
                    notes
                ) VALUES (
                    :employer_id,
                    :permit_number,
                    :permit_type_code,
                    :request_date,
                    :issue_date,
                    :expiry_date,
                    :status_code,
                    :notes
                )";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':employer_id'      => (int)($data['employer_id'] ?? 0),
            ':permit_number'    => trim((string)($data['permit_number'] ?? '')),
            ':permit_type_code' => $data['permit_type_code'] !== '' ? (int)$data['permit_type_code'] : null,
            ':request_date'     => ($data['request_date'] ?? '') ?: null,
            ':issue_date'       => ($data['issue_date'] ?? '') ?: null,
            ':expiry_date'      => ($data['expiry_date'] ?? '') ?: null,
            ':status_code'      => $data['status_code'] !== '' ? (int)$data['status_code'] : null,
            ':notes'            => ($data['notes'] ?? '') ?: null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /** עדכון */
    public function update(int $id, array $data): void
    {
        $sql = "UPDATE employment_permits
                   SET employer_id = :employer_id,
                       permit_number = :permit_number,
                       permit_type_code = :permit_type_code,
                       request_date = :request_date,
                       issue_date = :issue_date,
                       expiry_date = :expiry_date,
                       status_code = :status_code,
                       notes = :notes
                 WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id'               => $id,
            ':employer_id'      => (int)($data['employer_id'] ?? 0),
            ':permit_number'    => trim((string)($data['permit_number'] ?? '')),
            ':permit_type_code' => $data['permit_type_code'] !== '' ? (int)$data['permit_type_code'] : null,
            ':request_date'     => ($data['request_date'] ?? '') ?: null,
            ':issue_date'       => ($data['issue_date'] ?? '') ?: null,
            ':expiry_date'      => ($data['expiry_date'] ?? '') ?: null,
            ':status_code'      => $data['status_code'] !== '' ? (int)$data['status_code'] : null,
            ':notes'            => ($data['notes'] ?? '') ?: null,
        ]);
    }

    /** מחיקה */
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM employment_permits WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    /** שליפה לפי מזהה */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM employment_permits WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // ===== רשימות עם סינון + פאג'ינציה =====

    /**
     * סינון נתמך:
     * - employer_id: מספר מעסיק
     * - status: ערך מספרי מקוד הסטטוסים, או המחרוזת 'expired' כדי להציג היתרים שפגו
     * - q: חיפוש במס׳ היתר או הערות
     * - expires_until: YYYY-MM-DD — היתרים שפגים עד תאריך זה (כולל)
     */
    public function all(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['employer_id'])) {
            $where[] = 'p.employer_id = :employer_id';
            $params[':employer_id'] = (int)$filters['employer_id'];
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
            $where[] = '(p.permit_number LIKE :q OR p.notes LIKE :q)';
            $params[':q'] = '%' . trim($filters['q']) . '%';
        }
        if (!empty($filters['expires_until'])) {
            $where[] = 'p.expiry_date IS NOT NULL AND p.expiry_date <= :expires_until';
            $params[':expires_until'] = $filters['expires_until'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "SELECT p.*, r.first_name, r.last_name, r.id_number, r.passport_number,
                       sc.name_he AS status_name,
                       tc.name_he AS type_name
                  FROM employment_permits p
             LEFT JOIN employers r ON r.id = p.employer_id
             LEFT JOIN employment_permit_status_codes sc ON sc.employment_permit_status_code = p.status_code
             LEFT JOIN employment_permit_type_codes   tc ON tc.employment_permit_type_code   = p.permit_type_code
                  $whereSql
              ORDER BY COALESCE(p.expiry_date, '9999-12-31') ASC, p.id DESC
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

        if (!empty($filters['employer_id'])) {
            $where[] = 'employer_id = :employer_id';
            $params[':employer_id'] = (int)$filters['employer_id'];
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
            $where[] = '(permit_number LIKE :q OR notes LIKE :q)';
            $params[':q'] = '%' . trim($filters['q']) . '%';
        }
        if (!empty($filters['expires_until'])) {
            $where[] = 'expiry_date IS NOT NULL AND expiry_date <= :expires_until';
            $params[':expires_until'] = $filters['expires_until'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM employment_permits $whereSql");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
}
