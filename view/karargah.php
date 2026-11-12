<?php
// view/karargah.php - Karargah sekmesi (E10, docs/ux/02-karargah.md)
// Karakter, mini yetenek barları, günün ilerlemesi ve sıradaki görevler (hızlı tamamlama).
// Günlük sandık kartı (M01) sonraki aşamadadır.
require_once __DIR__ . '/ortak/cekirdek.php';
require_once __DIR__ . '/ortak/gorev_karti.php';

$ortak = sayfa_baslat();
$conn = $ortak['conn'];
$kullaniciID = $ortak['id'];

$esyalar = kusanilan_esyalar($conn, $kullaniciID);
$gorevler = aktif_gorevler($conn, $kullaniciID, $ortak['bugun'], $ortak['dun']);
$toplam = count($gorevler);
$tamamlanan = count(array_filter($gorevler, fn($g) => $g['tamam']));
// Sıradaki görevler: tamamlanmamışların hepsi çizilir, ilk 5'i görünür; biri tamamlanınca
// sıradaki görünür olur (02 §2.1 madde 7).
$siradakiler = array_values(array_filter($gorevler, fn($g) => !$g['tamam']));

// Ruh hali (02 §2.2, VARSAYIM S26). "Uykulu" için dün tamamlanan görev sayısı ve
// dün var olan aktif görev sayısı gerekir.
$ruhHali = 'gorev_yok';
if ($toplam > 0) {
    if ($tamamlanan === $toplam) {
        $ruhHali = 'zafer';
    } elseif ($tamamlanan > 0) {
        $ruhHali = 'neseli';
    } else {
        $dun = vt_satir($conn,
            "SELECT (SELECT COUNT(*) FROM TakipKayitlari TK JOIN KullaniciAliskanliklari KA
                       ON KA.KullaniciAliskanlikID = TK.KullaniciAliskanlikID
                      WHERE KA.KullaniciID = ? AND TK.Tarih = ?) AS Tamamlanan,
                    (SELECT COUNT(*) FROM KullaniciAliskanliklari
                      WHERE KullaniciID = ? AND AktifMi = 1 AND BaslangicTarihi <= ?) AS Aktif",
            "isis", [$kullaniciID, $ortak['dun'], $kullaniciID, $ortak['dun']]);
        $ruhHali = ((int) $dun['Aktif'] > 0 && (int) $dun['Tamamlanan'] === 0) ? 'uykulu' : 'hazir';
    }
}

$sayfa = ['sekme' => 'karargah', 'baslik' => metin('karargah.baslik')];
$sayfaBetigi = 'gorev.js';
$sayfaMetinleri = [
    'karargah.ruh_hali_hazir', 'karargah.ruh_hali_neseli', 'karargah.ruh_hali_zafer', 'karargah.ruh_hali_uykulu',
    'karargah.ruh_hali_gorev_yok', 'karargah.dokunma_sozu_1', 'karargah.dokunma_sozu_2', 'karargah.dokunma_sozu_3',
    'karargah.dokunma_sozu_4', 'karargah.dokunma_sozu_5', 'karargah.bugun_ilerleme', 'karargah.tum_gorevler',
    'ortak.yetenek_bar_etiketi', 'ortak.sonraki_seviye', 'karargah.karakter_etiketi',
    'gorevler.kart_odul', 'gorevler.kart_kazanildi', 'gorevler.kart_seri', 'gorevler.kart_seri_yok',
    'gorevler.kart_tamamlandi', 'gorevler.kart_onay_etiketi', 'gorevler.kart_tamamlandi_etiketi',
    'gorevler.tamamlandi_bildirim', 'gorevler.geri_alindi_bildirim', 'gorevler.hata_tamamlanamadi',
    'gorevler.hata_geri_alinamadi', 'gorevler.okuyucu_tamamlandi', 'gorevler.okuyucu_geri_alindi',
];
$sayfaVerisi = [
    'sayfa' => 'karargah',
    'ad' => $ortak['ad'],
    'ruhHali' => $ruhHali,
    'toplam' => $toplam,
    'tamamlanan' => $tamamlanan,
    'seviye' => $ortak['seviye'],
];

require __DIR__ . '/ortak/ust.php';
?>
<section class="bolum karargah-ust" aria-labelledby="selam">
    <p class="piksel" id="selam" style="font-size:22px;margin:0 0 8px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
        <?= t('karargah.selam', ['ad' => $ortak['ad']]) ?>
    </p>
    <div class="karakter-alan" style="display:grid;grid-template-columns:1fr;justify-items:center;gap:8px">
        <button type="button" class="karakter-dugme" id="karakter-dugme"
                style="background:none;border:0;padding:0;cursor:pointer;min-width:160px;min-height:160px;position:relative"
                aria-label="<?= t('karargah.karakter_etiketi', ['ad' => $ortak['ad'], 'seviye' => $ortak['seviye']]) ?>">
            <span class="karakter-kutu nefes" id="karakter-govde" aria-hidden="true"><?= renderAvatar($ortak['avatar'], '200px', $esyalar) ?></span>
            <span class="ust-seviye-metin" aria-hidden="true" id="karakter-seviye"
                  style="position:absolute;top:4px;right:4px"><?= t('ortak.seviye_kisa', ['seviye' => $ortak['seviye']]) ?></span>
        </button>
        <p class="kart" id="konusma-balonu" aria-live="polite"
           style="margin:0;padding:10px 14px;max-width:320px;text-align:center;border:2px solid var(--kenarlik-etkin)">
            <?= t('karargah.ruh_hali_' . $ruhHali) ?>
        </p>
    </div>
