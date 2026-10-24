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

        $this->contractModel->create($_POST);
        CoreHelper::setFlash('success', 'Lập hợp đồng thuê phòng mới thành công.');
        CoreHelper::redirect('/contracts');
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0 || !$this->contractModel->findById($id)) {
            CoreHelper::setFlash('danger', 'Không tìm thấy hợp đồng.');
            CoreHelper::redirect('/contracts');
        }

        $this->contractModel->update($id, $_POST);
        CoreHelper::setFlash('success', 'Cập nhật hợp đồng thành công.');
        CoreHelper::redirect('/contracts');
    }

    public function terminate(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($this->contractModel->terminate($id)) {
            CoreHelper::setFlash('success', 'Đã thanh lý hợp đồng và cập nhật trạng thái phòng trống.');
        } else {
            CoreHelper::setFlash('danger', 'Không thể thanh lý hợp đồng.');
        }
        CoreHelper::redirect('/contracts');
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
