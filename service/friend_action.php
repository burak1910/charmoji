<?php
// service/friend_action.php
session_start();
require_once __DIR__ . '/FriendManager.php';

// Güvenlik: Giriş yapmamışsa at
if (!isset($_SESSION['user_id'])) {
    header("Location: ../view/login.php");
    exit();
}

$myID = $_SESSION['user_id'];

// POST isteği gelmiş mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. İSTEK GÖNDERME (Topluluk sekmesinden)
    if (isset($_POST['action']) && $_POST['action'] === 'send_request') {
        $targetID = intval($_POST['target_id']);
        
        $sonuc = FriendManager::sendRequest($myID, $targetID);
        
        if ($sonuc === true) {
            $_SESSION['success'] = "Arkadaşlık isteği gönderildi!";
        } else {
            $_SESSION['error'] = "Hata: " . $sonuc;
        }
    }

    // 2. İSTEK KABUL ETME (İstekler sekmesinden)
    elseif (isset($_POST['accept_request_id'])) {
        $istekID = intval($_POST['accept_request_id']);
        
        if (FriendManager::acceptRequest($istekID, $myID)) {
            $_SESSION['success'] = "Arkadaşlık isteği kabul edildi!";
        } else {
            $_SESSION['error'] = "İstek kabul edilemedi.";
        }
    }

    // 3. İSTEK REDDETME (İstekler sekmesinden)
    elseif (isset($_POST['reject_request_id'])) {
        $istekID = intval($_POST['reject_request_id']);
        
        if (FriendManager::removeFriend($istekID)) {
            $_SESSION['success'] = "İstek reddedildi/silindi.";
        } else {
            $_SESSION['error'] = "İşlem başarısız.";
        }
    }
}

// İşlem bitince kullanıcıyı sayfasına geri yolla
header("Location: ../view/userpage.php");
exit();
?>