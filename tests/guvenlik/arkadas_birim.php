<?php
// tests/guvenlik/arkadas_birim.php
// Kullanım: CHARMOJI_ENV_FILE=<test.env> php arkadas_birim.php UYGULAMA_KOKU A_ID B_ID C_ID
// FriendManager metotlarının dönüş değerlerini ve sahiplik kurallarını doğrudan sınar.
// Her satır "[GEÇTİ] ..." ya da "[KALDI] ..." biçimindedir.

[$betik, $kok, $a, $b, $c] = $argv + [null, null, 0, 0, 0];
$a = (int) $a; $b = (int) $b; $c = (int) $c;

require_once $kok . '/service/FriendManager.php';

$conn = get_db_connection();

function ekle(mysqli $conn, int $gonderen, int $alan, int $durum): int {
    $s = $conn->prepare("INSERT INTO Arkadasliklar (IstekGonderenID, IstekAlanID, Durum) VALUES (?, ?, ?)");
    $s->bind_param("iii", $gonderen, $alan, $durum);
    $s->execute();
    return $conn->insert_id;
}
function durum(mysqli $conn, int $id): ?int {
    $r = $conn->query("SELECT Durum FROM Arkadasliklar WHERE ArkadaslikID = " . (int) $id)->fetch_assoc();
    return $r ? (int) $r['Durum'] : null;
}
function sonuc(bool $kosul, string $aciklama): void {
    echo ($kosul ? '[GEÇTİ] ' : '[KALDI] ') . $aciklama . PHP_EOL;
}

// acceptRequest
$id = ekle($conn, $a, $c, 0);
sonuc(FriendManager::acceptRequest($id, $b) === false && durum($conn, $id) === 0,
    'acceptRequest: başkasının isteğinde false döner, kayıt değişmez');
sonuc(FriendManager::acceptRequest($id, $c) === true && durum($conn, $id) === 1,
    'acceptRequest: alıcı bekleyen isteği kabul edince true döner');
sonuc(FriendManager::acceptRequest($id, $c) === false,
    'acceptRequest: zaten kabul edilmiş kayıtta (etkilenen satır yok) false döner');
sonuc(FriendManager::acceptRequest(999999, $c) === false,
    'acceptRequest: olmayan kayıtta false döner');

// rejectRequest
sonuc(FriendManager::rejectRequest($id, $c) === false && durum($conn, $id) === 1,
    'rejectRequest: kabul edilmiş arkadaşlığı silmez (yalnızca bekleyen istek)');
$id2 = ekle($conn, $a, $c, 0);
sonuc(FriendManager::rejectRequest($id2, $b) === false && durum($conn, $id2) === 0,
    'rejectRequest: alıcı olmayan (B) reddedemez');
sonuc(FriendManager::rejectRequest($id2, $a) === false && durum($conn, $id2) === 0,
    'rejectRequest: gönderen (A) "reddet" ile silemez');
sonuc(FriendManager::rejectRequest($id2, $c) === true && durum($conn, $id2) === null,
    'rejectRequest: alıcı (C) bekleyen isteği reddeder');

// removeFriend
sonuc(FriendManager::removeFriend($id, $b) === false && durum($conn, $id) === 1,
    'removeFriend: taraf olmayan (B) silemez');
$id3 = ekle($conn, $a, $c, 0);
sonuc(FriendManager::removeFriend($id3, $a) === false && durum($conn, $id3) === 0,
    'removeFriend: bekleyen isteği silmez (yalnızca kabul edilmiş arkadaşlık)');
sonuc(FriendManager::removeFriend($id, $c) === true && durum($conn, $id) === null,
    'removeFriend: taraf (C) arkadaşlığı siler');

$conn->query("DELETE FROM Arkadasliklar WHERE ArkadaslikID IN (" . (int) $id3 . ")");
