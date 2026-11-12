<?php
// view/ajax_islem.php - CHARMOJI FULL BACKEND
header('Content-Type: application/json');

// Hata yanıtları da JSON olsun (bkz. config/uygulama.php).
define('CHARMOJI_JSON_YANIT', true);

// Oturum ve hata ayarları (display_errors kapalı) auth.php içinde yapılır.
require_once __DIR__ . '/../service/auth.php';
require_once __DIR__ . '/avatar_renderer.php'; // Görünüm seçeneği doğrulama
// Ortak çekirdek: metin(), hesaplaLevelVerileri(), KullaniciHatasi. Include anında çıktı,
// yönlendirme ya da veritabanı işlemi yapmaz (oturum denetimi yalnızca sayfa_baslat() içindedir).
require_once __DIR__ . '/ortak/cekirdek.php';
// Görev iş kuralları ve görev kartı (yanıtta güncel kart HTML'i döner).
require_once __DIR__ . '/ortak/gorev_islemleri.php';
require_once __DIR__ . '/ortak/gorev_karti.php';

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
$bugun = oyun_bugun();
$dun = oyun_dun();

// Kullanıcıya gösterilebilecek (iş kuralı) hatalar KullaniciHatasi'dır (cekirdek.php).
// Bunların dışındaki her istisna (ör. mysqli_sql_exception) log'a yazılır, kullanıcıya
// genel mesaj döner. $kod: arayüzün metni seçtiği makine okunur gerekçe (ör. 'yetersiz_altin').

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
// D. GÖREVLER (docs/ux/03-gorevler.md; kurallar view/ortak/gorev_islemleri.php)
// ---------------------------------------------------------

/** POST'tan pozitif tam sayı (dizi ya da metin gelirse 0). */
function post_kimlik(string $ad): int {
    $deger = $_POST[$ad] ?? null;
    return (is_string($deger) && ctype_digit($deger)) ? (int) $deger : 0;
}

/** Görevin güncel kartı (yanıt için): kart verisi + sunucuda çizilmiş HTML. */
function gorev_karti_yaniti(mysqli $conn, int $kullaniciID, int $kaID, string $bugun, string $dun): array {
    $g = gorev_satiri($conn, $kullaniciID, $kaID, $bugun, null);
    if ($g === null) {
        return [];
    }
    $kart = gorev_kart_verisi($g, $bugun, $dun);
    // Karargah (E10) kartında menü yoktur; Görevler (E20) kartında vardır.
    $menu = ($_POST['kart_tipi'] ?? '') !== 'karargah';
    return ['kart' => $kart, 'kart_html' => gorev_karti_html($kart, $menu)];
}

