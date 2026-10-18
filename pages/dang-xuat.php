<?php
/**
 * Đăng Xuất Người Dùng
 * Dự án: BongDa16h
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

logoutUser();

setFlash('info', 'Bạn đã đăng xuất thành công khỏi hệ thống!');
header('Location: ' . BASE_URL . '/pages/dang-nhap.php');
exit;

