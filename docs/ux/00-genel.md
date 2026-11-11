# 00 · Genel: oyun döngüsü, terimler, bilgi mimarisi, ton

Bu klasör Charmoji'nin RPG'ye dönüşümü için UX spesifikasyonudur. Dosyalar:

| Dosya | İçerik |
|---|---|
| `00-genel.md` | Oyun döngüsü, terimler sözlüğü, bilgi mimarisi, ortak bileşenler, ton ve ses |
| `01-ekran-envanteri.md` | Tüm ekranlar, modallar, kutlama anları; görsel varlık ihtiyacı |
| `02-karargah.md` | Karargah sekmesi (ana ekran) ve günlük sandık |
| `03-gorevler.md` | Görevler sekmesi, görev ekleme/düzenleme, meydan okumalar, görev tamamlama geri bildirimi |
| `04-market.md` | Market, eşya detayı ve deneme, satın alma |
| `05-lonca.md` | Liderlik tablosu, arkadaşlar, istekler, maceracı bulma, arkadaş profili |
| `06-karakter.md` | Karakter, yetenekler, görünüm, çanta, çıkış |
| `07-giris-kayit-karsilama.md` | Tanıtım, giriş, kayıt, ilk kullanım (karşılama) |
| `08-metinler.md` | Tüm arayüz metinleri (anahtar → metin) |
| `09-acik-sorular-ve-oneriler.md` | Açık sorular (önerilen varsayılanlarla) ve kapsam dışı öneriler |

**Okuma kuralları**

- `ters tırnak içindeki nokta'lı adlar` (ör. `karargah.baslik`) metin anahtarlarıdır; metnin kendisi `08-metinler.md` dosyasındadır. Ekran dosyalarında metin örnek olarak yazılmışsa bile geçerli olan 08'deki metindir.
- **VARSAYIM** etiketi: bugünkü kodda ya da tasarım yönünde kesinleşmemiş bir kural için önerilen varsayılan. Her VARSAYIM, `09` dosyasında bir açık soru olarak (S-numarasıyla) listelenir. Karar verilene kadar geliştirici varsayılanı uygular.
- **YENİ VERİ** etiketi: bugünkü kodda karşılığı olmayan veri ihtiyacı. Tablo/sütun tasarımı arka uç geliştiricisine aittir; burada yalnızca "ekran şunu bilmeli" yazılır.
- Ekran kimlikleri (`E10`, `K01` vb.) `01-ekran-envanteri.md` dosyasında tanımlıdır.

---

## 1. Oyun döngüsü

Charmoji'de oyuncu, gerçek hayattaki alışkanlıklarını **görev** olarak tanımlar. Görevi tamamladıkça karakteri güçlenir (XP, yetenek seviyesi, karakter seviyesi), altın kazanır ve bu altınla karakterini süsler. Arkadaşlarıyla haftalık XP yarışına girer.

### 1.1 Günlük döngü (1–3 dakikalık oturum)

1. Oyuncu uygulamayı açar → **Karargah**'ta karakterini ve bugünkü ilerlemesini görür ("3/5 görev").
2. Yaptığı görevleri işaretler → her işaretlemede anında "+XP" uçar, yetenek barı dolar, karakter sevinir.
3. Seri sayaçları bir gün artar; 7, 14, 21, 28. günlerde **seri bonusu** yükselir (×1,25 → ×2).
4. Günlük **meydan okumalar** (bugünkü kodda "günlük görevler") tamamlandıkça "Ödülü Al" ile altın toplanır.
5. Günün tüm görevleri bitince **günlük sandık** açılır → ekstra altın.
6. Gün sonunda (00:00, **VARSAYIM S1**: Europe/Istanbul) yeni gün başlar; işaretlenmeyen görevlerin serisi bir sonraki günü kaçırırsa sıfırlanır.

**Neden geri gelir?** Seriyi kaybetmemek (kayıp korkusu, yükselen çarpan), sandığı açmak (her gün kapanmamış bir ödül), karakterinin "bugün mutlu" hâlini görmek.

### 1.2 Haftalık döngü

1. Her hafta (**VARSAYIM S4**: Pazartesi 00:00 – Pazar 23:59, Europe/Istanbul) liderlik tablosu sıfırlanır.
2. Oyuncu ve arkadaşları bu hafta kazandıkları XP ile sıralanır (**VARSAYIM S3**).
3. Lonca sekmesinde sıralama, "senden önceki kişiye {fark} XP" bilgisi ile görünür.

