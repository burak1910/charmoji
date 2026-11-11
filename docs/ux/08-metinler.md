# 08 · Arayüz metinleri

Tüm arayüz metinlerinin tek kaynağıdır. Ekran dosyalarında (`02`–`07`) ve `00` dosyasında geçen her metin anahtarı burada tanımlıdır; burada tanımlı her anahtar en az bir ekran dosyasında kullanılır (doğrulama yöntemi: `09` dosyasının sonu ve teslim raporu).

Kurallar:

- Anahtar biçimi: `alan.ad`, yalnızca küçük ASCII harf, rakam ve alt çizgi.
- `{degisken}` yer tutucuları çalışma anında doldurulur; değişken listesi bu dosyanın sonundadır. Sayılar Türkçe biçimde yazılır (00 §3.6). Türkçede sayıdan sonra isim tekil kaldığı için ayrı çoğul biçim gerekmez ("1 görev", "5 görev").
- Değişkenden hemen sonra ek gelmez (00 §4.1, kural 9).
- Kullanıcı girdisi olan değişkenler (`{ad}`, `{gorev}`, `{arama}`) her zaman HTML kaçışlanır.
- "Ekran okuyucu" yazan metinler görünmez; `aria-label` ya da `aria-live` bölgesinde kullanılır.
- Eşya adları, eşya açıklamaları ve meydan okuma başlık/açıklamaları içerik verisidir; bu dosyada yer almaz (içerik terim kuralları için 03 §10.1).

## Ortak (tüm ekranlar)

| Anahtar | Metin | Değişkenler | Kullanıldığı yer |
|---|---|---|---|
| `ortak.alt_menu_etiketi` | Ana menü | – | G01 |
| `ortak.menu_karargah` | Karargah | – | G01 |
| `ortak.menu_gorevler` | Görevler | – | G01 |
| `ortak.menu_market` | Market | – | G01 |
| `ortak.menu_lonca` | Lonca | – | G01 |
| `ortak.menu_karakter` | Karakter | – | G01 |
| `ortak.rozet_odul_etiketi` | alınmamış ödül var | – | G01 Görevler rozeti (sekme adına eklenir) |
| `ortak.rozet_istek_etiketi` | {sayi} bekleyen istek | `{sayi}` | G01 Lonca rozeti |
| `ortak.rozet_sandik_etiketi` | sandık hazır | – | G01 Karargah rozeti |
| `ortak.seviye_kisa` | Sv. {seviye} | `{seviye}` | G02, satırlar, kartlar |
| `ortak.seviye_uzun` | Seviye {seviye} | `{seviye}` | E50, E44 |
| `ortak.seviye_rozeti_etiketi` | Seviye {seviye}. Karakterini aç. | `{seviye}` | G02 (ekran okuyucu) |
| `ortak.altin_etiketi` | {altin} altın | `{altin}` | G02 ve tüm altın gösterimleri (ekran okuyucu) |
| `ortak.sonraki_seviye` | Seviye {sonraki} için {kalan} yetenek seviyesi daha | `{sonraki}`, `{kalan}` | E10, E50 |
| `ortak.geri` | Geri | – | G02 alt ekranlarda |
| `ortak.kapat` | Kapat | – | Modallar |
| `ortak.vazgec` | Vazgeç | – | M03, M04, M05, M06, M08 |
| `ortak.kaydet` | Kaydet | – | E51 |
| `ortak.geri_al` | Geri al | – | G03 bildirimleri (görev tamamlama, silme) |
| `ortak.tekrar_dene` | Tekrar dene | – | İstek başarısız durumları |
| `ortak.yeniden_yukle` | Yeniden yükle | – | Sayfa hatası |
| `ortak.yukleniyor` | Yükleniyor | – | G06 (ekran okuyucu) |
| `ortak.hata_genel` | Bir şeyler ters gitti. Birazdan tekrar dene. | – | Beklenmeyen sunucu hataları |
| `ortak.istek_basarisiz` | İstek ulaşmadı. Bağlantını kontrol edip tekrar dene. | – | İstek başarısız / zaman aşımı |
| `ortak.cevrimdisi_bandi` | Bağlantı yok. İnternete bağlanınca kaldığın yerden devam edebilirsin. | – | G04 |
| `ortak.cevrimici_geri_geldi` | Bağlantı geri geldi. | – | G04 |
| `ortak.cevrimdisi_islem` | Bunun için internet bağlantısı gerekiyor. | – | G04, devre dışı düğmeler |
| `ortak.oturum_bitti_baslik` | Oturumun sona erdi | – | G08 |
| `ortak.oturum_bitti_aciklama` | Güvenliğin için tekrar giriş yapman gerekiyor. İlerlemen kayıtlı. | – | G08 |
| `ortak.tekrar_giris_yap` | Tekrar giriş yap | – | G08 |
| `ortak.sayfa_hatasi_baslik` | Bu sayfa yüklenemedi | – | Tüm sekmelerin sayfa hatası |
| `ortak.sayfa_hatasi_aciklama` | Bir şeyler ters gitti. Sayfayı yeniden yüklemeyi dene. | – | Tüm sekmelerin sayfa hatası |
| `ortak.kaydedilmedi_baslik` | Değişiklikler kaydedilmedi | – | M07 |
| `ortak.kaydedilmedi_aciklama` | Çıkarsan yaptığın değişiklikler kaybolur. | – | M07 |
| `ortak.kaydedilmedi_cik` | Kaydetmeden çık | – | M07 |
| `ortak.kaydedilmedi_kal` | Düzenlemeye dön | – | M07 |
| `ortak.yetenek_zeka` | Zeka | – | Yetenek adı (her yerde) |
| `ortak.yetenek_guc` | Güç | – | Yetenek adı (her yerde) |
| `ortak.yetenek_sosyallik` | Sosyallik | – | Yetenek adı (her yerde) |
| `ortak.yetenek_disiplin` | Disiplin | – | Yetenek adı (her yerde) |
| `ortak.yetenek_yaraticilik` | Yaratıcılık | – | Yetenek adı (her yerde) |
| `ortak.yetenek_seviye_etiketi` | {yetenek} Sv. {seviye} | `{yetenek}`, `{seviye}` | E20 yetenek şeridi |
| `ortak.yetenek_bar_etiketi` | {yetenek}, seviye {seviye}, {mevcut} / {gereken} XP | `{yetenek}`, `{seviye}`, `{mevcut}`, `{gereken}` | Yetenek barları (ekran okuyucu) |
| `ortak.xp_ilerleme` | {mevcut} / {gereken} XP | `{mevcut}`, `{gereken}` | E50 yetenek kartı, E20 yetenek şeridi |
| `ortak.tur_sapka` | Şapka | – | Eşya türü |
| `ortak.tur_gozluk` | Gözlük | – | Eşya türü |
| `ortak.tur_maske` | Maske | – | Eşya türü |
| `ortak.tur_kolye` | Kolye | – | Eşya türü |
| `ortak.filtre_tumu` | Tümü | – | E30, E52 filtre çipi |
| `ortak.filtre_etiketi` | Türe göre filtrele | – | E30, E52 (ekran okuyucu) |
| `ortak.kusan` | Kuşan | – | E31, E52, K07 bildirimi |
| `ortak.cikar` | Çıkar | – | E31, E52 |
| `ortak.kusanildi` | Kuşanıldı | – | E30 kart, E31, E52 rozet |
| `ortak.kusanildi_bildirim` | {esya} kuşanıldı | `{esya}` | E31, E52 |
| `ortak.cikarildi_bildirim` | {esya} çıkarıldı | `{esya}` | E31, E52 |
| `ortak.ayni_tur_uyari` | Kuşanırsan {eski} çıkarılır. | `{eski}` | E31, E52 |
| `ortak.hata_kusanilamadi` | Eşya kuşanılamadı. Birazdan tekrar dene. | – | E31, E52 |
| `ortak.hata_cikarilamadi` | Eşya çıkarılamadı. Birazdan tekrar dene. | – | E52 |

