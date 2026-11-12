-- =====================================================================
-- 005_gorev_kurallari.sql  —  Görev (alışkanlık) kuralları için veri
-- ---------------------------------------------------------------------
-- Ön koşul: 003 uygulanmış olmalı. 004'ten (araclar/) önce de sonra da
-- çalışır: Aliskanliklar tablosuna ve AliskanlikID sütununa başvurmaz.
--
-- Neden (docs/ux/03-gorevler.md, 09 S7, S9, S18, S19, S25):
--   1. KullaniciAliskanliklari
--      * ArsivZamani DATETIME NULL: silme = arşivleme (S18). AktifMi = 0
--        yapılır; "Geri al" süresini sunucu bu zamana göre denetler.
--      * AktifAd (STORED üretilmiş sütun) = IF(AktifMi = 1, Ad, NULL),
--        karşılaştırma kuralı utf8mb4_tr_0900_ai_ci (Türkçe, büyük/küçük
--        harf duyarsız; ç ğ ı ö ş ü ayrı harftir: 'Kitap' = 'KİTAP', 'Şınav' <> 'Sınav').
--      * UQ_KA_Kullanici_Ad (KullaniciID, Ad) KALDIRILIR, yerine
--        UQ_KA_Kullanici_AktifAd (KullaniciID, AktifAd) eklenir: ad
--        yalnızca AKTİF görevler arasında benzersizdir (S9). Eski UNIQUE,
--        arşivlenmiş bir görevin adıyla yeni görev eklemeyi engellerdi.
--   2. TakipKayitlari: tamamlama anının verisi (geri alma için, 03 §8.1):
--        KazanilanXP, StatTuru (XP'nin yazıldığı yetenek; S19: yetenek
--        sonradan değişse de geri alma doğru yetenekten düşer),
--        OncekiSeri, OncekiSonIslemTarihi (geri almada seri ve son tarih
--        tamamlama öncesine döner; aynı gün yeniden tamamlanınca seri artar).
--        Eski satırlarda NULL kalır; kod NULL'da eski hesaba düşer.
--   3. GorevTanimlari.Tur: meydan okuma türü (S25), başlık metni yerine:
--        'giris'           : o gün uygulama açılınca tamamlanır
--        'gorev_tamamlama' : o gün tamamlanan her görev ilerlemeyi 1 artırır
--      Var olan satırlar: Baslik = 'Günlük Giriş' -> 'giris', diğerleri ->
--      'gorev_tamamlama' (bu dosya hangi satırların neye çevrildiğini basar).
--      Ayrıca seed'den gelen '3 Alışkanlık Tamamla' başlığı 03 §10.1
--      terimlerine göre '3 Görev Tamamla' yapılır (yalnızca bu birebir başlık).
--
-- VERİ SİLMEZ. Tekrar çalıştırılabilir (eksik olanı ekler). Güvenlik kapısı
-- 002/003 ile aynı kalıptadır: denetimler geçmezse hiçbir şey değişmez.
-- =====================================================================

SET NAMES utf8mb4;

DROP TEMPORARY TABLE IF EXISTS cm005_kapi003, cm005_sorunlar, cm005_kapi;

-- 003 uygulanmamışsa (Ad sütunu yok) burada durulur; hiçbir şey değişmez.
CREATE TEMPORARY TABLE cm005_kapi003 (
  Durum VARCHAR(10) NOT NULL,
  CONSTRAINT `005_DURDU_once_003_uygulanmali_hicbir_sey_degistirilmedi` CHECK (Durum = 'devam')
);
INSERT INTO cm005_kapi003
SELECT IF(COUNT(*) = 1, 'devam', 'dur') FROM SemaGecmisi WHERE Surum = '003';

-- ---------------------------------------------------------------------
-- 0. DENETİMLER (salt okunur; her biri tam bir satır yazar)
-- ---------------------------------------------------------------------
CREATE TEMPORARY TABLE cm005_sorunlar (
  Kod CHAR(3) NOT NULL PRIMARY KEY, Sorun VARCHAR(255) NOT NULL, Adet INT NOT NULL, Ornekler TEXT NULL
) DEFAULT CHARSET=utf8mb4;

