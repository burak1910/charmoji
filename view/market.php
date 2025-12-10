<?php
// view/market.php
session_start();
require_once __DIR__ . '/../service/auth.php'; 

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$kullaniciID = $_SESSION['user_id'];
$conn = get_db_connection();

// --- KULLANICI BİLGİLERİNİ VE LEVELINI HESAPLA ---
// Market'te level sınırı olduğu için önce kullanıcının levelını bulmamız lazım.
function hesaplaLevel($toplamPuan) {
    $level = 1;
    $gerekliXP = 100;
    while ($toplamPuan >= $gerekliXP) {
        $toplamPuan -= $gerekliXP;
        $level++;
        $gerekliXP *= 2;
    }
    return $level;
}

// Toplam XP'yi çek
$statsSql = $conn->query("SELECT SUM(ToplamPuan) as ToplamXP FROM KullaniciStatlari WHERE KullaniciID = $kullaniciID");
$toplamXP = $statsSql->fetch_assoc()['ToplamXP'] ?? 0;
// Stat sayısına bölerek ortalama levelı bul (Userpage ile aynı mantık)
$statSayisi = 5; // Zeka, Güç vb.
$ortalamaLevel = hesaplaLevel(floor($toplamXP / $statSayisi));

// Bakiyeyi Çek
$user = $conn->query("SELECT Bakiye FROM Kullanicilar WHERE KullaniciID = $kullaniciID")->fetch_assoc();
$bakiye = $user['Bakiye'];

// --- ÜRÜNLERİ ÇEK (YENİ SÜTUN İSİMLERİYLE) ---
$sql = "SELECT M.*, 
       (SELECT COUNT(*) FROM KullaniciEnvanteri E WHERE E.EsyaID = M.EsyaID AND E.KullaniciID = $kullaniciID) as SahipMi
       FROM MarketEsyalari M ORDER BY M.Fiyat ASC";
