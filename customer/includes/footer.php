<!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle d-lg-none" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <script src="../js/dashboard.js"></script>
    <script>
        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.querySelector('.modern-sidebar');
            sidebar.classList.toggle('active');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.modern-sidebar');
            const toggleBtn = document.querySelector('.mobile-menu-toggle');
            
            if (window.innerWidth <= 1024) {
                if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.querySelector('.modern-sidebar');
            if (window.innerWidth > 1024) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>