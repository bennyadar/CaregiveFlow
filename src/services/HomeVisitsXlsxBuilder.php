<?php
declare(strict_types=1);

// CaregiveFlow — Home Visits XLSX (PIBA-style) — SERVICE/BUILDER (no external lib)
// File: src/Services/HomeVisitsXlsxBuilder.php
// -----------------------------------------------------------------
// דפוס זהה ל‑PIBA: ExportsController → *Builder Service* (inject PDO) → מחזיר שם קובץ + תוכן בינארי.
// אין תלות ב‑lib/MinimalXlsxWriter. הבילדר עצמו מייצר XLSX (OOXML) עם ZipArchive.

class HomeVisitsXlsxBuilder
{
    public function __construct(private PDO $pdo) {}

    /**
     * Build Home Visits XLSX by filters and return ['filename'=>..., 'content'=>binary-string]
     */
    public function build(array $filters, int $limit = 50000): array
    {
        [$headers, $rows] = $this->fetchRows($filters, $limit);
        $workbook = $this->makeXlsx('HomeVisits', $headers, $rows);
        $filename = $this->makeFilename();
        return ['filename' => $filename, 'content' => $workbook];
    }

    private function makeFilename(): string
    {
        $now = new DateTime('now');
        return sprintf('home_visits_%s_%s.xlsx', $now->format('Ymd'), $now->format('His'));
    }

    // -----------------------------------------------------------------
    // Data fetching & mapping (same pattern as PIBA: all joins live here)
    private function getHeaders(): array
    {
        return [
            'מזהה מטופל/מעסיק', 'שם משפחה (מטופל)', 'שם פרטי (מטופל)', 'יישוב', 'רחוב', 'טלפון (מטופל/בן משפחה)',
            'שם משפחה (עובד)', 'שם פרטי (עובד)', 'פלאפון (עובד)', 'מספר דרכון', 'ארץ מוצא', 'תאריך הביקור',
            'סוג ביקור', 'שלב', 'סטטוס', 'סוג השמה', 'נדרש מעקב', 'ביקור הבא'
        ];
    }

    private function fetchRows(array $filters, int $limit): array
    {
        $headers = $this->getHeaders();

        $sql = "SELECT
                    hv.id,
                    hv.employee_id,
                    hv.placement_id,
                    hv.visit_date,
                    hv.visit_type_code,
                    hv.status_code,
                    hv.home_visit_stage_code,
                    hv.placement_type_code,
                    hv.followup_required,
                    hv.next_visit_due,

                    e.first_name  AS employee_first_name,
                    e.last_name   AS employee_last_name,
                    e.passport_number,
                    e.country_of_citizenship,
                    e.phone_prefix_il,
                    e.phone_number_il,

                    t.name_he AS type_name,
                    s.name_he AS status_name,
                    g.name_he AS stage_name,
                    pt.name_he AS placement_type_name,

                    p.employer_id,
                    p.patient_id,

                    emp.external_id   AS employer_external_id,
                    emp.city          AS employer_city,
                    emp.street        AS employer_street,
                    emp.phone         AS employer_phone,

                    pat.last_name     AS patient_last_name,
                    pat.first_name    AS patient_first_name,
                    pat.city          AS patient_city,
                    pat.street        AS patient_street,
                    pat.phone         AS patient_phone
                FROM home_visits hv
                JOIN employees e ON e.id = hv.employee_id
                LEFT JOIN home_visit_type_codes  t  ON t.home_visit_type_code  = hv.visit_type_code
                LEFT JOIN home_visit_status_codes s ON s.home_visit_status_code = hv.status_code
                LEFT JOIN home_visit_stage_codes g ON g.home_visit_stage_code  = hv.home_visit_stage_code
                LEFT JOIN placement_type_codes   pt ON pt.placement_type_code  = hv.placement_type_code
                LEFT JOIN placements p ON p.id = hv.placement_id
                LEFT JOIN employers emp ON emp.id = p.employer_id
                LEFT JOIN patients  pat ON pat.id = p.patient_id
                WHERE 1=1";

        $params = [];
        if (!empty($filters['employee_id'])) { $sql .= " AND hv.employee_id = :employee_id"; $params[':employee_id'] = (int)$filters['employee_id']; }
        if (!empty($filters['status_codes']) && is_array($filters['status_codes'])) { $in = implode(',', array_fill(0, count($filters['status_codes']), '?')); $sql .= " AND hv.status_code IN ($in)"; $params = array_merge($params, array_map('intval', $filters['status_codes'])); }
        if (!empty($filters['type_codes']) && is_array($filters['type_codes']))     { $in = implode(',', array_fill(0, count($filters['type_codes']), '?'));   $sql .= " AND hv.visit_type_code IN ($in)"; $params = array_merge($params, array_map('intval', $filters['type_codes'])); }
        if (!empty($filters['stage_codes']) && is_array($filters['stage_codes']))   { $in = implode(',', array_fill(0, count($filters['stage_codes']), '?'));   $sql .= " AND hv.home_visit_stage_code IN ($in)"; $params = array_merge($params, array_map('intval', $filters['stage_codes'])); }
        if (!empty($filters['placement_type_codes']) && is_array($filters['placement_type_codes'])) { $in = implode(',', array_fill(0, count($filters['placement_type_codes']), '?')); $sql .= " AND hv.placement_type_code IN ($in)"; $params = array_merge($params, array_map('intval', $filters['placement_type_codes'])); }
        if (isset($filters['followup_required']) && $filters['followup_required'] !== '') { $sql .= " AND hv.followup_required = ?"; $params[] = (int)$filters['followup_required']; }
        if (!empty($filters['date_from'])) { $sql .= " AND hv.visit_date >= ?"; $params[] = $filters['date_from']; }
        if (!empty($filters['date_to']))   { $sql .= " AND hv.visit_date <= ?"; $params[] = $filters['date_to']; }

        $sql .= " ORDER BY hv.visit_date DESC, hv.id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $i = 1; foreach ($params as $k=>$v) { is_int($k) ? $stmt->bindValue($i++, $v) : $stmt->bindValue($k, $v); }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', 0, PDO::PARAM_INT);
        $stmt->execute();

        $rows = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $employeePhone = trim((string)($r['phone_prefix_il'] ?? '') . (string)($r['phone_number_il'] ?? ''));
            $subjectPhone  = (string)($r['patient_phone'] ?? $r['employer_phone'] ?? '');

            $rows[] = [
                (string)($r['employer_external_id'] ?? $r['patient_id'] ?? ''),
                (string)($r['patient_last_name'] ?? ''),
                (string)($r['patient_first_name'] ?? ''),
                (string)($r['patient_city'] ?? $r['employer_city'] ?? ''),
                (string)($r['patient_street'] ?? $r['employer_street'] ?? ''),
                $subjectPhone,
                (string)($r['employee_last_name'] ?? ''),
                (string)($r['employee_first_name'] ?? ''),
                $employeePhone,
                (string)($r['passport_number'] ?? ''),
                (string)($r['country_of_citizenship'] ?? ''),
                (string)$r['visit_date'],
                (string)($r['type_name'] ?? ''),
                (string)($r['stage_name'] ?? ''),
                (string)($r['status_name'] ?? ''),
                (string)($r['placement_type_name'] ?? ''),
                !empty($r['followup_required']) ? 'כן' : 'לא',
                (string)($r['next_visit_due'] ?? ''),
            ];
        }

