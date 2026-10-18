<?php
/**
 * File cấu hình và kết nối cơ sở dữ liệu MySQL bằng PHP PDO
 * Dự án: Website Đặt Sân Bóng Đá BongDa16h
 * Tương thích: Cả môi trường Localhost (XAMPP) và Hosting (InfinityFree)
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

// Tự động nhận diện môi trường: Localhost (XAMPP) hay Online (Hosting InfinityFree)
$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']) || (php_sapi_name() === 'cli');

// Nạp file cấu hình bảo mật riêng nếu có (file database.local.php nằm trong .gitignore, không bị đẩy lên Git)
$localConfigFile = __DIR__ . '/database.local.php';
if (file_exists($localConfigFile)) {
    require_once $localConfigFile;
}

// ==============================================================================
// 1. CẤU HÌNH THÔNG SỐ KẾT NỐI DATABASE
// ==============================================================================
if ($isLocal) {
    // --- CẤU HÌNH KHI CHẠY TRÊN XAMPP (MÁY TÍNH CỦA BẠN) ---
    if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
    if (!defined('DB_PORT')) define('DB_PORT', '3306');
    if (!defined('DB_NAME')) define('DB_NAME', 'bongda16h');
    if (!defined('DB_USER')) define('DB_USER', 'root');
    if (!defined('DB_PASS')) define('DB_PASS', '');
    if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

    if (!defined('BASE_URL')) {
        define('BASE_URL', '/bongda16h');
    }
} else {
    // --- CẤU HÌNH KHI UPLOAD LÊN HOSTING INFINITYFREE ---
    // Điền chính xác các thông số trong mục "MySQL Details" của InfinityFree:
    if (!defined('DB_HOST')) define('DB_HOST', 'sql302.infinityfree.com');        // MySQL Host của bạn trên InfinityFree
    if (!defined('DB_PORT')) define('DB_PORT', '3306');
    if (!defined('DB_NAME')) define('DB_NAME', 'tên_database_trên_hosting');     // VD: if0_38000000_bongda16h
    if (!defined('DB_USER')) define('DB_USER', 'tên_user_trên_hosting');         // VD: if0_38000000
    if (!defined('DB_PASS')) define('DB_PASS', 'mật_khẩu_hosting_của_bạn');     // Mật khẩu tài khoản hosting (vPanel)
    if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

    if (!defined('BASE_URL')) {
        // Trên hosting, nếu source code đặt thẳng trong thư mục htdocs thì để trống ''
        define('BASE_URL', '');
    }
}

/**
 * Khởi tạo và trả về kết nối PDO (Singleton)
 * @return PDO
 * @throws PDOException
 */
function getDBConnection() {
    static $pdoInstance = null;

    if ($pdoInstance === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => true, // Kích hoạt chuẩn bị giả lập để tương thích tốt nhất trên hosting InfinityFree
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $pdoInstance = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            die(
                '<div style="font-family:sans-serif;padding:30px;max-width:600px;margin:50px auto;border:1px solid #dc3545;border-radius:8px;background:#fff5f5;color:#721c24;">' .
                '<h2 style="margin-top:0;">Lỗi kết nối cơ sở dữ liệu!</h2>' .
                '<p>Không thể kết nối đến cơ sở dữ liệu <strong>' . htmlspecialchars(DB_NAME) . '</strong> trên máy chủ <strong>' . htmlspecialchars(DB_HOST) . '</strong>.</p>' .
                '<p>Vui lòng kiểm tra lại các thông tin trong file <code>config/database.php</code>:</p>' .
                '<ul>' .
                '<li><strong>Host:</strong> ' . htmlspecialchars(DB_HOST) . '</li>' .
                '<li><strong>Database:</strong> ' . htmlspecialchars(DB_NAME) . '</li>' .
                '<li><strong>User:</strong> ' . htmlspecialchars(DB_USER) . '</li>' .
                '<li>Đã tạo Database và User trên InfinityFree chưa?</li>' .
                '</ul>' .
                '<p style="font-size:13px;color:#6c757d;">Chi tiết kỹ thuật: ' . htmlspecialchars($e->getMessage()) . '</p>' .
                '</div>'
            );
        }
    }

    return $pdoInstance;
}

// Khởi tạo sẵn biến $pdo để tương thích nếu có code gọi biến $pdo trực tiếp
try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    // getDBConnection đã xử lý thông báo lỗi
}

/**
 * Kiểm tra tình trạng kết nối DB
 * @return array
 */
function testDBConnection() {
    try {
        $db = getDBConnection();
        $version = $db->query('SELECT VERSION()')->fetchColumn();
        return [
            'success' => true,
            'message' => 'Kết nối thành công đến database ' . DB_NAME,
            'version' => $version
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Lỗi kết nối: ' . $e->getMessage(),
            'version' => null
        ];
    }
}
