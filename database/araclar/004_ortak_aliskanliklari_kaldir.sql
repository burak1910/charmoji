-- =====================================================================
-- araclar/004_ortak_aliskanliklari_kaldir.sql
-- ---------------------------------------------------------------------
-- !!! GERİ ALINAMAZ. OTOMATİK ÇALIŞTIRILMAZ. Elle ve onayla çalıştırılır. !!!
--
-- Ne yapar: 003 ile kullanıcıya özel yapıya taşınan alışkanlıklardan
-- sonra eski ortak yapıyı kaldırır:
--   * TRG_KA_EskiKodUyumu tetikleyicisi
--   * FK_KA_Aliskanlik, UQ_KA_Kullanici_Aliskanlik, FK_KA_Aliskanlik indeksi
--   * KullaniciAliskanliklari.AliskanlikID sütunu
--   * Aliskanliklar tablosu (VE İÇİNDEKİ TÜM VERİ; mevcut kurulumda
--     Aciklama/VarsayilanHedef/HedefTipi gibi taşınmamış sütunlar
--     varsa onlar da gider)
--
-- Ön koşullar (betik ilk ikisini kendisi denetler):
--   1. 003 uygulanmış olmalı.
--   2. KullaniciAliskanliklari'nda Ad/StatTuru/Puan boş satır olmamalı.
--   3. PHP kodu Aliskanliklar tablosunu ve AliskanlikID'yi artık
--      KULLANMIYOR olmalı (bkz. README "003 sonrası PHP değişiklikleri").
--      Eski kod bu adımdan sonra alışkanlık ekleyemez/listeleyemez.
--   4. Tam yedek alınmış olmalı:
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

DROP PROCEDURE IF EXISTS cm004_kontrol;
DELIMITER $$
CREATE PROCEDURE cm004_kontrol()
BEGIN
  IF @onay IS NULL OR @onay <> 'EVET-ALISKANLIKLAR-SILINSIN' THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Onay yok. Yedek alip SET @onay=''EVET-ALISKANLIKLAR-SILINSIN''; ile ayni oturumda calistirin.';
  END IF;
  IF EXISTS (SELECT 1 FROM SemaGecmisi WHERE Surum = '004') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '004 zaten uygulanmis.';
  END IF;
  IF NOT EXISTS (SELECT 1 FROM SemaGecmisi WHERE Surum = '003') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '004 DURDU: once 003 uygulanmali.';
  END IF;
  IF EXISTS (SELECT 1 FROM KullaniciAliskanliklari
              WHERE Ad IS NULL OR Ad = '' OR StatTuru IS NULL OR Puan IS NULL) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '004 DURDU: Ad/StatTuru/Puan bos satirlar var; tasima tamamlanmamis.';
  END IF;
END$$
DELIMITER ;
CALL cm004_kontrol();
DROP PROCEDURE IF EXISTS cm004_kontrol;

DROP TRIGGER IF EXISTS TRG_KA_EskiKodUyumu;

ALTER TABLE KullaniciAliskanliklari
  DROP FOREIGN KEY FK_KA_Aliskanlik;

-- UQ (KullaniciID, AliskanlikID) açıkça düşürülür; yoksa sütun silinince
-- UNIQUE(KullaniciID)'ye daralırdı. Yalnızca AliskanlikID'yi içeren FK
-- indeksi sütunla birlikte kendiliğinden kalkar.
ALTER TABLE KullaniciAliskanliklari
  DROP INDEX UQ_KA_Kullanici_Aliskanlik,
  DROP COLUMN AliskanlikID;

DROP TABLE Aliskanliklar;

INSERT INTO SemaGecmisi (Surum, Aciklama)
VALUES ('004', 'Eski ortak Aliskanliklar tablosu ve AliskanlikID kaldirildi (geri alinamaz)');

SET @onay = NULL;
