<?php

class Visa
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // יצירה
    public function create(array $data): int
    {
        $sql = "INSERT INTO visas (employee_id, visa_number, request_date, issue_date, expiry_date, status, notes)
                VALUES (:employee_id, :visa_number, :request_date, :issue_date, :expiry_date, :status, :notes)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':employee_id' => (int)$data['employee_id'],
            ':visa_number' => trim((string)$data['visa_number']),
            ':request_date' => $data['request_date'] ?: null,
            ':issue_date'   => $data['issue_date']   ?: null,
            ':expiry_date'  => $data['expiry_date']  ?: null,
            ':status'       => $data['status'] ?? 'requested',
            ':notes'        => $data['notes'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    // עדכון
    public function update(int $id, array $data): void
    {
        $sql = "UPDATE visas
                   SET employee_id = :employee_id,
                       visa_number = :visa_number,
                       request_date = :request_date,
                       issue_date   = :issue_date,
                       expiry_date  = :expiry_date,
                       status       = :status,
                       notes        = :notes
                 WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id'          => $id,
            ':employee_id' => (int)$data['employee_id'],
            ':visa_number' => trim((string)$data['visa_number']),
            ':request_date' => $data['request_date'] ?: null,
            ':issue_date'   => $data['issue_date']   ?: null,
            ':expiry_date'  => $data['expiry_date']  ?: null,
            ':status'       => $data['status'] ?? 'requested',
            ':notes'        => $data['notes'] ?? null,
        ]);
    }

    // מחיקה
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM visas WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    // שליפה לפי מזהה
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM visas WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // רשימה עם סינון בסיסי + פאג'ינציה
    public function all(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['employee_id'])) {
            $where[] = 'v.employee_id = :employee_id';
            $params[':employee_id'] = (int)$filters['employee_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'v.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['q'])) {
            // חיפוש לפי מספר ויזה
            $where[] = 'v.visa_number LIKE :q';
            $params[':q'] = '%' . trim($filters['q']) . '%';
        }
        if (!empty($filters['expires_until'])) { // YYYY-MM-DD
            $where[] = 'v.expiry_date IS NOT NULL AND v.expiry_date <= :expires_until';
            $params[':expires_until'] = $filters['expires_until'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "SELECT v.*, e.first_name, e.last_name, e.passport_number
                FROM visas v
                LEFT JOIN employees e ON e.id = v.employee_id
                $whereSql
                ORDER BY COALESCE(v.expiry_date, '9999-12-31') ASC, v.id DESC
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
            $where[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['q'])) {
            $where[] = 'visa_number LIKE :q';
            $params[':q'] = '%' . trim($filters['q']) . '%';
        }
        if (!empty($filters['expires_until'])) {
            $where[] = 'expiry_date IS NOT NULL AND expiry_date <= :expires_until';
            $params[':expires_until'] = $filters['expires_until'];
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM visas $whereSql");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
}

?>