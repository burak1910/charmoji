# 04 · Market (E30), eşya detayı ve deneme (E31), satın alma (M05, K07)

Bugünkü karşılığı: `view/market.php` (ızgara, fiyat, seviye rozeti, satın al), işlem `view/ajax_islem.php` (`buy_item_id`). Deneme ve nadirlik yeni.

## 1. Amaç

Market altının harcandığı yerdir ve uzun vadeli hedefleri görünür kılar: "Kral Tacı için 3 seviye ve 400 altın daha." Oyuncu her eşyayı **satın almadan kendi karakteri üzerinde dener**, nadirliği ve kilidi bir bakışta anlar.

## 2. İçerik hiyerarşisi ve yerleşim

```
┌────────────────────────────────────────┐
│ [Sv. 4]          Market        ◉ 1.250 │  G02 (altın burada da görünür)
├────────────────────────────────────────┤
│ (Tümü) (Şapka) (Gözlük) (Maske) (Kolye)│ 1  tür filtre çipleri, yatay kaydırma:
│                                        │    ortak.filtre_tumu, ortak.tur_sapka, ortak.tur_gozluk, ortak.tur_maske, ortak.tur_kolye
├────────────────────────────────────────┤
│ ┌─────────────────┐ ┌─────────────────┐│ 2  2 sütunlu ızgara
│ │ [Nadir]         │ │ [Efsanevi]      ││    nadirlik etiketi (sol üst)
│ │   [ ikon 96 ]   │ │  [ ikon 96 ]    ││    nadirlik çerçevesi
│ │                 │ │   (kilit)       ││
│ │ Büyücü Şapkası  │ │ Kral Tacı       ││    ad (2 satır)
│ │ Şapka           │ │ Şapka           ││    tür
│ │ ◉ 300           │ │ ◉ 1.200         ││    fiyat
│ │ 50 altın eksik  │ │ Sv. 6 gerekir   ││    durum satırı
│ └─────────────────┘ └─────────────────┘│
│ ...                                    │
├────────────────────────────────────────┤
│                 G01                    │
└────────────────────────────────────────┘
```

### 2.1 Sıralama

1. Alınabilir olanlar (seviye kilidi yok, sahip değil) — fiyat artan. Altını yetmeyenler de bu gruptadır.
2. Seviye kilitli olanlar — gereken seviye artan, sonra fiyat artan.
3. Sahip olunanlar — fiyat artan.

K03'teki "Market'e göz at" ile gelindiyse yeni açılan eşyalar en üstte, `market.kart_yeni` rozetiyle (yalnızca o ziyaret boyunca).

### 2.2 Eşya kartı

| Öğe | Ayrıntı |
|---|---|
| Nadirlik etiketi | `market.nadirlik_siradan` / `market.nadirlik_nadir` / `market.nadirlik_efsanevi`, nadirlik renginde metin + çerçeve (00 §3.5). **VARSAYIM S11**: nadirlik yalnızca görseldir; oyun etkisi yok. |
| İkon | `esya-{kod}-ikon` 96 px, nadirlik çerçevesi içinde. Efsanevide yavaş parıltı. |
| Kilit katmanı | Seviye kilitliyse ikonun üstünde %60 koyu katman + `kilit` ikonu. İkon yine görünür (hedef olarak). |
| Ad | Eşya adı, en fazla 2 satır. |
| Tür | `ortak.tur_sapka`, `ortak.tur_gozluk`, `ortak.tur_maske`, `ortak.tur_kolye` |
| Fiyat | `altin-ikon` + fiyat (Türkçe sayı biçimi). Sahip olunanlarda fiyat gizlenir. |
| Durum satırı | Önceliğe göre tek satır: kuşanılmış → `ortak.kusanildi`; sahip → `market.kart_cantada`; seviye kilitli → `market.kart_kilit`; altın yetmiyor → `market.kart_eksik` (`hata` renginde değil, `metin-ikincil`); alınabilir → satır boş. |

Kartın tamamı bir düğmedir → E31. Kartta doğrudan "Satın al" düğmesi **yoktur** (yanlış dokunmayla satın almayı önlemek ve denemeyi teşvik etmek için).

