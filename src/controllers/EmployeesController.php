<?php
require_once __DIR__ . '/../models/Employee.php';
require_once __DIR__ . '/../models/CodeTables.php';
require_once __DIR__ . '/../models/Placement.php';

class EmployeesController {
    public static function index(PDO $pdo) {
        require_login();
        $q = trim($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20; $offset = ($page-1)*$limit;
        $m = new Employee($pdo);
        $rows = $m->all($q, $limit, $offset);
        $total = $m->count($q);

        // === [ADDED] צרוף "מעסיק נוכחי" לכל עובד לרשימה ===
        // זיהוי העובדים שנמצאים בעמוד הנוכחי
        $empIds = array_column($rows, 'id');
        $employerByEmployee = [];

        if (!empty($empIds)) {
            // שליפה מרוכזת של כל השיבוצים + פרטי המעסיק עבור העובדים בעמוד
            $in = implode(',', array_fill(0, count($empIds), '?'));
            $sql = "
                SELECT 
                    p.employee_id,
                    p.id          AS placement_id,
                    p.start_date,
                    p.end_date,
                    COALESCE(NULLIF(r.company_name, ''), TRIM(CONCAT(r.last_name, ' ', r.first_name))) AS employer_name
                FROM placements p
                JOIN employers  r ON r.id = p.employer_id
                WHERE p.employee_id IN ($in)
            ";
            $st = $pdo->prepare($sql);
            $st->execute($empIds);
            $all = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // מימשנו את הלוגיקה של BAFI: פעיל תחילה => end_date (NULL הכי גדול) => id יורד
            $today = date('Y-m-d');
            $byEmp = [];
            foreach ($all as $pl) {
                $isActive = ($pl['start_date'] <= $today) && (is_null($pl['end_date']) || $pl['end_date'] >= $today);
                $pl['_rank'] = $isActive ? 1 : 0;
                $byEmp[(int)$pl['employee_id']][] = $pl;
            }

            foreach ($byEmp as $eid => $list) {
                usort($list, function ($a, $b) {
                    if ($a['_rank'] !== $b['_rank']) return $b['_rank'] <=> $a['_rank'];
                    $ae = $a['end_date'] ?? '9999-12-31';
                    $be = $b['end_date'] ?? '9999-12-31';
                    if ($ae !== $be) return strcmp($be, $ae);          // תאריך אחרון קודם
                    return (int)$b['placement_id'] <=> (int)$a['placement_id']; // חדש קודם
                });
                $employerByEmployee[$eid] = $list[0]['employer_name'] ?? null;
            }
        }
        
        if (!empty($rows)) {
            // 1) אוסף מזהים של העובדים בדף הנוכחי
            $ids = array_values(array_unique(array_map(static fn($r) => (int)($r['id'] ?? 0), $rows)));

            // 2) מביא מפות פקיעה במכה אחת (Per-page; יעיל)
            $exp = self::expiries_for_employees($pdo, $ids);

            // 3) ממלא טלפון ונצמד לשמות השדות ל-view
            foreach ($rows as &$r) {
                // טלפון: אם חסר phone – ננסה prefix+number, אחרת phone_alt
                if (empty($r['phone'])) {
                $prefix = trim((string)($r['phone_prefix_il'] ?? ''));
                $num = trim((string)($r['phone_number_il'] ?? ''));
                $alt = trim((string)($r['phone_alt'] ?? ''));
                $r['phone'] = $prefix.$num ?: $alt ?: '';
            }

                $id = (int)$r['id'];
                $r['passport_expiry_date'] = $exp[$id]['passport_expiry_date'] ?? null; // דרכון - פקיעה
                $r['visa_expiry_date'] = $exp[$id]['visa_expiry_date'] ?? null; // ויזה - פקיעה
                $r['insurance_expiry_date'] = $exp[$id]['insurance_expiry_date'] ?? null; // ביטוח - פקיעה
                $r['current_employer_name'] = $employerByEmployee[(int)$r['id']] ?? null; // מעסיק נוכחי
            }
            unset($r);
        }

        
        // Load mana type codes for the BAFI export modal
        $mana_type_codes = [];
        try {
            $stmt = $pdo->query("SELECT LPAD(mana_type_code, 2, '0') AS mana_type_code, name_he FROM mana_type_codes ORDER BY mana_type_code");
            $mana_type_codes = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            $mana_type_codes = [];
        }

        // Load record type codes for the BAFI export modal
        $record_type_codes = [];
        try {
            $stmt = $pdo->query("SELECT LPAD(record_type_code, 2, '0') AS record_type_code, name_he FROM record_type_codes ORDER BY record_type_code");
            $record_type_codes = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            $record_type_codes = [];
        }

        // Load "other" end-work reasons (anything not 00/03) for the BAFI export modal
        $end_reasons = [];
        try {
            $stmt = $pdo->query("SELECT LPAD(end_reason_code, 2, '0') AS end_reason_code, name_he FROM end_work_reason_codes 
                                 WHERE end_reason_code NOT IN ('00','03') ORDER BY end_reason_code");
            $end_reasons = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            $end_reasons = [];
        }
      
        require __DIR__ . '/../../views/employees/index.php';
    }
    public static function create(PDO $pdo) {
        require_login();
        $codes = new CodeTables($pdo);
        if (is_post()) {
            if (empty($_POST['first_name']) || empty($_POST['last_name'])) {
                flash('אנא מלא/י שדות חובה: שם פרטי  שם משפחה', 'danger');
            } else { 
                $m = new Employee($pdo); 
                
                try {
                    $pdo->beginTransaction();
                    // יצירת העובד
                    $id = $m->create($_POST);
            
                    // >>> passports quick-add on employee create
                    $pp_num = trim((string)($_POST['passport_number'] ?? ''));
                    if ($pp_num !== '') {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM employee_passports WHERE passport_number = ?");
                        $stmt->execute([$pp_num]);
                        $count = (int)$stmt->fetchColumn();
                        if ($count > 0) {
                            flash('שימו לב: דרכון זה כבר קיים במערכת. יש לבדוק אם העובד כבר קיים לפני יצירת רשומה חדשה.', 'warning');
                        }
                    }
                    $pp_country = $_POST['pp_issuing_country_code'] ?? null;
                    $pp_issue   = $_POST['pp_issue_date'] ?? null;
                    $pp_expiry  = $_POST['pp_expiry_date'] ?? null;
                    $pp_primary = !empty($_POST['pp_is_primary']) ? 1 : 0;
                    $pp_passport_type   = $_POST['pp_passport_type_code'] ?? null;                    
                    
                    // אם מסומן כראשי – ננקה ראשי קיים לעובד זה
                    if ($pp_primary) {
                        $pdo->prepare("UPDATE employee_passports SET is_primary = 0 WHERE employee_id = ?")->execute([$id]);
                    }
                    $stmt = $pdo->prepare("
                        INSERT INTO employee_passports
                            (employee_id, passport_number, passport_type_code, country_code, issue_date, expiry_date, is_primary, notes)
                        VALUES (?,?,?,?,?,?,?,?)
                    ");
                    $stmt->execute([$id, $pp_num, $pp_passport_type, $pp_country, $pp_issue, $pp_expiry, $pp_primary, 'created via employee form']);
                    // <<< passports quick-add

                    // === Visas quick-add ===
                    $vz_num    = trim((string)($_POST['vz_visa_number']   ?? ''));
                    $vz_req    = $_POST['vz_request_date'] ?? null;
                    $vz_issue  = $_POST['vz_issue_date']   ?? null;
                    $vz_expiry = $_POST['vz_expiry_date']  ?? null;
                    if ($vz_num !== '' || $vz_req || $vz_issue || $vz_expiry) {
                        $stmt = $pdo->prepare("INSERT INTO visas
                            (employee_id, visa_number, request_date, issue_date, expiry_date, status, notes)
                            VALUES (:eid, :num, :req, :iss, :exp, :status, :notes)");
                        $stmt->execute([
                            ':eid'    => $id,
                            ':num'    => $vz_num ?: null,
                            ':req'    => $vz_req ?: null,
                            ':iss'    => $vz_issue ?: null,
                            ':exp'    => $vz_expiry ?: null,
                            ':status' => 'requested', // ברירת מחדל לפי הסגנון במודול הוויזות
                            ':notes'  => 'created via employee quick-add',
                        ]);
                    } 
                    
                    // === Insurance quick-add ===
                    $ins_policy  = trim((string)($_POST['ins_policy_number']  ?? ''));
                    $ins_insurer = trim((string)($_POST['ins_insurer_name']   ?? ''));
                    $ins_req     = $_POST['ins_request_date'] ?? null;
                    $ins_issue   = $_POST['ins_issue_date']   ?? null;
                    $ins_expiry  = $_POST['ins_expiry_date']  ?? null;
                    if ($ins_policy !== '' || $ins_insurer !== '' || $ins_req || $ins_issue || $ins_expiry) {
                        $stmt = $pdo->prepare("INSERT INTO employee_insurances
                            (employee_id, policy_number, insurer_name, request_date, issue_date, expiry_date, status_code)
                            VALUES (:eid, :pol, :ins, :req, :iss, :exp, :status)");
                        $stmt->execute([
                            ':eid'    => $id,
                            ':pol'    => $ins_policy ?: null,
                            ':ins'    => $ins_insurer ?: null,
                            ':req'    => $ins_req ?: null,
                            ':iss'    => $ins_issue ?: null,
                            ':exp'    => $ins_expiry ?: null,
                            ':status' => null, // ייגזר להצגה דרך InsuranceService::derivedStatusCode
                        ]);
                    }                    

                    // הכול עבר – נבצע commit
                    $pdo->commit();

                    flash('העובד נשמר בהצלחה!');
                    redirect('employees/edit', ['id' => $id]);

                } catch (PDOException $e) {
                    // כשל באחת השאילתות – מבטלים הכל
                    if ($pdo->inTransaction()) { $pdo->rollBack(); }

                    // לפי הבקשה שלך: אין כתיבה חלקית. כל כישלון מפיל את הפעולה.
                    // אפשר להרחיב כאן מיפוי הודעות ייעודיות (1062 וכו') אם תרצה.
                    flash('שמירת העובד נכשלה. לא בוצעו עדכונים חלקיים. פרטי שגיאה: ' . e($e->getMessage()), 'danger');
                }
                // <<< טרנזקציה אחת לכל התהליך
            }
        }

        $item = ['is_active' => 1];
        $countries = $codes->countries();
        $maritals = $codes->marital_statuses();
        $cities = $codes->cities();
        $streets = [];
        $passport_type_codes = $codes->passport_type_codes();

        require __DIR__ . '/../../views/employees/form.php';
    }
    public static function edit(PDO $pdo) {
        require_login();
        $id = (int)($_GET['id'] ?? 0);
        $m = new Employee($pdo);
        $item = $m->find($id);
        if (!$item) { flash('עובד לא נמצא.', 'danger'); redirect('employees/index'); }
        $codes = new CodeTables($pdo);
        if (is_post()) {
            if (empty($_POST['first_name']) || empty($_POST['last_name'])) {
                flash('אנא מלא/י שדות חובה: שם פרטי  שם משפחה', 'danger');
                redirect('employees/edit', ['id' => $id]);
            }

            try {
                $pdo->beginTransaction();
                $m->update($id, $_POST);
                // >>> passports quick-add on employee update
                $pp_num = trim((string)($_POST['passport_number'] ?? ''));
                if ($pp_num !== '') {
                    $pp_country         = $_POST['pp_issuing_country_code'] ?? null;
                    $pp_issue           = $_POST['pp_issue_date'] ?? null;
                    $pp_expiry          = $_POST['pp_expiry_date'] ?? null;
                    $pp_primary         = !empty($_POST['pp_is_primary']) ? 1 : 0;
                    $pp_passport_type   = $_POST['pp_passport_type_code'] ?? null;

                    try {
                        if ($pp_primary) {
                            $stmt = $pdo->prepare("UPDATE employee_passports SET is_primary = 0 WHERE employee_id = ?")->execute([$id]);
                            $stmt = $pdo->prepare("UPDATE employees SET passport_number = :passport_number WHERE employee_id = :employee_id");
                            $stmt->execute([
                                ':employee_id'      => $id,
                                ':passport_number'  => $pp_num,
                            ]);
                        }
                        $stmt = $pdo->prepare("
                            INSERT INTO employee_passports
                                (employee_id, passport_number, passport_type_code, country_code, issue_date, expiry_date, is_primary, notes)
                            VALUES (?,?,?,?,?,?,?,?)
                        ");
                        $stmt->execute([$id, $pp_num, $pp_passport_type, $pp_country, $pp_issue, $pp_expiry, $pp_primary, 'added via employee edit form']);
                    } catch (PDOException $e) {
                        if ($pdo->inTransaction()) { $pdo->rollBack(); }
                        if (($e->errorInfo[1] ?? null) == 1062) {
                            flash('שימו לב: דרכון זה כבר קיים לעובד. ההוספה דולגה.', 'warning');
                        } else {
                            throw $e;
                        }
                    }
                }

                 // הצלחה מלאה
                $pdo->commit();

                flash('העובד עודכן.');
                redirect('employees/edit', ['id' => $id]);

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                flash('עדכון העובד נכשל. לא בוצעו עדכונים חלקיים. פרטי שגיאה: ' . e($e->getMessage()), 'danger');
            }

        }

        $countries = $codes->countries();
        $genders = $codes->genders();
        $maritals = $codes->marital_statuses();
        $cities = $codes->cities();
        $streets = $item['city_code'] ? $codes->streetsByCity((int)$item['city_code']) : [];
        $passport_type_codes = $codes->passport_type_codes();
        
        require __DIR__ . '/../../views/employees/form.php';
    }
    public static function delete(PDO $pdo) {
        require_login('admin');
        $id = (int)($_GET['id'] ?? 0);
        // ensure no placements exist for this employee before deleting
        if ((new Placement($pdo))->countActiveByEmployee($id) > 0) {
            flash('לא ניתן למחוק עובד עם שיבוץ פעיל.', 'danger');
            redirect('employers/index');
        }        
        (new Employee($pdo))->delete($id);
        flash('העובד נמחק.');
        redirect('employees/index');
    }
    public static function show(PDO $pdo) {
        require_login();
        $id = (int)($_GET['id'] ?? 0);
        $m = new Employee($pdo);
        $item = $m->find($id);
        // >>> passports: employee
        $st = $pdo->prepare("SELECT ep.*, c.name_he 
                             FROM employee_passports ep
                             LEFT JOIN countries c ON c.country_code = ep.country_code
                             WHERE employee_id = ? ORDER BY is_primary DESC, (expiry_date IS NULL) DESC, expiry_date DESC, id DESC");
        $st->execute([$id]);
        $employee_passports = $st->fetchAll(PDO::FETCH_ASSOC);
        // <<< passports
        if (!$item) { flash('עובד לא נמצא.', 'danger'); redirect('employees/index'); }
        
        $codes     = new CodeTables($pdo);
        $countries = $codes->countries();
        $genders   = $codes->genders();
        $maritals  = $codes->marital_statuses();

        // ==== קודי מת״ש למודל הייצוא ====
        // סוגי מנה
        $mana_type_codes = [];
        try {
            $stmt = $pdo->query("SELECT LPAD(mana_type_code, 2, '0') AS mana_type_code, name_he FROM mana_type_codes ORDER BY mana_type_code");
            $mana_type_codes = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) { $mana_type_codes = []; }

        // סוגי רשומה
        $record_type_codes = [];
        try {
            $stmt = $pdo->query("SELECT LPAD(record_type_code, 2, '0') AS record_type_code, name_he FROM record_type_codes ORDER BY record_type_code");
            $record_type_codes = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) { $record_type_codes = []; }

        // קודי סיבת סיום "אחר" (לא 00/03) עבור ה־optgroup במודל
        $end_reasons = [];
        try {
            $stmt = $pdo->query("
                SELECT LPAD(end_reason_code, 2, '0') AS end_reason_code, name_he
                FROM end_work_reason_codes
                WHERE end_reason_code NOT IN ('00','03')
                ORDER BY end_reason_code
            ");
            $end_reasons = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) { $end_reasons = []; }           
        
        require __DIR__ . '/../../views/employees/show.php';
    }

        /**
    * מחזיר מפה employee_id => ['passport_expiry_date','visa_expiry_date','insurance_expiry_date']
    * לוגיקה:
    * - דרכון: MAX(expiry_date) מהטבלה employee_passports (אם קיימות כמה רשומות)
    * - ויזה/ביטוח: הקרוב הבא (MIN>=היום), ואם אין – המאוחר ההיסטורי (MAX)
    *
    * שים לב: אם שמות הטבלאות אצלך שונים, עדכן כאן בלבד (משאיר את שאר הקובץ אחד-לאחד).
    */
    private static function expiries_for_employees(PDO $pdo, array $employee_ids): array
    {
    if (empty($employee_ids)) { return []; }


    // בניית placeholders ל-IN בצורה בטוחה
    $in = [];
    $params = [];
    foreach ($employee_ids as $i => $id) {
    $ph = ":id{$i}";
    $in[] = $ph;
    $params[$ph] = (int)$id;
    }
    $inList = implode(',', $in);


    // 1) דרכונים
    $passport = [];
    $sqlP = "SELECT ep.employee_id, MAX(ep.expiry_date) AS passport_expiry_date
    FROM employee_passports ep
    WHERE ep.employee_id IN ($inList)
    GROUP BY ep.employee_id";
    $stmtP = $pdo->prepare($sqlP);
    $stmtP->execute($params);
    foreach ($stmtP->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $passport[(int)$r['employee_id']] = (string)$r['passport_expiry_date'];
    }


    // 2) ויזות
    $visa = [];
    $sqlV = "SELECT v.employee_id,
    COALESCE(MIN(CASE WHEN v.expiry_date >= CURDATE() THEN v.expiry_date END),
    MAX(v.expiry_date)) AS visa_expiry_date
    FROM visas v
    WHERE v.employee_id IN ($inList)
    GROUP BY v.employee_id";
    $stmtV = $pdo->prepare($sqlV);
    $stmtV->execute($params);
    foreach ($stmtV->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $visa[(int)$r['employee_id']] = (string)$r['visa_expiry_date'];
    }


    // 3) ביטוחים
    $ins = [];
    $sqlI = "SELECT i.employee_id,
    COALESCE(MIN(CASE WHEN i.expiry_date >= CURDATE() THEN i.expiry_date END),
    MAX(i.expiry_date)) AS insurance_expiry_date
    FROM employee_insurances i
    WHERE i.employee_id IN ($inList)
    GROUP BY i.employee_id";
    $stmtI = $pdo->prepare($sqlI);
    $stmtI->execute($params);
    foreach ($stmtI->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $ins[(int)$r['employee_id']] = (string)$r['insurance_expiry_date'];
    }


    // מיזוג לתוצאה אחת
    $out = [];
    foreach ($employee_ids as $id) {
    $out[(int)$id] = [
    'passport_expiry_date' => $passport[(int)$id] ?? null,
    'visa_expiry_date' => $visa[(int)$id] ?? null,
    'insurance_expiry_date' => $ins[(int)$id] ?? null,
    ];
    }
    return $out;
    }
    
}
