<?php

use App\Helpers\CoreHelper;

$currentUser = CoreHelper::user();
$flashes = CoreHelper::getFlashes();
$currentUri = $_SERVER['REQUEST_URI'] ?? '/';
$isAdminOrStaff = CoreHelper::hasRole(['admin', 'staff']);

$roleLabels = [
    'admin'  => ['label' => 'Chủ trọ / Admin', 'class' => 'badge-role-admin', 'icon' => 'bi-shield-check'],
    'staff'  => ['label' => 'Nhân viên QL', 'class' => 'badge-role-staff', 'icon' => 'bi-person-Check'],
    'tenant' => ['label' => 'Khách thuê', 'class' => 'badge-role-tenant', 'icon' => 'bi-house-heart'],
];
$roleBadge = $roleLabels[$currentUser['role'] ?? 'tenant'] ?? $roleLabels['tenant'];
$isDashboard = (str_contains($currentUri, '/dashboard') || rtrim($currentUri, '/') === CoreHelper::basePath());
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= CoreHelper::e($pageTitle ?? 'Hệ thống Quản lý Nhà trọ') ?> - Nhà Trọ Thông Minh</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= CoreHelper::asset('css/style.css') ?>?v=2.0" rel="stylesheet">
    <script>
        window.APP_BASE_URL = <?= json_encode(CoreHelper::basePath()) ?>;
    </script>
</head>
<body>
<!-- Hiệu ứng nền ánh sáng chuyển động -->
<div class="ambient-bg d-print-none" aria-hidden="true">
    <div class="ambient-orb orb-1"></div>
    <div class="ambient-orb orb-2"></div>
    <div class="ambient-orb orb-3"></div>
</div>

