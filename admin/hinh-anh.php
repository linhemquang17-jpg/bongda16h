<?php
/**
 * Quản Lý Hình Ảnh Sân Bóng & Cụm Sân - Admin
 * Dự án: BongDa16h
 */
$pageTitle = 'Quản Lý Hình Ảnh Sân';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/auth_check.php';

$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Yêu cầu không hợp lệ!');
        header('Location: ' . BASE_URL . '/admin/hinh-anh.php');
        exit;
    }

    $action = sanitize($_POST['action'] ?? '');

    if ($action === 'add') {
        $urlHinhAnh = sanitize($_POST['url_hinh_anh'] ?? '');
        $targetType = sanitize($_POST['target_type'] ?? 'san_bong');
        $targetId   = (int)($_POST['target_id'] ?? 0);
        $laAnhChinh = isset($_POST['la_anh_chinh']) ? 1 : 0;

        if (empty($urlHinhAnh) || !$targetId) {
            setFlash('danger', 'Vui lòng nhập đường dẫn URL ảnh và chọn đối tượng liên kết!');
        } else {
            $sanBongId = ($targetType === 'san_bong') ? $targetId : null;
            $cumSanId  = ($targetType === 'cum_san') ? $targetId : null;

            try {
                // Nếu đặt làm ảnh chính, reset các ảnh khác cùng đối tượng về 0
                if ($laAnhChinh === 1) {
                    if ($sanBongId) {
                        $pdo->prepare("UPDATE hinh_anh_san SET la_anh_chinh = 0 WHERE san_bong_id = ?")->execute([$sanBongId]);
                        $pdo->prepare("UPDATE san_bong SET hinh_anh_dai_dien = ? WHERE id = ?")->execute([$urlHinhAnh, $sanBongId]);
                    } elseif ($cumSanId) {
                        $pdo->prepare("UPDATE hinh_anh_san SET la_anh_chinh = 0 WHERE cum_san_id = ?")->execute([$cumSanId]);
                        $pdo->prepare("UPDATE cum_san SET hinh_anh_dai_dien = ? WHERE id = ?")->execute([$urlHinhAnh, $cumSanId]);
                    }
                }

                $stmt = $pdo->prepare("
                    INSERT INTO hinh_anh_san (san_bong_id, cum_san_id, url_hinh_anh, la_anh_chinh)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$sanBongId, $cumSanId, $urlHinhAnh, $laAnhChinh]);
                setFlash('success', 'Thêm hình ảnh thành công!');
            } catch (Exception $e) {
                setFlash('danger', 'Lỗi thêm ảnh: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'set_main') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $img = $pdo->prepare("SELECT * FROM hinh_anh_san WHERE id = ?");
                $img->execute([$id]);
                $cur = $img->fetch();

                if ($cur) {
                    if ($cur['san_bong_id']) {
                        $pdo->prepare("UPDATE hinh_anh_san SET la_anh_chinh = 0 WHERE san_bong_id = ?")->execute([$cur['san_bong_id']]);
                        $pdo->prepare("UPDATE san_bong SET hinh_anh_dai_dien = ? WHERE id = ?")->execute([$cur['url_hinh_anh'], $cur['san_bong_id']]);
                    } elseif ($cur['cum_san_id']) {
                        $pdo->prepare("UPDATE hinh_anh_san SET la_anh_chinh = 0 WHERE cum_san_id = ?")->execute([$cur['cum_san_id']]);
                        $pdo->prepare("UPDATE cum_san SET hinh_anh_dai_dien = ? WHERE id = ?")->execute([$cur['url_hinh_anh'], $cur['cum_san_id']]);
                    }
                    $pdo->prepare("UPDATE hinh_anh_san SET la_anh_chinh = 1 WHERE id = ?")->execute([$id]);
                    setFlash('success', "Đã đặt ảnh #{$id} làm ảnh chính!");
                }
            } catch (Exception $e) {
                setFlash('danger', 'Lỗi: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare("DELETE FROM hinh_anh_san WHERE id = ?")->execute([$id]);
                setFlash('success', 'Xóa hình ảnh thành công!');
            } catch (Exception $e) {
                setFlash('danger', 'Lỗi xóa ảnh: ' . $e->getMessage());
            }
        }
    }

    header('Location: ' . BASE_URL . '/admin/hinh-anh.php');
    exit;
}

