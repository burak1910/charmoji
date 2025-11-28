<?php
// 1. AYARLAR
ob_start();
session_set_cookie_params(0, '/'); 
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

// GİRİŞ KONTROLÜ
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// 2. ARAMA TERİMİNİ YAKALA (YENİ)
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : "";

// VARSAYILAN DEĞERLER
$bekleyenIstekler = [];
$arkadaslarim = [];
$baskaKullanicilar = [];
$hataMesaji = "";

// VERİLERİ ÇEKME
$managerPath = __DIR__ . '/../service/FriendManager.php';

if (file_exists($managerPath)) {
    require_once $managerPath;
    $myID = $_SESSION['user_id'];

    try {
        if (class_exists('FriendManager')) {
            $bekleyenIstekler = FriendManager::getPendingRequests($myID) ?? [];
            $arkadaslarim = FriendManager::getFriends($myID) ?? [];
            
            // ARAMA SORGUSUNU BURAYA GÖNDERİYORUZ (GÜNCELLENDİ)
            $baskaKullanicilar = FriendManager::searchUsers($searchQuery, $myID) ?? [];
        } else {
            $hataMesaji = "FriendManager sınıfı bulunamadı!";
        }
    } catch (Exception $e) {
        $hataMesaji = "Veritabanı hatası: " . $e->getMessage();
    } catch (Error $e) {
        $hataMesaji = "Kod hatası: " . $e->getMessage();
    }
} else {
    $hataMesaji = "FriendManager dosyası bulunamadı!";
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim - Charmoji</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css"> 
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="body-base">

    <nav class="navbar fixed-top navbar-custom p-3">
        <div class="container-fluid justify-content-between">
            <a href="#" class="navbar-brand logo text-primary fs-4">CHARMOJİ</a>
            
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted d-none d-md-block">Merhaba, <b><?= htmlspecialchars($_SESSION['fullname']); ?></b></span>
                
                <button id="theme-toggle" class="p-2 rounded-circle theme-toggle-btn-custom border-0 bg-transparent">
                    <i data-lucide="moon" class="w-5 h-5 dark-icon"></i>
                    <i data-lucide="sun" class="w-5 h-5 light-icon text-warning"></i>
                </button>
                
                <a href="../service/logout.php" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-2">
                    <i data-lucide="log-out" class="w-4 h-4"></i> <span class="d-none d-md-inline">Çıkış</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 100px;">
        <div class="blob blob-1" style="opacity: 0.3; top: 10%; left: 10%;"></div>
        <div class="blob blob-2" style="opacity: 0.3; bottom: 10%; right: 10%;"></div>

        <?php if(!empty($hataMesaji)): ?>
            <div class="alert alert-danger shadow-sm border-0">
                <i data-lucide="alert-triangle" class="w-4 h-4 inline me-2"></i> <strong>Sistem Hatası:</strong> <?= $hataMesaji ?>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success shadow-sm border-0 d-flex align-items-center">
                <i data-lucide="check-circle" class="w-4 h-4 me-2"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center">
                <i data-lucide="x-circle" class="w-4 h-4 me-2"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="row mt-4 g-4 position-relative z-1">
            
            <div class="col-md-4">
                <div class="card card-custom h-100 shadow-sm border-0">
                    <div class="card-header bg-transparent border-bottom border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-primary">Gelen İstekler</h5>
                        <span class="badge bg-primary rounded-pill"><?= count($bekleyenIstekler) ?></span>
                    </div>
                    <ul class="list-group list-group-flush bg-transparent">
                        <?php if(!empty($bekleyenIstekler)): ?>
                            <?php foreach($bekleyenIstekler as $istek): ?>
                                <li class="list-group-item bg-transparent border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-primary bg-opacity-10 p-2 rounded-circle text-primary">
                                            <i data-lucide="user" class="w-4 h-4"></i>
                                        </div>
                                        <span class="fw-medium"><?= htmlspecialchars($istek['Ad']) ?></span>
                                    </div>
                                    <form action="../service/friend_action.php" method="POST" class="m-0">
                                        <input type="hidden" name="accept_request_id" value="<?= $istek['IstekID'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success rounded-pill px-3">
                                            <i data-lucide="check" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item bg-transparent text-muted text-center py-4 small">
                                <i data-lucide="inbox" class="w-8 h-8 mb-2 opacity-50 d-block mx-auto"></i>
                                Bekleyen istek yok.
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-custom h-100 shadow-sm border-0">
                    <div class="card-header bg-transparent border-bottom border-secondary border-opacity-10">
                        <h5 class="mb-0 fw-bold text-success">Arkadaşlarım</h5>
                    </div>
                    <ul class="list-group list-group-flush bg-transparent">
                        <?php if(!empty($arkadaslarim)): ?>
                            <?php foreach($arkadaslarim as $arkadas): ?>
                                <li class="list-group-item bg-transparent border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-success bg-opacity-10 p-2 rounded-circle text-success">
                                            <i data-lucide="smile" class="w-4 h-4"></i>
                                        </div>
                                        <span class="fw-medium"><?= htmlspecialchars($arkadas['Ad']) ?></span>
                                    </div>
                                    <form action="../service/friend_action.php" method="POST" class="m-0">
                                        <input type="hidden" name="remove_id" value="<?= $arkadas['ArkadaslikID'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0 p-1" title="Sil">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item bg-transparent text-muted text-center py-4 small">
                                <i data-lucide="users" class="w-8 h-8 mb-2 opacity-50 d-block mx-auto"></i>
                                Henüz arkadaşın yok.
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-custom h-100 shadow-sm border-0">
                    <div class="card-header bg-transparent border-bottom border-secondary border-opacity-10">
                        <h5 class="mb-0 fw-bold text-info">Kullanıcı Ara & Ekle</h5>
                    </div>
                    
                    <div class="p-3 pb-0">
                        <form action="" method="GET" class="d-flex gap-2">
                            <input type="text" name="q" class="form-control form-control-sm" 
                                   placeholder="İsim veya E-posta..." 
                                   value="<?= htmlspecialchars($searchQuery) ?>">
                            <button type="submit" class="btn btn-sm btn-info text-white">
                                <i data-lucide="search" class="w-4 h-4"></i>
                            </button>
                            <?php if(!empty($searchQuery)): ?>
                                <a href="userpage.php" class="btn btn-sm btn-outline-secondary">X</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <ul class="list-group list-group-flush bg-transparent mt-2">
                        <?php if(!empty($baskaKullanicilar)): ?>
                            <?php foreach($baskaKullanicilar as $kisi): ?>
                                <li class="list-group-item bg-transparent border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
                                    <div class="d-flex flex-column" style="line-height: 1.2;">
                                        <span class="fw-medium"><?= htmlspecialchars($kisi['Ad']) ?></span>
                                        <span class="text-muted small" style="font-size: 0.75rem;">
                                            <?= htmlspecialchars($kisi['Eposta']) ?>
                                        </span>
                                    </div>
                                    <form action="../service/friend_action.php" method="POST" class="m-0">
                                        <input type="hidden" name="add_friend_id" value="<?= $kisi['KullaniciID'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            Ekle
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item bg-transparent text-muted text-center py-4 small">
                                <i data-lucide="ghost" class="w-8 h-8 mb-2 opacity-50 d-block mx-auto"></i>
                                <?php echo !empty($searchQuery) ? "Sonuç bulunamadı." : "Önerilecek kimse yok."; ?>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        lucide.createIcons();

        const themeToggle = document.getElementById('theme-toggle');
        const html = document.documentElement;

        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            html.classList.add('dark');
        } else {
            html.classList.remove('dark');
        }

        themeToggle.addEventListener('click', () => {
            html.classList.toggle('dark');
            localStorage.theme = html.classList.contains('dark') ? 'dark' : 'light';
        }); 
    </script>
</body>
</html>