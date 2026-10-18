<?php
/**
 * Quản lý phiên làm việc, xác thực và phân quyền người dùng
 * Dự án: BongDa16h
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Kiểm tra xem người dùng đã đăng nhập chưa
 */
function isLoggedIn() {
    return !empty($_SESSION['user']['id']);
}

/**
 * Lấy thông tin người dùng hiện tại
 */
function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

/**
 * Kiểm tra quyền Quản trị viên (vai_tro_id = 1)
 */
function isAdmin() {
    return isLoggedIn() && (int)$_SESSION['user']['vai_tro_id'] === 1;
}

/**
 * Kiểm tra quyền Nhân viên hoặc Quản trị viên (vai_tro_id = 1 hoặc 2)
 */
function isStaffOrAdmin() {
    if (!isLoggedIn()) return false;
    $roleId = (int)$_SESSION['user']['vai_tro_id'];
    return ($roleId === 1 || $roleId === 2);
}

/**
 * Bắt buộc phải đăng nhập
 */
function requireLogin($redirectUrl = null) {
    if (!isLoggedIn()) {
        $target = $redirectUrl ?? $_SERVER['REQUEST_URI'];
        $_SESSION['redirect_after_login'] = $target;
        setFlash('warning', 'Vui lòng đăng nhập để tiếp tục thao tác!');
        header('Location: ' . BASE_URL . '/pages/dang-nhap.php');
        exit;
    }
}

/**
 * Bắt buộc quyền Quản trị viên (Admin)
 */
function requireAdmin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        setFlash('warning', 'Vui lòng đăng nhập tài khoản Quản trị viên!');
        header('Location: ' . BASE_URL . '/pages/dang-nhap.php');
        exit;
    }

    if (!isAdmin()) {
        setFlash('danger', 'Bạn không có quyền truy cập trang quản trị!');
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

/**
 * Bắt buộc quyền Nhân viên hoặc Quản trị viên
 */
function requireStaffOrAdmin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        setFlash('warning', 'Vui lòng đăng nhập để vào khu vực quản lý!');
        header('Location: ' . BASE_URL . '/pages/dang-nhap.php');
        exit;
    }

    if (!isStaffOrAdmin()) {
        setFlash('danger', 'Bạn không có quyền truy cập khu vực này!');
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

/**
 * Lưu thông tin đăng nhập vào Session
 */
function loginUser($userRow) {
    // Tái tạo session ID để chống Session Fixation
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'            => (int)$userRow['id'],
        'ho_ten'        => $userRow['ho_ten'],
        'email'         => $userRow['email'],
        'so_dien_thoai' => $userRow['so_dien_thoai'],
        'vai_tro_id'    => (int)$userRow['vai_tro_id'],
        'ten_vai_tro'   => $userRow['ten_vai_tro'] ?? 'Khách hàng'
    ];
}

/**
 * Đăng xuất người dùng
 */
function logoutUser() {
    $_SESSION['user'] = null;
    unset($_SESSION['user']);
    session_destroy();
}

/**
 * Tìm người dùng theo số điện thoại hoặc email
 */
function findUserByIdentifier($identifier, $pdo = null) {
    if (!$pdo) {
        $pdo = getDBConnection();
    }

    $stmt = $pdo->prepare("
        SELECT u.*, r.ten_vai_tro 
        FROM nguoi_dung u
        LEFT JOIN vai_tro r ON u.vai_tro_id = r.id
        WHERE u.email = :id1 OR u.so_dien_thoai = :id2
        LIMIT 1
    ");
    $stmt->execute([
        ':id1' => $identifier,
        ':id2' => $identifier
    ]);

    return $stmt->fetch();
}

