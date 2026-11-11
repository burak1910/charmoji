# 01 · Ekran envanteri

Kimlik önekleri: **E** = ekran / alt ekran, **M** = modal veya onay alt sayfası, **K** = kutlama / geri bildirim anı, **G** = ortak bileşen (tanımı `00-genel.md` §3.3).

"Bugünkü karşılığı" sütunu, ekranın bugün hangi dosyada (kısmen) var olduğunu gösterir; "yeni" = bugün hiç yok.

## 1. Ekranlar ve modallar

| Kimlik | Ad | Tür | Amaç | Giriş noktaları | Bugünkü karşılığı | Ayrıntı |
|---|---|---|---|---|---|---|
| E00 | Tanıtım | Ekran | Oturum açmamış ziyaretçiye oyunu anlatıp kayda yönlendirmek | Kök adres, çıkış sonrası | `view/index.php` | 07 §1 |
| E01 | Giriş | Ekran | Var olan oyuncunun oturum açması | E00 "Giriş yap", E02 alt bağlantı, oturum gerektiren her sayfadan yönlendirme, G08 | `service/login.php` (+ işlem `service/auth.php`) | 07 §2 |
| E02 | Kayıt | Ekran | Yeni hesap ve kahraman adı oluşturmak | E00 "Maceraya başla", E01 alt bağlantı | `service/register.php` (+ `service/auth.php`) | 07 §3 |
| E03a | Karşılama 1: Karakterini oluştur | Ekran (adım) | İlk görünümü seçtirerek karakterle bağ kurmak | Kayıttan hemen sonra (bir kez) | yeni | 07 §4 |
| E03b | Karşılama 2: İlk görevin | Ekran (adım) | İlk görevi oluşturtmak | E03a "Devam" | yeni (form mantığı `view/userpage.php` görev ekle modalında var) | 07 §4 |
| E03c | Karşılama 3: Nasıl oynanır | Ekran (adım) | XP, seri bonusu, sandık kurallarını üç maddede anlatmak | E03b "Görevi ekle" / "Atla" | yeni | 07 §4 |
| E10 | Karargah | Sekme | Karakteri merkeze koyup günün durumunu ve sıradaki görevleri göstermek; hızlı tamamlama | Alt menü, giriş sonrası açılış, E03c | `view/userpage.php` (karakter kartı + görev panosu, kısmen) | 02 |
| E20 | Görevler | Sekme | Bugünkü tüm görevleri tamamlama/geri alma, görev yönetimi, günlük meydan okumalar | Alt menü, E10 "Tüm görevler", E10 sandık kartı | `view/userpage.php` (Görev Panosu + Günlük Görevler kutusu) | 03 |
| E21 | Görev formu (ekle / düzenle) | Alt ekran (tam boy alt sayfa) | Görev adı ve yeteneğini belirlemek, düzenlemek, silmeye geçmek | E20 "Görev ekle", E20 kart menüsü "Düzenle", E10 boş durum, E50 yetenek kartı "Görev ekle" | `view/userpage.php` `#habitModal` (yalnızca ekleme) | 03 §5 |
| E30 | Market | Sekme | Eşyaları nadirlik, fiyat, seviye kilidiyle sergilemek | Alt menü, G02 altın, E52 boş durum, K07 | `view/market.php` | 04 |
| E31 | Eşya detayı ve deneme | Alt ekran (alt sayfa) | Eşyayı karakter üzerinde denemek; satın almak / kuşanmak | E30 eşya kartı | yeni | 04 §5 |
| E40 | Lonca | Sekme (4 iç sekme) | Arkadaşlarla haftalık yarış ve arkadaş yönetimi | Alt menü | `view/userpage.php` sağ sütun (Topluluk / Arkadaşlar / İstekler) | 05 |
| E40a | Lonca › Sıralama | İç sekme | Haftalık XP liderlik tablosu | E40 varsayılan iç sekme | yeni | 05 §3 |
| E40b | Lonca › Arkadaşlar | İç sekme | Arkadaş listesi | E40 iç sekme | `view/userpage.php` "Arkadaşlar" | 05 §4 |
| E40c | Lonca › İstekler | İç sekme | Gelen istekleri kabul/red, gönderilenleri görmek | E40 iç sekme, alt menü Lonca rozeti | `view/userpage.php` "İstekler" | 05 §5 |
| E40d | Lonca › Maceracı Bul | İç sekme | Kahraman adıyla arama ve öneriler; istek gönderme | E40 iç sekme, E40a/E40b boş durum | `view/userpage.php` "Topluluk" (+ `FriendManager::searchUsers`, arayüzü yok) | 05 §6 |
| E44 | Arkadaş profili | Alt ekran | Arkadaşın karakterini, seviyesini, haftalık XP'sini görmek; arkadaşlıktan çıkarmak | E40a satırı, E40b satırı | yeni | 05 §7 |
| E50 | Karakter | Sekme | Karakter, kuşanılan eşyalar, beş yetenek, hesap | Alt menü, G02 seviye rozeti | `view/userpage.php` sol sütun + `view/avatar.php` | 06 |
| E51 | Görünüm | Alt ekran | Ten, saç, göz, kıyafet rengini değiştirmek | E50 "Görünüm" | `view/avatar.php` "Görünüm" sekmesi | 06 §5 |
| E52 | Çanta | Alt ekran | Sahip olunan eşyaları kuşanmak / çıkarmak | E50 "Çanta", E50 eşya yuvası, K07 bildirimindeki "Kuşan" | `view/avatar.php` "Envanter" sekmesi | 06 §6 |
| M01 | Günlük sandık | Modal | Sandığı açmak ve ödülü göstermek | E10 sandık kartı (hazırken), K05 bildirimi | yeni | 02 §6 |
| M02 | Seri bilgisi | Modal (alt sayfa) | Seri ve seri bonusu kuralını, görevin bir sonraki bonusunu göstermek | Görev kartındaki seri/bonus alanı (E10, E20) | yeni | 03 §7 |
| M03 | Görev silme onayı | Onay alt sayfası (G07) | Silmeyi doğrulamak | E21 "Görevi sil", E20 kart menüsü "Sil" | `confirm()` (`view/userpage.php`) | 03 §6 |
| M04 | Geri alma onayı | Onay alt sayfası (G07) | Tamamlanmış görevi geri almayı doğrulamak | E20'de tamamlanmış görev kartına dokunma | yeni | 03 §8 |
| M05 | Satın alma onayı | Onay alt sayfası (G07) | Satın almayı ve kalan altını göstermek | E31 "Satın al" | `confirm()` (`view/market.php`) | 04 §6 |
| M06 | Arkadaşlıktan çıkarma onayı | Onay alt sayfası (G07) | Arkadaşlıktan çıkarmayı doğrulamak | E44 "Arkadaşlıktan çıkar" | yeni (`FriendManager::removeFriend` var, arayüzü yok) | 05 §7 |
| M07 | Kaydedilmemiş değişiklik | Onay alt sayfası (G07) | Kaydedilmemiş görünüm/görev formunu kapatırken uyarmak | E51 ve E21'den kaydetmeden çıkış | yeni | 06 §5, 03 §5 |
| M08 | Çıkış onayı | Onay alt sayfası (G07) | Oturumu kapatmayı doğrulamak | E50 "Çıkış yap" | yok (bugün doğrudan `service/logout.php` bağlantısı) | 06 §7 |
| K01 | Görev tamamlama | Geri bildirim anı | "+XP" uçar, bar dolar, seri artar | Görev tamamlama (E10, E20) | kısmen (`view/userpage.php` `toggleHabit`: yalnızca bar ve sayı güncellenir) | 03 §9 |
| K02 | Yetenek seviyesi atlama | Geri bildirim anı (bant) | Yetenek seviyesinin yükseldiğini kutlamak | K01 içinde, eşik aşılınca | yeni | 03 §9 |
| K03 | Karakter seviyesi atlama | Tam ekran kutlama modalı | Karakter seviyesinin yükseldiğini ve açılan eşyaları göstermek | K01/K02 sonrasında, karakter seviyesi artınca | yeni | 03 §9 |
| K04 | Seri bonusu yükseldi | Geri bildirim anı | 7/14/21/28. günde bonusun arttığını göstermek | K01 içinde | yeni | 03 §9 |
| K05 | Günün görevleri tamam, sandık hazır | Geri bildirim anı | Karakter zafer pozu + sandık hazır bildirimi | Günün son görevi tamamlanınca | yeni | 02 §5 |
| K06 | Sandık açılışı | Geri bildirim anı (M01 içinde) | Sandığın açılması ve altının sayılması | M01 "Sandığı aç" | yeni | 02 §6 |
| K07 | Satın alma başarısı | Geri bildirim anı | Altının azalması, eşyanın çantaya girmesi | M05 onayı sonrası | kısmen (`view/market.php`: düğme "Envanterde" olur) | 04 §7 |
| K08 | Meydan okuma ödülü alındı | Geri bildirim anı | Altının üst bara uçması ve sayacın artması | E20 "Ödülü al" | kısmen (`view/userpage.php` `claimReward`: sayı değişir) | 03 §10 |
| G01–G08 | Ortak bileşenler | Bileşen | Alt menü, üst bar, bildirim, bağlantı bandı, iskelet, düğme yükleme, onay alt sayfası, oturum bitti | Her yer | yok (bugün her sayfanın kendi gezinme çubuğu var) | 00 §3.3 |

