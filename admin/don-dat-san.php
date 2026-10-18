<?php
/**
 * Quản lý Đơn Đặt Sân - Admin
 * Dự án: BongDa16h
 */
$pageTitle = 'Quản Lý Đơn Đặt Sân';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/auth_check.php';

$pdo = getDBConnection();

// Xử lý cập nhật nhanh trạng thái qua POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_update') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn!');
        header('Location: ' . BASE_URL . '/admin/don-dat-san.php');
        exit;
    }

    $bId           = (int)($_POST['booking_id'] ?? 0);
    $newStatus     = sanitize($_POST['trang_thai_dat'] ?? '');
    $newPayStatus  = sanitize($_POST['trang_thai_thanh_toan'] ?? '');

    $validStatuses = ['Chờ xác nhận', 'Đã xác nhận', 'Đã hoàn thành', 'Đã hủy'];
    $validPayStatuses = ['Chưa thanh toán', 'Đã đặt cọc', 'Đã thanh toán đủ'];

    try {
        if ($bId > 0 && in_array($newStatus, $validStatuses)) {
            $stmt = $pdo->prepare("UPDATE dat_san SET trang_thai_dat = ? WHERE id = ?");
            $stmt->execute([$newStatus, $bId]);
        }

        if ($bId > 0 && in_array($newPayStatus, $validPayStatuses)) {
            $stmtPay = $pdo->prepare("UPDATE thanh_toan SET trang_thai_thanh_toan = ? WHERE dat_san_id = ?");
            $stmtPay->execute([$newPayStatus, $bId]);
        }

        setFlash('success', "Cập nhật đơn #BD16H{$bId} thành công!");
    } catch (Exception $e) {
        setFlash('danger', 'Lỗi cập nhật: ' . $e->getMessage());
    }

    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Lọc dữ liệu
$kw          = sanitize($_GET['kw'] ?? '');
$status      = sanitize($_GET['status'] ?? '');
$payStatus   = sanitize($_GET['pay_status'] ?? '');
$cumSanId    = (int)($_GET['cum_san_id'] ?? 0);
$ngayDat     = sanitize($_GET['ngay_dat'] ?? '');
$page        = max(1, (int)($_GET['page'] ?? 1));
$limit       = 10;
$offset      = ($page - 1) * $limit;

$conditions  = [];
$params      = [];

if (!empty($kw)) {
    $conditions[] = "(u.ho_ten LIKE :kw OR u.so_dien_thoai LIKE :kw OR d.id = :kw_id)";
    $params[':kw'] = '%' . $kw . '%';
    $params[':kw_id'] = (int)$kw;
}

if (!empty($status)) {
    $conditions[] = "d.trang_thai_dat = :status";
    $params[':status'] = $status;
}

if (!empty($payStatus)) {
    $conditions[] = "t.trang_thai_thanh_toan = :pay_status";
    $params[':pay_status'] = $payStatus;
}

if ($cumSanId > 0) {
    $conditions[] = "s.cum_san_id = :cum_id";
    $params[':cum_id'] = $cumSanId;
}

if (!empty($ngayDat)) {
    $conditions[] = "d.ngay_dat = :ngay_dat";
    $params[':ngay_dat'] = $ngayDat;
}

$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Đếm tổng số bản ghi
$countSql = "
    SELECT COUNT(*) 
    FROM dat_san d
    JOIN nguoi_dung u ON d.nguoi_dung_id = u.id
    JOIN san_bong s ON d.san_bong_id = s.id
    LEFT JOIN thanh_toan t ON d.id = t.dat_san_id
    {$whereClause}
";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages   = ceil($totalRecords / $limit);

// Lấy danh sách đơn
$sql = "
    SELECT d.*, 
           u.ho_ten as ten_khach, u.so_dien_thoai as sdt_khach,
           s.ten_san, c.ten_cum_san,
           t.phuong_thuc_thanh_toan, t.trang_thai_thanh_toan
    FROM dat_san d
    JOIN nguoi_dung u ON d.nguoi_dung_id = u.id
    JOIN san_bong s ON d.san_bong_id = s.id
    JOIN cum_san c ON s.cum_san_id = c.id
    LEFT JOIN thanh_toan t ON d.id = t.dat_san_id
    {$whereClause}
    ORDER BY d.id DESC
    LIMIT {$limit} OFFSET {$offset}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$cumSanList = $pdo->query("SELECT id, ten_cum_san FROM cum_san ORDER BY id ASC")->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold text-navy mb-1">Quản Lý Đơn Đặt Sân</h3>
        <p class="text-muted mb-0">Theo dõi, kiểm duyệt và cập nhật tình trạng đơn đặt sân trên toàn hệ thống</p>
    </div>
</div>

