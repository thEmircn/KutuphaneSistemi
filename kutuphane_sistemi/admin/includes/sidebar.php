<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Bildirim sayıları
$overdue_count = $pdo->query("SELECT COUNT(*) FROM odunc_islemleri WHERE durum = 'odunc' AND teslim_tarihi < CURDATE()")->fetchColumn();
$due_tomorrow_count = $pdo->query("SELECT COUNT(*) FROM odunc_islemleri WHERE durum = 'odunc' AND teslim_tarihi = DATE_ADD(CURDATE(), INTERVAL 1 DAY)")->fetchColumn();
$total_notifications = $overdue_count + $due_tomorrow_count;
?>

<style>
:root {
    --primary-color: #2c3e50;
    --secondary-color: #34495e;
    --accent-color: #3498db;
    --sidebar-bg: linear-gradient(180deg, #2c3e50 0%, #34495e 50%, #3c4f66 100%);
    --text-primary: #ffffff;
    --text-secondary: #bdc3c7;
    --text-muted: #95a5a6;
    --border-color: rgba(255, 255, 255, 0.1);
    --hover-bg: rgba(255, 255, 255, 0.08);
    --active-bg: rgba(52, 152, 219, 0.15);
    --shadow-light: rgba(0, 0, 0, 0.1);
    --shadow-medium: rgba(0, 0, 0, 0.2);
    --border-radius: 8px;
}

#sidebarMenu {
    background: var(--sidebar-bg);
    backdrop-filter: blur(10px);
    border-right: 1px solid var(--border-color);
    box-shadow: 0 0 20px var(--shadow-medium);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: fixed;
    height: 100vh;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--text-muted) transparent;
    z-index: 1000;
}

#sidebarMenu::-webkit-scrollbar {
    width: 4px;
}

#sidebarMenu::-webkit-scrollbar-track {
    background: transparent;
}

#sidebarMenu::-webkit-scrollbar-thumb {
    background: var(--text-muted);
    border-radius: 2px;
}

.sidebar-brand {
    padding: 1.5rem;
    text-align: center;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 1rem;
    display: block !important;
}

.sidebar-brand h4 {
    color: var(--text-primary) !important;
    font-weight: 600;
    margin: 0;
    font-size: 1.2rem;
}

.nav-item {
    margin: 0.3rem 0.8rem;
    position: relative;
    overflow: hidden;
    opacity: 0;
    transform: translateX(-20px);
    animation: slideIn 0.6s ease forwards;
}

.nav-item:nth-child(1) { animation-delay: 0.1s; }
.nav-item:nth-child(2) { animation-delay: 0.15s; }
.nav-item:nth-child(3) { animation-delay: 0.2s; }
.nav-item:nth-child(4) { animation-delay: 0.25s; }
.nav-item:nth-child(5) { animation-delay: 0.3s; }
.nav-item:nth-child(6) { animation-delay: 0.35s; }
.nav-item:nth-child(7) { animation-delay: 0.4s; }
.nav-item:nth-child(8) { animation-delay: 0.45s; }
.nav-item:nth-child(9) { animation-delay: 0.5s; }
.nav-item:nth-child(10) { animation-delay: 0.55s; }
.nav-item:nth-child(11) { animation-delay: 0.6s; }
.nav-item:nth-child(12) { animation-delay: 0.65s; }
.nav-item:nth-child(13) { animation-delay: 0.7s; }
.nav-item:nth-child(14) { animation-delay: 0.75s; }
.nav-item:nth-child(15) { animation-delay: 0.8s; }

@keyframes slideIn {
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.nav-link {
    color: var(--text-secondary) !important;
    padding: 0.9rem 1.2rem;
    border-radius: var(--border-radius);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    display: flex;
    align-items: center;
    font-weight: 500;
    border: 1px solid transparent;
    overflow: hidden;
    text-decoration: none;
}

.nav-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
    transition: left 0.6s ease;
}

.nav-link:hover::before {
    left: 100%;
}

.nav-link::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    width: 3px;
    height: 0;
    background: var(--accent-color);
    transform: translateY(-50%);
    transition: height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 0 2px 2px 0;
}

.nav-link:hover {
    color: var(--text-primary) !important;
    background: var(--hover-bg);
    border-color: var(--border-color);
    box-shadow: 0 4px 12px var(--shadow-light);
    transform: translateX(6px) scale(1.01);
}

.nav-link:hover::after {
    height: 50%;
}

.nav-link.active {
    color: var(--text-primary) !important;
    background: var(--active-bg);
    border-color: rgba(52, 152, 219, 0.3);
    box-shadow: 0 6px 15px var(--shadow-medium);
    transform: translateX(4px);
}

.nav-link.active::after {
    height: 70%;
    background: var(--accent-color);
}

.nav-link span:first-child {
    font-size: 1.1rem;
    margin-right: 0.8rem;
    transition: all 0.3s ease;
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
}

.nav-link:hover span:first-child {
    transform: scale(1.1);
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
}

.nav-link.active span:first-child {
    transform: scale(1.05);
    filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.4));
}

