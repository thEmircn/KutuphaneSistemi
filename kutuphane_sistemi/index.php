<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kütüphane Yönetim Sistemi</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --text-light: #ecf0f1;
            --text-dark: #2c3e50;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-success: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --shadow-light: rgba(0, 0, 0, 0.1);
            --shadow-medium: rgba(0, 0, 0, 0.2);
            --shadow-heavy: rgba(0, 0, 0, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Navbar */
        .navbar {
            background: rgba(44, 62, 80, 0.95) !important;
            backdrop-filter: blur(20px);
            box-shadow: 0 2px 20px var(--shadow-medium);
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            background: rgba(44, 62, 80, 0.98) !important;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
        }

        .navbar-nav .nav-link {
            color: var(--text-light) !important;
            font-weight: 500;
            margin: 0 0.5rem;
            padding: 0.5rem 1rem !important;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .auth-buttons .btn {
            margin: 0 0.25rem;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-login {
            background: transparent;
            border: 2px solid var(--secondary-color);
            color: var(--secondary-color);
        }

        .btn-login:hover {
            background: var(--secondary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }

        .btn-register {
            background: var(--gradient-secondary);
            border: none;
            color: white;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(240, 147, 251, 0.4);
        }

        /* Hero Section */
        .hero {
            background: var(--gradient-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="a"><stop offset="0%" stop-color="rgba(255,255,255,0.1)"/><stop offset="100%" stop-color="rgba(255,255,255,0)"/></radialGradient></defs><circle cx="300" cy="200" r="100" fill="url(%23a)"/><circle cx="700" cy="800" r="150" fill="url(%23a)"/><circle cx="100" cy="600" r="80" fill="url(%23a)"/></svg>');
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .hero-content {
            z-index: 2;
            position: relative;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 1.5rem;
            text-shadow: 0 4px 8px var(--shadow-medium);
            animation: slideInUp 1s ease;
        }

        .hero p {
            font-size: 1.2rem;
            color: var(--text-light);
            margin-bottom: 2rem;
            animation: slideInUp 1s ease 0.2s both;
        }

        .hero-buttons {
            animation: slideInUp 1s ease 0.4s both;
        }

        .hero-buttons .btn {
            margin: 0.5rem;
            padding: 0.8rem 2rem;
            font-size: 1.1rem;
            border-radius: 30px;
            transition: all 0.3s ease;
        }

        .btn-hero-primary {
            background: white;
            color: var(--primary-color);
            border: none;
            font-weight: 600;
        }

        .btn-hero-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.3);
        }

        .btn-hero-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-hero-secondary:hover {
            background: white;
            color: var(--primary-color);
            transform: translateY(-3px);
        }

        @keyframes slideInUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Features Section */
        .features {
            padding: 5rem 0;
            background: #f8f9fa;
        }

        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 10px 30px var(--shadow-light);
            transition: all 0.3s ease;
            margin-bottom: 2rem;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px var(--shadow-medium);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }

        .feature-card h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        /* About Section */
        .about {
            padding: 5rem 0;
            background: white;
        }

        .about-content {
            display: flex;
            align-items: center;
            gap: 3rem;
        }

        .about-text h2 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .about-text p {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        .about-image {
            background: var(--gradient-success);
            border-radius: 20px;
            padding: 3rem;
            text-align: center;
            color: white;
            font-size: 5rem;
        }

        /* Stats Section */
        .stats {
            background: var(--gradient-primary);
            padding: 3rem 0;
            color: white;
        }

        .stat-item {
            text-align: center;
            margin-bottom: 2rem;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            display: block;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Contact Section */
        .contact {
            padding: 5rem 0;
            background: #f8f9fa;
        }

        .contact h2 {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 3rem;
            font-weight: 700;
        }

        .contact-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px var(--shadow-light);
            margin-bottom: 2rem;
        }

        .contact-form .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.8rem 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .contact-form .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .contact-form .btn {
            background: var(--gradient-secondary);
            border: none;
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .contact-form .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(240, 147, 251, 0.4);
        }

        .contact-info {
            padding: 2rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            color: var(--text-dark);
        }

        .contact-item i {
            font-size: 1.5rem;
            margin-right: 1rem;
            color: var(--secondary-color);
            width: 30px;
        }

        /* Footer */
        .footer {
            background: var(--primary-color);
            color: white;
            padding: 2rem 0;
            text-align: center;
        }

        .footer p {
            margin: 0;
            opacity: 0.8;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .about-content {
                flex-direction: column;
                text-align: center;
            }
            
            .auth-buttons {
                margin-top: 1rem;
            }
            
            .navbar-nav {
                text-align: center;
                margin-top: 1rem;
            }
        }

        /* Scroll animations */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Modal Styles */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px var(--shadow-heavy);
        }

        .modal-header {
            background: var(--gradient-primary);
            color: white;
            border-bottom: none;
            border-radius: 20px 20px 0 0;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.8rem 1rem;
            margin-bottom: 1rem;
        }

        .modal .btn-primary {
            background: var(--gradient-secondary);
            border: none;
            border-radius: 25px;
            padding: 0.8rem 2rem;
            font-weight: 600;
            width: 100%;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">📚 KütüphaneOS</a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Özellikler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">Hakkımızda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">İletişim</a>
                    </li>
                </ul>
                
                <div class="auth-buttons">
                <a href="member/index.php"> <button class="btn btn-login">
                        Giriş Yap
                    </button></a>
                    <a href="register.php"> <button class="btn btn-register">
                        Kayıt Ol
                    </button></a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <h1>Modern Kütüphane Yönetimi</h1>
                    <p class="lead">Kitaplarınızı, üyelerinizi ve tüm kütüphane işlemlerinizi tek platformda yönetin. Gelişmiş analitik ve raporlama özellikleri ile kütüphanenizi dijital çağa taşıyın.</p>
                    <div class="hero-buttons">
                        <button class="btn btn-hero-primary btn-lg" data-bs-toggle="modal" data-bs-target="#registerModal">
                            Ücretsiz Başla
                        </button>
                        <button class="btn btn-hero-secondary btn-lg" onclick="document.getElementById('features').scrollIntoView({behavior: 'smooth'})">
                            Özellikleri Keşfet
                        </button>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div style="font-size: 15rem; color: rgba(255,255,255,0.3); animation: float 6s ease-in-out infinite;">
                        📚
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-12">
                    <h2 class="display-4 font-weight-bold text-primary mb-3 fade-in">Özellikler</h2>
                    <p class="lead text-muted fade-in">Kütüphanenizi yönetmek hiç bu kadar kolay olmamıştı</p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <span class="feature-icon">📖</span>
                        <h3>Kitap Yönetimi</h3>
                        <p>Kitap ekleme, düzenleme, kategorilendirme ve stok takibi. QR kod ile hızlı kitap arama ve kayıt işlemleri.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <span class="feature-icon">👥</span>
                        <h3>Üye Yönetimi</h3>
                        <p>Kapsamlı üye profilleri, üyelik durumu takibi, ödünç alma geçmişi ve otomatik bildirim sistemi.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <span class="feature-icon">📊</span>
                        <h3>Analitik & Raporlar</h3>
                        <p>Detaylı istatistikler, grafiksel raporlar, kullanım analizi ve performans takibi.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <span class="feature-icon">🔔</span>
                        <h3>Otomatik Bildirimler</h3>
                        <p>Teslim tarihi hatırlatmaları, geciken kitap bildirimleri ve üye durumu güncellemeleri.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <span class="feature-icon">💾</span>
                        <h3>Güvenli Yedekleme</h3>
                        <p>Otomatik veri yedekleme, güvenli veri saklama ve hızlı geri yükleme işlemleri.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <span class="feature-icon">📱</span>
                        <h3>Responsive Tasarım</h3>
                        <p>Masaüstü, tablet ve mobil cihazlarda mükemmel uyumlu, her yerden erişilebilir arayüz.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item fade-in">
                        <span class="stat-number" data-count="50000">0</span>
                        <span class="stat-label">Kayıtlı Kitap</span>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item fade-in">
                        <span class="stat-number" data-count="10000">0</span>
                        <span class="stat-label">Aktif Üye</span>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item fade-in">
                        <span class="stat-number" data-count="500">0</span>
                        <span class="stat-label">Kütüphane</span>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item fade-in">
                        <span class="stat-number" data-count="99">0</span>
                        <span class="stat-label">% Memnuniyet</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about">
        <div class="container">
            <div class="row about-content">
                <div class="col-lg-6 about-text fade-in">
                    <h2>Hakkımızda</h2>
                    <p>KütüphaneOS, modern kütüphanelerin ihtiyaçlarını karşılamak için geliştirilmiş kapsamlı bir yönetim sistemidir. 2020 yılından beri binlerce kütüphaneye hizmet veriyoruz.</p>
                    <p>Misyonumuz, kütüphanelerin dijital dönüşümünü desteklemek ve kullanıcı deneyimini en üst seviyeye çıkarmaktır. Sürekli gelişen teknoloji ile birlikte sistemimizi güncel tutarak, size en iyi hizmeti sunmaya devam ediyoruz.</p>
                    <p><strong>Vizyonumuz:</strong> Dünya çapında tüm kütüphanelerin dijitalleşmesine öncülük etmek ve bilgiye erişimi demokratikleştirmek.</p>
                </div>
                <div class="col-lg-6 text-center fade-in">
                    <div class="about-image">
                        🏛️
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact">
        <div class="container">
            <h2 class="fade-in">İletişim</h2>
            <div class="row">
                <div class="col-lg-8">
                    <div class="contact-card fade-in">
                        <form class="contact-form">
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="text" class="form-control" placeholder="Adınız" required>
                                </div>
                                <div class="col-md-6">
                                    <input type="email" class="form-control" placeholder="E-posta Adresiniz" required>
                                </div>
                            </div>
                            <input type="text" class="form-control" placeholder="Konu" required>
                            <textarea class="form-control" rows="5" placeholder="Mesajınız" required></textarea>
                            <button type="submit" class="btn">Mesaj Gönder</button>
                        </form>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="contact-card fade-in">
                        <div class="contact-info">
                            <div class="contact-item">
                                <span>📍</span>
                                <div>
                                    <strong>Adres</strong><br>
                                    Teknoloji Caddesi No:123<br>
                                    Ankara, Türkiye
                                </div>
                            </div>
                            <div class="contact-item">
                                <span>📞</span>
                                <div>
                                    <strong>Telefon</strong><br>
                                    +90 312 123 4567
                                </div>
                            </div>
                            <div class="contact-item">
                                <span>✉️</span>
                                <div>
                                    <strong>E-posta</strong><br>
                                    info@kutuphaneos.com
                                </div>
                            </div>
                            <div class="contact-item">
                                <span>🕒</span>
                                <div>
                                    <strong>Çalışma Saatleri</strong><br>
                                    Pzt-Cum: 09:00 - 18:00
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 KütüphaneOS. Tüm hakları saklıdır. ❤️ ile geliştirildi.</p>
        </div>
    </footer>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Giriş Yap</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">E-posta</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Şifre</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="remember">
                            <label class="form-check-label">Beni hatırla</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Giriş Yap</button>
                    </form>
                    <div class="text-center mt-3">
                        <small>Hesabınız yok mu? <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal" data-bs-dismiss="modal">Kaydolun</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kaydol</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="register.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Ad Soyad</label>
                            <input type="text" class="form-control" name="fullname" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">E-posta</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Şifre</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Şifre Tekrar</label>
                            <input type="password" class="form-control" name="password_confirm" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" required>
                            <label class="form-check-label">Kullanım şartlarını kabul ediyorum</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Kaydol</button>
                    </form>
                    <div class="text-center mt-3">
                        <small>Zaten hesabınız var mı? <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">Giriş yapın</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scrolling for navigation links
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

        // Fade in animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        // Observe all fade-in elements
        document.querySelectorAll('.fade-in').forEach(el => {
            observer.observe(el);
        });

        // Counter animation for stats
        function animateCounter(element) {
            const target = parseInt(element.getAttribute('data-count'));
            const duration = 2000;
            const start = 0;
            const increment = target / (duration / 16);
            let current = start;

            const timer = setInterval(() => {
                current += increment;
                element.textContent = Math.floor(current);
                
                if (current >= target) {
                    element.textContent = target;
                    clearInterval(timer);
                }
            }, 16);
        }

        // Stats counter observer
        const statsObserver = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counters = entry.target.querySelectorAll('[data-count]');
                    counters.forEach(counter => {
                        animateCounter(counter);
                    });
                    statsObserver.unobserve(entry.target);
                }
            });
        });

        const statsSection = document.querySelector('.stats');
        if (statsSection) {
            statsObserver.observe(statsSection);
        }

        // Contact form submission
        document.querySelector('.contact-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show success message
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.textContent;
            btn.textContent = 'Gönderiliyor...';
            btn.disabled = true;
            
            setTimeout(() => {
                btn.textContent = 'Mesaj Gönderildi! ✓';
                btn.style.background = 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)';
                
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.disabled = false;
                    btn.style.background = '';
                    this.reset();
                }, 2000);
            }, 1000);
        });

        // Typing effect for hero title
        function typeWriter(element, text, speed = 100) {
            let i = 0;
            element.textContent = '';
            
            function type() {
                if (i < text.length) {
                    element.textContent += text.charAt(i);
                    i++;
                    setTimeout(type, speed);
                }
            }
            type();
        }

        // Initialize typing effect on page load
        window.addEventListener('load', function() {
            const heroTitle = document.querySelector('.hero h1');
            const originalText = heroTitle.textContent;
            typeWriter(heroTitle, originalText, 80);
        });

        // Parallax effect for hero background
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const hero = document.querySelector('.hero');
            const rate = scrolled * -0.5;
            
            if (hero) {
                hero.style.transform = `translateY(${rate}px)`;
            }
        });

        // Add hover effect to feature cards
        document.querySelectorAll('.feature-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-15px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(-10px) scale(1)';
            });
        });

        // Navbar mobile menu auto-close
        document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
            link.addEventListener('click', function() {
                const navbarCollapse = document.querySelector('.navbar-collapse');
                if (navbarCollapse.classList.contains('show')) {
                    const navbarToggler = document.querySelector('.navbar-toggler');
                    navbarToggler.click();
                }
            });
        });

        // Add loading animation to buttons
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!this.classList.contains('loading')) {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                }
            });
        });

        // Easter egg - Konami code
        let konamiCode = [];
        const konamiSequence = [38, 38, 40, 40, 37, 39, 37, 39, 66, 65]; // Up Up Down Down Left Right Left Right B A

        document.addEventListener('keydown', function(e) {
            konamiCode.push(e.keyCode);
            if (konamiCode.length > konamiSequence.length) {
                konamiCode.shift();
            }
            
            if (konamiCode.join(',') === konamiSequence.join(',')) {
                document.body.style.transform = 'rotate(1deg)';
                document.body.style.filter = 'hue-rotate(180deg)';
                
                setTimeout(() => {
                    document.body.style.transform = '';
                    document.body.style.filter = '';
                }, 3000);
                
                konamiCode = [];
            }
        });

        // Progressive loading for images
        function lazyLoad() {
            const images = document.querySelectorAll('img[data-src]');
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            images.forEach(img => imageObserver.observe(img));
        }

        // Initialize lazy loading
        lazyLoad();

        // Add dynamic gradient to hero section
        setInterval(() => {
            const hero = document.querySelector('.hero');
            const gradients = [
                'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)'
            ];
            
            const randomGradient = gradients[Math.floor(Math.random() * gradients.length)];
            if (hero && Math.random() > 0.95) {
                hero.style.background = randomGradient;
                setTimeout(() => {
                    hero.style.background = 'var(--gradient-primary)';
                }, 5000);
            }
        }, 10000);
    </script>
</body>
</html>