<?php
require_once __DIR__ . '/../models/User.php';

class AuthController {
    public static function login(PDO $pdo) {
        start_session();
        if (is_post()) {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $u = (new User($pdo))->findByUsername($username);
            if ($u && password_verify($password, $u['password_hash'])) {
                $_SESSION['user'] = [
                    'id' => $u['id'],
                    'username' => $u['username'],
                    'display_name' => $u['display_name'] ?? $u['username'],
                    'role' => $u['role'],
                ];
                flash('התחברת בהצלחה!');
                redirect('dashboard/index');
            } else {
                flash('שם משתמש או סיסמה שגויים.', 'danger');
            }
        }
        require __DIR__ . '/../../views/auth/login.php';
    }
    public static function logout() {
        start_session();
        $_SESSION = [];
        session_destroy();
        redirect('auth/login');
    }
}
