# 03 · Görevler (E20), görev formu (E21) ve görev geri bildirimi (K01–K04, K08)

Bugünkü karşılığı: `view/userpage.php` orta sütun ("Görev Panosu", `#habitModal`) ve sağ sütundaki "Günlük Görevler" kutusu; işlemler `view/ajax_islem.php`.

Bu dosya **Görev kartı** bileşeninin ve **görev tamamlama geri bildiriminin (K01–K04)** tek kaynağıdır; Karargah (02) bunlara atıf yapar.

## 1. Amaç

Görevler sekmesi oyuncunun "görev defteri"dir: bugünkü tüm görevleri tamamlamak ve geri almak, yeni görev eklemek, görevleri düzenlemek/silmek ve günlük meydan okumaların altın ödüllerini toplamak.

## 2. İçerik hiyerarşisi ve yerleşim

```
┌────────────────────────────────────────┐
│ [Sv. 4]         Görevler       ◉ 1.250 │  G02
├────────────────────────────────────────┤
│ Bugün 3/5 tamamlandı      [+ Görev ekle]│ 1  gorevler.bugun_ozet + gorevler.ekle_dugme
│ ▓▓▓▓▓▓▓▓▓▓░░░░░░░                      │
├────────────────────────────────────────┤
│ Yapılacaklar (2)                       │ 2  gorevler.yapilacaklar_baslik
│ ┌────────────────────────────────────┐ │
│ │▌[ ] 30 sayfa kitap oku         [⋯] │ │    Görev kartı (§3)
│ │▌    [Zeka] +13 XP  (alev) 8 gün ×1,25│ │
│ └────────────────────────────────────┘ │
│ ┌────────────────────────────────────┐ │
│ │▌[ ] Bir arkadaşını ara         [⋯] │ │
│ │▌    [Sosyallik] +10 XP  Seri yok    │ │
│ │▌    Önceki serin: 12 gün. Yeni bir… │ │    (seri bozulduysa, §3.3)
│ └────────────────────────────────────┘ │
├────────────────────────────────────────┤
│ Tamamlananlar (3)                      │ 3  gorevler.tamamlananlar_baslik
│ ┌────────────────────────────────────┐ │
│ │▌[✓] Yatağını topla             [⋯] │ │
│ │▌    [Disiplin] +10 XP kazanıldı  (alev) 3 gün │
│ └────────────────────────────────────┘ │
├────────────────────────────────────────┤
│ Günlük meydan okumalar                 │ 4  gorevler.meydan_baslik (#meydan-okumalar çapası)
│ Yenilenmeye 5 sa 12 dk                 │
│ ┌────────────────────────────────────┐ │
│ │ [kapı] Günlük giriş       ◉ 10 altın│ │
│ │        1/1        [  Ödülü al  ]    │ │
│ └────────────────────────────────────┘ │
│ ┌────────────────────────────────────┐ │
│ │ [kılıç] 3 görev tamamla   ◉ 25 altın│ │
│ │        2/3 ▓▓▓▓▓▓░░░   Devam ediyor │ │
│ └────────────────────────────────────┘ │
│ Alınmayan ödüller gece yarısı kaybolur.│
├────────────────────────────────────────┤
│                 G01                    │
└────────────────────────────────────────┘
```

Kurallar:

- "Yapılacaklar" ve "Tamamlananlar" içinde sıralama: görevin oluşturulma sırası. Bir görev tamamlandığında kart 1,2 sn yerinde "Tamamlandı" hâlinde kalır, sonra "Tamamlananlar" bölümünün başına taşınır (300 ms; hareket azaltmada anında).
- Bir bölüm boşsa başlığı da gizlenir (ör. hepsi tamamsa "Yapılacaklar" görünmez).
- Karargah'taki sandık kartından ya da Görevler rozetinden gelinirse sayfa `#meydan-okumalar` çapasına kayar.
- "Görev ekle" düğmesi aktif görev sayısı sınıra ulaştıysa kilitli hâldedir (§13).

## 3. Görev kartı bileşeni

E10 ve E20'de aynı bileşen kullanılır. Karargah'ta kart menüsü (⋯) gösterilmez.

### 3.1 Yapı (soldan sağa)

| Öğe | Ayrıntı |
|---|---|
| Sol şerit | 4 px, yetenek renginde. |
| Onay kutusu | Görsel 28×28 px, dokunma alanı 44×44 px; boşken `kenarlik-etkin` kenarlık, doluyken altın zemin + koyu tik. **Kartın tamamı** da dokunulabilir alandır (menü düğmesi hariç). |
| Görev adı | Kullanıcı girdisi; en fazla 2 satır, taşarsa üç nokta. 16 px, `metin`. |
| Yetenek etiketi | Yetenek ikonu (16 px) + yetenek adı (`ortak.yetenek_zeka`, `ortak.yetenek_guc`, `ortak.yetenek_sosyallik`, `ortak.yetenek_disiplin`, `ortak.yetenek_yaraticilik`), yetenek renginde metin. |
| Ödül | Tamamlanmamışsa `gorevler.kart_odul` (ör. "+13 XP"; bir **sonraki** seriyle hesaplanmış XP). Tamamlanmışsa `gorevler.kart_kazanildi` (bugün gerçekte kazanılan XP). |
| Seri | `seri-alev` ikonu + `gorevler.kart_seri`; etkin seri 0 ise `gorevler.kart_seri_yok`. |
| Seri bonusu | Çarpan > 1 ise `gorevler.kart_bonus` rozeti (altın kenarlıklı küçük kapsül). |
| Menü (⋯) | Yalnızca E20; 44×44 px; açılır menü: `gorevler.kart_menu_duzenle`, `gorevler.kart_menu_sil`. Etiket `gorevler.kart_menu_etiketi`. |

