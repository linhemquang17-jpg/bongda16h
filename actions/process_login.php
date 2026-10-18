<?php
/**
 * Xử lý đăng nhập người dùng
 * Dự án: BongDa16h
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/dang-nhap.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    setFlash('danger', 'Phiên làm việc đã hết hạn hoặc yêu cầu không hợp lệ!');
    header('Location: ' . BASE_URL . '/pages/dang-nhap.php');
    exit;
}

$identifier = sanitize($_POST['identifier'] ?? '');
$password   = $_POST['mat_khau'] ?? '';

if (empty($identifier) || empty($password)) {
    setFlash('danger', 'Vui lòng điền đầy đủ thông tin đăng nhập!');
    header('Location: ' . BASE_URL . '/pages/dang-nhap.php');
    exit;
}

try {
    $pdo = getDBConnection();
    $user = findUserByIdentifier($identifier, $pdo);

    if (!$user) {
        setFlash('danger', 'Tài khoản không tồn tại trong hệ thống!');
        header('Location: ' . BASE_URL . '/pages/dang-nhap.php');
        exit;
    }

    if (!password_verify($password, $user['mat_khau'])) {
        setFlash('danger', 'Mật khẩu không chính xác!');
        header('Location: ' . BASE_URL . '/pages/dang-nhap.php');
        exit;
    }

    // Đăng nhập thành công
    loginUser($user);

    setFlash('success', 'Chào mừng ' . e($user['ho_ten']) . ' đã quay trở lại!');

    // Chuyển hướng theo vai trò hoặc trang đích trước đó
    if (!empty($_SESSION['redirect_after_login'])) {
        $target = $_SESSION['redirect_after_login'];
        unset($_SESSION['redirect_after_login']);
        header('Location: ' . $target);
        exit;
    }

    if (isAdmin()) {
        header('Location: ' . BASE_URL . '/admin/index.php');
        exit;
    }

    header('Location: ' . BASE_URL . '/index.php');
    exit;

} catch (Exception $e) {
    setFlash('danger', 'Lỗi hệ thống: ' . $e->getMessage());
    header('Location: ' . BASE_URL . '/pages/dang-nhap.php');
    exit;
}

