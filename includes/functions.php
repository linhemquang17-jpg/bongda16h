<?php
/**
 * Các hàm tiện ích dùng chung cho hệ thống BongDa16h
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Lọc và escape chuỗi hiển thị tránh XSS
 */
function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/**
 * Làm sạch dữ liệu input
 */
function sanitize($str) {
    return trim(strip_tags((string)$str));
}

/**
 * Định dạng tiền tệ VNĐ
 */
function formatCurrency($amount) {
    return number_format((float)$amount, 0, ',', '.') . ' đ';
}

/**
 * Định dạng ngày dd/mm/YYYY
 */
function formatDate($dateStr) {
    if (!$dateStr) return '';
    $timestamp = strtotime($dateStr);
    return date('d/m/Y', $timestamp);
}

/**
 * Định dạng giờ HH:ii
 */
function formatTime($timeStr) {
    if (!$timeStr) return '';
    return substr($timeStr, 0, 5);
}

/**
 * Định dạng ngày giờ dd/mm/YYYY HH:ii
 */
function formatDateTime($dateTimeStr) {
    if (!$dateTimeStr) return '';
    $timestamp = strtotime($dateTimeStr);
    return date('d/m/Y H:i', $timestamp);
}

/**
 * Tạo token CSRF
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Input ẩn chứa CSRF token
 */
function getCsrfField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Xác minh CSRF token
 */
function verifyCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Thiết lập Flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Lấy và xóa Flash message
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Hiển thị Flash message dạng Bootstrap Alert
 */
function renderFlash() {
    $flash = getFlash();
    if ($flash) {
        $type = e($flash['type']);
        $msg = e($flash['message']);
        $icon = 'bi-info-circle';
        if ($type === 'success') $icon = 'bi-check-circle-fill';
        elseif ($type === 'danger') $icon = 'bi-exclamation-triangle-fill';
        elseif ($type === 'warning') $icon = 'bi-exclamation-circle-fill';

        echo "
        <div class=\"alert alert-{$type} alert-dismissible fade show d-flex align-items-center shadow-sm\" role=\"alert\">
            <i class=\"bi {$icon} me-2 fs-5\"></i>
            <div>{$msg}</div>
            <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>
        </div>";
    }
}

/**
 * Chuẩn hóa URL hiển thị hình ảnh (hỗ trợ cả URL ngoài Unsplash/CDN và file cục bộ trong uploads/)
 */
function getImageUrl($url) {
    if (empty($url)) {
        return 'https://images.unsplash.com/photo-1529900748604-07564a03e7a6?auto=format&fit=crop&w=800&q=80';
    }
    // Nếu là URL ngoài (http:// hoặc https://)
    if (preg_match('~^https?://~i', $url)) {
        return $url;
    }
    // Nếu là đường dẫn cục bộ (ví dụ: uploads/san_bong/pitch_1_xxx.jpg)
    return rtrim(BASE_URL, '/') . '/' . ltrim($url, '/');
}

/**
 * Kiểm tra xem sân bóng có ảnh đại diện riêng chính thức hay không
 */
