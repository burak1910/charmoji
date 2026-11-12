<?php
// PHP Kimlik Doğrulama (Authentication) Hizmeti - service/auth.php

// Çıktı tamponlamayı başlat (Header hatalarını engeller)
ob_start();

// Hata gösterimi kapalı/loglama açık ve .env yapılandırması; her şeyden önce yüklenir.
require_once __DIR__ . '/../config/uygulama.php';

// Oturumu güvenli çerez ayarlarıyla başlat (HttpOnly, SameSite, strict mode).
require_once __DIR__ . '/guvenlik.php';
guvenli_oturum_baslat();

// ----------------------------------------------------------------
// 1. Veritabanı Bağlantısı (bilgiler .env / ortam değişkenlerinden)
// ----------------------------------------------------------------
function get_db_connection() {
    // Hatalar istisna olarak fırlatılır; ayrıntı log'a yazılır, kullanıcıya
    // genel mesaj gösterilir (bkz. config/uygulama.php).
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $port = (int) charmoji_ayar('DB_PORT', '3306');
    $conn = new mysqli(
        charmoji_ayar('DB_HOST'),
        charmoji_ayar('DB_USER'),
        charmoji_ayar('DB_PASSWORD', ''),
        charmoji_ayar('DB_NAME'),
        $port > 0 ? $port : 3306
    );
    $conn->set_charset("utf8mb4");
    return $conn;
}

// ----------------------------------------------------------------
// 2. AuthManager Class
// ----------------------------------------------------------------
class AuthManager {
    public static function register(array $data) {
        $conn = get_db_connection();
        
        $fullname = $data['fullname'];
        $email = $data['email'];
        $password = $data['password'];
        $kayitTarihi = date('Y-m-d');

        $check_query = $conn->prepare("SELECT KullaniciID FROM Kullanicilar WHERE Eposta = ?");
        $check_query->bind_param("s", $email);
        $check_query->execute();
        $check_query->store_result();

        if ($check_query->num_rows > 0) {
            $check_query->close();
            $conn->close();
            return false;
        }
        $check_query->close();

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $insert_query = $conn->prepare("INSERT INTO Kullanicilar (Ad, Eposta, SifreHash, KayitTarihi) VALUES (?, ?, ?, ?)");
        $insert_query->bind_param("ssss", $fullname, $email, $hashed_password, $kayitTarihi);

        $success = $insert_query->execute();
        
        $insert_query->close();
        $conn->close();
        
        return $success;
    }
    
    public static function login($emailOrUsername, $password) {
        $conn = get_db_connection();
        
        $login_query = $conn->prepare("SELECT KullaniciID, Ad, Eposta, SifreHash FROM Kullanicilar WHERE Eposta = ?");
        $login_query->bind_param("s", $emailOrUsername);
        $login_query->execute();
        $result = $login_query->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['SifreHash'])) {
                $login_query->close();
                $conn->close();
                return [
                    'id' => $user['KullaniciID'],
                    'username' => $user['Eposta'],
                    'fullname' => $user['Ad']
                ];
            }
        }
        $login_query->close();
        $conn->close();
        return false;
    }
}

// ----------------------------------------------------------------
// 3. KRİTİK DÜZELTME: SİHİRLİ KONTROL 🛡️
// ----------------------------------------------------------------
// Bu kod bloğu, auth.php SADECE doğrudan çağrıldığında çalışır.
// userpage.php gibi dosyalar burayı "include" ettiğinde bu kısım ÇALIŞMAZ.
// Böylece sonsuz döngü engellenir.

if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        // Formdan gelmiyorsa ana sayfaya at
        header("Location: ../view/index.php");
        exit();
    }

    // Giriş ve kayıt formları CSRF jetonu taşımalı (login CSRF'e karşı).
    csrf_dogrula();

    if (isset($_POST['username']) && isset($_POST['password']) && !isset($_POST['fullname'])) {
        handle_login(); 
    } elseif (isset($_POST['fullname']) && isset($_POST['email']) && isset($_POST['password'])) {
        handle_register();
    } else {
        $_SESSION['error'] = "Geçersiz işlem talebi.";
        header("Location: ../view/index.php");
        exit();
    }
}

// ----------------------------------------------------------------
// 4. Yardımcı Fonksiyonlar
// ----------------------------------------------------------------

function handle_login() {
    $email_or_username = trim($_POST['username'] ?? ''); 
    $password = $_POST['password'] ?? '';

    if (empty($email_or_username) || empty($password)) {
        $_SESSION['error'] = "Lütfen e-posta ve şifreyi girin.";
        header("Location: ../view/login.php"); 
        exit();
    }

    $user = AuthManager::login($email_or_username, $password);

    if ($user) {
        // Oturum sabitlemeye karşı: girişte yeni oturum kimliği.
        oturum_kimligini_yenile();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['success'] = "Hoş geldiniz, " . $user['fullname'] . "!";
        
        header("Location: ../view/karargah.php");
        exit();
    } else {
        $_SESSION['error'] = "E-posta veya şifre hatalı.";
        header("Location: ../view/login.php");
        exit();
    }
}

function handle_register() {
    $data = [
        'fullname' => trim($_POST['fullname'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? ''
    ];

    if (empty($data['fullname']) || empty($data['email']) || empty($data['password']) || $data['password'] !== $data['confirm_password']) {
        $_SESSION['error'] = "Lütfen tüm alanları doldurun ve şifrelerin eşleştiğinden emin olun.";
        header("Location: ../view/register.php");
        exit();
    }

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Lütfen geçerli bir e-posta adresi girin.";
        header("Location: ../view/register.php");
        exit();
    }

    if (AuthManager::register($data)) {
        // Otomatik giriş yap
        $user = AuthManager::login($data['email'], $data['password']);
        if ($user) {
            oturum_kimligini_yenile();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['success'] = "Kayıt başarılı! Hoş geldiniz.";
            
            header("Location: ../view/karargah.php");
            exit();
        } else {
            $_SESSION['success'] = "Kayıt başarılı. Lütfen giriş yapın.";
            header("Location: ../view/login.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Bu e-posta adresi zaten kullanımda.";
        header("Location: ../view/register.php");
        exit();
    }
}
?>