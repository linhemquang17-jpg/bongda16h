<?php
/**
 * Xử lý đặt sân bóng đá, lưu đơn vào CSDL bằng Transaction
 * Dự án: BongDa16h
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/dat-san.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    setFlash('danger', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn!');
    header('Location: ' . BASE_URL . '/pages/dat-san.php');
    exit;
}

// 1. Nhận dữ liệu đầu vào
$sanBongId    = (int)($_POST['san_bong_id'] ?? 0);
$ngayDat      = sanitize($_POST['ngay_dat'] ?? '');
$gioBatDau    = sanitize($_POST['gio_bat_dau'] ?? '');
$gioKetThuc   = sanitize($_POST['gio_ket_thuc'] ?? '');
$phuongThucTT = sanitize($_POST['phuong_thuc_thanh_toan'] ?? 'Tiền mặt');
$servicesPost = $_POST['services'] ?? []; // mảng [service_id => quantity]

// Thông tin khách hàng nếu chưa đăng nhập
$hoTen       = sanitize($_POST['ho_ten'] ?? '');
$soDienThoai = sanitize($_POST['so_dien_thoai'] ?? '');
$email       = sanitize($_POST['email'] ?? '');

// 2. Validate dữ liệu cơ bản
if (!$sanBongId || !$ngayDat || !$gioBatDau || !$gioKetThuc) {
    setFlash('danger', 'Vui lòng chọn đầy đủ sân bóng, ngày đá và khung giờ!');
    header('Location: ' . BASE_URL . '/pages/dat-san.php' . ($sanBongId ? '?san_bong_id=' . $sanBongId : ''));
    exit;
}

// Kiểm tra ngày không được ở quá khứ
$today = date('Y-m-d');
if ($ngayDat < $today) {
    setFlash('danger', 'Ngày đặt sân không thể ở trong quá khứ!');
    header('Location: ' . BASE_URL . '/pages/dat-san.php?san_bong_id=' . $sanBongId);
    exit;
}

// Nếu là hôm nay, giờ bắt đầu phải sau giờ hiện tại
if ($ngayDat === $today && $gioBatDau <= date('H:i')) {
    setFlash('danger', 'Giờ bắt đầu phải sau thời gian hiện tại (' . date('H:i') . ')!');
    header('Location: ' . BASE_URL . '/pages/dat-san.php?san_bong_id=' . $sanBongId);
    exit;
}

$pdo = getDBConnection();

try {
    // 3. Kiểm tra thông tin sân
    $stmt = $pdo->prepare("SELECT * FROM san_bong WHERE id = ?");
    $stmt->execute([$sanBongId]);
    $pitch = $stmt->fetch();

    if (!$pitch) {
        setFlash('danger', 'Sân bóng không tồn tại!');
        header('Location: ' . BASE_URL . '/pages/san-bong.php');
        exit;
    }

    if ($pitch['trang_thai'] !== 'Hoạt động') {
        setFlash('danger', 'Sân bóng đang trong thời gian bảo trì, quý khách vui lòng chọn sân khác!');
        header('Location: ' . BASE_URL . '/pages/dat-san.php');
        exit;
    }

    // 4. Tính toán giá sân tại Backend
    $calc = calculatePitchPrice($pitch['gia_theo_gio'], $gioBatDau, $gioKetThuc);
    if (!$calc['valid']) {
        setFlash('danger', $calc['message']);
        header('Location: ' . BASE_URL . '/pages/dat-san.php?san_bong_id=' . $sanBongId);
        exit;
    }
    $pitchTotal = $calc['total'];

    // 5. Kiểm tra xung đột lịch (trùng giờ) tại Backend
    $isAvailable = checkPitchAvailability($sanBongId, $ngayDat, $gioBatDau, $gioKetThuc, $pdo);
    if (!$isAvailable) {
        setFlash('danger', "Khung giờ {$gioBatDau} - {$gioKetThuc} ngày " . formatDate($ngayDat) . " đã có người đặt! Vui lòng chọn khung giờ khác.");
        header('Location: ' . BASE_URL . '/pages/dat-san.php?san_bong_id=' . $sanBongId);
        exit;
    }

    // 6. Xử lý thông tin người dùng
    $userId = null;
    if (isLoggedIn()) {
        $userId = (int)$_SESSION['user']['id'];
    } else {
        if (empty($hoTen) || empty($soDienThoai)) {
            setFlash('danger', 'Vui lòng nhập họ tên và số điện thoại liên hệ!');
            header('Location: ' . BASE_URL . '/pages/dat-san.php?san_bong_id=' . $sanBongId);
            exit;
        }

        // Kiểm tra xem số điện thoại đã có tài khoản chưa
        $uStmt = $pdo->prepare("SELECT * FROM nguoi_dung WHERE so_dien_thoai = ?");
        $uStmt->execute([$soDienThoai]);
        $existingUser = $uStmt->fetch();

        if ($existingUser) {
            // Đã có tài khoản: Sử dụng ID và tự động đăng nhập nếu mật khẩu chưa có
            $userId = (int)$existingUser['id'];
            loginUser($existingUser);
        } else {
            // Tạo tài khoản mới tự động cho khách
            $defaultPassword = password_hash($soDienThoai, PASSWORD_DEFAULT);
            $newUStmt = $pdo->prepare("
                INSERT INTO nguoi_dung (vai_tro_id, ho_ten, email, so_dien_thoai, mat_khau)
                VALUES (3, ?, ?, ?, ?)
            ");
            $newUStmt->execute([
                $hoTen,
                !empty($email) ? $email : null,
                $soDienThoai,
                $defaultPassword
            ]);
            $userId = (int)$pdo->lastInsertId();

            loginUser([
                'id' => $userId,
                'ho_ten' => $hoTen,
                'email' => $email,
                'so_dien_thoai' => $soDienThoai,
                'vai_tro_id' => 3,
                'ten_vai_tro' => 'Khách hàng'
            ]);
        }
    }

    // 7. BẮT ĐẦU TRANSACTION
    $pdo->beginTransaction();

    // 7.1 Tính tiền dịch vụ
    $servicesTotal = 0;
    $validServiceItems = [];

    if (!empty($servicesPost) && is_array($servicesPost)) {
        foreach ($servicesPost as $sId => $qty) {
            $sId = (int)$sId;
            $qty = (int)$qty;
            if ($qty > 0) {
                $svcQuery = $pdo->prepare("SELECT id, ten_dich_vu, don_gia, don_vi_tinh FROM dich_vu WHERE id = ?");
                $svcQuery->execute([$sId]);
                $svcData = $svcQuery->fetch();
                if ($svcData) {
                    $itemTotal = (float)$svcData['don_gia'] * $qty;
                    $servicesTotal += $itemTotal;
                    $validServiceItems[] = [
                        'dich_vu_id'  => $sId,
                        'ten_dich_vu' => $svcData['ten_dich_vu'],
                        'don_vi_tinh' => $svcData['don_vi_tinh'] ?? 'Phần',
                        'so_luong'    => $qty,
                        'don_gia'     => (float)$svcData['don_gia'],
                        'thanh_tien'  => $itemTotal
                    ];
                }
            }
        }
    }

    $grandTotal = $pitchTotal + $servicesTotal;

    // 7.2 Thêm vào bảng dat_san
    $bookingStmt = $pdo->prepare("
        INSERT INTO dat_san (nguoi_dung_id, san_bong_id, ngay_dat, gio_bat_dau, gio_ket_thuc, tong_tien, trang_thai_dat)
        VALUES (?, ?, ?, ?, ?, ?, 'Chờ xác nhận')
    ");
    $bookingStmt->execute([
        $userId,
        $sanBongId,
        $ngayDat,
        $gioBatDau,
        $gioKetThuc,
        $grandTotal
    ]);
    $bookingId = (int)$pdo->lastInsertId();

    // 7.3 Thêm vào bảng chi_tiet_dich_vu nếu có
    if (!empty($validServiceItems)) {
        // Tự động kiểm tra cấu trúc cột thực tế của bảng chi_tiet_dich_vu để tương thích tuyệt đối
        $existingCols = [];
        try {
            $existingCols = $pdo->query("SHOW COLUMNS FROM chi_tiet_dich_vu")->fetchAll(PDO::FETCH_COLUMN);
        } catch (Throwable $e) {}

        $hasFullCols = in_array('ten_dich_vu', $existingCols) && in_array('thanh_tien', $existingCols);

        if ($hasFullCols) {
            $detailStmt = $pdo->prepare("
                INSERT INTO chi_tiet_dich_vu (dat_san_id, dich_vu_id, ten_dich_vu, don_vi_tinh, so_luong, don_gia, thanh_tien)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($validServiceItems as $svc) {
                $detailStmt->execute([
                    $bookingId,
                    $svc['dich_vu_id'],
                    $svc['ten_dich_vu'],
                    $svc['don_vi_tinh'],
                    $svc['so_luong'],
                    $svc['don_gia'],
                    $svc['thanh_tien']
                ]);
            }
        } else {
            $detailStmt = $pdo->prepare("
                INSERT INTO chi_tiet_dich_vu (dat_san_id, dich_vu_id, so_luong, don_gia)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($validServiceItems as $svc) {
                $detailStmt->execute([
                    $bookingId,
                    $svc['dich_vu_id'],
                    $svc['so_luong'],
                    $svc['don_gia']
                ]);
            }
        }
    }

    // 7.4 Thêm vào bảng thanh_toan
    // Bảng thanh_toan: id, dat_san_id (UNIQUE), phuong_thuc_thanh_toan, so_tien, trang_thai_thanh_toan, ngay_thanh_toan
    // Các phương thức hợp lệ: 'Tiền mặt', 'Chuyển khoản', 'Momo', 'VNPay'
    $validPaymentMethods = ['Tiền mặt', 'Chuyển khoản', 'Momo', 'VNPay'];
    if (!in_array($phuongThucTT, $validPaymentMethods)) {
        $phuongThucTT = 'Tiền mặt';
    }

    $paymentStmt = $pdo->prepare("
        INSERT INTO thanh_toan (dat_san_id, phuong_thuc_thanh_toan, so_tien, trang_thai_thanh_toan)
        VALUES (?, ?, ?, 'Chưa thanh toán')
    ");
    $paymentStmt->execute([
        $bookingId,
        $phuongThucTT,
        $grandTotal
    ]);

    // 7.5 Hoàn tất transaction
    $pdo->commit();

    setFlash('success', 'Đặt sân thành công! Mã đơn của bạn là #' . $bookingId . '.');
    header('Location: ' . BASE_URL . '/pages/chi-tiet-don.php?id=' . $bookingId);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Booking Error: ' . $e->getMessage());
    setFlash('danger', 'Không thể hoàn tất đặt sân: ' . $e->getMessage());
    header('Location: ' . BASE_URL . '/pages/dat-san.php?san_bong_id=' . $sanBongId);
    exit;
}

