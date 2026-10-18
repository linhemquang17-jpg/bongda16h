<?php
/**
 * Footer giao diện người dùng
 * Dự án: BongDa16h
 */
?>
<!-- Footer -->
<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="brand-icon"><i class="bi bi-dribbble"></i></span>
                    <span class="fs-4 fw-bold text-white">BongDa<span class="text-success">16h</span></span>
                </div>
                <p class="text-secondary mb-3">
                    Hệ thống đặt sân bóng đá cỏ nhân tạo trực tuyến tiện lợi, nhanh chóng, minh bạch và chuyên nghiệp. Kết nối đam mê túc cầu của hàng ngàn cầu thủ phong trào mỗi ngày.
                </p>
                <div class="d-flex gap-3 fs-5 text-secondary">
                    <a href="#" class="text-secondary hover-success"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-secondary hover-success"><i class="bi bi-youtube"></i></a>
                    <a href="#" class="text-secondary hover-success"><i class="bi bi-tiktok"></i></a>
                    <a href="#" class="text-secondary hover-success"><i class="bi bi-telephone-fill"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <h5>Liên kết nhanh</h5>
                <ul class="list-unstyled">
                    <li><a href="<?php echo BASE_URL; ?>/index.php" class="footer-link">Trang chủ</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/pages/san-bong.php" class="footer-link">Tìm sân bóng</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/pages/cum-san.php" class="footer-link">Cụm sân đối tác</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/pages/dat-san.php" class="footer-link">Đặt sân trực tuyến</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/pages/lien-he.php" class="footer-link">Liên hệ hỗ trợ</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h5>Loại sân & Dịch vụ</h5>
                <ul class="list-unstyled">
                    <li><a href="<?php echo BASE_URL; ?>/pages/san-bong.php?loai_san_id=1" class="footer-link">Sân 5 người tiêu chuẩn</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/pages/san-bong.php?loai_san_id=2" class="footer-link">Sân 7 người tiêu chuẩn</a></li>
                    <li><a href="#" class="footer-link">Dịch vụ nước uống giải khát</a></li>
                    <li><a href="#" class="footer-link">Thuê áo bít, bóng thi đấu</a></li>
                    <li><a href="#" class="footer-link">Tổ chức giải đấu phong trào</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h5>Thông tin liên hệ</h5>
                <ul class="list-unstyled text-secondary">
                    <li class="mb-2"><i class="bi bi-geo-alt-fill text-success me-2"></i> TP. Vinh, Nghệ An</li>
                    <li class="mb-2"><i class="bi bi-telephone-fill text-success me-2"></i> Hotline: 0981 122 334</li>
                    <li class="mb-2"><i class="bi bi-envelope-fill text-success me-2"></i> contact@bongda16h.vn</li>
                    <li class="mb-2"><i class="bi bi-clock-fill text-success me-2"></i> Mở cửa: 05:30 - 23:00 hàng ngày</li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> BongDa16h. Tất cả quyền được bảo lưu. Phát triển trên nền tảng PHP & MySQL.</p>
        </div>
    </div>
</footer>

<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Main App JS -->
<script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
<?php if (isset($extraJs)) echo $extraJs; ?>
</body>
</html>