<div class="app-wrapper d-flex flex-column min-vh-100">
    <!-- Thanh Công Cụ Ngang (Horizontal Top Navigation Bar) -->
    <header class="top-navbar-wrapper sticky-top d-print-none">
        <div class="container-xxl px-3 px-lg-4">
            <nav class="navbar navbar-expand-xl top-glass-navbar">
                <!-- Logo & Thương hiệu -->
                <a href="<?= CoreHelper::url('/dashboard') ?>" class="navbar-brand d-flex align-items-center gap-2 me-xl-3 text-decoration-none">
                    <div class="brand-logo-animated">
                        <i class="bi bi-buildings-fill"></i>
                    </div>
                    <div class="brand-text">
                        <div class="brand-title">SMART STAY</div>
                        <div class="brand-subtitle">Quản Lý Nhà Trọ Pro</div>
                    </div>
                </a>

                <!-- Nút mở menu trên Mobile/Tablet -->
                <button class="navbar-toggler border-0 shadow-none nav-mobile-btn" type="button" data-bs-toggle="collapse" data-bs-target="#horizontalMainNavbar" aria-controls="horizontalMainNavbar" aria-expanded="false" aria-label="Mở menu">
                    <i class="bi bi-grid-fill fs-5"></i>
                </button>

                <!-- Menu Công Cụ Ngang Chính -->
                <div class="collapse navbar-collapse" id="horizontalMainNavbar">
                    <ul class="navbar-nav horizontal-nav-pills mx-auto my-3 my-xl-0 gap-1">
                        <li class="nav-item">
                            <a href="<?= CoreHelper::url('/dashboard') ?>" class="nav-link <?= $isDashboard ? 'active' : '' ?>">
                                <span class="nav-icon-box"><i class="bi bi-grid-1x2-fill"></i></span>
                                <span>Tổng quan</span>
                            </a>
                        </li>

                        <?php if ($isAdminOrStaff): ?>
                        <li class="nav-item">
                            <a href="<?= CoreHelper::url('/rooms') ?>" class="nav-link <?= str_contains($currentUri, '/rooms') ? 'active' : '' ?>">
                                <span class="nav-icon-box"><i class="bi bi-door-open-fill"></i></span>
                                <span>Phòng trọ</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= CoreHelper::url('/tenants') ?>" class="nav-link <?= str_contains($currentUri, '/tenants') ? 'active' : '' ?>">
                                <span class="nav-icon-box"><i class="bi bi-people-fill"></i></span>
                                <span>Khách thuê</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= CoreHelper::url('/contracts') ?>" class="nav-link <?= str_contains($currentUri, '/contracts') ? 'active' : '' ?>">
                                <span class="nav-icon-box"><i class="bi bi-file-earmark-medical-fill"></i></span>
                                <span>Hợp đồng</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= CoreHelper::url('/utilities') ?>" class="nav-link <?= str_contains($currentUri, '/utilities') ? 'active' : '' ?>">
                                <span class="nav-icon-box"><i class="bi bi-lightning-charge-fill"></i></span>
                                <span>Điện &amp; Nước</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= CoreHelper::url('/invoices/create') ?>" class="nav-link <?= str_contains($currentUri, '/invoices/create') || str_contains($currentUri, '/invoices/detail') ? 'active' : '' ?>">
                                <span class="nav-icon-box"><i class="bi bi-receipt-cutoff"></i></span>
                                <span>Hóa đơn</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= CoreHelper::url('/payments') ?>" class="nav-link <?= str_contains($currentUri, '/payments') ? 'active' : '' ?>">
                                <span class="nav-icon-box"><i class="bi bi-cash-coin"></i></span>
                                <span>Thu tiền</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= CoreHelper::url('/reports') ?>" class="nav-link <?= str_contains($currentUri, '/reports') ? 'active' : '' ?>">
                                <span class="nav-icon-box"><i class="bi bi-bar-chart-line-fill"></i></span>
                                <span>Báo cáo</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <li class="nav-item">
                            <a href="<?= CoreHelper::url('/invoices/my-list') ?>" class="nav-link <?= str_contains($currentUri, '/invoices/my-list') ? 'active' : '' ?>">
                                <span class="nav-icon-box"><i class="bi bi-wallet2"></i></span>
                                <span><?= $isAdminOrStaff ? 'Tra cứu HĐ' : 'Hóa đơn của tôi' ?></span>
                            </a>
                        </li>
                    </ul>

                    <!-- Khu vực Tài khoản & Đăng xuất -->
                    <?php if ($currentUser): ?>
                    <div class="d-flex align-items-center justify-content-between justify-content-xl-end gap-2 pt-2 pt-xl-0 border-top border-xl-0">
                        <div class="user-profile-pill d-flex align-items-center gap-2">
                            <div class="avatar-ring">
                                <?= CoreHelper::e(mb_strtoupper(mb_substr($currentUser['full_name'] ?? 'U', 0, 1))) ?>
                            </div>
                            <div class="lh-1 pe-1">
                                <div class="fw-bold text-dark" style="font-size: 0.82rem;"><?= CoreHelper::e($currentUser['full_name']) ?></div>
                                <span class="role-pill <?= $roleBadge['class'] ?>"><?= $roleBadge['label'] ?></span>
                            </div>
                        </div>
                        <a href="<?= CoreHelper::url('/logout') ?>" class="btn-logout-pill" title="Đăng xuất khỏi hệ thống">
                            <i class="bi bi-power"></i>
                            <span class="d-xl-none d-xxl-inline ms-1">Thoát</span>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <!-- Main Content Area -->
    <div class="main-content flex-grow-1 d-flex flex-column">
        <!-- Page Banner / Tiêu đề trang -->
        <section class="page-hero-section d-print-none">
            <div class="container-xxl px-3 px-lg-4">
                <div class="page-hero-card">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="hero-accent-badge">
                                <i class="bi bi-stars"></i>
                            </div>
                            <div>
                                <div class="hero-breadcrumb">
                                    <span>Hệ thống Quản lý</span>
                                    <i class="bi bi-chevron-right mx-1" style="font-size: 0.65rem;"></i>
                                    <span class="text-primary fw-semibold"><?= CoreHelper::e($pageTitle ?? 'Bảng điều khiển') ?></span>
                                </div>
                                <h4 class="hero-title mb-0"><?= CoreHelper::e($pageTitle ?? 'Bảng điều khiển') ?></h4>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="live-date-pill">
                                <span class="pulse-dot"></span>
                                <i class="bi bi-calendar3 me-1 text-primary"></i>
                                <span><?= date('d/m/Y') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Page Body -->
        <main class="container-xxl px-3 px-lg-4 pb-5 flex-grow-1 page-content-animate">
            <?php if (!empty($flashes)): ?>
                <div class="d-print-none mb-4">
                    <?php foreach ($flashes as $flash): ?>
                        <div class="alert alert-<?= CoreHelper::e($flash['type']) ?> alert-modern alert-dismissible fade show mb-2" role="alert">
                            <div class="d-flex align-items-center">
                                <div class="alert-icon-wrap me-3">
                                    <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : ($flash['type'] === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill') ?>"></i>
                                </div>
                                <div class="fw-medium"><?= CoreHelper::e($flash['message']) ?></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
