-- =====================================================================
-- schema.sql  —  Charmoji şeması, SIFIRDAN KURULUM için tek dosya
-- ---------------------------------------------------------------------
-- İçerik: migrations/001 + 002 + 003 uygulandıktan sonraki şemanın
-- birebir aynısı (mysqldump --no-data --routines --triggers ile karşılaştırılarak doğrulandı;
-- bkz. database/README.md).
--
-- Bilerek İÇERİLEN geçiş parçaları (araclar/004 ile kaldırılacak):
--   * Aliskanliklar (eski ortak tablo), KullaniciAliskanliklari.AliskanlikID,
--     FK_KA_Aliskanlik, UQ_KA_Kullanici_Aliskanlik, TRG_KA_EskiKodUyumu.
--   Bunlar, PHP kodu kullanıcıya özel alışkanlıklara geçene kadar mevcut
--   kodun sıfırdan kurulumda da çalışması için tutuluyor. PHP güncellenip
--   004 uygulandıktan sonra bu dosya da 004 sonrası hâle getirilmeli.
--
-- StatTuru kısıtı: CHECK + utf8mb4_0900_bin (ENUM değil). Gerekçe:
--   * Sütun VARCHAR kalır; PHP tarafı düz metin karşılaştırmaya devam eder
--     ('Guc' == ...), sorgularda değişiklik gerekmez.
--   * utf8mb4_unicode_ci büyük/küçük harf ve aksan duyarsızdır: 'güç',
--     'GUC' gibi değerler eşit sayılır. ENUM bunları sessizce 'Guc'a
--     çevirir, CHECK ise ikili (birebir) karşılaştırma ile reddeder.
--     userpage.php $barData[$row['StatTuru']] gibi birebir anahtar
--     kullandığı için yanlış yazımın hiç yazılamaması tercih edildi.
--   * ENUM, sql_mode katı değilse geçersiz değeri '' olarak saklar;
--     CHECK sql_mode'dan bağımsız olarak hata verir (INSERT IGNORE'da
--     satır eklenmez).
--   * Yeni stat eklemek ENUM'da tabloyu yeniden yazan ALTER olabilir;
--     CHECK'te kısıtı düşürüp yeniden eklemek yeterli.
--   * utf8mb4_0900_bin seçildi (utf8mb4_bin değil): NO PAD olduğu için
--     sondaki boşluk da fark sayılır; 'Guc ' reddedilir.
--   * MySQL 8.0.17+ gerekir (CHECK 8.0.16, utf8mb4_0900_bin 8.0.17).
--
-- Kullanım:
--   mysql -uroot -e "CREATE DATABASE charmoji CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
--   mysql -uroot --default-character-set=utf8mb4 charmoji < database/schema.sql
-- =====================================================================

SET NAMES utf8mb4;