## Karargah (E10)

| Anahtar | Metin | Değişkenler | Kullanıldığı yer |
|---|---|---|---|
| `karargah.baslik` | Karargah | – | E10 başlık |
| `karargah.selam` | Merhaba, {ad} | `{ad}` | E10 |
| `karargah.karakter_etiketi` | Karakterin {ad}, seviye {seviye}. Dokun, selam versin. | `{ad}`, `{seviye}` | E10 karakter düğmesi (ekran okuyucu) |
| `karargah.ruh_hali_hazir` | Hazırım. Bugün ne yapıyoruz? | – | E10 konuşma balonu |
| `karargah.ruh_hali_neseli` | Harika gidiyoruz, devam. | – | E10 konuşma balonu |
| `karargah.ruh_hali_zafer` | Bugünü fethettik! | – | E10 konuşma balonu, K05 |
| `karargah.ruh_hali_uykulu` | Biraz paslanmışım. Bir görevle ısınalım mı? | – | E10 konuşma balonu |
| `karargah.ruh_hali_gorev_yok` | Bir görev ekle, maceraya çıkalım. | – | E10 konuşma balonu (boş) |
| `karargah.dokunma_sozu_1` | Hadi bir görev daha! | – | E10 karaktere dokunma |
| `karargah.dokunma_sozu_2` | Serimizi koruyalım. | – | E10 karaktere dokunma |
| `karargah.dokunma_sozu_3` | Yeni bir şapka fena olmazdı. | – | E10 karaktere dokunma |
| `karargah.dokunma_sozu_4` | Loncada neler oluyor acaba? | – | E10 karaktere dokunma |
| `karargah.dokunma_sozu_5` | Bugün hangi yeteneği geliştiriyoruz? | – | E10 karaktere dokunma |
| `karargah.bugun_ilerleme` | Bugün {tamamlanan}/{toplam} görev | `{tamamlanan}`, `{toplam}` | E10 günün ilerlemesi |
| `karargah.siradaki_baslik` | Sıradaki görevler | – | E10 |
| `karargah.tum_gorevler` | Tüm görevler ({toplam}) | `{toplam}` | E10 → E20 |
| `karargah.hepsi_tamam_baslik` | Bugünün tüm görevleri tamam | – | E10, K05 |
| `karargah.hepsi_tamam_aciklama` | Yarın yeni bir gün. Serilerin seni bekliyor. | – | E10, K05 |
| `karargah.bos_baslik` | Maceran burada başlıyor | – | E10 boş |
| `karargah.bos_aciklama` | Henüz görevin yok. İlk görevini ekle, karakterin güçlenmeye başlasın. | – | E10 boş |
| `karargah.bos_dugme` | İlk görevini ekle | – | E10 boş |
| `karargah.yeni_gun_bildirim` | Yeni bir gün başladı. Görevlerin yenilendi. | – | E10, E20 gün değişince |

## Günlük sandık (E10 kartı, M01, K05, K06)

| Anahtar | Metin | Değişkenler | Kullanıldığı yer |
|---|---|---|---|
| `sandik.kart_kilitli_baslik` | Günlük sandık | – | E10 sandık kartı |
| `sandik.kart_kilitli_aciklama` | Tüm görevlerini bitir, sandık açılsın. {kalan} görev kaldı. | `{kalan}` | E10 sandık kartı (kilitli) |
| `sandik.kart_gorev_yok` | Görev ekle, günlük sandığın kilidi açılsın. | – | E10 sandık kartı (görev yok) |
| `sandik.kart_hazir_baslik` | Sandık hazır! | – | E10 sandık kartı (hazır) |
| `sandik.kart_hazir_dugme` | Sandığı aç | – | E10 sandık kartı |
| `sandik.kart_acildi_baslik` | Bugünkü sandık açıldı | – | E10 sandık kartı (açıldı) |
| `sandik.kart_acildi_aciklama` | {altin} altın kazandın. Yarın yenisi gelecek. | `{altin}` | E10 sandık kartı (açıldı) |
| `sandik.hazir_bildirim` | Günlük sandığın hazır! | – | K05 bildirimi |
| `sandik.hazir_bildirim_dugme` | Aç | – | K05 bildirimi |
| `sandik.modal_baslik` | Günlük sandık | – | M01 |
| `sandik.modal_aciklama` | Bugünkü {gorev_sayisi} görevin tamam. Ödülün içeride. | `{gorev_sayisi}` | M01 |
| `sandik.modal_ac_dugme` | Sandığı aç | – | M01 |
| `sandik.modal_odul` | +{altin} altın | `{altin}` | M01, K06 |
| `sandik.modal_odul_aciklama` | Bugün tamamladığın {gorev_sayisi} görevin ödülü | `{gorev_sayisi}` | M01, K06 |
| `sandik.modal_topla_dugme` | Topla | – | M01 |
| `sandik.acildi_okuyucu` | Sandık açıldı. {altin} altın kazandın. Toplam altın {toplam}. | `{altin}`, `{toplam}` | K06 (ekran okuyucu) |
| `sandik.hata_kilitli` | Sandık henüz açılmadı. Önce bugünkü tüm görevlerini bitir. | – | M01 hata |
| `sandik.hata_zaten_acildi` | Bu sandığı bugün zaten açtın. | – | M01 hata |
| `sandik.hata_sure_doldu` | Gün değişti, bu sandığın süresi doldu. | – | M01 hata |

## Görevler (E20), görev kartı, geri alma (M03, M04)

