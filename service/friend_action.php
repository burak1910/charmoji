<?php
// service/friend_action.php
// Arkadaşlık işlemleri: istek gönder, kabul, reddet, arkadaşlıktan çıkar.
// İki kipte çalışır:
//  - Form kipi (eski davranış): işlem sonrası Lonca sekmesine yönlendirir.
//  - JSON kipi: istek "Accept: application/json" taşıyorsa {success, kod} döner
//    (Lonca sekmesi sayfayı yenilemeden çalışır, UX 05 §5.1).
// Her iki kipte de yalnızca POST + CSRF jetonu kabul edilir; sahiplik kontrolleri
// FriendManager içindeki hazır ifadelerdedir.

$jsonKipi = stripos((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false;
if ($jsonKipi) {
    // Hata yanıtları (CSRF, sunucu hatası) da JSON olsun (bkz. config/uygulama.php).
    define('CHARMOJI_JSON_YANIT', true);
}

// auth.php (FriendManager üzerinden) oturumu güvenli ayarlarla başlatır.
require_once __DIR__ . '/FriendManager.php';

function arkadas_yanit(bool $basari, string $kod = '', array $ek = []): void
{
    global $jsonKipi;
    if ($jsonKipi) {
        // JSON kipinde arayüz bildirimi kendisi gösterir; sonraki sayfada tekrar çıkmasın.
        unset($_SESSION['bildirim']);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $basari] + ($kod !== '' ? ['kod' => $kod] : []) + $ek, JSON_UNESCAPED_UNICODE);
        exit();
    }
}

// Güvenlik: Giriş yapmamışsa at
if (!isset($_SESSION['user_id'])) {
    if ($jsonKipi) {
        http_response_code(401);
        arkadas_yanit(false, 'oturum_kapali');
    }
    header("Location: ../view/login.php");
    exit();
}

$myID = (int) $_SESSION['user_id'];
$donus = '';

// POST isteği gelmiş mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Jetonsuz ya da yanlış jetonlu istek 403 ile reddedilir.
    csrf_dogrula();

    // 1. İSTEK GÖNDERME (Maceracı bul)
    if (isset($_POST['action']) && $_POST['action'] === 'send_request') {
        $targetID = intval($_POST['target_id'] ?? 0);
        $donus = '#bul';

        $sonuc = FriendManager::sendRequest($myID, $targetID);

        if ($sonuc === true) {
            $_SESSION['bildirim'] = ['tur' => 'bilgi', 'metin' => 'Arkadaşlık isteği gönderildi.'];
            arkadas_yanit(true);
        } else {
            $kod = str_starts_with((string) $sonuc, 'Zaten') ? 'zaten_var' : 'gonderilemedi';
            $_SESSION['bildirim'] = ['tur' => 'hata', 'metin' => $kod === 'zaten_var'
                ? 'Bu maceracıyla aranızda zaten bir istek ya da arkadaşlık var.'
                : 'İstek gönderilemedi. Birazdan tekrar dene.'];
            arkadas_yanit(false, $kod);
        }
    }

    // 2. İSTEK KABUL ETME (İstekler)
    elseif (isset($_POST['accept_request_id'])) {
        $istekID = intval($_POST['accept_request_id']);
        $donus = '#istekler';

        if (FriendManager::acceptRequest($istekID, $myID)) {
            $_SESSION['bildirim'] = ['tur' => 'bilgi', 'metin' => 'Arkadaşlık isteği kabul edildi.'];
            arkadas_yanit(true);
        } else {
            $_SESSION['bildirim'] = ['tur' => 'hata', 'metin' => 'Bu istek artık geçerli değil.'];
            arkadas_yanit(false, 'artik_yok');
        }
    }

    // 3. İSTEK REDDETME (İstekler) - yalnızca bekleyen ve bana gelen istek
    elseif (isset($_POST['reject_request_id'])) {
        $istekID = intval($_POST['reject_request_id']);
        $donus = '#istekler';

        if (FriendManager::rejectRequest($istekID, $myID)) {
            $_SESSION['bildirim'] = ['tur' => 'bilgi', 'metin' => 'İstek reddedildi.'];
            arkadas_yanit(true);
        } else {
            $_SESSION['bildirim'] = ['tur' => 'hata', 'metin' => 'Bu istek artık geçerli değil.'];
            arkadas_yanit(false, 'artik_yok');
        }
    }

    // 4. ARKADAŞLIKTAN ÇIKARMA - yalnızca tarafı olduğum kabul edilmiş arkadaşlık
    elseif (isset($_POST['remove_friend_id'])) {
        $arkadaslikID = intval($_POST['remove_friend_id']);
        $donus = '#arkadaslar';

        if (FriendManager::removeFriend($arkadaslikID, $myID)) {
            $_SESSION['bildirim'] = ['tur' => 'bilgi', 'metin' => 'Arkadaşlıktan çıkarıldı.'];
            arkadas_yanit(true);
        } else {
            $_SESSION['bildirim'] = ['tur' => 'hata', 'metin' => 'Bu maceracı artık loncanda değil.'];
            arkadas_yanit(false, 'artik_yok');
        }
    }

    else {
        arkadas_yanit(false, 'gecersiz');
    }
} elseif ($jsonKipi) {
    http_response_code(405);
    arkadas_yanit(false, 'gecersiz');
}

// İşlem bitince kullanıcıyı Lonca sekmesine geri yolla
header("Location: ../view/lonca.php" . $donus);
exit();
