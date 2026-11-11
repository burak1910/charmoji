# 09 · Açık sorular ve öneriler

## 1. Açık sorular

Her soru için önerilen varsayılan, karar verilene kadar geliştiricinin uygulayacağı kuraldır. Ekran dosyalarında **VARSAYIM S{n}** etiketiyle işaretlidir. Sıra numarası kimliktir; aşağıdaki "Önem" sütunu karar önceliğini verir (1 = önce karar verilmeli).

| Kimlik | Soru | Önerilen varsayılan | Nerede | Önem |
|---|---|---|---|---|
| S1 | Gün ne zaman biter, hangi saat dilimine göre? | 00:00, Europe/Istanbul, sunucu saati esas. Seri, geri alma, sandık ve meydan okumalar bu sınıra göre. | 00 §1.1, 03 §8 | 1 |
| S3 | Haftalık sıralama puanı neye göre? | Hafta içinde görev tamamlamalarından kazanılan **net** XP (geri alınan düşülür). Katılımcılar: oyuncu + kabul edilmiş arkadaşları. Eşitlikte aynı sıra, alfabetik dizilim (1, 2, 2, 4). 0 XP olanlar da listelenir. | 05 §3, 03 §8.1 | 1 |
| S4 | Hafta ne zaman başlar? | Pazartesi 00:00 – Pazar 23:59, Europe/Istanbul. | 05 §3 | 1 |
| S6 | Günlük sandığın içeriği ve kuralları? | Açılma koşulu: en az 1 aktif görev ve bugünkü tüm aktif görevler tamam. Ödül: `20 + 5 × bugün tamamlanan görev`, en fazla 60 altın; altın dışında içerik yok. Günde bir kez. Açıldıktan sonra geri alma ödülü geri almaz. Gece yarısına kadar açılmazsa kaybolur. | 02 §5 | 1 |
| S12 | Seri bozulunca ne olur? | Bugünkü kural: kaçırılan günden sonra etkin seri 0 görünür, sonraki tamamlamada 1'den başlar. Önceki seri, yeni tamamlamaya kadar kartta bir kez anılır. Seri koruma/dondurma yok. | 03 §3, §7 | 1 |
| S27 | Görünüm seçeneklerinin sayısı? | Ten 4, saç 6 (biçim + renk birlikte), göz 3, üst giysi 4. Tümü ücretsiz ve açık. Her seçenek ayrı piksel çizim. | 06 §5, 01 §2.1 | 2 |
| S28 | Bugünkü hesapların serbest renk verisi nasıl taşınır? | Her bölge 1 numaralı seçeneğe döner (en yakın renge eşleme yapılmaz). | 06 §5 | 2 |
| S2 | Bugünkü "günlük görevler"in arayüz adı? | "Meydan okuma" ("Günlük meydan okumalar"), çünkü alışkanlıklar artık "görev" adını alıyor. | 00 §2, 03 §10 | 2 |
| S15 | Kahraman adı kuralları? | 3–20 karakter; harf (Türkçe dahil), rakam, boşluk, alt çizgi; büyük/küçük harf duyarsız benzersiz. Bugünkü hesaplarda çakışan adlar için arka uç bir çözüm belirlemeli (öneri: sonuna sayı eklenir, oyuncu Karakter sekmesinde görür). | 07 §3.2 | 2 |
| S11 | Nadirliğin etkisi? | Yalnızca görsel (çerçeve, etiket, efsanevide parıltı). Oyun etkisi yok. Nadirlik eşyaya sabit atanır. | 04 §2.2 | 2 |
| S5 | Alınmayan meydan okuma ödülü ne olur? | Gece yarısı kaybolur (bugünkü veri modeli zaten günlük). Arayüz bunu açıkça yazar. | 03 §10 | 2 |
| S25 | Meydan okumaların ilerlemesi hangi olaylardan gelir? | İki tür: "giriş" (günün ilk açılışında tamamlanır) ve "görev tamamlama sayısı" (her tamamlama +1, geri alma −1). Tür açık bir veri alanından okunur, başlık metninden değil. | 03 §10.1 | 2 |
| S7 | Geri alma meydan okumayı nasıl etkiler? | İlerleme 1 azalır; ödül alınmamışsa "devam ediyor"a döner; alınmışsa hiçbir şey değişmez. | 03 §8.1 | 3 |
| S8 | Geri almada seviye düşebilir mi? | Evet; sessizce düşer, kutlanmaz, ekran okuyucuya duyurulur. | 03 §8.1 | 3 |
| S24 | Gün içinde eklenen görev sandık koşuluna girer mi? | Evet; hazır ama açılmamış sandık yeniden kilitlenir. | 02 §5.1 | 3 |
| S18 | Görev silinince ne olur? | Arşivlenir; XP/altın/seviye değişmez; 5 sn "Geri al"; arşivi gösteren ekran yok. | 03 §4.4 | 3 |
| S19 | Görevin yeteneği değişince geçmiş XP? | Geçmiş XP eski yetenekte kalır; seri korunur; sonraki XP yeni yeteneğe gider. | 03 §4.3 | 3 |
| S9 | Aynı adlı iki görev olabilir mi? | Hayır, oyuncunun aktif görevleri arasında (büyük/küçük harf duyarsız). | 03 §5.1 | 3 |
| S10 | Aktif görev sınırı? | 20. Sınırda "Görev ekle" kilitli. | 03 §13 | 3 |
| S17 | Görev adı uzunluğu? | 1–60 karakter. | 03 §5.1 | 4 |
| S16 | Şifre kuralı? | En az 8 karakter (başka kural yok). | 07 §3.2 | 3 |
| S13 | Maceracı arama neye göre? | Yalnızca kahraman adı, en az 2 karakter, "içerir" eşleşmesi. E-postayla arama yok, e-posta hiç gösterilmez. | 05 §6 | 3 |
| S14 | Arkadaş profilinde ne görünür? | Görünüm, eşyalar, seviye, yetenek seviyeleri ve oranları, haftalık XP ve sıra. Görev adları, seriler, altın, e-posta görünmez. | 05 §7 | 3 |
| S20 | Karakter seviyesinin ilerlemesi nasıl anlatılır? | "Seviye {n+1} için {k} yetenek seviyesi daha"; `k = 5 × (seviye + 1) − yetenek seviyeleri toplamı`. | 02 §2.1 | 4 |
| S26 | Karakter ruh hali eşikleri? | Uykulu: bugün 0 ve dün hiç tamamlanmadı; Hazır: bugün 0; Neşeli: en az 1, hepsi değil; Zafer: hepsi. | 02 §2.2 | 4 |
| S22 | Seviye kilitli eşya denenebilir mi? | Evet (hedef göstermek için). | 04 §3.2 | 4 |
| S23 | Kuşanılmış eşya çıkarılabilir mi? | Evet ("Çıkar"); bugünkü kodda yok, eklenmeli. | 04 §3, 06 §6 | 4 |
| S21 | Satın alma iade edilebilir mi? | Hayır (bugünkü kural); bu yüzden onay zorunlu ve kalan altın gösterilir. | 04 §6 | 4 |

