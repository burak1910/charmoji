<?php
// view/avatar_renderer.php
// Piksel büst karakter: katmanlı PNG'ler (view/statik/gorsel/, üreten betik:
// assets/araclar/piksel-varlik-uret.py). Çizim sırası (docs/ux/01 §2): ten → üst giysi →
// saç → Kolye → Maske → Gözlük → Şapka.
//
// Güvenlik: görsel yolları hiçbir zaman kullanıcı verisinden kurulmaz. Görünüm yalnızca
// izinli bölge + 1..N tam sayı seçenek olarak okunur/kaydedilir; eşya katmanları eşya
// adından sabit eşleme tablosuyla (izinli liste) seçilir. Ad ve tür çıktıya basılmaz.

/** Görünüm bölgeleri ve seçenek sayıları (06 §5, VARSAYIM S27; göz için tek çizim var). */
const AVATAR_SECENEK_SAYILARI = [
    'ten' => 4,
    'sac' => 6,
    'ust' => 4,
];

/** Bölge => seçenek adlarının metin anahtarı öneki (1..N eklenir). */
const AVATAR_SECENEK_METINLERI = [
    'ten' => 'gorunum.secenek_ten_',
    'sac' => 'yerel.gorunum_sac_',
    'ust' => 'yerel.gorunum_ust_',
];

/** Görsellerin view/ altındaki sayfalara göre yolu. */
const AVATAR_GORSEL_KOK = 'statik/gorsel/';

/**
 * Kadraj => [tuval adı, büyük boy, küçük boy]. "tam" şapka için üstte boşluklu tuval,
 * "yakin" yalnızca büst (liste satırları, seçenek kutuları; eşya çizilmez).
 */
const AVATAR_KADRAJLAR = [
    'tam'   => [480, 240],
    'yakin' => [128, 64],
];

/**
 * Piksel görseli olan eşyalar: eşya adı (MarketEsyalari.Ad) => [yuva, dosya kodu].
 * Dosya adı yalnızca bu tablodan gelir.
 */
const ESYA_PIKSEL_GORSELLERI = [
    'Büyücü Şapkası' => ['sapka', 'sapka-buyucu'],
    'Güneş Gözlüğü'  => ['gozluk', 'gozluk-gunes'],
];

/**
 * Piksel görseli henüz olmayan eşyaların eski SVG çizimleri (sabit metin), piksel büste
 * hizalanmış dönüşümle. Tuval birimi: "tam" kadrajın kaynak pikseli (1269 x 1269).
 */
const ESYA_ESKI_SVG = [
    'Kral Tacı' => ['sapka', 'matrix(4.3 0 0 4.3 635 428)',
        '<path d="M-35,25 L-35,-10 L-15,10 L0,-25 L15,10 L35,-10 L35,25 Z" fill="#FACC15" stroke="#B45309" stroke-width="3" stroke-linejoin="round"/><circle cx="0" cy="20" r="3" fill="#B45309"/>'],
    'Ninja Maskesi' => ['maske', 'matrix(4.2 0 0 3.5 635 1316)',
        '<path d="M-45,-120 Q0,-90 45,-120 L45,-90 Q0,-60 -45,-90 Z" fill="#1F2937"/><rect x="-46" y="-120" width="92" height="40" rx="10" fill="#1F2937"/>'],
    'Altın Kolye' => ['kolye', 'matrix(2.8 0 0 2.8 635 1038)',
        '<path d="M-30,10 Q0,50 30,10" fill="none" stroke="#FACC15" stroke-width="4"/><circle cx="0" cy="30" r="8" fill="#FACC15" stroke="#B45309" stroke-width="2"/>'],
];

/** Eşya yuvaları (alttan üste çizim sırası) ve veritabanındaki tür adları. */
const AVATAR_YUVALARI = ['kolye' => 'Kolye', 'maske' => 'Maske', 'gozluk' => 'Gözlük', 'sapka' => 'Şapka'];

/**
 * Tek bir bölge değeri geçerli mi? Yalnızca tam sayı ya da tek rakamlı metin ("3"),
 * 1..N aralığında. "01", "1 ", "1<x>", 1.0, true reddedilir.
 */
