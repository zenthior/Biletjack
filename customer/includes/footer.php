

<script src="../js/dashboard.js"></script>
    <script>
        // Mobile navigation item click handler
        document.addEventListener('DOMContentLoaded', function() {
            const mobileNavItems = document.querySelectorAll('.mobile-nav-item');
            
            mobileNavItems.forEach(item => {
                item.addEventListener('click', function() {
                    // Close sidebar when navigation item is clicked
                    if (typeof closeSidebar === 'function') {
                        closeSidebar();
                    }
                });
            });
        });
    </script>
</body>
</html>