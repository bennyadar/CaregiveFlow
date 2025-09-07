<?php
class Employer {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    private array $fillable = [
        'id_type_code','id_number','passport_number',
        'last_name','first_name','gender_code',
        'phone','phone_prefix_il','phone_number_il','phone_alt','email',
        'birth_date','birth_year',
        'city_code','street_code','street_name_he','house_no','apartment','zipcode',
        'foreign_country_code',
        'notes','company_name','contact_person','contact_name','contact_phone'
    ];

    public function all(string $q = '', int $limit = 20, int $offset = 0): array {
        if ($q) {
            $sql = "SELECT * FROM employers
                    WHERE id_number LIKE :q OR first_name LIKE :q OR last_name LIKE :q
                    ORDER BY id DESC LIMIT :limit OFFSET :offset";
            $st = $this->pdo->prepare($sql);
            $like = "%{$q}%";
            $st->bindValue(':q', $like, PDO::PARAM_STR);
        } else {
            $st = $this->pdo->prepare("SELECT * FROM employers ORDER BY id DESC LIMIT :limit OFFSET :offset");
        }
        $st->bindValue(':limit', $limit, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count(string $q = ''): int {
        if ($q) {
            $st = $this->pdo->prepare("SELECT COUNT(*) FROM employers
                                       WHERE id_number LIKE ? OR first_name LIKE ? OR last_name LIKE ?");
            $like = "%{$q}%";
            $st->execute([$like, $like, $like]);
            return (int)$st->fetchColumn();
        }
        return (int)$this->pdo->query("SELECT COUNT(*) FROM employers")->fetchColumn();
    }

    public function find(int $id): ?array {
        $st = $this->pdo->prepare("SELECT * FROM employers WHERE id = ?");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $d): int {
        [$cols, $vals, $params] = $this->buildInsert($d);
        $sql = "INSERT INTO employers (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $d): void {
        [$sets, $params] = $this->buildUpdate($d);
        if (!$sets) return;
        $sql = "UPDATE employers SET " . implode(',', $sets) . " WHERE id = :id";
        $params[':id'] = $id;
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
    }

    public function delete(int $id): void {
        $st = $this->pdo->prepare("DELETE FROM employers WHERE id = ?");
        $st->execute([$id]);
    }

    private function normalize($k, $v) {
        $v = is_string($v) ? trim($v) : $v;
        return ($v === '' ? null : $v);
    }
    private function buildInsert(array $d): array {
        $cols=[]; $vals=[]; $params=[];
        foreach ($this->fillable as $k) {
            if (array_key_exists($k, $d)) {
                $cols[]=$k; $vals[]=':'.$k; $params[':'.$k]=$this->normalize($k,$d[$k]);
            }
        }
        // שדות חובה מינימליים
        foreach (['id_type_code','id_number','first_name','last_name'] as $req) {
            if (!in_array($req, $cols, true)) {
                $cols[]=$req; $vals[]=':'.$req; $params[':'.$req]=trim((string)($d[$req]??''));
            }
        }
        return [$cols,$vals,$params];
    }
    private function buildUpdate(array $d): array {
        $sets=[]; $params=[];
        foreach ($this->fillable as $k) {
            if (array_key_exists($k, $d)) {
                $sets[]="$k = :$k";
                $params[':'.$k]=$this->normalize($k,$d[$k]);
            }
        }
        return [$sets,$params];
    }
}
