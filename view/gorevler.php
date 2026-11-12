<?php
// view/gorevler.php - Görevler sekmesi (E20, docs/ux/03-gorevler.md)
// Bugünkü görevleri tamamlama / geri alma (M04), görev ekleme (E21 ekle kipi),
// silme (M03) ve günlük meydan okumalar. Görev düzenleme ve seri bilgisi (M02)
// sonraki aşamadadır.
require_once __DIR__ . '/ortak/cekirdek.php';
require_once __DIR__ . '/ortak/gorev_karti.php';

// ---------------------------------------------------------------- görev ekleme (POST, PRG)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
    csrf_dogrula(); // Jetonsuz/yanlış jetonlu istek 403 ile reddedilir.
    $kullaniciID = (int) $_SESSION['user_id'];

    if (isset($_POST['add_habit_btn'])) {
        $yeniAd = is_string($_POST['habit_name'] ?? null) ? trim($_POST['habit_name']) : '';
        $secilenStat = $_POST['habit_stat'] ?? '';
        $puan = 10;

        // Yalnızca beş geçerli yetenek kabul edilir (ikinci derece SQL enjeksiyonuna karşı).
        if (!is_string($secilenStat) || !isset(YETENEKLER[$secilenStat])) {
            bildirim_birak(metin('gorev_form.hata_yetenek_yok'), 'hata');
        } elseif ($yeniAd === '') {
            bildirim_birak(metin('gorev_form.hata_ad_bos'), 'hata');
        } elseif (mb_strlen($yeniAd, 'UTF-8') > 60) {
            bildirim_birak(metin('gorev_form.hata_ad_uzun'), 'hata');
        } else {
            $conn = get_db_connection();
            try {
                // Bugünkü veri modeli: görev adı eski ortak tabloda (Aliskanliklar), kullanıcı
                // bağlantısı KullaniciAliskanliklari'nda. Tümü hazır ifade.
                $var = vt_satir($conn, "SELECT AliskanlikID FROM Aliskanliklar WHERE Ad = ?", "s", [$yeniAd]);
                if ($var !== null) {
                    $aliskanlikID = (int) $var['AliskanlikID'];
                } else {
                    $ekle = vt_sorgu($conn, "INSERT INTO Aliskanliklar (Ad, StatTuru, Puan) VALUES (?, ?, ?)",
                        "ssi", [$yeniAd, $secilenStat, $puan]);
                    $aliskanlikID = (int) $ekle->insert_id;
                }
                $bag = vt_sorgu($conn, "INSERT IGNORE INTO KullaniciAliskanliklari (KullaniciID, AliskanlikID, BaslangicTarihi)
                                        VALUES (?, ?, CURDATE())", "ii", [$kullaniciID, $aliskanlikID]);
                if ($bag->affected_rows > 0) {
                    bildirim_birak(metin('gorevler.eklendi_bildirim'));
                } else {
                    bildirim_birak(metin('gorev_form.hata_ad_ayni'), 'hata');
                }
            } catch (mysqli_sql_exception $e) {
                error_log('[charmoji] Görev eklenemedi: ' . $e->getMessage());
                bildirim_birak(metin('gorev_form.hata_kaydedilemedi'), 'hata');
            }
        }
    }
    header('Location: gorevler.php', true, 303);
    exit();
}

// ---------------------------------------------------------------- sayfa
$ortak = sayfa_baslat();
$conn = $ortak['conn'];
$kullaniciID = $ortak['id'];

$gorevler = aktif_gorevler($conn, $kullaniciID, $ortak['bugun'], $ortak['dun']);
$yapilacaklar = array_values(array_filter($gorevler, fn($g) => !$g['tamam']));
$tamamlananlar = array_values(array_filter($gorevler, fn($g) => $g['tamam']));
$toplam = count($gorevler);

