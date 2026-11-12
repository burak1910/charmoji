<?php
// view/ajax_islem.php - CHARMOJI FULL BACKEND
header('Content-Type: application/json');

// Hata yanıtları da JSON olsun (bkz. config/uygulama.php).
define('CHARMOJI_JSON_YANIT', true);

// Oturum ve hata ayarları (display_errors kapalı) auth.php içinde yapılır.
require_once __DIR__ . '/../service/auth.php';
require_once __DIR__ . '/avatar_renderer.php'; // Görünüm seçeneği doğrulama
// Ortak çekirdek: metin(), hesaplaLevelVerileri(). Include anında çıktı, yönlendirme
// ya da veritabanı işlemi yapmaz (oturum denetimi yalnızca sayfa_baslat() içindedir).
require_once __DIR__ . '/ortak/cekirdek.php';

// 1. GÜVENLİK KONTROLÜ
if (!isset($_SESSION['user_id'])) {
    // 401 + kod: arayüz "Oturumun sona erdi" penceresini (G08) gösterir.
    http_response_code(401);
    echo json_encode(['success' => false, 'kod' => 'oturum_kapali', 'message' => metin('ortak.oturum_bitti_baslik')]);
    exit();
}

// Bu uç noktadaki tüm işlemler durum değiştirir: yalnızca POST + CSRF jetonu
// (X-CSRF-Token başlığı ya da csrf_token alanı).
yalnizca_post();
csrf_dogrula();

$kullaniciID = (int) $_SESSION['user_id'];
$conn = get_db_connection();
$bugun = date('Y-m-d');

// Kullanıcıya gösterilebilecek (iş kuralı) hatalar. Bunların dışındaki her
// istisna (ör. mysqli_sql_exception) log'a yazılır, kullanıcıya genel mesaj döner.
// $kod: arayüzün metni seçtiği makine okunur gerekçe (ör. 'yetersiz_altin').
class KullaniciHatasi extends Exception {
    public string $kod;
    public array $ek;
    public function __construct(string $mesaj, string $kod = '', array $ek = []) {
        parent::__construct($mesaj);
        $this->kod = $kod;
        $this->ek = $ek;
    }
}

// Başarısız işlem yanıtı: mesaj + (kullanıcı hatasıysa) kod ve ek alanlar.
function ajax_hata_yaniti(Throwable $e): array {
    $yanit = ['success' => false, 'message' => ajax_hata_mesaji($e)];
    if ($e instanceof KullaniciHatasi && $e->kod !== '') {
        $yanit['kod'] = $e->kod;
        $yanit += $e->ek;
    }
    return $yanit;
}

