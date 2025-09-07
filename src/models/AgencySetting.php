<?php
class AgencySetting {
    private PDO $pdo;
    public function __construct(PDO $pdo){ $this->pdo = $pdo; }

    public function all(): array {
        $sql = "SELECT id, agency_name, bureau_number,
               CorporateNumber, LicenseNumber, Address,
               phone, CellNumber, email, contact_person,
               house_no, zipcode, OwnerID, notes, updated_at
        FROM agency_settings
        ORDER BY id ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array {
        $st = $this->pdo->prepare(
            "SELECT id, agency_name, bureau_number,
                    CorporateNumber, LicenseNumber, Address,
                    phone, CellNumber, email, contact_person,
                    house_no, zipcode, OwnerID, notes, updated_at
            FROM agency_settings
            WHERE id = ?"
        );
        $st->execute([$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    // הטבלה אינה AUTO_INCREMENT → הבא בתור (TINYINT)
    private function nextId(): int {
        $row = $this->pdo->query("SELECT COALESCE(MAX(id),0) AS mx FROM agency_settings")->fetch(PDO::FETCH_ASSOC);
        $nxt = (int)($row['mx'] ?? 0) + 1;
        if ($nxt <= 0) $nxt = 1;
        if ($nxt > 255) throw new RuntimeException('No free IDs left in agency_settings (tinyint).');
        return $nxt;
    }

    public function create(array $d): int {
        $id = isset($d['id']) && $d['id'] !== '' ? (int)$d['id'] : $this->nextId();
        $st = $this->pdo->prepare(
            "INSERT INTO agency_settings
            (id, agency_name, bureau_number,
            CorporateNumber, LicenseNumber, Address,
            phone, CellNumber, email, contact_person,
            house_no, zipcode, OwnerID, notes)
            VALUES
            (:id, :agency_name, :bureau_number,
            :CorporateNumber, :LicenseNumber, :Address,
            :phone, :CellNumber, :email, :contact_person,
            :house_no, :zipcode, :OwnerID, :notes)"
        );

        $st->execute([
            ':id'             => $id,
            ':agency_name'    => $d['agency_name'] ?? '',
            ':bureau_number'  => self::digits($d['bureau_number'] ?? null, 20),
            ':CorporateNumber'=> trim((string)($d['CorporateNumber'] ?? '')), // עלול לכלול אפסים מובילים/תווי מפריד
            ':LicenseNumber'  => trim((string)($d['LicenseNumber'] ?? '')),
            ':Address'        => trim((string)($d['Address'] ?? '')),
            ':phone'          => self::digits($d['phone'] ?? null, 20),
            ':CellNumber'     => self::digits($d['CellNumber'] ?? null, 20),
            ':email'          => trim((string)($d['email'] ?? '')),
            ':contact_person' => trim((string)($d['contact_person'] ?? '')),
            ':house_no'       => trim((string)($d['house_no'] ?? '')),
            ':zipcode'        => trim((string)($d['zipcode'] ?? '')),
            ':OwnerID'        => self::digits($d['OwnerID'] ?? null, 9),
            ':notes'          => trim((string)($d['notes'] ?? '')),
        ]);
        return $id;
    }

    public function update(int $id, array $d): void {
        $sql = "UPDATE agency_settings SET
                agency_name     = :agency_name,
                bureau_number   = :bureau_number,
                CorporateNumber = :CorporateNumber,
                LicenseNumber   = :LicenseNumber,
                Address         = :Address,
                phone           = :phone,
                CellNumber      = :CellNumber,
                email           = :email,
                contact_person  = :contact_person,
                house_no        = :house_no,
                zipcode         = :zipcode,
                OwnerID         = :OwnerID,
                notes           = :notes
                WHERE id = :id";

        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':agency_name'     => $d['agency_name'] ?? '',
            ':bureau_number'   => self::digits($d['bureau_number'] ?? null, 20),
            ':CorporateNumber' => trim((string)($d['CorporateNumber'] ?? '')),
            ':LicenseNumber'   => trim((string)($d['LicenseNumber'] ?? '')),
            ':Address'         => trim((string)($d['Address'] ?? '')),
            ':phone'           => self::digits($d['phone'] ?? null, 20),
            ':CellNumber'      => self::digits($d['CellNumber'] ?? null, 20),
            ':email'           => trim((string)($d['email'] ?? '')),
            ':contact_person'  => trim((string)($d['contact_person'] ?? '')),
            ':house_no'        => trim((string)($d['house_no'] ?? '')),
            ':zipcode'         => trim((string)($d['zipcode'] ?? '')),
            ':OwnerID'         => self::digits($d['OwnerID'] ?? null, 9),
            ':notes'           => trim((string)($d['notes'] ?? '')),
            ':id'              => $id,
        ]);
    }

    public function delete(int $id): void {
        $st = $this->pdo->prepare("DELETE FROM agency_settings WHERE id=?");
        $st->execute([$id]);
    }

    private static function digits(?string $s, int $maxLen): ?string {
        if ($s === null) return null;
        $d = preg_replace('/\D+/', '', (string)$s);
        return $d === '' ? null : substr($d, 0, $maxLen);
    }
    private static function intOrNull($v){ return ($v === null || $v === '') ? null : (int)$v; }
}
