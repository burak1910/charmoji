-- tests/guvenlik/sema_test.sql
-- UYARI: Bu dosya YALNIZCA güvenlik doğrulama betikleri içindir.
-- Asıl şema database/ altındadır (veritabanı ajanı tarafından yazılır);
-- bu dosyayı uygulama şeması olarak kullanmayın.
--
-- Temel: style/"sql kodları" notu + kodun gerçekte kullandığı farklar
-- (Kullanicilar.AvatarVerisi, Arkadasliklar tablosu, INSERT IGNORE için
-- gereken UNIQUE anahtarlar, 'Günlük Giriş' görevi, örnek market eşyaları).
-- Betik, çalıştırıldığı veritabanındaki tabloları SİLİP yeniden kurar.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS KullaniciGunlukGorevleri, GorevTanimlari, KullaniciEnvanteri,
    MarketEsyalari, Arkadasliklar, TakipKayitlari, KullaniciAliskanliklari,
    Aliskanliklar, KullaniciStatlari, Kullanicilar, MarketEsyalari_gecici;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE Kullanicilar (
    KullaniciID INT AUTO_INCREMENT PRIMARY KEY,
    Ad VARCHAR(100) NOT NULL,
    Eposta VARCHAR(100) NOT NULL UNIQUE,
    SifreHash VARCHAR(255) NOT NULL,
    KayitTarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    AvatarURL VARCHAR(255) DEFAULT 'default.png',
    AvatarVerisi TEXT NULL,
    Seviye INT DEFAULT 1,
    XP INT DEFAULT 0,
    Bakiye DECIMAL(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE KullaniciStatlari (
    StatID INT AUTO_INCREMENT PRIMARY KEY,
    KullaniciID INT NOT NULL,
    StatTuru VARCHAR(50) NOT NULL,
    ToplamPuan INT DEFAULT 0,
    UNIQUE KEY UQ_Stat (KullaniciID, StatTuru),
    CONSTRAINT FK_Stat_Kullanici FOREIGN KEY (KullaniciID)
        REFERENCES Kullanicilar(KullaniciID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Aliskanliklar (
    AliskanlikID INT AUTO_INCREMENT PRIMARY KEY,
    Ad VARCHAR(100) UNIQUE NOT NULL,
    Aciklama TEXT,
    VarsayilanHedef VARCHAR(50),
    HedefTipi VARCHAR(20),
    StatTuru VARCHAR(50),
    Puan INT DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE KullaniciAliskanliklari (
    KullaniciAliskanlikID INT AUTO_INCREMENT PRIMARY KEY,
    KullaniciID INT NOT NULL,
    AliskanlikID INT NOT NULL,
    OzelHedefDegeri VARCHAR(50),
    BaslangicTarihi DATE NOT NULL,
    AktifMi TINYINT(1) DEFAULT 1,
    Seri INT DEFAULT 0,
    SonIslemTarihi DATE NULL,
    UNIQUE KEY UQ_KA (KullaniciID, AliskanlikID),
    CONSTRAINT FK_KA_Kullanici FOREIGN KEY (KullaniciID) REFERENCES Kullanicilar(KullaniciID),
    CONSTRAINT FK_KA_Aliskanlik FOREIGN KEY (AliskanlikID) REFERENCES Aliskanliklar(AliskanlikID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE TakipKayitlari (
    KayitID INT AUTO_INCREMENT PRIMARY KEY,
    KullaniciAliskanlikID INT NOT NULL,
    Tarih DATE NOT NULL,
    GerceklesenDeger DECIMAL(10,2),
    TamamlandiMi TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY UQ_TK (KullaniciAliskanlikID, Tarih),
    CONSTRAINT FK_TK_KA FOREIGN KEY (KullaniciAliskanlikID)
        REFERENCES KullaniciAliskanliklari(KullaniciAliskanlikID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Arkadasliklar (
    ArkadaslikID INT AUTO_INCREMENT PRIMARY KEY,
    IstekGonderenID INT NOT NULL,
    IstekAlanID INT NOT NULL,
    Durum TINYINT(1) NOT NULL DEFAULT 0, -- 0: beklemede, 1: kabul
    Tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT FK_Ark_Gonderen FOREIGN KEY (IstekGonderenID) REFERENCES Kullanicilar(KullaniciID),
    CONSTRAINT FK_Ark_Alici FOREIGN KEY (IstekAlanID) REFERENCES Kullanicilar(KullaniciID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE MarketEsyalari (
    EsyaID INT AUTO_INCREMENT PRIMARY KEY,
    Ad VARCHAR(100) NOT NULL,
    Aciklama VARCHAR(255),
    Fiyat DECIMAL(10,2) NOT NULL,
    GorselURL VARCHAR(255),
    EsyaTuru VARCHAR(50),
    GerekenSeviye INT DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE KullaniciEnvanteri (
    EnvanterID INT AUTO_INCREMENT PRIMARY KEY,
    KullaniciID INT NOT NULL,
    EsyaID INT NOT NULL,
    AlimTarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    KusandiMi TINYINT(1) DEFAULT 0,
    UNIQUE KEY UQ_Env (KullaniciID, EsyaID),
    CONSTRAINT FK_Env_Kullanici FOREIGN KEY (KullaniciID) REFERENCES Kullanicilar(KullaniciID),
    CONSTRAINT FK_Env_Esya FOREIGN KEY (EsyaID) REFERENCES MarketEsyalari(EsyaID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE GorevTanimlari (
    GorevTanimID INT AUTO_INCREMENT PRIMARY KEY,
    Baslik VARCHAR(100),
    Aciklama TEXT NOT NULL,
    OdulPara DECIMAL(10,2) DEFAULT 0.00,
    HedefSayi INT DEFAULT 1,
    Ikon VARCHAR(100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE KullaniciGunlukGorevleri (
    KullaniciGorevID INT AUTO_INCREMENT PRIMARY KEY,
    KullaniciID INT NOT NULL,
    GorevTanimID INT NOT NULL,
    Tarih DATE DEFAULT (CURRENT_DATE),
    MevcutIlerleme INT DEFAULT 0,
    TamamlandiMi TINYINT(1) DEFAULT 0,
    OdulAlindiMi TINYINT(1) DEFAULT 0,
    CONSTRAINT FK_KGG_Kullanici FOREIGN KEY (KullaniciID) REFERENCES Kullanicilar(KullaniciID),
    CONSTRAINT FK_KGG_Tanim FOREIGN KEY (GorevTanimID) REFERENCES GorevTanimlari(GorevTanimID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO GorevTanimlari (Baslik, Aciklama, OdulPara, HedefSayi, Ikon) VALUES
    ('Günlük Giriş', 'Bugün giriş yap', 10, 1, 'fa-door-open'),
    ('3 alışkanlık tamamla', 'Bugün 3 alışkanlık işaretle', 25, 3, 'fa-list-check');

INSERT INTO MarketEsyalari (Ad, Aciklama, Fiyat, GorselURL, EsyaTuru, GerekenSeviye) VALUES
    ('Büyücü Şapkası', 'Mor külah', 0, 'fa-hat-wizard', 'Şapka', 1),
    ('Kral Tacı', 'Altın taç', 0, 'fa-crown', 'Şapka', 1),
    ('Güneş Gözlüğü', 'Havalı gözlük', 0, 'fa-glasses', 'Gözlük', 1),
    ('Ninja Maskesi', 'Gizemli maske', 50, 'fa-mask', 'Maske', 1),
    ('Altın Kolye', 'Parlak kolye', 500, 'fa-gem', 'Kolye', 1);
