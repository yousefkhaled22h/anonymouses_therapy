<!-- includes/sidebar_toggle_script.php -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('dashboardSidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        
        if (toggleBtn && sidebar) {
            // Toggle Sidebar state
            toggleBtn.addEventListener('click', () => {
                const isMobile = window.innerWidth <= 992;
                const icon = document.getElementById('toggleIcon');
                
                if (isMobile) {
                    sidebar.classList.toggle('active');
                    const overlay = document.getElementById('sidebarOverlay');
                    if (overlay) overlay.classList.toggle('active');
                } else {
                    sidebar.classList.toggle('is-closed');
                    const wrapper = document.querySelector('.dashboard-wrapper');
                    if (wrapper) wrapper.classList.toggle('sidebar-closed');
                    
                    // Change icon
                    if (icon) {
                        if (sidebar.classList.contains('is-closed')) {
                            icon.className = 'fa-solid fa-arrow-right-long';
                        } else {
                            icon.className = 'fa-solid fa-bars-staggered';
                        }
                    }
                    
                    // Save preference
                    const isClosed = sidebar.classList.contains('is-closed');
                    localStorage.setItem('sidebarClosed', isClosed);
                }
            });
        }

        // Close mobile sidebar on overlay click
        const overlay = document.getElementById('sidebarOverlay');
        if (overlay && sidebar) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
        }

        // Load preference (Desktop only)
        if (window.innerWidth > 992 && localStorage.getItem('sidebarClosed') === 'true' && sidebar) {
            sidebar.classList.add('is-closed');
            const wrapper = document.querySelector('.dashboard-wrapper');
            if (wrapper) wrapper.classList.add('sidebar-closed');
            const icon = document.getElementById('toggleIcon');
            if (icon) icon.className = 'fa-solid fa-arrow-right-long';
        }
    });
</script>
