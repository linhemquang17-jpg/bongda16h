<?php
/**
 * Quản Lý Cụm Sân - Admin
 * Dự án: BongDa16h
 */
$pageTitle = 'Quản Lý Cụm Sân';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/auth_check.php';

$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Yêu cầu không hợp lệ!');
        header('Location: ' . BASE_URL . '/admin/cum-san.php');
        exit;
    }

    $action = sanitize($_POST['action'] ?? '');

    if ($action === 'add') {
        $tenCum   = sanitize($_POST['ten_cum_san'] ?? '');
        $diaChi   = sanitize($_POST['dia_chi'] ?? '');
        $linkMap  = sanitize($_POST['link_google_map'] ?? '');
        $gioMo    = sanitize($_POST['gio_mo_cua'] ?? '06:00:00');
        $gioDong  = sanitize($_POST['gio_dong_cua'] ?? '23:00:00');
        $sdt      = sanitize($_POST['so_dien_thoai_lien_he'] ?? '');

        if (empty($tenCum) || empty($diaChi)) {
            setFlash('danger', 'Vui lòng nhập tên cụm sân và địa chỉ!');
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO cum_san (ten_cum_san, dia_chi, link_google_map, gio_mo_cua, gio_dong_cua, so_dien_thoai_lien_he)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$tenCum, $diaChi, !empty($linkMap) ? $linkMap : null, $gioMo, $gioDong, $sdt]);
                setFlash('success', 'Thêm cụm sân mới thành công!');
            } catch (Exception $e) {
                setFlash('danger', 'Lỗi thêm cụm sân: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'edit') {
        $id       = (int)($_POST['id'] ?? 0);
        $tenCum   = sanitize($_POST['ten_cum_san'] ?? '');
        $diaChi   = sanitize($_POST['dia_chi'] ?? '');
        $linkMap  = sanitize($_POST['link_google_map'] ?? '');
        $gioMo    = sanitize($_POST['gio_mo_cua'] ?? '06:00:00');
        $gioDong  = sanitize($_POST['gio_dong_cua'] ?? '23:00:00');
        $sdt      = sanitize($_POST['so_dien_thoai_lien_he'] ?? '');

        if (!$id || empty($tenCum) || empty($diaChi)) {
            setFlash('danger', 'Thông tin cập nhật không hợp lệ!');
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE cum_san 
                    SET ten_cum_san = ?, dia_chi = ?, link_google_map = ?, gio_mo_cua = ?, gio_dong_cua = ?, so_dien_thoai_lien_he = ?
                    WHERE id = ?
                ");
                $stmt->execute([$tenCum, $diaChi, !empty($linkMap) ? $linkMap : null, $gioMo, $gioDong, $sdt, $id]);
                setFlash('success', "Cập nhật cụm sân #{$id} thành công!");
            } catch (Exception $e) {
                setFlash('danger', 'Lỗi cập nhật: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $chk = $pdo->prepare("SELECT COUNT(*) FROM san_bong WHERE cum_san_id = ?");
            $chk->execute([$id]);
            if ($chk->fetchColumn() > 0) {
                setFlash('warning', 'Cụm sân này đang có các sân bóng trực thuộc, bạn phải xóa hoặc chuyển các sân con trước khi xóa cụm sân!');
            } else {
                try {
                    $pdo->prepare("DELETE FROM hinh_anh_san WHERE cum_san_id = ?")->execute([$id]);
                    $pdo->prepare("DELETE FROM cum_san WHERE id = ?")->execute([$id]);
                    setFlash('success', 'Xóa cụm sân thành công!');
                } catch (Exception $e) {
                    setFlash('danger', 'Lỗi khi xóa: ' . $e->getMessage());
                }
            }
        }
    }

    header('Location: ' . BASE_URL . '/admin/cum-san.php');
    exit;
}

$stmt = $pdo->query("
    SELECT c.*, COUNT(s.id) as total_pitches 
    FROM cum_san c
    LEFT JOIN san_bong s ON c.id = s.cum_san_id
    GROUP BY c.id
    ORDER BY c.id ASC
");
$complexes = $stmt->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold text-navy mb-1">Quản Lý Cụm Sân</h3>
        <p class="text-muted mb-0">Hệ thống các địa điểm, giờ mở cửa và thông tin liên hệ cụm sân</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary-pitch" data-bs-toggle="modal" data-bs-target="#addComplexModal">
            <i class="bi bi-plus-circle me-1"></i> Thêm Cụm Sân Mới
        </button>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <span>Danh sách cụm sân (Tổng: <?php echo count($complexes); ?> cụm)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên cụm sân</th>
                    <th>Địa chỉ</th>
                    <th>Giờ hoạt động</th>
                    <th>Hotline</th>
                    <th>Số sân bóng</th>
                    <th>Bản đồ</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($complexes as $c): ?>
                    <tr>
                        <td><strong>#<?php echo (int)$c['id']; ?></strong></td>
                        <td class="fw-bold text-navy"><?php echo e($c['ten_cum_san']); ?></td>
                        <td><?php echo e($c['dia_chi']); ?></td>
                        <td><small class="text-success fw-semibold"><?php echo substr($c['gio_mo_cua'], 0, 5); ?> - <?php echo substr($c['gio_dong_cua'], 0, 5); ?></small></td>
                        <td><?php echo e($c['so_dien_thoai_lien_he'] ?? '—'); ?></td>
                        <td><span class="badge bg-primary-subtle text-primary"><?php echo (int)$c['total_pitches']; ?> sân</span></td>
                        <td>
                            <?php if (!empty($c['link_google_map'])): ?>
                                <a href="<?php echo e($c['link_google_map']); ?>" target="_blank" class="text-danger small"><i class="bi bi-geo-alt-fill"></i> Link map</a>
                            <?php else: ?>
                                <span class="text-muted small">Tự động</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editComplexModal<?php echo $c['id']; ?>">
                                <i class="bi bi-pencil"></i> Sửa
                            </button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa cụm sân này?');">
                                <?php echo getCsrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>

                    <!-- MODAL SỬA CỤM SÂN -->
                    <div class="modal fade" id="editComplexModal<?php echo $c['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <?php echo getCsrfField(); ?>
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Sửa Cụm Sân #<?php echo $c['id']; ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Tên cụm sân <span class="text-danger">*</span></label>
                                            <input type="text" name="ten_cum_san" class="form-control" value="<?php echo e($c['ten_cum_san']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Địa chỉ chi tiết <span class="text-danger">*</span></label>
                                            <input type="text" name="dia_chi" class="form-control" value="<?php echo e($c['dia_chi']); ?>" required>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Giờ mở cửa</label>
                                                <input type="time" name="gio_mo_cua" class="form-control" value="<?php echo substr($c['gio_mo_cua'], 0, 5); ?>" required>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Giờ đóng cửa</label>
                                                <input type="time" name="gio_dong_cua" class="form-control" value="<?php echo substr($c['gio_dong_cua'], 0, 5); ?>" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Số điện thoại hotline</label>
                                            <input type="tel" name="so_dien_thoai_lien_he" class="form-control" value="<?php echo e($c['so_dien_thoai_lien_he'] ?? ''); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Đường dẫn Google Map (Link URL)</label>
                                            <input type="url" name="link_google_map" class="form-control" value="<?php echo e($c['link_google_map'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
                                        <button type="submit" class="btn btn-primary-pitch btn-sm">Lưu Thay Đổi</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL THÊM CỤM SÂN -->
<div class="modal fade" id="addComplexModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo getCsrfField(); ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Thêm Cụm Sân Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tên cụm sân <span class="text-danger">*</span></label>
                        <input type="text" name="ten_cum_san" class="form-control" placeholder="Ví dụ: Cụm Sân Thể Thao Bến Thủy" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Địa chỉ chi tiết <span class="text-danger">*</span></label>
                        <input type="text" name="dia_chi" class="form-control" placeholder="Ví dụ: Đường Nguyễn Du, TP. Vinh" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Giờ mở cửa</label>
                            <input type="time" name="gio_mo_cua" class="form-control" value="06:00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Giờ đóng cửa</label>
                            <input type="time" name="gio_dong_cua" class="form-control" value="23:00" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Số điện thoại hotline</label>
                        <input type="tel" name="so_dien_thoai_lien_he" class="form-control" placeholder="0981122334">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Đường dẫn Google Map (Link URL)</label>
                        <input type="url" name="link_google_map" class="form-control" placeholder="https://maps.google.com/...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary-pitch btn-sm">Thêm Cụm Sân</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

