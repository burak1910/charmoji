<?php
// PHP Kimlik Doğrulama (Authentication) Hizmeti - service/auth.php

// ----------------------------------------------------------------
// 1. KRİTİK DÜZELTME: SESSION PATH AYARI (YENİDEN EKLENDİ!)
// ----------------------------------------------------------------
// Bu ayar, oturumun sadece 'service' klasöründe değil, sitenin ana 
// dizininde (/) ve tüm alt klasörlerde geçerli olmasını sağlar.
session_set_cookie_params(0, '/'); 
session_start();

// HATA AYIKLAMA MODU
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ----------------------------------------------------------------
// 1. Veritabanı Bağlantı Bilgileri
// ----------------------------------------------------------------
$servername = "localhost";
$db_username = "root"; 
$db_password = ""; 
$dbname = "charmoji"; 

function get_db_connection() {
    global $servername, $db_username, $db_password, $dbname;
    $conn = new mysqli($servername, $db_username, $db_password, $dbname);

    if ($conn->connect_error) {
        die("KRİTİK HATA: Veritabanına bağlanılamadı! " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

// ----------------------------------------------------------------
// 2. AuthManager Class
// ----------------------------------------------------------------

class AuthManager {

    public static function register(array $data) {
        $conn = get_db_connection();
        
        $fullname = $conn->real_escape_string($data['fullname']);
        $email = $conn->real_escape_string($data['email']);
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
// 3. İşlem Yöneticisi
// ----------------------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // Eğer biri auth.php'ye direkt girmeye çalışırsa index'e at
    header("Location: ../index.php"); 
    exit();
}

if (isset($_POST['username']) && isset($_POST['password']) && !isset($_POST['fullname'])) {
    handle_login(); 
} elseif (isset($_POST['fullname']) && isset($_POST['email']) && isset($_POST['password'])) {
    handle_register();
} else {
    $_SESSION['error'] = "Geçersiz işlem talebi.";
    header("Location: ../index.php"); // HATA DÜZELTMESİ
    exit();
}

function handle_login() {
    $email_or_username = trim($_POST['username'] ?? ''); 
    $password = $_POST['password'] ?? '';

    if (empty($email_or_username) || empty($password)) {
        $_SESSION['error'] = "Lütfen e-posta ve şifreyi girin.";
        header("Location: ../login.php"); // DÜZELTME: login.php yerine index.php
        exit();
    }

    $user = AuthManager::login($email_or_username, $password);

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['success'] = "Hoş geldiniz, " . $user['fullname'] . "!";
        
        // Başarılıysa View klasörüne git
        header("Location: ../view/userpage.php");
        exit();
    } else {
        $_SESSION['error'] = "E-posta veya şifre hatalı.";
        
        
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
        header("Location: ../register.php");
        exit();
    }

    if (AuthManager::register($data)) {
        
        // 🚨 YENİ EKLENEN KISIM: OTOMATİK GİRİŞ İŞLEMİ
        $user = AuthManager::login($data['email'], $data['password']);

        if ($user) {
            // Session'ı başlat ve userpage'e git
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['success'] = "Kayıt başarılı! Hoş geldiniz, " . $user['fullname'] . "!";

            header("Location: ../view/userpage.php"); // Artık direkt buraya gidiyoruz!
            exit();
        } else {
            // Otomatik girişte beklenmeyen bir hata olursa, yine de login ekranına gönder.
            $_SESSION['success'] = "Kayıt başarılı! Ancak otomatik girişte sorun oluştu. Lütfen şifrenizle giriş yapın.";
            header("Location: ../index.php"); 
            exit();
        }
        
    } else {
        $_SESSION['error'] = "Bu e-posta adresi zaten kullanımda.";
        header("Location: ../register.php");
        exit();
    }
}
?>