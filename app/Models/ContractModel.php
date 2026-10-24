<?php

namespace App\Models;

use App\Config\Database;
use InvalidArgumentException;
use PDO;

class ContractModel
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
     * Kiểm tra tính hợp lệ của dữ liệu hợp đồng (dùng chung cho nghiệp vụ và Unit Test)
     */
    public function validateContractData(array $data): array
    {
        $errors = [];

        if (empty($data['room_id']) || (int) $data['room_id'] <= 0) {
            $errors['room_id'] = 'Phòng trọ không hợp lệ.';
        }
        if (empty($data['tenant_id']) || (int) $data['tenant_id'] <= 0) {
            $errors['tenant_id'] = 'Khách thuê không hợp lệ.';
        }

        $startDate = $data['start_date'] ?? '';
        $endDate = $data['end_date'] ?? '';
        if (empty($startDate) || strtotime($startDate) === false) {
            $errors['start_date'] = 'Ngày bắt đầu không hợp lệ.';
        }
        if (empty($endDate) || strtotime($endDate) === false) {
            $errors['end_date'] = 'Ngày kết thúc không hợp lệ.';
        }
        if (empty($errors['start_date']) && empty($errors['end_date']) && strtotime($endDate) <= strtotime($startDate)) {
            $errors['end_date'] = 'Ngày kết thúc phải sau ngày bắt đầu.';
        }

        $monthlyRent = (float) ($data['monthly_rent'] ?? -1);
        if ($monthlyRent <= 0) {
            $errors['monthly_rent'] = 'Giá thuê hàng tháng phải lớn hơn 0.';
        }

        $deposit = (float) ($data['deposit_amount'] ?? 0);
        if ($deposit < 0) {
            $errors['deposit_amount'] = 'Tiền đặt cọc không được âm.';
        }

        return $errors;
    }

    /**
     * Tính số ngày còn lại của hợp đồng so với ngày tham chiếu
     */
    public function calculateRemainingDays(string $endDate, ?string $referenceDate = null): int
    {
        $ref = $referenceDate ? strtotime($referenceDate) : strtotime(date('Y-m-d'));
        $end = strtotime($endDate);
        if ($end === false || $ref === false) {
            throw new InvalidArgumentException('Ngày tháng không hợp lệ.');
        }
        return (int) floor(($end - $ref) / 86400);
    }

    /**
     * Xác định trạng thái cảnh báo hợp đồng (active, expiring_soon, expired, terminated)
     */
    public function evaluateContractHealth(array $contract, ?string $referenceDate = null): string
    {
        $status = $contract['status'] ?? 'active';
        if ($status === 'terminated') {
            return 'terminated';
        }
        $remainingDays = $this->calculateRemainingDays($contract['end_date'], $referenceDate);
        if ($remainingDays < 0) {
            return 'expired';
        }
        if ($remainingDays <= 30) {
            return 'expiring_soon';
        }
        return 'active';
    }

    public function getAll(array $filters = []): array
    {
        $sql = "
            SELECT c.*,
                   r.room_number,
                   r.floor,
                   t.full_name AS tenant_name,
                   t.phone AS tenant_phone,
                   t.cccd AS tenant_cccd
            FROM contracts c
            INNER JOIN rooms r ON r.id = c.room_id
            INNER JOIN tenants t ON t.id = c.tenant_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND c.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['room_id'])) {
            $sql .= " AND c.room_id = :room_id";
            $params['room_id'] = (int) $filters['room_id'];
        }

        if (!empty($filters['keyword'])) {
            $sql .= " AND (c.contract_code LIKE :kw1 OR r.room_number LIKE :kw2 OR t.full_name LIKE :kw3)";
            $kw = '%' . trim($filters['keyword']) . '%';
            $params['kw1'] = $kw;
            $params['kw2'] = $kw;
            $params['kw3'] = $kw;
        }

        $sql .= " ORDER BY c.status = 'active' DESC, c.id DESC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getActiveContracts(): array
    {
        return $this->getAll(['status' => 'active']);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db()->prepare("
            SELECT c.*, r.room_number, t.full_name AS tenant_name, t.phone AS tenant_phone
            FROM contracts c
            INNER JOIN rooms r ON r.id = c.room_id
            INNER JOIN tenants t ON t.id = c.tenant_id
            WHERE c.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findActiveByRoomId(int $roomId): ?array
    {
        $stmt = $this->db()->prepare("
            SELECT c.*, r.room_number, t.full_name AS tenant_name, t.phone AS tenant_phone
            FROM contracts c
            INNER JOIN rooms r ON r.id = c.room_id
            INNER JOIN tenants t ON t.id = c.tenant_id
            WHERE c.room_id = :room_id AND c.status = 'active'
            ORDER BY c.id DESC
            LIMIT 1
        ");
        $stmt->execute(['room_id' => $roomId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function generateContractCode(): string
    {
        $year = date('Y');
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM contracts WHERE YEAR(created_at) = :year");
        $stmt->execute(['year' => $year]);
        $count = (int) $stmt->fetchColumn() + 1;
        return sprintf('HD-%s-%03d', $year, $count + random_int(10, 99));
    }

    public function create(array $data): int
    {
        $pdo = $this->db();
        $pdo->beginTransaction();

        try {
            $code = !empty($data['contract_code']) ? trim($data['contract_code']) : $this->generateContractCode();
            $status = $data['status'] ?? 'active';

            $stmt = $pdo->prepare("
                INSERT INTO contracts (
                    contract_code, room_id, tenant_id, start_date, end_date,
                    deposit_amount, monthly_rent, electricity_rate, water_rate, service_fee, status, notes
                ) VALUES (
                    :contract_code, :room_id, :tenant_id, :start_date, :end_date,
                    :deposit_amount, :monthly_rent, :electricity_rate, :water_rate, :service_fee, :status, :notes
                )
            ");
            $stmt->execute([
                'contract_code'    => $code,
                'room_id'          => (int) $data['room_id'],
                'tenant_id'        => (int) $data['tenant_id'],
                'start_date'       => $data['start_date'],
                'end_date'         => $data['end_date'],
                'deposit_amount'   => (float) ($data['deposit_amount'] ?? 0),
                'monthly_rent'     => (float) $data['monthly_rent'],
                'electricity_rate' => (float) ($data['electricity_rate'] ?? 3500),
                'water_rate'       => (float) ($data['water_rate'] ?? 15000),
                'service_fee'      => (float) ($data['service_fee'] ?? 150000),
                'status'           => $status,
                'notes'            => trim($data['notes'] ?? ''),
            ]);

            $contractId = (int) $pdo->lastInsertId();

            if ($status === 'active') {
                $stmtRoom = $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE id = :room_id");
                $stmtRoom->execute(['room_id' => (int) $data['room_id']]);

                $stmtTenant = $pdo->prepare("UPDATE tenants SET status = 'staying' WHERE id = :tenant_id");
                $stmtTenant->execute(['tenant_id' => (int) $data['tenant_id']]);
            }

            $pdo->commit();
            return $contractId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db()->prepare("
            UPDATE contracts
            SET start_date = :start_date,
                end_date = :end_date,
                deposit_amount = :deposit_amount,
                monthly_rent = :monthly_rent,
                electricity_rate = :electricity_rate,
                water_rate = :water_rate,
                service_fee = :service_fee,
                status = :status,
                notes = :notes
            WHERE id = :id
        ");
        return $stmt->execute([
            'id'               => $id,
            'start_date'       => $data['start_date'],
            'end_date'         => $data['end_date'],
            'deposit_amount'   => (float) ($data['deposit_amount'] ?? 0),
            'monthly_rent'     => (float) $data['monthly_rent'],
            'electricity_rate' => (float) ($data['electricity_rate'] ?? 3500),
            'water_rate'       => (float) ($data['water_rate'] ?? 15000),
            'service_fee'      => (float) ($data['service_fee'] ?? 150000),
            'status'           => $data['status'] ?? 'active',
            'notes'            => trim($data['notes'] ?? ''),
        ]);
    }

    public function terminate(int $id): bool
    {
        $contract = $this->findById($id);
        if (!$contract) {
            return false;
        }

        $pdo = $this->db();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("UPDATE contracts SET status = 'terminated' WHERE id = :id");
            $stmt->execute(['id' => $id]);

            // Nếu phòng không còn hợp đồng active nào khác thì trả về trạng thái available
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM contracts WHERE room_id = :room_id AND status = 'active' AND id != :id");
            $stmtCheck->execute(['room_id' => $contract['room_id'], 'id' => $id]);
            if ((int) $stmtCheck->fetchColumn() === 0) {
                $stmtRoom = $pdo->prepare("UPDATE rooms SET status = 'available' WHERE id = :room_id");
                $stmtRoom->execute(['room_id' => $contract['room_id']]);
            }

            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db()->prepare("DELETE FROM contracts WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
