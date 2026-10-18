<?php
/**
 * Chi tiết Cụm Sân Thể Thao và Danh sách sân con
 * Dự án: BongDa16h
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDBConnection();
$cumId = (int)($_GET['id'] ?? 0);

if (!$cumId) {
    header('Location: ' . BASE_URL . '/pages/cum-san.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM cum_san WHERE id = ?");
$stmt->execute([$cumId]);
$cum = $stmt->fetch();

if (!$cum) {
    setFlash('danger', 'Cụm sân không tồn tại!');
    header('Location: ' . BASE_URL . '/pages/cum-san.php');
    exit;
}

$pageTitle = $cum['ten_cum_san'];

// Lấy danh sách sân thuộc cụm này
$pitchStmt = $pdo->prepare("
    SELECT s.*, l.ten_loai 
    FROM san_bong s
    JOIN loai_san l ON s.loai_san_id = l.id
    WHERE s.cum_san_id = ?
    ORDER BY s.id ASC
");
$pitchStmt->execute([$cumId]);
$pitches = $pitchStmt->fetchAll();

$mapUrl = getGoogleMapUrl($cum);
$cumImg = getPitchMainImage(0, $cum['id'], $pdo);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php" class="text-decoration-none">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/cum-san.php" class="text-decoration-none">Cụm sân</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo e($cum['ten_cum_san']); ?></li>
        </ol>
    </nav>

    <!-- THÔNG TIN CỤM SÂN -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
        <div class="row g-0">
            <div class="col-lg-5">
                <img src="<?php echo e($cumImg); ?>" alt="<?php echo e($cum['ten_cum_san']); ?>" class="w-100 h-100" style="object-fit: cover; min-height: 280px;">
            </div>
            <div class="col-lg-7 p-4 d-flex flex-column justify-content-center">
                <span class="badge bg-success-subtle text-success border border-success align-self-start px-3 py-1 rounded-pill mb-2">
                    <i class="bi bi-shield-check me-1"></i> Đối tác chính thức
                </span>
                <h2 class="fw-bold text-navy mb-2"><?php echo e($cum['ten_cum_san']); ?></h2>
                <p class="text-muted mb-3"><i class="bi bi-geo-alt-fill text-danger me-1"></i> <?php echo e($cum['dia_chi']); ?></p>

                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block">Giờ hoạt động</small>
                            <strong class="fs-6 text-dark"><i class="bi bi-clock me-1 text-success"></i> <?php echo substr($cum['gio_mo_cua'], 0, 5); ?> - <?php echo substr($cum['gio_dong_cua'], 0, 5); ?></strong>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block">Hotline đặt sân</small>
                            <strong class="fs-6 text-primary"><i class="bi bi-telephone me-1"></i> <?php echo e($cum['so_dien_thoai_lien_he'] ?? 'Đang cập nhật'); ?></strong>
                        </div>
                    </div>
                </div>

                <div>
                    <a href="<?php echo e($mapUrl); ?>" target="_blank" class="btn btn-danger px-4">
                        <i class="bi bi-map-fill me-1"></i> Xem đường đi trên Google Maps
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- DANH SÁCH SÂN CON TRỰC THUỘC -->
    <div class="mb-4">
        <h3 class="fw-bold text-navy"><i class="bi bi-grid-fill text-success me-2"></i> Các Sân Bóng Thuộc Cụm Sân</h3>
        <p class="text-muted">Chọn sân bóng bạn mong muốn để xem lịch và đặt sân ngay</p>
    </div>

    <div class="row g-4">
        <?php if (empty($pitches)): ?>
            <div class="col-12">
                <div class="alert alert-info">Cụm sân này hiện chưa có sân bóng nào được kích hoạt.</div>
            </div>
        <?php else: ?>
            <?php foreach ($pitches as $p): 
                $pImg = getPitchMainImage($p['id'], $cum['id'], $pdo);
            ?>
                <div class="col-lg-4 col-md-6">
                    <div class="pitch-card">
                        <div class="pitch-img-wrapper">
                            <img src="<?php echo e($pImg); ?>" alt="<?php echo e($p['ten_san']); ?>">
                            <span class="pitch-badge-type"><?php echo e($p['ten_loai']); ?></span>
                            <span class="pitch-badge-status"><?php echo renderPitchStatusBadge($p['trang_thai']); ?></span>
                        </div>
                        <div class="pitch-body">
                            <a href="<?php echo BASE_URL; ?>/pages/chi-tiet-san.php?id=<?php echo (int)$p['id']; ?>" class="pitch-title">
                                <?php echo e($p['ten_san']); ?>
                            </a>
                            <?php if (!empty($p['ghi_chu'])): ?>
                                <p class="text-muted small mb-3"><?php echo e($p['ghi_chu']); ?></p>
                            <?php endif; ?>
                            <div class="mt-auto d-flex align-items-center justify-content-between pt-3 border-top">
                                <span class="pitch-price-tag"><?php echo formatCurrency($p['gia_theo_gio']); ?> <small>/h</small></span>
                                <a href="<?php echo BASE_URL; ?>/pages/dat-san.php?san_bong_id=<?php echo (int)$p['id']; ?>" class="btn btn-primary-pitch btn-sm">
                                    Đặt sân
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

