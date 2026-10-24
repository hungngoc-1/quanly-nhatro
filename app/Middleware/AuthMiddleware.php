<?php

namespace App\Middleware;

use App\Helpers\CoreHelper;

class AuthMiddleware
{
    public static function handle(): void
    {
        if (!CoreHelper::isLoggedIn()) {
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            if (str_contains($uri, '/api/')) {
                CoreHelper::json([
                    'success' => false,
                    'message' => 'Bạn cần đăng nhập để thực hiện thao tác này.',
                ], 401);
            }

            CoreHelper::setFlash('warning', 'Vui lòng đăng nhập để tiếp tục.');
            CoreHelper::redirect('/login');
        }
    }
}