function hasCustomPitchAvatar($sanBongId, $pdo = null) {
    if (!$pdo) {
        $pdo = getDBConnection();
    }
    $stmt = $pdo->prepare("SELECT id FROM hinh_anh_san WHERE san_bong_id = ? AND la_anh_chinh = 1 LIMIT 1");
    $stmt->execute([(int)$sanBongId]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Lấy ảnh đại diện chính của sân bóng
 * Quy tắc ưu tiên:
 * 1. Ảnh của san_bong có la_anh_chinh = 1
 * 2. Ảnh bất kỳ của san_bong
 * 3. Ảnh của cum_san có la_anh_chinh = 1
 * 4. Ảnh bất kỳ của cum_san
 * 5. Ảnh mặc định dự phòng
 */
function getPitchMainImage($sanBongId, $cumSanId = null, $pdo = null) {
    if (!$pdo) {
        $pdo = getDBConnection();
    }

    $sanBongId = (int)$sanBongId;

    // 1. Ảnh đại diện lưu trực tiếp trong trường hinh_anh_dai_dien của bảng san_bong
    if ($sanBongId > 0) {
        try {
            $stmt = $pdo->prepare("SELECT hinh_anh_dai_dien FROM san_bong WHERE id = ?");
            $stmt->execute([$sanBongId]);
            $avatar = $stmt->fetchColumn();
            if (!empty($avatar)) {
                return getImageUrl($avatar);
            }
        } catch (Exception $e) {
            // Bỏ qua nếu cột chưa tồn tại
        }

        // 2. Ảnh chính của sân bóng trong hinh_anh_san
        $stmt = $pdo->prepare("SELECT url_hinh_anh FROM hinh_anh_san WHERE san_bong_id = ? AND la_anh_chinh = 1 LIMIT 1");
        $stmt->execute([$sanBongId]);
        $img = $stmt->fetchColumn();
        if ($img) return getImageUrl($img);

        // 3. Ảnh bất kỳ của sân bóng
        $stmt = $pdo->prepare("SELECT url_hinh_anh FROM hinh_anh_san WHERE san_bong_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$sanBongId]);
        $img = $stmt->fetchColumn();
        if ($img) return getImageUrl($img);
    }

    // 4. Ảnh chính của cụm sân
    if ($cumSanId) {
        $stmt = $pdo->prepare("SELECT url_hinh_anh FROM hinh_anh_san WHERE cum_san_id = ? AND la_anh_chinh = 1 LIMIT 1");
        $stmt->execute([$cumSanId]);
        $img = $stmt->fetchColumn();
        if ($img) return getImageUrl($img);

        // 5. Ảnh bất kỳ của cụm sân
        $stmt = $pdo->prepare("SELECT url_hinh_anh FROM hinh_anh_san WHERE cum_san_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$cumSanId]);
        $img = $stmt->fetchColumn();
        if ($img) return getImageUrl($img);
    }

    // 6. Ảnh dự phòng chất lượng cao về sân bóng đá
    return 'https://images.unsplash.com/photo-1529900748604-07564a03e7a6?auto=format&fit=crop&w=800&q=80';
}

/**
 * Lấy bộ sưu tập tất cả ảnh của sân (kết hợp sân và cụm sân)
 * Tương thích 100% với môi trường Hosting (không dùng trùng tên placeholder)
 */
function getPitchGallery($sanBongId, $cumSanId = null, $pdo = null) {
    if (!$pdo) {
        $pdo = getDBConnection();
    }

    $sanBongId = (int)$sanBongId;
    $cumSanId  = $cumSanId ? (int)$cumSanId : null;

    $defaultFallback = [[
        'id' => 0,
        'san_bong_id' => $sanBongId,
        'cum_san_id' => $cumSanId,
        'url_hinh_anh' => 'https://images.unsplash.com/photo-1529900748604-07564a03e7a6?auto=format&fit=crop&w=800&q=80',
        'la_anh_chinh' => 1
    ]];

    try {
        if ($cumSanId) {
            $sql = "
                SELECT * FROM hinh_anh_san 
                WHERE san_bong_id = ? OR cum_san_id = ?
                ORDER BY (san_bong_id = ? AND la_anh_chinh = 1) DESC, la_anh_chinh DESC, id DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sanBongId, $cumSanId, $sanBongId]);
        } else {
            $sql = "
                SELECT * FROM hinh_anh_san 
                WHERE san_bong_id = ?
                ORDER BY la_anh_chinh DESC, id DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sanBongId]);
        }
        $list = $stmt->fetchAll();

        // Kiểm tra ảnh đại diện trực tiếp trong san_bong nếu chưa có trong list
        if ($sanBongId > 0) {
            try {
                $pStmt = $pdo->prepare("SELECT hinh_anh_dai_dien FROM san_bong WHERE id = ?");
                $pStmt->execute([$sanBongId]);
                $avatar = $pStmt->fetchColumn();
                if (!empty($avatar)) {
                    $found = false;
                    foreach ($list as $item) {
                        if ($item['url_hinh_anh'] === $avatar) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        array_unshift($list, [
                            'id' => 0,
                            'san_bong_id' => $sanBongId,
                            'cum_san_id' => $cumSanId,
                            'url_hinh_anh' => $avatar,
                            'la_anh_chinh' => 1
                        ]);
                    }
                }
            } catch (Exception $e) {
                // Ignore
            }
        }

        if (empty($list)) {
            return $defaultFallback;
        }

        // Chuẩn hóa url_hinh_anh qua getImageUrl
        foreach ($list as &$item) {
            $item['url_hinh_anh'] = getImageUrl($item['url_hinh_anh']);
        }
        unset($item);

        return $list;
    } catch (Exception $e) {
        error_log('Error in getPitchGallery: ' . $e->getMessage());
        return $defaultFallback;
    }
}