Seri ve bonus alanı tek bir düğmedir (`gorevler.kart_seri_etiketi`); dokunulunca M02 açılır, kartı tamamlamaz.

### 3.2 Gösterilecek seri ve ödülün hesabı (arayüz ile arka uç aynı kuralı kullanmalı)

- **Etkin seri**: görevin son tamamlandığı gün bugün ya da dün ise kayıtlı seri; değilse 0.
- **Tamamlanırsa seri**: bugün tamamlanmamışsa `etkin seri + 1`.
- **Tamamlanırsa çarpan**: `min(2; 1 + floor(tamamlanırsa seri / 7) × 0,25)`.
- **Kartta gösterilen ödül**: `round(10 × tamamlanırsa çarpan)`.
- Kartta gösterilen "×{carpan}" rozeti: tamamlanmamış görevde **tamamlanırsa çarpan**, tamamlanmış görevde bugün uygulanan çarpan. Böylece 6 günlük serisi olan görevde rozet "×1,25", ödül "+13 XP" görünür: oyuncu bugün tamamlarsa bonusu alacağını görür.

> Not: Bugünkü `userpage.php` kartta ödülü mevcut seriyle (bir eksik) hesaplıyor; `ajax_islem.php` ise yeni seriyle veriyor. Gösterilen ve kazanılan değer farklı olabiliyor. Bu spesifikasyondaki kural ikisini eşitler.

### 3.3 Kartın hâlleri

| Hâl | Görünüm | Erişilebilir ad |
|---|---|---|
| Yapılacak | Boş onay kutusu, ödül "+{xp} XP" | `gorevler.kart_onay_etiketi` |
| Gönderiliyor | Onay kutusu dolu (basılma tepkisi), 400 ms sonra hâlâ yanıt yoksa kutuda dönen ikon; kart dokunmaya kapalı | `aria-busy="true"` |
| Tamamlandı | Altın dolu kutu + tik, `gorevler.kart_tamamlandi` etiketi (`basari` renginde, tik ikonuyla), ödül "+{xp} XP kazanıldı". Metin soluklaştırılmaz ve üstü çizilmez (okunabilirlik). | `gorevler.kart_tamamlandi_etiketi` |
| Seri bozulmuş | Yapılacak hâline ek olarak kartın altında bir satır: `gorevler.kart_seri_bozuldu`. Yalnızca kayıtlı seri > 0 ve etkin seri 0 iken, bugün görev tamamlanana kadar gösterilir. | Aynı etikete eklenir |
| Devre dışı (çevrimdışı) | Onay kutusu kenarlığı soluk; dokununca `ortak.cevrimdisi_islem` | Etikete `ortak.cevrimdisi_bandi` eklenmez; `aria-disabled="true"` |

## 4. Kullanıcı akışları

### 4.1 Görevi tamamlama (E10 ve E20)

1. Oyuncu "Yapılacak" kartına dokunur.
2. Kart "Gönderiliyor" hâline geçer; tamamlama isteği gönderilir.
3. Başarı → K01 (§9). Bildirim: `gorevler.tamamlandi_bildirim` + `ortak.geri_al`.
4. Kart 1,2 sn sonra "Tamamlananlar"a taşınır (E20) ya da listeden çıkar (E10).

Sapmalar: §13 durum tablosu ve 02 §3.3 ile aynıdır (zaten tamamlanmış, reddedildi, ulaşmadı, oturum yok, gün değişti).

### 4.2 Görev ekleme

1. "Görev ekle" (E20), boş durum düğmesi (E10, E20) ya da yetenek kartındaki "Görev ekle" (E50; yetenek önceden seçili) → E21 ekle kipinde açılır.
2. Oyuncu ad yazar (ya da bir öneri çipine dokunur: ad ve yetenek dolar), yetenek seçer.
3. "Görevi ekle" → G06 → başarı: E21 kapanır, yeni kart "Yapılacaklar"ın sonuna 300 ms'de yukarı kayarak girer ve 1 sn altın kenarlıkla vurgulanır. Bildirim `gorevler.eklendi_bildirim`. Sandık "hazır" ise yeniden "kilitli" olur (02 §5.1).
4. Sapmalar: doğrulama hataları alan altında (§5.3); sunucu hatası formun üstünde `gorev_form.hata_kaydedilemedi`; istek ulaşmazsa form açık kalır, girilen değerler korunur, G03 `ortak.istek_basarisiz`.

### 4.3 Görev düzenleme

