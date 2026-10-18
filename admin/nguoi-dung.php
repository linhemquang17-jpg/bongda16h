<?php
/**
 * Quản Lý Người Dùng & Phân Quyền - Admin
 * Dự án: BongDa16h
 */
$pageTitle = 'Quản Lý Người Dùng & Vai Trò';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/auth_check.php';

// Chỉ quản trị viên cấp cao nhất (vai_tro_id = 1) mới được quản lý tài khoản
requireAdmin();

$pdo = getDBConnection();
$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Yêu cầu không hợp lệ!');
        header('Location: ' . BASE_URL . '/admin/nguoi-dung.php');
        exit;
    }

    $action = sanitize($_POST['action'] ?? '');

    if ($action === 'add') {
        $hoTen       = sanitize($_POST['ho_ten'] ?? '');
        $soDienThoai = sanitize($_POST['so_dien_thoai'] ?? '');
        $email       = sanitize($_POST['email'] ?? '');
        $matKhau     = $_POST['mat_khau'] ?? '';
        $vaiTroId    = (int)($_POST['vai_tro_id'] ?? 3);

        if (empty($hoTen) || empty($soDienThoai) || empty($matKhau)) {
            setFlash('danger', 'Vui lòng nhập đầy đủ các trường bắt buộc!');
        } elseif (!preg_match('/^(0[3|5|7|8|9])[0-9]{8}$/', $soDienThoai)) {
            setFlash('danger', 'Số điện thoại không đúng định dạng 10 chữ số!');
        } else {
            try {
                // Kiểm tra trùng SĐT
                $chk = $pdo->prepare("SELECT id FROM nguoi_dung WHERE so_dien_thoai = ?");
                $chk->execute([$soDienThoai]);
                if ($chk->fetch()) {
                    setFlash('danger', 'Số điện thoại này đã tồn tại trong hệ thống!');
                } else {
                    $hashed = password_hash($matKhau, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO nguoi_dung (vai_tro_id, ho_ten, email, so_dien_thoai, mat_khau)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$vaiTroId, $hoTen, !empty($email) ? $email : null, $soDienThoai, $hashed]);
                    setFlash('success', 'Thêm tài khoản thành công!');
                }
            } catch (Exception $e) {
                setFlash('danger', 'Lỗi thêm tài khoản: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'edit_role') {
        $id       = (int)($_POST['id'] ?? 0);
        $vaiTroId = (int)($_POST['vai_tro_id'] ?? 3);

        // Không cho phép tự hạ quyền tài khoản của chính mình
        if ($id === (int)$currentUser['id'] && $vaiTroId !== 1) {
            setFlash('danger', 'Bạn không thể tự hạ quyền Quản trị viên của chính mình!');
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE nguoi_dung SET vai_tro_id = ? WHERE id = ?");
                $stmt->execute([$vaiTroId, $id]);
                setFlash('success', "Cập nhật vai trò cho người dùng #{$id} thành công!");
            } catch (Exception $e) {
                setFlash('danger', 'Lỗi cập nhật vai trò: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'reset_password') {
        $id      = (int)($_POST['id'] ?? 0);
        $newPass = $_POST['mat_khau_moi'] ?? '';

        if (strlen($newPass) < 6) {
            setFlash('danger', 'Mật khẩu mới phải có ít nhất 6 ký tự!');
        } else {
            try {
                $hashed = password_hash($newPass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE nguoi_dung SET mat_khau = ? WHERE id = ?");
                $stmt->execute([$hashed, $id]);
                setFlash('success', "Đổi mật khẩu tài khoản #{$id} thành công!");
            } catch (Exception $e) {
                setFlash('danger', 'Lỗi đổi mật khẩu: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id === (int)$currentUser['id']) {
            setFlash('danger', 'Bạn không thể tự xóa tài khoản của chính mình!');
        } else {
            // Kiểm tra xem user có đơn đặt sân không
            $chk = $pdo->prepare("SELECT COUNT(*) FROM dat_san WHERE nguoi_dung_id = ?");
            $chk->execute([$id]);
            if ($chk->fetchColumn() > 0) {
                setFlash('warning', 'Tài khoản này đã có lịch sử đặt sân, không thể xóa để bảo toàn dữ liệu lịch sử!');
            } else {
                try {
                    $pdo->prepare("DELETE FROM nguoi_dung WHERE id = ?")->execute([$id]);
                    setFlash('success', 'Xóa tài khoản thành công!');
                } catch (Exception $e) {
                    setFlash('danger', 'Lỗi xóa tài khoản: ' . $e->getMessage());
                }
            }
        }
    }

    header('Location: ' . BASE_URL . '/admin/nguoi-dung.php');
    exit;
}

// Lấy danh sách người dùng
$users = $pdo->query("
    SELECT u.*, r.ten_vai_tro, COUNT(d.id) as total_bookings
    FROM nguoi_dung u
    LEFT JOIN vai_tro r ON u.vai_tro_id = r.id
    LEFT JOIN dat_san d ON u.id = d.nguoi_dung_id
    GROUP BY u.id
    ORDER BY u.id ASC
")->fetchAll();

$roles = $pdo->query("SELECT * FROM vai_tro ORDER BY id ASC")->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold text-navy mb-1">Quản Lý Người Dùng & Phân Quyền</h3>
        <p class="text-muted mb-0">Quản lý tài khoản quản trị viên, nhân viên và khách hàng</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary-pitch" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus me-1"></i> Thêm Tài Khoản Mới
        </button>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <span>Danh sách người dùng (Tổng: <?php echo count($users); ?> tài khoản)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Họ và tên</th>
                    <th>Số điện thoại</th>
                    <th>Email</th>
                    <th>Vai trò</th>
                    <th>Số đơn đã đặt</th>
                    <th>Ngày tham gia</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><strong>#<?php echo (int)$u['id']; ?></strong></td>
                        <td class="fw-bold text-navy">
                            <?php echo e($u['ho_ten']); ?>
                            <?php if ((int)$u['id'] === (int)$currentUser['id']): ?>
                                <span class="badge bg-secondary-subtle text-secondary ms-1">Bạn</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e($u['so_dien_thoai']); ?></td>
                        <td><?php echo e($u['email'] ?? '—'); ?></td>
                        <td>
                            <?php 
                            $badgeClass = 'bg-secondary';
                            if ($u['vai_tro_id'] == 1) $badgeClass = 'bg-danger';
                            elseif ($u['vai_tro_id'] == 2) $badgeClass = 'bg-primary';
                            else $badgeClass = 'bg-success';
                            ?>
                            <span class="badge <?php echo $badgeClass; ?>"><?php echo e($u['ten_vai_tro'] ?? 'Khách hàng'); ?></span>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?php echo (int)$u['total_bookings']; ?> đơn</span></td>
                        <td><small class="text-muted"><?php echo formatDate($u['created_at']); ?></small></td>
                        <td class="text-end">
                            <!-- Nút sửa vai trò -->
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editRoleModal<?php echo $u['id']; ?>" title="Đổi vai trò">
                                <i class="bi bi-person-gear"></i>
                            </button>
                            <!-- Nút reset pass -->
                            <button type="button" class="btn btn-sm btn-outline-warning me-1" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#resetPassModal<?php echo $u['id']; ?>" title="Đổi mật khẩu">
                                <i class="bi bi-key"></i>
                            </button>
                            <!-- Nút xóa -->
                            <?php if ((int)$u['id'] !== (int)$currentUser['id']): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tài khoản này?');">
                                    <?php echo getCsrfField(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <!-- MODAL SỬA VAI TRÒ -->
                    <div class="modal fade" id="editRoleModal<?php echo $u['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <?php echo getCsrfField(); ?>
                                    <input type="hidden" name="action" value="edit_role">
                                    <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Phân Quyền Cho: <?php echo e($u['ho_ten']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Chọn vai trò mới</label>
                                            <select name="vai_tro_id" class="form-select">
                                                <?php foreach ($roles as $r): ?>
                                                    <option value="<?php echo $r['id']; ?>" <?php echo $u['vai_tro_id'] == $r['id'] ? 'selected' : ''; ?>>
                                                        <?php echo e($r['ten_vai_tro']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
                                        <button type="submit" class="btn btn-primary-pitch btn-sm">Cập Nhật Vai Trò</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- MODAL ĐẶT LẠI MẬT KHẨU -->
                    <div class="modal fade" id="resetPassModal<?php echo $u['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <?php echo getCsrfField(); ?>
                                    <input type="hidden" name="action" value="reset_password">
                                    <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Đặt Lại Mật Khẩu: <?php echo e($u['ho_ten']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Mật khẩu mới (Tối thiểu 6 ký tự) <span class="text-danger">*</span></label>
                                            <input type="password" name="mat_khau_moi" class="form-control" placeholder="Nhập mật khẩu mới..." minlength="6" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
                                        <button type="submit" class="btn btn-warning btn-sm">Lưu Mật Khẩu</button>
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

<!-- MODAL THÊM TÀI KHOẢN MỚI -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo getCsrfField(); ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Thêm Tài Khoản Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" name="ho_ten" class="form-control" placeholder="Ví dụ: Lê Văn Quản Lý" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="tel" name="so_dien_thoai" class="form-control" placeholder="0912345678" pattern="[0-9]{10}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="staff@example.com">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Mật khẩu <span class="text-danger">*</span></label>
                            <input type="password" name="mat_khau" class="form-control" placeholder="Tối thiểu 6 ký tự" minlength="6" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Vai trò <span class="text-danger">*</span></label>
                            <select name="vai_tro_id" class="form-select" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?php echo $r['id']; ?>" <?php echo $r['id'] == 2 ? 'selected' : ''; ?>>
                                        <?php echo e($r['ten_vai_tro']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary-pitch btn-sm">Tạo Tài Khoản</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

