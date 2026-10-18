# BongDa16h - Website Đặt Sân Bóng Đá Trực Tuyến Hoàn Chỉnh

Hệ thống website đặt sân bóng đá cỏ nhân tạo trực tuyến chuyên nghiệp, giao diện đẹp, hiện đại, responsive trên máy tính và điện thoại di động, kết nối trực tiếp với cơ sở dữ liệu MySQL `bongda16h`.

---

## 1. Công Nghệ Sử Dụng

- **Backend:** PHP 8+ (PDO Prepared Statements, Session an toàn, bảo vệ CSRF và chống XSS).
- **Database:** MariaDB / MySQL (`bongda16h`).
- **Frontend:** HTML5, CSS3, JavaScript (ES6 AJAX Fetch API), Bootstrap 5.3, Bootstrap Icons.
- **Máy chủ cục bộ:** XAMPP (`D:\xampp`).

---

## 2. Đường Dẫn Dự Án & Địa Chỉ Chạy

- **Đường dẫn XAMPP:** `D:\xampp\`
- **Đường dẫn mã nguồn:** `D:\xampp\htdocs\bongda16h\`
- **Địa chỉ website người dùng:** `http://localhost/bongda16h/`
- **Địa chỉ trang quản trị (Admin):** `http://localhost/bongda16h/admin/`

---

## 3. Cấu Trúc Mã Nguồn

```text
D:\xampp\htdocs\bongda16h\
├── index.php                           # Trang chủ: Banner, tìm kiếm, sân nổi bật, sân 5/7, cụm sân, dịch vụ
├── config/
│   └── database.php                    # Cấu hình kết nối PHP PDO MySQL an toàn
├── includes/
│   ├── header.php                      # Navbar, logo, trạng thái đăng nhập, flash alert
│   ├── footer.php                      # Footer thông tin liên hệ, hotline, link nhanh
│   ├── functions.php                   # Tiện ích: XSS, CSRF, tính tiền, kiểm tra trùng giờ, lấy ảnh, badge
│   └── auth.php                        # Xác thực, phân quyền (Admin, Nhân viên, Khách hàng), session
├── assets/
│   ├── css/
│   │   ├── style.css                   # Tone màu xanh sân cỏ, navy, trắng, responsive mượt mà
│   │   └── admin.css                   # Dashboard sidebar, cards, bảng biểu quản trị
│   └── js/
│       ├── main.js                     # Tooltip, auto-dismiss alerts, xác nhận thao tác
│       └── booking.js                  # AJAX kiểm tra slot giờ trống và tính tổng tiền real-time
├── pages/
│   ├── san-bong.php                    # Danh sách sân: tìm kiếm, lọc loại sân/cụm/giá, phân trang
│   ├── chi-tiet-san.php                # Chi tiết sân: slider ảnh, thông tin, Google Maps, lịch theo ngày
│   ├── cum-san.php                     # Danh sách các cụm sân thể thao đối tác
│   ├── chi-tiet-cum-san.php            # Chi tiết cụm sân & danh sách các sân bóng con thuộc cụm
│   ├── dat-san.php                     # Form quy trình đặt sân: chọn sân, ngày giờ, dịch vụ, thanh toán
│   ├── chi-tiet-don.php                # Chi tiết đơn: mã đơn, bảng kê chi phí, mã QR VietQR, nút hủy đơn
│   ├── lich-su-dat-san.php             # Khách xem lịch sử đơn đặt sân của chính mình, lọc trạng thái
│   ├── dang-nhap.php                   # Đăng nhập bằng SĐT hoặc Email + Mật khẩu
│   ├── dang-ky.php                     # Đăng ký tài khoản thành viên mới
│   ├── dang-xuat.php                   # Đăng xuất an toàn, xóa session
│   ├── tai-khoan.php                   # Quản lý hồ sơ cá nhân và đổi mật khẩu
│   └── lien-he.php                     # Thông tin hotline, địa chỉ các cụm sân trên bản đồ
├── actions/
│   ├── ajax_check_slot.php             # Endpoint AJAX lấy danh sách khung giờ đã đặt của sân trong ngày
│   ├── ajax_calc_price.php             # Endpoint AJAX kiểm tra xung đột trùng giờ & tính tiền thời gian thực
│   ├── process_booking.php             # Xử lý lưu đơn vào DB bằng Database Transaction (dat_san, chi_tiet_dich_vu, thanh_toan)
│   ├── process_cancel.php              # Xử lý hủy đơn đặt sân (chỉ khi đơn ở trạng thái 'Chờ xác nhận')
│   ├── process_login.php               # Xác minh đăng nhập bằng password_verify
│   ├── process_register.php            # Đăng ký thành viên, mã hóa mật khẩu password_hash
│   └── process_profile.php             # Cập nhật hồ sơ và đổi mật khẩu
├── admin/
│   ├── auth_check.php                  # Chốt chặn bảo mật phân quyền Quản trị viên ở Backend
│   ├── header.php                      # Sidebar quản trị và topbar
│   ├── footer.php                      # Script toggle sidebar và footer admin
│   ├── index.php                       # Dashboard thống kê doanh thu thực tế, tổng đơn, lịch đá hôm nay
│   ├── don-dat-san.php                 # Quản lý danh sách đơn, lọc, phân trang, duyệt nhanh
│   ├── chi-tiet-don.php                # Xem chi tiết đơn, duyệt đơn, cập nhật thanh toán, in đơn
│   ├── san-bong.php                    # Quản lý sân bóng (Thêm, Sửa, Bảo trì / Hoạt động, Xóa)
│   ├── cum-san.php                     # Quản lý cụm sân thể thao (Thêm, Sửa, Giờ mở cửa, Hotline, Map)
│   ├── loai-san.php                    # Quản lý loại sân (Sân 5, Sân 7, Sân 11 người...)
│   ├── dich-vu.php                     # Quản lý dịch vụ bán kèm (Nước uống, áo thi đấu...)
│   ├── hinh-anh.php                    # Quản lý ảnh sân bóng / cụm sân, chọn ảnh chính (la_anh_chinh)
│   ├── nguoi-dung.php                  # Quản lý tài khoản người dùng, phân quyền an toàn
│   └── thanh-toan.php                  # Báo cáo doanh thu thực tế, quản lý giao dịch thanh toán
├── database/
│   ├── seed_admin.php                  # Khởi tạo tài khoản Quản trị viên an toàn nếu chưa có
│   └── README_DB.md                    # Tài liệu cấu trúc 10 bảng CSDL
└── README.md
```