| Anahtar | Metin | Değişkenler | Kullanıldığı yer |
|---|---|---|---|
| `gorevler.baslik` | Görevler | – | E20 başlık |
| `gorevler.bugun_ozet` | Bugün {tamamlanan}/{toplam} tamamlandı | `{tamamlanan}`, `{toplam}` | E20 |
| `gorevler.ekle_dugme` | Görev ekle | – | E20 |
| `gorevler.yapilacaklar_baslik` | Yapılacaklar ({sayi}) | `{sayi}` | E20 |
| `gorevler.tamamlananlar_baslik` | Tamamlananlar ({sayi}) | `{sayi}` | E20 |
| `gorevler.kart_odul` | +{xp} XP | `{xp}` | Görev kartı |
| `gorevler.kart_kazanildi` | +{xp} XP kazanıldı | `{xp}` | Görev kartı (tamamlandı) |
| `gorevler.kart_seri` | {seri} gün | `{seri}` | Görev kartı |
| `gorevler.kart_seri_yok` | Seri yok | – | Görev kartı |
| `gorevler.kart_bonus` | ×{carpan} | `{carpan}` | Görev kartı (çarpan > 1) |
| `gorevler.kart_tamamlandi` | Tamamlandı | – | Görev kartı |
| `gorevler.kart_onay_etiketi` | {gorev}. Tamamla: {xp} XP, {yetenek}. | `{gorev}`, `{xp}`, `{yetenek}` | Görev kartı onay kutusu (ekran okuyucu) |
| `gorevler.kart_tamamlandi_etiketi` | {gorev}. Tamamlandı, {xp} XP kazanıldı. Geri almak için seç. | `{gorev}`, `{xp}` | Görev kartı (ekran okuyucu) |
| `gorevler.kart_seri_bozuldu` | Önceki serin {onceki} gündü. Yeni bir seri başlatmak için harika bir gün. | `{onceki}` | Görev kartı (seri bozulmuş) |
| `gorevler.kart_seri_etiketi` | {seri} günlük seri, bonus ×{carpan}. Ayrıntılar. | `{seri}`, `{carpan}` | Görev kartı seri düğmesi (ekran okuyucu) |
| `gorevler.kart_menu_etiketi` | {gorev} için seçenekler | `{gorev}` | Görev kartı menü düğmesi |
| `gorevler.kart_menu_duzenle` | Düzenle | – | Görev kartı menüsü |
| `gorevler.kart_menu_sil` | Sil | – | Görev kartı menüsü |
| `gorevler.tamamlandi_bildirim` | Tamamlandı: {gorev} | `{gorev}` | K01 bildirimi |
| `gorevler.geri_alindi_bildirim` | Geri alındı. {xp} XP düşüldü. | `{xp}` | Geri alma bildirimi |
| `gorevler.zaten_tamamlandi` | Bu görev bugün zaten tamamlanmış. | – | Tamamlama hatası |
| `gorevler.hata_tamamlanamadi` | Görev tamamlanamadı. Birazdan tekrar dene. | – | Tamamlama hatası |
| `gorevler.hata_geri_alinamadi` | Geri alınamadı. Birazdan tekrar dene. | – | Geri alma hatası |
| `gorevler.hata_gun_bitti` | Gün değişti. Geçmiş günlerdeki görevler değiştirilemez. | – | Geri alma hatası |
| `gorevler.hata_silinemedi` | Görev silinemedi. Birazdan tekrar dene. | – | M03 hata |
| `gorevler.bos_baslik` | Henüz görevin yok | – | E20 boş |
| `gorevler.bos_aciklama` | İlk görevini ekle, maceran başlasın. | – | E20 boş |
| `gorevler.bos_dugme` | İlk görevini ekle | – | E20 boş |
| `gorevler.okuyucu_tamamlandi` | {gorev} tamamlandı. {xp} XP kazandın. {yetenek}: {mevcut} / {gereken} XP. | `{gorev}`, `{xp}`, `{yetenek}`, `{mevcut}`, `{gereken}` | K01 (ekran okuyucu) |
| `gorevler.okuyucu_geri_alindi` | {gorev} geri alındı. {xp} XP düşüldü. | `{gorev}`, `{xp}` | Geri alma (ekran okuyucu) |
| `gorevler.okuyucu_yetenek_seviye` | Tebrikler, {yetenek} seviye {seviye} oldu. | `{yetenek}`, `{seviye}` | K02 (ekran okuyucu) |
| `gorevler.okuyucu_seviye_dustu` | {yetenek} seviye {seviye} oldu. | `{yetenek}`, `{seviye}` | Geri almada seviye düşüşü (ekran okuyucu) |
| `gorevler.silindi_bildirim` | Görev silindi. | – | Silme bildirimi |
| `gorevler.silme_geri_alindi` | Görev geri getirildi. | – | Silmeyi geri alma bildirimi |
| `gorevler.eklendi_bildirim` | Yeni görev eklendi. | – | Ekleme bildirimi |
| `gorevler.guncellendi_bildirim` | Görev güncellendi. | – | Düzenleme bildirimi |
| `gorevler.sinir_dolu` | En fazla {sinir} görevin olabilir. Yenisini eklemek için birini sil. | `{sinir}` | E20 kilitli |
| `gorevler.sil_onay_baslik` | Görev silinsin mi? | – | M03 |
| `gorevler.sil_onay_aciklama` | {gorev} listenden kalkar ve serisi sona erer. Kazandığın XP ve altın sende kalır. | `{gorev}` | M03 |
| `gorevler.sil_onay_dugme` | Sil | – | M03 |
| `gorevler.geri_al_onay_baslik` | Geri alınsın mı? | – | M04 |
| `gorevler.geri_al_onay_aciklama` | {gorev} tamamlanmamış sayılır, {xp} XP düşülür ve seri bir gün geri gider. | `{gorev}`, `{xp}` | M04 |
| `gorevler.geri_al_onay_dugme` | Geri al | – | M04 |

## Günlük meydan okumalar (E20, K08)

