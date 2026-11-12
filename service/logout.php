<?php
// service/logout.php
// 1. Mevcut oturumu güvenli ayarlarla yakala (auth.php başlatır)
require_once __DIR__ . '/auth.php';
// metin() için ortak çekirdek (include anında çıktı/yönlendirme/veritabanı işlemi yapmaz).
require_once __DIR__ . '/../view/ortak/cekirdek.php';

// Çıkış durum değiştiren bir işlemdir: yalnızca CSRF jetonlu POST ile yapılır
// (GET ile, ör. bir <img src> ya da bağlantıyla tetiklenemez).
yalnizca_post();
csrf_dogrula();

// 2. Session içindeki tüm değişkenleri (kullanıcı adı, id vs.) temizle
$_SESSION = array();

// 3. Oturumu tamamen yok et
session_destroy();

// 4. Kullanıcıya "Çıkış yapıldı" mesajı taşımak için yeni, temiz bir oturum
// başlat (strict mode sayesinde yok edilen eski kimlik yeniden kullanılmaz).
guvenli_oturum_baslat();
oturum_kimligini_yenile();
$_SESSION['success'] = metin('tanitim.cikis_bildirim');

// Göreli yönlendirme: uygulama hangi alt dizinde servis edilirse edilsin çalışır.
header("Location: ../view/index.php");
exit();
