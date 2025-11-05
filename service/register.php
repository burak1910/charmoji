<?php 
// Oturumu başlat
session_start(); 
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <title>Yeni Kayıt</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/dumenden/style/custom.css">
</head>
<body class="d-flex h-100vh justify-content-center align-items-center bg-primary"> 
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-5">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-4 p-md-5">

                        <h2 class="text-center fw-bold mb-4">Yeni Hesap Oluştur</h2>
                        
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($_SESSION['error']); ?></div>
                            <?php unset($_SESSION['error']); ?>
                        <?php elseif (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success" role="alert"><?= htmlspecialchars($_SESSION['success']); ?></div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>
                        
                        <form action="auth.php" method="POST">
                            
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="fullname" name="fullname" placeholder="Ad Soyad" required>
                                <label for="fullname">Ad Soyad</label>
                            </div>
                            
                            <div class="form-floating mb-3">
                                <input type="email" class="form-control" id="email" name="email" placeholder="E-posta Adresi" required>
                                <label for="email">E-posta Adresi</label>
                            </div>
                            
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Şifre" required>
                                <label for="password">Şifre</label>
                            </div>

                            <div class="form-floating mb-4"> 
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Şifre Tekrarı" required>
                                <label for="confirm_password">Şifre Tekrarı</label>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg fw-bold">Hesap Oluştur</button>
                            </div>

                            <div class="text-center mt-4">
                                <p class="small">Zaten hesabın var mı? <a href="login.php">Giriş Yap</a></p>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>