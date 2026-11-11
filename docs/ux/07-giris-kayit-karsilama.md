# 07 · Tanıtım (E00), Giriş (E01), Kayıt (E02), Karşılama (E03a–c)

Bugünkü karşılığı: `view/index.php` (tanıtım), `service/login.php` ve `service/register.php` (formlar), `service/auth.php` (işlem), `service/logout.php` (çıkış). Karşılama yeni.

Bu ekranlarda alt menü (G01) ve üst bar (G02) **yoktur**. Tümü koyu temadır; bugünkü açık/koyu tema düğmesi kaldırılır.

## 1. Tanıtım (E00)

### 1.1 Amaç ve hiyerarşi

Oyunu 10 saniyede anlatmak ve kayda yönlendirmek. Gerçek olmayan iddialar (rozetler, "100+ alışkanlık / 50+ kullanıcı / %99 motivasyon" sayıları, "Geleceği Kodluyoruz") ve konuyla ilgisiz stok fotoğraflar kaldırılır.

```
┌────────────────────────────────────────┐
│ [logo-charmoji]             Giriş yap  │  tanitim.giris_dugme (metin düğme)
├────────────────────────────────────────┤
│        [ tanitim-sahne 320×200 ]       │
│     Hayatını bir RPG gibi oyna         │  tanitim.slogan (piksel, 28 px)
│  Her gün yapmak istediklerini göreve…  │  tanitim.aciklama
│ [         Maceraya başla          ]    │  tanitim.basla_dugme (birincil) → E02
├────────────────────────────────────────┤
│ [yetenek ikonu] Görevlerini tamamla    │  tanitim.ozellik_1_baslik, tanitim.ozellik_1_aciklama
│ [sandık]        Karakterini güçlendir  │  tanitim.ozellik_2_baslik, tanitim.ozellik_2_aciklama
│ [kupa]          Loncanla yarış         │  tanitim.ozellik_3_baslik, tanitim.ozellik_3_aciklama
├────────────────────────────────────────┤
│ [       Maceraya başla        ]        │  (tekrar)
│ © Charmoji                             │
└────────────────────────────────────────┘
```

### 1.2 Akış ve durumlar

- Oturumu açık oyuncu E00'a gelirse Karargah'a yönlendirilir.
- Çıkış sonrası gelindiyse G03 `tanitim.cikis_bildirim` (3 sn).

| Durum | Ne görünür |
|---|---|
| Boş | Geçerli değil (statik içerik). |
| Yükleniyor | Geçerli değil (statik). Görseller yüklenene kadar alanları korunur (yerleşim kaymaz). |
| Hata | Geçerli değil (sunucu verisi yok). |
| İstek başarısız / çevrimdışı | Geçerli değil; bağlantılar tarayıcının normal davranışını izler. |
| Kilitli | Geçerli değil. |
| Başarı | Çıkış bildirimi `tanitim.cikis_bildirim`. |

## 2. Giriş (E01)

### 2.1 Hiyerarşi

```
┌────────────────────────────────────────┐
│ [<]                                    │  E00'a döner
│          [logo-charmoji]               │
│            Giriş yap                   │  giris.baslik
│      Kaldığın yerden devam et.         │  giris.alt_baslik
│ ┌ hata kutusu (varsa) ───────────────┐ │
│ E-posta veya kahraman adı              │  giris.kimlik_etiketi
│ [                                   ]  │
│ Şifre                                  │  giris.sifre_etiketi
│ [                          ] [göster]  │  giris.sifre_goster / giris.sifre_gizle
│ [            Giriş yap             ]   │  giris.gonder
│ Hesabın yok mu? Kayıt ol               │  giris.kayit_bagi → E02
└────────────────────────────────────────┘
```

### 2.2 Akış

