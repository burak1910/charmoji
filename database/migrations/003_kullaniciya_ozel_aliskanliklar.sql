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
-- Bu migration:
--   1. KullaniciAliskanliklari'na Ad, StatTuru, Puan sütunlarını ekler.
--   2. Mevcut veriyi Aliskanliklar'dan bu sütunlara KOPYALAR (silmez).
--   3. Sütunları NOT NULL yapar; StatTuru'ya CHECK (beş geçerli değer),
--      (KullaniciID, Ad) çiftine UNIQUE ekler (bir kullanıcının aynı
--      adlı iki alışkanlığı olmaz; farklı kullanıcılar aynı adı farklı
--      stat ile kullanabilir).
--   4. AliskanlikID'yi NULL olabilir yapar (yeni kod onu doldurmayacak).
--   5. GEÇİŞ UYUMLULUĞU: Eski PHP kodu hâlâ
--        INSERT IGNORE INTO KullaniciAliskanliklari (KullaniciID, AliskanlikID, BaslangicTarihi)
--      yapıyor. TRG_KA_EskiKodUyumu tetikleyicisi bu durumda Ad/StatTuru/
--      Puan'ı Aliskanliklar'dan doldurur; böylece 003, PHP kodu
--      güncellenmeden önce de uygulanabilir.
--
-- Eski ortak tablo (Aliskanliklar), AliskanlikID sütunu ve uyumluluk
-- tetikleyicisi BU DOSYADA SİLİNMEZ. Onları kaldıran adım ayrı ve geri
-- alınamazdır: database/araclar/004_ortak_aliskanliklari_kaldir.sql
-- (yalnızca PHP kodu güncellendikten sonra, yedek alınarak, elle).
--
-- Ön koşul: 002 uygulanmış olmalı (FK_KA_Aliskanlik sayesinde sahipsiz
-- satır yoktur). Tekrar çalıştırılabilir; 003 kaydı varsa hiçbir şey yapmaz.
-- =====================================================================

SET NAMES utf8mb4;

DELIMITER $$

