# Güvenlik doğrulama betikleri

Birinci dalga güvenlik düzeltmelerini (XSS, IDOR, CSRF, oturum, hata sızıntısı,
.env yapılandırması, ikinci derece SQL enjeksiyonu, giriş/kayıt akışı) uçtan uca
sınayan, tekrar çalıştırılabilir betikler.

Aynı betikler hem **yeni** kodu hem **eski** kodu sınar: eski kodda açıklar
`[KALDI]` olarak görünür (açık yeniden üretildi), yeni kodda hepsi `[GEÇTİ]` olmalıdır.

## Gereksinimler

- PHP 8.x CLI (`mysqli` eklentisiyle), `curl`, `mysql` istemcisi
- Erişilebilir bir MySQL 8.0.16+ sunucusu (resmi şemadaki CHECK kısıtları için) ve **testlere ayrılmış** bir veritabanı
- İsteğe bağlı: Node.js + Playwright (tarayıcı testleri; yoksa atlanır)

## Çalıştırma

Depo kökünden:

```bash
# Yeni kod, tüm testler (a..n)
bash tests/guvenlik/calistir.sh

# Yalnızca bazı testler
bash tests/guvenlik/calistir.sh a c g

# Açıkları eski kodda yeniden üret (varsayılan eski kod: 9f065c1, dalga öncesi main)
bash tests/guvenlik/calistir.sh --eski
bash tests/guvenlik/calistir.sh --eski <commit/branch> b d
```

Çıkış kodu: tüm denetimler geçtiyse `0`, en az biri kaldıysa `1`, ortam hatasında `2`.

### Ortam değişkenleri

| Değişken | Varsayılan | Açıklama |
|---|---|---|
| `TEST_DB` | `charmoji_ozellik` | Test veritabanı. **Veritabanı her çalıştırmada silinip (`DROP DATABASE`) yeniden oluşturulur.** `charmoji`, `charmoji_vt`, `charmoji_kk` reddedilir. |
| `TEST_DB_HOST` / `TEST_DB_USER` / `TEST_DB_PASSWORD` | `localhost` / `root` / boş | Test veritabanı bağlantısı |
| `PORT_TABAN` | `8150` | Kullanılan portlar: `PORT_TABAN` .. `PORT_TABAN+5` |
| `PLAYWRIGHT` | `1` | `0` yapılırsa tarayıcı testleri atlanır |
| `GECICI_SAKLA` | `0` | `1` yapılırsa geçici dizin (sunucu/PHP logları) silinmez |

Örnek: `TEST_DB=charmoji_kalite PORT_TABAN=8170 bash tests/guvenlik/calistir.sh`

## Ne yapar?

1. Test veritabanını sıfırlar ve resmi şemayla kurar: `database/schema.sql` +
   `database/seed.sql` (ayrı bir test şeması yoktur; `sema_test.sql` kaldırıldı).
2. Geçici bir `test.env` yazar; gerçek `.env` dosyanıza dokunmaz
   (`CHARMOJI_ENV_FILE` ortam değişkeniyle gösterilir).
3. Uygulamayı PHP yerleşik sunucusuyla (kökteki `sunucu.php` yönlendiricisiyle; eski kodda yoktur) iki kez başlatır: kökten
   (`http://127.0.0.1:8150/`) ve alt dizinden (`http://127.0.0.1:8151/charmoji/`).
4. Seçilen testleri çalıştırır, sonunda özet basar ve sunucuları kapatır.