CREATE TABLE SemaGecmisi (
  Surum           VARCHAR(20)  NOT NULL COMMENT 'Migration numarası: 001, 002 ...',
  Aciklama        VARCHAR(255) NOT NULL,
  UygulanmaZamani DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (Surum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Hangi migration dosyalarının uygulandığını tutar';

-- Kullanıcılar ----------------------------------------------------------
CREATE TABLE Kullanicilar (
  KullaniciID  INT           NOT NULL AUTO_INCREMENT,
  Ad           VARCHAR(100)  NOT NULL,
  Eposta       VARCHAR(100)  NOT NULL,
  SifreHash    VARCHAR(255)  NOT NULL COMMENT 'PHP password_hash() çıktısı',
  KayitTarihi  DATETIME      DEFAULT CURRENT_TIMESTAMP,
  Bakiye       DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Altın',
  AvatarVerisi TEXT          NULL COMMENT 'Avatar renkleri, JSON metni (skinColor, hairColor, eyeColor, topColor)',
  PRIMARY KEY (KullaniciID),
  UNIQUE KEY Eposta (Eposta),
  KEY IDX_Kullanici_Ad (Ad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Statlar: kullanıcı başına her stat türünden tek satır -----------------
CREATE TABLE KullaniciStatlari (
  StatID      INT         NOT NULL AUTO_INCREMENT,
  KullaniciID INT         NOT NULL,
  StatTuru    VARCHAR(20) NOT NULL,
  ToplamPuan  INT         NOT NULL DEFAULT 0,
  PRIMARY KEY (StatID),
  UNIQUE KEY UQ_Stat_User (KullaniciID, StatTuru),
  CONSTRAINT FK_Stat_Kullanici FOREIGN KEY (KullaniciID)
    REFERENCES Kullanicilar (KullaniciID) ON DELETE CASCADE,
  CONSTRAINT CHK_KS_StatTuru
    CHECK ((StatTuru COLLATE utf8mb4_0900_bin) IN ('Zeka','Guc','Sosyallik','Disiplin','Yaraticilik'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ESKİ ortak alışkanlık tablosu (geçiş için; araclar/004 ile kalkar) ----
CREATE TABLE Aliskanliklar (
  AliskanlikID INT          NOT NULL AUTO_INCREMENT,
  Ad           VARCHAR(100) NOT NULL,
  StatTuru     VARCHAR(50)  DEFAULT NULL,
  Puan         INT          DEFAULT 10,
  PRIMARY KEY (AliskanlikID),
  UNIQUE KEY Ad (Ad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kullanıcıya özel alışkanlıklar ----------------------------------------
CREATE TABLE KullaniciAliskanliklari (
  KullaniciAliskanlikID INT          NOT NULL AUTO_INCREMENT,
  KullaniciID           INT          NOT NULL,
  AliskanlikID          INT          NULL COMMENT 'ESKI: ortak Aliskanliklar tablosu; 004 ile kaldirilir, yeni kod doldurmaz',
  Ad                    VARCHAR(100) NOT NULL COMMENT 'Kullaniciya ozel aliskanlik adi',
  StatTuru              VARCHAR(20)  NOT NULL COMMENT 'Zeka, Guc, Sosyallik, Disiplin, Yaraticilik',
  Puan                  INT          NOT NULL DEFAULT 10 COMMENT 'Isaretleme basina taban puan',
  BaslangicTarihi       DATE         NOT NULL,
  AktifMi               TINYINT(1)   NOT NULL DEFAULT 1,
  Seri                  INT          NOT NULL DEFAULT 0,
  SonIslemTarihi        DATE         DEFAULT NULL,
  PRIMARY KEY (KullaniciAliskanlikID),
  UNIQUE KEY UQ_KA_Kullanici_Ad (KullaniciID, Ad),
  UNIQUE KEY UQ_KA_Kullanici_Aliskanlik (KullaniciID, AliskanlikID),
  KEY FK_KA_Aliskanlik (AliskanlikID),
  CONSTRAINT FK_KA_Aliskanlik FOREIGN KEY (AliskanlikID)
    REFERENCES Aliskanliklar (AliskanlikID) ON DELETE RESTRICT,
  CONSTRAINT FK_KA_Kullanici FOREIGN KEY (KullaniciID)
    REFERENCES Kullanicilar (KullaniciID) ON DELETE CASCADE,
  CONSTRAINT CHK_KA_StatTuru
    CHECK ((StatTuru COLLATE utf8mb4_0900_bin) IN ('Zeka','Guc','Sosyallik','Disiplin','Yaraticilik'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Günlük işaretleme kayıtları: alışkanlık başına günde tek satır -------
CREATE TABLE TakipKayitlari (
  KayitID               INT        NOT NULL AUTO_INCREMENT,
  KullaniciAliskanlikID INT        NOT NULL,
  Tarih                 DATE       NOT NULL,
  TamamlandiMi          TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (KayitID),
  UNIQUE KEY UQ_TK_KA_Tarih (KullaniciAliskanlikID, Tarih),
  CONSTRAINT FK_TK_KA FOREIGN KEY (KullaniciAliskanlikID)
    REFERENCES KullaniciAliskanliklari (KullaniciAliskanlikID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Arkadaşlıklar: iki kişi arasında (yönden bağımsız) tek satır ---------
CREATE TABLE Arkadasliklar (
  ArkadaslikID    INT     NOT NULL AUTO_INCREMENT,
  IstekGonderenID INT     NOT NULL,
  IstekAlanID     INT     NOT NULL,
  Durum           TINYINT NOT NULL DEFAULT 0 COMMENT '0 = beklemede, 1 = kabul edildi (reddedilen istek silinir)',
  PRIMARY KEY (ArkadaslikID),
  UNIQUE KEY UQ_Ark_Cift ((LEAST(IstekGonderenID, IstekAlanID)), (GREATEST(IstekGonderenID, IstekAlanID))),
  KEY IDX_Ark_Alan_Durum (IstekAlanID, Durum),
  KEY FK_Ark_Gonderen (IstekGonderenID),
  CONSTRAINT FK_Ark_Alan FOREIGN KEY (IstekAlanID)
    REFERENCES Kullanicilar (KullaniciID) ON DELETE CASCADE,
  CONSTRAINT FK_Ark_Gonderen FOREIGN KEY (IstekGonderenID)
    REFERENCES Kullanicilar (KullaniciID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Market eşyaları --------------------------------------------------------
CREATE TABLE MarketEsyalari (
  EsyaID        INT           NOT NULL AUTO_INCREMENT,
  Ad            VARCHAR(100)  NOT NULL,
  Aciklama      VARCHAR(255)  DEFAULT NULL,
  Fiyat         DECIMAL(10,2) NOT NULL,
  GorselURL     VARCHAR(255)  DEFAULT NULL COMMENT 'Font Awesome ikon sınıfı',
  EsyaTuru      VARCHAR(50)   DEFAULT NULL,
  GerekenSeviye INT           NOT NULL DEFAULT 1,
  PRIMARY KEY (EsyaID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Envanter: kullanıcı bir eşyaya en fazla bir kez sahip olur -----------
CREATE TABLE KullaniciEnvanteri (
  EnvanterID  INT        NOT NULL AUTO_INCREMENT,
  KullaniciID INT        NOT NULL,
  EsyaID      INT        NOT NULL,
  KusandiMi   TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (EnvanterID),
  UNIQUE KEY UQ_Env_Kullanici_Esya (KullaniciID, EsyaID),
  KEY FK_Env_Esya (EsyaID),
  CONSTRAINT FK_Env_Esya FOREIGN KEY (EsyaID)
    REFERENCES MarketEsyalari (EsyaID) ON DELETE RESTRICT,
  CONSTRAINT FK_Env_Kullanici FOREIGN KEY (KullaniciID)
    REFERENCES Kullanicilar (KullaniciID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Görev tanımları ---------------------------------------------------------
CREATE TABLE GorevTanimlari (
  GorevTanimID INT           NOT NULL AUTO_INCREMENT,
  Baslik       VARCHAR(100)  NOT NULL,
  Aciklama     TEXT          NULL,
  OdulPara     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  HedefSayi    INT           NOT NULL DEFAULT 1,
  Ikon         VARCHAR(100)  DEFAULT NULL COMMENT 'Font Awesome ikon sınıfı',
  PRIMARY KEY (GorevTanimID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kullanıcının günlük görevleri: kullanıcı+gün+görev başına tek satır --
CREATE TABLE KullaniciGunlukGorevleri (
  KullaniciGorevID INT        NOT NULL AUTO_INCREMENT,
  KullaniciID      INT        NOT NULL,
  GorevTanimID     INT        NOT NULL,
  Tarih            DATE       NOT NULL,
  MevcutIlerleme   INT        NOT NULL DEFAULT 0,
  TamamlandiMi     TINYINT(1) NOT NULL DEFAULT 0,
  OdulAlindiMi     TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (KullaniciGorevID),
  UNIQUE KEY UQ_KGG_Kullanici_Tarih_Gorev (KullaniciID, Tarih, GorevTanimID),
  KEY FK_KGG_Tanim (GorevTanimID),
  CONSTRAINT FK_KGG_Kullanici FOREIGN KEY (KullaniciID)
    REFERENCES Kullanicilar (KullaniciID) ON DELETE CASCADE,
  CONSTRAINT FK_KGG_Tanim FOREIGN KEY (GorevTanimID)
    REFERENCES GorevTanimlari (GorevTanimID) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Geçiş uyumluluğu tetikleyicisi (bkz. 003; araclar/004 ile kalkar) ----
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

-- Bu şema 001-003 migration'larına denktir; sonraki migration'lar
-- doğru yerden başlasın diye kayıtlar eklenir.
INSERT INTO SemaGecmisi (Surum, Aciklama) VALUES
  ('001', 'Başlangıç şeması (koddan türetildi)'),
  ('002', 'Eksik UNIQUE/FK/CHECK kisitlari ve indeksler'),
  ('003', 'Aliskanliklar kullaniciya ozel: Ad/StatTuru/Puan KullaniciAliskanliklari tablosunda');
