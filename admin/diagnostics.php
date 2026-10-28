<?php
/**
 * Công cụ Chẩn Đoán & Sửa Lỗi Hệ Thống Trực Tuyến
 * Dự án: BongDa16h - Dành riêng cho môi trường Hosting InfinityFree & Localhost
 */
$pageTitle = 'Chẩn Đoán & Kiểm Tra Hệ Thống';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Bảo mật: Cho phép truy cập nếu đã đăng nhập Admin HOẶC sử dụng Access Key bí mật
$accessKey = $_GET['key'] ?? ($_POST['key'] ?? '');
$isAuthorized = isAdmin() || ($accessKey === 'bongda16h_fix_2026');

if (!$isAuthorized) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_key'])) {
        if ($_POST['admin_pin'] === 'bongda16h_fix_2026') {
            header('Location: ' . BASE_URL . '/admin/diagnostics.php?key=bongda16h_fix_2026');
            exit;
        } else {
            $keyError = 'Mã bảo vệ không chính xác!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chẩn Đoán & Sửa Lỗi Hosting - BongDa16h</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -2px rgba(0,0,0,0.05); background: white; margin-bottom: 24px; }
        .card-header-custom { background: transparent; border-bottom: 1px solid #f1f5f9; padding: 16px 20px; font-weight: 700; }
        .badge-status { font-size: 0.85rem; padding: 6px 12px; border-radius: 20px; }
    </style>
</head>
<body>

<div class="container py-4" style="max-width: 900px;">

<?php if (!$isAuthorized): ?>
    <div class="card card-custom p-4 p-md-5 mx-auto" style="max-width: 500px; margin-top: 60px;">
        <div class="text-center mb-4">
            <div class="d-inline-flex p-3 rounded-circle bg-primary bg-opacity-10 text-primary mb-3">
                <i class="bi bi-shield-lock-fill fs-1"></i>
            </div>
            <h4 class="fw-bold">Xác Thực Quản Trị Hệ Thống</h4>
            <p class="text-muted small">Vui lòng đăng nhập Admin hoặc nhập Mã Chẩn Đoán để tiếp tục kiểm tra hosting</p>
        </div>

        <?php if (!empty($keyError)): ?>
            <div class="alert alert-danger py-2 small"><?php echo e($keyError); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold small">Mã Khóa Chẩn Đoán (Maintenance Key):</label>
                <input type="password" name="admin_pin" class="form-control" placeholder="Nhập: bongda16h_fix_2026" required autofocus>
                <div class="form-text">Mặc định được thiết lập an toàn: <code>bongda16h_fix_2026</code></div>
            </div>
            <button type="submit" name="submit_key" class="btn btn-primary w-100 py-2 fw-semibold">
                <i class="bi bi-unlock-fill me-1"></i> Mở Khóa Kiểm Tra
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="<?php echo BASE_URL; ?>/pages/dang-nhap.php" class="small text-decoration-none text-muted">
                <i class="bi bi-arrow-left"></i> Quay lại trang Đăng Nhập
            </a>
        </div>
    </div>
    </body></html>
    <?php exit; ?>
<?php endif; ?>

<?php
// ==============================================================================
// THỰC HIỆN KIỂM TRA HỆ THỐNG
// ==============================================================================

// 1. Kiểm tra PHP
$phpVersion = phpversion();
$isPhp8 = version_compare($phpVersion, '8.0.0', '>=');
$extensions = [
    'pdo' => extension_loaded('pdo'),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'session' => extension_loaded('session'),
    'mbstring' => extension_loaded('mbstring'),
    'fileinfo' => extension_loaded('fileinfo'),
    'gd' => extension_loaded('gd')
];

// 2. Thử kết nối Database
$dbConnected = false;
$dbError = null;
$existingTables = [];
$missingTables = [];
$requiredTables = [
    'vai_tro' => 'Phân quyền người dùng (Admin, Nhân viên, Khách)',
    'nguoi_dung' => 'Tài khoản người dùng & thông tin liên hệ',
    'cum_san' => 'Thông tin cụm sân bóng',
    'loai_san' => 'Phân loại sân (Sân 5, Sân 7)',
    'san_bong' => 'Danh sách từng sân bóng cụ thể',
    'hinh_anh_san' => 'Bộ sưu tập hình ảnh sân bóng',
    'dich_vu' => 'Danh sách dịch vụ bổ sung',
    'dat_san' => 'Dữ liệu đơn đặt sân bóng',
    'chi_tiet_dich_vu' => 'Chi tiết dịch vụ trong đơn đặt',
    'thanh_toan' => 'Dữ liệu thanh toán và doanh thu',
    'tim_doi_bong' => 'Tin đăng tìm đối giao lưu bóng đá'
];

try {
    $pdo = getDBConnection();
    $dbConnected = true;
    $rawTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $existingTables = array_map('strtolower', $rawTables);

    foreach (array_keys($requiredTables) as $reqTbl) {
        if (!in_array(strtolower($reqTbl), $existingTables)) {
            $missingTables[] = $reqTbl;
        }
    }
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}

// 3. Kiểm tra quyền thư mục upload
$uploadRootDir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'uploads';
$uploadSanBongDir = $uploadRootDir . DIRECTORY_SEPARATOR . 'san_bong';
$isUploadDirExists = is_dir($uploadRootDir);
$isUploadDirWritable = $isUploadDirExists && is_writable($uploadRootDir);
$isSanBongDirExists = is_dir($uploadSanBongDir);
$isSanBongDirWritable = $isSanBongDirExists && is_writable($uploadSanBongDir);

// 4. Xử lý Thao tác Tự Động Sửa Chữa (Auto-Fix)
$actionResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $act = $_POST['action'];

    if ($act === 'create_upload_dirs') {
        if (!is_dir($uploadRootDir)) @mkdir($uploadRootDir, 0777, true);
        if (!is_dir($uploadSanBongDir)) @mkdir($uploadSanBongDir, 0777, true);
        $actionResult = [
            'type' => 'success',
            'msg' => 'Đã khởi tạo thư mục uploads và uploads/san_bong thành công!'
        ];
        // Reload trạng thái
        $isUploadDirExists = is_dir($uploadRootDir);
        $isUploadDirWritable = $isUploadDirExists && is_writable($uploadRootDir);
        $isSanBongDirExists = is_dir($uploadSanBongDir);
        $isSanBongDirWritable = $isSanBongDirExists && is_writable($uploadSanBongDir);
    } elseif ($act === 'sync_missing_tables' && $dbConnected) {
        $created = [];
        $errors = [];

        // Tạo bảng vai_tro
        if (in_array('vai_tro', $missingTables)) {
            try {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `vai_tro` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `ten_vai_tro` varchar(50) NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                    INSERT IGNORE INTO `vai_tro` (`id`, `ten_vai_tro`) VALUES (1,'Admin'),(2,'Nhân viên'),(3,'Khách hàng');
                ");
                $created[] = 'vai_tro';
            } catch (Throwable $e) { $errors[] = "vai_tro: " . $e->getMessage(); }
        }

        // Tạo bảng nguoi_dung
        if (in_array('nguoi_dung', $missingTables)) {
            try {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `nguoi_dung` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `vai_tro_id` int(11) NOT NULL DEFAULT 3,
                      `ho_ten` varchar(100) NOT NULL,
                      `email` varchar(100) DEFAULT NULL,
                      `so_dien_thoai` varchar(15) NOT NULL,
                      `mat_khau` varchar(255) NOT NULL,
                      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
                $created[] = 'nguoi_dung';
            } catch (Throwable $e) { $errors[] = "nguoi_dung: " . $e->getMessage(); }
        }

        // Tạo bảng thanh_toan
        if (in_array('thanh_toan', $missingTables)) {
            try {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `thanh_toan` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `dat_san_id` int(11) NOT NULL,
                      `phuong_thuc_thanh_toan` enum('Tiền mặt','Chuyển khoản','Momo','VNPay') NOT NULL DEFAULT 'Tiền mặt',
                      `so_tien` decimal(10,2) NOT NULL DEFAULT 0.00,
                      `trang_thai_thanh_toan` enum('Chưa thanh toán','Đã đặt cọc','Đã thanh toán đủ') NOT NULL DEFAULT 'Chưa thanh toán',
                      `ngay_thanh_toan` timestamp NOT NULL DEFAULT current_timestamp(),
                      PRIMARY KEY (`id`),
                      UNIQUE KEY `dat_san_id` (`dat_san_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
                $created[] = 'thanh_toan';
            } catch (Throwable $e) { $errors[] = "thanh_toan: " . $e->getMessage(); }
        }

        // Tạo bảng tim_doi_bong
        if (in_array('tim_doi_bong', $missingTables)) {
            try {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `tim_doi_bong` (
                      `id` INT(11) NOT NULL AUTO_INCREMENT,
                      `nguoi_dung_id` INT(11) DEFAULT NULL,
                      `cum_san_id` INT(11) DEFAULT NULL,
                      `san_bong_id` INT(11) DEFAULT NULL,
                      `ten_doi` VARCHAR(100) NOT NULL,
                      `anh_dai_dien` VARCHAR(255) DEFAULT NULL,
                      `loai_san` ENUM('Sân 5 người', 'Sân 7 người', 'Sân 11 người') NOT NULL DEFAULT 'Sân 7 người',
                      `khu_vuc` VARCHAR(255) NOT NULL,
                      `kinh_do` DECIMAL(10,8) DEFAULT NULL,
                      `vi_do` DECIMAL(10,8) DEFAULT NULL,
                      `ngay_da` DATE NOT NULL,
                      `gio_da` TIME NOT NULL,
                      `thoi_luong` INT(11) NOT NULL DEFAULT 90,
                      `trinh_do` ENUM('Giao lưu', 'Phong trào', 'Trung bình', 'Khá') NOT NULL DEFAULT 'Phong trào',
                      `mo_ta` TEXT DEFAULT NULL,
                      `nguoi_lien_he` VARCHAR(100) NOT NULL,
                      `so_dien_thoai` VARCHAR(15) NOT NULL,
                      `zalo` VARCHAR(50) DEFAULT NULL,
                      `trang_thai` ENUM('Đang tìm đối', 'Đã tìm được đối', 'Đã hủy') NOT NULL DEFAULT 'Đang tìm đối',
                      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
                $created[] = 'tim_doi_bong';
            } catch (Throwable $e) { $errors[] = "tim_doi_bong: " . $e->getMessage(); }
        }

        // Tạo bảng dich_vu nếu thiếu
        if (in_array('dich_vu', $missingTables)) {
            try {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `dich_vu` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `ten_dich_vu` varchar(100) NOT NULL,
                      `don_gia` decimal(10,2) DEFAULT NULL,
                      `don_vi_tinh` varchar(20) DEFAULT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                    INSERT IGNORE INTO `dich_vu` (`id`, `ten_dich_vu`, `don_gia`, `don_vi_tinh`) VALUES 
                    (1,'Nước suối Aquafina',10000.00,'Chai'),
                    (2,'Sting / Revive',15000.00,'Chai'),
                    (3,'Thuê áo mượn đội',30000.00,'Bộ');
                ");
                $created[] = 'dich_vu';
            } catch (Throwable $e) { $errors[] = "dich_vu: " . $e->getMessage(); }
        }

        // Tạo bảng chi_tiet_dich_vu nếu thiếu
        if (in_array('chi_tiet_dich_vu', $missingTables)) {
            try {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `chi_tiet_dich_vu` (
                      `id` INT(11) NOT NULL AUTO_INCREMENT,
                      `dat_san_id` INT(11) NOT NULL COMMENT 'Mã đơn đặt sân (liên kết dat_san)',
                      `dich_vu_id` INT(11) NOT NULL COMMENT 'Mã dịch vụ bổ sung (liên kết dich_vu)',
                      `ten_dich_vu` VARCHAR(100) DEFAULT NULL COMMENT 'Tên dịch vụ tại thời điểm đặt',
                      `don_vi_tinh` VARCHAR(20) DEFAULT NULL COMMENT 'Đơn vị tính',
                      `don_gia` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Đơn giá tại thời điểm đặt',
                      `so_luong` INT(11) NOT NULL DEFAULT 1 COMMENT 'Số lượng đặt',
                      `thanh_tien` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Thành tiền = don_gia * so_luong',
                      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`),
                      KEY `fk_ctdv_dat_san` (`dat_san_id`),
                      KEY `fk_ctdv_dich_vu` (`dich_vu_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
                $created[] = 'chi_tiet_dich_vu';
            } catch (Throwable $e) { $errors[] = "chi_tiet_dich_vu: " . $e->getMessage(); }
        }

        // Bổ sung các cột dia_chi, link_google_map, hinh_anh_dai_dien vào san_bong nếu thiếu
        if (in_array('san_bong', $existingTables)) {
            try {
                $cols = $pdo->query("SHOW COLUMNS FROM san_bong")->fetchAll(PDO::FETCH_COLUMN);
                if (!in_array('dia_chi', $cols)) {
                    $pdo->exec("ALTER TABLE `san_bong` ADD COLUMN `dia_chi` VARCHAR(255) DEFAULT NULL AFTER `ten_san`");
                    $created[] = 'san_bong.dia_chi';
                }
                if (!in_array('link_google_map', $cols)) {
                    $pdo->exec("ALTER TABLE `san_bong` ADD COLUMN `link_google_map` TEXT DEFAULT NULL AFTER `dia_chi`");
                    $created[] = 'san_bong.link_google_map';
                }
                if (!in_array('hinh_anh_dai_dien', $cols)) {
                    $pdo->exec("ALTER TABLE `san_bong` ADD COLUMN `hinh_anh_dai_dien` VARCHAR(255) DEFAULT NULL AFTER `link_google_map`");
                    $created[] = 'san_bong.hinh_anh_dai_dien';
                }
            } catch (Throwable $e) { $errors[] = "san_bong columns: " . $e->getMessage(); }
        }

        // Tạo tài khoản Admin nếu chưa có
        if (in_array('nguoi_dung', $existingTables) || in_array('nguoi_dung', $created)) {
            try {
                $hasAdmin = (int)$pdo->query("SELECT COUNT(*) FROM nguoi_dung WHERE vai_tro_id = 1")->fetchColumn();
                if ($hasAdmin === 0) {
                    $pass = password_hash('admin123', PASSWORD_DEFAULT);
                    $pdo->prepare("
                        INSERT INTO nguoi_dung (vai_tro_id, ho_ten, email, so_dien_thoai, mat_khau)
                        VALUES (1, 'Quản Trị Viên Hệ Thống', 'admin@bongda16h.vn', '0981122334', ?)
                    ")->execute([$pass]);
                    $created[] = 'Tài khoản Admin mặc định (0981122334 / admin123)';
                }
            } catch (Throwable $e) { $errors[] = "Admin seed: " . $e->getMessage(); }
        }

        if (!empty($created)) {
            $actionResult = [
                'type' => 'success',
                'msg' => 'Đã tự động khởi tạo và đồng bộ thành công: ' . implode(', ', $created)
            ];
        }
        if (!empty($errors)) {
            $actionResult = [
                'type' => 'danger',
                'msg' => 'Gặp lỗi trong quá trình đồng bộ: ' . implode(' | ', $errors)
            ];
        }

        // Refresh danh sách bảng
        $rawTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $existingTables = array_map('strtolower', $rawTables);
        $missingTables = [];
        foreach (array_keys($requiredTables) as $reqTbl) {
            if (!in_array(strtolower($reqTbl), $existingTables)) {
                $missingTables[] = $reqTbl;
            }
        }
    }
}
?>

<!-- HEADER -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-shield-check text-success me-2"></i>Chẩn Đoán & Sửa Lỗi Hosting</h3>
        <p class="text-muted mb-0">Hệ thống phân tích tình trạng PHP, Database MySQL và Thư mục tải ảnh trên InfinityFree</p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>/admin/index.php" class="btn btn-outline-secondary">
            <i class="bi bi-speedometer2 me-1"></i> Về Dashboard Admin
        </a>
    </div>
</div>

<?php if ($actionResult): ?>
    <div class="alert alert-<?php echo $actionResult['type']; ?> alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo e($actionResult['msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- 1. MÔI TRƯỜNG PHP -->
<div class="card card-custom">
    <div class="card-header-custom d-flex justify-content-between align-items-center">
        <span><i class="bi bi-cpu text-primary me-2"></i> 1. Cấu Hình Môi Trường PHP</span>
        <span class="badge badge-status <?php echo $isPhp8 ? 'bg-success text-white' : 'bg-warning text-dark'; ?>">
            PHP <?php echo $phpVersion; ?>
        </span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="p-3 bg-light rounded-3">
                    <div class="small text-muted mb-1">Phiên bản PHP:</div>
                    <div class="fw-bold fs-5"><?php echo $phpVersion; ?></div>
                    <small class="text-muted">
                        <?php if ($isPhp8): ?>
                            <span class="text-success"><i class="bi bi-check-circle-fill"></i> Tương thích tốt với PHP 8+</span>
                        <?php else: ?>
                            <span class="text-warning"><i class="bi bi-exclamation-triangle-fill"></i> PHP 7.x (Hệ thống đã tự động bật Polyfill hỗ trợ)</span>
                        <?php endif; ?>
                    </small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 bg-light rounded-3">
                    <div class="small text-muted mb-1">Máy chủ Web & Hệ điều hành:</div>
                    <div class="fw-bold"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Apache'; ?></div>
                    <small class="text-muted">Hệ thống: <?php echo PHP_OS; ?></small>
                </div>
            </div>
        </div>

        <h6 class="fw-bold mt-4 mb-2 small text-uppercase text-secondary">Phần mở rộng PHP (Extensions):</h6>
        <div class="row g-2">
            <?php foreach ($extensions as $extName => $isLoaded): ?>
                <div class="col-sm-4 col-6">
                    <div class="border rounded p-2 d-flex justify-content-between align-items-center">
                        <span class="small fw-semibold"><?php echo $extName; ?></span>
                        <?php if ($isLoaded): ?>
                            <span class="badge bg-success-subtle text-success"><i class="bi bi-check-lg"></i> Sẵn sàng</span>
                        <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger"><i class="bi bi-x-lg"></i> Thiếu</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- 2. KẾT NỐI DATABASE MYSQL -->
<div class="card card-custom">
    <div class="card-header-custom d-flex justify-content-between align-items-center">
        <span><i class="bi bi-database text-primary me-2"></i> 2. Kết Nối Cơ Sở Dữ Liệu MySQL</span>
        <?php if ($dbConnected): ?>
            <span class="badge badge-status bg-success text-white"><i class="bi bi-check-circle-fill me-1"></i> Kết Nối Thành Công</span>
        <?php else: ?>
            <span class="badge badge-status bg-danger text-white"><i class="bi bi-x-circle-fill me-1"></i> Lỗi Kết Nối</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="small text-muted">MySQL Host:</div>
                <div class="fw-bold"><code><?php echo htmlspecialchars(DB_HOST); ?></code></div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">Database Name:</div>
                <div class="fw-bold"><code><?php echo htmlspecialchars(DB_NAME); ?></code></div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">MySQL User:</div>
                <div class="fw-bold"><code><?php echo htmlspecialchars(DB_USER); ?></code></div>
            </div>
        </div>

        <?php if (!$dbConnected): ?>
            <div class="alert alert-danger mb-0">
                <h6 class="fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i> Chi tiết lỗi kết nối:</h6>
                <code><?php echo htmlspecialchars($dbError); ?></code>
                <p class="small mt-2 mb-0">Vui lòng kiểm tra lại thông tin <strong>DB_HOST, DB_NAME, DB_USER, DB_PASS</strong> trong file <code>config/database.php</code> theo đúng thông số trên bảng điều khiển InfinityFree.</p>
            </div>
        <?php else: ?>
            <!-- BẢNG DỮ LIỆU ĐỐI CHIẾU -->
            <h6 class="fw-bold mt-3 mb-2 small text-uppercase text-secondary">Đối chiếu danh sách 11 bảng chuẩn:</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th style="width: 250px;">Tên Bảng</th>
                            <th>Mô Tả Chức Năng</th>
                            <th style="width: 120px;" class="text-center">Trạng Thái</th>
                            <th style="width: 100px;" class="text-end">Số Bản Ghi</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php foreach ($requiredTables as $tName => $tDesc): 
                            $hasTable = in_array(strtolower($tName), $existingTables);
                            $rowCount = 0;
                            if ($hasTable) {
                                try {
                                    $rowCount = (int)$pdo->query("SELECT COUNT(*) FROM `{$tName}`")->fetchColumn();
                                } catch (Throwable $e) { $rowCount = 'Lỗi'; }
                            }
                        ?>
                            <tr>
                                <td><code class="fw-bold"><?php echo $tName; ?></code></td>
                                <td class="text-muted"><?php echo $tDesc; ?></td>
                                <td class="text-center">
                                    <?php if ($hasTable): ?>
                                        <span class="badge bg-success-subtle text-success">Đã có</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger fw-bold">CHƯA CÓ</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end font-monospace"><?php echo $hasTable ? $rowCount : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($missingTables)): ?>
                <div class="alert alert-warning mt-3 mb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-exclamation-circle-fill text-warning fs-5 me-2"></i>
                        <strong>Phát hiện <?php echo count($missingTables); ?> bảng chưa được tạo:</strong> 
                        <code><?php echo implode(', ', $missingTables); ?></code>
                    </div>
                    <form method="POST" class="ms-3">
                        <input type="hidden" name="action" value="sync_missing_tables">
                        <button type="submit" class="btn btn-warning btn-sm fw-bold">
                            <i class="bi bi-wrench me-1"></i> Tự Động Khởi Tạo Ngay
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="alert alert-success mt-3 mb-0 py-2 small">
                    <i class="bi bi-check-circle-fill me-1"></i> Toàn bộ 11 bảng chuẩn đã hiện diện đầy đủ trong cơ sở dữ liệu!
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- 3. THƯ MỤC LƯU TRỮ VÀ UPLOAD ẢNH -->
<div class="card card-custom">
    <div class="card-header-custom d-flex justify-content-between align-items-center">
        <span><i class="bi bi-folder text-primary me-2"></i> 3. Thư Mục Lưu Trữ & Tải Ảnh Sân Bóng</span>
        <?php if ($isSanBongDirExists && $isSanBongDirWritable): ?>
            <span class="badge badge-status bg-success text-white">Sẵn Sàng Lưu Ảnh</span>
        <?php else: ?>
            <span class="badge badge-status bg-warning text-dark">Cần Khởi Tạo</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <ul class="list-group list-group-flush mb-3">
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <div>
                    <strong>Thư mục gốc <code>uploads/</code></strong>
                    <div class="small text-muted">Đường dẫn: <code><?php echo htmlspecialchars($uploadRootDir); ?></code></div>
                </div>
                <div>
                    <?php if ($isUploadDirExists): ?>
                        <span class="badge bg-success-subtle text-success me-1">Đã tồn tại</span>
                        <?php if ($isUploadDirWritable): ?>
                            <span class="badge bg-success-subtle text-success">Ghi file OK (755/777)</span>
                        <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger">Chỉ đọc (Cần cấp quyền ghi)</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge bg-danger-subtle text-danger">Chưa tồn tại</span>
                    <?php endif; ?>
                </div>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <div>
                    <strong>Thư mục ảnh sân <code>uploads/san_bong/</code></strong>
                    <div class="small text-muted">Đường dẫn: <code><?php echo htmlspecialchars($uploadSanBongDir); ?></code></div>
                </div>
                <div>
                    <?php if ($isSanBongDirExists): ?>
                        <span class="badge bg-success-subtle text-success me-1">Đã tồn tại</span>
                        <?php if ($isSanBongDirWritable): ?>
                            <span class="badge bg-success-subtle text-success">Ghi file OK (755/777)</span>
                        <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger">Chỉ đọc</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge bg-danger-subtle text-danger">Chưa tồn tại</span>
                    <?php endif; ?>
                </div>
            </li>
        </ul>

        <?php if (!$isSanBongDirExists || !$isUploadDirExists): ?>
            <form method="POST">
                <input type="hidden" name="action" value="create_upload_dirs">
                <button type="submit" class="btn btn-outline-primary btn-sm fw-semibold">
                    <i class="bi bi-folder-plus me-1"></i> Tự Động Tạo Thư Mục Uploads
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- 4. THÔNG TIN ĐĂNG NHẬP ADMIN MẪU -->
<div class="card card-custom">
    <div class="card-header-custom">
        <i class="bi bi-person-badge text-primary me-2"></i> 4. Tài Khoản Quản Trị Viên (Admin)
    </div>
    <div class="card-body">
        <?php 
        $adminAccount = null;
        if ($dbConnected && in_array('nguoi_dung', $existingTables)) {
            try {
                $adminAccount = $pdo->query("SELECT id, ho_ten, email, so_dien_thoai FROM nguoi_dung WHERE vai_tro_id = 1 LIMIT 1")->fetch();
            } catch (Throwable $e) {}
        }
        ?>

        <?php if ($adminAccount): ?>
            <div class="alert alert-success d-flex align-items-center mb-0">
                <i class="bi bi-check-circle-fill fs-3 text-success me-3"></i>
                <div>
                    <h6 class="fw-bold mb-1">Đã có tài khoản Quản trị viên trong hệ thống:</h6>
                    <div class="small">
                        Họ tên: <strong><?php echo e($adminAccount['ho_ten']); ?></strong> | 
                        SĐT đăng nhập: <strong><?php echo e($adminAccount['so_dien_thoai']); ?></strong> | 
                        Email: <strong><?php echo e($adminAccount['email'] ?? 'admin@bongda16h.vn'); ?></strong>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning mb-0 d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-exclamation-triangle-fill fs-4 text-warning me-2"></i>
                    Chưa tìm thấy tài khoản Quản trị viên (Admin) trong database!
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="sync_missing_tables">
                    <button type="submit" class="btn btn-primary btn-sm fw-bold">
                        <i class="bi bi-person-plus-fill me-1"></i> Tạo Admin Mặc Định
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="text-center py-3 text-muted small">
    Dự án Quản Lý Sân Bóng Đá BongDa16h &copy; <?php echo date('Y'); ?>. Chẩn đoán và sửa lỗi hosting tự động.
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

