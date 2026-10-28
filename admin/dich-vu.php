<?php
/**
 * Quản Lý Dịch Vụ Bổ Sung - Admin
 * Dự án: BongDa16h
 */
$pageTitle = 'Quản Lý Dịch Vụ';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/auth_check.php';

$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Yêu cầu không hợp lệ!');
        header('Location: ' . BASE_URL . '/admin/dich-vu.php');
        exit;
    }

    $action = sanitize($_POST['action'] ?? '');

    if ($action === 'add') {
        $tenDv   = sanitize($_POST['ten_dich_vu'] ?? '');
        $donGia  = (float)($_POST['don_gia'] ?? 0);
        $donViTinh = sanitize($_POST['don_vi_tinh'] ?? 'Chai/Bộ');

        if (empty($tenDv) || $donGia < 0) {
            setFlash('danger', 'Vui lòng nhập tên dịch vụ và đơn giá hợp lệ!');
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO dich_vu (ten_dich_vu, don_gia, don_vi_tinh) VALUES (?, ?, ?)");
                $stmt->execute([$tenDv, $donGia, $donViTinh]);
                setFlash('success', 'Thêm dịch vụ mới thành công!');
            } catch (Exception $e) {
                setFlash('danger', 'Lỗi thêm dịch vụ: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'edit') {
        $id      = (int)($_POST['id'] ?? 0);
        $tenDv   = sanitize($_POST['ten_dich_vu'] ?? '');
        $donGia  = (float)($_POST['don_gia'] ?? 0);
        $donViTinh = sanitize($_POST['don_vi_tinh'] ?? 'Chai/Bộ');

        if (!$id || empty($tenDv) || $donGia < 0) {
            setFlash('danger', 'Thông tin cập nhật dịch vụ không hợp lệ!');
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE dich_vu SET ten_dich_vu = ?, don_gia = ?, don_vi_tinh = ? WHERE id = ?");
                $stmt->execute([$tenDv, $donGia, $donViTinh, $id]);
                setFlash('success', "Cập nhật dịch vụ #{$id} thành công!");
            } catch (Exception $e) {
                setFlash('danger', 'Lỗi cập nhật: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $usedCount = 0;
            try {
                $chk = $pdo->prepare("SELECT COUNT(*) FROM chi_tiet_dich_vu WHERE dich_vu_id = ?");
                $chk->execute([$id]);
                $usedCount = (int)$chk->fetchColumn();
            } catch (Throwable $e) {
                $usedCount = 0;
            }

            if ($usedCount > 0) {
                setFlash('warning', 'Dịch vụ này đã có trong lịch sử đơn đặt sân của khách, không thể xóa để bảo toàn chứng từ!');
            } else {
                try {
                    $pdo->prepare("DELETE FROM dich_vu WHERE id = ?")->execute([$id]);
                    setFlash('success', 'Xóa dịch vụ thành công!');
                } catch (Exception $e) {
                    setFlash('danger', 'Lỗi xóa: ' . $e->getMessage());
                }
            }
        }
    }

    header('Location: ' . BASE_URL . '/admin/dich-vu.php');
    exit;
}

$services = $pdo->query("SELECT * FROM dich_vu ORDER BY id ASC")->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold text-navy mb-1">Quản Lý Dịch Vụ Bổ Sung</h3>
        <p class="text-muted mb-0">Nước giải khát, thuê áo tập, bóng thi đấu phục vụ khách đá bóng</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary-pitch" data-bs-toggle="modal" data-bs-target="#addServiceModal">
            <i class="bi bi-plus-circle me-1"></i> Thêm Dịch Vụ Mới
        </button>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <span>Danh mục dịch vụ (Tổng: <?php echo count($services); ?> dịch vụ)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên dịch vụ</th>
                    <th>Đơn vị tính</th>
                    <th>Đơn giá</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $s): ?>
                    <tr>
                        <td><strong>#<?php echo (int)$s['id']; ?></strong></td>
                        <td class="fw-bold text-navy"><?php echo e($s['ten_dich_vu']); ?></td>
                        <td><span class="badge bg-light text-dark border"><?php echo e($s['don_vi_tinh']); ?></span></td>
                        <td><span class="fw-bold text-success"><?php echo formatCurrency($s['don_gia']); ?></span></td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editServiceModal<?php echo $s['id']; ?>">
                                <i class="bi bi-pencil"></i> Sửa
                            </button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa dịch vụ này?');">
                                <?php echo getCsrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>

                    <!-- Modal sửa -->
                    <div class="modal fade" id="editServiceModal<?php echo $s['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <?php echo getCsrfField(); ?>
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Sửa Dịch Vụ #<?php echo $s['id']; ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Tên dịch vụ <span class="text-danger">*</span></label>
                                            <input type="text" name="ten_dich_vu" class="form-control" value="<?php echo e($s['ten_dich_vu']); ?>" required>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Đơn vị tính</label>
                                                <input type="text" name="don_vi_tinh" class="form-control" value="<?php echo e($s['don_vi_tinh']); ?>" required>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Đơn giá (VNĐ) <span class="text-danger">*</span></label>
                                                <input type="number" name="don_gia" class="form-control" value="<?php echo (int)$s['don_gia']; ?>" step="1000" required>
                                            </div>
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

<!-- Modal thêm -->
<div class="modal fade" id="addServiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo getCsrfField(); ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Thêm Dịch Vụ Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tên dịch vụ <span class="text-danger">*</span></label>
                        <input type="text" name="ten_dich_vu" class="form-control" placeholder="Ví dụ: Nước chanh muối Olemic" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Đơn vị tính</label>
                            <input type="text" name="don_vi_tinh" class="form-control" value="Chai" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Đơn giá (VNĐ) <span class="text-danger">*</span></label>
                            <input type="number" name="don_gia" class="form-control" placeholder="Ví dụ: 12000" step="1000" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary-pitch btn-sm">Thêm Dịch Vụ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