**Neden geri gelir?** Arkadaşını geçmek, haftanın sonunda zirvede olmak; pazartesi herkese yeni bir şans.

### 1.3 Uzun vadeli döngü

1. Yetenek seviyeleri yükselir; her seviye bir öncekinin iki katı XP ister (100, 200, 400, 800…). İleri seviyeler gerçek bir başarıdır.
2. Karakter seviyesi (yetenek seviyelerinin ortalaması, aşağı yuvarlanır) yükseldikçe Market'te yeni eşyaların **seviye kilidi** açılır.
3. Biriken altınla nadir ve efsanevi eşyalar alınır; karakter oyuncunun emeğinin görünür vitrini olur.
4. Dengeli gelişim ödüllendirilir: karakter seviyesi ortalama olduğu için tek yeteneği kasmak yetmez, oyuncu farklı yeteneklere görev eklemeye teşvik edilir.

**Neden geri gelir?** Kilitli bir eşyayı açmak, karakterini tamamlamak, beş yeteneği dengelemek.

### 1.4 Oyun kuralları özeti (bugünkü koddan, değiştirilmeden)

| Kural | Değer | Nerede görünür |
|---|---|---|
| Görev taban ödülü | 10 XP | Görev kartı, görev formu |
| Seri | Art arda günlerde tamamlanınca +1 | Görev kartı, Karargah |
| Seri bonusu (çarpan) | `1 + floor(seri / 7) × 0,25`, en fazla ×2 | Görev kartı, seri bilgisi modalı |
| Kazanılan XP | `round(10 × çarpan)` (çarpan, işaretlemeden **sonraki** seriyle hesaplanır) | Görev kartı, uçan "+XP" |
| Yetenek seviyesi | 1. seviye → 2. seviye için 100 XP; her seviyede gereken XP ikiye katlanır | Karakter, Karargah |
| Karakter seviyesi | Beş yetenek seviyesinin ortalaması, aşağı yuvarlanır | Üst bar, Karargah, Market kilidi, Lonca |
| Meydan okuma ödülü | Altın; tamamlanınca elle alınır | Görevler sekmesi |
| Eşya | Fiyat (altın) + gereken karakter seviyesi; tür: Şapka, Gözlük, Maske, Kolye | Market, Çanta |
| Kuşanma | Tür başına tek eşya | Çanta, Karakter |
| Arkadaşlık | Arama, istek gönderme, kabul/red, öneriler | Lonca |

Seri bonusu tablosu (arayüzde "Seri bilgisi" modalında aynen gösterilir):

| Seri (gün) | Çarpan | Görev başına XP |
|---|---|---|
| 1–6 | ×1 | 10 |
| 7–13 | ×1,25 | 13 (12,5 yarımdan yukarı yuvarlanır; bugünkü PHP `round` davranışı) |
| 14–20 | ×1,5 | 15 |
| 21–27 | ×1,75 | 18 (17,5 yuvarlanır) |
| 28+ | ×2 | 20 |

---

## 2. Terimler sözlüğü

Kural: Arayüzde **yalnızca** "Arayüz terimi" sütunu kullanılır. Kod terimi hiçbir metinde görünmez.

