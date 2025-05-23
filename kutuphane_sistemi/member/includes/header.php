<nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            📚 Kütüphane Üye Paneli
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">🏠 Ana Sayfa</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="search.php">🔍 Kitap Ara</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="history.php">📋 Geçmiş</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">👤 Profil</a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        👋 <?= $_SESSION['member_name'] ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php">👤 Profil</a></li>
                        <li><a class="dropdown-item" href="settings.php">⚙️ Ayarlar</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">🚪 Çıkış</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>