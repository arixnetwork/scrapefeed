<?php
require_once __DIR__ . '/Database.php';

class Auth
{
    public static function register(string $email, string $password, string $name): array
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [false, 'Enter a valid email address.'];
        }
        if (strlen($password) < 8) {
            return [false, 'Password must be at least 8 characters.'];
        }

        $db = Database::get();
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return [false, 'An account with that email already exists.'];
        }

        $starterCredits = (int) Settings::get('starter_credits', '200');
        $stmt = $db->prepare(
            'INSERT INTO users (email, password_hash, name, role, plan, credits_remaining)
             VALUES (?, ?, ?, \'user\', \'starter\', ?)'
        );
        $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT), $name, $starterCredits]);

        return [true, (int) $db->lastInsertId()];
    }

    public static function attempt(string $email, string $password): bool
    {
        $email = strtolower(trim($email));
        $db = Database::get();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['role'] = $user['role'];
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function isAdmin(): bool
    {
        return self::check() && ($_SESSION['role'] ?? '') === 'admin';
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: /login.php');
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        if (!self::isAdmin()) {
            header('Location: /admin/login.php');
            exit;
        }
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        $stmt = Database::get()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        return $user ?: null;
    }
}

class Settings
{
    public static function get(string $key, string $default = ''): string
    {
        $stmt = Database::get()->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : $default;
    }

    public static function set(string $key, string $value): void
    {
        $stmt = Database::get()->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $stmt->execute([$key, $value]);
    }
}