function avatar_secenek_gecerli_mi(string $bolge, $deger): bool
{
    if (!isset(AVATAR_SECENEK_SAYILARI[$bolge])) {
        return false;
    }
    if (is_string($deger) && preg_match('/^[1-9]\z/', $deger) === 1) {
        $deger = (int) $deger;
    }
    return is_int($deger) && $deger >= 1 && $deger <= AVATAR_SECENEK_SAYILARI[$bolge];
}

/**
 * Veritabanından gelen AvatarVerisi'ni (JSON metni ya da dizi) güvenli seçeneklere
 * çevirir: ['ten' => int, 'sac' => int, 'ust' => int]. Geçersiz, eksik ya da eski serbest
 * renk verisi (skinColor ...) olan her bölge 1 numaralı seçeneğe düşer (VARSAYIM S28).
 */
function avatar_secenekleri_temizle($avatarVerisi): array
{
    $secenekler = array_fill_keys(array_keys(AVATAR_SECENEK_SAYILARI), 1);

    $cozulmus = is_array($avatarVerisi) ? $avatarVerisi : null;
    if ($cozulmus === null && is_string($avatarVerisi) && $avatarVerisi !== '' && strlen($avatarVerisi) <= 1000) {
        $cozulmus = json_decode($avatarVerisi, true);
    }
    if (!is_array($cozulmus)) {
        return $secenekler;
    }
    foreach (array_keys(AVATAR_SECENEK_SAYILARI) as $bolge) {
        if (array_key_exists($bolge, $cozulmus) && avatar_secenek_gecerli_mi($bolge, $cozulmus[$bolge])) {
            $secenekler[$bolge] = (int) $cozulmus[$bolge];
        }
    }
    return $secenekler;
}

/**
 * Kayıt için gelen seçenekleri doğrular. Yalnızca izinli bölgeler ve geçerli numaralar;
 * bilinmeyen anahtar ya da geçersiz değer bütün isteği reddeder.
 * Dönen: ['secenekler' => tam dizi (eksikler 1), 'hatali' => null|bölge adı|'' (biçim)].
 */
function avatar_secenekleri_dogrula($gelen): array
{
    if (!is_array($gelen) || $gelen === [] || array_is_list($gelen)) {
        return ['secenekler' => null, 'hatali' => ''];
    }
    $temiz = array_fill_keys(array_keys(AVATAR_SECENEK_SAYILARI), 1);
    foreach ($gelen as $bolge => $deger) {
        if (!is_string($bolge) || !isset(AVATAR_SECENEK_SAYILARI[$bolge])) {
            return ['secenekler' => null, 'hatali' => ''];
        }
        if (!avatar_secenek_gecerli_mi($bolge, $deger)) {
            return ['secenekler' => null, 'hatali' => $bolge];
        }
        $temiz[$bolge] = (int) $deger;
    }
    return ['secenekler' => $temiz, 'hatali' => null];
}

/**
 * Genişlik/yükseklik için güvenli boyut: sayı + isteğe bağlı birim (px, %, em, rem).
 * Geçersiz değer '100%' olur.
 */
function avatar_boyut_temizle($boyut): string
{
    if (is_int($boyut) || is_float($boyut)) {
        $boyut = (string) $boyut;
    }
    if (is_string($boyut) && preg_match('/^\d{1,4}(\.\d{1,2})?(px|%|em|rem)?\z/', $boyut) === 1) {
        return $boyut;
    }
    return '100%';
}

/** Görüntülenen CSS piksel boyutu (srcset seçimi için); bilinmiyorsa null. */
function avatar_boyut_px(string $boyut): ?int
{
    if (preg_match('/^(\d{1,4})(\.\d{1,2})?(px)?\z/', $boyut, $m) === 1) {
        return (int) $m[1];
    }
    return null;
}

