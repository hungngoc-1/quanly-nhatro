<?php

use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Controllers\BuildingController;
use App\Controllers\ContractController;
use App\Controllers\DashboardController;
use App\Controllers\InvoiceController;
use App\Controllers\PaymentController;
use App\Controllers\PublicController;
use App\Controllers\ReportController;
use App\Controllers\RoomController;
use App\Controllers\TenantController;
use App\Controllers\UtilityController;

/**
 * Định nghĩa danh sách Route của hệ thống
 * Cú pháp: 'METHOD /path' => [Controller::class, 'action', ['roles' => [...]]]
 */
return [
    // Trang Công khai Giới thiệu & Cho thuê Phòng Trống + Xem Phòng 360° (Mọi người đều xem được)
    'GET /cho-thue'               => [PublicController::class, 'index', []],
    'GET /trang-chu'              => [PublicController::class, 'index', []],
    'GET /xem-phong'              => [PublicController::class, 'index', []],
    'POST /booking/request'       => [PublicController::class, 'bookRoom', []],
    'GET /booking/history'        => [PublicController::class, 'bookingHistory', ['roles' => ['admin', 'staff']]],
    'POST /booking/status'        => [PublicController::class, 'updateBookingStatus', ['roles' => ['admin', 'staff']]],
    'POST /booking/delete'        => [PublicController::class, 'deleteBooking', ['roles' => ['admin', 'staff']]],

    // Xác thực (Auth)
    'GET /login'                  => [AuthController::class, 'loginForm', []],
    'POST /login'                 => [AuthController::class, 'login', []],
    'GET /logout'                 => [AuthController::class, 'logout', []],
    'POST /logout'                => [AuthController::class, 'logout', []],

    // Quản trị viên: Trang điều hướng & quản lý Tòa nhà (Buildings)
    'GET /buildings'              => [BuildingController::class, 'index', ['roles' => ['admin']]],
    'GET /buildings/select'       => [BuildingController::class, 'select', ['roles' => ['admin']]],
    'POST /buildings/select'      => [BuildingController::class, 'select', ['roles' => ['admin']]],
    'POST /buildings/store'       => [BuildingController::class, 'store', ['roles' => ['admin']]],
    'POST /buildings/update'      => [BuildingController::class, 'update', ['roles' => ['admin']]],
    'POST /buildings/delete'      => [BuildingController::class, 'delete', ['roles' => ['admin']]],

    // Trang chủ & Bảng điều khiển (Dashboard)
    'GET /'                       => [DashboardController::class, 'index', ['auth' => true]],
    'GET /dashboard'              => [DashboardController::class, 'index', ['auth' => true]],

    // Quản lý Phòng trọ & Loại phòng (Rooms & Room Types)
    'GET /rooms'                  => [RoomController::class, 'index', ['roles' => ['admin', 'staff']]],
    'POST /rooms/store'           => [RoomController::class, 'store', ['roles' => ['admin', 'staff']]],
    'POST /rooms/update'          => [RoomController::class, 'update', ['roles' => ['admin', 'staff']]],
    'POST /rooms/delete'          => [RoomController::class, 'delete', ['roles' => ['admin']]],
    'POST /room-types/store'      => [RoomController::class, 'storeRoomType', ['roles' => ['admin', 'staff']]],
    'POST /room-types/update'     => [RoomController::class, 'updateRoomType', ['roles' => ['admin', 'staff']]],
    'POST /room-types/delete'     => [RoomController::class, 'deleteRoomType', ['roles' => ['admin']]],

    // Quản lý Khách thuê (Tenants)
    'GET /tenants'                => [TenantController::class, 'index', ['roles' => ['admin', 'staff']]],
    'POST /tenants/store'         => [TenantController::class, 'store', ['roles' => ['admin', 'staff']]],
    'POST /tenants/update'        => [TenantController::class, 'update', ['roles' => ['admin', 'staff']]],
    'POST /tenants/delete'        => [TenantController::class, 'delete', ['roles' => ['admin']]],

    // Quản lý Hợp đồng (Contracts)
    'GET /contracts'              => [ContractController::class, 'index', ['roles' => ['admin', 'staff']]],
    'GET /contracts/detail'       => [ContractController::class, 'detail', ['auth' => true]],
    'POST /contracts/store'       => [ContractController::class, 'store', ['roles' => ['admin', 'staff']]],
    'POST /contracts/update'      => [ContractController::class, 'update', ['roles' => ['admin', 'staff']]],
    'POST /contracts/terminate'   => [ContractController::class, 'terminate', ['roles' => ['admin', 'staff']]],
    'POST /contracts/delete'      => [ContractController::class, 'delete', ['roles' => ['admin']]],
    'POST /contracts/renew'       => [ContractController::class, 'tenantRenew', ['auth' => true]],
    'POST /contracts/sign-online' => [ContractController::class, 'signOnline', ['auth' => true]],
    'POST /contracts/upload-scan' => [ContractController::class, 'uploadScan', ['roles' => ['admin', 'staff']]],

    // Quản lý Điện & Nước (Utilities)
    'GET /utilities'              => [UtilityController::class, 'index', ['roles' => ['admin', 'staff']]],
    'POST /utilities/store'       => [UtilityController::class, 'store', ['roles' => ['admin', 'staff']]],
    'POST /utilities/update'      => [UtilityController::class, 'update', ['roles' => ['admin', 'staff']]],
    'POST /utilities/delete'      => [UtilityController::class, 'delete', ['roles' => ['admin']]],

    // Quản lý Hóa đơn (Invoices)
    'GET /invoices'               => [InvoiceController::class, 'create', ['roles' => ['admin', 'staff']]],
    'GET /invoices/create'        => [InvoiceController::class, 'create', ['roles' => ['admin', 'staff']]],
    'POST /invoices/store'        => [InvoiceController::class, 'store', ['roles' => ['admin', 'staff']]],
    'POST /invoices/generate-batch' => [InvoiceController::class, 'generateBatch', ['roles' => ['admin', 'staff']]],
    'GET /invoices/my-list'       => [InvoiceController::class, 'myList', ['auth' => true]],
    'GET /invoices/detail'        => [InvoiceController::class, 'detail', ['auth' => true]],
    'POST /invoices/delete'       => [InvoiceController::class, 'delete', ['roles' => ['admin']]],

    // Quản lý Thanh toán (Payments)
    'GET /payments'               => [PaymentController::class, 'index', ['roles' => ['admin', 'staff']]],
    'POST /payments/store'        => [PaymentController::class, 'store', ['roles' => ['admin', 'staff']]],
    'POST /payments/delete'       => [PaymentController::class, 'delete', ['roles' => ['admin']]],

    // Báo cáo & Thống kê (Reports)
    'GET /reports'                => [ReportController::class, 'index', ['roles' => ['admin', 'staff']]],
    'GET /reports/export'         => [ReportController::class, 'exportCsv', ['roles' => ['admin', 'staff']]],

    // API nội bộ phục vụ AJAX
    'GET /api/room-info'          => [ApiController::class, 'roomInfo', ['auth' => true]],
    'GET /api/invoice-info'       => [ApiController::class, 'invoiceInfo', ['auth' => true]],
    'GET /api/stats'              => [ApiController::class, 'stats', ['auth' => true]],
];
