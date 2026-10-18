<?php
/**
 * Quản Lý Sân Bóng - Admin
 * Dự án: BongDa16h
 * Đồng bộ trực tiếp 100% với Database MySQL
 */
$pageTitle = 'Quản Lý Sân Bóng';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/auth_check.php';

$pdo = getDBConnection();

// Hàm hỗ trợ xử lý upload hoặc nhận URL ảnh đại diện cho sân bóng
function handlePitchAvatarUpload($pitchId = 0) {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($_FILES['avatar']['size'] > $maxSize) {
            throw new Exception('Dung lượng file ảnh vượt quá giới hạn 5MB!');
        }

        $origName = $_FILES['avatar']['name'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowedExts)) {
            throw new Exception('Chỉ cho phép tải lên file ảnh có định dạng: JPG, JPEG, PNG, WEBP!');
        }

        $tmpPath = $_FILES['avatar']['tmp_name'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'])) {
            throw new Exception('File tải lên không phải là ảnh hợp lệ (MIME type: ' . $mimeType . ')!');
        }

        if (@getimagesize($tmpPath) === false) {
            throw new Exception('Nội dung file ảnh bị lỗi hoặc không đọc được!');
        }

        $uploadDir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'san_bong' . DIRECTORY_SEPARATOR;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $safeFileName = 'pitch_' . ($pitchId > 0 ? (int)$pitchId : 'new') . '_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
        $destFilePath = $uploadDir . $safeFileName;

        if (!move_uploaded_file($tmpPath, $destFilePath)) {
            throw new Exception('Không thể lưu file ảnh vào thư mục máy chủ!');
        }

        return 'uploads/san_bong/' . $safeFileName;
    } elseif (!empty($_POST['avatar_url'])) {
        return sanitize($_POST['avatar_url']);
    }

    return null;
}

