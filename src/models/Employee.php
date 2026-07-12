<?php
class Employee {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    // שדות שמותר לכתוב (CRUD)
    private array $fillable = [
        'country_of_citizenship',
        'last_name','last_name_he','first_name_he','first_name','father_name_en',
        'gender_code','marital_status_code','birth_date',
        'phone','phone_prefix_il','phone_number_il','phone_alt','email',
        'city_code','street_code','street_name_he','house_no','apartment','zipcode',
        'abroad_city','abroad_street','abroad_house_no','abroad_postal_code',
        'entry_date','visa_type','visa_expiry',
        'work_permit_number','work_permit_issue','work_permit_expiry',
        'is_active','employment_status_code','status_change_date',
        'record_type_code','mana_type_code','remarks_internal','notes',
        // בנק חו"ל  מוטב  היתר
        'bank_account','bank_foreign_country_code','bank_city_foreign','bank_street_foreign','bank_house_no_foreign',
        'bank_code_foreign','bank_name_foreign','bank_branch_code_foreign','bank_branch_name_foreign',
        'bank_swift','bank_iban','beneficiary_last_name','beneficiary_first_name','permit_number_bafi',
        'mother_name_en', 'spouse_name_en', 'spouse_in_israel', 'representative_abroad_name', 'health_ins_issue_date',
        'health_ins_expiry', 'metash_mana_number', 'metash_registration_date',
    ];

