<?php

namespace App\Controllers;

use App\Helpers\CoreHelper;
use App\Models\ContractModel;
use App\Models\RoomModel;
use App\Models\UtilityModel;
use InvalidArgumentException;

class UtilityController
{
    private UtilityModel $utilityModel;
    private RoomModel $roomModel;
    private ContractModel $contractModel;

    public function __construct()
    {
        $this->utilityModel = new UtilityModel();
        $this->roomModel = new RoomModel();
        $this->contractModel = new ContractModel();
    }

    public function index(): void
    {
        $filters = [
            'month'   => $_GET['month'] ?? (int) date('n'),
            'year'    => $_GET['year'] ?? (int) date('Y'),
            'room_id' => $_GET['room_id'] ?? '',
        ];

        $utilities = $this->utilityModel->getAll($filters);
        $rooms = $this->roomModel->getAll();

        CoreHelper::view('utilities/index', [
            'pageTitle' => 'Chốt chỉ số Điện & Nước',
            'utilities' => $utilities,
            'rooms'     => $rooms,
            'filters'   => $filters,
        ]);
    }

    public function store(): void
    {
        $roomId = (int) ($_POST['room_id'] ?? 0);
        $month = (int) ($_POST['month'] ?? date('n'));
        $year = (int) ($_POST['year'] ?? date('Y'));

        if ($roomId <= 0 || $month < 1 || $month > 12 || $year < 2000) {
            CoreHelper::setFlash('danger', 'Vui lòng chọn phòng và kỳ tháng/năm hợp lệ.');
            CoreHelper::redirect('/utilities', ['month' => $month, 'year' => $year]);
        }

        if ($this->utilityModel->findByRoomAndPeriod($roomId, $month, $year)) {
            CoreHelper::setFlash('danger', "Phòng này đã được chốt chỉ số điện nước kỳ tháng {$month}/{$year}.");
            CoreHelper::redirect('/utilities', ['month' => $month, 'year' => $year]);
        }

        $activeContract = $this->contractModel->findActiveByRoomId($roomId);
        $data = $_POST;
        $data['contract_id'] = $activeContract['id'] ?? null;
        $data['electric_rate'] = $_POST['electric_rate'] ?? ($activeContract['electricity_rate'] ?? 3500);
        $data['water_rate'] = $_POST['water_rate'] ?? ($activeContract['water_rate'] ?? 15000);
        $data['other_fee'] = $_POST['other_fee'] ?? ($activeContract['service_fee'] ?? 150000);

        try {
            $this->utilityModel->create($data);
            CoreHelper::setFlash('success', "Đã lưu chỉ số điện nước tháng {$month}/{$year} thành công.");
        } catch (InvalidArgumentException $e) {
            CoreHelper::setFlash('danger', $e->getMessage());
        }

        CoreHelper::redirect('/utilities', ['month' => $month, 'year' => $year]);
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $utility = $this->utilityModel->findById($id);
        if (!$utility) {
            CoreHelper::setFlash('danger', 'Không tìm thấy bản ghi chỉ số điện nước.');
            CoreHelper::redirect('/utilities');
        }

        try {
            $this->utilityModel->update($id, $_POST);
            CoreHelper::setFlash('success', 'Cập nhật chỉ số điện nước thành công.');
        } catch (InvalidArgumentException $e) {
            CoreHelper::setFlash('danger', $e->getMessage());
        }

        CoreHelper::redirect('/utilities', [
            'month' => $utility['month'],
            'year'  => $utility['year'],
        ]);
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $this->utilityModel->delete($id);
        CoreHelper::setFlash('success', 'Đã xóa bản ghi điện nước.');
        CoreHelper::redirect('/utilities');
    }
}
