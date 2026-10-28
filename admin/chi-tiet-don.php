<?php
/**
 * Chi tiết đơn đặt sân & Cập nhật trạng thái - Admin
 * Dự án: BongDa16h
 * Tương thích: Localhost XAMPP & Hosting InfinityFree
 */
$pageTitle = 'Chi Tiết Đơn Đặt Sân';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/auth_check.php';

// Xác thực tham số ID là số nguyên dương hợp lệ
$rawId = $_GET['id'] ?? null;
$bookingId = filter_var($rawId, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

$pdo = getDBConnection();
$booking = null;
$bookedServices = [];
$pitchFee = 0;
$calcTime = ['valid' => false, 'hours' => 0, 'duration_minutes' => 0, 'total' => 0];
$dbError = null;

// Xử lý cập nhật trạng thái đơn và thanh toán qua POST (Prepared Statements & Transaction)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $bookingId) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn!');
        header('Location: ' . BASE_URL . '/admin/chi-tiet-don.php?id=' . $bookingId);
        exit;
    }

    $trangThaiDat = sanitize($_POST['trang_thai_dat'] ?? '');
    $trangThaiTT  = sanitize($_POST['trang_thai_thanh_toan'] ?? '');
    $phuongThucTT = sanitize($_POST['phuong_thuc_thanh_toan'] ?? '');

    $validDat = ['Chờ xác nhận', 'Đã xác nhận', 'Đã hoàn thành', 'Đã hủy'];
    $validTT  = ['Chưa thanh toán', 'Đã đặt cọc', 'Đã thanh toán đủ'];
    $validPT  = ['Tiền mặt', 'Chuyển khoản', 'Momo', 'VNPay'];

    try {
        $pdo->beginTransaction();

        if (in_array($trangThaiDat, $validDat)) {
            $stmt = $pdo->prepare("UPDATE dat_san SET trang_thai_dat = ? WHERE id = ?");
            $stmt->execute([$trangThaiDat, $bookingId]);
        }

        if (in_array($trangThaiTT, $validTT) && in_array($phuongThucTT, $validPT)) {
            // Kiểm tra xem đã có bản ghi thanh toán cho đơn này chưa
            $chkPay = $pdo->prepare("SELECT id FROM thanh_toan WHERE dat_san_id = ?");
            $chkPay->execute([$bookingId]);
            $existingPayId = $chkPay->fetchColumn();

            if ($existingPayId) {
                $stmtPay = $pdo->prepare("
                    UPDATE thanh_toan 
                    SET trang_thai_thanh_toan = ?, phuong_thuc_thanh_toan = ? 
                    WHERE dat_san_id = ?
                ");
                $stmtPay->execute([$trangThaiTT, $phuongThucTT, $bookingId]);
            } else {
                // Lấy tổng tiền đơn để khởi tạo thanh toán nếu chưa có
                $bTongTien = (float)$pdo->query("SELECT tong_tien FROM dat_san WHERE id = {$bookingId}")->fetchColumn();
                $stmtPay = $pdo->prepare("
                    INSERT INTO thanh_toan (dat_san_id, phuong_thuc_thanh_toan, so_tien, trang_thai_thanh_toan)
                    VALUES (?, ?, ?, ?)
                ");
                $stmtPay->execute([$bookingId, $phuongThucTT, $bTongTien, $trangThaiTT]);
            }
        }

        $pdo->commit();
        setFlash('success', "Cập nhật đơn đặt sân #BD16H{$bookingId} thành công!");
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        setFlash('danger', 'Lỗi cập nhật: ' . $e->getMessage());
    }

    header('Location: ' . BASE_URL . '/admin/chi-tiet-don.php?id=' . $bookingId);
    exit;
}

// Truy vấn thông tin đơn đặt sân với cơ chế bảo vệ Try-Catch và LEFT JOIN
if ($bookingId) {
    try {
        $stmt = $pdo->prepare("
            SELECT d.*, 
                   u.ho_ten as ten_khach, u.so_dien_thoai as sdt_khach, u.email as email_khach,
                   s.ten_san, s.gia_theo_gio,
                   c.ten_cum_san, c.dia_chi, c.so_dien_thoai_lien_he,
                   l.ten_loai,
                   t.phuong_thuc_thanh_toan, t.so_tien as so_tien_thanh_toan, t.trang_thai_thanh_toan, t.ngay_thanh_toan
            FROM dat_san d
            LEFT JOIN nguoi_dung u ON d.nguoi_dung_id = u.id
            LEFT JOIN san_bong s ON d.san_bong_id = s.id
            LEFT JOIN cum_san c ON s.cum_san_id = c.id
            LEFT JOIN loai_san l ON s.loai_san_id = l.id
            LEFT JOIN thanh_toan t ON d.id = t.dat_san_id
            WHERE d.id = ?
            LIMIT 1
        ");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();

        if ($booking) {
            // Lấy danh sách dịch vụ kèm theo
            $svcStmt = $pdo->prepare("
                SELECT cd.*, v.ten_dich_vu, v.don_vi_tinh 
                FROM chi_tiet_dich_vu cd
                LEFT JOIN dich_vu v ON cd.dich_vu_id = v.id
                WHERE cd.dat_san_id = ?
            ");
            $svcStmt->execute([$bookingId]);
            $bookedServices = $svcStmt->fetchAll();

            // Tính tiền sân an toàn
            $calcTime = calculatePitchPrice(
                (float)($booking['gia_theo_gio'] ?? 0), 
                $booking['gio_bat_dau'] ?? '', 
                $booking['gio_ket_thuc'] ?? ''
            );
            $pitchFee = $calcTime['valid'] ? $calcTime['total'] : 0;
        }
    } catch (Throwable $e) {
        $dbError = $e->getMessage();
    }
}

require_once __DIR__ . '/header.php';
?>

<!-- TRƯỜNG HỢP 1: LỖI KẾT NỐI DATABASE HOẶC TRUY VẤN -->
<?php if (!empty($dbError)): ?>
    <div class="alert alert-danger border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="d-flex align-items-center mb-2">
            <i class="bi bi-exclamation-octagon-fill text-danger fs-3 me-2"></i>
            <h5 class="fw-bold mb-0">Lỗi Truy Vấn Cơ Sở Dữ Liệu</h5>
        </div>
        <p class="text-muted small mb-2">Không thể tải thông tin đơn đặt sân do lỗi từ phía máy chủ cơ sở dữ liệu:</p>
        <code class="d-block p-2 bg-light rounded text-danger mb-3"><?php echo e($dbError); ?></code>
        <div class="d-flex gap-2">
            <a href="<?php echo BASE_URL; ?>/admin/don-dat-san.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Quay lại danh sách đơn
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/diagnostics.php" class="btn btn-primary btn-sm">
                <i class="bi bi-tools me-1"></i> Kiểm tra cấu trúc database
            </a>
        </div>
    </div>
    <?php require_once __DIR__ . '/footer.php'; exit; ?>
<?php endif; ?>

<!-- TRƯỜNG HỢP 2: ĐƠN ĐẶT SÂN KHÔNG TỒN TẠI (HIỂN THỊ THÔNG BÁO DỄ HIỂU THAY VÌ LỖI 500) -->
<?php if (!$booking): ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center my-4 bg-white">
        <div class="mb-3">
            <span class="d-inline-flex p-3 rounded-circle bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-search fs-1"></i>
            </span>
        </div>
        <h4 class="fw-bold text-navy mb-2">Không Tìm Thấy Đơn Đặt Sân</h4>
        <p class="text-muted mb-4" style="max-width: 520px; margin: 0 auto;">
            Đơn đặt sân mã <strong>#BD16H<?php echo (int)($bookingId ?: $rawId); ?></strong> không tồn tại trên hệ thống hoặc đã được xóa trước đó.
        </p>
        <div class="d-flex justify-content-center gap-2">
            <a href="<?php echo BASE_URL; ?>/admin/don-dat-san.php" class="btn btn-primary-pitch px-4 py-2">
                <i class="bi bi-card-checklist me-1"></i> Danh Sách Đơn Đặt Sân
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/index.php" class="btn btn-outline-secondary px-4 py-2">
                <i class="bi bi-speedometer2 me-1"></i> Về Dashboard Tổng Quan
            </a>
        </div>
    </div>
    <?php require_once __DIR__ . '/footer.php'; exit; ?>
<?php endif; ?>

<!-- TRƯỜNG HỢP 3: HIỂN THỊ CHI TIẾT ĐƠN HỢP LỆ -->
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold text-navy mb-1">Chi Tiết Đơn Đặt Sân #BD16H<?php echo (int)$booking['id']; ?></h3>
        <p class="text-muted mb-0">Ngày tạo: <?php echo formatDateTime($booking['created_at']); ?></p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i> In hóa đơn
        </button>
        <a href="<?php echo BASE_URL; ?>/admin/don-dat-san.php" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Quay lại danh sách
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <!-- Thông tin thi đấu & bảng chi phí -->
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <span><i class="bi bi-calendar2-check text-success me-2"></i> Lịch Thi Đấu & Sân Bóng</span>
            </div>
            <div class="admin-card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Sân bóng:</small>
                        <h5 class="fw-bold text-primary mb-1">
                            <?php echo e($booking['ten_san'] ?? 'Sân bóng không xác định'); ?> 
                            (<?php echo e($booking['ten_loai'] ?? 'Tiêu chuẩn'); ?>)
                        </h5>
                        <p class="text-muted small mb-0">
                            <i class="bi bi-geo-alt-fill text-danger me-1"></i> 
                            <?php echo e($booking['ten_cum_san'] ?? 'Cụm sân'); ?> - <?php echo e($booking['dia_chi'] ?? 'Địa chỉ đang cập nhật'); ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Thời gian thi đấu:</small>
                        <div class="fs-5 fw-bold text-success mb-1">
                            <?php echo substr($booking['gio_bat_dau'] ?? '', 0, 5); ?> - <?php echo substr($booking['gio_ket_thuc'] ?? '', 0, 5); ?>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-calendar3 me-1"></i> Ngày: <strong><?php echo formatDate($booking['ngay_dat']); ?></strong> 
                            (<?php echo $calcTime['hours'] ?? 0; ?> giờ)
                        </small>
                    </div>
                </div>

                <h6 class="fw-bold text-navy mb-3">Chi Tiết Bảng Giá & Dịch Vụ:</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Khoản mục</th>
                                <th class="text-center">Số lượng / Giờ</th>
                                <th class="text-end">Đơn giá</th>
                                <th class="text-end">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Tiền thuê sân: <?php echo e($booking['ten_san'] ?? 'Sân bóng'); ?></td>
                                <td class="text-center"><?php echo $calcTime['hours'] ?? 0; ?> giờ</td>
                                <td class="text-end"><?php echo formatCurrency($booking['gia_theo_gio'] ?? 0); ?></td>
                                <td class="text-end fw-semibold"><?php echo formatCurrency($pitchFee); ?></td>
                            </tr>
                            <?php 
                            $serviceSum = 0;
                            foreach ($bookedServices as $svc): 
                                $sub = (float)($svc['don_gia'] ?? 0) * (int)($svc['so_luong'] ?? 1);
                                $serviceSum += $sub;
                            ?>
                                <tr>
                                    <td>Dịch vụ: <?php echo e($svc['ten_dich_vu'] ?? 'Dịch vụ kèm theo'); ?> (<?php echo e($svc['don_vi_tinh'] ?? 'Phần'); ?>)</td>
                                    <td class="text-center"><?php echo (int)($svc['so_luong'] ?? 1); ?></td>
                                    <td class="text-end"><?php echo formatCurrency($svc['don_gia'] ?? 0); ?></td>
                                    <td class="text-end fw-semibold"><?php echo formatCurrency($sub); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-light fs-5">
                                <th colspan="3" class="text-end fw-bold">TỔNG CỘNG:</th>
                                <th class="text-end fw-bold text-danger"><?php echo formatCurrency($booking['tong_tien']); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- CỘT PHẢI: KHÁCH HÀNG & FORM CẬP NHẬT TRẠNG THÁI -->
    <div class="col-lg-4">
        <!-- Thông tin khách hàng -->
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <span><i class="bi bi-person text-primary me-2"></i> Khách Hàng</span>
            </div>
            <div class="admin-card-body">
                <div class="mb-2"><strong>Họ tên:</strong> <?php echo e($booking['ten_khach'] ?? 'Khách vãng lai'); ?></div>
                <div class="mb-2"><strong>Số điện thoại:</strong> <a href="tel:<?php echo e($booking['sdt_khach'] ?? ''); ?>"><?php echo e($booking['sdt_khach'] ?? 'Chưa có'); ?></a></div>
                <div class="mb-2"><strong>Email:</strong> <?php echo e($booking['email_khach'] ?? 'Chưa có'); ?></div>
                <div class="mb-0"><strong>Trạng thái đơn:</strong> <?php echo renderBookingStatusBadge($booking['trang_thai_dat']); ?></div>
            </div>
        </div>

        <!-- Form cập nhật trạng thái đơn & thanh toán -->
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="bi bi-sliders text-warning me-2"></i> Cập Nhật Trạng Thái</span>
            </div>
            <div class="admin-card-body">
                <form method="POST">
                    <?php echo getCsrfField(); ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Trạng thái đơn đặt sân</label>
                        <select name="trang_thai_dat" class="form-select">
                            <option value="Chờ xác nhận" <?php echo $booking['trang_thai_dat'] === 'Chờ xác nhận' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                            <option value="Đã xác nhận" <?php echo $booking['trang_thai_dat'] === 'Đã xác nhận' ? 'selected' : ''; ?>>Đã xác nhận</option>
                            <option value="Đã hoàn thành" <?php echo $booking['trang_thai_dat'] === 'Đã hoàn thành' ? 'selected' : ''; ?>>Đã hoàn thành</option>
                            <option value="Đã hủy" <?php echo $booking['trang_thai_dat'] === 'Đã hủy' ? 'selected' : ''; ?>>Đã hủy</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tình trạng thanh toán</label>
                        <select name="trang_thai_thanh_toan" class="form-select">
                            <option value="Chưa thanh toán" <?php echo ($booking['trang_thai_thanh_toan'] ?? '') === 'Chưa thanh toán' ? 'selected' : ''; ?>>Chưa thanh toán</option>
                            <option value="Đã đặt cọc" <?php echo ($booking['trang_thai_thanh_toan'] ?? '') === 'Đã đặt cọc' ? 'selected' : ''; ?>>Đã đặt cọc</option>
                            <option value="Đã thanh toán đủ" <?php echo ($booking['trang_thai_thanh_toan'] ?? '') === 'Đã thanh toán đủ' ? 'selected' : ''; ?>>Đã thanh toán đủ</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Phương thức thanh toán</label>
                        <select name="phuong_thuc_thanh_toan" class="form-select">
                            <option value="Tiền mặt" <?php echo ($booking['phuong_thuc_thanh_toan'] ?? '') === 'Tiền mặt' ? 'selected' : ''; ?>>Tiền mặt</option>
                            <option value="Chuyển khoản" <?php echo ($booking['phuong_thuc_thanh_toan'] ?? '') === 'Chuyển khoản' ? 'selected' : ''; ?>>Chuyển khoản</option>
                            <option value="Momo" <?php echo ($booking['phuong_thuc_thanh_toan'] ?? '') === 'Momo' ? 'selected' : ''; ?>>Momo</option>
                            <option value="VNPay" <?php echo ($booking['phuong_thuc_thanh_toan'] ?? '') === 'VNPay' ? 'selected' : ''; ?>>VNPay</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary-pitch w-100 py-2">
                        <i class="bi bi-save me-1"></i> Lưu Cập Nhật
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
