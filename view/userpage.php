<?php
// view/userpage.php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../service/auth.php'; 
require_once __DIR__ . '/../service/FriendManager.php';
require_once __DIR__ . '/avatar_renderer.php'; // YENİ: Avatar çiziciyi dahil et

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php");
    exit(); 
}

$kullaniciID = $_SESSION['user_id'];
$conn = get_db_connection();

// --- KULLANICI BİLGİLERİNİ ÇEK (Avatar Verisi Dahil) ---
// Bakiye ve AvatarVerisi'ni tek sorguda alalım
$userQuery = $conn->query("SELECT Bakiye, AvatarVerisi FROM Kullanicilar WHERE KullaniciID = $kullaniciID");
$userData = $userQuery->fetch_assoc();
$bakiye = $userData['Bakiye'];
$myAvatarData = $userData['AvatarVerisi']; // Giriş yapan kişinin avatarı
// --- YENİ EKLENDİ: KUŞANILAN EŞYALARI ÇEK ---
$itemsSql = "SELECT M.Ad, M.EsyaTuru 
             FROM KullaniciEnvanteri KE 
             JOIN MarketEsyalari M ON KE.EsyaID = M.EsyaID 
             WHERE KE.KullaniciID = $kullaniciID AND KE.KusandiMi = 1";
$myItems = $conn->query($itemsSql)->fetch_all(MYSQLI_ASSOC);
// ... (Günlük Görev Oluşturucu Kodları Aynen Kalıyor) ...
$bugun = date('Y-m-d');
$checkGorev = $conn->query("SELECT COUNT(*) as Sayi FROM KullaniciGunlukGorevleri WHERE KullaniciID = $kullaniciID AND Tarih = '$bugun'");
$rowGorev = $checkGorev->fetch_assoc();
if ($rowGorev['Sayi'] == 0) {
    $tanimlar = $conn->query("SELECT GorevTanimID FROM GorevTanimlari");
    while($t = $tanimlar->fetch_assoc()) {
        $conn->query("INSERT INTO KullaniciGunlukGorevleri (KullaniciID, GorevTanimID, Tarih) VALUES ($kullaniciID, {$t['GorevTanimID']}, '$bugun')");
    }
}
$conn->query("UPDATE KullaniciGunlukGorevleri SET MevcutIlerleme = 1, TamamlandiMi = 1 WHERE KullaniciID = $kullaniciID AND Tarih = '$bugun' AND GorevTanimID = (SELECT GorevTanimID FROM GorevTanimlari WHERE Baslik = 'Günlük Giriş' LIMIT 1)");

$questSql = "SELECT KG.*, GT.Baslik, GT.Aciklama, GT.OdulPara, GT.HedefSayi, GT.Ikon 
             FROM KullaniciGunlukGorevleri KG
             JOIN GorevTanimlari GT ON KG.GorevTanimID = GT.GorevTanimID
             WHERE KG.KullaniciID = $kullaniciID AND KG.Tarih = '$bugun'";
$quests = $conn->query($questSql)->fetch_all(MYSQLI_ASSOC);

// ... (Level Hesaplama Kodları Aynen Kalıyor) ...
function hesaplaLevelVerileri($toplamPuan) {
    $level = 1; $gerekliXP = 100;
    while ($toplamPuan >= $gerekliXP) { $toplamPuan -= $gerekliXP; $level++; $gerekliXP *= 2; }
    return ['level' => $level, 'currentXP' => $toplamPuan, 'neededXP' => $gerekliXP, 'percent' => ($toplamPuan / $gerekliXP) * 100];
}
$stats = []; $levels = []; $barData = []; 
$varsayilanStatlar = ['Zeka', 'Guc', 'Sosyallik', 'Disiplin', 'Yaraticilik'];
foreach ($varsayilanStatlar as $stat) { $conn->query("INSERT IGNORE INTO KullaniciStatlari (KullaniciID, StatTuru, ToplamPuan) VALUES ($kullaniciID, '$stat', 0)"); }
$statSql = "SELECT StatTuru, ToplamPuan FROM KullaniciStatlari WHERE KullaniciID = ?";
$stmt = $conn->prepare($statSql); $stmt->bind_param("i", $kullaniciID); $stmt->execute(); $res = $stmt->get_result();
while($row = $res->fetch_assoc()) {
    $hesap = hesaplaLevelVerileri($row['ToplamPuan']);
    $levels[$row['StatTuru']] = $hesap['level'];
    $barData[$row['StatTuru']] = $hesap;
}
$siraliLevels = []; foreach($varsayilanStatlar as $key) { $siraliLevels[] = isset($levels[$key]) ? $levels[$key] : 1; }
$jsonLevels = json_encode($siraliLevels); $jsonLabels = json_encode($varsayilanStatlar);
$ortalamaLevel = floor(array_sum($levels) / count($levels));

