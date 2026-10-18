/**
 * JavaScript chung cho hệ thống BongDa16h
 */

document.addEventListener('DOMContentLoaded', function () {
    // Tự động ẩn alert sau 6 giây
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 6000);
    });

    // Xác nhận khi xóa hoặc thao tác nguy hiểm
    const confirmButtons = document.querySelectorAll('[data-confirm]');
    confirmButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            const message = this.getAttribute('data-confirm') || 'Bạn có chắc chắn muốn thực hiện thao tác này?';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });

    // Kích hoạt Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

/**
 * Định dạng số thành tiền tệ VNĐ hiển thị trên client
 */
function formatVND(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount) + ' đ';
}

