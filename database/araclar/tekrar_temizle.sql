-- =====================================================================
-- araclar/tekrar_temizle.sql  —  002'yi durduran veri sorunlarını temizler
-- ---------------------------------------------------------------------
-- !!! VERİ SİLER. OTOMATİK ÇALIŞTIRILMAZ. Elle ve onayla çalıştırılır. !!!
--
-- Ne zaman: 002_kisitlar.sql "002 DURDU" hatası verip tekrarlı /
-- sahipsiz / geçersiz satırları listelediğinde.
--
-- Nasıl:
--   1) ÖNCE YEDEK ALIN:
--        mysqldump -uroot --default-character-set=utf8mb4 --routines --triggers charmoji > yedek_once.sql
--   2) Onay değişkeniyle aynı oturumda çalıştırın:
--        mysql -uroot --default-character-set=utf8mb4 charmoji \
--          -e "SET @onay='EVET-TEMIZLE'; SOURCE database/araclar/tekrar_temizle.sql;"
--   3) Ardından 002_kisitlar.sql'i yeniden çalıştırın.
--
-- Ne yapar (tek yordam içinde; veri adımları tek transaction):
--   a. StatTuru yazımını standartlaştırır ('güç','GUC' -> 'Guc' vb.);
--      KullaniciStatlari ve Aliskanliklar'da. Satır silmez.
--   b. KullaniciStatlari.ToplamPuan NULL ise 0 yapar.
--   c. KullaniciAliskanliklari.SonIslemTarihi'ndeki saat bilgisini atar.
--   d. Tekrar gruplarında BİR satır tutar, diğerlerini siler:
--        KullaniciStatlari       : en yüksek ToplamPuan'lı satır kalır
--        KullaniciAliskanliklari : en küçük ID kalır; Seri/SonIslemTarihi/
--                                  AktifMi grubun en büyüğü olur; silinen
--                                  satırların TakipKayitlari'ı kalan satıra
--                                  taşınır
--        TakipKayitlari          : en küçük KayitID kalır (TamamlandiMi = max)
--        KullaniciEnvanteri      : en küçük ID kalır (KusandiMi = max)
--        KullaniciGunlukGorevleri: en küçük ID kalır (ilerleme/tamamlandı/
--                                  ödül alındı = grubun en büyüğü)
--        Arkadasliklar           : en küçük ID kalır (Durum = max; kabul korunur)
--   e. Sahipsiz satırları (ebeveyni olmayan) siler.
--
-- Silinen HER satır önce `_yedek_<Tablo>` tablosuna kopyalanır
-- (YedekZamani, Neden sütunlarıyla). Doğruladıktan sonra bu tabloları
-- elle düşürebilirsiniz. Geri almak için: yedek tablodan INSERT ... SELECT.
--
-- BİLİNEN SINIR: Tekrarlı TakipKayitlari, kullanıcının aynı gün için
-- fazladan puan kazandığı anlamına gelir. Bu betik KullaniciStatlari
-- puanlarını yeniden HESAPLAMAZ (çarpan seriye bağlı ve geçmişte
-- değişmiş olabilir). Gerekirse ayrıca karar verilmelidir.
--
-- Hedef şema: 001 uygulanmış, 002 uygulanamamış veritabanı.
-- 003 veya 004 sonrası için yazılmamıştır.
-- Standartlaştırılamayan StatTuru değerleri silinmez; elle düzeltin.
-- =====================================================================

SET NAMES utf8mb4;

