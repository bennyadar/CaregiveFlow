<?php
// CaregiveFlow — Home Visits Module
// File: src/models/HomeVisit.php
// HE: מודל ביקורי בית — חתימות זהות למודולים קיימים (all/count/find/create/update/delete)
// EN: Home Visits model — method signatures aligned 1:1 with existing modules

class HomeVisit {
    /**
     * Fetch list with filters + pagination
     * @param PDO   $db
     * @param array $filters [employee_id, status_codes[], type_codes[], stage_codes[], placement_type_codes[], followup_required, date_from, date_to]
     * @param int   $limit
     * @param int   $offset
     * @return array
     */
    public static function all(PDO $db, array $filters = [], int $limit = 50, int $offset = 0): array {
        $sql = "SELECT hv.*, 
                       e.first_name AS employee_first_name, e.last_name AS employee_last_name,
                       t.name_he AS type_name, s.name_he AS status_name, g.name_he AS stage_name,
                       pt.name_he AS placement_type_name
                FROM home_visits hv
                JOIN employees e ON e.id = hv.employee_id
                LEFT JOIN home_visit_type_codes t ON t.home_visit_type_code = hv.visit_type_code
                LEFT JOIN home_visit_status_codes s ON s.home_visit_status_code = hv.status_code
                LEFT JOIN home_visit_stage_codes g ON g.home_visit_stage_code = hv.home_visit_stage_code
                LEFT JOIN placement_type_codes pt ON pt.placement_type_code = hv.placement_type_code
                WHERE 1=1";
        $params = [];

        if (!empty($filters['employee_id'])) {
            $sql .= " AND hv.employee_id = :employee_id";
            $params[':employee_id'] = (int)$filters['employee_id'];
        }
        if (!empty($filters['status_codes']) && is_array($filters['status_codes'])) {
            $in = implode(',', array_fill(0, count($filters['status_codes']), '?'));
            $sql .= " AND hv.status_code IN ($in)";
            $params = array_merge($params, array_map('intval', $filters['status_codes']));
        }
        if (!empty($filters['type_codes']) && is_array($filters['type_codes'])) {
            $in = implode(',', array_fill(0, count($filters['type_codes']), '?'));
            $sql .= " AND hv.visit_type_code IN ($in)";
            $params = array_merge($params, array_map('intval', $filters['type_codes']));
        }
        if (!empty($filters['stage_codes']) && is_array($filters['stage_codes'])) {
            $in = implode(',', array_fill(0, count($filters['stage_codes']), '?'));
            $sql .= " AND hv.home_visit_stage_code IN ($in)";
            $params = array_merge($params, array_map('intval', $filters['stage_codes']));
        }
        if (!empty($filters['placement_type_codes']) && is_array($filters['placement_type_codes'])) {
            $in = implode(',', array_fill(0, count($filters['placement_type_codes']), '?'));
            $sql .= " AND hv.placement_type_code IN ($in)";
            $params = array_merge($params, array_map('intval', $filters['placement_type_codes']));
        }
        if (isset($filters['followup_required']) && $filters['followup_required'] !== '') {
            $sql .= " AND hv.followup_required = ?";
            $params[] = (int)$filters['followup_required'];
        }
        if (!empty($filters['date_from'])) { $sql .= " AND hv.visit_date >= ?"; $params[] = $filters['date_from']; }
        if (!empty($filters['date_to']))   { $sql .= " AND hv.visit_date <= ?"; $params[] = $filters['date_to']; }

        $sql .= " ORDER BY hv.visit_date DESC, hv.id DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        // bind positional first (for IN/=?), then named (limit/offset + :employee_id)
        $i = 1;
        foreach ($params as $k=>$v) {
            if (is_int($k)) { $stmt->bindValue($i++, $v); } else { $stmt->bindValue($k, $v); }
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return int */
    public static function count(PDO $db, array $filters = []): int {
        $sql = "SELECT COUNT(*) AS cnt FROM home_visits hv WHERE 1=1";
        $params = [];
        if (!empty($filters['employee_id'])) {
            $sql .= " AND hv.employee_id = :employee_id";
            $params[':employee_id'] = (int)$filters['employee_id'];
        }
        if (!empty($filters['status_codes']) && is_array($filters['status_codes'])) {
            $in = implode(',', array_fill(0, count($filters['status_codes']), '?'));
            $sql .= " AND hv.status_code IN ($in)";
            $params = array_merge($params, array_map('intval', $filters['status_codes']));
        }
        if (!empty($filters['type_codes']) && is_array($filters['type_codes'])) {
            $in = implode(',', array_fill(0, count($filters['type_codes']), '?'));
            $sql .= " AND hv.visit_type_code IN ($in)";
            $params = array_merge($params, array_map('intval', $filters['type_codes']));
        }
        if (!empty($filters['stage_codes']) && is_array($filters['stage_codes'])) {
            $in = implode(',', array_fill(0, count($filters['stage_codes']), '?'));
            $sql .= " AND hv.home_visit_stage_code IN ($in)";
            $params = array_merge($params, array_map('intval', $filters['stage_codes']));
        }
        if (!empty($filters['placement_type_codes']) && is_array($filters['placement_type_codes'])) {
            $in = implode(',', array_fill(0, count($filters['placement_type_codes']), '?'));
            $sql .= " AND hv.placement_type_code IN ($in)";
            $params = array_merge($params, array_map('intval', $filters['placement_type_codes']));
        }
        if (isset($filters['followup_required']) && $filters['followup_required'] !== '') {
            $sql .= " AND hv.followup_required = ?";
            $params[] = (int)$filters['followup_required'];
        }
        if (!empty($filters['date_from'])) { $sql .= " AND hv.visit_date >= ?"; $params[] = $filters['date_from']; }
        if (!empty($filters['date_to']))   { $sql .= " AND hv.visit_date <= ?"; $params[] = $filters['date_to']; }

        $stmt = $db->prepare($sql);
        $i = 1; foreach ($params as $k=>$v) { if (is_int($k)) { $stmt->bindValue($i++, $v); } else { $stmt->bindValue($k, $v); } }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /** @return array|null */
    public static function find(PDO $db, int $id): ?array {
        $stmt = $db->prepare("SELECT hv.*, 
                                     e.first_name AS employee_first_name, e.last_name AS employee_last_name,
                                     t.name_he AS type_name, s.name_he AS status_name, g.name_he AS stage_name,
                                     pt.name_he AS placement_type_name
                              FROM home_visits hv
                              JOIN employees e ON e.id = hv.employee_id
                              LEFT JOIN home_visit_type_codes t ON t.home_visit_type_code = hv.visit_type_code
                              LEFT JOIN home_visit_status_codes s ON s.home_visit_status_code = hv.status_code
                              LEFT JOIN home_visit_stage_codes g ON g.home_visit_stage_code = hv.home_visit_stage_code
                              LEFT JOIN placement_type_codes pt ON pt.placement_type_code = hv.placement_type_code
                              WHERE hv.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** @return int Newly created id */
    public static function create(PDO $db, array $data): int {
        $sql = "INSERT INTO home_visits
                (employee_id, placement_id, visit_date, visit_type_code, status_code, home_visit_stage_code,
                 placement_type_code, visited_by_user_id, summary, findings, followup_required, next_visit_due)
                VALUES (:employee_id, :placement_id, :visit_date, :visit_type_code, :status_code, :stage_code,
                        :placement_type_code, :visited_by_user_id, :summary, :findings, :followup_required, :next_visit_due)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':employee_id' => (int)$data['employee_id'],
            ':placement_id' => !empty($data['placement_id']) ? (int)$data['placement_id'] : null,
            ':visit_date' => $data['visit_date'],
            ':visit_type_code' => (int)$data['visit_type_code'],
            ':status_code' => (int)$data['status_code'],
            ':stage_code' => (int)$data['home_visit_stage_code'],
            ':placement_type_code' => !empty($data['placement_type_code']) ? (int)$data['placement_type_code'] : null,
            ':visited_by_user_id' => !empty($data['visited_by_user_id']) ? (int)$data['visited_by_user_id'] : null,
            ':summary' => $data['summary'] ?? null,
            ':findings' => $data['findings'] ?? null,
            ':followup_required' => !empty($data['followup_required']) ? 1 : 0,
            ':next_visit_due' => !empty($data['next_visit_due']) ? $data['next_visit_due'] : null,
        ]);
        return (int)$db->lastInsertId();
    }

    /** @return bool */
    public static function update(PDO $db, int $id, array $data): bool {
        $sql = "UPDATE home_visits SET
                  employee_id=:employee_id,
                  placement_id=:placement_id,
                  visit_date=:visit_date,
                  visit_type_code=:visit_type_code,
                  status_code=:status_code,
                  home_visit_stage_code=:stage_code,
                  placement_type_code=:placement_type_code,
                  visited_by_user_id=:visited_by_user_id,
                  summary=:summary,
                  findings=:findings,
                  followup_required=:followup_required,
                  next_visit_due=:next_visit_due
                WHERE id=:id";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':employee_id' => (int)$data['employee_id'],
            ':placement_id' => !empty($data['placement_id']) ? (int)$data['placement_id'] : null,
            ':visit_date' => $data['visit_date'],
            ':visit_type_code' => (int)$data['visit_type_code'],
            ':status_code' => (int)$data['status_code'],
            ':stage_code' => (int)$data['home_visit_stage_code'],
            ':placement_type_code' => !empty($data['placement_type_code']) ? (int)$data['placement_type_code'] : null,
            ':visited_by_user_id' => !empty($data['visited_by_user_id']) ? (int)$data['visited_by_user_id'] : null,
            ':summary' => $data['summary'] ?? null,
            ':findings' => $data['findings'] ?? null,
            ':followup_required' => !empty($data['followup_required']) ? 1 : 0,
            ':next_visit_due' => !empty($data['next_visit_due']) ? $data['next_visit_due'] : null,
        ]);
    }

    /** @return bool */
    public static function delete(PDO $db, int $id): bool {
        $stmt = $db->prepare("DELETE FROM home_visits WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
