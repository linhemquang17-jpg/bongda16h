<?php
/**
 * Xử lý các thao tác tin tìm đối đá bóng (Thêm, Đổi trạng thái, Xóa)
 * Dự án: BongDa16h
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/tim-doi-bong.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    setFlash('danger', 'Phiên làm việc đã hết hạn hoặc yêu cầu không hợp lệ!');
    header('Location: ' . BASE_URL . '/pages/tim-doi-bong.php');
    exit;
}

$action = sanitize($_POST['action'] ?? '');
$pdo = getDBConnection();
$currentUser = getCurrentUser();

if ($action === 'add') {
    $tenDoi       = sanitize($_POST['ten_doi'] ?? '');
    $loaiSan      = sanitize($_POST['loai_san'] ?? 'Sân 7 người');
    $khuVuc       = sanitize($_POST['khu_vuc'] ?? '');
    $cumSanId     = !empty($_POST['cum_san_id']) ? (int)$_POST['cum_san_id'] : null;
    $sanBongId    = !empty($_POST['san_bong_id']) ? (int)$_POST['san_bong_id'] : null;
    $ngayDa       = sanitize($_POST['ngay_da'] ?? '');
    $gioDa        = sanitize($_POST['gio_da'] ?? '');
    $thoiLuong    = (int)($_POST['thoi_luong'] ?? 90);
    $trinhDo      = sanitize($_POST['trinh_do'] ?? 'Phong trào');
    $moTa         = sanitize($_POST['mo_ta'] ?? '');
    $nguoiLienHe  = sanitize($_POST['nguoi_lien_he'] ?? '');
    $soDienThoai  = sanitize($_POST['so_dien_thoai'] ?? '');
    $zalo         = sanitize($_POST['zalo'] ?? '');
    $anhDaiDien   = sanitize($_POST['anh_dai_dien'] ?? '');

    // Validate
    if (empty($tenDoi) || empty($ngayDa) || empty($gioDa) || empty($nguoiLienHe) || empty($soDienThoai)) {
        setFlash('danger', 'Vui lòng điền đầy đủ các thông tin bắt buộc (Tên đội, Ngày đá, Giờ đá, Người liên hệ, SĐT)!');
        header('Location: ' . BASE_URL . '/pages/tim-doi-bong.php');
        exit;
    }

    if (!preg_match('/^[0-9]{9,11}$/', str_replace([' ', '.', '-'], '', $soDienThoai))) {
        setFlash('danger', 'Số điện thoại liên hệ không hợp lệ!');
        header('Location: ' . BASE_URL . '/pages/tim-doi-bong.php');
        exit;
    }

    if (empty($zalo)) {
        $zalo = $soDienThoai;
    }

    if (empty($anhDaiDien)) {
        $sampleLogos = [
            'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?auto=format&fit=crop&w=400&q=80',
            'https://images.unsplash.com/photo-1529900748604-07564a03e7a6?auto=format&fit=crop&w=400&q=80',
            'https://images.unsplash.com/photo-1574629810360-7efbbe195018?auto=format&fit=crop&w=400&q=80',
            'https://images.unsplash.com/photo-1551958219-acbc608c6377?auto=format&fit=crop&w=400&q=80'
        ];
        $anhDaiDien = $sampleLogos[array_rand($sampleLogos)];
    }

    // Nếu chọn cụm sân, tự động lấy tọa độ của cụm sân nếu có
    $kinhDo = null;
    $viDo = null;
    if ($cumSanId) {
        $cStmt = $pdo->prepare("SELECT kinh_do, vi_do, ten_cum_san FROM cum_san WHERE id = ?");
        $cStmt->execute([$cumSanId]);
        $cumData = $cStmt->fetch();
        if ($cumData) {
            $kinhDo = $cumData['kinh_do'];
            $viDo   = $cumData['vi_do'];
            if (empty($khuVuc)) {
                $khuVuc = $cumData['ten_cum_san'];
            }
        }
    }

    $nguoiDungId = $currentUser ? (int)$currentUser['id'] : null;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO tim_doi_bong 
            (nguoi_dung_id, cum_san_id, san_bong_id, ten_doi, anh_dai_dien, loai_san, khu_vuc, kinh_do, vi_do, ngay_da, gio_da, thoi_luong, trinh_do, mo_ta, nguoi_lien_he, so_dien_thoai, zalo, trang_thai)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Đang tìm đối')
        ");
        $stmt->execute([
            $nguoiDungId,
            $cumSanId,
            $sanBongId,
            $tenDoi,
            $anhDaiDien,
            $loaiSan,
            $khuVuc,
            $kinhDo,
            $viDo,
            $ngayDa,
            $gioDa,
            $thoiLuong,
            $trinhDo,
            $moTa,
            $nguoiLienHe,
            $soDienThoai,
            $zalo
        ]);

        $newId = (int)$pdo->lastInsertId();
        if (!isset($_SESSION['my_posted_match_ids'])) {
            $_SESSION['my_posted_match_ids'] = [];
        }
        $_SESSION['my_posted_match_ids'][] = $newId;

        setFlash('success', 'Đăng tin tìm đối thành công! Tin của bạn đã được hiển thị trên hệ thống.');
    } catch (Exception $e) {
        setFlash('danger', 'Lỗi khi đăng tin: ' . $e->getMessage());
    }

    header('Location: ' . BASE_URL . '/pages/tim-doi-bong.php');
    exit;

} elseif ($action === 'update_status') {
    $id = (int)($_POST['id'] ?? 0);
    $status = sanitize($_POST['trang_thai'] ?? '');

    $validStatuses = ['Đang tìm đối', 'Đã tìm được đối', 'Đã hủy'];
    if (!in_array($status, $validStatuses)) {
        setFlash('danger', 'Trạng thái không hợp lệ!');
        header('Location: ' . BASE_URL . '/pages/tim-doi-bong.php');
        exit;
    }

    $chk = $pdo->prepare("SELECT * FROM tim_doi_bong WHERE id = ?");
    $chk->execute([$id]);
    $post = $chk->fetch();

    if (!$post) {
        setFlash('danger', 'Tin không tồn tại!');
        header('Location: ' . BASE_URL . '/pages/tim-doi-bong.php');
        exit;
    }

    if (!canManageMatchPost($post, $currentUser)) {
        setFlash('danger', 'Bạn không có quyền thay đổi trạng thái tin này!');
        header('Location: ' . BASE_URL . '/pages/tim-doi-bong.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE tim_doi_bong SET trang_thai = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        setFlash('success', 'Cập nhật trạng thái tin thành công: "' . e($status) . '"');
    } catch (Exception $e) {
        setFlash('danger', 'Lỗi cập nhật: ' . $e->getMessage());
    }

    // Nếu gọi từ admin
    if (!empty($_POST['from_admin'])) {
        header('Location: ' . BASE_URL . '/admin/tim-doi.php');
        exit;
    }

    header('Location: ' . BASE_URL . '/pages/tim-doi-bong.php');
    exit;

} elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);

    $chk = $pdo->prepare("SELECT * FROM tim_doi_bong WHERE id = ?");
    $chk->execute([$id]);
    $post = $chk->fetch();

    if (!$post) {
        setFlash('danger', 'Tin không tồn tại!');
        header('Location: ' . BASE_URL . '/pages/tim-doi-bong.php');
        exit;
    }

    if (!canManageMatchPost($post, $currentUser)) {
        setFlash('danger', 'Bạn không có quyền xóa tin này!');
        header('Location: ' . BASE_URL . '/pages/tim-doi-bong.php');
        exit;
    }

    try {
        $pdo->prepare("DELETE FROM tim_doi_bong WHERE id = ?")->execute([$id]);
        setFlash('success', 'Đã xóa tin tìm đối thành công!');
    } catch (Exception $e) {
        setFlash('danger', 'Lỗi khi xóa: ' . $e->getMessage());
    }

    if (!empty($_POST['from_admin'])) {
        header('Location: ' . BASE_URL . '/admin/tim-doi.php');
        exit;
    }

    header('Location: ' . BASE_URL . '/pages/tim-doi-bong.php');
    exit;
}

header('Location: ' . BASE_URL . '/pages/tim-doi-bong.php');
exit;

