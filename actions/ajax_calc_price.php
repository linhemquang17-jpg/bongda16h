<?php
/**
 * AJAX Endpoint: Kiểm tra tính hợp lệ và tính tiền thời gian thực
 * Dự án: BongDa16h
 * Tương thích: Localhost XAMPP & Hosting InfinityFree
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode([
        'valid' => false,
        'message' => 'Dữ liệu yêu cầu không hợp lệ!',
        'pitch_total' => 0,
        'services_total' => 0,
        'grand_total' => 0,
        'hours' => 0,
        'duration_minutes' => 0
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$sanBongId        = isset($data['san_bong_id']) ? (int)$data['san_bong_id'] : 0;
$ngayDat          = isset($data['ngay_dat']) ? sanitize($data['ngay_dat']) : '';
$gioBatDau        = isset($data['gio_bat_dau']) ? sanitize($data['gio_bat_dau']) : '';
$gioKetThuc       = isset($data['gio_ket_thuc']) ? sanitize($data['gio_ket_thuc']) : '';
$selectedServices = isset($data['dich_vu']) && is_array($data['dich_vu']) ? $data['dich_vu'] : [];

if (!$sanBongId || !$gioBatDau || !$gioKetThuc) {
    echo json_encode([
        'valid' => false,
        'message' => 'Vui lòng chọn sân bóng và khung giờ thi đấu!',
        'pitch_total' => 0,
        'services_total' => 0,
        'grand_total' => 0,
        'hours' => 0,
        'duration_minutes' => 0
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = getDBConnection();

    // 1. Lấy thông tin sân bóng và cụm sân
    $stmt = $pdo->prepare("
        SELECT s.*, c.ten_cum_san, c.gio_mo_cua, c.gio_dong_cua 
        FROM san_bong s
        LEFT JOIN cum_san c ON s.cum_san_id = c.id
        WHERE s.id = ?
    ");
    $stmt->execute([$sanBongId]);
    $pitch = $stmt->fetch();

    if (!$pitch) {
        echo json_encode([
            'valid' => false,
            'message' => 'Sân bóng không tồn tại trong hệ thống!',
            'pitch_total' => 0,
            'services_total' => 0,
            'grand_total' => 0,
            'hours' => 0,
            'duration_minutes' => 0
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Tính tiền sân theo thời lượng (Công thức: Giá mỗi giờ * Số giờ thuê)
    $hourlyRate = (float)$pitch['gia_theo_gio'];
    $calc = calculatePitchPrice($hourlyRate, $gioBatDau, $gioKetThuc);

    // 3. Tính tiền dịch vụ bổ sung
    $servicesTotal = 0;
    if (!empty($selectedServices)) {
        $svcIds = array_column($selectedServices, 'id');
        $svcIds = array_map('intval', $svcIds);

        if (!empty($svcIds)) {
            $inClause = implode(',', array_fill(0, count($svcIds), '?'));
            $svcStmt = $pdo->prepare("SELECT id, don_gia FROM dich_vu WHERE id IN ($inClause)");
            $svcStmt->execute($svcIds);
            $serviceMap = [];
            while ($row = $svcStmt->fetch()) {
                $serviceMap[$row['id']] = (float)$row['don_gia'];
            }

            foreach ($selectedServices as $item) {
                $id = (int)$item['id'];
                $qty = max(1, (int)($item['quantity'] ?? 1));
                if (isset($serviceMap[$id])) {
                    $servicesTotal += ($serviceMap[$id] * $qty);
                }
            }
        }
    }

    $pitchTotal = $calc['valid'] ? $calc['total'] : 0;
    $grandTotal = $pitchTotal + $servicesTotal;

    // 4. Kiểm tra các điều kiện hợp lệ
    $isValid = true;
    $message = 'Khung giờ hợp lệ và còn trống!';

    if (!$calc['valid']) {
        $isValid = false;
        $message = $calc['message'];
    } elseif ($pitch['trang_thai'] !== 'Hoạt động') {
        $isValid = false;
        $message = 'Sân bóng hiện đang bảo trì, vui lòng chọn sân khác!';
    } else {
        // Kiểm tra giờ đóng/mở cửa linh hoạt (tối thiểu từ 05:00 sáng đến 23:30 đêm)
        $gioMo = !empty($pitch['gio_mo_cua']) ? substr($pitch['gio_mo_cua'], 0, 5) : '05:00';
        $gioDong = !empty($pitch['gio_dong_cua']) ? substr($pitch['gio_dong_cua'], 0, 5) : '23:30';

        // Cho phép khung giờ sớm từ 05:00 sáng
        if ($gioBatDau < '05:00') {
            $isValid = false;
            $message = 'Khung giờ đặt sân sớm nhất bắt đầu từ 05:00 sáng!';
        } elseif ($gioKetThuc > '23:30') {
            $isValid = false;
            $message = 'Cụm sân đóng cửa lúc 23:30 đêm!';
        }

        // Kiểm tra xung đột lịch đặt sân (trùng giờ) nếu ngày đặt đã được chọn
        if ($isValid && !empty($ngayDat)) {
            $isAvailable = checkPitchAvailability($sanBongId, $ngayDat, $gioBatDau, $gioKetThuc, $pdo);
            if (!$isAvailable) {
                $isValid = false;
                $message = "Khung giờ {$gioBatDau} - {$gioKetThuc} đã có người đặt trước! Vui lòng chọn khung giờ khác.";
            }
        }
    }

    echo json_encode([
        'valid'             => $isValid,
        'message'           => $message,
        'pitch_hourly_rate' => $hourlyRate,
        'duration_minutes'  => $calc['duration_minutes'] ?? 0,
        'hours'             => $calc['hours'] ?? 0,
        'pitch_total'       => $pitchTotal,
        'services_total'    => $servicesTotal,
        'grand_total'       => $grandTotal
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode([
        'valid'          => false,
        'message'        => 'Lỗi xử lý hệ thống: ' . $e->getMessage(),
        'pitch_total'    => 0,
        'services_total' => 0,
        'grand_total'    => 0,
        'hours'          => 0,
        'duration_minutes' => 0
    ], JSON_UNESCAPED_UNICODE);
}
