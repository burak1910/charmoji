-- =====================================================================
-- seed.sql  —  GELİŞTİRME verisi (üretimde çalıştırmayın)
-- ---------------------------------------------------------------------
-- Hedef şema: schema.sql (= migration 001+002+003). Tekrar çalıştırılabilir:
-- var olan kayıtlar yeniden eklenmez, mevcut veri silinmez/değiştirilmez.
--
-- Deneme kullanıcıları (düz şifreler README.md'de de yazılı):
--   deneme@charmoji.local / Charmoji123!   (Ad: Deneme Kahraman)
--   ayse@charmoji.local   / Charmoji123!   (Ad: Ayşe Test)
-- Şifre hash'i PHP password_hash('Charmoji123!', PASSWORD_DEFAULT) ile
-- üretildi; password_verify() ile doğrulanır.
-- =====================================================================

SET NAMES utf8mb4;

-- Günlük görev tanımları ------------------------------------------------
-- userpage.php : Baslik = 'Günlük Giriş' olan görev sayfa açılınca tamamlanır.
-- ajax_islem.php: Baslik LIKE '%alışkanlık%' olan görevler her işaretlemede +1 ilerler.
INSERT INTO GorevTanimlari (Baslik, Aciklama, OdulPara, HedefSayi, Ikon)
SELECT * FROM (
  SELECT 'Günlük Giriş' AS Baslik, 'Bugün Charmoji''ye giriş yap.' AS Aciklama,
         10.00 AS OdulPara, 1 AS HedefSayi, 'fa-right-to-bracket' AS Ikon
  UNION ALL
  SELECT '3 Alışkanlık Tamamla', 'Bugün üç alışkanlığını işaretle.', 25.00, 3, 'fa-list-check'
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

-- Statlar (userpage.php da eksikleri 0 ile ekler) ----------------------
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

-- Alışkanlıklar ---------------------------------------------------------
-- Geçiş döneminde eski kod alışkanlıkları Aliskanliklar ile JOIN'leyerek
-- listelediği için ortak tabloya da kayıt açılır ve AliskanlikID verilir.
-- Kullanıcıya özel değerler (Ad/StatTuru/Puan) KullaniciAliskanliklari'nda.
INSERT IGNORE INTO Aliskanliklar (Ad, StatTuru, Puan) VALUES
  ('Kitap Oku', 'Zeka', 10),
  ('Şınav Çek', 'Guc', 10),
  ('Erken Kalk', 'Disiplin', 10);

INSERT IGNORE INTO KullaniciAliskanliklari
  (KullaniciID, AliskanlikID, Ad, StatTuru, Puan, BaslangicTarihi, Seri, SonIslemTarihi)
SELECT k.KullaniciID, a.AliskanlikID, a.Ad, a.StatTuru, a.Puan,
       CURDATE() - INTERVAL 7 DAY, 1, CURDATE() - INTERVAL 1 DAY
  FROM Kullanicilar k
  JOIN Aliskanliklar a ON a.Ad IN ('Kitap Oku', 'Şınav Çek', 'Erken Kalk')
 WHERE k.Eposta = 'deneme@charmoji.local';

INSERT IGNORE INTO KullaniciAliskanliklari
  (KullaniciID, AliskanlikID, Ad, StatTuru, Puan, BaslangicTarihi)
SELECT k.KullaniciID, a.AliskanlikID, a.Ad, a.StatTuru, a.Puan, CURDATE()
  FROM Kullanicilar k
  JOIN Aliskanliklar a ON a.Ad = 'Kitap Oku'
 WHERE k.Eposta = 'ayse@charmoji.local';

-- Dünkü işaretlemeler (seri 1 olarak görünsün) -------------------------
INSERT IGNORE INTO TakipKayitlari (KullaniciAliskanlikID, Tarih, TamamlandiMi)
SELECT ka.KullaniciAliskanlikID, CURDATE() - INTERVAL 1 DAY, 1
  FROM KullaniciAliskanliklari ka
  JOIN Kullanicilar k ON k.KullaniciID = ka.KullaniciID
 WHERE k.Eposta = 'deneme@charmoji.local';

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
