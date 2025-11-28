<?php
// service/FriendManager.php

// Veritabanı bağlantısı için auth.php'yi çağırıyoruz.
// Eğer auth.php aynı klasördeyse (service içindeyse):
require_once __DIR__ . '/auth.php'; 

class FriendManager {

    // 1. Arkadaşlık İsteği Gönder
    public static function sendRequest($gonderenID, $aliciID) {
        $conn = get_db_connection();

        // Kendine istek atamasın
        if ($gonderenID == $aliciID) {
            return "Kendini ekleyemezsin.";
        }

        // Zaten arkadaş mı veya istek var mı?
        // Hem Gonderen->Alici hem de Alici->Gonderen kontrol edilmeli
        $check = $conn->prepare("SELECT ID FROM Arkadaslar WHERE (GonderenID = ? AND AliciID = ?) OR (GonderenID = ? AND AliciID = ?)");
        $check->bind_param("iiii", $gonderenID, $aliciID, $aliciID, $gonderenID);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            return "Zaten arkadaşsınız veya istek gönderilmiş.";
        }

        // İsteği Kaydet (Durum varsayılan olarak 'Beklemede')
        $stmt = $conn->prepare("INSERT INTO Arkadaslar (GonderenID, AliciID, Durum) VALUES (?, ?, 'Beklemede')");
        $stmt->bind_param("ii", $gonderenID, $aliciID);
        
        if ($stmt->execute()) {
            return true;
        } else {
            return "Veritabanı hatası: " . $stmt->error;
        }
    }

    // 2. İsteği Kabul Et
    public static function acceptRequest($istekID, $aliciID) {
        $conn = get_db_connection();
        
        // Güvenlik: Sadece isteği alan kişi (AliciID) kabul edebilir
        $stmt = $conn->prepare("UPDATE Arkadaslar SET Durum = 'Kabul' WHERE ID = ? AND AliciID = ?");
        $stmt->bind_param("ii", $istekID, $aliciID);
        
        return $stmt->execute();
    }

    // 3. İsteği Reddet veya Arkadaşı Sil
    public static function removeFriend($istekID) {
        $conn = get_db_connection();
        
        // ID'si bilinen arkadaşlık kaydını sil
        $stmt = $conn->prepare("DELETE FROM Arkadaslar WHERE ID = ?");
        $stmt->bind_param("i", $istekID);
        
        return $stmt->execute();
    }

    // 4. Bana Gelen Bekleyen İstekleri Listele
    public static function getPendingRequests($myID) {
        $conn = get_db_connection();
        
        // Arkadaslar tablosu ile Kullanicilar tablosunu birleştir (JOIN)
        // İsteyen kişinin adını (Ad) öğrenmek için
        $sql = "SELECT A.ID as IstekID, K.Ad, K.Eposta 
                FROM Arkadaslar A 
                JOIN Kullanicilar K ON A.GonderenID = K.KullaniciID 
                WHERE A.AliciID = ? AND A.Durum = 'Beklemede'";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $myID);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // 5. Mevcut Arkadaşlarımı Listele
    public static function getFriends($myID) {
        $conn = get_db_connection();
        
        // Arkadaşlık iki yönlüdür.
        // Sen göndermiş olabilirsin (GonderenID = Sen) YA DA sana gelmiş olabilir (AliciID = Sen).
        // Durumu 'Kabul' olanları çekiyoruz.
        
        $sql = "SELECT K.Ad, K.Eposta, A.ID as ArkadaslikID
                FROM Arkadaslar A
                JOIN Kullanicilar K ON 
                    (CASE 
                        WHEN A.GonderenID = ? THEN A.AliciID = K.KullaniciID
                        WHEN A.AliciID = ? THEN A.GonderenID = K.KullaniciID
                     END)
                WHERE (A.GonderenID = ? OR A.AliciID = ?) 
                  AND A.Durum = 'Kabul'";

        $stmt = $conn->prepare($sql);
        // Sorguda 4 tane ? işareti var, hepsi benim ID'm
        $stmt->bind_param("iiii", $myID, $myID, $myID, $myID);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // 6. Kullanıcı Ara (Arkadaş eklemek için liste)
    public static function searchUsers($keyword, $myID) {
        $conn = get_db_connection();
        
        // Kendim hariç diğer kullanıcıları getir
        // Eğer arama kelimesi boşsa rastgele 5 kişi getir
        if (empty($keyword)) {
            $stmt = $conn->prepare("SELECT KullaniciID, Ad, Eposta FROM Kullanicilar WHERE KullaniciID != ? ORDER BY RAND() LIMIT 5");
            $stmt->bind_param("i", $myID);
        } else {
            $term = "%" . $keyword . "%";
            $stmt = $conn->prepare("SELECT KullaniciID, Ad, Eposta FROM Kullanicilar WHERE Ad LIKE ? AND KullaniciID != ? LIMIT 10");
            $stmt->bind_param("si", $term, $myID);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>