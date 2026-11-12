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

        try {
            $stmt->execute();
            return true;
        } catch (mysqli_sql_exception $e) {
            // SQL hata metni kullanıcıya gösterilmez, log'a yazılır (ör. olmayan kullanıcı ID'si).
            error_log('[charmoji] Arkadaşlık isteği eklenemedi: ' . $e->getMessage());
            return "İstek gönderilemedi.";
        }
    }

    // 7. Topluluk Listesi (Arkadaş olmadığım herkesi getir)
    public static function getCommunitySuggestions($myID) {
        $conn = get_db_connection();
        
        // Mantık: Ben değilsem VE Arkadaş tablosunda (kabul veya beklemede) kaydımız yoksa getir.
        $sql = "
            SELECT k.KullaniciID, k.Ad, k.AvatarVerisi,
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
    // Yalnızca isteğin ALICISI, yalnızca BEKLEYEN (Durum = 0) bir isteği kabul edebilir.
    // Hiçbir satır değişmediyse (başkasının kaydı, zaten kabul edilmiş, yok) false döner.
    public static function acceptRequest($istekID, $myID) {
        $conn = get_db_connection();
        $stmt = $conn->prepare("UPDATE Arkadasliklar SET Durum = 1 WHERE ArkadaslikID = ? AND IstekAlanID = ? AND Durum = 0");
        $stmt->bind_param("ii", $istekID, $myID);
        $stmt->execute();
        return $stmt->affected_rows === 1;
    }

    // 3a. İsteği Reddet
    // Yalnızca isteğin ALICISI, yalnızca BEKLEYEN bir isteği reddedebilir (kayıt silinir).
    public static function rejectRequest($istekID, $myID) {
        $conn = get_db_connection();
        $stmt = $conn->prepare("DELETE FROM Arkadasliklar WHERE ArkadaslikID = ? AND IstekAlanID = ? AND Durum = 0");
        $stmt->bind_param("ii", $istekID, $myID);
        $stmt->execute();
        return $stmt->affected_rows === 1;
    }

    // 3b. Arkadaşı Sil
    // Yalnızca kabul edilmiş (Durum = 1) bir arkadaşlığı, yalnızca iki taraftan biri silebilir.
    public static function removeFriend($arkadaslikID, $myID) {
        $conn = get_db_connection();
        $stmt = $conn->prepare("DELETE FROM Arkadasliklar WHERE ArkadaslikID = ? AND Durum = 1 AND (IstekGonderenID = ? OR IstekAlanID = ?)");
        $stmt->bind_param("iii", $arkadaslikID, $myID, $myID);
        $stmt->execute();
        return $stmt->affected_rows === 1;
    }

    // 4. Bana Gelen Bekleyen İstekler
    public static function getPendingRequests($myID) {
        $conn = get_db_connection();
        // DÜZELTME: Durum = 0 (Bekleyenler)
        $sql = "SELECT ArkadaslikID as IstekID, K.KullaniciID, K.Ad, K.AvatarVerisi
                FROM Arkadasliklar A 
                JOIN Kullanicilar K ON A.IstekGonderenID = K.KullaniciID 
                WHERE A.IstekAlanID = ? AND A.Durum = 0
                ORDER BY A.ArkadaslikID";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $myID);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // 5. Mevcut Arkadaşlarım
    public static function getFriends($myID) {
        $conn = get_db_connection();
        // DÜZELTME: Durum = 1 (Kabul Edilenler)
        $sql = "SELECT K.KullaniciID, K.Ad, K.AvatarVerisi, ArkadaslikID as ArkadaslikID
                FROM Arkadasliklar A
                JOIN Kullanicilar K ON 
                    (CASE 
                        WHEN A.IstekGonderenID = ? THEN A.IstekAlanID = K.KullaniciID
                        WHEN A.IstekAlanID = ? THEN A.IstekGonderenID = K.KullaniciID
                     END)
                WHERE (A.IstekGonderenID = ? OR A.IstekAlanID = ?) 
                  AND A.Durum = 1
                ORDER BY K.Ad";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiii", $myID, $myID, $myID, $myID);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // 5b. Benim gönderdiğim, bekleyen istekler
    public static function getSentRequests($myID) {
        $conn = get_db_connection();
        $stmt = $conn->prepare("SELECT A.ArkadaslikID AS IstekID, K.KullaniciID, K.Ad, K.AvatarVerisi
                                FROM Arkadasliklar A
                                JOIN Kullanicilar K ON A.IstekAlanID = K.KullaniciID
                                WHERE A.IstekGonderenID = ? AND A.Durum = 0
                                ORDER BY A.ArkadaslikID");
        $stmt->bind_param("i", $myID);
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
            $sql = "SELECT k.KullaniciID, k.Ad
                    FROM Kullanicilar k 
                    WHERE k.KullaniciID != ? 
                    $excludeCondition 
                    ORDER BY RAND() LIMIT 5";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $myID, $myID, $myID);

        } else {
            // Yalnızca kahraman adıyla, "içerir" eşleşmesi (UX 05 §6, S13). E-postayla arama
            // yapılmaz (e-posta adresi tahmin edilerek hesap bulunamasın). LIKE joker
            // karakterleri (% _ \) kullanıcı girdisinde düz karakter sayılır.
            $term = "%" . addcslashes((string) $keyword, '%_\\') . "%";
            $sql = "SELECT k.KullaniciID, k.Ad, k.AvatarVerisi
                    FROM Kullanicilar k 
                    WHERE k.Ad LIKE ?
                    AND k.KullaniciID != ? 
                    $excludeCondition 
                    ORDER BY k.Ad
                    LIMIT 20";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siii", $term, $myID, $myID, $myID);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>