</section>

<section class="bolum" aria-label="<?= t('karakter.yetenekler_baslik') ?>">
    <ul class="liste mini-barlar" style="grid-template-columns:1fr 1fr;gap:8px 12px">
        <?php foreach (YETENEKLER as $kod => $sonek):
            $v = $ortak['yetenekler'][$kod]; ?>
        <li>
            <a href="karakter.php#yetenek-<?= e($sonek) ?>" class="y-<?= e($sonek) ?> y-renkli mini-bar"
               style="display:grid;gap:4px;min-height:44px;padding:4px 0;text-decoration:none;color:var(--metin)">
                <span style="display:flex;justify-content:space-between;gap:4px;font-size:14px">
                    <span class="yetenek-etiket"><?= t('ortak.yetenek_' . $sonek) ?></span>
                    <span class="ikincil-metin" id="mini-seviye-<?= e($kod) ?>" data-seviye="<?= (int) $v['level'] ?>"><?= t('ortak.seviye_kisa', ['seviye' => $v['level']]) ?></span>
                </span>
                <span class="bar ince" role="progressbar" id="mini-bar-<?= e($kod) ?>"
                      aria-valuemin="0" aria-valuemax="<?= (int) $v['neededXP'] ?>" aria-valuenow="<?= (int) $v['currentXP'] ?>"
                      aria-label="<?= t('ortak.yetenek_bar_etiketi', ['yetenek' => yetenek_adi($kod), 'seviye' => $v['level'], 'mevcut' => $v['currentXP'], 'gereken' => $v['neededXP']]) ?>">
                    <span class="bar-dolgu" style="width:<?= e(round($v['percent'], 2)) ?>%"></span>
                </span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
    <p class="ikincil-metin kucuk" id="sonraki-seviye" style="margin:8px 0 0">
        <?= t('ortak.sonraki_seviye', ['sonraki' => $ortak['seviye'] + 1, 'kalan' => $ortak['sonrakiKalan']]) ?>
    </p>
</section>

<?php if ($toplam > 0): ?>
<section class="bolum" aria-label="<?= t('karargah.bugun_ilerleme', ['tamamlanan' => $tamamlanan, 'toplam' => $toplam]) ?>" id="gunun-ilerlemesi">
    <p style="margin:0 0 6px;font-weight:600" id="ilerleme-metin"><?= t('karargah.bugun_ilerleme', ['tamamlanan' => $tamamlanan, 'toplam' => $toplam]) ?></p>
    <div class="bar" role="progressbar" id="ilerleme-bar" aria-valuemin="0" aria-valuemax="<?= $toplam ?>" aria-valuenow="<?= $tamamlanan ?>"
         aria-label="<?= t('karargah.bugun_ilerleme', ['tamamlanan' => $tamamlanan, 'toplam' => $toplam]) ?>">
        <span class="bar-dolgu" style="width:<?= e(round($tamamlanan / $toplam * 100, 2)) ?>%"></span>
    </div>
</section>
<?php endif; ?>

<?php $siradakiBaslikId = 'siradaki-baslik'; // aria-labelledby ve iki başlık dalı aynı kimliği kullanır ?>
<section class="bolum" aria-labelledby="<?= $siradakiBaslikId ?>">
    <?php if ($toplam === 0): ?>
        <div class="bos-durum">
            <span class="bos-durum-ikon" aria-hidden="true"><i class="fa-solid fa-scroll"></i></span>
            <h2 id="<?= $siradakiBaslikId ?>"><?= t('karargah.bos_baslik') ?></h2>
            <p><?= t('karargah.bos_aciklama') ?></p>
            <a class="dugme dugme-birincil" href="gorevler.php#ekle"><?= t('karargah.bos_dugme') ?></a>
        </div>
    <?php else: ?>
        <h2 class="bolum-baslik" id="<?= $siradakiBaslikId ?>"><?= t('karargah.siradaki_baslik') ?></h2>
        <ul class="liste" id="siradaki-liste" data-gorev-listesi="karargah">
            <?php foreach ($siradakiler as $i => $g) {
                $kart = gorev_karti_html($g, false);
                echo $i < 5 ? $kart : preg_replace('/^<li /', '<li hidden ', ltrim($kart), 1);
            } ?>
        </ul>
        <div class="bos-durum" id="hepsi-tamam" <?= $siradakiler === [] ? '' : 'hidden' ?>>
            <span class="bos-durum-ikon" aria-hidden="true"><i class="fa-solid fa-trophy"></i></span>
            <h3><?= t('karargah.hepsi_tamam_baslik') ?></h3>
            <p><?= t('karargah.hepsi_tamam_aciklama') ?></p>
        </div>
        <p style="margin:12px 0 0">
            <a class="dugme dugme-metin" href="gorevler.php" id="tum-gorevler"><?= t('karargah.tum_gorevler', ['toplam' => $toplam]) ?> &rarr;</a>
        </p>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/ortak/alt.php'; ?>
