# 02 · Karargah (E10) ve günlük sandık (M01)

Bugünkü karşılığı: `view/userpage.php` (sol sütundaki karakter kartı ve orta sütundaki görev panosu). Karargah bunların sadeleşmiş, karakter merkezli hâlidir; görev yönetimi (ekleme, düzenleme, silme, geri alma) ve meydan okumalar Görevler sekmesine (03) taşınır.

## 1. Amaç

Oyuncu uygulamayı açtığında **ilk üç saniyede** şunları anlamalı: karakterim nasıl (ruh hali, seviye), bugün ne kadar yol aldım (x/y görev), sandığa ne kadar kaldı, sıradaki görevim ne. Ve tek dokunuşla bir görevi tamamlayabilmeli.

## 2. İçerik hiyerarşisi ve yerleşim (mobil, yukarıdan aşağı)

```
┌────────────────────────────────────────┐
│ [Sv. 4]        Karargah        ◉ 1.250 │  G02 üst bar
├────────────────────────────────────────┤
│ Merhaba, {ad}                          │  1  karargah.selam
│                                        │
│            ┌──────────────┐            │
│            │  KARAKTER    │ ( konuşma  │  2  karakter, 200–240 px, kuşanılmış
│            │  200–240 px  │   balonu ) │     eşyalarla; ruh hali ifadesi
│            └──────────────┘            │
│              ‾‾‾ gölge ‾‾‾             │
│                                        │
│ [Zeka Sv.3 ▓▓▓░░] [Güç Sv.2 ▓░░░░]     │  3  beş mini yetenek barı
│ [Sos. Sv.1 ▓▓░░░] [Dis. Sv.2 ▓▓▓▓░]    │     (2 sütunlu ızgara, 5. tek)
│ [Yar. Sv.1 ░░░░░]                      │
│ Seviye 5 için 3 yetenek seviyesi daha  │  4  ortak.sonraki_seviye
├────────────────────────────────────────┤
│ Bugün 3/5 görev  ▓▓▓▓▓▓▓▓▓░░░░░░       │  5  günün ilerleme barı
│ ┌────────────────────────────────────┐ │
│ │ [sandık] Günlük sandık             │ │  6  sandık kartı (kilitli/hazır/açıldı)
│ │ Tüm görevleri bitir… 2 görev kaldı │ │
│ └────────────────────────────────────┘ │
├────────────────────────────────────────┤
│ Sıradaki görevler                      │  7  en fazla 5 tamamlanmamış görev
│ ┌────────────────────────────────────┐ │     (Görev kartı bileşeni, 03 §3)
│ │[ ] 30 sayfa kitap oku              │ │
│ │    [Zeka] +13 XP  (alev) 8 gün ×1,25│ │
│ └────────────────────────────────────┘ │
│ ┌────────────────────────────────────┐ │
│ │[ ] 20 dakika yürüyüş  ...          │ │
│ └────────────────────────────────────┘ │
│ Tüm görevler (5)  →                    │  8  E20'ye gider
├────────────────────────────────────────┤
│ Karargah  Görevler  Market  Lonca  Karakter │  G01
└────────────────────────────────────────┘
```

### 2.1 Bölümlerin ayrıntısı

