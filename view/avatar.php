<?php
// view/avatar.php
session_start();
// Hataları gizle (JSON bozulmasın diye)
ini_set('display_errors', 0); 
require_once __DIR__ . '/../service/auth.php'; 

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$kullaniciID = $_SESSION['user_id'];
$conn = get_db_connection();

// 1. Renkleri Çek
$userQuery = $conn->query("SELECT AvatarVerisi FROM Kullanicilar WHERE KullaniciID = $kullaniciID");
$user = $userQuery->fetch_assoc();

$avatarData = [
    'hairColor' => '#2D3436',
    'skinColor' => '#F3D2C1',
    'eyeColor' => '#000000',
    'topColor' => '#6C5CE7'
];

if ($user && !empty($user['AvatarVerisi'])) {
    $decoded = json_decode($user['AvatarVerisi'], true);
    if (is_array($decoded)) {
        $avatarData = array_merge($avatarData, $decoded);
    }
}

// 2. Envanteri Çek
$sql = "SELECT KE.EnvanterID, KE.KusandiMi, M.EsyaID, M.Ad, M.GorselURL, M.EsyaTuru 
        FROM KullaniciEnvanteri KE
        JOIN MarketEsyalari M ON KE.EsyaID = M.EsyaID
        WHERE KE.KullaniciID = $kullaniciID";
$envanter = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
$kusanilanlar = array_filter($envanter, function($item) { return $item['KusandiMi'] == 1; });
?>