| Anahtar | Metin | Değişkenler | Kullanıldığı yer |
|---|---|---|---|
| `gorevler.meydan_baslik` | Günlük meydan okumalar | – | E20 |
| `gorevler.meydan_yenilenme` | Yenilenmeye {saat} sa {dakika} dk | `{saat}`, `{dakika}` | E20 |
| `gorevler.meydan_odul` | {altin} altın | `{altin}` | Meydan okuma kartı |
| `gorevler.meydan_ilerleme` | {mevcut}/{hedef} | `{mevcut}`, `{hedef}` | Meydan okuma kartı |
| `gorevler.meydan_odulu_al` | Ödülü al | – | Meydan okuma kartı |
| `gorevler.meydan_alindi` | Alındı | – | Meydan okuma kartı |
| `gorevler.meydan_devam` | Devam ediyor | – | Meydan okuma kartı |
| `gorevler.meydan_kaybolur_uyari` | Alınmayan ödüller gece yarısı kaybolur. | – | E20 |
| `gorevler.meydan_bos` | Bugün için meydan okuma yok. Yarın yine bak. | – | E20 boş |
| `gorevler.meydan_tamamlandi_bildirim` | Meydan okuma tamamlandı: {baslik}. Ödülünü almayı unutma. | `{baslik}` | K01 sonrası bildirim |
| `gorevler.meydan_hata_alinamadi` | Ödül alınamadı. Birazdan tekrar dene. | – | K08 hata |
| `gorevler.meydan_hata_zaten` | Bu ödülü zaten aldın. | – | K08 hata |
| `gorevler.meydan_hata_suresi_doldu` | Bu meydan okumanın süresi doldu. | – | K08 hata |
| `gorevler.meydan_hata_tamamlanmadi` | Bu meydan okuma henüz tamamlanmadı. | – | K08 hata |
| `gorevler.meydan_odul_etiketi` | {baslik}. İlerleme {mevcut}/{hedef}. Ödül {altin} altın. | `{baslik}`, `{mevcut}`, `{hedef}`, `{altin}` | Meydan okuma kartı (ekran okuyucu) |
| `gorevler.meydan_odul_alindi_okuyucu` | {altin} altın aldın. Toplam altın {toplam}. | `{altin}`, `{toplam}` | K08 (ekran okuyucu) |

## Görev formu (E21, E03b)

| Anahtar | Metin | Değişkenler | Kullanıldığı yer |
|---|---|---|---|
| `gorev_form.ekle_baslik` | Yeni görev | – | E21 |
| `gorev_form.duzenle_baslik` | Görevi düzenle | – | E21 |
| `gorev_form.ad_etiketi` | Görev adı | – | E21, E03b |
| `gorev_form.ad_ipucu` | Örn: 30 sayfa kitap oku | – | E21, E03b |
| `gorev_form.ad_sayac` | {uzunluk}/60 | `{uzunluk}` | E21, E03b |
| `gorev_form.oneri_baslik` | Fikir lazım mı? | – | E21 |
| `gorev_form.oneri_1` | 30 sayfa kitap oku | – | Öneri çipi (Zeka) |
| `gorev_form.oneri_2` | 20 dakika yürüyüş yap | – | Öneri çipi (Güç) |
| `gorev_form.oneri_3` | Bir arkadaşını ara | – | Öneri çipi (Sosyallik) |
| `gorev_form.oneri_4` | Yatağını topla | – | Öneri çipi (Disiplin) |
| `gorev_form.oneri_5` | 10 dakika çizim yap | – | Öneri çipi (Yaratıcılık) |
| `gorev_form.yetenek_etiketi` | Hangi yeteneği geliştirir? | – | E21, E03b |
| `gorev_form.yetenek_aciklama_zeka` | Okumak, öğrenmek, düşünmek | – | Yetenek seçeneği |
| `gorev_form.yetenek_aciklama_guc` | Spor, hareket, beden | – | Yetenek seçeneği |
| `gorev_form.yetenek_aciklama_sosyallik` | İletişim, arkadaşlık, paylaşım | – | Yetenek seçeneği |
| `gorev_form.yetenek_aciklama_disiplin` | Düzen, rutin, irade | – | Yetenek seçeneği |
| `gorev_form.yetenek_aciklama_yaraticilik` | Sanat, üretmek, hayal etmek | – | Yetenek seçeneği |
| `gorev_form.odul_onizleme` | Her tamamlamada +10 XP, {yetenek} yeteneğine. Seri büyüdükçe ödül artar. | `{yetenek}` | E21 |
| `gorev_form.yetenek_degisti_uyari` | Bundan sonraki XP {yetenek} yeteneğine gider. Şimdiye kadar kazandığın XP yerinde kalır. | `{yetenek}` | E21 düzenle |
| `gorev_form.kaydet_ekle` | Görevi ekle | – | E21 ekle |
| `gorev_form.kaydet_duzenle` | Kaydet | – | E21 düzenle |
| `gorev_form.sil_dugme` | Görevi sil | – | E21 düzenle |
| `gorev_form.hata_ad_bos` | Görevine bir ad ver. | – | E21, E03b doğrulama |
| `gorev_form.hata_ad_uzun` | Görev adı en fazla 60 karakter olabilir. | – | E21, E03b doğrulama |
| `gorev_form.hata_yetenek_yok` | Bir yetenek seç. | – | E21, E03b doğrulama |
| `gorev_form.hata_ad_ayni` | Bu adda bir görevin zaten var. | – | E21 doğrulama |
| `gorev_form.hata_kaydedilemedi` | Görev kaydedilemedi. Birazdan tekrar dene. | – | E21, E03b sunucu hatası |

## Seri bilgisi (M02)

| Anahtar | Metin | Değişkenler | Kullanıldığı yer |
|---|---|---|---|
| `seri.modal_baslik` | Seri bonusu | – | M02 |
| `seri.modal_gorev_serisi` | Bu görevin serisi: {seri} gün | `{seri}` | M02 |
| `seri.modal_simdiki_bonus` | Şu anki bonus: ×{carpan} | `{carpan}` | M02 |
| `seri.modal_sonraki_bonus` | {kalan} gün sonra bonus ×{sonraki_carpan} olur. | `{kalan}`, `{sonraki_carpan}` | M02 |
| `seri.modal_en_yuksek` | En yüksek bonustasın: ×2. | – | M02 |
| `seri.modal_kural_1` | Görevi art arda günlerde tamamladıkça seri büyür. | – | M02 |
| `seri.modal_kural_2` | Her 7 günde bonus %25 artar, en fazla ×2 olur. | – | M02 |
| `seri.modal_kural_3` | Bir gün atlarsan seri sıfırdan başlar. | – | M02 |
| `seri.modal_tablo_baslik` | Bonus tablosu | – | M02 |
| `seri.tablo_sutun_seri` | Seri (gün) | – | M02 tablo |
| `seri.tablo_sutun_bonus` | Bonus | – | M02 tablo |
| `seri.tablo_sutun_xp` | Görev başına XP | – | M02 tablo |

## Kutlamalar (K02, K03, K04)

| Anahtar | Metin | Değişkenler | Kullanıldığı yer |
|---|---|---|---|
| `kutlama.yetenek_seviye` | {yetenek} seviye {seviye}! | `{yetenek}`, `{seviye}` | K02 bandı |
| `kutlama.seviye_baslik` | Seviye atladın! | – | K03 |
| `kutlama.seviye_alt` | Artık seviye {seviye} bir maceracısın. | `{seviye}` | K03 |
| `kutlama.seviye_acilan_baslik` | Market'te kilidi açılanlar | – | K03 |
| `kutlama.seviye_acilan_yok` | Yeni eşyalar ileriki seviyelerde seni bekliyor. | – | K03 |
| `kutlama.seviye_markete_git` | Market'e göz at | – | K03 |
| `kutlama.seviye_devam` | Devam et | – | K03 |
| `kutlama.seviye_okuyucu` | Tebrikler, karakter seviyen {seviye} oldu. | `{seviye}` | K03 (ekran okuyucu) |
| `kutlama.seri_bonus` | Seri bonusu ×{carpan}! | `{carpan}` | K04 bildirimi |
| `kutlama.seri_bonus_aciklama` | {gorev}: {seri} günlük seri. | `{gorev}`, `{seri}` | K04 bildirimi |

