# Hệ thống Quản lý Nhà trọ (PHP MVC - WAMP Server)

Ứng dụng web quản lý phòng trọ, khách thuê, hợp đồng, chốt chỉ số điện nước, phát hành hóa đơn và theo dõi công nợ được xây dựng theo kiến trúc **MVC thuần PHP (PDO, PSR-4)** tối ưu cho **WAMP Server**.

---

## 1. Cấu trúc thư mục

```text
quanly-nhatro/
├── app/
│   ├── Config/
│   │   ├── Database.php
│   │   └── routes.php
│   ├── Helpers/
│   │   ├── CoreHelper.php
│   │   └── Validator.php
│   ├── Middleware/
│   │   ├── AuthMiddleware.php
│   │   └── RoleMiddleware.php
│   ├── Models/
│   │   ├── UserModel.php
│   │   ├── RoomModel.php
│   │   ├── TenantModel.php
│   │   ├── ContractModel.php
│   │   ├── UtilityModel.php
│   │   ├── InvoiceModel.php
│   │   ├── PaymentModel.php
│   │   └── ReportModel.php
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── RoomController.php
│   │   ├── TenantController.php
│   │   ├── ContractController.php
│   │   ├── UtilityController.php
│   │   ├── InvoiceController.php
│   │   ├── PaymentController.php
│   │   ├── ReportController.php
│   │   └── ApiController.php
│   └── Views/
│       ├── layouts/ (header.php, footer.php)
│       ├── auth/ (login.php)
│       ├── dashboard/ (index.php)
│       ├── rooms/ (index.php)
│       ├── tenants/ (index.php)
│       ├── contracts/ (index.php)
│       ├── utilities/ (index.php)
│       ├── invoices/ (create.php, my_list.php, detail.php)
│       ├── payments/ (index.php)
│       └── reports/ (index.php)
├── public/
│   ├── css/style.css
│   ├── js/main.js
│   └── index.php
├── tests/
│   ├── ContractTest.php
│   ├── UtilityTest.php
│   └── InvoiceTest.php
├── database.sql
├── .env.example
├── .gitignore
├── composer.json
└── README.md
```

---

## 2. Hướng dẫn cài đặt trên WAMP Server

### Bước 1: Đặt thư mục vào `www` của WAMP
Sao chép (hoặc tạo liên kết) thư mục `quanly-nhatro` vào thư mục gốc của WAMP:
- `C:\wamp64\www\quanly-nhatro` (hoặc `C:\wamp\www\quanly-nhatro`)

### Bước 2: Khởi tạo Cơ sở dữ liệu (`quanly_nhatro`)
Bạn có **2 cách**:
- **Cách 1 (Tự động 100%)**: Chỉ cần bật WAMP (biểu tượng màu xanh lá) và truy cập `http://localhost/quanly-nhatro`. Nếu database `quanly_nhatro` chưa tồn tại, `Database.php` sẽ **tự động tạo database và nạp toàn bộ dữ liệu mẫu từ `database.sql`**.
- **Cách 2 (Thủ công qua phpMyAdmin)**:
  1. Mở `http://localhost/phpmyadmin` (User: `root`, Password để trống).
  2. Chọn tab **Import** -> Chọn file `database.sql` -> Nhấn **Import / Go**.

### Bước 3: Cấu hình `.env` (Mặc định đã cấu hình sẵn cho WAMP)
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=quanly_nhatro
DB_USERNAME=root
DB_PASSWORD=
```

### Bước 4: Truy cập ứng dụng
Mở trình duyệt và truy cập:
- `http://localhost/quanly-nhatro` (hoặc `http://localhost/quanly-nhatro/public`)

---

## 3. Tài khoản đăng nhập mẫu

| Vai trò | Tên đăng nhập | Mật khẩu | Quyền hạn |
| :--- | :--- | :--- | :--- |
| **Chủ trọ (Admin)** | `admin` | `123456` | Toàn quyền quản lý phòng, khách, hợp đồng, điện nước, hóa đơn, báo cáo, xóa dữ liệu |
| **Nhân viên QL** | `nhanvien` | `123456` | Quản lý vận hành, ghi điện nước, lập hóa đơn, thu tiền |
| **Khách thuê P101** | `khachthue1` | `123456` | Xem thông tin phòng đang thuê và tra cứu hóa đơn cá nhân (`invoices/my-list`) |
| **Khách thuê P102** | `khachthue2` | `123456` | Xem thông tin phòng đang thuê và tra cứu hóa đơn cá nhân |

---

## 4. Chạy Unit Tests trên WAMP

Bạn có thể chạy trực tiếp bằng PHP của WAMP mà không bắt buộc phải cài Composer:

```powershell
& "C:\wamp64\bin\php\php8.2.29\php.exe" tests/ContractTest.php
& "C:\wamp64\bin\php\php8.2.29\php.exe" tests/UtilityTest.php
& "C:\wamp64\bin\php\php8.2.29\php.exe" tests/InvoiceTest.php
```
