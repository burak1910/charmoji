<?php
// service/friend_action.php

// Session başlatma ve path ayarları
ob_start();
session_set_cookie_params(0, '/');
session_start();

// Hata Raporlama
ini_set('display_errors', 1);
error_reporting(E_ALL);

// FriendManager'ı dahil et (Aynı klasörde oldukları için __DIR__)
require_once __DIR__ . '/FriendManager.php';

if (!isset($_SESSION['user_id'])) {
    die("Giriş yapmalısınız.");
}

$myID = $_SESSION['user_id'];
$redirectPath = "../view/userpage.php";

// 1. Arkadaş Ekleme
if (isset($_POST['add_friend_id'])) {
    $targetID = intval($_POST['add_friend_id']);
    $result = FriendManager::sendRequest($myID, $targetID);
    
    if ($result === true) {
        $_SESSION['success'] = "Arkadaşlık isteği gönderildi.";
    } else {
        $_SESSION['error'] = "Hata: " . $result;
    }
}

// 2. Kabul Etme
elseif (isset($_POST['accept_request_id'])) {
    $reqID = intval($_POST['accept_request_id']);
    if (FriendManager::acceptRequest($reqID, $myID)) {
        $_SESSION['success'] = "Arkadaşlık isteği kabul edildi!";
    } else {
        $_SESSION['error'] = "İşlem başarısız.";
    }
}

// 3. Silme / Reddetme
elseif (isset($_POST['remove_id'])) {
    $reqID = intval($_POST['remove_id']);
    if (FriendManager::removeFriend($reqID)) {
        $_SESSION['success'] = "Kişi listenizden çıkarıldı.";
    } else {
        $_SESSION['error'] = "Silme işlemi başarısız.";
    }
}

// İşlem bitince userpage'e geri dön
header("Location: " . $redirectPath);
exit();
?>