<?php
// ----------------------------------------------------------------
// 1. OTURUM VE AYARLAR
// ----------------------------------------------------------------
ob_start();
session_set_cookie_params(0, '/'); 
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Zaten giriş yapılmışsa yönlendir
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header("Location: userpage.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Charmoji</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            darkMode: 'class', // Class tabanlı dark mode
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            DEFAULT: '#F97316', // Senin Turuncu rengin
                            hover: '#ea580c',
                        },
                        dark: {
                            bg: '#111827',
                            card: '#1F2937',
                            input: '#374151',
                            text: '#F3F4F6'
                        }
                    },
                    animation: {
                        'blob': 'blob 7s infinite',
                    },
                    keyframes: {
                        blob: {
                            '0%': { transform: 'translate(0px, 0px) scale(1)' },
                            '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
                            '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
                            '100%': { transform: 'translate(0px, 0px) scale(1)' },
                        }
                    }
                }
            }
        }
    </script>

    <style>
        /* Blob Efekti İçin Custom CSS */
        .blob-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }
        .blob {
            position: absolute;
            width: 300px;
            height: 300px;
            background: linear-gradient(180deg, rgba(249,115,22,0.4) 0%, rgba(168,85,247,0.4) 100%);
            border-radius: 50%;
            filter: blur(40px);
            opacity: 0.6;
            animation: blob 10s infinite alternate;
        }
        .blob-1 { top: 10%; left: 15%; animation-delay: 0s; }
        .blob-2 { bottom: 10%; right: 15%; animation-delay: 2s; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 dark:bg-dark-bg dark:text-gray-200 transition-colors duration-300 min-h-screen flex flex-col font-sans">

    <nav class="fixed top-0 w-full z-50 bg-white/80 dark:bg-dark-bg/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-700 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="../view/index.php" class="flex items-center gap-2 text-sm font-bold text-primary hover:text-primary-hover transition-colors">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                    <span>Ana Sayfa</span>
                </a>


                <button id="theme-toggle" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors focus:outline-none focus:ring-2 focus:ring-primary">
                    <i data-lucide="moon" class="w-5 h-5 hidden dark:block text-gray-300"></i>
                    <i data-lucide="sun" class="w-5 h-5 block dark:hidden text-orange-500"></i>
                </button>
            </div>
        </div>
    </nav>

    <main class="flex-grow flex items-center justify-center relative px-4 py-20">
        
        <div class="blob-bg">
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>
        </div>

        <div class="w-full max-w-md bg-white dark:bg-dark-card rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 p-8 relative z-10 transition-colors duration-300">
            
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Giriş Yap</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Charmoji dünyasına hoş geldiniz 👋</p>
            </div>

            <?php if(isset($_SESSION['error'])): ?>
                <div class="mb-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 flex items-center gap-3 text-red-700 dark:text-red-300 text-sm">
                    <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <span><?= $_SESSION['error']; unset($_SESSION['error']); ?></span>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['success'])): ?>
                <div class="mb-4 p-4 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 flex items-center gap-3 text-green-700 dark:text-green-300 text-sm">
                    <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <span><?= $_SESSION['success']; unset($_SESSION['success']); ?></span>
                </div>
            <?php endif; ?>

            <form action="../service/auth.php" method="POST" class="space-y-6">
                
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-posta veya Kullanıcı Adı</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i data-lucide="mail" class="w-5 h-5"></i>
                        </div>
                        <input type="text" id="username" name="username" required 
                            class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-dark-input text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all shadow-sm"
                            placeholder="ornek@mail.com">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Şifre</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i data-lucide="lock" class="w-5 h-5"></i>
                        </div>
                        <input type="password" id="password" name="password" required 
                            class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-dark-input text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all shadow-sm"
                            placeholder="••••••">
                    </div>
                </div>

                <button type="submit" 
                    class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-primary hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all transform hover:-translate-y-0.5">
                    Giriş Yap
                    <i data-lucide="arrow-right" class="w-4 h-4 ml-2"></i>
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Hesabın yok mu? 
                    <a href="register.php" class="font-semibold text-primary hover:text-primary-hover hover:underline transition-colors">
                        Hemen Kayıt Ol
                    </a>
                </p>
            </div>

        </div>
    </main>

    <script>
        // İkonları oluştur
        lucide.createIcons();

        // Dark Mode Mantığı
        const themeToggle = document.getElementById('theme-toggle');
        const html = document.documentElement;

        // Sayfa yüklendiğinde tema kontrolü
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            html.classList.add('dark');
        } else {
            html.classList.remove('dark');
        }

        // Butona tıklanınca tema değiştir
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