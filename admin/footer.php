<?php
/**
 * Footer giao diện Quản trị Admin
 * Dự án: BongDa16h
 */
?>
        </div> <!-- End .admin-content -->
    </div> <!-- End .admin-main -->
</div> <!-- End .admin-wrapper -->

<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Main App JS -->
<script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('adminSidebar');
        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('show');
            });
        }
    });
</script>
</body>
</html>

