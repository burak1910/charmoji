<?php
// view/avatar_renderer.php
// Modern "Flat Art" Tasarımı + Eşya Desteği

// İzinli avatar renk anahtarları ve varsayılan değerleri.
// Kayıt (ajax_islem.php) ve çizim aynı listeyi kullanır.
const AVATAR_VARSAYILAN_RENKLER = [
    'skinColor' => '#F3D2C1',
    'hairColor' => '#2D3436',
    'eyeColor'  => '#000000',
    'topColor'  => '#6C5CE7',
];

/**
 * Değer tam olarak #RRGGBB biçiminde mi? (\z: sondaki satır sonu da reddedilir)
 */
function avatar_renk_gecerli_mi($renk): bool
{
    return is_string($renk) && preg_match('/^#[0-9A-Fa-f]{6}\z/', $renk) === 1;
}

/**
 * Veritabanından gelen AvatarVerisi'ni (JSON metni ya da dizi) güvenli bir
 * renk dizisine çevirir: yalnızca izinli anahtarlar, yalnızca #RRGGBB değerler.
 * Geçersiz ya da eksik her değer varsayılan renge düşer.
 */
function avatar_renklerini_temizle($avatarVerisi): array
{
    $renkler = AVATAR_VARSAYILAN_RENKLER;

    $cozulmus = is_array($avatarVerisi) ? $avatarVerisi : null;
    if ($cozulmus === null && is_string($avatarVerisi) && $avatarVerisi !== '') {
        $cozulmus = json_decode($avatarVerisi, true);
    }
    if (!is_array($cozulmus)) {
        return $renkler;
    }

    foreach (array_keys(AVATAR_VARSAYILAN_RENKLER) as $anahtar) {
        if (isset($cozulmus[$anahtar]) && avatar_renk_gecerli_mi($cozulmus[$anahtar])) {
            $renkler[$anahtar] = $cozulmus[$anahtar];
        }
    }
    return $renkler;
}

