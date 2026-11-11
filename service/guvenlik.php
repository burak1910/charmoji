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

// ----------------------------------------------------------------
// CSRF koruması (oturuma bağlı eşitleyici jeton)
// ----------------------------------------------------------------

/**
 * Oturumun CSRF jetonunu döndürür; yoksa üretir.
 */
function csrf_jetonu(): string
{
    if (empty($_SESSION['csrf_jetonu']) || !is_string($_SESSION['csrf_jetonu'])) {
        $_SESSION['csrf_jetonu'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_jetonu'];
}

/**
 * Formlara eklenecek gizli alan.
 */
function csrf_alani(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_jetonu(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * İstekteki jeton (POST alanı csrf_token ya da X-CSRF-Token başlığı) oturumdakiyle eşleşiyor mu?
 */
function csrf_gecerli_mi(): bool
{
    $beklenen = $_SESSION['csrf_jetonu'] ?? '';
    $gelen = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($beklenen) && $beklenen !== ''
        && is_string($gelen) && hash_equals($beklenen, $gelen);
}

/**
 * Durum değiştiren her POST isteğinin başında çağrılır. Jeton yoksa ya da
 * yanlışsa 403 ile isteği bitirir (JSON uç noktalarında JSON yanıt).
 */
function csrf_dogrula(): void
{
    if (csrf_gecerli_mi()) {
        return;
    }
    error_log('[charmoji] CSRF doğrulaması başarısız: ' . ($_SERVER['REQUEST_METHOD'] ?? '') . ' ' . ($_SERVER['SCRIPT_NAME'] ?? ''));
    charmoji_genel_hata(403, 'Güvenlik doğrulaması başarısız oldu (CSRF). Sayfayı yenileyip tekrar deneyin.');
}

/**
 * Uç nokta yalnızca POST kabul eder; aksi halde 405 ile biter.
 */
function yalnizca_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Allow: POST');
        charmoji_genel_hata(405, 'Bu işlem yalnızca form gönderimiyle (POST) yapılabilir.');
    }
}

/**
 * Yetki seviyesi değiştiğinde (giriş/kayıt sonrası otomatik giriş) çağrılır:
 * oturum sabitleme (session fixation) saldırısına karşı yeni kimlik üretir.
 */
function oturum_kimligini_yenile(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
        // Giriş öncesi jeton giriş sonrasında kullanılmasın.
        unset($_SESSION['csrf_jetonu']);
    }
}
