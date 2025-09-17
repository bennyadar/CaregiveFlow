<?php

/**
 * מודל דרכונים למעסיק (employer_passports)
 * שימוש בטבלת countries עבור קוד לאום (country_code) ובטבלאות קוד לסטטוס/סוג דרכון.
 * המבנה והסגנון זהים למודול הוויזות/דרכונים לעובד.
 */
class EmployerPassport
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
        $data = $this->normalize($data);

        // אם הדרכון מסומן כראשי – נבטל ראשי לאחרים של אותו מעסיק
        if (!empty($data['is_primary'])) {
            $this->unsetPrimaryForEmployer((int)$data['employer_id']);
        }

        $sql = "INSERT INTO employer_passports (
                    employer_id,
                    passport_number,
                    passport_type_code,
                    country_code,
                    issue_date,
                    expiry_date,
                    is_primary,
                    issue_place,
                    status_code,
                    notes
                ) VALUES (
                    :employer_id,
                    :passport_number,
                    :passport_type_code,
                    :country_code,
                    :issue_date,
                    :expiry_date,
                    :is_primary,
                    :issue_place,
                    :status_code,
                    :notes
                )";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':employer_id'        => (int)($data['employer_id'] ?? 0),
            ':passport_number'    => trim((string)($data['passport_number'] ?? '')),
            ':passport_type_code' => $data['passport_type_code'] !== '' ? (int)$data['passport_type_code'] : null,
            ':country_code'       => $data['country_code'] !== '' ? (int)$data['country_code'] : null,
            ':issue_date'         => ($data['issue_date'] ?? '') ?: null,
            ':expiry_date'        => ($data['expiry_date'] ?? '') ?: null,
            ':is_primary'         => (int)$data['is_primary'],
            ':issue_place'        => ($data['issue_place'] ?? '') ?: null,
            ':status_code'        => $data['status_code'] !== '' ? (int)$data['status_code'] : null,
            ':notes'              => ($data['notes'] ?? '') ?: null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $existing = $this->find($id);
        if (!$existing) {
            return false;
        }

        $data = $this->normalize($data, $existing);

        if (!empty($data['is_primary'])) {
            $this->unsetPrimaryForEmployer((int)$data['employer_id'], $excludeId = $id);
        }

        $sql = "UPDATE employer_passports
                   SET employer_id = :employer_id,
                       passport_number = :passport_number,
                       passport_type_code = :passport_type_code,
                       country_code = :country_code,
                       issue_date = :issue_date,
                       expiry_date = :expiry_date,
                       is_primary = :is_primary,
                       issue_place = :issue_place,
                       status_code = :status_code,
                       notes = :notes
                 WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id'                 => $id,
            ':employer_id'        => (int)($data['employer_id'] ?? 0),
            ':passport_number'    => trim((string)($data['passport_number'] ?? '')),
            ':passport_type_code' => $data['passport_type_code'] !== '' ? (int)$data['passport_type_code'] : null,
            ':country_code'       => $data['country_code'] !== '' ? (int)$data['country_code'] : null,
            ':issue_date'         => ($data['issue_date'] ?? '') ?: null,
            ':expiry_date'        => ($data['expiry_date'] ?? '') ?: null,
            ':is_primary'         => (int)$data['is_primary'],
            ':issue_place'        => ($data['issue_place'] ?? '') ?: null,
            ':status_code'        => $data['status_code'] !== '' ? (int)$data['status_code'] : null,
            ':notes'              => ($data['notes'] ?? '') ?: null,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM employer_passports WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    /** סימון דרכון כראשי (מנקה ראשי לאחרים של אותו מעסיק) */
    public function setPrimary(int $id): bool
    {
        $row = $this->find($id);
        if (!$row) return false;

        $empId = (int)$row['employer_id'];
        $this->unsetPrimaryForEmployer($empId);

        $stmt = $this->pdo->prepare("UPDATE employer_passports SET is_primary = 1, updated_at = NOW() WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM employer_passports WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // ===== רשימות עם סינון + פאג'ינציה =====

  /**
     * שליפה מרובה עם סינון, מיון ופאגינציה
     * הערה: המיון ברירת מחדל – ראשי קודם, ואז לפי תאריך פקיעה עולה, ואז id יורד
     */
    public function all(array $filters = [], int $limit = 25, int $offset = 0, ?string $order_by = null): array
    {
        $where  = [];
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
            $where[] = '(p.passport_number LIKE :q OR p.issue_place LIKE :q OR p.notes LIKE :q)';
            $params[':q'] = '%' . trim((string)$filters['q']) . '%';
        }
        if (!empty($filters['expires_until'])) {
            $where[] = 'p.expiry_date IS NOT NULL AND p.expiry_date <= :expires_until';
            $params[':expires_until'] = $filters['expires_until'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $order    = $order_by ?: 'p.is_primary DESC, p.expiry_date ASC, p.id DESC';

        // שימו לב: פה יש ORDER BY אחד בלבד. אם היה לכם שורה נוספת של ORDER BY — יש להסיר.
        $sql = "SELECT p.*, e.first_name, e.last_name, e.id_number, e.passport_number AS employer_passport_number,
                       sc.name_he AS status_name,
                       tc.name_he AS type_name,
                       c.name_he  AS country_name
                  FROM employer_passports p
             LEFT JOIN employers             e  ON e.id = p.employer_id
             LEFT JOIN passport_status_codes sc ON sc.passport_status_code  = p.status_code
             LEFT JOIN passport_type_codes   tc ON tc.passport_type_code    = p.passport_type_code
             LEFT JOIN countries             c  ON c.country_code           = p.country_code
                  $whereSql
                  ORDER BY $order
                 LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->bindValue(':limit', max(0, $limit), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** ספירה לצורך פאגינציה — תנאים זהים ל-all() */
    public function count(array $filters = []): int
    {
        $where  = [];
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
            $where[] = '(p.passport_number LIKE :q OR p.issue_place LIKE :q OR p.notes LIKE :q)';
            $params[':q'] = '%' . trim((string)$filters['q']) . '%';
        }
        if (!empty($filters['expires_until'])) {
            $where[] = 'p.expiry_date IS NOT NULL AND p.expiry_date <= :expires_until';
            $params[':expires_until'] = $filters['expires_until'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM employer_passports p $whereSql");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

// ===================== Private Helpers =====================

    /**
     * בונה WHERE דינמי לפי פילטרים:
     * - employer_id (int)
     * - q (חיפוש חלקי במספר דרכון)
     * - expires_until (DATE 'YYYY-MM-DD') – דרכונים שפוקעים עד תאריך זה (כולל)
     */
    private function buildWhere(array $filters): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['employer_id'])) {
            $where[] = 'employer_id = :employer_id';
            $params[':employer_id'] = (int)$filters['employer_id'];
        }

        if (!empty($filters['q'])) {
            $where[] = 'passport_number LIKE :q';
            $params[':q'] = '%' . trim((string)$filters['q']) . '%';
        }

        if (!empty($filters['expires_until'])) {
            // אין המרת תאריך ב-PHP – מעבירים כפי שהוא; ה-DB יאמת את התקינות
            $where[] = '(expiry_date IS NOT NULL AND expiry_date <= :expires_until)';
            $params[':expires_until'] = $filters['expires_until'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        return [$whereSql, $params];
    }

    /**
     * נירמול נתונים לפני כתיבה:
     * - רווחים מיותרים
     * - המרות '' ל-NULL עבור תאריכים/הערות
     * - cast בטוח לטיפוסים בסיסיים
     */
    private function normalize(array $data, ?array $fallback = null): array
    {
        $out = [
            'employer_id'        => $data['employer_id']        ?? ($fallback['employer_id']        ?? null),
            'passport_number'    => trim((string)($data['passport_number'] ?? ($fallback['passport_number'] ?? ''))),
            'passport_type_code' => $data['passport_type_code'] ?? ($fallback['passport_type_code'] ?? ''),
            'country_code'       => $data['country_code']       ?? ($fallback['country_code']       ?? ''),
            'issue_date'         => $this->emptyToNull($data['issue_date']  ?? ($fallback['issue_date']  ?? null)),
            'expiry_date'        => $this->emptyToNull($data['expiry_date'] ?? ($fallback['expiry_date'] ?? null)),
            'is_primary'         => !empty($data['is_primary']) ? 1 : (int)($fallback['is_primary'] ?? 0),
            'issue_place'        => $this->emptyToNull($data['issue_place'] ?? ($fallback['issue_place'] ?? null)),
            'status_code'        => $data['status_code']        ?? ($fallback['status_code']        ?? ''),
            'notes'              => $this->emptyToNull($data['notes']        ?? ($fallback['notes']        ?? null)),
        ];

        // המרות/הקשחות
        $out['employer_id']        = (int)$out['employer_id'];
        $out['passport_type_code'] = ($out['passport_type_code'] === '' ? null : (int)$out['passport_type_code']);
        $out['country_code']       = ($out['country_code']       === '' ? null : (int)$out['country_code']);
        $out['status_code']        = ($out['status_code']        === '' ? null : (int)$out['status_code']);

        return $out;
    }

    private function emptyToNull($v)
    {
        return ($v === '' || $v === null) ? null : $v;
    }

    /** ניקוי ראשי לכל שאר הדרכונים של אותו מעסיק; אפשרות לא לכלול מזהה מסוים */
    private function unsetPrimaryForEmployer(int $employerId, ?int $excludeId = null): void
    {
        if ($excludeId) {
            $sql = "UPDATE employer_passports SET is_primary = 0, updated_at = NOW()
                    WHERE employer_id = :emp AND id <> :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':emp' => $employerId, ':id' => $excludeId]);
        } else {
            $sql = "UPDATE employer_passports SET is_primary = 0, updated_at = NOW()
                    WHERE employer_id = :emp";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':emp' => $employerId]);
        }
    }    
}
