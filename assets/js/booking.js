/**
 * Xử lý logic đặt sân, kiểm tra khung giờ và tính tiền trực tiếp (BongDa16h)
 */

document.addEventListener('DOMContentLoaded', function () {
    const pitchSelect = document.getElementById('san_bong_id');
    const dateInput = document.getElementById('ngay_dat');
    const startTimeInput = document.getElementById('gio_bat_dau');
    const endTimeInput = document.getElementById('gio_ket_thuc');
    const bookedSlotsContainer = document.getElementById('bookedSlotsContainer');
    const bookedSlotsList = document.getElementById('bookedSlotsList');
    const timeConflictNotice = document.getElementById('timeConflictNotice');
    const submitBookingBtn = document.getElementById('submitBookingBtn');

    // Các phần tử hiển thị giá
    const displayPitchPrice = document.getElementById('displayPitchPrice');
    const displayPitchDuration = document.getElementById('displayPitchDuration');
    const displayServiceTotal = document.getElementById('displayServiceTotal');
    const displayGrandTotal = document.getElementById('displayGrandTotal');

    // Lấy Base URL từ thẻ meta hoặc fallback
    const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '/bongda16h';

    // Cập nhật slot khi đổi sân hoặc đổi ngày
    function loadBookedSlots() {
        if (!pitchSelect || !dateInput) return;

        const pitchId = pitchSelect.value;
        const date = dateInput.value;

        if (!pitchId || !date) {
            if (bookedSlotsContainer) bookedSlotsContainer.classList.add('d-none');
            return;
        }

        fetch(`${baseUrl}/actions/ajax_check_slot.php?san_bong_id=${encodeURIComponent(pitchId)}&ngay_dat=${encodeURIComponent(date)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && bookedSlotsContainer && bookedSlotsList) {
                    bookedSlotsContainer.classList.remove('d-none');
                    if (data.slots && data.slots.length > 0) {
                        let html = '';
                        data.slots.forEach(slot => {
                            const start = slot.gio_bat_dau.substring(0, 5);
                            const end = slot.gio_ket_thuc.substring(0, 5);
                            html += `<span class="badge bg-danger-subtle text-danger border border-danger-subtle p-2 me-2 mb-2">
                                <i class="bi bi-clock-fill me-1"></i> ${start} - ${end} (${slot.trang_thai_dat})
                            </span>`;
                        });
                        bookedSlotsList.innerHTML = html;
                    } else {
                        bookedSlotsList.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i> Ngày này hiện chưa có lịch đặt nào. Tất cả khung giờ đều đang trống!</span>';
                    }
                }
                // Sau khi có slot, check xem giờ hiện tại có bị xung đột không
                checkCurrentSelectionConflict();
            })
            .catch(err => console.error('Error fetching slots:', err));
    }

    // Kiểm tra xung đột và tính tiền
    function checkCurrentSelectionConflict() {
        if (!pitchSelect || !dateInput || !startTimeInput || !endTimeInput) return;

        const pitchId = pitchSelect.value;
        const date = dateInput.value;
        const start = startTimeInput.value;
        const end = endTimeInput.value;

        if (!pitchId || !date || !start || !end) {
            updateTotals();
            return;
        }

        // Gọi action kiểm tra xung đột và tính toán
        const serviceData = collectSelectedServices();

        fetch(`${baseUrl}/actions/ajax_calc_price.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                san_bong_id: pitchId,
                ngay_dat: date,
                gio_bat_dau: start,
                gio_ket_thuc: end,
                dich_vu: serviceData
            })
        })
        .then(res => res.json())
        .then(res => {
            if (timeConflictNotice && submitBookingBtn) {
                if (!res.valid) {
                    timeConflictNotice.classList.remove('d-none');
                    timeConflictNotice.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i> ${res.message}`;
                    submitBookingBtn.disabled = true;
                } else {
                    timeConflictNotice.classList.add('d-none');
                    timeConflictNotice.innerHTML = '';
                    submitBookingBtn.disabled = false;
                }
            }

            // Cập nhật số tiền hiển thị
            if (res.valid) {
                if (displayPitchPrice) displayPitchPrice.innerText = formatVND(res.pitch_total);
                if (displayPitchDuration) displayPitchDuration.innerText = `(${res.hours} giờ / ${res.duration_minutes} phút)`;
                if (displayServiceTotal) displayServiceTotal.innerText = formatVND(res.services_total);
                if (displayGrandTotal) displayGrandTotal.innerText = formatVND(res.grand_total);
            }
        })
        .catch(err => console.error('Error calculating price:', err));
    }

    function collectSelectedServices() {
        const services = [];
        const checkboxes = document.querySelectorAll('.service-checkbox:checked');
        checkboxes.forEach(cb => {
            const id = cb.value;
            const qtyInput = document.getElementById(`service_qty_${id}`);
            const qty = qtyInput ? parseInt(qtyInput.value) || 1 : 1;
            services.push({ id: id, quantity: qty });
        });
        return services;
    }

    // Lắng nghe sự kiện
    if (pitchSelect) pitchSelect.addEventListener('change', loadBookedSlots);
    if (dateInput) dateInput.addEventListener('change', loadBookedSlots);
    if (startTimeInput) startTimeInput.addEventListener('change', checkCurrentSelectionConflict);
    if (endTimeInput) endTimeInput.addEventListener('change', checkCurrentSelectionConflict);

    // Dịch vụ bổ sung
    const serviceCheckboxes = document.querySelectorAll('.service-checkbox');
    serviceCheckboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            const id = this.value;
            const qtyInput = document.getElementById(`service_qty_${id}`);
            if (qtyInput) {
                qtyInput.disabled = !this.checked;
                if (!this.checked) qtyInput.value = 1;
            }
            checkCurrentSelectionConflict();
        });
    });

    const serviceQtyInputs = document.querySelectorAll('.service-qty-input');
    serviceQtyInputs.forEach(input => {
        input.addEventListener('input', checkCurrentSelectionConflict);
    });

    // Chạy khởi tạo nếu trang đã có giá trị sẵn
    if (pitchSelect && pitchSelect.value && dateInput && dateInput.value) {
        loadBookedSlots();
    }
});

