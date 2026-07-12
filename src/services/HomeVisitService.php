<?php

/**
 * שירות לביקורי בית – ולידציות ונרמול שדות לפני כתיבה ל־DB
 * שמירה על סגנון קוד פשוט ללא תלות חיצונית וללא לוגיקה ב־DB
 */
class HomeVisitService
{
    /**
     * ולידציה בסיסית של קלט מהטופס.
     * מחזיר מערך שגיאות בצורה: ['field' => 'message', ...]
     */
    public static function validate(array $data): array
    {
        $errors = [];

        if (empty($data['employee_id']) || !ctype_digit((string)$data['employee_id']) || (int)$data['employee_id'] <= 0) {
            $errors['employee_id'] = 'יש לבחור עובד.';
        }

        if (empty($data['visit_date']) || !self::isDate((string)$data['visit_date'])) {
            $errors['visit_date'] = 'יש להזין תאריך ביקור תקין.';
        }

        foreach (['visit_type_code','status_code','home_visit_stage_code'] as $k) {
            if (!isset($data[$k]) || $data[$k] === '' || !ctype_digit((string)$data[$k])) {
                $errors[$k] = 'ערך לא תקין.';
            }
        }

        foreach (['placement_id','placement_type_code','visited_by_user_id'] as $k) {
            if (isset($data[$k]) && $data[$k] !== '' && !ctype_digit((string)$data[$k])) {
                $errors[$k] = 'ערך לא תקין.';
            }
        }

        // followup_required → בוליאני
        if (isset($data['followup_required']) && !in_array($data['followup_required'], [0,1,'0','1',true,false,'on'], true)) {
            $errors['followup_required'] = 'ערך לא תקין לשדה "נדרש מעקב".';
        }

        if (!empty($data['next_visit_due']) && !self::isDate((string)$data['next_visit_due'])) {
            $errors['next_visit_due'] = 'תאריך ביקור הבא לא תקין.';
        }

        $followup = (!empty($data['followup_required']) && $data['followup_required'] !== '0');
        if ($followup && empty($data['next_visit_due'])) {
            $errors['next_visit_due'] = 'כאשר נדרש מעקב, יש למלא "תאריך ביקור הבא".';
        }

        return $errors;
    }

    /**
     * נרמול ערכים לפני שמירה:
     *  - INT/NULL לשדות קוד/FK
     *  - followup_required → 0/1
     *  - תאריכים ריקים → NULL
     *  - טקסטים → trim, וריקים → NULL
     */
    public static function normalize(array $data): array
    {
        $data['followup_required'] = (!empty($data['followup_required']) && $data['followup_required'] !== '0') ? 1 : 0;

        foreach (['visit_date','next_visit_due'] as $d) {
            if (empty($data[$d])) { $data[$d] = null; }
        }

        // המרות קודיות אם ריקות
        foreach (['visit_type_code','status_code','home_visit_stage_code'] as $k) {
            if ($data[$k] === '' || $data[$k] === null) {
                $data[$k] = null;
            } else {
                $data[$k] = (int)$data[$k];
            }
        }

        // FK אופציונליים
        foreach (['placement_id','placement_type_code','visited_by_user_id'] as $k) {
            if (!isset($data[$k]) || $data[$k] === '' || $data[$k] === null) {
                $data[$k] = null;
            } else {
                $data[$k] = (int)$data[$k];
            }
        }

        // טקסטים
        foreach (['summary','findings'] as $t) {
            $data[$t] = isset($data[$t]) ? trim((string)$data[$t]) : null;
            if ($data[$t] === '') { $data[$t] = null; }
        }

        // עובד חייב להיות INT
        $data['employee_id'] = (int)($data['employee_id'] ?? 0);

        return $data;
    }

    public static function derivedStatusCode($status_code, ?string $next_visit_due): ?string
    {
        if ($next_visit_due && strtotime($next_visit_due) < strtotime('today')) {
            return 'overdue';
        }
        return is_numeric($status_code) ? (int)$status_code : null;
    }

    public static function daysUntilExpiry(?string $next_visit_due): ?int
    {
        if (!$next_visit_due || !self::isDate($next_visit_due)) return null;
        $d = (new DateTime($next_visit_due))->setTime(0,0,0);
        $t = (new DateTime('today'))->setTime(0,0,0);
        return (int)$t->diff($d)->format('%r%a');
    }

    private static function isDate(string $value): bool
    {
        $dt = DateTime::createFromFormat('Y-m-d', $value);
        return $dt && $dt->format('Y-m-d') === $value;
    }
}
