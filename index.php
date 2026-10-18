<?php
/**
 * Trang chủ Website Đặt Sân Bóng Đá BongDa16h
 */
$pageTitle = 'Trang Chủ';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = getDBConnection();

// Lấy danh sách cụm sân
$cumSanList = $pdo->query("SELECT * FROM cum_san ORDER BY id ASC")->fetchAll();

// Lấy danh sách loại sân
$loaiSanList = $pdo->query("SELECT * FROM loai_san ORDER BY id ASC")->fetchAll();

// Lấy danh sách sân bóng nổi bật (đang Hoạt động)
$featuredPitches = $pdo->query("
    SELECT s.*, c.ten_cum_san, c.dia_chi, l.ten_loai 
    FROM san_bong s
    JOIN cum_san c ON s.cum_san_id = c.id
    JOIN loai_san l ON s.loai_san_id = l.id
    WHERE s.trang_thai = 'Hoạt động'
    ORDER BY s.id ASC
    LIMIT 6
")->fetchAll();

// Lấy danh sách sân 5 người
$pitches5 = $pdo->query("
    SELECT s.*, c.ten_cum_san, c.dia_chi, l.ten_loai 
    FROM san_bong s
    JOIN cum_san c ON s.cum_san_id = c.id
    JOIN loai_san l ON s.loai_san_id = l.id
    WHERE s.loai_san_id = 1 AND s.trang_thai = 'Hoạt động'
    ORDER BY s.id ASC
")->fetchAll();

// Lấy danh sách sân 7 người
$pitches7 = $pdo->query("
    SELECT s.*, c.ten_cum_san, c.dia_chi, l.ten_loai 
    FROM san_bong s
    JOIN cum_san c ON s.cum_san_id = c.id
    JOIN loai_san l ON s.loai_san_id = l.id
    WHERE s.loai_san_id = 2 AND s.trang_thai = 'Hoạt động'
    ORDER BY s.id ASC
")->fetchAll();

// Lấy danh sách dịch vụ đi kèm
$dichVuList = $pdo->query("SELECT * FROM dich_vu ORDER BY id ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- HERO SECTION -->
<section class="hero-section text-center">
    <div class="container">
        <span class="badge bg-success-subtle text-success border border-success px-3 py-2 rounded-pill mb-3 fw-bold">
            <i class="bi bi-fire me-1"></i> Nền tảng đặt sân bóng đá hàng đầu
        </span>
        <h1 class="hero-title">ĐẶT SÂN BÓNG ĐÁ NHANH CHÓNG<br><span class="text-success">THỎA SỨC ĐAM MÊ TÚC CẦU</span></h1>
        <p class="hero-subtitle">Hệ thống sân cỏ nhân tạo đạt chuẩn FIFA, đèn chiếu sáng ban đêm hiện đại, đặt sân trực tuyến trong 60 giây.</p>
        
        <div class="d-flex justify-content-center gap-3">
            <a href="<?php echo BASE_URL; ?>/pages/dat-san.php" class="btn btn-primary-pitch btn-lg">
                <i class="bi bi-calendar2-plus me-2"></i> Đặt sân ngay
            </a>
            <a href="<?php echo BASE_URL; ?>/pages/san-bong.php" class="btn btn-outline-light btn-lg">
                <i class="bi bi-search me-2"></i> Xem tất cả sân
            </a>
        </div>
    </div>
</section>

<!-- SEARCH BAR CARD -->
<div class="container">
    <div class="search-box-card">
        <form action="<?php echo BASE_URL; ?>/pages/san-bong.php" method="GET" class="row g-3 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label class="form-label fw-bold text-secondary small"><i class="bi bi-search me-1"></i> Tìm kiếm sân hoặc địa chỉ</label>
                <input type="text" name="keyword" class="form-control form-control-lg fs-6" placeholder="Nhập tên sân, đường phố...">
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label fw-bold text-secondary small"><i class="bi bi-geo-alt me-1"></i> Cụm sân</label>
                <select name="cum_san_id" class="form-select form-select-lg fs-6">
                    <option value="">-- Tất cả cụm sân --</option>
                    <?php foreach ($cumSanList as $cum): ?>
                        <option value="<?php echo (int)$cum['id']; ?>"><?php echo e($cum['ten_cum_san']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label fw-bold text-secondary small"><i class="bi bi-people me-1"></i> Loại sân</label>
                <select name="loai_san_id" class="form-select form-select-lg fs-6">
                    <option value="">-- Tất cả loại sân --</option>
                    <?php foreach ($loaiSanList as $loai): ?>
                        <option value="<?php echo (int)$loai['id']; ?>"><?php echo e($loai['ten_loai']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <button type="submit" class="btn btn-primary-pitch btn-lg w-100 fs-6">
                    <i class="bi bi-search me-1"></i> Tìm kiếm
                </button>
            </div>
        </form>
    </div>
</div>

<!-- SÂN NỔI BẬT -->
<section class="py-5">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Chất lượng hàng đầu</span>
            <h2 class="section-title">Danh Sách Sân Nổi Bật</h2>
            <p class="section-desc">Các sân bóng đá cỏ nhân tạo mới, hệ thống chiếu sáng chuẩn và vị trí thuận tiện nhất</p>
        </div>

        <div class="row g-4">
            <?php foreach ($featuredPitches as $pitch): 
                $imgUrl = getPitchMainImage($pitch['id'], $pitch['cum_san_id'], $pdo);
            ?>
                <div class="col-lg-4 col-md-6">
                    <div class="pitch-card">
                        <div class="pitch-img-wrapper">
                            <img src="<?php echo e($imgUrl); ?>" alt="<?php echo e($pitch['ten_san']); ?>" loading="lazy">
                            <span class="pitch-badge-type"><i class="bi bi-people-fill me-1"></i> <?php echo e($pitch['ten_loai']); ?></span>
                            <span class="pitch-badge-status"><?php echo renderPitchStatusBadge($pitch['trang_thai']); ?></span>
                        </div>
                        <div class="pitch-body">
                            <a href="<?php echo BASE_URL; ?>/pages/chi-tiet-san.php?id=<?php echo (int)$pitch['id']; ?>" class="pitch-title">
                                <?php echo e($pitch['ten_san']); ?>
                            </a>
                            <div class="pitch-location">
                                <i class="bi bi-geo-alt-fill text-danger flex-shrink-0 mt-1"></i>
                                <span><?php echo e($pitch['ten_cum_san']); ?> - <?php echo e($pitch['dia_chi']); ?></span>
                            </div>
                            <?php if (!empty($pitch['ghi_chu'])): ?>
                                <p class="text-muted small mb-3"><i class="bi bi-info-circle me-1"></i> <?php echo e($pitch['ghi_chu']); ?></p>
                            <?php endif; ?>
                            <div class="mt-auto d-flex align-items-center justify-content-between pt-3 border-top">
                                <div class="pitch-price-tag">
                                    <?php echo formatCurrency($pitch['gia_theo_gio']); ?>
                                    <small>/ giờ</small>
                                </div>
                                <a href="<?php echo BASE_URL; ?>/pages/dat-san.php?san_bong_id=<?php echo (int)$pitch['id']; ?>" class="btn btn-primary-pitch btn-sm">
                                    <i class="bi bi-lightning-charge-fill me-1"></i> Đặt ngay
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5">
            <a href="<?php echo BASE_URL; ?>/pages/san-bong.php" class="btn btn-outline-pitch px-4 py-2">
                Xem toàn bộ sân bóng <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</section>

<!-- PHÂN LOẠI SÂN 5 VÀ SÂN 7 -->
<section class="py-5 bg-white border-top border-bottom">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Quy mô đa dạng</span>
            <h2 class="section-title">Sân 5 Người & Sân 7 Người</h2>
            <p class="section-desc">Tùy chọn quy mô sân phù hợp với đội hình và tính chất trận đấu giao hữu hay giải đấu</p>
        </div>

        <ul class="nav nav-pills justify-content-center mb-4" id="pitchTypeTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold px-4 py-2 me-2" id="pitch5-tab" data-bs-toggle="pill" data-bs-target="#pitch5" type="button" role="tab">
                    <i class="bi bi-people me-1"></i> Sân 5 Người (<?php echo count($pitches5); ?> sân)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold px-4 py-2" id="pitch7-tab" data-bs-toggle="pill" data-bs-target="#pitch7" type="button" role="tab">
                    <i class="bi bi-diagram-3 me-1"></i> Sân 7 Người (<?php echo count($pitches7); ?> sân)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="pitchTypeTabContent">
            <!-- TAB SÂN 5 -->
            <div class="tab-pane fade show active" id="pitch5" role="tabpanel">
                <div class="row g-4">
                    <?php foreach ($pitches5 as $p): 
                        $img = getPitchMainImage($p['id'], $p['cum_san_id'], $pdo);
                    ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="pitch-card">
                                <div class="pitch-img-wrapper">
                                    <img src="<?php echo e($img); ?>" alt="<?php echo e($p['ten_san']); ?>">
                                    <span class="pitch-badge-type">Sân 5</span>
                                </div>
                                <div class="pitch-body">
                                    <a href="<?php echo BASE_URL; ?>/pages/chi-tiet-san.php?id=<?php echo (int)$p['id']; ?>" class="pitch-title"><?php echo e($p['ten_san']); ?></a>
                                    <div class="pitch-location">
                                        <i class="bi bi-geo-alt-fill text-danger flex-shrink-0"></i>
                                        <span><?php echo e($p['ten_cum_san']); ?></span>
                                    </div>
                                    <div class="mt-auto d-flex align-items-center justify-content-between pt-3 border-top">
                                        <span class="pitch-price-tag"><?php echo formatCurrency($p['gia_theo_gio']); ?> <small>/h</small></span>
                                        <a href="<?php echo BASE_URL; ?>/pages/dat-san.php?san_bong_id=<?php echo (int)$p['id']; ?>" class="btn btn-outline-pitch btn-sm">Đặt sân</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- TAB SÂN 7 -->
            <div class="tab-pane fade" id="pitch7" role="tabpanel">
                <div class="row g-4">
                    <?php foreach ($pitches7 as $p): 
                        $img = getPitchMainImage($p['id'], $p['cum_san_id'], $pdo);
                    ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="pitch-card">
                                <div class="pitch-img-wrapper">
                                    <img src="<?php echo e($img); ?>" alt="<?php echo e($p['ten_san']); ?>">
                                    <span class="pitch-badge-type">Sân 7</span>
                                </div>
                                <div class="pitch-body">
                                    <a href="<?php echo BASE_URL; ?>/pages/chi-tiet-san.php?id=<?php echo (int)$p['id']; ?>" class="pitch-title"><?php echo e($p['ten_san']); ?></a>
                                    <div class="pitch-location">
                                        <i class="bi bi-geo-alt-fill text-danger flex-shrink-0"></i>
                                        <span><?php echo e($p['ten_cum_san']); ?></span>
                                    </div>
                                    <div class="mt-auto d-flex align-items-center justify-content-between pt-3 border-top">
                                        <span class="pitch-price-tag"><?php echo formatCurrency($p['gia_theo_gio']); ?> <small>/h</small></span>
                                        <a href="<?php echo BASE_URL; ?>/pages/dat-san.php?san_bong_id=<?php echo (int)$p['id']; ?>" class="btn btn-outline-pitch btn-sm">Đặt sân</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CỤM SÂN LIÊN KẾT -->
<section class="py-5">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Địa điểm rộng khắp</span>
            <h2 class="section-title">Các Cụm Sân Thể Thao</h2>
            <p class="section-desc">Cụm sân có bãi đỗ xe rộng rãi, căn tin phục vụ giải khát, khu vực thay đồ sạch sẽ</p>
        </div>

        <div class="row g-4">
            <?php foreach ($cumSanList as $cum): 
                $mapUrl = getGoogleMapUrl($cum);
                // Đếm số lượng sân con
                $countSub = $pdo->prepare("SELECT COUNT(*) FROM san_bong WHERE cum_san_id = ?");
                $countSub->execute([$cum['id']]);
                $totalSub = (int)$countSub->fetchColumn();

                // Lấy ảnh cụm sân
                $cumImg = getPitchMainImage(0, $cum['id'], $pdo);
            ?>
                <div class="col-lg-4 col-md-6">
                    <div class="complex-card">
                        <img src="<?php echo e($cumImg); ?>" alt="<?php echo e($cum['ten_cum_san']); ?>" class="complex-img">
                        <div class="p-3">
                            <h5 class="fw-bold mb-2"><?php echo e($cum['ten_cum_san']); ?></h5>
                            <p class="text-muted small mb-2"><i class="bi bi-geo-alt-fill text-danger me-1"></i> <?php echo e($cum['dia_chi']); ?></p>
                            <p class="text-muted small mb-2"><i class="bi bi-clock-fill text-success me-1"></i> <?php echo substr($cum['gio_mo_cua'], 0, 5); ?> - <?php echo substr($cum['gio_dong_cua'], 0, 5); ?></p>
                            <p class="text-muted small mb-3"><i class="bi bi-telephone-fill text-primary me-1"></i> <?php echo e($cum['so_dien_thoai_lien_he'] ?? 'Đang cập nhật'); ?></p>
                            
                            <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                <span class="badge bg-secondary-subtle text-secondary"><?php echo $totalSub; ?> sân bóng</span>
                                <div class="d-flex gap-2">
                                    <a href="<?php echo e($mapUrl); ?>" target="_blank" class="btn btn-outline-danger btn-sm" title="Xem trên Google Map">
                                        <i class="bi bi-map-fill"></i> Bản đồ
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>/pages/chi-tiet-cum-san.php?id=<?php echo (int)$cum['id']; ?>" class="btn btn-outline-pitch btn-sm">
                                        Chi tiết
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- DỊCH VỤ BỔ SUNG NẾU CÓ TRONG DATABASE -->
<?php if (!empty($dichVuList)): ?>
<section class="py-5 bg-white border-top">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Tiện ích kèm theo</span>
            <h2 class="section-title">Dịch Vụ Đi Kèm</h2>
            <p class="section-desc">Phục vụ đầy đủ nước uống tăng lực, áo tập phân đội để trận cầu thêm sôi động</p>
        </div>

        <div class="row g-4 justify-content-center">
            <?php foreach ($dichVuList as $dv): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm p-3 text-center">
                        <div class="mx-auto mb-3 text-success fs-1">
                            <i class="bi bi-cup-straw"></i>
                        </div>
                        <h5 class="fw-bold"><?php echo e($dv['ten_dich_vu']); ?></h5>
                        <p class="text-muted small mb-2">Đơn vị: <?php echo e($dv['don_vi_tinh'] ?? 'Chai/Bộ'); ?></p>
                        <div class="fs-4 fw-bold text-success"><?php echo formatCurrency($dv['don_gia']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CAM KẾT & QUY TRÌNH ĐẶT SÂN -->
<section class="py-5">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Quy trình đơn giản</span>
            <h2 class="section-title">Cách Đặt Sân Tại BongDa16h</h2>
            <p class="section-desc">Chỉ với 4 bước đơn giản để có ngay khung giờ vàng đá bóng cùng đồng đội</p>
        </div>

        <div class="row g-4 text-center">
            <div class="col-lg-3 col-md-6">
                <div class="p-3 bg-white rounded-4 shadow-sm h-100 border">
                    <div class="rounded-circle bg-success-subtle text-success d-inline-flex p-3 fs-3 mb-3">
                        <i class="bi bi-search"></i>
                    </div>
                    <h5 class="fw-bold">1. Chọn sân ưng ý</h5>
                    <p class="text-muted small">Lựa chọn sân 5 hoặc 7 người tại cụm sân gần bạn nhất.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="p-3 bg-white rounded-4 shadow-sm h-100 border">
                    <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex p-3 fs-3 mb-3">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <h5 class="fw-bold">2. Chọn ngày & giờ</h5>
                    <p class="text-muted small">Hệ thống tự động kiểm tra slot trống, chống trùng giờ 100%.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="p-3 bg-white rounded-4 shadow-sm h-100 border">
                    <div class="rounded-circle bg-warning-subtle text-warning d-inline-flex p-3 fs-3 mb-3">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <h5 class="fw-bold">3. Xác nhận & Dịch vụ</h5>
                    <p class="text-muted small">Chọn nước uống, áo đấu và nhận mã đặt sân tức thì.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="p-3 bg-white rounded-4 shadow-sm h-100 border">
                    <div class="rounded-circle bg-danger-subtle text-danger d-inline-flex p-3 fs-3 mb-3">
                        <i class="bi bi-trophy"></i>
                    </div>
                    <h5 class="fw-bold">4. Ra sân tỏa sáng</h5>
                    <p class="text-muted small">Đến sân đúng giờ, nhận sân và tận hưởng đam mê sân cỏ.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

