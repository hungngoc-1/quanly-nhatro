<?php

use App\Helpers\CoreHelper;

$flashes = CoreHelper::getFlashes();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - SMART STAY Quản lý Nhà trọ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= CoreHelper::asset('css/style.css') ?>?v=2.0" rel="stylesheet">
</head>
<body class="login-page d-flex align-items-center min-vh-100 position-relative overflow-x-hidden">
<!-- Hiệu ứng nền động (Animated Floating Orbs) -->
<div class="ambient-bg" aria-hidden="true">
    <div class="ambient-orb orb-1"></div>
    <div class="ambient-orb orb-2"></div>
    <div class="ambient-orb orb-3"></div>
</div>

<div class="container py-5 position-relative" style="z-index: 2;">
    <div class="row justify-content-center align-items-center g-4">
        <div class="col-lg-10 col-xl-9">
            <div class="login-showcase-card glow-border-card overflow-hidden">
                <div class="row g-0">
                    <!-- Cột trái: Giới thiệu Hệ thống -->
                    <div class="col-lg-6 login-hero-panel d-flex flex-column justify-content-between p-4 p-md-5 text-white">
                        <div>
                            <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill bg-white bg-opacity-10 backdrop-blur mb-4">
                                <span class="pulse-dot bg-warning"></span>
                                <span class="small fw-semibold">Nền tảng Quản lý Phòng trọ Thế hệ mới</span>
                            </div>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="login-brand-icon">
                                    <i class="bi bi-buildings-fill"></i>
                                </div>
                                <div>
                                    <h3 class="fw-extrabold mb-0 tracking-tight">SMART STAY</h3>
                                    <div class="text-white-50 small">Hệ sinh thái Quản lý Nhà trọ Toàn diện</div>
                                </div>
                            </div>
                            <p class="text-white-75 mb-4" style="line-height: 1.7;">
                                Tự động hóa quy trình quản lý phòng trọ, hợp đồng, chốt chỉ số điện nước, phát hành hóa đơn và theo dõi dòng tiền theo thời gian thực.
                            </p>
                        </div>

                        <div class="login-feature-grid">
                            <div class="login-feature-item">
                                <i class="bi bi-door-open-fill text-warning"></i>
                                <span>Sơ đồ phòng trực quan</span>
                            </div>
                            <div class="login-feature-item">
                                <i class="bi bi-lightning-charge-fill text-info"></i>
                                <span>Tính điện nước tự động</span>
                            </div>
                            <div class="login-feature-item">
                                <i class="bi bi-receipt-cutoff text-success"></i>
                                <span>Phát hành hóa đơn 1 chạm</span>
                            </div>
                            <div class="login-feature-item">
                                <i class="bi bi-pie-chart-fill text-danger-emphasis"></i>
                                <span>Báo cáo doanh thu thông minh</span>
                            </div>
                        </div>
                    </div>

                    <!-- Cột phải: Form Đăng nhập -->
                    <div class="col-lg-6 bg-white p-4 p-md-5 d-flex flex-column justify-content-center">
                        <div class="mb-4">
                            <h4 class="fw-bold text-dark mb-1">Chào mừng trở lại! 👋</h4>
                            <p class="text-muted small mb-0">Vui lòng đăng nhập để truy cập bảng điều khiển của bạn</p>
                        </div>

                        <?php foreach ($flashes as $flash): ?>
                            <div class="alert alert-<?= CoreHelper::e($flash['type']) ?> alert-modern alert-dismissible fade show small mb-3" role="alert">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                <?= CoreHelper::e($flash['message']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endforeach; ?>

                        <form method="POST" action="<?= CoreHelper::url('/login') ?>" id="loginForm">
                            <?= CoreHelper::csrfField() ?>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-secondary">TÊN ĐĂNG NHẬP</label>
                                <div class="input-group input-group-modern">
                                    <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                                    <input type="text" name="username" id="usernameInput" class="form-control" placeholder="Nhập username (vd: admin)" required autofocus>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold small text-secondary">MẬT KHẨU</label>
                                <div class="input-group input-group-modern">
                                    <span class="input-group-text"><i class="bi bi-shield-lock-fill"></i></span>
                                    <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Nhập mật khẩu" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-gradient-primary w-100 py-2-5 fw-bold shadow-sm">
                                <span>Đăng nhập Hệ thống</span>
                                <i class="bi bi-arrow-right-circle-fill ms-2"></i>
                            </button>
                        </form>

                        <div class="demo-accounts-box mt-4 pt-3 border-top">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small fw-bold text-secondary">
                                    <i class="bi bi-stars text-warning me-1"></i>Tài khoản dùng thử nhanh:
                                </span>
                                <span class="badge bg-primary-subtle text-primary rounded-pill" style="font-size: 0.68rem;">1 Click Điền ngay</span>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="button" class="demo-account-btn" onclick="fillLogin('admin', '123456')">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="demo-role-icon bg-primary text-white"><i class="bi bi-shield-lock-fill"></i></span>
                                        <div class="text-start lh-sm">
                                            <div class="fw-bold text-dark small">Chủ trọ (Admin)</div>
                                            <small class="text-muted" style="font-size: 0.72rem;">admin / 123456</small>
                                        </div>
                                    </div>
                                    <span class="badge rounded-pill bg-primary-subtle text-primary">Toàn quyền</span>
                                </button>

                                <button type="button" class="demo-account-btn" onclick="fillLogin('nhanvien', '123456')">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="demo-role-icon bg-info text-white"><i class="bi bi-person-badge-fill"></i></span>
                                        <div class="text-start lh-sm">
                                            <div class="fw-bold text-dark small">Nhân viên Quản lý</div>
                                            <small class="text-muted" style="font-size: 0.72rem;">nhanvien / 123456</small>
                                        </div>
                                    </div>
                                    <span class="badge rounded-pill bg-info-subtle text-info-emphasis">Vận hành</span>
                                </button>

                                <button type="button" class="demo-account-btn" onclick="fillLogin('khachthue1', '123456')">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="demo-role-icon bg-success text-white"><i class="bi bi-house-heart-fill"></i></span>
                                        <div class="text-start lh-sm">
                                            <div class="fw-bold text-dark small">Khách thuê Phòng P101</div>
                                            <small class="text-muted" style="font-size: 0.72rem;">khachthue1 / 123456</small>
                                        </div>
                                    </div>
                                    <span class="badge rounded-pill bg-success-subtle text-success">Tra cứu HĐ</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function fillLogin(user, pass) {
    const u = document.getElementById('usernameInput');
    const p = document.getElementById('passwordInput');
    u.value = user;
    p.value = pass;
    u.classList.add('input-flash');
    p.classList.add('input-flash');
    setTimeout(() => {
        u.classList.remove('input-flash');
        p.classList.remove('input-flash');
    }, 500);
}
</script>
</body>
</html>
