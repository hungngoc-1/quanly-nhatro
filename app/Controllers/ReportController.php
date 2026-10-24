<?php

namespace App\Controllers;

use App\Helpers\CoreHelper;
use App\Models\InvoiceModel;
use App\Models\ReportModel;

class ReportController
{
    private ReportModel $reportModel;
    private InvoiceModel $invoiceModel;

    public function __construct()
    {
        $this->reportModel = new ReportModel();
        $this->invoiceModel = new InvoiceModel();
    }

    public function index(): void
    {
        $year = (int) ($_GET['year'] ?? date('Y'));
        $stats = $this->reportModel->getOverviewStats($year);
        $monthlyRevenue = $this->reportModel->getMonthlyRevenue($year);
        $expiringContracts = $this->reportModel->getExpiringContracts(60);
        $unpaidInvoices = $this->invoiceModel->getUnpaidInvoices();

        CoreHelper::view('reports/index', [
            'pageTitle'         => 'Báo cáo Doanh thu & Công nợ Năm ' . $year,
            'year'              => $year,
            'stats'             => $stats,
            'monthlyRevenue'    => $monthlyRevenue,
            'expiringContracts' => $expiringContracts,
            'unpaidInvoices'    => $unpaidInvoices,
        ]);
    }

    public function exportCsv(): never
    {
        $year = (int) ($_GET['year'] ?? date('Y'));
        $monthlyRevenue = $this->reportModel->getMonthlyRevenue($year);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="bao_cao_doanh_thu_' . $year . '.csv"');

        $output = fopen('php://output', 'w');
        // Thêm UTF-8 BOM để mở tiếng Việt chuẩn trên Microsoft Excel
        fwrite($output, "\xEF\xBB\xBF");

        fputcsv($output, [
            'Tháng',
            'Tiền phòng (VNĐ)',
            'Tiền điện (VNĐ)',
            'Tiền nước (VNĐ)',
            'Phí dịch vụ (VNĐ)',
            'Tổng phát sinh (VNĐ)',
            'Đã thu (VNĐ)',
            'Còn nợ (VNĐ)',
        ]);

        foreach ($monthlyRevenue as $row) {
            fputcsv($output, [
                'Tháng ' . $row['month'] . '/' . $year,
                $row['room_revenue'],
                $row['electric_revenue'],
                $row['water_revenue'],
                $row['service_revenue'],
                $row['total_billed'],
                $row['total_collected'],
                $row['total_debt'],
            ]);
        }

        fclose($output);
        exit;
    }
}