if (isset($_POST['add_habit_btn'])) {
    $yeniAd = trim($_POST['habit_name']); $secilenStat = $_POST['habit_stat']; $puan = 10;
    if (!empty($yeniAd)) {
        $sorgu = $conn->prepare("SELECT AliskanlikID FROM Aliskanliklar WHERE Ad = ?"); $sorgu->bind_param("s", $yeniAd); $sorgu->execute(); $sonuc = $sorgu->get_result();
        if($sonuc->num_rows > 0) { $aliskanlikID = $sonuc->fetch_assoc()['AliskanlikID']; } 
        else { $ekle = $conn->prepare("INSERT INTO Aliskanliklar (Ad, StatTuru, Puan) VALUES (?, ?, ?)"); $ekle->bind_param("ssi", $yeniAd, $secilenStat, $puan); $ekle->execute(); $aliskanlikID = $ekle->insert_id; }
        $conn->query("INSERT IGNORE INTO KullaniciAliskanliklari (KullaniciID, AliskanlikID, BaslangicTarihi) VALUES ($kullaniciID, $aliskanlikID, CURDATE())");
        header("Location: userpage.php"); exit();
    }
}

$habitSql = "SELECT KA.KullaniciAliskanlikID, A.Ad, A.StatTuru, A.Puan, KA.Seri, (SELECT COUNT(*) FROM TakipKayitlari TK WHERE TK.KullaniciAliskanlikID = KA.KullaniciAliskanlikID AND TK.Tarih = '$bugun') as BugunYapildi FROM KullaniciAliskanliklari KA JOIN Aliskanliklar A ON KA.AliskanlikID = A.AliskanlikID WHERE KA.KullaniciID = $kullaniciID AND KA.AktifMi = 1";
$aliskanliklar = $conn->query($habitSql)->fetch_all(MYSQLI_ASSOC);
$toplamGorev = count($aliskanliklar); $tamamlanan = 0; foreach($aliskanliklar as $a) if($a['BugunYapildi'] > 0) $tamamlanan++;

$arkadaslar = FriendManager::getFriends($kullaniciID);
$istekler = FriendManager::getPendingRequests($kullaniciID);
$topluluk = FriendManager::getCommunitySuggestions($kullaniciID);
?>

