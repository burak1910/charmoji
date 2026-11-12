-- =====================================================================
-- seed.sql  —  GELİŞTİRME verisi (üretimde çalıştırmayın)
-- ---------------------------------------------------------------------
-- Hedef şema: schema.sql (= migration 001+002+003+005). Eski ortak
-- Aliskanliklar tablosunu kullanmaz; araclar/004 sonrasında da çalışır.
-- Tekrar çalıştırılabilir: var olan kayıtlar yeniden eklenmez, mevcut veri
-- silinmez/değiştirilmez.
--
-- Deneme kullanıcıları (düz şifreler README.md'de de yazılı):
--   deneme@charmoji.local / Charmoji123!   (Ad: Deneme Kahraman)
--   ayse@charmoji.local   / Charmoji123!   (Ad: Ayşe Test)
-- Şifre hash'i PHP password_hash('Charmoji123!', PASSWORD_DEFAULT) ile
-- üretildi; password_verify() ile doğrulanır.
-- =====================================================================

SET NAMES utf8mb4;
-- CURDATE() oyunun gün sınırına (S1: Europe/Istanbul, UTC+3) göre hesaplansın.
SET time_zone = '+03:00';

-- Günlük meydan okuma tanımları (Tur, S25) --------------------------------
-- giris          : o gün herhangi bir sekme açılınca tamamlanır (view/ortak/cekirdek.php).
-- gorev_tamamlama: o gün tamamlanan her görev ilerlemeyi 1 artırır, geri alma 1 azaltır.
INSERT INTO GorevTanimlari (Baslik, Aciklama, OdulPara, HedefSayi, Ikon, Tur)
SELECT * FROM (
  SELECT 'Günlük Giriş' AS Baslik, 'Bugün Charmoji''ye giriş yap.' AS Aciklama,
         10.00 AS OdulPara, 1 AS HedefSayi, 'fa-right-to-bracket' AS Ikon, 'giris' AS Tur
  UNION ALL
  SELECT '3 Görev Tamamla', 'Bugün üç görevini tamamla.', 25.00, 3, 'fa-list-check', 'gorev_tamamlama'
) yeni
WHERE NOT EXISTS (SELECT 1 FROM GorevTanimlari g WHERE g.Baslik = yeni.Baslik);

-- Market eşyaları (view/avatar_renderer.php bu adlarla çizer) ----------
INSERT INTO MarketEsyalari (Ad, Aciklama, Fiyat, GorselURL, EsyaTuru, GerekenSeviye)
SELECT * FROM (
  SELECT 'Büyücü Şapkası' AS Ad, 'Bilgeliğin sivri simgesi.' AS Aciklama, 150.00 AS Fiyat,
         'fa-hat-wizard' AS GorselURL, 'Şapka' AS EsyaTuru, 1 AS GerekenSeviye
  UNION ALL SELECT 'Kral Tacı',      'Seri ustalarına layık.',          500.00, 'fa-crown',      'Şapka',  3
  UNION ALL SELECT 'Güneş Gözlüğü',  'Parlak günler için.',             100.00, 'fa-glasses',    'Gözlük', 1
  UNION ALL SELECT 'Ninja Maskesi',  'Sessiz ama disiplinli.',          300.00, 'fa-user-ninja', 'Maske',  2
  UNION ALL SELECT 'Altın Kolye',    'Kazanılmış altının göstergesi.',  250.00, 'fa-gem',        'Kolye',  2
) yeni
WHERE NOT EXISTS (SELECT 1 FROM MarketEsyalari m WHERE m.Ad = yeni.Ad);

-- Deneme kullanıcıları ----------------------------------------------------
INSERT IGNORE INTO Kullanicilar (Ad, Eposta, SifreHash, Bakiye, AvatarVerisi) VALUES
  ('Deneme Kahraman', 'deneme@charmoji.local',
   '$2y$10$9.HpigFxWGkm.OxnRwC7..4Y10CEcJodHLY9Ro0gL77Gdqd0itvoW', 500.00,
   '{"skinColor":"#F3D2C1","hairColor":"#6D4C41","eyeColor":"#1E3A8A","topColor":"#EA580C"}'),
  ('Ayşe Test', 'ayse@charmoji.local',
   '$2y$10$9.HpigFxWGkm.OxnRwC7..4Y10CEcJodHLY9Ro0gL77Gdqd0itvoW', 120.00, NULL);

-- Statlar (view/ortak/cekirdek.php da eksikleri 0 ile ekler) ----------
INSERT IGNORE INTO KullaniciStatlari (KullaniciID, StatTuru, ToplamPuan)
SELECT k.KullaniciID, s.StatTuru, s.Puan
  FROM Kullanicilar k
  JOIN (SELECT 'Zeka' AS StatTuru, 140 AS Puan UNION ALL SELECT 'Guc', 60
        UNION ALL SELECT 'Sosyallik', 30 UNION ALL SELECT 'Disiplin', 210
        UNION ALL SELECT 'Yaraticilik', 0) s
 WHERE k.Eposta = 'deneme@charmoji.local'
UNION ALL
SELECT k.KullaniciID, s.StatTuru, 0
  FROM Kullanicilar k
  JOIN (SELECT 'Zeka' AS StatTuru UNION ALL SELECT 'Guc' UNION ALL SELECT 'Sosyallik'
        UNION ALL SELECT 'Disiplin' UNION ALL SELECT 'Yaraticilik') s
 WHERE k.Eposta = 'ayse@charmoji.local';

-- Görevler (alışkanlıklar): kullanıcıya özel Ad/StatTuru/Puan ------------
-- Aynı adlı görev (aktif ya da arşivlenmiş) varsa yeniden eklenmez.
INSERT INTO KullaniciAliskanliklari
  (KullaniciID, Ad, StatTuru, Puan, BaslangicTarihi, Seri, SonIslemTarihi)
SELECT k.KullaniciID, y.Ad, y.StatTuru, 10, CURDATE() - INTERVAL 7 DAY, 1, CURDATE() - INTERVAL 1 DAY
  FROM Kullanicilar k
  JOIN (SELECT 'Kitap Oku' AS Ad, 'Zeka' AS StatTuru, 1 AS Sira UNION ALL SELECT 'Şınav Çek', 'Guc', 2
        UNION ALL SELECT 'Erken Kalk', 'Disiplin', 3) y
 WHERE k.Eposta = 'deneme@charmoji.local'
   AND NOT EXISTS (SELECT 1 FROM KullaniciAliskanliklari x WHERE x.KullaniciID = k.KullaniciID AND x.Ad = y.Ad)
 ORDER BY y.Sira;

INSERT INTO KullaniciAliskanliklari (KullaniciID, Ad, StatTuru, Puan, BaslangicTarihi)
SELECT k.KullaniciID, 'Kitap Oku', 'Zeka', 10, CURDATE()
  FROM Kullanicilar k
 WHERE k.Eposta = 'ayse@charmoji.local'
   AND NOT EXISTS (SELECT 1 FROM KullaniciAliskanliklari x WHERE x.KullaniciID = k.KullaniciID AND x.Ad = 'Kitap Oku');

-- Dünkü işaretlemeler (seri 1 olarak görünsün) -------------------------
-- Yalnızca hiç takip kaydı olmayan alışkanlıklara eklenir; böylece seed
-- başka bir günde yeniden çalıştırılınca yeni satır eklemez.
INSERT IGNORE INTO TakipKayitlari
  (KullaniciAliskanlikID, Tarih, TamamlandiMi, KazanilanXP, StatTuru, OncekiSeri, OncekiSonIslemTarihi)
SELECT ka.KullaniciAliskanlikID, ka.SonIslemTarihi, 1, 10, ka.StatTuru, 0, NULL
  FROM KullaniciAliskanliklari ka
  JOIN Kullanicilar k ON k.KullaniciID = ka.KullaniciID
 WHERE k.Eposta = 'deneme@charmoji.local'
   AND ka.SonIslemTarihi IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM TakipKayitlari t
                    WHERE t.KullaniciAliskanlikID = ka.KullaniciAliskanlikID);

-- Envanter: Deneme Kahraman büyücü şapkasını almış ve takmış ----------
INSERT IGNORE INTO KullaniciEnvanteri (KullaniciID, EsyaID, KusandiMi)
SELECT k.KullaniciID, m.EsyaID, 1
  FROM Kullanicilar k JOIN MarketEsyalari m ON m.Ad = 'Büyücü Şapkası'
 WHERE k.Eposta = 'deneme@charmoji.local';

-- Arkadaşlık: kabul edilmiş (Durum = 1) --------------------------------
INSERT IGNORE INTO Arkadasliklar (IstekGonderenID, IstekAlanID, Durum)
SELECT g.KullaniciID, a.KullaniciID, 1
  FROM Kullanicilar g JOIN Kullanicilar a
 WHERE g.Eposta = 'deneme@charmoji.local' AND a.Eposta = 'ayse@charmoji.local';
