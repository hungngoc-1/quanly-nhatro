<?php

namespace App\Controllers;

use App\Helpers\CoreHelper;
use App\Models\InvoiceModel;
use App\Models\ReportModel;
use App\Models\TenantModel;

class DashboardController
{
    public function index(): void
    {
        $user = CoreHelper::user();

        // Nếu là khách thuê thì hiển thị thông tin phòng và hóa đơn của khách thuê
        if (($user['role'] ?? '') === 'tenant') {
            $tenantModel = new TenantModel();
            $invoiceModel = new InvoiceModel();
            $tenantInfo = $tenantModel->findByUserId((int) $user['id']);
            $myInvoices = $invoiceModel->getByTenantUser((int) $user['id']);

            CoreHelper::view('dashboard/index', [
                'pageTitle'   => 'Trang chủ Khách thuê',
                'isTenant'    => true,
                'tenantInfo'  => $tenantInfo,
                'myInvoices'  => $myInvoices,
            ]);
            return;
        }

        $year = (int) ($_GET['year'] ?? date('Y'));
        $reportModel = new ReportModel();
        $invoiceModel = new InvoiceModel();

        $stats = $reportModel->getOverviewStats($year);
        $monthlyRevenue = $reportModel->getMonthlyRevenue($year);
        $expiringContracts = $reportModel->getExpiringContracts(60);
        $unpaidInvoices = $invoiceModel->getUnpaidInvoices();

        CoreHelper::view('dashboard/index', [
            'pageTitle'         => 'Tổng quan Quản lý Nhà trọ',
            'isTenant'          => false,
            'year'              => $year,
            'stats'             => $stats,
            'monthlyRevenue'    => $monthlyRevenue,
            'expiringContracts' => $expiringContracts,
            'unpaidInvoices'    => $unpaidInvoices,
        ]);
    }
}