<!-- BỘ LỌC TÌM KIẾM -->
<div class="admin-card mb-4">
    <div class="admin-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Từ khóa / Mã đơn / SĐT</label>
                <input type="text" name="kw" value="<?php echo e($kw); ?>" class="form-control form-control-sm" placeholder="Tên khách, SĐT, mã đơn...">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Trạng thái đơn</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="Chờ xác nhận" <?php echo $status === 'Chờ xác nhận' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                    <option value="Đã xác nhận" <?php echo $status === 'Đã xác nhận' ? 'selected' : ''; ?>>Đã xác nhận</option>
                    <option value="Đã hoàn thành" <?php echo $status === 'Đã hoàn thành' ? 'selected' : ''; ?>>Đã hoàn thành</option>
                    <option value="Đã hủy" <?php echo $status === 'Đã hủy' ? 'selected' : ''; ?>>Đã hủy</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Thanh toán</label>
                <select name="pay_status" class="form-select form-select-sm">
                    <option value="">-- Tất cả thanh toán --</option>
                    <option value="Chưa thanh toán" <?php echo $payStatus === 'Chưa thanh toán' ? 'selected' : ''; ?>>Chưa thanh toán</option>
                    <option value="Đã đặt cọc" <?php echo $payStatus === 'Đã đặt cọc' ? 'selected' : ''; ?>>Đã đặt cọc</option>
                    <option value="Đã thanh toán đủ" <?php echo $payStatus === 'Đã thanh toán đủ' ? 'selected' : ''; ?>>Đã thanh toán đủ</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Cụm sân</label>
                <select name="cum_san_id" class="form-select form-select-sm">
                    <option value="">-- Tất cả cụm sân --</option>
                    <?php foreach ($cumSanList as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>" <?php echo $cumSanId === (int)$c['id'] ? 'selected' : ''; ?>>
                            <?php echo e($c['ten_cum_san']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Ngày thi đấu</label>
                <input type="date" name="ngay_dat" value="<?php echo e($ngayDat); ?>" class="form-control form-control-sm">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-filter"></i> Lọc
                </button>
            </div>
        </form>
    </div>
</div>

<!-- BẢNG DANH SÁCH ĐƠN HÀNG -->
<div class="admin-card">
    <div class="admin-card-header">
        <span>Danh sách đơn đặt sân (Tổng: <?php echo $totalRecords; ?> đơn)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>Sân bóng & Cụm</th>
                    <th>Lịch thi đấu</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái đơn</th>
                    <th>Thanh toán</th>
                    <th>Cập nhật nhanh</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">Không tìm thấy đơn đặt sân nào phù hợp.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td>
                                <strong>#BD16H<?php echo (int)$b['id']; ?></strong>
                                <small class="text-muted d-block"><?php echo formatDate($b['created_at']); ?></small>
                            </td>
                            <td>
                                <div class="fw-semibold"><?php echo e($b['ten_khach']); ?></div>
                                <small class="text-muted"><i class="bi bi-telephone me-1"></i><?php echo e($b['sdt_khach']); ?></small>
                            </td>
                            <td>
                                <div class="fw-semibold text-primary"><?php echo e($b['ten_san']); ?></div>
                                <small class="text-muted"><?php echo e($b['ten_cum_san']); ?></small>
                            </td>
                            <td>
                                <div class="fw-bold text-success"><?php echo substr($b['gio_bat_dau'], 0, 5); ?> - <?php echo substr($b['gio_ket_thuc'], 0, 5); ?></div>
                                <small class="text-muted"><?php echo formatDate($b['ngay_dat']); ?></small>
                            </td>
                            <td>
                                <strong class="text-danger"><?php echo formatCurrency($b['tong_tien']); ?></strong>
                            </td>
                            <td>
                                <?php echo renderBookingStatusBadge($b['trang_thai_dat']); ?>
                            </td>
                            <td>
                                <?php echo renderPaymentStatusBadge($b['trang_thai_thanh_toan'] ?? 'Chưa thanh toán'); ?>
                                <small class="text-muted d-block"><?php echo e($b['phuong_thuc_thanh_toan'] ?? 'Tiền mặt'); ?></small>
                            </td>
                            <td>
                                <!-- Form cập nhật nhanh trạng thái -->
                                <form method="POST" class="d-flex flex-column gap-1">
                                    <?php echo getCsrfField(); ?>
                                    <input type="hidden" name="action" value="quick_update">
                                    <input type="hidden" name="booking_id" value="<?php echo (int)$b['id']; ?>">
                                    <div class="input-group input-group-sm">
                                        <select name="trang_thai_dat" class="form-select form-select-sm" style="font-size:0.75rem;">
                                            <option value="Chờ xác nhận" <?php echo $b['trang_thai_dat'] === 'Chờ xác nhận' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                                            <option value="Đã xác nhận" <?php echo $b['trang_thai_dat'] === 'Đã xác nhận' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                            <option value="Đã hoàn thành" <?php echo $b['trang_thai_dat'] === 'Đã hoàn thành' ? 'selected' : ''; ?>>Đã hoàn thành</option>
                                            <option value="Đã hủy" <?php echo $b['trang_thai_dat'] === 'Đã hủy' ? 'selected' : ''; ?>>Đã hủy</option>
                                        </select>
                                        <button type="submit" class="btn btn-outline-success btn-sm" title="Lưu trạng thái">
                                            <i class="bi bi-check"></i>
                                        </button>
                                    </div>
                                </form>
                            </td>
                            <td>
                                <a href="<?php echo BASE_URL; ?>/admin/chi-tiet-don.php?id=<?php echo (int)$b['id']; ?>" class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Phân trang -->
    <?php if ($totalPages > 1): ?>
        <div class="p-3 border-top">
            <ul class="pagination pagination-sm justify-content-center mb-0">
                <?php $q = $_GET; ?>
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <?php $q['page'] = $page - 1; ?>
                    <a class="page-link" href="?<?php echo http_build_query($q); ?>">Trước</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): 
                    $q['page'] = $i;
                ?>
                    <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query($q); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <?php $q['page'] = $page + 1; ?>
                    <a class="page-link" href="?<?php echo http_build_query($q); ?>">Tiếp</a>
                </li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

