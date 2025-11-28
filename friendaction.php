<?php
// friend_action.php
session_start();

// Dosya yolunu kontrol ederek dahil et
require_once __DIR__ . '/service/FriendManager.php';

if (!isset($_SESSION['user_id'])) {
    die("Giriş yapmalısınız.");
}

$myID = $_SESSION['user_id'];

// 1. Arkadaş Ekleme
if (isset($_POST['add_friend_id'])) {
    $targetID = intval($_POST['add_friend_id']);
    $result = FriendManager::sendRequest($myID, $targetID);
    
    if ($result === true) {
        $_SESSION['success'] = "Arkadaşlık isteği gönderildi.";
    } else {
        $_SESSION['error'] = "Hata: " . $result;
    }
    header("Location: view/userpage.php");
    exit();
}

// 2. Kabul Etme
if (isset($_POST['accept_request_id'])) {
    $reqID = intval($_POST['accept_request_id']);
    if (FriendManager::acceptRequest($reqID, $myID)) {
        $_SESSION['success'] = "Arkadaşlık isteği kabul edildi!";
    } else {
        $_SESSION['error'] = "İşlem başarısız.";
    }
    header("Location: view/userpage.php");
    exit();
}

// 3. Silme / Reddetme
if (isset($_POST['remove_id'])) {
    $reqID = intval($_POST['remove_id']);
    FriendManager::removeFriend($reqID);
    $_SESSION['success'] = "Kişi silindi.";
    header("Location: view/userpage.php");
    exit();
}
?>