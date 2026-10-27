<?php

namespace App\Controllers;

use App\Helpers\CoreHelper;
use App\Helpers\Validator;
use App\Models\BuildingModel;
use App\Models\RoomModel;
use App\Models\RoomTypeModel;

class RoomController
{
    private RoomModel $roomModel;
    private RoomTypeModel $roomTypeModel;
    private BuildingModel $buildingModel;

    public function __construct()
    {
        $this->roomModel = new RoomModel();
        $this->roomTypeModel = new RoomTypeModel();
        $this->buildingModel = new BuildingModel();
    }

    public function index(): void
    {
        $filters = [
            'status'       => $_GET['status'] ?? '',
            'floor'        => $_GET['floor'] ?? '',
            'room_type_id' => $_GET['room_type_id'] ?? '',
            'keyword'      => $_GET['keyword'] ?? '',
        ];

        $rooms = $this->roomModel->getAll($filters);
        $counts = $this->roomModel->getCountsByStatus();
        $roomTypes = $this->roomTypeModel->getAll();
        $buildings = $this->buildingModel->getAll();

        CoreHelper::view('rooms/index', [
            'pageTitle' => 'Quản lý Phòng trọ & Loại phòng',
            'rooms'     => $rooms,
            'counts'    => $counts,
            'roomTypes' => $roomTypes,
            'buildings' => $buildings,
            'filters'   => $filters,
        ]);
    }

    public function store(): void
    {
        $validator = Validator::make($_POST, [
            'room_number'   => 'required|max:20',
            'floor'         => 'required|integer|min:1',
            'area'          => 'required|numeric|min:5',
            'price'         => 'required|numeric|min:100000',
            'max_occupants' => 'required|integer|min:1',
            'status'        => 'required|in:available,occupied,maintenance',
        ], [
            'room_number'   => 'Mã phòng',
            'floor'         => 'Tầng',
            'area'          => 'Diện tích',
            'price'         => 'Giá thuê',
            'max_occupants' => 'Số người tối đa',
            'status'        => 'Trạng thái',
        ]);

        if ($validator->fails()) {
            CoreHelper::setFlash('danger', $validator->firstError());
            CoreHelper::redirect('/rooms');
        }

        if ($this->roomModel->findByRoomNumber($_POST['room_number'])) {
            CoreHelper::setFlash('danger', 'Mã phòng "' . strtoupper(trim($_POST['room_number'])) . '" đã tồn tại.');
            CoreHelper::redirect('/rooms');
        }

        $this->roomModel->create($_POST);
        CoreHelper::setFlash('success', 'Thêm phòng trọ mới thành công.');
        CoreHelper::redirect('/rooms');
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0 || !$this->roomModel->findById($id)) {
            CoreHelper::setFlash('danger', 'Không tìm thấy phòng trọ cần cập nhật.');
            CoreHelper::redirect('/rooms');
        }

        $validator = Validator::make($_POST, [
            'room_number'   => 'required|max:20',
            'floor'         => 'required|integer|min:1',
            'area'          => 'required|numeric|min:5',
            'price'         => 'required|numeric|min:100000',
            'max_occupants' => 'required|integer|min:1',
            'status'        => 'required|in:available,occupied,maintenance',
        ], [
            'room_number'   => 'Mã phòng',
            'floor'         => 'Tầng',
            'area'          => 'Diện tích',
            'price'         => 'Giá thuê',
            'max_occupants' => 'Số người tối đa',
            'status'        => 'Trạng thái',
        ]);

        if ($validator->fails()) {
            CoreHelper::setFlash('danger', $validator->firstError());
            CoreHelper::redirect('/rooms');
        }

        if ($this->roomModel->findByRoomNumber($_POST['room_number'], $id)) {
            CoreHelper::setFlash('danger', 'Mã phòng đã tồn tại ở phòng khác.');
            CoreHelper::redirect('/rooms');
        }

        $this->roomModel->update($id, $_POST);
        CoreHelper::setFlash('success', 'Cập nhật thông tin phòng thành công.');
        CoreHelper::redirect('/rooms');
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        try {
            $this->roomModel->delete($id);
            CoreHelper::setFlash('success', 'Đã xóa phòng trọ thành công.');
        } catch (\Throwable $e) {
            CoreHelper::setFlash('danger', 'Không thể xóa phòng đang có hợp đồng hoặc hóa đơn liên kết.');
        }
        CoreHelper::redirect('/rooms');
    }

    public function storeRoomType(): void
    {
        $validator = Validator::make($_POST, [
            'type_code'     => 'required|max:30',
            'type_name'     => 'required|max:100',
            'default_price' => 'required|numeric|min:100000',
        ], [
            'type_code'     => 'Mã loại phòng',
            'type_name'     => 'Tên loại phòng',
            'default_price' => 'Giá tham chiếu',
        ]);

        if ($validator->fails()) {
            CoreHelper::setFlash('danger', $validator->firstError());
            CoreHelper::redirect('/rooms');
        }

        try {
            $this->roomTypeModel->create($_POST);
            CoreHelper::setFlash('success', 'Đã thêm loại phòng mới thành công.');
        } catch (\Throwable $e) {
            CoreHelper::setFlash('danger', 'Mã loại phòng đã tồn tại hoặc dữ liệu không hợp lệ.');
        }

        CoreHelper::redirect('/rooms');
    }

    public function updateRoomType(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0 || !$this->roomTypeModel->findById($id)) {
            CoreHelper::setFlash('danger', 'Không tìm thấy loại phòng.');
            CoreHelper::redirect('/rooms');
        }

        try {
            $this->roomTypeModel->update($id, $_POST);
            CoreHelper::setFlash('success', 'Cập nhật loại phòng thành công.');
        } catch (\Throwable $e) {
            CoreHelper::setFlash('danger', 'Không thể cập nhật loại phòng.');
        }

        CoreHelper::redirect('/rooms');
    }

    public function deleteRoomType(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        try {
            $this->roomTypeModel->delete($id);
            CoreHelper::setFlash('success', 'Đã xóa loại phòng thành công.');
        } catch (\Throwable $e) {
            CoreHelper::setFlash('danger', 'Không thể xóa loại phòng đang được gán cho phòng trọ.');
        }
        CoreHelper::redirect('/rooms');
    }
}
