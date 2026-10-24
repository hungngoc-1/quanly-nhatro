<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class RoomModel
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
            SELECT r.*,
                   c.id AS active_contract_id,
                   c.contract_code,
                   t.full_name AS current_tenant_name,
                   t.phone AS current_tenant_phone
            FROM rooms r
            LEFT JOIN contracts c ON c.room_id = r.id AND c.status = 'active'
            LEFT JOIN tenants t ON t.id = c.tenant_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND r.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['floor'])) {
            $sql .= " AND r.floor = :floor";
            $params['floor'] = (int) $filters['floor'];
        }

        if (!empty($filters['keyword'])) {
            $sql .= " AND (r.room_number LIKE :kw1 OR r.amenities LIKE :kw2 OR t.full_name LIKE :kw3)";
            $kw = '%' . trim($filters['keyword']) . '%';
            $params['kw1'] = $kw;
            $params['kw2'] = $kw;
            $params['kw3'] = $kw;
        }

        $sql .= " ORDER BY r.floor ASC, r.room_number ASC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db()->prepare("SELECT * FROM rooms WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByRoomNumber(string $roomNumber, ?int $excludeId = null): ?array
    {
        $sql = "SELECT * FROM rooms WHERE room_number = :room_number";
        $params = ['room_number' => trim($roomNumber)];
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
            INSERT INTO rooms (room_number, floor, area, price, max_occupants, status, amenities, description)
            VALUES (:room_number, :floor, :area, :price, :max_occupants, :status, :amenities, :description)
        ");
        $stmt->execute([
            'room_number'   => strtoupper(trim($data['room_number'])),
            'floor'         => (int) ($data['floor'] ?? 1),
            'area'          => (float) ($data['area'] ?? 20),
            'price'         => (float) ($data['price'] ?? 0),
            'max_occupants' => (int) ($data['max_occupants'] ?? 2),
            'status'        => $data['status'] ?? 'available',
            'amenities'     => trim($data['amenities'] ?? ''),
            'description'   => trim($data['description'] ?? ''),
        ]);
        return (int) $this->db()->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db()->prepare("
            UPDATE rooms
            SET room_number = :room_number,
                floor = :floor,
                area = :area,
                price = :price,
                max_occupants = :max_occupants,
                status = :status,
                amenities = :amenities,
                description = :description
            WHERE id = :id
        ");
        return $stmt->execute([
            'id'            => $id,
            'room_number'   => strtoupper(trim($data['room_number'])),
            'floor'         => (int) ($data['floor'] ?? 1),
            'area'          => (float) ($data['area'] ?? 20),
            'price'         => (float) ($data['price'] ?? 0),
            'max_occupants' => (int) ($data['max_occupants'] ?? 2),
            'status'        => $data['status'] ?? 'available',
            'amenities'     => trim($data['amenities'] ?? ''),
            'description'   => trim($data['description'] ?? ''),
        ]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db()->prepare("UPDATE rooms SET status = :status WHERE id = :id");
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db()->prepare("DELETE FROM rooms WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getCountsByStatus(): array
    {
        $stmt = $this->db()->query("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) AS available,
                SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) AS occupied,
                SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) AS maintenance
            FROM rooms
        ");
        $row = $stmt->fetch() ?: [];
        return [
            'total'       => (int) ($row['total'] ?? 0),
            'available'   => (int) ($row['available'] ?? 0),
            'occupied'    => (int) ($row['occupied'] ?? 0),
            'maintenance' => (int) ($row['maintenance'] ?? 0),
        ];
    }
}
