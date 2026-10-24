<?php

namespace App\Controllers;

use App\Helpers\CoreHelper;
use App\Helpers\Validator;
use App\Models\TenantModel;
use App\Models\UserModel;

class TenantController
{
    private TenantModel $tenantModel;
    private UserModel $userModel;

    public function __construct()
    {
        $this->tenantModel = new TenantModel();
        $this->userModel = new UserModel();
    }

    public function index(): void
    {
        $filters = [
            'status'  => $_GET['status'] ?? '',
            'keyword' => $_GET['keyword'] ?? '',
        ];

        $tenants = $this->tenantModel->getAll($filters);

        CoreHelper::view('tenants/index', [
            'pageTitle' => 'Quản lý Khách thuê',
            'tenants'   => $tenants,
            'filters'   => $filters,
        ]);
    }

    public function store(): void
    {
        $validator = Validator::make($_POST, [
            'full_name' => 'required|min:2|max:100',
            'cccd'      => 'required|cccd',
            'phone'     => 'required|phone',
            'email'     => 'email',
            'status'    => 'required|in:staying,moved_out',
        ], [
            'full_name' => 'Họ và tên',
            'cccd'      => 'Số CCCD/CMND',
            'phone'     => 'Số điện thoại',
            'email'     => 'Email',
            'status'    => 'Trạng thái',
        ]);

        if ($validator->fails()) {
            CoreHelper::setFlash('danger', $validator->firstError());
            CoreHelper::redirect('/tenants');
        }

        if ($this->tenantModel->findByCccd($_POST['cccd'])) {
            CoreHelper::setFlash('danger', 'Số CCCD/CMND này đã tồn tại trong hệ thống.');
            CoreHelper::redirect('/tenants');
        }

        $userId = null;
        if (!empty($_POST['create_account']) && !empty($_POST['username'])) {
            $username = trim($_POST['username']);
            if ($this->userModel->findByUsername($username)) {
                CoreHelper::setFlash('danger', 'Tên đăng nhập "' . $username . '" đã tồn tại.');
                CoreHelper::redirect('/tenants');
            }
            $userId = $this->userModel->create([
                'username'  => $username,
                'password'  => !empty($_POST['password']) ? $_POST['password'] : '123456',
                'full_name' => $_POST['full_name'],
                'email'     => $_POST['email'] ?? null,
                'phone'     => $_POST['phone'],
                'role'      => 'tenant',
                'status'    => 'active',
            ]);
        }

        $data = $_POST;
        $data['user_id'] = $userId;
        $this->tenantModel->create($data);

        CoreHelper::setFlash('success', 'Thêm hồ sơ khách thuê thành công.');
        CoreHelper::redirect('/tenants');
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0 || !$this->tenantModel->findById($id)) {
            CoreHelper::setFlash('danger', 'Không tìm thấy khách thuê.');
            CoreHelper::redirect('/tenants');
        }

        $validator = Validator::make($_POST, [
            'full_name' => 'required|min:2|max:100',
            'cccd'      => 'required|cccd',
            'phone'     => 'required|phone',
            'email'     => 'email',
            'status'    => 'required|in:staying,moved_out',
        ], [
            'full_name' => 'Họ và tên',
            'cccd'      => 'Số CCCD/CMND',
            'phone'     => 'Số điện thoại',
            'email'     => 'Email',
            'status'    => 'Trạng thái',
        ]);

        if ($validator->fails()) {
            CoreHelper::setFlash('danger', $validator->firstError());
            CoreHelper::redirect('/tenants');
        }

        if ($this->tenantModel->findByCccd($_POST['cccd'], $id)) {
            CoreHelper::setFlash('danger', 'Số CCCD/CMND đã trùng với khách thuê khác.');
            CoreHelper::redirect('/tenants');
        }

        $this->tenantModel->update($id, $_POST);
        CoreHelper::setFlash('success', 'Cập nhật thông tin khách thuê thành công.');
        CoreHelper::redirect('/tenants');
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        try {
            $this->tenantModel->delete($id);
            CoreHelper::setFlash('success', 'Đã xóa khách thuê thành công.');
        } catch (\Throwable $e) {
            CoreHelper::setFlash('danger', 'Không thể xóa khách thuê đang có hợp đồng hoặc hóa đơn liên kết.');
        }
        CoreHelper::redirect('/tenants');
    }
}
