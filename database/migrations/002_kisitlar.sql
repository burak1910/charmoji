-- =====================================================================
-- 002_kisitlar.sql  —  Eksik UNIQUE, FOREIGN KEY, CHECK ve indeksler
-- ---------------------------------------------------------------------
-- Ön koşul: 001_baseline.sql uygulanmış olmalı.
--
-- Çözülen sorunlar:
--   * TakipKayitlari: UNIQUE(KullaniciAliskanlikID, Tarih) yoktu; kod
--     INSERT IGNORE ile buna güveniyor -> aynı gün puan tekrar kazanılıyordu.
--   * KullaniciEnvanteri: UNIQUE(KullaniciID, EsyaID) yoktu -> satın
--     almada yarış koşulu (aynı eşya iki kez alınabiliyordu).
--   * KullaniciStatlari: UNIQUE(KullaniciID, StatTuru) garanti altına
--     alınır (userpage.php INSERT IGNORE); StatTuru beş geçerli değerle
--     CHECK ile sınırlanır; ToplamPuan NOT NULL DEFAULT 0 olur
--     (kod her zaman 0 ile ekliyor; NULL olursa puan toplamı NULL kalır).
--   * KullaniciAliskanliklari: UNIQUE(KullaniciID, AliskanlikID)
--     (userpage.php INSERT IGNORE); SonIslemTarihi DATETIME ise DATE'e
--     çevrilir (kod Y-m-d ile karşılaştırıyor; DATETIME'da seri artmaz).
--   * KullaniciGunlukGorevleri: UNIQUE(KullaniciID, Tarih, GorevTanimID)
--     -> günlük görevlerin iki kez oluşup ödülün iki kez alınmasını önler.
--   * Arkadasliklar: aynı iki kişi arasında (yönden bağımsız) tek kayıt.
--   * Tüm ilişkiler için FOREIGN KEY; sorgu desenleri için indeksler.
--
-- VERİ SİLMEZ. Önce tüm kontroller yapılır; tekrarlı / sahipsiz /
-- geçersiz satır varsa bunlar bir tablo halinde listelenir ve migration
-- HİÇBİR ŞEYİ DEĞİŞTİRMEDEN hata ile durur. Temizlik için elle
-- çalıştırılan betik: database/araclar/tekrar_temizle.sql
--
-- Tekrar tekrar çalıştırılabilir: var olan kısıt/indeks yeniden eklenmez.
-- =====================================================================

SET NAMES utf8mb4;

DELIMITER $$

-- ---------------------------------------------------------------------
-- Yardımcılar (dosya sonunda silinir)
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS cm002_sql$$
CREATE PROCEDURE cm002_sql(IN p_sql TEXT)
BEGIN
  SET @cm002_sql = p_sql;
  PREPARE cm002_st FROM @cm002_sql;
  EXECUTE cm002_st;
  DEALLOCATE PREPARE cm002_st;
END$$

-- İndeks adıyla yoksa ekler. p_tanim: 'UNIQUE KEY `X` (...)' gibi.
DROP PROCEDURE IF EXISTS cm002_indeks$$
CREATE PROCEDURE cm002_indeks(IN p_tablo VARCHAR(64), IN p_ad VARCHAR(64), IN p_tanim TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND LOWER(TABLE_NAME) = LOWER(p_tablo)
       AND INDEX_NAME = p_ad
  ) THEN
    CALL cm002_sql(CONCAT('ALTER TABLE `', p_tablo, '` ADD ', p_tanim));
  END IF;
END$$

-- CHECK kısıtı adıyla yoksa ekler.
DROP PROCEDURE IF EXISTS cm002_check$$
CREATE PROCEDURE cm002_check(IN p_tablo VARCHAR(64), IN p_ad VARCHAR(64), IN p_ifade TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE()
       AND LOWER(TABLE_NAME) = LOWER(p_tablo)
       AND CONSTRAINT_NAME = p_ad
       AND CONSTRAINT_TYPE = 'CHECK'
  ) THEN
    CALL cm002_sql(CONCAT('ALTER TABLE `', p_tablo, '` ADD CONSTRAINT `', p_ad, '` CHECK (', p_ifade, ')'));
  END IF;
END$$

-- FOREIGN KEY'i istenen ad ve ON DELETE kuralıyla garanti eder.
-- Aynı sütunda farklı ad/kuralla bir FK varsa (ör. eski kurulumda
-- CASCADE'siz FK_TK_KA) önce o kısıt kaldırılır (yalnızca kısıt; veri değil).
DROP PROCEDURE IF EXISTS cm002_fk$$
CREATE PROCEDURE cm002_fk(IN p_tablo VARCHAR(64), IN p_ad VARCHAR(64), IN p_sutun VARCHAR(64),
                          IN p_hedef VARCHAR(64), IN p_hedef_sutun VARCHAR(64), IN p_kural VARCHAR(20))
BEGIN
  DECLARE v_dusur TEXT DEFAULT NULL;
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE()
       AND LOWER(TABLE_NAME) = LOWER(p_tablo)
       AND CONSTRAINT_NAME = p_ad
       AND LOWER(REFERENCED_TABLE_NAME) = LOWER(p_hedef)
       AND DELETE_RULE = p_kural
  ) THEN
    SELECT GROUP_CONCAT(DISTINCT CONCAT('DROP FOREIGN KEY `', CONSTRAINT_NAME, '`'))
      INTO v_dusur
      FROM information_schema.KEY_COLUMN_USAGE
     WHERE TABLE_SCHEMA = DATABASE()
       AND LOWER(TABLE_NAME) = LOWER(p_tablo)
       AND COLUMN_NAME = p_sutun
       AND REFERENCED_TABLE_NAME IS NOT NULL;
    IF v_dusur IS NOT NULL THEN
      CALL cm002_sql(CONCAT('ALTER TABLE `', p_tablo, '` ', v_dusur));
    END IF;
    CALL cm002_sql(CONCAT('ALTER TABLE `', p_tablo, '` ADD CONSTRAINT `', p_ad,
                          '` FOREIGN KEY (`', p_sutun, '`) REFERENCES `', p_hedef,
                          '` (`', p_hedef_sutun, '`) ON DELETE ', p_kural));
  END IF;
END$$

-- ---------------------------------------------------------------------
-- 1. KONTROL: hiçbir şey değiştirmeden önce tüm sorunları topla
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS cm002_kontrol$$
CREATE PROCEDURE cm002_kontrol()
BEGIN
  IF NOT EXISTS (SELECT 1 FROM SemaGecmisi WHERE Surum = '001') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '002 DURDU: once 001_baseline.sql uygulanmali.';
  END IF;

  DROP TEMPORARY TABLE IF EXISTS cm002_sorunlar;
  CREATE TEMPORARY TABLE cm002_sorunlar (
    Tablo    VARCHAR(64)  NOT NULL,
    Sorun    VARCHAR(255) NOT NULL,
    Adet     INT          NOT NULL,
    Ornekler TEXT         NULL
  ) DEFAULT CHARSET=utf8mb4;

  -- Tekrarlar ---------------------------------------------------------
  INSERT INTO cm002_sorunlar
  SELECT 'TakipKayitlari', 'Tekrar: ayni (KullaniciAliskanlikID, Tarih) icin birden cok satir (grup sayisi)',
         COUNT(*), GROUP_CONCAT(o SEPARATOR ' | ')
    FROM (SELECT CONCAT('KA=', KullaniciAliskanlikID, ' Tarih=', Tarih, ' KayitID:', GROUP_CONCAT(KayitID ORDER BY KayitID)) o
            FROM TakipKayitlari GROUP BY KullaniciAliskanlikID, Tarih HAVING COUNT(*) > 1) d
  HAVING COUNT(*) > 0;

  INSERT INTO cm002_sorunlar
  SELECT 'KullaniciEnvanteri', 'Tekrar: ayni (KullaniciID, EsyaID) icin birden cok satir (grup sayisi)',
         COUNT(*), GROUP_CONCAT(o SEPARATOR ' | ')
    FROM (SELECT CONCAT('Kullanici=', KullaniciID, ' Esya=', EsyaID, ' EnvanterID:', GROUP_CONCAT(EnvanterID ORDER BY EnvanterID)) o
            FROM KullaniciEnvanteri GROUP BY KullaniciID, EsyaID HAVING COUNT(*) > 1) d
  HAVING COUNT(*) > 0;

  INSERT INTO cm002_sorunlar
  SELECT 'KullaniciStatlari', 'Tekrar: ayni (KullaniciID, StatTuru) icin birden cok satir (grup sayisi)',
         COUNT(*), GROUP_CONCAT(o SEPARATOR ' | ')
    FROM (SELECT CONCAT('Kullanici=', KullaniciID, ' Stat=', MIN(StatTuru), ' StatID:', GROUP_CONCAT(StatID ORDER BY StatID)) o
            FROM KullaniciStatlari GROUP BY KullaniciID, StatTuru HAVING COUNT(*) > 1) d
  HAVING COUNT(*) > 0;

  INSERT INTO cm002_sorunlar
  SELECT 'KullaniciAliskanliklari', 'Tekrar: ayni (KullaniciID, AliskanlikID) icin birden cok satir (grup sayisi)',
         COUNT(*), GROUP_CONCAT(o SEPARATOR ' | ')
    FROM (SELECT CONCAT('Kullanici=', KullaniciID, ' Aliskanlik=', AliskanlikID, ' KA_ID:', GROUP_CONCAT(KullaniciAliskanlikID ORDER BY KullaniciAliskanlikID)) o
            FROM KullaniciAliskanliklari GROUP BY KullaniciID, AliskanlikID HAVING COUNT(*) > 1) d
  HAVING COUNT(*) > 0;

  INSERT INTO cm002_sorunlar
  SELECT 'KullaniciGunlukGorevleri', 'Tekrar: ayni (KullaniciID, Tarih, GorevTanimID) icin birden cok satir (grup sayisi)',
         COUNT(*), GROUP_CONCAT(o SEPARATOR ' | ')
    FROM (SELECT CONCAT('Kullanici=', KullaniciID, ' Tarih=', Tarih, ' Gorev=', GorevTanimID, ' ID:', GROUP_CONCAT(KullaniciGorevID ORDER BY KullaniciGorevID)) o
            FROM KullaniciGunlukGorevleri GROUP BY KullaniciID, Tarih, GorevTanimID HAVING COUNT(*) > 1) d
  HAVING COUNT(*) > 0;

  INSERT INTO cm002_sorunlar
  SELECT 'Arkadasliklar', 'Tekrar: ayni iki kullanici arasinda (yonden bagimsiz) birden cok satir (grup sayisi)',
         COUNT(*), GROUP_CONCAT(o SEPARATOR ' | ')
    FROM (SELECT CONCAT(kucuk, '<->', buyuk, ' ArkadaslikID:', GROUP_CONCAT(ArkadaslikID ORDER BY ArkadaslikID)) o
            FROM (SELECT LEAST(IstekGonderenID, IstekAlanID) AS kucuk,
                         GREATEST(IstekGonderenID, IstekAlanID) AS buyuk, ArkadaslikID
                    FROM Arkadasliklar) c
           GROUP BY kucuk, buyuk
          HAVING COUNT(*) > 1) d
  HAVING COUNT(*) > 0;

  -- Geçersiz değerler -------------------------------------------------
  INSERT INTO cm002_sorunlar
  SELECT 'KullaniciStatlari', 'Gecersiz StatTuru (gecerli: Zeka, Guc, Sosyallik, Disiplin, Yaraticilik; buyuk/kucuk harf dahil birebir)',
         COUNT(*), GROUP_CONCAT(DISTINCT CONCAT('''', StatTuru, '''') SEPARATOR ', ')
    FROM KullaniciStatlari
   WHERE NOT ((StatTuru COLLATE utf8mb4_bin) IN ('Zeka','Guc','Sosyallik','Disiplin','Yaraticilik'))
  HAVING COUNT(*) > 0;

  INSERT INTO cm002_sorunlar
  SELECT 'KullaniciStatlari', 'ToplamPuan NULL (NOT NULL yapilacak)', COUNT(*),
         GROUP_CONCAT(StatID ORDER BY StatID)
    FROM KullaniciStatlari WHERE ToplamPuan IS NULL
  HAVING COUNT(*) > 0;

  INSERT INTO cm002_sorunlar
  SELECT 'KullaniciAliskanliklari', 'SonIslemTarihi saat bilgisi iceriyor (DATE yapilacak; saat kaybolur)', COUNT(*),
         GROUP_CONCAT(KullaniciAliskanlikID ORDER BY KullaniciAliskanlikID)
    FROM KullaniciAliskanliklari
   WHERE SonIslemTarihi IS NOT NULL AND TIME(SonIslemTarihi) <> '00:00:00'
  HAVING COUNT(*) > 0;

  -- Sahipsiz satırlar (FK eklenemez) ---------------------------------
  INSERT INTO cm002_sorunlar
  SELECT 'TakipKayitlari', 'Sahipsiz: KullaniciAliskanlikID karsiligi yok (silinmis aliskanligin kayitlari)', COUNT(*),
         GROUP_CONCAT(t.KayitID ORDER BY t.KayitID)
    FROM TakipKayitlari t
    LEFT JOIN KullaniciAliskanliklari ka ON ka.KullaniciAliskanlikID = t.KullaniciAliskanlikID
   WHERE ka.KullaniciAliskanlikID IS NULL
  HAVING COUNT(*) > 0;

  INSERT INTO cm002_sorunlar
  SELECT 'KullaniciAliskanliklari', 'Sahipsiz: KullaniciID karsiligi yok', COUNT(*),
         GROUP_CONCAT(x.KullaniciAliskanlikID ORDER BY x.KullaniciAliskanlikID)
    FROM KullaniciAliskanliklari x LEFT JOIN Kullanicilar k ON k.KullaniciID = x.KullaniciID
   WHERE k.KullaniciID IS NULL
  HAVING COUNT(*) > 0;

  INSERT INTO cm002_sorunlar
  SELECT 'KullaniciAliskanliklari', 'Sahipsiz: AliskanlikID karsiligi yok', COUNT(*),
         GROUP_CONCAT(x.KullaniciAliskanlikID ORDER BY x.KullaniciAliskanlikID)
    FROM KullaniciAliskanliklari x LEFT JOIN Aliskanliklar a ON a.AliskanlikID = x.AliskanlikID
   WHERE a.AliskanlikID IS NULL
  HAVING COUNT(*) > 0;

  INSERT INTO cm002_sorunlar
  SELECT 'KullaniciStatlari', 'Sahipsiz: KullaniciID karsiligi yok', COUNT(*),
         GROUP_CONCAT(x.StatID ORDER BY x.StatID)
    FROM KullaniciStatlari x LEFT JOIN Kullanicilar k ON k.KullaniciID = x.KullaniciID
   WHERE k.KullaniciID IS NULL
  HAVING COUNT(*) > 0;

  INSERT INTO cm002_sorunlar
  SELECT 'KullaniciEnvanteri', 'Sahipsiz: KullaniciID karsiligi yok', COUNT(*),
         GROUP_CONCAT(x.EnvanterID ORDER BY x.EnvanterID)
    FROM KullaniciEnvanteri x LEFT JOIN Kullanicilar k ON k.KullaniciID = x.KullaniciID
   WHERE k.KullaniciID IS NULL
  HAVING COUNT(*) > 0;

  INSERT INTO cm002_sorunlar
  SELECT 'KullaniciEnvanteri', 'Sahipsiz: EsyaID karsiligi yok', COUNT(*),
         GROUP_CONCAT(x.EnvanterID ORDER BY x.EnvanterID)
    FROM KullaniciEnvanteri x LEFT JOIN MarketEsyalari m ON m.EsyaID = x.EsyaID
   WHERE m.EsyaID IS NULL
  HAVING COUNT(*) > 0;

  INSERT INTO cm002_sorunlar
  SELECT 'KullaniciGunlukGorevleri', 'Sahipsiz: KullaniciID karsiligi yok', COUNT(*),
         GROUP_CONCAT(x.KullaniciGorevID ORDER BY x.KullaniciGorevID)
    FROM KullaniciGunlukGorevleri x LEFT JOIN Kullanicilar k ON k.KullaniciID = x.KullaniciID
   WHERE k.KullaniciID IS NULL
  HAVING COUNT(*) > 0;

  INSERT INTO cm002_sorunlar
  SELECT 'KullaniciGunlukGorevleri', 'Sahipsiz: GorevTanimID karsiligi yok', COUNT(*),
         GROUP_CONCAT(x.KullaniciGorevID ORDER BY x.KullaniciGorevID)
    FROM KullaniciGunlukGorevleri x LEFT JOIN GorevTanimlari g ON g.GorevTanimID = x.GorevTanimID
   WHERE g.GorevTanimID IS NULL
  HAVING COUNT(*) > 0;

  INSERT INTO cm002_sorunlar
  SELECT 'Arkadasliklar', 'Sahipsiz: IstekGonderenID veya IstekAlanID karsiligi yok', COUNT(*),
         GROUP_CONCAT(x.ArkadaslikID ORDER BY x.ArkadaslikID)
    FROM Arkadasliklar x
    LEFT JOIN Kullanicilar g ON g.KullaniciID = x.IstekGonderenID
    LEFT JOIN Kullanicilar a ON a.KullaniciID = x.IstekAlanID
   WHERE g.KullaniciID IS NULL OR a.KullaniciID IS NULL
  HAVING COUNT(*) > 0;

  IF EXISTS (SELECT 1 FROM cm002_sorunlar) THEN
    SELECT Tablo, Sorun, Adet, Ornekler FROM cm002_sorunlar ORDER BY Tablo, Sorun;
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = '002 DURDU: yukaridaki veri sorunlari var; hicbir sey degistirilmedi. Bkz. database/araclar/tekrar_temizle.sql';
  END IF;
  DROP TEMPORARY TABLE IF EXISTS cm002_sorunlar;
END$$

-- ---------------------------------------------------------------------
-- 2. UYGULA: yalnızca kontrol temiz geçtiyse çağrılır
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS cm002_uygula$$
CREATE PROCEDURE cm002_uygula()
BEGIN
  -- Kullanicilar: giriş (WHERE Ad = ?) ve arama (Ad LIKE) için
  CALL cm002_indeks('Kullanicilar', 'IDX_Kullanici_Ad', 'KEY `IDX_Kullanici_Ad` (`Ad`)');

  -- KullaniciStatlari
  ALTER TABLE KullaniciStatlari MODIFY ToplamPuan INT NOT NULL DEFAULT 0;
  CALL cm002_indeks('KullaniciStatlari', 'UQ_Stat_User', 'UNIQUE KEY `UQ_Stat_User` (`KullaniciID`,`StatTuru`)');
  CALL cm002_check('KullaniciStatlari', 'CHK_KS_StatTuru',
       '(`StatTuru` collate utf8mb4_bin) in (''Zeka'',''Guc'',''Sosyallik'',''Disiplin'',''Yaraticilik'')');
  CALL cm002_fk('KullaniciStatlari', 'FK_Stat_Kullanici', 'KullaniciID', 'Kullanicilar', 'KullaniciID', 'CASCADE');

  -- KullaniciAliskanliklari
  IF EXISTS (SELECT 1 FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = 'kullanicialiskanliklari'
                AND COLUMN_NAME = 'SonIslemTarihi' AND DATA_TYPE <> 'date') THEN
    ALTER TABLE KullaniciAliskanliklari MODIFY SonIslemTarihi DATE DEFAULT NULL;
  END IF;
  CALL cm002_indeks('KullaniciAliskanliklari', 'UQ_KA_Kullanici_Aliskanlik',
       'UNIQUE KEY `UQ_KA_Kullanici_Aliskanlik` (`KullaniciID`,`AliskanlikID`)');
  CALL cm002_fk('KullaniciAliskanliklari', 'FK_KA_Kullanici', 'KullaniciID', 'Kullanicilar', 'KullaniciID', 'CASCADE');
  CALL cm002_fk('KullaniciAliskanliklari', 'FK_KA_Aliskanlik', 'AliskanlikID', 'Aliskanliklar', 'AliskanlikID', 'RESTRICT');

  -- TakipKayitlari (CASCADE: ajax_islem.php alışkanlığı doğrudan siler)
  CALL cm002_indeks('TakipKayitlari', 'UQ_TK_KA_Tarih',
       'UNIQUE KEY `UQ_TK_KA_Tarih` (`KullaniciAliskanlikID`,`Tarih`)');
  CALL cm002_fk('TakipKayitlari', 'FK_TK_KA', 'KullaniciAliskanlikID', 'KullaniciAliskanliklari', 'KullaniciAliskanlikID', 'CASCADE');

  -- KullaniciEnvanteri
  CALL cm002_indeks('KullaniciEnvanteri', 'UQ_Env_Kullanici_Esya',
       'UNIQUE KEY `UQ_Env_Kullanici_Esya` (`KullaniciID`,`EsyaID`)');
  CALL cm002_fk('KullaniciEnvanteri', 'FK_Env_Kullanici', 'KullaniciID', 'Kullanicilar', 'KullaniciID', 'CASCADE');
  CALL cm002_fk('KullaniciEnvanteri', 'FK_Env_Esya', 'EsyaID', 'MarketEsyalari', 'EsyaID', 'RESTRICT');

  -- KullaniciGunlukGorevleri (sorgular KullaniciID + Tarih ile süzer)
  CALL cm002_indeks('KullaniciGunlukGorevleri', 'UQ_KGG_Kullanici_Tarih_Gorev',
       'UNIQUE KEY `UQ_KGG_Kullanici_Tarih_Gorev` (`KullaniciID`,`Tarih`,`GorevTanimID`)');
  CALL cm002_fk('KullaniciGunlukGorevleri', 'FK_KGG_Kullanici', 'KullaniciID', 'Kullanicilar', 'KullaniciID', 'CASCADE');
  CALL cm002_fk('KullaniciGunlukGorevleri', 'FK_KGG_Tanim', 'GorevTanimID', 'GorevTanimlari', 'GorevTanimID', 'RESTRICT');

  -- Arkadasliklar: bekleyen istekler (IstekAlanID, Durum) ile aranır;
  -- A->B ve B->A aynı çift sayılır (fonksiyonel UNIQUE indeks).
  CALL cm002_indeks('Arkadasliklar', 'IDX_Ark_Alan_Durum', 'KEY `IDX_Ark_Alan_Durum` (`IstekAlanID`,`Durum`)');
  CALL cm002_indeks('Arkadasliklar', 'UQ_Ark_Cift',
       'UNIQUE KEY `UQ_Ark_Cift` ((least(`IstekGonderenID`,`IstekAlanID`)),(greatest(`IstekGonderenID`,`IstekAlanID`)))');
  CALL cm002_fk('Arkadasliklar', 'FK_Ark_Gonderen', 'IstekGonderenID', 'Kullanicilar', 'KullaniciID', 'CASCADE');
  CALL cm002_fk('Arkadasliklar', 'FK_Ark_Alan', 'IstekAlanID', 'Kullanicilar', 'KullaniciID', 'CASCADE');

  INSERT IGNORE INTO SemaGecmisi (Surum, Aciklama)
  VALUES ('002', 'Eksik UNIQUE/FK/CHECK kisitlari ve indeksler');
END$$

DELIMITER ;

CALL cm002_kontrol();   -- sorun varsa burada durur (mysql istemcisi devam etmez)
CALL cm002_uygula();

DROP PROCEDURE IF EXISTS cm002_kontrol;
DROP PROCEDURE IF EXISTS cm002_uygula;
DROP PROCEDURE IF EXISTS cm002_fk;
DROP PROCEDURE IF EXISTS cm002_check;
DROP PROCEDURE IF EXISTS cm002_indeks;
DROP PROCEDURE IF EXISTS cm002_sql;