1. **Selam**: `karargah.selam`. Kahraman adı en fazla 1 satır, taşarsa üç nokta.
2. **Karakter**: Kuşanılmış eşyalarla birlikte çizilir. Hafif "nefes" animasyonu (2 sn döngü, 2 px yukarı-aşağı; hareket azaltmada yok). Yanında, ruh haline göre bir konuşma balonu (aşağıdaki tablo). Karaktere dokunulabilir (bkz. §4.3). Karakterin üstüne seviye rozeti yerleşir (`ortak.seviye_kisa`).
3. **Mini yetenek barları**: Beş yetenek, sabit sıra: Zeka, Güç, Sosyallik, Disiplin, Yaratıcılık. Her biri: yetenek ikonu + kısa ad + "Sv. {n}" + 6 px yüksekliğinde bar (yetenek renginde dolgu, `kenarlik-etkin` çerçeve). XP sayıları burada gösterilmez (Karakter sekmesinde var); ekran okuyucuya `ortak.yetenek_bar_etiketi` okunur. Bir bara dokunmak Karakter sekmesine (E50) o yetenek açık olarak gider.
4. **Sonraki karakter seviyesi**: `ortak.sonraki_seviye`. Hesap: `kalan = 5 × (seviye + 1) − (beş yetenek seviyesinin toplamı)`. Örnek: seviye 4, yetenek seviyeleri 5+4+4+3+4 = 20 → `5 × 5 − 20 = 5`. **VARSAYIM S20**: karakter seviyesinin ilerlemesi XP ile değil, bu sayı ile anlatılır.
5. **Günün ilerlemesi**: `karargah.bugun_ilerleme` + 8 px bar (altın dolgu). Toplam = aktif görev sayısı; tamamlanan = bugün tamamlananlar.
6. **Sandık kartı**: Bkz. §5.
7. **Sıradaki görevler**: Başlık `karargah.siradaki_baslik`. Bugün **tamamlanmamış** görevler, oluşturulma sırasına göre, en fazla 5 tane. Bileşen: Görev kartı (03 §3). Tamamlanan kart 1,2 sn "Tamamlandı" hâlinde durur, sonra 300 ms içinde yüksekliği kapanarak listeden çıkar ve sıradaki görev aşağıdan gelir (hareket azaltmada: anında kaybolur). Tamamlanmış görevler Karargah'ta gösterilmez; geri alma Görevler sekmesinden ya da bildirimdeki "Geri al" düğmesinden yapılır.
8. **Tüm görevler bağlantısı**: `karargah.tum_gorevler`; görev sayısı 0 ise gizlenir.

### 2.2 Karakter ruh hali

| Ruh hali | Koşul (**VARSAYIM S26**) | İfade varlığı | Konuşma balonu |
|---|---|---|---|
| Uykulu | Bugün 0 görev tamamlandı **ve** dün hiç görev tamamlanmadı (dün aktif görev vardıysa) | `karakter-ifade-uykulu` | `karargah.ruh_hali_uykulu` |
| Hazır | Bugün 0 görev tamamlandı (uykulu değilse) | `karakter-ifade-hazir` | `karargah.ruh_hali_hazir` |
| Neşeli | Bugün en az 1, tümünden az görev tamamlandı | `karakter-ifade-nese` | `karargah.ruh_hali_neseli` |
| Zafer | Bugünün tüm görevleri tamam (en az 1 görev varken) | `karakter-ifade-zafer` | `karargah.ruh_hali_zafer` |
| (Görev yok) | Hiç aktif görev yok | `karakter-ifade-hazir` | `karargah.ruh_hali_gorev_yok` |

Ruh hali sayfa açılışında sunucudan gelen veriyle hesaplanır, görev tamamlanınca / geri alınınca istemcide yeniden hesaplanır.

## 3. Kullanıcı akışları

### 3.1 Mutlu yol: günün ilk görevini Karargah'tan tamamlama

1. Oyuncu Karargah'ı açar. Karakter "hazır" ifadesinde, balon `karargah.ruh_hali_hazir`.
2. "Sıradaki görevler"deki kartın onay kutusuna (ya da kartın herhangi bir yerine) dokunur.
3. K01 oynar (03 §9): onay kutusu dolar, sunucu onaylar, "+13 XP" karttan ilgili **mini yetenek barına** uçar, bar dolar, karakter zıplar, ifadesi "neşeli"ye döner, günün ilerleme barı ilerler, sandık kartındaki "kalan" sayısı azalır.
4. Bildirim (G03): `gorevler.tamamlandi_bildirim` + `ortak.geri_al` (5 sn).
5. Kart 1,2 sn sonra listeden çıkar.

### 3.2 Günün son görevi → sandık

1. Oyuncu son tamamlanmamış görevi tamamlar. K01 normal oynar.
2. K01 (ve gerekiyorsa K02/K03) bittikten sonra **K05**: karakter "zafer" pozuna geçer (600 ms), sandık kartı "hazır" hâline döner ve sallanmaya başlar, "Sıradaki görevler" alanı `karargah.hepsi_tamam_baslik` + `karargah.hepsi_tamam_aciklama` ile değişir. Bildirim: `sandik.hazir_bildirim` + `sandik.hazir_bildirim_dugme`.
3. Oyuncu sandık kartına ya da bildirimdeki "Aç"a dokunur → M01 (§6).

