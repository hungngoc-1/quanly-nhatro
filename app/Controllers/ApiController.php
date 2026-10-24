<?php

namespace App\Controllers;

use App\Helpers\CoreHelper;
use App\Models\ContractModel;
use App\Models\InvoiceModel;
use App\Models\ReportModel;
use App\Models\RoomModel;
use App\Models\UtilityModel;

class ApiController
{
    /**
     * API trả về thông tin phòng + hợp đồng đang hiệu lực + chỉ số điện nước gần nhất
     * Phục vụ tự động điền (Auto-fill) khi chốt điện nước hoặc tạo hóa đơn
     */
    public function roomInfo(): never
    {
        $roomId = (int) ($_GET['room_id'] ?? 0);
        $month = (int) ($_GET['month'] ?? date('n'));
        $year = (int) ($_GET['year'] ?? date('Y'));

        if ($roomId <= 0) {
            CoreHelper::json(['success' => false, 'message' => 'Thiếu room_id'], 400);
        }

        $roomModel = new RoomModel();
        $contractModel = new ContractModel();
        $utilityModel = new UtilityModel();

        $room = $roomModel->findById($roomId);
        if (!$room) {
            CoreHelper::json(['success' => false, 'message' => 'Không tìm thấy phòng'], 404);
        }

        $activeContract = $contractModel->findActiveByRoomId($roomId);
        $latestUtility = $utilityModel->getLatestReadingByRoom($roomId);
        $periodUtility = $utilityModel->findByRoomAndPeriod($roomId, $month, $year);

        CoreHelper::json([
            'success'         => true,
            'room'            => $room,
            'active_contract' => $activeContract,
            'latest_utility'  => $latestUtility,
            'period_utility'  => $periodUtility,
        ]);
    }

    /**
     * API trả về thông tin chi tiết hóa đơn phục vụ modal thu tiền nhanh
     */
    public function invoiceInfo(): never
    {
        $invoiceId = (int) ($_GET['invoice_id'] ?? 0);
        $invoiceModel = new InvoiceModel();
        $invoice = $invoiceModel->findById($invoiceId);

        if (!$invoice) {
            CoreHelper::json(['success' => false, 'message' => 'Không tìm thấy hóa đơn'], 404);
        }

        CoreHelper::json([
            'success' => true,
            'invoice' => $invoice,
        ]);
    }

    /**
     * API trả về dữ liệu thống kê tổng quan dạng JSON
     */
    public function stats(): never
    {
        $year = (int) ($_GET['year'] ?? date('Y'));
        $reportModel = new ReportModel();

        CoreHelper::json([
            'success'         => true,
            'year'            => $year,
            'overview'        => $reportModel->getOverviewStats($year),
            'monthly_revenue' => $reportModel->getMonthlyRevenue($year),
        ]);
    }
}