/**
 * Tạo link Google Map cho cụm sân
 */
function getGoogleMapUrl($cumSan) {
    if (!empty($cumSan['link_google_map']) && filter_var($cumSan['link_google_map'], FILTER_VALIDATE_URL)) {
        return $cumSan['link_google_map'];
    }

    // Nếu có kinh độ, vĩ độ hợp lệ (kinh_do < 90 và vi_do < 90)
    if (!empty($cumSan['vi_do']) && !empty($cumSan['kinh_do']) && (float)$cumSan['kinh_do'] < 90) {
        return 'https://www.google.com/maps?q=' . (float)$cumSan['vi_do'] . ',' . (float)$cumSan['kinh_do'];
    }

    // Sử dụng địa chỉ để tìm kiếm trên Google Maps
    $query = (!empty($cumSan['ten_cum_san']) ? $cumSan['ten_cum_san'] . ', ' : '') . ($cumSan['dia_chi'] ?? '');
    return 'https://www.google.com/maps/search/?api=1&query=' . urlencode($query);
}

/**
 * Kiểm tra xung đột lịch đặt sân (backend validation)
 * @return bool True nếu sân còn trống, False nếu bị trùng lịch
 */
function checkPitchAvailability($sanBongId, $date, $startTime, $endTime, $pdo = null, $excludeBookingId = null) {
    if (!$pdo) {
        $pdo = getDBConnection();
    }

    // Điều kiện xung đột hai khoảng thời gian (StartA < EndB) AND (EndA > StartB)
    $sql = "
        SELECT COUNT(*) 
        FROM dat_san 
        WHERE san_bong_id = :san_id 
          AND ngay_dat = :ngay_dat 
          AND trang_thai_dat != 'Đã hủy'
          AND (gio_bat_dau < :gio_ket_thuc AND gio_ket_thuc > :gio_bat_dau)
    ";

    $params = [
        ':san_id'      => $sanBongId,
        ':ngay_dat'    => $date,
        ':gio_bat_dau' => $startTime,
        ':gio_ket_thuc'=> $endTime
    ];

    if ($excludeBookingId !== null) {
        $sql .= " AND id != :exclude_id";
        $params[':exclude_id'] = $excludeBookingId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $count = (int)$stmt->fetchColumn();

    return ($count === 0);
}

/**
 * Lấy danh sách các khung giờ đã đặt của một sân trong ngày
 */
function getBookedSlots($sanBongId, $date, $pdo = null) {
    if (!$pdo) {
        $pdo = getDBConnection();
    }

    $stmt = $pdo->prepare("
        SELECT id, gio_bat_dau, gio_ket_thuc, trang_thai_dat
        FROM dat_san
        WHERE san_bong_id = ? AND ngay_dat = ? AND trang_thai_dat != 'Đã hủy'
        ORDER BY gio_bat_dau ASC
    ");
    $stmt->execute([$sanBongId, $date]);
    return $stmt->fetchAll();
}

/**
 * Tính số giờ và tổng tiền thuê sân
 */
function calculatePitchPrice($giaTheoGio, $startTime, $endTime) {
    $startTs = strtotime($startTime);
    $endTs = strtotime($endTime);

    if ($endTs <= $startTs) {
        return [
            'valid' => false,
            'message' => 'Giờ kết thúc phải lớn hơn giờ bắt đầu!',
            'hours' => 0,
            'total' => 0
        ];
    }

    $durationMinutes = ($endTs - $startTs) / 60;
    if ($durationMinutes < 30) {
        return [
            'valid' => false,
            'message' => 'Thời lượng đặt sân tối thiểu là 30 phút!',
            'hours' => 0,
            'total' => 0
        ];
    }

    $hours = $durationMinutes / 60;
    $total = round($hours * (float)$giaTheoGio);

    return [
        'valid' => true,
        'message' => 'Hợp lệ',
        'duration_minutes' => $durationMinutes,
        'hours' => round($hours, 2),
        'total' => $total
    ];
}

/**
 * Render Badge trạng thái đặt sân
 */
function renderBookingStatusBadge($status) {
    $badges = [
        'Chờ xác nhận' => 'bg-warning text-dark',
        'Đã xác nhận'  => 'bg-primary text-white',
        'Đã hoàn thành'=> 'bg-success text-white',
        'Đã hủy'        => 'bg-danger text-white'
    ];
    $cls = $badges[$status] ?? 'bg-secondary text-white';
    return '<span class="badge ' . $cls . ' px-2 py-1"><i class="bi bi-circle-fill me-1 small"></i>' . e($status) . '</span>';
}

/**
 * Render Badge trạng thái thanh toán
 */
function renderPaymentStatusBadge($status) {
    $badges = [
        'Chưa thanh toán' => 'bg-danger text-white',
        'Đã đặt cọc'      => 'bg-info text-dark',
        'Đã thanh toán đủ'=> 'bg-success text-white'
    ];
    $cls = $badges[$status] ?? 'bg-secondary text-white';
    return '<span class="badge ' . $cls . ' px-2 py-1">' . e($status) . '</span>';
}

/**
 * Render Badge trạng thái sân bóng
 */
function renderPitchStatusBadge($status) {
    if ($status === 'Hoạt động') {
        return '<span class="badge bg-success-subtle text-success border border-success px-2 py-1"><i class="bi bi-check-circle me-1"></i>Hoạt động</span>';
    }
    return '<span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1"><i class="bi bi-tools me-1"></i>Bảo trì</span>';
}

/**
 * Tính khoảng cách đường chim bay giữa 2 tọa độ theo công thức Haversine (km)
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    if (empty($lat1) || empty($lon1) || empty($lat2) || empty($lon2)) {
        return null;
    }
    $lat1 = (float)$lat1;
    $lon1 = (float)$lon1;
    $lat2 = (float)$lat2;
    $lon2 = (float)$lon2;

    // Kiểm tra tính hợp lệ của tọa độ (VN: Vĩ độ 8-24, Kinh độ 102-110)
    if ($lat1 <= 0 || $lon1 <= 0 || $lat2 <= 0 || $lon2 <= 0 || $lon1 > 120 || $lon2 > 120) {
        return null;
    }

    $earthRadius = 6371; // km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($earthRadius * $c, 1);
}

/**
 * Render Badge trình độ đội bóng
 */
function renderMatchSkillBadge($skill) {
    $map = [
        'Giao lưu'   => 'bg-info-subtle text-info-emphasis border border-info',
        'Phong trào' => 'bg-success-subtle text-success-emphasis border border-success',
        'Trung bình' => 'bg-warning-subtle text-warning-emphasis border border-warning',
        'Khá'        => 'bg-danger-subtle text-danger-emphasis border border-danger',
    ];
    $cls = $map[$skill] ?? 'bg-secondary-subtle text-secondary';
    return '<span class="badge ' . $cls . ' px-2 py-1"><i class="bi bi-fire me-1"></i>' . e($skill) . '</span>';
}

/**
 * Render Badge trạng thái tin tìm đối
 */
function renderMatchStatusBadge($status) {
    $map = [
        'Đang tìm đối'     => 'bg-success text-white',
        'Đã tìm được đối' => 'bg-primary text-white',
        'Đã hủy'           => 'bg-secondary text-white',
    ];
    $cls = $map[$status] ?? 'bg-secondary text-white';
    $icon = $status === 'Đang tìm đối' ? 'bi-broadcast-pin' : ($status === 'Đã tìm được đối' ? 'bi-check-all' : 'bi-x-circle');
    return '<span class="badge ' . $cls . ' px-2 py-1"><i class="bi ' . $icon . ' me-1"></i>' . e($status) . '</span>';
}

/**
 * Kiểm tra quyền quản lý (sửa/đổi trạng thái/xóa) tin tìm đối
 */
function canManageMatchPost($post, $currentUser = null) {
    if (!$currentUser && function_exists('getCurrentUser')) {
        $currentUser = getCurrentUser();
    }
    // Admin toàn quyền
    if ($currentUser && (int)($currentUser['vai_tro_id'] ?? 0) === 1) {
        return true;
    }
    // Chủ tin đăng khi đã đăng nhập
    if ($currentUser && !empty($post['nguoi_dung_id']) && (int)$post['nguoi_dung_id'] === (int)$currentUser['id']) {
        return true;
    }
    // Tin do khách đăng trong session hiện tại
    if (!empty($_SESSION['my_posted_match_ids']) && in_array((int)$post['id'], $_SESSION['my_posted_match_ids'])) {
        return true;
    }
    return false;
}

