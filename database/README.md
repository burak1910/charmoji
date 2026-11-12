# Charmoji veritabanı

Bu klasör Charmoji'nin MySQL şemasının tek doğru kaynağıdır. Gerçek veritabanının dökümü
elimizde olmadığından şema, PHP kodunun (`service/*.php`, `view/*.php`) kullandığı tablo ve
sütunlardan türetildi. Tek doğrudan kanıt, eskiden repoda takipli olan
`mysql-8.4/charmoji/kullanicistatlari.ibd` dosyasıydı (`ibd2sdi` ile okundu):
`KullaniciStatlari` tablosu 001'de birebir o tanımla yazıldı.

```
database/
├── schema.sql                     sıfırdan kurulum (= 001 + 002 + 003 + 005)
├── seed.sql                       geliştirme verisi
├── migrations/
│   ├── 001_baseline.sql           bugünkü kodun beklediği şema
│   ├── 002_kisitlar.sql           UNIQUE / FK / CHECK / indeksler (veri silmez)
│   ├── 003_kullaniciya_ozel_aliskanliklar.sql   alışkanlıkları kullanıcıya özel yapar (veri taşır, silmez)
│   └── 005_gorev_kurallari.sql    görev kuralları: aktif ad benzersizliği, arşiv, geri alma verisi, meydan okuma türü (veri silmez)
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

İkisi arkadaş (kabul edilmiş). Ayrıca 2 meydan okuma tanımı (`Günlük Giriş` türü `giris`,
`3 Görev Tamamla` türü `gorev_tamamlama`) ve `avatar_renderer.php`'nin adıyla çizdiği 5 market
eşyası eklenir. `seed.sql` eski ortak `Aliskanliklar` tablosunu kullanmaz (004 sonrası da
çalışır) ve tarihleri oyunun gün sınırına göre (`SET time_zone = '+03:00'`) hesaplar. Tekrar
çalıştırılabilir: var olan kullanıcı, eşya, meydan okuma, görev (aynı adlı aktif ya da
arşivlenmiş), envanter ve arkadaşlık satırlarını yeniden eklemez; takip kaydını yalnızca hiç
kaydı olmayan göreve ekler (başka bir günde çalıştırılsa da yeni satır eklemez). Var olan
satırları değiştirmez.

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

Sıra: **001 → 002 → 003 → 005** (004 bir migration değil, elle çalıştırılan araçtır; aşağıya
bakın). Hepsi tekrar çalıştırılabilir; uygulananlar `SemaGecmisi` tablosuna yazılır
(`SELECT * FROM SemaGecmisi;`). 002 ve 003'ün daha eski bir sürümünü uyguladıysanız ikisini
yeniden çalıştırın: StatTuru CHECK'ini sondaki boşluğu da reddeden sürüme günceller, başka bir
şeyi değiştirmez. 005'ten sonra 003 yeniden çalıştırılırsa `UQ_KA_Kullanici_Ad`'ı geri eklemez
ve arşivlenmiş aynı adlı görevleri sorun saymaz. 005, 004'ten önce de sonra da çalışır.

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

# 5) Görev kuralları (Aşama 2 PHP kodu bunu gerektirir). Sütun ekler, hiçbir şey silmez.
#    Aynı oyuncunun AKTİF görevlerinde yeni kurala göre aynı sayılan iki ad varsa listeyi basar
#    ve hiçbir şey değiştirmeden durur.
mysql -uroot --default-character-set=utf8mb4 charmoji < database/migrations/005_gorev_kurallari.sql
```

**Dağıtım sırası (Aşama 2):** önce 005, sonra PHP kodu. Aşama 2 kodu 005'in sütunlarını
(`AktifAd`, `ArsivZamani`, `TakipKayitlari.KazanilanXP` …, `GorevTanimlari.Tur`) kullanır;
005 uygulanmamış veritabanında görev sayfaları hata verir. 005 eski (Aşama 1) kodu bozmaz:
eski kod bu sütunları okumaz; tek fark, eski kodun silme işleminin satırı gerçekten silmesi ve
meydan okumayı başlıktan bulmasıdır (geçiş süresince zararsız).

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

