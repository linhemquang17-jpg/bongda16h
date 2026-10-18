<?php
/**
 * Trang Liên Hệ
 * Dự án: BongDa16h
 */
$pageTitle = 'Liên Hệ';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDBConnection();
$cumSanList = $pdo->query("SELECT * FROM cum_san ORDER BY id ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php" class="text-decoration-none">Trang chủ</a></li>
            <li class="breadcrumb-item active" aria-current="page">Liên hệ</li>
        </ol>
    </nav>

    <div class="section-header">
        <span class="section-badge">Hỗ trợ 24/7</span>
        <h2 class="section-title">Liên Hệ Với Ban Quản Trị Sân</h2>
        <p class="section-desc">Chúng tôi luôn sẵn sàng lắng nghe mọi góp ý và hỗ trợ bạn đặt sân bất cứ lúc nào</p>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100">
                <div class="rounded-circle bg-success-subtle text-success d-inline-flex p-3 fs-3 mx-auto mb-3">
                    <i class="bi bi-telephone-fill"></i>
                </div>
                <h5 class="fw-bold">Hotline Tổng Đài</h5>
                <p class="text-muted small">Hỗ trợ đặt sân khẩn cấp, tìm đối tác giao hữu</p>
                <div class="fs-5 fw-bold text-success mt-auto">0981 122 334</div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100">
                <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex p-3 fs-3 mx-auto mb-3">
                    <i class="bi bi-envelope-fill"></i>
                </div>
                <h5 class="fw-bold">Hòm Thư Điện Tử</h5>
                <p class="text-muted small">Hợp tác kinh doanh, đăng ký cụm sân mới</p>
                <div class="fs-6 fw-bold text-primary mt-auto">contact@bongda16h.vn</div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100">
                <div class="rounded-circle bg-warning-subtle text-warning d-inline-flex p-3 fs-3 mx-auto mb-3">
                    <i class="bi bi-clock-fill"></i>
                </div>
                <h5 class="fw-bold">Giờ Phục Vụ</h5>
                <p class="text-muted small">Tất cả các ngày trong tuần (cả lễ Tết)</p>
                <div class="fs-6 fw-bold text-navy mt-auto">05:30 - 23:00 Hàng ngày</div>
            </div>
        </div>
    </div>

    <!-- DANH SÁCH ĐỊA CHỈ CỤM SÂN & GOOGLE MAPS -->
    <div class="card border-0 shadow-sm rounded-4 p-4">
        <h4 class="fw-bold text-navy mb-4"><i class="bi bi-geo-alt-fill text-danger me-2"></i> Hệ Thống Địa Điểm Cụm Sân</h4>
        
        <div class="row g-4">
            <?php foreach ($cumSanList as $cum): 
                $mapUrl = getGoogleMapUrl($cum);
            ?>
                <div class="col-md-4">
                    <div class="p-3 border rounded-3 bg-light h-100 d-flex flex-column">
                        <h6 class="fw-bold text-navy mb-2"><?php echo e($cum['ten_cum_san']); ?></h6>
                        <p class="small text-muted mb-2"><i class="bi bi-pin-map-fill text-danger me-1"></i> <?php echo e($cum['dia_chi']); ?></p>
                        <p class="small text-muted mb-3"><i class="bi bi-telephone-fill text-success me-1"></i> <?php echo e($cum['so_dien_thoai_lien_he'] ?? 'Đang cập nhật'); ?></p>
                        <div class="mt-auto">
                            <a href="<?php echo e($mapUrl); ?>" target="_blank" class="btn btn-outline-danger btn-sm w-100">
                                <i class="bi bi-map me-1"></i> Mở trên Google Maps
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

