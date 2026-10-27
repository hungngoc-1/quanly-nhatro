<?php

namespace App\Controllers;

use App\Helpers\CoreHelper;
use App\Models\ContractModel;
use App\Models\RoomModel;
use App\Models\TenantModel;

class ContractController
{
    private ContractModel $contractModel;
    private RoomModel $roomModel;
    private TenantModel $tenantModel;

    public function __construct()
    {
        $this->contractModel = new ContractModel();
        $this->roomModel = new RoomModel();
        $this->tenantModel = new TenantModel();
    }

    public function index(): void
    {
        $filters = [
            'status'  => $_GET['status'] ?? '',
            'keyword' => $_GET['keyword'] ?? '',
        ];

        $contracts = $this->contractModel->getAll($filters);
        $rooms = $this->roomModel->getAll();
        $tenants = $this->tenantModel->getAll();

        CoreHelper::view('contracts/index', [
            'pageTitle' => 'Quản lý Hợp đồng Thuê phòng',
            'contracts' => $contracts,
            'rooms'     => $rooms,
            'tenants'   => $tenants,
            'filters'   => $filters,
        ]);
    }

    public function store(): void
    {
        $errors = $this->contractModel->validateContractData($_POST);
        if (!empty($errors)) {
            CoreHelper::setFlash('danger', reset($errors));
            CoreHelper::redirect('/contracts');
        }

        $roomId = (int) $_POST['room_id'];
        $existingActive = $this->contractModel->findActiveByRoomId($roomId);
        if ($existingActive) {
            CoreHelper::setFlash('danger', 'Phòng này đang có hợp đồng hiệu lực (' . $existingActive['contract_code'] . '). Vui lòng thanh lý trước khi tạo mới.');
            CoreHelper::redirect('/contracts');
        }

        $contractId = $this->contractModel->create($_POST);
        $contract = $this->contractModel->findById($contractId);
        $moveInDate = !empty($_POST['move_in_date']) ? $_POST['move_in_date'] : ($contract['start_date'] ?? $_POST['start_date']);
        $moveInCalc = $this->contractModel->calculateMoveInAmounts(
            (float) ($contract['monthly_rent'] ?? $_POST['monthly_rent']),
            (float) ($contract['service_fee'] ?? ($_POST['service_fee'] ?? 150000)),
            (string) $moveInDate
        );

        CoreHelper::setFlash(
            'success',
            'Đã lập hợp đồng ' . ($contract['contract_code'] ?? '') . '! ' . ($moveInCalc['rule_description'] ?? '')
        );
        CoreHelper::redirect('/contracts/detail', ['id' => $contractId]);
    }

    /**
     * Xem bản hợp đồng đầy đủ các điều khoản, Ký điện tử (Online) hoặc In ký giấy & Lưu bản Scan
     */
    public function detail(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $contract = $this->contractModel->findById($id);

        if (!$contract) {
            CoreHelper::setFlash('danger', 'Không tìm thấy hợp đồng.');
            CoreHelper::redirect('/contracts');
        }

        $user = CoreHelper::user();
        if (($user['role'] ?? '') === 'tenant' && (int) ($contract['tenant_user_id'] ?? 0) !== (int) $user['id']) {
            CoreHelper::setFlash('danger', 'Bạn chỉ được xem hợp đồng của mình.');
            CoreHelper::redirect('/dashboard');
        }

        CoreHelper::view('contracts/detail', [
            'pageTitle' => 'Hợp đồng ' . $contract['contract_code'],
            'contract'  => $contract,
        ]);
    }

    /**
     * Ký hợp đồng điện tử (Online)
     */
    public function signOnline(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $contract = $this->contractModel->findById($id);
        if (!$contract) {
            CoreHelper::setFlash('danger', 'Không tìm thấy hợp đồng.');
            CoreHelper::redirect('/contracts');
        }

        $user = CoreHelper::user();
        if (($user['role'] ?? '') === 'tenant' && (int) ($contract['tenant_user_id'] ?? 0) !== (int) $user['id']) {
            CoreHelper::setFlash('danger', 'Bạn không có quyền ký hợp đồng này.');
            CoreHelper::redirect('/dashboard');
        }

        $tenantSig = !empty($_POST['tenant_signature']) ? $_POST['tenant_signature'] : null;
        $managerSig = !empty($_POST['manager_signature']) ? $_POST['manager_signature'] : null;

        if ($this->contractModel->saveOnlineSignature($id, $tenantSig, $managerSig)) {
            CoreHelper::setFlash('success', 'Đã lưu chữ ký hợp đồng điện tử thành công.');
        } else {
            CoreHelper::setFlash('danger', 'Không thể lưu chữ ký.');
        }

        CoreHelper::redirect('/contracts/detail', ['id' => $id]);
    }

