<?php

namespace App\Models;

use App\Config\Database;
use App\Helpers\CoreHelper;
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
        $buildingId = array_key_exists('building_id', $filters)
            ? ($filters['building_id'] !== null && $filters['building_id'] !== '' ? (int) $filters['building_id'] : null)
            : CoreHelper::currentBuildingId();

        $sql = "
            SELECT t.*,
                   u.username AS login_username,
                   r.room_number AS current_room,
                   r.building_id,
                   b.building_name,
                   c.contract_code AS active_contract_code,
                   c.deposit_date AS contract_deposit_date,
                   c.start_date AS contract_start_date,
                   c.end_date AS contract_end_date
            FROM tenants t
            LEFT JOIN users u ON u.id = t.user_id
            LEFT JOIN contracts c ON c.tenant_id = t.id AND c.status = 'active'
            LEFT JOIN rooms r ON r.id = c.room_id
            LEFT JOIN buildings b ON b.id = r.building_id
            WHERE 1=1
        ";
        $params = [];

        if ($buildingId !== null && $buildingId > 0) {
            $sql .= " AND (r.building_id = :building_id OR c.id IS NULL)";
            $params['building_id'] = $buildingId;
        }

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
            SELECT t.*,
                   r.room_number, r.floor, r.area, r.building_id,
                   b.building_name,
                   rt.type_name AS room_type_name,
                   c.id AS contract_id,
                   c.contract_code, c.monthly_rent, c.service_fee,
                   c.deposit_date, c.start_date, c.end_date, c.deposit_amount,
                   c.deposit_status, c.signature_type, c.signature_status, c.scan_file,
                   c.renewal_status, c.renewal_months, c.renewal_responded_at,
                   DATEDIFF(c.end_date, CURDATE()) AS days_left
            FROM tenants t
            LEFT JOIN contracts c ON c.tenant_id = t.id AND c.status = 'active'
            LEFT JOIN rooms r ON r.id = c.room_id
            LEFT JOIN buildings b ON b.id = r.building_id
            LEFT JOIN room_types rt ON rt.id = r.room_type_id
            WHERE t.user_id = :user_id
            ORDER BY c.id DESC
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
