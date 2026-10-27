<?php

namespace App\Models;

use App\Config\Database;
use App\Helpers\CoreHelper;
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
                   b.building_name,
                   b.building_code,
                   rt.type_name AS room_type_name,
                   rt.type_code AS room_type_code,
                   c.id AS active_contract_id,
                   c.contract_code,
                   t.full_name AS current_tenant_name,
                   t.phone AS current_tenant_phone
            FROM rooms r
            LEFT JOIN buildings b ON b.id = r.building_id
            LEFT JOIN room_types rt ON rt.id = r.room_type_id
            LEFT JOIN contracts c ON c.room_id = r.id AND c.status = 'active'
            LEFT JOIN tenants t ON t.id = c.tenant_id
            WHERE 1=1
        ";
        $params = [];

        $buildingId = array_key_exists('building_id', $filters) && $filters['building_id'] !== ''
            ? (int) $filters['building_id']
            : CoreHelper::currentBuildingId();

        if (!empty($buildingId)) {
            $sql .= " AND r.building_id = :building_id";
            $params['building_id'] = (int) $buildingId;
        }

        if (!empty($filters['room_type_id'])) {
            $sql .= " AND r.room_type_id = :room_type_id";
            $params['room_type_id'] = (int) $filters['room_type_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND r.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['floor'])) {
            $sql .= " AND r.floor = :floor";
            $params['floor'] = (int) $filters['floor'];
        }

        if (!empty($filters['keyword'])) {
            $sql .= " AND (r.room_number LIKE :kw1 OR r.amenities LIKE :kw2 OR t.full_name LIKE :kw3 OR b.building_name LIKE :kw4 OR rt.type_name LIKE :kw5)";
            $kw = '%' . trim($filters['keyword']) . '%';
            $params['kw1'] = $kw;
            $params['kw2'] = $kw;
            $params['kw3'] = $kw;
            $params['kw4'] = $kw;
            $params['kw5'] = $kw;
        }

        $sql .= " ORDER BY r.building_id ASC, r.floor ASC, r.room_number ASC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db()->prepare("
            SELECT r.*,
                   b.building_name,
                   b.building_code,
                   rt.type_name AS room_type_name
            FROM rooms r
            LEFT JOIN buildings b ON b.id = r.building_id
            LEFT JOIN room_types rt ON rt.id = r.room_type_id
            WHERE r.id = :id
            LIMIT 1
        ");
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
        $buildingId = !empty($data['building_id']) ? (int) $data['building_id'] : (CoreHelper::currentBuildingId() ?? 1);
        $roomTypeId = !empty($data['room_type_id']) ? (int) $data['room_type_id'] : 1;

        $stmt = $this->db()->prepare("
            INSERT INTO rooms (building_id, room_type_id, room_number, floor, area, price, max_occupants, status, amenities, description)
            VALUES (:building_id, :room_type_id, :room_number, :floor, :area, :price, :max_occupants, :status, :amenities, :description)
        ");
        $stmt->execute([
            'building_id'   => $buildingId,
            'room_type_id'  => $roomTypeId,
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
        $buildingId = !empty($data['building_id']) ? (int) $data['building_id'] : (CoreHelper::currentBuildingId() ?? 1);
        $roomTypeId = !empty($data['room_type_id']) ? (int) $data['room_type_id'] : 1;

        $stmt = $this->db()->prepare("
            UPDATE rooms
            SET building_id = :building_id,
                room_type_id = :room_type_id,
                room_number = :room_number,
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
            'building_id'   => $buildingId,
            'room_type_id'  => $roomTypeId,
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

    public function getCountsByStatus(?int $buildingId = null): array
    {
        $bId = $buildingId ?? CoreHelper::currentBuildingId();
        $where = '';
        $params = [];
        if (!empty($bId)) {
            $where = ' WHERE building_id = :building_id';
            $params['building_id'] = (int) $bId;
        }

        $stmt = $this->db()->prepare("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) AS available,
                SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) AS occupied,
                SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) AS maintenance
            FROM rooms
            {$where}
        ");
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];
        return [
            'total'       => (int) ($row['total'] ?? 0),
            'available'   => (int) ($row['available'] ?? 0),
            'occupied'    => (int) ($row['occupied'] ?? 0),
            'maintenance' => (int) ($row['maintenance'] ?? 0),
        ];
    }
}
