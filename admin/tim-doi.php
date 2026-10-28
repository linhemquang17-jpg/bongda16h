<?php
/**
 * Quản Lý Tin Tìm Đối Đá Bóng - Admin
 * Dự án: BongDa16h
 */
$pageTitle = 'Quản Lý Tin Tìm Đối';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/auth_check.php';

$pdo = getDBConnection();

// Xử lý các action (Đổi trạng thái, Xóa tin)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Yêu cầu không hợp lệ!');
        header('Location: ' . BASE_URL . '/admin/tim-doi.php');
        exit;
    }

    $action = sanitize($_POST['action'] ?? '');

    if ($action === 'update_status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = sanitize($_POST['trang_thai'] ?? '');
        $validStatuses = ['Đang tìm đối', 'Đã tìm được đối', 'Đã hủy'];

        if ($id > 0 && in_array($status, $validStatuses)) {
            try {
                $stmt = $pdo->prepare("UPDATE tim_doi_bong SET trang_thai = ? WHERE id = ?");
                $stmt->execute([$status, $id]);
                setFlash('success', "Cập nhật trạng thái tin #{$id} thành \"{$status}\" thành công!");
            } catch (Exception $e) {
                setFlash('danger', 'Lỗi cập nhật: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM tim_doi_bong WHERE id = ?");
                $stmt->execute([$id]);
                setFlash('success', "Đã xóa tin tìm đối #{$id} thành công!");
            } catch (Exception $e) {
                setFlash('danger', 'Lỗi khi xóa: ' . $e->getMessage());
            }
        }
    }

    header('Location: ' . BASE_URL . '/admin/tim-doi.php');
    exit;
}

// Bộ lọc
$keyword = sanitize($_GET['keyword'] ?? '');
$statusFilter = sanitize($_GET['trang_thai'] ?? '');

$sql = "
    SELECT t.*, 
           c.ten_cum_san,
           s.ten_san,
           u.ho_ten as ten_tai_khoan
    FROM tim_doi_bong t
    LEFT JOIN cum_san c ON t.cum_san_id = c.id
    LEFT JOIN san_bong s ON t.san_bong_id = s.id
    LEFT JOIN nguoi_dung u ON t.nguoi_dung_id = u.id
    WHERE 1=1
";
$params = [];

if (!empty($keyword)) {
    $sql .= " AND (t.ten_doi LIKE ? OR t.khu_vuc LIKE ? OR t.nguoi_lien_he LIKE ? OR t.so_dien_thoai LIKE ?)";
    $kw = "%{$keyword}%";
    $params[] = $kw;
    $params[] = $kw;
    $params[] = $kw;
    $params[] = $kw;
}

if (!empty($statusFilter) && $statusFilter !== 'all') {
    $sql .= " AND t.trang_thai = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY t.id DESC";

$posts = [];
$timDoiWarning = null;
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();
} catch (Throwable $e) {
    $timDoiWarning = "Chưa tìm thấy bảng dữ liệu tìm đối hoặc truy vấn bị gián đoạn: " . $e->getMessage();
}

require_once __DIR__ . '/header.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-navy">Quản Lý Tin Tìm Đối Đá Bóng</h4>
        <p class="text-muted small mb-0">Kiểm duyệt, quản lý và theo dõi các tin đăng tìm đối của các đội bóng</p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>/pages/tim-doi-bong.php" target="_blank" class="btn btn-outline-success btn-sm">
            <i class="bi bi-box-arrow-up-right me-1"></i> Xem trang ngoài giao diện
        </a>
    </div>
</div>

<?php if (!empty($timDoiWarning)): ?>
    <div class="alert alert-warning border-0 shadow-sm rounded-3 mb-4">
        <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
        <?php echo e($timDoiWarning); ?> - <a href="<?php echo BASE_URL; ?>/admin/diagnostics.php" class="fw-bold text-dark text-decoration-none">Khởi tạo bảng tim_doi_bong</a>
    </div>
<?php endif; ?>

<?php renderFlash(); ?>

