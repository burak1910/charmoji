<?php
// service/FriendManager.php

// Veritabanı bağlantısını çekiyoruz
require_once __DIR__ . '/auth.php'; 

class FriendManager {

    // 1. Arkadaşlık İsteği Gönder
    public static function sendRequest($myID, $targetID) {
        $conn = get_db_connection();

        if ($myID == $targetID) {
            return "Kendini ekleyemezsin.";
        }

        $check = $conn->prepare("SELECT ID FROM Arkadaslar WHERE (GonderenID = ? AND AliciID = ?) OR (GonderenID = ? AND AliciID = ?)");
        $check->bind_param("iiii", $myID, $targetID, $targetID, $myID);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            return "Zaten arkadaşsınız veya istek gönderilmiş.";
        }

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
        $stmt = $conn->prepare("UPDATE Arkadaslar SET Durum = 'Kabul' WHERE ID = ? AND AliciID = ?");
        $stmt->bind_param("ii", $istekID, $myID);
        return $stmt->execute();
    }

    // 3. İsteği Reddet veya Arkadaşı Sil
    public static function removeFriend($istekID) {
        $conn = get_db_connection();
        $stmt = $conn->prepare("DELETE FROM Arkadaslar WHERE ID = ?");
        $stmt->bind_param("i", $istekID);
        return $stmt->execute();
    }

    // 4. Bana Gelen Bekleyen İstekler
    public static function getPendingRequests($myID) {
        $conn = get_db_connection();
        $sql = "SELECT A.ID as IstekID, K.Ad, K.Eposta 
                FROM Arkadaslar A 
                JOIN Kullanicilar K ON A.GonderenID = K.KullaniciID 
                WHERE A.AliciID = ? AND A.Durum = 'Beklemede'";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $myID);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // 5. Mevcut Arkadaşlarım
    public static function getFriends($myID) {
        $conn = get_db_connection();
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
    
    // 6. Kullanıcı Ara (GELİŞMİŞ FİLTRELEME İLE)
    // Artık mevcut arkadaşları veya istek atılmış kişileri listelemez.
    public static function searchUsers($keyword, $myID) {
        $conn = get_db_connection();
        
        // Bu SQL parçası, "Eğer bu kişiyle aramda bir kayıt varsa onu getirme" der.
        // NOT EXISTS yapısı performans açısından en hızlısıdır.
        $excludeCondition = "
            AND NOT EXISTS (
                SELECT 1 FROM Arkadaslar a 
                WHERE (a.GonderenID = k.KullaniciID AND a.AliciID = ?) 
                   OR (a.GonderenID = ? AND a.AliciID = k.KullaniciID)
            )
        ";

        if (empty($keyword)) {
            // Arama yoksa RASTGELE öner (Ama ekli olanlar hariç)
            $sql = "SELECT k.KullaniciID, k.Ad, k.Eposta 
                    FROM Kullanicilar k 
                    WHERE k.KullaniciID != ? 
                    $excludeCondition 
                    ORDER BY RAND() LIMIT 5";
            
            $stmt = $conn->prepare($sql);
            // Parametreler: (BenimID, BenimID[filtre1], BenimID[filtre2])
            $stmt->bind_param("iii", $myID, $myID, $myID);

        } else {
            // Arama varsa İSİM/E-POSTA ara (Ama ekli olanlar hariç)
            $term = "%" . $keyword . "%";
            $sql = "SELECT k.KullaniciID, k.Ad, k.Eposta 
                    FROM Kullanicilar k 
                    WHERE (k.Ad LIKE ? OR k.Eposta LIKE ?) 
                    AND k.KullaniciID != ? 
                    $excludeCondition 
                    LIMIT 20";
            
            $stmt = $conn->prepare($sql);
            // Parametreler: (Kelime, Kelime, BenimID, BenimID[filtre1], BenimID[filtre2])
            $stmt->bind_param("ssiii", $term, $term, $myID, $myID, $myID);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>