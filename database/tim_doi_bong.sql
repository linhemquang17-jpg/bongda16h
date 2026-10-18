-- ==============================================================================
-- BẢNG QUẢN LÝ TIN TÌM ĐỐI ĐÁ BÓNG (MATCHMAKING)
-- Dự án: BongDa16h
-- ==============================================================================

CREATE TABLE IF NOT EXISTS `tim_doi_bong` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nguoi_dung_id` INT(11) DEFAULT NULL COMMENT 'Tài khoản đăng tin nếu có',
  `cum_san_id` INT(11) DEFAULT NULL COMMENT 'Cụm sân bóng đá mong muốn thi đấu',
  `san_bong_id` INT(11) DEFAULT NULL COMMENT 'Sân bóng cụ thể nếu đã chốt sân',
  `ten_doi` VARCHAR(100) NOT NULL COMMENT 'Tên đội bóng',
  `anh_dai_dien` VARCHAR(255) DEFAULT NULL COMMENT 'Ảnh logo / đại diện đội',
  `loai_san` ENUM('Sân 5 người', 'Sân 7 người', 'Sân 11 người') NOT NULL DEFAULT 'Sân 7 người',
  `khu_vuc` VARCHAR(255) NOT NULL COMMENT 'Khu vực muốn đá (Quận/Huyện, Tuyến đường)',
  `kinh_do` DECIMAL(10,8) DEFAULT NULL COMMENT 'Tọa độ GPS kinh độ',
  `vi_do` DECIMAL(10,8) DEFAULT NULL COMMENT 'Tọa độ GPS vĩ độ',
  `ngay_da` DATE NOT NULL COMMENT 'Ngày mong muốn tổ chức trận đấu',
  `gio_da` TIME NOT NULL COMMENT 'Giờ bắt đầu',
  `thoi_luong` INT(11) NOT NULL DEFAULT 90 COMMENT 'Thời lượng trận (phút)',
  `trinh_do` ENUM('Giao lưu', 'Phong trào', 'Trung bình', 'Khá') NOT NULL DEFAULT 'Phong trào',
  `mo_ta` TEXT DEFAULT NULL COMMENT 'Chi tiết giới thiệu, điều lệ share tiền sân nước, quy định',
  `nguoi_lien_he` VARCHAR(100) NOT NULL COMMENT 'Tên đội trưởng / người đại diện',
  `so_dien_thoai` VARCHAR(15) NOT NULL COMMENT 'Số điện thoại gọi liên hệ',
  `zalo` VARCHAR(50) DEFAULT NULL COMMENT 'Số điện thoại hoặc link Zalo',
  `trang_thai` ENUM('Đang tìm đối', 'Đã tìm được đối', 'Đã hủy') NOT NULL DEFAULT 'Đang tìm đối',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_tim_doi_cum_san` (`cum_san_id`),
  KEY `fk_tim_doi_san_bong` (`san_bong_id`),
  KEY `fk_tim_doi_nguoi_dung` (`nguoi_dung_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- DỮ LIỆU MẪU BAN ĐẦU (SEED DATA THỰC TẾ)
-- ==============================================================================

INSERT INTO `tim_doi_bong` 
(`id`, `nguoi_dung_id`, `cum_san_id`, `san_bong_id`, `ten_doi`, `anh_dai_dien`, `loai_san`, `khu_vuc`, `kinh_do`, `vi_do`, `ngay_da`, `gio_da`, `thoi_luong`, `trinh_do`, `mo_ta`, `nguoi_lien_he`, `so_dien_thoai`, `zalo`, `trang_thai`, `created_at`) 
VALUES
(1, 1, 1, 1, 'FC Anh Em Vinh Tech', 'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?auto=format&fit=crop&w=400&q=80', 'Sân 7 người', 'Đường Nguyễn Viết Xuân, Phường Hưng Dũng, TP Vinh', 105.68812300, 18.67912300, CURDATE() + INTERVAL 1 DAY, '17:30:00', 90, 'Phong trào', 'Đội bóng văn phòng IT đá phong trào, vui vẻ thể thao không cay cú. Tiền sân và nước chia 50-50, có trọng tài nội bộ bắt trận.', 'Anh Hải (Đội trưởng)', '0981122334', '0981122334', 'Đang tìm đối', NOW()),

(2, NULL, 1, 2, 'FC Bến Thủy Boys', 'https://images.unsplash.com/photo-1574629810360-7efbbe195018?auto=format&fit=crop&w=400&q=80', 'Sân 7 người', 'Khu vực Cụm KT3 - Bến Thủy', 105.68920000, 18.67850000, CURDATE() + INTERVAL 2 DAY, '18:00:00', 90, 'Giao lưu', 'Cần tìm đội đá nhẹ nhàng vào tối mai. Đội toàn anh em trung niên rèn luyện sức khỏe, giao lưu học hỏi là chính.', 'Minh Tuấn', '0912345678', '0912345678', 'Đang tìm đối', NOW()),

(3, 2, 2, 4, 'FC Chiến Binh Xanh', 'https://images.unsplash.com/photo-1529900748604-07564a03e7a6?auto=format&fit=crop&w=400&q=80', 'Sân 7 người', 'Sân Quân Đội, Đường Phong Đình Cảng, Trường Thi', 105.67910000, 18.66578900, CURDATE() + INTERVAL 1 DAY, '19:00:00', 90, 'Trung bình', 'Tìm đối tác cứng cứng xíu để đá giải phong trào chuẩn bị tháng tới. Tiền sân nước campuchia công bằng.', 'Nguyễn Văn Test', '0919888999', '0919888999', 'Đang tìm đối', NOW()),

(4, NULL, 3, 6, 'FC Sinh Viên ĐHV', 'https://images.unsplash.com/photo-1551958219-acbc608c6377?auto=format&fit=crop&w=400&q=80', 'Sân 7 người', '01 Nguyễn Văn Trỗi, Bến Thủy, Vinh', 105.68233300, 18.67233300, CURDATE() + INTERVAL 3 DAY, '17:00:00', 90, 'Phong trào', 'Đội sinh viên năng động đá giao lưu cuối tuần. Mong tìm được các bạn sinh viên hoặc cựu sinh viên giao lưu kết nối.', 'Quang Huy', '0977889900', '0977889900', 'Đang tìm đối', NOW());

