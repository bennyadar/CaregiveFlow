<?php

/**
 * שירות לדרכונים – ולידציות ונרמול שדות לפני כתיבה ל־DB
 * שמירה על סגנון קוד פשוט ללא תלות חיצונית וללא לוגיקה ב־DB
 */
class PassportService
{
    /**
     * ולידציה בסיסית של קלט מהטופס.
     * מחזיר מערך שגיאות בצורה: ['field' => 'message', ...]
     *
     * שדות חדשים:
     *  - is_primary: בוליאני (0/1)
     *  - primary_employee_id: מספר שלם או ריק (NULL)
     */
    public static function validate(array $data): array
    {
        $errors = [];

        // חובה
        if (empty($data['employee_id']) || !ctype_digit((string)$data['employee_id'])) {
            $errors['employee_id'] = 'חובה לבחור עובד (מספר תקין).';
        }
        if (empty($data['passport_number'])) {
            $errors['passport_number'] = 'חובה להזין מס׳ דרכון.';
        }

        // פורמט תאריכים (אם מולאו)
        $issue = $data['issue_date'] ?? null;
        $exp   = $data['expiry_date'] ?? null;
        if ($issue && !self::isDate($issue)) {
            $errors['issue_date'] = 'תאריך הנפקה לא תקין (YYYY-MM-DD).';
        }
        if ($exp && !self::isDate($exp)) {
            $errors['expiry_date'] = 'תאריך פקיעה לא תקין (YYYY-MM-DD).';
        }
        if ($issue && $exp && $issue > $exp) {
            $errors['expiry_date'] = 'תאריך הפקיעה חייב להיות אחרי תאריך ההנפקה.';
        }

        // נרמול ובדיקה – is_primary & primary_employee_id
        // (המודל כבר מטפל בלוגיקת "ראשי יחיד" לכל עובד)
        if (isset($data['is_primary']) && !in_array($data['is_primary'], [0, 1, '0', '1', true, false, 'on'], true)) {
            $errors['is_primary'] = 'ערך לא תקין לשדה דרכון ראשי.';
        }
        if (isset($data['primary_employee_id']) && $data['primary_employee_id'] !== '' && !ctype_digit((string)$data['primary_employee_id'])) {
            $errors['primary_employee_id'] = 'ID דרכון ראשי חייב להיות מספרי.';
        }

        // קודי עזר (לא חובה – רק אם מולאו)
        if (isset($data['passport_type_code']) && $data['passport_type_code'] !== '' && !ctype_digit((string)$data['passport_type_code'])) {
            $errors['passport_type_code'] = 'קוד סוג דרכון לא תקין.';
        }
        if (isset($data['country_code']) && $data['country_code'] !== '' && !ctype_digit((string)$data['country_code'])) {
            $errors['country_code'] = 'קוד מדינה לא תקין.';
        }
        if (isset($data['status_code']) && $data['status_code'] !== '' && !ctype_digit((string)$data['status_code'])) {
            $errors['status_code'] = 'קוד סטטוס לא תקין.';
        }

        return $errors;
    }

    /**
     * נרמול קלט לפורמט כתיבה ל־DB (אופציונלי לשימוש בבקר/מודל)
     *  - is_primary → 0/1
     *  - primary_employee_id → NULL/INT
     */
    public static function normalize(array $data): array
    {
        // is_primary כ־0/1
        $data['is_primary'] = (!empty($data['is_primary']) && $data['is_primary'] !== '0') ? 1 : 0;

        // primary_employee_id → NULL אם ריק
        if (!isset($data['primary_employee_id']) || $data['primary_employee_id'] === '') {
            $data['primary_employee_id'] = null;
        } else {
            $data['primary_employee_id'] = (int)$data['primary_employee_id'];
        }

        // המרה לתאריכים NULL אם ריקים
        foreach (['issue_date','expiry_date'] as $d) {
            if (empty($data[$d])) { $data[$d] = null; }
        }

        // המרות קודיות אם ריקות
        foreach (['passport_type_code','country_code','status_code'] as $k) {
            if ($data[$k] === '' || $data[$k] === null) {
                $data[$k] = null;
            } else {
                $data[$k] = (int)$data[$k];
            }
        }

        return $data;
    }

    public static function derivedStatusCode($status_code, ?string $expiry_date): ?string
    {
        if ($expiry_date && strtotime($expiry_date) < strtotime('today')) {
            return 'expired';
        }
        return is_numeric($status_code) ? (int)$status_code : null;
    }

    /** ימים עד פקיעה (שלילי אם פג) */
    public static function daysUntilExpiry(?string $expiry_date): ?int
    {
        if (!$expiry_date || !self::isDate($expiry_date)) return null;
        $d = (new DateTime($expiry_date))->setTime(0,0,0);
        $t = (new DateTime('today'))->setTime(0,0,0);
        return (int)$t->diff($d)->format('%r%a');
    }

    // ===== עזר פנימי =====
    private static function isDate(string $d): bool
    {
        return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
    }
}
