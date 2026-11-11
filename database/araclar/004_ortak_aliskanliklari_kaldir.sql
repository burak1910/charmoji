-- =====================================================================
-- araclar/004_ortak_aliskanliklari_kaldir.sql
-- ---------------------------------------------------------------------
-- !!! GERİ ALINAMAZ. OTOMATİK ÇALIŞTIRILMAZ. Elle ve onayla çalıştırılır. !!!
--
-- Ne yapar: 003 ile kullanıcıya özel yapıya taşınan alışkanlıklardan
-- sonra eski ortak yapıyı kaldırır:
--   * TRG_KA_EskiKodUyumu tetikleyicisi
--   * KullaniciAliskanliklari.AliskanlikID üzerindeki TÜM FK'ler ve
--     AliskanlikID'yi içeren TÜM indeksler (adı ne olursa olsun; ör. eski
--     kurulumdaki UQ_KA). Yoksa sütun silinince UNIQUE(KullaniciID, AliskanlikID)
--     UNIQUE(KullaniciID)'ye daralır ve her kullanıcı tek alışkanlık tutabilirdi.
--   * KullaniciAliskanliklari.AliskanlikID sütunu
--   * Aliskanliklar tablosu (VE İÇİNDEKİ TÜM VERİ; taşınmamış sütunlar
--     varsa — Aciklama/VarsayilanHedef/HedefTipi — onlar da gider)
--
-- GÜVENLİK: Onay ve ön koşul denetimi, silme işlemlerini yapan yordamın
-- İÇİNDEDİR. İstemci hatada devam etse bile (mysql --force, etkileşimli
-- SOURCE) onay yoksa hiçbir şey değişmez.
--
-- Ön koşullar:
--   1. 003 uygulanmış olmalı; KullaniciAliskanliklari'nda Ad/StatTuru/Puan
--      boş satır olmamalı; UQ_KA_Kullanici_Ad bulunmalı (betik denetler).
--   2. PHP kodu Aliskanliklar tablosunu ve AliskanlikID'yi artık
--      KULLANMIYOR olmalı (bkz. README "003 sonrası PHP değişiklikleri").
--   3. Tam yedek alınmış olmalı:
--        mysqldump -uroot --default-character-set=utf8mb4 --routines --triggers charmoji > yedek_004_oncesi.sql
--
-- Çalıştırma (onay değişkeni aynı oturumda verilmeli):
--   mysql -uroot --default-character-set=utf8mb4 charmoji \
--     -e "SET @onay='EVET-ALISKANLIKLAR-SILINSIN'; SOURCE database/araclar/004_ortak_aliskanliklari_kaldir.sql;"
--
-- Sonrasında: database/schema.sql ve seed.sql 004 sonrası duruma
-- güncellenmelidir (ikisi de şu an Aliskanliklar tablosunu içerir).
-- =====================================================================

SET NAMES utf8mb4;

-- Erken kapı: onay yoksa hiçbir nesne oluşturulmadan durulur (batch).
DROP TEMPORARY TABLE IF EXISTS cm004_kapi;
CREATE TEMPORARY TABLE cm004_kapi (
  Durum VARCHAR(10) NOT NULL,
  CONSTRAINT `004_DURDU_onay_yok_SET_onay_EVET_ALISKANLIKLAR_SILINSIN` CHECK (Durum = 'onayli')
);
INSERT INTO cm004_kapi
SELECT IF(@onay IS NOT NULL AND @onay = 'EVET-ALISKANLIKLAR-SILINSIN', 'onayli', 'onaysiz');

DROP PROCEDURE IF EXISTS cm004_uygula;
DELIMITER $$
CREATE PROCEDURE cm004_uygula()
BEGIN
  DECLARE v_fk TEXT DEFAULT NULL;
  DECLARE v_indeks TEXT DEFAULT NULL;

  -- KAPI: onay ve ön koşullar (değişiklik yapan kodla AYNI yordamda)
  IF @onay IS NULL OR @onay <> 'EVET-ALISKANLIKLAR-SILINSIN' THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = '004 DURDU: onay yok; hicbir sey degistirilmedi. SET @onay=''EVET-ALISKANLIKLAR-SILINSIN''';
  END IF;
  SET @onay = NULL;   -- onay tek kullanımlık
  IF EXISTS (SELECT 1 FROM SemaGecmisi WHERE Surum = '004') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '004 zaten uygulanmis; hicbir sey degistirilmedi.';
  END IF;
  IF NOT EXISTS (SELECT 1 FROM SemaGecmisi WHERE Surum = '003') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '004 DURDU: once 003 uygulanmali; hicbir sey degistirilmedi.';
  END IF;
  IF EXISTS (SELECT 1 FROM KullaniciAliskanliklari
              WHERE Ad IS NULL OR Ad = '' OR StatTuru IS NULL OR Puan IS NULL) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '004 DURDU: Ad/StatTuru/Puan bos satirlar var; hicbir sey degistirilmedi.';
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS
                  WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = 'kullanicialiskanliklari'
                    AND INDEX_NAME = 'UQ_KA_Kullanici_Ad') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '004 DURDU: UQ_KA_Kullanici_Ad yok (003 tamamlanmamis); hicbir sey degistirilmedi.';
  END IF;

  DROP TRIGGER IF EXISTS TRG_KA_EskiKodUyumu;

  -- AliskanlikID üzerindeki tüm FK'ler (ad ne olursa olsun)
  SELECT GROUP_CONCAT(DISTINCT CONCAT('DROP FOREIGN KEY `', CONSTRAINT_NAME, '`'))
    INTO v_fk
    FROM information_schema.KEY_COLUMN_USAGE
   WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = 'kullanicialiskanliklari'
     AND COLUMN_NAME = 'AliskanlikID' AND REFERENCED_TABLE_NAME IS NOT NULL;
  IF v_fk IS NOT NULL THEN
    SET @cm004_sql = CONCAT('ALTER TABLE KullaniciAliskanliklari ', v_fk);
    PREPARE cm004_st FROM @cm004_sql; EXECUTE cm004_st; DEALLOCATE PREPARE cm004_st;
  END IF;

  -- AliskanlikID'yi içeren tüm indeksler + sütun, tek ALTER ile
  SELECT GROUP_CONCAT(DISTINCT CONCAT('DROP INDEX `', INDEX_NAME, '`'))
    INTO v_indeks
    FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = 'kullanicialiskanliklari'
     AND COLUMN_NAME = 'AliskanlikID' AND INDEX_NAME <> 'PRIMARY';
  SET @cm004_sql = CONCAT('ALTER TABLE KullaniciAliskanliklari ',
                          IF(v_indeks IS NULL, '', CONCAT(v_indeks, ', ')),
                          'DROP COLUMN AliskanlikID');
  PREPARE cm004_st FROM @cm004_sql; EXECUTE cm004_st; DEALLOCATE PREPARE cm004_st;

  DROP TABLE Aliskanliklar;

  INSERT INTO SemaGecmisi (Surum, Aciklama)
  VALUES ('004', 'Eski ortak Aliskanliklar tablosu ve AliskanlikID kaldirildi (geri alinamaz)');
END$$
DELIMITER ;

CALL cm004_uygula();

DROP PROCEDURE IF EXISTS cm004_uygula;
DROP TEMPORARY TABLE IF EXISTS cm004_kapi;
SET @onay = NULL;
