<?php
/**
 * Chi tiết Đơn Đặt Sân và Thông Tin Thanh Toán
 * Dự án: BongDa16h
 */
$pageTitle = 'Chi Tiết Đơn Đặt Sân';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$bookingId = (int)($_GET['id'] ?? 0);
if (!$bookingId) {
    header('Location: ' . BASE_URL . '/pages/san-bong.php');
    exit;
}

$pdo = getDBConnection();
$currentUser = getCurrentUser();

// Lấy thông tin đơn đặt sân, người dùng, sân bóng, cụm sân, loại sân
$stmt = $pdo->prepare("
    SELECT d.*, 
           u.ho_ten as ten_khach, u.so_dien_thoai as sdt_khach, u.email as email_khach,
           s.ten_san, s.gia_theo_gio,
           c.ten_cum_san, c.dia_chi, c.so_dien_thoai_lien_he, c.link_google_map,
           l.ten_loai,
           t.phuong_thuc_thanh_toan, t.so_tien as so_tien_thanh_toan, t.trang_thai_thanh_toan, t.ngay_thanh_toan
    FROM dat_san d
    JOIN nguoi_dung u ON d.nguoi_dung_id = u.id
    JOIN san_bong s ON d.san_bong_id = s.id
    JOIN cum_san c ON s.cum_san_id = c.id
    JOIN loai_san l ON s.loai_san_id = l.id
    LEFT JOIN thanh_toan t ON d.id = t.dat_san_id
    WHERE d.id = ?
");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    setFlash('danger', 'Đơn đặt sân không tồn tại trong hệ thống!');
    header('Location: ' . BASE_URL . '/pages/san-bong.php');
    exit;
}

// Kiểm tra quyền riêng tư:
// Nếu là khách thông thường đã đăng nhập, chỉ được xem đơn của mình
if (isLoggedIn() && !isStaffOrAdmin()) {
    if ((int)$booking['nguoi_dung_id'] !== (int)$currentUser['id']) {
        setFlash('danger', 'Bạn không có quyền truy cập vào đơn đặt sân này!');
        header('Location: ' . BASE_URL . '/pages/lich-su-dat-san.php');
        exit;
    }
}

