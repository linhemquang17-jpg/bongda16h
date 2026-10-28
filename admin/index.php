<?php
/**
 * Dashboard Quản Trị - Thống kê dữ liệu thực tế
 * Dự án: BongDa16h
 */
$pageTitle = 'Bảng Điều Khiển Tổng Quan';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/auth_check.php';

$pdo = getDBConnection();

// Khởi tạo các giá trị mặc định an toàn chống lỗi HTTP 500
$totalPitches      = 0;
$totalComplexes    = 0;
$totalUsers        = 0;
$totalBookings     = 0;
$pendingBookings   = 0;
$confirmedBookings = 0;
$completedBookings = 0;
$canceledBookings  = 0;
$validRevenue      = 0.0;
$recentBookings    = [];
$todayList         = [];
$dbWarnings        = [];

// 1. Thống kê số lượng
try {
    $totalPitches   = (int)$pdo->query("SELECT COUNT(*) FROM san_bong")->fetchColumn();
    $totalComplexes = (int)$pdo->query("SELECT COUNT(*) FROM cum_san")->fetchColumn();
    $totalUsers     = (int)$pdo->query("SELECT COUNT(*) FROM nguoi_dung")->fetchColumn();
    $totalBookings  = (int)$pdo->query("SELECT COUNT(*) FROM dat_san")->fetchColumn();
} catch (Throwable $e) {
    $dbWarnings[] = "Lỗi thống kê số lượng: " . $e->getMessage();
}

// 2. Thống kê trạng thái đơn
try {
    $pendingBookings   = (int)$pdo->query("SELECT COUNT(*) FROM dat_san WHERE trang_thai_dat = 'Chờ xác nhận'")->fetchColumn();
    $confirmedBookings = (int)$pdo->query("SELECT COUNT(*) FROM dat_san WHERE trang_thai_dat = 'Đã xác nhận'")->fetchColumn();
    $completedBookings = (int)$pdo->query("SELECT COUNT(*) FROM dat_san WHERE trang_thai_dat = 'Đã hoàn thành'")->fetchColumn();
    $canceledBookings  = (int)$pdo->query("SELECT COUNT(*) FROM dat_san WHERE trang_thai_dat = 'Đã hủy'")->fetchColumn();
} catch (Throwable $e) {
    $dbWarnings[] = "Lỗi thống kê trạng thái đơn: " . $e->getMessage();
}

