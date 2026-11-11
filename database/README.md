# Charmoji veritabanı

Bu klasör Charmoji'nin MySQL şemasının tek doğru kaynağıdır. Gerçek veritabanının dökümü
elimizde olmadığından şema, PHP kodunun (`service/*.php`, `view/*.php`) kullandığı tablo ve
sütunlardan türetildi. Tek doğrudan kanıt, eskiden repoda takipli olan
`mysql-8.4/charmoji/kullanicistatlari.ibd` dosyasıydı (`ibd2sdi` ile okundu):
`KullaniciStatlari` tablosu 001'de birebir o tanımla yazıldı.

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

* MySQL **8.0.17 veya üstü** (CHECK kısıtları 8.0.16, `utf8mb4_0900_bin` 8.0.17,
  fonksiyonel indeksler 8.0.13). Proje MySQL 8.4 kullanıyor; doğrulama MySQL 8.0.46'da yapıldı.
* Karakter seti `utf8mb4`, karşılaştırma `utf8mb4_unicode_ci`.
* `mysql` istemcisini her zaman `--default-character-set=utf8mb4` ile çalıştırın.
* PHP 8.1+ (`mysqli`). Uygulama veritabanı ayarlarını proje kökündeki `.env` dosyasından
  okur (`config/env.php`, `config/uygulama.php`; örnek: `.env.example`).
* Komutlar reponun kök dizininden çalıştırılır. Örneklerde veritabanı `charmoji`, uygulama
  kullanıcısı `charmoji_uygulama`'dır (`.env.example` ile aynı). Şifreyi kendiniz seçin.

## Sıfırdan kurulum (çalışan uygulamaya kadar)

İki ayrı MySQL kullanıcısı kullanılır:

* **Yönetici / migration kullanıcısı** (ör. `root`): şemayı kurar ve migration'ları çalıştırır.
  Tablo, indeks, FK, CHECK, tetikleyici ve geçici saklı yordam oluşturur.
* **Uygulama kullanıcısı** (`charmoji_uygulama`): PHP bu kullanıcıyla bağlanır; yalnızca
  veri okur/yazar.

### 1. Veritabanı ve uygulama kullanıcısı (yönetici olarak, elle)

```bash
mysql -uroot <<'SQL'
CREATE DATABASE charmoji CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'charmoji_uygulama'@'localhost' IDENTIFIED BY 'BURAYA-GUCLU-BIR-SIFRE';
-- Asgari yetkiler: uygulama yalnızca veri okur/yazar/siler; şema değiştirmez.
GRANT SELECT, INSERT, UPDATE, DELETE ON charmoji.* TO 'charmoji_uygulama'@'localhost';
SQL
```

PHP sunucusu MySQL'e ağ üzerinden (ör. `DB_HOST=127.0.0.1` ya da başka bir makine) bağlanıyorsa
`'localhost'` yerine o istemci adresini yazın (`'charmoji_uygulama'@'127.0.0.1'` gibi).
Tetikleyici, onu oluşturan kullanıcının (DEFINER) yetkisiyle çalıştığı için uygulama
kullanıcısına `TRIGGER` yetkisi gerekmez.

### 2. Şema ve (isteğe bağlı) geliştirme verisi (yönetici olarak)

```bash
mysql -uroot --default-character-set=utf8mb4 charmoji < database/schema.sql
mysql -uroot --default-character-set=utf8mb4 charmoji < database/seed.sql     # yalnızca geliştirme
```

### 3. `.env`

```bash
cp .env.example .env
```

`.env` içinde `DB_NAME=charmoji`, `DB_USER=charmoji_uygulama` ve `DB_PASSWORD=` satırına
1. adımda seçtiğiniz şifreyi yazın. `.env` git'e eklenmez (`.gitignore`).

### 4. Çalıştırma

```bash
php -S 127.0.0.1:8000 -t .
```

Tarayıcıda `http://127.0.0.1:8000/view/index.php` açılır; kayıt `view/register.php`, giriş
`view/login.php`. `seed.sql` yüklendiyse aşağıdaki kullanıcılarla da girilebilir.