1. Kart menüsü → "Düzenle" → E21 düzenle kipinde, alanlar dolu.
2. Oyuncu adı ve/veya yeteneği değiştirir. Yetenek değiştiyse alanın altında `gorev_form.yetenek_degisti_uyari` görünür (**VARSAYIM S19**).
3. "Kaydet" → başarı: E21 kapanır, kart yerinde güncellenir, bildirim `gorevler.guncellendi_bildirim`.
4. Seri, bugünkü tamamlanma durumu ve geçmiş XP değişmez.
5. Değişiklik yapılmadan "Kaydet" devre dışıdır.

### 4.4 Görev silme

1. Kart menüsü → "Sil" ya da E21 içinde "Görevi sil" → M03.
2. "Sil" → G06 → başarı: kart 300 ms'de yükseklik kapanarak kaybolur; bildirim `gorevler.silindi_bildirim` + `ortak.geri_al` (5 sn).
3. "Geri al" → görev aynı yerine, serisi ve bugünkü durumu korunarak döner; bildirim `gorevler.silme_geri_alindi`.
4. Silme kuralları (**VARSAYIM S18**): silinen görev arşivlenir (listeden ve sandık koşulundan çıkar); kazanılmış XP, seviye ve altın değişmez; 5 sn sonra arayüzden geri getirilemez.

### 4.5 Geri alma — §8.

### 4.6 Meydan okuma ödülünü alma — §10.

## 5. Görev formu (E21)