/** Bir dosya kodunun iki boydaki yolları: src (büyük) ve srcset. Girdi sabit tablolardan gelir. */
function avatar_gorsel_kaynagi(string $temel, string $kadraj): array
{
    [$buyuk, $kucuk] = AVATAR_KADRAJLAR[$kadraj];
    $b = AVATAR_GORSEL_KOK . $temel . '-' . $buyuk . '.png';
    $k = AVATAR_GORSEL_KOK . $temel . '-' . $kucuk . '.png';
    return ['src' => $b, 'srcset' => $k . ' ' . $kucuk . 'w, ' . $b . ' ' . $buyuk . 'w'];
}

/** Bölge katmanının görsel kaynağı ($n doğrulanmış seçenek numarası olmalı). */
function avatar_katman_kaynagi(string $kadraj, string $bolge, int $n): array
{
    if (!isset(AVATAR_KADRAJLAR[$kadraj]) || !avatar_secenek_gecerli_mi($bolge, $n)) {
        $n = 1;
        $kadraj = isset(AVATAR_KADRAJLAR[$kadraj]) ? $kadraj : 'tam';
        $bolge = isset(AVATAR_SECENEK_SAYILARI[$bolge]) ? $bolge : 'ten';
    }
    return avatar_gorsel_kaynagi('karakter/' . $kadraj . '/' . $bolge . '-' . $n, $kadraj);
}

/** Tüm seçeneklerin görsel kaynakları (Görünüm önizlemesi için, istemciye JSON). */
function avatar_katman_haritasi(): array
{
    $harita = [];
    foreach (array_keys(AVATAR_KADRAJLAR) as $kadraj) {
        foreach (AVATAR_SECENEK_SAYILARI as $bolge => $sayi) {
            for ($n = 1; $n <= $sayi; $n++) {
                $harita[$kadraj][$bolge][$n] = avatar_katman_kaynagi($kadraj, $bolge, $n);
            }
        }
    }
    return $harita;
}

function avatar_img_html(array $kaynak, string $sinif, string $sizes): string
{
    return '<img class="' . $sinif . '" src="' . htmlspecialchars($kaynak['src'], ENT_QUOTES, 'UTF-8')
        . '" srcset="' . htmlspecialchars($kaynak['srcset'], ENT_QUOTES, 'UTF-8')
        . '" sizes="' . htmlspecialchars($sizes, ENT_QUOTES, 'UTF-8') . '" alt="" draggable="false" decoding="async">';
}

/**
 * Bir eşyanın karakter üstündeki katmanı (yalnızca "tam" kadraj): piksel görseli varsa
 * <img>, yoksa hizalanmış eski SVG, ikisi de yoksa boş metin. Dönen: [yuva, html].
 */
function esya_katman_html(array $esya, string $sizes = '240px'): array
{
    $ad = $esya['Ad'] ?? '';
    if (is_string($ad) && isset(ESYA_PIKSEL_GORSELLERI[$ad])) {
        [$yuva, $kod] = ESYA_PIKSEL_GORSELLERI[$ad];
        return [$yuva, avatar_img_html(avatar_gorsel_kaynagi('esya/' . $kod . '-katman', 'tam'), 'pk-k', $sizes)];
    }
    if (is_string($ad) && isset(ESYA_ESKI_SVG[$ad])) {
        [$yuva, $donusum, $cizim] = ESYA_ESKI_SVG[$ad];
        return [$yuva, '<svg class="pk-k pk-svg" viewBox="0 0 1269 1269" aria-hidden="true" focusable="false">'
            . '<g transform="' . $donusum . '">' . $cizim . '</g></svg>'];
    }
    return ['', ''];
}

