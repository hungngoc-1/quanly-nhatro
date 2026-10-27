<?php

use App\Helpers\CoreHelper;
use App\Models\BookingModel;

$currentUser = CoreHelper::user();
$flashes = CoreHelper::getFlashes();
$currentUri = $_SERVER['REQUEST_URI'] ?? '/';
$isAdminOrStaff = CoreHelper::hasRole(['admin', 'staff']);

$roleLabels = [
    'admin'  => ['label' => 'Chủ trọ / Admin', 'class' => 'badge-role-admin', 'icon' => 'bi-shield-check'],
    'staff'  => ['label' => 'Nhân viên QL', 'class' => 'badge-role-staff', 'icon' => 'bi-person-check'],
    'tenant' => ['label' => 'Khách thuê', 'class' => 'badge-role-tenant', 'icon' => 'bi-house-heart'],
];
$roleBadge = $roleLabels[$currentUser['role'] ?? 'tenant'] ?? $roleLabels['tenant'];
$isDashboard = (str_contains($currentUri, '/dashboard') || rtrim($currentUri, '/') === CoreHelper::basePath());

// Lấy danh sách thông báo yêu cầu giữ phòng từ trang Web 360° (dành cho Chủ trọ & Nhân viên)
$pendingBookingsCount = 0;
$recentBookings = [];
if ($isAdminOrStaff) {
    $bookingModel = new BookingModel();
    $recentBookings = $bookingModel->getPending(8);
    $pendingBookingsCount = count($recentBookings);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= CoreHelper::e($pageTitle ?? 'Hệ thống Quản lý Nhà trọ') ?> - Nhà Trọ Thông Minh</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,500;0,600;0,700;0,800;1,600&family=Quicksand:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= CoreHelper::asset('css/style.css') ?>?v=7.0" rel="stylesheet">
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
    <!-- Thanh Công Cụ Ngang Gọn Gàng, Không Tràn Viền (Contained Floating Horizontal Navbar) -->
    <header class="top-navbar-wrapper sticky-top d-print-none">
        <div class="app-container">
            <nav class="navbar navbar-expand-xl top-glass-navbar">
                <!-- Logo & Thương hiệu gọn -->
                <a href="<?= CoreHelper::url('/dashboard') ?>" class="navbar-brand d-flex align-items-center gap-1.5 me-2 text-decoration-none">
                    <div class="brand-logo-animated">
                        <i class="bi bi-buildings-fill"></i>
                    </div>
                    <div class="brand-text">
                        <div class="brand-title">SMART STAY</div>
                        <div class="brand-subtitle">Quản Lý Nhà Trọ</div>
                    </div>
                </a>

                <!-- Nút mở menu trên Mobile/Tablet -->
                <button class="navbar-toggler border-0 shadow-none nav-mobile-btn" type="button" data-bs-toggle="collapse" data-bs-target="#horizontalMainNavbar" aria-controls="horizontalMainNavbar" aria-expanded="false" aria-label="Mở menu">
                    <i class="bi bi-grid-fill"></i>
                </button>

                <!-- Menu Công Cụ Ngang Chính (Đã tinh gọn chống tràn tuyệt đối) -->
                <div class="collapse navbar-collapse" id="horizontalMainNavbar">
                    <ul class="navbar-nav horizontal-nav-pills mx-auto my-2 my-xl-0">
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
                                <span>Điện Nước</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= CoreHelper::url('/invoices/create') ?>" class="nav-link <?= (str_contains($currentUri, '/invoices') || str_contains($currentUri, '/payments')) ? 'active' : '' ?>">
                                <span class="nav-icon-box"><i class="bi bi-receipt-cutoff"></i></span>
                                <span>Hóa đơn &amp; Thu tiền</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= CoreHelper::url('/reports') ?>" class="nav-link <?= str_contains($currentUri, '/reports') ? 'active' : '' ?>">
                                <span class="nav-icon-box"><i class="bi bi-bar-chart-line-fill"></i></span>
                                <span>Báo cáo</span>
                            </a>
                        </li>
                        <?php else: ?>
                        <li class="nav-item">
                            <a href="<?= CoreHelper::url('/invoices/my-list') ?>" class="nav-link <?= str_contains($currentUri, '/invoices/my-list') ? 'active' : '' ?>">
                                <span class="nav-icon-box"><i class="bi bi-wallet2"></i></span>
                                <span>Hóa đơn của tôi</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <li class="nav-item">
                            <a href="<?= CoreHelper::url('/cho-thue') ?>" class="nav-link <?= str_contains($currentUri, '/cho-thue') ? 'active' : '' ?>" title="Trang công khai cho thuê & xem phòng 360°">
                                <span class="nav-icon-box text-danger"><i class="bi bi-badge-vr-fill"></i></span>
                                <span>Web 360°</span>
                            </a>
                        </li>
                    </ul>

                    <!-- Khu vực Chuông Thông báo Đặt phòng & Tài khoản (Nút Đăng xuất nằm trong menu Tài khoản) -->
                    <?php if ($currentUser): ?>
                    <div class="d-flex align-items-center justify-content-between justify-content-xl-end gap-2 pt-2 pt-xl-0 border-top border-xl-0 ms-xl-2">
                        <?php if ($isAdminOrStaff): ?>
                        <!-- Chuông Thông báo Yêu cầu Giữ phòng từ khách xem Web 360° -->
                        <div class="dropdown">
                            <button type="button" class="notify-bell-btn position-relative" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Thông báo khách yêu cầu giữ phòng">
                                <i class="bi bi-bell-fill"></i>
                                <?php if ($pendingBookingsCount > 0): ?>
                                    <span class="notify-badge-count"><?= $pendingBookingsCount ?></span>
                                <?php endif; ?>
                            </button>

                            <div class="dropdown-menu dropdown-menu-end notify-dropdown-panel p-0 border-0 shadow-lg">
                                <div class="notify-dropdown-header d-flex justify-content-between align-items-center px-3 py-2.5 border-bottom">
                                    <div class="fw-bold text-dark small">
                                        <i class="bi bi-bell-fill text-primary me-1"></i>Yêu cầu Giữ phòng (Web 360°)
                                    </div>
                                    <span class="badge rounded-pill <?= $pendingBookingsCount > 0 ? 'bg-danger' : 'bg-secondary' ?>">
                                        <?= $pendingBookingsCount ?> chờ xử lý
                                    </span>
                                </div>

                                <div class="notify-dropdown-body overflow-auto" style="max-height: 320px;">
                                    <?php if (empty($recentBookings)): ?>
                                        <div class="text-center text-muted py-4 px-3 small">
                                            <i class="bi bi-inbox fs-4 d-block mb-1 opacity-50"></i>
                                            Chưa có yêu cầu đặt/giữ phòng nào từ trang công khai.
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($recentBookings as $bk): ?>
                                            <div class="notify-booking-item px-3 py-2 border-bottom <?= $bk['status'] === 'pending' ? 'bg-primary-subtle bg-opacity-25' : '' ?>">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <div class="d-flex align-items-center gap-1.5">
                                                        <span class="badge bg-dark">Phòng <?= CoreHelper::e($bk['room_number']) ?></span>
                                                        <?php if ($bk['status'] === 'pending'): ?>
                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Mới</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success-subtle text-success">Đã liên hệ</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <small class="text-muted" style="font-size: 0.68rem;"><?= CoreHelper::formatDate($bk['created_at'], 'd/m H:i') ?></small>
                                                </div>
                                                <div class="fw-bold text-dark" style="font-size: 0.8rem;">
                                                    <?= CoreHelper::e($bk['full_name']) ?> &bull; <a href="tel:<?= CoreHelper::e($bk['phone']) ?>" class="text-primary text-decoration-none"><?= CoreHelper::e($bk['phone']) ?></a>
                                                </div>
                                                <?php if (!empty($bk['move_in_date'])): ?>
                                                    <div class="text-muted" style="font-size: 0.72rem;">
                                                        <i class="bi bi-calendar-check me-1"></i>Dự kiến vào ở: <strong><?= CoreHelper::formatDate($bk['move_in_date']) ?></strong>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($bk['notes'])): ?>
                                                    <div class="text-secondary fst-italic text-truncate" style="font-size: 0.72rem;" title="<?= CoreHelper::e($bk['notes']) ?>">
                                                        "<?= CoreHelper::e($bk['notes']) ?>"
                                                    </div>
                                                <?php endif; ?>
                                                <div class="d-flex gap-1.5 mt-1.5">
                                                    <?php if ($bk['status'] === 'pending'): ?>
                                                        <form method="POST" action="<?= CoreHelper::url('/booking/status') ?>" class="m-0">
                                                            <?= CoreHelper::csrfField() ?>
                                                            <input type="hidden" name="id" value="<?= (int) $bk['id'] ?>">
                                                            <input type="hidden" name="status" value="contacted">
                                                            <button type="submit" class="btn btn-sm btn-success py-0 px-2" style="font-size: 0.68rem;">
                                                                <i class="bi bi-check2 me-1"></i>Đã liên hệ
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <a href="<?= CoreHelper::url('/contracts') ?>" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.68rem;">
                                                        Lập HĐ
                                                    </a>
                                                    <form method="POST" action="<?= CoreHelper::url('/booking/delete') ?>" class="m-0 ms-auto" onsubmit="return confirm('Xóa yêu cầu giữ phòng này?');">
                                                        <?= CoreHelper::csrfField() ?>
                                                        <input type="hidden" name="id" value="<?= (int) $bk['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1.5" style="font-size: 0.68rem;" title="Xóa">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="notify-dropdown-footer d-flex justify-content-between align-items-center py-2 px-3 bg-light border-top">
                                    <a href="<?= CoreHelper::url('/rooms') ?>#bookingRequestsPanel" class="small fw-bold text-primary text-decoration-none">
                                        Chờ xử lý <i class="bi bi-arrow-right"></i>
                                    </a>
                                    <a href="<?= CoreHelper::url('/booking/history') ?>" class="small fw-bold text-secondary text-decoration-none">
                                        <i class="bi bi-clock-history me-1"></i>Lịch sử đã liên hệ
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Hộp Tài khoản (Nhấn vào sẽ bung menu chứa nút Đăng xuất) -->
                        <div class="dropdown">
                            <button type="button" class="user-profile-pill d-flex align-items-center gap-1.5 border-0" data-bs-toggle="dropdown" aria-expanded="false" title="Nhấn để mở tùy chọn tài khoản & đăng xuất">
                                <div class="avatar-ring">
                                    <?= CoreHelper::e(mb_strtoupper(mb_substr($currentUser['full_name'] ?? 'U', 0, 1))) ?>
                                </div>
                                <div class="lh-1 text-start user-meta-box">
                                    <div class="user-name-text text-truncate"><?= CoreHelper::e($currentUser['full_name']) ?></div>
                                    <span class="role-pill <?= $roleBadge['class'] ?>"><?= $roleBadge['label'] ?></span>
                                </div>
                                <i class="bi bi-chevron-down text-muted ms-0.5" style="font-size: 0.68rem;"></i>
                            </button>

                            <ul class="dropdown-menu dropdown-menu-end user-dropdown-menu shadow-lg border-0 p-2">
                                <li class="px-2.5 py-2 border-bottom mb-1">
                                    <div class="fw-bold text-dark" style="font-size: 0.82rem;"><?= CoreHelper::e($currentUser['full_name']) ?></div>
                                    <div class="text-muted" style="font-size: 0.72rem;">@<?= CoreHelper::e($currentUser['username'] ?? 'user') ?> &bull; <?= $roleBadge['label'] ?></div>
                                </li>
                                <li>
                                    <a class="dropdown-item rounded-3 py-1.5 px-2.5 small fw-semibold" href="<?= CoreHelper::url('/invoices/my-list') ?>">
                                        <i class="bi bi-wallet2 text-primary me-2"></i>Tra cứu Hóa đơn
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item rounded-3 py-1.5 px-2.5 small fw-semibold" href="<?= CoreHelper::url('/cho-thue') ?>">
                                        <i class="bi bi-badge-vr-fill text-danger me-2"></i>Xem Trang Cho Thuê 360°
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li>
                                    <a class="dropdown-item rounded-3 py-1.5 px-2.5 small fw-bold text-danger bg-danger-subtle bg-opacity-50" href="<?= CoreHelper::url('/logout') ?>">
                                        <i class="bi bi-power me-2"></i>Đăng xuất tài khoản
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <!-- Main Content Area -->
    <div class="main-content flex-grow-1 d-flex flex-column">
        <!-- Page Banner / Tiêu đề trang gọn -->
        <section class="page-hero-section d-print-none">
            <div class="app-container">
                <div class="page-hero-card">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="hero-accent-badge">
                                <i class="bi bi-stars"></i>
                            </div>
                            <div>
                                <div class="hero-breadcrumb">
                                    <span>Hệ thống Quản lý</span>
                                    <i class="bi bi-chevron-right mx-1" style="font-size: 0.6rem;"></i>
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
        <main class="app-container pb-4 flex-grow-1 page-content-animate">
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