### Deneme kullanıcıları (seed.sql)

| Ad              | E-posta                 | Şifre          | Not |
|-----------------|-------------------------|----------------|-----|
| Deneme Kahraman | `deneme@charmoji.local` | `Charmoji123!` | 500 altın, 3 alışkanlık (seed'in ilk çalıştığı günün bir gün öncesinde işaretli), Büyücü Şapkası takılı |
| Ayşe Test       | `ayse@charmoji.local`   | `Charmoji123!` | 120 altın, 1 alışkanlık |

İkisi arkadaş (kabul edilmiş). Ayrıca 2 görev tanımı (`Günlük Giriş`, `3 Alışkanlık Tamamla`)
ve `avatar_renderer.php`'nin adıyla çizdiği 5 market eşyası eklenir. `seed.sql` tekrar
çalıştırılabilir: var olan kullanıcı, eşya, görev, alışkanlık, envanter ve arkadaşlık
satırlarını yeniden eklemez; takip kaydını yalnızca hiç kaydı olmayan alışkanlığa ekler
(başka bir günde çalıştırılsa da yeni satır eklemez). Var olan satırları değiştirmez.

## Migration'ları çalıştıran kullanıcının yetkileri

`root` yerine ayrı bir migration kullanıcısı kullanacaksanız şu yetkiler gerekir:

```sql
CREATE USER 'charmoji_goc'@'localhost' IDENTIFIED BY '...';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES,
      CREATE TEMPORARY TABLES, TRIGGER, CREATE ROUTINE, ALTER ROUTINE, EXECUTE
   ON charmoji.* TO 'charmoji_goc'@'localhost';
```

* `TRIGGER`: 003 (ve `schema.sql`) `TRG_KA_EskiKodUyumu` tetikleyicisini kurar; 004 kaldırır.
* `CREATE ROUTINE` / `ALTER ROUTINE` / `EXECUTE`: betikler denetim ve değişiklikleri geçici
  saklı yordamlar içinde yapar ve sonunda siler.
* `CREATE TEMPORARY TABLES`: denetim sonuçları ve güvenlik kapıları geçici tablolarda tutulur.
* **İkili günlük (binlog) açıksa** (`SELECT @@log_bin;` → 1, MySQL 8'de varsayılan),
  SUPER yetkisi olmayan bir kullanıcının tetikleyici oluşturabilmesi için sunucuda
  `log_bin_trust_function_creators=1` olmalıdır; aksi hâlde `ERROR 1419` alınır.
  `root` için gerekmez.

## Mevcut kuruluma migration uygulama

Sıra: **001 → 002 → 003**. Üçü de tekrar çalıştırılabilir; uygulananlar `SemaGecmisi`
tablosuna yazılır (`SELECT * FROM SemaGecmisi;`). 002 ve 003'ün daha eski bir sürümünü
uyguladıysanız ikisini yeniden çalıştırın: StatTuru CHECK'ini sondaki boşluğu da reddeden
sürüme günceller, başka bir şeyi değiştirmez.

```bash
# 0) Yedek
mysqldump -uroot --default-character-set=utf8mb4 --routines --triggers charmoji > yedek_$(date +%F).sql

# 1) Başlangıç şeması: eksik tabloları oluşturur, yoksa Kullanicilar.AvatarVerisi'ni ekler.
#    Var olan tablo ve veriye dokunmaz.
mysql -uroot --default-character-set=utf8mb4 charmoji < database/migrations/001_baseline.sql

# 2) Kısıtlar. Veri sorunu varsa listeyi basar ve HİÇBİR ŞEYİ DEĞİŞTİRMEDEN durur.
mysql -uroot --default-character-set=utf8mb4 charmoji < database/migrations/002_kisitlar.sql

# 3) Kullanıcıya özel alışkanlıklar. Veriyi kopyalar, hiçbir şey silmez.
mysql -uroot --default-character-set=utf8mb4 charmoji < database/migrations/003_kullaniciya_ozel_aliskanliklar.sql
```

### Migration'lar iki biçimde çalıştırılır

* **Batch (önerilen):** `mysql ... charmoji < dosya.sql` (yukarıdaki komutlar). İstemci ilk
  hatada durur.
* **`SOURCE`:** etkileşimli kabukta `mysql> SOURCE database/migrations/002_kisitlar.sql;`
  ya da `mysql -e "SOURCE ..."`. Onay değişkeni gereken araçlarda `SET @onay='...';` aynı
  oturumda önce verilir. `SOURCE` (ve `mysql --force`) hatadan sonra devam eder; bu yüzden
  kapılar değişiklikleri yapan yordamın içindedir (aşağıya bakın) ve hata görürseniz
  çıktının ilk `ERROR` satırına bakın: "DURDU" içeren mesaj hiçbir şeyin değişmediği
  anlamına gelir; sonraki `ERROR` satırları (ör. "PROCEDURE ... does not exist")
  bunun yan etkisidir.

### Güvenlik kapıları nasıl çalışır?

002, 003, `tekrar_temizle.sql` ve 004 aynı kalıbı kullanır:

1. Salt okunur denetimler (ya da onay değişkeni) geçici bir tabloya yazılır.
2. **Erken kapı:** sorun varsa adı mesaj olan bir CHECK kısıtı ihlal edilir
   (ör. `Check constraint '002_DURDU_veri_sorunu_var_hicbir_sey_degistirilmedi' is violated`).
   Normal `mysql db < dosya` kullanımında istemci burada durur; hiçbir kalıcı nesne
   oluşturulmamıştır.
3. **Asıl kapı:** değişiklik yapan saklı yordamın ilk adımı aynı denetimi yeniden yapar ve
   geçilmezse `SIGNAL` ile hiçbir şey yapmadan çıkar. Böylece istemci hatada durmasa bile
   (`mysql --force`, etkileşimli `mysql>` kabuğunda `SOURCE`) değişiklik yapılmaz. Bu
   durumda dosyanın sonundaki temizlik satırları geçici yordamları da siler.

### 002 "002_DURDU" ile durursa

002, eklemeye çalıştığı kısıtları bozan satırları bir tablo hâlinde basar (tablo, sorun,
adet, örnek ID'ler). Bu durumda:

1. Listeyi inceleyin. Tekrarlar ve sahipsiz satırlar eski kodun bilinen hatalarından gelir
   (aynı gün çift işaretleme, çift satın alma, silinen alışkanlığın kalan kayıtları,
   A→B ve B→A çift arkadaşlık isteği vb.).
2. Yedek alın ve temizlik betiğini **elle, onay değişkeniyle** çalıştırın:
   ```bash
   mysql -uroot --default-character-set=utf8mb4 charmoji \
     -e "SET @onay='EVET-TEMIZLE'; SOURCE database/araclar/tekrar_temizle.sql;"
   ```
   Her tekrar grubunda bir satır kalır, silinen her satır önce `_yedek_<Tablo>` tablosuna
   kopyalanır; veri adımları tek transaction'dadır, hata olursa geri alınır. Onaysız
   çalıştırılırsa hiçbir şey yapmaz. Yalnızca 003 öncesi şemada çalışır (003 uygulanmışsa
   reddeder). Standartlaştırılamayan `StatTuru` değerlerini silmez, listeler.
   **Bilinen sınır:** tekrarlı takip kayıtları yüzünden fazladan kazanılmış stat puanları
   yeniden hesaplanmaz.
3. 002'yi yeniden çalıştırın.

### 003 "003_DURDU" ile durursa

* **Denetimde** (`003_DURDU_veri_sorunu_var_hicbir_sey_degistirilmedi`): hiçbir şey
  değişmemiştir; eski kod çalışmaya devam eder. Basılan listeye göre veriyi elle düzeltin:
  * *"Ayni kullanicida ayni sayilan adlar"*: bir kullanıcıda, yeni sütunun karşılaştırma
    kuralına göre aynı sayılan iki alışkanlık var (ör. eski tablo `utf8mb4_bin` iken
    'Kitap Oku' ve 'kitap oku'). Birini yeniden adlandırın ya da birleştirin.
  * *"StatTuru NULL ya da gecersiz"* / *"Puan NULL"*: `Aliskanliklar`'daki değeri düzeltin.
  * *"002 uygulanmamis"*: önce 002.
* **Sıkılaştırma adımında** (ör. `ERROR 1062 ... UQ_KA_Kullanici_Ad`): sütunlar eklenmiş
  ve doldurulmuş, tetikleyici kurulmuştur; NOT NULL / CHECK / UNIQUE değişikliği tek ve
  atomik bir `ALTER TABLE` olduğundan hiçbiri uygulanmamıştır. Uygulama (eski ve yeni kod)
  çalışır durumdadır. Çakışan satırı düzeltip 003'ü yeniden çalıştırın.
* Bir saklı yordamın içinde durulduysa `cm003_*` (002'de `cm002_*`) yordamları veritabanında
  kalabilir; zararsızdırlar ve dosya bir sonraki çalıştırmada silinir. Hemen temizlemek için:
  ```sql
  DROP PROCEDURE IF EXISTS cm003_hazirla;  DROP PROCEDURE IF EXISTS cm003_sikilastir;
  -- 002: cm002_uygula, cm002_fk, cm002_check, cm002_indeks, cm002_sql
  ```
  `SHOW PROCEDURE STATUS WHERE Db = 'charmoji';` kalanları listeler.
* `Aliskanliklar.Ad` 100 karakterden uzunsa ("Ad 100 karakterden uzun") 003 durur; adı kısaltın.
* `003_DURDU_004_zaten_uygulanmis...`: 004 sonrası 003 bir daha çalıştırılmaz.

## Migration ve araç listesi

| Dosya | Ne yapar | Veri siler mi? | Otomatik mi? |
|---|---|---|---|
| `migrations/001_baseline.sql` | Kodun beklediği tablolar (`CREATE TABLE IF NOT EXISTS`), eksikse `AvatarVerisi` | Hayır | Evet |
| `migrations/002_kisitlar.sql` | UNIQUE, FK, CHECK, indeksler; `KullaniciStatlari.ToplamPuan` NOT NULL DEFAULT 0; `SonIslemTarihi` DATETIME ise DATE | Hayır (sorun varsa durur) | Evet |
| `migrations/003_kullaniciya_ozel_aliskanliklar.sql` | `KullaniciAliskanliklari`'na Ad/StatTuru/Puan ekler ve doldurur; geçiş tetikleyicisi | Hayır | Evet |
| `araclar/tekrar_temizle.sql` | 002'yi durduran satırları temizler | **Evet** (yedek tablolara kopyalayarak) | **Hayır — elle, `@onay` ile** |
| `araclar/004_ortak_aliskanliklari_kaldir.sql` | `Aliskanliklar` tablosunu, `AliskanlikID` sütununu ve onu içeren tüm indeks/FK'leri, tetikleyiciyi kaldırır | **Evet — GERİ ALINAMAZ** | **Hayır — elle, `@onay` ile** |

### ⚠ 004: geri alınamaz, elle ve onayla çalıştırılır

`araclar/004_ortak_aliskanliklari_kaldir.sql` normal kurulum/migration akışının parçası
**değildir**. Yalnızca şunlar sağlandıktan sonra çalıştırılır:

1. 003 uygulanmış ve en az bir süre sorunsuz çalışmış olmalı.
2. PHP kodu aşağıdaki "003 sonrası PHP değişiklikleri" listesine göre güncellenmiş olmalı
   (eski kod 004'ten sonra alışkanlık ekleyemez ve listeleyemez).
3. Tam yedek alınmış olmalı.
4. Betik `@onay='EVET-ALISKANLIKLAR-SILINSIN'` olmadan hiçbir şey yapmaz (istemci hatada
   devam etse bile).

004'ten sonra `schema.sql` ve `seed.sql` de 004 sonrası duruma güncellenmelidir; ikisi de
şu an geçiş için `Aliskanliklar` tablosunu içerir. 004'ten sonra 002 ve 003 yeniden
çalıştırılmaz (ikisi de `AliskanlikID`'ye başvurur).

## Şema hakkında kararlar

**Neden `schema.sql` = 001+002+003 (004 hariç)?** Bugünkü PHP kodu alışkanlıkları hâlâ
`Aliskanliklar` ile JOIN'leyerek okuyor. Sıfırdan kurulan ortamın kod güncellenmeden de
çalışması için eski tablo, `AliskanlikID` ve `TRG_KA_EskiKodUyumu` tetikleyicisi geçiş
süresince tutuluyor. `mysqldump --no-data --routines --triggers` ile karşılaştırıldığında
001→002→003 uygulanmış veritabanı ile `schema.sql` birebir aynıdır.

**StatTuru: ENUM değil, CHECK + `utf8mb4_0900_bin`.** Geçerli değerler kodda yazıldığı gibi:
`Zeka`, `Guc`, `Sosyallik`, `Disiplin`, `Yaraticilik` (`view/userpage.php:53`).
* `utf8mb4_unicode_ci` harf ve aksan duyarsız ve PAD SPACE'tir: `'güç'`, `'GUC'`, `'Guc '`
  normal karşılaştırmada `'Guc'`a eşittir. Kod ise birebir karşılaştırma yapıyor.
  `utf8mb4_0900_bin` ikili ve NO PAD'dir; bu yazımların hepsini reddeder. (`utf8mb4_bin`
  PAD SPACE olduğu için `'Guc '`u kabul ederdi.)
* ENUM, katı olmayan `sql_mode`'da geçersiz değeri `''` olarak saklar; CHECK her
  `sql_mode`'da hata verir (INSERT IGNORE'da satır eklenmez).
* Sütun VARCHAR kaldığı için PHP sorgularında değişiklik gerekmez.

Kısıt `KullaniciStatlari.StatTuru` (002) ve `KullaniciAliskanliklari.StatTuru` (003)
üzerindedir. Geçişte kalan eski `Aliskanliklar.StatTuru` kısıtsızdır; oradan gelen geçersiz
değer, tetikleyici `KullaniciAliskanliklari`'na yazarken reddedilir.

**Benzersizlik ve ilişkiler**

| Tablo | UNIQUE | FK (ON DELETE) | Neden |
|---|---|---|---|
| KullaniciStatlari | (KullaniciID, StatTuru) | Kullanicilar (CASCADE) | `userpage.php` INSERT IGNORE |
| KullaniciAliskanliklari | (KullaniciID, AliskanlikID), 003: (KullaniciID, Ad) | Kullanicilar (CASCADE), Aliskanliklar (RESTRICT) | `userpage.php:78` INSERT IGNORE |
| TakipKayitlari | (KullaniciAliskanlikID, Tarih) | KullaniciAliskanliklari (CASCADE) | INSERT IGNORE; aynı gün çift puan. CASCADE çünkü alışkanlık doğrudan silinir |
| KullaniciEnvanteri | (KullaniciID, EsyaID) | Kullanicilar (CASCADE), MarketEsyalari (RESTRICT) | Satın alma yarışı |
| KullaniciGunlukGorevleri | (KullaniciID, Tarih, GorevTanimID) | Kullanicilar (CASCADE), GorevTanimlari (RESTRICT) | Sayfa iki kez açılırsa görevler çiftlenir, ödül iki kez alınır |
| Arkadasliklar | (LEAST(a,b), GREATEST(a,b)) fonksiyonel | Kullanicilar ×2 (CASCADE) | Kontrol-sonra-ekle yarışı; A→B ve B→A aynı çift |

002, aynı adlı **ya da aynı sütun dizisine sahip başka adlı** bir indeks varsa yenisini
eklemez (ör. eski kurulumdaki `UQ_KA`, `UQ_Stat`, `UQ_TK`, `UQ_Env` kabul edilir); aynı
sütunlarda UNIQUE olmayan bir indeks varsa onu UNIQUE ile değiştirir.

## 003 sonrası PHP değişiklikleri (Özellik ajanı için)

003 uygulandıktan sonra kod, alışkanlığın adını/stat türünü/puanını
`KullaniciAliskanliklari`'ndan okumalı ve yazmalı. Tetikleyici sayesinde eski kod çalışmaya
devam eder, ama kullanıcıya özellik ancak bu değişikliklerle devreye girer ve 004 ancak
bunlardan sonra uygulanabilir. Satır numaraları `main` (f46c536) dalına göredir.

| Dosya:satır | Bugün | Olması gereken |
|---|---|---|
| `view/userpage.php:75-77` | `SELECT AliskanlikID FROM Aliskanliklar WHERE Ad = ?`, yoksa `INSERT INTO Aliskanliklar (Ad, StatTuru, Puan)` | Kaldırılmalı; ortak tabloya artık yazılmaz. |
| `view/userpage.php:78` | `$conn->query("INSERT IGNORE INTO KullaniciAliskanliklari (KullaniciID, AliskanlikID, BaslangicTarihi) VALUES ($kullaniciID, $aliskanlikID, CURDATE())")` | Hazır ifade: `INSERT IGNORE INTO KullaniciAliskanliklari (KullaniciID, Ad, StatTuru, Puan, BaslangicTarihi) VALUES (?, ?, ?, ?, CURDATE())`. Aynı kullanıcıda aynı ad varsa UQ_KA_Kullanici_Ad yüzünden eklenmez (`affected_rows = 0` → kullanıcıya mesaj). |
| `view/userpage.php:83` | `SELECT KA.KullaniciAliskanlikID, A.Ad, A.StatTuru, A.Puan, ... FROM KullaniciAliskanliklari KA JOIN Aliskanliklar A ON KA.AliskanlikID = A.AliskanlikID ...` | `SELECT KA.KullaniciAliskanlikID, KA.Ad, KA.StatTuru, KA.Puan, KA.Seri, (...) AS BugunYapildi FROM KullaniciAliskanliklari KA WHERE KA.KullaniciID = ? AND KA.AktifMi = 1` (JOIN yok) |
| `view/ajax_islem.php:240-243` | `SELECT KA.Seri, KA.SonIslemTarihi, A.Puan, A.StatTuru FROM KullaniciAliskanliklari KA JOIN Aliskanliklar A ON KA.AliskanlikID = A.AliskanlikID WHERE ...` | `SELECT KA.Seri, KA.SonIslemTarihi, KA.Puan, KA.StatTuru FROM KullaniciAliskanliklari KA WHERE KA.KullaniciAliskanlikID = ? AND KA.KullaniciID = ?` |

Başka hiçbir PHP dosyası `Aliskanliklar` tablosuna veya `AliskanlikID` sütununa başvurmuyor
(`grep -rnwE "Aliskanliklar|AliskanlikID" service view config` yalnızca yukarıdaki satırları bulur).

### 002 sonrası önerilen PHP düzeltmeleri (main'de hâlâ açık olanlar)

PHP 8.1+ `mysqli` hata durumunda istisna fırlatır. Yeni UNIQUE anahtarlar yarış durumunda
düz `INSERT`'leri hataya düşürür; veri bozulmaz ama kullanıcı hata görebilir:

| Dosya:satır | Sorun | Öneri |
|---|---|---|
| `view/userpage.php:35` | Günlük görevler düz `INSERT` ile ekleniyor; iki eşzamanlı sayfa açılışında ikincisi UQ_KGG hatasıyla yakalanmamış istisna verir | `INSERT IGNORE` |
| `view/ajax_islem.php:150` | Eşzamanlı aynı eşya alımında UQ_Env hatası `catch` ile yakalanır ama kullanıcı genel "sunucu hatası" mesajı görür | 1062 hatasını "Zaten sahipsin." olarak çevir |
| `view/ajax_islem.php:133-149` | Bakiye okunup PHP'de hesaplanıp yazılıyor (`SET Bakiye = ?`): eşzamanlı iki farklı alımda biri kaybolur (lost update) | `SELECT ... FOR UPDATE` ya da `UPDATE Kullanicilar SET Bakiye = Bakiye - ? WHERE KullaniciID = ? AND Bakiye >= ?` + `affected_rows` kontrolü |
| `view/ajax_islem.php:172-182` | Ödül önce okunup sonra veriliyor; eşzamanlı iki istekte ödül iki kez alınabilir | Önce `UPDATE KullaniciGunlukGorevleri SET OdulAlindiMi = 1 WHERE KullaniciGorevID = ? AND KullaniciID = ? AND TamamlandiMi = 1 AND OdulAlindiMi = 0`, `affected_rows = 1` ise bakiyeyi artır |
| `service/FriendManager.php:44,98,113` | Topluluk/istek/arkadaş sorguları `AvatarVerisi` seçmiyor; `view/userpage.php:273,286,298` bu alanı bekliyor (hep varsayılan avatar çizilir) | Sorgulara `k.AvatarVerisi` / `K.AvatarVerisi` ekle |

## Varsayımlar, sınırlar ve ortam notları

* Sütun tipleri koddan türetildi; yalnızca `KullaniciStatlari` kesin. Diğerlerinin gerçek
  veritabanındaki uzunlukları/NULL durumu farklı olabilir; 001 mevcut tabloları değiştirmez.
* `SonIslemTarihi` DATE: kod `'Y-m-d'` yazıp `'Y-m-d'` ile karşılaştırıyor; DATETIME olsaydı
  seri hiç artmazdı. 002, DATETIME ise (saat bilgisi yoksa) DATE'e çevirir.
* Kodun hiç kullanmadığı eski not sütunları (AvatarURL, Seviye, XP, Aciklama, VarsayilanHedef,
  HedefTipi, OzelHedefDegeri, GerceklesenDeger, AlimTarihi) sıfırdan kurulumda yoktur; mevcut
  kurulumda varlarsa dokunulmaz. `Aliskanliklar`'daki bu tür sütunlar 004 ile tablo birlikte gider.
* **`sql_mode`:** Betikler MySQL 8 varsayılanı olan katı modda (`STRICT_TRANS_TABLES`,
  `NO_ZERO_DATE`, `NO_ZERO_IN_DATE`) denendi. Katı olmayan modda (bazı XAMPP/WAMP
  kurulumları) MySQL geçersiz bir tarihi hata vermeden `0000-00-00` olarak kaydeder;
  `SELECT @@sql_mode;` ile kontrol edin ve katı modu tercih edin. CHECK kısıtları her modda
  geçerlidir.
* **`lower_case_table_names`:** Gerçek kurulum büyük olasılıkla Windows'ta
  (`lower_case_table_names=1`; eski veri dosyaları küçük harfliydi). Orada tablo adları
  küçük harfle saklanır (`kullanicilar`) ve `mysqldump` çıktısı da küçük harflidir. Betikler
  `information_schema` sorgularında tablo adlarını küçük harfe çevirerek karşılaştırır. Bu
  ayarla **denenmedi**. Windows'tan alınmış küçük harfli bir dökümü Linux'a
  (`lower_case_table_names=0`) yüklerseniz tablolar `kullanicilar` adıyla oluşur ve büyük/küçük
  harfe duyarlı Linux'ta PHP'nin `Kullanicilar` sorguları "Table doesn't exist" verir. Bu
  durumda ya Linux sunucusu `lower_case_table_names=1` ile **ilk kurulumda** başlatılmalı
  (sonradan değiştirilemez) ya da tablolar `RENAME TABLE` ile koddaki adlara çevrilmelidir.
* Eski nottaki `arkadaslar` tablosu kodda kullanılmıyor; mevcut kurulumda varsa dokunulmaz,
  verisi `Arkadasliklar`'a taşınmaz.
