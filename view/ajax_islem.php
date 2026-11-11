<?php
// view/ajax_islem.php - CHARMOJI FULL BACKEND
header('Content-Type: application/json');

// Hata yanıtları da JSON olsun (bkz. config/uygulama.php).
define('CHARMOJI_JSON_YANIT', true);

// Oturum ve hata ayarları (display_errors kapalı) auth.php içinde yapılır.
require_once __DIR__ . '/../service/auth.php';
require_once __DIR__ . '/avatar_renderer.php'; // Renk doğrulama fonksiyonları

// 1. GÜVENLİK KONTROLÜ
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum kapalı.']);
    exit();
}

$kullaniciID = (int) $_SESSION['user_id'];
$conn = get_db_connection();
$bugun = date('Y-m-d');

// Kullanıcıya gösterilebilecek (iş kuralı) hatalar. Bunların dışındaki her
// istisna (ör. mysqli_sql_exception) log'a yazılır, kullanıcıya genel mesaj döner.
class KullaniciHatasi extends Exception {}

function ajax_hata_mesaji(Throwable $e): string {
    if ($e instanceof KullaniciHatasi) {
        return $e->getMessage();
    }
    error_log(sprintf('[charmoji] ajax_islem hatası %s: %s (%s:%d)',
        get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));
    return 'İşlem sırasında bir sunucu hatası oluştu.';
}

// Hazır ifade (prepared statement) kısayolu: sorguyu hazırlar, bağlar, çalıştırır.
function sorgu(mysqli $conn, string $sql, string $tipler = '', array $degerler = []): mysqli_stmt {
    $stmt = $conn->prepare($sql);
    if ($tipler !== '') {
        $stmt->bind_param($tipler, ...$degerler);
    }
    $stmt->execute();
    return $stmt;
}

// ---------------------------------------------------------
// A. AVATAR İŞLEMLERİ (BU KISIM EKSİKTİ)
// ---------------------------------------------------------

// A1. Avatar Renklerini Kaydetme
if (isset($_POST['save_avatar_colors'])) {
    // JSON metni olarak gelir; yalnızca izinli anahtarlar ve #RRGGBB değerler kabul edilir.
    $ham = $_POST['colors'] ?? '';
    $gelen = (is_string($ham) && strlen($ham) <= 1000) ? json_decode($ham, true) : null;

    $temiz = [];
    $gecerli = is_array($gelen) && $gelen !== [];
    if ($gecerli) {
        foreach ($gelen as $anahtar => $renk) {
            if (!array_key_exists($anahtar, AVATAR_VARSAYILAN_RENKLER) || !avatar_renk_gecerli_mi($renk)) {
                $gecerli = false;
                break;
            }
            $temiz[$anahtar] = $renk;
        }
    }
    if (!$gecerli) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Geçersiz renk verisi. Renkler #RRGGBB biçiminde olmalı.']);
        exit();
    }

    // Eksik anahtarlar varsayılanla tamamlanır; veritabanına yalnızca doğrulanmış veri yazılır.
    $colors = json_encode(array_merge(AVATAR_VARSAYILAN_RENKLER, $temiz));
    try {
        sorgu($conn, "UPDATE Kullanicilar SET AvatarVerisi = ? WHERE KullaniciID = ?", "si", [$colors, $kullaniciID]);
        echo json_encode(['success' => true, 'message' => 'Görünüm kaydedildi!']);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => ajax_hata_mesaji($e)]);
    }
    exit();
}