### 3.3 Sapmalar

| Sapma | Davranış |
|---|---|
| Hiç görev yok | Boş durum (§7). Sandık kartı `sandik.kart_gorev_yok` gösterir, kilitli. |
| Sunucu tamamlamayı reddeder | Onay kutusu boşalır, kart yerinde kalır, G03 hata: sunucunun gerekçesine göre `gorevler.hata_tamamlanamadi` ya da `ortak.hata_genel`. Ödül animasyonu oynamaz. |
| İstek ulaşmaz / zaman aşımı | Onay kutusu boşalır, G03: `ortak.istek_basarisiz` + `ortak.tekrar_dene` (dokununca aynı istek yeniden gönderilir). |
| Oyuncu iki görevi art arda hızla işaretler | Her kart kendi isteğini bağımsız gönderir; K01 animasyonları sıraya girer (ikincisi birincinin "+XP" uçuşu bittikten sonra başlar, en fazla 300 ms gecikme). Seviye atlama kutlamaları sıraya girer, aynı anda iki modal açılmaz. |
| Gece yarısı geçerken sayfa açık | Sayfa görünür olduğunda (sekmeye dönüldüğünde) tarih değişmişse sayfa sessizce yeniden yüklenir ve G03 `karargah.yeni_gun_bildirim` gösterilir. |
| Görev başka bir cihazda tamamlanmış | Sunucu "zaten tamamlandı" der → kart tamamlandı hâline geçer, ödül animasyonu **oynamaz**, G03 `gorevler.zaten_tamamlandi`. |
| Oturum düşmüş | G08. |

### 3.4 Karaktere dokunma

1. Oyuncu karaktere dokunur → karakter el sallar (`karakter-el-salla`, 600 ms) ve balon metni `karargah.dokunma_sozu_1`, `karargah.dokunma_sozu_2`, `karargah.dokunma_sozu_3`, `karargah.dokunma_sozu_4`, `karargah.dokunma_sozu_5` arasından rastgele (bir önceki ile aynı olmayan) biriyle değişir. 4 sn sonra balon ruh hali metnine döner.
2. Art arda dokunmada animasyon yeniden başlamaz, yalnızca metin değişir (en fazla saniyede bir).
3. Hareket azaltmada el sallama yok; yalnızca balon metni değişir.
4. Klavye: karakter bir `button`dur (`karargah.karakter_etiketi`); Enter/Boşluk aynı tepkiyi verir. Balon metni `aria-live="polite"` alanındadır.

## 4. Etkileşim ve geri bildirim anları

| Tetik | Ne olur (sıra ve süre) | Hareket azaltma karşılığı |
|---|---|---|
| Görev tamamlama | K01 (03 §9). Karargah'a özgü: "+XP" hedefi ilgili **mini yetenek barı**; karakter zıplar (600 ms, K01'in T+0 anında); ruh hali yeniden hesaplanır; günün ilerleme barı 400 ms'de dolar; sandık kartındaki kalan sayısı güncellenir. | Uçuş ve zıplama yok; değerler 150 ms opaklık geçişiyle güncellenir. |
| Yetenek seviyesi atlama | K02 (03 §9); bant, ilgili mini barın hemen üstünde 2,5 sn görünür. | Bant hareketsiz görünür. |
| Karakter seviyesi atlama | K03 (03 §9); kapandıktan sonra karakterin üstündeki rozet yeni sayıyla 300 ms parlar; `ortak.sonraki_seviye` güncellenir. | Parlama yok. |
| Günün son görevi | K05 (§5.2). | Sallanma ve poz animasyonu yok; ifade anında değişir; bildirim yine gösterilir. |
| Sandık açma | K06 (§6). | Açılma ve uçan sikke yok; ödül metni doğrudan gösterilir. |
| Karaktere dokunma | §3.4. | Yalnızca metin. |

## 5. Günlük sandık kartı ve K05

