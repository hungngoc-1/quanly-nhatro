<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class RoomTypeModel
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

    public function getAll(): array
    {
        $sql = "
            SELECT rt.*,
                   COUNT(r.id) AS room_count
            FROM room_types rt
            LEFT JOIN rooms r ON r.room_type_id = rt.id
            GROUP BY rt.id
            ORDER BY rt.base_price ASC, rt.id ASC
        ";
        return $this->db()->query($sql)->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db()->prepare("SELECT * FROM room_types WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db()->prepare("
            INSERT INTO room_types (type_code, type_name, base_price, default_area, max_occupants, default_amenities, description)
            VALUES (:type_code, :type_name, :base_price, :default_area, :max_occupants, :default_amenities, :description)
        ");
        $stmt->execute([
            'type_code'         => strtoupper(trim($data['type_code'] ?? 'RT-' . random_int(10, 99))),
            'type_name'         => trim($data['type_name'] ?? ''),
            'base_price'        => (float) ($data['base_price'] ?? 2500000),
            'default_area'      => (float) ($data['default_area'] ?? 20),
            'max_occupants'     => (int) ($data['max_occupants'] ?? 2),
            'default_amenities' => trim($data['default_amenities'] ?? ''),
            'description'       => trim($data['description'] ?? ''),
        ]);
        return (int) $this->db()->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db()->prepare("
            UPDATE room_types
            SET type_code = :type_code,
                type_name = :type_name,
                base_price = :base_price,
                default_area = :default_area,
                max_occupants = :max_occupants,
                default_amenities = :default_amenities,
                description = :description
            WHERE id = :id
        ");
        return $stmt->execute([
            'id'                => $id,
            'type_code'         => strtoupper(trim($data['type_code'] ?? '')),
            'type_name'         => trim($data['type_name'] ?? ''),
            'base_price'        => (float) ($data['base_price'] ?? 2500000),
            'default_area'      => (float) ($data['default_area'] ?? 20),
            'max_occupants'     => (int) ($data['max_occupants'] ?? 2),
            'default_amenities' => trim($data['default_amenities'] ?? ''),
            'description'       => trim($data['description'] ?? ''),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db()->prepare("DELETE FROM room_types WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