// A2. Eşya Kuşanma (Equip)
if (isset($_POST['equip_item_id'])) {
    $esyaID = intval($_POST['equip_item_id']);

    try {
        $conn->begin_transaction();

        // 1. Eşyanın türünü bul (Şapka mı, Gözlük mü?)
        $turSql = sorgu($conn, "SELECT EsyaTuru FROM MarketEsyalari WHERE EsyaID = ?", "i", [$esyaID])
            ->get_result()->fetch_assoc();
        if(!$turSql) throw new KullaniciHatasi("Eşya bulunamadı.");
        $tur = $turSql['EsyaTuru'];

        // 2. Aynı türdeki diğer eşyaları çıkar (Sadece 1 şapka takılabilir)
        // EsyaTuru veritabanından gelen metindir: sorguya gömülmez, bağlanır.
        sorgu($conn, "UPDATE KullaniciEnvanteri KE
                      JOIN MarketEsyalari ME ON KE.EsyaID = ME.EsyaID
                      SET KE.KusandiMi = 0
                      WHERE KE.KullaniciID = ? AND ME.EsyaTuru = ?", "is", [$kullaniciID, $tur]);

        // 3. Seçilen eşyayı kuşan
        sorgu($conn, "UPDATE KullaniciEnvanteri SET KusandiMi = 1 WHERE KullaniciID = ? AND EsyaID = ?",
            "ii", [$kullaniciID, $esyaID]);

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => ajax_hata_mesaji($e)]);
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
        $esya = sorgu($conn, "SELECT Fiyat FROM MarketEsyalari WHERE EsyaID = ?", "i", [$esyaID])
            ->get_result()->fetch_assoc();
        $user = sorgu($conn, "SELECT Bakiye FROM Kullanicilar WHERE KullaniciID = ?", "i", [$kullaniciID])
            ->get_result()->fetch_assoc();

        if (!$esya) throw new KullaniciHatasi("Ürün bulunamadı.");

        // Zaten var mı?
        $checkInv = sorgu($conn, "SELECT EnvanterID FROM KullaniciEnvanteri WHERE KullaniciID = ? AND EsyaID = ?",
            "ii", [$kullaniciID, $esyaID])->get_result();
        if ($checkInv->num_rows > 0) throw new KullaniciHatasi("Zaten sahipsin.");

        // Para yetiyor mu?
        if ($user['Bakiye'] < $esya['Fiyat']) throw new KullaniciHatasi("Yetersiz bakiye.");

        // Satın alma işlemi
        $newBalance = $user['Bakiye'] - $esya['Fiyat'];
        sorgu($conn, "UPDATE Kullanicilar SET Bakiye = ? WHERE KullaniciID = ?", "di", [$newBalance, $kullaniciID]);
        sorgu($conn, "INSERT INTO KullaniciEnvanteri (KullaniciID, EsyaID) VALUES (?, ?)", "ii", [$kullaniciID, $esyaID]);

        $conn->commit();
        echo json_encode(['success' => true, 'new_balance' => $newBalance, 'message' => 'Satın alındı!']);

    } catch (Throwable $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => ajax_hata_mesaji($e)]);
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
        $gorev = sorgu($conn, $sql, "ii", [$kg_id, $kullaniciID])->get_result()->fetch_assoc();

        if ($gorev && $gorev['TamamlandiMi'] == 1 && $gorev['OdulAlindiMi'] == 0) {
            sorgu($conn, "UPDATE Kullanicilar SET Bakiye = Bakiye + ? WHERE KullaniciID = ?",
                "di", [$gorev['OdulPara'], $kullaniciID]);
            sorgu($conn, "UPDATE KullaniciGunlukGorevleri SET OdulAlindiMi = 1 WHERE KullaniciGorevID = ? AND KullaniciID = ?",
                "ii", [$kg_id, $kullaniciID]);

            $newBalance = sorgu($conn, "SELECT Bakiye FROM Kullanicilar WHERE KullaniciID = ?", "i", [$kullaniciID])
                ->get_result()->fetch_assoc()['Bakiye'];

            $conn->commit();
            echo json_encode(['success' => true, 'new_balance' => $newBalance]);
        } else {
            throw new KullaniciHatasi("Ödül alınamaz.");
        }
    } catch (Throwable $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => ajax_hata_mesaji($e)]);
    }
    exit();
}

// ---------------------------------------------------------
// D. ALIŞKANLIK YÖNETİMİ (SİLME)
// ---------------------------------------------------------

