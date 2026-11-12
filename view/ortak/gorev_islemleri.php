<?php
// view/ortak/gorev_islemleri.php
// Görev (alışkanlık) işlemlerinin iş kuralları: ekle, düzenle, arşivle (sil), geri getir,
// tamamla, geri al (docs/ux/03-gorevler.md §4, §5, §8; 09 S7, S8, S9, S10, S12, S17,
// S18, S19, S25). Kurallar burada, sunucuda uygulanır; arayüz yalnızca yansıtır.
//
// Her işlem:
//   * yalnızca hazır ifade kullanır,
//   * görevi KullaniciID ile birlikte seçer (sahiplik): başkasının görevi "bulunamadı"
//     sayılır ve hiçbir veri değişmez,
//   * çok adımlı yazmaları tek transaction içinde yapar,
//   * kullanıcıya gösterilecek hatayı KullaniciHatasi (kod + metin) olarak fırlatır.
// Ad, yetenek ve puan kullanıcıya özel sütunlardan (KullaniciAliskanliklari) okunur ve
// yazılır; eski ortak Aliskanliklar tablosu kullanılmaz (004 sonrası da çalışır).
// Doğrudan çağrılamaz (bkz. view/ortak/cekirdek.php).

if (!defined('CHARMOJI_SAYFA')) {
    http_response_code(404);
    exit();
}

const MYSQL_TEKRAR_ANAHTAR = 1062; // ER_DUP_ENTRY

/** Görev adını kırpar ve doğrular (S17). Hata: ad_bos, ad_uzun. */
function gorev_adi_dogrula($ham): string
{
    $ad = is_string($ham) ? (string) preg_replace('/^[\s\x{00A0}\x{200B}\x{FEFF}]+|[\s\x{00A0}\x{200B}\x{FEFF}]+$/u', '', $ham) : '';
    if (is_string($ham) && !mb_check_encoding($ham, 'UTF-8')) {
        $ad = '';
    }
    if ($ad === '') {
        throw new KullaniciHatasi(metin('gorev_form.hata_ad_bos'), 'ad_bos', ['alan' => 'ad']);
    }
    if (mb_strlen($ad, 'UTF-8') > GOREV_AD_EN_UZUN) {
        throw new KullaniciHatasi(metin('gorev_form.hata_ad_uzun'), 'ad_uzun', ['alan' => 'ad']);
    }
    return $ad;
}

/** Yalnızca beş geçerli yetenek kabul edilir (ikinci derece SQL enjeksiyonuna karşı da). */
function gorev_yetenegi_dogrula($ham): string
{
    if (!is_string($ham) || !isset(YETENEKLER[$ham])) {
        throw new KullaniciHatasi(metin('gorev_form.hata_yetenek_yok'), 'yetenek_yok', ['alan' => 'yetenek']);
    }
    return $ham;
}

function gorev_ad_ayni_hatasi(): KullaniciHatasi
{
    return new KullaniciHatasi(metin('gorev_form.hata_ad_ayni'), 'ad_ayni', ['alan' => 'ad']);
}

function gorev_sinir_hatasi(): KullaniciHatasi
{
    return new KullaniciHatasi(metin('gorevler.sinir_dolu', ['sinir' => GOREV_SINIRI]), 'sinir_dolu',
        ['sinir' => GOREV_SINIRI]);
}

/**
 * Aynı oyuncunun AKTİF görevlerinde bu ad (Türkçe, büyük/küçük harf duyarsız) var mı?
 * Karşılaştırma AktifAd sütununun kuralıyla (utf8mb4_tr_0900_ai_ci) yapılır; aynı kural
 * UQ_KA_Kullanici_AktifAd ile veritabanında da uygulanır (yarışta 1062 döner).
 */
function gorev_adi_kullanimda_mi(mysqli $conn, int $kullaniciID, string $ad, int $haric = 0): bool
{
    return vt_satir($conn, "SELECT KullaniciAliskanlikID FROM KullaniciAliskanliklari
                             WHERE KullaniciID = ? AND AktifAd = ? AND KullaniciAliskanlikID <> ? LIMIT 1",
        "isi", [$kullaniciID, $ad, $haric]) !== null;
}

