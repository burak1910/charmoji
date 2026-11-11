# 05 · Lonca (E40a–E40d), arkadaş profili (E44), arkadaşlıktan çıkarma (M06)

Bugünkü karşılığı: `view/userpage.php` sağ sütundaki "Topluluk / Arkadaşlar / İstekler" kutusu; işlemler `service/friend_action.php` ve `service/friendmanager.php` (arama işlevi `searchUsers` var, arayüzü yok). Haftalık sıralama ve arkadaş profili yeni.

## 1. Amaç

Lonca, oyuncunun arkadaşlarıyla **haftalık XP yarışı**dır. Oyuncu kendi sırasını, bir üst sıraya ne kadar kaldığını görür; arkadaş ekler, istekleri yönetir.

## 2. Yerleşim ve iç sekmeler

```
┌────────────────────────────────────────┐
│ [Sv. 4]           Lonca        ◉ 1.250 │  G02
├────────────────────────────────────────┤
│ [Sıralama][Arkadaşlar][İstekler ②][Bul]│  iç sekmeler (kaydırılabilir şerit)
├────────────────────────────────────────┤
│        (seçili iç sekmenin içeriği)    │
├────────────────────────────────────────┤
│                 G01                    │
└────────────────────────────────────────┘
```

- Varsayılan iç sekme: Sıralama. Alt menüdeki Lonca rozetinden (bekleyen istek) gelinirse İstekler.
- Seçili iç sekme adres çapasında tutulur (`#siralama`, `#arkadaslar`, `#istekler`, `#bul`) ki geri tuşu ve yenileme aynı yere dönsün.
- İç sekmeler: `lonca.sekme_siralama`, `lonca.sekme_arkadaslar`, `lonca.sekme_istekler`, `lonca.sekme_bul`. İstekler sekmesinde bekleyen gelen istek sayısı rozeti (en fazla "9+"), erişilebilir adı `lonca.sekme_istekler_sayili`.
- Sıralama, Arkadaşlar ve İstekler sayfa açılışında sunucuda çizilir; arama sonuçları istekle gelir.

## 3. Sıralama (E40a)

```
│ Bu haftanın sıralaması                 │  lonca.siralama_baslik
│ 10 Kas – 16 Kas · Bitmesine 5 gün 3 saat│  lonca.siralama_tarih · lonca.siralama_kalan
│ ┌────────────────────────────────────┐ │
│ │ [kupa] 1  [kr] Ayşe     Sv. 6  420 XP│ │
│ │ [kupa] 2  [kr] Mert     Sv. 5  390 XP│ │
│ │ ▌      3  [kr] Sen      Sv. 4  310 XP│ │  oyuncunun satırı: altın kenarlık, "Sen" etiketi
│ │ Bir üst sıraya 80 XP kaldı          │ │  lonca.siralama_ust_fark (oyuncu satırının altında)
│ │        4  [kr] Can      Sv. 3  120 XP│ │  4. sıradan itibaren kupa yok
│ └────────────────────────────────────┘ │
│ Bu hafta görevlerden kazanılan XP…     │  lonca.siralama_kural
```

Kurallar (**VARSAYIM S3, S4**):

- Katılımcılar: oyuncu + kabul edilmiş tüm arkadaşları. Sınır yok.
- Puan: hafta içinde görev tamamlamalarından kazanılan **net** XP (geri alınanlar düşülür). Sandık ve meydan okuma XP vermez, sayılmaz.
- Hafta: Pazartesi 00:00 – Pazar 23:59 (Europe/Istanbul).
- Eşitlik: aynı XP'ye sahip olanlar aynı sıra numarasını alır; aralarında kahraman adına göre alfabetik dizilir; sonraki sıra numarası atlanır (1, 2, 2, 4).
- 0 XP olanlar da listelenir.
- İlk üç sıra (eşitlikte aynı kupa) `kupa-altin`, `kupa-gumus`, `kupa-bronz`.

Kalan süre bir günden azsa `lonca.siralama_kalan` yerine `lonca.siralama_kalan_saat`.

