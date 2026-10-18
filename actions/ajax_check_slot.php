<?php
/**
 * AJAX Endpoint: Lấy danh sách khung giờ đã được đặt của sân trong ngày
 * Dự án: BongDa16h
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$sanBongId = isset($_GET['san_bong_id']) ? (int)$_GET['san_bong_id'] : 0;
$ngayDat = isset($_GET['ngay_dat']) ? sanitize($_GET['ngay_dat']) : '';

if (!$sanBongId || !$ngayDat) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin sân hoặc ngày đặt!',
        'slots' => []
    ]);
    exit;
}

// Kiểm tra định dạng ngày YYYY-MM-DD
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ngayDat)) {
    echo json_encode([
        'success' => false,
        'message' => 'Định dạng ngày không hợp lệ!',
        'slots' => []
    ]);
    exit;
}

try {
    $pdo = getDBConnection();
    $slots = getBookedSlots($sanBongId, $ngayDat, $pdo);

    echo json_encode([
        'success' => true,
        'message' => 'Thành công',
        'slots' => $slots
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống: ' . $e->getMessage(),
        'slots' => []
    ]);
}