// Xử lý các action (Thêm, Sửa, Xóa)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn!');
        header('Location: ' . BASE_URL . '/admin/san-bong.php');
        exit;
    }

    $action = sanitize($_POST['action'] ?? '');

    if ($action === 'add') {
        $tenSan    = sanitize($_POST['ten_san'] ?? '');
        $cumSanId  = (int)($_POST['cum_san_id'] ?? 0);
        $loaiSanId = (int)($_POST['loai_san_id'] ?? 0);
        $giaTheoGio= (float)($_POST['gia_theo_gio'] ?? 0);
        $diaChi    = sanitize($_POST['dia_chi'] ?? '');
        $linkMap   = sanitize($_POST['link_google_map'] ?? '');
        $trangThai = sanitize($_POST['trang_thai'] ?? 'Hoạt động');
        $ghiChu    = sanitize($_POST['ghi_chu'] ?? '');

        if (empty($tenSan) || !$cumSanId || !$loaiSanId || $giaTheoGio <= 0) {
            setFlash('danger', 'Vui lòng nhập đầy đủ các thông tin bắt buộc và giá thuê theo giờ hợp lệ!');
        } else {
            $newAvatarPath = null;
            try {
                $newAvatarPath = handlePitchAvatarUpload(0);

                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    INSERT INTO san_bong (cum_san_id, loai_san_id, ten_san, dia_chi, link_google_map, hinh_anh_dai_dien, gia_theo_gio, trang_thai, ghi_chu)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $cumSanId, 
                    $loaiSanId, 
                    $tenSan, 
                    !empty($diaChi) ? $diaChi : null, 
                    !empty($linkMap) ? $linkMap : null, 
                    $newAvatarPath, 
                    $giaTheoGio, 
                    $trangThai, 
                    $ghiChu
                ]);
                $newPitchId = (int)$pdo->lastInsertId();

                if (!empty($newAvatarPath)) {
                    $pdo->prepare("
                        INSERT INTO hinh_anh_san (san_bong_id, cum_san_id, url_hinh_anh, la_anh_chinh)
                        VALUES (?, NULL, ?, 1)
                    ")->execute([$newPitchId, $newAvatarPath]);
                }

                $pdo->commit();
                setFlash('success', "Thêm sân bóng mới #{$newPitchId} ('{$tenSan}') vào Database thành công!");
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($newAvatarPath && strpos($newAvatarPath, 'uploads/san_bong/') === 0) {
                    $fPath = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $newAvatarPath);
                    if (file_exists($fPath)) @unlink($fPath);
                }
                setFlash('danger', 'Lỗi thêm sân vào Database: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'edit') {
        $id        = (int)($_POST['id'] ?? 0);
        $tenSan    = sanitize($_POST['ten_san'] ?? '');
        $cumSanId  = (int)($_POST['cum_san_id'] ?? 0);
        $loaiSanId = (int)($_POST['loai_san_id'] ?? 0);
        $giaTheoGio= (float)($_POST['gia_theo_gio'] ?? 0);
        $diaChi    = sanitize($_POST['dia_chi'] ?? '');
        $linkMap   = sanitize($_POST['link_google_map'] ?? '');
        $trangThai = sanitize($_POST['trang_thai'] ?? 'Hoạt động');
        $ghiChu    = sanitize($_POST['ghi_chu'] ?? '');

        // Kiểm tra sân tồn tại
        $pitchStmt = $pdo->prepare("SELECT * FROM san_bong WHERE id = ?");
        $pitchStmt->execute([$id]);
        $currentPitch = $pitchStmt->fetch();

        if (!$currentPitch) {
            setFlash('danger', "Sân bóng #{$id} không tồn tại trong cơ sở dữ liệu!");
        } elseif (empty($tenSan) || !$cumSanId || !$loaiSanId || $giaTheoGio <= 0) {
            setFlash('danger', 'Thông tin cập nhật không hợp lệ! Vui lòng kiểm tra tên, cụm sân, loại sân và giá.');
        } else {
            $newAvatarPath = null;
            try {
                $newAvatarPath = handlePitchAvatarUpload($id);

                $pdo->beginTransaction();

                if ($newAvatarPath !== null) {
                    $stmt = $pdo->prepare("
                        UPDATE san_bong 
                        SET cum_san_id = ?, loai_san_id = ?, ten_san = ?, gia_theo_gio = ?, 
                            dia_chi = ?, link_google_map = ?, hinh_anh_dai_dien = ?, trang_thai = ?, ghi_chu = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $cumSanId, 
                        $loaiSanId, 
                        $tenSan, 
                        $giaTheoGio, 
                        !empty($diaChi) ? $diaChi : null, 
                        !empty($linkMap) ? $linkMap : null, 
                        $newAvatarPath, 
                        $trangThai, 
                        $ghiChu, 
                        $id
                    ]);

                    // Đồng bộ sang bảng hinh_anh_san
                    $pdo->prepare("UPDATE hinh_anh_san SET la_anh_chinh = 0 WHERE san_bong_id = ?")->execute([$id]);
                    $pdo->prepare("
                        INSERT INTO hinh_anh_san (san_bong_id, cum_san_id, url_hinh_anh, la_anh_chinh)
                        VALUES (?, NULL, ?, 1)
                    ")->execute([$id, $newAvatarPath]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE san_bong 
                        SET cum_san_id = ?, loai_san_id = ?, ten_san = ?, gia_theo_gio = ?, 
                            dia_chi = ?, link_google_map = ?, trang_thai = ?, ghi_chu = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $cumSanId, 
                        $loaiSanId, 
                        $tenSan, 
                        $giaTheoGio, 
                        !empty($diaChi) ? $diaChi : null, 
                        !empty($linkMap) ? $linkMap : null, 
                        $trangThai, 
                        $ghiChu, 
                        $id
                    ]);
                }

                $pdo->commit();
                setFlash('success', "Cập nhật sân bóng #{$id} ('{$tenSan}') vào Database thành công!");
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($newAvatarPath && strpos($newAvatarPath, 'uploads/san_bong/') === 0) {
                    $fPath = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $newAvatarPath);
                    if (file_exists($fPath)) @unlink($fPath);
                }
                setFlash('danger', 'Lỗi cập nhật Database: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Kiểm tra xem sân đã có đơn đặt sân trong lịch sử chưa
            $chkBooking = $pdo->prepare("SELECT COUNT(*) FROM dat_san WHERE san_bong_id = ?");
            $chkBooking->execute([$id]);
            $totalBookings = (int)$chkBooking->fetchColumn();

            if ($totalBookings > 0) {
                setFlash('warning', "Không thể xóa sân #{$id} vì sân đã có {$totalBookings} đơn đặt sân trong lịch sử! Để ngừng nhận khách, vui lòng sửa trạng thái sân thành 'Bảo trì'.");
            } else {
                try {
                    $pdo->beginTransaction();
                    $pdo->prepare("DELETE FROM hinh_anh_san WHERE san_bong_id = ?")->execute([$id]);
                    $pdo->prepare("DELETE FROM san_bong WHERE id = ?")->execute([$id]);
                    $pdo->commit();
                    setFlash('success', "Xóa sân bóng #{$id} khỏi cơ sở dữ liệu thành công!");
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    setFlash('danger', 'Lỗi khi xóa sân: ' . $e->getMessage());
                }
            }
        }
    }

    header('Location: ' . BASE_URL . '/admin/san-bong.php');
    exit;
}

