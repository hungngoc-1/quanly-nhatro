-- ==========================================================
-- HỆ THỐNG QUẢN LÝ NHÀ TRỌ (WAMP / MySQL / MariaDB)
-- Database: quanly_nhatro
-- ==========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `quanly_nhatro`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `quanly_nhatro`;

-- ----------------------------------------------------------
-- 1. Bảng users (Tài khoản đăng nhập: Chủ trọ, Nhân viên, Khách thuê)
-- Mật khẩu mặc định cho tất cả tài khoản mẫu: 123456
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `role` ENUM('admin', 'staff', 'tenant') NOT NULL DEFAULT 'tenant',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `phone`, `role`, `status`) VALUES
(1, 'admin', '$2y$10$atVaTQP5WQPl.HxhWwodbOTHaPIDGDpw5uX5/6hi2CVcUhAp2iFRO', 'Nguyễn Quản Lý (Chủ trọ)', 'admin@nhatro.vn', '0901234567', 'admin', 'active'),
(2, 'nhanvien', '$2y$10$atVaTQP5WQPl.HxhWwodbOTHaPIDGDpw5uX5/6hi2CVcUhAp2iFRO', 'Lê Hoàng Nam (Quản lý viên)', 'nhanvien@nhatro.vn', '0902345678', 'staff', 'active'),
(3, 'khachthue1', '$2y$10$atVaTQP5WQPl.HxhWwodbOTHaPIDGDpw5uX5/6hi2CVcUhAp2iFRO', 'Nguyễn Văn An', 'an.nguyen@gmail.com', '0912345001', 'tenant', 'active'),
(4, 'khachthue2', '$2y$10$atVaTQP5WQPl.HxhWwodbOTHaPIDGDpw5uX5/6hi2CVcUhAp2iFRO', 'Trần Thị Bình', 'binh.tran@gmail.com', '0912345002', 'tenant', 'active'),
(5, 'khachthue3', '$2y$10$atVaTQP5WQPl.HxhWwodbOTHaPIDGDpw5uX5/6hi2CVcUhAp2iFRO', 'Phạm Minh Tuấn', 'tuan.pham@gmail.com', '0912345003', 'tenant', 'active'),
(6, 'khachthue4', '$2y$10$atVaTQP5WQPl.HxhWwodbOTHaPIDGDpw5uX5/6hi2CVcUhAp2iFRO', 'Võ Thị Thu Hà', 'ha.vo@gmail.com', '0912345004', 'tenant', 'active');

-- ----------------------------------------------------------
-- 2. Bảng rooms (Danh sách phòng trọ)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `room_number` VARCHAR(20) NOT NULL UNIQUE,
  `floor` INT NOT NULL DEFAULT 1,
  `area` DECIMAL(6,2) NOT NULL DEFAULT 20.00,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 2500000.00,
  `max_occupants` INT NOT NULL DEFAULT 2,
  `status` ENUM('available', 'occupied', 'maintenance') NOT NULL DEFAULT 'available',
  `amenities` VARCHAR(255) DEFAULT 'Máy lạnh, Nước nóng, Wifi, Kệ bếp, WC riêng',
  `description` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `rooms` (`id`, `room_number`, `floor`, `area`, `price`, `max_occupants`, `status`, `amenities`, `description`) VALUES
(1, 'P101', 1, 22.00, 2800000.00, 2, 'occupied', 'Máy lạnh, Nước nóng, Wifi, Gác lửng, WC riêng', 'Phòng tầng trệt thoáng mát, gần cổng chính'),
(2, 'P102', 1, 25.00, 3200000.00, 3, 'occupied', 'Máy lạnh, Tủ lạnh, Nước nóng, Wifi, Ban công', 'Phòng rộng có cửa sổ lớn'),
(3, 'P103', 1, 20.00, 2500000.00, 2, 'available', 'Quạt trần, Nước nóng, Wifi, Kệ bếp, WC riêng', 'Phòng tiêu chuẩn tiết kiệm'),
(4, 'P201', 2, 24.00, 3000000.00, 2, 'occupied', 'Máy lạnh, Nước nóng, Wifi, Ban công, Tủ quần áo', 'Phòng lầu 1 hướng Đông Nam'),
(5, 'P202', 2, 24.00, 3000000.00, 2, 'occupied', 'Máy lạnh, Nước nóng, Wifi, Ban công, Kệ bếp', 'Phòng lầu 1 yên tĩnh'),
(6, 'P203', 2, 28.00, 3500000.00, 3, 'available', 'Máy lạnh, Tủ lạnh, Máy giặt, Nước nóng, Ban công', 'Phòng studio cao cấp đầy đủ nội thất'),
(7, 'P301', 3, 22.00, 2700000.00, 2, 'maintenance', 'Máy lạnh, Nước nóng, Wifi, Sân thượng chung', 'Đang sơn sửa và bảo trì điều hòa'),
(8, 'P302', 3, 25.00, 2900000.00, 2, 'available', 'Máy lạnh, Nước nóng, Wifi, Ban công thoáng', 'Phòng tầng 3 view đẹp');