Satır: sıra numarası (kupa ya da sayı), 40 px karakter (kuşanılmış eşyalarla), kahraman adı (taşarsa üç nokta), `ortak.seviye_kisa`, `lonca.siralama_xp`. Oyuncunun satırında ad yerine `lonca.siralama_sen`; satır altın kenarlıklı. Oyuncunun satırı görünüm alanı dışındaysa alt menünün hemen üstünde sabit bir kopyası görünür.

Oyuncu satırının altındaki ipucu: oyuncu 1. değilse `lonca.siralama_ust_fark` (fark = bir üstteki farklı puanlı satır − oyuncunun puanı); 1. ise `lonca.siralama_lider`.

Satıra dokunma: arkadaş satırı → E44; oyuncunun kendi satırı → E50.

## 4. Arkadaşlar (E40b)

- Üstte `lonca.arkadaslar_sayi`.
- Satır (en az 64 px): 48 px karakter, kahraman adı, `ortak.seviye_kisa`, bu haftaki XP (`lonca.siralama_xp`), sağda menü düğmesi (⋯, 44×44 px, erişilebilir adı `lonca.arkadas_menu_etiketi`). Satırın geri kalanı → E44.
- Menü tek seçenek içerir: `lonca.profil_cikar` (hata renginde) → M06. Akış §7.1.
- Sıra: kahraman adına göre alfabetik.

## 5. İstekler (E40c)

```
│ Gelen istekler                          │  lonca.istekler_gelen_baslik
│ ┌────────────────────────────────────┐  │
│ │ [kr] Zeynep seni arkadaş olarak     │  │  lonca.istek_metni
│ │      eklemek istiyor  · Sv. 3       │  │
│ │ [  Kabul et  ]   [  Reddet  ]       │  │  lonca.istek_kabul / lonca.istek_reddet
│ └────────────────────────────────────┘  │
│ Gönderdiklerin                          │  lonca.istekler_giden_baslik
│ [kr] Burak · Sv. 2            Bekliyor  │  lonca.istek_bekliyor
```

- "Gelen istekler" boşsa yerine `bos-istek` + `lonca.istekler_bos`. "Gönderdiklerin" boşsa bölüm tamamen gizlenir.
- Gönderilen isteği iptal etme bu dalgada yok (09, Öneri).

### 5.1 Kabul / Reddet akışı

1. "Kabul et" → G06 → başarı: kart 300 ms'de kaybolur; G03 `lonca.istek_kabul_bildirim`; Arkadaşlar listesine ve Sıralama'ya eklenir (iç sekme değiştirince görünür); İstekler rozeti azalır.
2. "Reddet" → onay **yok** (düşük risk; karşı taraf yeniden istek gönderebilir) → G06 → başarı: kart kaybolur; G03 `lonca.istek_red_bildirim`.
3. Hata: istek artık yok (geri çekilmiş / başka cihazda işlenmiş) → kart kaybolur, G03 `lonca.istek_hata_artik_yok`; diğer → kart yerinde kalır, G03 `lonca.istek_hata_islenemedi`.
4. İstek başarısız: düğmeler eski hâline döner, G03 `ortak.istek_basarisiz` + `ortak.tekrar_dene`.
5. İşlem sayfayı yeniden yüklemeden yapılır (bugünkü form gönderip yönlendirme yerine).

## 6. Maceracı bul (E40d)

```
│ Kahraman adıyla ara                     │  lonca.arama_etiketi
│ [ (büyüteç) Kahraman adı yaz        ]   │  lonca.arama_ipucu
│                                         │
│ Önerilen maceracılar                    │  lonca.oneriler_baslik (arama boşken)
│ [kr] Elif     Sv. 7     [İstek gönder]  │  lonca.istek_gonder
│ [kr] Kaan     Sv. 5     [İstek gönderildi] (devre dışı)
```

