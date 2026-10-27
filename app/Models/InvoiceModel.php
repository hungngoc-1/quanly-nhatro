<?php

namespace App\Models;

use App\Config\Database;
use App\Helpers\CoreHelper;
use InvalidArgumentException;
use PDO;

class InvoiceModel
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
     * Tính tổng tiền hóa đơn (dùng chung cho Controller & InvoiceTest)
     * Hỗ trợ cộng thêm tiền đặt cọc (khi khách vào ở) và trừ tiền hoàn cọc (khi khách rời đi sau khi hết hạn HĐ)
     */
    public function calculateInvoiceTotal(
        float $roomFee,
        float $electricFee,
        float $waterFee,
        float $serviceFee = 0.0,
        float $discount = 0.0,
        float $depositFee = 0.0,
        float $depositRefund = 0.0
    ): float {
        if ($roomFee < 0 || $electricFee < 0 || $waterFee < 0 || $serviceFee < 0 || $discount < 0 || $depositFee < 0 || $depositRefund < 0) {
            throw new InvalidArgumentException('Các khoản phí và giảm giá không được âm.');
        }

        $gross = $roomFee + $electricFee + $waterFee + $serviceFee + $depositFee;
        if ($discount > $gross && $depositRefund == 0.0) {
            throw new InvalidArgumentException('Số tiền giảm giá không được lớn hơn tổng tiền trước giảm.');
        }

        return max(0.0, round($gross - $discount - $depositRefund, 2));
    }

    /**
     * Xác định trạng thái hóa đơn dựa trên số tiền đã đóng và ngày đến hạn
     */
    public function determinePaymentStatus(
        float $totalAmount,
        float $paidAmount,
        ?string $dueDate = null,
        ?string $referenceDate = null
    ): string {
        if ($paidAmount < 0) {
            throw new InvalidArgumentException('Số tiền đã thanh toán không được âm.');
        }

        if ($totalAmount <= 0 || $paidAmount >= $totalAmount) {
            return 'paid';
        }

        if ($paidAmount > 0 && $paidAmount < $totalAmount) {
            return 'partial';
        }

        if ($dueDate !== null) {
            $refTs = $referenceDate ? strtotime($referenceDate) : strtotime(date('Y-m-d'));
            $dueTs = strtotime($dueDate);
            if ($dueTs !== false && $refTs !== false && $refTs > $dueTs) {
                return 'overdue';
            }
        }

        return 'unpaid';
    }

    public function getAll(array $filters = []): array
    {
        $sql = "
            SELECT inv.*,
                   r.room_number,
                   r.floor,
                   r.building_id,
                   b.building_name,
                   t.full_name AS tenant_name,
                   t.phone AS tenant_phone,
                   c.contract_code,
                   c.deposit_amount AS contract_deposit_amount,
                   c.deposit_status AS contract_deposit_status,
                   (inv.total_amount - inv.paid_amount) AS remaining_amount
            FROM invoices inv
            INNER JOIN rooms r ON r.id = inv.room_id
            LEFT JOIN buildings b ON b.id = r.building_id
            INNER JOIN tenants t ON t.id = inv.tenant_id
            INNER JOIN contracts c ON c.id = inv.contract_id
            WHERE 1=1
        ";
        $params = [];

        $buildingId = array_key_exists('building_id', $filters) && $filters['building_id'] !== ''
            ? (int) $filters['building_id']
            : CoreHelper::currentBuildingId();

        if (!empty($buildingId) && empty($filters['tenant_id'])) {
            $sql .= " AND r.building_id = :building_id";
            $params['building_id'] = (int) $buildingId;
        }

        if (!empty($filters['month'])) {
            $sql .= " AND inv.month = :month";
            $params['month'] = (int) $filters['month'];
        }

        if (!empty($filters['year'])) {
            $sql .= " AND inv.year = :year";
            $params['year'] = (int) $filters['year'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND inv.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['room_id'])) {
            $sql .= " AND inv.room_id = :room_id";
            $params['room_id'] = (int) $filters['room_id'];
        }

        if (!empty($filters['tenant_id'])) {
            $sql .= " AND inv.tenant_id = :tenant_id";
            $params['tenant_id'] = (int) $filters['tenant_id'];
        }

        if (!empty($filters['keyword'])) {
            $sql .= " AND (inv.invoice_code LIKE :kw1 OR r.room_number LIKE :kw2 OR t.full_name LIKE :kw3)";
            $kw = '%' . trim($filters['keyword']) . '%';
            $params['kw1'] = $kw;
            $params['kw2'] = $kw;
            $params['kw3'] = $kw;
        }

        $sql .= " ORDER BY inv.year DESC, inv.month DESC, inv.id DESC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getByTenantUser(int $userId, array $filters = []): array
    {
        $stmtTenant = $this->db()->prepare("SELECT id FROM tenants WHERE user_id = :user_id LIMIT 1");
        $stmtTenant->execute(['user_id' => $userId]);
        $tenantId = $stmtTenant->fetchColumn();

        if (!$tenantId) {
            return [];
        }

        $filters['tenant_id'] = (int) $tenantId;
        return $this->getAll($filters);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db()->prepare("
            SELECT inv.*,
                   r.room_number,
                   r.floor,
                   r.area,
                   r.building_id,
                   b.building_name,
                   b.address AS building_address,
                   rt.type_name AS room_type_name,
                   t.user_id AS tenant_user_id,
                   t.full_name AS tenant_name,
                   t.phone AS tenant_phone,
                   t.cccd AS tenant_cccd,
                   t.email AS tenant_email,
                   c.contract_code,
                   c.start_date AS contract_start_date,
                   c.end_date AS contract_end_date,
                   c.monthly_rent AS contract_monthly_rent,
                   c.deposit_amount AS contract_deposit_amount,
                   c.deposit_status AS contract_deposit_status,
                   c.termination_reason,
                   u.old_electric,
                   u.new_electric,
                   u.electric_used,
                   u.electric_rate,
                   u.old_water,
                   u.new_water,
                   u.water_used,
                   u.water_rate,
                   (inv.total_amount - inv.paid_amount) AS remaining_amount
            FROM invoices inv
            INNER JOIN rooms r ON r.id = inv.room_id
            LEFT JOIN buildings b ON b.id = r.building_id
            LEFT JOIN room_types rt ON rt.id = r.room_type_id
            INNER JOIN tenants t ON t.id = inv.tenant_id
            INNER JOIN contracts c ON c.id = inv.contract_id
            LEFT JOIN utilities u ON u.id = inv.utility_id
            WHERE inv.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByRoomAndPeriod(int $roomId, int $month, int $year): ?array
    {
        $stmt = $this->db()->prepare("
            SELECT * FROM invoices
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

    public function generateInvoiceCode(string $roomNumber, int $month, int $year): string
    {
        return sprintf('INV-%04d%02d-%s', $year, $month, strtoupper(trim($roomNumber)));
    }

    public function create(array $data): int
    {
        $roomFee = (float) ($data['room_fee'] ?? 0);
        $electricFee = (float) ($data['electric_fee'] ?? 0);
        $waterFee = (float) ($data['water_fee'] ?? 0);
        $serviceFee = (float) ($data['service_fee'] ?? 0);
        $depositFee = (float) ($data['deposit_fee'] ?? 0);
        $depositRefund = (float) ($data['deposit_refund'] ?? 0);
        $discount = (float) ($data['discount'] ?? 0);
        $paidAmount = (float) ($data['paid_amount'] ?? 0);
        $invoiceType = $data['invoice_type'] ?? ($depositFee > 0 ? 'move_in' : ($depositRefund > 0 ? 'move_out' : 'monthly'));

        $totalAmount = $this->calculateInvoiceTotal($roomFee, $electricFee, $waterFee, $serviceFee, $discount, $depositFee, $depositRefund);
        $dueDate = $data['due_date'] ?? date('Y-m-d', strtotime('+5 days'));
        $status = $data['status'] ?? $this->determinePaymentStatus($totalAmount, $paidAmount, $dueDate);

        $stmt = $this->db()->prepare("
            INSERT INTO invoices (
                invoice_code, invoice_type, contract_id, room_id, tenant_id, utility_id,
                month, year, room_fee, electric_fee, water_fee, service_fee,
                deposit_fee, deposit_refund, discount, total_amount, paid_amount, status, due_date, notes
            ) VALUES (
                :invoice_code, :invoice_type, :contract_id, :room_id, :tenant_id, :utility_id,
                :month, :year, :room_fee, :electric_fee, :water_fee, :service_fee,
                :deposit_fee, :deposit_refund, :discount, :total_amount, :paid_amount, :status, :due_date, :notes
            )
        ");

        $stmt->execute([
            'invoice_code'   => $data['invoice_code'],
            'invoice_type'   => $invoiceType,
            'contract_id'    => (int) $data['contract_id'],
            'room_id'        => (int) $data['room_id'],
            'tenant_id'      => (int) $data['tenant_id'],
            'utility_id'     => !empty($data['utility_id']) ? (int) $data['utility_id'] : null,
            'month'          => (int) $data['month'],
            'year'           => (int) $data['year'],
            'room_fee'       => $roomFee,
            'electric_fee'   => $electricFee,
            'water_fee'      => $waterFee,
            'service_fee'    => $serviceFee,
            'deposit_fee'    => $depositFee,
            'deposit_refund' => $depositRefund,
            'discount'       => $discount,
            'total_amount'   => $totalAmount,
            'paid_amount'    => $paidAmount,
            'status'         => $status,
            'due_date'       => $dueDate,
            'notes'          => trim($data['notes'] ?? ''),
        ]);

        return (int) $this->db()->lastInsertId();
    }

    public function recalculatePaymentStatus(int $invoiceId): void
    {
        $pdo = $this->db();
        $stmtSum = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = :invoice_id");
        $stmtSum->execute(['invoice_id' => $invoiceId]);
        $paidAmount = (float) $stmtSum->fetchColumn();

        $invoice = $this->findById($invoiceId);
        if (!$invoice) {
            return;
        }

        $totalAmount = (float) $invoice['total_amount'];
        $paid = min($paidAmount, $totalAmount);
        $status = $this->determinePaymentStatus($totalAmount, $paid, $invoice['due_date']);

        $stmtUpdate = $pdo->prepare("
            UPDATE invoices
            SET paid_amount = :paid_amount, status = :status
            WHERE id = :id
        ");
        $stmtUpdate->execute([
            'paid_amount' => $paid,
            'status'      => $status,
            'id'          => $invoiceId,
        ]);
    }

    public function getUnpaidInvoices(?int $buildingId = null): array
    {
        $bId = $buildingId ?? CoreHelper::currentBuildingId();
        $whereBuilding = '';
        $params = [];
        if (!empty($bId)) {
            $whereBuilding = ' AND r.building_id = :building_id';
            $params['building_id'] = (int) $bId;
        }

        $stmt = $this->db()->prepare("
            SELECT inv.*, r.room_number, r.building_id, b.building_name, t.full_name AS tenant_name,
                   (inv.total_amount - inv.paid_amount) AS remaining_amount
            FROM invoices inv
            INNER JOIN rooms r ON r.id = inv.room_id
            LEFT JOIN buildings b ON b.id = r.building_id
            INNER JOIN tenants t ON t.id = inv.tenant_id
            WHERE inv.status IN ('unpaid', 'partial', 'overdue')
            {$whereBuilding}
            ORDER BY inv.due_date ASC, inv.id DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db()->prepare("DELETE FROM invoices WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
