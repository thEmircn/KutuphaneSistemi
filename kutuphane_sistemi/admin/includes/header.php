<?php
// Kullanıcı bilgilerini al
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_initials = '';
$name_parts = explode(' ', $admin_name);
foreach($name_parts as $part) {
    $admin_initials .= strtoupper(substr($part, 0, 1));
}
$admin_initials = substr($admin_initials, 0, 2);

// Bildirim sayıları
?>

<style>
:root {
    --navbar-bg: rgba(44, 62, 80, 0.95);
    --navbar-blur: blur(20px);
    --text-primary: #ffffff;
    --text-secondary: #bdc3c7;
    --accent-color: #3498db;
    --hover-bg: rgba(255, 255, 255, 0.1);
    --dropdown-bg: rgba(52, 73, 94, 0.98);
    --shadow-light: rgba(0, 0, 0, 0.1);
    --shadow-medium: rgba(0, 0, 0, 0.2);
    --border-radius: 12px;
}

.navbar {
    background: var(--navbar-bg) !important;
    backdrop-filter: var(--navbar-blur);
    -webkit-backdrop-filter: var(--navbar-blur);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 4px 20px var(--shadow-medium);
    padding: 0.8rem 0;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    
}

.navbar.scrolled {
    background: rgba(44, 62, 80, 0.98) !important;
    box-shadow: 0 6px 30px rgba(0, 0, 0, 0.3);
}

.navbar-brand {
    font-weight: 700;
    font-size: 1.4rem;
    color: var(--text-primary) !important;
    text-decoration: none;
    position: relative;
    transition: all 0.3s ease;
    padding: 0.5rem 1rem;
    border-radius: var(--border-radius);
}

.navbar-brand::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    border-radius: var(--border-radius);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.navbar-brand:hover::before {
    opacity: 1;
}

.navbar-brand:hover {
    transform: translateY(-2px);
    text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
}

.navbar-brand span {
    background: linear-gradient(45deg, #3498db, #2ecc71);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.user-profile {
    position: relative;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3498db, #2ecc71);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 1.1rem;
    margin-right: 0.8rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
}

.user-avatar:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(52, 152, 219, 0.5);
}

.nav-link.dropdown-toggle {
    color: var(--text-primary) !important;
    font-weight: 500;
    padding: 0.7rem 1.2rem;
    border-radius: var(--border-radius);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    border: 1px solid transparent;
    position: relative;
    overflow: hidden;
}

.nav-link.dropdown-toggle::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transition: left 0.6s ease;
}

.nav-link.dropdown-toggle:hover::before {
    left: 100%;
}

.nav-link.dropdown-toggle:hover {
    background: var(--hover-bg);
    border-color: rgba(255, 255, 255, 0.2);
    transform: translateY(-1px);
    box-shadow: 0 4px 15px var(--shadow-light);
}

.nav-link.dropdown-toggle::after {
    margin-left: 0.8rem;
    transition: transform 0.3s ease;
}

.nav-link.dropdown-toggle:hover::after {
    transform: rotate(180deg);
}

.dropdown-menu {
    background: var(--dropdown-bg) !important;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: var(--border-radius);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    padding: 0.5rem 0;
    margin-top: 0.5rem;
    min-width: 200px;
    animation: dropdownSlide 0.3s ease;
}

@keyframes dropdownSlide {
    from {
        opacity: 0;
        transform: translateY(-10px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.dropdown-item {
    color: var(--text-secondary) !important;
    padding: 0.7rem 1.2rem;
    font-weight: 500;
    transition: all 0.3s ease;
    border-radius: 8px;
    margin: 0.2rem 0.5rem;
    position: relative;
    overflow: hidden;
}

.dropdown-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
    transition: left 0.4s ease;
}

.dropdown-item:hover::before {
    left: 100%;
}

.dropdown-item:hover {
    background: var(--hover-bg) !important;
    color: var(--text-primary) !important;
    transform: translateX(5px);
}

.dropdown-item.logout-item {
    color: #e74c3c !important;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    margin-top: 0.5rem;
    padding-top: 0.8rem;
}

.dropdown-item.logout-item:hover {
    background: rgba(231, 76, 60, 0.1) !important;
    color: #ff6b6b !important;
}

.dropdown-divider {
    border-color: rgba(255, 255, 255, 0.1);
    margin: 0.5rem 0;
}

.nav-icon {
    margin-right: 0.5rem;
    font-size: 1rem;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
    border-radius: 50px;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: pulse 2s infinite;
    box-shadow: 0 2px 8px rgba(231, 76, 60, 0.4);
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

@media (max-width: 768px) {
    .navbar-brand {
        font-size: 1.2rem;
    }
    
    .user-avatar {
        width: 35px;
        height: 35px;
        font-size: 1rem;
    }
}
</style>
<div style="margin:30px">
<nav class="navbar navbar-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            📚 <span>Kütüphane Yönetimi</span>
        </a>
        
        <div class="navbar-nav ms-auto">
            <div class="nav-item dropdown user-profile">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar">
                        <?= $admin_initials ?>
                    </div>
                    <span><?= $admin_name ?></span>
                    
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="member_detail.php">
                            <span class="nav-icon">👤</span>
                            Profil Ayarları
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="notifications.php">
                            <span class="nav-icon">🔔</span>
                            Bildirimler
                           
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="settings.php">
                            <span class="nav-icon">⚙️</span>
                            Sistem Ayarları
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item logout-item" href="../logout.php">
                            <span class="nav-icon">🚪</span>
                            Güvenli Çıkış
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
</div>
<script>
// Scroll efekti
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

// Kullanıcı avatarı click efekti
document.querySelector('.user-avatar')?.addEventListener('click', function() {
    this.style.transform = 'scale(0.9)';
    setTimeout(() => {
        this.style.transform = '';
    }, 150);
});

// Dropdown click efekti
const dropdownToggle = document.querySelector('.dropdown-toggle');
if (dropdownToggle) {
    dropdownToggle.addEventListener('click', function() {
        if (navigator.vibrate) {
            navigator.vibrate(30);
        }
    });
}

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const dropdowns = document.querySelectorAll('.dropdown-menu.show');
        dropdowns.forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    }
});

// İlk yüklemede smooth giriş
window.addEventListener('load', function() {
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        navbar.style.transform = 'translateY(-100%)';
        setTimeout(() => {
            navbar.style.transition = 'transform 0.5s ease';
            navbar.style.transform = 'translateY(0)';
        }, 100);
    }
});
</script>
