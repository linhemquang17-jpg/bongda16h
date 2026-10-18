<?php
/**
 * Header giao diện người dùng
 * Dự án: BongDa16h
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$currentUser = getCurrentUser();
$currentUrl = $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?php echo BASE_URL; ?>">
    <title><?php echo isset($pageTitle) ? e($pageTitle) . ' - BongDa16h' : 'BongDa16h - Hệ Thống Đặt Sân Bóng Đá Chuyên Nghiệp'; ?></title>
    
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <?php if (isset($extraCss)) echo $extraCss; ?>
</head>
<body>

<!-- Header & Navbar -->
<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a class="navbar-brand-custom" href="<?php echo BASE_URL; ?>/index.php">
            <span class="brand-icon"><i class="bi bi-dribbble"></i></span>
            <span>BongDa<span class="brand-accent">16h</span></span>
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?php echo str_ends_with($currentUrl, 'index.php') || $currentUrl === BASE_URL . '/' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/index.php">
                        <i class="bi bi-house-door me-1"></i> Trang chủ
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?php echo str_contains($currentUrl, 'san-bong.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/san-bong.php">
                        <i class="bi bi-grid me-1"></i> Danh sách sân
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?php echo str_contains($currentUrl, 'cum-san.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/cum-san.php">
                        <i class="bi bi-geo-alt me-1"></i> Cụm sân
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?php echo str_contains($currentUrl, 'tim-doi-bong.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/tim-doi-bong.php">
                        <i class="bi bi-people-fill me-1 text-success"></i> Tìm đối đá
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?php echo str_contains($currentUrl, 'lien-he.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/lien-he.php">
                        <i class="bi bi-telephone me-1"></i> Liên hệ
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <a href="<?php echo BASE_URL; ?>/pages/dat-san.php" class="btn btn-primary-pitch me-2">
                    <i class="bi bi-calendar-check me-1"></i> Đặt sân ngay
                </a>

                <?php if ($currentUser): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2 rounded-pill px-3 py-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle fs-5 text-success"></i>
                            <span class="fw-semibold text-truncate" style="max-width: 140px;"><?php echo e($currentUser['ho_ten']); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2" style="min-width: 210px;">
                            <li class="px-3 py-2 border-bottom mb-1">
                                <small class="text-muted d-block">Đang đăng nhập với vai trò:</small>
                                <span class="badge bg-success-subtle text-success"><?php echo e($currentUser['ten_vai_tro']); ?></span>
                            </li>
                            <?php if (isStaffOrAdmin()): ?>
                                <li>
                                    <a class="dropdown-item py-2 text-primary fw-semibold" href="<?php echo BASE_URL; ?>/admin/index.php">
                                        <i class="bi bi-speedometer2 me-2"></i> Trang Quản trị
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>/pages/lich-su-dat-san.php">
                                    <i class="bi bi-clock-history me-2"></i> Lịch sử đặt sân
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>/pages/tai-khoan.php">
                                    <i class="bi bi-person me-2"></i> Thông tin tài khoản
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item py-2 text-danger" href="<?php echo BASE_URL; ?>/pages/dang-xuat.php">
                                    <i class="bi bi-box-arrow-right me-2"></i> Đăng xuất
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>/pages/dang-nhap.php" class="btn btn-outline-secondary px-3">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập
                    </a>
                    <a href="<?php echo BASE_URL; ?>/pages/dang-ky.php" class="btn btn-outline-pitch px-3">
                        Đăng ký
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Container hiển thị thông báo Flash -->
<div class="container mt-3">
    <?php renderFlash(); ?>
</div>

