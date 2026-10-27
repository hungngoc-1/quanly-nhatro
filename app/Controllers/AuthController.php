<?php

namespace App\Controllers;

use App\Helpers\CoreHelper;
use App\Helpers\Validator;
use App\Models\UserModel;

class AuthController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function loginForm(): void
    {
        if (CoreHelper::isLoggedIn()) {
            CoreHelper::redirect('/dashboard');
        }

        CoreHelper::view('auth/login', [
            'pageTitle' => 'Đăng nhập hệ thống Quản lý Nhà trọ',
        ], false);
    }

    public function login(): void
    {
        $validator = Validator::make($_POST, [
            'username' => 'required',
            'password' => 'required',
        ], [
            'username' => 'Tên đăng nhập',
            'password' => 'Mật khẩu',
        ]);

        if ($validator->fails()) {
            CoreHelper::setFlash('danger', $validator->firstError());
            CoreHelper::redirect('/login');
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = $this->userModel->authenticate($username, $password);
        if (!$user) {
            CoreHelper::setFlash('danger', 'Tên đăng nhập hoặc mật khẩu không chính xác.');
            CoreHelper::redirect('/login');
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);
        $_SESSION['user'] = $user;

        if (($user['role'] ?? '') === 'staff') {
            $_SESSION['selected_building_id'] = !empty($user['building_id']) ? (int) $user['building_id'] : 1;
        } else {
            unset($_SESSION['selected_building_id']);
        }

        CoreHelper::setFlash('success', 'Xin chào, ' . $user['full_name'] . '!');
        if ($user['role'] === 'admin') {
            CoreHelper::redirect('/buildings');
        }

        CoreHelper::redirect('/dashboard');
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['user']);
        session_destroy();

        session_start();
        CoreHelper::setFlash('info', 'Bạn đã đăng xuất khỏi hệ thống.');
        CoreHelper::redirect('/login');
    }
}