## Market (E30, E31, M05, K07)

| Anahtar | Metin | Değişkenler | Kullanıldığı yer |
|---|---|---|---|
| `market.baslik` | Market | – | E30 başlık |
| `market.nadirlik_siradan` | Sıradan | – | Nadirlik etiketi (E30, E31, E52) |
| `market.nadirlik_nadir` | Nadir | – | Nadirlik etiketi (E30, E31, E52) |
| `market.nadirlik_efsanevi` | Efsanevi | – | Nadirlik etiketi (E30, E31, E52) |
| `market.kart_cantada` | Çantada | – | E30 kart |
| `market.kart_kilit` | Sv. {seviye} gerekir | `{seviye}` | E30 kart, E31 seviye koşulu |
| `market.kart_eksik` | {eksik} altın eksik | `{eksik}` | E30 kart |
| `market.kart_yeni` | Yeni açıldı | – | E30 kart (K03 sonrası) |
| `market.kart_etiketi` | {esya}, {nadirlik} {tur}, {fiyat} altın. {durum} | `{esya}`, `{nadirlik}`, `{tur}`, `{fiyat}`, `{durum}` | E30 kart (ekran okuyucu) |
| `market.bos_baslik` | Tüccar yolda | – | E30 boş |
| `market.bos_aciklama` | Market şu an boş. Yakında yeni eşyalar gelecek. | – | E30 boş |
| `market.filtre_bos` | Bu türde eşya yok. | – | E30 filtre boş |
| `market.filtre_bos_dugme` | Tüm eşyaları göster | – | E30 filtre boş |
| `market.detay_deneme_etiketi` | Deneme | – | E31 |
| `market.detay_deneme_aciklama` | Üzerinde böyle görünür. Satın almadan kaydedilmez. | – | E31 |
| `market.detay_onceki_hal` | Önceki hâli | – | E31 |
| `market.detay_onceki_hal_etiketi` | Basılı tutarak eşyasız hâlini gör | – | E31 (ekran okuyucu) |
| `market.detay_onizleme_etiketi` | Karakterin, {esya} ile | `{esya}` | E31 önizleme (ekran okuyucu) |
| `market.detay_onizleme_yok` | Önizleme yüklenemedi. | – | E31 hata |
| `market.detay_satin_al` | Satın al · {fiyat} altın | `{fiyat}` | E31 |
| `market.detay_eksik_dugme` | {eksik} altın eksik | `{eksik}` | E31 |
| `market.detay_eksik_aciklama` | Görevlerini tamamlayarak ve sandık açarak altın kazan. | – | E31 |
| `market.detay_gorevlere_git` | Görevlere git | – | E31 |
| `market.detay_kilit_dugme` | Sv. {gereken} gerekir | `{gereken}` | E31 kilitli |
| `market.detay_kilit_aciklama` | Seviye {gereken} olunca açılır. Şu an seviye {mevcut}. | `{gereken}`, `{mevcut}` | E31 kilitli |
| `market.satin_al_onay_baslik` | Satın almak istiyor musun? | – | M05 |
| `market.satin_al_onay_aciklama` | {esya} için {fiyat} altın ödeyeceksin. Kalan altının: {kalan}. | `{esya}`, `{fiyat}`, `{kalan}` | M05 |
| `market.satin_al_onay_not` | Satın alma geri alınamaz. | – | M05 |
| `market.satin_al_onay_dugme` | Satın al | – | M05 |
| `market.satin_alindi_bildirim` | Çantana eklendi: {esya} | `{esya}` | K07 bildirimi |
| `market.satin_alindi_okuyucu` | {esya} satın alındı. Kalan altın {kalan}. | `{esya}`, `{kalan}` | K07 (ekran okuyucu) |
| `market.hata_yetersiz_altin` | Altının yetmiyor. {eksik} altın daha lazım. | `{eksik}` | E31 hata |
| `market.hata_seviye` | Bu eşya için seviye {gereken} gerekiyor. | `{gereken}` | E31 hata |
| `market.hata_zaten_sahip` | Bu eşya zaten çantanda. | – | E31 hata |
| `market.hata_bulunamadi` | Bu eşya artık satışta değil. | – | E31 hata |
| `market.hata_satin_alinamadi` | Satın alma tamamlanamadı. Altının yerinde, birazdan tekrar dene. | – | E31 hata |

## Lonca (E40a–d, E44, M06)

