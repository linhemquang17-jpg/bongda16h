<?php
/**
 * Chi tiết sân bóng đá - Bộ sưu tập ảnh, thông tin, Google Maps, lịch đặt sân
 * Dự án: BongDa16h
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDBConnection();
$pitchId = (int)($_GET['id'] ?? 0);

if (!$pitchId) {
    header('Location: ' . BASE_URL . '/pages/san-bong.php');
    exit;
}

// Lấy thông tin sân, cụm sân và loại sân
$stmt = $pdo->prepare("
    SELECT s.*, 
           c.ten_cum_san, c.dia_chi, c.kinh_do, c.vi_do, c.link_google_map, 
           c.gio_mo_cua, c.gio_dong_cua, c.so_dien_thoai_lien_he,
           l.ten_loai, l.gia_co_ban_thang_gio
    FROM san_bong s
    JOIN cum_san c ON s.cum_san_id = c.id
    JOIN loai_san l ON s.loai_san_id = l.id
    WHERE s.id = ?
");
$stmt->execute([$pitchId]);
$pitch = $stmt->fetch();

if (!$pitch) {
    setFlash('danger', 'Sân bóng không tồn tại!');
    header('Location: ' . BASE_URL . '/pages/san-bong.php');
    exit;
}

$pageTitle = $pitch['ten_san'];

// Lấy bộ sưu tập ảnh của sân và cụm sân
$gallery = getPitchGallery($pitch['id'], $pitch['cum_san_id'], $pdo);

// Lấy ngày cần xem lịch (mặc định hôm nay)
$viewDate = sanitize($_GET['date'] ?? date('Y-m-d'));
$bookedSlots = getBookedSlots($pitch['id'], $viewDate, $pdo);

// Link Google Maps
$mapUrl = getGoogleMapUrl($pitch);

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
                        <span class="fs-5 fw-bold text-dark"><?php echo substr($pitch['gio_mo_cua'], 0, 5); ?> - <?php echo substr($pitch['gio_dong_cua'], 0, 5); ?></span>
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

                <div class="d-flex gap-3 mt-3">
                    <a href="<?php echo e($mapUrl); ?>" target="_blank" class="btn btn-outline-danger px-4">
                        <i class="bi bi-geo-alt-fill me-1"></i> Xem đường đi Google Maps
                    </a>
                    <a href="<?php echo BASE_URL; ?>/pages/dat-san.php?san_bong_id=<?php echo (int)$pitch['id']; ?>" class="btn btn-primary-pitch px-4">
                        <i class="bi bi-calendar-check me-1"></i> Đặt sân này ngay
                    </a>
                </div>
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
                        <div>Hôm nay sân chưa có lịch đặt nào. Tất cả các khung giờ từ <strong><?php echo substr($pitch['gio_mo_cua'], 0, 5); ?></strong> đến <strong><?php echo substr($pitch['gio_dong_cua'], 0, 5); ?></strong> đều đang trống!</div>
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

                    <button type="submit" class="btn btn-primary-pitch w-100 py-2 fs-6">
                        Tiếp tục đặt sân <i class="bi bi-arrow-right ms-1"></i>
                    </button>
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

