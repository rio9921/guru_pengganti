/**
 * GuruSinergi - Main JavaScript
 * 
 * File JavaScript utama untuk platform guru pengganti
 */

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
        accountToggle.addEventListener('click', function(e) {
            e.stopPropagation();
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
        notificationToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!notificationDropdown.contains(event.target) && !notificationToggle.contains(event.target)) {
                notificationDropdown.classList.remove('active');
            }
        });
    }
    
    // Date range validation for assignment form
    const tanggalMulai = document.getElementById('tanggal_mulai');
    const tanggalSelesai = document.getElementById('tanggal_selesai');
    
    if (tanggalMulai && tanggalSelesai) {
        // Set min date to today for tanggal_mulai
        const today = new Date();
        const yyyy = today.getFullYear();
        let mm = today.getMonth() + 1;
        let dd = today.getDate();
        
        if (dd < 10) dd = '0' + dd;
        if (mm < 10) mm = '0' + mm;
        
        const formattedToday = yyyy + '-' + mm + '-' + dd;
        tanggalMulai.setAttribute('min', formattedToday);
        
        // Update min date for tanggal_selesai when tanggal_mulai changes
        tanggalMulai.addEventListener('change', function() {
            tanggalSelesai.setAttribute('min', this.value);
            
            // If current end date is before new start date, update it
            if (tanggalSelesai.value && tanggalSelesai.value < this.value) {
                tanggalSelesai.value = this.value;
            }
        });
    }
    
    // Mark notification as read when clicked
    const notificationItems = document.querySelectorAll('.noti-item');
    
    if (notificationItems.length > 0) {
        notificationItems.forEach(item => {
            item.addEventListener('click', function() {
                const notificationId = this.getAttribute('data-id');
                if (notificationId && this.classList.contains('unread')) {
                    // Send AJAX request to mark as read
                    fetch('mark-notification-read.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'notification_id=' + notificationId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.classList.remove('unread');
                            
                            // Update notification count
                            const notiCount = document.querySelector('.noti-count');
                            if (notiCount) {
                                let count = parseInt(notiCount.textContent);
                                count--;
                                
                                if (count > 0) {
                                    notiCount.textContent = count;
                                } else {
                                    notiCount.style.display = 'none';
                                }
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                }
            });
        });
    }
    
    // Payment method selection
    const paymentMethods = document.querySelectorAll('.payment-method-item input[type="radio"]');
    
    if (paymentMethods.length > 0) {
        paymentMethods.forEach(method => {
            method.addEventListener('change', function() {
                // Remove active class from all items
                document.querySelectorAll('.payment-method-item').forEach(item => {
                    item.classList.remove('active');
                });
                
                // Add active class to selected item
                this.closest('.payment-method-item').classList.add('active');
            });
        });
        
        // Activate the first method by default
        paymentMethods[0].checked = true;
        paymentMethods[0].closest('.payment-method-item').classList.add('active');
    }
    
    // Form validation
    const forms = document.querySelectorAll('form[data-validate="true"]');
    
    if (forms.length > 0) {
        forms.forEach(form => {
            form.addEventListener('submit', function(event) {
                let isValid = true;
                
                // Get all required inputs
                const requiredInputs = form.querySelectorAll('[required]');
                
                requiredInputs.forEach(input => {
                    // Remove previous validation
                    input.classList.remove('is-invalid');
                    const feedbackElement = input.nextElementSibling;
                    if (feedbackElement && feedbackElement.classList.contains('invalid-feedback')) {
                        feedbackElement.remove();
                    }
                    
                    // Check if empty
                    if (!input.value.trim()) {
                        isValid = false;
                        input.classList.add('is-invalid');
                        
                        // Add feedback message
                        const feedback = document.createElement('div');
                        feedback.classList.add('invalid-feedback');
                        feedback.textContent = 'Field ini harus diisi.';
                        
                        input.parentNode.insertBefore(feedback, input.nextSibling);
                    }
                    
                    // Validate email
                    if (input.type === 'email' && input.value.trim()) {
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(input.value.trim())) {
                            isValid = false;
                            input.classList.add('is-invalid');
                            
                            // Add feedback message
                            const feedback = document.createElement('div');
                            feedback.classList.add('invalid-feedback');
                            feedback.textContent = 'Email tidak valid.';
                            
                            input.parentNode.insertBefore(feedback, input.nextSibling);
                        }
                    }
                    
                    // Validate password match
                    if (input.id === 'confirm_password') {
                        const password = document.getElementById('password');
                        if (password && input.value.trim() !== password.value.trim()) {
                            isValid = false;
                            input.classList.add('is-invalid');
                            
                            // Add feedback message
                            const feedback = document.createElement('div');
                            feedback.classList.add('invalid-feedback');
                            feedback.textContent = 'Password dan konfirmasi password tidak cocok.';
                            
                            input.parentNode.insertBefore(feedback, input.nextSibling);
                        }
                    }
                });
                
                if (!isValid) {
                    event.preventDefault();
                }
            });
        });
    }
    
    // File input preview
    const fileInputs = document.querySelectorAll('input[type="file"][data-preview]');
    
    if (fileInputs.length > 0) {
        fileInputs.forEach(input => {
            input.addEventListener('change', function() {
                const previewId = this.getAttribute('data-preview');
                const previewElement = document.getElementById(previewId);
                
                if (previewElement) {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            if (previewElement.tagName === 'IMG') {
                                previewElement.src = e.target.result;
                            } else {
                                previewElement.style.backgroundImage = `url(${e.target.result})`;
                            }
                        }
                        
                        reader.readAsDataURL(this.files[0]);
                    }
                }
            });
        });
    }
    
    // Rating stars
    const ratingInputs = document.querySelectorAll('.rating-input');
    
    if (ratingInputs.length > 0) {
        ratingInputs.forEach(input => {
            const stars = input.querySelectorAll('.rating-star');
            
            stars.forEach((star, index) => {
                star.addEventListener('click', function() {
                    const ratingValue = this.getAttribute('data-value');
                    const ratingField = input.querySelector('input[type="hidden"]');
                    
                    if (ratingField) {
                        ratingField.value = ratingValue;
                    }
                    
                    // Update stars
                    stars.forEach((s, i) => {
                        if (i < ratingValue) {
                            s.classList.add('active');
                            s.classList.remove('inactive');
                        } else {
                            s.classList.remove('active');
                            s.classList.add('inactive');
                        }
                    });
                });
            });
        });
    }
});