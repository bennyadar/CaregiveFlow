<?php

/**
 * מודל ביקורי בית (home_visits)
 * CRUD + חיפוש/סינון + JOIN לקודי סטטוס/סוג/שלב + עובדים/השמות
 */
class HomeVisit
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO home_visits
                    (employee_id, placement_id, visit_date, visit_type_code, status_code, home_visit_stage_code,
                     placement_type_code, visited_by_user_id, summary, findings, followup_required, next_visit_due)
                VALUES
                    (:employee_id, :placement_id, :visit_date, :visit_type_code, :status_code, :home_visit_stage_code,
                     :placement_type_code, :visited_by_user_id, :summary, :findings, :followup_required, :next_visit_due)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':employee_id'           => (int)($data['employee_id'] ?? 0),
            ':placement_id'          => ($data['placement_id'] ?? '') === '' ? null : (int)$data['placement_id'],
            ':visit_date'            => ($data['visit_date'] ?? '') ?: null,
            ':visit_type_code'       => (int)($data['visit_type_code'] ?? 0),
            ':status_code'           => (int)($data['status_code'] ?? 0),
            ':home_visit_stage_code' => (int)($data['home_visit_stage_code'] ?? 0),
            ':placement_type_code'   => ($data['placement_type_code'] ?? '') === '' ? null : (int)$data['placement_type_code'],
            ':visited_by_user_id'    => ($data['visited_by_user_id'] ?? '') === '' ? null : (int)$data['visited_by_user_id'],
            ':summary'               => ($data['summary'] ?? '') === '' ? null : (string)$data['summary'],
            ':findings'              => ($data['findings'] ?? '') === '' ? null : (string)$data['findings'],
            ':followup_required'     => !empty($data['followup_required']) ? 1 : 0,
            ':next_visit_due'        => ($data['next_visit_due'] ?? '') ?: null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE home_visits
                   SET employee_id = :employee_id,
                       placement_id = :placement_id,
                       visit_date = :visit_date,
                       visit_type_code = :visit_type_code,
                       status_code = :status_code,
                       home_visit_stage_code = :home_visit_stage_code,
                       placement_type_code = :placement_type_code,
                       visited_by_user_id = :visited_by_user_id,
                       summary = :summary,
                       findings = :findings,
                       followup_required = :followup_required,
                       next_visit_due = :next_visit_due
                 WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id'                    => $id,
            ':employee_id'           => (int)($data['employee_id'] ?? 0),
            ':placement_id'          => ($data['placement_id'] ?? '') === '' ? null : (int)$data['placement_id'],
            ':visit_date'            => ($data['visit_date'] ?? '') ?: null,
            ':visit_type_code'       => (int)($data['visit_type_code'] ?? 0),
            ':status_code'           => (int)($data['status_code'] ?? 0),
            ':home_visit_stage_code' => (int)($data['home_visit_stage_code'] ?? 0),
            ':placement_type_code'   => ($data['placement_type_code'] ?? '') === '' ? null : (int)$data['placement_type_code'],
            ':visited_by_user_id'    => ($data['visited_by_user_id'] ?? '') === '' ? null : (int)$data['visited_by_user_id'],
            ':summary'               => ($data['summary'] ?? '') === '' ? null : (string)$data['summary'],
            ':findings'              => ($data['findings'] ?? '') === '' ? null : (string)$data['findings'],
            ':followup_required'     => !empty($data['followup_required']) ? 1 : 0,
            ':next_visit_due'        => ($data['next_visit_due'] ?? '') ?: null,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM home_visits WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT hv.*,
                       e.first_name, e.last_name, e.passport_number AS employee_passport,
                       sc.name_he AS status_name,
                       tc.name_he AS type_name,
                       stg.name_he AS stage_name,
                       pt.name_he  AS placement_type_name,
                       pl.employer_id,
                       em.first_name AS employer_first_name,
                       em.last_name  AS employer_last_name,
                       em.id_number  AS employer_id_number,
                       u.email AS visited_by_email
                  FROM home_visits hv
             LEFT JOIN employees e ON e.id = hv.employee_id
             LEFT JOIN home_visit_status_codes sc ON sc.home_visit_status_code = hv.status_code
             LEFT JOIN home_visit_type_codes   tc ON tc.home_visit_type_code   = hv.visit_type_code
             LEFT JOIN home_visit_stage_codes stg ON stg.home_visit_stage_code = hv.home_visit_stage_code
             LEFT JOIN placement_type_codes    pt ON pt.placement_type_code    = hv.placement_type_code
             LEFT JOIN placements pl ON pl.id = hv.placement_id
             LEFT JOIN employers em ON em.id = pl.employer_id
             LEFT JOIN users u ON u.id = hv.visited_by_user_id
                 WHERE hv.id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function all(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['employee_id'])) {
            $where[] = 'hv.employee_id = :employee_id';
            $params[':employee_id'] = (int)$filters['employee_id'];
        }

        // status: מספרי או 'overdue'
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'overdue') {
                $where[] = 'hv.next_visit_due IS NOT NULL AND hv.next_visit_due < CURDATE()';
            } else {
                $where[] = 'hv.status_code = :status_code';
                $params[':status_code'] = (int)$filters['status'];
            }
        }

        if (!empty($filters['type'])) {
            $where[] = 'hv.visit_type_code = :visit_type_code';
            $params[':visit_type_code'] = (int)$filters['type'];
        }

        if (!empty($filters['stage'])) {
            $where[] = 'hv.home_visit_stage_code = :home_visit_stage_code';
            $params[':home_visit_stage_code'] = (int)$filters['stage'];
        }

        if (!empty($filters['due_until'])) {
            $where[] = 'hv.next_visit_due IS NOT NULL AND hv.next_visit_due <= :due_until';
            $params[':due_until'] = $filters['due_until'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'hv.visit_date >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'hv.visit_date <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        if (!empty($filters['followup_only']) && (string)$filters['followup_only'] === '1') {
            $where[] = 'hv.followup_required = 1';
        }

        if (!empty($filters['q'])) {
            $where[] = '(e.first_name LIKE :q OR e.last_name LIKE :q OR hv.summary LIKE :q OR hv.findings LIKE :q)';
            $params[':q'] = '%' . $filters['q'] . '%';
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

        $sql = "SELECT hv.*,
                       e.first_name, e.last_name, e.passport_number AS employee_passport,
                       sc.name_he AS status_name,
                       tc.name_he AS type_name,
                       stg.name_he AS stage_name,
                       pt.name_he  AS placement_type_name
                  FROM home_visits hv
             LEFT JOIN employees e ON e.id = hv.employee_id
             LEFT JOIN home_visit_status_codes sc ON sc.home_visit_status_code = hv.status_code
             LEFT JOIN home_visit_type_codes   tc ON tc.home_visit_type_code   = hv.visit_type_code
             LEFT JOIN home_visit_stage_codes stg ON stg.home_visit_stage_code = hv.home_visit_stage_code
             LEFT JOIN placement_type_codes    pt ON pt.placement_type_code    = hv.placement_type_code
                   $whereSql
              ORDER BY hv.visit_date DESC, hv.id DESC
                 LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count(array $filters = []): int
    {
        $where  = [];
        $params = [];

        if (!empty($filters['employee_id'])) {
            $where[] = 'hv.employee_id = :employee_id';
            $params[':employee_id'] = (int)$filters['employee_id'];
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'overdue') {
                $where[] = 'hv.next_visit_due IS NOT NULL AND hv.next_visit_due < CURDATE()';
            } else {
                $where[] = 'hv.status_code = :status_code';
                $params[':status_code'] = (int)$filters['status'];
            }
        }

        if (!empty($filters['type'])) {
            $where[] = 'hv.visit_type_code = :visit_type_code';
            $params[':visit_type_code'] = (int)$filters['type'];
        }

        if (!empty($filters['stage'])) {
            $where[] = 'hv.home_visit_stage_code = :home_visit_stage_code';
            $params[':home_visit_stage_code'] = (int)$filters['stage'];
        }

        if (!empty($filters['due_until'])) {
            $where[] = 'hv.next_visit_due IS NOT NULL AND hv.next_visit_due <= :due_until';
            $params[':due_until'] = $filters['due_until'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'hv.visit_date >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'hv.visit_date <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        if (!empty($filters['followup_only']) && (string)$filters['followup_only'] === '1') {
            $where[] = 'hv.followup_required = 1';
        }

        if (!empty($filters['q'])) {
            $where[] = '(e.first_name LIKE :q OR e.last_name LIKE :q OR hv.summary LIKE :q OR hv.findings LIKE :q)';
            $params[':q'] = '%' . $filters['q'] . '%';
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

        $sql = "SELECT COUNT(*) AS cnt
                  FROM home_visits hv
             LEFT JOIN employees e ON e.id = hv.employee_id
                   $whereSql";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int)($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);
    }
}