### 005 "005_DURDU" ile durursa

* `005_DURDU_once_003_uygulanmali...`: önce 003.
* `005_DURDU_veri_sorunu_var...`: basılan listede *"Ayni kullanicinin aktif gorevlerinde ayni
  sayilan adlar"* varsa bir oyuncunun iki aktif görevi yeni kurala (`utf8mb4_tr_0900_ai_ci`:
  Türkçe büyük/küçük harf duyarsız; ör. `'KITAP'` ile `'kıtap'`) göre aynı sayılıyordur. Birini
  yeniden adlandırın ya da `AktifMi = 0` yapın, 005'i yeniden çalıştırın. Hiçbir şey değişmemiştir.
* Yordamın içinde durulduysa `cm005_uygula`, `cm005_sql` kalabilir; dosya bir sonraki
  çalıştırmada siler.

## Migration ve araç listesi

| Dosya | Ne yapar | Veri siler mi? | Otomatik mi? |
|---|---|---|---|
| `migrations/001_baseline.sql` | Kodun beklediği tablolar (`CREATE TABLE IF NOT EXISTS`), eksikse `AvatarVerisi` | Hayır | Evet |
| `migrations/002_kisitlar.sql` | UNIQUE, FK, CHECK, indeksler; `KullaniciStatlari.ToplamPuan` NOT NULL DEFAULT 0; `SonIslemTarihi` DATETIME ise DATE | Hayır (sorun varsa durur) | Evet |
| `migrations/003_kullaniciya_ozel_aliskanliklar.sql` | `KullaniciAliskanliklari`'na Ad/StatTuru/Puan ekler ve doldurur; geçiş tetikleyicisi | Hayır | Evet |
| `migrations/005_gorev_kurallari.sql` | `KullaniciAliskanliklari`: `AktifAd` (üretilmiş) + `UQ_KA_Kullanici_AktifAd` (eski `UQ_KA_Kullanici_Ad` yerine), `ArsivZamani`; `TakipKayitlari`: `KazanilanXP`, `StatTuru`, `OncekiSeri`, `OncekiSonIslemTarihi` (+ CHECK); `GorevTanimlari.Tur` (+ CHECK) doldurulur; seed başlığı `3 Alışkanlık Tamamla` → `3 Görev Tamamla` | Hayır (bir UNIQUE indeksi daha geniş olanıyla değiştirir; yalnızca o birebir başlığı yeniden adlandırır) | Evet |
| `araclar/tekrar_temizle.sql` | 002'yi durduran satırları temizler | **Evet** (yedek tablolara kopyalayarak) | **Hayır — elle, `@onay` ile** |
| `araclar/004_ortak_aliskanliklari_kaldir.sql` | `Aliskanliklar` tablosunu, `AliskanlikID` sütununu ve onu içeren tüm indeks/FK'leri, tetikleyiciyi kaldırır | **Evet — GERİ ALINAMAZ** | **Hayır — elle, `@onay` ile** |

### ⚠ 004: geri alınamaz, elle ve onayla çalıştırılır

`araclar/004_ortak_aliskanliklari_kaldir.sql` normal kurulum/migration akışının parçası
**değildir**. Yalnızca şunlar sağlandıktan sonra çalıştırılır:

