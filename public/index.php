<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Ho_Chi_Minh');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Tự động nạp Composer autoload (nếu có) hoặc PSR-4 Autoloader nội bộ (không bắt buộc cài Composer trên WAMP)
$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
} else {
    spl_autoload_register(function (string $class): void {
        $prefix = 'App\\';
        $baseDir = dirname(__DIR__) . '/app/';
        $len = strlen($prefix);

        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    });
}

use App\Helpers\CoreHelper;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

CoreHelper::loadEnv(dirname(__DIR__));

// 2. Xác định HTTP Method và URI Path trên môi trường WAMP
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if (!empty($_GET['r'])) {
    $routePath = '/' . ltrim((string) $_GET['r'], '/');
} else {
    $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = rtrim(dirname($scriptName), '/');
    $rootDir = preg_replace('#/public$#', '', $scriptDir);

    if ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($requestUri, $scriptDir)) {
        $routePath = substr($requestUri, strlen($scriptDir));
    } elseif ($rootDir !== '' && $rootDir !== '/' && str_starts_with($requestUri, $rootDir)) {
        $routePath = substr($requestUri, strlen($rootDir));
    } else {
        $routePath = $requestUri;
    }

    $routePath = '/' . ltrim((string) $routePath, '/');
    if ($routePath !== '/') {
        $routePath = rtrim($routePath, '/');
    }
}

if ($routePath === '/index.php') {
    $routePath = '/';
}

// 3. Tra cứu Route trong app/Config/routes.php
$routes = require dirname(__DIR__) . '/app/Config/routes.php';
$routeKey = "{$method} {$routePath}";

if (!isset($routes[$routeKey])) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="vi"><head><meta charset="utf-8"><title>404 - Không tìm thấy trang</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head>';
    echo '<body class="bg-light d-flex align-items-center min-vh-100"><div class="container text-center">';
    echo '<h1 class="display-4 fw-bold text-primary">404</h1>';
    echo '<p class="lead text-muted">Đường dẫn <code>' . CoreHelper::e($routePath) . '</code> không tồn tại.</p>';
    echo '<a href="' . CoreHelper::url('/dashboard') . '" class="btn btn-primary">Quay về Trang chủ</a>';
    echo '</div></body></html>';
    exit;
}

[$controllerClass, $action, $middlewareConfig] = $routes[$routeKey];

// 4. Thực thi Middleware phân quyền
if (!empty($middlewareConfig['roles'])) {
    RoleMiddleware::handle($middlewareConfig['roles']);
} elseif (!empty($middlewareConfig['auth'])) {
    AuthMiddleware::handle();
}

// 5. Kiểm tra CSRF cho các request POST
if ($method === 'POST' && !empty($_SESSION['csrf_token'])) {
    if (!CoreHelper::verifyCsrf()) {
        CoreHelper::setFlash('danger', 'Phiên làm việc đã hết hạn (CSRF token không hợp lệ). Vui lòng thử lại.');
        CoreHelper::redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard');
    }
}

// 6. Khởi tạo Controller và gọi Action
$controller = new $controllerClass();
$controller->$action();
