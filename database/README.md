# Charmoji veritabanı

Bu klasör Charmoji'nin MySQL şemasının tek doğru kaynağıdır. Eski `style/sql kodları`
notu kodla uyuşmadığı için kaldırıldı. Gerçek veritabanının dökümü elimizde olmadığından
şema, PHP kodunun (`service/*.php`, `view/*.php`) kullandığı tablo ve sütunlardan
türetildi. Tek doğrudan kanıt, repoda takipli kalmış `mysql-8.4/charmoji/kullanicistatlari.ibd`
dosyasıydı (`ibd2sdi` ile okundu): `KullaniciStatlari` tablosu 001'de birebir o tanımla yazıldı.

```
database/
├── schema.sql                     sıfırdan kurulum (= 001 + 002 + 003)
├── seed.sql                       geliştirme verisi
├── migrations/
│   ├── 001_baseline.sql           bugünkü kodun beklediği şema
│   ├── 002_kisitlar.sql           UNIQUE / FK / CHECK / indeksler (veri silmez)
│   └── 003_kullaniciya_ozel_aliskanliklar.sql   alışkanlıkları kullanıcıya özel yapar (veri taşır, silmez)
└── araclar/                       ELLE çalıştırılan, otomatik akışta OLMAYAN betikler
    ├── tekrar_temizle.sql         002'yi durduran tekrar/sahipsiz satırları temizler (VERİ SİLER, yedekleyerek)
    └── 004_ortak_aliskanliklari_kaldir.sql      eski ortak tabloyu kaldırır (GERİ ALINAMAZ)
```

## Gereksinimler

* MySQL **8.0.16 veya üstü** (CHECK kısıtları 8.0.16'da, fonksiyonel indeksler 8.0.13'te geldi).
  Proje MySQL 8.4 kullanıyor; doğrulama MySQL 8.0.46 üzerinde yapıldı.
* Karakter seti `utf8mb4`, karşılaştırma `utf8mb4_unicode_ci` (gerçek veritabanında da bu
  kullanılıyor; .ibd dosyasından okundu).
* `mysql` istemcisini her zaman `--default-character-set=utf8mb4` ile çalıştırın. Dosyalar
  `SET NAMES utf8mb4` içerse de, ekranda Türkçe karakterlerin doğru görünmesi için gerekir.
* Komutlar reponun kök dizininden çalıştırılır. Örneklerdeki veritabanı adı `charmoji`'dir
  (`service/auth.php` bu adı kullanır); kullanıcı/şifre kendi kurulumunuza göre değişir.

## Sıfırdan kurulum

```bash
mysql -uroot -e "CREATE DATABASE charmoji CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -uroot --default-character-set=utf8mb4 charmoji < database/schema.sql
mysql -uroot --default-character-set=utf8mb4 charmoji < database/seed.sql     # yalnızca geliştirme
```

`seed.sql` tekrar çalıştırılabilir; var olan kayıtları yeniden eklemez, değiştirmez.

### Deneme kullanıcıları (seed.sql)

| Ad              | E-posta                 | Şifre          | Not |
|-----------------|-------------------------|----------------|-----|
| Deneme Kahraman | `deneme@charmoji.local` | `Charmoji123!` | 500 altın, 3 alışkanlık (dün işaretli), Büyücü Şapkası takılı |
| Ayşe Test       | `ayse@charmoji.local`   | `Charmoji123!` | 120 altın, 1 alışkanlık |

İkisi arkadaş (kabul edilmiş). Hash `password_hash('Charmoji123!', PASSWORD_DEFAULT)` ile
üretildi; `AuthManager::login()` ile giriş doğrulandı. Ayrıca 2 görev tanımı
(`Günlük Giriş`, `3 Alışkanlık Tamamla`) ve `avatar_renderer.php`'nin adıyla çizdiği
5 market eşyası eklenir.

## Uygulamayı çalıştırma

Geliştirmede PHP yerleşik sunucusu **yönlendirici betikle** başlatılır (depo kökünden):

```bash
php -S 127.0.0.1:8000 -t . sunucu.php
```

