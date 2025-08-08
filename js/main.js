// ===== GARUDA INDONESIA WEBSITE MAIN JAVASCRIPT =====

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeAnimations();
    initializeNavigation();
    initializeFormValidations();
});

// Animation on scroll
function initializeAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-up');
            }
        });
    }, observerOptions);

    // Observe elements with animation classes
    document.querySelectorAll('.card, .booking-card, .hero-content').forEach(el => {
        observer.observe(el);
    });
}

// Navigation functionality
function initializeNavigation() {
    // Mobile menu toggle (for future mobile implementation)
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navMenu = document.querySelector('.nav-menu');
    
    if (mobileMenuBtn && navMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            navMenu.classList.toggle('active');
        });
    }

    // Active navigation link
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        const linkHref = link.getAttribute('href');
        if (linkHref === currentPage || (currentPage === '' && linkHref === 'index.php')) {
            link.classList.add('active');
        }
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Form validations
function initializeFormValidations() {
    // Real-time email validation
    document.querySelectorAll('input[type="email"]').forEach(input => {
        input.addEventListener('blur', function() {
            validateEmail(this);
        });
    });

    // Real-time password validation
    document.querySelectorAll('input[type="password"]').forEach(input => {
        if (input.name === 'password') {
            input.addEventListener('input', function() {
                validatePasswordStrength(this);
            });
        }
    });

    // Phone number formatting
    document.querySelectorAll('input[type="tel"]').forEach(input => {
        input.addEventListener('input', function() {
            formatPhoneNumber(this);
        });
    });
}

// Email validation function
function validateEmail(input) {
    const email = input.value;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (email && !emailRegex.test(email)) {
        showFieldError(input, 'Format email tidak valid');
        return false;
    } else {
        clearFieldError(input);
        return true;
    }
}

// Password strength validation
function validatePasswordStrength(input) {
    const password = input.value;
    let strength = 0;
    let message = '';
    let color = '';

    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;

    switch(strength) {
        case 0:
        case 1:
            message = 'Password sangat lemah';
            color = '#ef4444';
            break;
        case 2:
            message = 'Password lemah';
            color = '#f97316';
            break;
        case 3:
            message = 'Password sedang';
            color = '#eab308';
            break;
        case 4:
            message = 'Password kuat';
            color = '#22c55e';
            break;
        case 5:
            message = 'Password sangat kuat';
            color = '#16a34a';
            break;
    }

    const strengthIndicator = input.parentNode.querySelector('.password-strength') || 
                             createPasswordStrengthIndicator(input);
    
    strengthIndicator.innerHTML = `<span style="color: ${color};">${message}</span>`;
    strengthIndicator.style.display = password.length > 0 ? 'block' : 'none';
}

// Create password strength indicator
function createPasswordStrengthIndicator(input) {
    const indicator = document.createElement('div');
    indicator.className = 'password-strength';
    indicator.style.fontSize = '0.875rem';
    indicator.style.marginTop = '0.5rem';
    input.parentNode.appendChild(indicator);
    return indicator;
}

// Phone number formatting
function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '');
    
    // Convert international format to local
    if (value.startsWith('62')) {
        value = '0' + value.substring(2);
    }
    
    // Limit length
    if (value.length > 13) {
        value = value.substring(0, 13);
    }
    
    input.value = value;
}

// Show field error
function showFieldError(input, message) {
    clearFieldError(input);
    
    input.classList.add('error');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    input.parentNode.appendChild(errorDiv);
}