    /* ===== Read methods ===== */
    /**
     * חיפוש עובדים עם פילטרים (שימוש במסך העובדים החדש).
     * פילטרים נתמכים (כולם אופציונליים):
     *   - q (string)         חיפוש חופשי
     *   - is_active ("1"|"0"|"")
     *   - placement ("any"|"active"|"none")
     *   - employer_id (int)  סינון לפי מעסיק בשיבוץ פעיל
     *   - expiry (string)    passport_expired|passport_soon|visa_expired|visa_soon|insurance_expired|insurance_soon
     *   - soon_days (int)    חלון "בקרוב" (ברירת מחדל: 30)
     */
    public function search(array $filters, int $limit = 20, int $offset = 0): array {
        [$sql, $params] = $this->buildSearchSql($filters, false);
        $sql .= " ORDER BY e.id DESC LIMIT :limit OFFSET :offset";
        $st = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v);
        }
        $st->bindValue(':limit', $limit, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function searchCount(array $filters): int {
        [$sql, $params] = $this->buildSearchSql($filters, true);
        $st = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v);
        }
        $st->execute();
        return (int)$st->fetchColumn();
    }

    private function buildSearchSql(array $filters, bool $forCount): array {
        $q = trim((string)($filters['q'] ?? ''));
        $isActive = (string)($filters['is_active'] ?? '');
        $placement = (string)($filters['placement'] ?? 'any');
        $employerId = (int)($filters['employer_id'] ?? 0);
        $expiry = (string)($filters['expiry'] ?? '');
        $soonDays = (int)($filters['soon_days'] ?? 30);

        // תאריך סיום "בקרוב" מחושב ב-PHP כדי להימנע מ-INTERVAL עם bind.
        $soonTo = date('Y-m-d', strtotime('+' . max(1, $soonDays) . ' days'));

        $select = $forCount
            ? 'SELECT COUNT(*)'
            : 'SELECT e.*';

        $sql = $select . "\nFROM employees e\n";

        // תוקפים (מוצגים במסך + מאפשרים פילטרים)
        $sql .= "LEFT JOIN (SELECT employee_id, MAX(expiry_date) AS passport_expiry_date FROM employee_passports GROUP BY employee_id) pp ON pp.employee_id = e.id\n";
        $sql .= "LEFT JOIN (SELECT employee_id, MAX(expiry_date) AS visa_expiry_date FROM visas GROUP BY employee_id) v ON v.employee_id = e.id\n";
        $sql .= "LEFT JOIN (SELECT employee_id, MAX(expiry_date) AS insurance_expiry_date FROM employee_insurances GROUP BY employee_id) ins ON ins.employee_id = e.id\n";

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = "(\n                   e.passport_number LIKE :q\n                OR e.first_name      LIKE :q\n                OR e.last_name       LIKE :q\n                OR e.phone           LIKE :q\n                OR e.phone_alt       LIKE :q\n                OR CONCAT(e.phone_prefix_il, e.phone_number_il) LIKE :q\n                OR EXISTS (\n                       SELECT 1\n                         FROM placements p\n                         JOIN employers r ON r.id = p.employer_id\n                        WHERE p.employee_id = e.id\n                          AND p.start_date <= CURDATE()\n                          AND (p.end_date IS NULL OR p.end_date >= CURDATE())\n                          AND COALESCE(NULLIF(r.company_name,''), TRIM(CONCAT(r.last_name,' ', r.first_name))) LIKE :q\n                   )\n            )";
            $params[':q'] = '%' . $q . '%';
        }

        if ($isActive === '1' || $isActive === '0') {
            $where[] = 'e.is_active = :is_active';
            $params[':is_active'] = (int)$isActive;
        }

        if ($placement === 'active') {
            $where[] = "EXISTS (SELECT 1 FROM placements p WHERE p.employee_id = e.id AND p.start_date <= CURDATE() AND (p.end_date IS NULL OR p.end_date >= CURDATE()))";
        } elseif ($placement === 'none') {
            $where[] = "NOT EXISTS (SELECT 1 FROM placements p WHERE p.employee_id = e.id AND p.start_date <= CURDATE() AND (p.end_date IS NULL OR p.end_date >= CURDATE()))";
        }

        if ($employerId > 0) {
            // לפי המוקאף: סינון לפי מעסיק בשיבוץ פעיל.
            $where[] = "EXISTS (\n                SELECT 1\n                  FROM placements p\n                 WHERE p.employee_id = e.id\n                   AND p.employer_id = :employer_id\n                   AND p.start_date <= CURDATE()\n                   AND (p.end_date IS NULL OR p.end_date >= CURDATE())\n            )";
            $params[':employer_id'] = $employerId;
        }

        if ($expiry !== '') {
            switch ($expiry) {
                case 'passport_expired':
                    $where[] = 'pp.passport_expiry_date IS NOT NULL AND pp.passport_expiry_date < CURDATE()';
                    break;
                case 'passport_soon':
                    $where[] = 'pp.passport_expiry_date IS NOT NULL AND pp.passport_expiry_date >= CURDATE() AND pp.passport_expiry_date <= :soon_to';
                    $params[':soon_to'] = $soonTo;
                    break;
                case 'visa_expired':
                    $where[] = 'v.visa_expiry_date IS NOT NULL AND v.visa_expiry_date < CURDATE()';
                    break;
                case 'visa_soon':
                    $where[] = 'v.visa_expiry_date IS NOT NULL AND v.visa_expiry_date >= CURDATE() AND v.visa_expiry_date <= :soon_to';
                    $params[':soon_to'] = $soonTo;
                    break;
                case 'insurance_expired':
                    $where[] = 'ins.insurance_expiry_date IS NOT NULL AND ins.insurance_expiry_date < CURDATE()';
                    break;
                case 'insurance_soon':
                    $where[] = 'ins.insurance_expiry_date IS NOT NULL AND ins.insurance_expiry_date >= CURDATE() AND ins.insurance_expiry_date <= :soon_to';
                    $params[':soon_to'] = $soonTo;
                    break;
                default:
                    // no-op
                    break;
            }
        }

        if ($where) {
            $sql .= "WHERE\n  " . implode("\n  AND ", $where) . "\n";
        }

        return [$sql, $params];
    }

    public function all(string $q = '', int $limit = 20, int $offset = 0): array {
        // שמירה על תאימות לאחור: all() ממשיך לעבוד עם q בלבד.
        return $this->search(['q' => $q], $limit, $offset);
    }

    public function count(string $q = ''): int {
        // שמירה על תאימות לאחור: count() ממשיך לעבוד עם q בלבד.
        return $this->searchCount(['q' => $q]);
    }

    public function find(int $id): ?array {
        $st = $this->pdo->prepare("SELECT * FROM employees WHERE id = ?");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /* ===== Write methods ===== */
    public function create(array $d): int {
        [$cols, $vals, $params] = $this->buildInsert($d);
        $sql = "INSERT INTO employees (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $d): void {
        [$sets, $params] = $this->buildUpdate($d);
        if (!$sets) return;
        $sql = "UPDATE employees SET " . implode(',', $sets) . " WHERE id = :id";
        $params[':id'] = $id;
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
    }

    public function delete(int $id): void {
        $st = $this->pdo->prepare("DELETE FROM employees WHERE id = ?");
        $st->execute([$id]);
    }

    /* ===== Helpers ===== */
    private function normalize($k, $v) {
        if ($k === 'is_active') return !empty($v) ? 1 : 0;
        $v = is_string($v) ? trim($v) : $v;
        return ($v === '' ? null : $v);
    }

    private function buildInsert(array $d): array {
        $cols = []; $vals = []; $params = [];
        foreach ($this->fillable as $k) {
            if (array_key_exists($k, $d)) {
                $cols[] = $k;
                $vals[] = ':' . $k;
                $params[':' . $k] = $this->normalize($k, $d[$k]);
            }
        }
        // שדות חובה מינימליים
        if (!in_array('passport_number', $cols, true)) {
            $cols[]='passport_number'; $vals[]=':passport_number'; $params[':passport_number']=trim((string)($d['passport_number']??''));
        }
        if (!in_array('first_name', $cols, true)) {
            $cols[]='first_name'; $vals[]=':first_name'; $params[':first_name']=trim((string)($d['first_name']??''));
        }
        if (!in_array('last_name', $cols, true)) {
            $cols[]='last_name'; $vals[]=':last_name'; $params[':last_name']=trim((string)($d['last_name']??''));
        }
        // ברירת מחדל is_active=1 אם לא נשלח
        if (!array_key_exists(':is_active', $params)) { $cols[]='is_active'; $vals[]=':is_active'; $params[':is_active']=1; }
        return [$cols,$vals,$params];
    }

    private function buildUpdate(array $d): array {
        $sets = []; $params = [];
        foreach ($this->fillable as $k) {
            if (array_key_exists($k, $d)) {
                $sets[] = "$k = :$k";
                $params[":$k"] = $this->normalize($k, $d[$k]);
            }
        }
        return [$sets,$params];
    }
}
