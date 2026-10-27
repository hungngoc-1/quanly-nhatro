<?php

namespace App\Controllers;

use App\Helpers\CoreHelper;
use App\Helpers\Validator;
use App\Models\BuildingModel;
use App\Models\UserModel;

class BuildingController
{
    private BuildingModel $buildingModel;
    private UserModel $userModel;

    public function __construct()
    {
        $this->buildingModel = new BuildingModel();
        $this->userModel = new UserModel();
    }

    /**
     * Trang điều hướng & quản lý Tòa nhà dành riêng cho Quản trị viên (Admin)
     */
    public function index(): void
    {
        $buildings = $this->buildingModel->getAll();
        $managers = $this->userModel->getManagers();
        $selectedBuildingId = CoreHelper::currentBuildingId();

        CoreHelper::view('buildings/index', [
            'pageTitle'          => 'Điều hướng & Quản lý Tòa nhà',
            'buildings'          => $buildings,
            'managers'           => $managers,
            'selectedBuildingId' => $selectedBuildingId,
        ]);
    }

    /**
     * Điều hướng Admin tới 1 tòa nhà cụ thể hoặc xem tất cả tòa nhà (id = 0)
     */
    public function select(): void
    {
        $id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
        $redirectTo = $_REQUEST['redirect_to'] ?? '/dashboard';

        if ($id > 0) {
            $building = $this->buildingModel->findById($id);
            if ($building) {
                CoreHelper::setSelectedBuildingId($id);
                CoreHelper::setFlash('success', 'Đã điều hướng sang: ' . $building['building_name']);
            } else {
                CoreHelper::setFlash('danger', 'Không tìm thấy tòa nhà được chọn.');
            }
        } else {
            CoreHelper::setSelectedBuildingId(null);
            CoreHelper::setFlash('info', 'Đang xem chế độ Toàn hệ thống (Tất cả các tòa nhà).');
        }

        CoreHelper::redirect($redirectTo);
    }

    public function store(): void
    {
        $validator = Validator::make($_POST, [
            'building_code' => 'required|max:20',
            'building_name' => 'required|max:120',
        ], [
            'building_code' => 'Mã tòa nhà',
            'building_name' => 'Tên tòa nhà',
        ]);

        if ($validator->fails()) {
            CoreHelper::setFlash('danger', $validator->firstError());
            CoreHelper::redirect('/buildings');
        }

        try {
            $buildingId = $this->buildingModel->create($_POST);
            if (!empty($_POST['manager_id'])) {
                $this->syncManagerBuilding((int) $_POST['manager_id'], $buildingId);
            }
            CoreHelper::setFlash('success', 'Thêm tòa nhà mới thành công.');
        } catch (\Throwable $e) {
            CoreHelper::setFlash('danger', 'Mã tòa nhà đã tồn tại hoặc dữ liệu không hợp lệ.');
        }

        CoreHelper::redirect('/buildings');
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0 || !$this->buildingModel->findById($id)) {
            CoreHelper::setFlash('danger', 'Không tìm thấy tòa nhà.');
            CoreHelper::redirect('/buildings');
        }

        try {
            $this->buildingModel->update($id, $_POST);
            if (!empty($_POST['manager_id'])) {
                $this->syncManagerBuilding((int) $_POST['manager_id'], $id);
            }
            CoreHelper::setFlash('success', 'Cập nhật thông tin tòa nhà thành công.');
        } catch (\Throwable $e) {
            CoreHelper::setFlash('danger', 'Không thể cập nhật tòa nhà.');
        }

        CoreHelper::redirect('/buildings');
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        try {
            $this->buildingModel->delete($id);
            if (CoreHelper::currentBuildingId() === $id) {
                CoreHelper::setSelectedBuildingId(null);
            }
            CoreHelper::setFlash('success', 'Đã xóa tòa nhà thành công.');
        } catch (\Throwable $e) {
            CoreHelper::setFlash('danger', 'Không thể xóa tòa nhà đang có phòng trọ trực thuộc.');
        }

        CoreHelper::redirect('/buildings');
    }

    private function syncManagerBuilding(int $managerId, int $buildingId): void
    {
        $db = \App\Config\Database::getConnection();
        $stmt = $db->prepare("UPDATE users SET building_id = :building_id WHERE id = :id AND role = 'staff'");
        $stmt->execute([
            'building_id' => $buildingId,
            'id'          => $managerId,
        ]);
    }
}
