<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class TenantModel
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

    public function getAll(array $filters = []): array
    {
        $sql = "
            SELECT t.*,
                   u.username AS login_username,
                   r.room_number AS current_room,
                   c.contract_code AS active_contract_code
            FROM tenants t
            LEFT JOIN users u ON u.id = t.user_id
            LEFT JOIN contracts c ON c.tenant_id = t.id AND c.status = 'active'
            LEFT JOIN rooms r ON r.id = c.room_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND t.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['keyword'])) {
            $sql .= " AND (t.full_name LIKE :kw1 OR t.cccd LIKE :kw2 OR t.phone LIKE :kw3 OR r.room_number LIKE :kw4)";
            $kw = '%' . trim($filters['keyword']) . '%';
            $params['kw1'] = $kw;
            $params['kw2'] = $kw;
            $params['kw3'] = $kw;
            $params['kw4'] = $kw;
        }

        $sql .= " ORDER BY t.id DESC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db()->prepare("SELECT * FROM tenants WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db()->prepare("
            SELECT t.*, r.room_number, r.floor, r.area, c.contract_code, c.monthly_rent, c.start_date, c.end_date, c.deposit_amount
            FROM tenants t
            LEFT JOIN contracts c ON c.tenant_id = t.id AND c.status = 'active'
            LEFT JOIN rooms r ON r.id = c.room_id
            WHERE t.user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByCccd(string $cccd, ?int $excludeId = null): ?array
    {
        $sql = "SELECT * FROM tenants WHERE cccd = :cccd";
        $params = ['cccd' => trim($cccd)];
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db()->prepare("
            INSERT INTO tenants (user_id, full_name, cccd, phone, email, dob, gender, hometown, occupation, emergency_contact, status)
            VALUES (:user_id, :full_name, :cccd, :phone, :email, :dob, :gender, :hometown, :occupation, :emergency_contact, :status)
        ");
        $stmt->execute([
            'user_id'           => !empty($data['user_id']) ? (int) $data['user_id'] : null,
            'full_name'         => trim($data['full_name']),
            'cccd'              => trim($data['cccd']),
            'phone'             => trim($data['phone']),
            'email'             => !empty($data['email']) ? trim($data['email']) : null,
            'dob'               => !empty($data['dob']) ? $data['dob'] : null,
            'gender'            => $data['gender'] ?? 'Nam',
            'hometown'          => trim($data['hometown'] ?? ''),
            'occupation'        => trim($data['occupation'] ?? ''),
            'emergency_contact' => trim($data['emergency_contact'] ?? ''),
            'status'            => $data['status'] ?? 'staying',
        ]);
        return (int) $this->db()->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db()->prepare("
            UPDATE tenants
            SET full_name = :full_name,
                cccd = :cccd,
                phone = :phone,
                email = :email,
                dob = :dob,
                gender = :gender,
                hometown = :hometown,
                occupation = :occupation,
                emergency_contact = :emergency_contact,
                status = :status
            WHERE id = :id
        ");
        return $stmt->execute([
            'id'                => $id,
            'full_name'         => trim($data['full_name']),
            'cccd'              => trim($data['cccd']),
            'phone'             => trim($data['phone']),
            'email'             => !empty($data['email']) ? trim($data['email']) : null,
            'dob'               => !empty($data['dob']) ? $data['dob'] : null,
            'gender'            => $data['gender'] ?? 'Nam',
            'hometown'          => trim($data['hometown'] ?? ''),
            'occupation'        => trim($data['occupation'] ?? ''),
            'emergency_contact' => trim($data['emergency_contact'] ?? ''),
            'status'            => $data['status'] ?? 'staying',
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db()->prepare("DELETE FROM tenants WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