/** Oyuncunun aktif görev sayısı. */
function aktif_gorev_sayisi(mysqli $conn, int $kullaniciID): int
{
    return (int) (vt_satir($conn, "SELECT COUNT(*) AS Sayi FROM KullaniciAliskanliklari WHERE KullaniciID = ? AND AktifMi = 1",
        "i", [$kullaniciID])['Sayi'] ?? 0);
}

/**
 * Aynı oyuncunun eşzamanlı ekleme/geri getirme isteklerini sıraya sokar (sınır
 * denetimi ile ekleme arasında yarış olmasın). Transaction içinde çağrılır.
 */
function oyuncuyu_kilitle(mysqli $conn, int $kullaniciID): void
{
    if (vt_satir($conn, "SELECT KullaniciID FROM Kullanicilar WHERE KullaniciID = ? FOR UPDATE", "i", [$kullaniciID]) === null) {
        throw new KullaniciHatasi(metin('ortak.oturum_bitti_baslik'), 'oturum_kapali');
    }
}

/**
 * Oyuncunun bir görevini (kilitleyerek) getirir; başkasınınsa ya da yoksa null.
 * $aktif: true yalnızca aktif, false yalnızca arşivlenmiş, null hepsi.
 */
function gorev_satiri(mysqli $conn, int $kullaniciID, int $kaID, string $bugun, ?bool $aktif = true, bool $kilitle = false): ?array
{
    $sql = GOREV_SECIMI . " WHERE KA.KullaniciAliskanlikID = ? AND KA.KullaniciID = ?";
    if ($aktif !== null) {
        $sql .= $aktif ? " AND KA.AktifMi = 1" : " AND KA.AktifMi = 0";
    }
    if ($kilitle) {
        $sql .= " FOR UPDATE";
    }
    return vt_satir($conn, $sql, "sii", [$bugun, $kaID, $kullaniciID]);
}

