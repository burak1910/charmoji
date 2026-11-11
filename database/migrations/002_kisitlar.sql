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
--     alınır (INSERT IGNORE); StatTuru beş geçerli değerle CHECK ile
--     sınırlanır; ToplamPuan NOT NULL DEFAULT 0 olur.
--   * KullaniciAliskanliklari: UNIQUE(KullaniciID, AliskanlikID)
--     (INSERT IGNORE); SonIslemTarihi DATETIME ise DATE'e çevrilir
--     (kod Y-m-d ile karşılaştırıyor; DATETIME'da seri artmaz).
--   * KullaniciGunlukGorevleri: UNIQUE(KullaniciID, Tarih, GorevTanimID).
--   * Arkadasliklar: aynı iki kişi arasında (yönden bağımsız) tek kayıt.
--   * Tüm ilişkiler için FOREIGN KEY; sorgu desenleri için indeksler.
--
-- VERİ SİLMEZ. GÜVENLİK KAPISI:
--   1. Önce her denetim cm002_sorunlar geçici tablosuna TAM BİR satır
--      yazar (sorun yoksa Adet = 0). Sorunlu satırlar ekrana basılır.
--   2. Kapı: denetimlerin hepsi çalışmış ve hepsi 0 değilse geçici
--      tablodaki CHECK ihlali ile durulur. Bu noktaya kadar kalıcı hiçbir
--      şey oluşturulmaz (yordam bile).
--   3. Değişiklikleri yapan cm002_uygula yordamı İLK İŞ olarak aynı kapıyı
--      yeniden denetler ve geçilmezse SIGNAL ile hiçbir şey yapmadan çıkar.
--      Böylece istemci hatada devam etse bile (mysql --force, etkileşimli
--      SOURCE) bozuk veride hiçbir değişiklik yapılmaz.
-- Temizlik için elle çalıştırılan betik: database/araclar/tekrar_temizle.sql
--
-- Tekrar tekrar çalıştırılabilir: var olan kısıt/indeks yeniden eklenmez;
-- aynı sütun dizisine sahip başka adlı bir indeks varsa o kabul edilir.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 1. DENETİMLER (salt okunur; her biri tam bir satır yazar)
-- ---------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS cm002_sorunlar, cm002_kapi;
CREATE TEMPORARY TABLE cm002_sorunlar (
  Kod      CHAR(3)      NOT NULL PRIMARY KEY,
  Tablo    VARCHAR(64)  NOT NULL,
  Sorun    VARCHAR(255) NOT NULL,
  Adet     INT          NOT NULL,
  Ornekler TEXT         NULL
) DEFAULT CHARSET=utf8mb4;

INSERT INTO cm002_sorunlar
SELECT 'K00', 'SemaGecmisi', '001_baseline.sql uygulanmamis', COUNT(*) = 0, NULL
  FROM SemaGecmisi WHERE Surum = '001';

-- Tekrarlar (Adet = tekrar grubu sayısı) -------------------------------
INSERT INTO cm002_sorunlar
SELECT 'K01', 'TakipKayitlari', 'Tekrar: ayni (KullaniciAliskanlikID, Tarih) icin birden cok satir',
       COUNT(*), GROUP_CONCAT(o SEPARATOR ' | ')
  FROM (SELECT CONCAT('KA=', KullaniciAliskanlikID, ' Tarih=', Tarih, ' KayitID:', GROUP_CONCAT(KayitID ORDER BY KayitID)) o
          FROM TakipKayitlari GROUP BY KullaniciAliskanlikID, Tarih HAVING COUNT(*) > 1) d;

INSERT INTO cm002_sorunlar
SELECT 'K02', 'KullaniciEnvanteri', 'Tekrar: ayni (KullaniciID, EsyaID) icin birden cok satir',
       COUNT(*), GROUP_CONCAT(o SEPARATOR ' | ')
  FROM (SELECT CONCAT('Kullanici=', KullaniciID, ' Esya=', EsyaID, ' EnvanterID:', GROUP_CONCAT(EnvanterID ORDER BY EnvanterID)) o
          FROM KullaniciEnvanteri GROUP BY KullaniciID, EsyaID HAVING COUNT(*) > 1) d;

INSERT INTO cm002_sorunlar
SELECT 'K03', 'KullaniciStatlari', 'Tekrar: ayni (KullaniciID, StatTuru) icin birden cok satir',
       COUNT(*), GROUP_CONCAT(o SEPARATOR ' | ')
  FROM (SELECT CONCAT('Kullanici=', KullaniciID, ' Stat=', MIN(StatTuru), ' StatID:', GROUP_CONCAT(StatID ORDER BY StatID)) o
          FROM KullaniciStatlari GROUP BY KullaniciID, StatTuru HAVING COUNT(*) > 1) d;

INSERT INTO cm002_sorunlar
SELECT 'K04', 'KullaniciAliskanliklari', 'Tekrar: ayni (KullaniciID, AliskanlikID) icin birden cok satir',
       COUNT(*), GROUP_CONCAT(o SEPARATOR ' | ')
  FROM (SELECT CONCAT('Kullanici=', KullaniciID, ' Aliskanlik=', AliskanlikID, ' KA_ID:', GROUP_CONCAT(KullaniciAliskanlikID ORDER BY KullaniciAliskanlikID)) o
          FROM KullaniciAliskanliklari WHERE AliskanlikID IS NOT NULL   -- 003 sonrası yeni satırlarda NULL olabilir
         GROUP BY KullaniciID, AliskanlikID HAVING COUNT(*) > 1) d;

INSERT INTO cm002_sorunlar
SELECT 'K05', 'KullaniciGunlukGorevleri', 'Tekrar: ayni (KullaniciID, Tarih, GorevTanimID) icin birden cok satir',
       COUNT(*), GROUP_CONCAT(o SEPARATOR ' | ')
  FROM (SELECT CONCAT('Kullanici=', KullaniciID, ' Tarih=', Tarih, ' Gorev=', GorevTanimID, ' ID:', GROUP_CONCAT(KullaniciGorevID ORDER BY KullaniciGorevID)) o
          FROM KullaniciGunlukGorevleri GROUP BY KullaniciID, Tarih, GorevTanimID HAVING COUNT(*) > 1) d;

INSERT INTO cm002_sorunlar
SELECT 'K06', 'Arkadasliklar', 'Tekrar: ayni iki kullanici arasinda (yonden bagimsiz) birden cok satir',
       COUNT(*), GROUP_CONCAT(o SEPARATOR ' | ')
  FROM (SELECT CONCAT(kucuk, '<->', buyuk, ' ArkadaslikID:', GROUP_CONCAT(ArkadaslikID ORDER BY ArkadaslikID)) o
          FROM (SELECT LEAST(IstekGonderenID, IstekAlanID) AS kucuk,
                       GREATEST(IstekGonderenID, IstekAlanID) AS buyuk, ArkadaslikID
                  FROM Arkadasliklar) c
         GROUP BY kucuk, buyuk
        HAVING COUNT(*) > 1) d;

-- Geçersiz değerler ------------------------------------------------------
-- utf8mb4_0900_bin: büyük/küçük harf, aksan ve SONDAKİ BOŞLUK duyarlı (NO PAD).
INSERT INTO cm002_sorunlar
SELECT 'K07', 'KullaniciStatlari', 'Gecersiz StatTuru (gecerli: Zeka, Guc, Sosyallik, Disiplin, Yaraticilik; harf ve bosluk dahil birebir)',
       COUNT(*), GROUP_CONCAT(DISTINCT CONCAT('''', StatTuru, '''') SEPARATOR ', ')
  FROM KullaniciStatlari
 WHERE NOT ((StatTuru COLLATE utf8mb4_0900_bin) IN ('Zeka','Guc','Sosyallik','Disiplin','Yaraticilik'));

INSERT INTO cm002_sorunlar
SELECT 'K08', 'KullaniciStatlari', 'ToplamPuan NULL (NOT NULL yapilacak)', COUNT(*),
       GROUP_CONCAT(StatID ORDER BY StatID)
  FROM KullaniciStatlari WHERE ToplamPuan IS NULL;

INSERT INTO cm002_sorunlar
SELECT 'K09', 'KullaniciAliskanliklari', 'SonIslemTarihi saat bilgisi iceriyor (DATE yapilacak; saat kaybolur)', COUNT(*),
       GROUP_CONCAT(KullaniciAliskanlikID ORDER BY KullaniciAliskanlikID)
  FROM KullaniciAliskanliklari
 WHERE SonIslemTarihi IS NOT NULL AND TIME(SonIslemTarihi) <> '00:00:00';

-- Sahipsiz satırlar (FK eklenemez) ---------------------------------------
INSERT INTO cm002_sorunlar
SELECT 'K10', 'TakipKayitlari', 'Sahipsiz: KullaniciAliskanlikID karsiligi yok (silinmis aliskanligin kayitlari)', COUNT(*),
       GROUP_CONCAT(t.KayitID ORDER BY t.KayitID)
  FROM TakipKayitlari t
  LEFT JOIN KullaniciAliskanliklari ka ON ka.KullaniciAliskanlikID = t.KullaniciAliskanlikID
 WHERE ka.KullaniciAliskanlikID IS NULL;

INSERT INTO cm002_sorunlar
SELECT 'K11', 'KullaniciAliskanliklari', 'Sahipsiz: KullaniciID karsiligi yok', COUNT(*),
       GROUP_CONCAT(x.KullaniciAliskanlikID ORDER BY x.KullaniciAliskanlikID)
  FROM KullaniciAliskanliklari x LEFT JOIN Kullanicilar k ON k.KullaniciID = x.KullaniciID
 WHERE k.KullaniciID IS NULL;

INSERT INTO cm002_sorunlar
SELECT 'K12', 'KullaniciAliskanliklari', 'Sahipsiz: AliskanlikID karsiligi yok', COUNT(*),
       GROUP_CONCAT(x.KullaniciAliskanlikID ORDER BY x.KullaniciAliskanlikID)
  FROM KullaniciAliskanliklari x LEFT JOIN Aliskanliklar a ON a.AliskanlikID = x.AliskanlikID
 WHERE x.AliskanlikID IS NOT NULL AND a.AliskanlikID IS NULL;

INSERT INTO cm002_sorunlar
SELECT 'K13', 'KullaniciStatlari', 'Sahipsiz: KullaniciID karsiligi yok', COUNT(*),
       GROUP_CONCAT(x.StatID ORDER BY x.StatID)
  FROM KullaniciStatlari x LEFT JOIN Kullanicilar k ON k.KullaniciID = x.KullaniciID
 WHERE k.KullaniciID IS NULL;

INSERT INTO cm002_sorunlar
SELECT 'K14', 'KullaniciEnvanteri', 'Sahipsiz: KullaniciID karsiligi yok', COUNT(*),
       GROUP_CONCAT(x.EnvanterID ORDER BY x.EnvanterID)
  FROM KullaniciEnvanteri x LEFT JOIN Kullanicilar k ON k.KullaniciID = x.KullaniciID
 WHERE k.KullaniciID IS NULL;

INSERT INTO cm002_sorunlar
SELECT 'K15', 'KullaniciEnvanteri', 'Sahipsiz: EsyaID karsiligi yok', COUNT(*),
       GROUP_CONCAT(x.EnvanterID ORDER BY x.EnvanterID)
  FROM KullaniciEnvanteri x LEFT JOIN MarketEsyalari m ON m.EsyaID = x.EsyaID
 WHERE m.EsyaID IS NULL;

INSERT INTO cm002_sorunlar
SELECT 'K16', 'KullaniciGunlukGorevleri', 'Sahipsiz: KullaniciID karsiligi yok', COUNT(*),
       GROUP_CONCAT(x.KullaniciGorevID ORDER BY x.KullaniciGorevID)
  FROM KullaniciGunlukGorevleri x LEFT JOIN Kullanicilar k ON k.KullaniciID = x.KullaniciID
 WHERE k.KullaniciID IS NULL;

INSERT INTO cm002_sorunlar
SELECT 'K17', 'KullaniciGunlukGorevleri', 'Sahipsiz: GorevTanimID karsiligi yok', COUNT(*),
       GROUP_CONCAT(x.KullaniciGorevID ORDER BY x.KullaniciGorevID)
  FROM KullaniciGunlukGorevleri x LEFT JOIN GorevTanimlari g ON g.GorevTanimID = x.GorevTanimID
 WHERE g.GorevTanimID IS NULL;

INSERT INTO cm002_sorunlar
SELECT 'K18', 'Arkadasliklar', 'Sahipsiz: IstekGonderenID veya IstekAlanID karsiligi yok', COUNT(*),
       GROUP_CONCAT(x.ArkadaslikID ORDER BY x.ArkadaslikID)
  FROM Arkadasliklar x
  LEFT JOIN Kullanicilar g ON g.KullaniciID = x.IstekGonderenID
  LEFT JOIN Kullanicilar a ON a.KullaniciID = x.IstekAlanID
 WHERE g.KullaniciID IS NULL OR a.KullaniciID IS NULL;

-- Sorunları göster (sorun yoksa boş sonuç)
SELECT Tablo, Sorun, Adet, Ornekler FROM cm002_sorunlar WHERE Adet > 0 ORDER BY Kod;

-- ---------------------------------------------------------------------
-- 2. KAPI: 19 denetimin hepsi çalışmış ve hepsi temiz olmalı. Değilse
--    aşağıdaki INSERT, adı mesaj olan CHECK kısıtını ihlal ederek durur.
-- ---------------------------------------------------------------------
CREATE TEMPORARY TABLE cm002_kapi (
  Durum VARCHAR(10) NOT NULL,
  CONSTRAINT `002_DURDU_veri_sorunu_var_hicbir_sey_degistirilmedi` CHECK (Durum = 'temiz')
);
INSERT INTO cm002_kapi
SELECT IF(COUNT(*) = 19 AND SUM(Adet) = 0, 'temiz', 'sorunlu') FROM cm002_sorunlar;

-- ---------------------------------------------------------------------
-- 3. UYGULAMA (kapı yordamın içinde yeniden denetlenir)
-- ---------------------------------------------------------------------
DELIMITER $$

DROP PROCEDURE IF EXISTS cm002_sql$$
CREATE PROCEDURE cm002_sql(IN p_sql TEXT)
BEGIN
  SET @cm002_sql = p_sql;
  PREPARE cm002_st FROM @cm002_sql;
  EXECUTE cm002_st;
  DEALLOCATE PREPARE cm002_st;
END$$

-- İndeksi garanti eder. Aynı ad varsa, ya da AYNI SÜTUN DİZİSİNE sahip
-- (UNIQUE isteniyorsa UNIQUE olan) başka adlı bir indeks varsa eklemez.
-- UNIQUE isteniyor ve aynı sütun dizili UNIQUE OLMAYAN bir indeks varsa,
-- onu aynı ALTER içinde UNIQUE ile değiştirir (yinelenen indeks kalmaz).
-- p_sutunlar NULL ise (fonksiyonel indeks) yalnızca ada bakılır.
DROP PROCEDURE IF EXISTS cm002_indeks$$
CREATE PROCEDURE cm002_indeks(IN p_tablo VARCHAR(64), IN p_ad VARCHAR(64), IN p_sutunlar VARCHAR(255),
                              IN p_unique TINYINT, IN p_tanim TEXT)
cm_indeks: BEGIN
  DECLARE v_esdeger VARCHAR(64) DEFAULT NULL;
  IF EXISTS (SELECT 1 FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = LOWER(p_tablo)
                AND INDEX_NAME = p_ad) THEN
    LEAVE cm_indeks;
  END IF;
  IF p_sutunlar IS NOT NULL THEN
    IF EXISTS (SELECT 1 FROM (
                 SELECT INDEX_NAME, MIN(NON_UNIQUE) AS nu,
                        LOWER(GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX)) AS sutunlar
                   FROM information_schema.STATISTICS
                  WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = LOWER(p_tablo)
                  GROUP BY INDEX_NAME) x
                WHERE x.sutunlar = LOWER(p_sutunlar) AND (p_unique = 0 OR x.nu = 0)) THEN
      LEAVE cm_indeks;
    END IF;
    IF p_unique = 1 THEN
      SELECT x.INDEX_NAME INTO v_esdeger FROM (
               SELECT INDEX_NAME, LOWER(GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX)) AS sutunlar
                 FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = LOWER(p_tablo)
                  AND INDEX_NAME <> 'PRIMARY'
                GROUP BY INDEX_NAME) x
       WHERE x.sutunlar = LOWER(p_sutunlar) LIMIT 1;
    END IF;
  END IF;
  IF v_esdeger IS NOT NULL THEN
    CALL cm002_sql(CONCAT('ALTER TABLE `', p_tablo, '` DROP INDEX `', v_esdeger, '`, ADD ', p_tanim));
  ELSE
    CALL cm002_sql(CONCAT('ALTER TABLE `', p_tablo, '` ADD ', p_tanim));
  END IF;
END$$

-- CHECK kısıtını garanti eder. Aynı adla var ama ifadesi p_isaret'i
-- içermiyorsa (eski sürüm) aynı ALTER içinde düşürüp yeniden ekler.
DROP PROCEDURE IF EXISTS cm002_check$$
CREATE PROCEDURE cm002_check(IN p_tablo VARCHAR(64), IN p_ad VARCHAR(64), IN p_ifade TEXT, IN p_isaret VARCHAR(64))
BEGIN
  DECLARE v_ifade TEXT DEFAULT NULL;
  SELECT cc.CHECK_CLAUSE INTO v_ifade
    FROM information_schema.CHECK_CONSTRAINTS cc
    JOIN information_schema.TABLE_CONSTRAINTS tc
      ON tc.CONSTRAINT_SCHEMA = cc.CONSTRAINT_SCHEMA AND tc.CONSTRAINT_NAME = cc.CONSTRAINT_NAME
   WHERE cc.CONSTRAINT_SCHEMA = DATABASE() AND LOWER(tc.TABLE_NAME) = LOWER(p_tablo)
     AND cc.CONSTRAINT_NAME = p_ad AND tc.CONSTRAINT_TYPE = 'CHECK'
   LIMIT 1;
  IF v_ifade IS NULL THEN
    CALL cm002_sql(CONCAT('ALTER TABLE `', p_tablo, '` ADD CONSTRAINT `', p_ad, '` CHECK (', p_ifade, ')'));
  ELSEIF LOCATE(p_isaret, v_ifade) = 0 THEN
    CALL cm002_sql(CONCAT('ALTER TABLE `', p_tablo, '` DROP CHECK `', p_ad, '`, ADD CONSTRAINT `', p_ad, '` CHECK (', p_ifade, ')'));
  END IF;
END$$

-- FOREIGN KEY'i istenen ad ve ON DELETE kuralıyla garanti eder.
-- Aynı sütunda farklı ad/kuralla bir FK varsa önce o kısıt kaldırılır
-- (yalnızca kısıt; veri değil).
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

DROP PROCEDURE IF EXISTS cm002_uygula$$
CREATE PROCEDURE cm002_uygula()
BEGIN
  DECLARE v_denetim INT DEFAULT 0;
  DECLARE v_sorun INT DEFAULT 1;
  -- KAPI: istemci önceki hatayı yok sayıp devam etse bile burada durulur.
  -- (Geçici tablo yoksa bu sorgu zaten hata verir ve yordam durur.)
  SELECT COUNT(*), COALESCE(SUM(Adet), 1) INTO v_denetim, v_sorun FROM cm002_sorunlar;
  IF v_denetim <> 19 OR v_sorun <> 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = '002 DURDU: veri sorunu var; hicbir sey degistirilmedi. Bkz. database/araclar/tekrar_temizle.sql';
  END IF;

  -- Kullanicilar: giriş (WHERE Ad = ?) ve arama (Ad LIKE) için
  CALL cm002_indeks('Kullanicilar', 'IDX_Kullanici_Ad', 'Ad', 0, 'KEY `IDX_Kullanici_Ad` (`Ad`)');

  -- KullaniciStatlari
  IF EXISTS (SELECT 1 FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = 'kullanicistatlari'
                AND COLUMN_NAME = 'ToplamPuan'
                AND NOT (IS_NULLABLE = 'NO' AND COLUMN_DEFAULT = '0')) THEN
    ALTER TABLE KullaniciStatlari MODIFY ToplamPuan INT NOT NULL DEFAULT 0;
  END IF;
  CALL cm002_indeks('KullaniciStatlari', 'UQ_Stat_User', 'KullaniciID,StatTuru', 1,
       'UNIQUE KEY `UQ_Stat_User` (`KullaniciID`,`StatTuru`)');
  CALL cm002_check('KullaniciStatlari', 'CHK_KS_StatTuru',
       '(`StatTuru` collate utf8mb4_0900_bin) in (''Zeka'',''Guc'',''Sosyallik'',''Disiplin'',''Yaraticilik'')',
       'utf8mb4_0900_bin');
  CALL cm002_fk('KullaniciStatlari', 'FK_Stat_Kullanici', 'KullaniciID', 'Kullanicilar', 'KullaniciID', 'CASCADE');

  -- KullaniciAliskanliklari
  IF EXISTS (SELECT 1 FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = 'kullanicialiskanliklari'
                AND COLUMN_NAME = 'SonIslemTarihi' AND DATA_TYPE <> 'date') THEN
    ALTER TABLE KullaniciAliskanliklari MODIFY SonIslemTarihi DATE DEFAULT NULL;
  END IF;
  CALL cm002_indeks('KullaniciAliskanliklari', 'UQ_KA_Kullanici_Aliskanlik', 'KullaniciID,AliskanlikID', 1,
       'UNIQUE KEY `UQ_KA_Kullanici_Aliskanlik` (`KullaniciID`,`AliskanlikID`)');
  CALL cm002_fk('KullaniciAliskanliklari', 'FK_KA_Kullanici', 'KullaniciID', 'Kullanicilar', 'KullaniciID', 'CASCADE');
  CALL cm002_fk('KullaniciAliskanliklari', 'FK_KA_Aliskanlik', 'AliskanlikID', 'Aliskanliklar', 'AliskanlikID', 'RESTRICT');

  -- TakipKayitlari (CASCADE: alışkanlık silinince kayıtları da gider)
  CALL cm002_indeks('TakipKayitlari', 'UQ_TK_KA_Tarih', 'KullaniciAliskanlikID,Tarih', 1,
       'UNIQUE KEY `UQ_TK_KA_Tarih` (`KullaniciAliskanlikID`,`Tarih`)');
  CALL cm002_fk('TakipKayitlari', 'FK_TK_KA', 'KullaniciAliskanlikID', 'KullaniciAliskanliklari', 'KullaniciAliskanlikID', 'CASCADE');

  -- KullaniciEnvanteri
  CALL cm002_indeks('KullaniciEnvanteri', 'UQ_Env_Kullanici_Esya', 'KullaniciID,EsyaID', 1,
       'UNIQUE KEY `UQ_Env_Kullanici_Esya` (`KullaniciID`,`EsyaID`)');
  CALL cm002_fk('KullaniciEnvanteri', 'FK_Env_Kullanici', 'KullaniciID', 'Kullanicilar', 'KullaniciID', 'CASCADE');
  CALL cm002_fk('KullaniciEnvanteri', 'FK_Env_Esya', 'EsyaID', 'MarketEsyalari', 'EsyaID', 'RESTRICT');

  -- KullaniciGunlukGorevleri (sorgular KullaniciID + Tarih ile süzer)
  CALL cm002_indeks('KullaniciGunlukGorevleri', 'UQ_KGG_Kullanici_Tarih_Gorev', 'KullaniciID,Tarih,GorevTanimID', 1,
       'UNIQUE KEY `UQ_KGG_Kullanici_Tarih_Gorev` (`KullaniciID`,`Tarih`,`GorevTanimID`)');
  CALL cm002_fk('KullaniciGunlukGorevleri', 'FK_KGG_Kullanici', 'KullaniciID', 'Kullanicilar', 'KullaniciID', 'CASCADE');
  CALL cm002_fk('KullaniciGunlukGorevleri', 'FK_KGG_Tanim', 'GorevTanimID', 'GorevTanimlari', 'GorevTanimID', 'RESTRICT');

  -- Arkadasliklar: bekleyen istekler (IstekAlanID, Durum) ile aranır;
  -- A->B ve B->A aynı çift sayılır (fonksiyonel UNIQUE indeks).
  CALL cm002_indeks('Arkadasliklar', 'IDX_Ark_Alan_Durum', 'IstekAlanID,Durum', 0,
       'KEY `IDX_Ark_Alan_Durum` (`IstekAlanID`,`Durum`)');
  CALL cm002_indeks('Arkadasliklar', 'UQ_Ark_Cift', NULL, 1,
       'UNIQUE KEY `UQ_Ark_Cift` ((least(`IstekGonderenID`,`IstekAlanID`)),(greatest(`IstekGonderenID`,`IstekAlanID`)))');
  CALL cm002_fk('Arkadasliklar', 'FK_Ark_Gonderen', 'IstekGonderenID', 'Kullanicilar', 'KullaniciID', 'CASCADE');
  CALL cm002_fk('Arkadasliklar', 'FK_Ark_Alan', 'IstekAlanID', 'Kullanicilar', 'KullaniciID', 'CASCADE');

  INSERT IGNORE INTO SemaGecmisi (Surum, Aciklama)
  VALUES ('002', 'Eksik UNIQUE/FK/CHECK kisitlari ve indeksler');
END$$

DELIMITER ;

CALL cm002_uygula();

DROP PROCEDURE IF EXISTS cm002_uygula;
DROP PROCEDURE IF EXISTS cm002_fk;
DROP PROCEDURE IF EXISTS cm002_check;
DROP PROCEDURE IF EXISTS cm002_indeks;
DROP PROCEDURE IF EXISTS cm002_sql;
DROP TEMPORARY TABLE IF EXISTS cm002_sorunlar, cm002_kapi;
