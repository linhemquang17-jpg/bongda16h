<?php
/**
 * Trang Đặt Sân Bóng Đá - Đầy đủ quy trình và tính toán trực tiếp
 * Dự án: BongDa16h
 */
$pageTitle = 'Đặt Sân Bóng Đá';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDBConnection();
$currentUser = getCurrentUser();

// Sân được chọn trước nếu có
$selectedPitchId = (int)($_GET['san_bong_id'] ?? 0);
$selectedDate    = sanitize($_GET['ngay_dat'] ?? date('Y-m-d'));
$selectedStart   = sanitize($_GET['gio_bat_dau'] ?? '17:00');
$selectedEnd     = sanitize($_GET['gio_ket_thuc'] ?? '18:30');

// Lấy danh sách tất cả các sân đang Hoạt động kèm thông tin cụm sân
$pitchList = $pdo->query("
    SELECT s.*, c.ten_cum_san, 
           COALESCE(NULLIF(s.dia_chi, ''), c.dia_chi) AS dia_chi, 
           COALESCE(NULLIF(s.link_google_map, ''), c.link_google_map) AS link_google_map,
           l.ten_loai 
    FROM san_bong s
    JOIN cum_san c ON s.cum_san_id = c.id
    JOIN loai_san l ON s.loai_san_id = l.id
    WHERE s.trang_thai = 'Hoạt động'
    ORDER BY c.ten_cum_san ASC, s.ten_san ASC
")->fetchAll();

// Lấy danh sách dịch vụ đi kèm
$services = $pdo->query("SELECT * FROM dich_vu ORDER BY id ASC")->fetchAll();

$extraJs = '<script src="' . BASE_URL . '/assets/js/booking.js"></script>';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php" class="text-decoration-none">Trang chủ</a></li>
            <li class="breadcrumb-item active" aria-current="page">Đặt sân trực tuyến</li>
        </ol>
    </nav>

    <div class="section-header text-start mb-4">
        <span class="section-badge">Thủ tục nhanh gọn</span>
        <h2 class="section-title">Đặt Sân Bóng Đá Trực Tuyến</h2>
        <p class="section-desc text-start">Điền thông tin và lựa chọn khung giờ bạn mong muốn. Hệ thống tự động kiểm tra slot trống và tính chi phí minh bạch.</p>
    </div>

    <form action="<?php echo BASE_URL; ?>/actions/process_booking.php" method="POST" id="bookingForm">
        <?php echo getCsrfField(); ?>

        <div class="row g-4">
            <!-- CỘT TRÁI: CHỌN SÂN & LỊCH ĐÁ -->
            <div class="col-lg-7">
                <!-- 1. CHỌN SÂN BÓNG & KHUNG GIỜ -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <h5 class="fw-bold text-navy mb-3">
                        <span class="badge bg-success rounded-circle me-2">1</span> Thông Tin Sân & Thời Gian
                    </h5>

                    <!-- Chọn sân -->
                    <div class="mb-3">
                        <label for="san_bong_id" class="form-label fw-semibold">Chọn sân bóng <span class="text-danger">*</span></label>
                        <select name="san_bong_id" id="san_bong_id" class="form-select form-select-lg fs-6" required>
                            <option value="">-- Vui lòng chọn sân bóng --</option>
                            <?php foreach ($pitchList as $p): ?>
                                <option value="<?php echo (int)$p['id']; ?>" 
                                    data-price="<?php echo (float)$p['gia_theo_gio']; ?>"
                                    <?php echo $selectedPitchId === (int)$p['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($p['ten_san']); ?> (<?php echo e($p['ten_loai']); ?>) - <?php echo e($p['ten_cum_san']); ?> [<?php echo formatCurrency($p['gia_theo_gio']); ?>/h]
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Chọn ngày đá -->
                    <div class="mb-3">
                        <label for="ngay_dat" class="form-label fw-semibold">Ngày đá <span class="text-danger">*</span></label>
                        <input type="date" name="ngay_dat" id="ngay_dat" class="form-control" 
                               value="<?php echo e($selectedDate); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <!-- Khung giờ đã đặt (Hiển thị qua AJAX) -->
                    <div id="bookedSlotsContainer" class="p-3 bg-light rounded-3 mb-3 d-none">
                        <small class="fw-bold text-muted d-block mb-2"><i class="bi bi-info-circle me-1"></i> Các khung giờ đã có người đặt trong ngày này:</small>
                        <div id="bookedSlotsList" class="d-flex flex-wrap"></div>
                    </div>

                    <!-- Khung giờ bắt đầu & kết thúc -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="gio_bat_dau" class="form-label fw-semibold">Giờ bắt đầu <span class="text-danger">*</span></label>
                            <input type="time" name="gio_bat_dau" id="gio_bat_dau" class="form-control" value="<?php echo e($selectedStart); ?>" step="900" required>
                        </div>
                        <div class="col-md-6">
                            <label for="gio_ket_thuc" class="form-label fw-semibold">Giờ kết thúc <span class="text-danger">*</span></label>
                            <input type="time" name="gio_ket_thuc" id="gio_ket_thuc" class="form-control" value="<?php echo e($selectedEnd); ?>" step="900" required>
                        </div>
                    </div>

                    <!-- Cảnh báo xung đột thời gian -->
                    <div id="timeConflictNotice" class="alert alert-danger mt-3 d-none" role="alert"></div>
                </div>

                <!-- 2. DỊCH VỤ BỔ SUNG -->
                <?php if (!empty($services)): ?>
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                        <h5 class="fw-bold text-navy mb-3">
                            <span class="badge bg-success rounded-circle me-2">2</span> Dịch Vụ Bổ Sung (Tùy chọn)
                        </h5>
                        <p class="text-muted small mb-3">Chuẩn bị sẵn nước uống và áo tập cho đội của bạn khi đến sân:</p>

                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40px;">Chọn</th>
                                        <th>Tên dịch vụ</th>
                                        <th>Đơn giá</th>
                                        <th style="width: 130px;">Số lượng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($services as $svc): ?>
                                        <tr>
                                            <td>
                                                <input class="form-check-input service-checkbox" type="checkbox" 
                                                       id="service_cb_<?php echo (int)$svc['id']; ?>"
                                                       data-id="<?php echo (int)$svc['id']; ?>"
                                                       data-price="<?php echo (float)$svc['don_gia']; ?>"
                                                       value="<?php echo (int)$svc['id']; ?>">
                                            </td>
                                            <td>
                                                <label for="service_cb_<?php echo (int)$svc['id']; ?>" class="fw-semibold mb-0 cursor-pointer">
                                                    <?php echo e($svc['ten_dich_vu']); ?>
                                                </label>
                                                <small class="text-muted d-block"><?php echo e($svc['don_vi_tinh']); ?></small>
                                            </td>
                                            <td class="fw-bold text-success">
                                                <?php echo formatCurrency($svc['don_gia']); ?>
                                            </td>
                                            <td>
                                                <input type="number" 
                                                       name="services[<?php echo (int)$svc['id']; ?>]" 
                                                       id="service_qty_<?php echo (int)$svc['id']; ?>" 
                                                       class="form-control form-control-sm service-qty-input" 
                                                       data-id="<?php echo (int)$svc['id']; ?>"
                                                       data-price="<?php echo (float)$svc['don_gia']; ?>"
                                                       value="1" min="1" max="50" disabled>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- 3. THÔNG TIN KHÁCH HÀNG -->
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold text-navy mb-3">
                        <span class="badge bg-success rounded-circle me-2">3</span> Thông Tin Khách Hàng
                    </h5>

                    <?php if ($currentUser): ?>
                        <div class="alert alert-info d-flex align-items-center mb-0">
                            <i class="bi bi-person-check-fill fs-3 me-3 text-primary"></i>
                            <div>
                                <strong>Đang đặt sân với tài khoản:</strong> <?php echo e($currentUser['ho_ten']); ?><br>
                                <small>Số điện thoại: <?php echo e($currentUser['so_dien_thoai']); ?> | Email: <?php echo e($currentUser['email'] ?? 'Chưa cập nhật'); ?></small>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-3">
                            <i class="bi bi-exclamation-circle me-1"></i> Bạn chưa đăng nhập. Vui lòng nhập thông tin dưới đây để tạo đơn đặt sân (hệ thống sẽ tự động liên kết tài khoản cho bạn).
                            Hoặc <a href="<?php echo BASE_URL; ?>/pages/dang-nhap.php" class="fw-bold text-dark">Đăng nhập tại đây</a> nếu đã có tài khoản.
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="ho_ten" class="form-label fw-semibold">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" name="ho_ten" id="ho_ten" class="form-control" placeholder="Ví dụ: Nguyễn Văn An" required>
                            </div>
                            <div class="col-md-6">
                                <label for="so_dien_thoai" class="form-label fw-semibold">Số điện thoại nhận tin <span class="text-danger">*</span></label>
                                <input type="tel" name="so_dien_thoai" id="so_dien_thoai" class="form-control" placeholder="Ví dụ: 0912345678" pattern="[0-9]{10}" required>
                            </div>
                            <div class="col-12">
                                <label for="email" class="form-label fw-semibold">Địa chỉ Email (tùy chọn)</label>
                                <input type="email" name="email" id="email" class="form-control" placeholder="name@example.com">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- CỘT PHẢI: TÍNH TIỀN & XÁC NHẬN THANH TOÁN -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top: 90px;">
                    <h5 class="fw-bold text-navy mb-3"><i class="bi bi-receipt text-success me-2"></i> Tóm Tắt Chi Phí</h5>

                    <ul class="list-group list-group-flush mb-4">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <span>Tiền thuê sân</span>
                                <small class="text-muted d-block" id="displayPitchDuration">(chưa chọn giờ)</small>
                            </div>
                            <strong class="text-dark" id="displayPitchPrice">0 đ</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Dịch vụ bổ sung</span>
                            <strong class="text-dark" id="displayServiceTotal">0 đ</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-light p-3 rounded-3 mt-2">
                            <span class="fs-5 fw-bold text-navy">TỔNG CỘNG:</span>
                            <span class="fs-3 fw-bold text-danger" id="displayGrandTotal">0 đ</span>
                        </li>
                    </ul>

                    <!-- PHƯƠNG THỨC THANH TOÁN -->
                    <h6 class="fw-bold text-navy mb-3"><i class="bi bi-credit-card text-primary me-2"></i> Phương Thức Thanh Toán</h6>
                    
                    <div class="mb-4">
                        <div class="form-check p-3 border rounded-3 mb-2">
                            <input class="form-check-input" type="radio" name="phuong_thuc_thanh_toan" id="pay_cash" value="Tiền mặt" checked>
                            <label class="form-check-label fw-semibold ms-2" for="pay_cash">
                                <i class="bi bi-cash-stack text-success me-1"></i> Thanh toán tiền mặt tại sân
                                <small class="text-muted d-block fw-normal">Thanh toán trực tiếp cho ban quản lý khi nhận sân</small>
                            </label>
                        </div>

                        <div class="form-check p-3 border rounded-3 mb-2">
                            <input class="form-check-input" type="radio" name="phuong_thuc_thanh_toan" id="pay_bank" value="Chuyển khoản">
                            <label class="form-check-label fw-semibold ms-2" for="pay_bank">
                                <i class="bi bi-qr-code text-primary me-1"></i> Chuyển khoản VietQR nhanh
                                <small class="text-muted d-block fw-normal">Quét mã QR qua app ngân hàng tức thì</small>
                            </label>
                        </div>

                        <div class="form-check p-3 border rounded-3 mb-2">
                            <input class="form-check-input" type="radio" name="phuong_thuc_thanh_toan" id="pay_momo" value="Momo">
                            <label class="form-check-label fw-semibold ms-2" for="pay_momo">
                                <i class="bi bi-wallet2 text-danger me-1"></i> Ví điện tử MoMo
                            </label>
                        </div>

                        <div class="form-check p-3 border rounded-3">
                            <input class="form-check-input" type="radio" name="phuong_thuc_thanh_toan" id="pay_vnpay" value="VNPay">
                            <label class="form-check-label fw-semibold ms-2" for="pay_vnpay">
                                <i class="bi bi-credit-card-2-front text-info me-1"></i> Cổng thanh toán VNPAY
                            </label>
                        </div>
                    </div>

                    <button type="submit" id="submitBookingBtn" class="btn btn-primary-pitch w-100 py-3 fs-5 fw-bold shadow">
                        <i class="bi bi-check2-circle me-1"></i> XÁC NHẬN ĐẶT SÂN
                    </button>

                    <p class="text-center text-muted small mt-3 mb-0">
                        <i class="bi bi-shield-lock me-1"></i> Thông tin giao dịch được bảo mật tuyệt đối
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