Alttan açılan tam boy alt sayfa (mobilde ekranın tamamı; ≥ 768 px'te 560 px genişlikte ortalanmış panel).

```
┌────────────────────────────────────────┐
│ [x]      Yeni görev                     │  gorev_form.ekle_baslik | gorev_form.duzenle_baslik
├────────────────────────────────────────┤
│ Görev adı                               │  gorev_form.ad_etiketi
│ [ Örn: 30 sayfa kitap oku          ]    │  gorev_form.ad_ipucu
│                                  0/60   │  gorev_form.ad_sayac
│ Fikir lazım mı?                         │  gorev_form.oneri_baslik (yalnızca ekle kipinde)
│ (30 sayfa kitap oku) (20 dakika yürüyüş)│  öneri çipleri, yatay kaydırma
│ (Bir arkadaşını ara) ...                │
│                                         │
│ Hangi yeteneği geliştirir?              │  gorev_form.yetenek_etiketi
│ ┌──────────┐ ┌──────────┐               │  5 seçenek kartı (radyo), 2 sütun
│ │[kitap]   │ │[dambıl]  │               │
│ │Zeka      │ │Güç       │               │
│ │Okumak,…  │ │Spor,…    │               │
│ └──────────┘ └──────────┘               │
│ ...                                     │
│                                         │
│ Her tamamlamada +10 XP, Zeka …          │  gorev_form.odul_onizleme
├────────────────────────────────────────┤
│ [          Görevi ekle             ]    │  sabit alt düğme: gorev_form.kaydet_ekle | gorev_form.kaydet_duzenle
│            Görevi sil                   │  gorev_form.sil_dugme; yalnızca düzenle kipinde, `hata` renginde metin düğme
└────────────────────────────────────────┘
```

### 5.1 Alanlar

| Alan | Kural | Not |
|---|---|---|
| Görev adı | Zorunlu; baştaki/sondaki boşluklar kırpılır; 1–60 karakter (**VARSAYIM S17**); aynı oyuncunun aktif görevleri arasında büyük/küçük harf duyarsız benzersiz (**VARSAYIM S9**) | Görev adı **yalnızca bu oyuncuya** aittir. Başka oyuncunun aynı adlı görevi bu görevi etkilemez (bugünkü kodda etkiliyor; bkz. rapor). |
| Yetenek | Zorunlu; ekle kipinde **önceden seçili değil** (bilinçli seçim); E50'den gelindiyse o yetenek seçili | Seçenek kartı: ikon + ad + kısa açıklama (`gorev_form.yetenek_aciklama_zeka`, `gorev_form.yetenek_aciklama_guc`, `gorev_form.yetenek_aciklama_sosyallik`, `gorev_form.yetenek_aciklama_disiplin`, `gorev_form.yetenek_aciklama_yaraticilik`). Seçili kart: yetenek renginde 2 px kenarlık + tik. |
| Ödül önizleme | Salt okunur. Yetenek seçilmemişse gizli. | Taban ödül her zaman 10 XP. |

Öneri çipleri (yalnızca ekle kipinde; dokununca ad ve yetenek dolar, kullanıcı değiştirebilir):

| Anahtar | Yetenek |
|---|---|
| `gorev_form.oneri_1` | Zeka |
| `gorev_form.oneri_2` | Güç |
| `gorev_form.oneri_3` | Sosyallik |
| `gorev_form.oneri_4` | Disiplin |
| `gorev_form.oneri_5` | Yaratıcılık |

### 5.2 Kapatma

- [x], arka plana dokunma, Esc ya da tarayıcı geri tuşu. Alanlarda değişiklik varsa M07 (`ortak.kaydedilmedi_baslik`) açılır.

### 5.3 E21 durumları

| Durum | Ne görünür | Metin |
|---|---|---|
| Boş | Ekle kipinin açılış hâli: alanlar boş, öneri çipleri görünür, düğme etkin (doğrulama gönderimde). | `gorev_form.ad_ipucu` |
| Yükleniyor | Kaydet düğmesinde G06; alanlar salt okunur. | – |
| Hata (doğrulama) | İlgili alanın altında `hata` renginde metin + ikon; alan kenarlığı `hata`; odak ilk hatalı alana gider. Doğrulama gönderimde ve alan odaktan çıkınca yapılır. | `gorev_form.hata_ad_bos`, `gorev_form.hata_ad_uzun`, `gorev_form.hata_yetenek_yok`, `gorev_form.hata_ad_ayni` |
| Hata (sunucu) | Formun üstünde hata kutusu; girilenler korunur. | `gorev_form.hata_kaydedilemedi` |
| İstek başarısız | Girilenler korunur; G03. | `ortak.istek_basarisiz`, `ortak.tekrar_dene` |
| Kilitli | Ekle kipi, aktif görev sınırı doluyken açılmaz; bkz. §13 "Kilitli". Düzenle kipinde kilit yok. | `gorevler.sinir_dolu` |
| Başarı | Form kapanır; §4.2 / §4.3 adım 3. | `gorevler.eklendi_bildirim`, `gorevler.guncellendi_bildirim` |

## 6. Görev silme onayı (M03)

G07 bileşeni. Başlık `gorevler.sil_onay_baslik`, açıklama `gorevler.sil_onay_aciklama`, birincil (tehlikeli; `hata` renginde zemin üzerinde koyu metin değil, `hata` renginde kenarlık + metin) `gorevler.sil_onay_dugme`, ikincil `ortak.vazgec` (varsayılan odak).

| Durum | Ne görünür |
|---|---|
| Yükleniyor | "Sil"de G06. |
| Hata | Alt sayfa açık kalır, açıklamanın yerine `gorevler.hata_silinemedi`. |
| İstek başarısız | Alt sayfa kapanır, G03 `ortak.istek_basarisiz`. |
| Başarı | §4.4. |

## 7. Seri bilgisi (M02)

Alt sayfa. Görev kartındaki seri/bonus alanından açılır.

```
┌────────────────────────────────────────┐
│ Seri bonusu                        [x] │  seri.modal_baslik
│ [seri-alev 32px]                       │
│ Bu görevin serisi: 8 gün               │  seri.modal_gorev_serisi
│ Şu anki bonus: ×1,25                   │  seri.modal_simdiki_bonus
│ 6 gün sonra bonus ×1,5 olur.           │  seri.modal_sonraki_bonus | seri.modal_en_yuksek
│                                        │
│ • Görevi art arda günlerde…            │  seri.modal_kural_1
│ • Her 7 günde bonus %25 artar…         │  seri.modal_kural_2
│ • Bir gün atlarsan seri…               │  seri.modal_kural_3
│                                        │
│ Bonus tablosu                          │  seri.modal_tablo_baslik
│ Seri     | Bonus | XP                  │  seri.tablo_sutun_seri, seri.tablo_sutun_bonus, seri.tablo_sutun_xp
│ 1–6      | ×1    | 10                  │
│ 7–13     | ×1,25 | 13   ◄ (şu anki satır vurgulu)
│ 14–20    | ×1,5  | 15                  │
│ 21–27    | ×1,75 | 18                  │
│ 28+      | ×2    | 20                  │
└────────────────────────────────────────┘
```

- "Şu anki" değerler etkin seriye göredir (tamamlanmamış görevde "bugün tamamlarsan" değil, şu anki seri). `sonraki_bonus` için `kalan = 7 × (floor(etkin seri / 7) + 1) − etkin seri`; etkin seri ≥ 28 ise `seri.modal_en_yuksek`.
- Tablo gerçek bir `<table>`'dır; şu anki satır `aria-current="true"` ve altın kenarlık.
- Durumlar: yalnızca istemci verisiyle çizilir; boş/yükleniyor/hata/istek başarısız/kilitli **bu modalda geçerli değil**. Başarı: geçerli değil (salt bilgi).
- **VARSAYIM S12**: seri bozulunca sıfırlanır, bir sonraki tamamlamada 1'den başlar (bugünkü kural); seri koruma/dondurma yok.

## 8. Geri alma kuralları ve onayı (M04)

### 8.1 Kurallar

| Kural | Değer |
|---|---|
| Ne geri alınabilir | Yalnızca **bugün** tamamlanmış görev. Geçmiş günler değiştirilemez. |
| Ne zamana kadar | Gün sonuna (00:00, **VARSAYIM S1**) kadar. |
| Yollar | (a) Tamamlamadan sonraki 5 sn'lik bildirimdeki `ortak.geri_al` → onaysız. (b) E20'de "Tamamlandı" kartına dokunma → M04 onayı. |
| XP | Bugün o görevden kazanılan XP'nin **tamamı** düşülür (kazanıldığı andaki miktar; o andan sonra değişmiş çarpana göre değil). |
| Seri | Tamamlamadan **önceki** değerine döner (ör. 8 → 7; 1 → önceki etkin seri, genellikle 0). Bugün yeniden tamamlanırsa yeniden 8 olur. Arka uç, tamamlama öncesi seriyi ve son tamamlama tarihini saklamalı (**YENİ VERİ**). |
| Seviye | XP düşünce yetenek seviyesi ve karakter seviyesi düşebilir (**VARSAYIM S8**). Düşüş kutlanmaz; sessizce güncellenir ve ekran okuyucuya `gorevler.okuyucu_seviye_dustu` duyurulur. |
| Meydan okuma | İlgili meydan okumanın ilerlemesi bir azalır; tamamlandıysa ve ödül **alınmadıysa** "devam ediyor"a döner. Ödül alındıysa ilerleme ve ödül değişmez (**VARSAYIM S7**). |
| Sandık | Sandık hazır ama açılmamışsa kilitliye döner. Açıldıysa açık kalır, ödül geri alınmaz (**VARSAYIM S6**). |
| Haftalık sıralama | Düşülen XP haftalık puandan da düşülür (**VARSAYIM S3**). |
| Altın | Değişmez (görev tamamlama altın vermez). |

### 8.2 M04

G07: başlık `gorevler.geri_al_onay_baslik`, açıklama `gorevler.geri_al_onay_aciklama`, birincil `gorevler.geri_al_onay_dugme`, ikincil `ortak.vazgec` (varsayılan odak).

### 8.3 Geri alma geri bildirimi

1. Onay kutusu hemen boşalır (basılma tepkisi), istek gider.
2. Başarı: kartın ödül alanında "−{xp} XP" metni gri renkte 600 ms içinde solarak kaybolur (uçmaz); ilgili yetenek barı (E10'da mini bar, E20'de yetenek şeridi; §9) 300 ms'de azalır; seri sayısı güncellenir; kart "Yapılacaklar"a geri taşınır; G03 `gorevler.geri_alindi_bildirim`; ekran okuyucu `gorevler.okuyucu_geri_alindi`.
3. Hata: kart "Tamamlandı" hâline geri döner; G03 `gorevler.hata_geri_alinamadi` (gün değiştiyse `gorevler.hata_gun_bitti` ve sayfa yeniden yüklenir).
4. İstek başarısız: kart "Tamamlandı"ya döner; G03 `ortak.istek_basarisiz` + `ortak.tekrar_dene`.
5. Hareket azaltma: solma yok, değerler anında değişir.

## 9. Görev tamamlama geri bildirimi (K01–K04)

### 9.1 Zaman çizelgesi

T0 = dokunma anı, T = sunucunun başarılı yanıt anı.

| Zaman | Olay | Süre |
|---|---|---|
| T0 | Onay kutusu altın dolar, 0,9 → 1 ölçek (basılma tepkisi). Destekleniyorsa 10 ms titreşim. Kart dokunmaya kapanır. | 120 ms |
| T0 + 400 ms (yanıt yoksa) | Onay kutusunda dönen ikon. | Yanıta kadar (en fazla 10 sn, sonra "istek başarısız") |
| T | Tik çizilir; kart "Tamamlandı" hâline geçer; ödül metni `gorevler.kart_kazanildi`. | 150 ms |
| T | **K01 – uçan XP**: "+{xp} XP" (piksel font, 20 px, altın) karttan yükselir ve hedefe uçar: E10'da ilgili mini yetenek barı; E20'de ekranın üstünde beliren **yetenek şeridi** (aşağıda). Hedefe varınca kaybolur. | 700 ms, ease-out |
| T | E10'da karakter zıplar (`karakter-ziplama`). | 600 ms |
| T | Bildirim: `gorevler.tamamlandi_bildirim` + `ortak.geri_al`. | 5 sn |
| T + 700 ms | **Bar dolar**: ilgili yetenek barı yeni değere dolar. Seri sayısı yukarı kayarak artar. | 500 ms / 300 ms |
| T + 700 ms | **K04** (yalnızca çarpan bu tamamlamayla yükseldiyse, yani yeni seri 7, 14, 21 veya 28 ise): karttaki bonus rozeti 1 → 1,3 → 1 ölçekte parlar, alev ikonu bir boy büyür. Bildirim kuyruğuna `kutlama.seri_bonus` + `kutlama.seri_bonus_aciklama`. | 600 ms |
| T + 1200 ms | **K02** (yalnızca yetenek seviyesi arttıysa): bar %100'e dolar (300 ms), "Sv. {n}" etiketi altın parlar ve şeridin/mini barın üstünde bant: `kutlama.yetenek_seviye` (2,5 sn); bar 0'dan yeni seviyedeki XP'ye dolar (300 ms). | ~3 sn |
| T + 1800 ms (K02 yoksa T + 1200 ms) | **K03** (yalnızca karakter seviyesi arttıysa): tam ekran kutlama modalı (§9.3). | Kullanıcı kapatana kadar |
| K03 kapandıktan sonra (yoksa T + 1800 ms) | **K05** (yalnızca sandık bu tamamlamayla hazır olduysa; 02 §5.2). | |
| Sonra | Bu tamamlamayla tamamlanan meydan okuma varsa: Görevler sekmesi rozeti + bildirim kuyruğuna `gorevler.meydan_tamamlandi_bildirim`. E20'de ilgili meydan okuma kartında ilerleme barı dolar ve "Ödülü al" düğmesi belirir. | |

**Bildirim kuyruğu**: aynı anda tek G03. Öncelik: (1) tamamlandı + Geri al, (2) seri bonusu, (3) sandık hazır, (4) meydan okuma tamamlandı. "Geri al" bildirimi 5 sn'den önce kapatılmaz; diğerleri 3 sn.

**Yetenek şeridi (yalnızca E20)**: üst barın hemen altında, 56 px yüksekliğinde panel rengi şerit: yetenek ikonu + `ortak.yetenek_seviye_etiketi` + bar + `ortak.xp_ilerleme`. T'de yukarıdan 200 ms'de iner, K02 dahil animasyonlar bitince 1,5 sn sonra 200 ms'de çıkar. Art arda tamamlamalarda şerit kapanmadan yeni yeteneğe geçer.

**Üst bar**: karakter seviyesi değiştiyse G02 seviye rozeti K03 kapanınca güncellenir.

**Ekran okuyucu** (`polite`): `gorevler.okuyucu_tamamlandi`; K02 varsa ardından `gorevler.okuyucu_yetenek_seviye`; K04 varsa `kutlama.seri_bonus`. K03 `assertive`: `kutlama.seviye_okuyucu`.

### 9.2 Hareket azaltma (`prefers-reduced-motion: reduce`)

| Öğe | Karşılığı |
|---|---|
| Basılma ölçeği, titreşim | Yok; kutu anında dolar. |
| Uçan "+XP" | Yok. Kartın ödül alanı anında `gorevler.kart_kazanildi` olur. |
| Karakter zıplama | Yok; ifade anında değişir. |
| Bar dolması | 150 ms opaklık geçişiyle yeni değer. |
| K04 parlama | Yok; rozet anında yeni değer; bildirim yine gösterilir. |
| K02 | Bar doğrudan yeni değer; bant hareketsiz 2,5 sn görünür. |
| K03 | Konfeti ve ışık hüzmesi yok; modal 150 ms opaklıkla açılır, içerik aynı. |
| Yetenek şeridi | Kayma yok; belirip kaybolur. |
| Kart taşınması | Anında. |

### 9.3 Karakter seviyesi kutlaması (K03)

```
┌────────────────────────────────────────┐
│              (konfeti)                 │
│           Seviye atladın!              │  kutlama.seviye_baslik (piksel, 28 px)
│        [ seviye-isigi + karakter ]     │  karakter zafer pozunda, 200 px
│             [ Sv. 5 rozet ]            │
│     Artık seviye 5 bir maceracısın.    │  kutlama.seviye_alt
│                                        │
│ Market'te kilidi açılanlar             │  kutlama.seviye_acilan_baslik
│ [ikon] Kral Tacı   [ikon] ...          │  en fazla 3 eşya; yoksa kutlama.seviye_acilan_yok
│                                        │
│ [      Market'e göz at       ]         │  kutlama.seviye_markete_git (yalnızca açılan eşya varsa; ikincil)
│ [          Devam et          ]         │  kutlama.seviye_devam (birincil)
└────────────────────────────────────────┘
```

- Tam ekran, `role="dialog"`, `aria-modal="true"`, başlık `aria-labelledby`. Odak "Devam et"e gider. Arka plana dokunma ve Esc **kapatmaz** (yanlışlıkla kaçırılmasın); yalnızca düğmeler.
- Konfeti 2 sn, bir kez.
- "Market'e göz at" → E30, açılan eşyalar en üstte, 1 sn altın kenarlıkla vurgulu.
- Bir yanıtta birden fazla seviye atlanmışsa (pratikte olmaz) tek modal, son seviye gösterilir.
- Durumlar: veri tamamlama yanıtıyla gelir; boş: açılan eşya yoksa `kutlama.seviye_acilan_yok`; yükleniyor/hata/istek başarısız/kilitli **geçerli değil**; başarı = modalın kendisi.

## 10. Günlük meydan okumalar ve K08

Bugünkü karşılığı: `userpage.php` "Günlük Görevler" kutusu (`GorevTanimlari` + `KullaniciGunlukGorevleri`).

### 10.1 Meydan okuma kartı

| Öğe | Ayrıntı |
|---|---|
| İkon | `meydan-okuma-{tur}` 32 px |
| Başlık ve açıklama | İçerikten gelir (yönetici tanımlı). İçerik metinleri bu spesifikasyonun terimlerine göre güncellenmelidir: "alışkanlık" yerine "görev", "coin" yerine "altın" (ör. "3 görev tamamla"). |
| Ödül | `altin-ikon` + `gorevler.meydan_odul` |
| İlerleme | `gorevler.meydan_ilerleme` + bar (hedef > 1 ise) |
| Eylem / etiket | Devam ediyor: `gorevler.meydan_devam` (etiket, düğme değil). Tamamlandı, ödül alınmadı: birincil küçük düğme `gorevler.meydan_odulu_al` (altın zemin, 2 sn'de bir hafif parıltı; hareket azaltmada yok). Ödül alındı: tik + `gorevler.meydan_alindi`. |

Bölüm başlığının yanında `gorevler.meydan_yenilenme` (dakikada bir güncellenir); bölüm altında `gorevler.meydan_kaybolur_uyari` (**VARSAYIM S5**: alınmayan ödül gece yarısı kaybolur).

Meydan okumaların ilerleme kuralı (**VARSAYIM S25**): iki tür vardır — "giriş" (o gün uygulamayı açınca tamamlanır) ve "görev tamamlama sayısı" (o gün tamamlanan her görev ilerlemeyi 1 artırır, geri alma 1 azaltır; §8.1). Arka uç türü başlık metninden değil açık bir tür bilgisinden okumalıdır.

### 10.2 Ödülü alma akışı (K08)

1. "Ödülü al" → G06.
2. Başarı: düğme "Alındı" etiketine dönüşür; 5 sikke (`altin-parcacik`) karttan üst bardaki altına uçar (600 ms); üst bar altını eski değerden yenisine sayarak artar (600 ms); ekran okuyucu `gorevler.meydan_odul_alindi_okuyucu`. Alınmamış başka ödül kalmadıysa Görevler sekmesi rozeti kalkar.
3. Hata: zaten alınmış → kart "Alındı"ya geçer, altın sunucu değeriyle güncellenir, G03 `gorevler.meydan_hata_zaten`; tamamlanmamış → kart "devam ediyor"a döner, G03 `gorevler.meydan_hata_tamamlanmadi`; süresi doldu (gün değişti) → G03 `gorevler.meydan_hata_suresi_doldu`, bölüm yeniden yüklenir; diğer → G03 `gorevler.meydan_hata_alinamadi`.
4. İstek başarısız: düğme eski hâline döner, G03 `ortak.istek_basarisiz` + `ortak.tekrar_dene`.
5. Hareket azaltma: uçuş ve sayma yok; altın anında yeni değer.
6. Geri alma: alınan ödül geri alınamaz.

## 11. Erişilebilirlik

- Sayfa başlığı (`<h1>`, görsel olarak üst barda): `gorevler.baslik`.
- Her görev kartı bir `<li>`; tamamlama denetimi `role="checkbox"` + `aria-checked`, erişilebilir adı `gorevler.kart_onay_etiketi` / `gorevler.kart_tamamlandi_etiketi`. Kartın görsel tamamına dokunma bu denetimi tetikler; klavyede yalnızca denetim odak alır (tüm kart ayrıca odaklanmaz), Boşluk ile işaretlenir.
- Seri/bonus alanı ayrı `button` (`gorevler.kart_seri_etiketi`); menü düğmesi ayrı `button` (`gorevler.kart_menu_etiketi`), açılır menü `role="menu"`, ok tuşlarıyla gezilir, Esc kapatır.
- Bölüm başlıkları `<h2>`: Yapılacaklar, Tamamlananlar, Günlük meydan okumalar.
- E21: her alanın görünür `<label>`'ı var; yetenek seçenekleri `fieldset` + `legend` (`gorev_form.yetenek_etiketi`) + radyo düğmeleri (ok tuşlarıyla gezilir); hata metinleri `aria-describedby` ile alana bağlı; karakter sayacı `aria-live="polite"` değil (gürültü), yalnızca 50 karakteri geçince `polite` uyarı.
- Meydan okuma ilerlemesi `role="progressbar"`, kartın adı `gorevler.meydan_odul_etiketi`.
- K01–K04 duyuruları §9.1'deki gibi; K03 açılınca odak modal içine taşınır, kapanınca tetikleyen karta döner.
- Dokunma hedefleri: onay kutusu 44×44, menü 44×44, kart en az 64 px, "Ödülü al" en az 44 px yükseklik ve 96 px genişlik.
- Renk: tamamlanma durumu yalnızca renkle değil tik + "Tamamlandı" metniyle; yetenek ikon + adla.

## 12. Veri ihtiyacı

Ekranın bilmesi gerekenler:

| Bilgi | Bugünkü kodda | Not |
|---|---|---|
| Aktif görevler: kimlik, ad, yetenek, oluşturulma sırası | Var (`KullaniciAliskanliklari` + `Aliskanliklar`) | Görev adı ve yetenek **oyuncuya özel** olmalı (**YENİ VERİ**: bugün ad ortak tabloda, ilk oluşturanın yeteneğiyle paylaşılıyor) |
| Bugün tamamlandı mı, bugün kazanılan XP | Tamamlandı mı var; kazanılan XP yok | **YENİ VERİ**: tamamlama başına kazanılan XP (geri alma ve haftalık sıralama için) |
| Kayıtlı seri, son tamamlama tarihi, etkin seri, tamamlanırsa seri/çarpan/XP | Seri ve son tarih var; diğerleri hesaplanmalı | **YENİ VERİ** (hesaplanan) |
| Seri bozulduysa önceki seri | Var (bugünkü kod seriyi bir sonraki tamamlamaya kadar saklıyor) | |
| Aktif görev sayısı ve sınır | Sayı var; sınır yok | **YENİ VERİ**: sınır (**VARSAYIM S10**: 20) |
| Bugünkü meydan okumalar: kimlik, başlık, açıklama, ikon, tür, hedef, mevcut ilerleme, tamamlandı mı, ödül alındı mı, ödül altını | Var (tür hariç) | **YENİ VERİ**: meydan okuma türü |
| Gün sonuna kalan süre (sunucu saatine göre) | Yok | **YENİ VERİ** (sunucu saati sayfaya yazılır) |
| Yetenek seviyeleri / XP | Var (hesaplanıyor) | Yetenek şeridi için |

Tetiklediği işlemler:

| İşlem | Bugünkü kodda | Başarı yanıtında olması gerekenler | Ret gerekçeleri |
|---|---|---|---|
| Görevi tamamla | Var (`ka_id`, `durum=1`) | Kazanılan XP; yeni seri; uygulanan çarpan; çarpan yükseldi mi; yetenek; yetenek seviyesi önce/sonra; o seviyedeki XP ve gereken XP; karakter seviyesi önce/sonra; karakter seviyesi arttıysa kilidi açılan eşyalar (ad, ikon, nadirlik); yeni "sonraki seviye için kalan"; bugün tamamlanan/toplam; sandık hâli; değişen meydan okumalar (kimlik, ilerleme, tamamlandı mı) | Zaten tamamlandı (güncel durumla), görev bulunamadı, oturum kapalı |
| Geri al | Var (`durum=0`) | Düşülen XP; geri dönen seri; yetenek ve karakter seviyesi önce/sonra; XP değerleri; bugün tamamlanan/toplam; sandık hâli; değişen meydan okumalar | Tamamlanmamış, gün değişti, bulunamadı, oturum kapalı |
| Görev ekle | Var (sayfa yeniden yüklenen form) | Yeni görevin tüm kart verisi; sandık hâli | Ad boş, ad uzun, ad aynı, yetenek geçersiz, sınır dolu |
| Görev düzenle | Yok | Güncellenmiş kart verisi | Ad boş/uzun/aynı, yetenek geçersiz, bulunamadı |
| Görev sil (arşivle) | Var ama kalıcı siliyor | Başarı; sandık hâli; bugün tamamlanan/toplam | Bulunamadı |
| Silmeyi geri al | Yok | Görevin kart verisi | Süre doldu, sınır dolu |
| Meydan okuma ödülünü al | Var (`claim_quest_id`) | Alınan altın, yeni altın toplamı, alınmamış ödül kaldı mı | Zaten alındı, tamamlanmadı, süresi doldu |

## 13. Durumlar (E20)

| Durum | Ne görünür | Metin |
|---|---|---|
| Boş (görev yok) | Özet ve "Görev ekle" gizli; `bos-gorev` + başlık + açıklama + birincil düğme. Meydan okumalar bölümü yine görünür. | `gorevler.bos_baslik`, `gorevler.bos_aciklama`, `gorevler.bos_dugme` |
| Boş (meydan okuma yok) | Bölüm başlığı altında tek satır. | `gorevler.meydan_bos` |
| Yükleniyor | Sayfa sunucuda çizilir. İşlemlerde kart "Gönderiliyor" hâli (§3.3) ve düğmelerde G06. | `ortak.yukleniyor` |
| Hata (sayfa) | Üst bar + alt menü + `hata-genel` + başlık + açıklama + "Yeniden yükle". | `ortak.sayfa_hatasi_baslik`, `ortak.sayfa_hatasi_aciklama`, `ortak.yeniden_yukle` |
| Hata (işlem) | Kart/düğme önceki hâline döner; G03 ilgili metin. | `gorevler.hata_tamamlanamadi`, `gorevler.hata_geri_alinamadi`, `gorevler.hata_gun_bitti`, `gorevler.zaten_tamamlandi`, `gorevler.hata_silinemedi`, `gorevler.meydan_hata_alinamadi`, `gorevler.meydan_hata_zaten`, `gorevler.meydan_hata_tamamlanmadi`, `gorevler.meydan_hata_suresi_doldu` |
| İstek başarısız / çevrimdışı | G04 bandı; kartlar "devre dışı" hâli; "Görev ekle" ve "Ödülü al" devre dışı; dokununca `ortak.cevrimdisi_islem`. Zaman aşımında §8.3/§10.2. | `ortak.cevrimdisi_bandi`, `ortak.cevrimdisi_islem`, `ortak.istek_basarisiz` |
| Kilitli | Aktif görev sınırı (**VARSAYIM S10**: 20) doluysa "Görev ekle" kilit ikonlu görünür; dokununca G03 `gorevler.sinir_dolu`. Meydan okuması tamamlanmamış kartta ödül düğmesi yok ("Devam ediyor"). | `gorevler.sinir_dolu`, `gorevler.meydan_devam` |
| Başarı | K01–K05, K08, ekleme/düzenleme/silme bildirimleri. | §4, §9, §10 |
