/**
 * Xử lý logic đặt sân, kiểm tra khung giờ và tính tiền trực tiếp thời gian thực
 * Dự án: BongDa16h
 * Tương thích: Cả Localhost XAMPP và Hosting InfinityFree
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Khởi tạo các phần tử DOM
    const pitchSelect            = document.getElementById('san_bong_id');
    const dateInput              = document.getElementById('ngay_dat');
    const startTimeInput         = document.getElementById('gio_bat_dau');
    const endTimeInput           = document.getElementById('gio_ket_thuc');
    const bookedSlotsContainer   = document.getElementById('bookedSlotsContainer');
    const bookedSlotsList        = document.getElementById('bookedSlotsList');
    const timeConflictNotice     = document.getElementById('timeConflictNotice');
    const submitBookingBtn       = document.getElementById('submitBookingBtn');

    // Các phần tử hiển thị bảng tóm tắt chi phí
    const displayPitchPrice      = document.getElementById('displayPitchPrice');
    const displayPitchDuration   = document.getElementById('displayPitchDuration');
    const displayServiceTotal    = document.getElementById('displayServiceTotal');
    const displayGrandTotal      = document.getElementById('displayGrandTotal');

    // Lấy Base URL chuẩn xác (nếu là chuỗi rỗng trên hosting thì không bị biến thành '/bongda16h')
    const metaBase = document.querySelector('meta[name="base-url"]');
    const baseUrl = metaBase ? (metaBase.getAttribute('content') || '') : '';

    // Hàm định dạng tiền tệ VNĐ chuẩn (ví dụ: 375000 -> "375.000 đ")
    function formatVND(amount) {
        const num = Math.round(Number(amount) || 0);
        return num.toLocaleString('vi-VN') + ' đ';
    }

    /**
     * TÍNH TOÁN CHI PHÍ TRỰC TIẾP (INSTANT CLIENT-SIDE CALCULATION)
     * Đảm bảo giá tiền tự động cập nhật ngay lập tức (0ms) khi thay đổi sân, giờ đá, dịch vụ
     */
    function calculateInstantTotals() {
        let pitchTotal = 0;
        let serviceTotal = 0;
        let isTimeValid = false;

        // 1. Lấy đơn giá sân theo giờ từ option đang chọn
        let hourlyPrice = 0;
        if (pitchSelect && pitchSelect.selectedIndex >= 0) {
            const selectedOpt = pitchSelect.options[pitchSelect.selectedIndex];
            if (selectedOpt && selectedOpt.value) {
                hourlyPrice = parseFloat(selectedOpt.getAttribute('data-price')) || 0;
            }
        }

        // 2. Tính tiền thuê sân theo thời lượng (gio_bat_dau, gio_ket_thuc)
        const startVal = startTimeInput ? startTimeInput.value : '';
        const endVal   = endTimeInput ? endTimeInput.value : '';

        if (!hourlyPrice || !startVal || !endVal) {
            if (displayPitchPrice) displayPitchPrice.innerText = '0 đ';
            if (displayPitchDuration) displayPitchDuration.innerText = '(chưa chọn giờ)';
        } else {
            const [sh, sm] = startVal.split(':').map(Number);
            const [eh, em] = endVal.split(':').map(Number);

            const startMinutes = (sh * 60) + sm;
            const endMinutes   = (eh * 60) + em;
            const diffMinutes  = endMinutes - startMinutes;

            if (diffMinutes <= 0) {
                if (timeConflictNotice) {
                    timeConflictNotice.classList.remove('d-none');
                    timeConflictNotice.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i> Giờ kết thúc phải lớn hơn giờ bắt đầu!';
                }
                if (submitBookingBtn) submitBookingBtn.disabled = true;
                if (displayPitchPrice) displayPitchPrice.innerText = '0 đ';
                if (displayPitchDuration) displayPitchDuration.innerText = '(khung giờ không hợp lệ)';
            } else if (diffMinutes < 30) {
                if (timeConflictNotice) {
                    timeConflictNotice.classList.remove('d-none');
                    timeConflictNotice.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i> Thời lượng thuê sân tối thiểu là 30 phút!';
                }
                if (submitBookingBtn) submitBookingBtn.disabled = true;
                if (displayPitchPrice) displayPitchPrice.innerText = '0 đ';
                if (displayPitchDuration) displayPitchDuration.innerText = `(${diffMinutes} phút - tối thiểu 30p)`;
            } else {
                isTimeValid = true;
                const hours = diffMinutes / 60;
                // Công thức: Tiền thuê sân = Giá mỗi giờ * Số giờ thuê
                pitchTotal = Math.round(hours * hourlyPrice);

                if (displayPitchPrice) displayPitchPrice.innerText = formatVND(pitchTotal);
                if (displayPitchDuration) {
                    displayPitchDuration.innerText = `(${Number(hours.toFixed(2))} giờ / ${diffMinutes} phút)`;
                }

                if (timeConflictNotice) {
                    timeConflictNotice.classList.add('d-none');
                    timeConflictNotice.innerHTML = '';
                }
                if (submitBookingBtn) submitBookingBtn.disabled = false;
            }
        }

        // 3. Tính tiền dịch vụ bổ sung
        const checkboxes = document.querySelectorAll('.service-checkbox');
        checkboxes.forEach(cb => {
            if (cb.checked) {
                const sId = cb.getAttribute('data-id') || cb.value;
                const qtyInput = document.getElementById(`service_qty_${sId}`);
                const qty = qtyInput ? (parseInt(qtyInput.value, 10) || 1) : 1;
                const unitPrice = parseFloat(cb.getAttribute('data-price')) || 
                                  (qtyInput ? parseFloat(qtyInput.getAttribute('data-price')) : 0) || 0;
                serviceTotal += (unitPrice * qty);
            }
        });

        if (displayServiceTotal) {
            displayServiceTotal.innerText = formatVND(serviceTotal);
        }

        // 4. Tính tổng cộng = Tiền thuê sân + Tiền dịch vụ
        const grandTotal = pitchTotal + serviceTotal;
        if (displayGrandTotal) {
            displayGrandTotal.innerText = formatVND(grandTotal);
        }

        return {
            hourlyPrice: hourlyPrice,
            pitchTotal: pitchTotal,
            serviceTotal: serviceTotal,
            grandTotal: grandTotal,
            isTimeValid: isTimeValid
        };
    }

    /**
     * TẢI DANH SÁCH KHUNG GIỜ ĐÃ ĐẶT (AJAX)
     */
    function loadBookedSlots() {
        if (!pitchSelect || !dateInput) return;

        const pitchId = pitchSelect.value;
        const date = dateInput.value;

        // Tính ngay giá tiền client-side trước
        calculateInstantTotals();

        if (!pitchId || !date) {
            if (bookedSlotsContainer) bookedSlotsContainer.classList.add('d-none');
            return;
        }

        fetch(`${baseUrl}/actions/ajax_check_slot.php?san_bong_id=${encodeURIComponent(pitchId)}&ngay_dat=${encodeURIComponent(date)}`)
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(data => {
                if (data.success && bookedSlotsContainer && bookedSlotsList) {
                    bookedSlotsContainer.classList.remove('d-none');
                    if (data.slots && data.slots.length > 0) {
                        let html = '';
                        data.slots.forEach(slot => {
                            const start = (slot.gio_bat_dau || '').substring(0, 5);
                            const end = (slot.gio_ket_thuc || '').substring(0, 5);
                            html += `<span class="badge bg-danger-subtle text-danger border border-danger-subtle p-2 me-2 mb-2">
                                <i class="bi bi-clock-fill me-1"></i> ${start} - ${end} (${slot.trang_thai_dat})
                            </span>`;
                        });
                        bookedSlotsList.innerHTML = html;
                    } else {
                        bookedSlotsList.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i> Ngày này hiện chưa có lịch đặt nào. Tất cả khung giờ đều đang trống!</span>';
                    }
                }
                // Kiểm tra xung đột với server
                verifyWithServer();
            })
            .catch(err => {
                console.warn('Lưu ý khi tải slot trùng:', err);
                // Vẫn giữ nguyên giá tiền client-side đã tính
                verifyWithServer();
            });
    }

    /**
     * XÁC MINH VỚI SERVER VÀ KIỂM TRA TRÙNG GIỜ (AJAX BACKGROUND CHECK)
     */
    function verifyWithServer() {
        const local = calculateInstantTotals();
        if (!local.isTimeValid) return;

        const pitchId = pitchSelect ? pitchSelect.value : '';
        const date    = dateInput ? dateInput.value : '';
        const start   = startTimeInput ? startTimeInput.value : '';
        const end     = endTimeInput ? endTimeInput.value : '';

        if (!pitchId || !date || !start || !end) return;

        // Thu thập dịch vụ
        const serviceData = [];
        const checkboxes = document.querySelectorAll('.service-checkbox:checked');
        checkboxes.forEach(cb => {
            const sId = cb.getAttribute('data-id') || cb.value;
            const qtyInput = document.getElementById(`service_qty_${sId}`);
            const qty = qtyInput ? (parseInt(qtyInput.value, 10) || 1) : 1;
            serviceData.push({ id: sId, quantity: qty });
        });

        fetch(`${baseUrl}/actions/ajax_calc_price.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                san_bong_id: pitchId,
                ngay_dat: date,
                gio_bat_dau: start,
                gio_ket_thuc: end,
                dich_vu: serviceData
            })
        })
        .then(res => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(res => {
            // Nếu server phát hiện trùng giờ đặt trước
            if (!res.valid) {
                if (timeConflictNotice) {
                    timeConflictNotice.classList.remove('d-none');
                    timeConflictNotice.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i> ${res.message}`;
                }
                if (submitBookingBtn) submitBookingBtn.disabled = true;
            } else {
                if (timeConflictNotice) {
                    timeConflictNotice.classList.add('d-none');
                    timeConflictNotice.innerHTML = '';
                }
                if (submitBookingBtn) submitBookingBtn.disabled = false;
            }

            // Đồng bộ số tiền chuẩn từ server nếu có trả về
            if (res.pitch_total !== undefined && res.pitch_total !== null) {
                if (displayPitchPrice) displayPitchPrice.innerText = formatVND(res.pitch_total);
                if (displayPitchDuration && res.hours) {
                    displayPitchDuration.innerText = `(${res.hours} giờ / ${res.duration_minutes} phút)`;
                }
                if (displayServiceTotal && res.services_total !== undefined) {
                    displayServiceTotal.innerText = formatVND(res.services_total);
                }
                if (displayGrandTotal && res.grand_total !== undefined) {
                    displayGrandTotal.innerText = formatVND(res.grand_total);
                }
            }
        })
        .catch(err => {
            console.warn('Lưu ý khi gọi ajax_calc_price (Client-side fallback vẫn hoạt động):', err);
        });
    }

    // 2. GẮN SỰ KIỆN LẮNG NGHE (EVENT LISTENERS)
    if (pitchSelect) {
        pitchSelect.addEventListener('change', loadBookedSlots);
    }
    if (dateInput) {
        dateInput.addEventListener('change', loadBookedSlots);
    }
    if (startTimeInput) {
        startTimeInput.addEventListener('change', function () {
            calculateInstantTotals();
            verifyWithServer();
        });
        startTimeInput.addEventListener('input', function () {
            calculateInstantTotals();
        });
    }
    if (endTimeInput) {
        endTimeInput.addEventListener('change', function () {
            calculateInstantTotals();
            verifyWithServer();
        });
        endTimeInput.addEventListener('input', function () {
            calculateInstantTotals();
        });
    }

    // Sự kiện dịch vụ bổ sung
    const serviceCheckboxes = document.querySelectorAll('.service-checkbox');
    serviceCheckboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            const sId = this.getAttribute('data-id') || this.value;
            const qtyInput = document.getElementById(`service_qty_${sId}`);
            if (qtyInput) {
                qtyInput.disabled = !this.checked;
                if (!this.checked) {
                    qtyInput.value = 1;
                }
            }
            calculateInstantTotals();
            verifyWithServer();
        });
    });

    const serviceQtyInputs = document.querySelectorAll('.service-qty-input');
    serviceQtyInputs.forEach(input => {
        input.addEventListener('input', function () {
            calculateInstantTotals();
            verifyWithServer();
        });
        input.addEventListener('change', function () {
            calculateInstantTotals();
            verifyWithServer();
        });
    });

    // 3. TỰ ĐỘNG CHẠY TÍNH TOÁN BAN ĐẦU KHI TRANG LOAD XONG
    calculateInstantTotals();
    if (pitchSelect && pitchSelect.value && dateInput && dateInput.value) {
        loadBookedSlots();
    }
});
