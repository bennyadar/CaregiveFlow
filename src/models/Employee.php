<?php
class Employee {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    // שדות שמותר לכתוב (CRUD)
    private array $fillable = [
        'passport_number','country_of_citizenship','country_symbol_moi',
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
    public function all(string $q = '', int $limit = 20, int $offset = 0): array {
        if ($q) {
        // חיפוש בשם/דרכון/טלפונים  מעסיק נוכחי (שיבוץ פעיל; ואם אין – השיבוץ האחרון)
        $sql = "SELECT * FROM employees e
                WHERE (
                       e.passport_number LIKE :q
                    OR e.first_name      LIKE :q
                    OR e.last_name       LIKE :q
                    OR e.phone           LIKE :q
                    OR e.phone_alt       LIKE :q
                    OR CONCAT(e.phone_prefix_il, e.phone_number_il) LIKE :q
                    OR EXISTS (
                           SELECT 1
                             FROM placements p
                             JOIN employers r ON r.id = p.employer_id
                            WHERE p.employee_id = e.id
                              AND p.start_date <= CURDATE()
                              AND (p.end_date IS NULL OR p.end_date >= CURDATE())             -- שיבוץ פעיל
                              AND COALESCE(NULLIF(r.company_name,''), TRIM(CONCAT(r.last_name,' ', r.first_name))) LIKE :q
                       )
                    OR (
                           NOT EXISTS (                                         -- אין שיבוץ פעיל
                               SELECT 1 FROM placements p
                                WHERE p.employee_id = e.id
                                  AND p.start_date <= CURDATE()
                                  AND (p.end_date IS NULL OR p.end_date >= CURDATE())
                           )
                           AND EXISTS (                                         -- השתמש בשיבוץ האחרון
                               SELECT 1
                                 FROM placements p
                                 JOIN employers r ON r.id = p.employer_id
                                WHERE p.employee_id = e.id
                                  AND p.id = (SELECT MAX(p2.id) FROM placements p2 WHERE p2.employee_id = e.id)
                                  AND COALESCE(NULLIF(r.company_name,''), TRIM(CONCAT(r.last_name,' ', r.first_name))) LIKE :q
                           )
                       )
                )
                ORDER BY e.id DESC LIMIT :limit OFFSET :offset";
        $st = $this->pdo->prepare($sql);
        $like = "%{$q}%";
        $st->bindValue(':q', $like, PDO::PARAM_STR);
        } else {
            $st = $this->pdo->prepare("SELECT * FROM employees ORDER BY id DESC LIMIT :limit OFFSET :offset");
        }
        $st->bindValue(':limit', $limit, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count(string $q = ''): int {
        if ($q) {
        // חיפוש בשם/דרכון/טלפונים  מעסיק נוכחי (שיבוץ פעיל; ואם אין – השיבוץ האחרון)
        $sql = "SELECT * FROM employees e
                WHERE (
                       e.passport_number LIKE :q
                    OR e.first_name      LIKE :q
                    OR e.last_name       LIKE :q
                    OR e.phone           LIKE :q
                    OR e.phone_alt       LIKE :q
                    OR CONCAT(e.phone_prefix_il, e.phone_number_il) LIKE :q
                    OR EXISTS (
                           SELECT 1
                             FROM placements p
                             JOIN employers r ON r.id = p.employer_id
                            WHERE p.employee_id = e.id
                              AND p.start_date <= CURDATE()
                              AND (p.end_date IS NULL OR p.end_date >= CURDATE())             -- שיבוץ פעיל
                              AND COALESCE(NULLIF(r.company_name,''), TRIM(CONCAT(r.last_name,' ', r.first_name))) LIKE :q
                       )
                    OR (
                           NOT EXISTS (                                         -- אין שיבוץ פעיל
                               SELECT 1 FROM placements p
                                WHERE p.employee_id = e.id
                                  AND p.start_date <= CURDATE()
                                  AND (p.end_date IS NULL OR p.end_date >= CURDATE())
                           )
                           AND EXISTS (                                         -- השתמש בשיבוץ האחרון
                               SELECT 1
                                 FROM placements p
                                 JOIN employers r ON r.id = p.employer_id
                                WHERE p.employee_id = e.id
                                  AND p.id = (SELECT MAX(p2.id) FROM placements p2 WHERE p2.employee_id = e.id)
                                  AND COALESCE(NULLIF(r.company_name,''), TRIM(CONCAT(r.last_name,' ', r.first_name))) LIKE :q
                           )
                       )
                )
                ORDER BY e.id DESC LIMIT :limit OFFSET :offset";
        $st = $this->pdo->prepare($sql);
            $like = "%{$q}%";
            $st->execute([$like, $like, $like]);
            return (int)$st->fetchColumn();
        }
        return (int)$this->pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
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
