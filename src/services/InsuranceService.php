<?php

class InsuranceService
{
    /** ולידציה בסיסית לצד השרת (לוגיקה ב-PHP) */
    public static function validate(array $data): array
    {
        $errors = [];

        if (empty($data['employee_id'])) {
            $errors['employee_id'] = 'חובה לבחור עובד.';
        }
        if (empty($data['policy_number'])) {
            $errors['policy_number'] = 'חובה למלא מס׳ פוליסה.';
        }
        if (empty($data['insurer_name'])) {
            $errors['insurer_name'] = 'חובה למלא שם מבטח.';
        }

        // בדיקת סדר תאריכים בסיסית (אם מולאו)
        $issue  = !empty($data['issue_date'])  ? strtotime($data['issue_date'])  : null;
        $expiry = !empty($data['expiry_date']) ? strtotime($data['expiry_date']) : null;
        if ($issue && $expiry && $expiry < $issue) {
            $errors['expiry_date'] = 'תאריך פקיעה קטן מתאריך הנפקה.';
        }

        // וולידציה מבנית לקודים (לא בודקים קיום ב-DB כאן; FK ידאג לזה)
        if ($data['status_code'] !== '' && !is_numeric($data['status_code'])) {
            $errors['status_code'] = 'קוד סטטוס לא תקין.';
        }
        if ($data['insurance_type_code'] !== '' && !is_numeric($data['insurance_type_code'])) {
            $errors['insurance_type_code'] = 'קוד סוג ביטוח לא תקין.';
        }

        return $errors;
    }

    /** סטטוס נגזר: אם פג תוקף – מציגים "פג" ללא תלות בקוד */
    public static function derivedStatusCode($status_code, ?string $expiry_date)
    {
        if ($expiry_date && strtotime($expiry_date) < strtotime('today')) {
            // קוד וירטואלי להצגת "פג" במסכים
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
