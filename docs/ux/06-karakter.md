# 06 · Karakter (E50), Görünüm (E51), Çanta (E52), çıkış (M08)

Bugünkü karşılığı: `view/userpage.php` sol sütun (karakter kartı, yetenek çubukları, "Yetenek Ağacı" radar grafiği) ve `view/avatar.php` ("Avatar Stüdyosu": Görünüm ve Envanter sekmeleri); işlemler `view/ajax_islem.php` (`save_avatar_colors`, `equip_item_id`). Çıkış: `service/logout.php`.

## 1. Amaç

Karakter sekmesi oyuncunun **kim olduğunu** gösterir: karakterin tam boy hâli, kuşandığı eşyalar, beş yeteneğin ayrıntılı ilerlemesi ve hesabı. Buradan görünüm değiştirilir ve çantadaki eşyalar kuşanılır.

Bugünkü radar grafiği ("Yetenek Ağacı") kaldırılır: mobilde okunması zor, aynı bilgi yetenek çubuklarında var. (Geri getirilmesi 09'da öneri.)

## 2. İçerik hiyerarşisi ve yerleşim (E50)

```
┌────────────────────────────────────────┐
│ [Sv. 4]         Karakter       ◉ 1.250 │  G02
├────────────────────────────────────────┤
│        [ KARAKTER 240 px + eşyalar ]   │  1  karakter (gölgeli, nefes animasyonu)
│              {Kahraman adı}            │  2  ad (piksel başlık)
│               Seviye 4                 │     ortak.seviye_uzun
│  Seviye 5 için 3 yetenek seviyesi daha │     ortak.sonraki_seviye
│ [   Görünüm   ]   [    Çanta    ]      │  3  karakter.gorunum_dugme / karakter.canta_dugme
├────────────────────────────────────────┤
│ Kuşanılanlar                           │  4  karakter.yuvalar_baslik
│ [Şapka] [Gözlük] [Maske] [Kolye]       │     4 yuva, 64×64 px
│ Büyücü…   Boş     Boş    Altın…        │
├────────────────────────────────────────┤
│ Yetenekler                             │  5  karakter.yetenekler_baslik
│ ┌────────────────────────────────────┐ │
│ │ [kitap] Zeka            Sv. 3   (v)│ │     yetenek kartı (açılır)
│ │ ▓▓▓▓▓▓▓░░░░░░░░░  140 / 400 XP     │ │
│ │ ── açıkken ──                      │ │
│ │ Seviye 4 için 260 XP               │ │     karakter.yetenek_kalan
│ │ Bu yeteneğe bağlı 2 görev          │ │     karakter.yetenek_gorev_sayisi | karakter.yetenek_gorev_yok
│ │                       [Görev ekle] │ │     karakter.yetenek_gorev_ekle
│ └────────────────────────────────────┘ │
│ ... (Güç, Sosyallik, Disiplin, Yaratıcılık)
│ Karakter seviyen yeteneklerinin…       │     karakter.yetenek_ipucu_denge
├────────────────────────────────────────┤
│ Hesap                                  │  6  karakter.hesap_baslik
│ Kahraman adı   Ayşe                    │     karakter.hesap_kahraman_adi
│ E-posta        a***@ornek.com          │     karakter.hesap_eposta (yalnızca kendisine)
│ [            Çıkış yap            ]    │     karakter.cikis_dugme (ikincil)
├────────────────────────────────────────┤
│                 G01                    │
└────────────────────────────────────────┘
```

Ayrıntılar:

- **Yetenek kartları** sabit sırada: Zeka, Güç, Sosyallik, Disiplin, Yaratıcılık. Kapalıyken: ikon, ad, `ortak.seviye_kisa`, bar (yetenek renginde), `ortak.xp_ilerleme`. Kart başlığına dokununca açılır/kapanır (aynı anda birden fazla açık olabilir). Karargah'taki mini bardan gelinirse ilgili kart açık ve görünür alana kaydırılmış gelir.
- `karakter.yetenek_kalan`: `kalan = gereken − mevcut` (o seviyedeki XP).
- "Görev ekle" → E21, yetenek önceden seçili.
- **Eşya yuvaları**: dolu yuva eşya ikonu + adı (1 satır, üç nokta); boş yuva `yuva-bos-{tur}` silüeti + `karakter.yuva_bos`. Yuvaya dokunma → E52, o türe filtrelenmiş.
- **E-posta** maskelenmiş gösterilir (ilk harf + `***` + `@` sonrası); yalnızca bu ekranda, yalnızca oyuncunun kendisine. Hesap bilgisi düzenleme bu dalgada yok (09, Öneri).

## 3. Kullanıcı akışları

### 3.1 Bir yeteneği incelemek ve görev eklemek

1. Oyuncu "Sosyallik" kartına dokunur → kart açılır: `karakter.yetenek_kalan` + `karakter.yetenek_gorev_yok` + "Görev ekle".
2. "Görev ekle" → E21 (Sosyallik seçili) → kaydedince E50'ye geri dönülür, kart `karakter.yetenek_gorev_sayisi` ile güncellenir.

### 3.2 Görünümü değiştirmek — §5.

### 3.3 Eşya kuşanmak / çıkarmak — §6.

### 3.4 Çıkış yapmak

1. "Çıkış yap" → M08 (G07): `karakter.cikis_onay_baslik`, `karakter.cikis_onay_aciklama`, birincil `karakter.cikis_onay_dugme`, ikincil `ortak.vazgec` (varsayılan odak).
2. Onay → oturum kapanır → E00 (Tanıtım), G03 `tanitim.cikis_bildirim`.
3. Çevrimdışıyken çıkış düğmesi devre dışı (`ortak.cevrimdisi_islem`).

## 4. Etkileşim ve geri bildirim anları

| Tetik | Ne olur | Süre | Hareket azaltma |
|---|---|---|---|
| Yetenek kartını aç/kapa | Yükseklik açılır, ok ikonu 180° döner. | 200 ms | Anında |
| E50'ye K03 sonrası gelmek | Seviye satırı 1 sn altın parıltı. | 1 sn | Yok |
| Karaktere dokunma | Karargah'taki el sallama tepkisi (02 §3.4) burada balonsuz. | 600 ms | Yok |

## 5. Görünüm (E51)

```
┌────────────────────────────────────────┐
│ [<]           Görünüm         [Kaydet] │  gorunum.baslik; Kaydet değişiklik yoksa devre dışı
├────────────────────────────────────────┤
│      [ KARAKTER önizleme 200 px ]      │  yapışkan üst alan, kaydırınca 120 px'e küçülür
├────────────────────────────────────────┤
│ Ten rengi                              │  gorunum.ten
│ (o)(o)(o)(o)(o)(o)  [+ Özel renk]      │  6 hazır renk (44 px daire) + özel renk
│ Saç rengi                              │  gorunum.sac
│ (o)(o)(o)(o)(o)(o)  [+ Özel renk]      │
│ Göz rengi                              │  gorunum.goz
│ ...                                    │
│ Kıyafet rengi                          │  gorunum.kiyafet
│ ...                                    │
├────────────────────────────────────────┤
│ [              Kaydet              ]   │  ortak.kaydet (sabit alt düğme; mobil)
└────────────────────────────────────────┘
```

Hazır renkler (ilk sütun varsayılan değerlerdir; bugünkü `avatar_renderer.php` varsayılanlarıyla aynı):

| Bölge | 1 | 2 | 3 | 4 | 5 | 6 |
|---|---|---|---|---|---|---|
| Ten (`skinColor`) | #F3D2C1 `gorunum.renk_ten_1` | #FDE7D6 `gorunum.renk_ten_2` | #E3B48F `gorunum.renk_ten_3` | #C68A5E `gorunum.renk_ten_4` | #94603D `gorunum.renk_ten_5` | #5E3A24 `gorunum.renk_ten_6` |
| Saç (`hairColor`) | #2D3436 `gorunum.renk_sac_1` | #6B4226 `gorunum.renk_sac_2` | #E6C46A `gorunum.renk_sac_3` | #B5452B `gorunum.renk_sac_4` | #E8E4F0 `gorunum.renk_sac_5` | #6C5CE7 `gorunum.renk_sac_6` |
| Göz (`eyeColor`) | #000000 `gorunum.renk_goz_1` | #6B4226 `gorunum.renk_goz_2` | #A0782C `gorunum.renk_goz_3` | #3E8E5A `gorunum.renk_goz_4` | #3A7BD5 `gorunum.renk_goz_5` | #8E8E9E `gorunum.renk_goz_6` |
| Kıyafet (`topColor`) | #6C5CE7 `gorunum.renk_kiyafet_1` | #3A7BD5 `gorunum.renk_kiyafet_2` | #3E8E5A `gorunum.renk_kiyafet_3` | #D64545 `gorunum.renk_kiyafet_4` | #F2C14E `gorunum.renk_kiyafet_5` | #2D3436 `gorunum.renk_kiyafet_6` |

- Seçili renk dairesi: 3 px altın halka + içinde tik; `aria-checked="true"`.
- `gorunum.ozel_renk`: tarayıcının renk seçicisini açar; seçilen renk, satırın sonunda 7. daire olarak görünür ve seçili olur. Kayıtlı renk hazır renklerden biri değilse açılışta bu 7. daire dolu gelir.
- Önizleme her seçimde anında güncellenir (sunucu isteği yok).

### 5.1 Akış

1. Oyuncu renk seçer → önizleme değişir, "Kaydet" etkinleşir.
2. "Kaydet" → G06 → başarı: G03 `gorunum.kaydedildi_bildirim`; "Kaydet" yeniden devre dışı; E50'ye dönülmez (oyuncu devam edebilir).
3. Kaydetmeden geri: değişiklik varsa M07 (`ortak.kaydedilmedi_baslik`, `ortak.kaydedilmedi_aciklama`, birincil `ortak.kaydedilmedi_kal`, ikincil `ortak.kaydedilmedi_cik`). "Kaydetmeden çık" → E50, değişiklikler atılır.
4. Hata: sunucu rengi reddederse (geçersiz değer) `gorunum.hata_gecersiz_renk`; diğer `gorunum.hata_kaydedilemedi`; seçimler korunur.
5. İstek başarısız: seçimler korunur, G03 `ortak.istek_basarisiz` + `ortak.tekrar_dene`.

Sunucu yalnızca `#RRGGBB` biçimindeki renkleri kabul etmelidir (bugünkü kod doğrulamıyor; rapora bakınız).

## 6. Çanta (E52)

```
┌────────────────────────────────────────┐
│ [<]            Çanta           ◉ 1.250 │  canta.baslik
├────────────────────────────────────────┤
│   [ KARAKTER önizleme 160 px ]         │  seçili eşya önizlemede (canta.onizleme_etiketi)
│   Bir eşya seç, üzerinde gör.          │  canta.sec_ipucu (seçim yokken)
├────────────────────────────────────────┤
│ (Tümü) (Şapka) (Gözlük) (Maske) (Kolye)│  ortak.filtre_tumu, ortak.tur_sapka … ortak.tur_kolye
├────────────────────────────────────────┤
│ ┌──────┐ ┌──────┐ ┌──────┐             │  3 sütunlu ızgara
│ │[ikon]│ │[ikon]│ │[ikon]│             │  64 px ikon, nadirlik çerçevesi
│ │Büyücü│ │Güneş │ │Altın │             │
│ │✓Kuşan│ │      │ │      │             │  kuşanılmışsa ortak.kusanildi rozeti
│ └──────┘ └──────┘ └──────┘             │
├────────────────────────────────────────┤
│ [              Kuşan               ]   │  seçim varsa: ortak.kusan | ortak.cikar
└────────────────────────────────────────┘
```

### 6.1 Akış

1. Oyuncu bir eşyaya dokunur → eşya seçilir (altın kenarlık), önizlemede karakterin üzerinde görünür; alt düğme belirir: kuşanılmamışsa `ortak.kusan`, kuşanılmışsa `ortak.cikar`. Aynı türde başka eşya kuşanılmışsa düğmenin üstünde `ortak.ayni_tur_uyari`.
2. "Kuşan" → G06 → başarı: eşya kuşanılır (tür başına tek: aynı türdeki eski eşya otomatik çıkarılır), ızgarada rozetler güncellenir, ekran okuyucu `ortak.kusanildi_bildirim`; görsel bildirim olarak G03 `ortak.kusanildi_bildirim`.
3. "Çıkar" → G06 → başarı: eşya çıkarılır, G03 `ortak.cikarildi_bildirim`.
4. Hata: `ortak.hata_kusanilamadi` / `ortak.hata_cikarilamadi`; önizleme ve rozetler kayıtlı duruma döner.
5. Bugünkü koddan fark: eşya ekrana **sunucu onayından sonra** kalıcı olarak çizilir (bugün önce çizilip hata sessizce yutuluyor); kuşanılmış eşya çıkarılabilir (bugün çıkarma yok).

Bildirimdeki "Kuşan" eylemiyle (K07) gelindiyse ilgili eşya seçili ve kuşanma isteği zaten gönderilmiş olarak açılır.

## 7. Durumlar

| Durum | E50 Karakter | E51 Görünüm | E52 Çanta |
|---|---|---|---|
| Boş | Hiç kuşanılmış eşya yoksa dört yuva da boş silüet (hata değil). Görevi olmayan yetenek kartında `karakter.yetenek_gorev_yok`. | Geçerli değil (her zaman varsayılan renkler vardır). | `bos-canta` + `canta.bos_baslik` + `canta.bos_aciklama` + düğme `canta.bos_dugme` (→ E30). Filtre boşsa `bos-filtre` + `canta.filtre_bos`. |
| Yükleniyor | Sunucuda çizilir; geçerli değil. | "Kaydet"te G06. | Kuşan/Çıkar'da G06; ikonlar için G05 iskelet. |
| Hata | Sayfa verisi alınamazsa `ortak.sayfa_hatasi_baslik` + `ortak.sayfa_hatasi_aciklama` + `ortak.yeniden_yukle`. | `gorunum.hata_gecersiz_renk`, `gorunum.hata_kaydedilemedi` (önizlemenin altında hata kutusu). | `ortak.hata_kusanilamadi`, `ortak.hata_cikarilamadi`. |
| İstek başarısız / çevrimdışı | G04; "Çıkış yap" devre dışı. | G04; renk seçimi çalışır, "Kaydet" devre dışı (`ortak.cevrimdisi_islem`). Zaman aşımında `ortak.istek_basarisiz`. | G04; seçim ve önizleme çalışır, Kuşan/Çıkar devre dışı. |
| Kilitli | Geçerli değil. | Geçerli değil (tüm renkler ücretsiz ve açık). | Geçerli değil (çantadaki her eşya kuşanılabilir; seviye kilidi yalnızca satın almada). |
| Başarı | Geçerli değil (salt bilgi); çıkışta `tanitim.cikis_bildirim`. | `gorunum.kaydedildi_bildirim`. | `ortak.kusanildi_bildirim`, `ortak.cikarildi_bildirim`. |

## 8. Geri alma kuralları

- Görünüm: kaydedilene kadar her şey geri alınabilir (M07 ile atılır). Kaydedildikten sonra eski renkler yeniden seçilerek dönülür; ayrı "geri al" yok.
- Kuşanma/çıkarma: karşı işlemle geri alınır.
- Çıkış: M08 onayı; çıkış sonrası yeniden giriş gerekir.

## 9. Erişilebilirlik

- Sayfa başlığı (`<h1>`, görsel olarak üst barda): `karakter.baslik`; alt ekranlarda `gorunum.baslik`, `canta.baslik`.
- Karakter görseli `role="img"`, `aria-label` = `karakter.karakter_etiketi`.
- Yetenek kartı başlığı `button` + `aria-expanded`; bar `role="progressbar"` + `ortak.yetenek_bar_etiketi`.
- Eşya yuvaları: `button`; erişilebilir adı dolu yuvada `karakter.yuva_etiketi`, boşta `karakter.yuva_bos_etiketi`.
- Renk seçimi: her bölge bir `radiogroup` (`aria-label` = bölge adı), her daire `role="radio"` + `aria-label` = renk adı (§5 tablosundaki `gorunum.renk_…` anahtarları); ok tuşlarıyla gezilir. Seçili rengin adı görünür metin olarak da bölge başlığının yanında yazılır (yalnızca renkle anlatmamak için).
- Çanta ızgarası: her eşya `button` + `aria-pressed` (seçili), adı `canta.esya_etiketi`.
- Odak: E51/E52 açılınca başlığa; kapanınca açan düğmeye.
- Dokunma hedefleri: renk daireleri 44 px (aralarında 8 px), eşya kutuları en az 96×112 px, yuvalar 64 px.

## 10. Veri ihtiyacı

| Bilgi | Bugünkü kodda | Not |
|---|---|---|
| Kahraman adı, e-posta (maskeli gösterim için) | Var | |
| Görünüm renkleri | Var (`AvatarVerisi`) | Kayıt sırasında boşsa varsayılanlar |
| Karakter seviyesi, sonraki seviye için kalan yetenek seviyesi | Seviye var; kalan yeni | **YENİ VERİ** (hesaplanan) |
| Beş yeteneğin seviyesi, seviye içi XP, gereken XP | Var (hesaplanıyor) | |
| Her yeteneğe bağlı aktif görev sayısı | Yok | **YENİ VERİ** (hesaplanan) |
| Çanta: eşya kimliği, ad, tür, nadirlik, ikon, katman görseli, kuşanılmış mı | Kısmen (nadirlik, görseller yok) | **YENİ VERİ** |

| İşlem | Bugünkü kodda | Başarı yanıtı | Ret gerekçeleri |
|---|---|---|---|
| Görünümü kaydet | Var (`save_avatar_colors`) | Kaydedilen renkler | Geçersiz renk biçimi |
| Kuşan | Var (`equip_item_id`) | Kuşanılan ve (varsa) çıkarılan eşya | Sahip değil, eşya yok |
| Çıkar | Yok | Çıkarılan eşya | Kuşanılmamış |
| Çıkış yap | Var (`service/logout.php`) | E00'a yönlendirme + çıkış bildirimi | – |