/**
 * SVG width/height için güvenli boyut: sayı + isteğe bağlı birim (px, %, em, rem).
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

function renderAvatar($avatarJson, $size = '100%', $equippedItems = []) {
    // Renkler yeniden doğrulanır: veritabanında eskiden kalmış kötü veri
    // varsayılan renge düşer; yine de her değer çıktıda kaçışlanır.
    $data = array_map(
        fn($renk) => htmlspecialchars($renk, ENT_QUOTES, 'UTF-8'),
        avatar_renklerini_temizle($avatarJson)
    );
    $size = htmlspecialchars(avatar_boyut_temizle($size), ENT_QUOTES, 'UTF-8');
    if (!is_array($equippedItems)) {
        $equippedItems = [];
    }

    // --- EŞYA ÇİZİM MANTIĞI (PHP) ---
    $hatSVG = '';
    $glassesSVG = '';
    $neckSVG = '';

    foreach ($equippedItems as $item) {
        // Ad/tür yalnızca sabit metinlerle karşılaştırılır, çıktıya basılmaz.
        $name = $item['Ad'] ?? '';
        $type = $item['EsyaTuru'] ?? '';

        // Şapka Grubu
        if ($type === 'Şapka') {
            if ($name === 'Büyücü Şapkası') {
                $hatSVG = '<ellipse cx="0" cy="20" rx="70" ry="15" fill="#4C1D95" />
                           <path d="M-40,20 Q0,-100 40,20" fill="#6D28D9" />
                           <path d="M-40,20 Q0,35 40,20" fill="none" stroke="#4C1D95" stroke-width="2" />';
            } elseif ($name === 'Kral Tacı') {
                $hatSVG = '<path d="M-35,25 L-35,-10 L-15,10 L0,-25 L15,10 L35,-10 L35,25 Z" fill="#FACC15" stroke="#B45309" stroke-width="3" stroke-linejoin="round"/>
                           <circle cx="0" cy="20" r="3" fill="#B45309" />';
            }
        }
        // Gözlük Grubu
        elseif ($type === 'Gözlük') {
            if ($name === 'Güneş Gözlüğü') {
                $glassesSVG = '<g fill="#111827">
                                <rect x="-55" y="-15" width="45" height="25" rx="5" />
                                <rect x="10" y="-15" width="45" height="25" rx="5" />
                                <rect x="-10" y="-10" width="20" height="4" />
                               </g>';
            }
        }
        // Maske/Boyun Grubu
        elseif ($type === 'Maske') {
            // Maske, gözlük grubuna değil boyun grubuna çizilir ama yukarı taşınır
            if ($name === 'Ninja Maskesi') {
                $neckSVG .= '<path d="M-45,-120 Q0,-90 45,-120 L45,-90 Q0,-60 -45,-90 Z" fill="#1F2937" />
                             <rect x="-46" y="-120" width="92" height="40" rx="10" fill="#1F2937" />';
            }
        }
        elseif ($type === 'Kolye') {
            if ($name === 'Altın Kolye') {
                $neckSVG .= '<path d="M-30,10 Q0,50 30,10" fill="none" stroke="#FACC15" stroke-width="4" />
                             <circle cx="0" cy="30" r="8" fill="#FACC15" stroke="#B45309" stroke-width="2" />';
            }
        }
    }

    // --- SVG ÇIKTISI ---
    return '
    <svg width="'.$size.'" height="'.$size.'" viewBox="0 0 260 280" version="1.1" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <filter id="filter-1" x="-10%" y="-10%" width="120%" height="120%">
                <feOffset dx="0" dy="4" in="SourceAlpha" result="shadowOffsetOuter1"></feOffset>
                <feColorMatrix values="0 0 0 0 0   0 0 0 0 0   0 0 0 0 0  0 0 0 0.1 0" type="matrix" in="shadowOffsetOuter1"></feColorMatrix>
            </filter>
        </defs>
        
        <g id="Body" transform="translate(30.000000, 180.000000)">
            <path d="M100,0 C138.659932,0 170,31.3400675 170,70 L170,100 L30,100 L30,70 C30,31.3400675 61.3400675,0 100,0 Z" id="Shirt" fill="'.$data['topColor'].'"></path>
            <path d="M100,0 C80,0 70,-20 70,-30 L130,-30 C130,-20 120,0 100,0 Z" id="Neck" fill="'.$data['skinColor'].'"></path>
        </g>

        <g id="Head" transform="translate(48.000000, 30.000000)">
            <path d="M82,158 C36.7126515,158 0,122.604218 0,79 C0,35.3957816 36.7126515,0 82,0 C127.287349,0 164,35.3957816 164,79 C164,122.604218 127.287349,158 82,158 Z" id="Skin" fill="'.$data['skinColor'].'"></path>
            <path d="M82,-10 C30,-10 10,20 10,60 C10,65 12,70 12,70 C12,70 20,40 40,40 C60,40 82,60 82,60 C82,60 104,40 124,40 C144,40 152,70 152,70 C152,70 154,65 154,60 C154,20 134,-10 82,-10 Z" id="Hair" fill="'.$data['hairColor'].'"></path>

            <g id="Face" transform="translate(36.000000, 50.000000)">
                <circle id="EyeLeft" cx="18" cy="28" r="6" fill="'.$data['eyeColor'].'"></circle>
                <circle id="EyeRight" cx="74" cy="28" r="6" fill="'.$data['eyeColor'].'"></circle>
                <path d="M10,18 Q20,10 30,18" stroke="'.$data['hairColor'].'" stroke-width="3" fill="none" opacity="0.6"/>
                <path d="M62,18 Q72,10 82,18" stroke="'.$data['hairColor'].'" stroke-width="3" fill="none" opacity="0.6"/>
                <path d="M26,55 Q46,70 66,55" id="Mouth" stroke="#000000" stroke-width="3" stroke-linecap="round" fill="none" opacity="0.7"></path>
                <path d="M46,35 Q38,45 46,50" fill="none" stroke="#000000" stroke-width="2" opacity="0.1"></path>
            </g>
        </g>

        <g id="hatGroup" transform="translate(130, 45)">'.$hatSVG.'</g> 
        <g id="glassesGroup" transform="translate(130, 110)">'.$glassesSVG.'</g> 
        <g id="neckGroup" transform="translate(130, 190)">'.$neckSVG.'</g> 

    </svg>';
}
?>