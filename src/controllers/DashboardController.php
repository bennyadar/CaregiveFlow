<?php
declare(strict_types=1);

class DashboardController
{
    /**
     * דף הדשבורד הראשי.
     * מחשב את כמות ה"שיבוצים הפעילים" לכל אחד מ־12 החודשים האחרונים
     * ללא תלות בעמודת status וללא שימוש ב-VIEWים.
     *
     * כלל: שיבוץ נחשב "פעיל" בחודש X אם:
     * start_date <= היום האחרון של החודש
     * AND (end_date IS NULL OR end_date >= היום הראשון של החודש)
     */
    public static function index(PDO $pdo): void
    {
        require_login();

        $labels = [];
        $series_active = [];

        $today = new DateTime('today');

        for ($i = 11; $i >= 0; $i--) {
            // היום הראשון/האחרון של החודש המבוקש
            $first = (clone $today)->modify("first day of -$i month")->format('Y-m-01');
            $last  = (new DateTime($first))->format('Y-m-t'); // יום אחרון בחודש

            // ספירת "פעילים בחודש"
            $stmt = $pdo->prepare("
                SELECT COUNT(*) AS cnt
                FROM placements
                WHERE start_date <= :last
                  AND (end_date IS NULL OR end_date >= :first)
            ");
            $stmt->execute([':first' => $first, ':last' => $last]);
            $countActive = (int)$stmt->fetchColumn();

            $labels[] = (new DateTime($first))->format('m/Y');
            $series_active[] = $countActive;
        }

        // ===== כרטיסיות סטטיסטיקה (ללא תלות ב-views) =====
        $stats = [
            'total_employees'   => (int)$pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn(),
            'total_employers'   => (int)$pdo->query("SELECT COUNT(*) FROM employers")->fetchColumn(),
            'total_placements'  => (int)$pdo->query("SELECT COUNT(*) FROM placements")->fetchColumn(),
            'total_home_visits' => (int)$pdo->query("SELECT COUNT(*) FROM home_visits")->fetchColumn(), // יש אצלך טבלה כזו
        ];

        // ===== נתוני הגרף: שיבוצים פעילים 12 חודשים אחורה =====
        $labels = [];
        $series_active = [];
        $today  = new DateTime('today');

        for ($i = 11; $i >= 0; $i--) {
            $first = (clone $today)->modify("first day of -$i month")->format('Y-m-01');
            $last  = (new DateTime($first))->format('Y-m-t');

            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM placements
                WHERE start_date <= :last
                AND (end_date IS NULL OR end_date >= :first)
            ");
            $stmt->execute([':first' => $first, ':last' => $last]);
            $series_active[] = (int)$stmt->fetchColumn();
            $labels[] = (new DateTime($first))->format('m/Y');
        }

        $chartData = ['labels' => $labels, 'active' => $series_active];

        // מסירה ל-view
        require __DIR__ . '/../../views/dashboard/index.php';
    }
}