## 2. Öneriler (kapsam dışı; bu dalgada uygulanmaz)

| Öneri | Neden | Not |
|---|---|---|
| Hatırlatma bildirimleri (tarayıcı bildirimi / e-posta): "Serin bu gece bitiyor" | Seri kaybını azaltır, geri dönüşü artırır | Yeni altyapı ve izin akışı gerekir |
| Seri koruma ("seri kalkanı") altınla alınabilir | Bozulan seri en büyük vazgeçme anı; altına yeni harcama yeri | S12 kararına bağlı |
| Haftalık şampiyon ödülü ve "geçen haftanın sonucu" kartı | Haftalık döngüyü pazartesi kapatır | S3/S4 kararından sonra |
| Rozetler / başarımlar | Uzun vadeli hedef çeşitliliği | Tanıtım sayfası bugün var olmayan rozetleri vaat ediyor; bu iddia kaldırıldı |
| Kilitli/özel görünüm seçenekleri (seviyeyle açılan saç vb.) | Seviyeye ek ödül | S27'den sonra |
| Görev sıklığı (haftada X gün, belirli günler) | Her işin günlük olmaması gerçekçi | Seri ve sandık kurallarını değiştirir |
| Gönderilen arkadaşlık isteğini geri çekme | Yanlış istekleri düzeltmek | Küçük arka uç işi |
| Arkadaşa "dürtme" / tezahürat | Sosyal motivasyon | Sohbet olmadan hafif etkileşim |
| Sohbet | – | Moderasyon gerektirir; önerilmez |
| Şifremi unuttum, hesap ayarları (ad/e-posta/şifre değiştirme), hesap silme | Temel hesap ihtiyaçları | E-posta gönderimi gerekir |
| Çok sayıda hatalı girişte geçici kilit, istek sınırlaması | Güvenlik | Arka uç işi; arayüze `giris` kilit metni eklenir |
| Uygulama içi "hareketi azalt" ve ses ayarı | Sistem ayarını bilmeyenler için | Şimdilik yalnızca `prefers-reduced-motion` |
| Ses efektleri (tamamlama, seviye, sandık) | Oyun hissi | Varsayılan kapalı olmalı |
| Yetenek radar grafiği (eski "Yetenek Ağacı") masaüstünde | Dengeyi tek bakışta göstermek | Mobilde kaldırıldı |
| Masaüstü için iki sütunlu yerleşim | Geniş ekranı kullanmak | Bu dalgada 640 px ortalı içerik |
| Nadirliğe göre fiyat aralığı rehberi (ör. sıradan 50–200, nadir 200–600, efsanevi 600+) | İçerik ekleyenler için tutarlılık | İçerik kararı |
| Arşivlenmiş görevleri gösterme / geri getirme ekranı | S18 sonrası kaybolan görevleri kurtarmak | |
| Gerçek para ile altın | – | Önerilmez; oyun dengesi ve güven sorunları |

