<?php
// view/market.php - Market sekmesi (E30, docs/ux/04-market.md)
// Tür filtresi, eşya ızgarası (sıralama 04 §2.1), satın alma onayı (M05, G07).
// Eşya detayı ve deneme (E31) ile nadirlik sonraki aşamadadır: bu aşamada karta
// dokunmak doğrudan M05 onayını açar.
require_once __DIR__ . '/ortak/cekirdek.php';

$ortak = sayfa_baslat();
$conn = $ortak['conn'];
$kullaniciID = $ortak['id'];

$urunler = vt_satirlar($conn,
    "SELECT M.EsyaID, M.Ad, M.Aciklama, M.Fiyat, M.GorselURL, M.EsyaTuru, M.GerekenSeviye,
            COALESCE(E.KusandiMi, -1) AS Durum
       FROM MarketEsyalari M
       LEFT JOIN KullaniciEnvanteri E ON E.EsyaID = M.EsyaID AND E.KullaniciID = ?
      ORDER BY M.Fiyat ASC, M.EsyaID ASC", "i", [$kullaniciID]);

// Sıralama (04 §2.1): alınabilir (fiyat artan) → seviye kilitli (seviye, fiyat) → sahip olunan (fiyat).
$grup = function (array $u) use ($ortak): int {
    if ((int) $u['Durum'] >= 0) {
        return 2;
    }
    return (int) $u['GerekenSeviye'] > $ortak['seviye'] ? 1 : 0;
};
usort($urunler, function ($a, $b) use ($grup) {
    $ga = $grup($a);
    $gb = $grup($b);
    if ($ga !== $gb) {
        return $ga <=> $gb;
    }
    if ($ga === 1 && (int) $a['GerekenSeviye'] !== (int) $b['GerekenSeviye']) {
        return (int) $a['GerekenSeviye'] <=> (int) $b['GerekenSeviye'];
    }
    return [(float) $a['Fiyat'], (int) $a['EsyaID']] <=> [(float) $b['Fiyat'], (int) $b['EsyaID']];
});

$sayfa = ['sekme' => 'market', 'baslik' => metin('market.baslik')];
$sayfaBetigi = 'market.js';
$sayfaMetinleri = [
    'market.kart_cantada', 'market.kart_kilit', 'market.kart_eksik', 'market.detay_kilit_aciklama',
    'market.satin_al_onay_baslik', 'market.satin_al_onay_aciklama', 'market.satin_al_onay_not',
    'market.satin_al_onay_dugme', 'market.satin_alindi_bildirim', 'market.satin_alindi_okuyucu',
    'market.hata_yetersiz_altin', 'market.hata_zaten_sahip', 'market.hata_bulunamadi', 'market.hata_satin_alinamadi',
    'market.kart_etiketi', 'ortak.kusan', 'ortak.kusanildi', 'ortak.kusanildi_bildirim', 'ortak.hata_kusanilamadi',
];
$sayfaVerisi = ['sayfa' => 'market', 'seviye' => $ortak['seviye']];

require __DIR__ . '/ortak/ust.php';
?>
<?php if ($urunler === []): ?>
<section class="bolum">
    <div class="bos-durum">
        <span class="bos-durum-ikon" aria-hidden="true"><i class="fa-solid fa-store"></i></span>
        <h2><?= t('market.bos_baslik') ?></h2>
        <p><?= t('market.bos_aciklama') ?></p>
    </div>
</section>
<?php else: ?>
<section class="bolum">
    <div class="cipler" role="group" aria-label="<?= t('ortak.filtre_etiketi') ?>" id="market-filtre">
        <button type="button" class="cip" data-filtre="" aria-pressed="true"><?= t('ortak.filtre_tumu') ?></button>
        <?php foreach (ESYA_TURLERI as $tur => $sonek): ?>
            <button type="button" class="cip" data-filtre="<?= e($tur) ?>" aria-pressed="false"><?= t('ortak.tur_' . $sonek) ?></button>
        <?php endforeach; ?>
    </div>
</section>

<section class="bolum" aria-label="<?= t('market.baslik') ?>">
    <ul class="liste esya-izgara" id="esya-izgara">
        <?php foreach ($urunler as $u):
            $fiyat = (int) floor((float) $u['Fiyat']);
            $sahip = (int) $u['Durum'] >= 0;
            $kusanili = (int) $u['Durum'] === 1;
            $kilitli = !$sahip && (int) $u['GerekenSeviye'] > $ortak['seviye'];
            $eksik = max(0, $fiyat - $ortak['altin']);
            if ($kusanili) {
                $durumMetni = metin('ortak.kusanildi');
            } elseif ($sahip) {
                $durumMetni = metin('market.kart_cantada');
            } elseif ($kilitli) {
                $durumMetni = metin('market.kart_kilit', ['seviye' => (int) $u['GerekenSeviye']]);
            } elseif ($eksik > 0) {
                $durumMetni = metin('market.kart_eksik', ['eksik' => sayi_tr($eksik)]);
            } else {
                $durumMetni = '';
            }
            // Nadirlik verisi henüz yok: etiketten nadirlik kısmı çıkarılır.
            $kartEtiketi = trim(preg_replace('/\s+/', ' ', metin('market.kart_etiketi', [
                'esya' => $u['Ad'], 'nadirlik' => '', 'tur' => tur_adi((string) $u['EsyaTuru']),
                'fiyat' => sayi_tr($fiyat), 'durum' => $durumMetni,
            ])));
        ?>
        <li data-tur="<?= e($u['EsyaTuru']) ?>">
            <button type="button" class="esya-karti" style="width:100%"
                    data-esya-id="<?= (int) $u['EsyaID'] ?>" data-ad="<?= e($u['Ad']) ?>" data-fiyat="<?= $fiyat ?>"
                    data-seviye="<?= (int) $u['GerekenSeviye'] ?>" data-sahip="<?= $sahip ? '1' : '0' ?>"
                    aria-label="<?= e($kartEtiketi) ?>">
                <span class="esya-ikon-kutu" aria-hidden="true">
                    <i class="fa-solid <?= e($u['GorselURL']) ?>"></i>
                    <?php if ($kilitli): ?><span class="esya-kilit"><i class="fa-solid fa-lock" style="font-size:24px;color:var(--metin)"></i></span><?php endif; ?>
                </span>
                <span class="esya-ad" aria-hidden="true"><?= e($u['Ad']) ?></span>
                <span class="esya-tur" aria-hidden="true"><?= e(tur_adi((string) $u['EsyaTuru'])) ?></span>
                <?php if (!$sahip): ?>
                    <span class="esya-fiyat" aria-hidden="true"><span class="altin-ikon"></span><?= e(sayi_tr($fiyat)) ?></span>
                <?php endif; ?>
                <span class="esya-durum<?= $kusanili ? ' kusanildi' : '' ?>" aria-hidden="true"><?= e($durumMetni) ?></span>
            </button>
        </li>
        <?php endforeach; ?>
    </ul>
    <div class="bos-durum" id="filtre-bos" hidden>
        <span class="bos-durum-ikon" aria-hidden="true"><i class="fa-solid fa-box-open"></i></span>
        <p><?= t('market.filtre_bos') ?></p>
        <button type="button" class="dugme dugme-ikincil" id="filtre-temizle"><?= t('market.filtre_bos_dugme') ?></button>
    </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/ortak/alt.php'; ?>