        return [$headers, $rows];
    }

    // -----------------------------------------------------------------
    // XLSX generation (OOXML minimal parts) — inline, like PIBA builder owns the output creation
    private function makeXlsx(string $sheetName, array $headers, array $rows): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            if ($zip->open($tmp, ZipArchive::CREATE) !== true) {
                throw new RuntimeException('Cannot create XLSX');
            }
        }
        // Build shared strings table
        [$sstXml, $useSst, $cellWriter] = $this->buildSharedStringsAndCellWriter($headers, $rows);

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml($useSst));
        $zip->addFromString('_rels/.rels', $this->relsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml($sheetName));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheetXml($cellWriter));
        if ($useSst) {
            $zip->addFromString('xl/sharedStrings.xml', $sstXml);
        }
        $zip->close();
        $bin = file_get_contents($tmp);
        @unlink($tmp);
        return $bin === false ? '' : $bin;
    }

    private function contentTypesXml(bool $hasSst): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .($hasSst ? '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>' : '')
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private function relsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbookXml(string $sheetName): string
    {
        $sheetName = htmlspecialchars($sheetName, ENT_QUOTES | ENT_XML1, 'UTF-8');
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$sheetName.'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            .'<borders count="1"><border/></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
            .'</styleSheet>';
    }

    private function buildSharedStringsAndCellWriter(array $headers, array $rows): array
    {
        $sst = [];
        $index = [];
        $useSst = false;

        $register = function($val) use (&$sst, &$index, &$useSst): ?int {
            if ($val === null || $val === '') return null;
            if (is_int($val) || is_float($val)) return null; // numeric → not in sst
            $s = (string)$val;
            $useSst = true;
            if (!array_key_exists($s, $index)) { $index[$s] = count($sst); $sst[] = $s; }
            return $index[$s];
        };

        $cells = [];
        $rowNum = 1;
        $writeRow = function(array $arr) use (&$cells, &$rowNum, $register) {
            $col = 1; // A=1
            $rowXml = '<row r="'.$rowNum.'">';
            foreach ($arr as $cell) {
                $ref = $this->cellRef($col).$rowNum;
                if ($cell === null || $cell === '') {
                    $rowXml .= '<c r="'.$ref.'"/>';
                } elseif (is_int($cell) || is_float($cell)) {
                    $rowXml .= '<c r="'.$ref.'" t="n"><v>'.$cell.'</v></c>';
                } else {
                    $idx = $register($cell);
                    if ($idx === null) {
                        $rowXml .= '<c r="'.$ref.'" t="inlineStr"><is><t>'.htmlspecialchars((string)$cell, ENT_QUOTES|ENT_XML1,'UTF-8').'</t></is></c>';
                    } else {
                        $rowXml .= '<c r="'.$ref.'" t="s"><v>'.$idx.'</v></c>';
                    }
                }
                $col++;
            }
            $rowXml .= '</row>';
            $cells[] = $rowXml;
            $rowNum++;
        };

        $writeRow($headers);
        foreach ($rows as $r) { $writeRow($r); }

        $si = '';
        foreach ($sst as $s) { $si .= '<si><t>'.htmlspecialchars($s, ENT_QUOTES|ENT_XML1,'UTF-8').'</t></si>'; }
        $sstXml = '<?xml version="1.0" encoding="UTF-8"?>'
                 .'<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.count($sst).'" uniqueCount="'.count($sst).'">'
                 .$si.'</sst>';

        $cellWriter = implode('', $cells);
        return [$sstXml, $useSst, $cellWriter];
    }

    private function sheetXml(string $cellWriter): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
             .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
             .'<sheetData>'.$cellWriter.'</sheetData>'
             .'</worksheet>';
    }

    private function cellRef(int $colIndex): string
    {
        $letters = '';
        while ($colIndex > 0) {
            $colIndex--;
            $letters = chr(65 + ($colIndex % 26)) . $letters;
            $colIndex = intdiv($colIndex, 26);
        }
        return $letters;
    }
}
