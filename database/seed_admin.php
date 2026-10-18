<?php
/**
 * Khởi tạo tài khoản Quản Trị Viên (Admin) an toàn cho hệ thống BongDa16h
 * Sử dụng mật khẩu mã hóa bằng password_hash()
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();

    // Kiểm tra xem đã có tài khoản Admin chưa
    $stmt = $pdo->query("SELECT id, ho_ten, so_dien_thoai FROM nguoi_dung WHERE vai_tro_id = 1 LIMIT 1");
    $admin = $stmt->fetch();

    if ($admin) {
        echo "<div style='font-family:sans-serif;padding:20px;color:#0f5132;background:#d1e7dd;border-radius:8px;'>";
        echo "<h3>Đã tồn tại tài khoản Admin!</h3>";
        echo "<p>Họ tên: <strong>" . htmlspecialchars($admin['ho_ten']) . "</strong></p>";
        echo "<p>Số điện thoại: <strong>" . htmlspecialchars($admin['so_dien_thoai']) . "</strong></p>";
        echo "<p><a href='" . BASE_URL . "/pages/dang-nhap.php'>Đến trang Đăng Nhập</a> | <a href='" . BASE_URL . "/admin/index.php'>Đến trang Quản Trị</a></p>";
        echo "</div>";
        exit;
    }

    // Tài khoản Admin mẫu
    $hoTen = "Quản Trị Viên Hệ Thống";
    $soDienThoai = "0981122334";
    $email = "admin@bongda16h.vn";
    $rawPassword = "admin123";
    $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);
    $vaiTroId = 1; // Admin

    // Thêm Admin
    $insertStmt = $pdo->prepare("
        INSERT INTO nguoi_dung (vai_tro_id, ho_ten, email, so_dien_thoai, mat_khau)
        VALUES (?, ?, ?, ?, ?)
    ");
    $insertStmt->execute([
        $vaiTroId,
        $hoTen,
        $email,
        $soDienThoai,
        $hashedPassword
    ]);

    echo "<div style='font-family:sans-serif;padding:25px;color:#0f5132;background:#d1e7dd;border:1px solid #badbcc;border-radius:8px;max-width:600px;margin:40px auto;'>";
    echo "<h2 style='color:#0f5132;margin-top:0;'>Khởi Tạo Tài Khoản Admin Thành Công!</h2>";
    echo "<p>Thông tin đăng nhập của Quản trị viên:</p>";
    echo "<ul>";
    echo "<li><strong>Số điện thoại:</strong> <code>0981122334</code> hoặc <strong>Email:</strong> <code>admin@bongda16h.vn</code></li>";
    echo "<li><strong>Mật khẩu:</strong> <code>admin123</code></li>";
    echo "<li><strong>Vai trò:</strong> Admin (Toàn quyền quản trị)</li>";
    echo "</ul>";
    echo "<p style='margin-top:20px;'><a href='" . BASE_URL . "/pages/dang-nhap.php' style='background:#10b981;color:white;padding:10px 20px;text-decoration:none;border-radius:6px;font-weight:bold;'>Đăng Nhập Ngay</a></p>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='font-family:sans-serif;padding:20px;color:#842029;background:#f8d7da;border-radius:8px;'>";
    echo "<h3>Lỗi khởi tạo Admin:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

