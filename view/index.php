<?php 
// Oturumu başlat
session_start(); 

// Eğer kullanıcı giriş yapmamışsa, login sayfasına yönlendir (Giriş zorunluysa)
// Eğer herkesin görmesini istiyorsak, bu bloğu kaldırabiliriz.
// Şimdilik, giriş yapanlara özel bir hoş geldiniz mesajı ile bırakalım:

$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? ($_SESSION['username'] ?? 'Kullanıcı') : 'Misafir'; 

// Başarı mesajını bir kez gösterip silelim
$success_message = $_SESSION['success'] ?? null;
unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Charmoji - Ana Sayfa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    
</head>
<body class="body-base">

    <nav class="navbar navbar-expand-md fixed-top navbar-custom">
        <div class="container px-4 px-sm-5 px-lg-4">
            <a class="navbar-brand logo text-primary" href="#" onclick="window.scrollTo(0,0)">CHARMOJİ</a>
            
            <button class="navbar-toggler p-2 border-0 mobile-btn-custom" type="button" id="mobile-menu-btn" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Menüyü Aç/Kapa">
                <i data-lucide="menu" class="w-6 h-6"></i>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <div class="d-flex align-items-center space-x-4 ml-md-3">
                    <a href="giris.html" class="btn btn-primary btn-sm nav-btn-custom">
                        Giriş Yap
                    </a>
                    
                    <button id="theme-toggle" class="p-2 rounded-circle theme-toggle-btn-custom">
                        <i data-lucide="moon" class="w-5 h-5 dark-icon"></i>
                        <i data-lucide="sun" class="w-5 h-5 light-icon text-warning"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <section id="hero" class="hero text-center pt-5 pb-5 overflow-hidden position-relative">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>

        <div class="container position-relative z-1" style="padding-top: 5rem; padding-bottom: 5rem;">
            <h1 class="display-3 fw-bold mb-3 tracking-tight">
                Geleceği <span class="text-gradient">Kodluyoruz</span>
            </h1>
            <p class="lead text-muted mx-auto mb-4" style="max-width: 42rem;">
                Charmoji ile alışkanlıklarınızı yönetin. Modern, hızlı ve tamamen size özel çözümler.
            </p>
            <div class="d-flex justify-content-center gap-3">
                <a href="kaydol.html" class="btn btn-primary btn-lg hero-btn-custom">
                    Başlayalım
                </a>
            </div>
        </div>
    </section>

    <section id="features" class="py-5 features-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="text-primary fw-semibold text-uppercase fs-6">Hizmetlerimiz</h2>
                <p class="display-6 fw-bold mt-2">Neden Charmoji?</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card card-custom h-100 p-3 shadow-sm hover-shadow">
                        <div class="icon-wrapper bg-orange-light">
                            <i data-lucide="zap" class="text-primary w-6 h-6"></i>
                        </div>
                        <h3 class="fs-4 fw-bold mb-2">Yüksek Hız</h3>
                        <p class="text-muted">Optimize edilmiş altyapı ile ışık hızında işlem yapın.</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-custom h-100 p-3 shadow-sm hover-shadow">
                        <div class="icon-wrapper bg-green-light">
                            <i data-lucide="smartphone" class="text-secondary w-6 h-6"></i>
                        </div>
                        <h3 class="fs-4 fw-bold mb-2">Mobil Uyumlu</h3>
                        <p class="text-muted">Telefon, tablet veya masaüstü; her yerde yanınızda.</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-custom h-100 p-3 shadow-sm hover-shadow">
                        <div class="icon-wrapper bg-pink-light">
                            <i data-lucide="shield" class="text-pink w-6 h-6"></i>
                        </div>
                        <h3 class="fs-4 fw-bold mb-2">Güvenli Yapı</h3>
                        <p class="text-muted">Verileriniz modern güvenlik standartları ile korunur.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="stats" class="py-5 stats-section">
        <div class="container text-white">
            <div class="row text-center g-4">
                <div class="col-6 col-md-3">
                    <div class="fs-1 fw-bold mb-1">100+</div>
                    <div class="text-orange-light-muted text-uppercase fs-7">Alışkanlık</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="fs-1 fw-bold mb-1">50+</div>
                    <div class="text-orange-light-muted text-uppercase fs-7">Kullanıcı</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="fs-1 fw-bold mb-1">%99</div>
                    <div class="text-orange-light-muted text-uppercase fs-7">Motivasyon</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="fs-1 fw-bold mb-1">24/7</div>
                    <div class="text-orange-light-muted text-uppercase fs-7">Erişim</div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer-custom py-4 border-top border-dark-subtle">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center">
            <div class="mb-2 mb-md-0 text-center text-md-start">
                <span class="fs-4 fw-bold text-white">Charmoji</span>
                <p class="fs-7 text-muted mt-1">&copy; 2025 Tüm hakları saklıdır.</p>
            </div>
            <div class="d-flex gap-3">
                <a href="https://www.instagram.com/Charmojiapp" target="_blank" rel="noopener noreferrer" class="text-muted social-icon-custom">
                    <i data-lucide="instagram" class="w-5 h-5"></i>
                </a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <script>
        lucide.createIcons();
        
        // Mobile menü butonu Bootstrap'in data-bs-toggle'ı ile çalışır, bu yüzden sadece Dark Mode mantığı kaldı.
        const themeToggle = document.getElementById('theme-toggle');
        const html = document.documentElement;

        // Dark Mode Kontrolü
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            html.classList.add('dark');
        } else {
            html.classList.remove('dark');
        }

        // Tema değiştir
        themeToggle.addEventListener('click', () => {
            html.classList.toggle('dark');
            if (html.classList.contains('dark')) {
                localStorage.theme = 'dark';
            } else {
                localStorage.theme = 'light';
            }
        });
    </script>
</body>
</html>