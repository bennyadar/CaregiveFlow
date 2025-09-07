<?php

class VisaService
{
    /** ולידציה בסיסית לצד השרת (לוגיקה ב-PHP) */
    public static function validate(array $data): array
    {
        $errors = [];

        if (empty($data['employee_id'])) {
            $errors['employee_id'] = 'חובה לבחור עובד.';
        }
        if (empty($data['visa_number'])) {
            $errors['visa_number'] = 'חובה למלא מספר ויזה/סימוכין.';
        }

        // בדיקת סדר תאריכים בסיסית
        $issue = !empty($data['issue_date']) ? strtotime($data['issue_date']) : null;
        $expiry = !empty($data['expiry_date']) ? strtotime($data['expiry_date']) : null;
        if ($issue && $expiry && $expiry < $issue) {
            $errors['expiry_date'] = 'תאריך פקיעה קטן מתאריך הנפקה.';
        }

        // סטטוס חוקי
        $allowed = ['requested','approved','denied','expired'];
        if (!empty($data['status']) && !in_array($data['status'], $allowed, true)) {
            $errors['status'] = 'ערך סטטוס שגוי.';
        }

        return $errors;
    }

    /** חישוב סטטוס נגזר (בלי לגעת ב-DB) לפי תאריך פקיעה */
    public static function derivedStatus(?string $status, ?string $expiry_date): string
    {
        if ($expiry_date && strtotime($expiry_date) < strtotime('today')) {
            return 'expired';
        }
        return $status ?: 'requested';
    }

    /** ימים עד פקיעה (שלילי אם פג) */
    public static function daysUntilExpiry(?string $expiry_date): ?int
    {
        if (!$expiry_date) return null;
        $d = (new DateTime($expiry_date))->setTime(0,0,0);
        $t = (new DateTime('today'))->setTime(0,0,0);
        return (int)$t->diff($d)->format('%r%a'); // כולל סימן
    }
}

?>