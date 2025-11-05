<?php 
// Oturumu başlat
session_start(); 
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Girişi</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/dumenden/style/custom.css">
    </head>
<body class="d-flex h-100vh justify-content-center align-items-center bg-primary"> 
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-4 p-md-5">

                        <h2 class="text-center fw-bold mb-4">Giriş Yap</h2>
                        
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($_SESSION['error']); ?></div>
                            <?php unset($_SESSION['error']); ?>
                        <?php elseif (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success" role="alert"><?= htmlspecialchars($_SESSION['success']); ?></div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>
                        
                        <form action="auth.php" method="POST">
                            
                            <div class="form-floating mb-3">
                                <input type="email" class="form-control" id="username" name="username" placeholder="E-posta Adresi" required>
                                <label for="username">E-posta Adresi</label>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Şifre" required>
                                <label for="password">Şifre</label>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="rememberMe" name="remember">
                                    <label class="form-check-label small" for="rememberMe">Beni Hatırla</label>
                                </div>
                                <a href="#" class="small text-decoration-none">Şifreni mi unuttun?</a>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg fw-bold">Giriş Yap</button>
                            </div>

                            <div class="text-center mt-4">
                                <p class="small">Hesabın yok mu? <a href="register.php">Hemen Kaydol</a></p>
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