// Lấy chi tiết dịch vụ của đơn nếu có
$svcStmt = $pdo->prepare("
    SELECT cd.*, v.ten_dich_vu, v.don_vi_tinh 
    FROM chi_tiet_dich_vu cd
    JOIN dich_vu v ON cd.dich_vu_id = v.id
    WHERE cd.dat_san_id = ?
");
$svcStmt->execute([$bookingId]);
$bookedServices = $svcStmt->fetchAll();

// Tính thời lượng đá bóng
$calcTime = calculatePitchPrice($booking['gia_theo_gio'], $booking['gio_bat_dau'], $booking['gio_ket_thuc']);
$pitchFee = $calcTime['valid'] ? $calcTime['total'] : 0;

$qrBankUrl = "https://img.vietqr.io/image/MB-0981122334-compact2.png?amount=" . (int)$booking['tong_tien'] . "&addInfo=BD16H" . $booking['id'] . "&accountName=BONGDA16H%20SPORT";

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php" class="text-decoration-none">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/lich-su-dat-san.php" class="text-decoration-none">Lịch sử đặt sân</a></li>
            <li class="breadcrumb-item active" aria-current="page">Đơn #BD16H<?php echo $booking['id']; ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- CỘT TRÁI: CHI TIẾT ĐƠN HÀNG -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between pb-3 mb-3 border-bottom">
                    <div>
                        <span class="text-muted small">MÃ ĐƠN HÀNG:</span>
                        <h3 class="fw-bold text-navy mb-0">#BD16H<?php echo $booking['id']; ?></h3>
                    </div>
                    <div class="text-end mt-2 mt-sm-0">
                        <small class="text-muted d-block mb-1">Trạng thái đơn:</small>
                        <?php echo renderBookingStatusBadge($booking['trang_thai_dat']); ?>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Khách hàng:</small>
                        <strong class="fs-6"><?php echo e($booking['ten_khach']); ?></strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Số điện thoại liên hệ:</small>
                        <strong class="fs-6"><?php echo e($booking['sdt_khach']); ?></strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Thời gian tạo đơn:</small>
                        <span><?php echo formatDateTime($booking['created_at']); ?></span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Email:</small>
                        <span><?php echo e($booking['email_khach'] ?? 'Chưa cung cấp'); ?></span>
                    </div>
                </div>

                <!-- THÔNG TIN SÂN & THỜI GIAN ĐÁ -->
                <h5 class="fw-bold text-navy mb-3"><i class="bi bi-calendar-check text-success me-2"></i> Lịch Thi Đấu</h5>
                <div class="p-3 bg-light rounded-3 mb-4 border">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <span class="text-muted small d-block">Sân bóng:</span>
                            <h5 class="fw-bold mb-1 text-primary"><?php echo e($booking['ten_san']); ?> (<?php echo e($booking['ten_loai']); ?>)</h5>
                            <span class="text-muted small"><i class="bi bi-geo-alt-fill text-danger me-1"></i> <?php echo e($booking['ten_cum_san']); ?> - <?php echo e($booking['dia_chi']); ?></span>
                        </div>
                        <div class="col-md-6 border-start-md">
                            <span class="text-muted small d-block">Thời gian đá:</span>
                            <div class="fs-5 fw-bold text-success mb-1">
                                <?php echo substr($booking['gio_bat_dau'], 0, 5); ?> - <?php echo substr($booking['gio_ket_thuc'], 0, 5); ?>
                            </div>
                            <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i> Ngày: <strong><?php echo formatDate($booking['ngay_dat']); ?></strong> (<?php echo $calcTime['hours'] ?? 0; ?> giờ)</span>
                        </div>
                    </div>
                </div>

                <!-- CHI TIẾT BẢNG TÍNH TIỀN -->
                <h5 class="fw-bold text-navy mb-3"><i class="bi bi-receipt text-primary me-2"></i> Chi Tiết Thanh Toán</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Khoản mục</th>
                                <th class="text-center">Số lượng / Thời lượng</th>
                                <th class="text-end">Đơn giá</th>
                                <th class="text-end">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong>Tiền thuê sân: <?php echo e($booking['ten_san']); ?></strong>
                                    <small class="text-muted d-block">Từ <?php echo substr($booking['gio_bat_dau'], 0, 5); ?> đến <?php echo substr($booking['gio_ket_thuc'], 0, 5); ?></small>
                                </td>
                                <td class="text-center"><?php echo $calcTime['hours'] ?? 0; ?> giờ</td>
                                <td class="text-end"><?php echo formatCurrency($booking['gia_theo_gio']); ?></td>
                                <td class="text-end fw-semibold"><?php echo formatCurrency($pitchFee); ?></td>
                            </tr>
                            <?php if (!empty($bookedServices)): ?>
                                <?php foreach ($bookedServices as $svc): 
                                    $itemSubtotal = (float)$svc['don_gia'] * (int)$svc['so_luong'];
                                ?>
                                    <tr>
                                        <td>
                                            <span>Dịch vụ: <?php echo e($svc['ten_dich_vu']); ?></span>
                                            <small class="text-muted d-block">ĐVT: <?php echo e($svc['don_vi_tinh']); ?></small>
                                        </td>
                                        <td class="text-center"><?php echo (int)$svc['so_luong']; ?></td>
                                        <td class="text-end"><?php echo formatCurrency($svc['don_gia']); ?></td>
                                        <td class="text-end fw-semibold"><?php echo formatCurrency($itemSubtotal); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-light fs-5">
                                <th colspan="3" class="text-end fw-bold text-navy">TỔNG CỘNG:</th>
                                <th class="text-end fw-bold text-danger"><?php echo formatCurrency($booking['tong_tien']); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- THAO TÁC HỦY ĐƠN NẾU THỎA ĐIỀU KIỆN -->
                <?php if ($booking['trang_thai_dat'] === 'Chờ xác nhận'): ?>
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Đơn đang ở trạng thái Chờ xác nhận, quý khách có thể hủy đơn nếu đổi kế hoạch.</small>
                        <form action="<?php echo BASE_URL; ?>/actions/process_cancel.php" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn đặt sân này?');">
                            <?php echo getCsrfField(); ?>
                            <input type="hidden" name="booking_id" value="<?php echo (int)$booking['id']; ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-x-circle me-1"></i> Hủy đơn đặt sân
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- CỘT PHẢI: TRẠNG THÁI & HƯỚNG DẪN THANH TOÁN -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h5 class="fw-bold text-navy mb-3"><i class="bi bi-wallet2 text-success me-2"></i> Trạng Thái Thanh Toán</h5>

                <div class="mb-3">
                    <small class="text-muted d-block">Phương thức:</small>
                    <span class="badge bg-secondary-subtle text-secondary fs-6 px-3 py-1 mt-1">
                        <?php echo e($booking['phuong_thuc_thanh_toan'] ?? 'Tiền mặt'); ?>
                    </span>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block">Tình trạng thanh toán:</small>
                    <div class="mt-1">
                        <?php echo renderPaymentStatusBadge($booking['trang_thai_thanh_toan'] ?? 'Chưa thanh toán'); ?>
                    </div>
                </div>

                <!-- Hướng dẫn chuyển khoản VietQR nếu là Chuyển khoản hoặc Momo -->
                <?php if ($booking['phuong_thuc_thanh_toan'] === 'Chuyển khoản' || $booking['phuong_thuc_thanh_toan'] === 'Momo'): ?>
                    <hr class="my-3">
                    <h6 class="fw-bold text-navy mb-2"><i class="bi bi-qr-code-scan text-primary me-1"></i> Quét Mã VietQR Thanh Toán</h6>
                    <p class="small text-muted mb-3">Mở app Ngân hàng hoặc ví để quét mã QR chuyển khoản chính xác nội dung:</p>
                    
                    <div class="text-center p-2 bg-light rounded-3 mb-3 border">
                        <img src="<?php echo e($qrBankUrl); ?>" alt="VietQR Code" class="img-fluid rounded-2" style="max-height: 240px;">
                    </div>

                    <div class="small bg-light p-3 rounded-3">
                        <div><strong>Ngân hàng:</strong> MBBank (Quân Đội)</div>
                        <div><strong>Số tài khoản:</strong> <code>0981122334</code></div>
                        <div><strong>Chủ tài khoản:</strong> BONGDA16H SPORT</div>
                        <div><strong>Số tiền:</strong> <span class="text-danger fw-bold"><?php echo formatCurrency($booking['tong_tien']); ?></span></div>
                        <div><strong>Nội dung CK:</strong> <code class="text-danger fw-bold">BD16H<?php echo $booking['id']; ?></code></div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-light border small mt-3">
                        <i class="bi bi-info-circle me-1"></i> Bạn đã chọn <strong>Thanh toán tiền mặt tại sân</strong>. Vui lòng thanh toán trực tiếp cho ban quản lý cụm sân khi đến nhận sân thi đấu.
                    </div>
                <?php endif; ?>

                <div class="mt-4 pt-3 border-top">
                    <a href="<?php echo BASE_URL; ?>/pages/san-bong.php" class="btn btn-outline-secondary w-100 mb-2">
                        <i class="bi bi-arrow-left me-1"></i> Tiếp tục tìm sân
                    </a>
                    <a href="<?php echo BASE_URL; ?>/pages/lich-su-dat-san.php" class="btn btn-primary-pitch w-100">
                        <i class="bi bi-clock-history me-1"></i> Xem lịch sử đặt sân
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