1. 003 uygulanmış ve en az bir süre sorunsuz çalışmış olmalı.
2. PHP kodu aşağıdaki "003 sonrası PHP değişiklikleri" listesine göre güncellenmiş olmalı
   (eski kod 004'ten sonra alışkanlık ekleyemez ve listeleyemez). **Durum:** Aşama 2 kodu
   (`claude/amazing-wright-ht2d7d`) bu koşulu sağlar; 004 sonrası şemada tüm güvenlik testleri
   geçer (bkz. aşağıdaki "004 sonrası doğrulama"). Canlıda eski kod kalmadığından emin olun.
3. Tam yedek alınmış olmalı.
4. Betik `@onay='EVET-ALISKANLIKLAR-SILINSIN'` olmadan hiçbir şey yapmaz (istemci hatada
   devam etse bile).

004'ten sonra `schema.sql` 004 sonrası duruma güncellenmelidir (şu an geçiş için
`Aliskanliklar` tablosunu, `AliskanlikID`'yi ve tetikleyiciyi içerir). `seed.sql` artık
`Aliskanliklar`'a yazmaz; 004 sonrasında da olduğu gibi çalışır. 004'ten sonra 002 ve 003
yeniden çalıştırılmaz (ikisi de `AliskanlikID`'ye başvurur); 005 çalıştırılabilir.

**004 sonrası doğrulama (yapıldı, elle):** deponun bir kopyasında test kurulumuna
`schema.sql` + `seed.sql` ardından `SET @onay='EVET-ALISKANLIKLAR-SILINSIN'; SOURCE
database/araclar/004_ortak_aliskanliklari_kaldir.sql;` eklenerek `tests/guvenlik/calistir.sh`
çalıştırıldı: tüm denetimler geçti. 004 otomatik akışa eklenmedi.

## Şema hakkında kararlar

**Neden `schema.sql` = 001+002+003+005 (004 hariç)?** PHP kodu artık `Aliskanliklar`'ı
kullanmıyor, ama 004 geri alınamaz ve elle çalıştırılır. 004 uygulanana kadar eski kodun (ör.
geri dönüş) sıfırdan kurulumda da çalışabilmesi için eski tablo, `AliskanlikID` ve
`TRG_KA_EskiKodUyumu` tetikleyicisi tutuluyor. `mysqldump --no-data --routines --triggers` ile
karşılaştırıldığında 001→002→003→005 uygulanmış veritabanı ile `schema.sql` birebir aynıdır.

**Görev adı benzersizliği (S9) ve arşiv (S18) — 005.** Silinen görev arşivlenir (`AktifMi = 0`,
`ArsivZamani`); XP, seri ve takip kayıtları yerinde kalır, 5 sn içinde geri getirilebilir
(sunucu ağ payıyla 15 sn kabul eder). Ad yalnızca **aktif** görevler arasında benzersizdir:
`AktifAd = IF(AktifMi = 1, Ad, NULL)` STORED üretilmiş sütunu ve `UNIQUE (KullaniciID,
AktifAd)`; NULL'lar çakışmadığı için arşivlenmiş görevin adıyla yeni görev eklenebilir. Sütunun
kuralı `utf8mb4_tr_0900_ai_ci`'dir: Türkçe büyük/küçük harf duyarsız (`'Kitap Oku'` =
`'KİTAP OKU'`, `'KITAP'` ≠ `'kitap'`), ç ğ ı ö ş ü ayrı harf (`'Şınav'` ≠ `'Sınav'`), NO PAD.
(MySQL 8.0'da `utf8mb4_tr_0900_as_ci` yoktur; `ai` Türkçe harfleri zaten ayırır, yalnızca
Türkçede olmayan aksanları (é = e) eşit sayar.) PHP önce aynı kuralla sorgular (`AktifAd = ?`),
yarışta UNIQUE 1062 döner ve "Bu adda bir görevin zaten var" gösterilir.

**Geri alma verisi — 005.** `TakipKayitlari` her tamamlamada kazanılan XP'yi, XP'nin yazıldığı
yeteneği ve tamamlama öncesi `Seri`/`SonIslemTarihi`'ni saklar. Geri alma bunları aynen geri
yükler: XP kazanıldığı yetenekten düşülür (S19: yetenek sonradan değişse de), seri ve son tarih
tamamlama öncesine döner; böylece aynı gün yeniden tamamlanınca seri yine artar (eski kodda
`SonIslemTarihi` bugün kaldığı için artmıyordu). 005 öncesi satırlarda bu sütunlar NULL'dır;
kod o durumda eski hesaba (seri − 1, önceki tamamlama günü) düşer.

**Meydan okuma türü (S25) — 005.** `GorevTanimlari.Tur`: `giris` (o gün herhangi bir sekme
açılınca tamamlanır) ya da `gorev_tamamlama` (o gün tamamlanan görev sayısı; geri alma 1
azaltır, ödülü alınmışsa değişmez — S7). Kod artık başlık metnine (`'Günlük Giriş'`,
`LIKE '%alışkanlık%'`) bakmaz. Yeni meydan okuma eklerken `Tur` zorunludur (NOT NULL + CHECK).

**StatTuru: ENUM değil, CHECK + `utf8mb4_0900_bin`.** Geçerli değerler kodda yazıldığı gibi:
`Zeka`, `Guc`, `Sosyallik`, `Disiplin`, `Yaraticilik` (`view/ortak/cekirdek.php:22` `YETENEKLER`, `view/ortak/gorev_islemleri.php` `gorev_yetenegi_dogrula()`).
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
| KullaniciStatlari | (KullaniciID, StatTuru) | Kullanicilar (CASCADE) | `view/ortak/cekirdek.php:331` INSERT IGNORE |
| KullaniciAliskanliklari | (KullaniciID, AliskanlikID); 005: (KullaniciID, AktifAd) — 003'teki (KullaniciID, Ad) yerine | Kullanicilar (CASCADE), Aliskanliklar (RESTRICT) | Eski kodun INSERT IGNORE'u; aktif görevlerde aynı ad (S9) |
| TakipKayitlari | (KullaniciAliskanlikID, Tarih) | KullaniciAliskanliklari (CASCADE) | INSERT IGNORE; aynı gün çift puan. CASCADE çünkü alışkanlık doğrudan silinir |
| KullaniciEnvanteri | (KullaniciID, EsyaID) | Kullanicilar (CASCADE), MarketEsyalari (RESTRICT) | Satın alma yarışı |
| KullaniciGunlukGorevleri | (KullaniciID, Tarih, GorevTanimID) | Kullanicilar (CASCADE), GorevTanimlari (RESTRICT) | Sayfa iki kez açılırsa görevler çiftlenir, ödül iki kez alınır |
| Arkadasliklar | (LEAST(a,b), GREATEST(a,b)) fonksiyonel | Kullanicilar ×2 (CASCADE) | Kontrol-sonra-ekle yarışı; A→B ve B→A aynı çift |

002, aynı adlı **ya da aynı sütun dizisine sahip başka adlı** bir indeks varsa yenisini
eklemez (ör. eski kurulumdaki `UQ_KA`, `UQ_Stat`, `UQ_TK`, `UQ_Env` kabul edilir); aynı
sütunlarda UNIQUE olmayan bir indeks varsa onu UNIQUE ile değiştirir.

## 003 sonrası PHP değişiklikleri (TAMAMLANDI — Dalga 2 / Aşama 2)

003 uygulandıktan sonra kod, alışkanlığın adını/stat türünü/puanını
`KullaniciAliskanliklari`'ndan okumalı ve yazmalıydı. **Aşama 2'de yapıldı:** görev iş
kuralları `view/ortak/gorev_islemleri.php`'de toplandı; okuma `view/ortak/cekirdek.php`
`GOREV_SECIMI` / `aktif_gorevler()` ile, yazma (ekle, düzenle, arşivle, geri getir, tamamla,
geri al) yalnızca kullanıcıya özel sütunlarla yapılır. `grep -rnwE "Aliskanliklar|AliskanlikID"
service view config` yalnızca açıklama satırlarını bulur. Aşağıdaki tablo kayıt için
(geçişten önceki durum) bırakıldı:

| Dosya:satır (geçiş öncesi) | O zaman | Şimdi |
|---|---|---|
| `view/gorevler.php:35-41` (görev ekleme) | `SELECT AliskanlikID FROM Aliskanliklar WHERE Ad = ?`, yoksa `INSERT INTO Aliskanliklar (Ad, StatTuru, Puan)` | Kaldırıldı; ortak tabloya yazılmıyor. |
| `view/gorevler.php:43-44` | `INSERT IGNORE INTO KullaniciAliskanliklari (KullaniciID, AliskanlikID, BaslangicTarihi) VALUES (?, ?, CURDATE())` | `gorev_ekle()`: transaction + oyuncu satırı kilidi, sınır (20) ve aktif ad denetimi, `INSERT INTO KullaniciAliskanliklari (KullaniciID, Ad, StatTuru, Puan, BaslangicTarihi) VALUES (?, ?, ?, ?, ?)` (tarih PHP'den, S1); 1062 → `ad_ayni`. |
| `view/ortak/cekirdek.php:306-312` (`aktif_gorevler()`) | `... FROM KullaniciAliskanliklari KA JOIN Aliskanliklar A ON ...` | `GOREV_SECIMI`: `KA.Ad, KA.StatTuru, KA.Puan, ...` + bugünkü `TakipKayitlari` (LEFT JOIN), JOIN'siz. |
| `view/ajax_islem.php:280-283` | `SELECT KA.Seri, KA.SonIslemTarihi, A.Puan, A.StatTuru ... JOIN Aliskanliklar ...` | `gorev_tamamla()` / `gorev_geri_al()`: `GOREV_SECIMI ... WHERE KA.KullaniciAliskanlikID = ? AND KA.KullaniciID = ? AND KA.AktifMi = 1 FOR UPDATE`. |
| `view/karakter.php:22-24` | `SELECT A.StatTuru, COUNT(*) ... JOIN Aliskanliklar ... GROUP BY A.StatTuru` | `SELECT KA.StatTuru, COUNT(*) FROM KullaniciAliskanliklari KA WHERE KA.KullaniciID = ? AND KA.AktifMi = 1 GROUP BY KA.StatTuru`. |

Tetikleyici (`TRG_KA_EskiKodUyumu`) yalnızca eski kodun `AliskanlikID` ile yaptığı INSERT'te
devreye girer; yeni kod `Ad` verdiği için hiç tetiklemez. 004 ile kaldırılabilir.

### 002 sonrası önerilen PHP düzeltmeleri (main'de hâlâ açık olanlar)

PHP 8.1+ `mysqli` hata durumunda istisna fırlatır. Yeni UNIQUE anahtarlar yarış durumunda
düz `INSERT`'leri hataya düşürür; veri bozulmaz ama kullanıcı hata görebilir:

| Dosya:satır | Sorun | Öneri |
|---|---|---|
| `view/ajax_islem.php:184-189` (satın alma `INSERT` + `catch`) | Eşzamanlı aynı eşya alımında UQ_Env hatası `catch` ile yakalanır ama kullanıcı genel "sunucu hatası" mesajı görür | 1062 hatasını "Zaten sahipsin." olarak çevir |
| `view/ajax_islem.php:158-183` (satın alma) | Bakiye okunup PHP'de hesaplanıp yazılıyor (`SET Bakiye = ?`): eşzamanlı iki farklı alımda biri kaybolur (lost update) | `SELECT ... FOR UPDATE` ya da `UPDATE Kullanicilar SET Bakiye = Bakiye - ? WHERE KullaniciID = ? AND Bakiye >= ?` + `affected_rows` kontrolü |
| `view/ajax_islem.php:205-222` (ödül alma) | Ödül önce okunup sonra veriliyor; eşzamanlı iki istekte ödül iki kez alınabilir | Önce `UPDATE KullaniciGunlukGorevleri SET OdulAlindiMi = 1 WHERE KullaniciGorevID = ? AND KullaniciID = ? AND TamamlandiMi = 1 AND OdulAlindiMi = 0`, `affected_rows = 1` ise bakiyeyi artır |

Giderilenler (artık geçerli değil): günlük görevlerin düz `INSERT` ile eklenmesi
(`view/ortak/cekirdek.php:297` `INSERT IGNORE` kullanıyor) ve `service/FriendManager.php`
sorgularında `AvatarVerisi` eksikliği (topluluk/istek/arkadaş sorguları artık seçiyor).

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
