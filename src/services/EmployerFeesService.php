<?php

class EmployerFeesService
{
    public static function validate(array $data): array
    {
        $errors = [];

        if (empty($data['employer_id'])) {
            $errors['employer_id'] = 'חובה לבחור מעסיק.';
        }
        if (empty($data['period_ym'])) {
            $errors['period_ym'] = 'חובה למלא חודש חיוב (YYYY-MM).';
        } elseif (!preg_match('/^\d{4}-\d{2}$/', $data['period_ym'])) {
            $errors['period_ym'] = 'פורמט חודש לא תקין (YYYY-MM).';
        }
        if (($data['amount'] ?? '') === '' || !is_numeric($data['amount'])) {
            $errors['amount'] = 'חובה למלא סכום תקין.';
        }

        // קשרי תאריכים: תשלום לא לפני מועד חיוב (אם יש due_date)
        $due    = !empty($data['due_date']) ? strtotime($data['due_date']) : null;
        $paid   = !empty($data['payment_date']) ? strtotime($data['payment_date']) : null;
        if ($due && $paid && $paid < $due) {
            $errors['payment_date'] = 'תאריך תשלום לא יכול להיות לפני מועד חיוב.';
        }

        // קודים נומריים אם מולאו
        foreach (['status_code','fee_type_code','payment_method_code'] as $k) {
            if ($data[$k] !== '' && !is_numeric($data[$k])) {
                $errors[$k] = 'קוד לא תקין.';
            }
        }

        return $errors;
    }
}
