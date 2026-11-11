# Güvenlik doğrulama betikleri

Birinci dalga güvenlik düzeltmelerini (XSS, IDOR, CSRF, oturum, hata sızıntısı,
.env yapılandırması, ikinci derece SQL enjeksiyonu, giriş/kayıt akışı) uçtan uca
sınayan, tekrar çalıştırılabilir betikler.

Aynı betikler hem **yeni** kodu hem **eski** kodu sınar: eski kodda açıklar
`[KALDI]` olarak görünür (açık yeniden üretildi), yeni kodda hepsi `[GEÇTİ]` olmalıdır.

## Gereksinimler

- PHP 8.x CLI (`mysqli` eklentisiyle), `curl`, `mysql` istemcisi
- Erişilebilir bir MySQL 8 sunucusu ve **testlere ayrılmış** bir veritabanı
- İsteğe bağlı: Node.js + Playwright (tarayıcı testleri; yoksa atlanır)

## Çalıştırma

Depo kökünden:

```bash
# Yeni kod, tüm testler (a..i)
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
| `TEST_DB` | `charmoji_ozellik` | Test veritabanı. **İçindeki tablolar her çalıştırmada silinip yeniden kurulur.** `charmoji`, `charmoji_vt`, `charmoji_kk` reddedilir. |
| `TEST_DB_HOST` / `TEST_DB_USER` / `TEST_DB_PASSWORD` | `localhost` / `root` / boş | Test veritabanı bağlantısı |
| `PORT_TABAN` | `8150` | Kullanılan portlar: `PORT_TABAN` .. `PORT_TABAN+4` |
| `PLAYWRIGHT` | `1` | `0` yapılırsa tarayıcı testleri atlanır |
| `GECICI_SAKLA` | `0` | `1` yapılırsa geçici dizin (sunucu/PHP logları) silinmez |

Örnek: `TEST_DB=charmoji_kalite PORT_TABAN=8170 bash tests/guvenlik/calistir.sh`

## Ne yapar?

1. `sema_test.sql` ile test veritabanını sıfırdan kurar (**yalnızca test içindir;
   asıl şema `database/` altındadır**).
2. Geçici bir `test.env` yazar; gerçek `.env` dosyanıza dokunmaz
   (`CHARMOJI_ENV_FILE` ortam değişkeniyle gösterilir).
3. Uygulamayı PHP yerleşik sunucusuyla iki kez başlatır: kökten
   (`http://127.0.0.1:8150/`) ve alt dizinden (`http://127.0.0.1:8151/charmoji/`).
4. Seçilen testleri çalıştırır, sonunda özet basar ve sunucuları kapatır.

| Dosya | Madde | Denetim |
|---|---|---|
| `test_a_xss.sh` (+ `xss_tarayici.js`) | a | Kötü renk değerleri kayıtta reddedilir; veritabanına elle yazılmış kötü değer çizimde varsayılana düşer; `$size` kaçamaz; tarayıcıda script çalışmaz |
| `test_b_idor.sh` (+ `arkadas_birim.php`) | b | B, A–C kaydını reddedemez/kabul edemez/silemez; meşru red, kabul ve silme çalışır; `FriendManager` dönüş değerleri |
| `test_c_csrf.sh` | c | Kayıt, giriş, friend_action (4 işlem), alışkanlık ekleme, 6 AJAX işlemi ve çıkış: jetonsuz ve yanlış jetonlu POST reddedilir, doğru jetonla çalışır; çıkış GET ile yapılamaz |
| `test_d_oturum.sh` | d | Giriş öncesi/sonrası oturum kimliği farklı, eski kimlik geçersiz; çerez HttpOnly + SameSite; uydurulmuş kimlik (sabitleme) kabul edilmez |
| `test_e_hata.sh` | e | Tablo geçici olarak yeniden adlandırılır: yanıtta PHP/SQL metni yok, ayrıntı log'da; olmayan veritabanıyla bağlantı hatası da sızmaz |
| `test_f_env.sh` | f | Kodda sabit DB bilgisi yok; `.env` olmadan anlaşılır hata, `.env` yazılınca çalışma (uygulamanın geçici kopyasında) |
| `test_g_sqli.sh` | g | `habit_stat` olarak `x' OR '1'='1` reddedilir; veritabanındaki kötü `StatTuru`/`EsyaTuru` başka kullanıcıları etkilemez |
| `test_h_duman.sh` (+ `duman_tarayici.js`) | h | Kayıt → çıkış → hatalı giriş → giriş → alışkanlık ekle → işaretle → market/avatar → çıkış; kökten ve `/charmoji/` alt dizininden; ayrıca tarayıcıda arayüz üzerinden |
| `test_i_lint.sh` | i | `php -l` tüm PHP dosyalarında |

## Eski kod modu (`--eski`)

`eski_kodu_hazirla.sh` eski commit'i geçici dizine çıkarır ve çalışabilmesi için
yalnızca iki uyarlama yapar: `service/auth.php` içindeki sabit veritabanı adını
`TEST_DB` yapar ve `service/friendmanager.php` dosyasını `FriendManager.php` olarak
kopyalar (aksi halde sorun 7 yüzünden `userpage.php` Linux'ta hiç açılmaz).
Eski kodda giriş/kayıt sayfaları `service/` altında olduğundan o yollar kullanılır.

Eski kodda bazı denetimlerin `[GEÇTİ]` görünmesi normaldir (meşru işlemler zaten
çalışıyordu); açığı gösteren satırlar `[KALDI]` olanlardır.
