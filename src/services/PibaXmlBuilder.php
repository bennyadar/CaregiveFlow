<?php
declare(strict_types=1);

class PibaXmlBuilder
{
    public function __construct(private PDO $pdo) {}

    /** יוצר XML PIBA לעובד ומחזיר ['filename'=>..., 'xml'=>string] */
    public function buildForEmployee(int $employee_id): array
    {
        /* --- שליפות נתונים --- */
        $emp = $this->fetchEmployee($employee_id);
        if (!$emp) throw new RuntimeException("Employee not found: $employee_id");

        $agency   = $this->fetchAgencySettings();
        if (!$agency) throw new RuntimeException('Missing agency_settings (row id=1)');

        $employer = $this->fetchEmployerForEmployee($employee_id); // ייתכן שאין

        // דרכון ראשי/קודם לפי מודול הדרכונים
        $pp             = $this->fetchPrimaryPassport($employee_id);
        $nowPassport    = trim((string)($pp['passport_number'] ?? ''));
        $passport_valid = $this->fmt_dmy($pp['expiry_date'] ?? null);
        $prev           = $this->fetchPreviousPassport($employee_id, $nowPassport);
        $oldPassport    = trim((string)($prev['passport_number'] ?? ''));

        // קודי רשות
        $cit   = $this->codeCountry((string)($emp['country_of_citizenship'] ?? '')); // {code,text}
        $city  = $this->codeCity((string)($emp['city_code'] ?? ''));                 // {code,text}
        $street= $this->codeStreet((string)($emp['city_code'] ?? ''), (string)($emp['street_code'] ?? '')); // {code,text} או null

        // תאריכים ושדות נוספים
        $birth      = $this->fmt_dmy($emp['birth_date'] ?? null);       // DateOfBirth
        $entryDate  = $this->fmt_dmy($emp['entry_date'] ?? null);       // dateFirstime (אופציונלי)
        $marStatus  = $this->codeMarital((string)($emp['marital_status_code'] ?? '')); // {code,text} או null
        $partner    = (string)($emp['spouse_name_en'] ?? '');
        $inIsrael   = (string)($emp['spouse_in_israel'] ?? '0');

        // טלפונים (one-of)
        $phoneMain  = $this->bestPhone($emp);                           // Phone1
        $phoneOther = (string)($emp['phone_alt'] ?? '');                // OtherPhone

        /* --- בדיקות חובה + one-of --- */
        $missing = [];

        // לשכה – חובה קשיחה
        if (trim((string)($agency['agency_name'] ?? '')) === '')        $missing[] = 'BureauName (agency_name)';
        if (trim((string)($agency['CorporateNumber'] ?? '')) === '')    $missing[] = 'CorporateNum (CorporateNumber)';

        // עובד – חובה פונקציונלית
        if ($nowPassport === '')                                        $missing[] = 'NowPassport (מספר דרכון ראשי)';
        if (($cit['code'] ?? '') === '')                                $missing[] = 'Citizenship (קוד מדינת אזרחות)';
        if ($birth === '')                                              $missing[] = 'DateOfBirth (תאריך לידה)';
        if (($city['code'] ?? '') === '' || ($city['text'] ?? '') === '') $missing[] = 'town (קוד + טקסט יישוב)';
        if (empty($street) || ($street['code'] ?? '') === '' || ($street['text'] ?? '') === '') $missing[] = 'street (קוד רחוב + טקסט)';
        if (trim((string)($emp['house_no'] ?? '')) === '')              $missing[] = 'homenum (מספר בית)';

        // מעסיק – לפחות מזהה
        if (!$employer || trim((string)($employer['id_number'] ?? '')) === '') $missing[] = 'EmployerId (מזהה מעסיק)';

        // one-of: פרטי קשר לעובד
        if ($phoneMain === '' && $phoneOther === '')                    $missing[] = 'פרטי קשר לעובד: לפחות אחד (Phone1 / OtherPhone)';

        if ($missing) {
            throw new RuntimeException('חסרים שדות חובה: ' . implode(', ', $missing));
        }

        /* --- טעינת תבנית --- */
        $tplPath = __DIR__ . '/../../resources/piba/template.xml';
        if (!is_file($tplPath)) throw new RuntimeException('PIBA template missing at resources/piba/template.xml');

        $dom = new DOMDocument();
        $dom->load($tplPath);
        $xp = new DOMXPath($dom);

        // עזרי הזרקה (מנקים xsi:nil כששמים ערך)
        $setText = function(string $ln, ?string $val) use ($xp, $dom): void {
            $nodes = $xp->query("//*[local-name()='{$ln}']");
            if ($nodes && $nodes->length > 0) {
                $n = $nodes->item(0);
                if ($val !== null && $val !== '' && $n->hasAttribute('xsi:nil')) $n->removeAttribute('xsi:nil');
                while ($n->firstChild) $n->removeChild($n->firstChild);
                $n->appendChild($dom->createTextNode((string)$val));
            }
        };
        $setTextWithAttr = function(string $ln, ?string $val, array $attrs) use ($xp, $dom): void {
            $nodes = $xp->query("//*[local-name()='{$ln}']");
            if ($nodes && $nodes->length > 0) {
                $n = $nodes->item(0);
                if ($val !== null && $val !== '' && $n->hasAttribute('xsi:nil')) $n->removeAttribute('xsi:nil');
                while ($n->firstChild) $n->removeChild($n->firstChild);
                $n->appendChild($dom->createTextNode((string)$val));
                foreach ($attrs as $k => $v) {
                    if ($v === null) continue;
                    $n->setAttribute($k, (string)$v);
                }
            }
        };
        $setNil = function(string $ln) use ($xp): void {
            $nodes = $xp->query("//*[local-name()='{$ln}']");
            if ($nodes && $nodes->length > 0) $nodes->item(0)->setAttribute('xsi:nil', 'true');
        };

        /* --- הזרקות 1:1 לתבנית --- */

        // דרכונים
        $setText('NowPassport',  $nowPassport);
        $setText('ValiDate',     $passport_valid);      // תוקף דרכון (אם חסר – התבנית נשארת עם ערך קיים/ריק)
        $setText('OldPassport',  $oldPassport);

        // אזרחות + "ארץ מוצא" (כרגע משווה לאזרחות)
        $setTextWithAttr('Citizenship',    (string)($cit['code'] ?? ''), ['text' => (string)($cit['text'] ?? '')]);
        $setTextWithAttr('CountrofOrigin', (string)($cit['code'] ?? ''), ['text' => (string)($cit['text'] ?? '')]);

        // פרטי עובד
        $setText('DateOfBirth',  $birth);
        if ($entryDate !== '') $setText('dateFirstime', $entryDate); else $setNil('dateFirstime');

        $setText('LastName',     (string)($emp['last_name'] ?? ''));
        $setText('FirstName',    (string)($emp['first_name'] ?? ''));

        if ($marStatus) $setTextWithAttr('MyStatus', (string)($marStatus['code'] ?? ''), ['text' => (string)($marStatus['text'] ?? '')]);
        if ($partner !== '') $setText('PartnerDetails', $partner); else $setNil('PartnerDetails');
        $setText('inIsrael',     $inIsrael !== '' ? $inIsrael : '0');

        // כתובת בישראל
        $setTextWithAttr('town',   (string)($city['code'] ?? ''),   ['text' => (string)($city['text'] ?? '')]);
        $setTextWithAttr('street', (string)($street['code'] ?? ''), ['text' => (string)($street['text'] ?? '')]);
        $setText('homenum',        (string)($emp['house_no'] ?? ''));

        // טלפונים (one-of)
        if ($phoneMain !== '') $setText('Phone1', $phoneMain); else $setNil('Phone1');
        if ($phoneOther !== '') $setText('OtherPhone', $phoneOther); else $setNil('OtherPhone');

        // כתובת בחו"ל (אופציונלי)
        $setText('AdressAbroad', $this->joinAbroadAddr($emp));

        // מעסיק
        if ($employer) {
            $fullEmployerName = trim(($employer['last_name'] ?? '').' '.($employer['first_name'] ?? ''));
            if ($fullEmployerName !== '') $setText('EmployerName', $fullEmployerName);
            $setText('EmployerId', (string)$employer['id_number']);
            if (!empty($employer['id_type_code'])) $setText('EmployerIdType', (string)$employer['id_type_code']);
        }

        // לשכה / תאגיד — חובה
        $setText('BureauName',   (string)$agency['agency_name']);
        $setText('CorporateNum', (string)$agency['CorporateNumber']);
        if (!empty($agency['email'])) $setText('organizationEmail', (string)$agency['email']);

        // הצהרות HATZHARA1..8 (true) + תאריך בקשה
        foreach (range(1, 8) as $i) $setText("HATZHARA{$i}", 'true');
        $setText('ApplicationDate', (new DateTime('now'))->format('d/m/Y'));
        $setText('DigitalSignature', '');

        // שם קובץ
        $now = new DateTime('now');
        $fn = sprintf('P%s_%s_%s.xml',
            preg_replace('/\W+/', '', $nowPassport),
            $now->format('Ymd'),
            $now->format('Hi')
        );

        return ['filename' => $fn, 'xml' => $dom->saveXML()];
    }

