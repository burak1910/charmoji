<?php
// service/guvenlik.php
// Oturum güvenliği yardımcıları.

/**
 * Oturumu güvenli ayarlarla başlatır:
 * - strict mode: istemcinin uydurduğu (sunucuda olmayan) oturum kimliği kabul edilmez,
 * - yalnızca çerez, URL'de oturum kimliği yok,
 * - çerez HttpOnly + SameSite=Lax, HTTPS'te Secure.
 */
function guvenli_oturum_baslat(): void
{
    if (session_status() === PHP_SESSION_ACTIVE || PHP_SAPI === 'cli') {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');

    // SESSION_COOKIE_SECURE: "1" her zaman, "0" hiçbir zaman, boş/tanımsız: HTTPS ise otomatik.
    $secureAyar = function_exists('charmoji_ayar') ? (string) charmoji_ayar('SESSION_COOKIE_SECURE', '') : '';
    if ($secureAyar === '1') {
        $secure = true;
    } elseif ($secureAyar === '0') {
        $secure = false;
    } else {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * Yetki seviyesi değiştiğinde (giriş/kayıt sonrası otomatik giriş) çağrılır:
 * oturum sabitleme (session fixation) saldırısına karşı yeni kimlik üretir.
 */
function oturum_kimligini_yenile(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}