    /**
     * Tải lên và lưu bản Scan hợp đồng ký giấy
     */
    public function uploadScan(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $contract = $this->contractModel->findById($id);
        if (!$contract) {
            CoreHelper::setFlash('danger', 'Không tìm thấy hợp đồng.');
            CoreHelper::redirect('/contracts');
        }

        if (empty($_FILES['scan_file']['tmp_name']) || $_FILES['scan_file']['error'] !== UPLOAD_ERR_OK) {
            CoreHelper::setFlash('danger', 'Vui lòng chọn file ảnh hoặc PDF bản scan hợp đồng.');
            CoreHelper::redirect('/contracts/detail', ['id' => $id]);
        }

        $ext = strtolower(pathinfo($_FILES['scan_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        if (!in_array($ext, $allowed, true)) {
            CoreHelper::setFlash('danger', 'Chỉ hỗ trợ file ảnh (JPG, PNG, WEBP) hoặc PDF.');
            CoreHelper::redirect('/contracts/detail', ['id' => $id]);
        }

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/contracts';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = 'scan_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $contract['contract_code']) . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . '/' . $fileName;

        if (move_uploaded_file($_FILES['scan_file']['tmp_name'], $targetPath)) {
            $this->contractModel->saveScanFile($id, 'uploads/contracts/' . $fileName);
            CoreHelper::setFlash('success', 'Đã lưu bản Scan hợp đồng ký giấy thành công.');
        } else {
            CoreHelper::setFlash('danger', 'Không thể lưu file tải lên.');
        }

        CoreHelper::redirect('/contracts/detail', ['id' => $id]);
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0 || !$this->contractModel->findById($id)) {
            CoreHelper::setFlash('danger', 'Không tìm thấy hợp đồng.');
            CoreHelper::redirect('/contracts');
        }

        $this->contractModel->update($id, $_POST);
        CoreHelper::setFlash('success', 'Cập nhật hợp đồng thành công (Tiền cọc luôn bằng 1 tháng tiền phòng).');
        CoreHelper::redirect('/contracts');
    }

    public function terminate(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $moveOutDate = !empty($_POST['move_out_date']) ? $_POST['move_out_date'] : date('Y-m-d');
        $reason = trim($_POST['termination_reason'] ?? '');

        $contractBefore = $this->contractModel->findById($id);
        if ($contractBefore && $this->contractModel->terminate($id, $moveOutDate, $reason)) {
            $deposit = (float) ($contractBefore['deposit_amount'] ?? $contractBefore['monthly_rent']);
            if ($moveOutDate >= $contractBefore['end_date']) {
                CoreHelper::setFlash(
                    'success',
                    'Đã thanh lý hợp đồng đúng/sau hạn (' . CoreHelper::formatDate($moveOutDate) . '). Khách thuê ĐƯỢC HOÀN LẠI CỌC ' . CoreHelper::formatCurrency($deposit) . ' và đã ghi nhận vào hóa đơn trả phòng.'
                );
            } else {
                CoreHelper::setFlash(
                    'warning',
                    'Đã thanh lý hợp đồng trước hạn (' . CoreHelper::formatDate($moveOutDate) . ' < ' . CoreHelper::formatDate($contractBefore['end_date']) . '). Khách thuê MẤT TIỀN CỌC ' . CoreHelper::formatCurrency($deposit) . ' và đã ghi nhận vào hóa đơn trả phòng.'
                );
            }
        } else {
            CoreHelper::setFlash('danger', 'Không thể thanh lý hợp đồng.');
        }
        CoreHelper::redirect('/contracts');
    }

    /**
     * Khách thuê phản hồi thông báo gia hạn hợp đồng trước 1 tháng trên trang Tổng quan
     */
    public function tenantRenew(): void
    {
        $contractId = (int) ($_POST['contract_id'] ?? 0);
        $decision = $_POST['decision'] ?? '';
        $months = (int) ($_POST['renewal_months'] ?? 6);

        $contract = $this->contractModel->findById($contractId);
        if (!$contract || $contract['status'] !== 'active') {
            CoreHelper::setFlash('danger', 'Không tìm thấy hợp đồng hiệu lực để phản hồi gia hạn.');
            CoreHelper::redirect('/dashboard');
        }

        $user = CoreHelper::user();
        if (($user['role'] ?? '') === 'tenant') {
            $tenant = $this->tenantModel->findByUserId((int) $user['id']);
            if (!$tenant || (int) ($tenant['id'] ?? 0) !== (int) $contract['tenant_id']) {
                CoreHelper::setFlash('danger', 'Bạn chỉ được phép phản hồi gia hạn cho hợp đồng của chính mình.');
                CoreHelper::redirect('/dashboard');
            }
        }

        if ($this->contractModel->respondRenewal($contractId, $decision, $months)) {
            if ($decision === 'accept') {
                $updated = $this->contractModel->findById($contractId);
                CoreHelper::setFlash(
                    'success',
                    'Bạn đã ĐỒNG Ý ở tiếp! Hợp đồng ' . $contract['contract_code'] . ' đã được tự động gia hạn thêm ' . max(1, $months) . ' tháng (đến ngày ' . CoreHelper::formatDate($updated['end_date'] ?? '') . ').'
                );
            } else {
                CoreHelper::setFlash(
                    'info',
                    'Đã ghi nhận phản hồi KHÔNG GIA HẠN hợp đồng ' . $contract['contract_code'] . '. Hợp đồng sẽ kết thúc vào ngày ' . CoreHelper::formatDate($contract['end_date']) . '.'
                );
            }
        } else {
            CoreHelper::setFlash('danger', 'Không thể xử lý phản hồi gia hạn hợp đồng.');
        }

        $redirectTo = $_POST['redirect_to'] ?? '/dashboard';
        CoreHelper::redirect($redirectTo);
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        try {
            $this->contractModel->delete($id);
            CoreHelper::setFlash('success', 'Đã xóa hợp đồng thành công.');
        } catch (\Throwable $e) {
            CoreHelper::setFlash('danger', 'Không thể xóa hợp đồng đã phát sinh hóa đơn.');
        }
        CoreHelper::redirect('/contracts');
    }
}