/** Aktif görev sayısı ve sınır (arayüz "Görev ekle" kilidini buna göre gösterir). */
function gorev_sayilari(mysqli $conn, int $kullaniciID, string $bugun): array {
    $s = vt_satir($conn, "SELECT COUNT(*) AS Toplam, COUNT(TK.KayitID) AS Tamamlanan
                            FROM KullaniciAliskanliklari KA
                            LEFT JOIN TakipKayitlari TK ON TK.KullaniciAliskanlikID = KA.KullaniciAliskanlikID AND TK.Tarih = ?
                           WHERE KA.KullaniciID = ? AND KA.AktifMi = 1", "si", [$bugun, $kullaniciID]);
    return ['toplam' => (int) $s['Toplam'], 'tamamlanan' => (int) $s['Tamamlanan'], 'sinir' => GOREV_SINIRI];
}

// D1. Görev ekle (E21 ekle kipi)
if (isset($_POST['gorev_ekle'])) {
    try {
        $kaID = gorev_ekle($conn, $kullaniciID, $_POST['habit_name'] ?? null, $_POST['habit_stat'] ?? null, $bugun);
        echo json_encode(['success' => true, 'message' => metin('gorevler.eklendi_bildirim')]
            + gorev_karti_yaniti($conn, $kullaniciID, $kaID, $bugun, $dun) + gorev_sayilari($conn, $kullaniciID, $bugun));
    } catch (Throwable $e) {
        echo json_encode(ajax_hata_yaniti($e));
    }
    exit();
}

// D2. Görev düzenle (E21 düzenle kipi): ad + yetenek
if (isset($_POST['gorev_duzenle'])) {
    $kaID = post_kimlik('ka_id');
    try {
        gorev_duzenle($conn, $kullaniciID, $kaID, $_POST['habit_name'] ?? null, $_POST['habit_stat'] ?? null, $bugun);
        echo json_encode(['success' => true, 'message' => metin('gorevler.guncellendi_bildirim')]
            + gorev_karti_yaniti($conn, $kullaniciID, $kaID, $bugun, $dun));
    } catch (Throwable $e) {
        echo json_encode(ajax_hata_yaniti($e));
    }
    exit();
}

// D3. Görev sil = arşivle (S18). Parametre adı eski arayüzle uyumlu: is_delete.
if (isset($_POST['is_delete']) && $_POST['is_delete'] == 1) {
    try {
        gorev_arsivle($conn, $kullaniciID, post_kimlik('ka_id'));
        echo json_encode(['success' => true, 'message' => metin('gorevler.silindi_bildirim')]
            + gorev_sayilari($conn, $kullaniciID, $bugun));
    } catch (Throwable $e) {
        $yanit = ajax_hata_yaniti($e);
        if (!($e instanceof KullaniciHatasi)) {
            $yanit['message'] = metin('gorevler.hata_silinemedi');
        }
        echo json_encode($yanit);
    }
    exit();
}

// D4. Silmeyi geri al (5 sn'lik "Geri al" bildirimi)
if (isset($_POST['gorev_geri_getir'])) {
    $kaID = post_kimlik('ka_id');
    try {
        gorev_geri_getir($conn, $kullaniciID, $kaID, $bugun);
        echo json_encode(['success' => true, 'message' => metin('gorevler.silme_geri_alindi')]
            + gorev_karti_yaniti($conn, $kullaniciID, $kaID, $bugun, $dun) + gorev_sayilari($conn, $kullaniciID, $bugun));
    } catch (Throwable $e) {
        echo json_encode(ajax_hata_yaniti($e));
    }
    exit();
}

// ---------------------------------------------------------
// E. GÖREV TAMAMLAMA / GERİ ALMA (ANA İŞLEM)
// ---------------------------------------------------------

if (!isset($_POST['ka_id']) || !isset($_POST['durum'])) {
    echo json_encode(['success' => false, 'message' => metin('ortak.hata_genel')]);
    exit();
}

$kaID = post_kimlik('ka_id');
$tamamla = (string) ($_POST['durum'] ?? '') === '1';

try {
    // Sayfa dünden kalmışsa (gün değişti) geçmiş gün değiştirilmez (03 §8.1, S1).
    $sayfaGunu = $_POST['gun'] ?? null;
    if (is_string($sayfaGunu) && $sayfaGunu !== '' && $sayfaGunu !== $bugun) {
        throw new KullaniciHatasi(metin('gorevler.hata_gun_bitti'), 'gun_bitti');
    }

    $sonuc = $tamamla
        ? gorev_tamamla($conn, $kullaniciID, $kaID, $bugun, $dun)
        : gorev_geri_al($conn, $kullaniciID, $kaID, $bugun);

    $yetenek = yetenek_verileri($conn, $kullaniciID);
    $statVerisi = $yetenek['yetenekler'][$sonuc['stat']] ?? hesaplaLevelVerileri($sonuc['puan_sonra']);

    // Alt menü rozeti: bugün alınmamış meydan okuma ödülü kaldı mı?
    $odulBekliyor = (int) vt_satir($conn, "SELECT COUNT(*) AS Sayi FROM KullaniciGunlukGorevleri
                                           WHERE KullaniciID = ? AND Tarih = ? AND TamamlandiMi = 1 AND OdulAlindiMi = 0",
        "is", [$kullaniciID, $bugun])['Sayi'] > 0;

    echo json_encode([
        'success' => true,
        'degisti' => true,
        'xp' => $sonuc['xp'],
        'new_streak' => $sonuc['seri'],
        'carpan' => $sonuc['carpan'],
        'carpan_yukseldi' => $sonuc['carpan_yukseldi'],
        'stat_type' => $sonuc['stat'],
        'stat_adi' => yetenek_adi($sonuc['stat']),
        'stat_data' => $statVerisi,
        'yetenek_seviye_once' => hesaplaLevelVerileri($sonuc['puan_once'])['level'],
        'yetenek_seviye_sonra' => hesaplaLevelVerileri($sonuc['puan_sonra'])['level'],
        'global_level' => $yetenek['seviye'],
        'meydan' => meydan_durumlari($conn, $kullaniciID, $bugun),
        'odul_bekliyor' => $odulBekliyor,
    ] + gorev_karti_yaniti($conn, $kullaniciID, $kaID, $bugun, $dun) + gorev_sayilari($conn, $kullaniciID, $bugun));
} catch (Throwable $e) {
    $yanit = ajax_hata_yaniti($e);
    // Zaten tamamlanmış / tamamlanmamış: arayüz kartı sunucudaki güncel hâle çeker.
    if ($e instanceof KullaniciHatasi && in_array($e->kod, ['zaten_tamamlandi', 'tamamlanmadi'], true)) {
        $yanit += gorev_karti_yaniti($conn, $kullaniciID, $kaID, $bugun, $dun);
    }
    echo json_encode($yanit);
}
