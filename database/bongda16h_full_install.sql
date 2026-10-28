-- =============================================================================
-- BONGDA16H - FILE CO SO DU LIEU CHUAN HOA DAY DU CHO HOSTING INFINITYFREE
-- Bao gom 11 bang day du, ho tro UTF-8 Unicode, an toan khong lam mat du lieu cu
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: bongda16h
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `chi_tiet_dich_vu`
--

-- DROP TABLE IF EXISTS `chi_tiet_dich_vu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `chi_tiet_dich_vu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dat_san_id` int(11) NOT NULL,
  `dich_vu_id` int(11) NOT NULL,
  `so_luong` int(11) NOT NULL,
  `don_gia` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chi_tiet_dich_vu`
--

LOCK TABLES `chi_tiet_dich_vu` WRITE;
/*!40000 ALTER TABLE `chi_tiet_dich_vu` DISABLE KEYS */;
INSERT IGNORE INTO `chi_tiet_dich_vu` VALUES (1,1,1,2,10000.00);
/*!40000 ALTER TABLE `chi_tiet_dich_vu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cum_san`
--

-- DROP TABLE IF EXISTS `cum_san`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `cum_san` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ten_cum_san` varchar(150) NOT NULL,
  `dia_chi` varchar(255) NOT NULL,
  `kinh_do` decimal(10,8) DEFAULT NULL,
  `vi_do` decimal(10,8) DEFAULT NULL,
  `link_google_map` text DEFAULT NULL,
  `gio_mo_cua` time NOT NULL,
  `gio_dong_cua` time NOT NULL,
  `so_dien_thoai_lien_he` varchar(15) DEFAULT NULL,
  `hinh_anh_dai_dien` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cum_san`
--

LOCK TABLES `cum_san` WRITE;
/*!40000 ALTER TABLE `cum_san` DISABLE KEYS */;
INSERT IGNORE INTO `cum_san` VALUES (1,'Cụm Sân Bóng Trường Công Nghệ Kỹ Thuật Vinh','-115duong Nguyễn Viết Xuân, Xuân Tiến, Trường Vinh, Nghệ An, Việt Nam\r\n',99.99999999,18.67912300,'https://share.google/SoT2hNSN89wLxNJwy\r\n','06:00:00','22:30:00','0981122334','cum_san_hung_loi.jpg'),(2,'Sân Bóng Quân Đội Phong Đình Cảng ','Ngõ 124 Đường Phong Đình Cảng , Phường Trường Vinh Nghệ An',99.99999999,18.66578900,'https://maps.app.goo.gl/oEaRpkYg86jocvN9A','05:30:00','23:00:00','0972334455','cum_san_truong_thi.jpg'),(3,'Sân ĐHV ','01 Nguyễn Văn Trỗi, Trường Vinh, Nghệ An',99.99999999,18.67233300,'https://maps.app.goo.gl/L7SziHBAYodFcSsu6','06:00:00','22:00:00','','cum_san_hung_dung.jpg');
/*!40000 ALTER TABLE `cum_san` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dat_san`
--

-- DROP TABLE IF EXISTS `dat_san`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `dat_san` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nguoi_dung_id` int(11) NOT NULL,
  `san_bong_id` int(11) NOT NULL,
  `ngay_dat` date NOT NULL,
  `gio_bat_dau` time NOT NULL,
  `gio_ket_thuc` time NOT NULL,
  `tong_tien` decimal(10,2) NOT NULL,
  `trang_thai_dat` enum('Chờ xác nhận','Đã xác nhận','Đã hoàn thành','Đã hủy') NOT NULL DEFAULT 'Chờ xác nhận',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dat_san`
--

LOCK TABLES `dat_san` WRITE;
/*!40000 ALTER TABLE `dat_san` DISABLE KEYS */;
INSERT IGNORE INTO `dat_san` VALUES (1,2,1,'2026-10-17','17:00:00','18:30:00',395000.00,'Đã xác nhận','2026-10-15 15:23:50');
/*!40000 ALTER TABLE `dat_san` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dich_vu`
--

-- DROP TABLE IF EXISTS `dich_vu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `dich_vu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ten_dich_vu` varchar(100) NOT NULL,
  `don_gia` decimal(10,2) DEFAULT NULL,
  `don_vi_tinh` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dich_vu`
--

LOCK TABLES `dich_vu` WRITE;
/*!40000 ALTER TABLE `dich_vu` DISABLE KEYS */;
INSERT IGNORE INTO `dich_vu` VALUES (1,'Nước suối Aquafina',10000.00,'Chai'),(2,'Sting / Revive',15000.00,'Chai'),(3,'Thuê áo mượn đội',30000.00,'Bộ');
/*!40000 ALTER TABLE `dich_vu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hinh_anh_san`
--

-- DROP TABLE IF EXISTS `hinh_anh_san`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `hinh_anh_san` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `san_bong_id` int(11) DEFAULT NULL,
  `cum_san_id` int(11) DEFAULT NULL,
  `url_hinh_anh` varchar(255) NOT NULL,
  `la_anh_chinh` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hinh_anh_san`
--

LOCK TABLES `hinh_anh_san` WRITE;
/*!40000 ALTER TABLE `hinh_anh_san` DISABLE KEYS */;
INSERT IGNORE INTO `hinh_anh_san` VALUES (1,NULL,1,'[https://images.unsplash.com/photo-1529900748604-07564a03e7a6]',1),(2,NULL,2,'https://images.unsplash.com/photo-1574629810360-7efbbe195018',1),(3,NULL,3,'https://images.unsplash.com/photo-1508098682722-e99c43a406b2',1),(4,1,NULL,'https://images.unsplash.com/photo-1551958219-acbc608c6377',0),(5,3,NULL,'https://images.unsplash.com/photo-1518091043644-c1d4457512c6',0);
/*!40000 ALTER TABLE `hinh_anh_san` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loai_san`
--

-- DROP TABLE IF EXISTS `loai_san`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `loai_san` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ten_loai` varchar(50) NOT NULL,
  `gia_co_ban_thang_gio` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loai_san`
--

LOCK TABLES `loai_san` WRITE;
/*!40000 ALTER TABLE `loai_san` DISABLE KEYS */;
INSERT IGNORE INTO `loai_san` VALUES (1,'Sân 5 người',250000.00),(2,'Sân 7 người',350000.00);
/*!40000 ALTER TABLE `loai_san` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nguoi_dung`
--

-- DROP TABLE IF EXISTS `nguoi_dung`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `nguoi_dung` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vai_tro_id` int(11) NOT NULL DEFAULT 3,
  `ho_ten` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `so_dien_thoai` varchar(15) NOT NULL,
  `mat_khau` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nguoi_dung`
--

LOCK TABLES `nguoi_dung` WRITE;
/*!40000 ALTER TABLE `nguoi_dung` DISABLE KEYS */;
INSERT IGNORE INTO `nguoi_dung` VALUES (1,1,'Quản Trị Viên Hệ Thống','admin@bongda16h.vn','0981122334','$2y$10$LAeSyAsQ0y2LfVd2PRbK5.O4tSZc0OJsLs5wi0ATkuHs4fY7pXGNe','2026-10-15 15:16:13'),(2,3,'Nguyễn Văn Test','test@test.com','0919888999','$2y$10$iDRMzdChPE.RDW7KDvvN9eEaTralFov/e6W4U5YWYmau/vG9t8phq','2026-10-15 15:23:50');
/*!40000 ALTER TABLE `nguoi_dung` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `san_bong`
--

-- DROP TABLE IF EXISTS `san_bong`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `san_bong` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cum_san_id` int(11) NOT NULL,
  `loai_san_id` int(11) NOT NULL,
  `ten_san` varchar(100) NOT NULL,
  `dia_chi` varchar(255) DEFAULT NULL,
  `link_google_map` text DEFAULT NULL,
  `hinh_anh_dai_dien` varchar(255) DEFAULT NULL,
  `gia_theo_gio` decimal(10,2) NOT NULL,
  `trang_thai` enum('Hoạt động','Bảo trì') NOT NULL DEFAULT 'Hoạt động',
  `ghi_chu` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `san_bong`
--

LOCK TABLES `san_bong` WRITE;
/*!40000 ALTER TABLE `san_bong` DISABLE KEYS */;
INSERT IGNORE INTO `san_bong` (`id`, `cum_san_id`, `loai_san_id`, `ten_san`, `gia_theo_gio`, `trang_thai`, `ghi_chu`) VALUES (1,1,2,'Sân 1 (sân7) sân Bóng KT3 Nguyễn Viết Xuân',350000.00,'Hoạt động','Cỏ nhân tạo mới, đèn chiếu sáng tốt\r\nBãi đậu xe oto, xe máy phí 2K\r\nCó căng tin bán nước '),(2,1,2,'Sân 2 (sân 7) sân Bóng KT3  Nguyễn Viết Xuân',350000.00,'Hoạt động','Cỏ nhân tạo mới, đèn chiếu sáng tốt\r\nBãi đậu xe oto, xe máy phí 2K\r\nCó căng tin bán nước \r\n'),(3,1,1,'Sân 3 (sân 5) sân bóng KT3 Nguyên Viết Xuân',250000.00,'Hoạt động','Cỏ nhân tạo mới, đèn chiếu sáng tốt\r\nBãi đậu xe oto, xe máy phí 2K\r\nCó căng tin bán nước \r\n'),(4,2,2,'Sân 1 (sân 7) Sân Quân đội ',350000.00,'Hoạt động','Nằm trong phía doanh trại quân đội \r\nCó căng tin bán nước \r\nmiễn phí để xe \r\n'),(5,2,2,'Sân 2 (sân 7) Sân Quân đội ',350000.00,'Hoạt động','Cỏ nhân tạo mới, đèn chiếu sáng tốt\r\nBãi đậu xe oto, xe máy phí 2K\r\nCó căng tin bán nước '),(6,3,2,'Sân 1 (sân 7) Sân ĐHV',350000.00,'Hoạt động','Sân rộng có chỗ chạy bộ nữa \r\nMặt cỏ mới làm lại \r\nCó căn tin bán nước và đồ ăn \r\nCó bãi đậu xe oto và xe máy ( có mất phí 3k đến 10k tùy vào xe )\r\n'),(7,3,2,'Sân 2 (sân 7) Sân ĐHV',350000.00,'Hoạt động','Sân rộng có chỗ chạy bộ nữa \r\nMặt cỏ mới làm lại \r\nCó căn tin bán nước và đồ ăn \r\nCó bãi đậu xe oto và xe máy ( có mất phí 3k đến 10k tùy vào xe )'),(8,3,2,'Sân 3 (sân 7) Sân ĐHV ',350000.00,'Hoạt động','Sân rộng có chỗ chạy bộ nữa \r\nMặt cỏ mới làm lại \r\nCó căn tin bán nước và đồ ăn \r\nCó bãi đậu xe oto và xe máy ( có mất phí 3k đến 10k tùy vào xe )'),(9,3,2,'Sân 4 (Sân 7) Sân ĐHV',350000.00,'Hoạt động','Sân rộng có chỗ chạy bộ nữa \r\nMặt cỏ mới làm lại \r\nCó căn tin bán nước và đồ ăn \r\nCó bãi đậu xe oto và xe máy ( có mất phí 3k đến 10k tùy vào xe )');
/*!40000 ALTER TABLE `san_bong` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `thanh_toan`
--

-- DROP TABLE IF EXISTS `thanh_toan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `thanh_toan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dat_san_id` int(11) NOT NULL,
  `phuong_thuc_thanh_toan` enum('Tiền mặt','Chuyển khoản','Momo','VNPay') NOT NULL,
  `so_tien` decimal(10,2) NOT NULL,
  `trang_thai_thanh_toan` enum('Chưa thanh toán','Đã đặt cọc','Đã thanh toán đủ') NOT NULL DEFAULT 'Chưa thanh toán',
  `ngay_thanh_toan` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `dat_san_id` (`dat_san_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `thanh_toan`
--

LOCK TABLES `thanh_toan` WRITE;
/*!40000 ALTER TABLE `thanh_toan` DISABLE KEYS */;
INSERT IGNORE INTO `thanh_toan` VALUES (1,1,'Chuyển khoản',395000.00,'Đã thanh toán đủ','2026-10-15 15:23:50');
/*!40000 ALTER TABLE `thanh_toan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vai_tro`
--

-- DROP TABLE IF EXISTS `vai_tro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `vai_tro` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ten_vai_tro` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vai_tro`
--

LOCK TABLES `vai_tro` WRITE;
/*!40000 ALTER TABLE `vai_tro` DISABLE KEYS */;
INSERT IGNORE INTO `vai_tro` VALUES (1,'Admin'),(2,'Nhân viên'),(3,'Khách hàng');
/*!40000 ALTER TABLE `vai_tro` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-18 11:05:07


-- =============================================================================
-- BANG 11: TIM DOI BONG (MATCHMAKING)
-- =============================================================================

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



SET FOREIGN_KEY_CHECKS = 1;