`sunucu.php` olmadan (`-t .` tek başına) `.env` (DB şifresi dahil), `.git/`, `database/`,
`tests/` ve `config/` web'den düz metin okunabilir. Yönlendirici bunlara 404 döner.
XAMPP/Apache'de kökteki `.htaccess` aynı yolları kapatır (`AllowOverride All` gerekir).

**Canlı ortam uyarısı:** Web kökü yalnızca sunulması gereken dosyaları içermelidir.
Canlıda `.env`, `config/`, `database/`, `tests/`, `docs/` web kökünün DIŞINDA tutulmalı
(ya da sunucu bu yolları reddedecek biçimde yapılandırılmalı); `.htaccess`/`sunucu.php`
yalnızca ek savunmadır.

## Mevcut kuruluma migration uygulama

Sıra: **001 → 002 → 003**. Üçü de tekrar çalıştırılabilir; uygulananlar `SemaGecmisi`
tablosuna yazılır (`SELECT * FROM SemaGecmisi;`).

```bash
# 0) Yedek
mysqldump -uroot --default-character-set=utf8mb4 --routines --triggers charmoji > yedek_$(date +%F).sql

# 1) Başlangıç şeması: eksik tabloları oluşturur, yoksa Kullanicilar.AvatarVerisi'ni ekler.
#    Var olan tablo ve veriye dokunmaz.
mysql -uroot --default-character-set=utf8mb4 charmoji < database/migrations/001_baseline.sql

# 2) Kısıtlar. Önce tüm verileri denetler; sorun varsa listeyi basar ve HİÇBİR ŞEYİ
#    DEĞİŞTİRMEDEN "002 DURDU" hatasıyla durur (aşağıya bakın).
mysql -uroot --default-character-set=utf8mb4 charmoji < database/migrations/002_kisitlar.sql

# 3) Kullanıcıya özel alışkanlıklar. Veriyi kopyalar, hiçbir şey silmez.
mysql -uroot --default-character-set=utf8mb4 charmoji < database/migrations/003_kullaniciya_ozel_aliskanliklar.sql
```

### 002 "002 DURDU" hatası verirse