| Dosya | Madde | Denetim |
|---|---|---|
| `test_a_xss.sh` (+ `xss_tarayici.js`) | a | Kötü renk değerleri kayıtta reddedilir; veritabanına elle yazılmış kötü değer çizimde varsayılana düşer; `$size` kaçamaz; tarayıcıda script çalışmaz |
| `test_b_idor.sh` (+ `arkadas_birim.php`) | b | B, A–C kaydını reddedemez/kabul edemez/silemez; meşru red, kabul ve silme çalışır; `FriendManager` dönüş değerleri; Maceracı bul araması (`view/lonca_ara.php`) e-posta sızdırmaz, e-postayla/joker karakterle arama yapılamaz, oturum ister |
| `test_c_csrf.sh` | c | Kayıt, giriş, friend_action (4 işlem + JSON kipi), görev ekleme (`view/gorevler.php`), 6 AJAX işlemi ve çıkış: jetonsuz ve yanlış jetonlu POST reddedilir, doğru jetonla çalışır; çıkış GET ile yapılamaz; oturumsuz AJAX 401 + `oturum_kapali` |
| `test_d_oturum.sh` | d | Giriş öncesi/sonrası oturum kimliği farklı, eski kimlik geçersiz; çerez HttpOnly + SameSite; uydurulmuş kimlik (sabitleme) kabul edilmez |
| `test_e_hata.sh` | e | Tablo geçici olarak yeniden adlandırılır: yanıtta PHP/SQL metni yok, ayrıntı log'da; olmayan veritabanıyla bağlantı hatası da sızmaz |
| `test_f_env.sh` | f | Kodda sabit DB bilgisi yok; `.env` olmadan anlaşılır hata, `.env` yazılınca çalışma (uygulamanın geçici kopyasında) |
| `test_g_sqli.sh` | g | `habit_stat` olarak `x' OR '1'='1` reddedilir (form ve AJAX); veritabanındaki kötü `StatTuru`/`EsyaTuru` başka kullanıcıları etkilemez ve kaçışlanarak basılır |
| `test_h_duman.sh` (+ `duman_tarayici.js`) | h | Kayıt → çıkış → hatalı giriş → giriş → eski `userpage.php` adresinin Karargah'a yönlenmesi → görev ekle → işaretle → beş sekme + Görünüm/Çanta → çıkış; kökten ve `/charmoji/` alt dizininden; ayrıca tarayıcıda (390 px) arayüz üzerinden: alt menü, görev formu, onay alt sayfaları (M05, M08), arama; `confirm()`/`alert()` açılmaz |
| `test_i_lint.sh` | i | `php -l` tüm PHP dosyalarında |
| `test_j_kimlik.sh` | j | B-1: Ad=<kurban e-postası> kaydı kurbanı kilitlemez, ad ile giriş yok, geçersiz e-posta reddedilir; B-3: `O'Brien` / `o'brien@ornek.test` kaydı (ters eğik çizgisiz) + otomatik ve elle giriş |
| `test_k_cikti.sh` (+ `xss_tarayici.js`) | k | B-2: `GorevTanimlari.Baslik/Ikon` içindeki HTML Görevler sekmesinde (eski kodda userpage'de) kaçışlanır; tarayıcıda dialog/script yok |
| `test_l_erisim.sh` | l | B-4: uygulama kopyasında `/.env`, `/.git/config`, `/database/schema.sql`, `/tests/...`, `/config/...`, `/sunucu.php` vb. kökten ve `/charmoji/` altından 404; `view/index.php`, giriş sayfası, `style/custom.css`, `view/statik/*.css|js` 200; `view/ortak/*.php` parçaları 404 |
| `test_m_ajax.sh` | m | K-1: başkasının alışkanlığını silme ve sahip olunmayan eşyayı kuşanma `success:false`, veri değişmez; meşru işlemler çalışır (silme = arşivleme: `AktifMi = 0`) |
| `test_n_gorevler.sh` | n | Aşama 2 görev kuralları: görev kullanıcıya özel sütunlara yazılır, başka oyuncunun aynı adlı görevi yeteneği etkilemez; ad 1–60 karakter (çok baytlı), aktif görevlerde Türkçe büyük/küçük harf duyarsız benzersiz; 20 aktif görev sınırı (AJAX ve form); düzenleme (S19: geçmiş XP eski yetenekte, geri alma doğru yetenekten düşer); silme = arşivleme, "Geri al" (süre, sınır, ad çakışması); sahiplik (düzenle/arşivle/geri getir/tamamla/geri al başkasında `success:false`, veri aynı); tamamla → geri al → tamamla serisi, 7. gün bonusu, bozuk seri (S12), gün sınırı, seviye düşmesi (S8); meydan okuma ilerlemesi türe (`GorevTanimlari.Tur`) göre (S25) ve S7; yeni uçlarda CSRF; kart HTML'inde kaçış |

## Sayfa yolları (Dalga 2)

Arayüz beş sekmeye bölündüğünden testler sayfa yollarını `calistir.sh`'te belirlenen
değişkenlerden alır: `ANA_YOLU` (giriş sonrası sayfa; yeni: `view/karargah.php`) ve
`GOREV_YOLU` (görev ekleme formu ve görev/meydan okuma listesi; yeni: `view/gorevler.php`).
Eski kod modunda ikisi de `view/userpage.php`'dir.

## Resmi şema ve fikstür kuralları

Testler resmi şemanın kısıtlarına uyar; kısıtı atlatmak için şema değiştirilmez.

- **Arkadaşlık:** `UQ_Ark_Cift` çift başına tek kayıt izin verir. Aynı çift için ikinci
  satır eklenmez; her senaryo ayrı kullanıcı çifti kullanır (b: bekleyen istek A-C,
  kabul edilmiş arkadaşlık A-D, B her ikisinde üçüncü kişi; c: her friend_action ucu için
  ayrı hedef kullanıcı).
- **Satın alma:** seed'deki eşyalar 100-500 altındır; c testi kullanıcıya 1000 altın verir.
- **Kötü `StatTuru`:** `CHK_KA_StatTuru`, `CHK_KS_StatTuru` ve `CHK_TK_StatTuru` geçersiz değeri
  reddeder; g testi bunu üç ayrı denetimle doğrular. Uygulama `StatTuru`'nu artık yalnızca
  kullanıcıya özel sütundan (`KullaniciAliskanliklari.StatTuru`) okur; koddaki savunmayı
  (giriş doğrulaması, bağlanan parametreler, kaçışlama) kısıttan bağımsız sınamak için g testi
  `CHK_KA_StatTuru` ve `CHK_TK_StatTuru`'yu **geçici olarak düşürür**, kötü satırı ekler,
  sınar, satırı silip iki kısıtı geri ekler ve geri eklendiğini ayrıca denetler. Sınama boş
  geçmesin diye fikstürün eklendiği de denetlenir. (Eski kod modunda fikstür eski ortak
  `Aliskanliklar` tablosu üzerinden kurulur.) Bu yol 004 sonrasında da çalışır.
- **Meydan okuma tanımı:** `GorevTanimlari.Tur` zorunludur (`giris` | `gorev_tamamlama`); k ve n
  testleri tanım eklerken türü verir.
- **Tarih:** uygulama günü Europe/Istanbul'a göre hesaplar (S1); n testi beklenen tarihleri
  PHP ile aynı saat diliminde üretir (veritabanının `CURDATE()`'i farklı olabilir).
- **Kötü `EsyaTuru` / `AvatarVerisi`:** resmi şemada bu sütunlarda kısıt yoktur; testler aynen
  doğrudan SQL ile kötü değer yazar.

## Eski kod modu (`--eski`)

`eski_kodu_hazirla.sh` eski commit'i geçici dizine çıkarır ve çalışabilmesi için
yalnızca iki uyarlama yapar: `service/auth.php` içindeki sabit veritabanı adını
`TEST_DB` yapar ve `service/friendmanager.php` dosyasını `FriendManager.php` olarak
kopyalar (aksi halde sorun 7 yüzünden `userpage.php` Linux'ta hiç açılmaz).
Eski kod resmi şemayla çalışır (eski kodun yazdığı ortak `Aliskanliklar` tablosu şemada hâlâ vardır). Eski kodda giriş/kayıt sayfaları `service/` altında olduğundan o yollar kullanılır.

Eski kodda bazı denetimlerin `[GEÇTİ]` görünmesi normaldir (meşru işlemler zaten
çalışıyordu); açığı gösteren satırlar `[KALDI]` olanlardır.
