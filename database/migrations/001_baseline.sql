-- =====================================================================
-- 001_baseline.sql  —  Charmoji başlangıç şeması
-- ---------------------------------------------------------------------
-- Amaç : Bugünkü PHP kodunun (service/*.php, view/*.php) beklediği şemayı
--        OLDUĞU GİBİ tanımlamak. Gerçek veritabanının dökümü elimizde
--        olmadığı için tablo/sütunlar koddaki sorgulardan türetildi.
--        Tek kesin kanıt: repoda takipli kalmış kullanicistatlari.ibd
--        dosyasından (ibd2sdi ile) okunan KullaniciStatlari tanımı; o
--        tablo burada birebir öyle yazıldı (StatTuru VARCHAR(20),
--        ToplamPuan DEFAULT 10, UQ_Stat_User, FK_Stat_Kullanici CASCADE,
--        utf8mb4_unicode_ci).
--
-- Güvenlik: Mevcut bir kurulumda zarar vermeden çalışır.
--   * Tüm tablolar CREATE TABLE IF NOT EXISTS ile oluşturulur; var olan
--     tabloya ve verisine dokunulmaz.
--   * Kodun kullandığı ama eski şema notunda olmayan tek sütun
--     (Kullanicilar.AvatarVerisi) yoksa eklenir; varsa dokunulmaz.
--   * Hiçbir satır silinmez, hiçbir sütun değiştirilmez.
--   * Tekrar tekrar çalıştırılabilir (idempotent).
--
-- Bilerek EKLENMEYENLER (002'nin işi): eksik UNIQUE anahtarlar, eksik
-- FOREIGN KEY'ler, indeksler, StatTuru kısıtı.
--
-- Not: Eski şema notunda olup kodun hiç kullanmadığı sütunlar
-- (Kullanicilar.AvatarURL/Seviye/XP, Aliskanliklar.Aciklama/
-- VarsayilanHedef/HedefTipi, KullaniciAliskanliklari.OzelHedefDegeri,
-- TakipKayitlari.GerceklesenDeger, KullaniciEnvanteri.AlimTarihi)
-- sıfırdan kurulumda oluşturulmaz. Mevcut kurulumda varlarsa olduğu
-- gibi kalırlar.
-- =====================================================================

SET NAMES utf8mb4;

-- Uygulanan migration'ların kaydı -------------------------------------
CREATE TABLE IF NOT EXISTS SemaGecmisi (
  Surum           VARCHAR(20)  NOT NULL COMMENT 'Migration numarası: 001, 002 ...',
  Aciklama        VARCHAR(255) NOT NULL,
  UygulanmaZamani DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (Surum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Hangi migration dosyalarının uygulandığını tutar';

-- 1. Kullanıcılar ------------------------------------------------------
-- auth.php (kayıt/giriş), ajax_islem.php (Bakiye, AvatarVerisi),
-- userpage.php, market.php, avatar.php, friendmanager.php
CREATE TABLE IF NOT EXISTS Kullanicilar (
  KullaniciID  INT           NOT NULL AUTO_INCREMENT,
  Ad           VARCHAR(100)  NOT NULL,
  Eposta       VARCHAR(100)  NOT NULL,
  SifreHash    VARCHAR(255)  NOT NULL COMMENT 'PHP password_hash() çıktısı',
  KayitTarihi  DATETIME      DEFAULT CURRENT_TIMESTAMP,
  Bakiye       DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Altın',
  AvatarVerisi TEXT          NULL COMMENT 'Avatar renkleri, JSON metni (skinColor, hairColor, eyeColor, topColor)',
  PRIMARY KEY (KullaniciID),
  UNIQUE KEY Eposta (Eposta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Kullanıcı statları (repodaki .ibd dosyasından okunan gerçek tanım) -
-- userpage.php: INSERT IGNORE ... (KullaniciID, StatTuru) -> UQ_Stat_User
CREATE TABLE IF NOT EXISTS KullaniciStatlari (
  StatID      INT         NOT NULL AUTO_INCREMENT,
  KullaniciID INT         NOT NULL,
  StatTuru    VARCHAR(20) NOT NULL,
  ToplamPuan  INT         DEFAULT 10,
  PRIMARY KEY (StatID),
  UNIQUE KEY UQ_Stat_User (KullaniciID, StatTuru),
  CONSTRAINT FK_Stat_Kullanici FOREIGN KEY (KullaniciID)
    REFERENCES Kullanicilar (KullaniciID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Ortak alışkanlık tablosu (003 ile kullanıcıya özel yapıya taşınır) --
-- userpage.php: SELECT ... WHERE Ad = ? / INSERT (Ad, StatTuru, Puan)
CREATE TABLE IF NOT EXISTS Aliskanliklar (
  AliskanlikID INT          NOT NULL AUTO_INCREMENT,
  Ad           VARCHAR(100) NOT NULL,
  StatTuru     VARCHAR(50)  DEFAULT NULL,
  Puan         INT          DEFAULT 10,
  PRIMARY KEY (AliskanlikID),
  UNIQUE KEY Ad (Ad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Kullanıcının takip ettiği alışkanlıklar ---------------------------
-- SonIslemTarihi: kod '$bugun' (Y-m-d) yazar ve '$dunkuTarih' ile
-- string karşılaştırır; DATETIME olursa seri hiç artmaz. Bu yüzden DATE.
CREATE TABLE IF NOT EXISTS KullaniciAliskanliklari (
  KullaniciAliskanlikID INT        NOT NULL AUTO_INCREMENT,
  KullaniciID           INT        NOT NULL,
  AliskanlikID          INT        NOT NULL,
  BaslangicTarihi       DATE       NOT NULL,
  AktifMi               TINYINT(1) NOT NULL DEFAULT 1,
  Seri                  INT        NOT NULL DEFAULT 0,
  SonIslemTarihi        DATE       DEFAULT NULL,
  PRIMARY KEY (KullaniciAliskanlikID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Günlük işaretleme kayıtları ----------------------------------------
CREATE TABLE IF NOT EXISTS TakipKayitlari (
  KayitID               INT        NOT NULL AUTO_INCREMENT,
  KullaniciAliskanlikID INT        NOT NULL,
  Tarih                 DATE       NOT NULL,
  TamamlandiMi          TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (KayitID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Arkadaşlıklar (eski nottaki `arkadaslar` DEĞİL; kod bunu kullanır) --
CREATE TABLE IF NOT EXISTS Arkadasliklar (
  ArkadaslikID    INT     NOT NULL AUTO_INCREMENT,
  IstekGonderenID INT     NOT NULL,
  IstekAlanID     INT     NOT NULL,
  Durum           TINYINT NOT NULL DEFAULT 0 COMMENT '0 = beklemede, 1 = kabul edildi (reddedilen istek silinir)',
  PRIMARY KEY (ArkadaslikID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Market eşyaları -----------------------------------------------------
-- Ad: avatar_renderer.php eşyayı adıyla çizer. GorselURL: Font Awesome
-- sınıfı (ör. fa-hat-wizard). EsyaTuru: Şapka / Gözlük / Maske / Kolye.
CREATE TABLE IF NOT EXISTS MarketEsyalari (
  EsyaID        INT           NOT NULL AUTO_INCREMENT,
  Ad            VARCHAR(100)  NOT NULL,
  Aciklama      VARCHAR(255)  DEFAULT NULL,
  Fiyat         DECIMAL(10,2) NOT NULL,
  GorselURL     VARCHAR(255)  DEFAULT NULL COMMENT 'Font Awesome ikon sınıfı',
  EsyaTuru      VARCHAR(50)   DEFAULT NULL,
  GerekenSeviye INT           NOT NULL DEFAULT 1,
  PRIMARY KEY (EsyaID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Kullanıcı envanteri -------------------------------------------------
CREATE TABLE IF NOT EXISTS KullaniciEnvanteri (
  EnvanterID  INT        NOT NULL AUTO_INCREMENT,
  KullaniciID INT        NOT NULL,
  EsyaID      INT        NOT NULL,
  KusandiMi   TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (EnvanterID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Günlük görev tanımları ---------------------------------------------
-- Kod başlığa göre arar: Baslik = 'Günlük Giriş' ve Baslik LIKE '%alışkanlık%'
CREATE TABLE IF NOT EXISTS GorevTanimlari (
  GorevTanimID INT           NOT NULL AUTO_INCREMENT,
  Baslik       VARCHAR(100)  NOT NULL,
  Aciklama     TEXT          NULL,
  OdulPara     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  HedefSayi    INT           NOT NULL DEFAULT 1,
  Ikon         VARCHAR(100)  DEFAULT NULL COMMENT 'Font Awesome ikon sınıfı',
  PRIMARY KEY (GorevTanimID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Kullanıcının o günkü görevleri ------------------------------------
CREATE TABLE IF NOT EXISTS KullaniciGunlukGorevleri (
  KullaniciGorevID INT        NOT NULL AUTO_INCREMENT,
  KullaniciID      INT        NOT NULL,
  GorevTanimID     INT        NOT NULL,
  Tarih            DATE       NOT NULL,
  MevcutIlerleme   INT        NOT NULL DEFAULT 0,
  TamamlandiMi     TINYINT(1) NOT NULL DEFAULT 0,
  OdulAlindiMi     TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (KullaniciGorevID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mevcut kurulumda AvatarVerisi yoksa ekle (varsa dokunma) -------------
DROP PROCEDURE IF EXISTS cm001_avatar_sutunu;
DELIMITER $$
CREATE PROCEDURE cm001_avatar_sutunu()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND LOWER(TABLE_NAME) = 'kullanicilar'
       AND COLUMN_NAME = 'AvatarVerisi'
  ) THEN
    ALTER TABLE Kullanicilar
      ADD COLUMN AvatarVerisi TEXT NULL
      COMMENT 'Avatar renkleri, JSON metni (skinColor, hairColor, eyeColor, topColor)';
  END IF;
END$$
DELIMITER ;
CALL cm001_avatar_sutunu();
DROP PROCEDURE IF EXISTS cm001_avatar_sutunu;

INSERT IGNORE INTO SemaGecmisi (Surum, Aciklama)
VALUES ('001', 'Başlangıç şeması (koddan türetildi)');