| Kod terimi (bugün) | Arayüz terimi | Not |
|---|---|---|
| Alışkanlık (`Aliskanliklar`, `KullaniciAliskanliklari`) | **Görev** | Kullanıcının kendi tanımladığı, her gün tekrarlanan iş |
| Takip kaydı / işaretleme (`TakipKayitlari`, `durum=1`) | **Tamamla** / **tamamlandı** | Fiil: "Görevi tamamla" |
| İşareti kaldırma (`durum=0`) | **Geri al** | |
| Günlük görev (`GorevTanimlari`, `KullaniciGunlukGorevleri`) | **Meydan okuma** (başlıkta "Günlük Meydan Okumalar") | "Görev" ile karışmasın diye ad değişti (**VARSAYIM S2**) |
| Ödül alma (`claim_quest_id`) | **Ödülü al** | |
| Bakiye (`Bakiye`) | **Altın** | Her zaman tam sayı gösterilir |
| Coin, para, bakiye (eski metinler) | **Altın** | "Coin" kelimesi kaldırılır |
| Puan, ToplamPuan, XP | **XP** | |
| Stat, StatTuru | **Yetenek** | Beş yetenek aşağıda |
| `Zeka` | Zeka | |
| `Guc` | **Güç** | Arayüzde Türkçe karakterle |
| `Sosyallik` | Sosyallik | |
| `Disiplin` | Disiplin | |
| `Yaraticilik` | **Yaratıcılık** | Arayüzde Türkçe karakterle |
| Stat seviyesi | **Yetenek seviyesi** | "Zeka Sv. 3" |
| Genel seviye, ortalama level, Lvl | **Seviye** (gerektiğinde "Karakter seviyesi") | Kısa biçim: "Sv. {n}" |
| Seri (`Seri`) | **Seri** | "{seri} günlük seri" |
| Çarpan | **Seri bonusu** | Gösterim: "×1,25" |
| Market eşyası (`MarketEsyalari`) | **Eşya** | |
| Eşya türü (`EsyaTuru`) | **Tür** (Şapka, Gözlük, Maske, Kolye) | |
| Gereken seviye (`GerekenSeviye`) | **Seviye kilidi** / "Sv. {n} gerekir" | |
| Nadirlik (yeni) | **Nadirlik**: Sıradan, Nadir, Efsanevi | YENİ VERİ |
| Envanter (`KullaniciEnvanteri`) | **Çanta** | |
| Kuşanmak (`KusandiMi`) | **Kuşan** / **Çıkar** / "Kuşanıldı" | |
| Satın almadan deneme (yeni) | **Dene** | |
| Avatar, AvatarVerisi | **Karakter** / **Görünüm** | "Avatar" kelimesi arayüzde kullanılmaz |
| Avatar stüdyosu (`avatar.php`) | **Görünüm** (Karakter sekmesi altında) | |
| Panel / profil (`userpage.php`) | **Karargah** | |
| Arkadaş, arkadaşlık (`Arkadasliklar`) | **Arkadaş**, **arkadaşlık isteği** | |
| Topluluk önerileri | **Maceracı Bul** (bölüm: "Önerilen maceracılar") | |
| Kullanıcı (`Kullanicilar`) | **Maceracı** (yalnızca tanıtıcı metinlerde); ad alanında **Kahraman adı** | |
| Ad (`Ad`) | **Kahraman adı** | Diğer oyunculara görünen ad |
| Günlük sandık (yeni) | **Günlük sandık** | |
| Haftalık liderlik (yeni) | **Haftalık sıralama** | |
| Seviye atlama | **Seviye atladın** | |
| Hata (genel) | **Bir şeyler ters gitti** | Teknik hata metni asla gösterilmez |

---

## 3. Bilgi mimarisi ve gezinme

### 3.1 Harita

```
Tanıtım (E00) ──► Giriş (E01) ◄──► Kayıt (E02)
                     │                 │
                     │                 └─► Karşılama (E03a → E03b → E03c)
                     ▼                                   │
┌──────────────────── Oturum açık uygulama ◄─────────────┘
│  Üst bar (G02): Seviye rozeti · sekme başlığı · Altın
│
│  [Karargah E10]  [Görevler E20]  [Market E30]  [Lonca E40]  [Karakter E50]   ◄ alt menü (G01)
│     │               │               │             │              │
│     ├ Sandık M01    ├ Görev formu   ├ Eşya detayı ├ Liderlik     ├ Görünüm E51
│     ├ Seri bilgisi  │   E21         │   + Dene    │   (varsayılan)├ Çanta E52
│     │   M02         ├ Sil onayı M03 │   E31       ├ Arkadaşlar   └ Çıkış onayı M08
│     └ Görev kartı   ├ Geri al M04   └ Satın al    ├ İstekler
│        (hızlı       └ Seri bilgisi    onayı M05   ├ Maceracı Bul
│         tamamla)       M02                        └ Arkadaş profili E44
│                                                       └ Çıkar onayı M06
│
│  Her yerde: Kutlama anları K01–K08, bildirim (toast) G03, bağlantı bandı G04
```

### 3.2 Gezinme kuralları

