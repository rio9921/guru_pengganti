</div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <div class="footer-logo">Guru<span class="highlight">Sinergi</span></div>
                    <p>Platform yang mendukung guru perempuan di Indonesia untuk menyeimbangkan tanggung jawab keluarga dan pengembangan karir profesional.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <div class="footer-column">
                    <h3>Tautan Penting</h3>
                    <ul class="footer-links">
                        <li><a href="<?php echo url(); ?>">Beranda</a></li>
                        <li><a href="<?php echo url('assignments/browse.php'); ?>">Penugasan</a></li>
                        <li><a href="<?php echo url('teachers/browse.php'); ?>">Cari Guru</a></li>
                        <li><a href="<?php echo url('materials.php'); ?>">Materi Pembelajaran</a></li>
                        <li><a href="<?php echo url('about.php'); ?>">Tentang Kami</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Bantuan</h3>
                    <ul class="footer-links">
                        <li><a href="<?php echo url('faq.php'); ?>">FAQ</a></li>
                        <li><a href="<?php echo url('terms.php'); ?>">Syarat & Ketentuan</a></li>
                        <li><a href="<?php echo url('privacy.php'); ?>">Kebijakan Privasi</a></li>
                        <li><a href="<?php echo url('contact.php'); ?>">Kontak Kami</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Kontak</h3>
                    <div class="contact-info">
                        <i class="fas fa-map-marker-alt"></i>
                        <p>Jl. Umban Sari<br>Pekanbaru, 28265<br>Indonesia</p>
                    </div>
                    <div class="contact-info">
                        <i class="fas fa-envelope"></i>
                        <p><a href="mailto:<?php echo config('admin_email'); ?>"><?php echo config('admin_email'); ?></a></p>
                    </div>
                    <div class="contact-info">
                        <i class="fas fa-phone-alt"></i>
                        <p><a href="tel:<?php echo config('support_phone'); ?>"><?php echo config('support_phone'); ?></a></p>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> GuruSinergi. Hak Cipta Dilindungi.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="<?php echo asset('js/script.js'); ?>"></script>
    
    <!-- JavaScript tambahan jika ada -->
    <?php if (isset($extra_js)): ?>
        <?php echo $extra_js; ?>
    <?php endif; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle mobile menu
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const mainNav = document.getElementById('main-nav');
        
        if (mobileMenuToggle && mainNav) {
            mobileMenuToggle.addEventListener('click', function() {
                this.classList.toggle('active');
                mainNav.classList.toggle('active');
            });
        }
        
        // Toggle account dropdown
        const accountToggle = document.getElementById('account-toggle');
        const accountDropdown = document.getElementById('account-dropdown');
        
        if (accountToggle && accountDropdown) {
            accountToggle.addEventListener('click', function() {
                accountDropdown.classList.toggle('active');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!accountDropdown.contains(event.target) && !accountToggle.contains(event.target)) {
                    accountDropdown.classList.remove('active');
                }
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
    });
    </script>
</body>
</html>