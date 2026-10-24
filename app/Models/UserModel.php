<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class UserModel
{
    private ?PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db;
    }

    private function db(): PDO
    {
        if ($this->db === null) {
            $this->db = Database::getConnection();
        }
        return $this->db;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db()->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => trim($username)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db()->prepare("SELECT id, username, full_name, email, phone, role, status, created_at FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function authenticate(string $username, string $password): ?array
    {
        $user = $this->findByUsername($username);
        if (!$user || $user['status'] !== 'active') {
            return null;
        }

        if (password_verify($password, $user['password'])) {
            unset($user['password']);
            return $user;
        }

        return null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db()->prepare("
            INSERT INTO users (username, password, full_name, email, phone, role, status)
            VALUES (:username, :password, :full_name, :email, :phone, :role, :status)
        ");
        $stmt->execute([
            'username'  => trim($data['username']),
            'password'  => password_hash($data['password'], PASSWORD_BCRYPT),
            'full_name' => trim($data['full_name']),
            'email'     => $data['email'] ?? null,
            'phone'     => $data['phone'] ?? null,
            'role'      => $data['role'] ?? 'tenant',
            'status'    => $data['status'] ?? 'active',
        ]);
        return (int) $this->db()->lastInsertId();
    }

    public function getAll(): array
    {
        $stmt = $this->db()->query("SELECT id, username, full_name, email, phone, role, status, created_at FROM users ORDER BY id ASC");
        return $stmt->fetchAll();
    }
}
