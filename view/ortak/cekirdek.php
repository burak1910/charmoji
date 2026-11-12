<?php
// view/ortak/cekirdek.php
// Oturum açık sayfaların (Karargah, Görevler, Market, Lonca, Karakter, Görünüm/Çanta)
// ortak açılışı: oturum denetimi, metin yardımcıları, oyuncu özeti (altın, seviye,
// yetenekler, alt menü rozetleri). Bu dosya ve view/ortak/ altındaki diğer parçalar
// yalnızca include edilir; web'den doğrudan çağrılırsa 404 döner (ayrıca sunucu.php ve
// .htaccess view/ortak/ yolunu kapatır).

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    http_response_code(404);
    exit();
}

require_once __DIR__ . '/../../service/auth.php';
require_once __DIR__ . '/../avatar_renderer.php';

if (!defined('CHARMOJI_SAYFA')) {
    define('CHARMOJI_SAYFA', true);
}

/** Beş yetenek: kod adı (veritabanı) => metin anahtarı soneki. Sıra sabittir. */
const YETENEKLER = [
    'Zeka'        => 'zeka',
    'Guc'         => 'guc',
    'Sosyallik'   => 'sosyallik',
    'Disiplin'    => 'disiplin',
    'Yaraticilik' => 'yaraticilik',
];

/** Eşya türü (veritabanı) => metin anahtarı soneki. */
const ESYA_TURLERI = [
    'Şapka'  => 'sapka',
    'Gözlük' => 'gozluk',
    'Maske'  => 'maske',
    'Kolye'  => 'kolye',
];

/** Boş eşya yuvası silüeti (Font Awesome; piksel ikonlar gelene kadar). */
const ESYA_TUR_IKONLARI = [
    'Şapka'  => 'fa-hat-wizard',
    'Gözlük' => 'fa-glasses',
    'Maske'  => 'fa-mask',
    'Kolye'  => 'fa-gem',
];

// ----------------------------------------------------------------
// Metin ve biçim yardımcıları
// ----------------------------------------------------------------

