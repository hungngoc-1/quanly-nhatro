<?php

namespace App\Models;

use App\Config\Database;
use App\Helpers\CoreHelper;
use PDO;

class BookingModel
{
    private ?PDO $db;
    private static bool $tableChecked = false;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db;
        $this->ensureTableExists();
    }

    private function db(): PDO
    {
        if ($this->db === null) {
            $this->db = Database::getConnection();
        }
        return $this->db;
    }

    /**
     * Tự động khởi tạo bảng booking_requests nếu chưa tồn tại
     */
    private function ensureTableExists(): void
    {
        if (self::$tableChecked) {
            return;
        }
        try {
            $this->db()->exec("
                CREATE TABLE IF NOT EXISTS `booking_requests` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `room_number` VARCHAR(20) NOT NULL,
                    `full_name` VARCHAR(100) NOT NULL,
                    `phone` VARCHAR(25) NOT NULL,
                    `move_in_date` DATE DEFAULT NULL,
                    `notes` TEXT DEFAULT NULL,
                    `status` ENUM('pending', 'contacted', 'cancelled') NOT NULL DEFAULT 'pending',
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
            self::$tableChecked = true;
        } catch (\Throwable $e) {
            // Bỏ qua nếu kết nối DB chưa sẵn sàng
        }
    }

    public function create(array $data): int
    {
        $stmt = $this->db()->prepare("
            INSERT INTO booking_requests (room_number, full_name, phone, move_in_date, notes, status)
            VALUES (:room_number, :full_name, :phone, :move_in_date, :notes, 'pending')
        ");
        $moveIn = !empty($data['move_in_date']) ? $data['move_in_date'] : null;
        $stmt->execute([
            'room_number'  => strtoupper(trim((string) ($data['room_number'] ?? ''))),
            'full_name'    => trim((string) ($data['full_name'] ?? '')),
            'phone'        => trim((string) ($data['phone'] ?? '')),
            'move_in_date' => $moveIn,
            'notes'        => trim((string) ($data['notes'] ?? '')),
        ]);
        return (int) $this->db()->lastInsertId();
    }

    public function getAll(?string $status = null, int $limit = 30): array
    {
        try {
            $buildingId = CoreHelper::currentBuildingId();
            $sql = "
                SELECT br.*, r.building_id, b.building_name
                FROM booking_requests br
                LEFT JOIN rooms r ON r.room_number = br.room_number
                LEFT JOIN buildings b ON b.id = r.building_id
                WHERE 1=1
            ";
            $params = [];
            if ($buildingId !== null && $buildingId > 0) {
                $sql .= " AND (r.building_id = :building_id OR r.id IS NULL)";
                $params['building_id'] = $buildingId;
            }
            if ($status !== null && $status !== '') {
                $sql .= " AND br.status = :status";
                $params['status'] = $status;
            }
            $sql .= " ORDER BY CASE WHEN br.status = 'pending' THEN 0 ELSE 1 END ASC, br.id DESC LIMIT " . (int) $limit;
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Lấy danh sách yêu cầu đặt trước đang chờ xử lý (Chưa liên hệ)
     */
    public function getPending(int $limit = 30): array
    {
        return $this->getAll('pending', $limit);
    }

    /**
     * Lấy danh sách lịch sử yêu cầu đã xử lý (Đã liên hệ / Đã hủy)
     */
    public function getHistory(int $limit = 100): array
    {
        try {
            $buildingId = CoreHelper::currentBuildingId();
            $sql = "
                SELECT br.*, r.building_id, b.building_name
                FROM booking_requests br
                LEFT JOIN rooms r ON r.room_number = br.room_number
                LEFT JOIN buildings b ON b.id = r.building_id
                WHERE br.status IN ('contacted', 'cancelled')
            ";
            $params = [];
            if ($buildingId !== null && $buildingId > 0) {
                $sql .= " AND (r.building_id = :building_id OR r.id IS NULL)";
                $params['building_id'] = $buildingId;
            }
            $sql .= " ORDER BY br.id DESC LIMIT " . (int) $limit;
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getPendingCount(): int
    {
        try {
            $buildingId = CoreHelper::currentBuildingId();
            if ($buildingId !== null && $buildingId > 0) {
                $stmt = $this->db()->prepare("
                    SELECT COUNT(*)
                    FROM booking_requests br
                    LEFT JOIN rooms r ON r.room_number = br.room_number
                    WHERE br.status = 'pending' AND (r.building_id = :building_id OR r.id IS NULL)
                ");
                $stmt->execute(['building_id' => $buildingId]);
                return (int) $stmt->fetchColumn();
            }
            $stmt = $this->db()->query("SELECT COUNT(*) FROM booking_requests WHERE status = 'pending'");
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function getHistoryCount(): int
    {
        try {
            $buildingId = CoreHelper::currentBuildingId();
            if ($buildingId !== null && $buildingId > 0) {
                $stmt = $this->db()->prepare("
                    SELECT COUNT(*)
                    FROM booking_requests br
                    LEFT JOIN rooms r ON r.room_number = br.room_number
                    WHERE br.status IN ('contacted', 'cancelled') AND (r.building_id = :building_id OR r.id IS NULL)
                ");
                $stmt->execute(['building_id' => $buildingId]);
                return (int) $stmt->fetchColumn();
            }
            $stmt = $this->db()->query("SELECT COUNT(*) FROM booking_requests WHERE status IN ('contacted', 'cancelled')");
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function updateStatus(int $id, string $status): bool
    {
        $allowed = ['pending', 'contacted', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }
        $stmt = $this->db()->prepare("UPDATE booking_requests SET status = :status WHERE id = :id");
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db()->prepare("DELETE FROM booking_requests WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
