<?php
// ----------------------------------------------------------------
// 1. AYARLAR (Burası auth.php ile AYNI olmak zorunda)
// ----------------------------------------------------------------

// Bu satır EKSİKTİ, o yüzden auth.php'nin oluşturduğu oturumu göremiyordu.
session_set_cookie_params(0, '/'); 
session_start();

// HATA GÖSTERİMİNİ AÇ
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ----------------------------------------------------------------
// 2. OTURUM KONTROLÜ (DEBUG MODU)
// ----------------------------------------------------------------

// Eğer oturum yoksa yönlendirme YAPMA, hatayı göster

// ----------------------------------------------------------------
// 3. DOSYA YOLU KONTROLÜ
// ----------------------------------------------------------------
$managerPath = __DIR__ . '/../service/FriendManager.php';

if (!file_exists($managerPath)) {
    die("<h3 style='color:red'>HATA: FriendManager dosyası bulunamadı!</h3> Aranan yol: $managerPath");
}

require_once $managerPath; 

// Verileri Çek
$myID = $_SESSION['user_id'];
try {
    $bekleyenIstekler = FriendManager::getPendingRequests($myID);
    $arkadaslarim = FriendManager::getFriends($myID);
    $baskaKullanicilar = FriendManager::searchUsers("", $myID); 
} catch (Exception $e) {
    die("Veritabanı Hatası: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Profilim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5">

    <h1>Hoşgeldin, <?= htmlspecialchars($_SESSION['fullname'] ?? 'Kullanıcı'); ?></h1>
    
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    Gelen İstekler (<?= count($bekleyenIstekler) ?>)
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach($bekleyenIstekler as $istek): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($istek['Ad']) ?>
                            <form action="../friend_action.php" method="POST" class="m-0">
                                <input type="hidden" name="accept_request_id" value="<?= $istek['IstekID'] ?>">
                                <button type="submit" class="btn btn-sm btn-success">Kabul Et</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                    <?php if(empty($bekleyenIstekler)) echo "<li class='list-group-item text-muted'>Bekleyen istek yok.</li>"; ?>
                </ul>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    Arkadaşlarım
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach($arkadaslarim as $arkadas): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <?= htmlspecialchars($arkadas['Ad']) ?>
                            <form action="../friend_action.php" method="POST" class="m-0">
                                <input type="hidden" name="remove_id" value="<?= $arkadas['ArkadaslikID'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Sil</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                    <?php if(empty($arkadaslarim)) echo "<li class='list-group-item text-muted'>Henüz arkadaşın yok.</li>"; ?>
                </ul>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    Kimi Tanıyorsun?
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach($baskaKullanicilar as $kisi): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <?= htmlspecialchars($kisi['Ad']) ?>
                            <form action="../friend_action.php" method="POST" class="m-0">
                                <input type="hidden" name="add_friend_id" value="<?= $kisi['KullaniciID'] ?>">
                                <button type="submit" class="btn btn-sm btn-primary">Ekle</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <br>
    <a href="../logout.php" class="btn btn-secondary">Çıkış Yap</a>
</body>
</html>