1. Oyuncu kimlik ve şifreyi girer, "Giriş yap"a dokunur (ya da Enter).
2. İstemci boş alanları denetler; boşsa alan altında hata, istek gitmez.
3. G06; form sunucuya gönderilir.
4. Başarı: başka bir sayfadan yönlendirilmişse o sayfaya, değilse Karargah'a. Karşılama tamamlanmamışsa (kayıt sırasında yarım bırakılmışsa) kaldığı karşılama adımına.
5. Hata: sayfa yeniden çizilir, kimlik alanı dolu kalır, şifre boşaltılır, formun üstünde hata kutusu `giris.hata_yanlis`, odak hata kutusuna.
6. Oturum süresi dolduğu için gelindiyse formun üstünde bilgi kutusu `giris.oturum_doldu`.

Hangi alanın yanlış olduğu söylenmez (hesap varlığını sızdırmamak için). Kahraman adı benzersiz olduğundan (**VARSAYIM S15**) kahraman adıyla giriş belirsiz olmaz.

### 2.3 Durumlar

| Durum | Ne görünür | Metin |
|---|---|---|
| Boş | İlk açılış: alanlar boş, odak kimlik alanında. | – |
| Yükleniyor | "Giriş yap" düğmesinde G06; alanlar salt okunur. | `ortak.yukleniyor` |
| Hata (doğrulama) | Alan altında hata metni. | `giris.hata_bos_kimlik`, `giris.hata_bos_sifre` |
| Hata (sunucu) | Formun üstünde hata kutusu. | `giris.hata_yanlis`, `ortak.hata_genel` |
| İstek başarısız / çevrimdışı | G04 bandı; "Giriş yap" devre dışı; çevrimiçi olunca etkinleşir. | `ortak.cevrimdisi_bandi` |
| Kilitli | Bu dalgada yok (çok sayıda hatalı denemede kilit 09'da öneri). | – |
| Başarı | Yönlendirme; ayrı mesaj yok (Karargah selamı yeterli). | – |

## 3. Kayıt (E02)

### 3.1 Hiyerarşi

```
│            Maceraya katıl              │  kayit.baslik
│ Kahramanını oluştur, ilk görevine çık. │  kayit.alt_baslik
│ Kahraman adı                           │  kayit.ad_etiketi
│ [                                   ]  │
│ Diğer maceracılar seni bu adla görür.  │  kayit.ad_ipucu
│ E-posta                                │  kayit.eposta_etiketi
│ [                                   ]  │
│ Şifre                                  │  kayit.sifre_etiketi
│ [                          ] [göster]  │
│ En az 8 karakter.                      │  kayit.sifre_ipucu
│ Şifre tekrarı                          │  kayit.sifre_tekrar_etiketi
│ [                          ] [göster]  │
│ [          Hesap oluştur           ]   │  kayit.gonder
│ Zaten hesabın var mı? Giriş yap        │  kayit.giris_bagi → E01
```

### 3.2 Alan kuralları

| Alan | Kural | Hata |
|---|---|---|
| Kahraman adı | Zorunlu; kırpılır; 3–20 karakter; harf (Türkçe harfler dahil), rakam, boşluk, alt çizgi; büyük/küçük harf duyarsız benzersiz (**VARSAYIM S15**) | `kayit.hata_ad_bos`, `kayit.hata_ad_uzunluk`, `kayit.hata_ad_karakter`, `kayit.hata_ad_alinmis` |
| E-posta | Zorunlu; geçerli biçim; benzersiz | `kayit.hata_eposta_bos`, `kayit.hata_eposta_gecersiz`, `kayit.hata_eposta_kayitli` |
| Şifre | Zorunlu; en az 8 karakter (**VARSAYIM S16**) | `kayit.hata_sifre_kisa` |
| Şifre tekrarı | Şifreyle aynı | `kayit.hata_sifre_eslesmiyor` |

Doğrulama: alan odaktan çıkınca ve gönderimde. Gönderimde birden fazla hata varsa odak ilk hatalı alana gider ve ekran okuyucuya `kayit.hata_ozet` duyurulur. Kahraman adının alınmış olup olmadığı gönderimde sunucuda denetlenir (canlı denetim yok).

### 3.3 Akış

1. Oyuncu alanları doldurur → "Hesap oluştur" → G06.
2. Başarı: hesap oluşur, oturum açılır, E03a'ya gidilir. Yeni hesabın görünümü 1 numaralı seçeneklerle, beş yeteneği 0 XP ile başlar.
3. Hata: sayfa yeniden çizilir; şifre alanları boşaltılır, diğerleri korunur; ilgili alanın altında hata.

### 3.4 Durumlar

| Durum | Ne görünür | Metin |
|---|---|---|
| Boş | İlk açılış, odak kahraman adında. | – |
| Yükleniyor | "Hesap oluştur"da G06. | `ortak.yukleniyor` |
| Hata | §3.2 alan hataları; beklenmeyen sunucu hatası formun üstünde. | §3.2, `ortak.hata_genel` |
| İstek başarısız / çevrimdışı | G04; gönder düğmesi devre dışı. | `ortak.cevrimdisi_bandi` |
| Kilitli | Geçerli değil. | – |
| Başarı | E03a'ya yönlendirme. | – |

## 4. Karşılama (E03a → E03b → E03c)

Yalnızca kayıttan sonra bir kez gösterilir. Her adımın üstünde ilerleme noktaları ve `karsilama.adim`; sağ üstte `karsilama.atla` (E03a ve E03b'de). "Atla" bir sonraki adıma geçer; E03c atlanamaz (tek dokunuş). Yarıda bırakılırsa bir sonraki girişte kalınan adımdan devam edilir (**YENİ VERİ**: karşılama adımı / tamamlandı mı).

### 4.1 E03a · Karakterini oluştur

```
│ ● ○ ○  Adım 1/3                  Atla │
│        Karakterini oluştur            │  karsilama.karakter_baslik
│ Görünümünü seç. İstediğin zaman…      │  karsilama.karakter_aciklama
│      [ KARAKTER önizleme 200 px ]     │
│ Ten [▣][▣][▣][▣]                      │  06 §5'teki hazır seçenekler
│ Saç [▣][▣][▣][▣][▣][▣]                │
│ Göz [▣][▣][▣]                         │
│ Üst [▣][▣][▣][▣]                      │
│ [             Devam              ]    │  karsilama.devam
```

- Bileşen E51 ile aynı (06 §5: piksel büst, hazır seçenekler); bölge adları `gorunum.ten`, `gorunum.sac`, `gorunum.goz`, `gorunum.kiyafet`.
- "Devam" → görünüm kaydedilir (G06) → E03b. "Atla" → 1 numaralı seçenekler kalır → E03b.
- Kaydetme hatası: adımda kalınır, hata kutusu `gorunum.hata_kaydedilemedi`; istek başarısız: G03 `ortak.istek_basarisiz`.

### 4.2 E03b · İlk görevin

```
│ ● ● ○  Adım 2/3                  Atla │
│            İlk görevin                │  karsilama.gorev_baslik
│ Her gün yapmak istediğin bir şey…     │  karsilama.gorev_aciklama
│ (öneri çipleri)                       │  gorev_form.oneri_1 … gorev_form.oneri_5
│ Görev adı [                      ]    │  gorev_form.ad_etiketi
│ Hangi yeteneği geliştirir? (5 kart)   │  gorev_form.yetenek_etiketi
│ [          Görevi ekle           ]    │  karsilama.gorev_ekle
```

- Alanlar ve doğrulama E21 ile aynı (03 §5): `gorev_form.hata_ad_bos`, `gorev_form.hata_ad_uzun`, `gorev_form.hata_yetenek_yok`; sunucu hatası `gorev_form.hata_kaydedilemedi`.
- Öneri çipleri (`gorev_form.oneri_1`, `gorev_form.oneri_2`, `gorev_form.oneri_3`, `gorev_form.oneri_4`, `gorev_form.oneri_5`) burada belirgin, iki satır hâlinde gösterilir.
- "Görevi ekle" → görev oluşturulur → E03c. "Atla" → görev oluşturulmadan E03c (Karargah boş durumu sonra yönlendirir).

### 4.3 E03c · Nasıl oynanır

```
│ ● ● ●  Adım 3/3                       │
│           Nasıl oynanır?              │  karsilama.nasil_baslik
│ [karsilama-nasil-1] Görevini tamamla… │  karsilama.nasil_1
│ [karsilama-nasil-2] Her gün devam et… │  karsilama.nasil_2
│ [karsilama-nasil-3] Günün tüm…        │  karsilama.nasil_3
│ [        Maceraya başla          ]    │  karsilama.basla
```

- "Maceraya başla" → karşılama tamamlandı olarak işaretlenir → Karargah. E03b'de görev eklendiyse Karargah'ta o görev "Sıradaki görevler"de, karakter "hazır" ifadesinde görünür.

### 4.4 Karşılama durumları

| Durum | E03a | E03b | E03c |
|---|---|---|---|
| Boş | 1 numaralı seçenekler seçili. | Alanlar boş. | Geçerli değil (statik). |
| Yükleniyor | "Devam"da G06. | "Görevi ekle"de G06. | "Maceraya başla"da G06. |
| Hata | `gorunum.hata_kaydedilemedi` | `gorev_form.hata_ad_bos`, `gorev_form.hata_ad_uzun`, `gorev_form.hata_yetenek_yok`, `gorev_form.hata_kaydedilemedi` | `ortak.hata_genel` (yine de Karargah'a geçilir; karşılama bir sonraki girişte tekrar gösterilmez) |
| İstek başarısız / çevrimdışı | G04; "Devam" devre dışı; "Atla" çalışır. | G04; "Görevi ekle" devre dışı; "Atla" çalışır. | G04; düğme devre dışı. |
| Kilitli | Geçerli değil. | Geçerli değil. | Geçerli değil. |
| Başarı | E03b'ye geçiş (adım noktası dolar, 200 ms; hareket azaltmada anında). | E03c'ye geçiş. | Karargah. |

## 5. Geri alma kuralları

- Karşılamada geri tuşu bir önceki adıma döner (E03b → E03a); E03a'dan geri gidilmez (kayıt tekrarlanmaz). Önceki adımda yapılan seçim korunur.
- E03b'de eklenen görev daha sonra Görevler sekmesinden düzenlenip silinebilir.

## 6. Erişilebilirlik

- Her alanın görünür `<label>`'ı; ipucu ve hata metinleri `aria-describedby` ile bağlı; hatalı alan `aria-invalid="true"`.
- Uygun `autocomplete` değerleri: giriş kimliği `username`, şifre `current-password`; kayıtta kahraman adı `nickname`, e-posta `email`, şifreler `new-password`.
- Şifre göster/gizle `button` + `aria-pressed`, etiketi `giris.sifre_goster` / `giris.sifre_gizle`.
- Form hata kutusu `role="alert"`.
- Karşılama adım göstergesi metin olarak da okunur (`karsilama.adim`); her adım açılınca odak adım başlığına gider.
- Dokunma hedefleri: alanlar 48 px yükseklik, düğmeler 52 px.
- Enter formu gönderir; Tab sırası görsel sırayla aynı.

## 7. Veri ihtiyacı

| Bilgi / işlem | Bugünkü kodda | Not |
|---|---|---|
| Giriş: kimlik (e-posta ya da kahraman adı) + şifre | Var (`AuthManager::login`) | Kahraman adı benzersiz değil → **YENİ VERİ**: benzersizlik |
| Giriş sonrası dönüş adresi | Yok | **YENİ VERİ** |
| Oturum süresi doldu bilgisi | Yok | **YENİ VERİ** (yönlendirmede işaret) |
| Kayıt: kahraman adı, e-posta, şifre | Var (alan adı "Ad Soyad" olarak) | Alan anlamı "kahraman adı" olarak değişir; kurallar §3.2 |
| Kayıt hatasının hangi alana ait olduğu | Yok (tek genel mesaj) | **YENİ VERİ**: alan bazlı hata kodları |
| Karşılama adımı / tamamlandı mı | Yok | **YENİ VERİ** |
| Görünümü kaydet, görev ekle | Var | 06 ve 03 ile aynı işlemler |
| Çıkış | Var | E00'a yönlendirme + bildirim |
