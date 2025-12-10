<?php
// view/ajax_islem.php - CHARMOJI FULL BACKEND
session_start();
header('Content-Type: application/json');

// Hataları gizle, JSON bozulmasın
ini_set('display_errors', 0);

require_once __DIR__ . '/../service/auth.php'; 

// 1. GÜVENLİK KONTROLÜ
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum kapalı.']);
    exit();
}

$kullaniciID = $_SESSION['user_id'];
$conn = get_db_connection();
$bugun = date('Y-m-d');

// ---------------------------------------------------------
// A. AVATAR İŞLEMLERİ (BU KISIM EKSİKTİ)
// ---------------------------------------------------------

// A1. Avatar Renklerini Kaydetme
if (isset($_POST['save_avatar_colors'])) {
    $colors = $_POST['colors']; // JSON string olarak gelir
    $stmt = $conn->prepare("UPDATE Kullanicilar SET AvatarVerisi = ? WHERE KullaniciID = ?");
    $stmt->bind_param("si", $colors, $kullaniciID);
    
    if($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Görünüm kaydedildi!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Veritabanı hatası.']);
    }
    exit();
}

// A2. Eşya Kuşanma (Equip)
if (isset($_POST['equip_item_id'])) {
    $esyaID = intval($_POST['equip_item_id']);
    
    try {
        $conn->begin_transaction();

        // 1. Eşyanın türünü bul (Şapka mı, Gözlük mü?)
        $turSql = $conn->query("SELECT EsyaTuru FROM MarketEsyalari WHERE EsyaID = $esyaID")->fetch_assoc();
        if(!$turSql) throw new Exception("Eşya bulunamadı.");
        $tur = $turSql['EsyaTuru'];

        // 2. Aynı türdeki diğer eşyaları çıkar (Sadece 1 şapka takılabilir)
        $conn->query("UPDATE KullaniciEnvanteri KE 
                      JOIN MarketEsyalari ME ON KE.EsyaID = ME.EsyaID 
                      SET KE.KusandiMi = 0 
                      WHERE KE.KullaniciID = $kullaniciID AND ME.EsyaTuru = '$tur'");

        // 3. Seçilen eşyayı kuşan
        $conn->query("UPDATE KullaniciEnvanteri SET KusandiMi = 1 WHERE KullaniciID = $kullaniciID AND EsyaID = $esyaID");

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// ---------------------------------------------------------
// B. MARKET İŞLEMLERİ
// ---------------------------------------------------------

if (isset($_POST['buy_item_id'])) {
    $esyaID = intval($_POST['buy_item_id']);
    
    try {
        $conn->begin_transaction();

        // Fiyat ve Bakiye Kontrolü
        $esya = $conn->query("SELECT Fiyat FROM MarketEsyalari WHERE EsyaID = $esyaID")->fetch_assoc();
        $user = $conn->query("SELECT Bakiye FROM Kullanicilar WHERE KullaniciID = $kullaniciID")->fetch_assoc();

        if (!$esya) throw new Exception("Ürün bulunamadı.");
        
        // Zaten var mı?
        $checkInv = $conn->query("SELECT EnvanterID FROM KullaniciEnvanteri WHERE KullaniciID = $kullaniciID AND EsyaID = $esyaID");
        if ($checkInv->num_rows > 0) throw new Exception("Zaten sahipsin.");

        // Para yetiyor mu?
        if ($user['Bakiye'] < $esya['Fiyat']) throw new Exception("Yetersiz bakiye.");

        // Satın alma işlemi
        $newBalance = $user['Bakiye'] - $esya['Fiyat'];
        $conn->query("UPDATE Kullanicilar SET Bakiye = $newBalance WHERE KullaniciID = $kullaniciID");
        $conn->query("INSERT INTO KullaniciEnvanteri (KullaniciID, EsyaID) VALUES ($kullaniciID, $esyaID)");

        $conn->commit();
        echo json_encode(['success' => true, 'new_balance' => $newBalance, 'message' => 'Satın alındı!']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// ---------------------------------------------------------
// C. GÜNLÜK GÖREV ÖDÜLÜ
// ---------------------------------------------------------

if (isset($_POST['claim_quest_id'])) {
    $kg_id = intval($_POST['claim_quest_id']);
    
    try {
        $conn->begin_transaction();

        $sql = "SELECT KG.TamamlandiMi, KG.OdulAlindiMi, GT.OdulPara 
                FROM KullaniciGunlukGorevleri KG
                JOIN GorevTanimlari GT ON KG.GorevTanimID = GT.GorevTanimID
                WHERE KG.KullaniciGorevID = ? AND KG.KullaniciID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $kg_id, $kullaniciID);
        $stmt->execute();
        $gorev = $stmt->get_result()->fetch_assoc();

        if ($gorev && $gorev['TamamlandiMi'] == 1 && $gorev['OdulAlindiMi'] == 0) {
            $conn->query("UPDATE Kullanicilar SET Bakiye = Bakiye + {$gorev['OdulPara']} WHERE KullaniciID = $kullaniciID");
            $conn->query("UPDATE KullaniciGunlukGorevleri SET OdulAlindiMi = 1 WHERE KullaniciGorevID = $kg_id");
            
            $newBalance = $conn->query("SELECT Bakiye FROM Kullanicilar WHERE KullaniciID = $kullaniciID")->fetch_assoc()['Bakiye'];
            
            $conn->commit();
            echo json_encode(['success' => true, 'new_balance' => $newBalance]);
        } else {
            throw new Exception("Ödül alınamaz.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// ---------------------------------------------------------
// D. ALIŞKANLIK YÖNETİMİ (SİLME)
// ---------------------------------------------------------

if (isset($_POST['is_delete']) && $_POST['is_delete'] == 1) {
    $ka_id = intval($_POST['ka_id']);
    $stmt = $conn->prepare("DELETE FROM KullaniciAliskanliklari WHERE KullaniciAliskanlikID = ? AND KullaniciID = ?");
    $stmt->bind_param("ii", $ka_id, $kullaniciID);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Silinemedi']);
    }
    exit();
}

// ---------------------------------------------------------
// E. ALIŞKANLIK İŞARETLEME (ANA İŞLEM)
// ---------------------------------------------------------

// Eğer yukarıdaki özel işlemlerden hiçbiri değilse, bu bir checkbox işaretlemesidir.
if (!isset($_POST['ka_id']) || !isset($_POST['durum'])) {
    echo json_encode(['success' => false, 'message' => 'Eksik parametre.']);
    exit();
}

$ka_id = intval($_POST['ka_id']);
$durum = intval($_POST['durum']); 
$dunkuTarih = date('Y-m-d', strtotime('-1 day'));

// İstatistik fonksiyonu
function hesaplaLevelVerileri($toplamPuan) {
    $level = 1; $gerekliXP = 100;
    while ($toplamPuan >= $gerekliXP) { $toplamPuan -= $gerekliXP; $level++; $gerekliXP *= 2; }
    return ['level' => $level, 'currentXP' => $toplamPuan, 'neededXP' => $gerekliXP, 'percent' => ($toplamPuan / $gerekliXP) * 100];
}

try {
    $conn->begin_transaction();

    $sql = "SELECT KA.Seri, KA.SonIslemTarihi, A.Puan, A.StatTuru 
            FROM KullaniciAliskanliklari KA
            JOIN Aliskanliklar A ON KA.AliskanlikID = A.AliskanlikID
            WHERE KA.KullaniciAliskanlikID = ? AND KA.KullaniciID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $ka_id, $kullaniciID);
    $stmt->execute();
    $veri = $stmt->get_result()->fetch_assoc();

    if (!$veri) throw new Exception("Kayıt bulunamadı.");

    $basePuan = $veri['Puan'];
    $mevcutSeri = $veri['Seri'];
    $sonTarih = $veri['SonIslemTarihi'];
    $statTuru = $veri['StatTuru'];

    if ($durum === 1) {
        // --- YAPILDI ---
        if ($sonTarih == $dunkuTarih) $yeniSeri = $mevcutSeri + 1;
        elseif ($sonTarih == $bugun) $yeniSeri = $mevcutSeri;
        else $yeniSeri = 1;

        $carpan = min(2.00, 1 + (floor($yeniSeri / 7) * 0.25));
        $kazanilanPuan = round($basePuan * $carpan);

        $stmt = $conn->prepare("INSERT IGNORE INTO TakipKayitlari (KullaniciAliskanlikID, Tarih, TamamlandiMi) VALUES (?, ?, 1)");
        $stmt->bind_param("is", $ka_id, $bugun);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $conn->query("UPDATE KullaniciAliskanliklari SET Seri = $yeniSeri, SonIslemTarihi = '$bugun' WHERE KullaniciAliskanlikID = $ka_id");
            $conn->query("UPDATE KullaniciStatlari SET ToplamPuan = ToplamPuan + $kazanilanPuan WHERE KullaniciID = $kullaniciID AND StatTuru = '$statTuru'");
            
            // Günlük görevi ilerlet
            $conn->query("UPDATE KullaniciGunlukGorevleri KG JOIN GorevTanimlari GT ON KG.GorevTanimID = GT.GorevTanimID 
                          SET KG.MevcutIlerleme = KG.MevcutIlerleme + 1, KG.TamamlandiMi = CASE WHEN (KG.MevcutIlerleme + 1) >= GT.HedefSayi THEN 1 ELSE 0 END
                          WHERE KG.KullaniciID = $kullaniciID AND KG.Tarih = '$bugun' AND KG.TamamlandiMi = 0 AND GT.Baslik LIKE '%alışkanlık%'");
        }
    } else {
        // --- GERİ ALINDI ---
        $carpan = min(2.00, 1 + (floor($mevcutSeri / 7) * 0.25));
        $silinecekPuan = round($basePuan * $carpan);

        $stmt = $conn->prepare("DELETE FROM TakipKayitlari WHERE KullaniciAliskanlikID = ? AND Tarih = ?");
        $stmt->bind_param("is", $ka_id, $bugun);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $yeniSeri = max(0, $mevcutSeri - 1);
            $conn->query("UPDATE KullaniciAliskanliklari SET Seri = $yeniSeri WHERE KullaniciAliskanlikID = $ka_id");
            $conn->query("UPDATE KullaniciStatlari SET ToplamPuan = ToplamPuan - $silinecekPuan WHERE KullaniciID = $kullaniciID AND StatTuru = '$statTuru'");
        } else {
            $yeniSeri = $mevcutSeri;
        }
    }

    $conn->commit();

    // Güncel verileri dön
    $statSql = $conn->query("SELECT ToplamPuan FROM KullaniciStatlari WHERE KullaniciID = $kullaniciID AND StatTuru = '$statTuru'");
    $yeniToplamPuan = $statSql->fetch_assoc()['ToplamPuan'];
    $statVerisi = hesaplaLevelVerileri($yeniToplamPuan);

    $allStats = $conn->query("SELECT ToplamPuan FROM KullaniciStatlari WHERE KullaniciID = $kullaniciID");
    $toplamLevel = 0; $count = 0;
    while($r = $allStats->fetch_assoc()) {
        $l = hesaplaLevelVerileri($r['ToplamPuan']);
        $toplamLevel += $l['level'];
        $count++;
    }
    $genelLevel = ($count > 0) ? floor($toplamLevel / $count) : 1;

    echo json_encode([
        'success' => true,
        'new_streak' => isset($yeniSeri) ? $yeniSeri : $mevcutSeri,
        'stat_type' => $statTuru,
        'stat_data' => $statVerisi,
        'global_level' => $genelLevel
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>