Kapsamdan çıkan bugünkü öğeler: `view/userpage.php` içindeki "Yetenek Ağacı" radar grafiği (yerine yetenek çubukları; bkz. 06 §1), giriş/kayıt sayfalarındaki açık/koyu tema düğmesi (uygulama yalnızca koyu temadır), tanıtım sayfasındaki gerçeğe dayanmayan iddialar ("rozetler", "100+ / 50+ / %99" istatistikleri, "Geleceği Kodluyoruz" başlığı). Ayrıntı 07 §1.

## 2. Görsel varlık ihtiyacı

Tüm görseller piksel sanat tarzında, şeffaf zeminli SVG (tercih) ya da 2× PNG. Karakter ve eşya katmanları **aynı tuval** (260×280 birim, bugünkü `avatar_renderer.php` görünüm kutusu) üzerine hizalanır ki katmanlar üst üste bindirilebilsin. Boyutlar CSS piksel cinsinden yaklaşık gösterim boyutudur.

### 2.1 Karakter

| Ad | Kullanım yeri | Yaklaşık boyut | Not |
|---|---|---|---|
| `karakter-govde` (baş, boyun, gövde; ten / kıyafet renk bölgeleri ayrı katman) | E10, E31, E44, E50, E51, E52, E03a, K03, lonca satırları | 40 px (liste) · 96 px (profil başı) · 200–240 px (Karargah, Karakter) | Renk bölgeleri koddan boyanabilir olmalı (bugünkü `skinColor`, `topColor`) |
| `karakter-sac` | aynı | aynı | `hairColor` ile boyanır |
| `karakter-goz` | aynı | aynı | `eyeColor` ile boyanır |
| `karakter-ifade-hazir` (normal ağız/göz) | E10 ruh hali "hazır" | aynı | |
| `karakter-ifade-nese` (gülümseme) | E10 ruh hali "neşeli", K01 | aynı | |
| `karakter-ifade-zafer` (geniş gülümseme, kollar yukarı pozu) | E10 ruh hali "zafer", K05, K03 | aynı | Kol pozu gövde katmanının ikinci karesi |
| `karakter-ifade-uykulu` (yarı kapalı göz) | E10 ruh hali "uykulu" | aynı | |
| `karakter-ziplama` animasyon kareleri (3–4 kare) | K01 sevinç tepkisi | aynı | Hareket azaltmada kullanılmaz |
| `karakter-el-salla` animasyon kareleri (3–4 kare) | E10 karaktere dokunma tepkisi | aynı | |
| `karakter-golge` (zemin elipsi) | E10, E50 | karakter genişliğinin %60'ı | |

