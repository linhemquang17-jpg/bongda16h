-- ==============================================================================
-- DỰ ÁN BONGDA16H - TẠO BẢNG CHI TIẾT DỊCH VỤ (chi_tiet_dich_vu)
-- Môi trường: Hosting InfinityFree & Localhost XAMPP
-- Database: if0_43184594_datsan
-- Đặc điểm: An toàn, không xóa dữ liệu, tương thích 100% với đơn cũ và mới
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- 1. TẠO BẢNG chi_tiet_dich_vu NẾU CHƯA TỒN TẠI
CREATE TABLE IF NOT EXISTS `chi_tiet_dich_vu` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `dat_san_id` INT(11) NOT NULL COMMENT 'Mã đơn đặt sân (liên kết bảng dat_san)',
  `dich_vu_id` INT(11) NOT NULL COMMENT 'Mã dịch vụ bổ sung (liên kết bảng dich_vu)',
  `ten_dich_vu` VARCHAR(100) DEFAULT NULL COMMENT 'Tên dịch vụ tại thời điểm đặt',
  `don_vi_tinh` VARCHAR(20) DEFAULT NULL COMMENT 'Đơn vị tính (Chai, Bộ, Lon...)',
  `don_gia` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Đơn giá tại thời điểm đặt',
  `so_luong` INT(11) NOT NULL DEFAULT 1 COMMENT 'Số lượng đặt',
  `thanh_tien` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Thành tiền = don_gia * so_luong',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời điểm tạo bản ghi',
  PRIMARY KEY (`id`),
  KEY `fk_ctdv_dat_san` (`dat_san_id`),
  KEY `fk_ctdv_dich_vu` (`dich_vu_id`),
  CONSTRAINT `fk_ctdv_dat_san` FOREIGN KEY (`dat_san_id`) REFERENCES `dat_san` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ctdv_dich_vu` FOREIGN KEY (`dich_vu_id`) REFERENCES `dich_vu` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. ĐỒNG BỘ DỮ LIỆU ĐƠN CŨ #1 (NẾU ĐÃ CÓ BẢN GHI TRƯỚC ĐÓ)
-- Nạp dịch vụ mẫu cho đơn #1 nếu bảng vừa tạo mới và đơn #1 tồn tại
INSERT IGNORE INTO `chi_tiet_dich_vu` (`id`, `dat_san_id`, `dich_vu_id`, `ten_dich_vu`, `don_vi_tinh`, `don_gia`, `so_luong`, `thanh_tien`)
SELECT 1, 1, 1, 'Nước suối Aquafina', 'Chai', 10000.00, 2, 20000.00
FROM `dat_san` WHERE `id` = 1
ON DUPLICATE KEY UPDATE `id` = `id`;

-- Cập nhật tên và thành tiền cho bất kỳ bản ghi cũ nào bị thiếu
UPDATE `chi_tiet_dich_vu` cd
JOIN `dich_vu` v ON cd.dich_vu_id = v.id
SET cd.ten_dich_vu = CASE WHEN (cd.ten_dich_vu IS NULL OR cd.ten_dich_vu = '') THEN v.ten_dich_vu ELSE cd.ten_dich_vu END,
    cd.don_vi_tinh = CASE WHEN (cd.don_vi_tinh IS NULL OR cd.don_vi_tinh = '') THEN v.don_vi_tinh ELSE cd.don_vi_tinh END,
    cd.thanh_tien  = CASE WHEN (cd.thanh_tien IS NULL OR cd.thanh_tien = 0) THEN (cd.don_gia * cd.so_luong) ELSE cd.thanh_tien END;

SET FOREIGN_KEY_CHECKS = 1;

