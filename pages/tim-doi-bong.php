<?php
/**
 * Trang Tìm Đối Đá Bóng - Kết nối các đội bóng quanh khu vực sân
 * Dự án: BongDa16h
 */
$pageTitle = 'Tìm Đối Đá Bóng - Kết Nối Đội Bóng Quanh Sân';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDBConnection();
$currentUser = getCurrentUser();

// Tham số lọc
$sanId     = isset($_GET['san_id']) ? (int)$_GET['san_id'] : 0;
$cumId     = isset($_GET['cum_id']) ? (int)$_GET['cum_id'] : 0;
$keyword   = sanitize($_GET['keyword'] ?? '');
$loaiSan   = sanitize($_GET['loai_san'] ?? '');
$trinhDo   = sanitize($_GET['trinh_do'] ?? '');
$ngayDa    = sanitize($_GET['ngay_da'] ?? '');
$trangThai = sanitize($_GET['trang_thai'] ?? 'Đang tìm đối'); // Mặc định chỉ lấy tin đang tìm đối
$radius    = isset($_GET['radius']) ? (float)$_GET['radius'] : 0; // Bán kính km

// Thông tin sân đang lọc (nếu có)
$selectedPitch = null;
$selectedCum   = null;

if ($sanId > 0) {
    $pStmt = $pdo->prepare("
        SELECT s.*, c.ten_cum_san, c.dia_chi, c.kinh_do, c.vi_do 
        FROM san_bong s 
        JOIN cum_san c ON s.cum_san_id = c.id 
        WHERE s.id = ?
    ");
    $pStmt->execute([$sanId]);
    $selectedPitch = $pStmt->fetch();
    if ($selectedPitch) {
        $cumId = (int)$selectedPitch['cum_san_id'];
    }
}

if ($cumId > 0 && !$selectedPitch) {
    $cStmt = $pdo->prepare("SELECT * FROM cum_san WHERE id = ?");
    $cStmt->execute([$cumId]);
    $selectedCum = $cStmt->fetch();
}

// Xây dựng câu truy vấn danh sách tin tìm đối
$sql = "
    SELECT t.*, 
           c.ten_cum_san, c.dia_chi as dia_chi_cum,
           s.ten_san
    FROM tim_doi_bong t
    LEFT JOIN cum_san c ON t.cum_san_id = c.id
    LEFT JOIN san_bong s ON t.san_bong_id = s.id
    WHERE 1=1
";
$params = [];

// Lọc trạng thái
if (!empty($trangThai) && $trangThai !== 'all') {
    $sql .= " AND t.trang_thai = ?";
    $params[] = $trangThai;
}

// Lọc loại sân
if (!empty($loaiSan)) {
    $sql .= " AND t.loai_san = ?";
    $params[] = $loaiSan;
}

// Lọc trình độ
if (!empty($trinhDo)) {
    $sql .= " AND t.trinh_do = ?";
    $params[] = $trinhDo;
}

// Lọc ngày đá
if (!empty($ngayDa)) {
    $sql .= " AND t.ngay_da = ?";
    $params[] = $ngayDa;
} else {
    // Nếu không chọn ngày cụ thể, ưu tiên hiển thị từ hôm nay trở đi
    $sql .= " AND t.ngay_da >= CURDATE()";
}

// Lọc từ khóa
if (!empty($keyword)) {
    $sql .= " AND (t.ten_doi LIKE ? OR t.khu_vuc LIKE ? OR t.mo_ta LIKE ? OR c.ten_cum_san LIKE ?)";
    $kwParam = "%{$keyword}%";
    $params[] = $kwParam;
    $params[] = $kwParam;
    $params[] = $kwParam;
    $params[] = $kwParam;
}

// Lọc theo sân bóng / cụm sân nếu có
if ($sanId > 0 && $radius <= 0) {
    $sql .= " AND (t.san_bong_id = ? OR t.cum_san_id = ?)";
    $params[] = $sanId;
    $params[] = $cumId;
} elseif ($cumId > 0 && $radius <= 0) {
    $sql .= " AND t.cum_san_id = ?";
    $params[] = $cumId;
}

$sql .= " ORDER BY t.ngay_da ASC, t.gio_da ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rawMatches = $stmt->fetchAll();

// Xử lý khoảng cách nếu có tọa độ của sân đang chọn
$matches = [];
$pitchLat = $selectedPitch['vi_do'] ?? ($selectedCum['vi_do'] ?? null);
$pitchLon = $selectedPitch['kinh_do'] ?? ($selectedCum['kinh_do'] ?? null);

foreach ($rawMatches as $m) {
    $dist = null;
    if ($pitchLat && $pitchLon && !empty($m['vi_do']) && !empty($m['kinh_do'])) {
        $dist = calculateDistance($pitchLat, $pitchLon, $m['vi_do'], $m['kinh_do']);
    }
    $m['distance'] = $dist;

    // Nếu lọc theo bán kính (radius > 0)
    if ($radius > 0 && $dist !== null) {
        if ($dist <= $radius) {
            $matches[] = $m;
        }
    } else {
        $matches[] = $m;
    }
}

// Nếu có lọc khoảng cách, sắp xếp theo khoảng cách tăng dần
if ($radius > 0 || ($sanId > 0 && $pitchLat && $pitchLon)) {
    usort($matches, function($a, $b) {
        if ($a['distance'] === null && $b['distance'] === null) return 0;
        if ($a['distance'] === null) return 1;
        if ($b['distance'] === null) return -1;
        return $a['distance'] <=> $b['distance'];
    });
}

// Lấy danh sách cụm sân & sân để điền vào form đăng tin
$allCumSan = $pdo->query("SELECT id, ten_cum_san, dia_chi FROM cum_san ORDER BY id ASC")->fetchAll();
$allSanBong = $pdo->query("SELECT id, ten_san, cum_san_id FROM san_bong ORDER BY cum_san_id, id")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php" class="text-decoration-none">Trang chủ</a></li>
            <li class="breadcrumb-item active" aria-current="page">Tìm đối đá bóng</li>
        </ol>
    </nav>

    <?php renderFlash(); ?>

    <!-- BANNER TIÊU ĐỀ & NÚT ĐĂNG TIN -->
    <div class="card border-0 rounded-4 p-4 p-lg-5 mb-4 shadow-sm" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #064e3b 100%); color: white;">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="badge bg-success px-3 py-2 rounded-pill fs-6 mb-2">
                    <i class="bi bi-broadcast me-1"></i> Cộng Đồng Giao Lưu Bóng Đá
                </span>
                <h1 class="fw-bold mb-2 display-6">Tìm Đối Đá Bóng Quanh Sân</h1>
                <p class="text-light text-opacity-75 mb-0 fs-6">
                    Kết nối nhanh chóng giữa các đội bóng đang thiếu đối tác thi đấu. Dễ dàng tìm đội cùng trình độ, giao lưu học hỏi, chia sẻ tiền sân và nâng cao sức khỏe!
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <button type="button" class="btn btn-success btn-lg px-4 py-3 fw-bold shadow rounded-pill" data-bs-toggle="modal" data-bs-target="#addMatchModal">
                    <i class="bi bi-plus-circle-fill me-2"></i> Đăng Tin Tìm Đối Ngay
                </button>
            </div>
        </div>
    </div>

    <!-- BANNER KHI ĐANG LỌC QUANH SÂN CỤ THỂ -->
    <?php if ($selectedPitch || $selectedCum): ?>
        <div class="alert alert-info border-info d-flex flex-wrap align-items-center justify-content-between rounded-4 p-3 mb-4 shadow-sm">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-geo-alt-fill fs-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0 text-navy">
                        ⚽ Đang tìm đối quanh: 
                        <?php echo e($selectedPitch ? $selectedPitch['ten_san'] . ' (' . $selectedPitch['ten_cum_san'] . ')' : $selectedCum['ten_cum_san']); ?>
                    </h6>
                    <small class="text-muted">
                        Địa chỉ: <?php echo e($selectedPitch ? $selectedPitch['dia_chi'] : $selectedCum['dia_chi']); ?>
                    </small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                <a href="<?php echo BASE_URL; ?>/pages/tim-doi-bong.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i> Xem tất cả khu vực
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/chi-tiet-san.php?id=<?php echo (int)($selectedPitch['id'] ?? $sanId); ?>" class="btn btn-sm btn-primary-pitch">
                    <i class="bi bi-arrow-left me-1"></i> Quay lại chi tiết sân
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- BỘ LỌC TÌM KIẾM -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <form action="<?php echo BASE_URL; ?>/pages/tim-doi-bong.php" method="GET" class="row g-3">
            <?php if ($sanId > 0): ?>
                <input type="hidden" name="san_id" value="<?php echo (int)$sanId; ?>">
            <?php endif; ?>
            <?php if ($cumId > 0): ?>
                <input type="hidden" name="cum_id" value="<?php echo (int)$cumId; ?>">
            <?php endif; ?>

            <!-- Từ khóa -->
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Từ khóa tìm kiếm</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="keyword" class="form-control" placeholder="Tên đội, khu vực, ghi chú..." value="<?php echo e($keyword); ?>">
                </div>
            </div>

            <!-- Loại sân -->
            <div class="col-md-2 col-6">
                <label class="form-label small fw-semibold">Quy mô sân</label>
                <select name="loai_san" class="form-select">
                    <option value="">Tất cả quy mô</option>
                    <option value="Sân 5 người" <?php echo $loaiSan === 'Sân 5 người' ? 'selected' : ''; ?>>Sân 5 người</option>
                    <option value="Sân 7 người" <?php echo $loaiSan === 'Sân 7 người' ? 'selected' : ''; ?>>Sân 7 người</option>
                    <option value="Sân 11 người" <?php echo $loaiSan === 'Sân 11 người' ? 'selected' : ''; ?>>Sân 11 người</option>
                </select>
            </div>

            <!-- Trình độ -->
            <div class="col-md-2 col-6">
                <label class="form-label small fw-semibold">Trình độ đội</label>
                <select name="trinh_do" class="form-select">
                    <option value="">Mọi trình độ</option>
                    <option value="Giao lưu" <?php echo $trinhDo === 'Giao lưu' ? 'selected' : ''; ?>>Giao lưu nhẹ nhàng</option>
                    <option value="Phong trào" <?php echo $trinhDo === 'Phong trào' ? 'selected' : ''; ?>>Phong trào</option>
                    <option value="Trung bình" <?php echo $trinhDo === 'Trung bình' ? 'selected' : ''; ?>>Trung bình</option>
                    <option value="Khá" <?php echo $trinhDo === 'Khá' ? 'selected' : ''; ?>>Khá / Cọ xát</option>
                </select>
            </div>

            <!-- Ngày đá -->
            <div class="col-md-2 col-6">
                <label class="form-label small fw-semibold">Ngày thi đấu</label>
                <input type="date" name="ngay_da" class="form-control" value="<?php echo e($ngayDa); ?>">
            </div>

            <!-- Bán kính nếu có sân -->
            <?php if ($selectedPitch || $selectedCum): ?>
                <div class="col-md-2 col-6">
                    <label class="form-label small fw-semibold">Bán kính quanh sân</label>
                    <select name="radius" class="form-select">
                        <option value="0" <?php echo $radius == 0 ? 'selected' : ''; ?>>Tại sân / Tất cả</option>
                        <option value="3" <?php echo $radius == 3 ? 'selected' : ''; ?>>Dưới 3 km</option>
                        <option value="5" <?php echo $radius == 5 ? 'selected' : ''; ?>>Dưới 5 km</option>
                        <option value="10" <?php echo $radius == 10 ? 'selected' : ''; ?>>Dưới 10 km</option>
                        <option value="20" <?php echo $radius == 20 ? 'selected' : ''; ?>>Dưới 20 km</option>
                    </select>
                </div>
            <?php else: ?>
                <!-- Trạng thái tin -->
                <div class="col-md-2 col-6">
                    <label class="form-label small fw-semibold">Trạng thái</label>
                    <select name="trang_thai" class="form-select">
                        <option value="Đang tìm đối" <?php echo $trangThai === 'Đang tìm đối' ? 'selected' : ''; ?>>Đang tìm đối</option>
                        <option value="Đã tìm được đối" <?php echo $trangThai === 'Đã tìm được đối' ? 'selected' : ''; ?>>Đã tìm được đối</option>
                        <option value="all" <?php echo $trangThai === 'all' ? 'selected' : ''; ?>>Tất cả trạng thái</option>
                    </select>
                </div>
            <?php endif; ?>

            <!-- Nút Lọc & Đặt lại -->
            <div class="col-md-1 col-12 d-flex align-items-end gap-1">
                <button type="submit" class="btn btn-primary-pitch w-100" title="Tìm kiếm">
                    <i class="bi bi-funnel"></i>
                </button>
                <a href="<?php echo BASE_URL; ?>/pages/tim-doi-bong.php" class="btn btn-outline-secondary" title="Đặt lại bộ lọc">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- DANH SÁCH TIN TÌM ĐỐI -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="fw-bold mb-0 text-navy">
            Danh Sách Kèo Giao Lưu (<?php echo count($matches); ?> tin phù hợp)
        </h5>
        <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#addMatchModal">
            <i class="bi bi-plus-lg me-1"></i> Đăng tin mới
        </button>
    </div>

    <?php if (empty($matches)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center my-4">
            <div class="display-3 text-muted mb-3"><i class="bi bi-emoji-smile"></i></div>
            <h4 class="fw-bold text-navy">Chưa có đội bóng nào phù hợp với bộ lọc hiện tại</h4>
            <p class="text-muted mx-auto mb-4" style="max-width: 500px;">
                Bạn có thể mở rộng bán kính tìm kiếm, chọn ngày thi đấu khác hoặc trở thành người đầu tiên đăng tin tìm đối tại khu vực này!
            </p>
            <div>
                <button type="button" class="btn btn-success px-4 py-2 rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#addMatchModal">
                    <i class="bi bi-plus-circle me-1"></i> Đăng Tin Tìm Đối Ngay Bây Giờ
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($matches as $m): 
                $canManage = canManageMatchPost($m, $currentUser);
            ?>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-4 transition-hover" style="border-left: 5px solid #10b981 !important;">
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <img src="<?php echo e($m['anh_dai_dien'] ?: 'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?auto=format&fit=crop&w=400&q=80'); ?>" 
                                     alt="<?php echo e($m['ten_doi']); ?>" 
                                     class="rounded-circle object-fit-cover border shadow-sm" 
                                     style="width: 56px; height: 56px;">
                                <div>
                                    <h5 class="fw-bold text-navy mb-1"><?php echo e($m['ten_doi']); ?></h5>
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="badge bg-success-subtle text-success border border-success"><?php echo e($m['loai_san']); ?></span>
                                        <?php echo renderMatchSkillBadge($m['trinh_do']); ?>
                                        <?php echo renderMatchStatusBadge($m['trang_thai']); ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Menu quản lý (nếu có quyền) -->
                            <?php if ($canManage): ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light rounded-circle" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                        <?php if ($m['trang_thai'] === 'Đang tìm đối'): ?>
                                            <li>
                                                <form action="<?php echo BASE_URL; ?>/actions/process_tim_doi.php" method="POST">
                                                    <?php echo getCsrfField(); ?>
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>">
                                                    <input type="hidden" name="trang_thai" value="Đã tìm được đối">
                                                    <button type="submit" class="dropdown-item text-primary">
                                                        <i class="bi bi-check2-circle me-2"></i> Đánh dấu: Đã có đối
                                                    </button>
                                                </form>
                                            </li>
                                        <?php else: ?>
                                            <li>
                                                <form action="<?php echo BASE_URL; ?>/actions/process_tim_doi.php" method="POST">
                                                    <?php echo getCsrfField(); ?>
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>">
                                                    <input type="hidden" name="trang_thai" value="Đang tìm đối">
                                                    <button type="submit" class="dropdown-item text-success">
                                                        <i class="bi bi-broadcast me-2"></i> Đăng tìm đối lại
                                                    </button>
                                                </form>
                                            </li>
                                        <?php endif; ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="<?php echo BASE_URL; ?>/actions/process_tim_doi.php" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tin này không?');">
                                                <?php echo getCsrfField(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>">
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="bi bi-trash me-2"></i> Xóa tin này
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Thông tin chi tiết trận đấu -->
                        <div class="row g-2 mb-3 py-2 bg-light rounded-3 px-3">
                            <div class="col-sm-6">
                                <small class="text-muted d-block"><i class="bi bi-calendar-event text-primary me-1"></i> Ngày & Giờ đá:</small>
                                <strong class="text-dark"><?php echo formatDate($m['ngay_da']); ?> lúc <?php echo substr($m['gio_da'], 0, 5); ?></strong> 
                                <span class="badge bg-secondary-subtle text-secondary small"><?php echo (int)$m['thoi_luong']; ?> phút</span>
                            </div>
                            <div class="col-sm-6">
                                <small class="text-muted d-block"><i class="bi bi-geo-alt text-danger me-1"></i> Khu vực / Sân:</small>
                                <strong class="text-dark text-truncate d-block" title="<?php echo e($m['ten_cum_san'] ?: $m['khu_vuc']); ?>">
                                    <?php echo e($m['ten_cum_san'] ?: $m['khu_vuc']); ?>
                                </strong>
                            </div>
                        </div>

                        <!-- Khoảng cách nếu có tọa độ GPS hợp lệ -->
                        <?php if ($m['distance'] !== null): ?>
                            <div class="mb-2">
                                <span class="badge bg-primary-subtle text-primary border border-primary">
                                    <i class="bi bi-compass me-1"></i> Cách sân chọn ~<?php echo $m['distance']; ?> km
                                </span>
                            </div>
                        <?php endif; ?>

                        <!-- Mô tả / Yêu cầu -->
                        <?php if (!empty($m['mo_ta'])): ?>
                            <p class="text-secondary small mb-3">
                                <?php echo nl2br(e($m['mo_ta'])); ?>
                            </p>
                        <?php endif; ?>

                        <!-- Thông tin liên hệ & Nút bấm -->
                        <div class="mt-auto pt-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div>
                                <small class="text-muted d-block">Liên hệ:</small>
                                <span class="fw-bold text-navy"><?php echo e($m['nguoi_lien_he']); ?></span>
                            </div>

                            <div class="d-flex gap-2">
                                <!-- Nút gọi điện thoại -->
                                <a href="tel:<?php echo e($m['so_dien_thoai']); ?>" class="btn btn-sm btn-outline-success px-3 py-2">
                                    <i class="bi bi-telephone-fill me-1"></i> <?php echo e($m['so_dien_thoai']); ?>
                                </a>

                                <!-- Nút Zalo -->
                                <?php if (!empty($m['zalo'])): 
                                    $zaloPhone = preg_replace('/[^0-9]/', '', $m['zalo']);
                                    $zaloUrl = (str_starts_with($m['zalo'], 'http')) ? $m['zalo'] : "https://zalo.me/{$zaloPhone}";
                                ?>
                                    <a href="<?php echo e($zaloUrl); ?>" target="_blank" class="btn btn-sm btn-primary px-3 py-2" title="Nhắn tin qua Zalo">
                                        <i class="bi bi-chat-dots-fill me-1"></i> Zalo
                                    </a>
                                <?php endif; ?>

                                <!-- Nút chi tiết Modal -->
                                <button type="button" class="btn btn-sm btn-light border px-2 py-2" data-bs-toggle="modal" data-bs-target="#matchModal<?php echo $m['id']; ?>">
                                    <i class="bi bi-info-circle"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MODAL CHI TIẾT TRẬN ĐẤU -->
                <div class="modal fade" id="matchModal<?php echo $m['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow rounded-4">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title fw-bold text-navy">Chi Tiết Kèo Giao Lưu</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="text-center mb-4">
                                    <img src="<?php echo e($m['anh_dai_dien'] ?: 'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?auto=format&fit=crop&w=400&q=80'); ?>" 
                                         class="rounded-circle object-fit-cover border shadow-sm mb-2" style="width: 72px; height: 72px;" alt="">
                                    <h4 class="fw-bold text-navy mb-1"><?php echo e($m['ten_doi']); ?></h4>
                                    <div class="d-flex justify-content-center gap-1">
                                        <span class="badge bg-success"><?php echo e($m['loai_san']); ?></span>
                                        <?php echo renderMatchSkillBadge($m['trinh_do']); ?>
                                        <?php echo renderMatchStatusBadge($m['trang_thai']); ?>
                                    </div>
                                </div>

                                <ul class="list-group list-group-flush mb-4">
                                    <li class="list-group-item px-0 d-flex justify-content-between">
                                        <span class="text-muted"><i class="bi bi-calendar-event me-2"></i>Ngày thi đấu:</span>
                                        <strong class="text-navy"><?php echo formatDate($m['ngay_da']); ?></strong>
                                    </li>
                                    <li class="list-group-item px-0 d-flex justify-content-between">
                                        <span class="text-muted"><i class="bi bi-clock me-2"></i>Giờ bắt đầu:</span>
                                        <strong class="text-navy"><?php echo substr($m['gio_da'], 0, 5); ?> (Thời lượng: <?php echo (int)$m['thoi_luong']; ?> phút)</strong>
                                    </li>
                                    <li class="list-group-item px-0 d-flex justify-content-between">
                                        <span class="text-muted"><i class="bi bi-geo-alt me-2"></i>Khu vực / Cụm sân:</span>
                                        <strong class="text-navy text-end"><?php echo e($m['ten_cum_san'] ?: $m['khu_vuc']); ?></strong>
                                    </li>
                                    <?php if (!empty($m['dia_chi_cum'])): ?>
                                        <li class="list-group-item px-0 d-flex justify-content-between">
                                            <span class="text-muted"><i class="bi bi-map me-2"></i>Địa chỉ sân:</span>
                                            <small class="text-muted text-end" style="max-width: 250px;"><?php echo e($m['dia_chi_cum']); ?></small>
                                        </li>
                                    <?php endif; ?>
                                    <li class="list-group-item px-0 d-flex justify-content-between">
                                        <span class="text-muted"><i class="bi bi-person me-2"></i>Người liên hệ:</span>
                                        <strong class="text-navy"><?php echo e($m['nguoi_lien_he']); ?></strong>
                                    </li>
                                    <li class="list-group-item px-0 d-flex justify-content-between">
                                        <span class="text-muted"><i class="bi bi-telephone me-2"></i>Số điện thoại:</span>
                                        <a href="tel:<?php echo e($m['so_dien_thoai']); ?>" class="fw-bold text-success text-decoration-none"><?php echo e($m['so_dien_thoai']); ?></a>
                                    </li>
                                </ul>

                                <?php if (!empty($m['mo_ta'])): ?>
                                    <div class="mb-4">
                                        <h6 class="fw-bold text-navy mb-2">Lời nhắn từ đội bóng:</h6>
                                        <div class="p-3 bg-light rounded-3 text-secondary small">
                                            <?php echo nl2br(e($m['mo_ta'])); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="d-grid gap-2">
                                    <a href="tel:<?php echo e($m['so_dien_thoai']); ?>" class="btn btn-success py-2 fw-semibold">
                                        <i class="bi bi-telephone-fill me-2"></i> Gọi Điện Ngay Cho Đội Trưởng
                                    </a>
                                    <?php if (!empty($m['zalo'])): 
                                        $zPhone = preg_replace('/[^0-9]/', '', $m['zalo']);
                                        $zUrl = (str_starts_with($m['zalo'], 'http')) ? $m['zalo'] : "https://zalo.me/{$zPhone}";
                                    ?>
                                        <a href="<?php echo e($zUrl); ?>" target="_blank" class="btn btn-primary py-2 fw-semibold">
                                            <i class="bi bi-chat-dots-fill me-2"></i> Nhắn Tin Trực Tiếp Qua Zalo
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL ĐĂNG TIN TÌM ĐỐI -->
<div class="modal fade" id="addMatchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom p-4">
                <h5 class="modal-title fw-bold text-navy">
                    <i class="bi bi-megaphone-fill text-success me-2"></i> Đăng Tin Tìm Đối Thủ Đá Bóng
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="<?php echo BASE_URL; ?>/actions/process_tim_doi.php" method="POST">
                <?php echo getCsrfField(); ?>
                <input type="hidden" name="action" value="add">

                <div class="modal-body p-4">
                    <div class="alert alert-success d-flex align-items-center small mb-4 py-2">
                        <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                        <div>Điền đầy đủ thông tin để các đội bóng quanh khu vực dễ dàng liên hệ và sắp xếp trận đấu nhanh nhất!</div>
                    </div>

                    <div class="row g-3">
                        <!-- Tên đội bóng -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tên đội bóng của bạn <span class="text-danger">*</span></label>
                            <input type="text" name="ten_doi" class="form-control" placeholder="Ví dụ: FC Anh Em, FC Văn Phòng..." required>
                        </div>

                        <!-- Quy mô sân -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Quy mô sân mong muốn <span class="text-danger">*</span></label>
                            <select name="loai_san" class="form-select" required>
                                <option value="Sân 7 người" selected>Sân 7 người (Phổ biến)</option>
                                <option value="Sân 5 người">Sân 5 người</option>
                                <option value="Sân 11 người">Sân 11 người</option>
                            </select>
                        </div>

                        <!-- Cụm sân / Sân bóng dự kiến -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Cụm sân thi đấu dự kiến</label>
                            <select name="cum_san_id" class="form-select">
                                <option value="">-- Chưa chốt cụm sân cụ thể --</option>
                                <?php foreach ($allCumSan as $c): ?>
                                    <option value="<?php echo (int)$c['id']; ?>" <?php echo $cumId === (int)$c['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($c['ten_cum_san']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Khu vực mong muốn -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Khu vực muốn đá <span class="text-danger">*</span></label>
                            <input type="text" name="khu_vuc" class="form-control" 
                                   value="<?php echo e($selectedPitch['dia_chi'] ?? ($selectedCum['dia_chi'] ?? '')); ?>" 
                                   placeholder="Ví dụ: Bến Thủy, Hưng Dũng, TP Vinh..." required>
                        </div>

                        <!-- Ngày đá -->
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Ngày dự kiến đá <span class="text-danger">*</span></label>
                            <input type="date" name="ngay_da" class="form-control" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <!-- Giờ đá -->
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Giờ bắt đầu <span class="text-danger">*</span></label>
                            <input type="time" name="gio_da" class="form-control" value="17:30" required>
                        </div>

                        <!-- Thời lượng -->
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Thời lượng trận</label>
                            <select name="thoi_luong" class="form-select">
                                <option value="60">60 phút (1 tiếng)</option>
                                <option value="90" selected>90 phút (1.5 tiếng)</option>
                                <option value="120">120 phút (2 tiếng)</option>
                            </select>
                        </div>

                        <!-- Trình độ đội -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Trình độ đội bóng của bạn <span class="text-danger">*</span></label>
                            <select name="trinh_do" class="form-select" required>
                                <option value="Giao lưu">Giao lưu (Vui vẻ, nhẹ nhàng, không va chạm)</option>
                                <option value="Phong trào" selected>Phong trào (Đá thường xuyên, thể lực trung bình)</option>
                                <option value="Trung bình">Trung bình (Có chiến thuật, cọ xát tốt)</option>
                                <option value="Khá">Khá (Tập luyện đều, đá giải phong trào)</option>
                            </select>
                        </div>

                        <!-- Ảnh logo / đại diện đội -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Link ảnh đại diện đội (Không bắt buộc)</label>
                            <input type="url" name="anh_dai_dien" class="form-control" placeholder="https://...">
                        </div>

                        <!-- Tên người liên hệ -->
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Người liên hệ <span class="text-danger">*</span></label>
                            <input type="text" name="nguoi_lien_he" class="form-control" 
                                   value="<?php echo e($currentUser['ho_ten'] ?? ''); ?>" 
                                   placeholder="Tên đội trưởng..." required>
                        </div>

                        <!-- SĐT -->
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Số điện thoại liên hệ <span class="text-danger">*</span></label>
                            <input type="tel" name="so_dien_thoai" class="form-control" 
                                   value="<?php echo e($currentUser['so_dien_thoai'] ?? ''); ?>" 
                                   placeholder="0981 123 456" required>
                        </div>

                        <!-- Zalo -->
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Zalo liên hệ (Nếu có)</label>
                            <input type="text" name="zalo" class="form-control" 
                                   value="<?php echo e($currentUser['so_dien_thoai'] ?? ''); ?>" 
                                   placeholder="SĐT hoặc link Zalo">
                        </div>

                        <!-- Mô tả & Yêu cầu -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nội dung giới thiệu & Thỏa thuận</label>
                            <textarea name="mo_ta" class="form-control" rows="3" 
                                      placeholder="Ví dụ: Đội văn phòng đá giao lưu rèn luyện sức khỏe, chia tiền sân nước 50/50. Cần tìm đội đá đẹp, không cay cú..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-success px-4 fw-bold">
                        <i class="bi bi-send-fill me-1"></i> Xác Nhận Đăng Tin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

