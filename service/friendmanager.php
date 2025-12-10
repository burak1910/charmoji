<?php
// service/FriendManager.php

require_once __DIR__ . '/auth.php'; 

class FriendManager {

    // 1. Arkadaşlık İsteği Gönder
    public static function sendRequest($myID, $targetID) {
        $conn = get_db_connection();

        if ($myID == $targetID) {
            return "Kendini ekleyemezsin.";
        }

        $check = $conn->prepare("SELECT ArkadaslikID FROM Arkadasliklar WHERE (IstekGonderenID = ? AND IstekAlanID = ?) OR (IstekGonderenID = ? AND IstekAlanID = ?)");
        $check->bind_param("iiii", $myID, $targetID, $targetID, $myID);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            return "Zaten arkadaşsınız veya istek gönderilmiş.";
        }

        // DÜZELTME: 'Beklemede' yerine 0 gönderiyoruz.
        $stmt = $conn->prepare("INSERT INTO Arkadasliklar (IstekGonderenID, IstekAlanID, Durum) VALUES (?, ?, 0)");
        $stmt->bind_param("ii", $myID, $targetID);
        
        if ($stmt->execute()) {
            return true;
        } else {
            return "Veritabanı hatası: " . $stmt->error;
        }
    }

    // 7. Topluluk Listesi (Arkadaş olmadığım herkesi getir)
    public static function getCommunitySuggestions($myID) {
        $conn = get_db_connection();
        
        // Mantık: Ben değilsem VE Arkadaş tablosunda (kabul veya beklemede) kaydımız yoksa getir.
        $sql = "
            SELECT k.KullaniciID, k.Ad, k.Eposta, 
                   (SELECT COALESCE(SUM(ToplamPuan),0) FROM KullaniciStatlari WHERE KullaniciID = k.KullaniciID) as ToplamXP
            FROM Kullanicilar k
            WHERE k.KullaniciID != ?
            AND NOT EXISTS (
                SELECT 1 FROM Arkadasliklar a 
                WHERE (a.IstekGonderenID = k.KullaniciID AND a.IstekAlanID = ?) 
                   OR (a.IstekGonderenID = ? AND a.IstekAlanID = k.KullaniciID)
            )
            ORDER BY ToplamXP DESC -- En yüksek puanlıları üstte göster (Prestij)
            LIMIT 50
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $myID, $myID, $myID);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // 2. İsteği Kabul Et
    public static function acceptRequest($istekID, $myID) {
        $conn = get_db_connection();
        // DÜZELTME: 'Kabul' yerine 1 yapıyoruz.
        $stmt = $conn->prepare("UPDATE Arkadasliklar SET Durum = 1 WHERE ArkadaslikID = ? AND IstekAlanID = ?");
        $stmt->bind_param("ii", $istekID, $myID);
        return $stmt->execute();
    }

    // 3. İsteği Reddet veya Arkadaşı Sil
    public static function removeFriend($istekID) {
        $conn = get_db_connection();
        $stmt = $conn->prepare("DELETE FROM Arkadasliklar WHERE ArkadaslikID = ?");
        $stmt->bind_param("i", $istekID);
        return $stmt->execute();
    }

    // 4. Bana Gelen Bekleyen İstekler
    public static function getPendingRequests($myID) {
        $conn = get_db_connection();
        // DÜZELTME: Durum = 0 (Bekleyenler)
        $sql = "SELECT ArkadaslikID as IstekID, K.Ad, K.Eposta 
                FROM Arkadasliklar A 
                JOIN Kullanicilar K ON A.IstekGonderenID = K.KullaniciID 
                WHERE A.IstekAlanID = ? AND A.Durum = 0";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $myID);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // 5. Mevcut Arkadaşlarım
    public static function getFriends($myID) {
        $conn = get_db_connection();
        // DÜZELTME: Durum = 1 (Kabul Edilenler)
        $sql = "SELECT K.Ad, K.Eposta, ArkadaslikID as ArkadaslikID
                FROM Arkadasliklar A
                JOIN Kullanicilar K ON 
                    (CASE 
                        WHEN A.IstekGonderenID = ? THEN A.IstekAlanID = K.KullaniciID
                        WHEN A.IstekAlanID = ? THEN A.IstekGonderenID = K.KullaniciID
                     END)
                WHERE (A.IstekGonderenID = ? OR A.IstekAlanID = ?) 
                  AND A.Durum = 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiii", $myID, $myID, $myID, $myID);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // 6. Kullanıcı Ara
    public static function searchUsers($keyword, $myID) {
        $conn = get_db_connection();
        
        $excludeCondition = "
            AND NOT EXISTS (
                SELECT 1 FROM Arkadasliklar a 
                WHERE (a.IstekGonderenID = k.KullaniciID AND a.IstekAlanID = ?) 
                   OR (a.IstekGonderenID = ? AND a.IstekAlanID = k.KullaniciID)
            )
        ";

        if (empty($keyword)) {
            $sql = "SELECT k.KullaniciID, k.Ad, k.Eposta 
                    FROM Kullanicilar k 
                    WHERE k.KullaniciID != ? 
                    $excludeCondition 
                    ORDER BY RAND() LIMIT 5";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $myID, $myID, $myID);

        } else {
            $term = "%" . $keyword . "%";
            $sql = "SELECT k.KullaniciID, k.Ad, k.Eposta 
                    FROM Kullanicilar k 
                    WHERE (k.Ad LIKE ? OR k.Eposta LIKE ?) 
                    AND k.KullaniciID != ? 
                    $excludeCondition 
                    LIMIT 20";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssiii", $term, $term, $myID, $myID, $myID);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>