// Lấy danh sách sân bóng kèm cụm sân, loại sân và ảnh đại diện
$pitches = $pdo->query("
    SELECT s.*, c.ten_cum_san, 
           COALESCE(NULLIF(s.dia_chi, ''), c.dia_chi) AS dia_chi_hien_thi,
           COALESCE(NULLIF(s.link_google_map, ''), c.link_google_map) AS map_hien_thi,
           l.ten_loai 
    FROM san_bong s
    JOIN cum_san c ON s.cum_san_id = c.id
    JOIN loai_san l ON s.loai_san_id = l.id
    ORDER BY s.id ASC
")->fetchAll();

$cumSanList = $pdo->query("SELECT id, ten_cum_san, dia_chi, link_google_map FROM cum_san ORDER BY id ASC")->fetchAll();
$loaiSanList = $pdo->query("SELECT id, ten_loai FROM loai_san ORDER BY id ASC")->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold text-navy mb-1">Quản Lý Sân Bóng</h3>
        <p class="text-muted mb-0">Thông tin sân bóng được liên kết và cập nhật trực tiếp với cơ sở dữ liệu MySQL</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary-pitch" data-bs-toggle="modal" data-bs-target="#addPitchModal">
            <i class="bi bi-plus-circle me-1"></i> Thêm Sân Bóng Mới
        </button>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header d-flex justify-content-between align-items-center">
        <span>Danh sách sân bóng (Tổng: <?php echo count($pitches); ?> sân)</span>
        <span class="badge bg-success-subtle text-success"><i class="bi bi-database-check me-1"></i> MySQL Live</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th style="width: 90px;">Ảnh sân</th>
                    <th>Tên sân bóng</th>
                    <th>Cụm sân & Địa chỉ</th>
                    <th>Loại sân</th>
                    <th>Giá / giờ</th>
                    <th>Trạng thái</th>
                    <th>Bản đồ</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pitches as $p): 
                    $avatarImg = getPitchMainImage($p['id'], $p['cum_san_id'], $pdo);
                ?>
                    <tr>
                        <td><strong>#<?php echo (int)$p['id']; ?></strong></td>
                        <td>
                            <img src="<?php echo e($avatarImg); ?>" class="rounded-3 border shadow-sm" style="width: 75px; height: 50px; object-fit: cover;" alt="Ảnh sân #<?php echo $p['id']; ?>">
                        </td>
                        <td class="fw-bold text-navy">
                            <?php echo e($p['ten_san']); ?>
                            <?php if (!empty($p['ghi_chu'])): ?>
                                <small class="text-muted d-block fw-normal text-truncate" style="max-width: 200px;">
                                    <?php echo e($p['ghi_chu']); ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong class="d-block text-secondary"><?php echo e($p['ten_cum_san']); ?></strong>
                            <small class="text-muted text-truncate d-block" style="max-width: 250px;">
                                <i class="bi bi-geo-alt me-1 text-danger"></i><?php echo e($p['dia_chi_hien_thi']); ?>
                            </small>
                        </td>
                        <td><span class="badge bg-secondary-subtle text-secondary"><?php echo e($p['ten_loai']); ?></span></td>
                        <td><span class="fw-bold text-success"><?php echo formatCurrency($p['gia_theo_gio']); ?></span></td>
                        <td><?php echo renderPitchStatusBadge($p['trang_thai']); ?></td>
                        <td>
                            <?php if (!empty($p['map_hien_thi'])): ?>
                                <a href="<?php echo e($p['map_hien_thi']); ?>" target="_blank" class="text-danger small fw-semibold text-decoration-none">
                                    <i class="bi bi-geo-alt-fill"></i> Xem map
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">Tự động</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editPitchModal<?php echo $p['id']; ?>">
                                <i class="bi bi-pencil"></i> Sửa
                            </button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa sân này khỏi Database?');">
                                <?php echo getCsrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>

                    <!-- MODAL SỬA SÂN -->
                    <div class="modal fade" id="editPitchModal<?php echo $p['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <form method="POST" enctype="multipart/form-data">
                                    <?php echo getCsrfField(); ?>
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Chỉnh Sửa Sân Bóng #<?php echo $p['id']; ?> - <?php echo e($p['ten_san']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <!-- Tên sân -->
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Tên sân bóng <span class="text-danger">*</span></label>
                                            <input type="text" name="ten_san" class="form-control" value="<?php echo e($p['ten_san']); ?>" required>
                                        </div>

                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label small fw-semibold">Cụm sân trực thuộc <span class="text-danger">*</span></label>
                                                <select name="cum_san_id" class="form-select" required>
                                                    <?php foreach ($cumSanList as $c): ?>
                                                        <option value="<?php echo $c['id']; ?>" <?php echo $p['cum_san_id'] == $c['id'] ? 'selected' : ''; ?>>
                                                            <?php echo e($c['ten_cum_san']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-semibold">Loại sân <span class="text-danger">*</span></label>
                                                <select name="loai_san_id" class="form-select" required>
                                                    <?php foreach ($loaiSanList as $l): ?>
                                                        <option value="<?php echo $l['id']; ?>" <?php echo $p['loai_san_id'] == $l['id'] ? 'selected' : ''; ?>>
                                                            <?php echo e($l['ten_loai']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label small fw-semibold">Giá thuê theo giờ (VNĐ/h) <span class="text-danger">*</span></label>
                                                <input type="number" name="gia_theo_gio" class="form-control" value="<?php echo (int)$p['gia_theo_gio']; ?>" step="10000" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-semibold">Trạng thái hoạt động <span class="text-danger">*</span></label>
                                                <select name="trang_thai" class="form-select" required>
                                                    <option value="Hoạt động" <?php echo $p['trang_thai'] === 'Hoạt động' ? 'selected' : ''; ?>>Hoạt động</option>
                                                    <option value="Bảo trì" <?php echo $p['trang_thai'] === 'Bảo trì' ? 'selected' : ''; ?>>Bảo trì</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Địa chỉ & Google Maps riêng cho sân nếu có -->
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label small fw-semibold">Địa chỉ cụ thể của sân</label>
                                                <input type="text" name="dia_chi" class="form-control" value="<?php echo e($p['dia_chi'] ?? $p['dia_chi_hien_thi']); ?>" placeholder="Địa chỉ chi tiết của sân bóng">
                                                <small class="text-muted">Mặc định kế thừa từ địa chỉ của cụm sân</small>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-semibold">Liên kết Google Maps</label>
                                                <input type="url" name="link_google_map" class="form-control" value="<?php echo e($p['link_google_map'] ?? $p['map_hien_thi']); ?>" placeholder="https://maps.google.com/...">
                                                <small class="text-muted">Đường dẫn Google Maps định vị sân</small>
                                            </div>
                                        </div>

                                        <!-- Ảnh đại diện sân -->
                                        <div class="card bg-light border-0 p-3 mb-3">
                                            <label class="form-label small fw-bold text-navy mb-2"><i class="bi bi-image me-1"></i> Ảnh Đại Diện Sân Bóng</label>
                                            <div class="d-flex align-items-center gap-3 mb-2">
                                                <img src="<?php echo e($avatarImg); ?>" class="rounded border" style="width: 90px; height: 60px; object-fit: cover;" alt="Current avatar">
                                                <div class="flex-grow-1">
                                                    <input type="file" name="avatar" class="form-control form-control-sm mb-1" accept="image/jpeg,image/png,image/webp">
                                                    <input type="url" name="avatar_url" class="form-control form-control-sm" placeholder="Hoặc dán link URL ảnh (Unsplash/CDN)..." value="<?php echo e(preg_match('~^https?://~i', $p['hinh_anh_dai_dien'] ?? '') ? $p['hinh_anh_dai_dien'] : ''); ?>">
                                                </div>
                                            </div>
                                            <small class="text-muted">Chọn file ảnh từ máy (JPG, PNG, WEBP tối đa 5MB) hoặc nhập URL. File mới sẽ được lưu vào thư mục <code>uploads/san_bong/</code> và đồng bộ vào Database.</small>
                                        </div>

                                        <!-- Mô tả & Ghi chú -->
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Mô tả / Ghi chú / Tiện ích sân bóng</label>
                                            <textarea name="ghi_chu" class="form-control" rows="3" placeholder="Ví dụ: Cỏ nhân tạo tiêu chuẩn FIFA, dàn đèn LED ban đêm, bãi đỗ xe..."><?php echo e($p['ghi_chu'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
                                        <button type="submit" class="btn btn-primary-pitch btn-sm"><i class="bi bi-check-lg me-1"></i> Lưu Vào Cơ Sở Dữ Liệu</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL THÊM SÂN MỚI -->
<div class="modal fade" id="addPitchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <?php echo getCsrfField(); ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Thêm Sân Bóng Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tên sân bóng <span class="text-danger">*</span></label>
                        <input type="text" name="ten_san" class="form-control" placeholder="Ví dụ: Sân 1 (sân 7) Sân Bóng KT3" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Cụm sân trực thuộc <span class="text-danger">*</span></label>
                            <select name="cum_san_id" class="form-select" required>
                                <option value="">-- Chọn cụm sân --</option>
                                <?php foreach ($cumSanList as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo e($c['ten_cum_san']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Loại sân <span class="text-danger">*</span></label>
                            <select name="loai_san_id" class="form-select" required>
                                <option value="">-- Chọn loại sân --</option>
                                <?php foreach ($loaiSanList as $l): ?>
                                    <option value="<?php echo $l['id']; ?>"><?php echo e($l['ten_loai']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Giá thuê/giờ (VNĐ) <span class="text-danger">*</span></label>
                            <input type="number" name="gia_theo_gio" class="form-control" placeholder="Ví dụ: 350000" step="10000" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Trạng thái <span class="text-danger">*</span></label>
                            <select name="trang_thai" class="form-select" required>
                                <option value="Hoạt động">Hoạt động</option>
                                <option value="Bảo trì">Bảo trì</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Địa chỉ cụ thể của sân</label>
                            <input type="text" name="dia_chi" class="form-control" placeholder="Để trống nếu dùng chung địa chỉ cụm sân">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Liên kết Google Maps</label>
                            <input type="url" name="link_google_map" class="form-control" placeholder="https://maps.google.com/...">
                        </div>
                    </div>
                    <div class="card bg-light border-0 p-3 mb-3">
                        <label class="form-label small fw-bold text-navy mb-2"><i class="bi bi-image me-1"></i> Ảnh Đại Diện Ban Đầu</label>
                        <input type="file" name="avatar" class="form-control form-control-sm mb-2" accept="image/jpeg,image/png,image/webp">
                        <input type="url" name="avatar_url" class="form-control form-control-sm" placeholder="Hoặc dán URL ảnh trực tuyến (https://...)">
                        <small class="text-muted mt-1">Ảnh sẽ được upload hoặc liên kết trực tiếp vào cơ sở dữ liệu MySQL.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Ghi chú / Tiện ích sân bóng</label>
                        <textarea name="ghi_chu" class="form-control" rows="2" placeholder="Cỏ nhân tạo tiêu chuẩn FIFA, dàn đèn LED, căng tin..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary-pitch btn-sm"><i class="bi bi-plus-lg me-1"></i> Lưu Vào Cơ Sở Dữ Liệu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