$meydanOkumalari = vt_satirlar($conn,
    "SELECT KG.KullaniciGorevID, KG.MevcutIlerleme, KG.TamamlandiMi, KG.OdulAlindiMi,
            GT.Baslik, GT.Aciklama, GT.OdulPara, GT.HedefSayi, GT.Ikon
       FROM KullaniciGunlukGorevleri KG
       JOIN GorevTanimlari GT ON KG.GorevTanimID = GT.GorevTanimID
      WHERE KG.KullaniciID = ? AND KG.Tarih = ?
      ORDER BY KG.KullaniciGorevID", "is", [$kullaniciID, $ortak['bugun']]);

$gunSonu = strtotime('tomorrow') - time();
$onerilenYetenek = is_string($_GET['yetenek'] ?? null) && isset(YETENEKLER[$_GET['yetenek']]) ? $_GET['yetenek'] : '';

$sayfa = ['sekme' => 'gorevler', 'baslik' => metin('gorevler.baslik')];
$sayfaBetigi = 'gorev.js';
$sayfaMetinleri = [
    'gorevler.bugun_ozet', 'gorevler.yapilacaklar_baslik', 'gorevler.tamamlananlar_baslik',
    'gorevler.kart_odul', 'gorevler.kart_kazanildi', 'gorevler.kart_seri', 'gorevler.kart_seri_yok',
    'gorevler.kart_tamamlandi', 'gorevler.kart_onay_etiketi', 'gorevler.kart_tamamlandi_etiketi',
    'gorevler.tamamlandi_bildirim', 'gorevler.geri_alindi_bildirim', 'gorevler.hata_tamamlanamadi',
    'gorevler.hata_geri_alinamadi', 'gorevler.okuyucu_tamamlandi', 'gorevler.okuyucu_geri_alindi',
    'gorevler.geri_al_onay_baslik', 'gorevler.geri_al_onay_aciklama', 'gorevler.geri_al_onay_dugme',
    'gorevler.sil_onay_baslik', 'gorevler.sil_onay_aciklama', 'gorevler.sil_onay_dugme',
    'gorevler.silindi_bildirim', 'gorevler.hata_silinemedi',
    'gorevler.meydan_alindi', 'gorevler.meydan_yenilenme', 'gorevler.meydan_hata_alinamadi',
    'gorevler.meydan_hata_zaten', 'gorevler.meydan_hata_tamamlanmadi', 'gorevler.meydan_odul_alindi_okuyucu',
    'gorev_form.hata_ad_bos', 'gorev_form.hata_ad_uzun', 'gorev_form.hata_yetenek_yok',
    'gorev_form.ad_sayac', 'gorev_form.odul_onizleme',
];
$sayfaVerisi = ['sayfa' => 'gorevler', 'gunSonuSn' => $gunSonu, 'seviye' => $ortak['seviye']];

require __DIR__ . '/ortak/ust.php';
?>
<?php if ($toplam === 0): ?>
<section class="bolum">
    <div class="bos-durum">
        <span class="bos-durum-ikon" aria-hidden="true"><i class="fa-solid fa-scroll"></i></span>
        <h2><?= t('gorevler.bos_baslik') ?></h2>
        <p><?= t('gorevler.bos_aciklama') ?></p>
        <button type="button" class="dugme dugme-birincil" data-ekle-ac data-veri-degistirir><?= t('gorevler.bos_dugme') ?></button>
    </div>
</section>
<?php else: ?>
<section class="bolum" id="gorev-ozet">
    <div class="bolum-ust">
        <p style="margin:0;font-weight:600" id="ozet-metin"><?= t('gorevler.bugun_ozet', ['tamamlanan' => count($tamamlananlar), 'toplam' => $toplam]) ?></p>
        <button type="button" class="dugme dugme-birincil dugme-kucuk" data-ekle-ac data-veri-degistirir>
            <i class="fa-solid fa-plus" aria-hidden="true"></i> <?= t('gorevler.ekle_dugme') ?>
        </button>
    </div>
    <div class="bar" role="progressbar" id="ozet-bar" aria-valuemin="0" aria-valuemax="<?= $toplam ?>" aria-valuenow="<?= count($tamamlananlar) ?>"
         aria-label="<?= t('gorevler.bugun_ozet', ['tamamlanan' => count($tamamlananlar), 'toplam' => $toplam]) ?>">
        <span class="bar-dolgu" style="width:<?= e(round(count($tamamlananlar) / $toplam * 100, 2)) ?>%"></span>
    </div>
</section>

<section class="bolum" id="bolum-yapilacaklar" <?= $yapilacaklar === [] ? 'hidden' : '' ?> aria-labelledby="baslik-yapilacaklar">
    <h2 class="bolum-baslik" id="baslik-yapilacaklar"><?= t('gorevler.yapilacaklar_baslik', ['sayi' => count($yapilacaklar)]) ?></h2>
    <ul class="liste" id="liste-yapilacaklar" data-gorev-listesi="yapilacaklar">
        <?php foreach ($yapilacaklar as $g) { echo gorev_karti_html($g, true); } ?>
    </ul>
</section>

<section class="bolum" id="bolum-tamamlananlar" <?= $tamamlananlar === [] ? 'hidden' : '' ?> aria-labelledby="baslik-tamamlananlar">
    <h2 class="bolum-baslik" id="baslik-tamamlananlar"><?= t('gorevler.tamamlananlar_baslik', ['sayi' => count($tamamlananlar)]) ?></h2>
    <ul class="liste" id="liste-tamamlananlar" data-gorev-listesi="tamamlananlar">
        <?php foreach ($tamamlananlar as $g) { echo gorev_karti_html($g, true); } ?>
    </ul>
</section>
<?php endif; ?>

<section class="bolum" id="meydan-okumalar" aria-labelledby="baslik-meydan">
    <div class="bolum-ust" style="flex-wrap:wrap">
        <h2 class="bolum-baslik" id="baslik-meydan"><?= t('gorevler.meydan_baslik') ?></h2>
        <span class="soluk-metin" id="meydan-yenilenme"><?= t('gorevler.meydan_yenilenme', ['saat' => intdiv($gunSonu, 3600), 'dakika' => intdiv($gunSonu % 3600, 60)]) ?></span>
    </div>
    <?php if ($meydanOkumalari === []): ?>
        <p class="ikincil-metin"><?= t('gorevler.meydan_bos') ?></p>
    <?php else: ?>
    <ul class="liste">
        <?php foreach ($meydanOkumalari as $q):
            $hedef = max(1, (int) $q['HedefSayi']);
            $mevcut = min($hedef, (int) $q['MevcutIlerleme']);
            $odul = (int) $q['OdulPara'];
            $kartEtiketi = metin('gorevler.meydan_odul_etiketi', ['baslik' => $q['Baslik'], 'mevcut' => $mevcut, 'hedef' => $hedef, 'altin' => sayi_tr($odul)]);
        ?>
        <li class="kart meydan-karti" style="display:grid;gap:8px" aria-label="<?= e($kartEtiketi) ?>">
            <div style="display:flex;gap:12px;align-items:center">
                <span aria-hidden="true" style="width:40px;height:40px;flex:none;display:inline-flex;align-items:center;justify-content:center;background:var(--zemin);border-radius:8px;color:var(--altin)">
                    <i class="fa-solid <?= e($q['Ikon']) ?>"></i>
                </span>
                <div style="flex:1;min-width:0">
                    <p style="margin:0;font-weight:600;overflow-wrap:anywhere"><?= e($q['Baslik']) ?></p>
                    <?php if ((string) $q['Aciklama'] !== ''): ?><p class="soluk-metin" style="margin:0"><?= e($q['Aciklama']) ?></p><?php endif; ?>
                </div>
                <span class="esya-fiyat"><span class="altin-ikon" aria-hidden="true"></span><?= t('gorevler.meydan_odul', ['altin' => sayi_tr($odul)]) ?></span>
            </div>
            <div style="display:flex;gap:12px;align-items:center;justify-content:space-between">
                <span style="display:flex;gap:8px;align-items:center;flex:1">
                    <span class="kucuk"><?= t('gorevler.meydan_ilerleme', ['mevcut' => $mevcut, 'hedef' => $hedef]) ?></span>
                    <?php if ($hedef > 1): ?>
                        <span class="bar" role="progressbar" style="flex:1;max-width:160px" aria-valuemin="0" aria-valuemax="<?= $hedef ?>" aria-valuenow="<?= $mevcut ?>" aria-label="<?= e($kartEtiketi) ?>">
                            <span class="bar-dolgu" style="width:<?= e(round($mevcut / $hedef * 100, 2)) ?>%"></span>
                        </span>
                    <?php endif; ?>
                </span>
                <span class="meydan-eylem">
                <?php if ($q['TamamlandiMi'] && !$q['OdulAlindiMi']): ?>
                    <button type="button" class="dugme dugme-birincil dugme-kucuk" style="min-width:96px" data-odul-al="<?= (int) $q['KullaniciGorevID'] ?>" data-odul="<?= $odul ?>" data-veri-degistirir><?= t('gorevler.meydan_odulu_al') ?></button>
                <?php elseif ($q['OdulAlindiMi']): ?>
                    <span class="gorev-tamam-etiket"><i class="fa-solid fa-check" aria-hidden="true"></i> <?= t('gorevler.meydan_alindi') ?></span>
                <?php else: ?>
                    <span class="ikincil-metin kucuk"><?= t('gorevler.meydan_devam') ?></span>
                <?php endif; ?>
                </span>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
    <p class="soluk-metin" style="margin:8px 0 0"><?= t('gorevler.meydan_kaybolur_uyari') ?></p>
    <?php endif; ?>
</section>

<!-- E21 görev formu (ekle kipi) -->
<div class="alt-sayfa-arka" id="ekle-arka" hidden>
    <form class="alt-sayfa" id="ekle-form" method="POST" action="gorevler.php" role="dialog" aria-modal="true"
          aria-labelledby="ekle-baslik" novalidate>
        <?= csrf_alani() ?>
        <input type="hidden" name="add_habit_btn" value="1">
        <div class="alt-sayfa-ust">
            <h2 class="alt-sayfa-baslik" id="ekle-baslik"><?= t('gorev_form.ekle_baslik') ?></h2>
            <button type="button" class="kapat-dugme" data-ekle-kapat aria-label="<?= t('ortak.kapat') ?>">
                <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2.5"/></svg>
            </button>
        </div>
        <div class="alan">
            <label for="habit_name"><?= t('gorev_form.ad_etiketi') ?></label>
            <input class="girdi" type="text" id="habit_name" name="habit_name" maxlength="60" autocomplete="off"
                   placeholder="<?= t('gorev_form.ad_ipucu') ?>" aria-describedby="ad-hata ad-sayac">
            <div class="alan-alt">
                <span class="alan-hata" id="ad-hata" hidden></span>
                <span class="soluk-metin" id="ad-sayac" style="margin-left:auto"><?= t('gorev_form.ad_sayac', ['uzunluk' => 0]) ?></span>
            </div>
        </div>
        <div class="alan">
            <span class="ikincil-metin kucuk"><?= t('gorev_form.oneri_baslik') ?></span>
            <div class="cipler">
                <?php $i = 0; foreach (YETENEKLER as $kod => $sonek): $i++; ?>
                    <button type="button" class="cip" data-oneri-ad="<?= t('gorev_form.oneri_' . $i) ?>" data-oneri-yetenek="<?= e($kod) ?>"><?= t('gorev_form.oneri_' . $i) ?></button>
                <?php endforeach; ?>
            </div>
        </div>
        <fieldset class="alan" aria-describedby="yetenek-hata">
            <legend><?= t('gorev_form.yetenek_etiketi') ?></legend>
            <div class="secenekler">
                <?php foreach (YETENEKLER as $kod => $sonek): ?>
                <div class="secenek y-<?= e($sonek) ?>">
                    <input type="radio" name="habit_stat" id="yetenek-<?= e($sonek) ?>" value="<?= e($kod) ?>" <?= $onerilenYetenek === $kod ? 'checked' : '' ?>>
                    <label for="yetenek-<?= e($sonek) ?>">
                        <span class="yetenek-etiket"><?= t('ortak.yetenek_' . $sonek) ?></span>
                        <small><?= t('gorev_form.yetenek_aciklama_' . $sonek) ?></small>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
            <span class="alan-hata" id="yetenek-hata" hidden></span>
        </fieldset>
        <p class="ikincil-metin kucuk" id="odul-onizleme" hidden></p>
        <div class="alt-sayfa-dugmeler">
            <button type="submit" class="dugme dugme-birincil" data-veri-degistirir><?= t('gorev_form.kaydet_ekle') ?></button>
        </div>
    </form>
</div>
<?php require __DIR__ . '/ortak/alt.php'; ?>
