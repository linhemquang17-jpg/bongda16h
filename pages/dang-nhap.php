<?php
/**
 * Trang Đăng Nhập
 * Dự án: BongDa16h
 */
$pageTitle = 'Đăng Nhập';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Nếu đã đăng nhập, chuyển hướng phù hợp
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: ' . BASE_URL . '/admin/index.php');
    } else {
        header('Location: ' . BASE_URL . '/index.php');
    }
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                <div class="text-center mb-4">
                    <span class="brand-icon mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.5rem;">
                        <i class="bi bi-dribbble"></i>
                    </span>
                    <h3 class="fw-bold text-navy">Đăng Nhập Tài Khoản</h3>
                    <p class="text-muted small">Chào mừng bạn quay lại hệ thống đặt sân BongDa16h</p>
                </div>

                <form action="<?php echo BASE_URL; ?>/actions/process_login.php" method="POST">
                    <?php echo getCsrfField(); ?>

                    <div class="mb-3">
                        <label for="identifier" class="form-label fw-semibold small">Số điện thoại hoặc Email <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                            <input type="text" name="identifier" id="identifier" class="form-control border-start-0" placeholder="0981122334 hoặc email@example.com" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="mat_khau" class="form-label fw-semibold small mb-0">Mật khẩu <span class="text-danger">*</span></label>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                            <input type="password" name="mat_khau" id="mat_khau" class="form-control border-start-0" placeholder="Nhập mật khẩu..." required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-pitch w-100 py-2 fs-6 mb-3">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập ngay
                    </button>

                    <div class="text-center">
                        <span class="text-muted small">Chưa có tài khoản? </span>
                        <a href="<?php echo BASE_URL; ?>/pages/dang-ky.php" class="fw-bold text-success text-decoration-none">Đăng ký thành viên</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

