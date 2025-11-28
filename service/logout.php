<?php
// 1. Mevcut oturumu yakala
session_start();

// 2. Session içindeki tüm değişkenleri (kullanıcı adı, id vs.) temizle
$_SESSION = array();

// 3. Oturumu tamamen yok et
session_destroy();

// 4. (Opsiyonel) Kullanıcıya "Çıkış yapıldı" mesajı göstermek istersen:
// session_destroy() her şeyi sildiği için, mesaj taşımak adına 
// yeni, temiz bir session başlatıyoruz.
session_start();
$_SESSION['success'] = "Başarıyla çıkış yaptınız. Tekrar bekleriz!";


header("Location: /charmoji/view/index.php");
exit();
?>