-- ----------------------------------------------------------
-- 3. Bảng tenants (Khách thuê trọ)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `tenants`;
CREATE TABLE `tenants` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `cccd` VARCHAR(20) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `dob` DATE DEFAULT NULL,
  `gender` ENUM('Nam', 'Nữ', 'Khác') NOT NULL DEFAULT 'Nam',
  `hometown` VARCHAR(150) DEFAULT NULL,
  `occupation` VARCHAR(100) DEFAULT NULL,
  `emergency_contact` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('staying', 'moved_out') NOT NULL DEFAULT 'staying',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tenants_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tenants` (`id`, `user_id`, `full_name`, `cccd`, `phone`, `email`, `dob`, `gender`, `hometown`, `occupation`, `emergency_contact`, `status`) VALUES
(1, 3, 'Nguyễn Văn An', '079099001234', '0912345001', 'an.nguyen@gmail.com', '1999-05-14', 'Nam', 'TP. Hồ Chí Minh', 'Kỹ sư phần mềm', '0988111222 (Bố)', 'staying'),
(2, 4, 'Trần Thị Bình', '048198005678', '0912345002', 'binh.tran@gmail.com', '2001-09-20', 'Nữ', 'Đà Nẵng', 'Kế toán viên', '0988333444 (Mẹ)', 'staying'),
(3, 5, 'Phạm Minh Tuấn', '036097009101', '0912345003', 'tuan.pham@gmail.com', '1997-11-02', 'Nam', 'Nam Định', 'Nhân viên kinh doanh', '0977555666 (Anh trai)', 'staying'),
(4, 6, 'Võ Thị Thu Hà', '082199003456', '0912345004', 'ha.vo@gmail.com', '2002-03-18', 'Nữ', 'Tiền Giang', 'Sinh viên năm cuối', '0966777888 (Mẹ)', 'staying'),
(5, NULL, 'Đặng Quốc Khánh', '075095007890', '0912345005', 'khanh.dang@gmail.com', '1995-07-25', 'Nam', 'Đồng Nai', 'Thiết kế đồ họa', '0955999000 (Chị gái)', 'moved_out');

-- ----------------------------------------------------------
-- 4. Bảng contracts (Hợp đồng thuê phòng)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `contracts`;
CREATE TABLE `contracts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `contract_code` VARCHAR(30) NOT NULL UNIQUE,
  `room_id` INT UNSIGNED NOT NULL,
  `tenant_id` INT UNSIGNED NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `deposit_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `monthly_rent` DECIMAL(12,2) NOT NULL,
  `electricity_rate` DECIMAL(10,2) NOT NULL DEFAULT 3500.00,
  `water_rate` DECIMAL(10,2) NOT NULL DEFAULT 15000.00,
  `service_fee` DECIMAL(10,2) NOT NULL DEFAULT 150000.00,
  `status` ENUM('active', 'expired', 'terminated') NOT NULL DEFAULT 'active',
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_contracts_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_contracts_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `contracts` (`id`, `contract_code`, `room_id`, `tenant_id`, `start_date`, `end_date`, `deposit_amount`, `monthly_rent`, `electricity_rate`, `water_rate`, `service_fee`, `status`, `notes`) VALUES
(1, 'HD-2026-001', 1, 1, '2026-01-01', '2026-12-31', 2800000.00, 2800000.00, 3500.00, 15000.00, 150000.00, 'active', 'Thanh toán từ ngày 01 đến 05 hàng tháng'),
(2, 'HD-2026-002', 2, 2, '2026-02-01', '2027-01-31', 3200000.00, 3200000.00, 3500.00, 15000.00, 150000.00, 'active', 'Ở 2 người, có gửi 1 xe máy'),
(3, 'HD-2026-003', 4, 3, '2026-03-01', '2026-11-05', 3000000.00, 3000000.00, 3500.00, 15000.00, 150000.00, 'active', 'Sắp hết hạn hợp đồng, cần liên hệ gia hạn'),
(4, 'HD-2026-004', 5, 4, '2026-04-01', '2027-03-31', 3000000.00, 3000000.00, 3500.00, 15000.00, 150000.00, 'active', 'Sinh viên thuê dài hạn'),
(5, 'HD-2025-009', 3, 5, '2025-06-01', '2026-05-31', 2500000.00, 2500000.00, 3500.00, 15000.00, 120000.00, 'terminated', 'Đã thanh lý hợp đồng và hoàn trả cọc');

-- ----------------------------------------------------------
-- 5. Bảng utilities (Chỉ số Điện & Nước hàng tháng)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `utilities`;
CREATE TABLE `utilities` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `room_id` INT UNSIGNED NOT NULL,
  `contract_id` INT UNSIGNED DEFAULT NULL,
  `month` TINYINT UNSIGNED NOT NULL,
  `year` SMALLINT UNSIGNED NOT NULL,
  `old_electric` INT UNSIGNED NOT NULL DEFAULT 0,
  `new_electric` INT UNSIGNED NOT NULL DEFAULT 0,
  `electric_used` INT UNSIGNED NOT NULL DEFAULT 0,
  `electric_rate` DECIMAL(10,2) NOT NULL DEFAULT 3500.00,
  `electric_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `old_water` INT UNSIGNED NOT NULL DEFAULT 0,
  `new_water` INT UNSIGNED NOT NULL DEFAULT 0,
  `water_used` INT UNSIGNED NOT NULL DEFAULT 0,
  `water_rate` DECIMAL(10,2) NOT NULL DEFAULT 15000.00,
  `water_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `other_fee` DECIMAL(10,2) NOT NULL DEFAULT 150000.00,
  `recorded_at` DATE NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_room_month_year` (`room_id`, `month`, `year`),
  CONSTRAINT `fk_utilities_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_utilities_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `utilities` (`id`, `room_id`, `contract_id`, `month`, `year`, `old_electric`, `new_electric`, `electric_used`, `electric_rate`, `electric_amount`, `old_water`, `new_water`, `water_used`, `water_rate`, `water_amount`, `other_fee`, `recorded_at`) VALUES
-- Tháng 9/2026
(1, 1, 1, 9, 2026, 1200, 1310, 110, 3500.00, 385000.00, 80, 88, 8, 15000.00, 120000.00, 150000.00, '2026-09-28'),
(2, 2, 2, 9, 2026, 950, 1090, 140, 3500.00, 490000.00, 65, 75, 10, 15000.00, 150000.00, 150000.00, '2026-09-28'),
(3, 4, 3, 9, 2026, 540, 630, 90, 3500.00, 315000.00, 42, 48, 6, 15000.00, 90000.00, 150000.00, '2026-09-28'),
(4, 5, 4, 9, 2026, 410, 510, 100, 3500.00, 350000.00, 30, 37, 7, 15000.00, 105000.00, 150000.00, '2026-09-28'),
-- Tháng 10/2026
(5, 1, 1, 10, 2026, 1310, 1430, 120, 3500.00, 420000.00, 88, 97, 9, 15000.00, 135000.00, 150000.00, '2026-10-18'),
(6, 2, 2, 10, 2026, 1090, 1240, 150, 3500.00, 525000.00, 75, 86, 11, 15000.00, 165000.00, 150000.00, '2026-10-18'),
(7, 4, 3, 10, 2026, 630, 730, 100, 3500.00, 350000.00, 48, 55, 7, 15000.00, 105000.00, 150000.00, '2026-10-18'),
(8, 5, 4, 10, 2026, 510, 590, 80, 3500.00, 280000.00, 37, 43, 6, 15000.00, 90000.00, 150000.00, '2026-10-18');

-- ----------------------------------------------------------
-- 6. Bảng invoices (Hóa đơn tiền trọ hàng tháng)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `invoice_code` VARCHAR(30) NOT NULL UNIQUE,
  `contract_id` INT UNSIGNED NOT NULL,
  `room_id` INT UNSIGNED NOT NULL,
  `tenant_id` INT UNSIGNED NOT NULL,
  `utility_id` INT UNSIGNED DEFAULT NULL,
  `month` TINYINT UNSIGNED NOT NULL,
  `year` SMALLINT UNSIGNED NOT NULL,
  `room_fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `electric_fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `water_fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `service_fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('unpaid', 'partial', 'paid', 'overdue', 'cancelled') NOT NULL DEFAULT 'unpaid',
  `due_date` DATE NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_invoice_room_month_year` (`room_id`, `month`, `year`),
  CONSTRAINT `fk_invoices_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_invoices_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_invoices_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_invoices_utility` FOREIGN KEY (`utility_id`) REFERENCES `utilities` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `invoices` (`id`, `invoice_code`, `contract_id`, `room_id`, `tenant_id`, `utility_id`, `month`, `year`, `room_fee`, `electric_fee`, `water_fee`, `service_fee`, `discount`, `total_amount`, `paid_amount`, `status`, `due_date`, `notes`) VALUES
