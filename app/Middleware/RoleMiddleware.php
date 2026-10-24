<?php

namespace App\Middleware;

use App\Helpers\CoreHelper;

class RoleMiddleware
{
    public static function handle(array $allowedRoles): void
    {
        AuthMiddleware::handle();

        if (!CoreHelper::hasRole($allowedRoles)) {
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            if (str_contains($uri, '/api/')) {
                CoreHelper::json([
                    'success' => false,
                    'message' => 'Bạn không có quyền truy cập tài nguyên này.',
                ], 403);
            }

            CoreHelper::setFlash('danger', 'Tài khoản của bạn không có quyền truy cập chức năng này.');
            if (CoreHelper::hasRole('tenant')) {
                CoreHelper::redirect('/invoices/my-list');
            }
            CoreHelper::redirect('/dashboard');
        }
    }
}
