<?php
// config/env.php
// Composer'sız, küçük bir .env yükleyicisi.
//
// Biçim: her satırda ANAHTAR=DEGER. Boş satırlar ve # ile başlayan satırlar
// yok sayılır. Değer tek ya da çift tırnak içinde yazılabilir; tırnaksız
// değerlerde satır sonundaki " # yorum" kısmı atılır. Değişken genişletme
// (${...}) ve çok satırlı değer desteklenmez.
//
// Öncelik: gerçek ortam değişkenleri (sunucu/konteyner ayarı) .env
// dosyasındaki değerlerin üzerine yazılmaz; .env yalnızca eksikleri doldurur.

/**
 * .env dosyasını okuyup anahtar => değer dizisi döndürür.
 * Dosya yoksa ya da okunamazsa null döner.
 */
function charmoji_env_dosyasini_oku(string $dosya): ?array
{
    if (!is_file($dosya) || !is_readable($dosya)) {
        return null;
    }

    $satirlar = file($dosya, FILE_IGNORE_NEW_LINES);
    if ($satirlar === false) {
        return null;
    }

    $degerler = [];
    foreach ($satirlar as $satir) {
        $satir = trim($satir);
        if ($satir === '' || $satir[0] === '#') {
            continue;
        }
        if (str_starts_with($satir, 'export ')) {
            $satir = trim(substr($satir, 7));
        }

        $esittir = strpos($satir, '=');
        if ($esittir === false) {
            continue; // Geçersiz satır: sessizce atla.
        }

        $anahtar = trim(substr($satir, 0, $esittir));
        $deger = trim(substr($satir, $esittir + 1));

        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $anahtar)) {
            continue;
        }

        $ilk = $deger[0] ?? '';
        if (($ilk === '"' || $ilk === "'") && strlen($deger) >= 2 && substr($deger, -1) === $ilk) {
            $deger = substr($deger, 1, -1);
            if ($ilk === '"') {
                $deger = str_replace(['\\n', '\\"', '\\\\'], ["\n", '"', '\\'], $deger);
            }
        } else {
            // Tırnaksız değerde satır içi yorumu at.
            $deger = trim(preg_replace('/\s+#.*$/', '', $deger));
        }

        $degerler[$anahtar] = $deger;
    }

    return $degerler;
}

/**
 * Ayarlanmış yapılandırma deposu (statik).
 * İlk çağrıda $yukle verilirse depoya yazar.
 */
function charmoji_env_deposu(?array $yukle = null): array
{
    static $depo = [];
    if ($yukle !== null) {
        $depo = $yukle;
    }
    return $depo;
}

/**
 * Ayar değerini döndürür: önce gerçek ortam değişkeni, sonra .env içeriği.
 * Hiçbirinde yoksa $varsayilan döner.
 */
function charmoji_ayar(string $anahtar, ?string $varsayilan = null): ?string
{
    $ortam = getenv($anahtar);
    if ($ortam !== false) {
        return $ortam;
    }
    $depo = charmoji_env_deposu();
    return array_key_exists($anahtar, $depo) ? $depo[$anahtar] : $varsayilan;
}

/**
 * Ayarın tanımlı olup olmadığını (boş da olsa) söyler.
 */
function charmoji_ayar_tanimli_mi(string $anahtar): bool
{
    return getenv($anahtar) !== false || array_key_exists($anahtar, charmoji_env_deposu());
}