## 3. Tutarlılık kontrolü (nasıl yapıldı)

1. **Metin anahtarları**: `00`–`07` ve bu dosyada `(ortak|karargah|sandik|gorevler|gorev_form|seri|kutlama|market|lonca|karakter|gorunum|canta|tanitim|giris|kayit|karsilama).anahtar` biçimindeki tüm geçişler düzenli ifadeyle toplandı ve `08` tablosundaki anahtar sütunuyla iki yönlü karşılaştırıldı. Sonuç: 418 tanımlı anahtar, 418 kullanılan anahtar; tanımsız 0, kullanılmayan 0. `08` tablosu yinelenen anahtar içermez (üretim sırasında denetlendi).
2. **Terimler**: `00 §2` sözlüğündeki kod terimleri ("alışkanlık", "bakiye", "coin", "avatar", "envanter", "Lvl") `08` metinlerinde aranıp hiçbirinin arayüz metni olarak geçmediği doğrulandı.
3. **VARSAYIM etiketleri**: ekran dosyalarındaki tüm `VARSAYIM S{n}` etiketleri toplandı; S1–S28'in her biri yukarıdaki tabloda var.
4. **Altı durum**: her ekran dosyasının "Durumlar" tablosunda (ve modal/kutlama alt bölümlerinde) boş, yükleniyor, hata, istek başarısız/çevrimdışı, kilitli ve başarı satırları var ya da "geçerli değil" yazıyor.
5. **Görsel varlıklar**: `01 §2` listesindeki varlık adları ekran dosyalarında geçen adlarla (`karakter-*`, `sandik-*`, `bos-*` vb.) karşılaştırıldı.
