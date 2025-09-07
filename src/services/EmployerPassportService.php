<?php

class EmployerPassportService
{
    public static function validate(array $data): array
    {
        $errors = [];

        if (empty($data['employer_id'])) {
            $errors['employer_id'] = 'חובה לבחור מעסיק.';
        }
        if (empty($data['passport_number'])) {
            $errors['passport_number'] = 'חובה למלא מס׳ דרכון.';
        }

        // סדר תאריכים
        $issue  = !empty($data['issue_date'])  ? strtotime($data['issue_date'])  : null;
        $expiry = !empty($data['expiry_date']) ? strtotime($data['expiry_date']) : null;
        if ($issue && $expiry && $expiry < $issue) {
            $errors['expiry_date'] = 'תאריך פקיעה קטן מתאריך הנפקה.';
        }

        // קודים מספריים אם מולאו (כולל country_code)
        foreach (['status_code','passport_type_code','country_code'] as $k) {
            if (isset($data[$k]) && $data[$k] !== '' && !is_numeric($data[$k])) {
                $errors[$k] = 'קוד לא תקין.';
            }
        }

        return $errors;
    }

    /** סטטוס נגזר: אם פג תוקף – מציגים "פג" ללא תלות בקוד */
    public static function derivedStatusCode($status_code, ?string $expiry_date)
    {
        if ($expiry_date && strtotime($expiry_date) < strtotime('today')) {
            return 'expired';
        }
        return is_numeric($status_code) ? (int)$status_code : null;
    }

    /** ימים עד פקיעה (שלילי אם פג) */
    public static function daysUntilExpiry(?string $expiry_date): ?int
    {
        if (!$expiry_date) return null;
        $d = (new DateTime($expiry_date))->setTime(0,0,0);
        $t = (new DateTime('today'))->setTime(0,0,0);
        return (int)$t->diff($d)->format('%r%a');
    }
}