002, eklemeye çalıştığı kısıtları bozan satırları bir tablo hâlinde basar (tablo, sorun,
adet, örnek ID'ler) ve durur. Bu durumda:

1. Listeyi inceleyin. Tekrarlar ve sahipsiz satırlar bugünkü kodun bilinen hatalarından
   gelir (aynı gün çift işaretleme, çift satın alma, silinen alışkanlığın kalan kayıtları,
   A→B ve B→A çift arkadaşlık isteği vb.).
2. Yedek alın ve temizlik betiğini **elle, onay değişkeniyle** çalıştırın:
   ```bash
   mysql -uroot --default-character-set=utf8mb4 charmoji \
     -e "SET @onay='EVET-TEMIZLE'; SOURCE database/araclar/tekrar_temizle.sql;"
   ```
   Her tekrar grubunda bir satır kalır, silinen her satır önce `_yedek_<Tablo>` tablosuna
   kopyalanır (kurallar betiğin başında yazılı). Betik onaysız çalıştırılırsa hiçbir şey
   yapmadan durur. Standartlaştırılamayan `StatTuru` değerlerini silmez; listeler.
   **Bilinen sınır:** tekrarlı takip kayıtları yüzünden fazladan kazanılmış stat puanları
   yeniden hesaplanmaz.
3. 002'yi yeniden çalıştırın.

Not: 002 durduğunda `cm002_*` adlı yardımcı saklı yordamlar veritabanında kalır;
zararsızdırlar ve 002 başarıyla bittiğinde silinirler.

## Migration ve araç listesi

| Dosya | Ne yapar | Veri siler mi? | Otomatik mi? |
|---|---|---|---|
| `migrations/001_baseline.sql` | Kodun beklediği tablolar (`CREATE TABLE IF NOT EXISTS`), eksikse `AvatarVerisi` | Hayır | Evet |
| `migrations/002_kisitlar.sql` | UNIQUE, FK, CHECK, indeksler; `KullaniciStatlari.ToplamPuan` NOT NULL DEFAULT 0; `SonIslemTarihi` DATETIME ise DATE | Hayır (sorun varsa durur) | Evet |
| `migrations/003_kullaniciya_ozel_aliskanliklar.sql` | `KullaniciAliskanliklari`'na Ad/StatTuru/Puan ekler ve doldurur; geçiş tetikleyicisi | Hayır | Evet |
| `araclar/tekrar_temizle.sql` | 002'yi durduran satırları temizler | **Evet** (yedek tablolara kopyalayarak) | **Hayır — elle, `@onay` ile** |
| `araclar/004_ortak_aliskanliklari_kaldir.sql` | `Aliskanliklar` tablosunu, `AliskanlikID` sütununu, tetikleyiciyi kaldırır | **Evet — GERİ ALINAMAZ** | **Hayır — elle, `@onay` ile** |

### ⚠ 004: geri alınamaz, elle ve onayla çalıştırılır

`araclar/004_ortak_aliskanliklari_kaldir.sql` normal kurulum/migration akışının parçası
**değildir**. Yalnızca şunlar sağlandıktan sonra çalıştırılır:

1. 003 uygulanmış ve en az bir süre sorunsuz çalışmış olmalı.
2. PHP kodu aşağıdaki "003 sonrası PHP değişiklikleri" listesine göre güncellenmiş olmalı
   (eski kod 004'ten sonra alışkanlık ekleyemez ve listeleyemez).
3. Tam yedek alınmış olmalı.
4. Betik `@onay='EVET-ALISKANLIKLAR-SILINSIN'` olmadan hiçbir şey yapmaz.

004'ten sonra `schema.sql` ve `seed.sql` de 004 sonrası duruma güncellenmelidir; ikisi de
şu an geçiş için `Aliskanliklar` tablosunu içerir. 004'ten sonra 003 yeniden çalıştırılırsa
bilerek hata verir.

## Şema hakkında kararlar

**Neden `schema.sql` = 001+002+003 (004 hariç)?** Bugünkü PHP kodu alışkanlıkları hâlâ
`Aliskanliklar` ile JOIN'leyerek okuyor. Sıfırdan kurulan bir geliştirme ortamının kod
güncellenmeden de çalışması için eski tablo, `AliskanlikID` ve `TRG_KA_EskiKodUyumu`
tetikleyicisi geçiş süresince tutuluyor. `mysqldump --no-data --routines --triggers` ile
karşılaştırıldığında 001→002→003 uygulanmış veritabanı ile `schema.sql` birebir aynıdır.

**StatTuru: ENUM değil, CHECK + `utf8mb4_bin`.** Geçerli değerler kodda yazıldığı gibi:
`Zeka`, `Guc`, `Sosyallik`, `Disiplin`, `Yaraticilik` (`view/userpage.php:56`).
* `utf8mb4_unicode_ci` harf ve aksan duyarsız olduğu için `'güç'`, `'GUC'` normal
  karşılaştırmada `'Guc'`a eşittir. Kod ise `$barData[$row['StatTuru']]` ve
  `$gorev['StatTuru']=='Guc'` gibi birebir karşılaştırma yapıyor. CHECK ikili
  karşılaştırmayla bu yazımları reddeder; ENUM sessizce kabul edip dönüştürürdü.
* ENUM, katı olmayan `sql_mode`'da geçersiz değeri `''` olarak saklar; CHECK her
  `sql_mode`'da hata verir (INSERT IGNORE'da satır eklenmez).
* Sütun VARCHAR kaldığı için PHP sorgularında değişiklik gerekmez; yeni stat eklemek için
  kısıtı düşürüp yeniden eklemek yeterli.

Kısıt `KullaniciStatlari.StatTuru` (002) ve `KullaniciAliskanliklari.StatTuru` (003)
üzerindedir. Geçişte kalan eski `Aliskanliklar.StatTuru` kısıtsızdır; oradan gelen geçersiz
değer, tetikleyici `KullaniciAliskanliklari`'na yazarken reddedilir.

**Benzersizlik ve ilişkiler**

| Tablo | UNIQUE | FK (ON DELETE) | Neden |
|---|---|---|---|
| KullaniciStatlari | (KullaniciID, StatTuru) | Kullanicilar (CASCADE) | `userpage.php:57` INSERT IGNORE |
| KullaniciAliskanliklari | (KullaniciID, AliskanlikID), 003: (KullaniciID, Ad) | Kullanicilar (CASCADE), Aliskanliklar (RESTRICT) | `userpage.php:75` INSERT IGNORE |
| TakipKayitlari | (KullaniciAliskanlikID, Tarih) | KullaniciAliskanliklari (CASCADE) | `ajax_islem.php:209` INSERT IGNORE; aynı gün çift puan. CASCADE çünkü `ajax_islem.php:150` alışkanlığı doğrudan siler |
| KullaniciEnvanteri | (KullaniciID, EsyaID) | Kullanicilar (CASCADE), MarketEsyalari (RESTRICT) | Satın alma yarışı (`ajax_islem.php:86-95`) |
| KullaniciGunlukGorevleri | (KullaniciID, Tarih, GorevTanimID) | Kullanicilar (CASCADE), GorevTanimlari (RESTRICT) | `userpage.php:33-39` sayfa iki kez açılırsa görevler çiftlenir, ödül iki kez alınır |
| Arkadasliklar | (LEAST(a,b), GREATEST(a,b)) fonksiyonel | Kullanicilar ×2 (CASCADE) | `friendmanager.php:16-25` kontrol-sonra-ekle yarışı; A→B ve B→A aynı çift |

İndeksler: `Kullanicilar(Ad)` (giriş `WHERE Ad = ?`), `Arkadasliklar(IstekAlanID, Durum)`
(bekleyen istekler). Diğer sorgular UNIQUE anahtarların sol önekini kullanır.

## 003 sonrası PHP değişiklikleri (Özellik ajanı için)

003 uygulandıktan sonra kod, alışkanlığın adını/stat türünü/puanını
`KullaniciAliskanliklari`'ndan okumalı ve yazmalı. Tetikleyici sayesinde eski kod çalışmaya
devam eder, ama kullanıcıya özellik ancak bu değişikliklerle devreye girer ve 004 ancak
bunlardan sonra uygulanabilir. Satır numaraları `main` dalındaki bugünkü koda göredir.

| Dosya:satır | Bugün | Olması gereken |
|---|---|---|
| `view/userpage.php:72-74` | `SELECT AliskanlikID FROM Aliskanliklar WHERE Ad = ?`, yoksa `INSERT INTO Aliskanliklar (Ad, StatTuru, Puan)` | Kaldırılmalı; ortak tabloya artık yazılmaz. |
| `view/userpage.php:75` | `INSERT IGNORE INTO KullaniciAliskanliklari (KullaniciID, AliskanlikID, BaslangicTarihi) VALUES (...)` (değişken gömülü) | Hazır ifade: `INSERT IGNORE INTO KullaniciAliskanliklari (KullaniciID, Ad, StatTuru, Puan, BaslangicTarihi) VALUES (?, ?, ?, ?, CURDATE())`. Aynı kullanıcıda aynı ad varsa UQ_KA_Kullanici_Ad yüzünden eklenmez (`affected_rows = 0` → kullanıcıya mesaj). |
| `view/userpage.php:70` | `$secilenStat = $_POST['habit_stat']` doğrulanmıyor | `$statList` anahtarlarından biri mi diye kontrol edilmeli; değilse CHECK yüzünden INSERT IGNORE satırı sessizce atlar. |
| `view/userpage.php:80` | `SELECT KA.KullaniciAliskanlikID, A.Ad, A.StatTuru, A.Puan, ... FROM KullaniciAliskanliklari KA JOIN Aliskanliklar A ON KA.AliskanlikID = A.AliskanlikID ...` | `SELECT KA.KullaniciAliskanlikID, KA.Ad, KA.StatTuru, KA.Puan, KA.Seri, (...) AS BugunYapildi FROM KullaniciAliskanliklari KA WHERE KA.KullaniciID = ? AND KA.AktifMi = 1` (JOIN yok) |
| `view/ajax_islem.php:184-187` | `SELECT KA.Seri, KA.SonIslemTarihi, A.Puan, A.StatTuru FROM KullaniciAliskanliklari KA JOIN Aliskanliklar A ON KA.AliskanlikID = A.AliskanlikID WHERE ...` | `SELECT KA.Seri, KA.SonIslemTarihi, KA.Puan, KA.StatTuru FROM KullaniciAliskanliklari KA WHERE KA.KullaniciAliskanlikID = ? AND KA.KullaniciID = ?` |

Başka hiçbir PHP dosyası `Aliskanliklar` tablosuna veya `AliskanlikID` sütununa başvurmuyor
(`grep -rnwE "Aliskanliklar|AliskanlikID" service view` yalnızca yukarıdaki satırları bulur).

### 002 sonrası önerilen PHP düzeltmeleri

PHP 8.1+ `mysqli` varsayılan olarak hata durumunda istisna fırlatır. Yeni UNIQUE anahtarlar
yarış durumunda düz `INSERT`'leri hataya düşürür; veri bozulmaz ama sayfa hata verebilir:

| Dosya:satır | Sorun | Öneri |
|---|---|---|
| `view/userpage.php:38` | Günlük görevler düz `INSERT` ile ekleniyor; iki eşzamanlı sayfa açılışında ikincisi UQ_KGG hatasıyla yakalanmamış istisna verir | `INSERT IGNORE` |
| `view/ajax_islem.php:95` | Eşzamanlı aynı eşya alımında UQ_Env hatası `catch` ile yakalanır, ama mesaj ham veritabanı hatası olur | 1062 hatasını "Zaten sahipsin." olarak çevir |
| `view/ajax_islem.php:81,90-94` | Bakiye okunup PHP'de hesaplanıp yazılıyor: eşzamanlı iki farklı alımda biri kaybolur (lost update) | `SELECT ... FOR UPDATE` ya da `UPDATE Kullanicilar SET Bakiye = Bakiye - ? WHERE KullaniciID = ? AND Bakiye >= ?` + `affected_rows` kontrolü |
| `view/ajax_islem.php:117-128` | Ödül önce okunup sonra veriliyor; eşzamanlı iki istekte ödül iki kez alınabilir | Önce `UPDATE KullaniciGunlukGorevleri SET OdulAlindiMi = 1 WHERE KullaniciGorevID = ? AND KullaniciID = ? AND TamamlandiMi = 1 AND OdulAlindiMi = 0`, `affected_rows = 1` ise bakiyeyi artır |
| `service/friendmanager.php:25` | UQ_Ark_Cift ihlalinde `execute()` istisna fırlatır, `sendRequest` yakalamıyor | `try/catch` ile "Zaten arkadaşsınız veya istek gönderilmiş." döndür |
| `service/friendmanager.php:72` | `removeFriend` yalnızca `ArkadaslikID` ile siliyor; herkes herhangi bir arkadaşlığı silebilir | `AND (IstekGonderenID = ? OR IstekAlanID = ?)` ekle |

## Varsayımlar ve bilinen belirsizlikler

* Sütun tipleri koddan türetildi; yalnızca `KullaniciStatlari` kesin. Diğerlerinin gerçek
  veritabanındaki uzunlukları/NULL durumu farklı olabilir; 001 mevcut tabloları değiştirmez.
* `SonIslemTarihi` DATE seçildi: kod `'Y-m-d'` yazıp `'Y-m-d'` ile karşılaştırıyor; DATETIME
  olsaydı seri hiç artmazdı. Eski not DATETIME diyordu; 002 DATETIME ise (saat bilgisi yoksa)
  DATE'e çevirir.
* Kodun hiç kullanmadığı eski not sütunları (AvatarURL, Seviye, XP, Aciklama, VarsayilanHedef,
  HedefTipi, OzelHedefDegeri, GerceklesenDeger, AlimTarihi) sıfırdan kurulumda yoktur; mevcut
  kurulumda varlarsa dokunulmaz. `Aliskanliklar`'daki bu tür sütunlar 004 ile tablo birlikte gider.
* Gerçek kurulum büyük olasılıkla Windows'ta (`lower_case_table_names=1`, dosya adları küçük
  harf). Betikler `information_schema` sorgularında tablo adlarını küçük harfe çevirerek
  karşılaştırır. Linux'ta tablo adları büyük/küçük harfe duyarlıdır; kodla aynı yazım
  (`Kullanicilar`, `KullaniciStatlari` …) kullanılır.
* Eski nottaki `arkadaslar` tablosu kodda kullanılmıyor; mevcut kurulumda varsa dokunulmaz,
  verisi `Arkadasliklar`'a taşınmaz.
