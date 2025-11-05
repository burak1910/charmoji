<?php
// PHP Kimlik Doğrulama (Authentication) Hizmeti - service/auth.php
session_start();

// HATA AYIKLAMA MODU: Geliştirme aşamasında bu satırları aktif tut!
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ----------------------------------------------------------------
// 1. Veritabanı Bağlantı Bilgileri
// ----------------------------------------------------------------
$servername = "localhost";
$db_username = "root"; 
$db_password = ""; // XAMPP kullanıyorsan genellikle BOŞ bırakılır!
$dbname = "charmoji"; 

// MySQLi ile bağlantı nesnesini oluşturan fonksiyon
function get_db_connection() {
    global $servername, $db_username, $db_password, $dbname;
    $conn = new mysqli($servername, $db_username, $db_password, $dbname);

    if ($conn->connect_error) {
        // BAĞLANTI HATASI DURUMU
        die("KRİTİK HATA: Veritabanına bağlanılamadı! Lütfen XAMPP/MySQL durumunu ve $db_username, $db_password bilgilerini kontrol edin. Hata: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

// ----------------------------------------------------------------
// 2. AuthManager Class (Veritabanı İşlemleri)
// ----------------------------------------------------------------

class AuthManager {

    public static function register(array $data) {
        $conn = get_db_connection();
        
        $fullname = $conn->real_escape_string($data['fullname']);
        $email = $conn->real_escape_string($data['email']);
        $password = $data['password'];
        $kayitTarihi = date('Y-m-d');

        // E-posta Tekrarlılığı Kontrolü
        $check_query = $conn->prepare("SELECT KullaniciID FROM Kullanicilar WHERE Eposta = ?");
        if (!$check_query) {
            die("SORGULAMA PREPARE HATASI: " . $conn->error);
        }
        $check_query->bind_param("s", $email);
        $check_query->execute();
        $check_query->store_result();

        if ($check_query->num_rows > 0) {
            $check_query->close();
            $conn->close();
            return false; // E-posta zaten kullanımda
        }
        $check_query->close();

        // Şifreyi hash'le
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // KAYIT SORGUSU: (Ad, Eposta, SifreHash, KayitTarihi)
        $insert_query = $conn->prepare("INSERT INTO Kullanicilar (Ad, Eposta, SifreHash, KayitTarihi) VALUES (?, ?, ?, ?)");
        
        if (!$insert_query) {
            die("INSERT PREPARE HATASI: SQL sorgusu hatalı. Tablo adı/sütun adı kontrol et: " . $conn->error);
        }
        
        $insert_query->bind_param("ssss", $fullname, $email, $hashed_password, $kayitTarihi);

        $success = $insert_query->execute();
        
        if (!$success) {
            die("KAYIT BAŞARISIZ: Veritabanı sorgusu çalıştırılamadı: " . $insert_query->error);
        }
        
        $insert_query->close();
        $conn->close();
        
        return $success;
    }
    
    // (Login fonksiyonu aynı mantıkla çalışır, burada yer kazanmak için atlandı)
    public static function login($emailOrUsername, $password) {
        $conn = get_db_connection();
        
        $login_query = $conn->prepare("SELECT KullaniciID, Ad, Eposta, SifreHash FROM Kullanicilar WHERE Eposta = ?");
        
        if (!$login_query) {
             die("GİRİŞ PREPARE HATASI: SQL sorgusu hatalı. Tablo adı/sütun adı kontrol et: " . $conn->error);
        }
        
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
// 3. İşlem Yöneticisi (Değişmedi)
// ----------------------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location:  ../view/userpage.php");
    exit();
}

// Hangi formdan geldiğini belirleme (Form yapısı korundu)
if (isset($_POST['username']) && isset($_POST['password']) && !isset($_POST['fullname'])) {
    handle_login(); 
} elseif (isset($_POST['fullname']) && isset($_POST['email']) && isset($_POST['password'])) {
    handle_register();
} else {
    $_SESSION['error'] = "Geçersiz işlem talebi.";
    header("Location:  ../view/userpage.php");
    exit();
}

function handle_login() {
    $email_or_username = trim($_POST['username'] ?? ''); 
    $password = $_POST['password'] ?? '';

    if (empty($email_or_username) || empty($password)) {
        $_SESSION['error'] = "Lütfen e-posta ve şifreyi girin.";
        header("Location: login.php");
        exit();
    }

    $user = AuthManager::login($email_or_username, $password);

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['success'] = "Hoş geldiniz, " . $user['fullname'] . "!";
        header("Location: ../view/userpage.php");
        exit();
    } else {
        $_SESSION['error'] = "E-posta veya şifre hatalı.";
        header("Location: login.php");
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
        header("Location: register.php");
        exit();
    }

    if (AuthManager::register($data)) {
        $_SESSION['success'] = "Kayıt başarılı! Şimdi giriş yapabilirsiniz.";
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['error'] = "Bu e-posta adresi zaten kullanımda.";
        header("Location: register.php");
        exit();
    }
}
?>