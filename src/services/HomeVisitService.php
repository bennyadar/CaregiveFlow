<?php
// CaregiveFlow — Home Visits Module
// File: src/services/HomeVisitService.php
// HE: ולידציות עסקיות + גזירת ברירות מחדל (למשל מציאת שיבוץ פעיל ביום הביקור)
// EN: Business validation + defaults (e.g., resolve active placement on visit_date)

class HomeVisitService {
    /**
     * Attempt to resolve active placement for employee at visit_date.
     * Adjust field names if placements schema differs in your DB.
     * @return int|null placement_id or null if none active
     */
    public static function resolvePlacementId(PDO $db, int $employeeId, string $visitDate): ?int {
        $sql = "SELECT p.id
                FROM placements p
                WHERE p.employee_id = :eid
                  AND (p.start_date IS NULL OR p.start_date <= :d)
                  AND (p.end_date   IS NULL OR p.end_date   >= :d)
                ORDER BY p.id DESC
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([':eid' => $employeeId, ':d' => $visitDate]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }

    /**
     * Server-side validation according to the spec.
     * Throws InvalidArgumentException on failure.
     */
    public static function validate(array $data): void {
        $required = ['employee_id','visit_date','visit_type_code','status_code','home_visit_stage_code'];
        foreach ($required as $k) {
            if (empty($data[$k])) {
                throw new InvalidArgumentException("Missing required field: $k");
            }
        }
        // If set to DONE(2) → require at least summary or findings
        if ((int)$data['status_code'] === 2) {
            $hasSummary  = !empty(trim($data['summary']  ?? ''));
            $hasFindings = !empty(trim($data['findings'] ?? ''));
            if (!$hasSummary && !$hasFindings) {
                throw new InvalidArgumentException('שינוי לסטטוס "בוצע" מחייב סיכום או ממצאים.');
            }
        }
        // followup_required requires next_visit_due >= visit_date
        if (!empty($data['followup_required'])) {
            if (empty($data['next_visit_due'])) {
                throw new InvalidArgumentException('סומן "נדרש מעקב" אך לא סופק תאריך ביקור הבא.');
            }
            if ($data['next_visit_due'] < $data['visit_date']) {
                throw new InvalidArgumentException('תאריך ביקור הבא חייב להיות מאוחר או שווה לתאריך הביקור.');
            }
        }
    }
}
