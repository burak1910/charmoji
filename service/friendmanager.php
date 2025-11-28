<?php
// service/FriendManager.php

// Veritabanı bağlantısını çekiyoruz
require_once __DIR__ . '/auth.php'; 

class FriendManager {

    // 1. Arkadaşlık İsteği Gönder
    public static function sendRequest($myID, $targetID) {
        $conn = get_db_connection();

        // Kendine istek atamasın
        if ($myID == $targetID) {
            return "Kendini ekleyemezsin.";
        }

        // Zaten arkadaş mı veya istek var mı?
        // TABLO: Arkadaslar | SÜTUNLAR: GonderenID, AliciID
        // SENİN TABLONDA ID sütunu 'ID' olarak geçiyor.
        $check = $conn->prepare("SELECT ID FROM Arkadaslar WHERE (GonderenID = ? AND AliciID = ?) OR (GonderenID = ? AND AliciID = ?)");
        $check->bind_param("iiii", $myID, $targetID, $targetID, $myID);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            return "Zaten arkadaşsınız veya istek gönderilmiş.";
        }

        // İsteği Kaydet
        // DİKKAT: Senin tablonda Durum sütunu ENUM. O yüzden '0' değil 'Beklemede' yazıyoruz.
        $stmt = $conn->prepare("INSERT INTO Arkadaslar (GonderenID, AliciID, Durum) VALUES (?, ?, 'Beklemede')");
        $stmt->bind_param("ii", $myID, $targetID);
        
        if ($stmt->execute()) {
            return true;
        } else {
            return "Veritabanı hatası: " . $stmt->error;
        }
    }

    // 2. İsteği Kabul Et
    public static function acceptRequest($istekID, $myID) {
        $conn = get_db_connection();
        
        // Güvenlik: Sadece isteği alan kişi (AliciID) kabul edebilir.
        // DİKKAT: Durum sütunu ENUM olduğu için '1' yerine 'Kabul' yazıyoruz.
        // Tablodaki ID sütunu: ID
        $stmt = $conn->prepare("UPDATE Arkadaslar SET Durum = 'Kabul' WHERE ID = ? AND AliciID = ?");
        $stmt->bind_param("ii", $istekID, $myID);
        
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
        
        // Durum = 'Beklemede' olanları çek (ENUM yapısına uygun)
        // Frontend 'IstekID' beklediği için 'ID as IstekID' yapıyoruz.
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
        
        // Durum = 'Kabul' olanları çek
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
        $stmt->bind_param("iiii", $myID, $myID, $myID, $myID);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // 6. Kullanıcı Ara (Hem İsim Hem E-posta)
    public static function searchUsers($keyword, $myID) {
        $conn = get_db_connection();
        
        if (empty($keyword)) {
            // Arama yoksa rastgele öner
            $stmt = $conn->prepare("SELECT KullaniciID, Ad, Eposta FROM Kullanicilar WHERE KullaniciID != ? ORDER BY RAND() LIMIT 5");
            $stmt->bind_param("i", $myID);
        } else {
            // Arama varsa: İsim VEYA E-posta içinde ara
            $term = "%" . $keyword . "%";
            $stmt = $conn->prepare("
                SELECT KullaniciID, Ad, Eposta 
                FROM Kullanicilar 
                WHERE (Ad LIKE ? OR Eposta LIKE ?) 
                AND KullaniciID != ? 
                LIMIT 20
            ");
            $stmt->bind_param("ssi", $term, $term, $myID);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>