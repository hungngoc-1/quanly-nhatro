<?php

namespace App\Controllers;

use App\Helpers\CoreHelper;
use App\Models\ContractModel;
use App\Models\InvoiceModel;
use App\Models\PaymentModel;
use App\Models\RoomModel;
use App\Models\UtilityModel;
use InvalidArgumentException;

class InvoiceController
{
    private InvoiceModel $invoiceModel;
    private ContractModel $contractModel;
    private UtilityModel $utilityModel;
    private RoomModel $roomModel;
    private PaymentModel $paymentModel;

    public function __construct()
    {
        $this->invoiceModel = new InvoiceModel();
        $this->contractModel = new ContractModel();
        $this->utilityModel = new UtilityModel();
        $this->roomModel = new RoomModel();
        $this->paymentModel = new PaymentModel();
    }

    /**
     * Trang quản lý & lập hóa đơn mới (app/Views/invoices/create.php)
     */
    public function create(): void
    {
        $filters = [
            'month'   => $_GET['month'] ?? '',
            'year'    => $_GET['year'] ?? (int) date('Y'),
            'status'  => $_GET['status'] ?? '',
            'keyword' => $_GET['keyword'] ?? '',
        ];

        $invoices = $this->invoiceModel->getAll($filters);
        $activeContracts = $this->contractModel->getActiveContracts();

        // Nếu chuyển từ trang Điện Nước sang để tạo nhanh hóa đơn
        $prefillUtility = null;
        if (!empty($_GET['utility_id'])) {
            $prefillUtility = $this->utilityModel->findById((int) $_GET['utility_id']);
        }

        CoreHelper::view('invoices/create', [
            'pageTitle'       => 'Quản lý & Lập Hóa đơn Tiền trọ',
            'invoices'        => $invoices,
            'activeContracts' => $activeContracts,
            'filters'         => $filters,
            'prefillUtility'  => $prefillUtility,
        ]);
    }

    /**
     * Lưu hóa đơn mới
     */
    public function store(): void
    {
        $contractId = (int) ($_POST['contract_id'] ?? 0);
        $month = (int) ($_POST['month'] ?? date('n'));
        $year = (int) ($_POST['year'] ?? date('Y'));

        $contract = $this->contractModel->findById($contractId);
        if (!$contract) {
            CoreHelper::setFlash('danger', 'Vui lòng chọn hợp đồng / phòng hợp lệ.');
            CoreHelper::redirect('/invoices/create');
        }

        $roomId = (int) $contract['room_id'];
        if ($this->invoiceModel->findByRoomAndPeriod($roomId, $month, $year)) {
            CoreHelper::setFlash('danger', "Phòng {$contract['room_number']} đã có hóa đơn kỳ tháng {$month}/{$year}.");
            CoreHelper::redirect('/invoices/create', ['month' => $month, 'year' => $year]);
        }

        $utility = $this->utilityModel->findByRoomAndPeriod($roomId, $month, $year);
        $roomFee = isset($_POST['room_fee']) && $_POST['room_fee'] !== ''
            ? (float) $_POST['room_fee']
            : (float) $contract['monthly_rent'];
        $electricFee = isset($_POST['electric_fee']) && $_POST['electric_fee'] !== ''
            ? (float) $_POST['electric_fee']
            : (float) ($utility['electric_amount'] ?? 0);
        $waterFee = isset($_POST['water_fee']) && $_POST['water_fee'] !== ''
            ? (float) $_POST['water_fee']
            : (float) ($utility['water_amount'] ?? 0);
        $serviceFee = isset($_POST['service_fee']) && $_POST['service_fee'] !== ''
            ? (float) $_POST['service_fee']
            : (float) ($utility['other_fee'] ?? $contract['service_fee'] ?? 150000);
        $discount = (float) ($_POST['discount'] ?? 0);

        try {
            $invoiceCode = $this->invoiceModel->generateInvoiceCode($contract['room_number'], $month, $year);
            $invoiceId = $this->invoiceModel->create([
                'invoice_code' => $invoiceCode,
                'contract_id'  => $contractId,
                'room_id'      => $roomId,
                'tenant_id'    => (int) $contract['tenant_id'],
                'utility_id'   => $utility['id'] ?? null,
                'month'        => $month,
                'year'         => $year,
                'room_fee'     => $roomFee,
                'electric_fee' => $electricFee,
                'water_fee'    => $waterFee,
                'service_fee'  => $serviceFee,
                'discount'     => $discount,
                'due_date'     => !empty($_POST['due_date']) ? $_POST['due_date'] : date('Y-m-d', strtotime('+5 days')),
                'notes'        => $_POST['notes'] ?? '',
            ]);

            CoreHelper::setFlash('success', "Đã lập hóa đơn {$invoiceCode} thành công.");
            CoreHelper::redirect('/invoices/detail', ['id' => $invoiceId]);
        } catch (InvalidArgumentException $e) {
            CoreHelper::setFlash('danger', $e->getMessage());
            CoreHelper::redirect('/invoices/create');
        }
    }

