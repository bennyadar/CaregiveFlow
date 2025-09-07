<?php

/**
 * מודל דמי תאגיד למעסיק (employer_corporate_fees)
 *
 * לוגיקה ב-PHP בלבד; DB לאחסון/שליפה/עדכון/מחיקה.
 * מבנה וסגנון זהים למודול הוויזות (CRUD + רשימות עם סינון/פאג'ינציה).
 */
class EmployerFee
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
        $sql = "INSERT INTO employer_corporate_fees (
                    employer_id,
                    period_ym,
                    fee_type_code,
                    amount,
                    currency_code,
                    due_date,
                    payment_date,
                    status_code,
                    payment_method_code,
                    reference_number,
                    notes
                ) VALUES (
                    :employer_id,
                    :period_ym,
                    :fee_type_code,
                    :amount,
                    :currency_code,
                    :due_date,
                    :payment_date,
                    :status_code,
                    :payment_method_code,
                    :reference_number,
                    :notes
                )";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':employer_id'         => (int)($data['employer_id'] ?? 0),
            ':period_ym'           => trim((string)($data['period_ym'] ?? '')) ?: null, // YYYY-MM
            ':fee_type_code'       => $data['fee_type_code'] !== '' ? (int)$data['fee_type_code'] : null,
            ':amount'              => ($data['amount'] ?? '') === '' ? null : (float)$data['amount'],
            ':currency_code'       => trim((string)($data['currency_code'] ?? 'ILS')) ?: 'ILS',
            ':due_date'            => ($data['due_date'] ?? '') ?: null,
            ':payment_date'        => ($data['payment_date'] ?? '') ?: null,
            ':status_code'         => $data['status_code'] !== '' ? (int)$data['status_code'] : null,
            ':payment_method_code' => $data['payment_method_code'] !== '' ? (int)$data['payment_method_code'] : null,
            ':reference_number'    => trim((string)($data['reference_number'] ?? '')) ?: null,
            ':notes'               => ($data['notes'] ?? '') ?: null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE employer_corporate_fees
                   SET employer_id = :employer_id,
                       period_ym = :period_ym,
                       fee_type_code = :fee_type_code,
                       amount = :amount,
                       currency_code = :currency_code,
                       due_date = :due_date,
                       payment_date = :payment_date,
                       status_code = :status_code,
                       payment_method_code = :payment_method_code,
                       reference_number = :reference_number,
                       notes = :notes
                 WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id'                  => $id,
            ':employer_id'         => (int)($data['employer_id'] ?? 0),
            ':period_ym'           => trim((string)($data['period_ym'] ?? '')) ?: null,
            ':fee_type_code'       => $data['fee_type_code'] !== '' ? (int)$data['fee_type_code'] : null,
            ':amount'              => ($data['amount'] ?? '') === '' ? null : (float)$data['amount'],
            ':currency_code'       => trim((string)($data['currency_code'] ?? 'ILS')) ?: 'ILS',
            ':due_date'            => ($data['due_date'] ?? '') ?: null,
            ':payment_date'        => ($data['payment_date'] ?? '') ?: null,
            ':status_code'         => $data['status_code'] !== '' ? (int)$data['status_code'] : null,
            ':payment_method_code' => $data['payment_method_code'] !== '' ? (int)$data['payment_method_code'] : null,
            ':reference_number'    => trim((string)($data['reference_number'] ?? '')) ?: null,
            ':notes'               => ($data['notes'] ?? '') ?: null,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM employer_corporate_fees WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM employer_corporate_fees WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // ===== רשימות עם סינון + פאג'ינציה =====

    /**
     * פילטרים:
     * - employer_id
     * - status: קוד סטטוס מספרי
     * - q: חיפוש במס׳ אסמכתא/הערות
     * - period_from / period_to: YYYY-MM
     * - paid_until: YYYY-MM-DD – תשלומים שבוצעו עד תאריך
     */
    public function all(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['employer_id'])) {
            $where[] = 'f.employer_id = :employer_id';
            $params[':employer_id'] = (int)$filters['employer_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'f.status_code = :status_code';
            $params[':status_code'] = (int)$filters['status'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(f.reference_number LIKE :q OR f.notes LIKE :q)';
            $params[':q'] = '%' . trim($filters['q']) . '%';
        }
        if (!empty($filters['period_from'])) {
            $where[] = 'f.period_ym >= :period_from';
            $params[':period_from'] = $filters['period_from'];
        }
        if (!empty($filters['period_to'])) {
            $where[] = 'f.period_ym <= :period_to';
            $params[':period_to'] = $filters['period_to'];
        }
        if (!empty($filters['paid_until'])) {
            $where[] = 'f.payment_date IS NOT NULL AND f.payment_date <= :paid_until';
            $params[':paid_until'] = $filters['paid_until'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "SELECT f.*, r.first_name, r.last_name, r.id_number,
                       sc.name_he AS status_name,
                       tc.name_he AS fee_type_name,
                       pmc.name_he AS payment_method_name
                  FROM employer_corporate_fees f
             LEFT JOIN employers r ON r.id = f.employer_id
             LEFT JOIN corporate_fee_status_codes sc ON sc.corporate_fee_status_code = f.status_code
             LEFT JOIN corporate_fee_type_codes   tc ON tc.corporate_fee_type_code   = f.fee_type_code
             LEFT JOIN payment_method_codes      pmc ON pmc.payment_method_code      = f.payment_method_code
                  $whereSql
              ORDER BY COALESCE(f.payment_date, '9999-12-31') DESC, COALESCE(f.due_date, '9999-12-31') DESC, f.id DESC
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
            $where[] = 'status_code = :status_code';
            $params[':status_code'] = (int)$filters['status'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(reference_number LIKE :q OR notes LIKE :q)';
            $params[':q'] = '%' . trim($filters['q']) . '%';
        }
        if (!empty($filters['period_from'])) {
            $where[] = 'period_ym >= :period_from';
            $params[':period_from'] = $filters['period_from'];
        }
        if (!empty($filters['period_to'])) {
            $where[] = 'period_ym <= :period_to';
            $params[':period_to'] = $filters['period_to'];
        }
        if (!empty($filters['paid_until'])) {
            $where[] = 'payment_date IS NOT NULL AND payment_date <= :paid_until';
            $params[':paid_until'] = $filters['paid_until'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM employer_corporate_fees $whereSql");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
}
