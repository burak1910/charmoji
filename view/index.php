<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Charmoji - Ana Sayfa</title>
    
    <link rel="icon" type="image/png" href="charmoji.png"> 
        <!--Kütüphaneler-->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Tailwind Ayarları-->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: '#F97316', 
                        primaryHover: '#c2410c', 
                        secondary: '#10B981', 
                    }
                }
            }
        }
    </script>

    <style>
        /*Scrollbar Kısmı*/
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #555; }
        
        /* Sayfayı Modern Tutuyor*/
        .blob {
            position: absolute;
            filter: blur(40px);
            z-index: -1;
            opacity: 0.4;
            animation: move 10s infinite alternate;
        
        }
        /*Sayfaya Canlılık Katar*/
        @keyframes move {
            from { transform: translate(0, 0) scale(1); }
            to { transform: translate(20px, -20px) scale(1.1); }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 dark:bg-gray-900 dark:text-gray-100 transition-colors duration-300">

    <!-- NAVBAR -->
    <nav class="fixed w-full z-50 bg-white/80 dark:bg-gray-900/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <div class="flex-shrink-0 flex items-center gap-2 cursor-pointer" onclick="window.scrollTo(0,0)">
                    <!-- Logo -->
                    <img src="charmoji.png" alt="Charmoji Logo" class="w-12 h-12"> <div class="font-bold text-2xl text-primary tracking-tighter">CHARMOJİ</div>
                </div>
                
                <div class="hidden md:block">
                    <div class="ml-10 flex items-center space-x-4">
                        <a href="giris.html" class="px-5 py-2 text-sm font-medium rounded-lg text-white bg-primary hover:bg-primaryHover transition shadow-md hover:shadow-orange-500/30">
                            Giriş Yap
                        </a>
                        
                        <button id="theme-toggle" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                            <i data-lucide="moon" class="w-5 h-5 dark:hidden"></i>
                            <i data-lucide="sun" class="w-5 h-5 hidden dark:block text-yellow-400"></i>
                        </button>
                    </div>
                </div>

                <div class="-mr-2 flex md:hidden">
                    <button id="mobile-menu-btn" class="inline-flex items-center justify-center p-2 rounded-md text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none">
                        <i data-lucide="menu" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
        </div>

        <div id="mobile-menu" class="hidden md:hidden bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="giris.php" class="block text-center w-full px-5 py-3 rounded-md text-base font-medium text-white bg-primary hover:bg-primaryHover">
                    Giriş Yap
                </a>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION KISMI -->
    <section id="hero" class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
        <div class="blob bg-orange-300 w-64 h-64 rounded-full top-0 left-0 -translate-x-1/2"></div>
        <div class="blob bg-yellow-300 w-64 h-64 rounded-full bottom-0 right-0 translate-x-1/2"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight mb-6">
                Geleceği 
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-yellow-500">Kodluyoruz</span>
            </h1>
            <p class="mt-4 max-w-2xl mx-auto text-xl text-gray-500 dark:text-gray-400">
                Charmoji ile alışkanlıklarınızı yönetin. Modern, hızlı ve tamamen size özel çözümler.
            </p>
            <div class="mt-8 flex justify-center gap-4">
                <a href="kaydol.html" class="px-8 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-primary hover:bg-primaryHover md:text-lg md:px-10 shadow-xl shadow-orange-500/40 transition hover:-translate-y-1">
                    Başlayalım
                </a>
            </div>
        </div>
    </section>

    <!-- Fotoğraflı Tanıtım Alanı-->
    <section class="py-20 bg-white dark:bg-gray-800 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-24">
            
            <!-- 1.Fotoğraf ve Yazı ayarı -->
            <div class="flex flex-col md:flex-row items-center gap-12">
                <!-- Yazı Kısmı-->
                <div class="w-full md:w-1/2 space-y-6">
                    <div class="inline-block px-3 py-1 text-xs font-semibold tracking-wider text-orange-600 uppercase bg-orange-100 rounded-full dark:bg-orange-900/30 dark:text-orange-400">
                        Oyunlaştırma
                    </div>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">
                        Hayatını Bir <span class="text-primary">RPG Oyunu</span> Gibi Yönet
                    </h2>
                    <p class="text-lg text-gray-600 dark:text-gray-300 leading-relaxed">
                        Sıkıcı listelerden sıkılmadın mı? Charmoji ile görevlerini tamamla, XP kazan ve seviye atla. Her gününü bir maceraya dönüştür.
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-center gap-3">
                            <i data-lucide="check-circle" class="text-green-500 w-6 h-6"></i>
                            <span class="text-gray-700 dark:text-gray-300">Günlük görev takibi</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i data-lucide="check-circle" class="text-green-500 w-6 h-6"></i>
                            <span class="text-gray-700 dark:text-gray-300">Ödül sistemi ve rozetler</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i data-lucide="check-circle" class="text-green-500 w-6 h-6"></i>
                            <span class="text-gray-700 dark:text-gray-300">Arkadaşlarını takip et ve motive ol</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Fotoğraf -->
                <div class="w-full md:w-1/2 relative">
                    <!-- Arkadaki Glow Efekti-->
                    <div class="absolute -inset-4 bg-primary/20 rounded-full blur-2xl -z-10"></div>
                    <img src="https://images.unsplash.com/photo-1484480974693-6ca0a78fb36b?q=80&w=2072&auto=format&fit=crop" 
                         alt="Charmoji Arayüz" 
                         class="w-full h-auto rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 hover:scale-[1.02] transition duration-500">
                </div>
            </div>

            <!-- 2.Fotoğraf ve Yazı ayarı-->
            <div class="flex flex-col md:flex-row-reverse items-center gap-12">
                <!-- Yazı Kısmı -->
                <div class="w-full md:w-1/2 space-y-6">
                    <div class="inline-block px-3 py-1 text-xs font-semibold tracking-wider text-green-600 uppercase bg-green-100 rounded-full dark:bg-green-900/30 dark:text-green-400">
                        Analiz
                    </div>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">
                        Gelişimini <span class="text-secondary">Adım Adım</span> İzle
                    </h2>
                    <p class="text-lg text-gray-600 dark:text-gray-300 leading-relaxed">
                        Tek başına ilerlemek kolay, peki ya rakiplerin varken? Arkadaşlarına meydan oku, puanlarını yarıştır ve liderlik tablosunun zirvesine kimin oturacağını herkese göster.
                    </p>
                </div>

                <!-- Fotoğraf Kısmı -->
                <div class="w-full md:w-1/2 relative">
                    <div class="absolute -inset-4 bg-secondary/20 rounded-full blur-2xl -z-10"></div>
                    <img src="https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=2070&auto=format&fit=crop" 
                         alt="Charmoji Analiz" 
                         class="w-full h-auto rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 hover:scale-[1.02] transition duration-500">
                </div>
            </div>

        </div>
    </section>

    <!-- Hizmetlerimiz -->
    <section id="features" class="py-20 bg-gray-50 dark:bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-base text-primary font-semibold tracking-wide uppercase">Hizmetlerimiz</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight sm:text-4xl">
                    Neden Mi Charmoji?
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="p-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 hover:shadow-xl transition duration-300 group">
                    <div class="w-12 h-12 bg-orange-100 dark:bg-gray-700 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition">
                        <i data-lucide="zap" class="text-primary w-6 h-6"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Yüksek Hız</h3>
                    <p class="text-gray-500 dark:text-gray-400">Optimize edilmiş altyapı ile ışık hızında işlem yapın.</p>
                </div>

                <div class="p-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 hover:shadow-xl transition duration-300 group">
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition">
                        <i data-lucide="smartphone" class="text-green-600 w-6 h-6"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Mobil Uyumlu</h3>
                    <p class="text-gray-500 dark:text-gray-400">Telefon, tablet veya masaüstü; her yerde yanınızda.</p>
                </div>

                <div class="p-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 hover:shadow-xl transition duration-300 group">
                    <div class="w-12 h-12 bg-pink-100 dark:bg-pink-900 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition">
                        <i data-lucide="shield" class="text-pink-600 w-6 h-6"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Güvenli Yapı</h3>
                    <p class="text-gray-500 dark:text-gray-400">Verileriniz modern güvenlik standartları ile korunur.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- İstatistik -->
    <section id="stats" class="py-16 bg-primary">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center text-white">
                <div>
                    <div class="text-4xl font-bold mb-1">100+</div>
                    <div class="text-orange-100 text-sm uppercase">Alışkanlık</div>
                </div>
                <div>
                    <div class="text-4xl font-bold mb-1">50+</div>
                    <div class="text-orange-100 text-sm uppercase">Kullanıcı</div>
                </div>
                <div>
                    <div class="text-4xl font-bold mb-1">%99</div>
                    <div class="text-orange-100 text-sm uppercase">Motivasyon</div>
                </div>
                <div>
                    <div class="text-4xl font-bold mb-1">24/7</div>
                    <div class="text-orange-100 text-sm uppercase">Erişim</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Telif Hakkı kısmı -->
    <footer class="bg-gray-900 text-gray-300 py-12 border-t border-gray-800">
        <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center">
            
            <div class="mb-4 md:mb-0 text-center md:text-left">
                <span class="text-2xl font-bold text-white">Charmoji</span>
                <p class="text-sm mt-1 text-gray-500">&copy; 2025 Tüm hakları saklıdır.</p>
            </div>

            <div class="flex space-x-6">
                <a href="https://www.instagram.com/Charmojiapp" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 hover:text-primary transition">
                    <i data-lucide="instagram" class="w-10 h-10"></i>
                    <h3 class="text-2xl font-bold">@charmojiapp</h3>
                </a>
            </div>
        </div>
    </footer>

    <script>
        /* JAVASCRİPT Kodları */
        lucide.createIcons();
            /*Mobil Menü Kısmı*/
        const btn = document.getElementById('mobile-menu-btn');
        const menu = document.getElementById('mobile-menu');
        const themeToggle = document.getElementById('theme-toggle');
        const html = document.documentElement;

        btn.addEventListener('click', () => {
            menu.classList.toggle('hidden');
        });
            /*Kullanıcı Arka Planı Hangisinde kullanıyor onu kontrol ediyoruz */
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            html.classList.add('dark');
        } else {
            html.classList.remove('dark');
        }
            /*Arka Planın Modunu Kontrol ederiz*/
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