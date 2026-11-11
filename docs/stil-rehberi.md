# Charmoji Görsel Stil Rehberi

Bu belge Charmoji'nin tek ve bağlayıcı görsel stil rehberidir. Tüm görsel üretim prompt'ları (`assets/PROMPTS.md`) bu rehbere ve aşağıdaki **Ortak Stil Bloğu**'na uyar. Rehber değişirse blok da değişir; blok değişirse daha önce üretilmiş görseller yeniden değerlendirilir.

## 1. Stil tanımı

**Piksel sanat, 64x64 mantıksal ızgara.** Her görsel 64x64'lük bir piksel ızgarasında çizilmiş gibi durur ve sert kenarlı (anti-aliasing yok) büyütülmüş olarak gösterilir. Üretim çıktısı büyük (örn. 1024 px) olur; uygulamada `image-rendering: pixelated` ile gösterilir.

Gerekçe:
- Arayüz başlık fontu Pixelify Sans (piksel). Piksel sanat karakter ve eşyaları aynı görsel dile oturtur; düz vektör avatar ile piksel başlık yan yana kötü durur.
- Koyu lacivert RPG zemini (#17142A) üstünde kalın, koyu konturlu ve az renkli piksel sprite'lar okunaklı kalır; küçük boyutta (market kartı, ikon) bile siluet seçilir.
- Üretken model için "az ayrıntı, düz renk blokları" basit ve tutarlı çıktı vermeyi kolaylaştırır; fotogerçekçi ya da boyalı stile göre stil kayması riski düşüktür.
- 64x64 çözünürlük hissi yüz ayrıntısına (göz, ağız) yetecek kadar yüksek, eşya ayrıntısını ucuz tutacak kadar düşüktür.

Gerçek bir oyunun ya da sanatçının adı hiçbir prompt'ta kullanılmaz.

## 2. Palet

Yalnızca aşağıdaki renkler kullanılır. Her malzeme için üç ton vardır: ana, gölge (sağ-alt), ışık (sol-üst).

| Malzeme | Ana | Gölge | Işık |
|---|---|---|---|
| Kontur (tek renk) | #0E0B1E | - | - |
| Ten (varsayılan) | #F3D2C1 | #D9A58F | #FBE6DA |
| Saç (varsayılan) | #2D3436 | #1B2022 | #4A5456 |
| Üst / kumaş (varsayılan) | #6C5CE7 | #4A3DB8 | #9A8DF5 |
| Altın / metal vurgu | #F2C14E | #B8832B | #FFE08A |
| Büyücü moru | #6D28D9 | #4C1D95 | #9F67F0 |
| Koyu kumaş (gözlük, maske) | #2B2F4A | #181B2E | - |
| Çelik (gümüş) | #C9CEDB | #8A90A6 | - |
| Ahşap | #8A5A33 | #5C3A20 | #B07A47 |
| Kırmızı vurgu (mücevher, dil) | #E5484D | - | - |
| Beyaz parıltı | #F5F1FF | - | - |

Arayüz renkleri (görsellerde kullanılmaz, uyum için referans): zemin #17142A, panel #221E3A, kenarlık #3A3460, altın vurgu #F2C14E. Altın tonu arayüzün altın vurgusuyla aynıdır; para ve eşyalar arayüzle aynı altını paylaşır.

**Nadirlik renkleri** yalnızca arayüzde (market kartı kenarlığı, rozet, parıltı çerçevesi) CSS ile uygulanır; görselin içine işlenmez. Böylece aynı sprite her nadirlikte kullanılabilir.

| Nadirlik | Renk |
|---|---|
| Sıradan | #9AA3B8 |
| Nadir | #4DA3FF |
| Efsanevi | #FF8A3D |

Paletin dışına çıkmak için önce bu rehber güncellenir (yeni malzeme = yeni satır).

## 3. Çizgi, gölge, ışık

- **Kontur:** tek renk #0E0B1E, 1 mantıksal piksel kalınlık (1024 px çıktıda yaklaşık 16 px). Her şekilde aynı kalınlık; iç ayrıntılarda da aynı. Saf siyah yok. Kontur dışa doğru kalın "sticker" çerçevesi değildir.
- **Gölgeleme:** düz renk blokları. Her malzemede yalnızca bir gölge tonu ve bir ışık tonu; geçiş, gradyan, kareli dithering, yumuşak fırça yok.
- **Işık yönü:** her zaman sol-üst. Gölge sağ-altta, ışık sol-üstte. Karakter ve tüm eşyalar için tek yön.

## 4. Arka plan

Üretim aracında şeffaf arka plan parametresi yok; "transparent" dendiğinde model gri-beyaz dama tahtası deseni çiziyor (D01 ilk denemesinde görüldü). Bu yüzden iki aşamalı çalışılır:

- **Üretim ara çıktısı:** arka plan tamamen düz, tek renk, saf yeşil **#00FF00** (anahtar rengi). Desen, dama tahtası, gradyan, doku, gölge, glow, çerçeve yok. Öznenin hiçbir yerinde yeşil kullanılmaz (palette yeşil yok; bu yüzden güvenle anahtarlanır). Kontur ile zemin arasında yarı saydam ya da karışmış geçiş pikseli olmaz. Prompt'larda "transparent" sözcüğü kullanılmaz.
- **Nihai dosya:** her zaman gerçek şeffaf RGBA PNG. Yeşil zemin üretimden sonra `assets/araclar/arka-plan-sil.py` ile silinir (bölüm 9).
- **Yer gölgesi yok** (karakter, eşya, sandık, para için). Gölge ve parıltı uygulamada CSS ile verilir.
- Dış parıltı (glow) yok; efsanevi vurgusu CSS ile eklenir.

## 5. Kompozisyon

- **Tuval:** kare, 1:1. Oranın asıl kaynağı üretim aracının `aspect_ratio` parametresidir (prompt'taki "Square 1:1 canvas" ifadesi oranı değiştirmez, yalnızca kompozisyonu destekler). Karakter ve eşyalar aynı tuval oranındadır (bindirme için gerekli).
- **Özne boyutu:** tuvalin yaklaşık %80'i; her kenarda en az %10 boş düz yeşil boşluk (üretim sonrası şeffaf olur).
- **Hizalama:** özne yatayda ve dikeyde ortalı.
- **Bakış yönü:** karakter ve tüm eşyalar tam önden (frontal) görünür; perspektif eğimi, 3/4 görünüm, yan profil yok. Bu seçim eşyaların karakterle aynı açıdan durmasını sağlar.
- **Karakter kadrajı:** büst (baş + omuz + göğüs üstü), kafa büyük (kadrajın yaklaşık %55'i). Gerekçe: eşyaların tamamı baş, yüz ve boyunla ilgili (şapka, gözlük, maske, kolye); tam boy karakterde yüz ve eşyalar mobilde küçük kalır; ana ekranda karakter büyük gösterilecek; mevcut avatar da büst. Tam boy gerekirse (kıyafet, silah gibi yeni eşya türleri) rehber güncellenir.
- **Karakter ayrıntıları:** kısa saç, alın görünür (şapka payı), küçük kulaklar görünür, boyun görünür (kolye payı), basit düz yakalı üst, nötr hafif gülümseyen yüz. Gözler yatay hizada; gözlük ve maske için yüz boş bırakılır.

## 6. Karakter ve eşya bindirme yaklaşımı

**Sorun:** üretken modeller katmanları piksel piksel hizalı üretemez. "Karakterin üstüne tam oturan şapka" istemek tutmaz.

**Karar:** her eşya **ayrı, kare tuvalde (nihai dosyada şeffaf), tek başına nesne olarak** üretilir (ortalı, önden, %80 doluluk). Karakter ayrı bir kare tuvalde üretilir. Uygulama bindirmeyi CSS ile yapar:
- Karakter ve eşya aynı kapsayıcıda `position: absolute` ile katmanlanır.
- Her eşya için bir yerleşim kaydı tutulur (örn. DB veya JSON): `left%`, `top%`, `genişlik%` (isteğe bağlı `rotate`). Eşya, karakter tuvaline göre yüzde olarak konumlanıp ölçeklenir.
- Katman sırası: karakter < kolye < maske < gözlük < şapka.
- `image-rendering: pixelated`; mümkünse ölçek katsayıları sade tutulur.

Bu yaklaşımın riskleri:
1. **Piksel boyutu uyuşmazlığı:** eşya ölçeklenince onun piksel boyutu karakterinkinden farklı olur; hafif "karışık çözünürlük" görünür. Hafifletme: ölçek katsayılarını 0,6-1,4 aralığında tut; sorun görünürse eşyayı karakterle aynı büyüklükte çizdirmek için eşya prompt'una oran ipucu ekle.
2. **Oturma hatası:** model eşyayı her seferinde biraz farklı boyut ve oranda çizer; yerleşim kaydı eşya başına elle ayarlanır (genelde bir kez).
3. **Karakter varyasyonu:** her yeni karakter görseli (renk varyantı) kafa boyutunu biraz değiştirebilir, yerleşimi bozar. Karakter görselini sabit tutmak şart; varyant üretilecekse ayrı yerleşim kaydı gerekebilir.
4. **Gözlük ve maske:** yüzün tam üstüne oturmaları gereken eşyalar en riskli türdür; Güneş Gözlüğü bu yüzden deneme setinde.
5. **Altta kalan parçalar:** şapka saçı kapatır; saç tuvalinde şapka altında kalan kısım görünmez, bu kabul edilir.

## 7. Renk özelleştirme (ten, saç, göz, üst)

Bugünkü SVG avatar dört rengi serbestçe değiştiriyor; yapay zekâ ile üretilmiş tek bir renkli PNG bunu yapamaz. Öneri (deneme görselleri onaylandıktan sonra karar verilir):

- **Öneri A (tercih):** karakteri parçalara bölünmüş, gri tonlu "maske" katmanları olarak ürettir (vücut/ten, saç, göz, üst) ve rengi CSS ile ver (`mix-blend-mode: multiply` ya da CSS `mask-image` ile düz renk doldur). Avantaj: tüm renkler serbest. Risk: modelin hizalı katman üretememesi; bu yüzden parçalar aynı tuvalde tek görselden tek renkli maskeler olarak çıkarılmalı (örn. aynı karakterin gri tonlu hali ve ayrı olarak her bölgenin düz renk haritası) ve hizaları denenmeli. Yüksek risk, deneme gerektirir.
- **Öneri B (güvenli):** sınırlı hazır set: örn. 5 ten x 6 saç x 6 üst rengi tek tek üretmek yerine, kombinasyon sayısını sınırla (örn. 4 hazır karakter). Özgürlük azalır, kalite sabit kalır. Yeniden renklendirme için CSS `hue-rotate` yalnızca üst rengi için kabaca işe yarar, ten ve saç için güvenilmez.
- **Öneri C (karma):** karakter çizimi SVG olarak kalır (renkler serbest), yalnızca eşyalar, sandık ve ikonlar piksel PNG olur. Stil uyumsuzluğu riski yüksek; yalnızca A ve B başarısız olursa.

Açık sorular (yöneticiye):
1. Renk özelleştirme ürün için ne kadar önemli? Serbest renk mi, hazır set mi yeterli?
2. Öneri A denenecekse gri tonlu karakter için ayrı bir deneme turu açılsın mı?
3. Göz rengi 64x64 çözünürlükte 2x2 piksellik alan; ayrı özelleştirilmeye değer mi?

## 8. Dosya adlandırma ve sürümleme

Tüm adlar küçük harf, yalnızca ASCII, sözcükler arasında tire. Türkçe karakterler çevrilir (ş=s, ğ=g, ı=i, ö=o, ü=u, ç=c). Boşluk, alt çizgi, büyük harf yok.

| Klasör | İçerik | Örnek |
|---|---|---|
| `assets/karakter/` | temel karakter ve karakter varyantları | `assets/karakter/temel-karakter.png` |
| `assets/esyalar/` | eşyalar, ad: `tur-esya-adi.png` (tur: sapka, gozluk, maske, kolye) | `assets/esyalar/sapka-buyucu-sapkasi.png` |
| `assets/ikonlar/` | para, stat ve arayüz ikonları | `assets/ikonlar/altin-para.png` |
| `assets/nesneler/` | sandık gibi büyük oyun nesneleri | `assets/nesneler/gunluk-sandik-kapali.png` |
| `assets/deneme/` | stil sınama görselleri, `deneme-NN-ad.png` | `assets/deneme/deneme-01-temel-karakter.png` |

Kurallar:
- Biçim: PNG, gerçek alfa kanallı (RGBA) şeffaf arka plan, kare. Yeşil zeminli ham çıktı repoya girmez. Dosya klasörde yalnızca **seçilen varyasyon** olarak durur; diğer üç varyasyon commit edilmez.
- Sürümleme: yeniden üretilen görsel yeni son ek alır: `-v2`, `-v3`. Eski sürüm silinmez, onaylanan sürüm `PROMPTS.md`'de belirtilir. Onaylı nihai dosya sürüm son eki taşımaz (`sapka-buyucu-sapkasi.png`); onaylandığında ilgili `-vN` dosyası bu adla kopyalanır.
- Deneme görselleri `assets/deneme/` altında kalır; onaylanırsa nihai klasöre yeni adla kopyalanır, reddedilirse kalır ama kullanılmaz.
- Prompt kaydı: her görselin prompt'u `assets/PROMPTS.md`'dedir; prompt değişirse yeni bölüm (`-r2`) açılır, eskisi silinmez.
- Prompt kimliği: deneme için `D01`...`D05`.

## 9. Ortak Stil Bloğu

Aşağıdaki paragraf **her prompt'un sonuna kelimesi kelimesine** eklenir. Üzerinde oynama; değişiklik gerekiyorsa önce bu belgede yapılır ve tüm prompt'lar güncellenir.

```
Art style for a mobile RPG game interface sprite: low-resolution pixel art drawn on a 64x64 logical pixel grid, shown upscaled with crisp nearest-neighbor hard edges, no anti-aliasing, no blur, no gradients, no dithering noise. Every shape has a solid 1-pixel dark outline in #0E0B1E, the only outline color, the same thickness everywhere, never pure black. Flat color blocks with exactly two extra tones per material: one darker shade placed on the lower-right side and one lighter highlight placed on the upper-left side, with the light always coming from the top-left. Use only this palette: outline #0E0B1E; skin #F3D2C1 with shade #D9A58F and highlight #FBE6DA; hair #2D3436 with shade #1B2022 and highlight #4A5456; shirt #6C5CE7 with shade #4A3DB8 and highlight #9A8DF5; gold #F2C14E with shade #B8832B and highlight #FFE08A; wizard purple #6D28D9 with shade #4C1D95 and highlight #9F67F0; dark fabric #2B2F4A with shade #181B2E; steel #C9CEDB with shade #8A90A6; wood #8A5A33 with shade #5C3A20 and highlight #B07A47; accent red #E5484D; white glint #F5F1FF. The background is one completely flat, solid, uniform pure green #00FF00 filling every pixel not covered by the subject, with no pattern, no checkerboard, no gradient, no texture, no shadow, no glow, no floor and no frame. No green is used anywhere on the subject itself, and the subject outline meets the green background directly with hard edges, with no half-faded, blended or anti-aliased transition pixels. Square 1:1 canvas with a single subject viewed straight from the front, centered both horizontally and vertically, filling about 80% of the canvas with at least 10% empty flat green margin on every side. The image contains no text, no letters, no numbers, no watermark and no signature.
```

Bloğun rehberle eşlemesi: 64x64 ızgara, sert kenar ve anti-aliasing yok (bölüm 1); 1 piksel #0E0B1E kontur (bölüm 3); iki ek ton, sol-üst ışık (bölüm 3); palet hex'leri (bölüm 2); düz #00FF00 anahtar zemin (sonradan şeffaf), yer gölgesi/parıltı yok, öznede yeşil yok, geçiş pikseli yok (bölüm 4); kare tuval, ortalı, %80 doluluk, %10 boş zemin, önden görünüm (bölüm 5); yazı yok.

## 10. Prompt yazım düzeni

Her prompt İngilizce yazılır ve sırası şudur: kullanım amacı ve arka plan, özne, ana ayrıntılar (şekil, malzeme, renk hex'i), kısıtlar, ardından Ortak Stil Bloğu. "Stunning, 8K, ultra-detailed" gibi kalite sözcükleri yazılmaz. Oran, çözünürlük ve kalite prompt'ta değil üretim parametresiyle verilir (bölüm 12). Arka plan düz #00FF00 olarak istenir. Her prompt aynı anda 4 varyasyon üretir; prompt ilk denemede isabetli olacak kadar somut yazılır.

## 11. Yapılmayacaklar

- Metin, harf, rakam, filigran, imza içeren görsel.
- Gerçek bir oyunun, markanın, sanatçının adını ya da stilini anmak.
- Palet dışı renk, gradyan, yumuşak gölge, dithering, anti-aliasing, bulanık kenar, dış parıltı.
- Saf siyah kontur; kontur kalınlığını görselden görsele değiştirmek.
- Düz yeşil dışında arka plan; zemin, yer gölgesi, çerçeve, vinyet. Prompt'ta "transparent" yazmak (dama tahtası çizdiriyor). Öznede yeşil kullanmak.
- Perspektifli, 3/4 ya da yan görünüm; ışığı sol-üst dışından vermek.
- Eşyayı karakter üzerinde giyilmiş halde ürettirmek ya da "tam oturacak" hizalama beklemek (bindirme CSS ile yapılır).
- Nadirlik rengini görselin içine işlemek.
- Hızlıca denemek için yer tutucu dosya (boş PNG, elle SVG) koymak.
- Aynı prompt'u yeniden çalıştırarak kredi harcamak; önce prompt'u düzelt, sonra üret.
- Deneme onaylanmadan tam eşya setini üretmek.
- Yeşil zeminli ham çıktıyı şeffaflaştırmadan `assets/` klasörüne koymak.

## 12. Üretim parametreleri ve üretim sonrası işlem

Her üretimde aracın parametreleri (prompt'a yazılmaz):

| Parametre | Değer |
|---|---|
| `aspect_ratio` | `"1:1"` (varsayılan 16:9'dur; verilmezse yatay çıkar) |
| `resolution` | `"1K"` |
| `quality` | `"high"` |
| Varyasyon sayısı | 4 (varsayılan) |

Üretim sonrası adımlar (seçilen varyasyon için):
1. Seçilen yeşil zeminli PNG indirilir (repoya girmez).
2. Arka plan silinir ve özne kare tuvale %10 boşlukla ortalanır:
   `python3 assets/araclar/arka-plan-sil.py girdi.png cikti.png --ortala`
   (`--tolerans N` anahtar rengine uzaklık eşiğidir, varsayılan 60; saçak kalırsa artırılır.)
3. Çıktı RGBA mı ve köşeler alfa=0 mı kontrol edilir; yeşil saçak varsa tolerans artırılıp yeniden çalıştırılır.
4. Dosya bölüm 8'e göre adlandırılıp `assets/` altına konur; `PROMPTS.md` kaydı güncellenir.

Betik yalnızca anahtar rengine yakın pikselleri değil, kontur ile zemin arasındaki karışmış yeşilimsi kenar piksellerini de siler ve kalan piksellerdeki yeşil sızıntıyı giderir. Palette yeşil olmadığı için tüm anahtar rengi pikselleri silinir (yalnızca dıştan bağlantılı olanlar değil); bu, öznenin içindeki boşlukları (örn. kol arası) da şeffaf yapar.

Oran sorusu: model yine 1:1 vermezse betik `--ortala` ile özneyi kare tuvale taşır, ancak yatay çıktıda özne küçük kalabilir; bu durumda parametre kontrol edilir.
