# Cấu Trúc Cơ Sở Dữ Liệu `bongda16h`

Cơ sở dữ liệu được quản lý bởi MariaDB/MySQL trên XAMPP.

## 1. Danh Sách 10 Bảng

1. **`cum_san`**: Quản lý thông tin cụm sân thể thao (tên cụm, địa chỉ, kinh độ, vĩ độ, link Google Maps, giờ mở cửa, giờ đóng cửa, số điện thoại hotline, hình ảnh đại diện).
2. **`loai_san`**: Phân loại quy mô sân bóng đá (Sân 5 người, Sân 7 người, giá cơ bản theo giờ).
3. **`san_bong`**: Thông tin từng sân bóng đá cụ thể thuộc cụm sân (`cum_san_id`, `loai_san_id`, `ten_san`, `gia_theo_gio`, `trang_thai`, `ghi_chu`).
4. **`hinh_anh_san`**: Bộ sưu tập hình ảnh liên kết (`san_bong_id` hoặc `cum_san_id`, `url_hinh_anh`, `la_anh_chinh`).
5. **`vai_tro`**: Phân quyền người dùng (1: Admin, 2: Nhân viên, 3: Khách hàng).
6. **`nguoi_dung`**: Tài khoản người dùng (`vai_tro_id`, `ho_ten`, `email`, `so_dien_thoai`, `mat_khau`, `created_at`). Mật khẩu được mã hóa an toàn bằng thuật toán `password_hash()`.
7. **`dich_vu`**: Bảng dịch vụ bán kèm tại sân (`ten_dich_vu`, `don_gia`, `don_vi_tinh`).
8. **`dat_san`**: Đơn đặt sân bóng (`nguoi_dung_id`, `san_bong_id`, `ngay_dat`, `gio_bat_dau`, `gio_ket_thuc`, `tong_tien`, `trang_thai_dat`, `created_at`).
9. **`chi_tiet_dich_vu`**: Dịch vụ bổ sung gắn với đơn đặt sân (`dat_san_id`, `dich_vu_id`, `so_luong`, `don_gia`).
10. **`thanh_toan`**: Thông tin thanh toán cho đơn đặt sân (`dat_san_id` UNIQUE, `phuong_thuc_thanh_toan`, `so_tien`, `trang_thai_thanh_toan`, `ngay_thanh_toan`).

## 2. Các Ràng Buộc Khóa Ngoại (Foreign Keys)
- `san_bong.cum_san_id` -> `cum_san.id`
- `san_bong.loai_san_id` -> `loai_san.id`
- `hinh_anh_san.san_bong_id` -> `san_bong.id`
- `hinh_anh_san.cum_san_id` -> `cum_san.id`
- `nguoi_dung.vai_tro_id` -> `vai_tro.id`
- `dat_san.nguoi_dung_id` -> `nguoi_dung.id`
- `dat_san.san_bong_id` -> `san_bong.id`
- `chi_tiet_dich_vu.dat_san_id` -> `dat_san.id`
- `chi_tiet_dich_vu.dich_vu_id` -> `dich_vu.id`
- `thanh_toan.dat_san_id` -> `dat_san.id`

