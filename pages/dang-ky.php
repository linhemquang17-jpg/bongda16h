<?php
/**
 * Trang Đăng Ký Tài Khoản Khách Hàng
 * Dự án: BongDa16h
 */
$pageTitle = 'Đăng Ký Tài Khoản';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                <div class="text-center mb-4">
                    <span class="brand-icon mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.5rem;">
                        <i class="bi bi-dribbble"></i>
                    </span>
                    <h3 class="fw-bold text-navy">Tạo Tài Khoản Mới</h3>
                    <p class="text-muted small">Đăng ký thành viên để dễ dàng đặt sân và theo dõi lịch sử thi đấu</p>
                </div>

                <form action="<?php echo BASE_URL; ?>/actions/process_register.php" method="POST">
                    <?php echo getCsrfField(); ?>

                    <div class="mb-3">
                        <label for="ho_ten" class="form-label fw-semibold small">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" name="ho_ten" id="ho_ten" class="form-control" placeholder="Ví dụ: Nguyễn Văn An" required autofocus>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="so_dien_thoai" class="form-label fw-semibold small">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="tel" name="so_dien_thoai" id="so_dien_thoai" class="form-control" placeholder="Ví dụ: 0912345678" pattern="[0-9]{10}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold small">Email (tùy chọn)</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="name@example.com">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="mat_khau" class="form-label fw-semibold small">Mật khẩu <span class="text-danger">*</span></label>
                            <input type="password" name="mat_khau" id="mat_khau" class="form-control" placeholder="Tối thiểu 6 ký tự" minlength="6" required>
                        </div>
                        <div class="col-md-6">
                            <label for="mat_khau_xac_nhan" class="form-label fw-semibold small">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                            <input type="password" name="mat_khau_xac_nhan" id="mat_khau_xac_nhan" class="form-control" placeholder="Nhập lại mật khẩu" minlength="6" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-pitch w-100 py-2 fs-6 mb-3">
                        <i class="bi bi-person-plus me-1"></i> Đăng ký tài khoản
                    </button>

                    <div class="text-center">
                        <span class="text-muted small">Đã có tài khoản? </span>
                        <a href="<?php echo BASE_URL; ?>/pages/dang-nhap.php" class="fw-bold text-success text-decoration-none">Đăng nhập tại đây</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