### 2.2 Eşyalar (her eşya için iki parça)

| Ad | Kullanım yeri | Yaklaşık boyut | Not |
|---|---|---|---|
| `esya-{kod}-ikon` | E30 kart, E31 başlık, E52 ızgara, K07, E50 eşya yuvası | 96 px (kart) · 64 px (çanta) · 40 px (yuva) | Bugün Font Awesome sınıfı (`GorselURL`) kullanılıyor; yerine gerçek ikon |
| `esya-{kod}-katman` | Karakter üzerinde (E10, E31 deneme, E44, E50, E52) | Karakter tuvali ile aynı | Tür başına bağlantı noktası: Şapka → baş üstü, Gözlük → göz hizası, Maske → yüz alt yarısı, Kolye → boyun |
| Bugünkü dört eşya: Büyücü Şapkası, Kral Tacı, Güneş Gözlüğü, Ninja Maskesi, Altın Kolye | | | Bugün `avatar_renderer.php` içinde elle yazılmış SVG; yeniden çizilmeli. Market'teki diğer eşyaların katman çizimi bugün yok |
| `nadirlik-cerceve-siradan` / `-nadir` / `-efsanevi` | E30, E31, E52 | 96 px ve 64 px kare | Efsanevi için 4 karelik parıltı animasyonu (hareket azaltmada ilk kare) |
| `yuva-bos-{tur}` (şapka, gözlük, maske, kolye silüetleri) | E50 eşya yuvaları, E52 filtre çipleri | 40 px | Soluk çizgi silüet |

