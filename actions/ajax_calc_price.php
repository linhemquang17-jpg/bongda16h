<?php
/**
 * AJAX Endpoint: Kiểm tra tính hợp lệ và tính tiền thời gian thực
 * Dự án: BongDa16h
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode([
        'valid' => false,
        'message' => 'Dữ liệu không hợp lệ!'
    ]);
    exit;
}

$sanBongId = isset($data['san_bong_id']) ? (int)$data['san_bong_id'] : 0;
$ngayDat = isset($data['ngay_dat']) ? sanitize($data['ngay_dat']) : '';
$gioBatDau = isset($data['gio_bat_dau']) ? sanitize($data['gio_bat_dau']) : '';
$gioKetThuc = isset($data['gio_ket_thuc']) ? sanitize($data['gio_ket_thuc']) : '';
$selectedServices = isset($data['dich_vu']) && is_array($data['dich_vu']) ? $data['dich_vu'] : [];

if (!$sanBongId || !$ngayDat || !$gioBatDau || !$gioKetThuc) {
    echo json_encode([
        'valid' => false,
        'message' => 'Vui lòng chọn đầy đủ sân, ngày và khung giờ!'
    ]);
    exit;
}

// Kiểm tra ngày không được là ngày trong quá khứ
$today = date('Y-m-d');
if ($ngayDat < $today) {
    echo json_encode([
        'valid' => false,
        'message' => 'Ngày đặt không được là ngày trong quá khứ!'
    ]);
    exit;
}

// Nếu ngày đặt là hôm nay, kiểm tra giờ bắt đầu không được trong quá khứ
if ($ngayDat === $today) {
    $currentTime = date('H:i');
    if ($gioBatDau <= $currentTime) {
        echo json_encode([
            'valid' => false,
            'message' => 'Giờ bắt đầu phải lớn hơn thời gian hiện tại (' . $currentTime . ')!'
        ]);
        exit;
    }
}

try {
    $pdo = getDBConnection();

    // 1. Lấy thông tin sân bóng và cụm sân
    $stmt = $pdo->prepare("
        SELECT s.*, c.gio_mo_cua, c.gio_dong_cua 
        FROM san_bong s
        JOIN cum_san c ON s.cum_san_id = c.id
        WHERE s.id = ?
    ");
    $stmt->execute([$sanBongId]);
    $pitch = $stmt->fetch();

    if (!$pitch) {
        echo json_encode([
            'valid' => false,
            'message' => 'Sân bóng không tồn tại!'
        ]);
        exit;
    }

    if ($pitch['trang_thai'] !== 'Hoạt động') {
        echo json_encode([
            'valid' => false,
            'message' => 'Sân bóng hiện đang bảo trì, vui lòng chọn sân khác!'
        ]);
        exit;
    }

    // 2. Kiểm tra giờ mở cửa và đóng cửa của cụm sân
    if ($pitch['gio_mo_cua'] && $gioBatDau < substr($pitch['gio_mo_cua'], 0, 5)) {
        echo json_encode([
            'valid' => false,
            'message' => 'Giờ mở cửa cụm sân bắt đầu từ ' . substr($pitch['gio_mo_cua'], 0, 5) . '!'
        ]);
        exit;
    }

    if ($pitch['gio_dong_cua'] && $gioKetThuc > substr($pitch['gio_dong_cua'], 0, 5)) {
        echo json_encode([
            'valid' => false,
            'message' => 'Cụm sân đóng cửa lúc ' . substr($pitch['gio_dong_cua'], 0, 5) . '!'
        ]);
        exit;
    }

    // 3. Tính thời lượng và giá sân
    $calc = calculatePitchPrice($pitch['gia_theo_gio'], $gioBatDau, $gioKetThuc);
    if (!$calc['valid']) {
        echo json_encode($calc);
        exit;
    }

    // 4. Kiểm tra xung đột lịch đặt sân (trùng giờ)
    $isAvailable = checkPitchAvailability($sanBongId, $ngayDat, $gioBatDau, $gioKetThuc, $pdo);
    if (!$isAvailable) {
        echo json_encode([
            'valid' => false,
            'message' => 'Khung giờ ' . $gioBatDau . ' - ' . $gioKetThuc . ' đã có người đặt trước! Vui lòng chọn khung giờ khác.'
        ]);
        exit;
    }

    // 5. Tính tiền dịch vụ bổ sung
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
                $qty = max(1, (int)$item['quantity']);
                if (isset($serviceMap[$id])) {
                    $servicesTotal += ($serviceMap[$id] * $qty);
                }
            }
        }
    }

    $grandTotal = $calc['total'] + $servicesTotal;

    echo json_encode([
        'valid' => true,
        'message' => 'Khung giờ trống và hợp lệ!',
        'pitch_hourly_rate' => (float)$pitch['gia_theo_gio'],
        'duration_minutes' => $calc['duration_minutes'],
        'hours' => $calc['hours'],
        'pitch_total' => $calc['total'],
        'services_total' => $servicesTotal,
        'grand_total' => $grandTotal
    ]);

} catch (Exception $e) {
    echo json_encode([
        'valid' => false,
        'message' => 'Lỗi xử lý: ' . $e->getMessage()
    ]);
}

