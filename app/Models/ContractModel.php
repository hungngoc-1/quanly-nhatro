<?php

namespace App\Models;

use App\Config\Database;
use App\Helpers\CoreHelper;
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

        $deposit = (float) ($data['deposit_amount'] ?? $monthlyRent);
        if ($deposit < 0) {
            $errors['deposit_amount'] = 'Tiền đặt cọc không được âm.';
        }

        return $errors;
    }

    /**
     * Tính tiền phòng, tiền cọc và phí dịch vụ khi khách mới vào ở dựa theo ngày bắt đầu hợp đồng:
     * - Tiền cọc luôn bằng 1 tháng tiền thuê của phòng đó.
     * - Vào ở mùng 1 hoặc trước/trong ngày 15: Tính 100% tiền phòng cả tháng + tiền dịch vụ bình thường + tiền cọc 1 tháng.
     * - Vào ở sau ngày 15: Tính nửa tháng tiền phòng (50%) + tiền dịch vụ bình thường + tiền cọc 1 tháng.
     */
    public function calculateMoveInAmounts(float $monthlyRent, float $serviceFee, string $startDate): array
    {
        $ts = strtotime($startDate) ?: time();
        $dayOfMonth = (int) date('j', $ts);
        $isAfter15th = ($dayOfMonth > 15);

        $firstMonthRoomFee = $isAfter15th ? round($monthlyRent * 0.5, 2) : $monthlyRent;
        $depositFee = $monthlyRent; // Tiền cọc bằng 1 tháng tiền thuê phòng

        return [
            'day_of_month'     => $dayOfMonth,
            'is_after_15th'    => $isAfter15th,
            'room_fee'         => $firstMonthRoomFee,
            'service_fee'      => $serviceFee,
            'deposit_fee'      => $depositFee,
            'total_move_in'    => round($firstMonthRoomFee + $serviceFee + $depositFee, 2),
            'rule_description' => $isAfter15th
                ? "Vào ở ngày {$dayOfMonth} (sau ngày 15): Tính 50% tiền phòng tháng đầu (" . CoreHelper::formatCurrency($firstMonthRoomFee) . ") + 100% phí dịch vụ + Tiền cọc 1 tháng (" . CoreHelper::formatCurrency($depositFee) . ")."
                : "Vào ở ngày {$dayOfMonth} (trước/trong ngày 15): Tính 100% tiền phòng cả tháng (" . CoreHelper::formatCurrency($firstMonthRoomFee) . ") + 100% phí dịch vụ + Tiền cọc 1 tháng (" . CoreHelper::formatCurrency($depositFee) . ").",
        ];
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
                   r.building_id,
                   b.building_name,
                   rt.type_name AS room_type_name,
                   t.full_name AS tenant_name,
                   t.phone AS tenant_phone,
                   t.cccd AS tenant_cccd,
                   DATEDIFF(c.end_date, CURDATE()) AS days_left
            FROM contracts c
            INNER JOIN rooms r ON r.id = c.room_id
            LEFT JOIN buildings b ON b.id = r.building_id
            LEFT JOIN room_types rt ON rt.id = r.room_type_id
            INNER JOIN tenants t ON t.id = c.tenant_id
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
            SELECT c.*,
                   r.room_number,
                   r.floor,
                   r.area,
                   r.amenities,
                   r.building_id,
                   rt.type_name AS room_type_name,
                   b.building_code,
                   b.building_name,
                   b.address AS building_address,
                   m.full_name AS manager_name,
                   m.phone AS manager_phone,
                   t.full_name AS tenant_name,
                   t.phone AS tenant_phone,
                   t.cccd AS tenant_cccd,
                   t.dob AS tenant_dob,
                   t.hometown AS tenant_hometown,
                   t.email AS tenant_email,
                   t.user_id AS tenant_user_id,
                   DATEDIFF(c.end_date, CURDATE()) AS days_left
            FROM contracts c
            INNER JOIN rooms r ON r.id = c.room_id
            LEFT JOIN room_types rt ON rt.id = r.room_type_id
            LEFT JOIN buildings b ON b.id = r.building_id
            LEFT JOIN users m ON m.id = b.manager_id
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
            SELECT c.*, r.room_number, r.building_id, t.full_name AS tenant_name, t.phone AS tenant_phone
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
            $monthlyRent = (float) $data['monthly_rent'];
            // Tiền đặt cọc luôn bằng 1 tháng tiền thuê của phòng đó
            $depositAmount = $monthlyRent;
            $serviceFee = (float) ($data['service_fee'] ?? 150000);
            $depositDate = !empty($data['deposit_date']) ? $data['deposit_date'] : date('Y-m-d');

            // Hợp đồng luôn có hiệu lực từ ngày mùng 01 tháng sau so với ngày lên hợp đồng / đặt cọc
            if (!empty($data['start_date']) && strtotime($data['start_date']) > strtotime($depositDate) && date('d', strtotime($data['start_date'])) === '01') {
                $startDate = date('Y-m-01', strtotime($data['start_date']));
            } else {
                $startDate = date('Y-m-01', strtotime($depositDate . ' first day of next month'));
            }

            // Ngày hết hạn hợp đồng luôn tính từ ngày mùng 01 có hiệu lực (start_date), KHÔNG tính từ ngày lên hợp đồng (deposit_date)
            $durationMonths = !empty($data['duration_months']) ? max(1, (int) $data['duration_months']) : 12;
            $endDate = date('Y-m-01', strtotime($startDate . " +{$durationMonths} months"));

            $signatureType = in_array($data['signature_type'] ?? '', ['online', 'paper'], true)
                ? $data['signature_type']
                : 'online';

            // Kiểm tra nếu hợp đồng có ngày kết thúc trong vòng 30 ngày thì bật sẵn thông báo gia hạn
            $daysLeft = $this->calculateRemainingDays($endDate);
            $renewalStatus = ($daysLeft >= 0 && $daysLeft <= 30) ? 'notified' : 'none';

            $stmt = $pdo->prepare("
                INSERT INTO contracts (
                    contract_code, room_id, tenant_id, start_date, end_date,
                    deposit_amount, deposit_date, deposit_status, monthly_rent, electricity_rate, water_rate, service_fee,
                    status, signature_type, signature_status, renewal_status, notes
                ) VALUES (
                    :contract_code, :room_id, :tenant_id, :start_date, :end_date,
                    :deposit_amount, :deposit_date, 'held', :monthly_rent, :electricity_rate, :water_rate, :service_fee,
                    :status, :signature_type, 'pending', :renewal_status, :notes
                )
            ");
            $stmt->execute([
                'contract_code'    => $code,
                'room_id'          => (int) $data['room_id'],
                'tenant_id'        => (int) $data['tenant_id'],
                'start_date'       => $startDate,
                'end_date'         => $endDate,
                'deposit_amount'   => $depositAmount,
                'deposit_date'     => $depositDate,
                'monthly_rent'     => $monthlyRent,
                'electricity_rate' => (float) ($data['electricity_rate'] ?? 3500),
                'water_rate'       => (float) ($data['water_rate'] ?? 15000),
                'service_fee'      => $serviceFee,
                'status'           => $status,
                'signature_type'   => $signatureType,
                'renewal_status'   => $renewalStatus,
                'notes'            => trim($data['notes'] ?? ''),
            ]);

            $contractId = (int) $pdo->lastInsertId();

            if ($status === 'active') {
                $stmtRoom = $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE id = :room_id");
                $stmtRoom->execute(['room_id' => (int) $data['room_id']]);

                $stmtTenant = $pdo->prepare("UPDATE tenants SET status = 'staying' WHERE id = :tenant_id");
                $stmtTenant->execute(['tenant_id' => (int) $data['tenant_id']]);

                // Tự động tạo Hóa đơn Nhận phòng & Đặt cọc tháng đầu tiên theo quy tắc ngày vào ở (<=15 hoặc >15)
                $stmtRoomInfo = $pdo->prepare("SELECT room_number FROM rooms WHERE id = :id LIMIT 1");
                $stmtRoomInfo->execute(['id' => (int) $data['room_id']]);
                $roomNumber = (string) ($stmtRoomInfo->fetchColumn() ?: 'P');

                $moveInDate = !empty($data['move_in_date']) ? $data['move_in_date'] : $startDate;
                $moveInCalc = $this->calculateMoveInAmounts($monthlyRent, $serviceFee, $moveInDate);
                $startTs = strtotime($moveInDate) ?: time();
                $month = (int) date('n', $startTs);
                $year = (int) date('Y', $startTs);

                $invoiceModel = new InvoiceModel($pdo);
                if (!$invoiceModel->findByRoomAndPeriod((int) $data['room_id'], $month, $year)) {
                    $invCode = $invoiceModel->generateInvoiceCode($roomNumber, $month, $year);
                    $invoiceModel->create([
                        'invoice_code'   => $invCode,
                        'invoice_type'   => 'move_in',
                        'contract_id'    => $contractId,
                        'room_id'        => (int) $data['room_id'],
                        'tenant_id'      => (int) $data['tenant_id'],
                        'utility_id'     => null,
                        'month'          => $month,
                        'year'           => $year,
                        'room_fee'       => $moveInCalc['room_fee'],
                        'electric_fee'   => 0,
                        'water_fee'      => 0,
                        'service_fee'    => $moveInCalc['service_fee'],
                        'deposit_fee'    => $moveInCalc['deposit_fee'],
                        'deposit_refund' => 0,
                        'discount'       => 0,
                        'due_date'       => date('Y-m-d', strtotime($moveInDate . ' +5 days')),
                        'notes'          => 'Ngày đặt cọc: ' . CoreHelper::formatDate($depositDate) . ' | ' . $moveInCalc['rule_description'],
                    ]);
                }
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
        $monthlyRent = (float) $data['monthly_rent'];
        $depositDate = !empty($data['deposit_date']) ? $data['deposit_date'] : date('Y-m-d');
        $startDate = !empty($data['start_date'])
            ? date('Y-m-01', strtotime($data['start_date']))
            : date('Y-m-01', strtotime($depositDate . ' first day of next month'));
        $durationMonths = !empty($data['duration_months']) ? max(1, (int) $data['duration_months']) : 12;
        $endDate = !empty($data['duration_months'])
            ? date('Y-m-01', strtotime($startDate . " +{$durationMonths} months"))
            : date('Y-m-01', strtotime($data['end_date'] ?? ($startDate . ' +12 months')));

        $stmt = $this->db()->prepare("
            UPDATE contracts
            SET deposit_date = :deposit_date,
                start_date = :start_date,
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
            'deposit_date'     => $depositDate,
            'start_date'       => $startDate,
            'end_date'         => $endDate,
            'deposit_amount'   => $monthlyRent,
            'monthly_rent'     => $monthlyRent,
            'electricity_rate' => (float) ($data['electricity_rate'] ?? 3500),
            'water_rate'       => (float) ($data['water_rate'] ?? 15000),
            'service_fee'      => (float) ($data['service_fee'] ?? 150000),
            'status'           => $data['status'] ?? 'active',
            'notes'            => trim($data['notes'] ?? ''),
        ]);
    }

    /**
     * Thanh lý hợp đồng khi khách rời đi:
     * - Nếu ngày rời đi >= end_date (Đi sau khi hết hạn hợp đồng): Được hoàn trả lại tiền cọc (deposit_status = 'refunded').
     * - Nếu ngày rời đi < end_date (Chưa hết hợp đồng mà không ở nữa): Mất tiền cọc (deposit_status = 'forfeited').
     * Đồng thời ghi nhận vào Hóa đơn trả phòng (move_out).
     */
    public function terminate(int $id, ?string $moveOutDate = null): array|bool
    {
        $contract = $this->findById($id);
        if (!$contract) {
            return false;
        }

        $actualMoveOut = !empty($moveOutDate) ? $moveOutDate : date('Y-m-d');
        $isCompletedTerm = (strtotime($actualMoveOut) >= strtotime($contract['end_date']));
        $depositAmount = (float) $contract['deposit_amount'];

        $depositStatus = $isCompletedTerm ? 'refunded' : 'forfeited';
        $depositRefund = $isCompletedTerm ? $depositAmount : 0.0;
        $reason = $isCompletedTerm
            ? 'Trả phòng sau khi hết hạn hợp đồng — Được nhận lại 100% tiền cọc (' . CoreHelper::formatCurrency($depositAmount) . ').'
            : 'Trả phòng trước khi hết hạn hợp đồng (' . CoreHelper::formatDate($contract['end_date']) . ') — Mất tiền đặt cọc (' . CoreHelper::formatCurrency($depositAmount) . ').';

        $pdo = $this->db();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("
                UPDATE contracts
                SET status = 'terminated',
                    deposit_status = :deposit_status,
                    terminated_at = :terminated_at,
                    termination_reason = :termination_reason
                WHERE id = :id
            ");
            $stmt->execute([
                'id'                 => $id,
                'deposit_status'     => $depositStatus,
                'terminated_at'      => $actualMoveOut,
                'termination_reason' => $reason,
            ]);

            // Tạo hoặc cập nhật Hóa đơn Tất toán / Trả phòng (move_out) để ghi nhận tiền cọc khi rời đi
            $invoiceModel = new InvoiceModel($pdo);
            $month = (int) date('n', strtotime($actualMoveOut));
            $year = (int) date('Y', strtotime($actualMoveOut));
            $existingInv = $invoiceModel->findByRoomAndPeriod((int) $contract['room_id'], $month, $year);

            if ($existingInv) {
                $newTotal = max(0, (float) $existingInv['room_fee'] + (float) $existingInv['electric_fee'] + (float) $existingInv['water_fee'] + (float) $existingInv['service_fee'] + (float) $existingInv['deposit_fee'] - $depositRefund - (float) $existingInv['discount']);
                $newStatus = $invoiceModel->determinePaymentStatus($newTotal, (float) $existingInv['paid_amount'], $existingInv['due_date']);
                $stmtUpdInv = $pdo->prepare("
                    UPDATE invoices
                    SET invoice_type = 'move_out',
                        deposit_refund = :deposit_refund,
                        total_amount = :total_amount,
                        status = :status,
                        notes = CONCAT(COALESCE(notes, ''), ' | ', :reason)
                    WHERE id = :inv_id
                ");
                $stmtUpdInv->execute([
                    'deposit_refund' => $depositRefund,
                    'total_amount'   => $newTotal,
                    'status'         => $newStatus,
                    'reason'         => $reason,
                    'inv_id'         => (int) $existingInv['id'],
                ]);
            } else {
                $invCode = 'OUT-' . date('Ym', strtotime($actualMoveOut)) . '-' . strtoupper(trim($contract['room_number'])) . '-' . random_int(10, 99);
                $invoiceModel->create([
                    'invoice_code'   => $invCode,
                    'invoice_type'   => 'move_out',
                    'contract_id'    => $id,
                    'room_id'        => (int) $contract['room_id'],
                    'tenant_id'      => (int) $contract['tenant_id'],
                    'utility_id'     => null,
                    'month'          => $month,
                    'year'           => $year,
                    'room_fee'       => 0,
                    'electric_fee'   => 0,
                    'water_fee'      => 0,
                    'service_fee'    => 0,
                    'deposit_fee'    => 0,
                    'deposit_refund' => $depositRefund,
                    'discount'       => 0,
                    'paid_amount'    => 0,
                    'status'         => 'paid',
                    'due_date'       => $actualMoveOut,
                    'notes'          => $reason,
                ]);
            }

            // Nếu phòng không còn hợp đồng active nào khác thì trả về trạng thái available
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM contracts WHERE room_id = :room_id AND status = 'active' AND id != :id");
            $stmtCheck->execute(['room_id' => $contract['room_id'], 'id' => $id]);
            if ((int) $stmtCheck->fetchColumn() === 0) {
                $stmtRoom = $pdo->prepare("UPDATE rooms SET status = 'available' WHERE id = :room_id");
                $stmtRoom->execute(['room_id' => $contract['room_id']]);
            }

            $pdo->commit();
            return [
                'is_completed_term' => $isCompletedTerm,
                'deposit_status'    => $depositStatus,
                'deposit_refund'    => $depositRefund,
                'reason'            => $reason,
            ];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Xử lý phản hồi của Khách thuê đối với Thông báo Gia hạn Hợp đồng trước 1 tháng
     */
    public function respondRenewal(int $contractId, string $decision, int $months = 12): bool
    {
        $contract = $this->findById($contractId);
        if (!$contract || $contract['status'] !== 'active') {
            return false;
        }

        $months = max(1, min(36, $months));
        if (in_array($decision, ['renew', 'accept'], true)) {
            $baseEnd = date('Y-m-01', strtotime($contract['end_date']));
            $newEndDate = date('Y-m-01', strtotime($baseEnd . " +{$months} months"));
            $stmt = $this->db()->prepare("
                UPDATE contracts
                SET end_date = :end_date,
                    renewal_status = 'renewed',
                    renewal_months = :months,
                    renewal_responded_at = NOW(),
                    notes = CONCAT(COALESCE(notes, ''), ' | Khách đồng ý gia hạn thêm ', :months_note, ' tháng đến ', :new_end_note)
                WHERE id = :id
            ");
            return $stmt->execute([
                'end_date'     => $newEndDate,
                'months'       => $months,
                'months_note'  => $months,
                'new_end_note' => CoreHelper::formatDate($newEndDate),
                'id'           => $contractId,
            ]);
        }

        // Nếu khách từ chối gia hạn
        $stmt = $this->db()->prepare("
            UPDATE contracts
            SET renewal_status = 'declined',
                renewal_responded_at = NOW(),
                notes = CONCAT(COALESCE(notes, ''), ' | Khách xác nhận không gia hạn khi hết hợp đồng')
            WHERE id = :id
        ");
        return $stmt->execute(['id' => $contractId]);
    }

    /**
     * Lưu chữ ký điện tử (online) của Khách thuê hoặc Quản lý/Chủ trọ
     */
    public function saveOnlineSignature(int $id, ?string $tenantSig = null, ?string $managerSig = null): bool
    {
        $contract = $this->findById($id);
        if (!$contract) {
            return false;
        }

        $newTenantSig = !empty($tenantSig) ? $tenantSig : ($contract['tenant_signature'] ?? null);
        $newManagerSig = !empty($managerSig) ? $managerSig : ($contract['manager_signature'] ?? null);
        $sigStatus = (!empty($newTenantSig) || !empty($newManagerSig)) ? 'signed' : 'pending';

        $stmt = $this->db()->prepare("
            UPDATE contracts
            SET signature_type = 'online',
                signature_status = :sig_status,
                tenant_signature = :tenant_sig,
                manager_signature = :manager_sig,
                signed_at = NOW()
            WHERE id = :id
        ");

        return $stmt->execute([
            'sig_status'  => $sigStatus,
            'tenant_sig'  => $newTenantSig,
            'manager_sig' => $newManagerSig,
            'id'          => $id,
        ]);
    }

    /**
     * Lưu bản scan của Hợp đồng ký giấy
     */
    public function saveScanFile(int $id, string $scanFilePath): bool
    {
        $stmt = $this->db()->prepare("
            UPDATE contracts
            SET signature_type = 'paper',
                signature_status = 'signed',
                scan_file = :scan_file,
                signed_at = NOW()
            WHERE id = :id
        ");

        return $stmt->execute([
            'scan_file' => $scanFilePath,
            'id'        => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db()->prepare("DELETE FROM contracts WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
