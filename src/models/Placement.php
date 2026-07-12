<?php
class Placement {
    private PDO $pdo;
    private array $cols;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->cols = $this->detectColumns();
    }

    private function detectColumns(): array {
        $map = [
            'employee_id' => ['employee_id', 'emp_id', 'worker_id'],
            'employer_id' => ['employer_id', 'client_id', 'empr_id'],
            'start'       => ['start_date', 'start_at', 'start', 'from_date', 'placement_start_date'],
            'end'         => ['end_date', 'end_at', 'end', 'to_date', 'placement_end_date'],
            'notes'       => ['notes', 'remarks', 'comment'],
            'id'          => ['id', 'placement_id']
        ];
        $found = [];
        foreach ($map as $key => $cands) {
            $found[$key] = $this->firstExistingColumn('placements', $cands);
        }
        if (!$found['employee_id']) $found['employee_id'] = 'employee_id';
        if (!$found['employer_id']) $found['employer_id'] = 'employer_id';
        if (!$found['start'])       $found['start'] = 'start_date';
        if (!$found['end'])         $found['end']   = 'end_date';
        if (!$found['id'])          $found['id']    = 'id';
        return $found;
    }

    private function firstExistingColumn(string $table, array $candidates): ?string {
        $in  = implode(',', array_fill(0, count($candidates), '?'));
        $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND COLUMN_NAME IN ($in)
                LIMIT 1";
        $params = array_merge([$table], $candidates);
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row['COLUMN_NAME'] ?? null;
    }

    public function all(string $q = '', int $limit = 20, int $offset = 0): array {
        $idc = $this->cols['id']; $ec = $this->cols['employee_id']; $er = $this->cols['employer_id'];
        $sc = $this->cols['start']; $en = $this->cols['end'];
        $sql = "SELECT p.*, 
                       e.first_name AS emp_first, e.last_name AS emp_last, e.passport_number AS emp_passport,
                       er.first_name AS employer_first, er.last_name AS employer_last, er.id_number AS employer_idnum
                FROM placements p
                JOIN employees e  ON e.id = p.$ec
                JOIN employers er ON er.id = p.$er";
        $where = "";
        $params = [];
        if ($q !== '') {
            $where = " WHERE e.passport_number LIKE :like OR e.first_name LIKE :like OR e.last_name LIKE :like OR er.first_name LIKE :like OR er.last_name LIKE :like";
            $params[':like'] = "%{$q}%";
        }
        $order = " ORDER BY p.$idc DESC LIMIT :limit OFFSET :offset";
        $st = $this->pdo->prepare($sql . $where . $order);
        if ($q !== '') $st->bindValue(':like', $params[':like'], PDO::PARAM_STR);
        $st->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $st->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public function count(string $q = ''): int {
        $ec = $this->cols['employee_id']; $er = $this->cols['employer_id'];
        if ($q !== '') {
            $st = $this->pdo->prepare("SELECT COUNT(*) FROM placements p
                JOIN employees e ON e.id = p.$ec
                JOIN employers er ON er.id = p.$er
                WHERE e.passport_number LIKE ? OR e.first_name LIKE ? OR e.last_name LIKE ? OR er.first_name LIKE ? OR er.last_name LIKE ?");
            $like = "%{$q}%";
            $st->execute([$like,$like,$like,$like,$like]);
            return (int)$st->fetchColumn();
        }
        return (int)$this->pdo->query("SELECT COUNT(*) FROM placements")->fetchColumn();
    }

    public function find(int $id): ?array {
        $idc = $this->cols['id'];
        $st = $this->pdo->prepare("SELECT * FROM placements WHERE $idc = ?");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function countActiveByEmployer(int $id): int {
        $sc = $this->cols['start']; $en = $this->cols['end'];
        $idc = $this->cols['employer_id'];
        $sql = $this->pdo->prepare("SELECT COUNT(*) FROM placements WHERE $idc = ? 
                                   AND $sc <= CURDATE()
                                   AND ( $en IS NULL OR $en >= CURDATE() )");
        $sql->execute([$id]);
        return (int)$sql->fetchColumn();
    }    

    public function countActiveByEmployee(int $id): int {
        $sc = $this->cols['start']; $en = $this->cols['end'];
        $idc = $this->cols['employee_id'];
        $sql = $this->pdo->prepare("SELECT COUNT(*) FROM placements WHERE $idc = ? 
                                   AND $sc <= CURDATE()
                                   AND ( $en IS NULL OR $en >= CURDATE() )");
        $sql->execute([$id]);
        return (int)$sql->fetchColumn();
    }   

    public function create(array $d): int {
        $ec = $this->cols['employee_id']; $er = $this->cols['employer_id'];
        $sc = $this->cols['start']; $en = $this->cols['end']; $nt = $this->cols['notes'] ?? null;

        $cols = [$ec,$er,$sc,$en]; $vals = [':emp',':empr',':start',':end'];
        if ($nt) { $cols[] = $nt; $vals[] = ':notes'; }

        $sql = "INSERT INTO placements (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
        $st = $this->pdo->prepare($sql);
        $st->bindValue(':emp',  (int)$d['employee_id'], PDO::PARAM_INT);
        $st->bindValue(':empr', (int)$d['employer_id'], PDO::PARAM_INT);
        $st->bindValue(':start', $d['start_date'] ?: null);
        $st->bindValue(':end',   $d['end_date'] ?: null);
        if ($nt) $st->bindValue(':notes', $d['notes'] ?? null);
        $st->execute();
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $d): void {
        $idc = $this->cols['id'];
        $ec = $this->cols['employee_id']; $er = $this->cols['employer_id'];
        $sc = $this->cols['start']; $en = $this->cols['end']; $nt = $this->cols['notes'] ?? null;

        $set = ["$ec = :emp", "$er = :empr", "$sc = :start", "$en = :end"];
        if ($nt) $set[] = "$nt = :notes";

        $sql = "UPDATE placements SET " . implode(',', $set) . " WHERE $idc = :id";
        $st = $this->pdo->prepare($sql);
        $st->bindValue(':emp',  (int)$d['employee_id'], PDO::PARAM_INT);
        $st->bindValue(':empr', (int)$d['employer_id'], PDO::PARAM_INT);
        $st->bindValue(':start', $d['start_date'] ?: null);
        $st->bindValue(':end',   $d['end_date'] ?: null);
        if ($nt) $st->bindValue(':notes', $d['notes'] ?? null);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
    }

    public function delete(int $id): void {
        $idc = $this->cols['id'];
        $st = $this->pdo->prepare("DELETE FROM placements WHERE $idc = ?");
        $st->execute([$id]);
    }

    public function hasOverlap(int $employee_id, ?string $start_date, ?string $end_date, ?int $exclude_id = null): bool {
        $idc = $this->cols['id']; $ec = $this->cols['employee_id']; $sc = $this->cols['start']; $en = $this->cols['end'];
        $sql = "SELECT COUNT(*) FROM placements
                WHERE $ec = :eid
                  AND (:start <= IFNULL($en, '9999-12-31'))
                  AND $sc <= IFNULL(:end, '9999-12-31')";
        if ($exclude_id) $sql .= " AND $idc <> :id";
        $st = $this->pdo->prepare($sql);
        $st->bindValue(':eid', $employee_id, PDO::PARAM_INT);
        $st->bindValue(':start', $start_date ?: '0001-01-01');
        $st->bindValue(':end', $end_date ?: '9999-12-31');
        if ($exclude_id) $st->bindValue(':id', $exclude_id, PDO::PARAM_INT);
        $st->execute();
        return ((int)$st->fetchColumn()) > 0;
    }

    public function activeNowCount(): int {
        $sc = $this->cols['start']; $en = $this->cols['end'];
        $sql = "SELECT COUNT(*) FROM placements
                WHERE $sc <= CURDATE()
                  AND ( $en IS NULL OR $en >= CURDATE() )";
        return (int)$this->pdo->query($sql)->fetchColumn();
    }

    public function monthsActiveCounts(int $monthsBack = 12): array {
        $sc = $this->cols['start']; $en = $this->cols['end'];
        $data = [];
        $start = new DateTime(date('Y-m-01', strtotime('-'.($monthsBack-1).' months')));
        for ($i=0; $i<$monthsBack; $i++) {
            $mStart = clone $start;
            $mStart->modify('+' . $i . ' month');
            $mEnd = clone $mStart;
            $mEnd->modify('last day of this month');
            $st = $this->pdo->prepare("SELECT COUNT(*) FROM placements
                WHERE $sc <= :mend
                  AND ( $en IS NULL OR $en >= :mstart )");
            $st->execute([
                ':mend' => $mEnd->format('Y-m-d'),
                ':mstart' => $mStart->format('Y-m-d')
            ]);
            $data[] = ['month' => $mStart->format('Y-m'), 'count' => (int)$st->fetchColumn()];
        }
        return $data;
    }

    public function columns(): array { return $this->cols; }
}
