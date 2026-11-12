<?php
// view/lonca_ara.php - Maceracı bul araması (E40d, UX 05 §6), JSON.
// Salt okunur GET ucu; oturum gerektirir. Yalnızca kahraman adıyla, en az 2 karakter.
// Yanıtta e-posta yoktur. Kahraman adı JSON içinde düz metindir; arayüz textContent ile basar.
define('CHARMOJI_JSON_YANIT', true);
require_once __DIR__ . '/ortak/cekirdek.php';
require_once __DIR__ . '/../service/FriendManager.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'kod' => 'oturum_kapali']);
    exit();
}
$kullaniciID = (int) $_SESSION['user_id'];

$arama = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
if (mb_strlen($arama, 'UTF-8') < 2) {
    echo json_encode(['success' => false, 'kod' => 'kisa']);
    exit();
}
$arama = mb_substr($arama, 0, 100, 'UTF-8');

$sonuclar = FriendManager::searchUsers($arama, $kullaniciID);
$seviyeler = karakter_seviyeleri(get_db_connection(), array_column($sonuclar, 'KullaniciID'));

$cikti = [];
foreach ($sonuclar as $s) {
    $id = (int) $s['KullaniciID'];
    $cikti[] = [
        'id' => $id,
        'ad' => (string) $s['Ad'],
        'seviye' => $seviyeler[$id] ?? 1,
        // renderAvatar yalnızca doğrulanmış seçenek numaralarıyla ve sabit görsel yollarıyla HTML üretir.
        'avatar' => renderAvatar($s['AvatarVerisi'] ?? null, '48px'),
    ];
}
echo json_encode(['success' => true, 'sonuclar' => $cikti], JSON_UNESCAPED_UNICODE);
