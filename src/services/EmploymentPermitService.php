<?php

class EmploymentPermitService
{
    /** ולידציה בסיסית לצד השרת (לוגיקה ב-PHP) */
    public static function validate(array $data): array
    {
        $errors = [];

        if (empty($data['employer_id'])) {
            $errors['employer_id'] = 'חובה לבחור מעסיק.';
        }
        if (empty($data['permit_number'])) {
            $errors['permit_number'] = 'חובה למלא מס׳ היתר.';
        }

        // בדיקת סדר תאריכים בסיסית (אם מולאו)
        $issue  = !empty($data['issue_date'])  ? strtotime($data['issue_date'])  : null;
        $expiry = !empty($data['expiry_date']) ? strtotime($data['expiry_date']) : null;
        if ($issue && $expiry && $expiry < $issue) {
            $errors['expiry_date'] = 'תאריך פקיעה קטן מתאריך הנפקה.';
        }

        // וולידציה מבנית לקודים (לא בודקים קיום ב-DB כאן; FK ידאג לזה)
        foreach (['status_code','permit_type_code'] as $k) {
            if ($data[$k] !== '' && !is_numeric($data[$k])) {
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
