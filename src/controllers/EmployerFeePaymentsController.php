<?php
declare(strict_types=1);

class EmployerFeePaymentsController
{
    public static function store(PDO $pdo): void
    {
        require_login();
        $fee_id = (int)($_POST['employer_fee_id'] ?? 0);
        $paid_amount = (float)($_POST['paid_amount'] ?? 0);
        $paid_at = ($_POST['paid_at'] ?? null);
        $note = trim((string)($_POST['note'] ?? ''));

        if ($fee_id <= 0 || $paid_amount <= 0 || empty($paid_at)) {
            flash('נא למלא סכום ותאריך תשלום.', 'danger');
            redirect('employer_fees/edit', ['id' => $fee_id]);
        }

        $pdo->prepare("INSERT INTO employer_fee_payments (employer_fee_id, paid_amount, paid_at, note) VALUES (?,?,?,?)")
            ->execute([$fee_id, $paid_amount, $paid_at, $note]);

        // עדכון סטטוס/תאריך תשלום בשורה הראשית (רק אם מצוין במפורש)
        if (isset($_POST['apply_to_header'])) {
            $pdo->prepare("UPDATE employer_fees SET payment_status='paid', payment_date=? WHERE id=?")
                ->execute([$paid_at, $fee_id]);
        }

        // חזרה לעמוד העריכה של הרשומה
        flash('תשלום נוסף להיסטוריה.');
        redirect('employer_fees/edit', ['id' => $fee_id]);
    }

    public static function destroy(PDO $pdo): void
    {
        require_login();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { redirect('employers/index'); }

        $st = $pdo->prepare("SELECT employer_fee_id FROM employer_fee_payments WHERE id = ?");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) { redirect('employers/index'); }

        $fee_id = (int)$row['employer_fee_id'];
        $pdo->prepare("DELETE FROM employer_fee_payments WHERE id = ?")->execute([$id]);
        flash('תשלום נמחק מההיסטוריה.');
        redirect('employer_fees/edit', ['id' => $fee_id]);
    }
}