if (isset($_POST['is_delete']) && $_POST['is_delete'] == 1) {
    $ka_id = intval($_POST['ka_id'] ?? 0);
    try {
        sorgu($conn, "DELETE FROM KullaniciAliskanliklari WHERE KullaniciAliskanlikID = ? AND KullaniciID = ?",
            "ii", [$ka_id, $kullaniciID]);
        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        ajax_hata_mesaji($e); // log'a yaz
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
    $veri = sorgu($conn, $sql, "ii", [$ka_id, $kullaniciID])->get_result()->fetch_assoc();

    if (!$veri) throw new KullaniciHatasi("Kayıt bulunamadı.");

    $basePuan = $veri['Puan'];
    $mevcutSeri = $veri['Seri'];
    $sonTarih = $veri['SonIslemTarihi'];
    // StatTuru veritabanından gelir ama kaynağı kullanıcı girdisidir (ikinci
    // derece enjeksiyon): aşağıdaki sorgulara asla gömülmez, hep bağlanır.
    $statTuru = (string) $veri['StatTuru'];

    if ($durum === 1) {
        // --- YAPILDI ---
        if ($sonTarih == $dunkuTarih) $yeniSeri = $mevcutSeri + 1;
        elseif ($sonTarih == $bugun) $yeniSeri = $mevcutSeri;
        else $yeniSeri = 1;

        $carpan = min(2.00, 1 + (floor($yeniSeri / 7) * 0.25));
        $kazanilanPuan = round($basePuan * $carpan);

        $stmt = sorgu($conn, "INSERT IGNORE INTO TakipKayitlari (KullaniciAliskanlikID, Tarih, TamamlandiMi) VALUES (?, ?, 1)",
            "is", [$ka_id, $bugun]);

        if ($stmt->affected_rows > 0) {
            sorgu($conn, "UPDATE KullaniciAliskanliklari SET Seri = ?, SonIslemTarihi = ? WHERE KullaniciAliskanlikID = ?",
                "isi", [$yeniSeri, $bugun, $ka_id]);
            sorgu($conn, "UPDATE KullaniciStatlari SET ToplamPuan = ToplamPuan + ? WHERE KullaniciID = ? AND StatTuru = ?",
                "iis", [(int) $kazanilanPuan, $kullaniciID, $statTuru]);

            // Günlük görevi ilerlet
            sorgu($conn, "UPDATE KullaniciGunlukGorevleri KG JOIN GorevTanimlari GT ON KG.GorevTanimID = GT.GorevTanimID
                          SET KG.MevcutIlerleme = KG.MevcutIlerleme + 1, KG.TamamlandiMi = CASE WHEN (KG.MevcutIlerleme + 1) >= GT.HedefSayi THEN 1 ELSE 0 END
                          WHERE KG.KullaniciID = ? AND KG.Tarih = ? AND KG.TamamlandiMi = 0 AND GT.Baslik LIKE '%alışkanlık%'",
                "is", [$kullaniciID, $bugun]);
        }
    } else {
        // --- GERİ ALINDI ---
        $carpan = min(2.00, 1 + (floor($mevcutSeri / 7) * 0.25));
        $silinecekPuan = round($basePuan * $carpan);

        $stmt = sorgu($conn, "DELETE FROM TakipKayitlari WHERE KullaniciAliskanlikID = ? AND Tarih = ?",
            "is", [$ka_id, $bugun]);

        if ($stmt->affected_rows > 0) {
            $yeniSeri = max(0, $mevcutSeri - 1);
            sorgu($conn, "UPDATE KullaniciAliskanliklari SET Seri = ? WHERE KullaniciAliskanlikID = ?",
                "ii", [$yeniSeri, $ka_id]);
            sorgu($conn, "UPDATE KullaniciStatlari SET ToplamPuan = ToplamPuan - ? WHERE KullaniciID = ? AND StatTuru = ?",
                "iis", [(int) $silinecekPuan, $kullaniciID, $statTuru]);
        } else {
            $yeniSeri = $mevcutSeri;
        }
    }

    $conn->commit();

    // Güncel verileri dön
    $statSatiri = sorgu($conn, "SELECT ToplamPuan FROM KullaniciStatlari WHERE KullaniciID = ? AND StatTuru = ?",
        "is", [$kullaniciID, $statTuru])->get_result()->fetch_assoc();
    $yeniToplamPuan = $statSatiri['ToplamPuan'] ?? 0;
    $statVerisi = hesaplaLevelVerileri($yeniToplamPuan);

    $allStats = sorgu($conn, "SELECT ToplamPuan FROM KullaniciStatlari WHERE KullaniciID = ?", "i", [$kullaniciID])
        ->get_result();
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

} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => ajax_hata_mesaji($e)]);
}
