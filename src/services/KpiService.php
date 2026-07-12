<?php
declare(strict_types=1);

/**
 * KpiService
 *
 * מרכז לוגיקת KPI (כרטיסי דשבורד) כדי למנוע שכפול שאילתות בין מסכים.
 * כלל: השירות מבצע קריאה בלבד מה-DB.
 */
class KpiService
{
    /**
     * KPI תוקפים לעובדים: דרכון/ויזה/ביטוח.
     *
     * מחזיר:
     * [
     *   'passport'   => ['expired'=>int, 'soon'=>int],
     *   'visa'       => ['expired'=>int, 'soon'=>int],
     *   'insurance'  => ['expired'=>int, 'soon'=>int],
     * ]
     */
    public static function employeeExpiry(PDO $pdo, int $soonDays = 30): array
    {
        $soonTo = (new DateTime('today'))->modify('+' . $soonDays . ' days')->format('Y-m-d');

        // דרכונים: משתמשים ב-MAX(expiry_date) לכל עובד.
        $sqlPassport = "
            SELECT
              COALESCE(SUM(t.expiry_date < CURDATE()),0) AS expired,
              COALESCE(SUM(t.expiry_date >= CURDATE() AND t.expiry_date <= :soon),0) AS soon
            FROM (
              SELECT employee_id, MAX(expiry_date) AS expiry_date
              FROM employee_passports
              GROUP BY employee_id
            ) t
            WHERE t.expiry_date IS NOT NULL
        ";
        $stmt = $pdo->prepare($sqlPassport);
        $stmt->execute([':soon' => $soonTo]);
        $passport = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['expired' => 0, 'soon' => 0];

        // ויזות: מיישר ללוגיקה שקיימת אצלך בעובדים -
        // מועד הקרוב ביותר בעתיד, ואם אין - האחרון הקיים.
        $sqlVisa = "
            SELECT
              COALESCE(SUM(t.expiry_date < CURDATE()),0) AS expired,
              COALESCE(SUM(t.expiry_date >= CURDATE() AND t.expiry_date <= :soon),0) AS soon
            FROM (
              SELECT employee_id,
                     COALESCE(
                       MIN(CASE WHEN expiry_date >= CURDATE() THEN expiry_date END),
                       MAX(expiry_date)
                     ) AS expiry_date
              FROM visas
              GROUP BY employee_id
            ) t
            WHERE t.expiry_date IS NOT NULL
        ";
        $stmt = $pdo->prepare($sqlVisa);
        $stmt->execute([':soon' => $soonTo]);
        $visa = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['expired' => 0, 'soon' => 0];

        // ביטוחים: אותו עיקרון כמו ויזות.
        $sqlIns = "
            SELECT
              COALESCE(SUM(t.expiry_date < CURDATE()),0) AS expired,
              COALESCE(SUM(t.expiry_date >= CURDATE() AND t.expiry_date <= :soon),0) AS soon
            FROM (
              SELECT employee_id,
                     COALESCE(
                       MIN(CASE WHEN expiry_date >= CURDATE() THEN expiry_date END),
                       MAX(expiry_date)
                     ) AS expiry_date
              FROM employee_insurances
              GROUP BY employee_id
            ) t
            WHERE t.expiry_date IS NOT NULL
        ";
        $stmt = $pdo->prepare($sqlIns);
        $stmt->execute([':soon' => $soonTo]);
        $insurance = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['expired' => 0, 'soon' => 0];

        return [
            'passport'  => ['expired' => (int)$passport['expired'], 'soon' => (int)$passport['soon']],
            'visa'      => ['expired' => (int)$visa['expired'], 'soon' => (int)$visa['soon']],
            'insurance' => ['expired' => (int)$insurance['expired'], 'soon' => (int)$insurance['soon']],
        ];
    }

    /**
     * KPI לשיבוצים.
     */
    public static function placements(PDO $pdo, int $soonDays = 30): array
    {
        $total = (int)$pdo->query("SELECT COUNT(*) FROM placements")->fetchColumn();

        $activeStmt = $pdo->query("
            SELECT COUNT(*)
            FROM placements
            WHERE start_date <= CURDATE()
              AND (end_date IS NULL OR end_date >= CURDATE())
        ");
        $active = (int)$activeStmt->fetchColumn();

        $soonStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM placements
            WHERE end_date IS NOT NULL
              AND end_date >= CURDATE()
              AND end_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
        ");
        $soonStmt->bindValue(':days', $soonDays, PDO::PARAM_INT);
        $soonStmt->execute();
        $endingSoon = (int)$soonStmt->fetchColumn();

        return [
            'total'       => $total,
            'active'      => $active,
            'ending_soon' => $endingSoon,
        ];
    }

    /**
     * KPI לביקורי בית.
     */
    public static function homeVisits(PDO $pdo, int $dueSoonDays = 7, int $lookbackDays = 30): array
    {
        $total = (int)$pdo->query("SELECT COUNT(*) FROM home_visits")->fetchColumn();

        $followup = (int)$pdo->query("SELECT COUNT(*) FROM home_visits WHERE followup_required = 1")->fetchColumn();

        $overdue = (int)$pdo->query("
            SELECT COUNT(*)
            FROM home_visits
            WHERE followup_required = 1
              AND next_visit_due IS NOT NULL
              AND next_visit_due < CURDATE()
        ")->fetchColumn();

        $dueSoonStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM home_visits
            WHERE followup_required = 1
              AND next_visit_due IS NOT NULL
              AND next_visit_due >= CURDATE()
              AND next_visit_due <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
        ");
        $dueSoonStmt->bindValue(':days', $dueSoonDays, PDO::PARAM_INT);
        $dueSoonStmt->execute();
        $dueSoon = (int)$dueSoonStmt->fetchColumn();

        $recentStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM home_visits
            WHERE visit_date IS NOT NULL
              AND visit_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
        ");
        $recentStmt->bindValue(':days', $lookbackDays, PDO::PARAM_INT);
        $recentStmt->execute();
        $recent = (int)$recentStmt->fetchColumn();

        return [
            'total'    => $total,
            'followup' => $followup,
            'overdue'  => $overdue,
            'due_soon' => $dueSoon,
            'recent'   => $recent,
        ];
    }
}