### 5.1 Kartın hâlleri

| Hâl | Koşul | Görsel | Metin | Dokununca |
|---|---|---|---|---|
| Görev yok | Aktif görev sayısı 0 | `sandik-kilitli`, soluk | `sandik.kart_kilitli_baslik` + `sandik.kart_gorev_yok` | E21 (görev ekle) |
| Kilitli | En az 1 aktif görev, hepsi tamam değil | `sandik-kilitli` | `sandik.kart_kilitli_baslik` + `sandik.kart_kilitli_aciklama` | M01 kilitli hâlde açılmaz; bunun yerine kart 200 ms hafifçe sallanır ve G03 `sandik.kart_kilitli_aciklama` |
| Hazır | Tüm aktif görevler bugün tamam, sandık bugün açılmadı | `sandik-hazir`, 2 sn'de bir sallanma, altın kenarlık | `sandik.kart_hazir_baslik` + düğme `sandik.kart_hazir_dugme` | M01 |
| Açıldı | Sandık bugün açıldı | `sandik-acildi` | `sandik.kart_acildi_baslik` + `sandik.kart_acildi_aciklama` | Etkisiz (kart düğme değildir) |

Kurallar (**VARSAYIM S6**, S24):

- Açılma koşulu: en az 1 aktif görev ve bugünkü **tüm** aktif görevler tamamlanmış. Gün içinde eklenen görev de koşula dahildir; hazır sandık, yeni görev eklenince yeniden kilitlenir.
- Ödül: `20 + 5 × (bugün tamamlanan görev sayısı)` altın, en fazla 60 altın. Altın dışında içerik yok.
- Günde bir kez açılır. Açıldıktan sonra görev geri alınsa bile sandık "açıldı" kalır ve ödül geri alınmaz.
- Gece yarısına kadar açılmayan hazır sandık kaybolur (kartta gün içinde rozet ve sallanma ile sürekli hatırlatılır).

### 5.2 K05: Günün görevleri tamam

Tetik: bir tamamlama yanıtı "sandık bugün açılabilir hâle geldi" der.

1. K01 ve varsa K02/K03 bitene kadar beklenir.
2. Karakter `karakter-ifade-zafer` pozuna geçer (600 ms ölçek 1 → 1,08 → 1).
3. Sandık kartı kilitliden hazıra döner: kilit 300 ms'de düşer, kart altın kenarlık alır, sallanma başlar.
4. Günün ilerleme barı tam dolu, bar 1 sn altın parıltı.
5. Alt menüde Karargah sekmesine altın nokta (`ortak.rozet_sandik_etiketi`).
6. G03 bildirim: `sandik.hazir_bildirim` + `sandik.hazir_bildirim_dugme` (5 sn).
7. Ekran okuyucu (`polite`): `sandik.hazir_bildirim`.

Görevler sekmesindeyken K05 oluşursa 2–6. adımlardan yalnızca 5 ve 6 uygulanır ("Aç" düğmesi Karargah'a gider ve M01'i açar).

## 6. Günlük sandık modalı (M01) ve K06

```
┌────────────────────────────────┐
│ Günlük sandık             [x]  │  sandik.modal_baslik
│                                │
│        [ sandik-hazir 160px ]  │
│                                │
│ Bugünkü 5 görevin tamam.       │  sandik.modal_aciklama
│                                │
│ [        Sandığı aç         ]  │  sandik.modal_ac_dugme (birincil)
└────────────────────────────────┘
        ↓ açıldıktan sonra
┌────────────────────────────────┐
│ Günlük sandık             [x]  │
│        [ sandik-acik ]         │
│          +45 altın             │  sandik.modal_odul (büyük, piksel font)
│  Bugünkü 5 görevinin ödülü     │  sandik.modal_odul_aciklama
│ [          Topla            ]  │  sandik.modal_topla_dugme
└────────────────────────────────┘
```

Akış:

