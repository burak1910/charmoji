<?php
// config/uygulama.php
// Uygulamanın ortak açılışı: hata gösterimi/loglama ve .env yapılandırması.
// service/auth.php tarafından, başka her şeyden önce yüklenir.

if (defined('CHARMOJI_YAPILANDIRILDI')) {
    return;
}
define('CHARMOJI_YAPILANDIRILDI', true);
define('CHARMOJI_KOK', dirname(__DIR__));

require_once __DIR__ . '/env.php';

// ----------------------------------------------------------------
// 0. Oyun günü (docs/ux/09 VARSAYIM S1): gün 00:00'da biter, Europe/Istanbul,
//    sunucu saati esas. Seri, geri alma, meydan okumalar ve sandık bu sınırı
//    kullanır. Tarih hesaplayan her kod PHP date() ile bu saat dilimini kullanır;
//    SQL'de CURDATE()/NOW() kullanılmaz (veritabanı saat dilimi farklı olabilir).
//    Günün kendisi view/ortak/cekirdek.php oyun_bugun() / oyun_dun() ile okunur.
// ----------------------------------------------------------------
const CHARMOJI_SAAT_DILIMI = 'Europe/Istanbul';
date_default_timezone_set(CHARMOJI_SAAT_DILIMI);

// ----------------------------------------------------------------
// 1. Hata gösterimi: kullanıcıya asla PHP/SQL hata metni gösterme, logla.
// ----------------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');

/**
 * Bu istek JSON yanıt mı bekliyor? (ajax_islem.php gibi uç noktalar
 * dosyanın başında CHARMOJI_JSON_YANIT sabitini tanımlar.)
 */
function charmoji_json_istegi_mi(): bool
{
    return defined('CHARMOJI_JSON_YANIT');
}

/**
 * Kullanıcıya genel bir hata mesajı gösterip isteği bitirir.
 * Ayrıntı yalnızca log'a yazılmış olmalıdır.
 */
function charmoji_genel_hata(int $durumKodu, string $mesaj): void
{
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $mesaj . PHP_EOL);
        exit(1);
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code($durumKodu);
    }

    if (charmoji_json_istegi_mi()) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['success' => false, 'message' => $mesaj], JSON_UNESCAPED_UNICODE);
    } else {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }
        echo '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><title>Hata - Charmoji</title></head>'
            . '<body style="font-family:sans-serif;padding:2rem">'
            . '<h1>Bir sorun oluştu</h1><p>' . htmlspecialchars($mesaj, ENT_QUOTES, 'UTF-8') . '</p>'
            . '</body></html>';
    }
    exit();
}

// Yakalanmamış istisnalar (ör. mysqli_sql_exception): log'a ayrıntı, kullanıcıya genel mesaj.
set_exception_handler(function (Throwable $e): void {
    error_log(sprintf(
        '[charmoji] Yakalanmamış %s: %s (%s:%d)',
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));
    charmoji_genel_hata(500, 'Sunucuda beklenmeyen bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
});

// ----------------------------------------------------------------
// 2. .env yükle
// ----------------------------------------------------------------
// Varsayılan konum proje kökündeki .env dosyasıdır. Test ya da dağıtım için
// CHARMOJI_ENV_FILE ortam değişkeniyle başka bir dosya gösterilebilir.
$charmojiEnvDosyasi = getenv('CHARMOJI_ENV_FILE') ?: CHARMOJI_KOK . '/.env';
$charmojiEnv = charmoji_env_dosyasini_oku($charmojiEnvDosyasi);
charmoji_env_deposu($charmojiEnv ?? []);

// Log dosyası ayarlandıysa PHP hatalarını oraya yaz (yoksa sunucunun hata günlüğü kullanılır).
$charmojiLog = charmoji_ayar('APP_LOG_FILE', '');
if ($charmojiLog !== '') {
    ini_set('error_log', $charmojiLog);
}

// Zorunlu veritabanı ayarları: DB_PASSWORD boş olabilir ama tanımlı olmalı.
$charmojiEksikler = [];
foreach (['DB_HOST', 'DB_NAME', 'DB_USER'] as $anahtar) {
    if ((string) charmoji_ayar($anahtar, '') === '') {
        $charmojiEksikler[] = $anahtar;
    }
}
if (!charmoji_ayar_tanimli_mi('DB_PASSWORD')) {
    $charmojiEksikler[] = 'DB_PASSWORD';
}

if ($charmojiEksikler !== []) {
    $neden = $charmojiEnv === null
        ? '.env dosyası bulunamadı (' . $charmojiEnvDosyasi . ')'
        : '.env dosyasında eksik ayar var';
    error_log('[charmoji] Yapılandırma hatası: ' . $neden . '; eksik: ' . implode(', ', $charmojiEksikler));
    charmoji_genel_hata(
        500,
        'Uygulama yapılandırılmamış: veritabanı ayarları bulunamadı. '
        . 'Proje kökündeki .env.example dosyasını .env adıyla kopyalayıp '
        . 'DB_HOST, DB_NAME, DB_USER ve DB_PASSWORD değerlerini doldurun. '
        . '(Eksik: ' . implode(', ', $charmojiEksikler) . ')'
    );
}

unset($charmojiEnvDosyasi, $charmojiEnv, $charmojiLog, $charmojiEksikler);