-- GÜVENLİK: onay denetimi, silen kodla AYNI yordamın (cm_temizle) ilk
-- adımıdır. İstemci hatada devam etse bile (mysql --force, etkileşimli
-- SOURCE) onay yoksa hiçbir şey değişmez. Aşağıdaki erken kapı yalnızca
-- batch modda hiçbir nesne oluşturulmadan durulmasını sağlar.
DROP TEMPORARY TABLE IF EXISTS cm_temizle_kapi;
CREATE TEMPORARY TABLE cm_temizle_kapi (
  Durum VARCHAR(10) NOT NULL,
  CONSTRAINT `TEMIZLIK_DURDU_onay_yok_once_yedek_SET_onay_EVET_TEMIZLE` CHECK (Durum = 'onayli')
);
INSERT INTO cm_temizle_kapi
SELECT IF(@onay IS NOT NULL AND @onay = 'EVET-TEMIZLE', 'onayli', 'onaysiz');

DROP PROCEDURE IF EXISTS cm_temizle;
DELIMITER $$
CREATE PROCEDURE cm_temizle()
BEGIN
  -- Hata olursa veri değişiklikleri geri alınır (yedek tabloları DDL'dir, kalır).
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  IF @onay IS NULL OR @onay <> 'EVET-TEMIZLE' THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Onay yok; hicbir sey degistirilmedi. Once yedek alin, sonra SET @onay=''EVET-TEMIZLE''; ile ayni oturumda calistirin.';
  END IF;
  SET @onay = NULL;   -- onay tek kullanımlık
  -- 003 sonrası AliskanlikID'si boş (kullanıcıya özel) satırlar "sahipsiz"
  -- sanılıp silinmesin: betik yalnızca 002 öncesi şema için.
  IF EXISTS (SELECT 1 FROM SemaGecmisi WHERE Surum IN ('003', '004')) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Bu betik 003 oncesi sema icindir (003 uygulanmis); hicbir sey degistirilmedi.';
  END IF;
  SET @cm_zaman = NOW();

  -- Yedek tabloları (DDL örtük COMMIT yaptığı için transaction'dan önce)
  CREATE TABLE IF NOT EXISTS _yedek_KullaniciStatlari
    AS SELECT t.*, NOW() AS YedekZamani, CAST('' AS CHAR(40)) AS Neden FROM KullaniciStatlari t LIMIT 0;
  CREATE TABLE IF NOT EXISTS _yedek_KullaniciAliskanliklari
    AS SELECT t.*, NOW() AS YedekZamani, CAST('' AS CHAR(40)) AS Neden FROM KullaniciAliskanliklari t LIMIT 0;
  CREATE TABLE IF NOT EXISTS _yedek_TakipKayitlari
    AS SELECT t.*, NOW() AS YedekZamani, CAST('' AS CHAR(40)) AS Neden FROM TakipKayitlari t LIMIT 0;
  CREATE TABLE IF NOT EXISTS _yedek_KullaniciEnvanteri
    AS SELECT t.*, NOW() AS YedekZamani, CAST('' AS CHAR(40)) AS Neden FROM KullaniciEnvanteri t LIMIT 0;
  CREATE TABLE IF NOT EXISTS _yedek_KullaniciGunlukGorevleri
    AS SELECT t.*, NOW() AS YedekZamani, CAST('' AS CHAR(40)) AS Neden FROM KullaniciGunlukGorevleri t LIMIT 0;
  CREATE TABLE IF NOT EXISTS _yedek_Arkadasliklar
    AS SELECT t.*, NOW() AS YedekZamani, CAST('' AS CHAR(40)) AS Neden FROM Arkadasliklar t LIMIT 0;

  DROP TEMPORARY TABLE IF EXISTS cm_grup, cm_harita;

  START TRANSACTION;

  -- a. StatTuru yazımını standartlaştır (harf/aksan/sondaki boşluk farkı) --
  UPDATE KullaniciStatlari SET StatTuru = 'Zeka'        WHERE TRIM(StatTuru) = 'Zeka' AND (StatTuru COLLATE utf8mb4_0900_bin) <> 'Zeka';
  UPDATE KullaniciStatlari SET StatTuru = 'Guc'         WHERE TRIM(StatTuru) = 'Guc' AND (StatTuru COLLATE utf8mb4_0900_bin) <> 'Guc';
  UPDATE KullaniciStatlari SET StatTuru = 'Sosyallik'   WHERE TRIM(StatTuru) = 'Sosyallik' AND (StatTuru COLLATE utf8mb4_0900_bin) <> 'Sosyallik';
  UPDATE KullaniciStatlari SET StatTuru = 'Disiplin'    WHERE TRIM(StatTuru) = 'Disiplin' AND (StatTuru COLLATE utf8mb4_0900_bin) <> 'Disiplin';
  UPDATE KullaniciStatlari SET StatTuru = 'Yaraticilik' WHERE TRIM(StatTuru) = 'Yaraticilik' AND (StatTuru COLLATE utf8mb4_0900_bin) <> 'Yaraticilik';
  UPDATE Aliskanliklar     SET StatTuru = 'Zeka'        WHERE TRIM(StatTuru) = 'Zeka' AND (StatTuru COLLATE utf8mb4_0900_bin) <> 'Zeka';
  UPDATE Aliskanliklar     SET StatTuru = 'Guc'         WHERE TRIM(StatTuru) = 'Guc' AND (StatTuru COLLATE utf8mb4_0900_bin) <> 'Guc';
  UPDATE Aliskanliklar     SET StatTuru = 'Sosyallik'   WHERE TRIM(StatTuru) = 'Sosyallik' AND (StatTuru COLLATE utf8mb4_0900_bin) <> 'Sosyallik';
  UPDATE Aliskanliklar     SET StatTuru = 'Disiplin'    WHERE TRIM(StatTuru) = 'Disiplin' AND (StatTuru COLLATE utf8mb4_0900_bin) <> 'Disiplin';
  UPDATE Aliskanliklar     SET StatTuru = 'Yaraticilik' WHERE TRIM(StatTuru) = 'Yaraticilik' AND (StatTuru COLLATE utf8mb4_0900_bin) <> 'Yaraticilik';

  -- b. NULL puan ------------------------------------------------------------
  UPDATE KullaniciStatlari SET ToplamPuan = 0 WHERE ToplamPuan IS NULL;

  -- c. SonIslemTarihi saat bilgisi (kod yalnızca tarihi kullanır) ---------
  UPDATE KullaniciAliskanliklari SET SonIslemTarihi = DATE(SonIslemTarihi)
   WHERE SonIslemTarihi IS NOT NULL AND TIME(SonIslemTarihi) <> '00:00:00';

  -- d1. KullaniciStatlari tekrarları: en yüksek puanlı satır kalır ---------
  CREATE TEMPORARY TABLE cm_grup AS
  SELECT x.KullaniciID, x.StatTuru,
         (SELECT s.StatID FROM KullaniciStatlari s
           WHERE s.KullaniciID = x.KullaniciID AND s.StatTuru = x.StatTuru
           ORDER BY s.ToplamPuan DESC, s.StatID ASC LIMIT 1) AS Tut
    FROM KullaniciStatlari x
   GROUP BY x.KullaniciID, x.StatTuru
  HAVING COUNT(*) > 1;
  INSERT INTO _yedek_KullaniciStatlari
  SELECT t.*, @cm_zaman, 'tekrar' FROM KullaniciStatlari t
    JOIN cm_grup g ON g.KullaniciID = t.KullaniciID AND g.StatTuru = t.StatTuru AND t.StatID <> g.Tut;
  DELETE t FROM KullaniciStatlari t
    JOIN cm_grup g ON g.KullaniciID = t.KullaniciID AND g.StatTuru = t.StatTuru AND t.StatID <> g.Tut;
  DROP TEMPORARY TABLE cm_grup;

  -- d2. KullaniciAliskanliklari tekrarları ---------------------------------
  CREATE TEMPORARY TABLE cm_grup AS
  SELECT KullaniciID, AliskanlikID, MIN(KullaniciAliskanlikID) AS Tut,
         MAX(Seri) AS Seri, MAX(SonIslemTarihi) AS SonIslem, MAX(AktifMi) AS Aktif
    FROM KullaniciAliskanliklari
   GROUP BY KullaniciID, AliskanlikID
  HAVING COUNT(*) > 1;
  CREATE TEMPORARY TABLE cm_harita AS
  SELECT ka.KullaniciAliskanlikID AS Eski, g.Tut AS Yeni
    FROM KullaniciAliskanliklari ka
    JOIN cm_grup g ON g.KullaniciID = ka.KullaniciID AND g.AliskanlikID = ka.AliskanlikID
   WHERE ka.KullaniciAliskanlikID <> g.Tut;
  UPDATE KullaniciAliskanliklari ka JOIN cm_grup g ON g.Tut = ka.KullaniciAliskanlikID
     SET ka.Seri = g.Seri, ka.SonIslemTarihi = g.SonIslem, ka.AktifMi = g.Aktif;
  -- Takip kayıtlarını kalan satıra taşı (çakışan gün varsa yerinde kalır, aşağıda yedeklenip silinir)
  UPDATE IGNORE TakipKayitlari t JOIN cm_harita h ON h.Eski = t.KullaniciAliskanlikID
     SET t.KullaniciAliskanlikID = h.Yeni;
  INSERT INTO _yedek_TakipKayitlari
  SELECT t.*, @cm_zaman, 'tekrar-aliskanlik' FROM TakipKayitlari t
    JOIN cm_harita h ON h.Eski = t.KullaniciAliskanlikID;
  DELETE t FROM TakipKayitlari t JOIN cm_harita h ON h.Eski = t.KullaniciAliskanlikID;
  INSERT INTO _yedek_KullaniciAliskanliklari
  SELECT ka.*, @cm_zaman, 'tekrar' FROM KullaniciAliskanliklari ka
    JOIN cm_harita h ON h.Eski = ka.KullaniciAliskanlikID;
  DELETE ka FROM KullaniciAliskanliklari ka JOIN cm_harita h ON h.Eski = ka.KullaniciAliskanlikID;
  DROP TEMPORARY TABLE cm_grup, cm_harita;

  -- e. Sahipsiz satırlar ------------------------------------------------------
  -- Önce sahipsiz alışkanlıkların takip kayıtları (CASCADE'li bir FK varsa
  -- yedeksiz silinmesinler diye açıkça yedeklenip silinir).
  INSERT INTO _yedek_TakipKayitlari
  SELECT t.*, @cm_zaman, 'sahipsiz-aliskanlik' FROM TakipKayitlari t
    JOIN KullaniciAliskanliklari x ON x.KullaniciAliskanlikID = t.KullaniciAliskanlikID
    LEFT JOIN Kullanicilar k ON k.KullaniciID = x.KullaniciID
    LEFT JOIN Aliskanliklar a ON a.AliskanlikID = x.AliskanlikID
   WHERE k.KullaniciID IS NULL OR a.AliskanlikID IS NULL;
  DELETE t FROM TakipKayitlari t
    JOIN KullaniciAliskanliklari x ON x.KullaniciAliskanlikID = t.KullaniciAliskanlikID
    LEFT JOIN Kullanicilar k ON k.KullaniciID = x.KullaniciID
    LEFT JOIN Aliskanliklar a ON a.AliskanlikID = x.AliskanlikID
   WHERE k.KullaniciID IS NULL OR a.AliskanlikID IS NULL;

  INSERT INTO _yedek_KullaniciAliskanliklari
  SELECT x.*, @cm_zaman, 'sahipsiz' FROM KullaniciAliskanliklari x
    LEFT JOIN Kullanicilar k ON k.KullaniciID = x.KullaniciID
    LEFT JOIN Aliskanliklar a ON a.AliskanlikID = x.AliskanlikID
   WHERE k.KullaniciID IS NULL OR a.AliskanlikID IS NULL;
  DELETE x FROM KullaniciAliskanliklari x
    LEFT JOIN Kullanicilar k ON k.KullaniciID = x.KullaniciID
    LEFT JOIN Aliskanliklar a ON a.AliskanlikID = x.AliskanlikID
   WHERE k.KullaniciID IS NULL OR a.AliskanlikID IS NULL;

  INSERT INTO _yedek_TakipKayitlari
  SELECT t.*, @cm_zaman, 'sahipsiz' FROM TakipKayitlari t
    LEFT JOIN KullaniciAliskanliklari ka ON ka.KullaniciAliskanlikID = t.KullaniciAliskanlikID
   WHERE ka.KullaniciAliskanlikID IS NULL;
  DELETE t FROM TakipKayitlari t
    LEFT JOIN KullaniciAliskanliklari ka ON ka.KullaniciAliskanlikID = t.KullaniciAliskanlikID
   WHERE ka.KullaniciAliskanlikID IS NULL;

  INSERT INTO _yedek_KullaniciStatlari
  SELECT x.*, @cm_zaman, 'sahipsiz' FROM KullaniciStatlari x
    LEFT JOIN Kullanicilar k ON k.KullaniciID = x.KullaniciID WHERE k.KullaniciID IS NULL;
  DELETE x FROM KullaniciStatlari x
    LEFT JOIN Kullanicilar k ON k.KullaniciID = x.KullaniciID WHERE k.KullaniciID IS NULL;

  INSERT INTO _yedek_KullaniciEnvanteri
  SELECT x.*, @cm_zaman, 'sahipsiz' FROM KullaniciEnvanteri x
    LEFT JOIN Kullanicilar k ON k.KullaniciID = x.KullaniciID
    LEFT JOIN MarketEsyalari m ON m.EsyaID = x.EsyaID
   WHERE k.KullaniciID IS NULL OR m.EsyaID IS NULL;
  DELETE x FROM KullaniciEnvanteri x
    LEFT JOIN Kullanicilar k ON k.KullaniciID = x.KullaniciID
    LEFT JOIN MarketEsyalari m ON m.EsyaID = x.EsyaID
   WHERE k.KullaniciID IS NULL OR m.EsyaID IS NULL;

  INSERT INTO _yedek_KullaniciGunlukGorevleri
  SELECT x.*, @cm_zaman, 'sahipsiz' FROM KullaniciGunlukGorevleri x
    LEFT JOIN Kullanicilar k ON k.KullaniciID = x.KullaniciID
    LEFT JOIN GorevTanimlari g ON g.GorevTanimID = x.GorevTanimID
   WHERE k.KullaniciID IS NULL OR g.GorevTanimID IS NULL;
  DELETE x FROM KullaniciGunlukGorevleri x
    LEFT JOIN Kullanicilar k ON k.KullaniciID = x.KullaniciID
    LEFT JOIN GorevTanimlari g ON g.GorevTanimID = x.GorevTanimID
   WHERE k.KullaniciID IS NULL OR g.GorevTanimID IS NULL;

  INSERT INTO _yedek_Arkadasliklar
  SELECT x.*, @cm_zaman, 'sahipsiz' FROM Arkadasliklar x
    LEFT JOIN Kullanicilar g ON g.KullaniciID = x.IstekGonderenID
    LEFT JOIN Kullanicilar a ON a.KullaniciID = x.IstekAlanID
   WHERE g.KullaniciID IS NULL OR a.KullaniciID IS NULL;
  DELETE x FROM Arkadasliklar x
    LEFT JOIN Kullanicilar g ON g.KullaniciID = x.IstekGonderenID
    LEFT JOIN Kullanicilar a ON a.KullaniciID = x.IstekAlanID
   WHERE g.KullaniciID IS NULL OR a.KullaniciID IS NULL;

  -- d3. TakipKayitlari tekrarları -------------------------------------------
  CREATE TEMPORARY TABLE cm_grup AS
  SELECT KullaniciAliskanlikID, Tarih, MIN(KayitID) AS Tut, MAX(TamamlandiMi) AS Tamam
    FROM TakipKayitlari GROUP BY KullaniciAliskanlikID, Tarih HAVING COUNT(*) > 1;
  UPDATE TakipKayitlari t JOIN cm_grup g ON g.Tut = t.KayitID SET t.TamamlandiMi = g.Tamam;
  INSERT INTO _yedek_TakipKayitlari
  SELECT t.*, @cm_zaman, 'tekrar' FROM TakipKayitlari t
    JOIN cm_grup g ON g.KullaniciAliskanlikID = t.KullaniciAliskanlikID AND g.Tarih = t.Tarih AND t.KayitID <> g.Tut;
  DELETE t FROM TakipKayitlari t
    JOIN cm_grup g ON g.KullaniciAliskanlikID = t.KullaniciAliskanlikID AND g.Tarih = t.Tarih AND t.KayitID <> g.Tut;
  DROP TEMPORARY TABLE cm_grup;

  -- d4. KullaniciEnvanteri tekrarları ----------------------------------------
  CREATE TEMPORARY TABLE cm_grup AS
  SELECT KullaniciID, EsyaID, MIN(EnvanterID) AS Tut, MAX(KusandiMi) AS Kusandi
    FROM KullaniciEnvanteri GROUP BY KullaniciID, EsyaID HAVING COUNT(*) > 1;
  UPDATE KullaniciEnvanteri t JOIN cm_grup g ON g.Tut = t.EnvanterID SET t.KusandiMi = g.Kusandi;
  INSERT INTO _yedek_KullaniciEnvanteri
  SELECT t.*, @cm_zaman, 'tekrar' FROM KullaniciEnvanteri t
    JOIN cm_grup g ON g.KullaniciID = t.KullaniciID AND g.EsyaID = t.EsyaID AND t.EnvanterID <> g.Tut;
  DELETE t FROM KullaniciEnvanteri t
    JOIN cm_grup g ON g.KullaniciID = t.KullaniciID AND g.EsyaID = t.EsyaID AND t.EnvanterID <> g.Tut;
  DROP TEMPORARY TABLE cm_grup;

  -- d5. KullaniciGunlukGorevleri tekrarları ----------------------------------
  CREATE TEMPORARY TABLE cm_grup AS
  SELECT KullaniciID, Tarih, GorevTanimID, MIN(KullaniciGorevID) AS Tut,
         MAX(MevcutIlerleme) AS Ilerleme, MAX(TamamlandiMi) AS Tamam, MAX(OdulAlindiMi) AS Odul
    FROM KullaniciGunlukGorevleri GROUP BY KullaniciID, Tarih, GorevTanimID HAVING COUNT(*) > 1;
  UPDATE KullaniciGunlukGorevleri t JOIN cm_grup g ON g.Tut = t.KullaniciGorevID
     SET t.MevcutIlerleme = g.Ilerleme, t.TamamlandiMi = g.Tamam, t.OdulAlindiMi = g.Odul;
  INSERT INTO _yedek_KullaniciGunlukGorevleri
  SELECT t.*, @cm_zaman, 'tekrar' FROM KullaniciGunlukGorevleri t
    JOIN cm_grup g ON g.KullaniciID = t.KullaniciID AND g.Tarih = t.Tarih AND g.GorevTanimID = t.GorevTanimID
     AND t.KullaniciGorevID <> g.Tut;
  DELETE t FROM KullaniciGunlukGorevleri t
    JOIN cm_grup g ON g.KullaniciID = t.KullaniciID AND g.Tarih = t.Tarih AND g.GorevTanimID = t.GorevTanimID
     AND t.KullaniciGorevID <> g.Tut;
  DROP TEMPORARY TABLE cm_grup;

  -- d6. Arkadasliklar tekrarları (A->B ve B->A aynı çift) --------------------
  CREATE TEMPORARY TABLE cm_grup AS
  SELECT LEAST(IstekGonderenID, IstekAlanID) AS Kucuk, GREATEST(IstekGonderenID, IstekAlanID) AS Buyuk,
         MIN(ArkadaslikID) AS Tut, MAX(Durum) AS Durum
    FROM Arkadasliklar
   GROUP BY LEAST(IstekGonderenID, IstekAlanID), GREATEST(IstekGonderenID, IstekAlanID)
  HAVING COUNT(*) > 1;
  UPDATE Arkadasliklar t JOIN cm_grup g ON g.Tut = t.ArkadaslikID SET t.Durum = g.Durum;
  INSERT INTO _yedek_Arkadasliklar
  SELECT t.*, @cm_zaman, 'tekrar' FROM Arkadasliklar t
    JOIN cm_grup g ON g.Kucuk = LEAST(t.IstekGonderenID, t.IstekAlanID)
                  AND g.Buyuk = GREATEST(t.IstekGonderenID, t.IstekAlanID) AND t.ArkadaslikID <> g.Tut;
  DELETE t FROM Arkadasliklar t
    JOIN cm_grup g ON g.Kucuk = LEAST(t.IstekGonderenID, t.IstekAlanID)
                  AND g.Buyuk = GREATEST(t.IstekGonderenID, t.IstekAlanID) AND t.ArkadaslikID <> g.Tut;
  DROP TEMPORARY TABLE cm_grup;

  COMMIT;

  -- Özet: bu çalıştırmada yedeklenip silinen satırlar -----------------------
  SELECT 'KullaniciStatlari' AS Tablo, Neden, COUNT(*) AS Silinen FROM _yedek_KullaniciStatlari WHERE YedekZamani = @cm_zaman GROUP BY Neden
  UNION ALL SELECT 'KullaniciAliskanliklari', Neden, COUNT(*) FROM _yedek_KullaniciAliskanliklari WHERE YedekZamani = @cm_zaman GROUP BY Neden
  UNION ALL SELECT 'TakipKayitlari', Neden, COUNT(*) FROM _yedek_TakipKayitlari WHERE YedekZamani = @cm_zaman GROUP BY Neden
  UNION ALL SELECT 'KullaniciEnvanteri', Neden, COUNT(*) FROM _yedek_KullaniciEnvanteri WHERE YedekZamani = @cm_zaman GROUP BY Neden
  UNION ALL SELECT 'KullaniciGunlukGorevleri', Neden, COUNT(*) FROM _yedek_KullaniciGunlukGorevleri WHERE YedekZamani = @cm_zaman GROUP BY Neden
  UNION ALL SELECT 'Arkadasliklar', Neden, COUNT(*) FROM _yedek_Arkadasliklar WHERE YedekZamani = @cm_zaman GROUP BY Neden;

  -- Standartlaştırılamayan StatTuru değerleri (elle düzeltilmeli) ---------
  SELECT 'KullaniciStatlari' AS Tablo, StatID AS ID, StatTuru FROM KullaniciStatlari
   WHERE NOT ((StatTuru COLLATE utf8mb4_0900_bin) IN ('Zeka','Guc','Sosyallik','Disiplin','Yaraticilik'))
  UNION ALL
  SELECT 'Aliskanliklar', AliskanlikID, StatTuru FROM Aliskanliklar
   WHERE StatTuru IS NULL OR NOT ((StatTuru COLLATE utf8mb4_0900_bin) IN ('Zeka','Guc','Sosyallik','Disiplin','Yaraticilik'));

END$$
DELIMITER ;

CALL cm_temizle();

DROP PROCEDURE IF EXISTS cm_temizle;
DROP TEMPORARY TABLE IF EXISTS cm_temizle_kapi;
SET @onay = NULL;
