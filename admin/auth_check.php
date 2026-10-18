<?php
/**
 * Kiểm tra quyền quản trị Admin ở Backend
 * Dự án: BongDa16h
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Bắt buộc quyền Quản trị viên hoặc Nhân viên
requireStaffOrAdmin();