.badge {
    background: #e74c3c !important;
    border: 1px solid rgba(255, 255, 255, 0.2);
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    animation: subtlePulse 3s infinite;
    box-shadow: 0 2px 6px rgba(231, 76, 60, 0.3);
}

@keyframes subtlePulse {
    0%, 100% { 
        transform: scale(1);
        opacity: 1;
    }
    50% { 
        transform: scale(1.05);
        opacity: 0.9;
    }
}

.nav-item:nth-child(odd) .nav-link:hover {
    background: rgba(255, 255, 255, 0.06);
}

.nav-item:nth-child(even) .nav-link:hover {
    background: rgba(255, 255, 255, 0.08);
}

.mobile-toggle {
    display: none;
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 1050;
    background: var(--primary-color);
    border: none;
    border-radius: 8px;
    width: 45px;
    height: 45px;
    color: white;
    font-size: 1.1rem;
    box-shadow: 0 2px 8px var(--shadow-medium);
    transition: all 0.2s ease;
}

.mobile-toggle:hover {
    background: var(--secondary-color);
    transform: scale(1.05);
}

@media (max-width: 768px) {
    .mobile-toggle {
        display: block;
    }
    
    #sidebarMenu {
        transform: translateX(-100%);
    }
    
    #sidebarMenu.show {
        transform: translateX(0);
    }
}
</style>

<button class="mobile-toggle" onclick="toggleSidebar()">☰</button>

<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse" style="margin-top: 1px;">
    
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                    <span class="me-2">📊</span>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_page == 'search.php' ? 'active' : '' ?>" href="search.php">
                    <span class="me-2">🔍</span>
                    Arama
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_page == 'books.php' ? 'active' : '' ?>" href="books.php">
                    <span class="me-2">📚</span>
                    Kitaplar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_page == 'members.php' ? 'active' : '' ?>" href="members.php">
                    <span class="me-2">👥</span>
                    Üyeler
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_page == 'loans.php' ? 'active' : '' ?>" href="loans.php">
                    <span class="me-2">📖</span>
                    Ödünç İşlemleri
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_page == 'authors.php' ? 'active' : '' ?>" href="authors.php">
                    <span class="me-2">✍️</span>
                    Yazarlar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_page == 'categories.php' ? 'active' : '' ?>" href="categories.php">
                    <span class="me-2">🏷️</span>
                    Kategoriler
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_page == 'notifications.php' ? 'active' : '' ?>" href="notifications.php">
                    <span class="me-2">🔔</span>
                    Bildirimler
                    <?php if ($total_notifications > 0): ?>
                        <span class="badge bg-danger ms-2"><?= $total_notifications ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_page == 'analytics.php' ? 'active' : '' ?>" href="analytics.php">
                    <span class="me-2">📈</span>
                    Analitik
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_page == 'reports.php' ? 'active' : '' ?>" href="reports.php">
                    <span class="me-2">📊</span>
                    Raporlar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_page == 'staff.php' ? 'active' : '' ?>" href="staff.php">
                    <span class="me-2">👨‍💼</span>
                    Personel
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_page == 'fees.php' ? 'active' : '' ?>" href="fees.php">
                    <span class="me-2">💰</span>
                    Ücretler
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_page == 'member_panel.php' ? 'active' : '' ?>" href="../member/">
                    <span class="me-2">👤</span>
                    Üye Paneli
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_page == 'backup.php' ? 'active' : '' ?>" href="backup.php">
                    <span class="me-2">💾</span>
                    Yedekleme
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_page == 'settings.php' ? 'active' : '' ?>" href="settings.php">
                    <span class="me-2">⚙️</span>
                    Ayarlar
                </a>
            </li>
        </ul>
    </div>
</nav>

<script>
// Mobile sidebar toggle
function toggleSidebar() {
    const sidebar = document.getElementById('sidebarMenu');
    sidebar.classList.toggle('show');
}

// Klavye navigasyonu
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.getElementById('sidebarMenu').classList.remove('show');
    }
});

// Click efekti
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function(e) {
        // Küçük bir vibration efekti (mobil cihazlarda)
        if (navigator.vibrate) {
            navigator.vibrate(50);
        }
        
        // Link'e gitme animasyonu
        this.style.transform = 'scale(0.95)';
        setTimeout(() => {
            this.style.transform = '';
        }, 150);
    });
});

// Scroll efekti
const sidebar = document.getElementById('sidebarMenu');
if (sidebar) {
    sidebar.addEventListener('scroll', function() {
        const scrollPercent = this.scrollTop / (this.scrollHeight - this.clientHeight);
        const opacity = Math.min(0.95, 0.8 + (scrollPercent * 0.15));
        this.style.background = `linear-gradient(180deg, 
            rgba(44, 62, 80, ${opacity}) 0%, 
            rgba(52, 73, 94, ${opacity + 0.05}) 50%, 
            rgba(60, 79, 102, ${opacity + 0.1}) 100%)`;
    });
}

// Sayfa yüklendiğinde smooth giriş
window.addEventListener('load', function() {
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity 0.5s ease';
    setTimeout(() => {
        document.body.style.opacity = '1';
    }, 100);
});
</script>