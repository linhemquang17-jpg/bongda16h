<?php
/**
 * Cập nhật thông tin cá nhân và đổi mật khẩu
 * Dự án: BongDa16h
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/tai-khoan.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    setFlash('danger', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn!');
    header('Location: ' . BASE_URL . '/pages/tai-khoan.php');
    exit;
}

$action = sanitize($_POST['action'] ?? '');
$currentUser = getCurrentUser();
$userId = (int)$currentUser['id'];
$pdo = getDBConnection();

if ($action === 'update_profile') {
    $hoTen = sanitize($_POST['ho_ten'] ?? '');
    $email = sanitize($_POST['email'] ?? '');

    if (empty($hoTen)) {
        setFlash('danger', 'Họ tên không được để trống!');
        header('Location: ' . BASE_URL . '/pages/tai-khoan.php');
        exit;
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('danger', 'Email không hợp lệ!');
        header('Location: ' . BASE_URL . '/pages/tai-khoan.php');
        exit;
    }

    try {
        if (!empty($email)) {
            $stmt = $pdo->prepare("SELECT id FROM nguoi_dung WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                setFlash('danger', 'Email này đã được sử dụng bởi một tài khoản khác!');
                header('Location: ' . BASE_URL . '/pages/tai-khoan.php');
                exit;
            }
        }

        $update = $pdo->prepare("UPDATE nguoi_dung SET ho_ten = ?, email = ? WHERE id = ?");
        $update->execute([$hoTen, !empty($email) ? $email : null, $userId]);

        $_SESSION['user']['ho_ten'] = $hoTen;
        $_SESSION['user']['email'] = $email;

        setFlash('success', 'Cập nhật thông tin cá nhân thành công!');
        header('Location: ' . BASE_URL . '/pages/tai-khoan.php');
        exit;
    } catch (Exception $e) {
        setFlash('danger', 'Lỗi cập nhật: ' . $e->getMessage());
        header('Location: ' . BASE_URL . '/pages/tai-khoan.php');
        exit;
    }
} elseif ($action === 'change_password') {
    $currentPass = $_POST['mat_khau_hien_tai'] ?? '';
    $newPass     = $_POST['mat_khau_moi'] ?? '';
    $confirmPass = $_POST['xac_nhan_mat_khau'] ?? '';

    if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
        setFlash('danger', 'Vui lòng điền đầy đủ các thông tin đổi mật khẩu!');
        header('Location: ' . BASE_URL . '/pages/tai-khoan.php');
        exit;
    }

    if (strlen($newPass) < 6) {
        setFlash('danger', 'Mật khẩu mới phải có tối thiểu 6 ký tự!');
        header('Location: ' . BASE_URL . '/pages/tai-khoan.php');
        exit;
    }

    if ($newPass !== $confirmPass) {
        setFlash('danger', 'Mật khẩu mới và xác nhận mật khẩu không trùng khớp!');
        header('Location: ' . BASE_URL . '/pages/tai-khoan.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT mat_khau FROM nguoi_dung WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($currentPass, $user['mat_khau'])) {
            setFlash('danger', 'Mật khẩu hiện tại không chính xác!');
            header('Location: ' . BASE_URL . '/pages/tai-khoan.php');
            exit;
        }

        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE nguoi_dung SET mat_khau = ? WHERE id = ?");
        $update->execute([$hashed, $userId]);

        setFlash('success', 'Đổi mật khẩu thành công!');
        header('Location: ' . BASE_URL . '/pages/tai-khoan.php');
        exit;
    } catch (Exception $e) {
        setFlash('danger', 'Lỗi đổi mật khẩu: ' . $e->getMessage());
        header('Location: ' . BASE_URL . '/pages/tai-khoan.php');
        exit;
    }
} else {
    header('Location: ' . BASE_URL . '/pages/tai-khoan.php');
    exit;
}