---

## 4. Hướng Dẫn Chạy Website Bằng XAMPP

### Bước 1: Mở XAMPP Control Panel
- Khởi động ứng dụng **XAMPP Control Panel** trên máy tính (nằm tại thư mục `D:\xampp\xampp-control.exe`).

### Bước 2: Khởi động Apache và MySQL
- Bấm nút **Start** ở dòng **Apache**. Khi chữ Apache chuyển sang màu xanh lá và hiển thị cổng `80, 443` là thành công.
- Bấm nút **Start** ở dòng **MySQL**. Khi chữ MySQL chuyển sang màu xanh lá và hiển thị cổng `3306` là thành công.

### Bước 3: Kiểm tra vị trí Source Code
- Đảm bảo toàn bộ thư mục mã nguồn nằm chính xác tại:
  `D:\xampp\htdocs\bongda16h\`

### Bước 4: Kiểm tra Cơ sở dữ liệu trong phpMyAdmin
- Mở trình duyệt web và truy cập địa chỉ:
  `http://localhost/phpmyadmin/`
- Kiểm tra danh sách cơ sở dữ liệu ở cột bên trái đã có database tên là: `bongda16h` với đầy đủ 10 bảng:
  1. `chi_tiet_dich_vu`
  2. `cum_san`
  3. `dat_san`
  4. `dich_vu`
  5. `hinh_anh_san`
  6. `loai_san`
  7. `nguoi_dung`
  8. `san_bong`
  9. `thanh_toan`
  10. `vai_tro`

### Bước 5: Cấu hình kết nối CSDL (nếu cần đổi cổng/mật khẩu)
- File cấu hình: `D:\xampp\htdocs\bongda16h\config\database.php`
- Cấu hình mặc định của XAMPP:
  - `DB_HOST`: `127.0.0.1`
  - `DB_PORT`: `3306`
  - `DB_NAME`: `bongda16h`
  - `DB_USER`: `root`
  - `DB_PASS`: `''` (để trống)
  - `DB_CHARSET`: `utf8mb4`
