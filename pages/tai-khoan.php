<?php
/**
 * Quản lý Hồ Sơ & Đổi Mật Khẩu
 * Dự án: BongDa16h
 */
$pageTitle = 'Tài Khoản Của Tôi';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$currentUser = getCurrentUser();
$userId = (int)$currentUser['id'];
$pdo = getDBConnection();

$stmt = $pdo->prepare("SELECT u.*, r.ten_vai_tro FROM nguoi_dung u LEFT JOIN vai_tro r ON u.vai_tro_id = r.id WHERE u.id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php" class="text-decoration-none">Trang chủ</a></li>
            <li class="breadcrumb-item active" aria-current="page">Hồ sơ cá nhân</li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- CỘT THÔNG TIN TỔNG QUAN -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center mb-4">
                <div class="mx-auto mb-3" style="width: 80px; height: 80px; background: #e0f2fe; color: #0284c7; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem;">
                    <i class="bi bi-person-fill"></i>
                </div>
                <h4 class="fw-bold text-navy mb-1"><?php echo e($user['ho_ten']); ?></h4>
                <p class="text-muted small mb-2"><i class="bi bi-telephone me-1"></i> <?php echo e($user['so_dien_thoai']); ?></p>
                <div>
                    <span class="badge bg-success-subtle text-success border border-success px-3 py-1 rounded-pill">
                        <?php echo e($user['ten_vai_tro'] ?? 'Khách hàng'); ?>
                    </span>
                </div>
                <hr class="my-3">
                <div class="text-start small text-muted">
                    <div class="mb-2"><i class="bi bi-envelope me-2"></i> <?php echo e($user['email'] ?? 'Chưa cập nhật email'); ?></div>
                    <div><i class="bi bi-calendar-event me-2"></i> Tham gia: <?php echo formatDate($user['created_at']); ?></div>
                </div>
            </div>

            <div class="list-group rounded-4 shadow-sm border-0">
                <a href="<?php echo BASE_URL; ?>/pages/lich-su-dat-san.php" class="list-group-item list-group-item-action py-3">
                    <i class="bi bi-clock-history me-2 text-success"></i> Xem lịch sử đặt sân của tôi
                </a>
                <?php if (isStaffOrAdmin()): ?>
                    <a href="<?php echo BASE_URL; ?>/admin/index.php" class="list-group-item list-group-item-action py-3 text-primary fw-semibold">
                        <i class="bi bi-speedometer2 me-2"></i> Quản trị hệ thống
                    </a>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>/pages/dang-xuat.php" class="list-group-item list-group-item-action py-3 text-danger">
                    <i class="bi bi-box-arrow-right me-2"></i> Đăng xuất
                </a>
            </div>
        </div>

        <!-- CỘT FORM CẬP NHẬT -->
        <div class="col-lg-8">
            <!-- Form cập nhật thông tin cá nhân -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h5 class="fw-bold text-navy mb-3"><i class="bi bi-person-gear text-success me-2"></i> Cập Nhật Thông Tin Cá Nhân</h5>
                <form action="<?php echo BASE_URL; ?>/actions/process_profile.php" method="POST">
                    <?php echo getCsrfField(); ?>
                    <input type="hidden" name="action" value="update_profile">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Số điện thoại (định danh duy nhất)</label>
                            <input type="text" class="form-control" value="<?php echo e($user['so_dien_thoai']); ?>" disabled>
                            <small class="text-muted">Số điện thoại không thể thay đổi</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Vai trò tài khoản</label>
                            <input type="text" class="form-control" value="<?php echo e($user['ten_vai_tro']); ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label for="ho_ten" class="form-label small fw-semibold">Họ và tên <span class="text-danger">*</span></label>
                            <input type="text" name="ho_ten" id="ho_ten" class="form-control" value="<?php echo e($user['ho_ten']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label small fw-semibold">Địa chỉ Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="<?php echo e($user['email'] ?? ''); ?>" placeholder="name@example.com">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-pitch px-4">
                        <i class="bi bi-check2 me-1"></i> Lưu thay đổi
                    </button>
                </form>
            </div>

            <!-- Form đổi mật khẩu -->
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold text-navy mb-3"><i class="bi bi-key text-warning me-2"></i> Đổi Mật Khẩu</h5>
                <form action="<?php echo BASE_URL; ?>/actions/process_profile.php" method="POST">
                    <?php echo getCsrfField(); ?>
                    <input type="hidden" name="action" value="change_password">

                    <div class="mb-3">
                        <label for="mat_khau_hien_tai" class="form-label small fw-semibold">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                        <input type="password" name="mat_khau_hien_tai" id="mat_khau_hien_tai" class="form-control" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="mat_khau_moi" class="form-label small fw-semibold">Mật khẩu mới <span class="text-danger">*</span></label>
                            <input type="password" name="mat_khau_moi" id="mat_khau_moi" class="form-control" minlength="6" required>
                        </div>
                        <div class="col-md-6">
                            <label for="xac_nhan_mat_khau" class="form-label small fw-semibold">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                            <input type="password" name="xac_nhan_mat_khau" id="xac_nhan_mat_khau" class="form-control" minlength="6" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-outline-pitch px-4">
                        <i class="bi bi-lock me-1"></i> Cập nhật mật khẩu
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