// Clear field error
function clearFieldError(input) {
    input.classList.remove('error');
    const existingError = input.parentNode.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }
}

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 10px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        max-width: 400px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    `;
    
    // Set background color based on type
    switch(type) {
        case 'success':
            notification.style.background = 'linear-gradient(135deg, #10b981, #059669)';
            break;
        case 'error':
            notification.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
            break;
        case 'warning':
            notification.style.background = 'linear-gradient(135deg, #f59e0b, #d97706)';
            break;
        default:
            notification.style.background = 'linear-gradient(135deg, #3b82f6, #2563eb)';
    }
    
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : type === 'warning' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: white; font-size: 1.2rem; cursor: pointer; margin-left: auto;">&times;</button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, 5000);
}

// Loading overlay
function showLoading() {
    const overlay = document.createElement('div');
    overlay.id = 'loadingOverlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(30, 58, 138, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        backdrop-filter: blur(5px);
    `;
    
    overlay.innerHTML = `
        <div style="text-align: center; color: white;">
            <img src="Assets/images/Logo(Vertikal).png" alt="Loading" style="height: 80px; animation: pulse 2s infinite;">
            <div style="margin-top: 1rem;">
                <div style="display: inline-block; width: 40px; height: 40px; border: 3px solid rgba(255,255,255,0.3); border-top: 3px solid white; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            </div>
            <p style="margin-top: 1rem; font-size: 1.1rem;">Memproses...</p>
        </div>
        <style>
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
    `;
    
    document.body.appendChild(overlay);
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.remove();
    }
}

// Confirm dialog
function confirmAction(message, callback) {
    const confirmed = confirm(message);
    if (confirmed && typeof callback === 'function') {
        callback();
    }
    return confirmed;
}

// Format currency (Indonesian Rupiah)
function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

// Format date
function formatDate(date, options = {}) {
    const defaultOptions = {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        locale: 'id-ID'
    };
    
    const formatOptions = { ...defaultOptions, ...options };
    return new Date(date).toLocaleDateString('id-ID', formatOptions);
}

// Booking card selection
function selectBookingCard(card, type) {
    // Remove selected class from all cards
    document.querySelectorAll('.booking-card').forEach(c => {
        c.classList.remove('selected');
    });
    
    // Add selected class to clicked card
    card.classList.add('selected');
    
    // Add some visual feedback
    card.style.transform = 'scale(1.02)';
    setTimeout(() => {
        card.style.transform = '';
    }, 200);
    
    return type;
}

// File upload preview
function handleFileUpload(input, previewContainer) {
    const file = input.files[0];
    if (!file) return;
    
    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
    if (!allowedTypes.includes(file.type)) {
        showNotification('Tipe file tidak didukung. Gunakan JPG, PNG, atau PDF.', 'error');
        input.value = '';
        return;
    }
    
    // Validate file size (5MB)
    if (file.size > 5 * 1024 * 1024) {
        showNotification('Ukuran file terlalu besar. Maksimal 5MB.', 'error');
        input.value = '';
        return;
    }
    
    // Show preview for images
    if (file.type.startsWith('image/') && previewContainer) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewContainer.innerHTML = `
                <img src="${e.target.result}" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 10px;">
                <p style="margin-top: 0.5rem; color: var(--dark-gray); font-size: 0.875rem;">${file.name}</p>
            `;
        };
        reader.readAsDataURL(file);
    } else if (previewContainer) {
        previewContainer.innerHTML = `
            <div style="padding: 2rem; text-align: center; border: 2px dashed var(--primary-blue); border-radius: 10px;">
                <i class="fas fa-file-pdf" style="font-size: 2rem; color: var(--primary-blue); margin-bottom: 0.5rem;"></i>
                <p style="margin: 0; color: var(--dark-gray);">${file.name}</p>
            </div>
        `;
    }
}

// Initialize tooltips (if needed)
function initializeTooltips() {
    document.querySelectorAll('[data-tooltip]').forEach(element => {
        element.addEventListener('mouseenter', function() {
            showTooltip(this, this.dataset.tooltip);
        });
        
        element.addEventListener('mouseleave', function() {
            hideTooltip();
        });
    });
}

// Global error handler
window.addEventListener('error', function(e) {
    console.error('JavaScript Error:', e.error);
    // Could send error to logging service here
});

// Prevent form double submission
document.addEventListener('submit', function(e) {
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
    
    if (submitBtn && !submitBtn.disabled) {
        submitBtn.disabled = true;
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
        
        // Re-enable after 3 seconds as fallback
        setTimeout(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }, 3000);
    }
});
