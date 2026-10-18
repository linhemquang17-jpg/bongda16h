<?php
/**
 * Header giao diện Quản trị Admin
 * Dự án: BongDa16h
 */
require_once __DIR__ . '/auth_check.php';

$currentUser = getCurrentUser();
$adminUri = $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? e($pageTitle) . ' - Quản Trị BongDa16h' : 'Quản Trị Hệ Thống - BongDa16h'; ?></title>
    
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css">
</head>
<body>

<div class="admin-wrapper">
    <!-- SIDEBAR -->
    <aside class="admin-sidebar" id="adminSidebar">
        <a href="<?php echo BASE_URL; ?>/admin/index.php" class="admin-brand">
            <span class="brand-icon" style="width:36px;height:36px;font-size:1.1rem;background:linear-gradient(135deg,#10b981,#047857);color:white;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-shield-lock-fill"></i>
            </span>
            <span>BongDa<span style="color:#10b981;">16h</span> Admin</span>
        </a>

        <ul class="admin-nav">
            <li class="admin-nav-item">
                <a href="<?php echo BASE_URL; ?>/admin/index.php" class="admin-nav-link <?php echo str_ends_with($adminUri, 'admin/index.php') || str_ends_with($adminUri, 'admin/') ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Tổng quan (Dashboard)</span>
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="<?php echo BASE_URL; ?>/admin/don-dat-san.php" class="admin-nav-link <?php echo str_contains($adminUri, 'don-dat-san.php') || str_contains($adminUri, 'chi-tiet-don.php') ? 'active' : ''; ?>">
                    <i class="bi bi-calendar-check"></i>
                    <span>Quản lý Đơn đặt sân</span>
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="<?php echo BASE_URL; ?>/admin/san-bong.php" class="admin-nav-link <?php echo str_contains($adminUri, 'san-bong.php') ? 'active' : ''; ?>">
                    <i class="bi bi-grid-3x3-gap"></i>
                    <span>Quản lý Sân bóng</span>
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="<?php echo BASE_URL; ?>/admin/cum-san.php" class="admin-nav-link <?php echo str_contains($adminUri, 'cum-san.php') ? 'active' : ''; ?>">
                    <i class="bi bi-geo-alt"></i>
                    <span>Quản lý Cụm sân</span>
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="<?php echo BASE_URL; ?>/admin/loai-san.php" class="admin-nav-link <?php echo str_contains($adminUri, 'loai-san.php') ? 'active' : ''; ?>">
                    <i class="bi bi-diagram-3"></i>
                    <span>Quản lý Loại sân</span>
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="<?php echo BASE_URL; ?>/admin/dich-vu.php" class="admin-nav-link <?php echo str_contains($adminUri, 'dich-vu.php') ? 'active' : ''; ?>">
                    <i class="bi bi-cup-straw"></i>
                    <span>Dịch vụ bổ sung</span>
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="<?php echo BASE_URL; ?>/admin/hinh-anh.php" class="admin-nav-link <?php echo str_contains($adminUri, 'hinh-anh.php') ? 'active' : ''; ?>">
                    <i class="bi bi-images"></i>
                    <span>Hình ảnh sân</span>
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="<?php echo BASE_URL; ?>/admin/thanh-toan.php" class="admin-nav-link <?php echo str_contains($adminUri, 'thanh-toan.php') ? 'active' : ''; ?>">
                    <i class="bi bi-credit-card"></i>
                    <span>Thanh toán & Doanh thu</span>
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="<?php echo BASE_URL; ?>/admin/tim-doi.php" class="admin-nav-link <?php echo str_contains($adminUri, 'tim-doi.php') ? 'active' : ''; ?>">
                    <i class="bi bi-people-fill"></i>
                    <span>Tìm đối đá bóng</span>
                </a>
            </li>
            <?php if (isAdmin()): ?>
                <li class="admin-nav-item">
                    <a href="<?php echo BASE_URL; ?>/admin/nguoi-dung.php" class="admin-nav-link <?php echo str_contains($adminUri, 'nguoi-dung.php') ? 'active' : ''; ?>">
                        <i class="bi bi-people"></i>
                        <span>Người dùng & Phân quyền</span>
                    </a>
                </li>
            <?php endif; ?>
            <li class="admin-nav-item mt-4 pt-3 border-top border-secondary border-opacity-25">
                <a href="<?php echo BASE_URL; ?>/index.php" target="_blank" class="admin-nav-link text-white-50">
                    <i class="bi bi-box-arrow-up-right"></i>
                    <span>Xem trang chủ Web</span>
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="<?php echo BASE_URL; ?>/pages/dang-xuat.php" class="admin-nav-link text-danger">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Đăng xuất</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <div class="admin-main">
        <!-- TOPBAR -->
        <header class="admin-topbar">
            <button class="btn btn-outline-secondary d-lg-none" type="button" id="sidebarToggle">
                <i class="bi bi-list fs-5"></i>
            </button>

            <div class="d-none d-md-block">
                <span class="badge bg-success-subtle text-success border border-success px-3 py-1 rounded-pill">
                    <i class="bi bi-database-check me-1"></i> Database: bongda16h (Active)
                </span>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="text-end d-none d-sm-block">
                    <span class="fw-bold d-block text-navy"><?php echo e($currentUser['ho_ten']); ?></span>
                    <small class="badge bg-primary-subtle text-primary"><?php echo e($currentUser['ten_vai_tro']); ?></small>
                </div>
                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center fw-bold" style="width:40px;height:40px;">
                    <?php echo mb_substr($currentUser['ho_ten'], 0, 1, 'UTF-8'); ?>
                </div>
            </div>
        </header>

        <!-- CONTAINER NỘI DUNG VÀ FLASH ALERTS -->
        <div class="admin-content">
            <?php renderFlash(); ?>