## 3. Kullanıcı akışları

### 3.1 Mutlu yol: dene ve satın al

1. Oyuncu Market'te bir karta dokunur → E31 alttan açılır.
2. E31'in üstünde oyuncunun **kendi karakteri, bu eşya kuşanılmış hâlde** görünür (`market.detay_deneme_etiketi` rozeti). Aynı türde kuşanılmış eşyası varsa deneme onun yerine geçer ve `ortak.ayni_tur_uyari` gösterilmez (yalnızca satın alındıktan sonra kuşanırken gösterilir).
3. Oyuncu "Önceki hâli" düğmesini basılı tutarak eşyasız hâlini görür, bırakınca deneme hâline döner.
4. `market.detay_satin_al` → M05.
5. M05 "Satın al" → G06 → başarı: K07 (§7).
6. E31 açık kalır; birincil düğme `ortak.kusan` olur.
7. Oyuncu "Kuşan"a dokunur → eşya kuşanılır; düğme `ortak.cikar` olur; G03 `ortak.kusanildi_bildirim`.

### 3.2 Sapmalar

| Sapma | Davranış |
|---|---|
| Altın yetmiyor | E31 birincil düğmesi yerine devre dışı görünümlü bilgi düğmesi `market.detay_eksik_dugme`, altında `market.detay_eksik_aciklama` ve ikincil bağlantı `market.detay_gorevlere_git` (→ E20). Deneme yine çalışır. |
| Seviye kilitli | Birincil düğme yerine `market.detay_kilit_dugme` (kilit ikonlu, devre dışı) + `market.detay_kilit_aciklama`. Deneme yine çalışır (**VARSAYIM S22**: kilitli eşya da denenebilir). |
| Zaten sahip, kuşanılmamış | Birincil `ortak.kusan`; aynı türde kuşanılmış eşya varsa düğmenin üstünde `ortak.ayni_tur_uyari`. |
| Zaten sahip, kuşanılmış | Birincil `ortak.cikar`; deneme rozeti yerine `ortak.kusanildi`. |
| Sunucu satın almayı reddeder | M05 kapanır; E31'de düğmenin üstünde hata kutusu: yetersiz altın → `market.hata_yetersiz_altin` (altın sunucu değeriyle güncellenir); seviye → `market.hata_seviye`; zaten sahip → E31 "sahip" hâline geçer, G03 `market.hata_zaten_sahip`; eşya kalktı → E31 kapanır, kart ızgaradan çıkar, G03 `market.hata_bulunamadi`; diğer → `market.hata_satin_alinamadi`. |
| İstek ulaşmaz | M05 kapanır; altın değişmez; G03 `ortak.istek_basarisiz` + `ortak.tekrar_dene` (yeniden M05 açar). |
| Kuşanma başarısız | Düğme eski hâline döner; G03 `ortak.hata_kusanilamadi`. |

Arka uç, istemcinin gösterdiğinden bağımsız olarak **altını ve seviye kilidini sunucuda** denetlemelidir (bugünkü kod seviyeyi denetlemiyor; rapora bakınız).

## 4. Etkileşim ve geri bildirim anları

| Tetik | Ne olur | Süre | Hareket azaltma |
|---|---|---|---|
| Filtre çipine dokunma | Izgara filtrelenir, kartlar 150 ms opaklıkla değişir; seçili çip altın zemin + koyu metin, `aria-pressed="true"`. Seçim sayfa boyunca hatırlanır (tarayıcı belleği). | 150 ms | Anında |
| Karta dokunma | E31 alttan kayarak açılır. | 250 ms | Opaklıkla 150 ms |
| Deneme | Karakter önizlemesinde eşya katmanı 200 ms'de "düşerek" yerine oturur (4 px yukarıdan). | 200 ms | Anında |
| "Önceki hâli" basılı tutma | Eşya katmanı gizlenir; bırakınca geri gelir. Klavyede: Boşluk basılı. Dokunmatik olmayan cihazda tıklama ile aç/kapa (`aria-pressed`). | Anında | Aynı |
| Satın alma (K07) | §7 | ~1,2 sn | §7 |
| Kuşan / Çıkar | Önizlemede katman eklenir/kalkar (200 ms), düğme metni değişir. | 200 ms | Anında |