- Arama yalnızca **kahraman adıyla**, en az 2 karakter, büyük/küçük harf duyarsız, "içerir" eşleşmesi; e-posta ile arama ve e-postanın gösterilmesi yok (**VARSAYIM S13**).
- Yazmayı bıraktıktan 400 ms sonra istek gönderilir; önceki istek iptal edilir. 1 karakterde `lonca.arama_kisa`.
- Sonuçlar ve öneriler: oyuncunun kendisi, arkadaşları ve aralarında bekleyen istek olanlar **hariç** (bugünkü kuralla aynı). En fazla 20 sonuç; öneriler en fazla 10 (en yüksek toplam XP'ye göre, bugünkü kural).
- Satır: 40 px karakter, kahraman adı, `ortak.seviye_kisa` (resmî karakter seviyesi formülüyle), `lonca.istek_gonder`. Satırın kendisi dokunulabilir değildir (profil yalnızca arkadaşlara açık).

### 6.1 İstek gönderme akışı

1. "İstek gönder" → G06 → başarı: düğme devre dışı `lonca.istek_gonderildi` olur; G03 `lonca.istek_gonderildi_bildirim`; kişi "Gönderdiklerin" listesine eklenir.
2. Hata: zaten istek/arkadaşlık var → düğme `lonca.istek_gonderildi` olur, G03 `lonca.istek_zaten_var`; karşı taraf zaten istek göndermişse → G03 `lonca.istek_karsi_taraf_gonderdi` ve İstekler rozeti güncellenir; diğer → `lonca.istek_gonder_hata`.
3. İstek başarısız: düğme eski hâline, G03 `ortak.istek_basarisiz`.

## 7. Arkadaş profili (E44) ve M06

```
┌────────────────────────────────────────┐
│ [<]            Ayşe            ◉ 1.250 │  G02 (başlık kahraman adı)
├────────────────────────────────────────┤
│        [ KARAKTER 200 px + eşyalar ]   │
│              [ Sv. 6 ]                  │
│     Bu hafta 420 XP · 1. sıra          │  lonca.profil_haftalik
├────────────────────────────────────────┤
│ Yetenekler                              │  lonca.profil_yetenekler_baslik
│ [kitap] Zeka        Sv. 5  ▓▓▓▓░        │  beş yetenek: seviye + bar (XP sayısı yok)
│ ...                                     │
│ Görevlerin adları yalnızca sahibine…    │  lonca.profil_gizlilik_notu
├────────────────────────────────────────┤
│        Arkadaşlıktan çıkar              │  lonca.profil_cikar (hata renginde metin düğme)
└────────────────────────────────────────┘
```

- Görünen bilgiler (**VARSAYIM S14**): görünüm, kuşanılmış eşyalar, karakter seviyesi, beş yetenek seviyesi ve seviye içi ilerleme oranı, bu haftanın XP'si ve sırası. Görev adları, seriler, altın, e-posta **görünmez**.
### 7.1 Arkadaşlıktan çıkarma akışı (onaylı)

Arka uçta arkadaş silme artık **reddetmeden ayrı** bir işlemdir; arayüz yalnızca bu işlemi kullanır (istek reddi `lonca.istek_reddet` ile karıştırılmaz).

Giriş noktaları: E44 altındaki `lonca.profil_cikar` düğmesi ya da E40b satır menüsündeki `lonca.profil_cikar`.

1. Oyuncu "Arkadaşlıktan çıkar"a dokunur → M06 (G07) açılır: başlık `lonca.cikar_onay_baslik`, açıklama `lonca.cikar_onay_aciklama`, birincil (tehlikeli: `hata` renginde kenarlık + metin) `lonca.cikar_onay_dugme`, ikincil `ortak.vazgec`. Varsayılan odak "Vazgeç"te; Esc ve arka plana dokunma "Vazgeç" sayılır.
2. "Vazgeç" → M06 kapanır, hiçbir şey değişmez, odak çıkarma düğmesine döner.
3. "Çıkar" → birincil düğmede G06; ikincil düğme devre dışı.
4. Başarı: M06 kapanır. E44'ten gelindiyse E44 kapanıp Arkadaşlar listesine dönülür; E40b'den gelindiyse satır 300 ms'de yükseklik kapanarak kaybolur (hareket azaltmada anında). `lonca.arkadaslar_sayi` güncellenir; kişi Sıralama'dan çıkar. G03 `lonca.cikarildi_bildirim`. Ekran okuyucu (`polite`): `lonca.cikarildi_bildirim`. Liste boşaldıysa E40b boş durumu.
5. Hata (sunucu reddetti): M06 açık kalır, açıklamanın yerine `lonca.cikar_hata`; birincil düğme tekrar etkin. Arkadaşlık zaten yoksa (başka cihazda çıkarılmış ya da karşı taraf çıkarmış) M06 kapanır, satır kaldırılır, G03 `lonca.profil_hata_yok`.
6. İstek başarısız: M06 kapanır, hiçbir şey değişmez, G03 `ortak.istek_basarisiz` + `ortak.tekrar_dene` (yeniden M06 açar).
7. Çevrimdışı: çıkarma düğmeleri devre dışı, dokununca `ortak.cevrimdisi_islem`.

| Durum | M06 |
|---|---|
| Boş | Geçerli değil. |
| Yükleniyor | Adım 3. |
| Hata | Adım 5. |
| İstek başarısız / çevrimdışı | Adım 6–7. |
| Kilitli | Geçerli değil. |
| Başarı | Adım 4. |

Kurallar: Geri alma yok (yeniden istek gönderilerek arkadaş olunur). Karşı tarafa bildirim gitmez. Sunucu, oyuncunun bu arkadaşlığın tarafı olduğunu denetlemelidir.

## 8. Etkileşim ve geri bildirim anları

| Tetik | Ne olur | Süre | Hareket azaltma |
|---|---|---|---|
| İç sekme değişimi | İçerik 150 ms opaklıkla değişir; seçili sekmenin altında altın çizgi kayar. | 150 ms | Anında |
| Kabul / Reddet | Kart yüksekliği kapanarak kaybolur. | 300 ms | Anında |
| İstek gönder | Düğme metni değişir, 300 ms `basari` renginde tik. | 300 ms | Anında |
| Sıralama açılışı | Oyuncunun satırı 1 sn altın parıltı (yalnızca sayfa açılışında). | 1 sn | Yok |

## 9. Durumlar

| Durum | E40a Sıralama | E40b Arkadaşlar | E40c İstekler | E40d Maceracı bul | E44 Profil |
|---|---|---|---|---|---|
| Boş | Arkadaş yoksa yalnızca oyuncunun satırı + `bos-lonca` + `lonca.siralama_tek_baslik` + `lonca.siralama_tek_aciklama` + düğme `lonca.siralama_tek_dugme` (→ E40d). Herkes 0 XP ise liste + üstte `lonca.siralama_sifir`. | `bos-lonca` + `lonca.arkadaslar_bos_baslik` + `lonca.arkadaslar_bos_aciklama` + `lonca.arkadaslar_bos_dugme`. | `bos-istek` + `lonca.istekler_bos`. | Öneri yoksa `lonca.oneriler_bos`; arama sonucu yoksa `bos-arama` + `lonca.arama_sonuc_yok`. | Geçerli değil. |
| Yükleniyor | Sunucuda çizilir; geçerli değil. | Sunucuda çizilir; geçerli değil. | Kabul/Reddet düğmelerinde G06. | Arama sırasında 3 satırlık G05 iskelet; "İstek gönder"de G06. | Sunucuda çizilir; "Çıkar"da G06. |
| Hata | Sayfa verisi alınamazsa `ortak.sayfa_hatasi_baslik` + `ortak.sayfa_hatasi_aciklama` + `ortak.yeniden_yukle` (tüm Lonca için ortak). | Aynı. | §5.1: `lonca.istek_hata_artik_yok`, `lonca.istek_hata_islenemedi`. | `lonca.arama_hata` (sonuç alanında, `hata-genel` 96 px ile); §6.1 hataları. | `lonca.profil_hata_yok`, `lonca.cikar_hata`. |
| İstek başarısız / çevrimdışı | G04 bandı; liste son hâliyle görünür. | G04; satır menüsündeki çıkarma devre dışı (§7.1 adım 6–7). | Düğmeler devre dışı; `ortak.cevrimdisi_islem`. | Arama kutusu devre dışı; altında `ortak.cevrimdisi_bandi`. | "Çıkar" devre dışı. |
| Kilitli | Geçerli değil. | Geçerli değil. | Geçerli değil. | Gönderilmiş istek satırında düğme `lonca.istek_gonderildi` (devre dışı). | Arkadaş olmayan profil açılamaz (`lonca.profil_hata_yok`). |
| Başarı | Geçerli değil (salt bilgi). | Arkadaşlıktan çıkarma: `lonca.cikarildi_bildirim` (§7.1). | `lonca.istek_kabul_bildirim`, `lonca.istek_red_bildirim`. | `lonca.istek_gonderildi_bildirim`. | `lonca.cikarildi_bildirim`. |

## 10. Geri alma kuralları

- Kabul edilmiş istek: geri alınamaz; arkadaşlıktan çıkarma (M06) ile sonlandırılır.
- Reddedilen istek: geri alınamaz; karşı taraf yeni istek gönderebilir.
- Gönderilen istek: bu dalgada geri çekilemez.
- Arkadaşlıktan çıkarma: geri alınamaz; yeni istekle yeniden arkadaş olunur. Haftalık sıralamadan hemen çıkar; o hafta kazandığı XP kendisinde kalır.

## 11. Erişilebilirlik

- Sayfa başlığı (`<h1>`, görsel olarak üst barda): `lonca.baslik`.
- İç sekmeler: `role="tablist"` / `role="tab"` / `role="tabpanel"`, ok tuşlarıyla gezilir; seçili sekme `aria-selected="true"`.
- Sıralama gerçek bir sıralı listedir (`<ol>`); her satırın erişilebilir adı `lonca.siralama_satir_etiketi`; kupalar `aria-hidden`; oyuncunun satırı `aria-current="true"`.
- Arkadaş satırı: bağlantı, erişilebilir adı `lonca.arkadas_satir_etiketi`; ayrı menü düğmesi `lonca.arkadas_menu_etiketi` (`aria-haspopup="menu"`).
- Arama kutusu: görünür etiket `lonca.arama_etiketi`, `type="search"`; sonuç sayısı değişince `polite` duyuru: `lonca.arama_sonuc_sayisi`.
- İstek kartındaki düğmeler kişinin adını içeren erişilebilir ad alır: `lonca.istek_kabul_etiketi`, `lonca.istek_reddet_etiketi`.
- Sonuç satırındaki düğme: `lonca.istek_gonder_etiketi`.
- Dokunma hedefleri: satırlar en az 56 px, düğmeler en az 44 px yükseklik, "Kabul et" ve "Reddet" arasında en az 8 px boşluk.
- Kahraman adları kullanıcı girdisidir; kaçışlanarak gösterilir.

## 12. Veri ihtiyacı

| Bilgi | Bugünkü kodda | Not |
|---|---|---|
| Arkadaş listesi: kimlik, kahraman adı, görünüm, kuşanılmış eşyalar, karakter seviyesi | Ad var; görünüm sorguda yok (arayüz hep varsayılan karakter çiziyor); seviye yok | **YENİ VERİ**: görünüm, eşyalar, seviye |
| Haftalık XP (oyuncu + arkadaşlar), sıra, hafta başlangıç/bitiş, bitişe kalan süre | Yok | **YENİ VERİ**: tamamlama başına kazanılan XP ve tarih kaydı |
| Gelen bekleyen istekler: istek kimliği, gönderenin adı, görünümü, seviyesi | Ad var; görünüm ve seviye yok | **YENİ VERİ** |
| Gönderilen bekleyen istekler | Yok (`FriendManager`'da sorgu yok) | **YENİ VERİ** |
| Öneriler ve arama sonuçları: kimlik, ad, görünüm, seviye | Ad, e-posta, toplam XP var | E-posta arayüze **gönderilmemeli**; seviye resmî formülle |
| Arkadaş profili: görünüm, eşyalar, seviye, yetenek seviyeleri ve oranları, haftalık XP ve sıra | Yok | **YENİ VERİ** |

| İşlem | Bugünkü kodda | Başarı yanıtı | Ret gerekçeleri |
|---|---|---|---|
| Kahraman adıyla ara | Var (`searchUsers`, arayüzü yok; e-postada da arıyor) | Sonuç listesi | Çok kısa sorgu |
| İstek gönder | Var (`send_request`) | Başarı | Kendine, zaten var, karşı taraf göndermiş, kullanıcı yok |
| Kabul et | Var (`accept_request_id`) | Yeni arkadaşın satır verisi | İstek yok / bu oyuncuya değil |
| Reddet | Var (`reject_request_id`) | Başarı | İstek yok / bu oyuncuya değil |
| Arkadaşlıktan çıkar | Var (arka uçta reddetmeden ayrı silme işlemi; arayüzü yok) | Başarı, kalan arkadaş sayısı | Arkadaşlık yok / oyuncu taraf değil |