/** Eşya ikonu: piksel görseli varsa <img>, yoksa bugünkü Font Awesome sınıfı (kaçışlı). */
function esya_ikon_html(array $esya, int $boy = 96, string $ekSinif = ''): string
{
    $ad = $esya['Ad'] ?? '';
    if (is_string($ad) && isset(ESYA_PIKSEL_GORSELLERI[$ad])) {
        $kod = ESYA_PIKSEL_GORSELLERI[$ad][1];
        $boy = max(16, min(192, $boy));
        $k = AVATAR_GORSEL_KOK . 'esya/' . $kod . '-ikon-96.png';
        $b = AVATAR_GORSEL_KOK . 'esya/' . $kod . '-ikon-192.png';
        return '<img class="esya-piksel' . ($ekSinif !== '' ? ' ' . htmlspecialchars($ekSinif, ENT_QUOTES, 'UTF-8') : '')
            . '" src="' . $b . '" srcset="' . $k . ' 96w, ' . $b . ' 192w" sizes="' . $boy . 'px" width="' . $boy
            . '" height="' . $boy . '" alt="" draggable="false" decoding="async">';
    }
    $sinif = (string) ($esya['GorselURL'] ?? '');
    return '<i class="fa-solid ' . htmlspecialchars($sinif, ENT_QUOTES, 'UTF-8')
        . ($ekSinif !== '' ? ' ' . htmlspecialchars($ekSinif, ENT_QUOTES, 'UTF-8') : '') . '" aria-hidden="true"></i>';
}

/**
 * Piksel büst karakteri çizer.
 *   $avatarJson    : Kullanicilar.AvatarVerisi (doğrulanır; geçersiz bölge 1'e düşer)
 *   $size          : görüntü boyu ('240px', '48px', '100%' ...)
 *   $equippedItems : kuşanılmış eşyalar (Ad, EsyaTuru); yalnızca "tam" kadrajda çizilir
 *   $kadraj        : 'tam' | 'yakin'; verilmezse 64 px ve altı için 'yakin'
 *   $secenekler    : (isteğe bağlı) önizleme için doğrulanmış seçenek dizisi
 */
function renderAvatar($avatarJson, $size = '100%', $equippedItems = [], ?string $kadraj = null, ?array $secenekler = null): string
{
    $secim = avatar_secenekleri_temizle($secenekler ?? $avatarJson);
    $boyut = avatar_boyut_temizle($size);
    $px = avatar_boyut_px($boyut);
    if ($kadraj === null || !isset(AVATAR_KADRAJLAR[$kadraj])) {
        $kadraj = ($px !== null && $px <= 64) ? 'yakin' : 'tam';
    }
    $sizes = ($px ?? 240) . 'px';
    if (!is_array($equippedItems)) {
        $equippedItems = [];
    }

    $yuvalar = array_fill_keys(array_keys(AVATAR_YUVALARI), '');
    $sapkali = false;
    if ($kadraj === 'tam') {
        foreach ($equippedItems as $esya) {
            if (!is_array($esya)) {
                continue;
            }
            [$yuva, $html] = esya_katman_html($esya, $sizes);
            // Tür veritabanındaki türle de uyuşmalı (tür başına tek eşya).
            if ($yuva !== '' && ($esya['EsyaTuru'] ?? null) === AVATAR_YUVALARI[$yuva]) {
                $yuvalar[$yuva] = $html;
                if ($yuva === 'sapka') {
                    $sapkali = isset(ESYA_PIKSEL_GORSELLERI[$esya['Ad']]);
                }
            }
        }
    }

    $stil = 'width:' . $boyut . ';' . ($px !== null ? 'height:' . $boyut . ';' : '');
    // Şapka yoksa üstteki boşluk kırpılır (karakter kutuda daha büyük görünür; CSS .pk-sapkasiz).
    $sapkasiz = $kadraj === 'tam' && $yuvalar['sapka'] === '';
    $cikti = '<span class="pk pk-' . $kadraj . ($sapkali ? ' pk-sapkali' : '') . ($sapkasiz ? ' pk-sapkasiz' : '') . '" style="'
        . htmlspecialchars($stil, ENT_QUOTES, 'UTF-8') . '" data-kadraj="' . $kadraj . '">';
    foreach (['ten', 'ust', 'sac'] as $bolge) {
        $cikti .= avatar_img_html(avatar_katman_kaynagi($kadraj, $bolge, $secim[$bolge]), 'pk-k pk-' . $bolge, $sizes);
    }
    if ($kadraj === 'tam') {
        foreach ($yuvalar as $yuva => $html) {
            $cikti .= '<span class="pk-yuva" data-yuva="' . $yuva . '">' . $html . '</span>';
        }
    }
    return $cikti . '</span>';
}
