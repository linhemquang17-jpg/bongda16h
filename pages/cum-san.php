<?php
/**
 * Danh sách Cụm Sân Thể Thao
 * Dự án: BongDa16h
 */
$pageTitle = 'Cụm Sân Thể Thao';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDBConnection();

$stmt = $pdo->query("
    SELECT c.*, COUNT(s.id) as total_pitches
    FROM cum_san c
    LEFT JOIN san_bong s ON c.id = s.cum_san_id
    GROUP BY c.id
    ORDER BY c.id ASC
");
$cumSanList = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php" class="text-decoration-none">Trang chủ</a></li>
            <li class="breadcrumb-item active" aria-current="page">Cụm sân thể thao</li>
        </ol>
    </nav>

    <div class="section-header">
        <span class="section-badge">Hệ thống sân bãi</span>
        <h2 class="section-title">Các Cụm Sân Bóng Đá Hàng Đầu</h2>
        <p class="section-desc">Hệ thống cụm sân phủ sóng tại các trục đường chính, dễ dàng di chuyển và bãi đỗ xe rộng rãi</p>
    </div>

    <div class="row g-4">
        <?php foreach ($cumSanList as $cum): 
            $img = getPitchMainImage(0, $cum['id'], $pdo);
            $mapUrl = getGoogleMapUrl($cum);
        ?>
            <div class="col-lg-4 col-md-6">
                <div class="complex-card">
                    <img src="<?php echo e($img); ?>" alt="<?php echo e($cum['ten_cum_san']); ?>" class="complex-img">
                    <div class="p-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-success-subtle text-success"><?php echo (int)$cum['total_pitches']; ?> sân bóng</span>
                            <span class="small text-muted"><i class="bi bi-clock me-1"></i> <?php echo substr($cum['gio_mo_cua'], 0, 5); ?> - <?php echo substr($cum['gio_dong_cua'], 0, 5); ?></span>
                        </div>

                        <h4 class="fw-bold mb-2">
                            <a href="<?php echo BASE_URL; ?>/pages/chi-tiet-cum-san.php?id=<?php echo (int)$cum['id']; ?>" class="text-navy text-decoration-none hover-success">
                                <?php echo e($cum['ten_cum_san']); ?>
                            </a>
                        </h4>

                        <p class="text-muted small mb-2"><i class="bi bi-geo-alt-fill text-danger me-1"></i> <?php echo e($cum['dia_chi']); ?></p>
                        <p class="text-muted small mb-3"><i class="bi bi-telephone-fill text-primary me-1"></i> Hotline: <strong><?php echo e($cum['so_dien_thoai_lien_he'] ?? 'Đang cập nhật'); ?></strong></p>

                        <div class="d-flex gap-2 pt-3 border-top">
                            <a href="<?php echo e($mapUrl); ?>" target="_blank" class="btn btn-outline-danger btn-sm flex-grow-1">
                                <i class="bi bi-geo-alt me-1"></i> Bản đồ
                            </a>
                            <a href="<?php echo BASE_URL; ?>/pages/chi-tiet-cum-san.php?id=<?php echo (int)$cum['id']; ?>" class="btn btn-primary-pitch btn-sm flex-grow-1">
                                Xem sân bóng
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