$urunler = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Charmoji - Market</title>
    <link rel="icon" type="image/png" href="../charmoji.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: { primary: '#F97316', darkBg: '#111827', cardBg: '#1F2937' }
                }
            }
        }
    </script>
    <style>
        body { background-color: #111827; color: #F3F4F6; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #111827; }
        ::-webkit-scrollbar-thumb { background: #374151; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #F97316; }
    </style>
</head>
<body class="antialiased min-h-screen">

    <nav class="fixed w-full z-50 bg-gray-900/90 backdrop-blur-md border-b border-gray-700 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 relative">
                
                <div class="flex-shrink-0 flex items-center gap-2 z-10">
                    <a href="userpage.php" class="text-gray-400 hover:text-white transition flex items-center gap-2 group">
                        <div class="w-8 h-8 rounded-full border border-gray-600 flex items-center justify-center group-hover:border-primary group-hover:text-primary transition">
                            <i class="fa-solid fa-arrow-left"></i>
                        </div>
                        <span class="text-sm font-bold hidden sm:block">Geri Dön</span>
                    </a>
                </div>
                
                <div class="absolute left-1/2 transform -translate-x-1/2 z-10 pointer-events-none">
                    <div class="relative px-6 py-2">
                        <div class="absolute inset-0 bg-primary/20 rounded-lg blur-xl opacity-50"></div>
                        <span class="relative font-black text-2xl text-white tracking-[0.2em] text-shadow">
                            MARKET
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-4 z-10">
                    <div class="flex items-center gap-2 bg-gray-950 px-4 py-2 rounded-lg border border-yellow-600/40 text-yellow-500 font-bold text-lg shadow-inner shadow-black/50">
                        <i class="fa-solid fa-coins text-yellow-400 animate-pulse"></i> 
                        <span id="user-balance" class="text-gray-100 tracking-wide"><?php echo number_format($bakiye, 0); ?></span>
                    </div>
                </div>

            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-28 pb-12">
        
        <?php if(empty($urunler)): ?>
            <div class="text-center py-20">
                <i class="fa-solid fa-box-open text-gray-700 text-6xl mb-4"></i>
                <h2 class="text-xl text-gray-500">Market şu an boş.</h2>
                <p class="text-sm text-gray-600">Veritabanında ürün yok.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach($urunler as $urun): 
                    // Level Kontrolü
                    $yeterliLevel = $ortalamaLevel >= $urun['GerekenSeviye'];
                ?>
                <div id="item-card-<?php echo $urun['EsyaID']; ?>" class="bg-gray-800 rounded-2xl border border-gray-700 overflow-hidden hover:border-primary/50 transition-all hover:shadow-2xl hover:shadow-orange-900/20 group relative flex flex-col">
                    
                    <div class="absolute top-3 left-3 z-10 flex gap-2">
                        <span class="bg-black/60 backdrop-blur text-[10px] font-bold px-2 py-1 rounded text-gray-300 border border-gray-600 uppercase tracking-wide">
                            <?php echo htmlspecialchars($urun['EsyaTuru']); ?>
                        </span>
                        
                        <span class="text-[10px] font-bold px-2 py-1 rounded border uppercase tracking-wide <?php echo $yeterliLevel ? 'bg-green-900/60 text-green-400 border-green-700' : 'bg-red-900/60 text-red-400 border-red-700'; ?>">
                            Lvl <?php echo $urun['GerekenSeviye']; ?>
                        </span>
                    </div>

                    <div class="h-48 bg-gradient-to-b from-gray-800 to-gray-900 flex items-center justify-center relative overflow-hidden">
                        <div class="absolute inset-0 bg-primary/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <i class="fa-solid <?php echo htmlspecialchars($urun['GorselURL']); ?> text-7xl text-gray-500 group-hover:text-primary group-hover:scale-110 transition-all duration-300 drop-shadow-2xl <?php echo !$yeterliLevel ? 'opacity-50' : ''; ?>"></i>
                        
                        <?php if(!$yeterliLevel): ?>
                            <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                                <i class="fa-solid fa-lock text-4xl text-gray-400"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="p-5 flex flex-col flex-1">
                        <h3 class="text-lg font-black text-white mb-1 group-hover:text-primary transition-colors"><?php echo htmlspecialchars($urun['Ad']); ?></h3>
                        <p class="text-xs text-gray-400 mb-6 line-clamp-2 h-8"><?php echo htmlspecialchars($urun['Aciklama']); ?></p>
                        
                        <div class="mt-auto flex items-center justify-between">
                            <span class="text-yellow-500 font-bold flex items-center gap-1.5 text-lg">
                                <i class="fa-solid fa-coins text-sm"></i> <?php echo number_format($urun['Fiyat'], 0); ?>
                            </span>

                            <?php if($urun['SahipMi'] > 0): ?>
                                <button disabled class="bg-gray-700/50 text-gray-500 border border-gray-600 px-4 py-2 rounded-lg text-xs font-bold cursor-not-allowed w-28 uppercase tracking-wide">
                                    Envanterde
                                </button>
                            <?php elseif(!$yeterliLevel): ?>
                                <button disabled class="bg-gray-800 text-gray-500 border border-gray-600 px-4 py-2 rounded-lg text-xs font-bold cursor-not-allowed w-28 uppercase tracking-wide">
                                    Lvl <?php echo $urun['GerekenSeviye']; ?>+
                                </button>
                            <?php else: ?>
                                <button onclick="buyItem(<?php echo $urun['EsyaID']; ?>, <?php echo $urun['Fiyat']; ?>, this)" 
                                        class="bg-white text-black hover:bg-primary hover:text-white px-4 py-2 rounded-lg text-xs font-black transition w-28 shadow-lg uppercase tracking-wide transform active:scale-95">
                                    SATIN AL
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function buyItem(itemId, price, btn) {
            const currentBalance = parseInt(document.getElementById('user-balance').innerText.replace(/,/g, ''));
            
            if(currentBalance < price) {
                alert("Yetersiz bakiye! Biraz daha görev yapmalısın.");
                return;
            }

            if(!confirm("Bu eşyayı satın almak istiyor musun?")) return;

            const originalText = btn.innerHTML;
            const originalClass = btn.className;
            
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';
            btn.className = "bg-gray-600 text-white px-4 py-2 rounded-lg text-xs font-bold w-28 cursor-wait";
            btn.disabled = true;

            fetch('ajax_islem.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `buy_item_id=${itemId}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    document.getElementById('user-balance').innerText = new Intl.NumberFormat().format(data.new_balance);
                    btn.className = "bg-gray-700/50 text-gray-500 border border-gray-600 px-4 py-2 rounded-lg text-xs font-bold cursor-not-allowed w-28 uppercase tracking-wide";
                    btn.innerHTML = 'Envanterde';
                } else {
                    alert("Hata: " + data.message);
                    btn.innerHTML = originalText;
                    btn.className = originalClass;
                    btn.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                btn.innerHTML = originalText;
                btn.className = originalClass;
                btn.disabled = false;
            });
        }
    </script>
</body>
</html>