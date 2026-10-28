<?php
/**
 * Quản Lý Giao Dịch Thanh Toán & Báo Cáo Doanh Thu - Admin
 * Dự án: BongDa16h
 */
$pageTitle = 'Quản Lý Thanh Toán & Doanh Thu';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/auth_check.php';

$pdo = getDBConnection();

// Cập nhật trạng thái thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Yêu cầu không hợp lệ!');
        header('Location: ' . BASE_URL . '/admin/thanh-toan.php');
        exit;
    }

    $payId    = (int)($_POST['payment_id'] ?? 0);
    $status   = sanitize($_POST['trang_thai_thanh_toan'] ?? '');
    $validTT  = ['Chưa thanh toán', 'Đã đặt cọc', 'Đã thanh toán đủ'];

    if ($payId > 0 && in_array($status, $validTT)) {
        try {
            $stmt = $pdo->prepare("UPDATE thanh_toan SET trang_thai_thanh_toan = ? WHERE id = ?");
            $stmt->execute([$status, $payId]);
            setFlash('success', "Cập nhật trạng thái giao dịch thanh toán #{$payId} thành công!");
        } catch (Exception $e) {
            setFlash('danger', 'Lỗi cập nhật: ' . $e->getMessage());
        }
    }

    header('Location: ' . BASE_URL . '/admin/thanh-toan.php');
    exit;
}

// Thống kê tổng quan an toàn
$totalPaid = 0.0;
$totalDeposit = 0.0;
$totalPending = 0.0;
$payments = [];
$payError = null;

