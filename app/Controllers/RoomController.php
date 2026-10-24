<?php

namespace App\Controllers;

use App\Helpers\CoreHelper;
use App\Helpers\Validator;
use App\Models\RoomModel;

class RoomController
{
    private RoomModel $roomModel;

    public function __construct()
    {
        $this->roomModel = new RoomModel();
    }

    public function index(): void
    {
        $filters = [
            'status'  => $_GET['status'] ?? '',
            'floor'   => $_GET['floor'] ?? '',
            'keyword' => $_GET['keyword'] ?? '',
        ];

        $rooms = $this->roomModel->getAll($filters);
        $counts = $this->roomModel->getCountsByStatus();

        CoreHelper::view('rooms/index', [
            'pageTitle' => 'Quản lý Phòng trọ',
            'rooms'     => $rooms,
            'counts'    => $counts,
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
}