<!DOCTYPE html>
<html lang="tr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stüdyo - Charmoji</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: { primary: '#F97316', darkBg: '#0f172a', cardBg: '#1e293b' }
                }
            }
        }
    </script>
    <style>
        body { background-color: #0f172a; color: #F8FAFC; }
        /* Renk seçiciyi daire yapma */
        .color-circle { 
            -webkit-appearance: none; border: none; 
            width: 48px; height: 48px; 
            border-radius: 50%; cursor: pointer; 
            padding: 0; overflow: hidden;
            box-shadow: 0 0 0 2px #334155;
            transition: all 0.2s;
        }
        .color-circle:hover { transform: scale(1.1); box-shadow: 0 0 0 3px #F97316; }
        .color-circle::-webkit-color-swatch-wrapper { padding: 0; }
        .color-circle::-webkit-color-swatch { border: none; }
    </style>
</head>
<body class="antialiased h-screen flex flex-col overflow-hidden">

    <header class="bg-slate-900/80 backdrop-blur border-b border-slate-800 h-16 flex items-center justify-between px-6 shrink-0 z-10">
        <a href="userpage.php" class="flex items-center gap-2 text-slate-400 hover:text-white transition group">
            <div class="w-8 h-8 rounded-full border border-slate-700 flex items-center justify-center group-hover:border-primary group-hover:text-primary bg-slate-800">
                <i class="fa-solid fa-arrow-left text-sm"></i>
            </div>
            <span class="font-bold text-sm tracking-wide">GERİ DÖN</span>
        </a>
        <div class="font-black text-xl tracking-[0.2em] text-white">
            <span class="text-primary">AVATAR</span> STÜDYOSU
        </div>
        <button onclick="saveColors()" class="bg-primary hover:bg-orange-600 text-white px-5 py-2 rounded-lg text-sm font-bold shadow-lg shadow-orange-900/20 transition flex items-center gap-2">
            <i class="fa-solid fa-floppy-disk"></i> KAYDET
        </button>
    </header>

    <div class="flex-1 flex flex-col lg:flex-row overflow-hidden">
        
        <div class="lg:w-1/2 h-1/2 lg:h-full bg-slate-900 flex flex-col items-center justify-center relative border-r border-slate-800">
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-5"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 transform scale-125 lg:scale-150 transition-transform duration-500">
                <svg id="mainSVG" width="260" height="280" viewBox="0 0 260 280" version="1.1" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%">
                            <feDropShadow dx="0" dy="10" stdDeviation="10" flood-color="#000" flood-opacity="0.3"/>
                        </filter>
                    </defs>
                    
                    <g filter="url(#shadow)">
                        <g id="Body" transform="translate(30.000000, 180.000000)">
                            <path d="M100,0 C138.659932,0 170,31.3400675 170,70 L170,100 L30,100 L30,70 C30,31.3400675 61.3400675,0 100,0 Z" id="Shirt" fill="<?php echo $avatarData['topColor']; ?>"></path>
                            <path d="M100,0 C80,0 70,-20 70,-30 L130,-30 C130,-20 120,0 100,0 Z" id="Neck" fill="<?php echo $avatarData['skinColor']; ?>"></path>
                        </g>

                        <g id="Head" transform="translate(48.000000, 30.000000)">
                            <path d="M82,158 C36.7126515,158 0,122.604218 0,79 C0,35.3957816 36.7126515,0 82,0 C127.287349,0 164,35.3957816 164,79 C164,122.604218 127.287349,158 82,158 Z" id="Skin" fill="<?php echo $avatarData['skinColor']; ?>"></path>
                            <path d="M82,-10 C30,-10 10,20 10,60 C10,65 12,70 12,70 C12,70 20,40 40,40 C60,40 82,60 82,60 C82,60 104,40 124,40 C144,40 152,70 152,70 C152,70 154,65 154,60 C154,20 134,-10 82,-10 Z" id="Hair" fill="<?php echo $avatarData['hairColor']; ?>"></path>

                            <g id="Face" transform="translate(36.000000, 50.000000)">
                                <circle id="EyeLeft" cx="18" cy="28" r="6" fill="<?php echo $avatarData['eyeColor']; ?>"></circle>
                                <circle id="EyeRight" cx="74" cy="28" r="6" fill="<?php echo $avatarData['eyeColor']; ?>"></circle>
                                <path d="M10,18 Q20,10 30,18" stroke="<?php echo $avatarData['hairColor']; ?>" stroke-width="3" fill="none" opacity="0.6"/>
                                <path d="M62,18 Q72,10 82,18" stroke="<?php echo $avatarData['hairColor']; ?>" stroke-width="3" fill="none" opacity="0.6"/>
                                <path d="M26,55 Q46,70 66,55" id="Mouth" stroke="#000000" stroke-width="3" stroke-linecap="round" fill="none" opacity="0.7"></path>
                                <path d="M46,35 Q38,45 46,50" fill="none" stroke="#000000" stroke-width="2" opacity="0.1"></path>
                            </g>
                        </g>

                        <g id="hatGroup" transform="translate(130, 45)"></g> 
                        <g id="glassesGroup" transform="translate(130, 110)"></g> 
                        <g id="neckGroup" transform="translate(130, 190)"></g> 
                    </g>
                </svg>
            </div>
        </div>

        <div class="lg:w-1/2 h-1/2 lg:h-full bg-slate-950 flex flex-col">
            
            <div class="flex border-b border-slate-800">
                <button onclick="switchTab('colors')" id="btn-colors" class="flex-1 py-4 font-bold text-sm tracking-widest transition border-b-2 border-primary text-white bg-slate-900">
                    GÖRÜNÜM
                </button>
                <button onclick="switchTab('items')" id="btn-items" class="flex-1 py-4 font-bold text-sm tracking-widest transition border-b-2 border-transparent text-slate-500 hover:text-slate-300">
                    ENVANTER
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                
                <div id="tab-colors" class="space-y-8 animate-fade-in">
                    
                    <div class="space-y-3">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest">Ten Rengi</label>
                        <div class="flex gap-4 items-center bg-slate-900 p-4 rounded-xl border border-slate-800">
                            <input type="color" id="skinColor" class="color-circle" value="<?php echo $avatarData['skinColor']; ?>" oninput="updateFill(['Skin', 'Neck'], this.value)">
                            <span class="text-slate-400 text-sm font-mono" id="hex-skin"><?php echo $avatarData['skinColor']; ?></span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest">Saç Rengi</label>
                        <div class="flex gap-4 items-center bg-slate-900 p-4 rounded-xl border border-slate-800">
                            <input type="color" id="hairColor" class="color-circle" value="<?php echo $avatarData['hairColor']; ?>" oninput="updateFill(['Hair'], this.value)">
                            <span class="text-slate-400 text-sm font-mono" id="hex-hair"><?php echo $avatarData['hairColor']; ?></span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest">Kıyafet Rengi</label>
                        <div class="flex gap-4 items-center bg-slate-900 p-4 rounded-xl border border-slate-800">
                            <input type="color" id="topColor" class="color-circle" value="<?php echo $avatarData['topColor']; ?>" oninput="updateFill(['Shirt'], this.value)">
                            <span class="text-slate-400 text-sm font-mono" id="hex-top"><?php echo $avatarData['topColor']; ?></span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest">Göz Rengi</label>
                        <div class="flex gap-4 items-center bg-slate-900 p-4 rounded-xl border border-slate-800">
                            <input type="color" id="eyeColor" class="color-circle" value="<?php echo $avatarData['eyeColor']; ?>" oninput="updateFill(['EyeLeft', 'EyeRight'], this.value)">
                            <span class="text-slate-400 text-sm font-mono" id="hex-eye"><?php echo $avatarData['eyeColor']; ?></span>
                        </div>
                    </div>

                </div>

                <div id="tab-items" class="hidden grid grid-cols-2 sm:grid-cols-3 gap-4 animate-fade-in">
                    <?php if(empty($envanter)): ?>
                        <div class="col-span-full text-center py-12 flex flex-col items-center justify-center opacity-50">
                            <i class="fa-solid fa-box-open text-4xl mb-3 text-slate-600"></i>
                            <p class="text-slate-500 text-sm">Envanter boş.</p>
                            <a href="market.php" class="text-primary text-xs mt-2 font-bold hover:underline">MARKETE GİT</a>
                        </div>
                    <?php else: ?>
                        <?php foreach($envanter as $item): ?>
                            <div onclick="equipItem(<?php echo $item['EsyaID']; ?>, '<?php echo $item['Ad']; ?>', '<?php echo $item['EsyaTuru']; ?>', this)" 
                                 class="item-card cursor-pointer bg-slate-900 border-2 <?php echo $item['KusandiMi'] ? 'border-primary shadow-lg shadow-orange-900/20' : 'border-slate-800 hover:border-slate-600'; ?> rounded-xl p-4 flex flex-col items-center transition relative overflow-hidden group">
                                
                                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition"></div>
                                
                                <i class="fa-solid <?php echo $item['GorselURL']; ?> text-3xl mb-3 text-slate-400 group-hover:text-white <?php echo $item['KusandiMi'] ? 'text-primary' : ''; ?> transition"></i>
                                <span class="text-[10px] font-bold text-center text-slate-300 uppercase tracking-wide relative z-10"><?php echo htmlspecialchars($item['Ad']); ?></span>
                                
                                <?php if($item['KusandiMi']): ?>
                                    <div class="absolute top-2 right-2 w-2 h-2 bg-primary rounded-full animate-pulse"></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <script>
        // --- RENK GÜNCELLEME ---
        function updateFill(ids, color) {
            if(!Array.isArray(ids)) ids = [ids];
            ids.forEach(id => {
                const el = document.getElementById(id);
                if(el) el.setAttribute('fill', color);
            });
        }

        // --- KAYDETME ---
        function saveColors() {
            const colors = {
                skinColor: document.getElementById('skinColor').value,
                hairColor: document.getElementById('hairColor').value,
                eyeColor: document.getElementById('eyeColor').value,
                topColor: document.getElementById('topColor').value
            };

            fetch('ajax_islem.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `save_avatar_colors=1&colors=${JSON.stringify(colors)}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) alert("Görünüm Kaydedildi!");
            });
        }

        // --- TAB GEÇİŞLERİ ---
        function switchTab(tab) {
            document.getElementById('tab-colors').classList.add('hidden');
            document.getElementById('tab-items').classList.add('hidden');
            
            document.getElementById('btn-colors').className = "flex-1 py-4 font-bold text-sm tracking-widest transition border-b-2 border-transparent text-slate-500 hover:text-slate-300";
            document.getElementById('btn-items').className = "flex-1 py-4 font-bold text-sm tracking-widest transition border-b-2 border-transparent text-slate-500 hover:text-slate-300";

            document.getElementById('tab-' + tab).classList.remove('hidden');
            
            const activeBtn = document.getElementById('btn-' + tab);
            activeBtn.className = "flex-1 py-4 font-bold text-sm tracking-widest transition border-b-2 border-primary text-white bg-slate-900";
        }

        // --- KRİTİK KISIM: EŞYA ÇİZİM KOORDİNATLARI ---
        function renderAccessory(itemName, itemType) {
            let path = "";
            let targetGroup = "";
            
            // Koordinatlar (translate) SVG içindeki grup konumlarına göredir.
            // HatGroup: (130, 45) -> Kafanın tepesi
            // GlassesGroup: (130, 110) -> Göz hizası
            
            if (itemType === 'Şapka') {
                targetGroup = 'hatGroup';
                if (itemName === 'Büyücü Şapkası') {
                    // Mor Külah ve Geniş Kenar
                    path = `
                        <ellipse cx="0" cy="20" rx="70" ry="15" fill="#4C1D95" /> <path d="M-40,20 Q0,-100 40,20" fill="#6D28D9" /> <path d="M-40,20 Q0,35 40,20" fill="none" stroke="#4C1D95" stroke-width="2" /> `;
                } else if (itemName === 'Kral Tacı') {
                    // Altın Taç
                    path = `
                        <path d="M-35,25 L-35,-10 L-15,10 L0,-25 L15,10 L35,-10 L35,25 Z" fill="#FACC15" stroke="#B45309" stroke-width="3" stroke-linejoin="round"/>
                        <circle cx="0" cy="20" r="3" fill="#B45309" />
                    `;
                }
            } 
            else if (itemType === 'Gözlük') {
                targetGroup = 'glassesGroup';
                if (itemName === 'Güneş Gözlüğü') {
                    // Siyah Havalı Gözlük
                    path = `
                        <g fill="#111827">
                            <rect x="-55" y="-15" width="45" height="25" rx="5" /> <rect x="10" y="-15" width="45" height="25" rx="5" /> <rect x="-10" y="-10" width="20" height="4" /> </g>
                    `;
                }
            } 
            else if (itemType === 'Maske') {
                targetGroup = 'neckGroup'; // Maskeyi boyun/yüz altına çiziyoruz
                if (itemName === 'Ninja Maskesi') {
                    // Ağız kapatan maske (Koordinatları manuel ayarlıyoruz çünkü neckGroup aşağıda)
                    // Yüz hizasına yukarı taşıyoruz (dy="-60")
                    path = `
                        <path d="M-45,-120 Q0,-90 45,-120 L45,-90 Q0,-60 -45,-90 Z" fill="#1F2937" />
                        <rect x="-46" y="-120" width="92" height="40" rx="10" fill="#1F2937" />
                    `;
                }
            }
            else if (itemType === 'Kolye') {
                targetGroup = 'neckGroup';
                if (itemName === 'Altın Kolye') {
                    path = `
                        <path d="M-30,10 Q0,50 30,10" fill="none" stroke="#FACC15" stroke-width="4" />
                        <circle cx="0" cy="30" r="8" fill="#FACC15" stroke="#B45309" stroke-width="2" />
                    `;
                }
            }

            // Çizimi yap
            const groupEl = document.getElementById(targetGroup);
            if(groupEl) {
                // Önce o gruptaki eski eşyayı temizle (Örn: Eski şapkayı sil)
                groupEl.innerHTML = path;
            }
        }

        // --- EŞYA KUŞANMA (AJAX) ---
        function equipItem(esyaId, itemName, itemType, cardElement) {
            // Görsel seçim çerçevesi
            document.querySelectorAll('.item-card').forEach(el => {
                el.classList.remove('border-primary', 'shadow-lg');
                el.classList.add('border-slate-800');
                // Kart içindeki "Takılı" yazısını temizlemek (opsiyonel, sayfa yenileyince gelir)
            });
            cardElement.classList.remove('border-slate-800');
            cardElement.classList.add('border-primary', 'shadow-lg');

            // 1. Ekrana Çiz
            renderAccessory(itemName, itemType);

            // 2. Veritabanına Kaydet
            fetch('ajax_islem.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `equip_item_id=${esyaId}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    console.log("Eşya veritabanına kaydedildi.");
                } else {
                    console.error("Hata:", data.message);
                }
            });
        }

        // --- SAYFA YÜKLENİNCE ÇALIŞIR ---
        // Veritabanında "KusandiMi = 1" olanları otomatik çizer
        window.addEventListener('load', function() {
            console.log("Avatar yükleniyor...");
            <?php foreach($kusanilanlar as $kusanilan): ?>
                console.log("Takılıyor: <?php echo $kusanilan['Ad']; ?>");
                renderAccessory("<?php echo $kusanilan['Ad']; ?>", "<?php echo $kusanilan['EsyaTuru']; ?>");
            <?php endforeach; ?>
        });
    </script>
</body>
</html>