<?php

namespace App\Helpers;

class CoreHelper
{
    private static bool $envLoaded = false;
    private static array $envCache = [];

    /**
     * Load file .env hoặc .env.example vào biến môi trường
     */
    public static function loadEnv(?string $basePath = null): void
    {
        if (self::$envLoaded) {
            return;
        }

        $root = $basePath ?? dirname(__DIR__, 2);
        $envFile = $root . DIRECTORY_SEPARATOR . '.env';
        if (!file_exists($envFile)) {
            $envFile = $root . DIRECTORY_SEPARATOR . '.env.example';
        }

        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                if (str_contains($line, '=')) {
                    [$key, $val] = explode('=', $line, 2);
                    $key = trim($key);
                    $val = trim($val);
                    $val = trim($val, "\"'");
                    self::$envCache[$key] = $val;
                    $_ENV[$key] = $val;
                }
            }
        }

        self::$envLoaded = true;
    }

    /**
     * Lấy giá trị cấu hình từ .env
     */
    public static function env(string $key, mixed $default = null): mixed
    {
        self::loadEnv();
        if (array_key_exists($key, self::$envCache)) {
            return self::$envCache[$key];
        }
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        $val = getenv($key);
        return $val !== false ? $val : $default;
    }

    /**
     * Tự động phát hiện Base Path trên WAMP (ví dụ: /quanly-nhatro hoặc /quanly-nhatro/public)
     */
    public static function basePath(): string
    {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $dir = rtrim(dirname($scriptName), '/');
        if ($dir === '.' || $dir === '/') {
            return '';
        }
        return $dir;
    }

    /**
     * Tạo URL cho route trong ứng dụng
     */
    public static function url(string $path = '', array $query = []): string
    {
        $base = self::basePath();
        $cleanPath = '/' . ltrim($path, '/');
        if ($cleanPath === '/') {
            $url = $base !== '' ? $base . '/' : '/';
        } else {
            $url = $base . $cleanPath;
        }

        if (!empty($query)) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . http_build_query($query);
        }

        return $url;
    }

    /**
     * Tạo URL cho file tĩnh (css, js, images) trong thư mục public
     */
    public static function asset(string $path): string
    {
        $base = self::basePath();
        $cleanPath = ltrim($path, '/');

        // Nếu đang truy cập từ thư mục gốc quanly-nhatro (chưa có /public trên URL)
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        if (!str_ends_with(dirname($scriptName), '/public') && file_exists(dirname(__DIR__, 2) . '/public/' . $cleanPath)) {
            return ($base !== '' ? $base : '') . '/public/' . $cleanPath;
        }

        return ($base !== '' ? $base : '') . '/' . $cleanPath;
    }

    /**
     * Chuyển hướng trang
     */
    public static function redirect(string $path, array $query = []): never
    {
        $target = str_starts_with($path, 'http') ? $path : self::url($path, $query);
        header("Location: {$target}");
        exit;
    }

    /**
     * Render View cùng với Layout
     */
    public static function view(string $viewName, array $data = [], bool $useLayout = true): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = dirname(__DIR__) . '/Views/' . ltrim($viewName, '/') . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo "Không tìm thấy giao diện: " . htmlspecialchars($viewName);
            return;
        }

        if ($useLayout) {
            require dirname(__DIR__) . '/Views/layouts/header.php';
            require $viewFile;
            require dirname(__DIR__) . '/Views/layouts/footer.php';
        } else {
            require $viewFile;
        }
    }

    /**
     * Trả về JSON response
     */
    public static function json(mixed $data, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Định dạng tiền tệ VNĐ
     */
    public static function formatCurrency(float|int|string|null $amount, bool $withSymbol = true): string
    {
        $num = (float) ($amount ?? 0);
        $formatted = number_format($num, 0, ',', '.');
        return $withSymbol ? $formatted . ' đ' : $formatted;
    }

    /**
     * Định dạng ngày tháng Việt Nam (dd/mm/yyyy)
     */
    public static function formatDate(?string $date, string $format = 'd/m/Y'): string
    {
        if (empty($date) || $date === '0000-00-00') {
            return '-';
        }
        $timestamp = strtotime($date);
        return $timestamp ? date($format, $timestamp) : $date;
    }

    /**
     * Escape HTML chống XSS
     */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Quản lý Flash Message qua Session
     */
    public static function setFlash(string $type, string $message): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash_messages'][] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    public static function getFlashes(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $flashes = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        return $flashes;
    }

    /**
     * CSRF Token bảo vệ form POST
     */
    public static function csrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function csrfField(): string
    {
        $token = self::csrfToken();
        return '<input type="hidden" name="_csrf_token" value="' . self::e($token) . '">';
    }

    public static function verifyCsrf(?string $token = null): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $submitted = $token ?? ($_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $stored = $_SESSION['csrf_token'] ?? '';
        return !empty($stored) && !empty($submitted) && hash_equals($stored, $submitted);
    }

    /**
     * Thông tin người dùng đang đăng nhập
     */
    public static function user(): ?array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user'] ?? null;
    }

    public static function isLoggedIn(): bool
    {
        return self::user() !== null;
    }

    public static function hasRole(string|array $roles): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }
        $roles = (array) $roles;
        return in_array($user['role'] ?? '', $roles, true);
    }
}
