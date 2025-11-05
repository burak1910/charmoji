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
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dümenden | Ana Sayfa & Tanıtım</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/custom.css"> 
</head>
<body class="bg-light"> 
    
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">🍊 DÜMENDEN PROJE</a>
            <div class="d-flex">
                <?php if ($is_logged_in): ?>
                    <span class="navbar-text me-3 text-light">
                        Hoş Geldin, <strong class="text-primary"><?= htmlspecialchars($username); ?></strong>
                    </span>
                    <a href="logout.php" class="btn btn-sm btn-primary">Çıkış Yap</a>
                <?php else: ?>
                    <a href="../service/login.php" class="btn btn-sm btn-primary me-2">Giriş Yap</a>
                    <a href="../service/register.php" class="btn btn-sm btn-outline-primary">Kaydol</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <div class="container mt-5">
        
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <header class="text-center mb-5">
            <h1 class="display-4 fw-bold text-dark">Projeye Güç Katıyoruz</h1>
            <p class="lead text-secondary">Turuncu ve Siyahın Gücüyle İnşa Edilmiş Modern Bir Yapı.</p>
        </header>

        <div class="row g-4">
            
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm text-center">
                    <div class="card-body">
                        <h3 class="text-primary mb-3">🚀 Hız</h3>
                        <p class="text-muted">Bootstrap 5 gücü sayesinde sayfalarımız hızlı yüklenir ve her cihazda uyumludur.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-lg text-white bg-secondary">
                    <div class="card-body text-center">
                        <h3 class="text-info mb-3">🔒 Güvenlik</h3>
                        <p>PHP ile sağlamlaştırılmış backend mantığı ve şifre hash'leme ile verileriniz güvende tutulur.</p>
                        <a href="../service/login.php," class="btn btn-outline-info btn-sm mt-3">Detay Gör</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm text-center">
                    <div class="card-body">
                         <h3 class="text-primary mb-3">🛠️ Kolaylık</h3>
                        <p class="text-muted">Temiz kod yapısı ve minimal PHP kullanımı sayesinde geliştirmesi basittir.</p>
                    </div>
                </div>
            </div>

        </div> <?php if (!$is_logged_in): ?>
        <section class="text-center py-5 mt-5 bg-primary rounded shadow-lg text-white">
            <h2 class="fw-bold">Hemen Aramıza Katıl!</h2>
            <p class="lead">Projenin tüm özelliklerini keşfetmek için şimdi kaydol veya giriş yap.</p>
            <a href="../service/register.php" class="btn btn-lg btn-dark me-3">Hemen Kaydol</a>
            <a href="../service/login.php" class="btn btn-lg btn-outline-dark">Giriş Yap</a>
        </section>
        <?php endif; ?>

    </div> <footer class="bg-dark text-white text-center py-3 mt-5">
        <p class="mb-0 small">&copy; <?= date('Y'); ?> Dümenden Proje. Tüm Hakları Saklıdır.</p>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>