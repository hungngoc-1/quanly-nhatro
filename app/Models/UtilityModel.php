<?php

namespace App\Models;

use App\Config\Database;
use InvalidArgumentException;
use PDO;

class UtilityModel
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
     * Tính toán chỉ số tiêu thụ và thành tiền điện, nước
     * Dùng chung cho cả Controller và Unit Test (UtilityTest)
     */
    public function calculateUsageAndCost(
        int $oldElectric,
        int $newElectric,
        int $oldWater,
        int $newWater,
        float $electricRate = 3500.0,
        float $waterRate = 15000.0,
        float $otherFee = 150000.0
    ): array {
        if ($oldElectric < 0 || $newElectric < 0 || $oldWater < 0 || $newWater < 0) {
            throw new InvalidArgumentException('Chỉ số điện nước không được là số âm.');
        }

        if ($newElectric < $oldElectric) {
            throw new InvalidArgumentException('Chỉ số điện mới phải lớn hơn hoặc bằng chỉ số điện cũ.');
        }

        if ($newWater < $oldWater) {
            throw new InvalidArgumentException('Chỉ số nước mới phải lớn hơn hoặc bằng chỉ số nước cũ.');
        }

        if ($electricRate < 0 || $waterRate < 0 || $otherFee < 0) {
            throw new InvalidArgumentException('Đơn giá điện, nước và phí dịch vụ không được âm.');
        }

        $electricUsed = $newElectric - $oldElectric;
        $waterUsed = $newWater - $oldWater;
        $electricAmount = round($electricUsed * $electricRate, 2);
        $waterAmount = round($waterUsed * $waterRate, 2);
        $totalUtilityAmount = round($electricAmount + $waterAmount + $otherFee, 2);

        return [
            'old_electric'         => $oldElectric,
            'new_electric'         => $newElectric,
            'electric_used'        => $electricUsed,
            'electric_rate'        => $electricRate,
            'electric_amount'      => $electricAmount,
            'old_water'            => $oldWater,
            'new_water'            => $newWater,
            'water_used'           => $waterUsed,
            'water_rate'           => $waterRate,
            'water_amount'         => $waterAmount,
            'other_fee'            => $otherFee,
            'total_utility_amount' => $totalUtilityAmount,
        ];
    }

    public function getAll(array $filters = []): array
    {
        $sql = "
            SELECT u.*,
                   r.room_number,
                   r.floor,
                   t.full_name AS tenant_name,
                   inv.id AS invoice_id,
                   inv.invoice_code
            FROM utilities u
            INNER JOIN rooms r ON r.id = u.room_id
            LEFT JOIN contracts c ON c.id = u.contract_id
            LEFT JOIN tenants t ON t.id = c.tenant_id
            LEFT JOIN invoices inv ON inv.utility_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['month'])) {
            $sql .= " AND u.month = :month";
            $params['month'] = (int) $filters['month'];
        }

        if (!empty($filters['year'])) {
            $sql .= " AND u.year = :year";
            $params['year'] = (int) $filters['year'];
        }

        if (!empty($filters['room_id'])) {
            $sql .= " AND u.room_id = :room_id";
            $params['room_id'] = (int) $filters['room_id'];
        }

        $sql .= " ORDER BY u.year DESC, u.month DESC, r.room_number ASC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db()->prepare("
            SELECT u.*, r.room_number
            FROM utilities u
            INNER JOIN rooms r ON r.id = u.room_id
            WHERE u.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByRoomAndPeriod(int $roomId, int $month, int $year): ?array
    {
        $stmt = $this->db()->prepare("
            SELECT * FROM utilities
            WHERE room_id = :room_id AND month = :month AND year = :year
            LIMIT 1
        ");
        $stmt->execute([
            'room_id' => $roomId,
            'month'   => $month,
            'year'    => $year,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getLatestReadingByRoom(int $roomId): ?array
    {
        $stmt = $this->db()->prepare("
            SELECT * FROM utilities
            WHERE room_id = :room_id
            ORDER BY year DESC, month DESC, id DESC
            LIMIT 1
        ");
        $stmt->execute(['room_id' => $roomId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $calc = $this->calculateUsageAndCost(
            (int) $data['old_electric'],
            (int) $data['new_electric'],
            (int) $data['old_water'],
            (int) $data['new_water'],
            (float) ($data['electric_rate'] ?? 3500),
            (float) ($data['water_rate'] ?? 15000),
            (float) ($data['other_fee'] ?? 150000)
        );

        $stmt = $this->db()->prepare("
            INSERT INTO utilities (
                room_id, contract_id, month, year,
                old_electric, new_electric, electric_used, electric_rate, electric_amount,
                old_water, new_water, water_used, water_rate, water_amount,
                other_fee, recorded_at
            ) VALUES (
                :room_id, :contract_id, :month, :year,
                :old_electric, :new_electric, :electric_used, :electric_rate, :electric_amount,
                :old_water, :new_water, :water_used, :water_rate, :water_amount,
                :other_fee, :recorded_at
            )
        ");

        $stmt->execute([
            'room_id'         => (int) $data['room_id'],
            'contract_id'     => !empty($data['contract_id']) ? (int) $data['contract_id'] : null,
            'month'           => (int) $data['month'],
            'year'            => (int) $data['year'],
            'old_electric'    => $calc['old_electric'],
            'new_electric'    => $calc['new_electric'],
            'electric_used'   => $calc['electric_used'],
            'electric_rate'   => $calc['electric_rate'],
            'electric_amount' => $calc['electric_amount'],
            'old_water'       => $calc['old_water'],
            'new_water'       => $calc['new_water'],
            'water_used'      => $calc['water_used'],
            'water_rate'      => $calc['water_rate'],
            'water_amount'    => $calc['water_amount'],
            'other_fee'       => $calc['other_fee'],
            'recorded_at'     => $data['recorded_at'] ?? date('Y-m-d'),
        ]);

        return (int) $this->db()->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $calc = $this->calculateUsageAndCost(
            (int) $data['old_electric'],
            (int) $data['new_electric'],
            (int) $data['old_water'],
            (int) $data['new_water'],
            (float) ($data['electric_rate'] ?? 3500),
            (float) ($data['water_rate'] ?? 15000),
            (float) ($data['other_fee'] ?? 150000)
        );

        $stmt = $this->db()->prepare("
            UPDATE utilities
            SET old_electric = :old_electric,
                new_electric = :new_electric,
                electric_used = :electric_used,
                electric_rate = :electric_rate,
                electric_amount = :electric_amount,
                old_water = :old_water,
                new_water = :new_water,
                water_used = :water_used,
                water_rate = :water_rate,
                water_amount = :water_amount,
                other_fee = :other_fee,
                recorded_at = :recorded_at
            WHERE id = :id
        ");

        return $stmt->execute([
            'id'              => $id,
            'old_electric'    => $calc['old_electric'],
            'new_electric'    => $calc['new_electric'],
            'electric_used'   => $calc['electric_used'],
            'electric_rate'   => $calc['electric_rate'],
            'electric_amount' => $calc['electric_amount'],
            'old_water'       => $calc['old_water'],
            'new_water'       => $calc['new_water'],
            'water_used'      => $calc['water_used'],
            'water_rate'      => $calc['water_rate'],
            'water_amount'    => $calc['water_amount'],
            'other_fee'       => $calc['other_fee'],
            'recorded_at'     => $data['recorded_at'] ?? date('Y-m-d'),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db()->prepare("DELETE FROM utilities WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