-- Hóa đơn Tháng 9/2026 (Đã thanh toán)
(1, 'INV-202609-P101', 1, 1, 1, 1, 9, 2026, 2800000.00, 385000.00, 120000.00, 150000.00, 0.00, 3455000.00, 3455000.00, 'paid', '2026-10-05', 'Đã thu đủ tiền phòng tháng 09/2026'),
(2, 'INV-202609-P102', 2, 2, 2, 2, 9, 2026, 3200000.00, 490000.00, 150000.00, 150000.00, 0.00, 3990000.00, 3990000.00, 'paid', '2026-10-05', 'Chuyển khoản Vietcombank'),
(3, 'INV-202609-P201', 3, 4, 3, 3, 9, 2026, 3000000.00, 315000.00, 90000.00, 150000.00, 0.00, 3555000.00, 3555000.00, 'paid', '2026-10-05', 'Thu tiền mặt'),
(4, 'INV-202609-P202', 4, 5, 4, 4, 9, 2026, 3000000.00, 350000.00, 105000.00, 150000.00, 50000.00, 3555000.00, 3555000.00, 'paid', '2026-10-05', 'Giảm 50k hỗ trợ sinh viên'),
-- Hóa đơn Tháng 10/2026 (Đủ trạng thái: Đã đóng, Đóng 1 phần, Chưa đóng, Quá hạn)
(5, 'INV-202610-P101', 1, 1, 1, 5, 10, 2026, 2800000.00, 420000.00, 135000.00, 150000.00, 0.00, 3505000.00, 2000000.00, 'partial', '2026-10-25', 'Khách đã ứng trước 2.000.000đ'),
(6, 'INV-202610-P102', 2, 2, 2, 6, 10, 2026, 3200000.00, 525000.00, 165000.00, 150000.00, 0.00, 4040000.00, 4040000.00, 'paid', '2026-10-25', 'Đã thanh toán đủ qua chuyển khoản'),
(7, 'INV-202610-P201', 3, 4, 3, 7, 10, 2026, 3000000.00, 350000.00, 105000.00, 150000.00, 0.00, 3605000.00, 0.00, 'overdue', '2026-10-15', 'Quá hạn thanh toán, cần nhắc khách'),
(8, 'INV-202610-P202', 4, 5, 4, 8, 10, 2026, 3000000.00, 280000.00, 90000.00, 150000.00, 0.00, 3520000.00, 0.00, 'unpaid', '2026-10-28', 'Hóa đơn tháng 10/2026');