### 2.3 Oyun nesneleri ve ikonlar

| Ad | Kullanım yeri | Yaklaşık boyut | Not |
|---|---|---|---|
| `sandik-kilitli` | E10 sandık kartı | 64 px | Asma kilitli |
| `sandik-hazir` (+ 4 karelik sallanma) | E10 sandık kartı, K05 | 64 px | Hareket azaltmada tek kare + parıltı yok |
| `sandik-acik` (+ 6 karelik açılma) | M01, K06 | 160 px | |
| `sandik-acildi` (boş açık sandık, soluk) | E10 sandık kartı (bugün açıldı) | 64 px | |
| `altin-ikon` | G02, E30, E31, M01, M05, meydan okuma kartları, K07, K08 | 16 px · 24 px · 32 px | |
| `altin-parcacik` (tek sikke) | K06, K08 uçan altın | 16 px | |
| `xp-ikon` | Görev kartı, K01 | 16 px | |
| `seri-alev` (seri ikonu; 3 boy: 1–6, 7–27, 28+ gün) | Görev kartı, M02, K04 | 16 px · 32 px | Büyüyen alev seri bonusunu görselleştirir |
| `yetenek-zeka` / `-guc` / `-sosyallik` / `-disiplin` / `-yaraticilik` | Görev kartı, E21, E10 mini barlar, E50 | 16 px · 24 px · 32 px | Kitap, dambıl, konuşma balonu, kum saati, fırça |
| `seviye-rozet` (kalkan biçimli çerçeve) | G02, E10, E50, E44, lonca satırları | 24 px · 48 px | İçine sayı yazılır |
| `kupa-altin` / `-gumus` / `-bronz` | E40a ilk üç sıra | 24 px | |
| `kilit` | E30, E31 seviye kilidi, E10 sandık kilitli | 16 px · 24 px | |
| `meydan-okuma-{tur}` (giriş: kapı, görev sayısı: kılıç) | E20 meydan okuma kartları | 32 px | Bugün `GorevTanimlari.Ikon` Font Awesome sınıfı |
| Alt menü ikonları: `menu-karargah` (kale), `menu-gorevler` (parşömen), `menu-market` (tezgâh), `menu-lonca` (bayrak), `menu-karakter` (miğfer); her biri etkin + pasif | G01 | 24 px | |
| `konfeti-piksel` (parçacık sayfası) | K03 | Tam ekran | Hareket azaltmada kullanılmaz |
| `seviye-isigi` (karakter arkası ışık hüzmesi) | K03, K02 | 240 px | |
| `logo-charmoji` (piksel yazı) | E00, E01, E02 | 160×40 px | Bugünkü `charmoji.png` piksel stile uyarlanmalı |

### 2.4 Boş durum ve karşılama çizimleri

| Ad | Kullanım yeri | Yaklaşık boyut |
|---|---|---|
| `bos-gorev` (boş parşömen ve tüy kalem) | E10 boş, E20 boş | 120 px |
| `bos-market` (kapalı tezgâh, "yolda" tabelası) | E30 boş | 120 px |
| `bos-filtre` (boş raf) | E30 / E52 filtre sonucu boş | 96 px |
| `bos-canta` (boş deri çanta) | E52 boş | 120 px |
| `bos-lonca` (tek başına bayrak tutan karakter silüeti) | E40a tek kişi, E40b boş | 120 px |
| `bos-istek` (boş posta kutusu) | E40c boş | 96 px |
| `bos-arama` (büyüteç) | E40d sonuç yok | 96 px |
| `hata-genel` (devrilmiş iksir şişesi) | Tüm ekranların hata durumu | 96 px |
| `cevrimdisi` (kopuk zincir) | G04 ve istek başarısız durumları | 24 px (band) · 96 px (tam alan) |
| `karsilama-nasil-1` (görev → XP) / `-2` (seri alevi) / `-3` (sandık) | E03c | 80 px |
| `tanitim-sahne` (karakter + sandık + lonca bayrağı) | E00 kahraman alanı | 320×200 px |