    /**
     * Lập hóa đơn hàng loạt cho tất cả hợp đồng đang hoạt động trong tháng/năm
     */
    public function generateBatch(): void
    {
        $month = (int) ($_POST['month'] ?? date('n'));
        $year = (int) ($_POST['year'] ?? date('Y'));
        $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : date('Y-m-d', strtotime('+5 days'));

        $activeContracts = $this->contractModel->getActiveContracts();
        $createdCount = 0;

        foreach ($activeContracts as $contract) {
            $roomId = (int) $contract['room_id'];
            if ($this->invoiceModel->findByRoomAndPeriod($roomId, $month, $year)) {
                continue;
            }

            $utility = $this->utilityModel->findByRoomAndPeriod($roomId, $month, $year);
            $invoiceCode = $this->invoiceModel->generateInvoiceCode($contract['room_number'], $month, $year);

            $this->invoiceModel->create([
                'invoice_code' => $invoiceCode,
                'contract_id'  => (int) $contract['id'],
                'room_id'      => $roomId,
                'tenant_id'    => (int) $contract['tenant_id'],
                'utility_id'   => $utility['id'] ?? null,
                'month'        => $month,
                'year'         => $year,
                'room_fee'     => (float) $contract['monthly_rent'],
                'electric_fee' => (float) ($utility['electric_amount'] ?? 0),
                'water_fee'    => (float) ($utility['water_amount'] ?? 0),
                'service_fee'  => (float) ($utility['other_fee'] ?? $contract['service_fee'] ?? 150000),
                'discount'     => 0,
                'due_date'     => $dueDate,
                'notes'        => "Hóa đơn tiền phòng tháng {$month}/{$year}",
            ]);
            $createdCount++;
        }

        if ($createdCount > 0) {
            CoreHelper::setFlash('success', "Đã tạo tự động {$createdCount} hóa đơn kỳ tháng {$month}/{$year}.");
        } else {
            CoreHelper::setFlash('info', "Tất cả các phòng đang thuê đều đã có hóa đơn kỳ tháng {$month}/{$year}.");
        }

        CoreHelper::redirect('/invoices/create', ['month' => $month, 'year' => $year]);
    }

    /**
     * Danh sách hóa đơn của tôi dành cho Khách thuê (app/Views/invoices/my_list.php)
     */
    public function myList(): void
    {
        $user = CoreHelper::user();
        $filters = [
            'status' => $_GET['status'] ?? '',
            'year'   => $_GET['year'] ?? '',
        ];

        if (($user['role'] ?? '') === 'tenant') {
            $invoices = $this->invoiceModel->getByTenantUser((int) $user['id'], $filters);
        } else {
            $invoices = $this->invoiceModel->getAll($filters);
        }

        CoreHelper::view('invoices/my_list', [
            'pageTitle' => 'Hóa đơn Tiền phòng của tôi',
            'invoices'  => $invoices,
            'filters'   => $filters,
        ]);
    }

    /**
     * Chi tiết hóa đơn & In phiếu báo tiền trọ (app/Views/invoices/detail.php)
     */
    public function detail(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $invoice = $this->invoiceModel->findById($id);

        if (!$invoice) {
            CoreHelper::setFlash('danger', 'Không tìm thấy hóa đơn yêu cầu.');
            CoreHelper::redirect('/invoices/my-list');
        }

        $user = CoreHelper::user();
        if (($user['role'] ?? '') === 'tenant' && (int) ($invoice['tenant_user_id'] ?? 0) !== (int) $user['id']) {
            CoreHelper::setFlash('danger', 'Bạn chỉ được phép xem hóa đơn thuộc phòng của mình.');
            CoreHelper::redirect('/invoices/my-list');
        }

        $payments = $this->paymentModel->getByInvoiceId($id);

        CoreHelper::view('invoices/detail', [
            'pageTitle' => 'Chi tiết Hóa đơn ' . $invoice['invoice_code'],
            'invoice'   => $invoice,
            'payments'  => $payments,
        ]);
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $this->invoiceModel->delete($id);
        CoreHelper::setFlash('success', 'Đã xóa hóa đơn thành công.');
        CoreHelper::redirect('/invoices/create');
    }
}