-- K01: aynı kullanıcının AKTİF görevlerinde yeni kurala göre aynı sayılan adlar.
INSERT INTO cm005_sorunlar
SELECT 'K01', 'Ayni kullanicinin aktif gorevlerinde ayni sayilan adlar (kural: utf8mb4_tr_0900_ai_ci); birini yeniden adlandirin ya da AktifMi = 0 yapin',
       COUNT(*), GROUP_CONCAT(o SEPARATOR ' | ')
  FROM (SELECT CONCAT('Kullanici=', KullaniciID, ' Adlar: ',
                      GROUP_CONCAT(CONCAT('''', Ad, ''' (KA=', KullaniciAliskanlikID, ')') SEPARATOR ', ')) o
          FROM KullaniciAliskanliklari
         WHERE AktifMi = 1
         GROUP BY KullaniciID, (CONVERT(Ad USING utf8mb4) COLLATE utf8mb4_tr_0900_ai_ci)
        HAVING COUNT(*) > 1) d;

-- K02: TakipKayitlari.StatTuru'ya eklenecek CHECK'i bozacak değer (sütun varsa).
SET @cm005_sql = IF(EXISTS (SELECT 1 FROM information_schema.COLUMNS
                             WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = 'takipkayitlari'
                               AND COLUMN_NAME = 'StatTuru'),
  'INSERT INTO cm005_sorunlar SELECT ''K02'', ''TakipKayitlari.StatTuru gecersiz'', COUNT(*), GROUP_CONCAT(KayitID) FROM TakipKayitlari
    WHERE StatTuru IS NOT NULL AND NOT ((StatTuru COLLATE utf8mb4_0900_bin) IN (''Zeka'',''Guc'',''Sosyallik'',''Disiplin'',''Yaraticilik''))',
  'INSERT INTO cm005_sorunlar VALUES (''K02'', ''TakipKayitlari.StatTuru gecersiz'', 0, NULL)');
PREPARE cm005_st FROM @cm005_sql; EXECUTE cm005_st; DEALLOCATE PREPARE cm005_st;

SELECT Sorun, Adet, Ornekler FROM cm005_sorunlar WHERE Adet > 0 ORDER BY Kod;

CREATE TEMPORARY TABLE cm005_kapi (
  Durum VARCHAR(10) NOT NULL,
  CONSTRAINT `005_DURDU_veri_sorunu_var_hicbir_sey_degistirilmedi` CHECK (Durum = 'temiz')
);
INSERT INTO cm005_kapi
SELECT IF(COUNT(*) = 2 AND SUM(Adet) = 0, 'temiz', 'sorunlu') FROM cm005_sorunlar;

-- ---------------------------------------------------------------------
-- 1-3. UYGULAMA
-- ---------------------------------------------------------------------
DELIMITER $$

DROP PROCEDURE IF EXISTS cm005_sql$$
CREATE PROCEDURE cm005_sql(IN p_sql TEXT)
BEGIN
  SET @cm005_sql = p_sql;
  PREPARE cm005_st FROM @cm005_sql;
  EXECUTE cm005_st;
  DEALLOCATE PREPARE cm005_st;
END$$

DROP PROCEDURE IF EXISTS cm005_uygula$$
CREATE PROCEDURE cm005_uygula()
BEGIN
  DECLARE v_denetim INT DEFAULT 0;
  DECLARE v_sorun INT DEFAULT 1;
  DECLARE v_degisiklik TEXT DEFAULT '';

  -- KAPI (istemci hatada devam etse bile)
  SELECT COUNT(*), COALESCE(SUM(Adet), 1) INTO v_denetim, v_sorun FROM cm005_sorunlar;
  IF (SELECT COUNT(*) FROM SemaGecmisi WHERE Surum = '003') = 0 OR v_denetim <> 2 OR v_sorun <> 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '005 DURDU: denetimler gecmedi; hicbir sey degistirilmedi.';
  END IF;

  -- 1. KullaniciAliskanliklari ------------------------------------------
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = 'kullanicialiskanliklari'
                    AND COLUMN_NAME = 'ArsivZamani') THEN
    SET v_degisiklik = 'ADD COLUMN ArsivZamani DATETIME NULL DEFAULT NULL COMMENT ''Silme = arsivleme (AktifMi = 0) zamani; Geri al suresi buna gore'' AFTER AktifMi';
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = 'kullanicialiskanliklari'
                    AND COLUMN_NAME = 'AktifAd') THEN
    SET v_degisiklik = CONCAT(v_degisiklik, IF(v_degisiklik = '', '', ', '),
      'ADD COLUMN AktifAd VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_tr_0900_ai_ci ',
      'GENERATED ALWAYS AS (IF(AktifMi = 1, Ad, NULL)) STORED ',
      'COMMENT ''Yalnizca aktif gorevde Ad; ad benzersizligi (S9) bu sutunda'' AFTER Ad');
  END IF;
  IF EXISTS (SELECT 1 FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = 'kullanicialiskanliklari'
                AND INDEX_NAME = 'UQ_KA_Kullanici_Ad') THEN
    SET v_degisiklik = CONCAT(v_degisiklik, IF(v_degisiklik = '', '', ', '), 'DROP INDEX UQ_KA_Kullanici_Ad');
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS
                  WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = 'kullanicialiskanliklari'
                    AND INDEX_NAME = 'UQ_KA_Kullanici_AktifAd') THEN
    SET v_degisiklik = CONCAT(v_degisiklik, IF(v_degisiklik = '', '', ', '),
      'ADD UNIQUE KEY UQ_KA_Kullanici_AktifAd (KullaniciID, AktifAd)');
  END IF;
  -- Tek ALTER: eski UNIQUE'in düşmesi ile yenisinin eklenmesi birlikte olur.
  IF v_degisiklik <> '' THEN
    CALL cm005_sql(CONCAT('ALTER TABLE KullaniciAliskanliklari ', v_degisiklik));
  END IF;

  -- 2. TakipKayitlari ---------------------------------------------------
  SET v_degisiklik = '';
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = 'takipkayitlari'
                    AND COLUMN_NAME = 'KazanilanXP') THEN
    SET v_degisiklik = CONCAT(
      'ADD COLUMN KazanilanXP INT NULL DEFAULT NULL COMMENT ''Bu tamamlamada kazanilan XP (geri almada aynen dusulur)'', ',
      'ADD COLUMN StatTuru VARCHAR(20) NULL DEFAULT NULL COMMENT ''XP nin yazildigi yetenek (tamamlama anindaki)'', ',
      'ADD COLUMN OncekiSeri INT NULL DEFAULT NULL COMMENT ''Tamamlamadan onceki Seri (geri almada geri yuklenir)'', ',
      'ADD COLUMN OncekiSonIslemTarihi DATE NULL DEFAULT NULL COMMENT ''Tamamlamadan onceki SonIslemTarihi''');
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.CHECK_CONSTRAINTS
                  WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'CHK_TK_StatTuru') THEN
    SET v_degisiklik = CONCAT(v_degisiklik, IF(v_degisiklik = '', '', ', '),
      'ADD CONSTRAINT CHK_TK_StatTuru CHECK ((StatTuru IS NULL) OR ((StatTuru COLLATE utf8mb4_0900_bin) IN (''Zeka'',''Guc'',''Sosyallik'',''Disiplin'',''Yaraticilik'')))');
  END IF;
  IF v_degisiklik <> '' THEN
    CALL cm005_sql(CONCAT('ALTER TABLE TakipKayitlari ', v_degisiklik));
  END IF;

  -- 3. GorevTanimlari ---------------------------------------------------
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = 'gorevtanimlari'
                    AND COLUMN_NAME = 'Tur') THEN
    ALTER TABLE GorevTanimlari ADD COLUMN Tur VARCHAR(20) NULL AFTER Ikon;
  END IF;

  -- Bilgi: hangi satır hangi türe çevriliyor (yalnızca Tur'u boş olanlar)
  SELECT GorevTanimID, Baslik,
         IF(Baslik = 'Günlük Giriş', 'giris', 'gorev_tamamlama') AS YeniTur,
         IF(Baslik = 'Günlük Giriş' OR Baslik LIKE '%alışkanlık%' OR Baslik LIKE '%görev%', '',
            'DIKKAT: eski kodda bu meydan okuma hic ilerlemiyordu') AS Not_
    FROM GorevTanimlari WHERE Tur IS NULL;

  UPDATE GorevTanimlari
     SET Tur = IF(Baslik = 'Günlük Giriş', 'giris', 'gorev_tamamlama')
   WHERE Tur IS NULL;

  -- 03 §10.1: içerik terimi "alışkanlık" -> "görev" (yalnızca seed'deki birebir başlık)
  UPDATE GorevTanimlari
     SET Aciklama = IF(Aciklama = 'Bugün üç alışkanlığını işaretle.', 'Bugün üç görevini tamamla.', Aciklama),
         Baslik = '3 Görev Tamamla'
   WHERE Baslik = '3 Alışkanlık Tamamla'
     AND NOT EXISTS (SELECT 1 FROM (SELECT Baslik FROM GorevTanimlari) g WHERE g.Baslik = '3 Görev Tamamla');

  IF EXISTS (SELECT 1 FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = 'gorevtanimlari'
                AND COLUMN_NAME = 'Tur' AND IS_NULLABLE = 'YES') THEN
    ALTER TABLE GorevTanimlari
      MODIFY Tur VARCHAR(20) NOT NULL COMMENT 'Meydan okuma turu: giris | gorev_tamamlama (S25)';
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.CHECK_CONSTRAINTS
                  WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'CHK_GT_Tur') THEN
    ALTER TABLE GorevTanimlari
      ADD CONSTRAINT CHK_GT_Tur CHECK ((Tur COLLATE utf8mb4_0900_bin) IN ('giris','gorev_tamamlama'));
  END IF;

  INSERT IGNORE INTO SemaGecmisi (Surum, Aciklama)
  VALUES ('005', 'Gorev kurallari: aktif ad benzersizligi, arsiv zamani, tamamlama verisi, meydan okuma turu');
END$$

DELIMITER ;

CALL cm005_uygula();

DROP PROCEDURE IF EXISTS cm005_uygula;
DROP PROCEDURE IF EXISTS cm005_sql;
DROP TEMPORARY TABLE IF EXISTS cm005_kapi003, cm005_sorunlar, cm005_kapi;