*(Nếu MySQL của bạn đổi sang cổng khác như 3307 hoặc có đặt mật khẩu root, hãy chỉnh sửa trực tiếp trong file này).*

### Bước 6: Khởi tạo tài khoản Quản trị viên (Admin)
Hệ thống đã có sẵn tài khoản Quản trị viên (Admin):
- **Số điện thoại:** `0981122334`
- **Email:** `admin@bongda16h.vn`
- **Mật khẩu:** `admin123`
- **Vai trò:** Admin (Toàn quyền quản trị)

*(Nếu cần khởi tạo lại, bạn chỉ cần truy cập link `http://localhost/bongda16h/database/seed_admin.php`).*

### Bước 7: Trải nghiệm Website
- **Trang chủ người dùng:** `http://localhost/bongda16h/`
- **Đăng ký tài khoản:** `http://localhost/bongda16h/pages/dang-ky.php`
- **Đăng nhập:** `http://localhost/bongda16h/pages/dang-nhap.php`
- **Đặt sân trực tuyến:** `http://localhost/bongda16h/pages/dat-san.php`
- **Trang Quản trị Admin:** `http://localhost/bongda16h/admin/`

---

## 5. Các Tính Năng Nghiệp Vụ Nổi Bật

1. **Chống trùng giờ đặt sân 100%:**
   - Thuật toán kiểm tra xung đột thời gian hai khoảng: `(gio_bat_dau < :gio_ket_thuc AND gio_ket_thuc > :gio_bat_dau)`.
   - Kiểm tra cả ở Frontend (AJAX thời gian thực khi khách chọn giờ) và Backend (trước khi ghi vào DB).
2. **Quy trình đặt sân hoàn chỉnh & Database Transaction:**
   - Sử dụng `PDO::beginTransaction()` đảm bảo lưu đồng bộ thông tin đơn `dat_san`, dịch vụ phát sinh `chi_tiet_dich_vu` và chứng từ `thanh_toan`.
3. **Tính toán giá tự động:**
   - Backend tính tiền thuê sân theo giờ chính xác từng phút, cộng dồn chi phí dịch vụ chọn thêm.
4. **Bảo mật và phân quyền:**
   - Mật khẩu mã hóa bằng `password_hash()` chuẩn `BCRYPT`.
   - Kiểm tra xác thực `password_verify()`.
   - Chống Session Fixation với `session_regenerate_id(true)`.
   - Ngăn chặn SQL Injection bằng 100% Prepared Statements.
   - Ngăn chặn XSS bằng hàm `e()` / `htmlspecialchars()`.
   - Bảo vệ form thao tác với CSRF Token.
   - Khách hàng chỉ xem được đơn của chính mình; chặn người dùng thường truy cập trang `/admin/`.
5. **Thanh toán minh bạch:**
   - Hỗ trợ Tiền mặt tại sân, Chuyển khoản VietQR nhanh, MoMo, VNPay.
   - Hướng dẫn chuyển khoản VietQR tự động điền số tiền và mã đơn.
   - Báo cáo doanh thu chỉ ghi nhận từ các giao dịch đã xác minh ("Đã thanh toán đủ" hoặc "Đã đặt cọc").

---

## 6. Xử Lý Các Vấn Đề Thường Gặp

| Lỗi | Nguyên nhân | Cách khắc phục |
|---|---|---|
| Không kết nối được Database | MySQL chưa bật hoặc sai cổng | Mở XAMPP bật MySQL; kiểm tra cổng trong file `config/database.php`. |
| 404 Not Found khi mở website | Đường dẫn thư mục chưa đúng | Đảm bảo source code nằm đúng tại `D:\xampp\htdocs\bongda16h\`. |
| Lỗi xung đột cổng 80 Apache | Ứng dụng khác (Skype, IIS...) chiếm cổng | Đổi cổng Apache sang `8080` trong `httpd.conf` của XAMPP, khi đó truy cập `http://localhost:8080/bongda16h/`. |
| Quên mật khẩu Admin | Tài khoản bị đổi mật khẩu | Chạy file `database/seed_admin.php` hoặc cập nhật hash mật khẩu mới trong phpMyAdmin. |