// 3. Doanh thu đã thanh toán hợp lệ
try {
    $stmtRev = $pdo->query("
        SELECT COALESCE(SUM(so_tien), 0) 
        FROM thanh_toan 
        WHERE trang_thai_thanh_toan IN ('Đã thanh toán đủ', 'Đã đặt cọc')
    ");
    $validRevenue = (float)$stmtRev->fetchColumn();
} catch (Throwable $e) {
    $dbWarnings[] = "Lỗi bảng thanh toán: " . $e->getMessage();
}

// 4. Danh sách 6 đơn đặt sân mới nhất
try {
    $recentBookings = $pdo->query("
        SELECT d.*, 
               u.ho_ten as ten_khach, u.so_dien_thoai as sdt_khach,
               s.ten_san, c.ten_cum_san,
               t.phuong_thuc_thanh_toan, t.trang_thai_thanh_toan
        FROM dat_san d
        JOIN nguoi_dung u ON d.nguoi_dung_id = u.id
        JOIN san_bong s ON d.san_bong_id = s.id
        JOIN cum_san c ON s.cum_san_id = c.id
        LEFT JOIN thanh_toan t ON d.id = t.dat_san_id
        ORDER BY d.id DESC
        LIMIT 6
    ")->fetchAll();
} catch (Throwable $e) {
    $dbWarnings[] = "Lỗi danh sách đơn mới: " . $e->getMessage();
}

// 5. Lịch đá bóng hôm nay
try {
    $today = date('Y-m-d');
    $todayBookings = $pdo->prepare("
        SELECT d.*, 
               u.ho_ten as ten_khach, u.so_dien_thoai as sdt_khach,
               s.ten_san, c.ten_cum_san
        FROM dat_san d
        JOIN nguoi_dung u ON d.nguoi_dung_id = u.id
        JOIN san_bong s ON d.san_bong_id = s.id
        JOIN cum_san c ON s.cum_san_id = c.id
        WHERE d.ngay_dat = ? AND d.trang_thai_dat != 'Đã hủy'
        ORDER BY d.gio_bat_dau ASC
    ");
    $todayBookings->execute([$today]);
    $todayList = $todayBookings->fetchAll();
} catch (Throwable $e) {
    $dbWarnings[] = "Lỗi lịch đá hôm nay: " . $e->getMessage();
}

require_once __DIR__ . '/header.php';
?>

<?php if (!empty($dbWarnings)): ?>
    <div class="alert alert-warning border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="d-flex align-items-center mb-2">
            <i class="bi bi-exclamation-triangle-fill text-warning fs-3 me-2"></i>
            <h5 class="fw-bold mb-0 text-dark">Cảnh báo đồng bộ Cơ sở dữ liệu</h5>
        </div>
        <p class="small text-muted mb-2">Hệ thống phát hiện một số bảng hoặc truy vấn MySQL chưa hoàn tất dữ liệu. Trang Admin vẫn hoạt động an toàn và không bị lỗi HTTP 500. Vui lòng kiểm tra các mục bên dưới:</p>
        <ul class="small mb-3 text-danger">
            <?php foreach ($dbWarnings as $warn): ?>
                <li><code><?php echo e($warn); ?></code></li>
            <?php endforeach; ?>
        </ul>
        <a href="<?php echo BASE_URL; ?>/admin/diagnostics.php" class="btn btn-warning btn-sm fw-bold">
            <i class="bi bi-tools me-1"></i> Mở Công Cụ Kiểm Tra & Sửa Lỗi Database (Diagnostics)
        </a>
    </div>
<?php endif; ?>
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold text-navy mb-1">Bảng Điều Khiển Tổng Quan</h3>
        <p class="text-muted mb-0">Theo dõi hoạt động kinh doanh sân bóng và lịch đặt sân thời gian thực</p>
    </div>
    <div>
        <span class="badge bg-light text-dark border p-2">
            <i class="bi bi-calendar3 me-1 text-primary"></i> Hôm nay: <?php echo date('d/m/Y'); ?>
        </span>
    </div>
</div>

<!-- STAT CARDS -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-currency-dollar"></i>
            </div>
            <div>
                <div class="stat-value text-success"><?php echo formatCurrency($validRevenue); ?></div>
                <div class="stat-label">Doanh thu thực tế</div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="bi bi-clock-history"></i>
            </div>
            <div>
                <div class="stat-value text-warning"><?php echo $pendingBookings; ?></div>
                <div class="stat-label">Đơn chờ xác nhận</div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div>
                <div class="stat-value text-primary"><?php echo $totalBookings; ?></div>
                <div class="stat-label">Tổng lượt đặt sân</div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="bi bi-grid"></i>
            </div>
            <div>
                <div class="stat-value text-purple"><?php echo $totalPitches; ?> sân</div>
                <div class="stat-label"><?php echo $totalComplexes; ?> cụm sân bóng</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- CỘT TRÁI: ĐƠN ĐẶT SÂN MỚI NHẤT -->
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="bi bi-list-check text-success me-2"></i> Đơn Đặt Sân Mới Nhất</span>
                <a href="<?php echo BASE_URL; ?>/admin/don-dat-san.php" class="btn btn-outline-primary btn-sm">Xem tất cả</a>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Sân bóng</th>
                            <th>Thời gian đá</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentBookings)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    Chưa có đơn đặt sân nào trong hệ thống.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentBookings as $b): ?>
                                <tr>
                                    <td><strong>#BD16H<?php echo (int)$b['id']; ?></strong></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo e($b['ten_khach']); ?></div>
                                        <small class="text-muted"><?php echo e($b['sdt_khach']); ?></small>
                                    </td>
                                    <td>
                                        <div><?php echo e($b['ten_san']); ?></div>
                                        <small class="text-muted"><?php echo e($b['ten_cum_san']); ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?php echo substr($b['gio_bat_dau'], 0, 5); ?> - <?php echo substr($b['gio_ket_thuc'], 0, 5); ?></div>
                                        <small class="text-muted"><?php echo formatDate($b['ngay_dat']); ?></small>
                                    </td>
                                    <td><strong class="text-danger"><?php echo formatCurrency($b['tong_tien']); ?></strong></td>
                                    <td><?php echo renderBookingStatusBadge($b['trang_thai_dat']); ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/admin/chi-tiet-don.php?id=<?php echo (int)$b['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                            Chi tiết
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- CỘT PHẢI: LỊCH ĐÁ HÔM NAY & TRẠNG THÁI HỆ THỐNG -->
    <div class="col-lg-4">
        <!-- Lịch đá bóng hôm nay -->
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <span><i class="bi bi-clock-history text-primary me-2"></i> Lịch Thi Đấu Hôm Nay</span>
                <span class="badge bg-primary"><?php echo count($todayList); ?> trận</span>
            </div>
            <div class="admin-card-body p-3">
                <?php if (empty($todayList)): ?>
                    <p class="text-muted small text-center my-3">Hôm nay chưa có lịch đặt sân nào.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($todayList as $td): ?>
                            <div class="list-group-item px-0 py-2 border-bottom">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-success"><?php echo substr($td['gio_bat_dau'], 0, 5); ?> - <?php echo substr($td['gio_ket_thuc'], 0, 5); ?></strong>
                                    <?php echo renderBookingStatusBadge($td['trang_thai_dat']); ?>
                                </div>
                                <div class="small fw-semibold"><?php echo e($td['ten_san']); ?> (<?php echo e($td['ten_cum_san']); ?>)</div>
                                <div class="small text-muted"><i class="bi bi-person me-1"></i> <?php echo e($td['ten_khach']); ?> - <?php echo e($td['sdt_khach']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Thống kê trạng thái đơn hàng -->
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="bi bi-pie-chart text-warning me-2"></i> Tỷ Lệ Đơn Hàng</span>
            </div>
            <div class="admin-card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="small">Chờ xác nhận:</span>
                    <strong class="text-warning"><?php echo $pendingBookings; ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="small">Đã xác nhận:</span>
                    <strong class="text-primary"><?php echo $confirmedBookings; ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="small">Đã hoàn thành:</span>
                    <strong class="text-success"><?php echo $completedBookings; ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="small">Đã hủy:</span>
                    <strong class="text-danger"><?php echo $canceledBookings; ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

