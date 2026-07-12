<?php
declare(strict_types=1);

/**
 * PIBA JSON (Permit Extension) builder.
 *
 * Generates a JSON file according to: "אפיון קובץ טעינת JSON - הארכת רישיון לעובד זר בסיעוד".
 *
 * Notes:
 * - dataText fields must be sent empty (per spec).
 * - This builder resolves the current employer from the latest active placement.
 * - Database is used only for data retrieval.
 */
class PibaPermitJsonBuilder
{
    public function __construct(private PDO $pdo) {}

    /**
     * Builds the JSON payload and returns ['filename' => string, 'json' => string].
     */
    public function buildForEmployee(int $employee_id): array
    {
        $built = $this->buildPayload($employee_id);
        if (!empty($built['missing'])) {
            throw new RuntimeException('חסרים/לא תקינים שדות חובה: ' . implode(', ', $built['missing']));
        }

        return ['filename' => $built['filename'], 'json' => $built['json']];
    }

    /**
     * Preview endpoint helper.
     * Returns: ['filename'=>string,'payload'=>array,'json'=>string,'missing'=>array]
     */
    public function previewForEmployee(int $employee_id): array
    {
        return $this->buildPayload($employee_id);
    }

    /**
     * Builds payload + pretty JSON and returns also missing fields without throwing.
     */
    private function buildPayload(int $employee_id): array
    {
        $emp = $this->fetchEmployee($employee_id);
        if (!$emp) {
            throw new RuntimeException("Employee not found: {$employee_id}");
        }

        $agency = $this->fetchAgencySettings();
        if (!$agency) {
            throw new RuntimeException('Missing agency_settings (row id=1)');
        }

        $employer = $this->fetchEmployerForEmployee($employee_id);
        if (!$employer) {
            throw new RuntimeException('No active employer placement found for employee');
        }

        // Passport data (prefer passports module; fallback to employees.*)
        $pp = $this->fetchPrimaryPassport($employee_id);
        $nowPassport = trim((string)($pp['passport_number'] ?? $emp['passport_number'] ?? ''));
        $passportValid = $this->fmt_dmy($pp['expiry_date'] ?? $emp['passport_expiry_date'] ?? null);
        $oldPassport = '';
        if ($nowPassport !== '') {
            $prev = $this->fetchPreviousPassport($employee_id, $nowPassport);
            $oldPassport = trim((string)($prev['passport_number'] ?? ''));
        }

        // Codes
        $citizenshipCode = (string)($emp['country_of_citizenship'] ?? '');
        $maritalCode = $this->mapMaritalStatusCode($emp['marital_status_code'] ?? null);
        $inIsraelCode = $this->mapYesNoCode($emp['spouse_in_israel'] ?? null);

        // Phones
        $empPhoneMain = $this->fmt_phone_il($this->bestPhone($emp));
        $empPhoneAlt  = $this->fmt_phone_il((string)($emp['phone_alt'] ?? ''));
        $employerPhone = $this->fmt_phone_il($this->bestPhone($employer));

        // Address codes
        $employerCityCode = (string)($employer['city_code'] ?? '');
        $employerStreetCode = (string)($employer['street_code'] ?? '');

        // Build payload (dataText must be empty strings)
        $payload = [
            'employerDetails' => [
                'corporateEmail' => (string)($agency['email'] ?? ''),
                'employerFirstName' => (string)($employer['first_name'] ?? ''),
                'employerLastName' => (string)($employer['last_name'] ?? ''),
                'employerId' => (string)($employer['id_number'] ?? ''),
                'city' => [
                    'dataCode' => $employerCityCode,
                    'dataText' => '',
                ],
                'street' => [
                    'dataCode' => $employerStreetCode,
                    'dataText' => '',
                ],
                'homeNum' => (string)($employer['house_no'] ?? ''),
                'entryNumber' => '',
                'appartmentNum' => (string)($employer['apartment'] ?? ''),
                'zipCode' => (string)($employer['zipcode'] ?? ''),
                'employerPhone' => $employerPhone,
            ],
            'foreignWorkerDetails' => [
                'lastName' => (string)($emp['last_name'] ?? ''),
                'firstName' => (string)($emp['first_name'] ?? ''),
                'dateOfBirth' => $this->fmt_dmy($emp['birth_date'] ?? null),
                'nowPassport' => $nowPassport,
                'citizenship' => [
                    'dataCode' => $citizenshipCode,
                    'dataText' => '',
                ],
                'vadlidDate' => $passportValid,
                'oldPassport' => $oldPassport,
                'dateFirstTime' => $this->fmt_dmy($emp['entry_date'] ?? null),
                'personalStatus' => [
                    'dataCode' => $maritalCode,
                    'dataText' => '',
                ],
                'partnerDetails' => (string)($emp['spouse_name_en'] ?? ''),
                'inIsrael' => [
                    'dataCode' => $inIsraelCode,
                    'dataText' => '',
                ],
                'motherFirstName' => (string)($emp['mother_name_en'] ?? ''),
                'fatherFirstName' => (string)($emp['father_name_en'] ?? ''),
                'phoneNumber' => $empPhoneMain,
                'otherPhone' => $empPhoneAlt,
            ],
        ];

        $missing = $this->validate($payload);

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new RuntimeException('Failed to encode JSON: ' . json_last_error_msg());
        }