| Anahtar | Metin | Değişkenler | Kullanıldığı yer |
|---|---|---|---|
| `lonca.baslik` | Lonca | – | E40 başlık |
| `lonca.sekme_siralama` | Sıralama | – | E40 iç sekme |
| `lonca.sekme_arkadaslar` | Arkadaşlar | – | E40 iç sekme |
| `lonca.sekme_istekler` | İstekler | – | E40 iç sekme |
| `lonca.sekme_bul` | Maceracı bul | – | E40 iç sekme |
| `lonca.sekme_istekler_sayili` | İstekler, {sayi} yeni | `{sayi}` | E40 iç sekme (ekran okuyucu) |
| `lonca.siralama_baslik` | Bu haftanın sıralaması | – | E40a |
| `lonca.siralama_tarih` | {baslangic} – {bitis} | `{baslangic}`, `{bitis}` | E40a |
| `lonca.siralama_kalan` | Bitmesine {gun} gün {saat} saat | `{gun}`, `{saat}` | E40a |
| `lonca.siralama_kalan_saat` | Bitmesine {saat} saat | `{saat}` | E40a (son gün) |
| `lonca.siralama_xp` | {xp} XP | `{xp}` | E40a, E40b satırı |
| `lonca.siralama_sen` | Sen | – | E40a oyuncunun satırı |
| `lonca.siralama_ust_fark` | Bir üst sıraya {fark} XP kaldı. | `{fark}` | E40a |
| `lonca.siralama_lider` | Zirvedesin. Yerini koru! | – | E40a |
| `lonca.siralama_kural` | Bu hafta görevlerden kazanılan XP sayılır. Sıralama her pazartesi sıfırlanır. | – | E40a |
| `lonca.siralama_satir_etiketi` | {sira}. sıra: {ad}, seviye {seviye}, bu hafta {xp} XP | `{sira}`, `{ad}`, `{seviye}`, `{xp}` | E40a satırı (ekran okuyucu) |
| `lonca.siralama_tek_baslik` | Bu hafta yalnızsın | – | E40a boş |
| `lonca.siralama_tek_aciklama` | Arkadaş ekle, yarış başlasın. | – | E40a boş |
| `lonca.siralama_tek_dugme` | Maceracı bul | – | E40a boş |
| `lonca.siralama_sifir` | Bu hafta henüz kimse XP kazanmadı. İlk sen ol. | – | E40a |
| `lonca.arkadaslar_sayi` | {sayi} arkadaş | `{sayi}` | E40b |
| `lonca.arkadas_menu_etiketi` | {ad} için seçenekler | `{ad}` | E40b satır menüsü (ekran okuyucu) |
| `lonca.arkadas_satir_etiketi` | {ad}, seviye {seviye}, bu hafta {xp} XP. Profili aç. | `{ad}`, `{seviye}`, `{xp}` | E40b satırı (ekran okuyucu) |
| `lonca.arkadaslar_bos_baslik` | Loncan henüz boş | – | E40b boş |
| `lonca.arkadaslar_bos_aciklama` | Arkadaşlarını bul, birlikte daha güçlü olun. | – | E40b boş |
| `lonca.arkadaslar_bos_dugme` | Maceracı bul | – | E40b boş |
| `lonca.istekler_gelen_baslik` | Gelen istekler | – | E40c |
| `lonca.istekler_giden_baslik` | Gönderdiklerin | – | E40c |
| `lonca.istek_metni` | {ad} seni arkadaş olarak eklemek istiyor | `{ad}` | E40c |
| `lonca.istek_kabul` | Kabul et | – | E40c |
| `lonca.istek_reddet` | Reddet | – | E40c |
| `lonca.istek_kabul_etiketi` | Kabul et: {ad} | `{ad}` | E40c (ekran okuyucu) |
| `lonca.istek_reddet_etiketi` | Reddet: {ad} | `{ad}` | E40c (ekran okuyucu) |
| `lonca.istek_bekliyor` | Bekliyor | – | E40c gönderilenler |
| `lonca.istek_kabul_bildirim` | {ad} artık loncanda. | `{ad}` | E40c başarı |
| `lonca.istek_red_bildirim` | İstek reddedildi. | – | E40c başarı |
| `lonca.istekler_bos` | Bekleyen istek yok. | – | E40c boş |
| `lonca.istek_hata_islenemedi` | İstek işlenemedi. Birazdan tekrar dene. | – | E40c hata |
| `lonca.istek_hata_artik_yok` | Bu istek artık geçerli değil. | – | E40c hata |
| `lonca.arama_etiketi` | Kahraman adıyla ara | – | E40d |
| `lonca.arama_ipucu` | Kahraman adı yaz | – | E40d |
| `lonca.arama_kisa` | Aramak için en az 2 harf yaz. | – | E40d |
| `lonca.arama_sonuc_yok` | "{arama}" adında bir maceracı bulunamadı. | `{arama}` | E40d boş |
| `lonca.arama_sonuc_sayisi` | {sayi} sonuç bulundu | `{sayi}` | E40d (ekran okuyucu) |
| `lonca.arama_hata` | Arama yapılamadı. Birazdan tekrar dene. | – | E40d hata |
| `lonca.oneriler_baslik` | Önerilen maceracılar | – | E40d |
| `lonca.oneriler_bos` | Şimdilik önerecek kimse yok. | – | E40d boş |
| `lonca.istek_gonder` | İstek gönder | – | E40d |
| `lonca.istek_gonder_etiketi` | İstek gönder: {ad} | `{ad}` | E40d (ekran okuyucu) |
| `lonca.istek_gonderildi` | İstek gönderildi | – | E40d (devre dışı düğme) |
| `lonca.istek_gonderildi_bildirim` | Arkadaşlık isteği gönderildi. | – | E40d başarı |
| `lonca.istek_gonder_hata` | İstek gönderilemedi. Birazdan tekrar dene. | – | E40d hata |
| `lonca.istek_zaten_var` | Bu maceracıyla aranızda zaten bir istek ya da arkadaşlık var. | – | E40d hata |
| `lonca.istek_karsi_taraf_gonderdi` | Bu maceracı sana zaten istek göndermiş. İstekler bölümüne bak. | – | E40d hata |
| `lonca.profil_haftalik` | Bu hafta {xp} XP · {sira}. sıra | `{xp}`, `{sira}` | E44 |
| `lonca.profil_yetenekler_baslik` | Yetenekler | – | E44 |
| `lonca.profil_gizlilik_notu` | Görev adları ve seriler yalnızca sahibine görünür. | – | E44 |
| `lonca.profil_cikar` | Arkadaşlıktan çıkar | – | E44, E40b satır menüsü |
| `lonca.profil_hata_yok` | Bu maceracı artık loncanda değil. | – | E44 hata |
| `lonca.cikar_onay_baslik` | Arkadaşlıktan çıkarılsın mı? | – | M06 |
| `lonca.cikar_onay_aciklama` | {ad} loncandan ve haftalık sıralamandan çıkar. Ona bildirim gitmez. | `{ad}` | M06 |
| `lonca.cikar_onay_dugme` | Çıkar | – | M06 |
| `lonca.cikarildi_bildirim` | Arkadaşlıktan çıkarıldı. | – | M06 başarı |
| `lonca.cikar_hata` | Arkadaşlıktan çıkarılamadı. Birazdan tekrar dene. | – | M06 hata |

## Karakter (E50, M08)

| Anahtar | Metin | Değişkenler | Kullanıldığı yer |
|---|---|---|---|
| `karakter.baslik` | Karakter | – | E50 başlık |
| `karakter.karakter_etiketi` | Karakterin {ad}, seviye {seviye}. Kuşanılanlar: {esyalar}. | `{ad}`, `{seviye}`, `{esyalar}` | E50 karakter görseli (ekran okuyucu) |
| `karakter.gorunum_dugme` | Görünüm | – | E50 |
| `karakter.canta_dugme` | Çanta | – | E50 |
| `karakter.yuvalar_baslik` | Kuşanılanlar | – | E50 |
| `karakter.yuva_bos` | Boş | – | E50 eşya yuvası |
| `karakter.yuva_etiketi` | {tur}: {esya}. Çantayı aç. | `{tur}`, `{esya}` | E50 eşya yuvası (ekran okuyucu) |
| `karakter.yuva_bos_etiketi` | {tur} boş. Çantayı aç. | `{tur}` | E50 eşya yuvası (ekran okuyucu) |
| `karakter.yetenekler_baslik` | Yetenekler | – | E50 |
| `karakter.yetenek_kalan` | Seviye {sonraki} için {kalan} XP | `{sonraki}`, `{kalan}` | E50 yetenek kartı |
| `karakter.yetenek_gorev_sayisi` | Bu yeteneğe bağlı {sayi} görev | `{sayi}` | E50 yetenek kartı |
| `karakter.yetenek_gorev_yok` | Bu yeteneğe bağlı görevin yok. | – | E50 yetenek kartı |
| `karakter.yetenek_gorev_ekle` | Görev ekle | – | E50 yetenek kartı |
| `karakter.yetenek_ipucu_denge` | Karakter seviyen yeteneklerinin ortalamasıdır. En düşük yeteneğini yükseltmek seni en hızlı ilerletir. | – | E50 |
| `karakter.hesap_baslik` | Hesap | – | E50 |
| `karakter.hesap_kahraman_adi` | Kahraman adı | – | E50 |
| `karakter.hesap_eposta` | E-posta | – | E50 |
| `karakter.cikis_dugme` | Çıkış yap | – | E50 |
| `karakter.cikis_onay_baslik` | Çıkış yapılsın mı? | – | M08 |
| `karakter.cikis_onay_aciklama` | İlerlemen ve serilerin kayıtlı kalır. | – | M08 |
| `karakter.cikis_onay_dugme` | Çıkış yap | – | M08 |