1. Modal açılır; odak "Sandığı aç" düğmesine gider.
2. Oyuncu "Sandığı aç"a dokunur → düğme G06 yükleme hâline geçer, istek gider.
3. Başarı (K06): sandık açılma animasyonu (6 kare, 700 ms), ardından "+{altin} altın" sayısı 0'dan hedefe 600 ms'de sayar; 6–10 sikke (`altin-parcacik`) modalın üstünden üst bardaki altın göstergesine uçar (600 ms); üst bar altını aynı sürede artarak güncellenir. Düğme "Topla"ya döner.
4. "Topla" ya da [x] modalı kapatır; sandık kartı "açıldı" hâline geçer; Karargah sekmesindeki altın nokta kalkar.
5. Ekran okuyucu (`polite`): `sandik.acildi_okuyucu`.

| Durum | Ne görünür |
|---|---|
| Yükleniyor | "Sandığı aç" düğmesinde G06. |
| Hata: koşul sağlanmıyor (ör. başka cihazda görev geri alındı) | Modal içinde `sandik.hata_kilitli`, düğme `ortak.kapat`; kapatınca kart yeniden "kilitli" çizilir. |
| Hata: zaten açıldı | `sandik.hata_zaten_acildi`; kart "açıldı"ya geçer; altın sunucudan gelen değerle güncellenir, animasyon yok. |
| Hata: gün değişti | `sandik.hata_sure_doldu`; kart yeni güne göre yeniden çizilir. |
| İstek başarısız | Düğme eski hâline döner; modal içinde `ortak.istek_basarisiz` + `ortak.tekrar_dene`. |
| Hareket azaltma | Açılma, sayma ve uçan sikke yok; açık sandık ve "+{altin} altın" doğrudan gösterilir. |

## 7. Durumlar (E10)

| Durum | Ne görünür | Metin |
|---|---|---|
| Boş (hiç görev yok) | Karakter "hazır" ifadesinde, balon `karargah.ruh_hali_gorev_yok`. Mini barlar ve sonraki seviye görünür. İlerleme barı gizli. Sandık kartı "görev yok" hâlinde. "Sıradaki görevler" yerine `bos-gorev` çizimi + başlık + açıklama + birincil düğme (→ E21). | `karargah.bos_baslik`, `karargah.bos_aciklama`, `karargah.bos_dugme` |
| Boş (hepsi tamam) | "Sıradaki görevler" yerine zafer metni; sandık kartı hazır ya da açıldı. | `karargah.hepsi_tamam_baslik`, `karargah.hepsi_tamam_aciklama` |
| Yükleniyor | Sayfa sunucuda çizilir; tam sayfa yükleme durumu yok. Görev tamamlama sırasında yalnızca ilgili kartın onay kutusunda 400 ms sonra dönen ikon (03 §9). Sandık açarken G06. | `ortak.yukleniyor` (yalnızca ekran okuyucu) |
| Hata (sayfa verisi alınamadı) | Üst bar ve alt menü çizilir; içerik alanında `hata-genel` + başlık + açıklama + "Yeniden yükle". | `ortak.sayfa_hatasi_baslik`, `ortak.sayfa_hatasi_aciklama`, `ortak.yeniden_yukle` |
| Hata (işlem reddedildi) | §3.3 tablosu. | `gorevler.hata_tamamlanamadi`, `ortak.hata_genel` |
| İstek başarısız / çevrimdışı | G04 bandı; onay kutuları ve sandık düğmesi devre dışı görünür (opaklık %50 değil, kenarlık soluk + kilit yok), dokununca `ortak.cevrimdisi_islem`. Son başarılı tamamlamalar ekranda kalır. | `ortak.cevrimdisi_bandi`, `ortak.cevrimdisi_islem`, `ortak.istek_basarisiz` |
| Kilitli | Sandık kartı kilitli hâli (§5.1). Ekranın kendisi kilitlenmez. | `sandik.kart_kilitli_aciklama`, `sandik.kart_gorev_yok` |
| Başarı | K01, K05, K06. | §4, §5, §6 |

## 8. Geri alma kuralları

- Karargah'ta tamamlanan görev, yalnızca G03 bildirimindeki `ortak.geri_al` ile (5 sn içinde, onaysız) geri alınabilir. Sonrasında geri alma Görevler sekmesinden yapılır (03 §8).
- Bildirimden geri alınınca görev "Sıradaki görevler" listesine eski yerine geri döner; ruh hali, ilerleme barı, mini bar ve sandık kartı yeniden hesaplanır. Ayrıntılı kurallar 03 §8.
- Sandık açma geri alınamaz.