<!DOCTYPE html>
<html lang="tr" class="dark scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Charmoji - Profil</title>
    <link rel="icon" type="image/png" href="../charmoji.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        primary: '#F97316', 
                        primaryHover: '#c2410c',
                        darkBg: '#111827', 
                        cardBg: '#1F2937', 
                    }
                }
            }
        }
    </script>
    <style>
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #111827; }
        ::-webkit-scrollbar-thumb { background: #374151; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #F97316; }
        body { background-color: #111827; color: #F3F4F6; }
        .habit-check:checked + div { text-decoration: line-through; opacity: 0.5; }
        .habit-check:checked + div .icon-box { filter: grayscale(100%); }
        dialog::backdrop { background: rgba(0, 0, 0, 0.8); backdrop-filter: blur(4px); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .tab-btn.active { border-bottom: 2px solid #F97316; color: #F97316; }
        .tab-btn { color: #9CA3AF; }
        .tab-btn:hover { color: #fff; }
    </style>
</head>
<body class="antialiased min-h-screen">

    <nav class="fixed w-full z-50 bg-gray-900/90 backdrop-blur-md border-b border-gray-700 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 relative">
                
                <div class="flex-shrink-0 flex items-center gap-2 z-10">
                    <div class="font-black text-2xl text-primary tracking-tighter drop-shadow-md">CHARMOJİ</div>
                </div>
                
                <div class="absolute left-1/2 transform -translate-x-1/2 z-10">
                    <a href="market.php" class="group relative px-6 py-2">
                        <div class="absolute inset-0 bg-primary/20 rounded-lg blur opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                        <span class="relative font-black text-xl text-white tracking-[0.2em] group-hover:text-primary transition-colors duration-300">MARKET</span>
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-primary transition-all duration-300 group-hover:w-full"></span>
                    </a>
                </div>

                <div class="flex items-center gap-4 z-10">
                    <div class="hidden sm:flex items-center gap-1.5 bg-gray-950 px-3 py-1.5 rounded-lg border border-yellow-600/40 text-yellow-500 font-bold text-sm shadow-inner">
                        <i class="fa-solid fa-coins text-yellow-400"></i> 
                        <span id="user-balance" class="text-gray-100"><?php echo number_format($bakiye, 0); ?></span>
                    </div>

                    <span id="navbar-level" class="hidden sm:block text-xs font-bold bg-gray-800 text-primary px-3 py-1.5 rounded-lg border border-gray-700">
                        Lvl <?php echo $ortalamaLevel; ?>
                    </span>

                    <a href="avatar.php" class="hover:scale-105 transition">
                        <?php echo renderAvatar($myAvatarData, '40px', $myItems); ?>
                    </a>
                    
                    <a href="../service/logout.php" class="text-gray-500 hover:text-red-500 transition ml-1" title="Çıkış Yap">
                        <i class="fa-solid fa-right-from-bracket text-lg"></i>
                    </a>
                </div>

            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-12 grid grid-cols-1 lg:grid-cols-4 gap-8">
        
        <aside class="lg:col-span-1 space-y-6">
            <div class="bg-gray-800 p-6 rounded-xl border border-gray-700 shadow-xl relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-primary to-yellow-500"></div>
                <div class="relative inline-block mt-4 text-center w-full">
                    
                    <div class="flex justify-center">
                        <?php echo renderAvatar($myAvatarData, '120px', $myItems); ?>
                    </div>

                    <span id="sidebar-level" class="absolute bottom-0 right-1/2 translate-x-12 bg-primary text-white text-xs font-bold px-2 py-0.5 rounded-full border border-gray-900"><?php echo $ortalamaLevel; ?></span>
                </div>
                <h2 class="text-lg font-bold text-center text-white mt-3">Karakter İstatistikleri</h2>
                <a href="avatar.php" class="block w-full text-center mt-2 text-xs text-primary hover:underline">Görünümü Düzenle</a>

                <button onclick="toggleStatsPanel()" class="w-full bg-gray-700 hover:bg-gray-600 text-white border border-gray-600 font-semibold py-2 px-4 rounded-lg transition text-xs uppercase tracking-wide flex items-center justify-center gap-2 mt-4 mb-4">
                    <i class="fa-solid fa-chart-radar text-primary"></i> Yetenek Ağacı
                </button>
                <div id="statsPanel" class="hidden mt-4 pt-4 border-t border-gray-700"><canvas id="statRadar"></canvas></div>
                
                <div class="mt-6 space-y-5">
                    <?php foreach($varsayilanStatlar as $stat): $veri = $barData[$stat] ?? ['level'=>1, 'percent'=>0, 'currentXP'=>0, 'neededXP'=>100]; ?>
                    <div>
                        <div class="flex justify-between items-end mb-1">
                            <span class="text-xs font-bold text-gray-300 uppercase"><?php echo $stat; ?></span>
                            <span id="stat-text-<?php echo $stat; ?>" class="text-[10px] text-gray-500 font-mono">Lvl <?php echo $veri['level']; ?> <span class="text-gray-600">|</span> <?php echo $veri['currentXP']; ?>/<?php echo $veri['neededXP']; ?></span>
                        </div>
                        <div class="w-full bg-gray-900 border border-gray-700 rounded-full h-2 overflow-hidden">
                            <div id="stat-bar-<?php echo $stat; ?>" class="bg-gradient-to-r from-primary to-yellow-500 h-2 rounded-full transition-all duration-1000 relative shadow-[0_0_10px_rgba(249,115,22,0.4)]" style="width: <?php echo $veri['percent']; ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>

        <main class="lg:col-span-2">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-white tracking-tight">Görev Panosu</h2>
                    <p class="text-sm text-gray-400 mt-1">Bugün: <span class="text-primary font-bold"><?php echo $tamamlanan; ?>/<?php echo $toplamGorev; ?></span> Tamamlandı</p>
                </div>
                <button onclick="document.getElementById('habitModal').showModal()" class="bg-primary hover:bg-primaryHover text-white px-5 py-2.5 rounded-lg text-sm font-bold transition shadow-lg shadow-orange-900/20 flex items-center gap-2 transform hover:-translate-y-0.5"><i class="fa-solid fa-plus"></i> Görev Ekle</button>
            </div>
            <div class="space-y-4">
                <?php if(empty($aliskanliklar)): ?>
                    <div class="text-center py-16 bg-gray-800 rounded-xl border border-gray-700 border-dashed"><i class="fa-solid fa-ghost text-gray-600 text-5xl mb-4"></i><p class="text-gray-500 text-sm">Henüz bir görevin yok.</p></div>
                <?php endif; ?>
                <?php foreach($aliskanliklar as $gorev): $hafta = floor($gorev['Seri'] / 7); $carpan = min(2.00, 1 + ($hafta * 0.25)); ?>
                    <div id="habit-card-<?php echo $gorev['KullaniciAliskanlikID']; ?>" class="relative group transition-all duration-500 ease-out">
                        <label class="block bg-gray-800 p-5 rounded-xl border border-gray-700 shadow-md cursor-pointer hover:border-primary/50 hover:bg-gray-750 transition-all relative overflow-hidden">
                            <div class="absolute left-0 top-0 bottom-0 w-1.5 <?php if($gorev['StatTuru']=='Guc') echo 'bg-red-600'; elseif($gorev['StatTuru']=='Zeka') echo 'bg-blue-600'; elseif($gorev['StatTuru']=='Sosyallik') echo 'bg-yellow-500'; else echo 'bg-gray-500'; ?>"></div>
                            <div class="flex items-center gap-4 pl-3">
                                <div class="relative flex-shrink-0">
                                    <input type="checkbox" class="habit-check peer appearance-none w-6 h-6 border-2 border-gray-600 rounded bg-gray-900 checked:bg-primary checked:border-primary transition-colors cursor-pointer" onclick="toggleHabit(<?php echo $gorev['KullaniciAliskanlikID']; ?>, this)" <?php echo ($gorev['BugunYapildi'] > 0) ? 'checked' : ''; ?>>
                                    <i class="fa-solid fa-check text-white text-xs absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 opacity-0 peer-checked:opacity-100 pointer-events-none"></i>
                                </div>
                                <div class="flex-1 pr-8">
                                    <div class="flex justify-between items-start">
                                        <h3 class="font-bold text-gray-200 text-lg group-hover:text-white transition"><?php echo htmlspecialchars($gorev['Ad']); ?></h3>
                                        <div class="flex flex-col items-end">
                                            <span id="streak-count-<?php echo $gorev['KullaniciAliskanlikID']; ?>" class="text-xs font-bold text-primary flex items-center gap-1"><?php if($gorev['Seri'] > 0): ?><i class="fa-solid fa-fire animate-pulse"></i> <?php echo $gorev['Seri']; ?> Gün<?php endif; ?></span>
                                            <?php if($carpan > 1): ?><span class="text-[10px] font-bold text-gray-400 bg-gray-900 px-2 py-0.5 rounded mt-1 border border-gray-700"><?php echo $carpan; ?>x</span><?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 mt-2">
                                        <span class="text-[10px] bg-gray-900 text-gray-400 border border-gray-700 px-2 py-0.5 rounded uppercase tracking-wider font-bold"><?php echo $gorev['StatTuru']; ?></span>
                                        <span class="text-[10px] text-green-500 font-bold flex items-center gap-1"><i class="fa-solid fa-caret-up"></i> +<?php echo round($gorev['Puan'] * $carpan); ?> XP</span>
                                    </div>
                                </div>
                            </div>
                        </label>
                        <button onclick="deleteHabit(<?php echo $gorev['KullaniciAliskanlikID']; ?>, event)" class="absolute top-4 right-4 text-gray-600 hover:text-red-500 bg-gray-800 hover:bg-gray-700 p-2 rounded-lg transition-all z-10 opacity-0 group-hover:opacity-100 shadow-lg border border-gray-700"><i class="fa-solid fa-trash-can"></i></button>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>

        <aside class="lg:col-span-1 space-y-6">
            
            <div class="bg-gray-800 p-5 rounded-xl border border-gray-700 shadow-xl">
                <div class="flex border-b border-gray-700 mb-4 pb-0">
                    <button onclick="openTab('topluluk')" class="tab-btn active flex-1 text-center pb-3 text-xs font-bold transition uppercase tracking-wide">Topluluk</button>
                    <button onclick="openTab('arkadaslar')" class="tab-btn flex-1 text-center pb-3 text-xs font-bold transition uppercase tracking-wide">Arkadaşlar</button>
                    <button onclick="openTab('istekler')" class="tab-btn flex-1 text-center pb-3 text-xs font-bold transition uppercase tracking-wide relative">İstekler <?php if(count($istekler) > 0): ?><span class="absolute top-0 right-1 w-2 h-2 bg-primary rounded-full animate-ping"></span><span class="absolute top-0 right-1 w-2 h-2 bg-primary rounded-full"></span><?php endif; ?></button>
                </div>
                <div id="topluluk" class="tab-content active">
                    <ul class="space-y-2">
                        <?php if(empty($topluluk)): ?><li class="text-center text-gray-500 text-xs py-4">Kimse bulunamadı.</li><?php endif; ?>
                        <?php foreach($topluluk as $kisi): ?>
                        <li class="flex items-center justify-between hover:bg-gray-700/50 p-2 rounded-lg transition border border-transparent hover:border-gray-600">
                            <div class="flex items-center gap-3">
                                <?php echo renderAvatar($kisi['AvatarVerisi'] ?? null, '36px'); ?>
                                <div><p class="text-sm font-semibold text-gray-300"><?php echo htmlspecialchars($kisi['Ad']); ?></p><p class="text-[10px] text-gray-500">Lvl <?php echo floor($kisi['ToplamXP'] / 100); ?></p></div>
                            </div>
                            <form action="../service/friend_action.php" method="POST"><input type="hidden" name="target_id" value="<?php echo $kisi['KullaniciID']; ?>"><input type="hidden" name="action" value="send_request"><button class="w-8 h-8 rounded-full bg-gray-700 text-gray-400 hover:bg-primary hover:text-white border border-gray-600 hover:border-primary transition flex items-center justify-center"><i class="fa-solid fa-plus text-xs"></i></button></form>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div id="arkadaslar" class="tab-content">
                    <ul class="space-y-2">
                        <?php if(empty($arkadaslar)): ?><li class="text-center text-gray-500 text-xs py-4">Listen boş.</li><?php endif; ?>
                        <?php foreach($arkadaslar as $arkadas): ?>
                        <li class="flex items-center gap-3 hover:bg-gray-700 p-2 rounded-lg transition border border-transparent hover:border-gray-600">
                            <?php echo renderAvatar($arkadas['AvatarVerisi'] ?? null, '36px'); ?>
                            <span class="text-sm font-semibold text-gray-300"><?php echo htmlspecialchars($arkadas['Ad']); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div id="istekler" class="tab-content">
                    <ul class="space-y-3">
                        <?php if(empty($istekler)): ?><li class="text-center text-gray-500 text-xs py-4">Bekleyen yok.</li><?php endif; ?>
                        <?php foreach($istekler as $istek): ?>
                        <li class="bg-gray-700/50 p-3 rounded-lg border border-gray-700">
                            <div class="flex items-center gap-3 mb-3">
                                <?php echo renderAvatar($istek['AvatarVerisi'] ?? null, '36px'); ?>
                                <span class="text-sm font-bold text-gray-200"><?php echo htmlspecialchars($istek['Ad']); ?></span>
                            </div>
                            <div class="flex gap-2">
                                <form action="../service/friend_action.php" method="POST" class="flex-1"><input type="hidden" name="accept_request_id" value="<?php echo $istek['IstekID']; ?>"><button class="w-full bg-green-600 text-white rounded py-1.5 text-xs font-bold hover:bg-green-500 transition">Kabul</button></form>
                                <form action="../service/friend_action.php" method="POST" class="flex-1"><input type="hidden" name="reject_request_id" value="<?php echo $istek['IstekID']; ?>"><button class="w-full bg-gray-600 text-red-400 border border-gray-500 rounded py-1.5 text-xs font-bold hover:bg-gray-500 transition">Red</button></form>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="bg-gray-800 p-5 rounded-xl border border-gray-700 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Günlük Görevler</h3>
                    <span class="text-xs text-gray-500"><i class="fa-regular fa-clock"></i> Yenilenir</span>
                </div>
                <div class="space-y-3">
                    <?php foreach($quests as $quest): ?>
                        <div class="bg-gray-700/30 p-3 rounded-lg border border-gray-700 flex justify-between items-center group hover:border-gray-600 transition">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gray-800 flex items-center justify-center text-primary border border-gray-700">
                                    <i class="fa-solid <?php echo $quest['Ikon']; ?> text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-gray-300"><?php echo $quest['Baslik']; ?></p>
                                    <p class="text-[10px] text-green-500 font-bold">+<?php echo $quest['OdulPara']; ?> Coin</p>
                                </div>
                            </div>
                            <?php if($quest['TamamlandiMi'] && !$quest['OdulAlindiMi']): ?>
                                <button onclick="claimReward(<?php echo $quest['KullaniciGorevID']; ?>, this)" class="bg-yellow-500 hover:bg-yellow-600 text-black text-[10px] font-bold px-3 py-1.5 rounded-md shadow-lg shadow-yellow-500/20 transition animate-pulse">ÖDÜLÜ AL</button>
                            <?php elseif($quest['OdulAlindiMi']): ?>
                                <span class="text-gray-500 text-xs font-bold flex items-center gap-1"><i class="fa-solid fa-check"></i> Alındı</span>
                            <?php else: ?>
                                <span class="text-gray-600 text-[10px] bg-gray-800 px-2 py-1 rounded border border-gray-700">Yapılıyor</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </aside>
    </div>

    <dialog id="habitModal" class="p-0 rounded-2xl shadow-2xl w-full max-w-md bg-gray-900 border border-gray-700 text-white backdrop:bg-black/80">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6 border-b border-gray-800 pb-4">
                <h3 class="text-lg font-bold text-primary">YENİ GÖREV</h3>
                <button onclick="document.getElementById('habitModal').close()" class="text-gray-500 hover:text-white transition"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            <form method="POST" action="">
                <div class="space-y-5">
                    <div><label class="block text-xs font-bold text-gray-500 uppercase mb-2">Görev Adı</label><input type="text" name="habit_name" required placeholder="Örn: 30 Sayfa Kitap Oku" class="w-full bg-gray-950 border border-gray-700 text-white rounded-lg px-4 py-3 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary placeholder-gray-700 transition"></div>
                    <div><label class="block text-xs font-bold text-gray-500 uppercase mb-3">Geliştireceği Yetenek</label><div class="grid grid-cols-2 gap-3"><?php $statList = ['Zeka'=>'book', 'Guc'=>'dumbbell', 'Sosyallik'=>'comments', 'Disiplin'=>'bed', 'Yaraticilik'=>'paintbrush']; $first=true; foreach($statList as $s => $icon): ?><label class="cursor-pointer group"><input type="radio" name="habit_stat" value="<?php echo $s; ?>" class="peer sr-only" <?php echo $first?'checked':''; $first=false; ?>><div class="p-3 border border-gray-700 rounded-lg text-center text-xs font-semibold text-gray-400 bg-gray-800 peer-checked:bg-primary peer-checked:text-white peer-checked:border-primary group-hover:border-gray-500 transition"><i class="fa-solid fa-<?php echo $icon; ?> mb-2 block text-sm"></i> <?php echo $s; ?></div></label><?php endforeach; ?></div></div>
                    <button type="submit" name="add_habit_btn" class="w-full bg-white text-black hover:bg-primary hover:text-white font-black py-3 rounded-xl shadow-lg transition mt-2">OLUŞTUR</button>
                </div>
            </form>
        </div>
    </dialog>

    <script>
        function openTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            const btns = document.querySelectorAll('.tab-btn');
            if(tabName === 'topluluk') btns[0].classList.add('active');
            if(tabName === 'arkadaslar') btns[1].classList.add('active');
            if(tabName === 'istekler') btns[2].classList.add('active');
        }
        function toggleStatsPanel() { document.getElementById('statsPanel').classList.toggle('hidden'); }
        Chart.defaults.color = '#9CA3AF'; Chart.defaults.borderColor = '#374151';
        const ctx = document.getElementById('statRadar').getContext('2d');
        new Chart(ctx, { type: 'radar', data: { labels: <?php echo $jsonLabels; ?>, datasets: [{ label: 'Seviye', data: <?php echo $jsonLevels; ?>, fill: true, backgroundColor: 'rgba(249, 115, 22, 0.2)', borderColor: '#F97316', pointBackgroundColor: '#F97316', pointBorderColor: '#fff' }] }, options: { scales: { r: { angleLines: {display: true, color: '#374151'}, grid: { color: '#374151' }, suggestedMin: 0, suggestedMax: 10, ticks: {display: false, backdropColor: 'transparent'} } }, plugins: { legend: {display: false} } } });

        function toggleHabit(ka_id, checkbox) {
            const isChecked = checkbox.checked;
            fetch('ajax_islem.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `ka_id=${ka_id}&durum=${isChecked ? 1 : 0}` })
            .then(res => res.json()).then(data => {
                if(!data.success) { alert("Hata: " + data.message); checkbox.checked = !isChecked; } 
                else {
                    const bar = document.getElementById(`stat-bar-${data.stat_type}`); const text = document.getElementById(`stat-text-${data.stat_type}`);
                    if(bar && text) { bar.style.width = `${data.stat_data.percent}%`; text.innerHTML = `Lvl ${data.stat_data.level} <span class="text-gray-500">|</span> ${data.stat_data.currentXP}/${data.stat_data.neededXP}`; }
                    const navLvl = document.getElementById('navbar-level'); const sideLvl = document.getElementById('sidebar-level');
                    if(navLvl) navLvl.innerText = `Lvl ${data.global_level}`; if(sideLvl) sideLvl.innerText = data.global_level;
                    const streakSpan = document.getElementById(`streak-count-${ka_id}`);
                    if(streakSpan) { if(data.new_streak > 0) { streakSpan.innerHTML = `<i class="fa-solid fa-fire animate-pulse"></i> ${data.new_streak} Gün`; } else { streakSpan.innerHTML = ''; } }
                }
            });
        }

        function deleteHabit(ka_id, event) {
            event.stopPropagation();
            if(!confirm("Bu görevi silmek istediğine emin misin?")) return;
            fetch('ajax_islem.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `ka_id=${ka_id}&is_delete=1` })
            .then(res => res.json()).then(data => {
                if(data.success) { const card = document.getElementById(`habit-card-${ka_id}`); card.style.opacity = '0'; card.style.transform = 'scale(0.95)'; setTimeout(() => { card.remove(); }, 300); } 
                else { alert("Silinemedi: " + data.message); }
            });
        }

        function claimReward(questId, btnElement) {
            fetch('ajax_islem.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `claim_quest_id=${questId}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    document.getElementById('user-balance').innerText = new Intl.NumberFormat().format(data.new_balance);
                    const parent = btnElement.parentElement;
                    btnElement.remove();
                    parent.innerHTML += '<span class="text-gray-500 text-xs font-bold flex items-center gap-1"><i class="fa-solid fa-check"></i> Alındı</span>';
                } else {
                    alert(data.message);
                }
            })
            .catch(err => console.error(err));
        }
    </script>
</body>
</html>