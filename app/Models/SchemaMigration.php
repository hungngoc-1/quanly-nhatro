<?php

namespace App\Models;

use PDO;

class SchemaMigration
{
    private static bool $migrated = false;

    public static function ensureMigrated(PDO $pdo): void
    {
        if (self::$migrated) {
            return;
        }
        self::$migrated = true;

        try {
            // 1. Tạo bảng buildings (Tòa nhà)
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `buildings` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `building_code` VARCHAR(20) NOT NULL UNIQUE,
                    `building_name` VARCHAR(120) NOT NULL,
                    `address` VARCHAR(255) DEFAULT NULL,
                    `manager_id` INT UNSIGNED DEFAULT NULL,
                    `total_floors` INT NOT NULL DEFAULT 5,
                    `description` TEXT DEFAULT NULL,
                    `status` ENUM('active', 'inactive', 'maintenance') NOT NULL DEFAULT 'active',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 2. Tạo bảng room_types (Loại phòng)
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `room_types` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `type_code` VARCHAR(30) NOT NULL UNIQUE,
                    `type_name` VARCHAR(100) NOT NULL,
                    `default_price` DECIMAL(12,2) NOT NULL DEFAULT 2500000.00,
                    `default_area` FLOAT NOT NULL DEFAULT 20,
                    `max_occupants` INT NOT NULL DEFAULT 2,
                    `default_amenities` VARCHAR(255) DEFAULT NULL,
                    `description` TEXT DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            if (!self::columnExists($pdo, 'room_types', 'default_price')) {
                $pdo->exec("ALTER TABLE `room_types` ADD COLUMN `default_price` DECIMAL(12,2) NOT NULL DEFAULT 2500000.00 AFTER `type_name`");
                if (self::columnExists($pdo, 'room_types', 'base_price')) {
                    $pdo->exec("UPDATE `room_types` SET `default_price` = `base_price`");
                }
            }
            if (!self::columnExists($pdo, 'room_types', 'updated_at')) {
                $pdo->exec("ALTER TABLE `room_types` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            }
            if (!self::columnExists($pdo, 'buildings', 'updated_at')) {
                $pdo->exec("ALTER TABLE `buildings` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            }

            // 3. Bổ sung cột building_id cho bảng users
            if (!self::columnExists($pdo, 'users', 'building_id')) {
                $pdo->exec("ALTER TABLE `users` ADD COLUMN `building_id` INT UNSIGNED DEFAULT NULL AFTER `role`");
            }

            // 4. Bổ sung cột building_id và room_type_id cho bảng rooms
            if (!self::columnExists($pdo, 'rooms', 'building_id')) {
                $pdo->exec("ALTER TABLE `rooms` ADD COLUMN `building_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`");
            }
            if (!self::columnExists($pdo, 'rooms', 'room_type_id')) {
                $pdo->exec("ALTER TABLE `rooms` ADD COLUMN `room_type_id` INT UNSIGNED DEFAULT 1 AFTER `building_id`");
            }

            // 5. Bổ sung các cột tiền cọc, chữ ký & gia hạn tự động cho bảng contracts
            if (!self::columnExists($pdo, 'contracts', 'deposit_date')) {
                $pdo->exec("ALTER TABLE `contracts` ADD COLUMN `deposit_date` DATE DEFAULT NULL AFTER `deposit_amount`");
                $pdo->exec("UPDATE `contracts` SET `deposit_date` = DATE_SUB(`start_date`, INTERVAL 12 DAY) WHERE `deposit_date` IS NULL");
            }
            if (!self::columnExists($pdo, 'contracts', 'deposit_status')) {
                $pdo->exec("ALTER TABLE `contracts` ADD COLUMN `deposit_status` ENUM('held', 'refunded', 'forfeited') NOT NULL DEFAULT 'held' AFTER `deposit_amount`");
            }
            if (!self::columnExists($pdo, 'contracts', 'signature_type')) {
                $pdo->exec("ALTER TABLE `contracts` ADD COLUMN `signature_type` ENUM('online', 'paper') NOT NULL DEFAULT 'online' AFTER `status`");
            }
            if (!self::columnExists($pdo, 'contracts', 'signature_status')) {
                $pdo->exec("ALTER TABLE `contracts` ADD COLUMN `signature_status` ENUM('pending', 'signed') NOT NULL DEFAULT 'pending' AFTER `signature_type`");
            }
            if (!self::columnExists($pdo, 'contracts', 'tenant_signature')) {
                $pdo->exec("ALTER TABLE `contracts` ADD COLUMN `tenant_signature` LONGTEXT DEFAULT NULL AFTER `signature_status`");
            }
            if (!self::columnExists($pdo, 'contracts', 'manager_signature')) {
                $pdo->exec("ALTER TABLE `contracts` ADD COLUMN `manager_signature` LONGTEXT DEFAULT NULL AFTER `tenant_signature`");
            }
            if (!self::columnExists($pdo, 'contracts', 'signed_at')) {
                $pdo->exec("ALTER TABLE `contracts` ADD COLUMN `signed_at` DATETIME DEFAULT NULL AFTER `manager_signature`");
            }
            if (!self::columnExists($pdo, 'contracts', 'scan_file')) {
                $pdo->exec("ALTER TABLE `contracts` ADD COLUMN `scan_file` VARCHAR(255) DEFAULT NULL AFTER `signed_at`");
            }
            if (!self::columnExists($pdo, 'contracts', 'terminated_at')) {
                $pdo->exec("ALTER TABLE `contracts` ADD COLUMN `terminated_at` DATE DEFAULT NULL AFTER `status`");
            }
            if (!self::columnExists($pdo, 'contracts', 'termination_reason')) {
                $pdo->exec("ALTER TABLE `contracts` ADD COLUMN `termination_reason` VARCHAR(255) DEFAULT NULL AFTER `terminated_at`");
            }
            if (!self::columnExists($pdo, 'contracts', 'renewal_status')) {
                $pdo->exec("ALTER TABLE `contracts` ADD COLUMN `renewal_status` ENUM('none', 'notified', 'renewed', 'declined', 'auto_no_renew') NOT NULL DEFAULT 'none' AFTER `termination_reason`");
            }
            if (!self::columnExists($pdo, 'contracts', 'renewal_months')) {
                $pdo->exec("ALTER TABLE `contracts` ADD COLUMN `renewal_months` INT NOT NULL DEFAULT 12 AFTER `renewal_status`");
            }
            if (!self::columnExists($pdo, 'contracts', 'renewal_responded_at')) {
                $pdo->exec("ALTER TABLE `contracts` ADD COLUMN `renewal_responded_at` DATETIME DEFAULT NULL AFTER `renewal_months`");
            }

            // 6. Bổ sung các cột tiền cọc (khi vào ở / rời đi) cho bảng invoices
            if (!self::columnExists($pdo, 'invoices', 'invoice_type')) {
                $pdo->exec("ALTER TABLE `invoices` ADD COLUMN `invoice_type` ENUM('monthly', 'move_in', 'move_out') NOT NULL DEFAULT 'monthly' AFTER `invoice_code`");
            }
            if (!self::columnExists($pdo, 'invoices', 'deposit_fee')) {
                $pdo->exec("ALTER TABLE `invoices` ADD COLUMN `deposit_fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `service_fee`");
            }
            if (!self::columnExists($pdo, 'invoices', 'deposit_refund')) {
                $pdo->exec("ALTER TABLE `invoices` ADD COLUMN `deposit_refund` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `deposit_fee`");
            }

            // 7. Seed dữ liệu mặc định cho 4 Loại phòng (room_types) nếu trống
            $countTypes = (int) $pdo->query("SELECT COUNT(*) FROM `room_types`")->fetchColumn();
            if ($countTypes === 0) {
                $pdo->exec("
                    INSERT INTO `room_types` (`id`, `type_code`, `type_name`, `default_price`, `default_area`, `max_occupants`, `description`) VALUES
                    (1, 'STD', 'Phòng Tiêu chuẩn (Standard)', 2600000.00, 20, 2, 'Phòng đơn tiêu chuẩn, thoáng mát, phù hợp sinh viên & người đi làm.'),
                    (2, 'DUPLEX', 'Phòng Gác lửng (Duplex)', 3200000.00, 25, 3, 'Phòng có gác lửng rộng rãi, tách biệt không gian ngủ và sinh hoạt.'),
                    (3, 'STUDIO', 'Căn hộ Studio Full Nội thất', 3800000.00, 30, 3, 'Căn hộ Studio trang bị đầy đủ nội thất cao cấp, chỉ việc xách vali vào ở.'),
                    (4, 'VIP', 'Căn hộ 1 Phòng ngủ (Deluxe VIP)', 4500000.00, 38, 4, 'Căn hộ cao cấp diện tích lớn dành cho gia đình hoặc nhóm bạn.')
                ");
            }

            // 8. Đảm bảo có đủ 3 tài khoản Chủ trọ / Quản lý cho 3 Tòa nhà A, B, C & dữ liệu phong phú
            self::seedManagersAndBuildings($pdo);
            self::normalizeAllContractDates($pdo);
            self::seedRichVisualData($pdo);

            // 9. Tự động kiểm tra và cập nhật trạng thái thông báo gia hạn hợp đồng trước 30 ngày
            self::syncContractRenewalNotifications($pdo);
        } catch (\Throwable $e) {
            // Bỏ qua lỗi nếu DB đang khởi tạo
        }
    }

    private static function columnExists(PDO $pdo, string $table, string $column): bool
    {
        $quotedCol = $pdo->quote($column);
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE {$quotedCol}");
        return $stmt ? (bool) $stmt->fetch() : false;
    }

    private static function seedManagersAndBuildings(PDO $pdo): void
    {
        $hash123456 = password_hash('123456', PASSWORD_BCRYPT);

        // Cập nhật user 'nhanvien' thành Quản lý (Chủ trọ) Tòa A (building_id = 1)
        $pdo->exec("UPDATE `users` SET `building_id` = 1, `full_name` = 'Quản lý Tòa A (Nguyễn Văn Quản)' WHERE `username` = 'nhanvien' AND (`building_id` IS NULL OR `building_id` = 0)");
        $pdo->exec("UPDATE `users` SET `building_id` = NULL, `full_name` = 'Quản trị viên Hệ thống (Admin)' WHERE `username` = 'admin'");

        // Tạo thêm tài khoản Quản lý Tòa B (`quanly_b`) và Quản lý Tòa C (`quanly_c`) nếu chưa có
        $stmtCheckB = $pdo->prepare("SELECT id FROM `users` WHERE `username` = 'quanly_b' LIMIT 1");
        $stmtCheckB->execute();
        $managerBId = (int) $stmtCheckB->fetchColumn();
        if ($managerBId === 0) {
            $stmtInsB = $pdo->prepare("
                INSERT INTO `users` (`username`, `password`, `full_name`, `email`, `phone`, `role`, `building_id`, `status`)
                VALUES ('quanly_b', :pw, 'Quản lý Tòa B (Trần Thị Bích)', 'quanlyb@nhatro.vn', '0902345678', 'staff', 2, 'active')
            ");
            $stmtInsB->execute(['pw' => $hash123456]);
            $managerBId = (int) $pdo->lastInsertId();
        }

        $stmtCheckC = $pdo->prepare("SELECT id FROM `users` WHERE `username` = 'quanly_c' LIMIT 1");
        $stmtCheckC->execute();
        $managerCId = (int) $stmtCheckC->fetchColumn();
        if ($managerCId === 0) {
            $stmtInsC = $pdo->prepare("
                INSERT INTO `users` (`username`, `password`, `full_name`, `email`, `phone`, `role`, `building_id`, `status`)
                VALUES ('quanly_c', :pw, 'Quản lý Tòa C (Lê Hoàng Nam)', 'quanlyc@nhatro.vn', '0903456789', 'staff', 3, 'active')
            ");
            $stmtInsC->execute(['pw' => $hash123456]);
            $managerCId = (int) $pdo->lastInsertId();
        }

        $stmtCheckA = $pdo->query("SELECT id FROM `users` WHERE `username` = 'nhanvien' LIMIT 1");
        $managerAId = (int) $stmtCheckA->fetchColumn() ?: 2;

        // Seed 3 Tòa nhà nếu bảng buildings đang trống
        $countBuildings = (int) $pdo->query("SELECT COUNT(*) FROM `buildings`")->fetchColumn();
        if ($countBuildings === 0) {
            $stmtBld = $pdo->prepare("
                INSERT INTO `buildings` (`id`, `building_code`, `building_name`, `address`, `manager_id`, `total_floors`, `description`, `status`) VALUES
                (1, 'BLD-A', 'Tòa A - Smart Stay Sunrise', 'Số 128 Cầu Giấy, Quận Cầu Giấy, TP. Hà Nội', :mA, 5, 'Tòa nhà trung tâm Cầu Giấy, gần các trường ĐH lớn, có thang máy & hầm để xe rộng.', 'active'),
                (2, 'BLD-B', 'Tòa B - Smart Stay Emerald', 'Số 45 Nguyễn Chí Thanh, Quận Đống Đa, TP. Hà Nội', :mB, 5, 'Khu căn hộ dịch vụ cao cấp Đống Đa, an ninh vân tay 2 lớp, sân phơi tầng thượng.', 'active'),
                (3, 'BLD-C', 'Tòa C - Smart Stay Horizon', 'Số 88 Nguyễn Trãi, Quận Thanh Xuân, TP. Hà Nội', :mC, 5, 'Tòa nhà căn hộ Studio & Duplex hiện đại ngay ngã tư Sở, tiện ích khép kín.', 'active')
            ");
            $stmtBld->execute([
                'mA' => $managerAId,
                'mB' => $managerBId,
                'mC' => $managerCId,
            ]);
        }

        // Đồng bộ tiền cọc bằng 1 tháng tiền thuê phòng cho các hợp đồng
        $pdo->exec("UPDATE `contracts` SET `deposit_amount` = `monthly_rent` WHERE `deposit_amount` != `monthly_rent`");
    }

    /**
     * Chuẩn hóa toàn bộ ngày bắt đầu hợp đồng (start_date) vào mùng 01 đầu tháng
     * và ngày hết hạn (end_date) tính tròn tháng từ ngày mùng 01 bắt đầu hợp đồng (không tính từ ngày lên HĐ/đặt cọc).
     */
    private static function normalizeAllContractDates(PDO $pdo): void
    {
        // Hợp đồng #1 (khachthue1 - Nguyễn Văn An - P101):
        // Đặt cọc trước ngày 18/10/2025 -> Hiệu lực từ mùng 01/11/2025 -> Hết hạn 12 tháng vào đúng mùng 01/11/2026
        $pdo->exec("
            UPDATE `contracts`
            SET `deposit_date` = '2025-10-18',
                `start_date`   = '2025-11-01',
                `end_date`     = '2026-11-01',
                `renewal_status` = 'notified'
            WHERE `id` = 1 AND `renewal_status` != 'renewed' AND (`start_date` != '2025-11-01' OR `end_date` != '2026-11-01')
        ");

        // Hợp đồng #2 (khachthue2 - Trần Thị Bình - P102):
        // Đặt cọc trước ngày 20/01/2026 -> Hiệu lực từ mùng 01/02/2026 -> Hết hạn 12 tháng vào đúng mùng 01/02/2027
        $pdo->exec("
            UPDATE `contracts`
            SET `deposit_date` = '2026-01-20',
                `start_date`   = '2026-02-01',
                `end_date`     = '2027-02-01'
            WHERE `id` = 2 AND (`start_date` != '2026-02-01' OR `end_date` != '2027-02-01')
        ");

        // Hợp đồng #3 (khachthue3 - Phạm Minh Tuấn - P201):
        // Đặt cọc trước ngày 15/10/2025 -> Hiệu lực từ mùng 01/11/2025 -> Hết hạn 12 tháng vào đúng mùng 01/11/2026
        $pdo->exec("
            UPDATE `contracts`
            SET `deposit_date` = '2025-10-15',
                `start_date`   = '2025-11-01',
                `end_date`     = '2026-11-01',
                `renewal_status` = 'notified'
            WHERE `id` = 3 AND `renewal_status` != 'renewed' AND (`start_date` != '2025-11-01' OR `end_date` != '2026-11-01')
        ");

        // Hợp đồng #4 (khachthue4 - Võ Thị Thu Hà - P202):
        // Đặt cọc trước ngày 22/03/2026 -> Hiệu lực từ mùng 01/04/2026 -> Hết hạn 12 tháng vào đúng mùng 01/04/2027
        $pdo->exec("
            UPDATE `contracts`
            SET `deposit_date` = '2026-03-22',
                `start_date`   = '2026-04-01',
                `end_date`     = '2027-04-01'
            WHERE `id` = 4 AND (`start_date` != '2026-04-01' OR `end_date` != '2027-04-01')
        ");
        $pdo->exec("UPDATE `tenants` SET `status` = 'staying' WHERE `id` = 4");

        // Hợp đồng #5 (Đặng Quốc Khánh - P103 - Đã thanh lý):
        // Đặt cọc trước ngày 19/05/2025 -> Hiệu lực từ mùng 01/06/2025 -> Hết hạn 12 tháng vào đúng mùng 01/06/2026
        $pdo->exec("
            UPDATE `contracts`
            SET `deposit_date` = '2025-05-19',
                `start_date`   = '2025-06-01',
                `end_date`     = '2026-06-01',
                `deposit_status` = 'refunded',
                `terminated_at` = '2026-06-01',
                `termination_reason` = 'Hết hạn hợp đồng, bàn giao phòng đúng hạn và hoàn trả 100% tiền cọc'
            WHERE `id` = 5 AND (`start_date` != '2025-06-01' OR `end_date` != '2026-06-01')
        ");

        // Chuẩn hóa mọi hợp đồng khác có ngày bắt đầu hoặc ngày kết thúc chưa rơi vào mùng 01 đầu tháng
        // Nếu đặt cọc trong tháng T -> start_date là 01/(T+1), end_date là start_date + 12 tháng (cũng là ngày mùng 01)
        $pdo->exec("
            UPDATE `contracts`
            SET `start_date` = DATE_FORMAT(
                CASE
                    WHEN `deposit_date` IS NOT NULL AND `start_date` <= `deposit_date`
                    THEN DATE_ADD(`deposit_date`, INTERVAL 1 MONTH)
                    ELSE `start_date`
                END,
                '%Y-%m-01'
            )
            WHERE DAY(`start_date`) != 1 OR (`deposit_date` IS NOT NULL AND `start_date` <= `deposit_date`)
        ");

        $pdo->exec("
            UPDATE `contracts`
            SET `end_date` = DATE_FORMAT(DATE_ADD(`start_date`, INTERVAL 12 MONTH), '%Y-%m-01')
            WHERE DAY(`end_date`) != 1
        ");
    }

    /**
     * Bổ sung dữ liệu trực quan, phong phú cho cả 3 Tòa nhà (Phòng, Khách thuê, Hợp đồng điện tử/giấy, Điện nước, Hóa đơn, Phiếu thu, Đặt phòng)
     */
    private static function seedRichVisualData(PDO $pdo): void
    {
        // Kiểm tra nếu đã seed đầy đủ dữ liệu hóa đơn mở rộng (>= 35 hóa đơn) thì không chạy lại
        $invoiceCount = (int) $pdo->query("SELECT COUNT(*) FROM `invoices`")->fetchColumn();
        if ($invoiceCount >= 35) {
            return;
        }

        $hash123456 = password_hash('123456', PASSWORD_BCRYPT);

        // Chữ ký điện tử mẫu dạng SVG Data URI để hiển thị trực quan trên văn bản hợp đồng
        $managerSigSvg = 'data:image/svg+xml;utf8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="260" height="90" viewBox="0 0 260 90"><path d="M25,65 C45,20 65,75 85,35 C100,15 110,65 130,45 C150,25 165,60 190,40 C205,30 220,50 240,35" fill="none" stroke="#1d4ed8" stroke-width="3" stroke-linecap="round"/><text x="35" y="82" font-family="Segoe UI, sans-serif" font-size="12" fill="#1e40af" font-style="italic">Đã ký số: Ban Quản Lý Smart Stay</text></svg>');
        $tenantSigSvg  = 'data:image/svg+xml;utf8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="260" height="90" viewBox="0 0 260 90"><path d="M30,60 Q55,15 80,55 T130,45 T185,50 L230,35" fill="none" stroke="#0f172a" stroke-width="2.8" stroke-linecap="round"/><text x="45" y="82" font-family="Segoe UI, sans-serif" font-size="12" fill="#334155" font-style="italic">Khách thuê ký xác nhận Online</text></svg>');

        // Cập nhật chữ ký mẫu cho các hợp đồng gốc #1, #2, #3, #4
        $stmtUpdSig = $pdo->prepare("
            UPDATE `contracts`
            SET `signature_type` = 'online',
                `signature_status` = 'signed',
                `tenant_signature` = :tSig,
                `manager_signature` = :mSig,
                `signed_at` = CONCAT(`deposit_date`, ' 10:30:00')
            WHERE `id` IN (1, 2, 3) AND `signature_status` = 'pending'
        ");
        $stmtUpdSig->execute(['tSig' => $tenantSigSvg, 'mSig' => $managerSigSvg]);

        $pdo->exec("
            UPDATE `contracts`
            SET `signature_type` = 'paper',
                `signature_status` = 'signed',
                `signed_at` = '2026-03-22 15:00:00'
            WHERE `id` = 4
        ");

        // 1. Thêm thêm các phòng cho Tòa A, Tòa B, Tòa C (Tổng cộng 24 phòng)
        $pdo->exec("
            INSERT IGNORE INTO `rooms` (`building_id`, `room_type_id`, `room_number`, `floor`, `area`, `price`, `max_occupants`, `status`, `amenities`, `description`) VALUES
            (1, 3, 'P104', 1, 30, 3800000, 3, 'occupied', 'Điều hòa, Nóng lạnh, Tủ lạnh, Máy giặt, Bếp từ, Ban công', 'Căn hộ Studio tầng 1 Tòa A đầy đủ nội thất'),
            (1, 4, 'P204', 2, 38, 4500000, 4, 'occupied', 'Full nội thất VIP, Sofa, Tủ lạnh lớn, Máy giặt riêng, Ban công rộng', 'Căn hộ VIP 1 phòng ngủ tầng 2 Tòa A'),
            (1, 2, 'P303', 3, 25, 3200000, 3, 'occupied', 'Điều hòa, Nóng lạnh, Gác lửng gỗ, Kệ bếp, Khóa vân tay', 'Phòng Duplex tầng 3 Tòa A thoáng sáng'),
            (1, 3, 'P304', 3, 30, 3800000, 3, 'available', 'Điều hòa, Nóng lạnh, Tủ lạnh, Giường tủ, Bàn làm việc', 'Studio tầng 3 Tòa A view mặt phố Cầu Giấy'),
            (2, 2, 'B101', 1, 25, 3200000, 3, 'occupied', 'Điều hòa, Nóng lạnh, Gác lửng, Kệ bếp, Ban công', 'Phòng gác lửng tầng 1 Tòa B'),
            (2, 1, 'B102', 1, 20, 2700000, 2, 'occupied', 'Điều hòa, Nóng lạnh, Wifi, Khóa vân tay', 'Phòng tiêu chuẩn Tòa B thoáng mát'),
            (2, 3, 'B201', 2, 30, 3800000, 3, 'occupied', 'Điều hòa, Nóng lạnh, Tủ lạnh, Máy giặt, Giường tủ', 'Studio full đồ Tòa B'),
            (2, 4, 'B202', 2, 38, 4500000, 4, 'maintenance', 'Full nội thất cao cấp, Sofa, Bếp từ, Máy giặt riêng', 'Căn hộ VIP Tòa B đang bảo dưỡng điều hòa'),
            (2, 2, 'B203', 2, 26, 3300000, 3, 'occupied', 'Điều hòa, Nóng lạnh, Gác lửng, Tủ quần áo, Cửa sổ lớn', 'Phòng Duplex tầng 2 Tòa B'),
            (2, 3, 'B301', 3, 32, 3900000, 3, 'available', 'Điều hòa, Nóng lạnh, Tủ lạnh, Máy giặt, Ban công', 'Căn hộ Studio tầng 3 Tòa B mới sơn sửa'),
            (2, 1, 'B302', 3, 21, 2800000, 2, 'available', 'Điều hòa, Nóng lạnh, Giường, Tủ đồ, Wifi tốc độ cao', 'Phòng tiêu chuẩn tầng 3 Tòa B'),
            (3, 3, 'C101', 1, 30, 3900000, 3, 'occupied', 'Điều hòa, Nóng lạnh, Tủ lạnh, Máy giặt, Ban công', 'Căn hộ Studio Tòa C tầng 1'),
            (3, 2, 'C102', 1, 26, 3300000, 3, 'occupied', 'Điều hòa, Nóng lạnh, Gác lửng, Kệ bếp', 'Phòng Duplex Tòa C'),
            (3, 1, 'C201', 2, 22, 2800000, 2, 'occupied', 'Điều hòa, Nóng lạnh, Wifi, Khóa vân tay', 'Phòng tiêu chuẩn Tòa C'),
            (3, 4, 'C202', 2, 40, 4600000, 4, 'occupied', 'Full nội thất cao cấp, Sofa, Bếp từ, Máy giặt riêng', 'Căn hộ 1PN VIP Tòa C'),
            (3, 3, 'C203', 2, 30, 3800000, 3, 'available', 'Điều hòa, Nóng lạnh, Tủ lạnh, Giường nệm, Tủ áo', 'Căn hộ Studio tầng 2 Tòa C'),
            (3, 2, 'C301', 3, 25, 3200000, 3, 'available', 'Điều hòa, Nóng lạnh, Gác lửng, Ban công thoáng', 'Phòng Duplex tầng 3 Tòa C'),
            (3, 1, 'C302', 3, 20, 2700000, 2, 'maintenance', 'Điều hòa, Nóng lạnh, Wifi', 'Phòng tiêu chuẩn Tòa C đang thay vòi sen')
        ");

        // Lấy map room_number -> [id, price, building_id]
        $roomMap = [];
        foreach ($pdo->query("SELECT id, room_number, price, building_id FROM `rooms`") as $r) {
            $roomMap[$r['room_number']] = $r;
        }

        // 2. Tạo thêm tài khoản đăng nhập cho khách thuê mới (khachthue5..khachthue8)
        $extraUsers = [
            ['username' => 'khachthue5', 'full_name' => 'Hoàng Đức Minh',   'email' => 'minhhd@gmail.com',   'phone' => '0916112233'],
            ['username' => 'khachthue6', 'full_name' => 'Lê Ngọc Ánh',      'email' => 'anhln@gmail.com',    'phone' => '0917223344'],
            ['username' => 'khachthue7', 'full_name' => 'Bùi Quang Huy',    'email' => 'huybq@gmail.com',    'phone' => '0918334455'],
            ['username' => 'khachthue8', 'full_name' => 'Ngô Thanh Tâm',    'email' => 'tamnt@gmail.com',    'phone' => '0919445566'],
        ];
        $userMap = [];
        $stmtInsUser = $pdo->prepare("
            INSERT IGNORE INTO `users` (`username`, `password`, `full_name`, `email`, `phone`, `role`, `status`)
            VALUES (:username, :pw, :full_name, :email, :phone, 'tenant', 'active')
        ");
        foreach ($extraUsers as $u) {
            $stmtInsUser->execute([
                'username'  => $u['username'],
                'pw'        => $hash123456,
                'full_name' => $u['full_name'],
                'email'     => $u['email'],
                'phone'     => $u['phone'],
            ]);
        }
        foreach ($pdo->query("SELECT id, username FROM `users`") as $u) {
            $userMap[$u['username']] = (int) $u['id'];
        }

        // 3. Thêm danh sách khách thuê phong phú (15 khách thuê)
        $newTenants = [
            [
                'user_id' => $userMap['khachthue5'] ?? null,
                'full_name' => 'Hoàng Đức Minh', 'cccd' => '001098012345', 'phone' => '0916112233',
                'email' => 'minhhd@gmail.com', 'dob' => '1998-05-14', 'gender' => 'Nam',
                'hometown' => 'TP. Hải Phòng', 'occupation' => 'Chuyên viên Ngân hàng', 'emergency_contact' => '0904111222', 'status' => 'staying'
            ],
            [
                'user_id' => $userMap['khachthue6'] ?? null,
                'full_name' => 'Lê Ngọc Ánh', 'cccd' => '036199023456', 'phone' => '0917223344',
                'email' => 'anhln@gmail.com', 'dob' => '1999-09-20', 'gender' => 'Nữ',
                'hometown' => 'Nam Định', 'occupation' => 'Thiết kế Đồ họa (Designer)', 'emergency_contact' => '0904222333', 'status' => 'staying'
            ],
            [
                'user_id' => $userMap['khachthue7'] ?? null,
                'full_name' => 'Bùi Quang Huy', 'cccd' => '038097034567', 'phone' => '0918334455',
                'email' => 'huybq@gmail.com', 'dob' => '1997-11-08', 'gender' => 'Nam',
                'hometown' => 'Thanh Hóa', 'occupation' => 'Kỹ sư Phần mềm', 'emergency_contact' => '0904333444', 'status' => 'staying'
            ],
            [
                'user_id' => $userMap['khachthue8'] ?? null,
                'full_name' => 'Ngô Thanh Tâm', 'cccd' => '040201045678', 'phone' => '0919445566',
                'email' => 'tamnt@gmail.com', 'dob' => '2001-03-25', 'gender' => 'Nữ',
                'hometown' => 'Nghệ An', 'occupation' => 'Giáo viên Tiếng Anh', 'emergency_contact' => '0904444555', 'status' => 'staying'
            ],
            [
                'user_id' => null,
                'full_name' => 'Trương Quốc Bảo', 'cccd' => '034096056789', 'phone' => '0932556677',
                'email' => 'baotq@gmail.com', 'dob' => '1996-07-19', 'gender' => 'Nam',
                'hometown' => 'Thái Bình', 'occupation' => 'Kinh doanh Tự do', 'emergency_contact' => '0904555666', 'status' => 'staying'
            ],
            [
                'user_id' => null,
                'full_name' => 'Phan Thị Mai Hương', 'cccd' => '042200067890', 'phone' => '0933667788',
                'email' => 'huongptm@gmail.com', 'dob' => '2000-12-03', 'gender' => 'Nữ',
                'hometown' => 'Hà Tĩnh', 'occupation' => 'Dược sĩ', 'emergency_contact' => '0904666777', 'status' => 'staying'
            ],
            [
                'user_id' => null,
                'full_name' => 'Đỗ Tuấn Kiệt', 'cccd' => '030099078901', 'phone' => '0934778899',
                'email' => 'kietdt@gmail.com', 'dob' => '1999-04-11', 'gender' => 'Nam',
                'hometown' => 'Hải Dương', 'occupation' => 'Chuyên viên Digital Marketing', 'emergency_contact' => '0904777888', 'status' => 'staying'
            ],
            [
                'user_id' => null,
                'full_name' => 'Vũ Hoàng Yến', 'cccd' => '035202089012', 'phone' => '0935889900',
                'email' => 'yenvh@gmail.com', 'dob' => '2002-08-30', 'gender' => 'Nữ',
                'hometown' => 'Hà Nam', 'occupation' => 'Sinh viên ĐH Quốc Gia', 'emergency_contact' => '0904888999', 'status' => 'staying'
            ],
            [
                'user_id' => null,
                'full_name' => 'Lý Minh Khôi', 'cccd' => '019095090123', 'phone' => '0936990011',
                'email' => 'khoilm@gmail.com', 'dob' => '1995-02-17', 'gender' => 'Nam',
                'hometown' => 'Thái Nguyên', 'occupation' => 'Kiến trúc sư', 'emergency_contact' => '0904999000', 'status' => 'staying'
            ],
            [
                'user_id' => null,
                'full_name' => 'Đinh Thảo Nguyên', 'cccd' => '037201091234', 'phone' => '0937001122',
                'email' => 'nguyendt@gmail.com', 'dob' => '2001-06-15', 'gender' => 'Nữ',
                'hometown' => 'Ninh Bình', 'occupation' => 'Nhân viên Nhân sự (HR)', 'emergency_contact' => '0904000111', 'status' => 'moved_out'
            ],
        ];

        $stmtInsTenant = $pdo->prepare("
            INSERT IGNORE INTO `tenants` (`user_id`, `full_name`, `cccd`, `phone`, `email`, `dob`, `gender`, `hometown`, `occupation`, `emergency_contact`, `status`)
            VALUES (:user_id, :full_name, :cccd, :phone, :email, :dob, :gender, :hometown, :occupation, :emergency_contact, :status)
        ");
        foreach ($newTenants as $t) {
            $stmtInsTenant->execute($t);
        }

        // Map cccd -> tenant_id
        $tenantByCccd = [];
        foreach ($pdo->query("SELECT id, cccd FROM `tenants`") as $t) {
            $tenantByCccd[$t['cccd']] = (int) $t['id'];
        }

        // 4. Thêm Hợp đồng thuê phòng chuẩn nguyên tắc:
        // - deposit_date: Ngày đặt cọc trước (tháng trước)
        // - start_date: Ngày mùng 01 của tháng kế tiếp (01/MM/YYYY)
        // - end_date: Ngày mùng 01 tính tròn 12 tháng từ start_date (01/MM/YYYY)
        $contractsToSeed = [
            // Tòa A
            [
                'code' => 'HD-2026-005', 'room' => 'P104', 'cccd' => '001098012345', // Hoàng Đức Minh
                'deposit_date' => '2026-04-16', 'start_date' => '2026-05-01', 'end_date' => '2027-05-01',
                'rent' => 3800000, 'service' => 150000, 'status' => 'active', 'deposit_status' => 'held',
                'sig_type' => 'online', 'sig_status' => 'signed', 'renewal_status' => 'none',
                'notes' => 'Đặt cọc ngày 16/04/2026, hiệu lực từ 01/05/2026 đến 01/05/2027.'
            ],
            [
                'code' => 'HD-2026-006', 'room' => 'P204', 'cccd' => '036199023456', // Lê Ngọc Ánh
                'deposit_date' => '2026-05-20', 'start_date' => '2026-06-01', 'end_date' => '2027-06-01',
                'rent' => 4500000, 'service' => 200000, 'status' => 'active', 'deposit_status' => 'held',
                'sig_type' => 'online', 'sig_status' => 'signed', 'renewal_status' => 'none',
                'notes' => 'Căn hộ VIP tầng 2 Tòa A, hiệu lực từ 01/06/2026 đến 01/06/2027.'
            ],
            [
                'code' => 'HD-2026-007', 'room' => 'P303', 'cccd' => '034096056789', // Trương Quốc Bảo
                'deposit_date' => '2026-06-18', 'start_date' => '2026-07-01', 'end_date' => '2027-07-01',
                'rent' => 3200000, 'service' => 150000, 'status' => 'active', 'deposit_status' => 'held',
                'sig_type' => 'paper', 'sig_status' => 'signed', 'renewal_status' => 'none',
                'notes' => 'Hợp đồng ký giấy, hiệu lực từ 01/07/2026 đến 01/07/2027.'
            ],
            // Tòa B
            [
                'code' => 'HD-2026-008', 'room' => 'B101', 'cccd' => '038097034567', // Bùi Quang Huy (khachthue7)
                'deposit_date' => '2025-10-20', 'start_date' => '2025-11-01', 'end_date' => '2026-11-01',
                'rent' => 3200000, 'service' => 150000, 'status' => 'active', 'deposit_status' => 'held',
                'sig_type' => 'online', 'sig_status' => 'signed', 'renewal_status' => 'notified',
                'notes' => 'Hợp đồng Tòa B sắp đến hạn 01/11/2026, đã gửi thông báo gia hạn tự động.'
            ],
            [
                'code' => 'HD-2026-009', 'room' => 'B201', 'cccd' => '042200067890', // Phan Thị Mai Hương
                'deposit_date' => '2026-02-18', 'start_date' => '2026-03-01', 'end_date' => '2027-03-01',
                'rent' => 3800000, 'service' => 150000, 'status' => 'active', 'deposit_status' => 'held',
                'sig_type' => 'online', 'sig_status' => 'signed', 'renewal_status' => 'none',
                'notes' => 'Studio Tòa B, cọc ngày 18/02/2026, hiệu lực 01/03/2026 - 01/03/2027.'
            ],
            [
                'code' => 'HD-2026-010', 'room' => 'B203', 'cccd' => '030099078901', // Đỗ Tuấn Kiệt
                'deposit_date' => '2026-07-21', 'start_date' => '2026-08-01', 'end_date' => '2027-08-01',
                'rent' => 3300000, 'service' => 150000, 'status' => 'active', 'deposit_status' => 'held',
                'sig_type' => 'paper', 'sig_status' => 'pending', 'renewal_status' => 'none',
                'notes' => 'Hợp đồng ký giấy chờ tải bản scan, hiệu lực 01/08/2026 - 01/08/2027.'
            ],
            // Tòa C
            [
                'code' => 'HD-2026-011', 'room' => 'C101', 'cccd' => '040201045678', // Ngô Thanh Tâm (khachthue8)
                'deposit_date' => '2026-01-15', 'start_date' => '2026-02-01', 'end_date' => '2027-02-01',
                'rent' => 3900000, 'service' => 150000, 'status' => 'active', 'deposit_status' => 'held',
                'sig_type' => 'online', 'sig_status' => 'signed', 'renewal_status' => 'none',
                'notes' => 'Studio tầng 1 Tòa C, hiệu lực từ 01/02/2026 đến 01/02/2027.'
            ],
            [
                'code' => 'HD-2026-012', 'room' => 'C102', 'cccd' => '035202089012', // Vũ Hoàng Yến
                'deposit_date' => '2026-04-22', 'start_date' => '2026-05-01', 'end_date' => '2027-05-01',
                'rent' => 3300000, 'service' => 150000, 'status' => 'active', 'deposit_status' => 'held',
                'sig_type' => 'online', 'sig_status' => 'signed', 'renewal_status' => 'none',
                'notes' => 'Phòng Duplex Tòa C, hiệu lực từ 01/05/2026 đến 01/05/2027.'
            ],
            [
                'code' => 'HD-2026-013', 'room' => 'C201', 'cccd' => '019095090123', // Lý Minh Khôi
                'deposit_date' => '2026-08-19', 'start_date' => '2026-09-01', 'end_date' => '2027-09-01',
                'rent' => 2800000, 'service' => 150000, 'status' => 'active', 'deposit_status' => 'held',
                'sig_type' => 'online', 'sig_status' => 'pending', 'renewal_status' => 'none',
                'notes' => 'Phòng tiêu chuẩn Tòa C, hiệu lực từ 01/09/2026 đến 01/09/2027.'
            ],
            [
                'code' => 'HD-2025-014', 'room' => 'C203', 'cccd' => '037201091234', // Đinh Thảo Nguyên (Đã trả phòng trước hạn -> mất cọc)
                'deposit_date' => '2025-11-18', 'start_date' => '2025-12-01', 'end_date' => '2026-12-01',
                'rent' => 3800000, 'service' => 150000, 'status' => 'terminated', 'deposit_status' => 'forfeited',
                'sig_type' => 'paper', 'sig_status' => 'signed', 'renewal_status' => 'none',
                'notes' => 'Khách chấm dứt hợp đồng trước hạn vào ngày 15/08/2026 (trước ngày hết hạn 01/12/2026) -> Mất tiền cọc.'
            ],
        ];

        $stmtInsContract = $pdo->prepare("
            INSERT IGNORE INTO `contracts` (
                `contract_code`, `room_id`, `tenant_id`, `deposit_date`, `start_date`, `end_date`,
                `monthly_rent`, `deposit_amount`, `deposit_status`, `service_fee`,
                `status`, `terminated_at`, `termination_reason`,
                `signature_type`, `signature_status`, `tenant_signature`, `manager_signature`, `signed_at`,
                `renewal_status`, `renewal_months`, `notes`
            ) VALUES (
                :code, :room_id, :tenant_id, :deposit_date, :start_date, :end_date,
                :rent, :deposit_amount, :deposit_status, :service,
                :status, :terminated_at, :termination_reason,
                :sig_type, :sig_status, :t_sig, :m_sig, :signed_at,
                :renewal_status, 12, :notes
            )
        ");

        foreach ($contractsToSeed as $c) {
            if (empty($roomMap[$c['room']]) || empty($tenantByCccd[$c['cccd']])) {
                continue;
            }
            $roomId = (int) $roomMap[$c['room']]['id'];
            $tenantId = $tenantByCccd[$c['cccd']];
            $isSignedOnline = ($c['sig_type'] === 'online' && $c['sig_status'] === 'signed');

            $stmtInsContract->execute([
                'code'               => $c['code'],
                'room_id'            => $roomId,
                'tenant_id'          => $tenantId,
                'deposit_date'       => $c['deposit_date'],
                'start_date'         => $c['start_date'],
                'end_date'           => $c['end_date'],
                'rent'               => $c['rent'],
                'deposit_amount'     => $c['rent'],
                'deposit_status'     => $c['deposit_status'],
                'service'            => $c['service'],
                'status'             => $c['status'],
                'terminated_at'      => $c['status'] === 'terminated' ? '2026-08-15' : null,
                'termination_reason' => $c['status'] === 'terminated' ? 'Khách chuyển công tác, trả phòng trước hạn hợp đồng (mất cọc)' : null,
                'sig_type'           => $c['sig_type'],
                'sig_status'         => $c['sig_status'],
                't_sig'              => $isSignedOnline ? $tenantSigSvg : null,
                'm_sig'              => $isSignedOnline ? $managerSigSvg : null,
                'signed_at'          => $c['sig_status'] === 'signed' ? $c['deposit_date'] . ' 09:30:00' : null,
                'renewal_status'     => $c['renewal_status'],
                'notes'              => $c['notes'],
            ]);
        }

        // Đồng bộ trạng thái phòng occupied / available theo hợp đồng active
        $pdo->exec("
            UPDATE `rooms` r
            SET r.`status` = 'occupied'
            WHERE EXISTS (SELECT 1 FROM `contracts` c WHERE c.`room_id` = r.`id` AND c.`status` = 'active')
        ");

        // 5. Seed chỉ số điện nước & Hóa đơn nhiều tháng (tháng 5, 6, 7, 8, 9, 10 năm 2026) cho cả 3 tòa A, B, C
        $activeContracts = $pdo->query("
            SELECT c.id AS contract_id, c.room_id, c.tenant_id, c.monthly_rent, c.service_fee, c.start_date, r.room_number
            FROM `contracts` c
            JOIN `rooms` r ON r.id = c.room_id
            WHERE c.status = 'active'
        ")->fetchAll(PDO::FETCH_ASSOC);

        $stmtInsUtil = $pdo->prepare("
            INSERT IGNORE INTO `utilities` (
                `room_id`, `contract_id`, `month`, `year`,
                `old_electric`, `new_electric`, `electric_used`, `electric_rate`, `electric_amount`,
                `old_water`, `new_water`, `water_used`, `water_rate`, `water_amount`,
                `other_fee`, `recorded_at`
            ) VALUES (
                :room_id, :contract_id, :month, :year,
                :e_old, :e_new, :e_used, 3500, :e_amount,
                :w_old, :w_new, :w_used, 15000, :w_amount,
                :other_fee, :recorded_at
            )
        ");

        $stmtFindUtil = $pdo->prepare("SELECT id FROM `utilities` WHERE `room_id` = :room_id AND `month` = :month AND `year` = :year LIMIT 1");

        $stmtInsInv = $pdo->prepare("
            INSERT IGNORE INTO `invoices` (
                `invoice_code`, `invoice_type`, `contract_id`, `room_id`, `tenant_id`, `utility_id`,
                `month`, `year`, `room_fee`, `electric_fee`, `water_fee`, `service_fee`,
                `deposit_fee`, `deposit_refund`, `discount`, `total_amount`, `paid_amount`,
                `status`, `due_date`, `notes`
            ) VALUES (
                :code, 'monthly', :contract_id, :room_id, :tenant_id, :utility_id,
                :month, :year, :room_fee, :e_fee, :w_fee, :service_fee,
                0, 0, 0, :total, :paid,
                :status, :due_date, :notes
            )
        ");

        $stmtInsPay = $pdo->prepare("
            INSERT IGNORE INTO `payments` (
                `payment_code`, `invoice_id`, `amount`, `payment_method`,
                `transaction_ref`, `payment_date`, `received_by`, `notes`
            ) VALUES (
                :payment_code, :invoice_id, :amount, :method,
                :tx_ref, :pdate, 1, :notes
            )
        ");

        foreach ($activeContracts as $idx => $ac) {
            $roomId = (int) $ac['room_id'];
            $contractId = (int) $ac['contract_id'];
            $tenantId = (int) $ac['tenant_id'];
            $rent = (float) $ac['monthly_rent'];
            $service = (float) $ac['service_fee'];
            $roomNum = $ac['room_number'];

            $eBase = 100 + ($roomId * 25);
            $wBase = 20 + ($roomId * 5);

            for ($m = 5; $m <= 10; $m++) {
                $eUsed = 48 + (($roomId * 7 + $m * 5) % 45);
                $wUsed = 4 + (($roomId + $m) % 5);
                $eOld = $eBase;
                $eNew = $eOld + $eUsed;
                $wOld = $wBase;
                $wNew = $wOld + $wUsed;
                $eBase = $eNew;
                $wBase = $wNew;

                $eFee = $eUsed * 3500;
                $wFee = $wUsed * 15000;

                $stmtFindUtil->execute(['room_id' => $roomId, 'month' => $m, 'year' => 2026]);
                $utilId = (int) $stmtFindUtil->fetchColumn();
                if ($utilId === 0) {
                    $stmtInsUtil->execute([
                        'room_id'     => $roomId,
                        'contract_id' => $contractId,
                        'month'       => $m,
                        'year'        => 2026,
                        'e_old'       => $eOld,
                        'e_new'       => $eNew,
                        'e_used'      => $eUsed,
                        'e_amount'    => $eFee,
                        'w_old'       => $wOld,
                        'w_new'       => $wNew,
                        'w_used'      => $wUsed,
                        'w_amount'    => $wFee,
                        'other_fee'   => $service,
                        'recorded_at' => sprintf('2026-%02d-01', $m),
                    ]);
                    $utilId = (int) $pdo->lastInsertId();
                }

                // Kiểm tra nếu phòng đã có hóa đơn tháng này rồi thì bỏ qua
                $stmtChkInv = $pdo->prepare("SELECT id FROM `invoices` WHERE `room_id` = :rid AND `month` = :m AND `year` = 2026 LIMIT 1");
                $stmtChkInv->execute(['rid' => $roomId, 'm' => $m]);
                if ($stmtChkInv->fetchColumn()) {
                    continue;
                }

                $total = $rent + $eFee + $wFee + $service;

                // Các tháng 5..9: đã thu đủ (trừ 1 vài hóa đơn tháng 9 quá hạn nhẹ để bảng công nợ trực quan)
                // Tháng 10 (tháng hiện tại): đa dạng trạng thái paid, partial, unpaid, overdue
                if ($m <= 8) {
                    $status = 'paid';
                    $paid = $total;
                } elseif ($m === 9) {
                    if ($roomId % 5 === 0) {
                        $status = 'overdue';
                        $paid = 0;
                    } else {
                        $status = 'paid';
                        $paid = $total;
                    }
                } else {
                    // Tháng 10
                    $mod = $roomId % 4;
                    if ($mod === 0) {
                        $status = 'paid';
                        $paid = $total;
                    } elseif ($mod === 1) {
                        $status = 'partial';
                        $paid = round($total * 0.5, -4);
                    } elseif ($mod === 2) {
                        $status = 'unpaid';
                        $paid = 0;
                    } else {
                        $status = 'overdue';
                        $paid = 0;
                    }
                }

                $invCode = sprintf('INV-2026%02d-%s', $m, $roomNum);
                $dueDate = sprintf('2026-%02d-05', $m);

                $stmtInsInv->execute([
                    'code'        => $invCode,
                    'contract_id' => $contractId,
                    'room_id'     => $roomId,
                    'tenant_id'   => $tenantId,
                    'utility_id'  => $utilId ?: null,
                    'month'       => $m,
                    'year'        => 2026,
                    'room_fee'    => $rent,
                    'e_fee'       => $eFee,
                    'w_fee'       => $wFee,
                    'service_fee' => $service,
                    'total'       => $total,
                    'paid'        => $paid,
                    'status'      => $status,
                    'due_date'    => $dueDate,
                    'notes'       => "Hóa đơn tiền phòng & điện nước tháng {$m}/2026 - Phòng {$roomNum}",
                ]);

                $newInvId = (int) $pdo->lastInsertId();
                if ($newInvId > 0 && $paid > 0) {
                    $method = ($roomId + $m) % 2 === 0 ? 'bank_transfer' : 'cash';
                    $txRef  = $method === 'bank_transfer' ? sprintf('VCB2026%02d%03d', $m, $newInvId) : null;
                    $pCode  = sprintf('PAY-2026%02d-%03d', $m, $newInvId);
                    $pDate  = sprintf('2026-%02d-04 14:%02d:00', $m, ($roomId * 3) % 59);
                    $stmtInsPay->execute([
                        'payment_code' => $pCode,
                        'invoice_id'   => $newInvId,
                        'amount'       => $paid,
                        'method'       => $method,
                        'tx_ref'       => $txRef,
                        'pdate'        => $pDate,
                        'notes'        => $status === 'paid' ? "Thanh toán đủ hóa đơn {$invCode}" : "Thanh toán đợt 1 hóa đơn {$invCode}",
                    ]);
                }
            }
        }

        // 6. Seed thêm danh sách Đặt trước phòng 360° (cả chờ liên hệ & lịch sử đã xử lý)
        $bookingCount = (int) $pdo->query("SELECT COUNT(*) FROM `booking_requests`")->fetchColumn();
        if ($bookingCount < 6) {
            $bookings = [
                ['room' => 'P103', 'name' => 'Nguyễn Tuấn Anh',    'phone' => '0988112233', 'date' => '2026-11-01', 'notes' => 'Muốn đặt cọc trước giữ phòng P103 vào ở từ mùng 01/11/2026.', 'status' => 'pending'],
                ['room' => 'B301', 'name' => 'Trần Ngọc Bảo Trân', 'phone' => '0977223344', 'date' => '2026-11-01', 'notes' => 'Đã xem ảnh 360 độ căn Studio B301, hẹn chiều mai qua ký hợp đồng.', 'status' => 'pending'],
                ['room' => 'C203', 'name' => 'Lê Minh Hoàng',      'phone' => '0966334455', 'date' => '2026-11-01', 'notes' => 'Mình muốn thuê 12 tháng, có thể chuyển khoản cọc trước không?', 'status' => 'pending'],
                ['room' => 'P203', 'name' => 'Phạm Thu Trang',     'phone' => '0944556677', 'date' => '2026-11-01', 'notes' => 'Ở 2 người, có 1 xe máy điện cần chỗ sạc dưới hầm.', 'status' => 'pending'],
                ['room' => 'P304', 'name' => 'Vũ Đình Trọng',      'phone' => '0912667788', 'date' => '2026-10-01', 'notes' => 'Đã gọi điện tư vấn và hẹn lịch xem phòng trực tiếp.', 'status' => 'contacted'],
                ['room' => 'B302', 'name' => 'Đặng Thị Mai',       'phone' => '0938778899', 'date' => '2026-10-01', 'notes' => 'Đã liên hệ, khách hẹn cuối tuần qua đặt cọc.', 'status' => 'contacted'],
                ['room' => 'C301', 'name' => 'Hoàng Gia Bảo',      'phone' => '0909889900', 'date' => '2026-10-01', 'notes' => 'Khách đổi lịch công tác nên báo hủy lịch xem phòng.', 'status' => 'cancelled'],
            ];
            $stmtInsBooking = $pdo->prepare("
                INSERT INTO `booking_requests` (`room_number`, `full_name`, `phone`, `move_in_date`, `notes`, `status`)
                VALUES (:room_number, :name, :phone, :date, :notes, :status)
            ");
            foreach ($bookings as $b) {
                $stmtInsBooking->execute([
                    'room_number' => $b['room'],
                    'name'        => $b['name'],
                    'phone'       => $b['phone'],
                    'date'        => $b['date'],
                    'notes'       => $b['notes'],
                    'status'      => $b['status'],
                ]);
            }
        }
    }

    /**
     * Tự động gửi thông báo gia hạn trước khi hết hợp đồng 1 tháng (30 ngày)
     * và tự động chuyển thành 'auto_no_renew' (Không gia hạn) nếu hết hạn mà khách không trả lời
     */
    public static function syncContractRenewalNotifications(PDO $pdo): void
    {
        // Hợp đồng còn <= 30 ngày và >= 0 ngày mà chưa gửi thông báo -> Tự động chuyển sang 'notified'
        $pdo->exec("
            UPDATE `contracts`
            SET `renewal_status` = 'notified'
            WHERE `status` = 'active'
              AND `renewal_status` = 'none'
              AND DATEDIFF(`end_date`, CURDATE()) BETWEEN 0 AND 30
        ");

        // Hợp đồng đã quá hạn (DATEDIFF < 0) mà khách vẫn không trả lời ('none' hoặc 'notified') -> Tự động hiểu là không gia hạn ('auto_no_renew')
        $pdo->exec("
            UPDATE `contracts`
            SET `renewal_status` = 'auto_no_renew'
            WHERE `status` = 'active'
              AND `renewal_status` IN ('none', 'notified')
              AND DATEDIFF(`end_date`, CURDATE()) < 0
        ");
    }
}
