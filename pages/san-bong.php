<?php
/**
 * Danh sách sân bóng đá - Tìm kiếm, lọc và phân trang
 * Dự án: BongDa16h
 */
$pageTitle = 'Danh Sách Sân Bóng';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDBConnection();

// Lọc dữ liệu từ GET
$keyword   = sanitize($_GET['keyword'] ?? '');
$cumSanId  = (int)($_GET['cum_san_id'] ?? 0);
$loaiSanId = (int)($_GET['loai_san_id'] ?? 0);
$priceMin  = (float)($_GET['price_min'] ?? 0);
$priceMax  = (float)($_GET['price_max'] ?? 0);
$sortBy    = sanitize($_GET['sort'] ?? 'id_asc');
$page      = max(1, (int)($_GET['page'] ?? 1));
$limit     = 6;
$offset    = ($page - 1) * $limit;

// Xây dựng câu truy vấn động
$conditions = [];
$params     = [];

if (!empty($keyword)) {
    $conditions[] = "(s.ten_san LIKE :kw OR c.dia_chi LIKE :kw OR c.ten_cum_san LIKE :kw)";
    $params[':kw'] = '%' . $keyword . '%';
}

if ($cumSanId > 0) {
    $conditions[] = "s.cum_san_id = :cum_id";
    $params[':cum_id'] = $cumSanId;
}

if ($loaiSanId > 0) {
    $conditions[] = "s.loai_san_id = :loai_id";
    $params[':loai_id'] = $loaiSanId;
}

if ($priceMin > 0) {
    $conditions[] = "s.gia_theo_gio >= :price_min";
    $params[':price_min'] = $priceMin;
}

if ($priceMax > 0) {
    $conditions[] = "s.gia_theo_gio <= :price_max";
    $params[':price_max'] = $priceMax;
}

$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Sắp xếp
$orderBy = "ORDER BY s.id ASC";
if ($sortBy === 'price_asc') {
    $orderBy = "ORDER BY s.gia_theo_gio ASC";
} elseif ($sortBy === 'price_desc') {
    $orderBy = "ORDER BY s.gia_theo_gio DESC";
} elseif ($sortBy === 'name_asc') {
    $orderBy = "ORDER BY s.ten_san ASC";
}

// Đếm tổng số sân phù hợp
$countSql = "
    SELECT COUNT(*) 
    FROM san_bong s
    JOIN cum_san c ON s.cum_san_id = c.id
    JOIN loai_san l ON s.loai_san_id = l.id
    {$whereClause}
";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages   = ceil($totalRecords / $limit);

// Lấy danh sách sân cho trang hiện tại
$sql = "
    SELECT s.*, c.ten_cum_san, c.dia_chi, l.ten_loai 
    FROM san_bong s
    JOIN cum_san c ON s.cum_san_id = c.id
    JOIN loai_san l ON s.loai_san_id = l.id
    {$whereClause}
    {$orderBy}
    LIMIT {$limit} OFFSET {$offset}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pitches = $stmt->fetchAll();

