<?php
/**
 * Quản Lý Loại Sân - Admin
 * Dự án: BongDa16h
 */
$pageTitle = 'Quản Lý Loại Sân';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/auth_check.php';

$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Yêu cầu không hợp lệ!');
        header('Location: ' . BASE_URL . '/admin/loai-san.php');
        exit;
    }

    $action = sanitize($_POST['action'] ?? '');

    if ($action === 'add') {
        $tenLoai = sanitize($_POST['ten_loai'] ?? '');
        $giaCoBan= (float)($_POST['gia_co_ban_thang_gio'] ?? 0);

        if (empty($tenLoai) || $giaCoBan <= 0) {
            setFlash('danger', 'Vui lòng nhập tên loại sân và giá cơ bản hợp lệ!');
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO loai_san (ten_loai, gia_co_ban_thang_gio) VALUES (?, ?)");
                $stmt->execute([$tenLoai, $giaCoBan]);
                setFlash('success', 'Thêm loại sân mới thành công!');
            } catch (Exception $e) {
                setFlash('danger', 'Lỗi thêm loại sân: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'edit') {
        $id      = (int)($_POST['id'] ?? 0);
        $tenLoai = sanitize($_POST['ten_loai'] ?? '');
        $giaCoBan= (float)($_POST['gia_co_ban_thang_gio'] ?? 0);

        if (!$id || empty($tenLoai) || $giaCoBan <= 0) {
            setFlash('danger', 'Thông tin cập nhật không hợp lệ!');
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE loai_san SET ten_loai = ?, gia_co_ban_thang_gio = ? WHERE id = ?");
                $stmt->execute([$tenLoai, $giaCoBan, $id]);
                setFlash('success', "Cập nhật loại sân #{$id} thành công!");
            } catch (Exception $e) {
                setFlash('danger', 'Lỗi cập nhật: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $chk = $pdo->prepare("SELECT COUNT(*) FROM san_bong WHERE loai_san_id = ?");
            $chk->execute([$id]);
            if ($chk->fetchColumn() > 0) {
                setFlash('warning', 'Không thể xóa loại sân này vì đang có các sân bóng sử dụng loại sân này!');
            } else {
                try {
                    $pdo->prepare("DELETE FROM loai_san WHERE id = ?")->execute([$id]);
                    setFlash('success', 'Xóa loại sân thành công!');
                } catch (Exception $e) {
                    setFlash('danger', 'Lỗi xóa loại sân: ' . $e->getMessage());
                }
            }
        }
    }

    header('Location: ' . BASE_URL . '/admin/loai-san.php');
    exit;
}

$types = $pdo->query("
    SELECT l.*, COUNT(s.id) as pitch_count 
    FROM loai_san l 
    LEFT JOIN san_bong s ON l.id = s.loai_san_id 
    GROUP BY l.id 
    ORDER BY l.id ASC
")->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold text-navy mb-1">Quản Lý Loại Sân</h3>
        <p class="text-muted mb-0">Các phân loại quy mô sân bóng đá (Sân 5, Sân 7, Sân 11 người...)</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary-pitch" data-bs-toggle="modal" data-bs-target="#addTypeModal">
            <i class="bi bi-plus-circle me-1"></i> Thêm Loại Sân
        </button>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <span>Danh sách loại sân</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên loại sân</th>
                    <th>Giá cơ bản tham chiếu / giờ</th>
                    <th>Số sân bóng đang dùng</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($types as $t): ?>
                    <tr>
                        <td><strong>#<?php echo (int)$t['id']; ?></strong></td>
                        <td class="fw-bold text-navy"><?php echo e($t['ten_loai']); ?></td>
                        <td><span class="fw-bold text-success"><?php echo formatCurrency($t['gia_co_ban_thang_gio']); ?></span></td>
                        <td><span class="badge bg-secondary-subtle text-secondary"><?php echo (int)$t['pitch_count']; ?> sân</span></td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editTypeModal<?php echo $t['id']; ?>">
                                <i class="bi bi-pencil"></i> Sửa
                            </button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa loại sân này?');">
                                <?php echo getCsrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>

                    <!-- Modal sửa -->
                    <div class="modal fade" id="editTypeModal<?php echo $t['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <?php echo getCsrfField(); ?>
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Sửa Loại Sân #<?php echo $t['id']; ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Tên loại sân <span class="text-danger">*</span></label>
                                            <input type="text" name="ten_loai" class="form-control" value="<?php echo e($t['ten_loai']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Giá cơ bản theo giờ (VNĐ) <span class="text-danger">*</span></label>
                                            <input type="number" name="gia_co_ban_thang_gio" class="form-control" value="<?php echo (int)$t['gia_co_ban_thang_gio']; ?>" step="10000" required>
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
<div class="modal fade" id="addTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo getCsrfField(); ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Thêm Loại Sân Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tên loại sân <span class="text-danger">*</span></label>
                        <input type="text" name="ten_loai" class="form-control" placeholder="Ví dụ: Sân 11 người" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Giá cơ bản theo giờ (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" name="gia_co_ban_thang_gio" class="form-control" placeholder="Ví dụ: 500000" step="10000" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary-pitch btn-sm">Thêm Loại Sân</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

