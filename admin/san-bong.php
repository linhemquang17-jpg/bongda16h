<?php
/**
 * Quản Lý Sân Bóng - Admin
 * Dự án: BongDa16h
 */
$pageTitle = 'Quản Lý Sân Bóng';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/auth_check.php';

$pdo = getDBConnection();

// Xử lý các action (Thêm, Sửa, Xóa)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Yêu cầu không hợp lệ!');
        header('Location: ' . BASE_URL . '/admin/san-bong.php');
        exit;
    }

    $action = sanitize($_POST['action'] ?? '');

    if ($action === 'add') {
        $tenSan    = sanitize($_POST['ten_san'] ?? '');
        $cumSanId  = (int)($_POST['cum_san_id'] ?? 0);
        $loaiSanId = (int)($_POST['loai_san_id'] ?? 0);
        $giaTheoGio= (float)($_POST['gia_theo_gio'] ?? 0);
        $trangThai = sanitize($_POST['trang_thai'] ?? 'Hoạt động');
        $ghiChu    = sanitize($_POST['ghi_chu'] ?? '');

        if (empty($tenSan) || !$cumSanId || !$loaiSanId || $giaTheoGio <= 0) {
            setFlash('danger', 'Vui lòng nhập đầy đủ các thông tin bắt buộc và giá theo giờ hợp lệ!');
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO san_bong (cum_san_id, loai_san_id, ten_san, gia_theo_gio, trang_thai, ghi_chu)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$cumSanId, $loaiSanId, $tenSan, $giaTheoGio, $trangThai, $ghiChu]);
                setFlash('success', 'Thêm sân bóng mới thành công!');
            } catch (Exception $e) {
                setFlash('danger', 'Lỗi thêm sân: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'edit') {
        $id        = (int)($_POST['id'] ?? 0);
        $tenSan    = sanitize($_POST['ten_san'] ?? '');
        $cumSanId  = (int)($_POST['cum_san_id'] ?? 0);
        $loaiSanId = (int)($_POST['loai_san_id'] ?? 0);
        $giaTheoGio= (float)($_POST['gia_theo_gio'] ?? 0);
        $trangThai = sanitize($_POST['trang_thai'] ?? 'Hoạt động');
        $ghiChu    = sanitize($_POST['ghi_chu'] ?? '');

        if (!$id || empty($tenSan) || !$cumSanId || !$loaiSanId || $giaTheoGio <= 0) {
            setFlash('danger', 'Thông tin cập nhật không hợp lệ!');
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE san_bong 
                    SET cum_san_id = ?, loai_san_id = ?, ten_san = ?, gia_theo_gio = ?, trang_thai = ?, ghi_chu = ?
                    WHERE id = ?
                ");
                $stmt->execute([$cumSanId, $loaiSanId, $tenSan, $giaTheoGio, $trangThai, $ghiChu, $id]);
                setFlash('success', "Cập nhật sân #{$id} thành công!");
            } catch (Exception $e) {
                setFlash('danger', 'Lỗi cập nhật: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'change_avatar') {
        $id = (int)($_POST['id'] ?? 0);
        $pitchStmt = $pdo->prepare("SELECT id, ten_san FROM san_bong WHERE id = ?");
        $pitchStmt->execute([$id]);
        $pitch = $pitchStmt->fetch();

        if (!$pitch) {
            setFlash('danger', 'Sân bóng không tồn tại!');
            header('Location: ' . BASE_URL . '/admin/san-bong.php');
            exit;
        }

        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $errCode = $_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE;
            $errMessages = [
                UPLOAD_ERR_INI_SIZE   => 'Dung lượng file vượt quá giới hạn cấu hình của PHP (upload_max_filesize)!',
                UPLOAD_ERR_FORM_SIZE  => 'Dung lượng file vượt quá giới hạn của form!',
                UPLOAD_ERR_PARTIAL    => 'File mới chỉ được tải lên một phần!',
                UPLOAD_ERR_NO_FILE    => 'Vui lòng chọn một file ảnh từ máy tính!',
                UPLOAD_ERR_NO_TMP_DIR => 'Lỗi máy chủ: Thiếu thư mục tạm!',
                UPLOAD_ERR_CANT_WRITE => 'Lỗi máy chủ: Không thể ghi file vào ổ đĩa!',
                UPLOAD_ERR_EXTENSION  => 'Quá trình tải file bị chặn bởi extension máy chủ!'
            ];
            setFlash('danger', $errMessages[$errCode] ?? 'Vui lòng chọn file ảnh hợp lệ từ máy tính!');
            header('Location: ' . BASE_URL . '/admin/san-bong.php');
            exit;
        }

        // 1. Kiểm tra dung lượng file (tối đa 5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($_FILES['avatar']['size'] > $maxSize) {
            setFlash('danger', 'Dung lượng ảnh vượt quá giới hạn cho phép (Tối đa 5MB)!');
            header('Location: ' . BASE_URL . '/admin/san-bong.php');
            exit;
        }

        // 2. Kiểm tra phần mở rộng file (extension)
        $origName = $_FILES['avatar']['name'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowedExts)) {
            setFlash('danger', 'Định dạng file không hợp lệ! Chỉ cho phép tải lên file ảnh: JPG, JPEG, PNG, WEBP.');
            header('Location: ' . BASE_URL . '/admin/san-bong.php');
            exit;
        }

        // 3. Kiểm tra MIME type thực tế bằng Fileinfo
        $tmpPath = $_FILES['avatar']['tmp_name'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mimeType, $allowedMimes)) {
            setFlash('danger', 'File tải lên không phải là định dạng hình ảnh hợp lệ (MIME type: ' . e($mimeType) . ')!');
            header('Location: ' . BASE_URL . '/admin/san-bong.php');
            exit;
        }

        // 4. Kiểm tra tính toàn vẹn của ảnh bằng getimagesize
        $imageSizeInfo = @getimagesize($tmpPath);
        if ($imageSizeInfo === false) {
            setFlash('danger', 'File ảnh không hợp lệ hoặc bị lỗi nội dung!');
            header('Location: ' . BASE_URL . '/admin/san-bong.php');
            exit;
        }

        // 5. Chuẩn bị thư mục lưu trữ thực tế
        $uploadDir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'san_bong' . DIRECTORY_SEPARATOR;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // 6. Đặt tên file an toàn, duy nhất
        $safeFileName = 'pitch_' . $id . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $destFilePath = $uploadDir . $safeFileName;

        // 7. Di chuyển file vào thư mục uploads/san_bong
        if (!move_uploaded_file($tmpPath, $destFilePath)) {
            setFlash('danger', 'Không thể lưu file ảnh vào thư mục máy chủ!');
            header('Location: ' . BASE_URL . '/admin/san-bong.php');
            exit;
        }

        // 8. Kiểm tra file đã được lưu thành công trên máy chủ
        if (!file_exists($destFilePath) || filesize($destFilePath) === 0) {
            setFlash('danger', 'Lỗi lưu trữ: File sau khi lưu không tồn tại hoặc có dung lượng 0 byte!');
            header('Location: ' . BASE_URL . '/admin/san-bong.php');
            exit;
        }

        // Đường dẫn tương đối lưu vào Database
        $relativeDbPath = 'uploads/san_bong/' . $safeFileName;

        // 9. Cập nhật cơ sở dữ liệu với Transaction an toàn
        try {
            $pdo->beginTransaction();

            // Đặt tất cả ảnh hiện có của sân này thành la_anh_chinh = 0
            $pdo->prepare("UPDATE hinh_anh_san SET la_anh_chinh = 0 WHERE san_bong_id = ?")->execute([$id]);

            // Thêm bản ghi ảnh mới với la_anh_chinh = 1 cho đúng sân này
            $insertStmt = $pdo->prepare("
                INSERT INTO hinh_anh_san (san_bong_id, cum_san_id, url_hinh_anh, la_anh_chinh)
                VALUES (?, NULL, ?, 1)
            ");
            $insertStmt->execute([$id, $relativeDbPath]);

            $pdo->commit();
            setFlash('success', 'Đã thay đổi và lưu cố định ảnh đại diện mới cho sân "' . $pitch['ten_san'] . '" thành công!');
        } catch (Exception $e) {
            $pdo->rollBack();
            // Nếu lưu database thất bại, xóa file vừa upload để không để lại file rác
            if (file_exists($destFilePath)) {
                @unlink($destFilePath);
            }
            setFlash('danger', 'Lỗi khi cập nhật cơ sở dữ liệu: ' . $e->getMessage() . '. Ảnh cũ vẫn được giữ nguyên.');
        }

        header('Location: ' . BASE_URL . '/admin/san-bong.php');
        exit;
    }

    header('Location: ' . BASE_URL . '/admin/san-bong.php');
    exit;
}

