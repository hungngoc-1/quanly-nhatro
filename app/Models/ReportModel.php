<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class ReportModel
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
     * Tổng quan KPI trên Dashboard và Báo cáo
     */
    public function getOverviewStats(?int $year = null): array
    {
        $year = $year ?? (int) date('Y');
        $pdo = $this->db();

        $roomStats = (new RoomModel($pdo))->getCountsByStatus();

        $stmtTenants = $pdo->query("SELECT COUNT(*) FROM tenants WHERE status = 'staying'");
        $activeTenants = (int) $stmtTenants->fetchColumn();

        $stmtContracts = $pdo->query("SELECT COUNT(*) FROM contracts WHERE status = 'active'");
        $activeContracts = (int) $stmtContracts->fetchColumn();

        $stmtFinancial = $pdo->prepare("
            SELECT
                COALESCE(SUM(total_amount), 0) AS total_billed,
                COALESCE(SUM(paid_amount), 0) AS total_collected,
                COALESCE(SUM(total_amount - paid_amount), 0) AS total_unpaid,
                COUNT(CASE WHEN status IN ('unpaid', 'partial', 'overdue') THEN 1 END) AS unpaid_invoice_count
            FROM invoices
            WHERE year = :year AND status != 'cancelled'
        ");
        $stmtFinancial->execute(['year' => $year]);
        $fin = $stmtFinancial->fetch() ?: [];

        $occupancyRate = $roomStats['total'] > 0
            ? round(($roomStats['occupied'] / $roomStats['total']) * 100, 1)
            : 0.0;

        return [
            'rooms'                => $roomStats,
            'occupancy_rate'       => $occupancyRate,
            'active_tenants'       => $activeTenants,
            'active_contracts'     => $activeContracts,
            'total_billed'         => (float) ($fin['total_billed'] ?? 0),
            'total_collected'      => (float) ($fin['total_collected'] ?? 0),
            'total_unpaid'         => (float) ($fin['total_unpaid'] ?? 0),
            'unpaid_invoice_count' => (int) ($fin['unpaid_invoice_count'] ?? 0),
        ];
    }

    /**
     * Doanh thu theo từng tháng trong năm (1..12)
     */
    public function getMonthlyRevenue(int $year): array
    {
        $stmt = $this->db()->prepare("
            SELECT
                month,
                COALESCE(SUM(room_fee), 0) AS room_revenue,
                COALESCE(SUM(electric_fee), 0) AS electric_revenue,
                COALESCE(SUM(water_fee), 0) AS water_revenue,
                COALESCE(SUM(service_fee), 0) AS service_revenue,
                COALESCE(SUM(total_amount), 0) AS total_billed,
                COALESCE(SUM(paid_amount), 0) AS total_collected,
                COALESCE(SUM(total_amount - paid_amount), 0) AS total_debt
            FROM invoices
            WHERE year = :year AND status != 'cancelled'
            GROUP BY month
            ORDER BY month ASC
        ");
        $stmt->execute(['year' => $year]);
        $rows = $stmt->fetchAll();

        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(int) $row['month']] = $row;
        }

        $result = [];
        for ($m = 1; $m <= 12; $m++) {
            $r = $indexed[$m] ?? null;
            $result[] = [
                'month'            => $m,
                'room_revenue'     => (float) ($r['room_revenue'] ?? 0),
                'electric_revenue' => (float) ($r['electric_revenue'] ?? 0),
                'water_revenue'    => (float) ($r['water_revenue'] ?? 0),
                'service_revenue'  => (float) ($r['service_revenue'] ?? 0),
                'total_billed'     => (float) ($r['total_billed'] ?? 0),
                'total_collected'  => (float) ($r['total_collected'] ?? 0),
                'total_debt'       => (float) ($r['total_debt'] ?? 0),
            ];
        }

        return $result;
    }

    /**
     * Danh sách hợp đồng sắp hết hạn (trong vòng N ngày tới)
     */
    public function getExpiringContracts(int $days = 45): array
    {
        $stmt = $this->db()->prepare("
            SELECT c.*, r.room_number, t.full_name AS tenant_name, t.phone AS tenant_phone,
                   DATEDIFF(c.end_date, CURDATE()) AS days_left
            FROM contracts c
            INNER JOIN rooms r ON r.id = c.room_id
            INNER JOIN tenants t ON t.id = c.tenant_id
            WHERE c.status = 'active'
              AND DATEDIFF(c.end_date, CURDATE()) <= :days
            ORDER BY c.end_date ASC
        ");
        $stmt->execute(['days' => $days]);
        return $stmt->fetchAll();
    }
}