-- ----------------------------------------------------------
-- 7. Bảng payments (Lịch sử thu tiền / Thanh toán)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `payment_code` VARCHAR(30) NOT NULL UNIQUE,
  `invoice_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `payment_method` ENUM('cash', 'bank_transfer', 'momo', 'vnpay') NOT NULL DEFAULT 'bank_transfer',
  `transaction_ref` VARCHAR(100) DEFAULT NULL,
  `payment_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `received_by` INT UNSIGNED DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_payments_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_user` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `payments` (`id`, `payment_code`, `invoice_id`, `amount`, `payment_method`, `transaction_ref`, `payment_date`, `received_by`, `notes`) VALUES
(1, 'PAY-202609-001', 1, 3455000.00, 'bank_transfer', 'VCB99881122', '2026-10-02 09:30:00', 1, 'Thu tiền phòng P101 tháng 9'),
(2, 'PAY-202609-002', 2, 3990000.00, 'bank_transfer', 'TCB77665544', '2026-10-03 14:15:00', 1, 'Thu tiền phòng P102 tháng 9'),
(3, 'PAY-202609-003', 3, 3555000.00, 'cash', NULL, '2026-10-04 18:00:00', 2, 'Thu tiền mặt tại phòng P201'),
(4, 'PAY-202609-004', 4, 3555000.00, 'momo', 'MOMO11223344', '2026-10-04 20:10:00', 1, 'Thanh toán qua ví MoMo P202'),
(5, 'PAY-202610-001', 6, 4040000.00, 'bank_transfer', 'VCB20261019', '2026-10-19 10:00:00', 1, 'Thanh toán đủ hóa đơn tháng 10 P102'),
(6, 'PAY-202610-002', 5, 2000000.00, 'bank_transfer', 'MB20261019', '2026-10-19 15:45:00', 2, 'Khách P101 đóng trước 1 phần tháng 10');

SET FOREIGN_KEY_CHECKS = 1;
