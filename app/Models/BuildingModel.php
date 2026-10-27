<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class BuildingModel
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

    /**
     * Lấy danh sách tất cả các tòa nhà kèm thống kê số phòng, công suất thuê và Quản lý phụ trách
     */
    public function getAllWithStats(): array
    {
        $sql = "
            SELECT b.*,
                   u.full_name AS manager_name,
                   u.username AS manager_username,
                   u.phone AS manager_phone,
                   COUNT(DISTINCT r.id) AS total_rooms,
                   COUNT(DISTINCT CASE WHEN r.status = 'occupied' THEN r.id END) AS occupied_rooms,
                   COUNT(DISTINCT CASE WHEN r.status = 'available' THEN r.id END) AS available_rooms,
                   COUNT(DISTINCT CASE WHEN r.status = 'maintenance' THEN r.id END) AS maintenance_rooms
            FROM buildings b
            LEFT JOIN users u ON u.id = b.manager_id
            LEFT JOIN rooms r ON r.building_id = b.id
            GROUP BY b.id
            ORDER BY b.id ASC
        ";
        return $this->db()->query($sql)->fetchAll();
    }

    public function getAll(): array
    {
        $stmt = $this->db()->query("
            SELECT b.*, u.full_name AS manager_name, u.username AS manager_username
            FROM buildings b
            LEFT JOIN users u ON u.id = b.manager_id
            ORDER BY b.id ASC
        ");
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db()->prepare("
            SELECT b.*, u.full_name AS manager_name, u.username AS manager_username
            FROM buildings b
            LEFT JOIN users u ON u.id = b.manager_id
            WHERE b.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db()->prepare("
            INSERT INTO buildings (building_code, building_name, address, manager_id, total_floors, description, status)
            VALUES (:building_code, :building_name, :address, :manager_id, :total_floors, :description, :status)
        ");
        $stmt->execute([
            'building_code' => strtoupper(trim($data['building_code'] ?? 'BLD-NEW')),
            'building_name' => trim($data['building_name'] ?? ''),
            'address'       => trim($data['address'] ?? ''),
            'manager_id'    => !empty($data['manager_id']) ? (int) $data['manager_id'] : null,
            'total_floors'  => (int) ($data['total_floors'] ?? 5),
            'description'   => trim($data['description'] ?? ''),
            'status'        => $data['status'] ?? 'active',
        ]);
        $buildingId = (int) $this->db()->lastInsertId();

        if (!empty($data['manager_id'])) {
            $stmtUser = $this->db()->prepare("UPDATE users SET building_id = :b_id WHERE id = :u_id");
            $stmtUser->execute(['b_id' => $buildingId, 'u_id' => (int) $data['manager_id']]);
        }

        return $buildingId;
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db()->prepare("
            UPDATE buildings
            SET building_code = :building_code,
                building_name = :building_name,
                address = :address,
                manager_id = :manager_id,
                total_floors = :total_floors,
                description = :description,
                status = :status
            WHERE id = :id
        ");
        $ok = $stmt->execute([
            'id'            => $id,
            'building_code' => strtoupper(trim($data['building_code'] ?? '')),
            'building_name' => trim($data['building_name'] ?? ''),
            'address'       => trim($data['address'] ?? ''),
            'manager_id'    => !empty($data['manager_id']) ? (int) $data['manager_id'] : null,
            'total_floors'  => (int) ($data['total_floors'] ?? 5),
            'description'   => trim($data['description'] ?? ''),
            'status'        => $data['status'] ?? 'active',
        ]);

        if (!empty($data['manager_id'])) {
            $stmtUser = $this->db()->prepare("UPDATE users SET building_id = :b_id WHERE id = :u_id");
            $stmtUser->execute(['b_id' => $id, 'u_id' => (int) $data['manager_id']]);
        }

        return $ok;
    }
}