// Lấy danh mục cho bộ lọc
$cumSanList  = $pdo->query("SELECT * FROM cum_san ORDER BY id ASC")->fetchAll();
$loaiSanList = $pdo->query("SELECT * FROM loai_san ORDER BY id ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php" class="text-decoration-none">Trang chủ</a></li>
            <li class="breadcrumb-item active" aria-current="page">Danh sách sân bóng</li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- SIDEBAR BỘ LỌC -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                    <h5 class="fw-bold mb-0 text-navy"><i class="bi bi-funnel-fill text-success me-2"></i> Bộ Lọc Tìm Kiếm</h5>
                    <a href="<?php echo BASE_URL; ?>/pages/san-bong.php" class="small text-danger text-decoration-none">Đặt lại</a>
                </div>

                <form action="<?php echo BASE_URL; ?>/pages/san-bong.php" method="GET">
                    <!-- Từ khóa -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Từ khóa tìm kiếm</label>
                        <input type="text" name="keyword" value="<?php echo e($keyword); ?>" class="form-control" placeholder="Tên sân, địa chỉ...">
                    </div>

                    <!-- Cụm sân -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Cụm sân</label>
                        <select name="cum_san_id" class="form-select">
                            <option value="">Tất cả cụm sân</option>
                            <?php foreach ($cumSanList as $c): ?>
                                <option value="<?php echo (int)$c['id']; ?>" <?php echo $cumSanId === (int)$c['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($c['ten_cum_san']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Loại sân -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Loại sân</label>
                        <select name="loai_san_id" class="form-select">
                            <option value="">Tất cả loại sân</option>
                            <?php foreach ($loaiSanList as $l): ?>
                                <option value="<?php echo (int)$l['id']; ?>" <?php echo $loaiSanId === (int)$l['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($l['ten_loai']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Khoảng giá -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Giá tối thiểu (VNĐ/h)</label>
                        <input type="number" name="price_min" value="<?php echo $priceMin > 0 ? (int)$priceMin : ''; ?>" class="form-control" placeholder="Ví dụ: 200000" step="10000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Giá tối đa (VNĐ/h)</label>
                        <input type="number" name="price_max" value="<?php echo $priceMax > 0 ? (int)$priceMax : ''; ?>" class="form-control" placeholder="Ví dụ: 500000" step="10000">
                    </div>

                    <!-- Sắp xếp -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Sắp xếp theo</label>
                        <select name="sort" class="form-select">
                            <option value="id_asc" <?php echo $sortBy === 'id_asc' ? 'selected' : ''; ?>>Mặc định</option>
                            <option value="price_asc" <?php echo $sortBy === 'price_asc' ? 'selected' : ''; ?>>Giá: Thấp đến cao</option>
                            <option value="price_desc" <?php echo $sortBy === 'price_desc' ? 'selected' : ''; ?>>Giá: Cao đến thấp</option>
                            <option value="name_asc" <?php echo $sortBy === 'name_asc' ? 'selected' : ''; ?>>Tên sân: A - Z</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary-pitch w-100">
                        <i class="bi bi-filter me-1"></i> Áp dụng bộ lọc
                    </button>
                </form>
            </div>
        </div>

        <!-- DANH SÁCH SÂN VÀ PHÂN TRANG -->
        <div class="col-lg-9">
            <div class="d-flex align-items-center justify-content-between mb-4 bg-white p-3 rounded-4 shadow-sm">
                <div>
                    <h4 class="fw-bold mb-0">Tất Cả Sân Bóng</h4>
                    <small class="text-muted">Tìm thấy <strong><?php echo $totalRecords; ?></strong> sân bóng phù hợp</small>
                </div>
            </div>

            <?php if (empty($pitches)): ?>
                <div class="card border-0 shadow-sm p-5 text-center rounded-4">
                    <div class="fs-1 text-muted mb-3"><i class="bi bi-emoji-frown"></i></div>
                    <h5>Không tìm thấy sân bóng nào phù hợp!</h5>
                    <p class="text-muted">Quý khách vui lòng thử tìm kiếm với các điều kiện hoặc từ khóa khác.</p>
                    <div>
                        <a href="<?php echo BASE_URL; ?>/pages/san-bong.php" class="btn btn-outline-pitch">Xem toàn bộ danh sách sân</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($pitches as $pitch): 
                        $img = getPitchMainImage($pitch['id'], $pitch['cum_san_id'], $pdo);
                    ?>
                        <div class="col-md-6">
                            <div class="pitch-card">
                                <div class="pitch-img-wrapper">
                                    <img src="<?php echo e($img); ?>" alt="<?php echo e($pitch['ten_san']); ?>">
                                    <span class="pitch-badge-type"><?php echo e($pitch['ten_loai']); ?></span>
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
                                        <p class="text-muted small mb-3"><?php echo e($pitch['ghi_chu']); ?></p>
                                    <?php endif; ?>
                                    <div class="mt-auto d-flex align-items-center justify-content-between pt-3 border-top">
                                        <div class="pitch-price-tag">
                                            <?php echo formatCurrency($pitch['gia_theo_gio']); ?>
                                            <small>/ giờ</small>
                                        </div>
                                        <div class="d-flex gap-1">
                                            <a href="<?php echo BASE_URL; ?>/pages/tim-doi-bong.php?san_id=<?php echo (int)$pitch['id']; ?>" class="btn btn-outline-success btn-sm" title="Tìm đối quanh sân này">
                                                <i class="bi bi-people-fill"></i> Tìm đối
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>/pages/chi-tiet-san.php?id=<?php echo (int)$pitch['id']; ?>" class="btn btn-outline-secondary btn-sm">
                                                Chi tiết
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>/pages/dat-san.php?san_bong_id=<?php echo (int)$pitch['id']; ?>" class="btn btn-primary-pitch btn-sm">
                                                Đặt sân
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- PHÂN TRANG -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-5">
                        <ul class="pagination justify-content-center">
                            <?php
                            // Tạo chuỗi query giữ nguyên các param lọc
                            $queryParams = $_GET;
                            ?>
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <?php $queryParams['page'] = $page - 1; ?>
                                <a class="page-link" href="?<?php echo http_build_query($queryParams); ?>">Trước</a>
                            </li>

                            <?php for ($i = 1; $i <= $totalPages; $i++): 
                                $queryParams['page'] = $i;
                            ?>
                                <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query($queryParams); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <?php $queryParams['page'] = $page + 1; ?>
                                <a class="page-link" href="?<?php echo http_build_query($queryParams); ?>">Tiếp</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