<!-- BỘ LỌC TÌM KIẾM -->
<div class="admin-card mb-4 p-3">
    <form action="<?php echo BASE_URL; ?>/admin/tim-doi.php" method="GET" class="row g-2">
        <div class="col-md-5">
            <input type="text" name="keyword" class="form-control form-control-sm" 
                   placeholder="Tìm theo tên đội, người liên hệ, SĐT, khu vực..." value="<?php echo e($keyword); ?>">
        </div>
        <div class="col-md-3">
            <select name="trang_thai" class="form-select form-select-sm">
                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>-- Tất cả trạng thái --</option>
                <option value="Đang tìm đối" <?php echo $statusFilter === 'Đang tìm đối' ? 'selected' : ''; ?>>Đang tìm đối</option>
                <option value="Đã tìm được đối" <?php echo $statusFilter === 'Đã tìm được đối' ? 'selected' : ''; ?>>Đã tìm được đối</option>
                <option value="Đã hủy" <?php echo $statusFilter === 'Đã hủy' ? 'selected' : ''; ?>>Đã hủy</option>
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary-pitch btn-sm">
                <i class="bi bi-search me-1"></i> Tìm kiếm
            </button>
            <a href="<?php echo BASE_URL; ?>/admin/tim-doi.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Đặt lại
            </a>
        </div>
    </form>
</div>