## 9. Erişilebilirlik

- Sayfa başlığı (`<h1>`, görsel olarak üst barda): `karargah.baslik`. Bölüm başlıkları `<h2>`: sıradaki görevler, sandık.
- Karakter: `button`, erişilebilir adı `karargah.karakter_etiketi`; karakter SVG'si `aria-hidden="true"`.
- Konuşma balonu ve K01/K05 duyuruları tek bir `aria-live="polite"` bölgede; K03 seviye atlama `assertive`.
- Mini yetenek barları: her biri `role="progressbar"` + `aria-valuenow/min/max` (o seviyedeki XP) + `aria-label` = `ortak.yetenek_bar_etiketi`.
- Günün ilerleme barı: `role="progressbar"`, etiket `karargah.bugun_ilerleme`.
- Görev kartı erişilebilirliği 03 §11'de.
- Odak sırası: selam → karakter → mini barlar → sandık kartı → görev kartları → tüm görevler → alt menü.
- Dokunma hedefleri: karakter en az 160×160 px, görev kartı en az 64 px yükseklik (tamamı dokunulabilir), sandık kartı en az 72 px yükseklik.
- Hiçbir bilgi yalnızca renkle verilmez: yetenek her zaman ikon + ad ile; sandık hâlleri metinle.

## 10. Veri ihtiyacı

Bu ekran açılırken bilmesi gerekenler:

| Bilgi | Bugünkü kodda | Not |
|---|---|---|
| Kahraman adı | Var (`Kullanicilar.Ad`) | |
| Görünüm renkleri | Var (`AvatarVerisi`) | |
| Kuşanılmış eşyalar (tür, eşya, karakter katmanı) | Kısmen (ad ve tür var; katman çizimi koda gömülü) | **YENİ VERİ**: eşyanın karakter katmanı görseline erişim |
| Altın | Var (`Bakiye`) | Tam sayı olarak |
| Beş yeteneğin seviyesi ve o seviyedeki XP / gereken XP | Hesaplanıyor (`hesaplaLevelVerileri`) | |
| Karakter seviyesi ve `sonraki_seviye` için kalan yetenek seviyesi | Seviye hesaplanıyor; kalan yeni | **YENİ VERİ** (hesaplanan) |
| Bugünkü aktif görevler: kimlik, ad, yetenek, bugün tamamlandı mı, **etkin seri**, tamamlanırsa kazanılacak XP, tamamlanırsa geçerli çarpan | Kısmen (ad, yetenek, seri, bugün yapıldı mı var) | **YENİ VERİ**: "etkin seri" (son tamamlama dün ya da bugün değilse 0), "tamamlanırsa kazanılacak XP" ve çarpanı (bir sonraki seriyle hesaplanmış) |
| Dün aktif görev vardı mı, dün kaç görev tamamlandı | Yok | **YENİ VERİ** (ruh hali "uykulu" için) |
| Sandık hâli (görev yok / kilitli / hazır / açıldı) ve açıldıysa kazanılan altın | Yok | **YENİ VERİ** |
| Alınmamış meydan okuma ödülü var mı | Hesaplanabilir (`KullaniciGunlukGorevleri`) | Alt menü rozeti |
| Bekleyen gelen arkadaşlık isteği sayısı | Var (`getPendingRequests`) | Alt menü rozeti |
| Sunucu tarihi (bugün) | Var (`date('Y-m-d')`) | Gün değişimi kontrolü için sayfaya yazılmalı |

Tetiklediği işlemler:

| İşlem | Bugünkü kodda | Yanıtta olması gerekenler |
|---|---|---|
| Görevi tamamla / geri al | Var (`ajax_islem.php`, `ka_id` + `durum`) | 03 §12'deki tam yanıt |
| Sandığı aç | Yok | **YENİ VERİ / İŞLEM**: başarı, kazanılan altın, yeni altın toplamı; ret gerekçesi (kilitli / zaten açıldı / gün değişti) |
