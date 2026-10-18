<?php
/**
 * Chi tiết sân bóng đá - Bộ sưu tập ảnh, thông tin, Google Maps, lịch đặt sân & Tìm đối
 * Dự án: BongDa16h
 * Tương thích: Hosting InfinityFree & Localhost XAMPP
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDBConnection();
$pitchId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pitch = null;
$errorMsg = null;

if ($pitchId > 0) {
    try {
        // Lấy thông tin sân, cụm sân và loại sân an toàn với tham số truyền vào
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   c.ten_cum_san, 
                   COALESCE(NULLIF(s.dia_chi, ''), c.dia_chi) AS dia_chi, 
                   c.kinh_do, c.vi_do, 
                   COALESCE(NULLIF(s.link_google_map, ''), c.link_google_map) AS link_google_map, 
                   c.gio_mo_cua, c.gio_dong_cua, c.so_dien_thoai_lien_he,
                   l.ten_loai, l.gia_co_ban_thang_gio
            FROM san_bong s
            JOIN cum_san c ON s.cum_san_id = c.id
            JOIN loai_san l ON s.loai_san_id = l.id
            WHERE s.id = ?
            LIMIT 1
        ");
        $stmt->execute([$pitchId]);
        $pitch = $stmt->fetch();
    } catch (Exception $e) {
        error_log('Error loading pitch detail: ' . $e->getMessage());
        $errorMsg = 'Lỗi truy xuất cơ sở dữ liệu. Vui lòng thử lại sau.';
    }
}

// Nếu không tìm thấy sân bóng hoặc ID không hợp lệ -> Hiển thị trang thông báo thân thiện (tránh lỗi 500)
if (!$pitch) {
    $pageTitle = 'Không tìm thấy sân bóng';
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <div class="container py-5 text-center">
        <div class="card border-0 shadow-sm rounded-4 p-5 mx-auto" style="max-width: 600px;">
            <div class="display-1 text-warning mb-3">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <h3 class="fw-bold text-navy mb-2">Không tìm thấy sân bóng</h3>
            <p class="text-muted mb-4">
                <?php echo $errorMsg ? e($errorMsg) : 'Sân bóng bạn đang tìm kiếm không tồn tại (ID: ' . (int)$pitchId . ') hoặc đã ngừng hoạt động.'; ?>
            </p>
            <div class="d-flex justify-content-center gap-3">
                <a href="<?php echo BASE_URL; ?>/pages/san-bong.php" class="btn btn-primary-pitch px-4 py-2">
                    <i class="bi bi-grid me-1"></i> Xem danh sách tất cả sân
                </a>
                <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-outline-secondary px-4 py-2">
                    <i class="bi bi-house me-1"></i> Về trang chủ
                </a>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$pageTitle = $pitch['ten_san'];

// Lấy bộ sưu tập ảnh của sân và cụm sân (đã tối ưu không gây lỗi 500)
$gallery = getPitchGallery($pitch['id'], $pitch['cum_san_id'], $pdo);

// Lấy ngày cần xem lịch (mặc định hôm nay)
$viewDate = sanitize($_GET['date'] ?? date('Y-m-d'));
$bookedSlots = getBookedSlots($pitch['id'], $viewDate, $pdo);

// Link Google Maps
$mapUrl = getGoogleMapUrl($pitch);

// Lấy danh sách các đội đang tìm đối quanh sân này
$nearbyMatches = [];
try {
    $matchStmt = $pdo->prepare("
        SELECT * FROM tim_doi_bong 
        WHERE (san_bong_id = ? OR cum_san_id = ?) 
          AND trang_thai = 'Đang tìm đối'
          AND ngay_da >= CURDATE()
        ORDER BY ngay_da ASC, gio_da ASC
        LIMIT 4
    ");
    $matchStmt->execute([$pitch['id'], $pitch['cum_san_id']]);
    $nearbyMatches = $matchStmt->fetchAll();
} catch (Exception $e) {
    // Bảng tim_doi_bong chưa có hoặc lỗi nhẹ, bỏ qua không làm crash trang
    $nearbyMatches = [];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php" class="text-decoration-none">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/san-bong.php" class="text-decoration-none">Danh sách sân</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo e($pitch['ten_san']); ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- BỘ SƯU TẬP ẢNH & CHI TIẾT SÂN -->
        <div class="col-lg-8">
            <!-- Carousel Bộ Sưu Tập Ảnh -->
            <div id="pitchCarousel" class="carousel slide rounded-4 overflow-hidden shadow-sm mb-4" data-bs-ride="carousel">
                <div class="carousel-indicators">
                    <?php foreach ($gallery as $index => $img): ?>
                        <button type="button" data-bs-target="#pitchCarousel" data-bs-slide-to="<?php echo $index; ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>" aria-current="<?php echo $index === 0 ? 'true' : 'false'; ?>"></button>
                    <?php endforeach; ?>
                </div>
                <div class="carousel-inner" style="height: 420px; background-color: #0f172a;">
                    <?php foreach ($gallery as $index => $img): ?>
                        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?> h-100">
                            <img src="<?php echo e($img['url_hinh_anh']); ?>" class="d-block w-100 h-100" style="object-fit: cover;" alt="Hình ảnh sân bóng">
                            <?php if (!empty($img['la_anh_chinh'])): ?>
                                <div class="carousel-caption d-none d-md-block text-start">
                                    <span class="badge bg-success px-3 py-2"><i class="bi bi-star-fill me-1"></i> Ảnh tiêu biểu</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($gallery) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#pitchCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Trước</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#pitchCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Sau</span>
                    </button>
                <?php endif; ?>
            </div>

            <!-- THÔNG TIN CHI TIẾT -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="badge bg-primary-subtle text-primary border border-primary px-3 py-2 rounded-pill fs-6">
                        <i class="bi bi-people-fill me-1"></i> <?php echo e($pitch['ten_loai']); ?>
                    </span>
                    <div>
                        <?php echo renderPitchStatusBadge($pitch['trang_thai']); ?>
                    </div>
                </div>

                <h2 class="fw-bold text-navy mb-3"><?php echo e($pitch['ten_san']); ?></h2>

                <div class="mb-3 text-muted">
                    <i class="bi bi-geo-alt-fill text-danger me-2"></i>
                    <strong>Cụm sân:</strong> <?php echo e($pitch['ten_cum_san']); ?> - <?php echo e($pitch['dia_chi']); ?>
                </div>

                <div class="row g-3 mb-4 py-3 bg-light rounded-3 text-center">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Giá thuê sân</small>
                        <span class="fs-4 fw-bold text-success"><?php echo formatCurrency($pitch['gia_theo_gio']); ?></span>
                        <small class="text-muted">/ giờ</small>
                    </div>
                    <div class="col-md-4 border-start border-end">
                        <small class="text-muted d-block">Giờ mở cửa cụm sân</small>
                        <span class="fs-5 fw-bold text-dark"><?php echo substr($pitch['gio_mo_cua'] ?? '06:00', 0, 5); ?> - <?php echo substr($pitch['gio_dong_cua'] ?? '22:00', 0, 5); ?></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Hotline đặt sân</small>
                        <span class="fs-5 fw-bold text-primary"><?php echo e($pitch['so_dien_thoai_lien_he'] ?? '0981 122 334'); ?></span>
                    </div>
                </div>

                <?php if (!empty($pitch['ghi_chu'])): ?>
                    <h5 class="fw-bold mb-2">Đặc điểm & Tiện ích sân:</h5>
                    <p class="text-secondary"><?php echo nl2br(e($pitch['ghi_chu'])); ?></p>
                <?php endif; ?>

                <!-- CÁC NÚT THAO TÁC CHÍNH -->
                <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
                    <a href="<?php echo BASE_URL; ?>/pages/dat-san.php?san_bong_id=<?php echo (int)$pitch['id']; ?>" class="btn btn-primary-pitch px-4 py-2">
                        <i class="bi bi-calendar-check me-1"></i> Đặt sân này ngay
                    </a>
                    <a href="<?php echo BASE_URL; ?>/pages/tim-doi-bong.php?san_id=<?php echo (int)$pitch['id']; ?>&cum_id=<?php echo (int)$pitch['cum_san_id']; ?>" class="btn btn-success px-4 py-2 fw-semibold">
                        <i class="bi bi-people-fill me-1"></i> ⚽ Tìm đối quanh sân này
                    </a>
                    <a href="<?php echo e($mapUrl); ?>" target="_blank" class="btn btn-outline-danger px-4 py-2">
                        <i class="bi bi-geo-alt-fill me-1"></i> Xem Google Maps
                    </a>
                </div>
            </div>

            <!-- DANH SÁCH ĐỘI ĐANG TÌM ĐỐI QUANH SÂN NÀY -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 pb-2 border-bottom">
                    <h5 class="fw-bold mb-0 text-navy">
                        <i class="bi bi-trophy-fill text-warning me-2"></i> ⚽ Các Đội Đang Tìm Đối Quanh Sân Này
                    </h5>
                    <a href="<?php echo BASE_URL; ?>/pages/tim-doi-bong.php?san_id=<?php echo (int)$pitch['id']; ?>&cum_id=<?php echo (int)$pitch['cum_san_id']; ?>" class="small text-success text-decoration-none fw-semibold">
                        Xem tất cả <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <?php if (empty($nearbyMatches)): ?>
                    <div class="p-3 bg-light rounded-3 text-center">
                        <p class="text-muted mb-2">Chưa có đội bóng nào đăng tin tìm đối tại sân này trong những ngày tới.</p>
                        <a href="<?php echo BASE_URL; ?>/pages/tim-doi-bong.php?san_id=<?php echo (int)$pitch['id']; ?>&cum_id=<?php echo (int)$pitch['cum_san_id']; ?>" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-plus-circle me-1"></i> Đăng tin tìm đối tại sân này ngay
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($nearbyMatches as $m): ?>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-white h-100 d-flex flex-column justify-content-between shadow-sm">
                                    <div>
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="badge bg-success-subtle text-success border border-success"><?php echo e($m['loai_san']); ?></span>
                                            <?php echo renderMatchSkillBadge($m['trinh_do']); ?>
                                        </div>
                                        <h6 class="fw-bold text-navy mb-1"><?php echo e($m['ten_doi']); ?></h6>
                                        <small class="text-muted d-block mb-2">
                                            <i class="bi bi-calendar-event me-1 text-primary"></i> <?php echo formatDate($m['ngay_da']); ?> lúc <?php echo substr($m['gio_da'], 0, 5); ?> (<?php echo (int)$m['thoi_luong']; ?>p)
                                        </small>
                                        <?php if (!empty($m['mo_ta'])): ?>
                                            <p class="small text-muted mb-2 text-truncate"><?php echo e($m['mo_ta']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between pt-2 border-top mt-2">
                                        <small class="text-secondary fw-semibold"><i class="bi bi-person me-1"></i> <?php echo e($m['nguoi_lien_he']); ?></small>
                                        <a href="tel:<?php echo e($m['so_dien_thoai']); ?>" class="btn btn-sm btn-outline-success py-1 px-2">
                                            <i class="bi bi-telephone-fill"></i> Gọi ngay
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- LỊCH ĐẶT SÂN THEO NGÀY -->
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 pb-2 border-bottom">
                    <h5 class="fw-bold mb-0 text-navy"><i class="bi bi-calendar3 text-success me-2"></i> Lịch Đặt Sân Theo Ngày</h5>
                    
                    <!-- Form đổi ngày -->
                    <form method="GET" class="d-flex align-items-center gap-2 mt-2 mt-sm-0">
                        <input type="hidden" name="id" value="<?php echo (int)$pitch['id']; ?>">
                        <label class="small text-muted fw-semibold">Xem ngày:</label>
                        <input type="date" name="date" class="form-control form-control-sm" value="<?php echo e($viewDate); ?>" onchange="this.form.submit()">
                    </form>
                </div>

                <p class="small text-muted mb-3">
                    Ngày đang xem: <strong><?php echo formatDate($viewDate); ?></strong>. Các khung giờ dưới đây đã có người đặt, quý khách vui lòng chọn các khung giờ còn lại khi đặt sân.
                </p>

                <?php if (empty($bookedSlots)): ?>
                    <div class="alert alert-success d-flex align-items-center mb-0">
                        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                        <div>Hôm nay sân chưa có lịch đặt nào. Tất cả các khung giờ từ <strong><?php echo substr($pitch['gio_mo_cua'] ?? '06:00', 0, 5); ?></strong> đến <strong><?php echo substr($pitch['gio_dong_cua'] ?? '22:00', 0, 5); ?></strong> đều đang trống!</div>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-wrap">
                        <?php foreach ($bookedSlots as $slot): ?>
                            <div class="timeline-slot-badge slot-booked">
                                <i class="bi bi-clock-fill me-1"></i>
                                <?php echo substr($slot['gio_bat_dau'], 0, 5); ?> - <?php echo substr($slot['gio_ket_thuc'], 0, 5); ?>
                                <span class="badge bg-secondary ms-1"><?php echo e($slot['trang_thai_dat']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- CỘT BÊN PHẢI: ĐẶT SÂN NHANH -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top: 90px;">
                <h5 class="fw-bold mb-3 text-navy"><i class="bi bi-lightning-charge-fill text-warning me-1"></i> Đặt Sân Nhanh</h5>
                
                <form action="<?php echo BASE_URL; ?>/pages/dat-san.php" method="GET">
                    <input type="hidden" name="san_bong_id" value="<?php echo (int)$pitch['id']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Sân đã chọn</label>
                        <input type="text" class="form-control" value="<?php echo e($pitch['ten_san']); ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Đơn giá</label>
                        <input type="text" class="form-control fw-bold text-success" value="<?php echo formatCurrency($pitch['gia_theo_gio']); ?> / giờ" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Chọn ngày đá</label>
                        <input type="date" name="ngay_dat" class="form-control" value="<?php echo e($viewDate); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted">Giờ bắt đầu</label>
                            <input type="time" name="gio_bat_dau" class="form-control" value="17:00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted">Giờ kết thúc</label>
                            <input type="time" name="gio_ket_thuc" class="form-control" value="18:30" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-pitch w-100 py-2 fs-6 mb-2">
                        Tiếp tục đặt sân <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                    
                    <a href="<?php echo BASE_URL; ?>/pages/tim-doi-bong.php?san_id=<?php echo (int)$pitch['id']; ?>" class="btn btn-outline-success w-100 py-2 fs-6">
                        <i class="bi bi-people-fill me-1"></i> Tìm đối quanh sân này
                    </a>
                </form>

                <hr class="my-4">

                <div class="small text-muted">
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-shield-check text-success fs-5 me-2"></i>
                        <span>Cam kết giữ sân 100% khi xác nhận</span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-arrow-repeat text-primary fs-5 me-2"></i>
                        <span>Hỗ trợ hủy đơn linh hoạt trước 2 tiếng</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-headset text-warning fs-5 me-2"></i>
                        <span>Hỗ trợ hotline: <?php echo e($pitch['so_dien_thoai_lien_he'] ?? '0981 122 334'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