## Görünüm (E51, E03a)

| Anahtar | Metin | Değişkenler | Kullanıldığı yer |
|---|---|---|---|
| `gorunum.baslik` | Görünüm | – | E51 |
| `gorunum.ten` | Ten | – | E51, E03a |
| `gorunum.sac` | Saç | – | E51, E03a |
| `gorunum.goz` | Gözler | – | E51, E03a |
| `gorunum.kiyafet` | Üst giysi | – | E51, E03a |
| `gorunum.secili` | Seçili: {secenek} | `{secenek}` | E51 bölge başlığı |
| `gorunum.kaydedildi_bildirim` | Görünüm kaydedildi. | – | E51 başarı |
| `gorunum.hata_kaydedilemedi` | Görünüm kaydedilemedi. Birazdan tekrar dene. | – | E51, E03a hata |
| `gorunum.hata_gecersiz_secenek` | Bu seçenek kullanılamıyor. Başka bir seçenek dene. | – | E51 hata |
| `gorunum.secenek_ten_1` | Açık | – | Seçenek adı (`karakter-ten-1`) |
| `gorunum.secenek_ten_2` | Buğday | – | Seçenek adı (`karakter-ten-2`) |
| `gorunum.secenek_ten_3` | Esmer | – | Seçenek adı (`karakter-ten-3`) |
| `gorunum.secenek_ten_4` | Koyu | – | Seçenek adı (`karakter-ten-4`) |
| `gorunum.secenek_sac_1` | Kısa siyah | – | Seçenek adı (`karakter-sac-1`) |
| `gorunum.secenek_sac_2` | Uzun kahverengi | – | Seçenek adı (`karakter-sac-2`) |
| `gorunum.secenek_sac_3` | Dağınık sarı | – | Seçenek adı (`karakter-sac-3`) |
| `gorunum.secenek_sac_4` | Kıvırcık kızıl | – | Seçenek adı (`karakter-sac-4`) |
| `gorunum.secenek_sac_5` | Topuz siyah | – | Seçenek adı (`karakter-sac-5`) |
| `gorunum.secenek_sac_6` | Kar beyazı kâkül | – | Seçenek adı (`karakter-sac-6`) |
| `gorunum.secenek_goz_1` | Koyu | – | Seçenek adı (`karakter-goz-1`) |
| `gorunum.secenek_goz_2` | Ela | – | Seçenek adı (`karakter-goz-2`) |
| `gorunum.secenek_goz_3` | Mavi | – | Seçenek adı (`karakter-goz-3`) |
| `gorunum.secenek_ust_1` | Mor tunik | – | Seçenek adı (`karakter-ust-1`) |
| `gorunum.secenek_ust_2` | Mavi gömlek | – | Seçenek adı (`karakter-ust-2`) |
| `gorunum.secenek_ust_3` | Yeşil yelek | – | Seçenek adı (`karakter-ust-3`) |
| `gorunum.secenek_ust_4` | Kırmızı pelerin | – | Seçenek adı (`karakter-ust-4`) |

## Çanta (E52)

| Anahtar | Metin | Değişkenler | Kullanıldığı yer |
|---|---|---|---|
| `canta.baslik` | Çanta | – | E52 |
| `canta.bos_baslik` | Çantan boş | – | E52 boş |
| `canta.bos_aciklama` | Market'te seni bekleyen eşyalar var. | – | E52 boş |
| `canta.bos_dugme` | Market'e git | – | E52 boş |
| `canta.filtre_bos` | Bu türde eşyan yok. | – | E52 filtre boş |
| `canta.esya_etiketi` | {esya}, {nadirlik} {tur}. {durum} | `{esya}`, `{nadirlik}`, `{tur}`, `{durum}` | E52 eşya (ekran okuyucu) |
| `canta.sec_ipucu` | Bir eşya seç, üzerinde gör. | – | E52 |
| `canta.onizleme_etiketi` | Önizleme | – | E52 |

## Tanıtım (E00)

| Anahtar | Metin | Değişkenler | Kullanıldığı yer |
|---|---|---|---|
| `tanitim.slogan` | Hayatını bir RPG gibi oyna | – | E00 |
| `tanitim.aciklama` | Her gün yapmak istediklerini göreve çevir. XP kazan, karakterini güçlendir, arkadaşlarınla yarış. | – | E00 |
| `tanitim.basla_dugme` | Maceraya başla | – | E00 |
| `tanitim.giris_dugme` | Giriş yap | – | E00 |
| `tanitim.ozellik_1_baslik` | Görevlerini tamamla | – | E00 |
| `tanitim.ozellik_1_aciklama` | Her gün yaptığın işler XP kazandırır; seri büyüdükçe ödül artar. | – | E00 |
| `tanitim.ozellik_2_baslik` | Karakterini güçlendir | – | E00 |
| `tanitim.ozellik_2_aciklama` | Beş yeteneğini geliştir, altınla eşya al, tarzını yarat. | – | E00 |
| `tanitim.ozellik_3_baslik` | Loncanla yarış | – | E00 |
| `tanitim.ozellik_3_aciklama` | Arkadaşlarını ekle, haftalık sıralamada zirveye çık. | – | E00 |
| `tanitim.cikis_bildirim` | Çıkış yaptın. Yine bekleriz. | – | E00 (çıkış sonrası) |

## Giriş (E01)

| Anahtar | Metin | Değişkenler | Kullanıldığı yer |
|---|---|---|---|
| `giris.baslik` | Giriş yap | – | E01 |
| `giris.alt_baslik` | Kaldığın yerden devam et. | – | E01 |
| `giris.kimlik_etiketi` | E-posta veya kahraman adı | – | E01 |
| `giris.sifre_etiketi` | Şifre | – | E01 |
| `giris.sifre_goster` | Şifreyi göster | – | E01, E02 |
| `giris.sifre_gizle` | Şifreyi gizle | – | E01, E02 |
| `giris.gonder` | Giriş yap | – | E01 |
| `giris.kayit_bagi` | Hesabın yok mu? Kayıt ol | – | E01 |
| `giris.hata_bos_kimlik` | E-posta ya da kahraman adını yaz. | – | E01 doğrulama |
| `giris.hata_bos_sifre` | Şifreni yaz. | – | E01 doğrulama |
| `giris.hata_yanlis` | E-posta, kahraman adı ya da şifre hatalı. | – | E01 hata |
| `giris.oturum_doldu` | Oturumun sona erdi. Tekrar giriş yap. | – | E01 bilgi |