## 5. Eşya detayı ve deneme (E31)

```
┌────────────────────────────────────────┐
│ [x]                                    │
│      ┌─────────────────────┐           │
│      │ [Deneme]            │           │  market.detay_deneme_etiketi
│      │  KARAKTER + EŞYA    │           │  karakter 200 px, eşya katmanı
│      │                     │           │
│      └─────────────────────┘           │
│   [ Önceki hâli (basılı tut) ]         │  market.detay_onceki_hal
│ Üzerinde böyle görünür. Satın almadan… │  market.detay_deneme_aciklama
├────────────────────────────────────────┤
│ [Nadir] Şapka                          │  nadirlik etiketi + tür
│ Büyücü Şapkası                         │  ad (piksel başlık)
│ Yıldızlı mor bir külah. …              │  açıklama (içerikten)
│ ◉ 300            Sv. 3 gerekir (✓)     │  fiyat + seviye koşulu (karşılanıyorsa tik)
├────────────────────────────────────────┤
│ [     Satın al · 300 altın      ]      │  market.detay_satin_al; birincil düğme hâle göre değişir (§3)
└────────────────────────────────────────┘
```

- Alt sayfa, ekranın en fazla %90'ı; içerik kayar, eylem düğmesi altta sabit.
- Seviye koşulu satırı: karşılanıyorsa `basari` renginde tik + "Sv. {n} gerekir" (`market.kart_kilit` metni); karşılanmıyorsa `kilit` ikonu + aynı metin `metin-ikincil`.
- Kapatma: [x], aşağı kaydırma, arka plana dokunma, Esc, geri tuşu. Deneme hiçbir zaman kaydedilmez; kapatınca karakter eski hâlindedir.

## 6. Satın alma onayı (M05)

G07: başlık `market.satin_al_onay_baslik`; açıklama `market.satin_al_onay_aciklama`; not `market.satin_al_onay_not`; birincil `market.satin_al_onay_dugme`; ikincil `ortak.vazgec` (varsayılan odak).

Satın alma geri alınamaz (bugünkü kural; iade yok).

## 7. Satın alma başarısı (K07)

1. M05 kapanır.
2. Üst bardaki altın eski değerden yenisine 600 ms'de aşağı sayar.
3. E31'deki eşya ikonu (başlık bölümünde) 300 ms parlar; deneme rozeti kalkar.
4. G03: `market.satin_alindi_bildirim` + eylem `ortak.kusan` (5 sn). Bildirimdeki "Kuşan" = E31'deki "Kuşan" ile aynı işlem.
5. Izgaradaki kart "Çantada" durumuna geçer ve sıralamada "sahip olunanlar" grubuna taşınır (E31 kapanınca).
6. Ekran okuyucu (`polite`): `market.satin_alindi_okuyucu`.
7. Hareket azaltma: sayma ve parlama yok; değerler anında.

## 8. Durumlar

| Durum | E30 | E31 |
|---|---|---|
| Boş | Hiç eşya yok: `bos-market` + `market.bos_baslik` + `market.bos_aciklama`. Filtre sonucu boş: `bos-filtre` + `market.filtre_bos` + düğme `market.filtre_bos_dugme`. | Geçerli değil (eşya seçilmeden açılmaz). |
| Yükleniyor | Sayfa sunucuda çizilir; filtre istemcide uygulanır, yükleme yok. Eşya ikonları yüklenene kadar çerçeve içinde G05 iskelet. | Karakter ve eşya katmanı görselleri yüklenene kadar önizleme alanında G05 iskelet; eylem düğmeleri etkindir. Satın alma/kuşanma sırasında G06. |
| Hata | Sayfa verisi alınamazsa `ortak.sayfa_hatasi_baslik` + `ortak.sayfa_hatasi_aciklama` + `ortak.yeniden_yukle`. | §3.2: `market.hata_yetersiz_altin`, `market.hata_seviye`, `market.hata_zaten_sahip`, `market.hata_bulunamadi`, `market.hata_satin_alinamadi`, `ortak.hata_kusanilamadi`. Katman görseli yüklenemezse önizleme eşyasız gösterilir ve altında `market.detay_onizleme_yok`. |
| İstek başarısız / çevrimdışı | G04 bandı; ızgara gezilebilir. | Deneme çalışır (istemci tarafı); "Satın al" ve "Kuşan/Çıkar" devre dışı, dokununca `ortak.cevrimdisi_islem`. Zaman aşımında G03 `ortak.istek_basarisiz`. |
| Kilitli | Seviye kilitli kartlarda kilit katmanı + `market.kart_kilit`. | `market.detay_kilit_dugme` + `market.detay_kilit_aciklama`. |
| Başarı | Kart durumu güncellenir. | K07; kuşanınca `ortak.kusanildi_bildirim`, çıkarınca `ortak.cikarildi_bildirim`. |