- **Alt menü (G01)**: beş sekme her zaman görünür (modal açıkken hariç). Sıra sabittir: Karargah, Görevler, Market, Lonca, Karakter (`ortak.menu_karargah`, `ortak.menu_gorevler`, `ortak.menu_market`, `ortak.menu_lonca`, `ortak.menu_karakter`). Etkin sekme altın renkli ikon + etiket; diğerleri ikincil renk.
- **Üst bar (G02)**: solda seviye rozeti (`ortak.seviye_kisa`, erişilebilir adı `ortak.seviye_rozeti_etiketi`; dokunulunca Karakter sekmesine gider), ortada sekme başlığı (piksel font), sağda altın ("1.250" + altın ikonu, erişilebilir adı `ortak.altin_etiketi`; dokunulunca Market'e gider). Alt ekranlarda solda `ortak.geri` düğmesi seviye rozetinin yerini alır.
- **Rozet (badge) noktaları** alt menüde: Görevler'de alınmamış meydan okuma ödülü varsa altın nokta (`ortak.rozet_odul_etiketi`); Lonca'da bekleyen arkadaşlık isteği varsa sayı rozeti, en fazla "9+" (`ortak.rozet_istek_etiketi`); Karargah'ta sandık hazırsa altın nokta (`ortak.rozet_sandik_etiketi`). Rozetlerin anlamı sekmenin erişilebilir adına eklenir.
- **Giriş sonrası açılış**: her zaman Karargah. Kayıt sonrası: Karşılama, ardından Karargah.
- **Oturum yoksa**: oturum gerektiren her sayfa Giriş'e yönlendirir; giriş sonrası istenen sayfaya geri dönülür (**YENİ VERİ**: dönüş adresi).
- **Geri tuşu**: alt ekranlar (E21, E31, E44, E51, E52) tarayıcı geri tuşuyla kapanır; modallar Esc ile ve arka plana dokunarak kapanır (kutlama modalı K03 hariç; onu yalnızca düğme kapatır).
- **Masaüstü (≥ 768 px)**: içerik en fazla 640 px genişlikte ortalanır; alt menü ekranın altında aynı kalır. Ayrı masaüstü yerleşimi bu dalgada yok.
- Sayfa adları (öneri, bağlayıcı değil): `karargah.php`, `gorevler.php`, `market.php`, `lonca.php`, `karakter.php`, `giris.php`, `kayit.php`, `karsilama.php`.

### 3.3 Ortak bileşenler (tüm ekran dosyaları bunlara atıf yapar)

| Kimlik | Bileşen | Davranış |
|---|---|---|
| G01 | Alt menü | 64 px yükseklik + cihaz güvenli alanı; her sekme en az 64×56 px dokunma alanı; `<nav aria-label>` = `ortak.alt_menu_etiketi`; etkin sekmede `aria-current="page"`. |
| G02 | Üst bar | 56 px; sabit. Seviye rozeti ve altın canlı güncellenir (bkz. K01, K06). |
| G03 | Bildirim (toast) | Alt menünün hemen üstünde, en fazla 1 tane görünür; yenisi gelince eskisi kaybolur. Bilgi/başarı: 3 sn; hata: 5 sn; "Geri al" içeren: 5 sn. `role="status"` (hata için `role="alert"`). Kapat düğmesi 44×44 px. |
| G04 | Bağlantı bandı | Tarayıcı çevrimdışı olduğunda üst barın altında sabit band: `ortak.cevrimdisi_bandi`. Çevrimiçi olunca band 2 sn `ortak.cevrimici_geri_geldi` gösterip kaybolur. Çevrimdışıyken veri değiştiren tüm düğmeler devre dışıdır ve dokunulursa G03 ile `ortak.cevrimdisi_islem` gösterilir. |
| G05 | İskelet yükleyici | Sayfa sunucuda çizildiği için tam sayfa yükleme durumu yoktur. Yalnızca sonradan istekle dolan bölümler (Lonca listeleri, arama, Market filtreleri) için panel renginde, 1,2 sn'lik nabız animasyonlu gri kutular. Hareket azaltmada nabız yok, sabit gri. |
| G06 | Düğme yükleme hâli | İstek sürerken düğme metni yerinde kalır, solunda dönen ikon; düğme devre dışı; `aria-busy="true"`. İstek 10 sn'de dönmezse zaman aşımı → "istek başarısız" durumu. |
| G07 | Onay alt sayfası (bottom sheet) | Tarayıcının `confirm()`/`alert()` pencereleri **hiç kullanılmaz**; yerine alttan açılan panel: başlık, açıklama, birincil + ikincil düğme. Odak açılınca birincil olmayan (güvenli) düğmeye gider. |
| G08 | Oturum sona erdi | Bir istek "oturum kapalı" dönerse: G03 yerine tam ekran olmayan modal `ortak.oturum_bitti_baslik` + `ortak.oturum_bitti_aciklama` + `ortak.tekrar_giris_yap` düğmesi → Giriş. |

### 3.4 Ortak durum sözleşmesi

Her ekran dosyası altı durumu şu anlamlarda kullanır:

| Durum | Anlam |
|---|---|
| Boş | Gösterilecek veri yok (ör. hiç görev yok). |
| Yükleniyor | Veri ya da işlem sonucu bekleniyor. Sunucuda çizilen sayfalarda yalnızca işlemler ve sonradan dolan bölümler için. |
| Hata | Sunucu işlemi reddetti ya da beklenmeyen yanıt döndü (ör. "yetersiz altın", sunucu hatası). |
| İstek başarısız / çevrimdışı | İstek sunucuya ulaşamadı ya da zaman aşımına uğradı. Arayüz isteği yapmadan önceki hâline döner, G03 ile `ortak.istek_basarisiz` + "Tekrar dene". |
| Kilitli | Kullanıcı bir şeyi henüz yapamaz (seviye kilidi, sandık kapalı vb.). |
| Başarı | İşlem tamamlandı; geri bildirim gösterilir. |

Genel kural: **sunucu onaylamadan ödül gösterilmez.** "+XP", altın artışı, seviye atlama yalnızca başarılı yanıttan sonra oynatılır. Onay kutusunun dolması gibi basılma tepkileri hemen gösterilir.

### 3.5 Görsel belirteçler (renk, yazı, boyut)

| Belirteç | Değer | Kullanım | Kontrast (zemin #17142A / panel #221E3A) |
|---|---|---|---|
| `zemin` | #17142A | Sayfa arka planı | – |
| `panel` | #221E3A | Kart, alt sayfa, alt menü | – |
| `kenarlik` | #3A3460 | Yalnızca süs amaçlı ayraçlar | 1,57 / 1,4 (metin ve etkileşim sınırı için **kullanılmaz**) |
| `kenarlik-etkin` | #6E66A3 | Giriş kutusu, boş onay kutusu, boş eşya yuvası sınırı | 3,5 / 3,1 (etkileşimli öğe için gereken 3:1 sağlanır) |
| `altin` | #F2C14E | Vurgu, birincil düğme zemini, altın miktarı, etkin sekme | 10,7 / 9,5 |
| `metin` | #F4F1FF | Ana metin | 16,1 / 14,4 |
| `metin-ikincil` | #B8B2D9 | Açıklama, etiket | 8,9 / 7,9 |
| `metin-soluk` | #8E87B5 | Yalnızca 14 px ve üzeri yardımcı metin | 5,4 / 4,8 |
| `basari` | #5BD69A | Başarı, "Tamamlandı" | 9,9 / 8,8 |
| `hata` | #FF7A7A | Hata metni, tehlikeli işlem | 7,1 / 6,3 |
| Birincil düğme metni | #17142A üzerine #F2C14E zemin | | 10,7 |

Yetenek renkleri (etiket, bar dolgusu, kart sol şeridi):

| Yetenek | Renk | İkon (piksel) | Kontrast panel üzerinde |
|---|---|---|---|
| Zeka | #4EA8F2 | Kitap | 6,2 |
| Güç | #FF7A7A | Dambıl | 6,3 |
| Sosyallik | #FF8FC7 | Konuşma balonu | 7,6 |
| Disiplin | #5BD69A | Kum saati | 8,8 |
| Yaratıcılık | #C08CFF | Fırça | 6,4 |

Nadirlik renkleri (eşya çerçevesi + etiket): Sıradan #C0C0D0 (8,9), Nadir #4EA8F2 (6,2), Efsanevi #C08CFF (6,4) + efsanevide yavaş parıltı (hareket azaltmada yok). Nadirlik **yalnızca renkle** anlatılmaz; her zaman yazılı etiket (`market.nadirlik_siradan` vb.) da bulunur.

Yazı: başlıklar Pixelify Sans (en az 18 px; gövde metninde kullanılmaz), gövde ve tüm sayılar Inter (sayılarda `tabular-nums`). Gövde en az 16 px, yardımcı metin en az 13 px.
Dokunma hedefi: en az 44×44 px; liste satırları en az 56 px yükseklik. Köşe yuvarlaklığı 8 px (piksel hissi için küçük tutulur).

### 3.6 Sayı ve biçim kuralları

- Sayılar Türkçe biçimde: binlik ayırıcı nokta (`1.250`), ondalık virgül (`×1,25`).
- Altın her zaman tam sayı (aşağı yuvarlanmış), ondalık gösterilmez.
- XP: "{mevcut} / {gereken} XP" (ör. `40 / 200 XP`). Mevcut XP o seviyedeki XP'dir, toplam değil.
- Tarih: "11 Kasım" (gün + ay adı); yıl yalnızca farklı yılsa.
- Seviye kısa biçim: "Sv. 4"; uzun biçim: "Seviye 4".

---

## 4. Ton ve ses

**Kişilik**: Yanında yürüyen neşeli bir yol arkadaşı. Masal anlatıcısı değil, koç değil; "sen" diye konuşur, kısa keser, abartmaz.

### 4.1 Yazım kuralları

1. **"Sen" dili**, samimi ama saygılı. "Lütfen" yalnızca hata ve izin metinlerinde.
2. **Kısa**: düğmeler 1–3 kelime, fiil ile başlar ("Görev ekle", "Ödülü al", "Dene"). Başlıklar en fazla 4 kelime. Açıklamalar en fazla 2 kısa cümle.
3. **RPG tadı, ama anlaşılır**: Sekme ve bölüm adları oyunsu (Karargah, Lonca, Meydan okuma, Sandık); işlem fiilleri gündelik ("Kaydet", "Sil", "Geri al"). Kullanıcı hiçbir zaman ne olacağını tahmin etmek zorunda kalmamalı.
4. **Suçlama yok**: Seri bozulduğunda, görev kaçırıldığında ya da hata olduğunda kullanıcı suçlanmaz. "Serini kaybettin!" değil, "Yeni bir seri başlatmak için harika bir gün."
5. **Ünlem tutumlu**: Yalnızca kutlama metinlerinde ve en fazla bir tane. Hata metinlerinde hiç.
6. **Emoji yok**: Metinlerde emoji kullanılmaz; duygu ikon ve piksel çizimlerle verilir (ekran okuyucu ve tutarlılık için).
7. **Teknik dil yok**: "Veritabanı", "JSON", "parametre", "ID" asla görünmez. Hata metni: ne oldu + ne yapabilirsin.
8. **Büyük harf**: Cümle düzeni ("Görev ekle"), tamamı büyük harf kullanılmaz. Piksel başlıkta da cümle düzeni (görsel vurgu fontla verilir). Türkçe büyük harf dönüşümü gerekirse `İ/ı` kuralına uyulur (CSS `text-transform` kullanılacaksa `lang="tr"` zorunlu).
9. **Değişkenler**: Metinlerde `{degisken}` biçiminde. Türkçe ek uyumu sorunu yaşamamak için **değişkenden hemen sonra ek getirilmez**; cümle eke gerek kalmayacak şekilde kurulur ("{ad} seni arkadaş olarak eklemek istiyor" doğru; "{ad}'ın isteği" yanlış). Sayıdan sonra isim tekil kalır ("3 görev", "5 gün").
10. **Kahraman adı** kullanıcı girdisidir; her yerde metin olarak kaçışlanarak (escape) gösterilir.

### 4.2 Örnek ton karşılaştırması

| Durum | Doğru | Yanlış |
|---|---|---|
| Altın yetmiyor | "{eksik} altın daha lazım. Görevlerine devam!" | "Yetersiz bakiye." |
| Sunucu hatası | "Bir şeyler ters gitti. Birazdan tekrar dene." | "Hata: Veritabanı hatası." |
| Seri bozuldu | "Yeni bir seri başlatmak için harika bir gün." | "Serini kaybettin!" |
| Boş liste | "Henüz görevin yok. İlk görevini ekle, maceran başlasın." | "Kayıt bulunamadı." |
