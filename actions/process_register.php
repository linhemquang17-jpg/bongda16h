<?php
/**
 * Xử lý đăng ký tài khoản khách hàng mới
 * Dự án: BongDa16h
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/dang-ky.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    setFlash('danger', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn!');
    header('Location: ' . BASE_URL . '/pages/dang-ky.php');
    exit;
}

$hoTen       = sanitize($_POST['ho_ten'] ?? '');
$soDienThoai = sanitize($_POST['so_dien_thoai'] ?? '');
$email       = sanitize($_POST['email'] ?? '');
$matKhau     = $_POST['mat_khau'] ?? '';
$matKhauXacNhan = $_POST['mat_khau_xac_nhan'] ?? '';

// Validate các trường
if (empty($hoTen) || empty($soDienThoai) || empty($matKhau)) {
    setFlash('danger', 'Vui lòng nhập đầy đủ các trường bắt buộc (Họ tên, SĐT, Mật khẩu)!');
    header('Location: ' . BASE_URL . '/pages/dang-ky.php');
    exit;
}

// Kiểm tra số điện thoại Việt Nam hợp lệ (10 chữ số)
if (!preg_match('/^(0[3|5|7|8|9])[0-9]{8}$/', $soDienThoai)) {
    setFlash('danger', 'Số điện thoại không đúng định dạng 10 chữ số tại Việt Nam!');
    header('Location: ' . BASE_URL . '/pages/dang-ky.php');
    exit;
}

// Kiểm tra email nếu có
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('danger', 'Địa chỉ email không hợp lệ!');
    header('Location: ' . BASE_URL . '/pages/dang-ky.php');
    exit;
}

// Kiểm tra độ dài mật khẩu
if (strlen($matKhau) < 6) {
    setFlash('danger', 'Mật khẩu phải chứa ít nhất 6 ký tự!');
    header('Location: ' . BASE_URL . '/pages/dang-ky.php');
    exit;
}

if ($matKhau !== $matKhauXacNhan) {
    setFlash('danger', 'Mật khẩu xác nhận không trùng khớp!');
    header('Location: ' . BASE_URL . '/pages/dang-ky.php');
    exit;
}

try {
    $pdo = getDBConnection();

    // Kiểm tra trùng lặp số điện thoại
    $stmt = $pdo->prepare("SELECT id FROM nguoi_dung WHERE so_dien_thoai = ?");
    $stmt->execute([$soDienThoai]);
    if ($stmt->fetch()) {
        setFlash('danger', 'Số điện thoại này đã được đăng ký tài khoản!');
        header('Location: ' . BASE_URL . '/pages/dang-ky.php');
        exit;
    }

    // Kiểm tra trùng lặp email nếu email có giá trị
    if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT id FROM nguoi_dung WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            setFlash('danger', 'Địa chỉ email này đã được sử dụng!');
            header('Location: ' . BASE_URL . '/pages/dang-ky.php');
            exit;
        }
    }

    // Mã hóa mật khẩu an toàn
    $hashedPassword = password_hash($matKhau, PASSWORD_DEFAULT);

    // Mặc định vai_tro_id = 3 (Khách hàng)
    $vaiTroId = 3;

    $insertStmt = $pdo->prepare("
        INSERT INTO nguoi_dung (vai_tro_id, ho_ten, email, so_dien_thoai, mat_khau)
        VALUES (?, ?, ?, ?, ?)
    ");
    $insertStmt->execute([
        $vaiTroId,
        $hoTen,
        !empty($email) ? $email : null,
        $soDienThoai,
        $hashedPassword
    ]);

    $newUserId = (int)$pdo->lastInsertId();

    // Tự động đăng nhập cho người dùng mới
    $userRow = [
        'id'            => $newUserId,
        'ho_ten'        => $hoTen,
        'email'         => $email,
        'so_dien_thoai' => $soDienThoai,
        'vai_tro_id'    => $vaiTroId,
        'ten_vai_tro'   => 'Khách hàng'
    ];
    loginUser($userRow);

    setFlash('success', 'Đăng ký tài khoản thành công! Chào mừng bạn đến với BongDa16h.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;

} catch (Exception $e) {
    setFlash('danger', 'Lỗi đăng ký tài khoản: ' . $e->getMessage());
    header('Location: ' . BASE_URL . '/pages/dang-ky.php');
    exit;
}

