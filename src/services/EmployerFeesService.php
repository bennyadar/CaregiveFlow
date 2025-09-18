<?php
/**
 * ולידציות למודול דמי תאגיד – שמירה על סגנון/פילוסופיה קיימת.
 * הערה: ה-DB משמש לאחסון/שליפה בלבד; כל הלוגיקה כאן (PHP).
 */
class EmployerFeesService
{
    /**
     * @param array $data נתוני הטופס כפי שנשלחו מה-Controller
     * @return array  מערך שגיאות לפי שם שדה (ריק = תקין)
     */
    public static function validate(array $data): array
    {
        $errors = [];

        // מעסיק חובה
        if (empty($data['employer_id'])) {
            $errors['employer_id'] = 'יש לבחור מעסיק.';
        }

        // סוג חיוב חובה
        if (($data['fee_type_code'] ?? '') === '') {
            $errors['fee_type_code'] = 'יש לבחור סוג חיוב.';
        }

        // סכום > 0 (רשות לריק, אבל אם מולא אז מספרי וחיובי)
        if (($data['amount'] ?? '') !== '') {
            if (!is_numeric($data['amount'])) {
                $errors['amount'] = 'הסכום חייב להיות מספר.';
            } elseif ((float)$data['amount'] < 0) {
                $errors['amount'] = 'הסכום חייב להיות אפס ומעלה.';
            }
        }

        // תאריכים בסיסיים – פורמט YYYY-MM-DD
        foreach (['due_date','payment_date','payment_from_date','payment_to_date'] as $field) {
            if (($data[$field] ?? '') !== '' && !self::isValidDateYmd($data[$field])) {
                $errors[$field] = 'פורמט תאריך לא תקין (YYYY-MM-DD).';
            }
        }

        // תקופת תשלום – הכללים:
        // 1) אם מולא אחד – שניהם חייבים להיות מלאים.
        // 2) אם שניהם מולאו – from <= to.
        $hasFrom = ($data['payment_from_date'] ?? '') !== '';
        $hasTo   = ($data['payment_to_date']   ?? '') !== '';
        if ($hasFrom xor $hasTo) {
            $errors['payment_from_date'] = $errors['payment_to_date'] = 'יש למלא גם "מתאריך" וגם "עד תאריך".';
        }
        if ($hasFrom && $hasTo && empty($errors['payment_from_date']) && empty($errors['payment_to_date'])) {
            if ($data['payment_from_date'] > $data['payment_to_date']) {
                $errors['payment_to_date'] = '"עד תאריך" חייב להיות שווה או גדול מ-"מתאריך".';
            }
        }

        // אם יש תאריך תשלום בפועל אך אין סטטוס/אמצעי תשלום – לא חובה, אבל אזהרה קלה (לפי הסטייל שלך – כשגיאה רכה)
        if (($data['payment_date'] ?? '') !== '') {
            if (($data['status_code'] ?? '') === '') {
                $errors['status_code'] = 'מומלץ לבחור סטטוס עבור רשומה ששולמה.';
            }
            if (($data['payment_method_code'] ?? '') === '') {
                $errors['payment_method_code'] = 'מומלץ לציין אמצעי תשלום.';
            }
        }

        return $errors;
    }

    // ===== Helpers =====

    private static function isValidDateYmd(string $s): bool
    {
        // מצפה ל-YYYY-MM-DD
        $dt = DateTime::createFromFormat('Y-m-d', $s);
        return $dt && $dt->format('Y-m-d') === $s;
    }
}
