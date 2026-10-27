<?php

use App\Helpers\CoreHelper;

$currentUser = CoreHelper::user();
$flashes = CoreHelper::getFlashes();
$default360Room = !empty($availableRooms) ? $availableRooms[0] : (!empty($allRooms) ? $allRooms[0] : [
    'id' => 1,
    'room_number' => 'P103',
    'floor' => 1,
    'area' => 22,
    'price' => 2500000,
    'max_occupants' => 2,
    'status' => 'available',
    'amenities' => 'Máy lạnh, Nước nóng, Wifi, Kệ bếp, WC riêng',
    'description' => 'Phòng tiêu chuẩn thoáng mát, đầy đủ tiện nghi',
]);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= CoreHelper::e($pageTitle ?? 'Khám phá & Thuê Phòng Trọ 360° - SMART STAY') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,500;0,600;0,700;0,800;1,600&family=Quicksand:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Thư viện WebGL Pannellum 360° Panorama Viewer -->
    <link href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css" rel="stylesheet">
    <link href="<?= CoreHelper::asset('css/style.css') ?>?v=6.0" rel="stylesheet">
    <style>
        /* CSS chuyên biệt cho Trình Xem Phòng 360° & Trang Cổng Thông Tin Công Khai */
        .viewer-360-wrapper {
            position: relative;
            width: 100%;
            height: 430px;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 20px 45px -12px rgba(15, 23, 42, 0.25);
            border: 2px solid rgba(255, 255, 255, 0.95);
            background: #0f172a;
        }

        #panorama360 {
            width: 100%;
            height: 100%;
        }

        .viewer-360-overlay-top {
            position: absolute;
            top: 12px;
            left: 12px;
            right: 54px;
            z-index: 20;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            pointer-events: none;
        }

        .viewer-360-overlay-top > * {
            pointer-events: auto;
        }

        .viewer-360-badge {
            background: rgba(15, 23, 42, 0.82);
            backdrop-filter: blur(10px);
            color: #ffffff;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 700;
            border: 1px solid rgba(255, 255, 255, 0.18);
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }

        .viewer-360-overlay-bottom {
            position: absolute;
            bottom: 12px;
            left: 12px;
            right: 12px;
            z-index: 20;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            pointer-events: none;
        }

        .viewer-360-overlay-bottom > * {
            pointer-events: auto;
        }

        .scene-switch-group {
            background: rgba(15, 23, 42, 0.82);
            backdrop-filter: blur(10px);
            padding: 0.25rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.16);
            display: inline-flex;
            gap: 0.25rem;
        }

        .scene-btn {
            border: none;
            background: transparent;
            color: #cbd5e1;
            font-size: 0.73rem;
            font-weight: 600;
            padding: 0.3rem 0.65rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .scene-btn:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.1);
        }

        .scene-btn.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.4);
        }

        .room-360-selector-item {
            cursor: pointer;
            padding: 0.55rem 0.75rem;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            background: #ffffff;
            transition: all 0.22s ease;
        }

        .room-360-selector-item:hover {
            border-color: var(--primary-light);
            transform: translateX(3px);
        }

        .room-360-selector-item.active {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.08), rgba(124, 58, 237, 0.08));
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.12);
        }

        .vr-pulse-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #ef4444;
            color: #fff;
            font-size: 0.68rem;
            animation: vrPulse 1.8s infinite;
        }

        @keyframes vrPulse {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.65); }
            70% { box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }

        .amenity-feature-box {
            height: 100%;
            padding: 0.9rem 1rem;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: var(--transition-spring);
        }

        .amenity-feature-box:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
            border-color: var(--primary-light);
        }

        /* ==========================================================================
           MÀN HÌNH LỜI CHÀO & ANIMATION MỞ ĐẦU (WELCOME INTRO SPLASH SCREEN)
           ========================================================================== */
        .welcome-intro-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: radial-gradient(circle at 20% 20%, rgba(99, 102, 241, 0.32), transparent 45%),
                        radial-gradient(circle at 80% 75%, rgba(6, 182, 212, 0.28), transparent 45%),
                        linear-gradient(135deg, #090d16 0%, #1e1b4b 52%, #0f172a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: #ffffff;
            overflow: hidden;
            transition: opacity 0.75s cubic-bezier(0.22, 1, 0.36, 1),
                        transform 0.85s cubic-bezier(0.22, 1, 0.36, 1),
                        visibility 0.85s;
        }

        .welcome-intro-overlay.intro-hidden {
            opacity: 0;
            visibility: hidden;
            transform: scale(1.12) translateY(-30px);
            pointer-events: none;
        }

        /* Vòng quỹ đạo 360° xoay quanh Logo */
        .intro-orbital-wrap {
            position: relative;
            width: 118px;
            height: 118px;
            margin: 0 auto 1.4rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .intro-orbit-ring {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 2px dashed rgba(129, 140, 248, 0.55);
            animation: spinOrbit 8s linear infinite;
        }

        .intro-orbit-ring-2 {
            position: absolute;
            inset: -12px;
            border-radius: 50%;
            border: 1.5px solid rgba(56, 189, 248, 0.35);
            border-top-color: #38bdf8;
            border-bottom-color: #a855f7;
            animation: spinOrbitReverse 5s linear infinite;
        }

        .intro-center-logo {
            width: 78px;
            height: 78px;
            border-radius: 24px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 55%, #06b6d4 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.3rem;
            color: #ffffff;
            box-shadow: 0 15px 35px rgba(79, 70, 229, 0.65),
                        inset 0 0 15px rgba(255, 255, 255, 0.35);
            animation: logoFloatPulse 2.2s ease-in-out infinite alternate;
            position: relative;
            z-index: 2;
        }

        @keyframes spinOrbit {
            0% { transform: rotate(0deg) scale(1); }
            50% { transform: rotate(180deg) scale(1.06); }
            100% { transform: rotate(360deg) scale(1); }
        }

        @keyframes spinOrbitReverse {
            0% { transform: rotate(360deg); }
            100% { transform: rotate(0deg); }
        }

        @keyframes logoFloatPulse {
            0% { transform: translateY(0) scale(1); }
            100% { transform: translateY(-6px) scale(1.06); }
        }

        .intro-card-glass {
            max-width: 620px;
            width: 100%;
            text-align: center;
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 28px;
            padding: 2.4rem 2rem;
            box-shadow: 0 30px 70px -15px rgba(0, 0, 0, 0.55);
            animation: introCardZoomIn 0.7s cubic-bezier(0.22, 1, 0.36, 1);
        }

        @keyframes introCardZoomIn {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.92);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .intro-greeting-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.38rem 1rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.22);
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: #38bdf8;
            margin-bottom: 1rem;
            animation: fadeSlideUp 0.6s ease 0.15s both;
        }

        .intro-main-title {
            font-family: 'Quicksand', 'Plus Jakarta Sans', sans-serif;
            font-size: clamp(1.55rem, 3.5vw, 2.2rem);
            font-weight: 800;
            line-height: 1.25;
            margin-bottom: 0.75rem;
            background: linear-gradient(135deg, #ffffff 0%, #c7d2fe 50%, #38bdf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: fadeSlideUp 0.65s ease 0.3s both;
        }

        .intro-subtitle {
            color: #cbd5e1;
            font-size: 0.92rem;
            max-width: 480px;
            margin: 0 auto 1.4rem;
            line-height: 1.55;
            animation: fadeSlideUp 0.65s ease 0.45s both;
        }

        .intro-feature-badges {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            animation: fadeSlideUp 0.65s ease 0.6s both;
        }

        .intro-feature-tag {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.14);
            padding: 0.32rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            color: #e2e8f0;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .intro-progress-wrap {
            max-width: 360px;
            margin: 0 auto 1.15rem;
            animation: fadeSlideUp 0.65s ease 0.7s both;
        }

        .intro-progress-bar-bg {
            height: 7px;
            width: 100%;
            background: rgba(255, 255, 255, 0.14);
            border-radius: 999px;
            overflow: hidden;
            margin-bottom: 0.45rem;
        }

        .intro-progress-bar-fill {
            height: 100%;
            width: 0%;
            border-radius: 999px;
            background: linear-gradient(90deg, #4f46e5, #06b6d4, #10b981);
            box-shadow: 0 0 12px rgba(6, 182, 212, 0.8);
            transition: width 0.12s linear;
        }

        @keyframes fadeSlideUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Hiệu ứng xuất hiện mượt mà của trang chính sau khi màn chào kết thúc */
        .showcase-main-wrapper {
            opacity: 0;
            transform: translateY(18px) scale(0.985);
            transition: opacity 0.7s cubic-bezier(0.22, 1, 0.36, 1),
                        transform 0.7s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .showcase-main-wrapper.showcase-ready {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    </style>
</head>
<body>
<!-- MÀN HÌNH LỜI CHÀO & ANIMATION MỞ ĐẦU KHI VÀO TRANG -->
<div class="welcome-intro-overlay d-print-none" id="welcomeIntroOverlay">
    <div class="intro-card-glass">
        <div class="intro-orbital-wrap">
            <div class="intro-orbit-ring"></div>
            <div class="intro-orbit-ring-2"></div>
            <div class="intro-center-logo">
                <i class="bi bi-buildings-fill"></i>
            </div>
        </div>

        <div class="intro-greeting-pill">
            <i class="bi bi-stars text-warning"></i>
            <span>XIN CHÀO QUÝ KHÁCH ĐẾN VỚI SMART STAY</span>
            <i class="bi bi-stars text-warning"></i>
        </div>

        <h1 class="intro-main-title">
            Khám Phá Không Gian Phòng Trọ 360° Thực Tế Ảo
        </h1>

        <p class="intro-subtitle">
            Chào mừng bạn đến với hệ thống phòng trọ &amp; căn hộ mini thông minh.
            Hiện đang có <strong class="text-warning"><?= (int) $counts['available'] ?> phòng trống</strong> sẵn sàng đón bạn dọn vào ở ngay!
        </p>

        <div class="intro-feature-badges">
            <span class="intro-feature-tag"><i class="bi bi-badge-vr-fill text-info"></i> Xoay 360° Toàn Cảnh</span>
            <span class="intro-feature-tag"><i class="bi bi-fingerprint text-success"></i> An Ninh Vân Tay 24/7</span>
            <span class="intro-feature-tag"><i class="bi bi-snow2 text-primary"></i> Full Nội Thất Tiện Nghi</span>
        </div>

        <div class="intro-progress-wrap">
            <div class="intro-progress-bar-bg">
                <div class="intro-progress-bar-fill" id="introProgressFill"></div>
            </div>
            <div class="d-flex justify-content-between align-items-center text-white-50" style="font-size: 0.74rem;">
                <span id="introStatusText">Đang khởi tạo không gian 360°...</span>
                <span class="fw-bold text-info" id="introPercentText">0%</span>
            </div>
        </div>

        <button type="button" class="btn btn-sm btn-light rounded-pill px-4 py-1.5 fw-bold text-primary shadow-sm mt-1" onclick="finishWelcomeIntro()">
            <span>Vào Xem Phòng Ngay</span>
            <i class="bi bi-arrow-right-circle-fill ms-1"></i>
        </button>
    </div>
</div>

<!-- Hiệu ứng nền ánh sáng -->
<div class="ambient-bg d-print-none" aria-hidden="true">
    <div class="ambient-orb orb-1"></div>
    <div class="ambient-orb orb-2"></div>
    <div class="ambient-orb orb-3"></div>
</div>

<div class="app-wrapper showcase-main-wrapper d-flex flex-column min-vh-100" id="showcaseMainWrapper">
    <!-- Thanh Menu Ngang Công Khai (Không tràn viền, gọn gàng) -->
    <header class="top-navbar-wrapper sticky-top">
        <div class="app-container">
            <nav class="navbar navbar-expand-lg top-glass-navbar">
                <a href="<?= CoreHelper::url('/cho-thue') ?>" class="navbar-brand d-flex align-items-center gap-2 me-3 text-decoration-none">
                    <div class="brand-logo-animated">
                        <i class="bi bi-buildings-fill"></i>
                    </div>
                    <div class="brand-text">
                        <div class="brand-title">SMART STAY</div>
                        <div class="brand-subtitle">Hệ Thống Phòng Trọ 360°</div>
                    </div>
                </a>

                <button class="navbar-toggler border-0 shadow-none nav-mobile-btn" type="button" data-bs-toggle="collapse" data-bs-target="#publicNavbar">
                    <i class="bi bi-grid-fill"></i>
                </button>

                <div class="collapse navbar-collapse" id="publicNavbar">
                    <ul class="navbar-nav horizontal-nav-pills mx-auto my-2 my-lg-0">
                        <li class="nav-item">
                            <a href="#tour360Section" class="nav-link active">
                                <span class="nav-icon-box"><i class="bi bi-badge-vr-fill"></i></span>
                                <span>Trải nghiệm Phòng 360°</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#availableRoomsSection" class="nav-link">
                                <span class="nav-icon-box"><i class="bi bi-door-open-fill"></i></span>
                                <span>Phòng đang trống (<?= (int) $counts['available'] ?>)</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#amenitiesSection" class="nav-link">
                                <span class="nav-icon-box"><i class="bi bi-stars"></i></span>
                                <span>Tiện ích &amp; Bảng giá</span>
                            </a>
                        </li>
                    </ul>

                    <div class="d-flex align-items-center gap-2 pt-2 pt-lg-0">
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2.5" onclick="replayWelcomeIntro()" title="Xem lại hiệu ứng lời chào mở đầu">
                            <i class="bi bi-play-circle-fill me-1"></i>Lời chào
                        </button>
                        <a href="tel:0901234567" class="btn btn-sm btn-outline-success rounded-pill px-2.5">
                            <i class="bi bi-telephone-fill me-1"></i>Hotline: 0901.234.567
                        </a>
                        <?php if ($currentUser): ?>
                            <a href="<?= CoreHelper::url('/dashboard') ?>" class="btn btn-sm btn-gradient-primary rounded-pill px-3">
                                <i class="bi bi-speedometer2 me-1"></i>Về Bảng Quản Lý
                            </a>
                        <?php else: ?>
                            <a href="<?= CoreHelper::url('/login') ?>" class="btn btn-sm btn-gradient-primary rounded-pill px-3">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Đăng nhập
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <main class="app-container pb-4 flex-grow-1 page-content-animate mt-2">
        <?php if (!empty($flashes)): ?>
            <div class="mb-3">
                <?php foreach ($flashes as $flash): ?>
                    <div class="alert alert-<?= CoreHelper::e($flash['type']) ?> alert-modern alert-dismissible fade show mb-2" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                            <div class="fw-semibold"><?= CoreHelper::e($flash['message']) ?></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- PHẦN 1: TRẢI NGHIỆM XEM PHÒNG 360 ĐỘ TƯƠNG TÁC TRỰC TIẾP -->
        <section id="tour360Section" class="mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="vr-pulse-icon"><i class="bi bi-badge-vr-fill"></i></span>
                        <div>
                            <h5 class="mb-0 fw-bold text-dark">Khám Phá Không Gian Phòng Trọ 360° Thực Tế Ảo</h5>
                            <div class="text-muted" style="font-size: 0.74rem;">
                                Giữ chuột và kéo để xoay 360° quanh phòng &bull; Lăn chuột để phóng to/thu nhỏ &bull; Nhấn vào các điểm tròn trong phòng để xem chi tiết
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <!-- Cho phép tải thử ảnh 360 thực tế từ máy nếu chủ trọ có ảnh chụp camera 360 -->
                        <label class="btn btn-sm btn-outline-primary rounded-pill mb-0" title="Tải ảnh Panorama 360° thực tế từ máy tính của bạn">
                            <i class="bi bi-camera-fill me-1"></i>Thử ảnh 360° từ máy
                            <input type="file" id="custom360Upload" accept="image/*" class="d-none" onchange="loadCustom360File(this)">
                        </label>
                        <button type="button" class="btn btn-sm btn-gradient-primary rounded-pill px-3" id="tourBookNowBtn" onclick="openBookingForCurrent360Room()">
                            <i class="bi bi-calendar2-check-fill me-1"></i>Giữ phòng đang xem
                        </button>
                    </div>
                </div>

                <div class="card-body p-3">
                    <div class="row g-3">
                        <!-- Khung Xem 360° WebGL -->
                        <div class="col-lg-8">
                            <div class="viewer-360-wrapper">
                                <!-- Overlay Thông tin Phòng đang xem 360 -->
                                <div class="viewer-360-overlay-top">
                                    <div class="viewer-360-badge">
                                        <span class="status-dot bg-success"></span>
                                        <span id="vrRoomTitle">Phòng <?= CoreHelper::e($default360Room['room_number']) ?> (Tầng <?= (int) $default360Room['floor'] ?> - <?= (float) $default360Room['area'] ?>m²)</span>
                                        <span class="text-warning fw-bold ms-1" id="vrRoomPrice"><?= CoreHelper::formatCurrency($default360Room['price']) ?>/tháng</span>
                                    </div>
                                </div>

                                <!-- Khung Pannellum 360 Canvas -->
                                <div id="panorama360"></div>

                                <!-- Overlay Đổi Góc Nhìn 360° -->
                                <div class="viewer-360-overlay-bottom">
                                    <div class="scene-switch-group">
                                        <button type="button" class="scene-btn active" id="sceneBtn_main" onclick="switch360Scene('main')">
                                            <i class="bi bi-house-door-fill me-1"></i>Góc Phòng Chính &amp; Giường
                                        </button>
                                        <button type="button" class="scene-btn" id="sceneBtn_kitchen" onclick="switch360Scene('kitchen')">
                                            <i class="bi bi-cup-hot-fill me-1"></i>Góc Kệ Bếp &amp; WC Riêng
                                        </button>
                                        <button type="button" class="scene-btn" id="sceneBtn_balcony" onclick="switch360Scene('balcony')">
                                            <i class="bi bi-brightness-alt-high-fill me-1"></i>Góc Ban Công &amp; Cửa Sổ
                                        </button>
                                    </div>

                                    <button type="button" class="viewer-360-badge border-0" onclick="toggle360AutoRotate()" id="autoRotateBtn" style="cursor: pointer;">
                                        <i class="bi bi-arrow-repeat"></i>
                                        <span>Tự xoay 360°</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Cột Chọn Phòng Xem 360° Nhanh -->
                        <div class="col-lg-4 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold text-dark small"><i class="bi bi-collection-play-fill text-primary me-1"></i>Chọn phòng để xem 360°:</span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><?= count($availableRooms) ?> phòng trống</span>
                                </div>

                                <div class="d-flex flex-column gap-2 overflow-auto pe-1" style="max-height: 295px;" id="room360SelectorList">
                                    <?php foreach ($allRooms as $idx => $r): ?>
                                        <?php
                                        $isAvail = $r['status'] === 'available';
                                        $rJson = htmlspecialchars(json_encode($r, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                        $isDefault = ((int) $r['id'] === (int) $default360Room['id']);
                                        ?>
                                        <div class="room-360-selector-item <?= $isDefault ? 'active' : '' ?>"
                                             id="selector360_<?= (int) $r['id'] ?>"
                                             onclick="loadRoomInto360Viewer(<?= $rJson ?>, this)">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-dark">Phòng <?= CoreHelper::e($r['room_number']) ?></span>
                                                    <span class="text-muted" style="font-size: 0.72rem;">Tầng <?= (int) $r['floor'] ?> &bull; <?= (float) $r['area'] ?>m²</span>
                                                </div>
                                                <?php if ($isAvail): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Còn trống</span>
                                                <?php elseif ($r['status'] === 'occupied'): ?>
                                                    <span class="badge bg-secondary-subtle text-secondary">Đã thuê</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-subtle text-warning-emphasis">Bảo trì</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-bold text-danger" style="font-size: 0.84rem;"><?= CoreHelper::formatCurrency($r['price']) ?>/tháng</span>
                                                <span class="text-primary fw-semibold" style="font-size: 0.72rem;">
                                                    <i class="bi bi-badge-vr me-1"></i>Xem 360°
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Hộp tóm tắt tiện nghi của phòng đang xem 360 -->
                            <div class="bg-light rounded-3 p-2.5 border mt-2">
                                <div class="text-muted fw-bold mb-1" style="font-size: 0.7rem; text-transform: uppercase;">
                                    <i class="bi bi-stars text-warning me-1"></i>Tiện nghi phòng đang xem:
                                </div>
                                <div class="text-dark small fw-semibold text-truncate" id="vrRoomAmenities">
                                    <?= CoreHelper::e($default360Room['amenities'] ?: 'Máy lạnh, Nước nóng, Wifi, Kệ bếp, WC riêng') ?>
                                </div>
                                <div class="text-muted mt-0.5 text-truncate" style="font-size: 0.73rem;" id="vrRoomDesc">
                                    <?= CoreHelper::e($default360Room['description'] ?: 'Phòng sạch đẹp, thoáng mát, an ninh 24/7') ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- PHẦN 2: TIỆN ÍCH CHUNG & BẢNG GIÁ DỊCH VỤ MINH BẠCH -->
        <section id="amenitiesSection" class="mb-4">
            <div class="row g-2.5">
                <div class="col-6 col-md-3">
                    <div class="amenity-feature-box">
                        <div class="stat-icon-modern icon-indigo"><i class="bi bi-fingerprint"></i></div>
                        <div>
                            <div class="fw-bold text-dark">Khóa Vân Tay 24/7</div>
                            <div class="text-muted" style="font-size: 0.74rem;">Giờ giấc tự do, camera an ninh</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="amenity-feature-box">
                        <div class="stat-icon-modern icon-emerald"><i class="bi bi-lightning-charge-fill"></i></div>
                        <div>
                            <div class="fw-bold text-dark">Điện Nước Minh Bạch</div>
                            <div class="text-muted" style="font-size: 0.74rem;">Điện 3.500đ/kWh &bull; Nước 15.000đ/m³</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="amenity-feature-box">
                        <div class="stat-icon-modern icon-cyan"><i class="bi bi-wifi"></i></div>
                        <div>
                            <div class="fw-bold text-dark">Full Nội Thất &amp; Wifi</div>
                            <div class="text-muted" style="font-size: 0.74rem;">Máy lạnh, nước nóng, kệ bếp, WC</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="amenity-feature-box">
                        <div class="stat-icon-modern icon-rose"><i class="bi bi-receipt-cutoff"></i></div>
                        <div>
                            <div class="fw-bold text-dark">Tra Cứu Hóa Đơn Online</div>
                            <div class="text-muted" style="font-size: 0.74rem;">Cấp tài khoản riêng cho mỗi phòng</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- PHẦN 3: DANH SÁCH PHÒNG CHO THUÊ (CÁC Ô BẰNG NHAU TUYỆT ĐỐI 100%) -->
        <section id="availableRoomsSection">
            <!-- Thanh tiêu đề & Bộ lọc 1 hàng ngang -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body py-2-5 px-3">
                    <form method="GET" action="<?= CoreHelper::url('/cho-thue') ?>#availableRoomsSection" class="row g-2 align-items-center">
                        <div class="col-lg-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="header-icon-pill bg-primary-subtle text-primary"><i class="bi bi-buildings-fill"></i></span>
                                <div>
                                    <h6 class="mb-0 fw-bold">Danh Sách Phòng Cho Thuê</h6>
                                    <div class="text-muted" style="font-size: 0.7rem;">Đang trống <?= (int) $counts['available'] ?> / <?= (int) $counts['total'] ?> phòng</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3 col-lg-2">
                            <select name="status" class="form-select form-select-sm">
                                <option value="available" <?= ($filters['status'] ?? 'available') === 'available' ? 'selected' : '' ?>>Chỉ phòng còn trống (<?= (int) $counts['available'] ?>)</option>
                                <option value="all" <?= ($filters['status'] ?? '') === 'all' ? 'selected' : '' ?>>Tất cả các phòng (<?= (int) $counts['total'] ?>)</option>
                            </select>
                        </div>
                        <div class="col-6 col-sm-3 col-md-2 col-lg-2">
                            <select name="floor" class="form-select form-select-sm">
                                <option value="">-- Mọi tầng --</option>
                                <?php for ($f = 1; $f <= 5; $f++): ?>
                                    <option value="<?= $f ?>" <?= ((string) ($filters['floor'] ?? '') === (string) $f) ? 'selected' : '' ?>>Tầng <?= $f ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-6 col-sm-3 col-md-3 col-lg-2">
                            <select name="price_range" class="form-select form-select-sm">
                                <option value="">-- Mọi mức giá --</option>
                                <option value="under_3m" <?= ($filters['price_range'] ?? '') === 'under_3m' ? 'selected' : '' ?>>Dưới 3.000.000 đ</option>
                                <option value="3m_to_35m" <?= ($filters['price_range'] ?? '') === '3m_to_35m' ? 'selected' : '' ?>>3.000.000 - 3.500.000 đ</option>
                                <option value="over_35m" <?= ($filters['price_range'] ?? '') === 'over_35m' ? 'selected' : '' ?>>Trên 3.500.000 đ</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4 col-lg-3 d-flex gap-1.5">
                            <input type="text" name="keyword" value="<?= CoreHelper::e($filters['keyword'] ?? '') ?>" class="form-control form-control-sm" placeholder="Tìm tiện nghi, mã phòng...">
                            <button type="submit" class="btn btn-sm btn-primary px-3"><i class="bi bi-funnel me-1"></i>Lọc</button>
                            <a href="<?= CoreHelper::url('/cho-thue') ?>" class="btn btn-sm btn-outline-secondary" title="Làm mới"><i class="bi bi-arrow-counterclockwise"></i></a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lưới Thẻ Phòng Rộng Rãi & Bằng Nhau 100% -->
            <div class="row g-3 room-equal-grid">
                <?php if (empty($rooms)): ?>
                    <div class="col-12">
                        <div class="card border-0 shadow-sm p-4 text-center text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-1 opacity-50"></i>
                            Không tìm thấy phòng phù hợp với tiêu chí lọc. <a href="<?= CoreHelper::url('/cho-thue', ['status' => 'all']) ?>" class="fw-bold">Xem tất cả phòng</a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($rooms as $room): ?>
                        <?php
                        $isAvailable = $room['status'] === 'available';
                        $statusBadge = match ($room['status']) {
                            'occupied'    => ['text' => 'Đã có khách', 'class' => 'bg-secondary-subtle text-secondary border border-secondary-subtle', 'dot' => 'bg-secondary', 'accent' => 'room-accent-occupied'],
                            'maintenance' => ['text' => 'Đang bảo trì', 'class' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle', 'dot' => 'bg-warning', 'accent' => 'room-accent-maintenance'],
                            default       => ['text' => 'Còn trống', 'class' => 'bg-success-subtle text-success border border-success-subtle', 'dot' => 'bg-success', 'accent' => 'room-accent-available'],
                        };
                        $roomJson = htmlspecialchars(json_encode($room, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                        ?>
                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3 d-flex align-items-stretch">
                            <div class="card border-0 shadow-sm room-luxury-card <?= $statusBadge['accent'] ?> w-100">
                                <div class="card-body d-flex flex-column p-3">
                                    <!-- 1. Đầu thẻ cố định chiều cao 40px -->
                                    <div class="room-card-header-row mb-2.5">
                                        <div class="d-flex align-items-center gap-2 min-w-0">
                                            <div class="room-number-badge flex-shrink-0">
                                                <?= CoreHelper::e($room['room_number']) ?>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="fw-bold text-dark text-truncate lh-sm">Phòng <?= CoreHelper::e($room['room_number']) ?></div>
                                                <div class="text-muted text-nowrap" style="font-size: 0.72rem;">
                                                    Tầng <?= (int) $room['floor'] ?> &bull; <?= (float) $room['area'] ?>m&sup2; &bull; <i class="bi bi-people"></i> Tối đa <?= (int) $room['max_occupants'] ?>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="badge rounded-pill <?= $statusBadge['class'] ?> flex-shrink-0">
                                            <span class="status-dot <?= $statusBadge['dot'] ?> me-1"></span><?= $statusBadge['text'] ?>
                                        </span>
                                    </div>

                                    <!-- 2. Khung Giá thuê cố định 42px -->
                                    <div class="room-price-banner mb-2.5">
                                        <span class="text-muted small fw-medium">Giá thuê:</span>
                                        <div class="text-nowrap">
                                            <span class="room-price-text fw-bold text-danger"><?= CoreHelper::formatCurrency($room['price']) ?></span>
                                            <span class="text-muted" style="font-size: 0.72rem;">/tháng</span>
                                        </div>
                                    </div>

                                    <!-- 3. Dòng tiện nghi cố định 22px -->
                                    <div class="room-amenities-row text-muted mb-2.5" title="<?= CoreHelper::e($room['amenities'] ?: 'Tiêu chuẩn') ?>">
                                        <i class="bi bi-stars text-warning flex-shrink-0"></i>
                                        <span class="text-truncate"><?= CoreHelper::e($room['amenities'] ?: 'Tiêu chuẩn') ?></span>
                                    </div>

                                    <!-- 4. Khung mô tả cố định 58px đồng nhất mọi thẻ -->
                                    <div class="room-tenant-box <?= $isAvailable ? 'occupied' : 'available' ?> mb-2.5">
                                        <div class="d-flex align-items-center gap-1.5 text-truncate mb-0.5">
                                            <?php if ($isAvailable): ?>
                                                <i class="bi bi-check-circle-fill text-success flex-shrink-0"></i>
                                                <strong class="text-success text-truncate">Sẵn sàng dọn vào ở ngay</strong>
                                            <?php else: ?>
                                                <i class="bi bi-info-circle-fill text-secondary flex-shrink-0"></i>
                                                <strong class="text-secondary text-truncate">Hiện đã có khách lưu trú</strong>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted text-truncate" style="font-size: 0.74rem;" title="<?= CoreHelper::e($room['description'] ?: 'Phòng thoáng mát, an ninh, giờ giấc tự do') ?>">
                                            <?= CoreHelper::e($room['description'] ?: 'Phòng thoáng mát, an ninh, giờ giấc tự do') ?>
                                        </div>
                                    </div>

                                    <!-- 5. Chân thẻ: Nút Xem 360° & Nút Đặt Phòng -->
                                    <div class="mt-auto pt-2.5 border-top d-flex justify-content-between align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill flex-grow-1"
                                                onclick="focusAndLoad360Room(<?= $roomJson ?>)">
                                            <i class="bi bi-badge-vr-fill me-1"></i>Xem 360°
                                        </button>
                                        <?php if ($isAvailable): ?>
                                            <button type="button" class="btn btn-sm btn-gradient-primary rounded-pill flex-grow-1"
                                                    onclick="openBookingModal(<?= $roomJson ?>)">
                                                <i class="bi bi-calendar2-check me-1"></i>Giữ phòng
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-light text-muted border rounded-pill flex-grow-1" disabled>
                                                Đã kín phòng
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="modern-footer mt-auto">
        <div class="app-container">
            <div class="footer-glass-card d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="footer-badge"><i class="bi bi-buildings-fill"></i></span>
                    <span>&copy; <?= date('Y') ?> <strong>SMART STAY - Hệ thống Phòng trọ &amp; Căn hộ Mini Công nghệ 360°</strong></span>
                </div>
                <div class="text-muted small d-flex align-items-center gap-3">
                    <span><i class="bi bi-geo-alt-fill text-danger me-1"></i>Xem phòng trực tuyến 24/7</span>
                    <span>&bull;</span>
                    <span><i class="bi bi-telephone-fill text-success me-1"></i>Liên hệ: <strong>0901.234.567</strong></span>
                </div>
            </div>
        </div>
    </footer>
</div>

<!-- Modal Đặt lịch xem phòng / Giữ phòng -->
<div class="modal fade" id="bookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="<?= CoreHelper::url('/booking/request') ?>" class="modal-content">
            <?= CoreHelper::csrfField() ?>
            <input type="hidden" name="room_number" id="book_room_number">
            <div class="modal-header">
                <div>
                    <h6 class="modal-title fw-bold mb-0" id="bookModalTitle">Đặt lịch xem &amp; Giữ phòng P103</h6>
                    <div class="text-muted" style="font-size: 0.74rem;" id="bookModalSub">Giá thuê: 2.500.000 đ/tháng</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2.5">
                    <div class="col-12">
                        <label class="form-label small fw-semibold mb-1">Họ và tên của bạn <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control form-control-sm" placeholder="Nhập họ và tên..." required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Số điện thoại / Zalo <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control form-control-sm" placeholder="VD: 0912345678" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Ngày dự kiến dọn vào</label>
                        <input type="date" name="move_in_date" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('+2 days')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold mb-1">Lời nhắn (Giờ muốn đến xem phòng, số người ở...)</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="VD: Mình muốn hẹn xem phòng vào 18h chiều nay..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-sm btn-gradient-primary px-3">
                    <i class="bi bi-send-check-fill me-1"></i>Gửi yêu cầu giữ phòng
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
<script>
let pannellumViewer = null;
let current360Room = <?= json_encode($default360Room, JSON_UNESCAPED_UNICODE) ?>;
let currentSceneType = 'main';
let isAutoRotating = true;

function formatVnd(amount) {
    return new Intl.NumberFormat('vi-VN').format(Math.round(Number(amount) || 0)) + ' đ';
}

/**
 * Tạo ảnh Panorama Equirectangular 360° (2048x1024) trực tiếp bằng Canvas cho từng Phòng & từng Góc nhìn
 * Đảm bảo hoạt động mượt mà 100% trên mọi trình duyệt (kể cả không có mạng) với đầy đủ nội thất trực quan.
 */
function generateEquirectangularPanorama(room, sceneType) {
    const canvas = document.createElement('canvas');
    const W = 2048;
    const H = 1024;
    canvas.width = W;
    canvas.height = H;
    const ctx = canvas.getContext('2d');

    // Bảng màu theo góc nhìn & mã phòng
    const palettes = {
        main:    { wallTop: '#f8fafc', wallBot: '#e2e8f0', floor: '#b4835b', ceiling: '#ffffff', accent: '#4f46e5' },
        kitchen: { wallTop: '#f1f5f9', wallBot: '#cbd5e1', floor: '#94a3b8', ceiling: '#f8fafc', accent: '#0891b2' },
        balcony: { wallTop: '#e0f2fe', wallBot: '#bae6fd', floor: '#78716c', ceiling: '#f0f9ff', accent: '#10b981' }
    };
    const pal = palettes[sceneType] || palettes.main;

    // 1. Vẽ Trần nhà (0 -> 0.25H), Tường (0.25H -> 0.72H), Sàn nhà (0.72H -> H)
    const ceilGrad = ctx.createLinearGradient(0, 0, 0, H * 0.28);
    ceilGrad.addColorStop(0, pal.ceiling);
    ceilGrad.addColorStop(1, '#e2e8f0');
    ctx.fillStyle = ceilGrad;
    ctx.fillRect(0, 0, W, H * 0.28);

    const wallGrad = ctx.createLinearGradient(0, H * 0.26, 0, H * 0.73);
    wallGrad.addColorStop(0, pal.wallTop);
    wallGrad.addColorStop(1, pal.wallBot);
    ctx.fillStyle = wallGrad;
    ctx.fillRect(0, H * 0.26, W, H * 0.47);

    const floorGrad = ctx.createLinearGradient(0, H * 0.72, 0, H);
    floorGrad.addColorStop(0, pal.floor);
    floorGrad.addColorStop(1, '#451a03');
    ctx.fillStyle = floorGrad;
    ctx.fillRect(0, H * 0.72, W, H * 0.28);

    // Vẽ đường phào chỉ trần & len chân tường
    ctx.fillStyle = '#cbd5e1';
    ctx.fillRect(0, H * 0.26 - 4, W, 8);
    ctx.fillStyle = '#475569';
    ctx.fillRect(0, H * 0.72 - 6, W, 12);

    // Kẻ vân sàn gỗ / gạch
    ctx.strokeStyle = 'rgba(0,0,0,0.14)';
    ctx.lineWidth = 2;
    for (let x = 0; x < W; x += 64) {
        ctx.beginPath();
        ctx.moveTo(x, H * 0.72);
        ctx.lineTo((x - W / 2) * 1.6 + W / 2, H);
        ctx.stroke();
    }
    for (let y = H * 0.75; y < H; y += 36) {
        ctx.beginPath();
        ctx.moveTo(0, y);
        ctx.lineTo(W, y);
        ctx.stroke();
    }

    // Kẻ góc tường 4 bức tường (0°, 90°, 180°, 270°)
    [W * 0.125, W * 0.375, W * 0.625, W * 0.875].forEach(wx => {
        ctx.strokeStyle = 'rgba(100, 116, 139, 0.32)';
        ctx.lineWidth = 4;
        ctx.beginPath();
        ctx.moveTo(wx, H * 0.26);
        ctx.lineTo(wx, H * 0.72);
        ctx.stroke();
    });

    // Đèn LED âm trần
    ctx.fillStyle = '#fef08a';
    [W * 0.25, W * 0.5, W * 0.75, W * 0.95].forEach(lx => {
        ctx.beginPath();
        ctx.ellipse(lx, H * 0.14, 48, 18, 0, 0, Math.PI * 2);
        ctx.fill();
        ctx.strokeStyle = '#ca8a04';
        ctx.lineWidth = 3;
        ctx.stroke();
    });

    if (sceneType === 'main') {
        // --- MẶT CHÍNH DIỆN (Giữa Canvas = Yaw 0°): GIƯỜNG NGỦ & TRANH TREO TƯỜNG + MÁY LẠNH ---
        // Máy lạnh Inverter trên cao
        ctx.fillStyle = '#ffffff';
        ctx.strokeStyle = '#94a3b8';
        ctx.lineWidth = 3;
        ctx.fillRect(W * 0.44, H * 0.30, 240, 62);
        ctx.strokeRect(W * 0.44, H * 0.30, 240, 62);
        ctx.fillStyle = '#0ea5e9';
        ctx.fillRect(W * 0.45, H * 0.34, 220, 6);
        ctx.fillStyle = '#10b981';
        ctx.font = 'bold 16px sans-serif';
        ctx.fillText('INVERTER COOL 18°C', W * 0.46, H * 0.325);

        // Tranh treo tường nghệ thuật
        ctx.fillStyle = '#1e1b4b';
        ctx.fillRect(W * 0.45, H * 0.39, 200, 115);
        ctx.fillStyle = '#818cf8';
        ctx.fillRect(W * 0.458, H * 0.40, 184, 95);
        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 22px sans-serif';
        ctx.fillText('SMART STAY ' + room.room_number, W * 0.465, H * 0.45);
        ctx.font = '15px sans-serif';
        ctx.fillText('Tầng ' + room.floor + ' • ' + room.area + 'm²', W * 0.478, H * 0.475);

        // Đầu giường & Nệm cao cấp
        ctx.fillStyle = '#334155';
        ctx.fillRect(W * 0.41, H * 0.54, 360, 185);
        ctx.fillStyle = '#f8fafc';
        ctx.fillRect(W * 0.42, H * 0.60, 340, 125);
        // Chăn ga màu Indigo
        ctx.fillStyle = '#4f46e5';
        ctx.fillRect(W * 0.42, H * 0.65, 340, 80);
        // 2 Gối nằm
        ctx.fillStyle = '#e0e7ff';
        ctx.fillRect(W * 0.44, H * 0.57, 120, 36);
        ctx.fillRect(W * 0.53, H * 0.57, 120, 36);

        // --- BÊN PHẢI (Yaw +90° = W * 0.75): CỬA KÍNH BAN CÔNG ĐÓN NẮNG ---
        ctx.fillStyle = '#38bdf8';
        ctx.fillRect(W * 0.68, H * 0.34, 280, 360);
        // Cảnh thành phố ngoài cửa sổ
        ctx.fillStyle = '#0284c7';
        ctx.fillRect(W * 0.69, H * 0.52, 60, 170);
        ctx.fillRect(W * 0.73, H * 0.46, 75, 230);
        ctx.fillRect(W * 0.78, H * 0.50, 65, 190);
        // Khung cửa kính
        ctx.strokeStyle = '#1e293b';
        ctx.lineWidth = 8;
        ctx.strokeRect(W * 0.68, H * 0.34, 280, 360);
        ctx.beginPath();
        ctx.moveTo(W * 0.75, H * 0.34);
        ctx.lineTo(W * 0.75, H * 0.70);
        ctx.stroke();

        // --- BÊN TRÁI (Yaw -90° = W * 0.22): TỦ QUẦN ÁO & BÀN LÀM VIỆC ---
        ctx.fillStyle = '#78350f';
        ctx.fillRect(W * 0.16, H * 0.35, 210, 375);
        ctx.strokeStyle = '#451a03';
        ctx.lineWidth = 5;
        ctx.strokeRect(W * 0.16, H * 0.35, 210, 375);
        ctx.beginPath();
        ctx.moveTo(W * 0.21, H * 0.35);
        ctx.lineTo(W * 0.21, H * 0.72);
        ctx.stroke();

        // Bàn làm việc
        ctx.fillStyle = '#92400e';
        ctx.fillRect(W * 0.28, H * 0.58, 160, 140);
        ctx.fillStyle = '#1e293b';
        ctx.fillRect(W * 0.30, H * 0.50, 90, 65);

        // --- PHÍA SAU (Yaw 180° = W * 0.04 & W * 0.92): KỆ BẾP & CỬA PHÒNG ---
        ctx.fillStyle = '#475569';
        ctx.fillRect(W * 0.03, H * 0.56, 200, 165);
        ctx.fillStyle = '#0f172a';
        ctx.fillRect(W * 0.90, H * 0.36, 130, 365);
    } else if (sceneType === 'kitchen') {
        // --- GÓC KỆ BẾP, TỦ LẠNH & WC RIÊNG ---
        // Tủ bếp trên
        ctx.fillStyle = '#f8fafc';
        ctx.fillRect(W * 0.40, H * 0.32, 420, 120);
        ctx.strokeStyle = '#94a3b8';
        ctx.lineWidth = 4;
        ctx.strokeRect(W * 0.40, H * 0.32, 420, 120);

        // Mặt đá bếp & Tủ bếp dưới
        ctx.fillStyle = '#1e293b';
        ctx.fillRect(W * 0.40, H * 0.55, 420, 22);
        ctx.fillStyle = '#0f766e';
        ctx.fillRect(W * 0.40, H * 0.57, 420, 155);

        // Tủ lạnh 2 cánh bên trái kệ bếp
        ctx.fillStyle = '#e2e8f0';
        ctx.fillRect(W * 0.28, H * 0.34, 175, 385);
        ctx.strokeStyle = '#64748b';
        ctx.strokeRect(W * 0.28, H * 0.34, 175, 385);

        // Cửa phòng tắm / WC riêng bên phải
        ctx.fillStyle = '#0284c7';
        ctx.fillRect(W * 0.68, H * 0.35, 190, 375);
        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 6;
        ctx.strokeRect(W * 0.68, H * 0.35, 190, 375);
        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 22px sans-serif';
        ctx.fillText('WC RIÊNG + NƯỚC NÓNG', W * 0.685, H * 0.52);
    } else {
        // --- GÓC BAN CÔNG & CỬA SỔ ĐÓN GIÓ ---
        ctx.fillStyle = '#7dd3fc';
        ctx.fillRect(W * 0.35, H * 0.28, 620, 440);
        // Tòa nhà & Cây xanh nhìn từ ban công
        ctx.fillStyle = '#0369a1';
        ctx.fillRect(W * 0.38, H * 0.44, 110, 280);
        ctx.fillRect(W * 0.46, H * 0.38, 140, 340);
        ctx.fillRect(W * 0.56, H * 0.42, 120, 300);
        // Lan can ban công kính cường lực
        ctx.fillStyle = 'rgba(15, 23, 42, 0.78)';
        ctx.fillRect(W * 0.35, H * 0.58, 620, 14);
        for (let bx = W * 0.36; bx < W * 0.65; bx += 45) {
            ctx.fillRect(bx, H * 0.58, 6, 140);
        }
        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 26px sans-serif';
        ctx.fillText('BAN CÔNG THOÁNG MÁT - TẦNG ' + room.floor, W * 0.41, H * 0.34);
    }

    return canvas.toDataURL('image/jpeg', 0.92);
}

/**
 * Khởi tạo hoặc cập nhật Trình xem 360° Pannellum với các điểm Hotspot tương tác
 */
function render360Viewer(room, sceneType, customDataUrl = null) {
    current360Room = room;
    currentSceneType = sceneType || 'main';

    document.getElementById('vrRoomTitle').innerText = `Phòng ${room.room_number} (Tầng ${room.floor} - ${room.area}m²)`;
    document.getElementById('vrRoomPrice').innerText = formatVnd(room.price) + '/tháng';
    document.getElementById('vrRoomAmenities').innerText = room.amenities || 'Máy lạnh, Nước nóng, Wifi, Kệ bếp, WC riêng';
    document.getElementById('vrRoomDesc').innerText = room.description || 'Phòng sạch đẹp, giờ giấc tự do, an ninh vân tay 24/7';

    ['main', 'kitchen', 'balcony'].forEach(sc => {
        const btn = document.getElementById('sceneBtn_' + sc);
        if (btn) btn.classList.toggle('active', sc === currentSceneType);
    });

    const panoUrl = customDataUrl || generateEquirectangularPanorama(room, currentSceneType);

    let hotSpots = [];
    if (!customDataUrl) {
        if (currentSceneType === 'main') {
            hotSpots = [
                { pitch: 18, yaw: -2, type: 'info', text: `Máy lạnh Inverter tiết kiệm điện - Phòng ${room.room_number}` },
                { pitch: -14, yaw: 0, type: 'info', text: `Khu vực giường nệm cao cấp (Tối đa ${room.max_occupants} người)` },
                { pitch: -5, yaw: -58, type: 'info', text: 'Tủ quần áo gỗ lớn & Bàn làm việc cá nhân' },
                { pitch: -2, yaw: 88, type: 'scene', text: 'Nhấn để ra Góc Ban Công & Cửa sổ đón nắng', clickHandlerFunc: () => switch360Scene('balcony') },
                { pitch: -8, yaw: -145, type: 'scene', text: 'Nhấn để sang Góc Kệ Bếp & WC riêng', clickHandlerFunc: () => switch360Scene('kitchen') }
            ];
        } else if (currentSceneType === 'kitchen') {
            hotSpots = [
                { pitch: -6, yaw: 0, type: 'info', text: 'Kệ bếp mặt đá hoa cương & Bồn rửa inox' },
                { pitch: -4, yaw: -38, type: 'info', text: 'Tủ lạnh tiết kiệm điện trang bị sẵn' },
                { pitch: -4, yaw: 45, type: 'info', text: 'Phòng tắm / WC khép kín có máy nước nóng' },
                { pitch: -6, yaw: 140, type: 'scene', text: 'Quay lại Góc Phòng Chính & Giường ngủ', clickHandlerFunc: () => switch360Scene('main') }
            ];
        } else {
            hotSpots = [
                { pitch: 5, yaw: 0, type: 'info', text: `Tầm nhìn thoáng từ Tầng ${room.floor} - Đón gió và ánh sáng tự nhiên` },
                { pitch: -8, yaw: 135, type: 'scene', text: 'Quay lại Góc Phòng Chính & Giường ngủ', clickHandlerFunc: () => switch360Scene('main') }
            ];
        }
    }

    if (pannellumViewer) {
        try { pannellumViewer.destroy(); } catch (e) {}
    }

    pannellumViewer = pannellum.viewer('panorama360', {
        type: 'equirectangular',
        panorama: panoUrl,
        autoLoad: true,
        autoRotate: isAutoRotating ? -2.2 : false,
        compass: true,
        showZoomCtrl: true,
        showFullscreenCtrl: true,
        hfov: 105,
        pitch: -3,
        yaw: 0,
        hotSpots: hotSpots
    });
}

function switch360Scene(sceneType) {
    render360Viewer(current360Room, sceneType);
}

function toggle360AutoRotate() {
    isAutoRotating = !isAutoRotating;
    const btn = document.getElementById('autoRotateBtn');
    if (btn) {
        btn.style.opacity = isAutoRotating ? '1' : '0.65';
    }
    if (pannellumViewer) {
        if (isAutoRotating) {
            pannellumViewer.startAutoRotate(-2.2);
        } else {
            pannellumViewer.stopAutoRotate();
        }
    }
}

function loadRoomInto360Viewer(room, itemEl) {
    document.querySelectorAll('.room-360-selector-item').forEach(el => el.classList.remove('active'));
    if (itemEl) {
        itemEl.classList.add('active');
    } else {
        const matched = document.getElementById('selector360_' + room.id);
        if (matched) matched.classList.add('active');
    }
    render360Viewer(room, 'main');
}

function focusAndLoad360Room(room) {
    loadRoomInto360Viewer(room, null);
    document.getElementById('tour360Section').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function loadCustom360File(inputEl) {
    const file = inputEl.files && inputEl.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function (e) {
        render360Viewer(current360Room, 'main', e.target.result);
    };
    reader.readAsDataURL(file);
}

function openBookingModal(room) {
    document.getElementById('book_room_number').value = room.room_number;
    document.getElementById('bookModalTitle').innerText = 'Đặt lịch xem & Giữ Phòng ' + room.room_number;
    document.getElementById('bookModalSub').innerText = `Tầng ${room.floor} • Diện tích ${room.area}m² • Giá thuê: ${formatVnd(room.price)}/tháng`;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('bookingModal')).show();
}

function openBookingForCurrent360Room() {
    openBookingModal(current360Room);
}

let introTimer = null;

function runWelcomeIntroAnimation() {
    const overlay = document.getElementById('welcomeIntroOverlay');
    const mainWrap = document.getElementById('showcaseMainWrapper');
    const fillEl = document.getElementById('introProgressFill');
    const percentEl = document.getElementById('introPercentText');
    const statusEl = document.getElementById('introStatusText');

    if (!overlay || !mainWrap) return;

    overlay.classList.remove('intro-hidden');
    mainWrap.classList.remove('showcase-ready');

    let progress = 0;
    if (introTimer) clearInterval(introTimer);

    introTimer = setInterval(function () {
        progress += 4;
        if (progress > 100) progress = 100;

        if (fillEl) fillEl.style.width = progress + '%';
        if (percentEl) percentEl.innerText = progress + '%';

        if (statusEl) {
            if (progress < 40) {
                statusEl.innerText = 'Đang khởi tạo không gian 360°...';
            } else if (progress < 80) {
                statusEl.innerText = 'Đang tải danh sách phòng còn trống...';
            } else {
                statusEl.innerText = 'Chào mừng bạn! Đang mở cửa phòng...';
            }
        }

        if (progress >= 100) {
            clearInterval(introTimer);
            setTimeout(finishWelcomeIntro, 280);
        }
    }, 85);
}

function finishWelcomeIntro() {
    if (introTimer) {
        clearInterval(introTimer);
        introTimer = null;
    }
    const overlay = document.getElementById('welcomeIntroOverlay');
    const mainWrap = document.getElementById('showcaseMainWrapper');

    if (overlay) overlay.classList.add('intro-hidden');
    if (mainWrap) mainWrap.classList.add('showcase-ready');

    // Đảm bảo khung 360° cập nhật đúng kích thước sau hiệu ứng mở màn
    setTimeout(function () {
        if (pannellumViewer && typeof pannellumViewer.resize === 'function') {
            pannellumViewer.resize();
        }
    }, 350);
}

function replayWelcomeIntro() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
    runWelcomeIntroAnimation();
}

document.addEventListener('DOMContentLoaded', function () {
    // Đưa modal ra trực tiếp dưới body
    document.querySelectorAll('.modal').forEach(function (m) {
        if (m.parentElement !== document.body) {
            document.body.appendChild(m);
        }
    });

    // Khởi tạo phòng 360 mặc định ngay khi mở trang
    render360Viewer(current360Room, 'main');

    // Nếu người dùng vừa bấm lọc phòng hoặc gửi form giữ phòng thì vào thẳng, ngược lại chạy hiệu ứng lời chào
    const hasFilterQuery = window.location.search.length > 1;
    const hasFlashAlert = document.querySelector('.alert-modern') !== null;

    if (hasFilterQuery || hasFlashAlert) {
        finishWelcomeIntro();
    } else {
        runWelcomeIntroAnimation();
    }
});
</script>
</body>
</html>
