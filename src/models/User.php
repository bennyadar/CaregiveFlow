<?php
class User {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function findByUsername(string $username): ?array {
        $st = $this->pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $st->execute([$username]);
        $row = $st->fetch();
        return $row ?: null;
    }
    public function create(string $username, string $password, string $role = 'admin', ?string $display_name = null, ?string $email = null): int {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $st = $this->pdo->prepare("INSERT INTO users (username, password_hash, role, display_name, email) VALUES (?,?,?,?,?)");
        $st->execute([$username, $hash, $role, $display_name, $email]);
        return (int)$this->pdo->lastInsertId();
    }
    public function count(): int {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }
}
