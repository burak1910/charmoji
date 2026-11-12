<?php
// view/karakter.php - Karakter sekmesi (E50, docs/ux/06-karakter.md)
// Karakter, kuşanılanlar (4 yuva), beş yetenek kartı, hesap ve çıkış (M08).
// Görünüm (E51) ve Çanta (E52) alt ekranı: view/avatar.php.
require_once __DIR__ . '/ortak/cekirdek.php';

$ortak = sayfa_baslat();
$conn = $ortak['conn'];
$kullaniciID = $ortak['id'];

$esyalar = kusanilan_esyalar($conn, $kullaniciID);
$yuvalar = array_fill_keys(array_keys(ESYA_TURLERI), null);
foreach ($esyalar as $esya) {
    if (array_key_exists($esya['EsyaTuru'], $yuvalar)) {
        $yuvalar[$esya['EsyaTuru']] = $esya;
    }
}
$esyaAdlari = $esyalar === [] ? 'hiçbiri' : implode(', ', array_column($esyalar, 'Ad'));

$gorevSayilari = [];
foreach (vt_satirlar($conn,
    "SELECT A.StatTuru, COUNT(*) AS Sayi FROM KullaniciAliskanliklari KA
       JOIN Aliskanliklar A ON KA.AliskanlikID = A.AliskanlikID
      WHERE KA.KullaniciID = ? AND KA.AktifMi = 1 GROUP BY A.StatTuru", "i", [$kullaniciID]) as $s) {
    $gorevSayilari[$s['StatTuru']] = (int) $s['Sayi'];
}

$sayfa = ['sekme' => 'karakter', 'baslik' => metin('karakter.baslik')];
$sayfaBetigi = 'karakter.js';
$sayfaMetinleri = ['karakter.cikis_onay_baslik', 'karakter.cikis_onay_aciklama', 'karakter.cikis_onay_dugme'];

require __DIR__ . '/ortak/ust.php';
?>
<section class="bolum" style="text-align:center">
    <div class="karakter-kutu nefes" role="img"
         aria-label="<?= t('karakter.karakter_etiketi', ['ad' => $ortak['ad'], 'seviye' => $ortak['seviye'], 'esyalar' => $esyaAdlari]) ?>">
        <span aria-hidden="true"><?= renderAvatar($ortak['avatar'], '240px', $esyalar) ?></span>
    </div>
    <h2 class="piksel" style="font-size:24px;margin-top:8px;overflow-wrap:anywhere"><?= e($ortak['ad']) ?></h2>
    <p style="margin:4px 0;font-weight:700;color:var(--altin)"><?= t('ortak.seviye_uzun', ['seviye' => $ortak['seviye']]) ?></p>
    <p class="ikincil-metin kucuk" style="margin:0"><?= t('ortak.sonraki_seviye', ['sonraki' => $ortak['seviye'] + 1, 'kalan' => $ortak['sonrakiKalan']]) ?></p>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:16px">
        <a class="dugme dugme-ikincil" href="avatar.php#gorunum"><?= t('karakter.gorunum_dugme') ?></a>
        <a class="dugme dugme-ikincil" href="avatar.php#canta"><?= t('karakter.canta_dugme') ?></a>
    </div>
</section>

<section class="bolum" aria-labelledby="yuvalar-baslik">
    <h2 class="bolum-baslik" id="yuvalar-baslik"><?= t('karakter.yuvalar_baslik') ?></h2>
    <ul class="liste yuvalar">
        <?php foreach ($yuvalar as $tur => $esya): $turAdi = tur_adi($tur); ?>
        <li>
            <a class="yuva<?= $esya ? ' dolu' : '' ?>" href="avatar.php?tur=<?= e(rawurlencode($tur)) ?>#canta"
               aria-label="<?= $esya ? t('karakter.yuva_etiketi', ['tur' => $turAdi, 'esya' => $esya['Ad']]) : t('karakter.yuva_bos_etiketi', ['tur' => $turAdi]) ?>">
                <i class="fa-solid <?= e($esya ? $esya['GorselURL'] : ESYA_TUR_IKONLARI[$tur]) ?>" aria-hidden="true"></i>
                <span class="yuva-tur" aria-hidden="true"><?= e($turAdi) ?></span>
                <span class="yuva-ad" aria-hidden="true"><?= $esya ? e($esya['Ad']) : t('karakter.yuva_bos') ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="bolum" aria-labelledby="yetenekler-baslik">
    <h2 class="bolum-baslik" id="yetenekler-baslik"><?= t('karakter.yetenekler_baslik') ?></h2>
    <ul class="liste">
        <?php foreach (YETENEKLER as $kod => $sonek):
            $v = $ortak['yetenekler'][$kod];
            $sayi = $gorevSayilari[$kod] ?? 0; ?>
        <li class="yetenek-karti y-<?= e($sonek) ?> y-renkli" id="yetenek-<?= e($sonek) ?>">
            <button type="button" class="yetenek-karti-baslik" aria-expanded="false" aria-controls="yetenek-detay-<?= e($sonek) ?>">
                <span class="yetenek-etiket" style="font-size:16px"><?= t('ortak.yetenek_' . $sonek) ?></span>
                <span class="ikincil-metin"><?= t('ortak.seviye_kisa', ['seviye' => $v['level']]) ?></span>
                <svg class="ok" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2.5"/></svg>
            </button>
            <div class="yetenek-karti-alt">
                <div class="bar" role="progressbar" aria-valuemin="0" aria-valuemax="<?= (int) $v['neededXP'] ?>" aria-valuenow="<?= (int) $v['currentXP'] ?>"
                     aria-label="<?= t('ortak.yetenek_bar_etiketi', ['yetenek' => yetenek_adi($kod), 'seviye' => $v['level'], 'mevcut' => $v['currentXP'], 'gereken' => $v['neededXP']]) ?>">
                    <span class="bar-dolgu" style="width:<?= e(round($v['percent'], 2)) ?>%"></span>
                </div>
                <span class="soluk-metin"><?= t('ortak.xp_ilerleme', ['mevcut' => $v['currentXP'], 'gereken' => $v['neededXP']]) ?></span>
            </div>
            <div class="yetenek-karti-detay" id="yetenek-detay-<?= e($sonek) ?>" hidden>
                <span><?= t('karakter.yetenek_kalan', ['sonraki' => $v['level'] + 1, 'kalan' => $v['neededXP'] - $v['currentXP']]) ?></span>
                <span class="ikincil-metin"><?= $sayi > 0 ? t('karakter.yetenek_gorev_sayisi', ['sayi' => $sayi]) : t('karakter.yetenek_gorev_yok') ?></span>
                <a class="dugme dugme-ikincil dugme-kucuk" style="justify-self:end" href="gorevler.php?yetenek=<?= e(rawurlencode($kod)) ?>#ekle"><?= t('karakter.yetenek_gorev_ekle') ?></a>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
    <p class="soluk-metin" style="margin:12px 0 0"><?= t('karakter.yetenek_ipucu_denge') ?></p>
</section>

<section class="bolum" aria-labelledby="hesap-baslik">
    <h2 class="bolum-baslik" id="hesap-baslik"><?= t('karakter.hesap_baslik') ?></h2>
    <div class="kart">
        <dl style="margin:0">
            <div class="hesap-satiri"><dt><?= t('karakter.hesap_kahraman_adi') ?></dt><dd><?= e($ortak['ad']) ?></dd></div>
            <div class="hesap-satiri"><dt><?= t('karakter.hesap_eposta') ?></dt><dd><?= e(eposta_maskele($ortak['eposta'])) ?></dd></div>
        </dl>
        <form action="../service/logout.php" method="POST" id="cikis-form" style="margin-top:16px">
            <?= csrf_alani() ?>
            <button type="submit" class="dugme dugme-ikincil dugme-tam" id="cikis-dugme" data-veri-degistirir><?= t('karakter.cikis_dugme') ?></button>
        </form>
    </div>
</section>
<?php require __DIR__ . '/ortak/alt.php'; ?>
