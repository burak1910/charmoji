-- =====================================================================
-- 003_kullaniciya_ozel_aliskanliklar.sql
-- ---------------------------------------------------------------------
-- Karar: Alışkanlıklar kullanıcıya özeldir.
--
-- Bugün: Aliskanliklar.Ad global ve UNIQUE; bir alışkanlığı ilk ekleyen
-- kişi onun StatTuru ve Puan'ını herkes için belirliyor.
-- Hedef: Her KullaniciAliskanliklari satırı kendi Ad, StatTuru ve
-- Puan değerini taşır.
--
-- Adımlar:
--   0. Denetimler (salt okunur) + kapı. Sorun varsa HİÇBİR ŞEY değişmez.
--   1. cm003_hazirla : kapıyı yeniden denetler; Ad/StatTuru/Puan
--      sütunlarını NULL olabilir şekilde ekler ve Aliskanliklar'dan
--      KOPYALAR (silmez).
--   2. TRG_KA_EskiKodUyumu: eski PHP kodunun
--        INSERT IGNORE INTO KullaniciAliskanliklari (KullaniciID, AliskanlikID, BaslangicTarihi)
--      sorgusunda Ad/StatTuru/Puan'ı ortak tablodan doldurur. NOT NULL /
--      UNIQUE adımından ÖNCE kurulur; böylece sonraki adım başarısız olsa
--      bile eski kod satır eklemeye devam eder.
--   3. cm003_sikilastir: kalan boşlukları doldurur; NOT NULL, CHECK
--      (beş geçerli StatTuru) ve UNIQUE(KullaniciID, Ad) değişikliklerini
--      TEK bir ALTER TABLE ile yapar (MySQL 8'de atomik: ya hepsi ya hiçbiri).
--
-- Hangi adımda durursa dursun uygulama çalışır kalır ve dosya yeniden
-- çalıştırılabilir. "Aynı kullanıcıda aynı ad" denetimi, hedef sütunun
-- (KullaniciAliskanliklari.Ad) karşılaştırma kuralıyla yapılır; eski
-- tablonun kuralı farklı olsa bile (ör. utf8mb4_bin) UNIQUE ile aynı sonucu verir.
--
-- Eski ortak tablo (Aliskanliklar), AliskanlikID ve tetikleyici BU
-- DOSYADA SİLİNMEZ. Onları kaldıran adım ayrı ve geri alınamaz:
-- database/araclar/004_ortak_aliskanliklari_kaldir.sql
-- Ön koşul: 002 uygulanmış olmalı.
-- =====================================================================

SET NAMES utf8mb4;

DROP TEMPORARY TABLE IF EXISTS cm003_sorunlar, cm003_kapi004, cm003_kapi;

-- 004 uygulanmışsa eski tablo yok; bu dosya bir daha çalıştırılmaz.
CREATE TEMPORARY TABLE cm003_kapi004 (
  Durum VARCHAR(10) NOT NULL,
  CONSTRAINT `003_DURDU_004_zaten_uygulanmis_bu_dosya_calistirilmaz` CHECK (Durum = 'devam')
);
INSERT INTO cm003_kapi004
SELECT IF(COUNT(*) = 0, 'devam', 'dur') FROM SemaGecmisi WHERE Surum = '004';

-- ---------------------------------------------------------------------
-- 0. DENETİMLER (her biri tam bir satır yazar)
-- ---------------------------------------------------------------------
CREATE TEMPORARY TABLE cm003_sorunlar (
  Kod CHAR(3) NOT NULL PRIMARY KEY, Sorun VARCHAR(255) NOT NULL, Adet INT NOT NULL, Ornekler TEXT NULL
) DEFAULT CHARSET=utf8mb4;

INSERT INTO cm003_sorunlar
SELECT 'K00', '002_kisitlar.sql uygulanmamis', COUNT(*) = 0, NULL FROM SemaGecmisi WHERE Surum = '002';

INSERT INTO cm003_sorunlar
SELECT 'K01', 'Aliskanliklar.StatTuru NULL ya da gecersiz (gecerli: Zeka, Guc, Sosyallik, Disiplin, Yaraticilik; birebir)',
       COUNT(*), GROUP_CONCAT(DISTINCT CONCAT('AliskanlikID=', a.AliskanlikID, ' ''', IFNULL(a.StatTuru, 'NULL'), '''') SEPARATOR ', ')
  FROM KullaniciAliskanliklari ka
  JOIN Aliskanliklar a ON a.AliskanlikID = ka.AliskanlikID
 WHERE a.StatTuru IS NULL
    OR NOT ((CONVERT(a.StatTuru USING utf8mb4) COLLATE utf8mb4_0900_bin) IN ('Zeka','Guc','Sosyallik','Disiplin','Yaraticilik'));

INSERT INTO cm003_sorunlar
SELECT 'K02', 'Aliskanliklar.Puan NULL', COUNT(*), GROUP_CONCAT(DISTINCT a.AliskanlikID ORDER BY a.AliskanlikID)
  FROM KullaniciAliskanliklari ka
  JOIN Aliskanliklar a ON a.AliskanlikID = ka.AliskanlikID
 WHERE a.Puan IS NULL;

INSERT INTO cm003_sorunlar
SELECT 'K04', 'Aliskanliklar.Ad 100 karakterden uzun (KullaniciAliskanliklari.Ad VARCHAR(100))', COUNT(*),
       GROUP_CONCAT(DISTINCT a.AliskanlikID ORDER BY a.AliskanlikID)
  FROM KullaniciAliskanliklari ka
  JOIN Aliskanliklar a ON a.AliskanlikID = ka.AliskanlikID
 WHERE CHAR_LENGTH(a.Ad) > 100;

-- K03: aynı kullanıcıda, HEDEF sütunun kuralına göre aynı sayılan adlar.
-- Hedef kural: KullaniciAliskanliklari.Ad varsa onunki, yoksa tablonun
-- varsayılanı (ADD COLUMN bu kuralı alır).
SET @cm003_kural = COALESCE(
  (SELECT COLLATION_NAME FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = 'kullanicialiskanliklari' AND COLUMN_NAME = 'Ad'),
  (SELECT TABLE_COLLATION FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = 'kullanicialiskanliklari'));
SET @cm003_karakter = (SELECT CHARACTER_SET_NAME FROM information_schema.COLLATIONS WHERE COLLATION_NAME = @cm003_kural);
-- Ad sütunu önceki (yarım kalmış) bir çalıştırmadan varsa onun değeri, yoksa
-- Aliskanliklar.Ad esas alınır; AliskanlikID'si boş satırlar da dahildir.
SET @cm003_ad = IF(EXISTS (SELECT 1 FROM information_schema.COLUMNS
                            WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = 'kullanicialiskanliklari'
                              AND COLUMN_NAME = 'Ad'),
                   'COALESCE(ka.Ad, a.Ad)', 'a.Ad');
SET @cm003_sql = CONCAT(
  'INSERT INTO cm003_sorunlar ',
  'SELECT ''K03'', CONCAT(''Ayni kullanicida ayni sayilan adlar (kural: ', @cm003_kural, '); UNIQUE(KullaniciID, Ad) eklenemez''), ',
  '       COUNT(*), GROUP_CONCAT(o SEPARATOR '' | '') ',
  '  FROM (SELECT CONCAT(''Kullanici='', ka.KullaniciID, '' Adlar: '', GROUP_CONCAT(CONCAT('''''''', ', @cm003_ad, ', '''''''') SEPARATOR '', '')) o ',
  '          FROM KullaniciAliskanliklari ka LEFT JOIN Aliskanliklar a ON a.AliskanlikID = ka.AliskanlikID ',
  '         WHERE ', @cm003_ad, ' IS NOT NULL ',
  '         GROUP BY ka.KullaniciID, (CONVERT(', @cm003_ad, ' USING ', @cm003_karakter, ') COLLATE ', @cm003_kural, ') ',
  '        HAVING COUNT(*) > 1) d');
PREPARE cm003_st FROM @cm003_sql;
EXECUTE cm003_st;
DEALLOCATE PREPARE cm003_st;

SELECT Sorun, Adet, Ornekler FROM cm003_sorunlar WHERE Adet > 0 ORDER BY Kod;

CREATE TEMPORARY TABLE cm003_kapi (
  Durum VARCHAR(10) NOT NULL,
  CONSTRAINT `003_DURDU_veri_sorunu_var_hicbir_sey_degistirilmedi` CHECK (Durum = 'temiz')
);
INSERT INTO cm003_kapi
SELECT IF(COUNT(*) = 5 AND SUM(Adet) = 0, 'temiz', 'sorunlu') FROM cm003_sorunlar;

-- ---------------------------------------------------------------------
-- 1-3. UYGULAMA
-- ---------------------------------------------------------------------
DELIMITER $$

DROP PROCEDURE IF EXISTS cm003_hazirla$$
CREATE PROCEDURE cm003_hazirla()
BEGIN
  DECLARE v_denetim INT DEFAULT 0;
  DECLARE v_sorun INT DEFAULT 1;
  SET @cm003_hazir = NULL;
  -- KAPI (istemci hatada devam etse bile)
  SELECT COUNT(*), COALESCE(SUM(Adet), 1) INTO v_denetim, v_sorun FROM cm003_sorunlar;
  IF (SELECT COUNT(*) FROM SemaGecmisi WHERE Surum = '004') > 0
     OR v_denetim <> 5 OR v_sorun <> 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = '003 DURDU: denetimler gecmedi; hicbir sey degistirilmedi.';
  END IF;

  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = 'kullanicialiskanliklari'
                    AND COLUMN_NAME = 'Ad') THEN
    ALTER TABLE KullaniciAliskanliklari
      ADD COLUMN Ad       VARCHAR(100) NULL AFTER AliskanlikID,
      ADD COLUMN StatTuru VARCHAR(20)  NULL AFTER Ad,
      ADD COLUMN Puan     INT          NULL AFTER StatTuru;
  END IF;

  UPDATE KullaniciAliskanliklari ka
    JOIN Aliskanliklar a ON a.AliskanlikID = ka.AliskanlikID
     SET ka.Ad       = COALESCE(ka.Ad, a.Ad),
         ka.StatTuru = COALESCE(ka.StatTuru, a.StatTuru),
         ka.Puan     = COALESCE(ka.Puan, a.Puan)
   WHERE ka.Ad IS NULL OR ka.StatTuru IS NULL OR ka.Puan IS NULL;

  -- Tetikleyici hemen ardından (yordam dışında) yeniden kurulur; CREATE
  -- TRIGGER saklı yordam içinde kullanılamaz.
  DROP TRIGGER IF EXISTS TRG_KA_EskiKodUyumu;
  SET @cm003_hazir = 'evet';
END$$

DROP PROCEDURE IF EXISTS cm003_sikilastir$$
CREATE PROCEDURE cm003_sikilastir()
BEGIN
  DECLARE v_degisiklik TEXT DEFAULT '';
  DECLARE v_check TEXT DEFAULT NULL;

  IF @cm003_hazir IS NULL OR @cm003_hazir <> 'evet' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '003 DURDU: hazirlik adimi tamamlanmadi; sikilastirma yapilmadi.';
  END IF;
  SET @cm003_hazir = NULL;
  IF NOT EXISTS (SELECT 1 FROM information_schema.TRIGGERS
                  WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = 'TRG_KA_EskiKodUyumu') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '003 DURDU: TRG_KA_EskiKodUyumu kurulamadi; sikilastirma yapilmadi.';
  END IF;

  -- Tetikleyici kurulmadan önceki anda eklenmiş satırlar için tekrar doldur
  UPDATE KullaniciAliskanliklari ka
    JOIN Aliskanliklar a ON a.AliskanlikID = ka.AliskanlikID
     SET ka.Ad       = COALESCE(ka.Ad, a.Ad),
         ka.StatTuru = COALESCE(ka.StatTuru, a.StatTuru),
         ka.Puan     = COALESCE(ka.Puan, a.Puan)
   WHERE ka.Ad IS NULL OR ka.StatTuru IS NULL OR ka.Puan IS NULL;

  IF EXISTS (SELECT 1 FROM KullaniciAliskanliklari WHERE Ad IS NULL OR StatTuru IS NULL OR Puan IS NULL) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = '003 DURDU: bazi satirlarda Ad/StatTuru/Puan bos; sikilastirma yapilmadi. Uygulama calisir durumda.';
  END IF;

  -- Tek, atomik ALTER için yalnızca eksik değişiklikleri topla
  IF EXISTS (SELECT 1 FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = 'kullanicialiskanliklari'
                AND ((COLUMN_NAME IN ('Ad', 'StatTuru', 'Puan') AND IS_NULLABLE = 'YES')
                  OR (COLUMN_NAME = 'AliskanlikID' AND IS_NULLABLE = 'NO'))) THEN
    SET v_degisiklik = CONCAT(
      'MODIFY AliskanlikID INT NULL COMMENT ''ESKI: ortak Aliskanliklar tablosu; 004 ile kaldirilir, yeni kod doldurmaz'', ',
      'MODIFY Ad VARCHAR(100) NOT NULL COMMENT ''Kullaniciya ozel aliskanlik adi'', ',
      'MODIFY StatTuru VARCHAR(20) NOT NULL COMMENT ''Zeka, Guc, Sosyallik, Disiplin, Yaraticilik'', ',
      'MODIFY Puan INT NOT NULL DEFAULT 10 COMMENT ''Isaretleme basina taban puan''');
  END IF;

  SELECT cc.CHECK_CLAUSE INTO v_check
    FROM information_schema.CHECK_CONSTRAINTS cc
   WHERE cc.CONSTRAINT_SCHEMA = DATABASE() AND cc.CONSTRAINT_NAME = 'CHK_KA_StatTuru' LIMIT 1;
  IF v_check IS NULL OR LOCATE('utf8mb4_0900_bin', v_check) = 0 THEN
    SET v_degisiklik = CONCAT(v_degisiklik, IF(v_degisiklik = '', '', ', '),
      IF(v_check IS NULL, '', 'DROP CHECK CHK_KA_StatTuru, '),
      'ADD CONSTRAINT CHK_KA_StatTuru CHECK ((StatTuru COLLATE utf8mb4_0900_bin) IN (''Zeka'',''Guc'',''Sosyallik'',''Disiplin'',''Yaraticilik''))');
  END IF;

  IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS
                  WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = 'kullanicialiskanliklari'
                    AND INDEX_NAME = 'UQ_KA_Kullanici_Ad') THEN
    SET v_degisiklik = CONCAT(v_degisiklik, IF(v_degisiklik = '', '', ', '),
      'ADD UNIQUE KEY UQ_KA_Kullanici_Ad (KullaniciID, Ad)');
  END IF;

  IF v_degisiklik <> '' THEN
    SET @cm003_sql = CONCAT('ALTER TABLE KullaniciAliskanliklari ', v_degisiklik);
    PREPARE cm003_st FROM @cm003_sql;
    EXECUTE cm003_st;
    DEALLOCATE PREPARE cm003_st;
  END IF;

  INSERT IGNORE INTO SemaGecmisi (Surum, Aciklama)
  VALUES ('003', 'Aliskanliklar kullaniciya ozel: Ad/StatTuru/Puan KullaniciAliskanliklari tablosunda');
END$$

DELIMITER ;

CALL cm003_hazirla();

-- Kapı geçilmediyse cm003_hazirla tetikleyiciyi düşürmemiştir ve (ilk
-- çalıştırmada) Ad/StatTuru/Puan sütunları yoktur: bu CREATE "Unknown column"
-- ya da "zaten var" hatası verir ve hiçbir şey değişmez. Kapı geçildiyse
-- tetikleyici, NOT NULL / UNIQUE adımından ÖNCE kurulmuş olur.
DELIMITER $$
CREATE TRIGGER TRG_KA_EskiKodUyumu
BEFORE INSERT ON KullaniciAliskanliklari
FOR EACH ROW
BEGIN
  -- Eski kod yalnizca AliskanlikID verir; Ad/StatTuru/Puan ortak tablodan doldurulur.
  -- Yeni kod Ad verir ve AliskanlikID bos birakir.
  IF NEW.AliskanlikID IS NOT NULL AND (NEW.Ad IS NULL OR NEW.Ad = '') THEN
    SET NEW.Ad       = (SELECT a.Ad       FROM Aliskanliklar a WHERE a.AliskanlikID = NEW.AliskanlikID);
    SET NEW.StatTuru = (SELECT a.StatTuru FROM Aliskanliklar a WHERE a.AliskanlikID = NEW.AliskanlikID);
    SET NEW.Puan     = (SELECT COALESCE(a.Puan, 10) FROM Aliskanliklar a WHERE a.AliskanlikID = NEW.AliskanlikID);
  END IF;
END$$
DELIMITER ;

CALL cm003_sikilastir();

DROP PROCEDURE IF EXISTS cm003_hazirla;
DROP PROCEDURE IF EXISTS cm003_sikilastir;
DROP TEMPORARY TABLE IF EXISTS cm003_sorunlar, cm003_kapi004, cm003_kapi;
