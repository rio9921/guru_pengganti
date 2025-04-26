</div>
            </main>
            
            <footer class="admin-footer">
                <div class="footer-content">
                    <p>&copy; <?php echo date('Y'); ?> GuruSinergi. Hak Cipta Dilindungi.</p>
                    <p>Versi <?php echo config('version'); ?></p>
                </div>
            </footer>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="<?php echo asset('js/admin-script.js'); ?>"></script>
    
    <!-- JavaScript tambahan jika ada -->
    <?php if (isset($extra_js)): ?>
        <?php echo $extra_js; ?>
    <?php endif; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle sidebar
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const adminLayout = document.querySelector('.admin-layout');
        
        if (sidebarToggle && adminLayout) {
            sidebarToggle.addEventListener('click', function() {
                adminLayout.classList.toggle('sidebar-collapsed');
            });
        }
        
        // Toggle notification dropdown
        const notificationToggle = document.getElementById('notification-toggle');
        const notificationDropdown = document.getElementById('notification-dropdown');
        
        if (notificationToggle && notificationDropdown) {
            notificationToggle.addEventListener('click', function() {
                notificationDropdown.classList.toggle('active');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!notificationDropdown.contains(event.target) && !notificationToggle.contains(event.target)) {
                    notificationDropdown.classList.remove('active');
                }
            });
        }
        
        // Toggle user dropdown
        const userDropdownToggle = document.getElementById('user-dropdown-toggle');
        const userDropdownMenu = document.getElementById('user-dropdown-menu');
        
        if (userDropdownToggle && userDropdownMenu) {
            userDropdownToggle.addEventListener('click', function() {
                userDropdownMenu.classList.toggle('active');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!userDropdownMenu.contains(event.target) && !userDropdownToggle.contains(event.target)) {
                    userDropdownMenu.classList.remove('active');
                }
            });
        }
    });
    </script>
</body>
</html>