## Kayıt (E02)

| Anahtar | Metin | Değişkenler | Kullanıldığı yer |
|---|---|---|---|
| `kayit.baslik` | Maceraya katıl | – | E02 |
| `kayit.alt_baslik` | Kahramanını oluştur, ilk görevine çık. | – | E02 |
| `kayit.ad_etiketi` | Kahraman adı | – | E02 |
| `kayit.ad_ipucu` | Diğer maceracılar seni bu adla görür. | – | E02 |
| `kayit.eposta_etiketi` | E-posta | – | E02 |
| `kayit.sifre_etiketi` | Şifre | – | E02 |
| `kayit.sifre_ipucu` | En az 8 karakter. | – | E02 |
| `kayit.sifre_tekrar_etiketi` | Şifre tekrarı | – | E02 |
| `kayit.gonder` | Hesap oluştur | – | E02 |
| `kayit.giris_bagi` | Zaten hesabın var mı? Giriş yap | – | E02 |
| `kayit.hata_ad_bos` | Bir kahraman adı seç. | – | E02 doğrulama |
| `kayit.hata_ad_uzunluk` | Kahraman adı 3–20 karakter olmalı. | – | E02 doğrulama |
| `kayit.hata_ad_karakter` | Kahraman adında yalnızca harf, rakam, boşluk ve alt çizgi kullanılabilir. | – | E02 doğrulama |
| `kayit.hata_ad_alinmis` | Bu kahraman adı alınmış. Başka bir ad dene. | – | E02 hata |
| `kayit.hata_eposta_bos` | E-posta adresini yaz. | – | E02 doğrulama |
| `kayit.hata_eposta_gecersiz` | Geçerli bir e-posta adresi yaz. | – | E02 doğrulama |
| `kayit.hata_eposta_kayitli` | Bu e-postayla zaten bir hesap var. Giriş yapmayı dene. | – | E02 hata |
| `kayit.hata_sifre_kisa` | Şifre en az 8 karakter olmalı. | – | E02 doğrulama |
| `kayit.hata_sifre_eslesmiyor` | Şifreler eşleşmiyor. | – | E02 doğrulama |
| `kayit.hata_ozet` | Formda {sayi} hata var. | `{sayi}` | E02 (ekran okuyucu) |

## Karşılama (E03a–c)

| Anahtar | Metin | Değişkenler | Kullanıldığı yer |
|---|---|---|---|
| `karsilama.adim` | Adım {adim}/3 | `{adim}` | E03a–c |
| `karsilama.atla` | Atla | – | E03a, E03b |
| `karsilama.devam` | Devam | – | E03a |
| `karsilama.karakter_baslik` | Karakterini oluştur | – | E03a |
| `karsilama.karakter_aciklama` | Görünümünü seç. İstediğin zaman Karakter sekmesinden değiştirebilirsin. | – | E03a |
| `karsilama.gorev_baslik` | İlk görevin | – | E03b |
| `karsilama.gorev_aciklama` | Her gün yapmak istediğin bir şey seç. Tamamladıkça karakterin güçlenir. | – | E03b |
| `karsilama.gorev_ekle` | Görevi ekle | – | E03b |
| `karsilama.nasil_baslik` | Nasıl oynanır? | – | E03c |
| `karsilama.nasil_1` | Görevini tamamla, XP kazan. XP yeteneklerini, yeteneklerin seviyeni yükseltir. | – | E03c |
| `karsilama.nasil_2` | Her gün devam et: her 7 günlük seride bonus %25 artar, en fazla ×2. | – | E03c |
| `karsilama.nasil_3` | Günün tüm görevlerini bitir, günlük sandığı aç, altın kazan. | – | E03c |
| `karsilama.basla` | Maceraya başla | – | E03c |

## Değişkenler

| Değişken | Anlamı |
|---|---|
| `{ad}` | Kahraman adı (kullanıcı girdisi, kaçışlanır) |
| `{adim}` | Adım numarası |
| `{altin}` | Altın miktarı (tam sayı, Türkçe biçim) |
| `{arama}` | Aranan metin (kullanıcı girdisi) |
| `{baslangic}` | Hafta başlangıcı ("10 Kas") |
| `{baslik}` | Meydan okuma başlığı (içerik) |
| `{bitis}` | Hafta bitişi ("16 Kas") |
| `{carpan}` | Çarpan, virgüllü (1,25) |
| `{dakika}` | Dakika |
| `{durum}` | Kartın durum satırı metni (boşsa atlanır) |
| `{eksik}` | Eksik altın |
| `{eski}` | Aynı türde çıkarılacak eşya adı |
| `{esya}` | Eşya adı |
| `{esyalar}` | Kuşanılan eşya adları, virgülle (yoksa "hiçbiri") |
| `{fark}` | XP farkı |
| `{fiyat}` | Fiyat (altın) |
| `{gereken}` | Gereken değer |
| `{gorev}` | Görev adı (kullanıcı girdisi) |
| `{gorev_sayisi}` | Bugün tamamlanan görev sayısı |
| `{gun}` | Gün |
| `{hedef}` | Hedef sayı |
| `{kalan}` | Kalan miktar (görev, gün, XP, altın ya da yetenek seviyesi) |
| `{mevcut}` | O seviyedeki / mevcut değer |
| `{nadirlik}` | Nadirlik adı (`market.nadirlik_*`) |
| `{onceki}` | Önceki seri (gün) |
| `{saat}` | Saat |
| `{sayi}` | Sayı |
| `{secenek}` | Seçili görünüm seçeneğinin adı (`gorunum.secenek_*`) |
| `{seri}` | Seri (gün) |
| `{seviye}` | Seviye sayısı |
| `{sinir}` | Aktif görev sınırı |
| `{sira}` | Sıra numarası |
| `{sonraki}` | Bir sonraki seviye |
| `{sonraki_carpan}` | Bir sonraki çarpan |
| `{tamamlanan}` | Tamamlanan görev sayısı |
| `{toplam}` | Toplam (altın ya da görev) |
| `{tur}` | Eşya türü (`ortak.tur_*`) |
| `{uzunluk}` | Girilen karakter sayısı |
| `{xp}` | XP miktarı |
| `{yetenek}` | Yetenek adı (`ortak.yetenek_*`) |

Toplam anahtar sayısı: 418.
