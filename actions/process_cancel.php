<?php
/**
 * Xử lý hủy đơn đặt sân
 * Dự án: BongDa16h
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/lich-su-dat-san.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    setFlash('danger', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn!');
    header('Location: ' . BASE_URL . '/pages/lich-su-dat-san.php');
    exit;
}

$bookingId = (int)($_POST['booking_id'] ?? 0);
if (!$bookingId) {
    setFlash('danger', 'Mã đơn đặt sân không hợp lệ!');
    header('Location: ' . BASE_URL . '/pages/lich-su-dat-san.php');
    exit;
}

try {
    $pdo = getDBConnection();
    $currentUser = getCurrentUser();

    $stmt = $pdo->prepare("SELECT * FROM dat_san WHERE id = ?");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        setFlash('danger', 'Đơn đặt sân không tồn tại!');
        header('Location: ' . BASE_URL . '/pages/lich-su-dat-san.php');
        exit;
    }

    // Kiểm tra quyền hủy đơn: Admin/Nhân viên hoặc chính chủ sở hữu đơn
    if (!isStaffOrAdmin() && (int)$booking['nguoi_dung_id'] !== (int)$currentUser['id']) {
        setFlash('danger', 'Bạn không có quyền thực hiện thao tác trên đơn đặt sân này!');
        header('Location: ' . BASE_URL . '/pages/lich-su-dat-san.php');
        exit;
    }

    // Điều kiện hủy đối với khách hàng: chỉ hủy được khi đang 'Chờ xác nhận'
    if (!isStaffOrAdmin() && $booking['trang_thai_dat'] !== 'Chờ xác nhận') {
        setFlash('warning', 'Đơn đặt sân đã được xác nhận hoặc đã hoàn thành, không thể tự hủy. Vui lòng liên hệ hotline cụm sân để được hỗ trợ.');
        header('Location: ' . BASE_URL . '/pages/chi-tiet-don.php?id=' . $bookingId);
        exit;
    }

    // Cập nhật trạng thái thành 'Đã hủy'
    $updateStmt = $pdo->prepare("UPDATE dat_san SET trang_thai_dat = 'Đã hủy' WHERE id = ?");
    $updateStmt->execute([$bookingId]);

    setFlash('success', "Đơn đặt sân #{$bookingId} đã được hủy thành công!");
    header('Location: ' . BASE_URL . '/pages/chi-tiet-don.php?id=' . $bookingId);
    exit;

} catch (Exception $e) {
    setFlash('danger', 'Lỗi khi hủy đơn: ' . $e->getMessage());
    header('Location: ' . BASE_URL . '/pages/chi-tiet-don.php?id=' . $bookingId);
    exit;
}