<!-- BẢNG DANH SÁCH -->
<div class="admin-card">
    <div class="admin-card-header d-flex justify-content-between align-items-center">
        <span>Danh sách tin tìm đối (Tổng: <?php echo count($posts); ?> tin)</span>
    </div>

    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Đội bóng</th>
                    <th>Quy mô & Trình độ</th>
                    <th>Lịch đá & Khu vực</th>
                    <th>Người liên hệ</th>
                    <th>Trạng thái</th>
                    <th class="text-end" style="width: 140px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($posts)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Không có tin tìm đối nào phù hợp với bộ lọc!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($posts as $p): ?>
                        <tr>
                            <td><strong>#<?php echo (int)$p['id']; ?></strong></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?php echo e($p['anh_dai_dien'] ?: 'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?auto=format&fit=crop&w=100&q=80'); ?>" 
                                         class="rounded-circle object-fit-cover border" style="width: 40px; height: 40px;" alt="">
                                    <div>
                                        <div class="fw-bold text-navy"><?php echo e($p['ten_doi']); ?></div>
                                        <?php if (!empty($p['ten_tai_khoan'])): ?>
                                            <small class="text-muted"><i class="bi bi-person me-1"></i>Acc: <?php echo e($p['ten_tai_khoan']); ?></small>
                                        <?php else: ?>
                                            <small class="text-muted"><i class="bi bi-person-slash me-1"></i>Khách vãng lai</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-success-subtle text-success border border-success mb-1 d-inline-block"><?php echo e($p['loai_san']); ?></span>
                                <br>
                                <?php echo renderMatchSkillBadge($p['trinh_do']); ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">
                                    <i class="bi bi-calendar-event text-primary me-1"></i> <?php echo formatDate($p['ngay_da']); ?> lúc <?php echo substr($p['gio_da'], 0, 5); ?>
                                </div>
                                <small class="text-muted d-block text-truncate" style="max-width: 200px;">
                                    <i class="bi bi-geo-alt text-danger me-1"></i> <?php echo e($p['ten_cum_san'] ?: $p['khu_vuc']); ?>
                                </small>
                            </td>
                            <td>
                                <div class="fw-bold"><?php echo e($p['nguoi_lien_he']); ?></div>
                                <a href="tel:<?php echo e($p['so_dien_thoai']); ?>" class="small text-decoration-none text-success">
                                    <i class="bi bi-telephone-fill me-1"></i><?php echo e($p['so_dien_thoai']); ?>
                                </a>
                                <?php if (!empty($p['zalo'])): ?>
                                    <span class="badge bg-primary-subtle text-primary ms-1 small">Zalo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <!-- Dropdown đổi nhanh trạng thái -->
                                <form method="POST" class="d-inline">
                                    <?php echo getCsrfField(); ?>
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                    <select name="trang_thai" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 135px;">
                                        <option value="Đang tìm đối" <?php echo $p['trang_thai'] === 'Đang tìm đối' ? 'selected' : ''; ?>>🟢 Đang tìm đối</option>
                                        <option value="Đã tìm được đối" <?php echo $p['trang_thai'] === 'Đã tìm được đối' ? 'selected' : ''; ?>>🔵 Đã có đối</option>
                                        <option value="Đã hủy" <?php echo $p['trang_thai'] === 'Đã hủy' ? 'selected' : ''; ?>>⚪ Đã hủy</option>
                                    </select>
                                </form>
                            </td>
                            <td class="text-end">
                                <!-- Xem chi tiết Modal -->
                                <button type="button" class="btn btn-sm btn-outline-info me-1" data-bs-toggle="modal" data-bs-target="#adminViewMatch<?php echo $p['id']; ?>" title="Xem chi tiết">
                                    <i class="bi bi-eye"></i>
                                </button>

                                <!-- Xóa tin -->
                                <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tin này vĩnh viễn?');">
                                    <?php echo getCsrfField(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa tin">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- MODAL CHI TIẾT DÀNH CHO ADMIN -->
                        <div class="modal fade" id="adminViewMatch<?php echo $p['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow rounded-4">
                                    <div class="modal-header border-bottom">
                                        <h5 class="modal-title fw-bold text-navy">Chi Tiết Tin Tìm Đối #<?php echo (int)$p['id']; ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <div class="mb-3 text-center">
                                            <img src="<?php echo e($p['anh_dai_dien'] ?: 'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?auto=format&fit=crop&w=400&q=80'); ?>" 
                                                 class="rounded-circle border shadow-sm mb-2" style="width: 72px; height: 72px; object-fit: cover;" alt="">
                                            <h5 class="fw-bold text-navy mb-0"><?php echo e($p['ten_doi']); ?></h5>
                                            <small class="text-muted">Đăng lúc: <?php echo formatDateTime($p['created_at']); ?></small>
                                        </div>

                                        <ul class="list-group list-group-flush small mb-3">
                                            <li class="list-group-item d-flex justify-content-between px-0">
                                                <span class="text-muted">Quy mô sân:</span>
                                                <strong><?php echo e($p['loai_san']); ?></strong>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between px-0">
                                                <span class="text-muted">Trình độ đội:</span>
                                                <strong><?php echo e($p['trinh_do']); ?></strong>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between px-0">
                                                <span class="text-muted">Ngày & giờ đá:</span>
                                                <strong><?php echo formatDate($p['ngay_da']); ?> lúc <?php echo substr($p['gio_da'], 0, 5); ?> (<?php echo (int)$p['thoi_luong']; ?> phút)</strong>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between px-0">
                                                <span class="text-muted">Khu vực / Cụm sân:</span>
                                                <strong class="text-end"><?php echo e($p['ten_cum_san'] ?: $p['khu_vuc']); ?></strong>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between px-0">
                                                <span class="text-muted">Người liên hệ:</span>
                                                <strong><?php echo e($p['nguoi_lien_he']); ?></strong>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between px-0">
                                                <span class="text-muted">Số điện thoại:</span>
                                                <a href="tel:<?php echo e($p['so_dien_thoai']); ?>" class="text-success fw-bold"><?php echo e($p['so_dien_thoai']); ?></a>
                                            </li>
                                            <?php if (!empty($p['zalo'])): ?>
                                                <li class="list-group-item d-flex justify-content-between px-0">
                                                    <span class="text-muted">Zalo:</span>
                                                    <span><?php echo e($p['zalo']); ?></span>
                                                </li>
                                            <?php endif; ?>
                                        </ul>

                                        <?php if (!empty($p['mo_ta'])): ?>
                                            <div class="p-3 bg-light rounded-3 small">
                                                <strong class="d-block mb-1">Mô tả / Lời nhắn:</strong>
                                                <p class="text-secondary mb-0"><?php echo nl2br(e($p['mo_ta'])); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="modal-footer border-top">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
