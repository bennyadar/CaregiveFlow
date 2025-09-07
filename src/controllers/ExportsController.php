<?php
declare(strict_types=1);

class ExportsController
{
        /**
     * BAFI data-line fixed length per spec (21.08.2019): 705 chars.
     * חשוב: אין לקצץ/להוסיף תווים מלבד ריפוד רווחים בסוף השורה.
     */
    private const BAFI_LINE_LEN = 705;

    /** תצוגת טופס BAFI: GET index.php?r=exports/bafi */
    public static function bafi(PDO $pdo): void
    {
        require_login();

        $preselect_id = (int)($_GET['employee_id'] ?? 0);
        
        // רשימת עובדים לבחירה בטופס
        $employees = $pdo->query("SELECT id, id_number, last_name, first_name FROM employees ORDER BY id DESC")
                         ->fetchAll(PDO::FETCH_ASSOC);

        // טבלאות קוד למסכים (מותאם לשמות עמודות שהיו במודל)
        $record_type_codes = $pdo->query("
            SELECT record_type_code, name_he
            FROM record_type_codes
            ORDER BY record_type_code
        ")->fetchAll(PDO::FETCH_ASSOC);

        $mana_type_codes = $pdo->query("
            SELECT mana_type_code, name_he
            FROM mana_type_codes
            ORDER BY mana_type_code
        ")->fetchAll(PDO::FETCH_ASSOC);

        $end_reasons = $pdo->query("
            SELECT end_reason_code, name_he
            FROM end_work_reason_codes
            ORDER BY end_reason_code
        ")->fetchAll(PDO::FETCH_ASSOC);

        // ערכי דיפולט/פרה-פילד לתיבה
        $prefill = [
            'employee_id'   => $preselect_id,
            'file_type'     => $_GET['file_type']     ?? '50',
            'bureau_number' => $_GET['bureau_number'] ?? '',
            'sector_code'   => $_GET['sector_code']   ?? '09',
            'record_type'   => $_GET['record_type']   ?? '',
            'mana_type'     => $_GET['mana_type']     ?? '',
            'status_code'   => $_GET['status_code']   ?? '00',
            'status_date'   => $_GET['status_date']   ?? date('Y-m-d'), // קלט UI
            'save_history'  => isset($_GET['save_history']) ? (int)$_GET['save_history'] : 1,
        ];  

        require __DIR__ . '/../../views/exports/bafi_form.php';
    }

    /** ולידציה עסקית ל־BAFI לפי טבלת (-4/-5/-18) במסמך.
     *  קלט כשמות בטופס: record_type, mana_type, status_code, status_date (Y-m-d מהטופס).
     *  מחזיר מערך שגיאות (ריק = תקין).
     */
    private static function validateBafiInputs(array $in): array
    {
        $errors = [];

        $rt = str_pad((string)($in['record_type'] ?? ''), 2, '0', STR_PAD_LEFT);
        $mt = str_pad((string)($in['mana_type']   ?? ''), 2, '0', STR_PAD_LEFT);
        $sc = strtoupper(trim((string)($in['status_code'] ?? '')));
        $sd = trim((string)($in['status_date'] ?? '')); // Y-m-d (כפי שמגיע מהטופס)

        $requiresDate = false;
        $combo = $rt.$mt;

        // 01/01 + סטטוס 00 → חובה "תאריך תחילת עבודה"
        if ($combo === '0101' && $sc === '00') {
            $requiresDate = true;
        }
        // 02/02 מעבר בין לשכות/מעסיקים → חובה תאריך (במסמך מצוין גם סיום וגם תחילה; בטופס יש שדה תאריך יחיד)
        if ($combo === '0202') {
            $requiresDate = true;
        }
        // 02/03, 02/04, 02/05 → חובה תאריך (ת.תחילת עבודה)
        if (in_array($combo, ['0203','0204','0205'], true)) {
            $requiresDate = true;
        }
        // 03/02 סגירה → חובה תאריך + "קוד סיבת סיום" (לא 00/03)
        if ($combo === '0302') {
            $requiresDate = true;
            if ($sc === '00' || $sc === '03' || $sc === '') {
                $errors[] = 'ב־03/02 חובה לבחור "קוד סיבת סיום" (לא 00/03).';
            }
        }
        // 01/04, 01/49 (הברקה/פיילוט) → חובה תאריך (ת.קליטה)
        if (in_array($combo, ['0104','0149'], true)) {
            $requiresDate = true;
        }
        // 02/10, 02/19 (חזרה מאינטרויזה/ועדה הומניטרית) → חובה תאריך (ת.תחילת עבודה)
        if (in_array($combo, ['0210','0219'], true)) {
            $requiresDate = true;
        }
        // "מחליף" 020500/020503/020504/020505 → חובה תאריך
        if (in_array($rt.$mt.$sc, ['020500','020503','020504','020505'], true)) {
            $requiresDate = true;
        }

        if ($requiresDate) {
            if ($sd === '') {
                $errors[] = 'יש להזין "תאריך שינוי סטטוס".';
            } else {
                $d = date_create_from_format('Y-m-d', $sd);
                if (!$d || $d->format('Y-m-d') !== $sd) {
                    $errors[] = 'פורמט "תאריך שינוי סטטוס" אינו תקין (YYYY-MM-DD).';
                }
            }
        }

        return $errors;
    }

    /** הרצה/הורדה + שמירה אופציונלית: POST index.php?r=exports/run */
    public static function run(PDO $pdo): void
    {
        require_login();

        $employee_id  = (int)($_POST['employee_id'] ?? 0);
        $save_history = (int)($_POST['save_history'] ?? 0); // 0/1
        $file_type     = substr(trim((string)($_POST['file_type'] ?? '50')), 0, 2);
        $bureau_number = trim((string)($_POST['bureau_number'] ?? ''));
        $sector_code   = substr(trim((string)($_POST['sector_code'] ?? '09')), 0, 2);

        if ($employee_id <= 0) {
            http_response_code(400);
            echo "Employee id is required";
            return;
        }

        // אם לא סופק מס' לשכה בטופס – ננסה לקרוא מ-agency_settings.id=1
        if ($bureau_number === '') {
            $row = self::fetchRow($pdo, "SELECT bureau_number FROM agency_settings WHERE id=1");
            if ($row && !empty($row['bureau_number'])) {
                $bureau_number = (string)$row['bureau_number'];
            }
        }

        // פרמטרים (מותאם לשדות שבטופס שלך)
        $record_type = self::twoDigits($_POST['record_type'] ?? '04');
        $mana_type   = self::twoDigits($_POST['mana_type']   ?? '01');
        $status_code = self::twoDigits($_POST['status_code'] ?? '00');
        $status_date = self::yyyymmdd($_POST['status_date']  ?? date('Ymd')); // YYYYMMDD

        // 2) DATA line
        $data_line = self::fetchScalar($pdo, "CALL sp_bafi_file_for_employee(:eid,:rt,:mt,:sc,:sd)", [
            ':eid' => $employee_id, ':rt' => $record_type, ':mt' => $mana_type, ':sc' => $status_code, ':sd' => $status_date,
        ]);
        //self::consumeNextResult($pdo);
        if (!$data_line) {
            http_response_code(500);
            echo "No BAFI data returned for employee #{$employee_id}";
            return;
        }

        // התאמה למבנה החדש: לוודא שאורך DATA הוא 705 בדיוק (כולל רווחים)
        $raw_line = rtrim((string)$data_line, "\r\n");
        $raw_len  = mb_strlen($raw_line, 'UTF-8');

        if ($raw_len > self::BAFI_LINE_LEN) {
            // אם מתקבלת שורה ארוכה מ-705 (למשל 718) – כנראה ה-SP/מיפוי לא עודכן (כולל שדה E_MAIL)
            http_response_code(500);
            echo "BAFI data line length is {$raw_len}, expected " . self::BAFI_LINE_LEN . ". "
               . "יש לעדכן את ה-SP/מיפוי שדות לפי המבנה החדש (כולל שדה E_MAIL) ולאחר מכן לנסות שוב.";
            return;
        } elseif ($raw_len < self::BAFI_LINE_LEN) {
            // נרפד ברווחים למקרה של חיסור תווים (לא אמור לקרות אם ה-SP תקין)
            $data_line = str_pad($raw_line, self::BAFI_LINE_LEN, ' ');
        } else {
            $data_line = $raw_line;
        }

        // ולידציה עסקית מול טבלת (-4/-5/-18) לפי המסמך
        $vErrors = self::validateBafiInputs([
            'record_type' => $record_type,
            'mana_type'   => $mana_type,
            'status_code' => $status_code,
            // משתמשים בערך המקורי מהטופס (Y-m-d) כדי לבדוק פורמט אנושי
            'status_date' => (string)($_POST['status_date'] ?? ''),
        ]);
        if (!empty($vErrors)) {
            flash('ולידציה נכשלה: ' . implode(' | ', $vErrors), 'danger');
            header('Location: ?r=exports/bafi&employee_id='.(int)$employee_id);
            return;
        }

        // 1) שם קובץ בצד PHP
        $filename = self::buildBafiFilename($pdo, $employee_id, $bureau_number);

        // 2) DATA line (705) בצד PHP
        $data_line = self::buildBafiDataLine705($pdo, [
            'employee_id'   => $employee_id,
            'record_type'   => $record_type,
            'mana_type'     => $mana_type,
            'status_code'   => $status_code,
            'status_date'   => $status_date,  // YYYYMMDD
            'file_type'     => $file_type ?: '50',
            'bureau_number' => $bureau_number, // אם ריק – יילקח אוטומטית מ־agency_settings
            'sector_code'   => $sector_code ?: '09',
        ]);

        if (!$data_line) {
            http_response_code(500);
            echo "No BAFI data returned for employee #{$employee_id}";
            return;
        }
        $len = strlen($data_line);
        if ($len !== 705) {
            http_response_code(500);
            echo "BAFI data line length is {$len}, expected 705. יש לעדכן את המיפוי/נתונים ולהריץ שוב.";
            return;
        }

        // 3) CONTROL line (705) בצד PHP
        $control_line = self::buildBafiControlLine705($pdo, 1, $file_type, $bureau_number, $sector_code);

        // איחוי התוכן עם CRLF
        $content = rtrim((string)$data_line, "\r\n") . "\r\n" . rtrim((string)$control_line, "\r\n") . "\r\n";


        /*
        // 1) שם קובץ מה־SP; fallback אם לא חוזר
        $filename = self::fetchScalar($pdo, "CALL sp_bafi_filename_for_employee(:eid)", [':eid' => $employee_id]);
        //self::consumeNextResult($pdo);
        if (!$filename) {
            $filename = "bafi_{$employee_id}.txt";
        }

        // 2) DATA line
        $data_line = self::fetchScalar($pdo, "CALL sp_bafi_file_for_employee(:eid,:rt,:mt,:sc,:sd)", [
            ':eid' => $employee_id, ':rt' => $record_type, ':mt' => $mana_type, ':sc' => $status_code, ':sd' => $status_date,
        ]);
        //self::consumeNextResult($pdo);
        if (!$data_line) {
            http_response_code(500);
            echo "No BAFI data returned for employee #{$employee_id}";
            return;
        }

        // 3) CONTROL line
        $control_line = self::fetchScalar($pdo, "CALL sp_bafi_control_record(:total)", [':total' => 1]);
        //self::consumeNextResult($pdo);

        $content = rtrim((string)$data_line, "\r\n") . "\r\n" . rtrim((string)$control_line, "\r\n") . "\r\n";
*/        
        $rows_count = 2;

        // 4) שיוכים (אחרון/פעיל) לנוחות בהיסטוריה
        $placement = self::fetchRow($pdo, "
            SELECT id AS placement_id, employer_id
            FROM placements
            WHERE employee_id = :eid
            ORDER BY (end_date IS NULL) DESC, COALESCE(end_date,'9999-12-31') DESC, id DESC
            LIMIT 1
        ", [':eid' => $employee_id]);
        $placement_id = $placement['placement_id'] ?? null;
        $employer_id  = $placement['employer_id'] ?? null;

        $job_id = null;
        $file_id = null;

        try {
            if ($save_history) {
                $pdo->beginTransaction();

                // job
                $stmt = $pdo->prepare("INSERT INTO export_jobs (export_type, requested_by, status) VALUES ('BAFI', :uid, 'running')");
                $stmt->execute([':uid' => ($_SESSION['user']['id'] ?? null)]);
                $job_id = (int)$pdo->lastInsertId();

                // כתיבה לדיסק לאפשר הורדות חוזרות
                $dir = __DIR__ . '/../../exports/' . date('Y/m') . "/job_{$job_id}";
                if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
                    throw new RuntimeException("Cannot create export dir: {$dir}");
                }
                $file_path = $dir . '/' . $filename;
                if (file_put_contents($file_path, $content) === false) {
                    throw new RuntimeException("Cannot write export file");
                }
                $sha256 = hash_file('sha256', $file_path);

                // רישום קובץ
                $stmt = $pdo->prepare("
                    INSERT INTO export_files
                      (export_job_id, employee_id, employer_id, placement_id, filename, file_path, rows_count, sha256)
                    VALUES
                      (:job,:emp,:er,:pl,:fn,:fp,:rows,:sha)
                ");
                $stmt->execute([
                    ':job'  => $job_id,
                    ':emp'  => $employee_id,
                    ':er'   => $employer_id,
                    ':pl'   => $placement_id,
                    ':fn'   => $filename,
                    ':fp'   => $file_path,
                    ':rows' => $rows_count,
                    ':sha'  => $sha256,
                ]);
                $file_id = (int)$pdo->lastInsertId();

                // סוגרים job
                $pdo->prepare("UPDATE export_jobs SET status='done' WHERE id=:id")->execute([':id' => $job_id]);
                $pdo->commit();
            }

            // הורדה לדפדפן (תמיד)
            header('Content-Type: text/plain; charset=UTF-8');
            header('Content-Length: ' . strlen($content));
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $content;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            if ($job_id) {
                $pdo->prepare("UPDATE export_jobs SET status='failed', notes=:n WHERE id=:id")
                    ->execute([':n' => $e->getMessage(), ':id' => $job_id]);
            }
            http_response_code(500);
            echo "Export failed";
        }
    }

    /** היסטוריה: GET index.php?r=exports/history */
    public static function history(PDO $pdo): void
    {
        require_login();
        $rows = $pdo->query("
            SELECT f.id AS file_id, f.*, j.status, j.export_type
            FROM export_files f
            JOIN export_jobs j ON j.id = f.export_job_id
            ORDER BY f.created_at DESC
            LIMIT 500
        ")->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../../views/exports/history.php';
    }

    /** הורדה חוזרת: GET index.php?r=exports/download&id=### */
    public static function download(PDO $pdo): void
    {
        require_login();
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { http_response_code(404); echo "Not found"; return; }

        $row = self::fetchRow($pdo, "SELECT filename, file_path FROM export_files WHERE id=:id", [':id' => $id]);
        if (!$row || empty($row['file_path']) || !is_file($row['file_path'])) {
            http_response_code(404); echo "File not available"; return;
        }

        $filename = $row['filename'] ?: basename($row['file_path']);
        $content  = file_get_contents($row['file_path']);

        header('Content-Type: text/plain; charset=UTF-8');
        header('Content-Length: ' . strlen($content));
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $content;
    }

    /* ================= עזרות ================= */

    private static function twoDigits(string $val): string
    {
        $val = preg_replace('/\D+/', '', $val) ?? '0';
        return str_pad(substr($val, 0, 2), 2, '0', STR_PAD_LEFT);
    }
    private static function yyyymmdd(string $val): string
    {
        $val = preg_replace('/\D+/', '', $val) ?? '';
        return str_pad(substr($val, 0, 8), 8, '0', STR_PAD_RIGHT);
    }

    private static function fetchScalar(PDO $pdo, string $sql, array $bind = [])
    {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bind);
        $val = $stmt->fetchColumn();
        if (method_exists($stmt, 'nextRowset')) {
            while ($stmt->nextRowset()) { /* flush extra results from CALL */ }
        }
        $stmt->closeCursor();
        return $val;
    }

    private static function fetchRow(PDO $pdo, string $sql, array $bind = [])
    {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bind);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (method_exists($stmt, 'nextRowset')) {
            while ($stmt->nextRowset()) { /* flush extra results from CALL */ }
        }
        $stmt->closeCursor();
        return $row;
    }

    /* ===== עזרי עיצוב שדות ל־BAFI ===== */
    private static function strL(?string $val, int $len): string
    {
        // לועזי: גזירה לשמאל וריפוד לימין
        $s = (string)($val ?? '');
        $s = mb_substr($s, 0, $len, 'UTF-8');
        return str_pad($s, $len, ' ', STR_PAD_RIGHT);
    }
    private static function strR(?string $val, int $len): string
    {
        // עברית: גזירה וריפוד משמאל (יישור לימין)
        $s = (string)($val ?? '');
        $s = mb_substr($s, 0, $len, 'UTF-8');
        return str_pad($s, $len, ' ', STR_PAD_LEFT);
    }
    private static function numPad($val, int $len): string
    {
        // ספרות בלבד, ריפוד באפסים משמאל
        $s = preg_replace('/\D+/', '', (string)($val ?? '')) ?? '';
        $s = substr($s, 0, $len);
        return str_pad($s, $len, '0', STR_PAD_LEFT);
    }
    private static function digitsOnly($val): string
    {
        return preg_replace('/\D+/', '', (string)($val ?? '')) ?? '';
    }

    /** שם קובץ: oz_siud_manot_<bureau>_<employee_id>.txt */
    private static function buildBafiFilename(PDO $pdo, int $employeeId, ?string $bureauNumber = null): string
    {
        $bureau = trim((string)($bureauNumber ?? ''));
        if ($bureau === '') {
            $row = self::fetchRow($pdo, "SELECT bureau_number FROM agency_settings WHERE id=1");
            $bureau = (string)($row['bureau_number'] ?? '');
        }
        if ($bureau === '') { $bureau = '0000000000'; }
        return 'oz_siud_manot_' . $bureau . '_' . $employeeId . '.txt';
    }

    /** DATA line 705 — מחזיר null אם אין שיבוץ לעובד */
    private static function buildBafiDataLine705(PDO $pdo, array $p): ?string
    {
        $eid = (int)($p['employee_id'] ?? 0);
        if ($eid <= 0) { return null; }

        $fileType = substr((string)($p['file_type'] ?? '50'), 0, 2);
        $sector   = substr((string)($p['sector_code'] ?? '09'), 0, 2);
        $bureau   = (string)($p['bureau_number'] ?? '');
        if ($bureau === '') {
            $row = self::fetchRow($pdo, "SELECT bureau_number FROM agency_settings WHERE id=1");
            $bureau = (string)($row['bureau_number'] ?? '');
        }

        $rt = substr((string)($p['record_type'] ?? '04'), 0, 2);
        $mt = substr((string)($p['mana_type']   ?? '01'), 0, 2);
        $sc = substr((string)($p['status_code'] ?? '00'), 0, 2);
        $sd = substr((string)($p['status_date'] ?? ''), 0, 8); // YYYYMMDD

        // בחירת שיבוץ: פעיל אם קיים, אחרת אחרון
        $row = self::fetchRow($pdo, "
            SELECT *
            FROM (
                SELECT p.*,
                    (p.start_date <= CURDATE() AND (p.end_date IS NULL OR p.end_date >= CURDATE())) AS is_active
                FROM placements p
                WHERE p.employee_id = :eid
            ) t
            ORDER BY t.is_active DESC, COALESCE(t.end_date,'9999-12-31') DESC, t.id DESC
            LIMIT 1
        ", [':eid' => $eid]);
        if (!$row) { return null; }

        $employerId = (int)$row['employer_id'];
        $e = self::fetchRow($pdo, "SELECT * FROM employees WHERE id = :id", [':id' => $eid]);
        $r = self::fetchRow($pdo, "SELECT * FROM employers WHERE id = :id", [':id' => $employerId]);
        if (!$e || !$r) { return null; }

        // ===== בניית השורה לפי המבנה המעודכן (705), כולל E_MAIL =====
        $line = '';
        $line .= self::numPad($fileType, 2);                       // 01-02
        $line .= self::numPad($bureau, 10);                        // 03-12
        $line .= self::numPad($sector, 2);                         // 13-14
        $line .= self::numPad($rt, 2);                             // 15-16
        $line .= self::numPad($mt, 2);                             // 17-18

        $country = $e['country_symbol_moi'] ?? $e['country_of_citizenship'] ?? '';
        $line .= self::numPad($country, 3);                        // 19-21
        $line .= self::strL($e['passport_number'] ?? '', 15);      // 22-36
        $line .= self::strL($e['last_name'] ?? '', 20);            // 37-56 (EN)
        $line .= self::strL($e['first_name'] ?? '', 20);           // 57-76 (EN)
        $line .= self::strL($e['father_name_en'] ?? '', 20);       // 77-96 (EN)
        $line .= self::strR($e['last_name_he'] ?? '', 20);         // 97-116 (HE)
        $line .= self::strR($e['first_name_he'] ?? '', 20);        // 117-136 (HE)
        $line .= self::numPad($e['gender_code'] ?? '', 1);         // 137
        $line .= self::numPad(isset($e['birth_date']) && $e['birth_date'] ? date('Ymd', strtotime($e['birth_date'])) : '', 8); // 138-145
        $line .= self::numPad($e['marital_status_code'] ?? '', 1); // 146
        $line .= self::strL($e['phone_prefix_il'] ?? '', 4);       // 147-150
        $line .= self::strL($e['phone_number_il'] ?? '', 9);       // 151-159

        $line .= self::numPad($sc, 2);                             // 160-161
        $line .= self::numPad($sd, 8);                             // 162-169

        $line .= self::numPad($r['id_type_code'] ?? '', 1);        // 170
        $line .= self::numPad($r['id_number'] ?? '', 9);           // 171-179
        $line .= self::strL($r['passport_number'] ?? '', 15);      // 180-194
        $line .= self::strL($r['last_name'] ?? '', 20);            // 195-214
        $line .= self::strL($r['first_name'] ?? '', 20);           // 215-234
        $line .= self::numPad($r['gender_code'] ?? '', 1);         // 235
        $line .= self::numPad($r['birth_year'] ?? '', 4);          // 236-239

        $line .= self::numPad($r['city_code'] ?? '', 4);           // 240-243
        $line .= self::strL($r['city_name_he'] ?? '', 30);         // 244-273
        $line .= self::strL($r['street_name_he'] ?? '', 20);       // 274-293
        $line .= self::strL($r['house_no'] ?? '', 6);              // 294-299
        $line .= self::numPad($r['zipcode'] ?? '', 5);             // 300-304
        $line .= self::strL(($r['phone_number_il'] ?? '') ?: self::digitsOnly($r['phone'] ?? ''), 10); // 305-314

        // E_MAIL
        $line .= self::strL($e['email'] ?? '', 40);                // 315-354

        // כתובת עובד בחו״ל
        $line .= self::strL($e['abroad_city'] ?? '', 30);          // 355-384
        $line .= self::strL($e['abroad_street'] ?? '', 30);        // 385-414
        $line .= self::strL($e['abroad_house_no'] ?? '', 6);       // 415-420
        $line .= self::numPad($e['abroad_postal_code'] ?? '', 10); // 421-430

        // פרטי בנק בחו״ל
        $line .= self::numPad($e['bank_foreign_country_code'] ?? '', 3); // 431-433
        $line .= self::strL($e['bank_city_foreign'] ?? '', 30);          // 434-463
        $line .= self::strL($e['bank_street_foreign'] ?? '', 30);        // 464-493
        $line .= self::strL($e['bank_house_no_foreign'] ?? '', 6);       // 494-499
        $line .= self::strL($e['bank_code_foreign'] ?? '', 10);          // 500-509
        $line .= self::strL($e['bank_name_foreign'] ?? '', 30);          // 510-539
        $line .= self::strL($e['bank_branch_code_foreign'] ?? '', 10);   // 540-549
        $line .= self::strL($e['bank_branch_name_foreign'] ?? '', 30);   // 550-579
        $line .= self::strL($e['bank_swift'] ?? '', 25);                 // 580-604
        $line .= self::strL($e['bank_iban'] ?? '', 40);                  // 605-644

        // מוטב
        $line .= self::strL($e['beneficiary_last_name'] ?? '', 30);      // 645-674
        $line .= self::strL($e['beneficiary_first_name'] ?? '', 30);     // 675-704

        // חיתוך/ריפוד סופי ל-705
        $line = substr($line, 0, 705);
        if (strlen($line) < 705) { $line = str_pad($line, 705, ' '); }

        return $line;
    }

    /** CONTROL line 705 */
    private static function buildBafiControlLine705(PDO $pdo, int $totalRows, string $fileType = '50', ?string $bureauNumber = null, string $sectorCode = '09'): string
    {
        $bureau = trim((string)($bureauNumber ?? ''));
        if ($bureau === '') {
            $row = self::fetchRow($pdo, "SELECT bureau_number FROM agency_settings WHERE id=1");
            $bureau = (string)($row['bureau_number'] ?? '');
        }
        $line  = self::numPad($fileType, 2);        // 01-02
        $line .= self::numPad($bureau, 10);         // 03-12
        $line .= self::numPad($sectorCode, 2);      // 13-14
        $line .= self::numPad('99', 2);             // 15-16
        $line .= self::numPad((string)$totalRows, 4);// 17-20
        return str_pad(substr($line, 0, 705), 705, ' '); // 21-705 ריפוד
    }

    public static function piba(PDO $pdo)
    {
        require_login();
        // טופס בחירת עובד (בדומה ל-bafi_form)
        $employees = $pdo->query("SELECT id, passport_number, last_name_he, first_name_he FROM employees ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
        $selected_employee_id = $_SESSION['old']['employee_id'] ?? null;
        unset($_SESSION['old']);
        require __DIR__ . '/../../views/exports/piba_form.php';
    }

    public static function piba_export(PDO $pdo)
    {
        require_login();
        $employee_id = (int)($_POST['employee_id'] ?? 0);
        if ($employee_id <= 0) {
            flash('יש לבחור עובד.', 'danger');
            redirect('exports/piba'); // redirect() אצלך עושה exit;

            return;
        }

        try {
            require_once __DIR__ . '/../services/PibaXmlBuilder.php';
            $builder = new PibaXmlBuilder($pdo);
            $res = $builder->buildForEmployee($employee_id);

            // כתיבה להיסטוריה (אם יש לך טבלה מתאימה – התאם לשמה)
            if ($pdo->query("SHOW TABLES LIKE 'exports_jobs'")->rowCount()) {
                $st = $pdo->prepare("INSERT INTO exports_jobs(export_type, employee_id, file_name, created_at) VALUES('PIBA', :eid, :fn, NOW())");
                $st->execute([':eid'=>$employee_id, ':fn'=>$res['filename']]);
            }

            // הורדה
            header('Content-Type: application/xml; charset=UTF-8');
            header('Content-Disposition: attachment; filename="'.$res['filename'].'"');
            echo $res['xml'];
        } catch (Throwable $e) {
            error_log('PIBA export error: '.$e->getMessage());
            flash('ייצור הקובץ PIBA נכשל: ' . $e->getMessage(), 'danger');
            $_SESSION['old'] = ['employee_id' => $employee_id];
            redirect('exports/piba'); // יוצא מיד
        }
    }


}