## 9. Geri alma kuralları

- Satın alma geri alınamaz; bu yüzden M05 onayı zorunludur ve kalan altın açıkça gösterilir.
- Kuşanma/çıkarma serbestçe tekrarlanabilir; ayrıca geri alma gerekmez.
- Deneme hiçbir şeyi değiştirmez.

## 10. Erişilebilirlik

- Sayfa başlığı (`<h1>`, görsel olarak üst barda): `market.baslik`.
- Filtre çipleri: `role="group"` + `aria-label` = `ortak.filtre_etiketi`; her çip `button` + `aria-pressed`.
- Eşya kartı: `button`, erişilebilir adı `market.kart_etiketi` (ad, nadirlik, tür, fiyat, durum). İkon `alt=""`.
- E31: `role="dialog"`, `aria-modal="true"`, başlık eşya adı; açılınca odak başlığa, kapanınca karta döner.
- Karakter önizlemesi: `role="img"`, `aria-label` = `market.detay_onizleme_etiketi`.
- "Önceki hâli": `button` + `aria-pressed`; etiket `market.detay_onceki_hal_etiketi`.
- Kilit ve nadirlik yalnızca renkle değil metinle de verilir.
- Dokunma hedefleri: kart en az 160 px yükseklik; çipler en az 44 px yükseklik; eylem düğmesi 52 px.
- Altın miktarı her yerde `ortak.altin_etiketi` ile okunur ("1.250 altın").

## 11. Veri ihtiyacı

| Bilgi | Bugünkü kodda | Not |
|---|---|---|
| Eşyalar: kimlik, ad, açıklama, tür, fiyat, gereken seviye | Var (`MarketEsyalari`) | |
| Eşya nadirliği | Yok | **YENİ VERİ** |
| Eşya ikon görseli ve karakter katmanı görseli | Yok (Font Awesome sınıfı + koda gömülü SVG) | **YENİ VERİ** |
| Oyuncu bu eşyaya sahip mi, kuşanılmış mı | Sahiplik var; kuşanma Market'te sorgulanmıyor | Kuşanma bilgisi Market'e eklenmeli |
| Karakter seviyesi (00 §1.4 formülüyle) | Var ama **farklı formülle** hesaplanıyor | Tek formül kullanılmalı (rapora bakınız) |
| Altın | Var | Tam sayı |
| Görünüm renkleri ve kuşanılmış eşyalar (deneme önizlemesi için) | Var (`AvatarVerisi`, envanter) | |
| Son karakter seviyesi atlamasında açılan eşyalar | Yok | **YENİ VERİ** (K03'ten gelen bilgi; sayfa parametresi ile taşınabilir) |

| İşlem | Bugünkü kodda | Başarı yanıtı | Ret gerekçeleri |
|---|---|---|---|
| Satın al | Var (`buy_item_id`) | Yeni altın toplamı, eşyanın sahiplik durumu | Yetersiz altın (güncel altınla), seviye yetmiyor, zaten sahip, eşya yok |
| Kuşan | Var (`equip_item_id`) | Kuşanılan eşya, aynı türden çıkarılan eşya (varsa) | Sahip değil, eşya yok |
| Çıkar | Yok | Çıkarılan eşya | Kuşanılmamış |
