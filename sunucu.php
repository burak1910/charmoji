<?php
// PHP yerleşik sunucusu için yönlendirici betik.
// Kullanım (depo kökünden):  php -S 127.0.0.1:8000 -t . sunucu.php
// Gizli yolları (.env, .git, config/, database/, tests/, docs/, view/ortak/ ...) 404 ile kapatır;
// diğer her isteği olağan biçimde sunar. Apache için eşdeğeri: .htaccess

$yol = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$yol = str_replace('\\', '/', $yol);
// Uygulama alt dizinden de servis edilebilir (/charmoji/...): kurallar her yol parçasına uygulanır.
$parcalar = array_values(array_filter(explode('/', $yol), static fn($p) => $p !== ''));

$yasak = false;
foreach ($parcalar as $i => $parca) {
    $kucuk = strtolower($parca);
    if ($kucuk[0] === '.'                                   // .env, .git, .htaccess ...
        || in_array($kucuk, ['config', 'database', 'tests', 'docs', 'sunucu.php'], true)
        || ($kucuk === 'araclar' && $i > 0 && strtolower($parcalar[$i - 1]) === 'assets')
        // Ortak sayfa parçaları (view/ortak/*.php) yalnızca include edilir, doğrudan çağrılmaz.
        // Statik dosyalar (view/statik/*.css, *.js) bu kurala girmez, olağan biçimde sunulur.
        || ($kucuk === 'ortak' && $i > 0 && strtolower($parcalar[$i - 1]) === 'view')
        || preg_match('/\.(sql|md|sh|log|env)$/', $kucuk)
        || strpos($kucuk, "\0") !== false) {
        $yasak = true;
        break;
    }
}

if ($yasak) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "404 Not Found";
    return true;
}

return false;