        $now = new DateTime('now');
        $fn = sprintf(
            'PIBA_PERMIT_%s_%s_%s.json',
            preg_replace('/\W+/', '', $nowPassport ?: (string)$employee_id),
            $now->format('Ymd'),
            $now->format('Hi')
        );

        return ['filename' => $fn, 'payload' => $payload, 'json' => $json, 'missing' => $missing];
    }

    /* ---------------------- Data ---------------------- */

    private function fetchAgencySettings(): ?array
    {
        $st = $this->pdo->query('SELECT * FROM agency_settings ORDER BY id ASC LIMIT 1');
        return $st ? ($st->fetch(PDO::FETCH_ASSOC) ?: null) : null;
    }

    private function fetchEmployee(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM employees WHERE id = :id');
        $st->execute([':id' => $id]);
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
        $st->execute([':eid' => $employee_id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function fetchPrimaryPassport(int $employee_id): ?array
    {
        // 1) view (if exists)
        try {
            // NOTE: In the provided DB dump the view definition references a legacy column name
            // (issuing_country_code) that does not exist in employee_passports.
            // We do NOT need issuing country for the PIBA JSON spec, so we avoid selecting it.
            $st = $this->pdo->prepare("SELECT passport_number, issue_date, expiry_date
                                       FROM v_employee_primary_passport
                                       WHERE primary_employee_id = :eid
                                       LIMIT 1");
            $st->execute([':eid' => $employee_id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row) return $row;
        } catch (Throwable $e) {
            // ignore, fallback
        }

        // 2) passports table is_primary=1
        // Table column is country_code (not issuing_country_code). We don't need it here.
        $st = $this->pdo->prepare("SELECT passport_number, issue_date, expiry_date
                                   FROM employee_passports
                                   WHERE employee_id = :eid AND is_primary = 1
                                   ORDER BY updated_at DESC, created_at DESC
                                   LIMIT 1");
        $st->execute([':eid' => $employee_id]);
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
        $st->execute([':eid' => $employee_id, ':pn' => $excludePassport]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /* ---------------------- Validation + Helpers ---------------------- */

    /**
     * Validations based on the spec's "שדה חובה" notes.
     */
    private function validate(array $p): array
    {
        $missing = [];

        // Employer tab
        if (trim((string)($p['employerDetails']['corporateEmail'] ?? '')) === '') {
            $missing[] = 'corporateEmail (agency_settings.email)';
        }
        if (trim((string)($p['employerDetails']['employerFirstName'] ?? '')) === '') {
            $missing[] = 'employerFirstName (employers.first_name)';
        }
        if (trim((string)($p['employerDetails']['employerLastName'] ?? '')) === '') {
            $missing[] = 'employerLastName (employers.last_name)';
        }
        $eid = (string)($p['employerDetails']['employerId'] ?? '');
        if ($eid === '' || !preg_match('/^\d{9}$/', $eid)) {
            $missing[] = 'employerId (9 digits string)';
        }
        if (trim((string)($p['employerDetails']['city']['dataCode'] ?? '')) === '') {
            $missing[] = 'city.dataCode (employers.city_code)';
        }
        if (trim((string)($p['employerDetails']['street']['dataCode'] ?? '')) === '') {
            $missing[] = 'street.dataCode (employers.street_code)';
        }
        if (trim((string)($p['employerDetails']['homeNum'] ?? '')) === '') {
            $missing[] = 'homeNum (employers.house_no)';
        }

        // Foreign worker tab (practical minimum required to submit)
        if (trim((string)($p['foreignWorkerDetails']['lastName'] ?? '')) === '') {
            $missing[] = 'foreignWorkerDetails.lastName (employees.last_name)';
        }
        if (trim((string)($p['foreignWorkerDetails']['firstName'] ?? '')) === '') {
            $missing[] = 'foreignWorkerDetails.firstName (employees.first_name)';
        }
        if (trim((string)($p['foreignWorkerDetails']['dateOfBirth'] ?? '')) === '') {
            $missing[] = 'foreignWorkerDetails.dateOfBirth (employees.birth_date)';
        }
        if (trim((string)($p['foreignWorkerDetails']['nowPassport'] ?? '')) === '') {
            $missing[] = 'foreignWorkerDetails.nowPassport (primary passport)';
        }
        if (trim((string)($p['foreignWorkerDetails']['citizenship']['dataCode'] ?? '')) === '') {
            $missing[] = 'citizenship.dataCode (employees.country_of_citizenship)';
        }
        if (trim((string)($p['foreignWorkerDetails']['vadlidDate'] ?? '')) === '') {
            $missing[] = 'vadlidDate (passport expiry date)';
        }
        if (trim((string)($p['foreignWorkerDetails']['dateFirstTime'] ?? '')) === '') {
            $missing[] = 'dateFirstTime (employees.entry_date)';
        }

        return $missing;
    }

    private function fmt_dmy(?string $ymd): string
    {
        if (!$ymd) return '';
        if (preg_match('/^\d{8}$/', (string)$ymd)) {
            $y = substr($ymd, 0, 4);
            $m = substr($ymd, 4, 2);
            $d = substr($ymd, 6, 2);
            return "$d/$m/$y";
        }
        $d = date_create((string)$ymd);
        return $d ? $d->format('d/m/Y') : '';
    }

    private function bestPhone(array $row): string
    {
        // Try direct phone
        if (!empty($row['phone'])) return preg_replace('/\D+/', '', (string)$row['phone']);

        // Try prefix + number
        $p = preg_replace('/\D+/', '', (string)($row['phone_prefix_il'] ?? ''));
        $n = preg_replace('/\D+/', '', (string)($row['phone_number_il'] ?? ''));
        return trim($p . $n);
    }

    /**
     * Formats an IL mobile/phone to: 0XX-XXXXXXX when possible.
     */
    private function fmt_phone_il(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw);
        if ($digits === '') return '';

        // Common case: 10 digits (05XYYYYYYY / 0XYYYYYYYY)
        if (strlen($digits) === 10) {
            return substr($digits, 0, 3) . '-' . substr($digits, 3);
        }
        // Sometimes 9 digits (without leading 0)
        if (strlen($digits) === 9) {
            return '0' . substr($digits, 0, 2) . '-' . substr($digits, 2);
        }

        return $digits;
    }

    /**
     * Spec: 1=רווק, 2=נשוי, 3=גרוש, 4=אלמן. Otherwise empty.
     */
    private function mapMaritalStatusCode(mixed $code): string
    {
        if ($code === null || $code === '') return '';
        $c = (string)$code;
        return in_array($c, ['1', '2', '3', '4'], true) ? $c : '';
    }

    /**
     * Spec: 1=yes, 2=no.
     */
    private function mapYesNoCode(mixed $flag): string
    {
        if ($flag === null || $flag === '') return '';
        return ((int)$flag) === 1 ? '1' : '2';
    }
}