// Lấy danh sách ảnh
$images = $pdo->query("
    SELECT h.*, s.ten_san, c.ten_cum_san 
    FROM hinh_anh_san h
    LEFT JOIN san_bong s ON h.san_bong_id = s.id
    LEFT JOIN cum_san c ON h.cum_san_id = c.id
    ORDER BY h.id DESC
")->fetchAll();

$pitches = $pdo->query("SELECT id, ten_san FROM san_bong ORDER BY id ASC")->fetchAll();
$complexes = $pdo->query("SELECT id, ten_cum_san FROM cum_san ORDER BY id ASC")->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold text-navy mb-1">Quản Lý Hình Ảnh Sân</h3>
        <p class="text-muted mb-0">Quản lý kho ảnh đại diện và bộ sưu tập ảnh của sân bóng, cụm sân</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary-pitch" data-bs-toggle="modal" data-bs-target="#addImageModal">
            <i class="bi bi-plus-circle me-1"></i> Thêm Hình Ảnh Mới
        </button>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <span>Danh sách hình ảnh (Tổng: <?php echo count($images); ?> ảnh)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th style="width: 100px;">Ảnh</th>
                    <th>Đối tượng liên kết</th>
                    <th>URL Hình ảnh</th>
                    <th>Ảnh chính</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($images as $img): ?>
                    <tr>
                        <td>
                            <img src="<?php echo e($img['url_hinh_anh']); ?>" class="rounded-3 border" style="width: 80px; height: 55px; object-fit: cover;" alt="Thumbnail">
                        </td>
                        <td>
                            <?php if ($img['san_bong_id']): ?>
                                <span class="badge bg-success-subtle text-success me-1">Sân bóng</span>
                                <strong><?php echo e($img['ten_san']); ?></strong>
                            <?php elseif ($img['cum_san_id']): ?>
                                <span class="badge bg-primary-subtle text-primary me-1">Cụm sân</span>
                                <strong><?php echo e($img['ten_cum_san']); ?></strong>
                            <?php else: ?>
                                <span class="text-muted">Chưa gán</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo e($img['url_hinh_anh']); ?>" target="_blank" class="text-truncate d-inline-block small text-muted" style="max-width: 320px;">
                                <?php echo e($img['url_hinh_anh']); ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($img['la_anh_chinh']): ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-star-fill me-1"></i> Ảnh chính</span>
                            <?php else: ?>
                                <form method="POST" class="d-inline">
                                    <?php echo getCsrfField(); ?>
                                    <input type="hidden" name="action" value="set_main">
                                    <input type="hidden" name="id" value="<?php echo (int)$img['id']; ?>">
                                    <button type="submit" class="btn btn-outline-secondary btn-sm" title="Đặt làm ảnh chính">
                                        <i class="bi bi-star"></i> Chọn làm chính
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa ảnh này?');">
                                <?php echo getCsrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$img['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i> Xóa
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal thêm ảnh -->
<div class="modal fade" id="addImageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo getCsrfField(); ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Thêm Hình Ảnh Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Đường dẫn URL ảnh (Unsplash hoặc CDN) <span class="text-danger">*</span></label>
                        <input type="url" name="url_hinh_anh" class="form-control" placeholder="https://images.unsplash.com/..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Gán ảnh cho đối tượng <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="target_type" id="type_pitch" value="san_bong" checked onchange="toggleTargetSelect()">
                                <label class="form-check-label small" for="type_pitch">Sân bóng cụ thể</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="target_type" id="type_complex" value="cum_san" onchange="toggleTargetSelect()">
                                <label class="form-check-label small" for="type_complex">Cụm sân thể thao</label>
                            </div>
                        </div>

                        <!-- Select sân -->
                        <div id="selectPitchContainer">
                            <select name="target_id" id="pitchSelectEl" class="form-select">
                                <option value="">-- Chọn sân bóng --</option>
                                <?php foreach ($pitches as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo e($p['ten_san']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Select cụm sân -->
                        <div id="selectComplexContainer" class="d-none">
                            <select id="complexSelectEl" class="form-select" disabled>
                                <option value="">-- Chọn cụm sân --</option>
                                <?php foreach ($complexes as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo e($c['ten_cum_san']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="la_anh_chinh" value="1" id="la_anh_chinh">
                        <label class="form-check-label small fw-semibold" for="la_anh_chinh">
                            Đặt làm ảnh đại diện chính (Primary Image)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary-pitch btn-sm">Lưu Hình Ảnh</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleTargetSelect() {
        const isPitch = document.getElementById('type_pitch').checked;
        const pCont = document.getElementById('selectPitchContainer');
        const cCont = document.getElementById('selectComplexContainer');
        const pEl = document.getElementById('pitchSelectEl');
        const cEl = document.getElementById('complexSelectEl');

        if (isPitch) {
            pCont.classList.remove('d-none');
            cCont.classList.add('d-none');
            pEl.name = 'target_id';
            pEl.disabled = false;
            cEl.name = '';
            cEl.disabled = true;
        } else {
            pCont.classList.add('d-none');
            cCont.classList.remove('d-none');
            cEl.name = 'target_id';
            cEl.disabled = false;
            pEl.name = '';
            pEl.disabled = true;
        }
    }
</script>

<?php require_once __DIR__ . '/footer.php'; ?>

