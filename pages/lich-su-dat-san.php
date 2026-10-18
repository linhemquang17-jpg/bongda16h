<?php
/**
 * Lịch Sử Đặt Sân của Khách Hàng
 * Dự án: BongDa16h
 */
$pageTitle = 'Lịch Sử Đặt Sân';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$currentUser = getCurrentUser();
$userId = (int)$currentUser['id'];
$pdo = getDBConnection();

// Lọc trạng thái
$filterStatus = sanitize($_GET['status'] ?? '');

$sql = "
    SELECT d.*, 
           s.ten_san, s.gia_theo_gio,
           c.ten_cum_san, c.dia_chi,
           l.ten_loai,
           t.phuong_thuc_thanh_toan, t.trang_thai_thanh_toan
    FROM dat_san d
    JOIN san_bong s ON d.san_bong_id = s.id
    JOIN cum_san c ON s.cum_san_id = c.id
    JOIN loai_san l ON s.loai_san_id = l.id
    LEFT JOIN thanh_toan t ON d.id = t.dat_san_id
    WHERE d.nguoi_dung_id = :uid
";
$params = [':uid' => $userId];

if (!empty($filterStatus)) {
    $sql .= " AND d.trang_thai_dat = :status";
    $params[':status'] = $filterStatus;
}

$sql .= " ORDER BY d.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php" class="text-decoration-none">Trang chủ</a></li>
            <li class="breadcrumb-item active" aria-current="page">Lịch sử đặt sân</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold text-navy mb-1"><i class="bi bi-clock-history text-success me-2"></i> Lịch Sử Đặt Sân Của Bạn</h3>
            <p class="text-muted mb-0">Theo dõi trạng thái các đơn đặt sân và thông tin thi đấu</p>
        </div>
        <div class="mt-3 mt-sm-0">
            <a href="<?php echo BASE_URL; ?>/pages/dat-san.php" class="btn btn-primary-pitch">
                <i class="bi bi-plus-circle me-1"></i> Đặt sân mới
            </a>
        </div>
    </div>

    <!-- TABS LỌC TRẠNG THÁI -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
        <div class="d-flex flex-wrap gap-2">
            <a href="<?php echo BASE_URL; ?>/pages/lich-su-dat-san.php" class="btn btn-sm <?php echo empty($filterStatus) ? 'btn-success' : 'btn-outline-secondary'; ?> rounded-pill px-3">
                Tất cả đơn
            </a>
            <a href="<?php echo BASE_URL; ?>/pages/lich-su-dat-san.php?status=Chờ xác nhận" class="btn btn-sm <?php echo $filterStatus === 'Chờ xác nhận' ? 'btn-warning text-dark' : 'btn-outline-warning text-dark'; ?> rounded-pill px-3">
                Chờ xác nhận
            </a>
            <a href="<?php echo BASE_URL; ?>/pages/lich-su-dat-san.php?status=Đã xác nhận" class="btn btn-sm <?php echo $filterStatus === 'Đã xác nhận' ? 'btn-primary' : 'btn-outline-primary'; ?> rounded-pill px-3">
                Đã xác nhận
            </a>
            <a href="<?php echo BASE_URL; ?>/pages/lich-su-dat-san.php?status=Đã hoàn thành" class="btn btn-sm <?php echo $filterStatus === 'Đã hoàn thành' ? 'btn-success' : 'btn-outline-success'; ?> rounded-pill px-3">
                Đã hoàn thành
            </a>
            <a href="<?php echo BASE_URL; ?>/pages/lich-su-dat-san.php?status=Đã hủy" class="btn btn-sm <?php echo $filterStatus === 'Đã hủy' ? 'btn-danger' : 'btn-outline-danger'; ?> rounded-pill px-3">
                Đã hủy
            </a>
        </div>
    </div>

    <!-- BẢNG DANH SÁCH ĐƠN -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <?php if (empty($bookings)): ?>
            <div class="p-5 text-center">
                <div class="fs-1 text-muted mb-3"><i class="bi bi-calendar-x"></i></div>
                <h5>Chưa có đơn đặt sân nào!</h5>
                <p class="text-muted">Bạn chưa có đơn đặt sân nào phù hợp với bộ lọc hiện tại.</p>
                <a href="<?php echo BASE_URL; ?>/pages/dat-san.php" class="btn btn-primary-pitch mt-2">
                    <i class="bi bi-calendar-plus me-1"></i> Đặt sân ngay
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Mã đơn</th>
                            <th>Sân bóng & Cụm sân</th>
                            <th>Thời gian đá</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái đơn</th>
                            <th>Thanh toán</th>
                            <th class="text-end pe-4">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td class="ps-4">
                                    <strong class="text-navy">#BD16H<?php echo (int)$b['id']; ?></strong>
                                    <small class="text-muted d-block"><?php echo formatDate($b['created_at']); ?></small>
                                </td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/pages/chi-tiet-san.php?id=<?php echo (int)$b['san_bong_id']; ?>" class="fw-bold text-decoration-none text-navy">
                                        <?php echo e($b['ten_san']); ?>
                                    </a>
                                    <small class="text-muted d-block"><i class="bi bi-geo-alt text-danger me-1"></i> <?php echo e($b['ten_cum_san']); ?></small>
                                </td>
                                <td>
                                    <strong class="text-success"><?php echo substr($b['gio_bat_dau'], 0, 5); ?> - <?php echo substr($b['gio_ket_thuc'], 0, 5); ?></strong>
                                    <small class="text-muted d-block"><?php echo formatDate($b['ngay_dat']); ?></small>
                                </td>
                                <td>
                                    <span class="fw-bold text-danger"><?php echo formatCurrency($b['tong_tien']); ?></span>
                                </td>
                                <td>
                                    <?php echo renderBookingStatusBadge($b['trang_thai_dat']); ?>
                                </td>
                                <td>
                                    <?php echo renderPaymentStatusBadge($b['trang_thai_thanh_toan'] ?? 'Chưa thanh toán'); ?>
                                    <small class="text-muted d-block"><?php echo e($b['phuong_thuc_thanh_toan'] ?? 'Tiền mặt'); ?></small>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="<?php echo BASE_URL; ?>/pages/chi-tiet-don.php?id=<?php echo (int)$b['id']; ?>" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-eye me-1"></i> Xem chi tiết
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