DROP PROCEDURE IF EXISTS cm003_tasi$$
CREATE PROCEDURE cm003_tasi()
cm003: BEGIN
  IF EXISTS (SELECT 1 FROM SemaGecmisi WHERE Surum = '004') THEN
    -- 004 sonrası eski tablo ve AliskanlikID yok; tetikleyici yeniden kurulamaz.
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = '003 zaten uygulanmis ve 004 ile tamamlanmis; bu dosya tekrar calistirilmaz.';
  END IF;
  IF EXISTS (SELECT 1 FROM SemaGecmisi WHERE Surum = '003') THEN
    LEAVE cm003;   -- zaten uygulanmış
  END IF;
  IF NOT EXISTS (SELECT 1 FROM SemaGecmisi WHERE Surum = '002') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '003 DURDU: once 002_kisitlar.sql uygulanmali.';
  END IF;

  -- KONTROL (hiçbir şey değiştirmeden önce) ----------------------------
  DROP TEMPORARY TABLE IF EXISTS cm003_sorunlar;
  CREATE TEMPORARY TABLE cm003_sorunlar (
    Sorun VARCHAR(255) NOT NULL, Adet INT NOT NULL, Ornekler TEXT NULL
  ) DEFAULT CHARSET=utf8mb4;

  INSERT INTO cm003_sorunlar
  SELECT 'Aliskanliklar.StatTuru NULL ya da gecersiz (gecerli: Zeka, Guc, Sosyallik, Disiplin, Yaraticilik)',
         COUNT(*), GROUP_CONCAT(DISTINCT CONCAT('AliskanlikID=', a.AliskanlikID, ' ''', IFNULL(a.StatTuru, 'NULL'), '''') SEPARATOR ', ')
    FROM KullaniciAliskanliklari ka
    JOIN Aliskanliklar a ON a.AliskanlikID = ka.AliskanlikID
   WHERE a.StatTuru IS NULL
      OR NOT ((a.StatTuru COLLATE utf8mb4_bin) IN ('Zeka','Guc','Sosyallik','Disiplin','Yaraticilik'))
  HAVING COUNT(*) > 0;

  INSERT INTO cm003_sorunlar
  SELECT 'Aliskanliklar.Puan NULL', COUNT(*),
         GROUP_CONCAT(DISTINCT a.AliskanlikID ORDER BY a.AliskanlikID)
    FROM KullaniciAliskanliklari ka
    JOIN Aliskanliklar a ON a.AliskanlikID = ka.AliskanlikID
   WHERE a.Puan IS NULL
  HAVING COUNT(*) > 0;

  INSERT INTO cm003_sorunlar
  SELECT 'Ayni kullanicida ayni adli birden cok aliskanlik (KullaniciID, Ad) (grup sayisi)',
         COUNT(*), GROUP_CONCAT(o SEPARATOR ' | ')
    FROM (SELECT CONCAT('Kullanici=', ka.KullaniciID, ' Ad=', MIN(a.Ad)) o
            FROM KullaniciAliskanliklari ka
            JOIN Aliskanliklar a ON a.AliskanlikID = ka.AliskanlikID
           GROUP BY ka.KullaniciID, a.Ad HAVING COUNT(*) > 1) d
  HAVING COUNT(*) > 0;

  IF EXISTS (SELECT 1 FROM cm003_sorunlar) THEN
    SELECT Sorun, Adet, Ornekler FROM cm003_sorunlar;
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = '003 DURDU: yukaridaki veri sorunlari var; hicbir sey degistirilmedi.';
  END IF;
  DROP TEMPORARY TABLE IF EXISTS cm003_sorunlar;

  -- 1. Sütunları ekle (yarıda kalmış bir çalıştırmadan kalmışsa atla) --
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = 'kullanicialiskanliklari'
                    AND COLUMN_NAME = 'Ad') THEN
    ALTER TABLE KullaniciAliskanliklari
      ADD COLUMN Ad       VARCHAR(100) NULL AFTER AliskanlikID,
      ADD COLUMN StatTuru VARCHAR(20)  NULL AFTER Ad,
      ADD COLUMN Puan     INT          NULL AFTER StatTuru;
  END IF;

  -- 2. Veriyi kopyala (yalnızca boş olan alanlar doldurulur) -----------
  UPDATE KullaniciAliskanliklari ka
    JOIN Aliskanliklar a ON a.AliskanlikID = ka.AliskanlikID
     SET ka.Ad       = COALESCE(ka.Ad, a.Ad),
         ka.StatTuru = COALESCE(ka.StatTuru, a.StatTuru),
         ka.Puan     = COALESCE(ka.Puan, a.Puan)
   WHERE ka.Ad IS NULL OR ka.StatTuru IS NULL OR ka.Puan IS NULL;

  IF EXISTS (SELECT 1 FROM KullaniciAliskanliklari
              WHERE Ad IS NULL OR StatTuru IS NULL OR Puan IS NULL) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = '003 DURDU: bazi satirlar kopyalanamadi (Ad/StatTuru/Puan NULL). Veri silinmedi.';
  END IF;

  -- 3-4. Sütunları sıkılaştır, kısıtları ekle -------------------------
  ALTER TABLE KullaniciAliskanliklari
    MODIFY AliskanlikID INT NULL COMMENT 'ESKI: ortak Aliskanliklar tablosu; 004 ile kaldirilir, yeni kod doldurmaz',
    MODIFY Ad           VARCHAR(100) NOT NULL COMMENT 'Kullaniciya ozel aliskanlik adi',
    MODIFY StatTuru     VARCHAR(20)  NOT NULL COMMENT 'Zeka, Guc, Sosyallik, Disiplin, Yaraticilik',
    MODIFY Puan         INT          NOT NULL DEFAULT 10 COMMENT 'Isaretleme basina taban puan';

  IF NOT EXISTS (SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
                  WHERE CONSTRAINT_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = 'kullanicialiskanliklari'
                    AND CONSTRAINT_NAME = 'CHK_KA_StatTuru') THEN
    ALTER TABLE KullaniciAliskanliklari
      ADD CONSTRAINT CHK_KA_StatTuru
      CHECK ((StatTuru COLLATE utf8mb4_bin) IN ('Zeka','Guc','Sosyallik','Disiplin','Yaraticilik'));
  END IF;

  IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS
                  WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = 'kullanicialiskanliklari'
                    AND INDEX_NAME = 'UQ_KA_Kullanici_Ad') THEN
    ALTER TABLE KullaniciAliskanliklari
      ADD UNIQUE KEY UQ_KA_Kullanici_Ad (KullaniciID, Ad);
  END IF;
END$$

DELIMITER ;

CALL cm003_tasi();   -- sorun varsa burada durur
DROP PROCEDURE IF EXISTS cm003_tasi;

-- 5. Geçiş uyumluluğu tetikleyicisi (eski PHP kodu için) ---------------
-- CREATE TRIGGER saklı yordam içinde kullanılamadığı için burada.
DROP TRIGGER IF EXISTS TRG_KA_EskiKodUyumu;
DELIMITER $$
CREATE TRIGGER TRG_KA_EskiKodUyumu
BEFORE INSERT ON KullaniciAliskanliklari
FOR EACH ROW
BEGIN
  -- Eski kod yalnızca AliskanlikID verir; Ad/StatTuru/Puan'ı ortak
  -- tablodan doldur. Yeni kod Ad verir ve AliskanlikID'yi boş bırakır.
  IF NEW.AliskanlikID IS NOT NULL AND (NEW.Ad IS NULL OR NEW.Ad = '') THEN
    SET NEW.Ad       = (SELECT a.Ad       FROM Aliskanliklar a WHERE a.AliskanlikID = NEW.AliskanlikID);
    SET NEW.StatTuru = (SELECT a.StatTuru FROM Aliskanliklar a WHERE a.AliskanlikID = NEW.AliskanlikID);
    SET NEW.Puan     = (SELECT COALESCE(a.Puan, 10) FROM Aliskanliklar a WHERE a.AliskanlikID = NEW.AliskanlikID);
  END IF;
END$$
DELIMITER ;

INSERT IGNORE INTO SemaGecmisi (Surum, Aciklama)
VALUES ('003', 'Aliskanliklar kullaniciya ozel: Ad/StatTuru/Puan KullaniciAliskanliklari tablosunda');