// Lấy danh sách sân bóng kèm cụm sân và loại sân
$pitches = $pdo->query("
    SELECT s.*, c.ten_cum_san, l.ten_loai 
    FROM san_bong s
    JOIN cum_san c ON s.cum_san_id = c.id
    JOIN loai_san l ON s.loai_san_id = l.id
    ORDER BY s.id ASC
")->fetchAll();

$cumSanList = $pdo->query("SELECT id, ten_cum_san FROM cum_san ORDER BY id ASC")->fetchAll();
$loaiSanList = $pdo->query("SELECT id, ten_loai FROM loai_san ORDER BY id ASC")->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold text-navy mb-1">Quản Lý Sân Bóng</h3>
        <p class="text-muted mb-0">Danh sách các sân bóng 5 người, 7 người trong toàn bộ cụm sân</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary-pitch" data-bs-toggle="modal" data-bs-target="#addPitchModal">
            <i class="bi bi-plus-circle me-1"></i> Thêm Sân Bóng Mới
        </button>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <span>Danh sách sân bóng (Tổng: <?php echo count($pitches); ?> sân)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên sân</th>
                    <th>Cụm sân</th>
                    <th>Loại sân</th>
                    <th>Giá / giờ</th>
                    <th>Trạng thái</th>
                    <th>Ghi chú / Tiện ích</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pitches as $p): ?>
                    <tr>
                        <td><strong>#<?php echo (int)$p['id']; ?></strong></td>
                        <td class="fw-bold text-navy"><?php echo e($p['ten_san']); ?></td>
                        <td><?php echo e($p['ten_cum_san']); ?></td>
                        <td><span class="badge bg-secondary-subtle text-secondary"><?php echo e($p['ten_loai']); ?></span></td>
                        <td><span class="fw-bold text-success"><?php echo formatCurrency($p['gia_theo_gio']); ?></span></td>
                        <td><?php echo renderPitchStatusBadge($p['trang_thai']); ?></td>
                        <td><small class="text-muted"><?php echo e($p['ghi_chu'] ?? '—'); ?></small></td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editPitchModal<?php echo $p['id']; ?>">
                                <i class="bi bi-pencil"></i> Sửa
                            </button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa sân này?');">
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
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <?php echo getCsrfField(); ?>
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Chỉnh Sửa Sân #<?php echo $p['id']; ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Tên sân bóng <span class="text-danger">*</span></label>
                                            <input type="text" name="ten_san" class="form-control" value="<?php echo e($p['ten_san']); ?>" required>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Cụm sân <span class="text-danger">*</span></label>
                                                <select name="cum_san_id" class="form-select" required>
                                                    <?php foreach ($cumSanList as $c): ?>
                                                        <option value="<?php echo $c['id']; ?>" <?php echo $p['cum_san_id'] == $c['id'] ? 'selected' : ''; ?>>
                                                            <?php echo e($c['ten_cum_san']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-6">
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
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Giá theo giờ (VNĐ) <span class="text-danger">*</span></label>
                                                <input type="number" name="gia_theo_gio" class="form-control" value="<?php echo (int)$p['gia_theo_gio']; ?>" step="10000" required>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Trạng thái <span class="text-danger">*</span></label>
                                                <select name="trang_thai" class="form-select" required>
                                                    <option value="Hoạt động" <?php echo $p['trang_thai'] === 'Hoạt động' ? 'selected' : ''; ?>>Hoạt động</option>
                                                    <option value="Bảo trì" <?php echo $p['trang_thai'] === 'Bảo trì' ? 'selected' : ''; ?>>Bảo trì</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Ghi chú / Tiện ích</label>
                                            <textarea name="ghi_chu" class="form-control" rows="2"><?php echo e($p['ghi_chu'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
                                        <button type="submit" class="btn btn-primary-pitch btn-sm">Lưu Thay Đổi</button>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo getCsrfField(); ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Thêm Sân Bóng Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tên sân bóng <span class="text-danger">*</span></label>
                        <input type="text" name="ten_san" class="form-control" placeholder="Ví dụ: Sân 5 - Sân A3" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Cụm sân <span class="text-danger">*</span></label>
                            <select name="cum_san_id" class="form-select" required>
                                <option value="">-- Chọn cụm sân --</option>
                                <?php foreach ($cumSanList as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo e($c['ten_cum_san']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Loại sân <span class="text-danger">*</span></label>
                            <select name="loai_san_id" class="form-select" required>
                                <option value="">-- Chọn loại sân --</option>
                                <?php foreach ($loaiSanList as $l): ?>
                                    <option value="<?php echo $l['id']; ?>"><?php echo e($l['ten_loai']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Giá thuê/giờ (VNĐ) <span class="text-danger">*</span></label>
                            <input type="number" name="gia_theo_gio" class="form-control" placeholder="Ví dụ: 250000" step="10000" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Trạng thái <span class="text-danger">*</span></label>
                            <select name="trang_thai" class="form-select" required>
                                <option value="Hoạt động">Hoạt động</option>
                                <option value="Bảo trì">Bảo trì</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Ghi chú / Tiện ích</label>
                        <textarea name="ghi_chu" class="form-control" rows="2" placeholder="Cỏ nhân tạo tiêu chuẩn, dàn đèn LED..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary-pitch btn-sm">Thêm Mới Sân</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