    /* ---------------------- Data + Helpers ---------------------- */

    private function fetchAgencySettings(): ?array
    {
        $st = $this->pdo->query("SELECT * FROM agency_settings ORDER BY id ASC LIMIT 1");
        return $st? $st->fetch(PDO::FETCH_ASSOC) : null; // agency_name, CorporateNumber, bureau_number, email...
    }

    private function fetchEmployee(int $id): ?array
    {
        $sql = "SELECT e.* FROM employees e WHERE e.id = :id";
        $st = $this->pdo->prepare($sql);
        $st->execute([':id'=>$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function fetchEmployerForEmployee(int $employee_id): ?array
    {
        $sql = "SELECT em.*
                FROM placements p
                JOIN employers em ON em.id = p.employer_id
                WHERE p.employee_id = :eid
                  AND (p.end_date IS NULL OR p.end_date >= CURDATE())
                ORDER BY p.start_date DESC
                LIMIT 1";
        $st = $this->pdo->prepare($sql);
        $st->execute([':eid'=>$employee_id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function fetchPrimaryPassport(int $employee_id): ?array
    {
        // 1) view ייעודי
        try {
            $st = $this->pdo->prepare("SELECT passport_number, issuing_country_code, issue_date, expiry_date
                                       FROM v_employee_primary_passport
                                       WHERE primary_employee_id = :eid
                                       LIMIT 1");
            $st->execute([':eid'=>$employee_id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row) return $row;
        } catch (\Throwable $e) {}

        // 2) טבלה is_primary=1
        $st = $this->pdo->prepare("SELECT passport_number, issuing_country_code, issue_date, expiry_date
                                   FROM employee_passports
                                   WHERE employee_id = :eid AND is_primary = 1
                                   ORDER BY updated_at DESC, created_at DESC
                                   LIMIT 1");
        $st->execute([':eid'=>$employee_id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function fetchPreviousPassport(int $employee_id, string $excludePassport): ?array
    {
        $st = $this->pdo->prepare("SELECT passport_number, issue_date, expiry_date
                                   FROM employee_passports
                                   WHERE employee_id = :eid
                                     AND passport_number <> :pn
                                   ORDER BY COALESCE(issue_date, created_at) DESC
                                   LIMIT 1");
        $st->execute([':eid'=>$employee_id, ':pn'=>$excludePassport]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function codeCountry(string $code): array
    {
        if ($code === '') return ['code'=>'','text'=>''];
        $st = $this->pdo->prepare("SELECT country_code AS code, name_he AS text FROM countries WHERE country_code = :c");
        $st->execute([':c'=>$code]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: ['code'=>$code,'text'=>''];
    }

    private function codeCity(string $code): array
    {
        if ($code === '') return ['code'=>'','text'=>''];
        $st = $this->pdo->prepare("SELECT city_code AS code, name_he AS text FROM cities WHERE city_code = :c");
        $st->execute([':c'=>$code]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: ['code'=>$code,'text'=>''];
    }

    private function codeStreet(string $cityCode, string $streetCode): ?array
    {
        if ($cityCode === '' || $streetCode === '') return null;
        $st = $this->pdo->prepare("SELECT street_code AS code, street_name_he AS text
                                   FROM streets
                                   WHERE city_code = :city AND street_code = :sc");
        $st->execute([':city'=>$cityCode, ':sc'=>$streetCode]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function codeMarital(string $code): ?array
    {
        if ($code==='') return null;
        $st = $this->pdo->prepare("SELECT marital_status_code AS code, name_he AS text
                                   FROM marital_status_codes
                                   WHERE marital_status_code=:c");
        $st->execute([':c'=>$code]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: ['code'=>$code,'text'=>''];
    }

    private function fmt_dmy(?string $ymd): string
    {
        if (!$ymd) return '';
        if (preg_match('/^\d{8}$/', (string)$ymd)) {
            $y = substr($ymd,0,4); $m = substr($ymd,4,2); $d = substr($ymd,6,2);
            return "$d/$m/$y";
        }
        $d = date_create((string)$ymd);
        return $d ? $d->format('d/m/Y') : '';
    }

    private function bestPhone(array $emp): string
    {
        if (!empty($emp['phone'])) return preg_replace('/\D+/', '', (string)$emp['phone']);
        $p = preg_replace('/\D+/', '', (string)($emp['phone_prefix_il'] ?? ''));
        $n = preg_replace('/\D+/', '', (string)($emp['phone_number_il'] ?? ''));
        return ltrim($p.$n, '0');
    }

    private function joinAbroadAddr(array $emp): string
    {
        $parts = array_filter([
            $emp['abroad_city'] ?? null,
            $emp['abroad_street'] ?? null,
            $emp['abroad_house_no'] ?? null,
            $emp['abroad_postal_code'] ?? null,
        ], fn($v) => !empty($v));
        return $parts ? implode(', ', $parts) : '';
    }
}
