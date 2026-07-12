<?php
class CodeTables {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function countries(): array {
        return $this->pdo->query("SELECT country_code, name_he FROM countries ORDER BY name_he")->fetchAll();
    }
    public function genders(): array {
        return $this->pdo->query("SELECT gender_code, name_he FROM gender_codes ORDER BY gender_code")->fetchAll();
    }
    public function marital_statuses(): array {
        return $this->pdo->query("SELECT marital_status_code, name_he FROM marital_status_codes ORDER BY marital_status_code")->fetchAll();
    }
    public function employer_id_types(): array {
        return $this->pdo->query("SELECT id_type_code, name_he FROM employer_id_types ORDER BY id_type_code")->fetchAll();
    }
    public function cities(): array {
        return $this->pdo->query("SELECT city_code, name_he FROM cities ORDER BY name_he")->fetchAll();
    }
    public function streetsByCity(int $city_code): array {
        $st = $this->pdo->prepare("SELECT street_code, street_name_he FROM streets WHERE city_code = ? ORDER BY street_name_he");
        $st->execute([$city_code]);
        return $st->fetchAll();
    }
    public function passport_type_codes(): array {
        return $this->pdo->query("SELECT passport_type_code, name_he FROM passport_type_codes ORDER BY name_he")->fetchAll();
    }    
}