function ajax_hata_mesaji(Throwable $e): string {
    if ($e instanceof KullaniciHatasi) {
        return $e->getMessage();
    }
    error_log(sprintf('[charmoji] ajax_islem hatası %s: %s (%s:%d)',
        get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));
    return metin('ortak.hata_genel');
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

// A1. Görünüm seçeneklerini kaydetme (06 §5): {"ten":1..4,"sac":1..6,"ust":1..4}
// JSON metni olarak gelir; yalnızca izinli bölgeler ve tanımlı seçenek numaraları kabul
// edilir (eski serbest renk anahtarları da dahil, başka her şey reddedilir).
if (isset($_POST['save_avatar_options'])) {
    $ham = $_POST['secenekler'] ?? '';
    $gelen = (is_string($ham) && strlen($ham) <= 200) ? json_decode($ham, true) : null;
    $sonuc = avatar_secenekleri_dogrula($gelen);
    if ($sonuc['secenekler'] === null) {
        http_response_code(400);
        $yanit = ['success' => false, 'kod' => 'gecersiz_secenek', 'message' => metin('gorunum.hata_gecersiz_secenek')];
        if ($sonuc['hatali'] !== '') {
            $yanit['bolge'] = $sonuc['hatali'];
        }
        echo json_encode($yanit);
        exit();
    }

    // Veritabanına yalnızca doğrulanmış tam sayılardan kurulan JSON yazılır; eksik bölge 1.
    $kayit = json_encode($sonuc['secenekler']);
    try {
        sorgu($conn, "UPDATE Kullanicilar SET AvatarVerisi = ? WHERE KullaniciID = ?", "si", [$kayit, $kullaniciID]);
        echo json_encode(['success' => true, 'secenekler' => $sonuc['secenekler'], 'message' => metin('gorunum.kaydedildi_bildirim')]);
    } catch (Throwable $e) {
        echo json_encode(ajax_hata_yaniti($e));
    }
    exit();
}

// A2. Eşya Kuşanma (Equip)
if (isset($_POST['equip_item_id'])) {
    $esyaID = intval($_POST['equip_item_id']);

    try {
        $conn->begin_transaction();

        // 1. Eşyanın türünü bul (Şapka mı, Gözlük mü?) - eşya kullanıcının envanterinde olmalı
        $turSql = sorgu($conn, "SELECT ME.EsyaTuru FROM MarketEsyalari ME
                                JOIN KullaniciEnvanteri KE ON KE.EsyaID = ME.EsyaID
                                WHERE ME.EsyaID = ? AND KE.KullaniciID = ?", "ii", [$esyaID, $kullaniciID])
            ->get_result()->fetch_assoc();
        if(!$turSql) throw new KullaniciHatasi(metin('ortak.hata_kusanilamadi'), 'bulunamadi');
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
        echo json_encode(ajax_hata_yaniti($e));
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
        $esya = sorgu($conn, "SELECT Ad, Fiyat FROM MarketEsyalari WHERE EsyaID = ?", "i", [$esyaID])
            ->get_result()->fetch_assoc();
        $user = sorgu($conn, "SELECT Bakiye FROM Kullanicilar WHERE KullaniciID = ?", "i", [$kullaniciID])
            ->get_result()->fetch_assoc();

        if (!$esya) throw new KullaniciHatasi(metin('market.hata_bulunamadi'), 'bulunamadi');
        // Kullanıcı kaydı yoksa (hesap silinmiş) oturum geçersizdir: arayüz G08'i gösterir.
        if (!$user) throw new KullaniciHatasi(metin('ortak.oturum_bitti_baslik'), 'oturum_kapali');

        // Zaten var mı?
        $checkInv = sorgu($conn, "SELECT EnvanterID FROM KullaniciEnvanteri WHERE KullaniciID = ? AND EsyaID = ?",
            "ii", [$kullaniciID, $esyaID])->get_result();
        if ($checkInv->num_rows > 0) throw new KullaniciHatasi(metin('market.hata_zaten_sahip'), 'zaten_sahip');

        // Para yetiyor mu?
        if ($user['Bakiye'] < $esya['Fiyat']) {
            $eksik = (int) ceil($esya['Fiyat'] - $user['Bakiye']);
            throw new KullaniciHatasi(metin('market.hata_yetersiz_altin', ['eksik' => $eksik]), 'yetersiz_altin', [
                'eksik' => $eksik,
                'bakiye' => (int) floor($user['Bakiye']),
            ]);
        }

        // Satın alma işlemi
        $newBalance = $user['Bakiye'] - $esya['Fiyat'];
        sorgu($conn, "UPDATE Kullanicilar SET Bakiye = ? WHERE KullaniciID = ?", "di", [$newBalance, $kullaniciID]);
        sorgu($conn, "INSERT INTO KullaniciEnvanteri (KullaniciID, EsyaID) VALUES (?, ?)", "ii", [$kullaniciID, $esyaID]);

        $conn->commit();
        echo json_encode(['success' => true, 'new_balance' => $newBalance, 'message' => metin('market.satin_alindi_bildirim', ['esya' => $esya['Ad']])]);

    } catch (Throwable $e) {
        $conn->rollback();
        echo json_encode(ajax_hata_yaniti($e));
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

        if (!$gorev) {
            throw new KullaniciHatasi(metin('gorevler.meydan_hata_alinamadi'), 'bulunamadi');
        } elseif ($gorev['OdulAlindiMi'] == 1) {
            throw new KullaniciHatasi(metin('gorevler.meydan_hata_zaten'), 'zaten_alindi');
        } elseif ($gorev['TamamlandiMi'] != 1) {
            throw new KullaniciHatasi(metin('gorevler.meydan_hata_tamamlanmadi'), 'tamamlanmadi');
        } else {
            sorgu($conn, "UPDATE Kullanicilar SET Bakiye = Bakiye + ? WHERE KullaniciID = ?",
                "di", [$gorev['OdulPara'], $kullaniciID]);
            sorgu($conn, "UPDATE KullaniciGunlukGorevleri SET OdulAlindiMi = 1 WHERE KullaniciGorevID = ? AND KullaniciID = ?",
                "ii", [$kg_id, $kullaniciID]);

            $newBalance = sorgu($conn, "SELECT Bakiye FROM Kullanicilar WHERE KullaniciID = ?", "i", [$kullaniciID])
                ->get_result()->fetch_assoc()['Bakiye'];

            $kalanOdul = sorgu($conn, "SELECT COUNT(*) AS Sayi FROM KullaniciGunlukGorevleri
                                       WHERE KullaniciID = ? AND Tarih = ? AND TamamlandiMi = 1 AND OdulAlindiMi = 0",
                "is", [$kullaniciID, $bugun])->get_result()->fetch_assoc()['Sayi'];

            $conn->commit();
            echo json_encode(['success' => true, 'new_balance' => $newBalance,
                'odul' => (int) $gorev['OdulPara'], 'odul_bekliyor' => $kalanOdul > 0]);
        }
    } catch (Throwable $e) {
        $conn->rollback();
        echo json_encode(ajax_hata_yaniti($e));
    }
    exit();
}

// ---------------------------------------------------------
// D. ALIŞKANLIK YÖNETİMİ (SİLME)
// ---------------------------------------------------------

if (isset($_POST['is_delete']) && $_POST['is_delete'] == 1) {
    $ka_id = intval($_POST['ka_id'] ?? 0);
    try {
        $silme = sorgu($conn, "DELETE FROM KullaniciAliskanliklari WHERE KullaniciAliskanlikID = ? AND KullaniciID = ?",
            "ii", [$ka_id, $kullaniciID]);
        if ($silme->affected_rows < 1) {
            echo json_encode(['success' => false, 'kod' => 'bulunamadi', 'message' => metin('gorevler.hata_silinemedi')]);
            exit();
        }
        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        ajax_hata_mesaji($e); // log'a yaz
        echo json_encode(['success' => false, 'message' => metin('gorevler.hata_silinemedi')]);
    }
    exit();
}

// ---------------------------------------------------------
// E. ALIŞKANLIK İŞARETLEME (ANA İŞLEM)
// ---------------------------------------------------------

// Eğer yukarıdaki özel işlemlerden hiçbiri değilse, bu bir checkbox işaretlemesidir.
if (!isset($_POST['ka_id']) || !isset($_POST['durum'])) {
    echo json_encode(['success' => false, 'message' => metin('ortak.hata_genel')]);
    exit();
}

$ka_id = intval($_POST['ka_id']);
$durum = intval($_POST['durum']);
$dunkuTarih = date('Y-m-d', strtotime('-1 day'));

try {
    $conn->begin_transaction();

    $sql = "SELECT KA.Seri, KA.SonIslemTarihi, A.Puan, A.StatTuru
            FROM KullaniciAliskanliklari KA
            JOIN Aliskanliklar A ON KA.AliskanlikID = A.AliskanlikID
            WHERE KA.KullaniciAliskanlikID = ? AND KA.KullaniciID = ?";
    $veri = sorgu($conn, $sql, "ii", [$ka_id, $kullaniciID])->get_result()->fetch_assoc();

    if (!$veri) throw new KullaniciHatasi(metin('gorevler.hata_tamamlanamadi'), 'bulunamadi');

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

        $degisti = $stmt->affected_rows > 0;
        $islemXP = (int) $kazanilanPuan;
        if ($degisti) {
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

        $degisti = $stmt->affected_rows > 0;
        $islemXP = (int) $silinecekPuan;
        if ($degisti) {
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

    // Alt menü rozeti: bugün alınmamış meydan okuma ödülü kaldı mı?
    $odulBekliyor = sorgu($conn, "SELECT COUNT(*) AS Sayi FROM KullaniciGunlukGorevleri
                                  WHERE KullaniciID = ? AND Tarih = ? AND TamamlandiMi = 1 AND OdulAlindiMi = 0",
        "is", [$kullaniciID, $bugun])->get_result()->fetch_assoc()['Sayi'] > 0;

    echo json_encode([
        'success' => true,
        'new_streak' => isset($yeniSeri) ? $yeniSeri : $mevcutSeri,
        'stat_type' => $statTuru,
        'stat_data' => $statVerisi,
        'global_level' => $genelLevel,
        'xp' => $islemXP,
        'degisti' => $degisti,
        'odul_bekliyor' => $odulBekliyor
    ]);

} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(ajax_hata_yaniti($e));
}