/** İşlem fonksiyonunu transaction içinde çalıştırır; hata olursa geri alır ve yeniden fırlatır. */
function islem_transaction(mysqli $conn, callable $fn)
{
    $conn->begin_transaction();
    try {
        $sonuc = $fn();
        $conn->commit();
        return $sonuc;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

/** Yeni görev ekler (03 §4.2, S9, S10, S17). Döner: yeni KullaniciAliskanlikID. */
function gorev_ekle(mysqli $conn, int $kullaniciID, $hamAd, $hamYetenek, string $bugun): int
{
    $ad = gorev_adi_dogrula($hamAd);
    $yetenek = gorev_yetenegi_dogrula($hamYetenek);

    return islem_transaction($conn, function () use ($conn, $kullaniciID, $ad, $yetenek, $bugun) {
        oyuncuyu_kilitle($conn, $kullaniciID);
        if (aktif_gorev_sayisi($conn, $kullaniciID) >= GOREV_SINIRI) {
            throw gorev_sinir_hatasi();
        }
        if (gorev_adi_kullanimda_mi($conn, $kullaniciID, $ad)) {
            throw gorev_ad_ayni_hatasi();
        }
        try {
            $ekle = vt_sorgu($conn, "INSERT INTO KullaniciAliskanliklari (KullaniciID, Ad, StatTuru, Puan, BaslangicTarihi)
                                     VALUES (?, ?, ?, ?, ?)", "issis", [$kullaniciID, $ad, $yetenek, GOREV_TABAN_XP, $bugun]);
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === MYSQL_TEKRAR_ANAHTAR) {
                throw gorev_ad_ayni_hatasi();
            }
            throw $e;
        }
        return (int) $ekle->insert_id;
    });
}

/**
 * Görevin adını ve yeteneğini değiştirir (03 §4.3, S19). Seri, bugünkü durum ve geçmiş
 * XP değişmez: geçmiş XP eski yetenekte kalır (TakipKayitlari.StatTuru), sonraki
 * tamamlamaların XP'si yeni yeteneğe gider.
 */
function gorev_duzenle(mysqli $conn, int $kullaniciID, int $kaID, $hamAd, $hamYetenek, string $bugun): void
{
    $ad = gorev_adi_dogrula($hamAd);
    $yetenek = gorev_yetenegi_dogrula($hamYetenek);

    islem_transaction($conn, function () use ($conn, $kullaniciID, $kaID, $ad, $yetenek, $bugun) {
        if (gorev_satiri($conn, $kullaniciID, $kaID, $bugun, true, true) === null) {
            throw new KullaniciHatasi(metin('gorev_form.hata_kaydedilemedi'), 'bulunamadi');
        }
        if (gorev_adi_kullanimda_mi($conn, $kullaniciID, $ad, $kaID)) {
            throw gorev_ad_ayni_hatasi();
        }
        try {
            vt_sorgu($conn, "UPDATE KullaniciAliskanliklari SET Ad = ?, StatTuru = ?
                              WHERE KullaniciAliskanlikID = ? AND KullaniciID = ? AND AktifMi = 1",
                "ssii", [$ad, $yetenek, $kaID, $kullaniciID]);
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === MYSQL_TEKRAR_ANAHTAR) {
                throw gorev_ad_ayni_hatasi();
            }
            throw $e;
        }
    });
}

/**
 * Görevi siler = arşivler (S18): AktifMi = 0, ArsivZamani = şimdi. XP, altın, seviye,
 * seri ve takip kayıtları değişmez; görev listeden ve günlük ilerlemeden çıkar.
 */
function gorev_arsivle(mysqli $conn, int $kullaniciID, int $kaID): void
{
    $sonuc = vt_sorgu($conn, "UPDATE KullaniciAliskanliklari SET AktifMi = 0, ArsivZamani = ?
                               WHERE KullaniciAliskanlikID = ? AND KullaniciID = ? AND AktifMi = 1",
        "sii", [oyun_simdi(), $kaID, $kullaniciID]);
    if ($sonuc->affected_rows < 1) {
        throw new KullaniciHatasi(metin('gorevler.hata_silinemedi'), 'bulunamadi');
    }
}

/** Silmeyi geri alır (S18): yalnızca kısa süre içinde, sınır ve ad kuralına uyarak. */
function gorev_geri_getir(mysqli $conn, int $kullaniciID, int $kaID, string $bugun): void
{
    islem_transaction($conn, function () use ($conn, $kullaniciID, $kaID, $bugun) {
        oyuncuyu_kilitle($conn, $kullaniciID);
        $g = gorev_satiri($conn, $kullaniciID, $kaID, $bugun, false, true);
        if ($g === null) {
            throw new KullaniciHatasi(metin('yerel.silme_geri_alinamadi'), 'bulunamadi');
        }
        $arsiv = $g['ArsivZamani'] !== null ? strtotime((string) $g['ArsivZamani']) : false;
        if ($arsiv === false || time() - $arsiv > GOREV_GERI_GETIRME_SN) {
            throw new KullaniciHatasi(metin('yerel.silme_geri_alinamadi'), 'sure_doldu');
        }
        if (aktif_gorev_sayisi($conn, $kullaniciID) >= GOREV_SINIRI) {
            throw gorev_sinir_hatasi();
        }
        if (gorev_adi_kullanimda_mi($conn, $kullaniciID, (string) $g['Ad'], $kaID)) {
            throw gorev_ad_ayni_hatasi();
        }
        vt_sorgu($conn, "UPDATE KullaniciAliskanliklari SET AktifMi = 1, ArsivZamani = NULL
                          WHERE KullaniciAliskanlikID = ? AND KullaniciID = ? AND AktifMi = 0",
            "ii", [$kaID, $kullaniciID]);
    });
}

/** Bir yeteneğin toplam XP'si (satır yoksa 0 ile oluşturulur). */
function yetenek_puani(mysqli $conn, int $kullaniciID, string $stat): int
{
    vt_sorgu($conn, "INSERT IGNORE INTO KullaniciStatlari (KullaniciID, StatTuru, ToplamPuan) VALUES (?, ?, 0)",
        "is", [$kullaniciID, $stat]);
    return (int) (vt_satir($conn, "SELECT ToplamPuan FROM KullaniciStatlari WHERE KullaniciID = ? AND StatTuru = ? FOR UPDATE",
        "is", [$kullaniciID, $stat])['ToplamPuan'] ?? 0);
}

/** Bugünkü "görev tamamlama" meydan okumalarının durumu (arayüz kartları günceller). */
function meydan_durumlari(mysqli $conn, int $kullaniciID, string $bugun): array
{
    $satirlar = vt_satirlar($conn,
        "SELECT KG.KullaniciGorevID, KG.MevcutIlerleme, KG.TamamlandiMi, KG.OdulAlindiMi, GT.HedefSayi
           FROM KullaniciGunlukGorevleri KG JOIN GorevTanimlari GT ON GT.GorevTanimID = KG.GorevTanimID
          WHERE KG.KullaniciID = ? AND KG.Tarih = ? AND GT.Tur = ?", "iss", [$kullaniciID, $bugun, MEYDAN_TUR_GOREV]);
    return array_map(fn($q) => [
        'id' => (int) $q['KullaniciGorevID'],
        'mevcut' => (int) $q['MevcutIlerleme'],
        'hedef' => max(1, (int) $q['HedefSayi']),
        'tamam' => (bool) $q['TamamlandiMi'],
        'alindi' => (bool) $q['OdulAlindiMi'],
    ], $satirlar);
}

/**
 * Görevi bugün için tamamlar (03 §8, §3.2). Tek transaction: takip kaydı (tamamlama
 * anının XP'si, yeteneği ve önceki seri/tarih ile) + seri + XP + meydan okuma.
 * Döner: işlem özeti (xp, yeni seri, yetenek, önce/sonra puan, çarpan).
 */
function gorev_tamamla(mysqli $conn, int $kullaniciID, int $kaID, string $bugun, string $dun): array
{
    return islem_transaction($conn, function () use ($conn, $kullaniciID, $kaID, $bugun, $dun) {
        $g = gorev_satiri($conn, $kullaniciID, $kaID, $bugun, true, true);
        if ($g === null) {
            throw new KullaniciHatasi(metin('gorevler.hata_tamamlanamadi'), 'bulunamadi');
        }
        if ((int) $g['BugunYapildi'] > 0) {
            throw new KullaniciHatasi(metin('gorevler.zaten_tamamlandi'), 'zaten_tamamlandi');
        }
        // Meydan okuma satırları henüz yoksa (gece yarısından kalan açık sekme) oluşsun.
        gunluk_meydan_okumalari_hazirla($conn, $kullaniciID, $bugun);

        $taban = (int) $g['Puan'];
        $oncekiSeri = (int) $g['Seri'];
        $oncekiSon = $g['SonIslemTarihi'];
        $etkin = etkin_seri($oncekiSeri, $oncekiSon, $bugun, $dun);
        $yeniSeri = $etkin + 1;
        $xp = seri_xp($taban, $yeniSeri);
        // StatTuru kullanıcıya özel sütundan gelir; CHECK kısıtı ve aşağıdaki bağlama
        // (asla sorguya gömülmez) ikinci derece enjeksiyona karşı korur.
        $stat = (string) $g['StatTuru'];

        try {
            vt_sorgu($conn, "INSERT INTO TakipKayitlari
                               (KullaniciAliskanlikID, Tarih, TamamlandiMi, KazanilanXP, StatTuru, OncekiSeri, OncekiSonIslemTarihi)
                             VALUES (?, ?, 1, ?, ?, ?, ?)",
                "isisis", [$kaID, $bugun, $xp, $stat, $oncekiSeri, $oncekiSon]);
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === MYSQL_TEKRAR_ANAHTAR) {
                throw new KullaniciHatasi(metin('gorevler.zaten_tamamlandi'), 'zaten_tamamlandi');
            }
            throw $e;
        }
        vt_sorgu($conn, "UPDATE KullaniciAliskanliklari SET Seri = ?, SonIslemTarihi = ?
                          WHERE KullaniciAliskanlikID = ? AND KullaniciID = ?", "isii", [$yeniSeri, $bugun, $kaID, $kullaniciID]);

        $once = isset(YETENEKLER[$stat]) ? yetenek_puani($conn, $kullaniciID, $stat) : 0;
        vt_sorgu($conn, "UPDATE KullaniciStatlari SET ToplamPuan = ToplamPuan + ? WHERE KullaniciID = ? AND StatTuru = ?",
            "iis", [$xp, $kullaniciID, $stat]);
        meydan_ilerlemesini_esitle($conn, $kullaniciID, $bugun);

        return [
            'xp' => $xp,
            'seri' => $yeniSeri,
            'stat' => $stat,
            'puan_once' => $once,
            'puan_sonra' => $once + $xp,
            'carpan' => seri_carpani($yeniSeri),
            'carpan_yukseldi' => seri_carpani($yeniSeri) > seri_carpani($etkin),
        ];
    });
}

/**
 * Bugünkü tamamlamayı geri alır (03 §8.1). XP, tamamlama anında kazanıldığı miktarla
 * ve kazanıldığı yetenekten düşülür; seri ve son tamamlama tarihi tamamlama öncesine
 * döner (aynı gün yeniden tamamlanınca seri yine artar); meydan okuma ilerlemesi 1 azalır
 * (ödülü alınmışsa değişmez, S7); seviye sessizce düşebilir (S8).
 */
function gorev_geri_al(mysqli $conn, int $kullaniciID, int $kaID, string $bugun): array
{
    return islem_transaction($conn, function () use ($conn, $kullaniciID, $kaID, $bugun) {
        $g = gorev_satiri($conn, $kullaniciID, $kaID, $bugun, true, true);
        if ($g === null) {
            throw new KullaniciHatasi(metin('gorevler.hata_geri_alinamadi'), 'bulunamadi');
        }
        $tk = vt_satir($conn, "SELECT KayitID, KazanilanXP, StatTuru, OncekiSeri, OncekiSonIslemTarihi
                                 FROM TakipKayitlari WHERE KullaniciAliskanlikID = ? AND Tarih = ? FOR UPDATE",
            "is", [$kaID, $bugun]);
        if ($tk === null) {
            throw new KullaniciHatasi(metin('gorevler.hata_geri_alinamadi'), 'tamamlanmadi');
        }
        gunluk_meydan_okumalari_hazirla($conn, $kullaniciID, $bugun);

        $seri = (int) $g['Seri'];
        if ($tk['OncekiSeri'] !== null) {
            $oncekiSeri = (int) $tk['OncekiSeri'];
            $oncekiSon = $tk['OncekiSonIslemTarihi'];
        } else {
            // 005 öncesi kayıt: önceki değerler saklanmamış; en yakın tahmin.
            $oncekiSeri = max(0, $seri - 1);
            $oncekiSon = vt_satir($conn, "SELECT MAX(Tarih) AS Tarih FROM TakipKayitlari
                                           WHERE KullaniciAliskanlikID = ? AND Tarih < ?", "is", [$kaID, $bugun])['Tarih'] ?? null;
        }
        $xp = $tk['KazanilanXP'] !== null ? (int) $tk['KazanilanXP'] : seri_xp((int) $g['Puan'], $seri);
        $stat = $tk['StatTuru'] !== null ? (string) $tk['StatTuru'] : (string) $g['StatTuru'];

        vt_sorgu($conn, "DELETE FROM TakipKayitlari WHERE KayitID = ?", "i", [(int) $tk['KayitID']]);
        vt_sorgu($conn, "UPDATE KullaniciAliskanliklari SET Seri = ?, SonIslemTarihi = ?
                          WHERE KullaniciAliskanlikID = ? AND KullaniciID = ?", "isii", [$oncekiSeri, $oncekiSon, $kaID, $kullaniciID]);
        $once = isset(YETENEKLER[$stat]) ? yetenek_puani($conn, $kullaniciID, $stat) : 0;
        vt_sorgu($conn, "UPDATE KullaniciStatlari SET ToplamPuan = GREATEST(0, ToplamPuan - ?) WHERE KullaniciID = ? AND StatTuru = ?",
            "iis", [$xp, $kullaniciID, $stat]);
        meydan_ilerlemesini_esitle($conn, $kullaniciID, $bugun);

        return [
            'xp' => $xp,
            'seri' => $oncekiSeri,
            'stat' => $stat,
            'puan_once' => $once,
            'puan_sonra' => max(0, $once - $xp),
            'carpan' => null,
            'carpan_yukseldi' => false,
        ];
    });
}