try {
    $totalPaid = (float)$pdo->query("
        SELECT COALESCE(SUM(so_tien), 0) 
        FROM thanh_toan 
        WHERE trang_thai_thanh_toan = 'Đã thanh toán đủ'
    ")->fetchColumn();

    $totalDeposit = (float)$pdo->query("
        SELECT COALESCE(SUM(so_tien), 0) 
        FROM thanh_toan 
        WHERE trang_thai_thanh_toan = 'Đã đặt cọc'
    ")->fetchColumn();

    $totalPending = (float)$pdo->query("
        SELECT COALESCE(SUM(so_tien), 0) 
        FROM thanh_toan 
        WHERE trang_thai_thanh_toan = 'Chưa thanh toán'
    ")->fetchColumn();

    // Lọc dữ liệu danh sách giao dịch
    $filterMethod = sanitize($_GET['method'] ?? '');
    $filterStatus = sanitize($_GET['status'] ?? '');

    $conditions = [];
    $params = [];

    if (!empty($filterMethod)) {
        $conditions[] = "t.phuong_thuc_thanh_toan = :method";
        $params[':method'] = $filterMethod;
    }

    if (!empty($filterStatus)) {
        $conditions[] = "t.trang_thai_thanh_toan = :status";
        $params[':status'] = $filterStatus;
    }

    $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $sql = "
        SELECT t.*, 
               d.ngay_dat, d.gio_bat_dau, d.gio_ket_thuc, d.trang_thai_dat,
               u.ho_ten as ten_khach, u.so_dien_thoai as sdt_khach,
               s.ten_san, c.ten_cum_san
        FROM thanh_toan t
        JOIN dat_san d ON t.dat_san_id = d.id
        JOIN nguoi_dung u ON d.nguoi_dung_id = u.id
        JOIN san_bong s ON d.san_bong_id = s.id
        JOIN cum_san c ON s.cum_san_id = c.id
        {$whereClause}
        ORDER BY t.id DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $payments = $stmt->fetchAll();
} catch (Throwable $e) {
    $payError = "Cảnh báo cơ sở dữ liệu: " . $e->getMessage();
}

$validTotalRevenue = $totalPaid + $totalDeposit;

require_once __DIR__ . '/header.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold text-navy mb-1">Quản Lý Giao Dịch & Báo Cáo Doanh Thu</h3>
        <p class="text-muted mb-0">Theo dõi dòng tiền thanh toán hợp lệ từ khách hàng</p>
    </div>
</div>

<?php if (!empty($payError)): ?>
    <div class="alert alert-warning border-0 shadow-sm rounded-3 mb-4">
        <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
        <?php echo e($payError); ?> - <a href="<?php echo BASE_URL; ?>/admin/diagnostics.php" class="fw-bold text-decoration-none text-dark">Mở công cụ chẩn đoán để đồng bộ bảng thanh toán</a>
    </div>
<?php endif; ?>

<!-- STAT CARDS DOANH THU -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-wallet-fill"></i>
            </div>
            <div>
                <div class="stat-value text-success"><?php echo formatCurrency($validTotalRevenue); ?></div>
                <div class="stat-label">Doanh thu thực tế (Đã thu)</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-piggy-bank-fill"></i>
            </div>
            <div>
                <div class="stat-value text-primary"><?php echo formatCurrency($totalDeposit); ?></div>
                <div class="stat-label">Tiền khách đã cọc</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div>
                <div class="stat-value text-warning"><?php echo formatCurrency($totalPending); ?></div>
                <div class="stat-label">Đang chờ thanh toán</div>
            </div>
        </div>
    </div>
</div>

<!-- BỘ LỌC GIAO DỊCH -->
<div class="admin-card mb-4">
    <div class="admin-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Phương thức thanh toán</label>
                <select name="method" class="form-select form-select-sm">
                    <option value="">-- Tất cả phương thức --</option>
                    <option value="Tiền mặt" <?php echo $filterMethod === 'Tiền mặt' ? 'selected' : ''; ?>>Tiền mặt</option>
                    <option value="Chuyển khoản" <?php echo $filterMethod === 'Chuyển khoản' ? 'selected' : ''; ?>>Chuyển khoản (VietQR)</option>
                    <option value="Momo" <?php echo $filterMethod === 'Momo' ? 'selected' : ''; ?>>Ví MoMo</option>
                    <option value="VNPay" <?php echo $filterMethod === 'VNPay' ? 'selected' : ''; ?>>Cổng VNPay</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Tình trạng thanh toán</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- Tất cả tình trạng --</option>
                    <option value="Chưa thanh toán" <?php echo $filterStatus === 'Chưa thanh toán' ? 'selected' : ''; ?>>Chưa thanh toán</option>
                    <option value="Đã đặt cọc" <?php echo $filterStatus === 'Đã đặt cọc' ? 'selected' : ''; ?>>Đã đặt cọc</option>
                    <option value="Đã thanh toán đủ" <?php echo $filterStatus === 'Đã thanh toán đủ' ? 'selected' : ''; ?>>Đã thanh toán đủ</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm me-2">
                    <i class="bi bi-filter"></i> Lọc giao dịch
                </button>
                <a href="<?php echo BASE_URL; ?>/admin/thanh-toan.php" class="btn btn-outline-secondary btn-sm">Đặt lại</a>
            </div>
        </form>
    </div>
</div>

<!-- BẢNG DANH SÁCH GIAO DỊCH -->
<div class="admin-card">
    <div class="admin-card-header">
        <span>Lịch sử giao dịch thanh toán (Tổng: <?php echo count($payments); ?> giao dịch)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>Mã GD</th>
                    <th>Đơn đặt sân</th>
                    <th>Khách hàng</th>
                    <th>Sân bóng</th>
                    <th>Số tiền</th>
                    <th>Phương thức</th>
                    <th>Trạng thái</th>
                    <th>Thời gian</th>
                    <th class="text-end">Cập nhật</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">Chưa có giao dịch thanh toán nào phù hợp.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><strong>#TT<?php echo (int)$p['id']; ?></strong></td>
                            <td>
                                <a href="<?php echo BASE_URL; ?>/admin/chi-tiet-don.php?id=<?php echo (int)$p['dat_san_id']; ?>" class="fw-bold text-decoration-none">
                                    #BD16H<?php echo (int)$p['dat_san_id']; ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold"><?php echo e($p['ten_khach']); ?></div>
                                <small class="text-muted"><?php echo e($p['sdt_khach']); ?></small>
                            </td>
                            <td>
                                <div><?php echo e($p['ten_san']); ?></div>
                                <small class="text-muted"><?php echo e($p['ten_cum_san']); ?></small>
                            </td>
                            <td><strong class="text-danger"><?php echo formatCurrency($p['so_tien']); ?></strong></td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary"><?php echo e($p['phuong_thuc_thanh_toan']); ?></span>
                            </td>
                            <td><?php echo renderPaymentStatusBadge($p['trang_thai_thanh_toan']); ?></td>
                            <td><small class="text-muted"><?php echo formatDateTime($p['ngay_thanh_toan']); ?></small></td>
                            <td class="text-end">
                                <form method="POST" class="d-inline-flex gap-1">
                                    <?php echo getCsrfField(); ?>
                                    <input type="hidden" name="payment_id" value="<?php echo (int)$p['id']; ?>">
                                    <select name="trang_thai_thanh_toan" class="form-select form-select-sm" style="font-size:0.75rem; width:130px;">
                                        <option value="Chưa thanh toán" <?php echo $p['trang_thai_thanh_toan'] === 'Chưa thanh toán' ? 'selected' : ''; ?>>Chưa thanh toán</option>
                                        <option value="Đã đặt cọc" <?php echo $p['trang_thai_thanh_toan'] === 'Đã đặt cọc' ? 'selected' : ''; ?>>Đã đặt cọc</option>
                                        <option value="Đã thanh toán đủ" <?php echo $p['trang_thai_thanh_toan'] === 'Đã thanh toán đủ' ? 'selected' : ''; ?>>Đã thanh toán đủ</option>
                                    </select>
                                    <button type="submit" class="btn btn-outline-success btn-sm" title="Lưu trạng thái">
                                        <i class="bi bi-check"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