/** HTML kaçışlama (metin ve öznitelik için). */
function e($deger): string
{
    return htmlspecialchars((string) $deger, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Tüm arayüz metinleri (08-metinler.md alt kümesi). */
function metinler(): array
{
    static $tablo = null;
    if ($tablo === null) {
        $tablo = require __DIR__ . '/metinler.php';
    }
    return $tablo;
}

/**
 * Anahtarın metnini döndürür; {degisken} yer tutucularını doldurur. Dönen değer
 * KAÇIŞLANMAMIŞ düz metindir: HTML'e basarken her zaman t() kullanın.
 */
function metin(string $anahtar, array $degiskenler = []): string
{
    $tablo = metinler();
    $sablon = $tablo[$anahtar] ?? $anahtar;
    if ($degiskenler === []) {
        return $sablon;
    }
    $yerine = [];
    foreach ($degiskenler as $ad => $deger) {
        $yerine['{' . $ad . '}'] = (string) $deger;
    }
    return strtr($sablon, $yerine);
}

/** metin() + HTML kaçışlama. Kullanıcı girdisi olan değişkenler de böylece kaçışlanır. */
function t(string $anahtar, array $degiskenler = []): string
{
    return e(metin($anahtar, $degiskenler));
}

/** Türkçe sayı biçimi: 1.250 (tam sayı, aşağı yuvarlanır). */
function sayi_tr($sayi): string
{
    return number_format((float) floor((float) $sayi), 0, ',', '.');
}

/** Çarpan biçimi: 1 -> "1", 1.25 -> "1,25", 1.5 -> "1,5". */
function carpan_tr(float $carpan): string
{
    return str_replace('.', ',', rtrim(rtrim(number_format($carpan, 2, '.', ''), '0'), '.'));
}

function yetenek_adi(string $kod): string
{
    return isset(YETENEKLER[$kod]) ? metin('ortak.yetenek_' . YETENEKLER[$kod]) : $kod;
}

function tur_adi(string $tur): string
{
    return isset(ESYA_TURLERI[$tur]) ? metin('ortak.tur_' . ESYA_TURLERI[$tur]) : $tur;
}

/** E-postayı maskeler: ilk harf + *** + @alan (06 §2). */
function eposta_maskele(string $eposta): string
{
    $at = strrpos($eposta, '@');
    if ($at === false || $at === 0) {
        return '***';
    }
    return mb_substr($eposta, 0, 1, 'UTF-8') . '***' . substr($eposta, $at);
}

// ----------------------------------------------------------------
// Oyun kuralları (00 §1.4; bugünkü koddan, değiştirilmeden)
// ----------------------------------------------------------------

/** Yetenek seviyesi: 1→2 için 100 XP, her seviyede iki katı. */
function hesaplaLevelVerileri($toplamPuan): array
{
    $toplamPuan = max(0, (int) $toplamPuan);
    $level = 1;
    $gerekliXP = 100;
    while ($toplamPuan >= $gerekliXP) {
        $toplamPuan -= $gerekliXP;
        $level++;
        $gerekliXP *= 2;
    }
    return [
        'level' => $level,
        'currentXP' => $toplamPuan,
        'neededXP' => $gerekliXP,
        'percent' => ($toplamPuan / $gerekliXP) * 100,
    ];
}

/** Seri bonusu: 1 + floor(seri / 7) × 0,25, en fazla ×2. */
function seri_carpani(int $seri): float
{
    return min(2.0, 1 + floor($seri / 7) * 0.25);
}

/**
 * Bir görevin kartta gösterilecek seri/ödül bilgisi (03 §3.2). Tamamlama XP'si
 * ajax_islem.php ile aynı kuralla (bir sonraki seriyle) hesaplanır.
 */
function gorev_kart_verisi(array $g, string $bugun, string $dun): array
{
    $seri = (int) $g['Seri'];
    $son = (string) ($g['SonIslemTarihi'] ?? '');
    $taban = (int) ($g['Puan'] ?? 10);
    $tamam = (int) $g['BugunYapildi'] > 0;

    $etkinSeri = ($son === $bugun || $son === $dun) ? $seri : 0;
    if ($tamam) {
        $carpan = seri_carpani($seri);
        $xp = (int) round($taban * $carpan);
    } else {
        $sonrakiSeri = $son === $dun ? $seri + 1 : ($son === $bugun ? $seri : 1);
        $carpan = seri_carpani($sonrakiSeri);
        $xp = (int) round($taban * $carpan);
    }
    return [
        'id' => (int) $g['KullaniciAliskanlikID'],
        'ad' => (string) $g['Ad'],
        'yetenek' => (string) $g['StatTuru'],
        'tamam' => $tamam,
        'etkinSeri' => $etkinSeri,
        'bozukSeri' => (!$tamam && $seri > 0 && $etkinSeri === 0) ? $seri : 0,
        'carpan' => $carpan,
        'xp' => $xp,
        'taban' => $taban,
    ];
}

// ----------------------------------------------------------------
// Veri
// ----------------------------------------------------------------

/** Hazır ifade kısayolu: sorguyu hazırlar, bağlar, çalıştırır. */
function vt_sorgu(mysqli $conn, string $sql, string $tipler = '', array $degerler = []): mysqli_stmt
{
    $stmt = $conn->prepare($sql);
    if ($tipler !== '') {
        $stmt->bind_param($tipler, ...$degerler);
    }
    $stmt->execute();
    return $stmt;
}

function vt_satirlar(mysqli $conn, string $sql, string $tipler = '', array $degerler = []): array
{
    return vt_sorgu($conn, $sql, $tipler, $degerler)->get_result()->fetch_all(MYSQLI_ASSOC);
}

function vt_satir(mysqli $conn, string $sql, string $tipler = '', array $degerler = []): ?array
{
    $satir = vt_sorgu($conn, $sql, $tipler, $degerler)->get_result()->fetch_assoc();
    return $satir ?: null;
}

/**
 * Günün meydan okuma satırlarını oluşturur ve "Günlük Giriş"i tamamlar
 * (eskiden userpage.php açılışında yapılıyordu; artık her sekmede, çünkü alt
 * menünün ödül rozeti bu veriye bakar). Tekrar çalıştırılabilir.
 */
function gunluk_meydan_okumalari_hazirla(mysqli $conn, int $kullaniciID, string $bugun): void
{
    $var = vt_satir($conn, "SELECT COUNT(*) AS Sayi FROM KullaniciGunlukGorevleri WHERE KullaniciID = ? AND Tarih = ?",
        "is", [$kullaniciID, $bugun]);
    if ((int) ($var['Sayi'] ?? 0) === 0) {
        // INSERT IGNORE: iki sekme aynı anda açılırsa UQ_KGG çakışması hata vermez.
        vt_sorgu($conn, "INSERT IGNORE INTO KullaniciGunlukGorevleri (KullaniciID, GorevTanimID, Tarih)
                         SELECT ?, GorevTanimID, ? FROM GorevTanimlari", "is", [$kullaniciID, $bugun]);
    }
    vt_sorgu($conn, "UPDATE KullaniciGunlukGorevleri SET MevcutIlerleme = 1, TamamlandiMi = 1
                     WHERE KullaniciID = ? AND Tarih = ? AND TamamlandiMi = 0
                       AND GorevTanimID = (SELECT GorevTanimID FROM GorevTanimlari WHERE Baslik = 'Günlük Giriş' LIMIT 1)",
        "is", [$kullaniciID, $bugun]);
}

/** Bir kullanıcının beş yetenek verisi ve karakter seviyesi. */
function yetenek_verileri(mysqli $conn, int $kullaniciID): array
{
    foreach (array_keys(YETENEKLER) as $stat) {
        vt_sorgu($conn, "INSERT IGNORE INTO KullaniciStatlari (KullaniciID, StatTuru, ToplamPuan) VALUES (?, ?, 0)",
            "is", [$kullaniciID, $stat]);
    }
    $satirlar = vt_satirlar($conn, "SELECT StatTuru, ToplamPuan FROM KullaniciStatlari WHERE KullaniciID = ?",
        "i", [$kullaniciID]);
    $puan = [];
    foreach ($satirlar as $s) {
        $puan[$s['StatTuru']] = (int) $s['ToplamPuan'];
    }
    $yetenekler = [];
    $toplamSeviye = 0;
    foreach (array_keys(YETENEKLER) as $stat) {
        $yetenekler[$stat] = hesaplaLevelVerileri($puan[$stat] ?? 0);
        $toplamSeviye += $yetenekler[$stat]['level'];
    }
    $seviye = (int) floor($toplamSeviye / count(YETENEKLER));
    return [
        'yetenekler' => $yetenekler,
        'seviye' => $seviye,
        'toplamYetenekSeviyesi' => $toplamSeviye,
        'sonrakiKalan' => count(YETENEKLER) * ($seviye + 1) - $toplamSeviye,
    ];
}

/**
 * Birden fazla kullanıcının karakter seviyesi (resmî formül: beş yetenek seviyesinin
 * ortalaması, aşağı yuvarlanır). Satırı olmayan yetenek 1. seviye sayılır.
 * @param int[] $idler
 * @return array<int,int> KullaniciID => seviye
 */
function karakter_seviyeleri(mysqli $conn, array $idler): array
{
    $idler = array_values(array_unique(array_map('intval', $idler)));
    if ($idler === []) {
        return [];
    }
    $yer = implode(',', array_fill(0, count($idler), '?'));
    $satirlar = vt_satirlar($conn,
        "SELECT KullaniciID, StatTuru, ToplamPuan FROM KullaniciStatlari WHERE KullaniciID IN ($yer)",
        str_repeat('i', count($idler)), $idler);
    $toplam = array_fill_keys($idler, []);
    foreach ($satirlar as $s) {
        if (isset(YETENEKLER[$s['StatTuru']])) {
            $toplam[(int) $s['KullaniciID']][$s['StatTuru']] = hesaplaLevelVerileri($s['ToplamPuan'])['level'];
        }
    }
    $sonuc = [];
    foreach ($toplam as $id => $seviyeler) {
        $t = 0;
        foreach (array_keys(YETENEKLER) as $stat) {
            $t += $seviyeler[$stat] ?? 1;
        }
        $sonuc[$id] = (int) floor($t / count(YETENEKLER));
    }
    return $sonuc;
}

/** Kuşanılmış eşyalar (ad, tür) - renderAvatar için. */
function kusanilan_esyalar(mysqli $conn, int $kullaniciID): array
{
    return vt_satirlar($conn, "SELECT M.EsyaID, M.Ad, M.EsyaTuru, M.GorselURL
                               FROM KullaniciEnvanteri KE JOIN MarketEsyalari M ON KE.EsyaID = M.EsyaID
                               WHERE KE.KullaniciID = ? AND KE.KusandiMi = 1", "i", [$kullaniciID]);
}

/**
 * Oyuncunun aktif görevleri (kart verisiyle). Eski ortak tablo (Aliskanliklar) ile
 * okunur: 003 sonrası PHP geçişi (database/README.md) bu aşamanın kapsamı dışında.
 */
function aktif_gorevler(mysqli $conn, int $kullaniciID, string $bugun, string $dun): array
{
    $satirlar = vt_satirlar($conn,
        "SELECT KA.KullaniciAliskanlikID, A.Ad, A.StatTuru, A.Puan, KA.Seri, KA.SonIslemTarihi,
                (SELECT COUNT(*) FROM TakipKayitlari TK
                  WHERE TK.KullaniciAliskanlikID = KA.KullaniciAliskanlikID AND TK.Tarih = ?) AS BugunYapildi
           FROM KullaniciAliskanliklari KA
           JOIN Aliskanliklar A ON KA.AliskanlikID = A.AliskanlikID
          WHERE KA.KullaniciID = ? AND KA.AktifMi = 1
          ORDER BY KA.KullaniciAliskanlikID", "si", [$bugun, $kullaniciID]);
    return array_map(fn($g) => gorev_kart_verisi($g, $bugun, $dun), $satirlar);
}

// ----------------------------------------------------------------
// Sayfa açılışı
// ----------------------------------------------------------------

/**
 * Oturum gerektiren her sayfanın başında çağrılır. Oturum yoksa girişe yönlendirir.
 * Üst bar ve alt menünün ihtiyaç duyduğu verileri döndürür.
 */
function sayfa_baslat(): array
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
    $kullaniciID = (int) $_SESSION['user_id'];
    $conn = get_db_connection();
    $bugun = date('Y-m-d');
    $dun = date('Y-m-d', strtotime('-1 day'));

    $kullanici = vt_satir($conn, "SELECT Ad, Eposta, Bakiye, AvatarVerisi FROM Kullanicilar WHERE KullaniciID = ?",
        "i", [$kullaniciID]);
    if ($kullanici === null) {
        // Hesap silinmiş: oturumu kapatıp girişe gönder.
        $_SESSION = [];
        header('Location: login.php');
        exit();
    }

    gunluk_meydan_okumalari_hazirla($conn, $kullaniciID, $bugun);
    $yetenek = yetenek_verileri($conn, $kullaniciID);

    $odul = vt_satir($conn, "SELECT COUNT(*) AS Sayi FROM KullaniciGunlukGorevleri
                             WHERE KullaniciID = ? AND Tarih = ? AND TamamlandiMi = 1 AND OdulAlindiMi = 0",
        "is", [$kullaniciID, $bugun]);
    $istek = vt_satir($conn, "SELECT COUNT(*) AS Sayi FROM Arkadasliklar WHERE IstekAlanID = ? AND Durum = 0",
        "i", [$kullaniciID]);

    // Tek seferlik bildirim (form gönderimi sonrası PRG).
    $bildirim = null;
    if (isset($_SESSION['bildirim']) && is_array($_SESSION['bildirim'])) {
        $bildirim = [
            'tur' => ($_SESSION['bildirim']['tur'] ?? '') === 'hata' ? 'hata' : 'bilgi',
            'metin' => (string) ($_SESSION['bildirim']['metin'] ?? ''),
        ];
        unset($_SESSION['bildirim']);
    }

    return [
        'conn' => $conn,
        'id' => $kullaniciID,
        'bugun' => $bugun,
        'dun' => $dun,
        'ad' => (string) $kullanici['Ad'],
        'eposta' => (string) $kullanici['Eposta'],
        'altin' => (int) floor((float) $kullanici['Bakiye']),
        'avatar' => $kullanici['AvatarVerisi'],
        'yetenekler' => $yetenek['yetenekler'],
        'seviye' => $yetenek['seviye'],
        'sonrakiKalan' => $yetenek['sonrakiKalan'],
        'rozetOdul' => (int) ($odul['Sayi'] ?? 0) > 0,
        'rozetIstek' => (int) ($istek['Sayi'] ?? 0),
        'bildirim' => $bildirim,
    ];
}

/** Sonraki sayfa açılışında G03 ile gösterilecek bildirim. */
function bildirim_birak(string $metin, string $tur = 'bilgi'): void
{
    $_SESSION['bildirim'] = ['tur' => $tur, 'metin' => $metin];
}
