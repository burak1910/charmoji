<?php
// view/avatar.php - Karakter sekmesinin alt ekranı: Görünüm (E51) ve Çanta (E52)
// (docs/ux/06-karakter.md §5, §6). Görünüm: piksel büst için hazır seçenekler (ten 4,
// saç 6, üst giysi 4; VARSAYIM S27). Eşya çıkarma (S23) sonraki aşamadadır.
require_once __DIR__ . '/ortak/cekirdek.php';

$ortak = sayfa_baslat();
$conn = $ortak['conn'];
$kullaniciID = $ortak['id'];

// Kayıtlı seçenekler doğrulanır: tanımsız ya da eski serbest renk verisi 1'e düşer (S28).
$secim = avatar_secenekleri_temizle($ortak['avatar'] ?? null);

$envanter = vt_satirlar($conn,
    "SELECT KE.KusandiMi, M.EsyaID, M.Ad, M.GorselURL, M.EsyaTuru
       FROM KullaniciEnvanteri KE
       JOIN MarketEsyalari M ON KE.EsyaID = M.EsyaID
      WHERE KE.KullaniciID = ?
      ORDER BY M.EsyaTuru, M.Ad", "i", [$kullaniciID]);
$kusanilanlar = array_values(array_filter($envanter, fn($item) => (int) $item['KusandiMi'] === 1));
$baslangicTuru = is_string($_GET['tur'] ?? null) && isset(ESYA_TURLERI[$_GET['tur']]) ? $_GET['tur'] : '';

$bolgeBasliklari = [
    'ten' => 'gorunum.ten',
    'sac' => 'gorunum.sac',
    'ust' => 'gorunum.kiyafet',
];
$secenekAdi = fn(string $bolge, int $n): string => metin(AVATAR_SECENEK_METINLERI[$bolge] . $n);

$sayfa = ['sekme' => 'karakter', 'baslik' => metin('gorunum.baslik'), 'geri' => 'karakter.php'];
$sayfaBetigi = 'avatar.js';
$sayfaMetinleri = [
    'gorunum.baslik', 'canta.baslik', 'gorunum.kaydedildi_bildirim', 'gorunum.hata_kaydedilemedi',
    'gorunum.hata_gecersiz_secenek', 'gorunum.secili', 'ortak.kusan', 'ortak.kusanildi',
    'ortak.kusanildi_bildirim', 'ortak.hata_kusanilamadi',
];
$sayfaVerisi = [
    'sayfa' => 'avatar',
    'tur' => $baslangicTuru,
    'secenekler' => $secim,
    // Önizleme yalnızca bu sabit tablodaki yolları kullanır (kullanıcı verisinden yol kurulmaz).
    'katmanlar' => avatar_katman_haritasi(),
];

require __DIR__ . '/ortak/ust.php';
?>
<section class="bolum onizleme-alan">
    <div class="karakter-kutu" id="onizleme" role="img" aria-label="<?= t('karakter.karakter_etiketi', ['ad' => $ortak['ad'], 'seviye' => $ortak['seviye'], 'esyalar' => $kusanilanlar === [] ? 'hiçbiri' : implode(', ', array_column($kusanilanlar, 'Ad'))]) ?>">
        <span aria-hidden="true"><?= renderAvatar($ortak['avatar'], '200px', $kusanilanlar) ?></span>
    </div>
</section>

<div class="ic-sekmeler" role="tablist" aria-label="<?= t('karakter.baslik') ?>" id="avatar-sekmeler" style="margin-top:8px">
    <button type="button" role="tab" class="ic-sekme" id="sekme-gorunum" aria-controls="panel-gorunum" data-capa="gorunum" aria-selected="true"><?= t('gorunum.baslik') ?></button>
    <button type="button" role="tab" class="ic-sekme" id="sekme-canta" aria-controls="panel-canta" data-capa="canta" aria-selected="false" tabindex="-1"><?= t('canta.baslik') ?></button>
</div>

<!-- E51 Görünüm: her bölge bir radiogroup; kutularda o seçenekle küçük büst -->
<section role="tabpanel" id="panel-gorunum" aria-labelledby="sekme-gorunum">
    <?php foreach (AVATAR_SECENEK_SAYILARI as $bolge => $sayi): ?>
    <div class="gorunum-bolge">
        <div class="gorunum-bolge-baslik">
            <h2 id="bolge-<?= e($bolge) ?>"><?= t($bolgeBasliklari[$bolge]) ?></h2>
            <span class="ikincil-metin kucuk" id="secili-<?= e($bolge) ?>"><?= t('gorunum.secili', ['secenek' => $secenekAdi($bolge, $secim[$bolge])]) ?></span>
        </div>
        <div class="gorunum-secenekler" role="radiogroup" aria-labelledby="bolge-<?= e($bolge) ?>" data-bolge="<?= e($bolge) ?>">
            <?php for ($n = 1; $n <= $sayi; $n++):
                $secili = $secim[$bolge] === $n;
                $kutuSecim = array_merge($secim, [$bolge => $n]); ?>
            <button type="button" role="radio" class="secenek-kutu" aria-checked="<?= $secili ? 'true' : 'false' ?>"
                    tabindex="<?= $secili ? '0' : '-1' ?>" data-bolge="<?= e($bolge) ?>" data-n="<?= $n ?>"
                    data-ad="<?= e($secenekAdi($bolge, $n)) ?>" aria-label="<?= e($secenekAdi($bolge, $n)) ?>">
                <?= renderAvatar(null, '56px', [], 'yakin', $kutuSecim) ?>
                <span class="secenek-tik" aria-hidden="true">&#10003;</span>
            </button>
            <?php endfor; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <div class="alt-sayfa-dugmeler">
        <button type="button" class="dugme dugme-birincil" id="gorunum-kaydet" disabled data-veri-degistirir><?= t('ortak.kaydet') ?></button>
    </div>
</section>

<!-- E52 Çanta -->
<section role="tabpanel" id="panel-canta" aria-labelledby="sekme-canta" hidden>
    <?php if ($envanter === []): ?>
        <div class="bos-durum">
            <span class="bos-durum-ikon" aria-hidden="true"><i class="fa-solid fa-bag-shopping"></i></span>
            <h2><?= t('canta.bos_baslik') ?></h2>
            <p><?= t('canta.bos_aciklama') ?></p>
            <a class="dugme dugme-birincil" href="market.php"><?= t('canta.bos_dugme') ?></a>
        </div>
    <?php else: ?>
        <div class="cipler" role="group" aria-label="<?= t('ortak.filtre_etiketi') ?>" id="canta-filtre" style="margin-bottom:12px">
            <button type="button" class="cip" data-filtre="" aria-pressed="true"><?= t('ortak.filtre_tumu') ?></button>
            <?php foreach (ESYA_TURLERI as $tur => $sonek): ?>
                <button type="button" class="cip" data-filtre="<?= e($tur) ?>" aria-pressed="false"><?= t('ortak.tur_' . $sonek) ?></button>
            <?php endforeach; ?>
        </div>
        <ul class="liste esya-izgara" id="canta-izgara" style="grid-template-columns:repeat(3,1fr)">
            <?php foreach ($envanter as $item):
                $kusanili = (int) $item['KusandiMi'] === 1;
                [$katmanYuva, $katmanHtml] = esya_katman_html($item, '200px'); ?>
            <li data-tur="<?= e($item['EsyaTuru']) ?>">
                <button type="button" class="esya-karti item-card<?= $kusanili ? ' secili' : '' ?>" style="width:100%;min-height:112px"
                        aria-pressed="false" data-esya-id="<?= (int) $item['EsyaID'] ?>" data-ad="<?= e($item['Ad']) ?>"
                        data-tur="<?= e($item['EsyaTuru']) ?>" data-kusanili="<?= $kusanili ? '1' : '0' ?>"
                        aria-label="<?= e($item['Ad'] . ', ' . tur_adi((string) $item['EsyaTuru']) . '.' . ($kusanili ? ' ' . metin('ortak.kusanildi') : '')) ?>">
                    <span class="esya-ikon-kutu" style="height:64px" aria-hidden="true"><?= esya_ikon_html($item, 56, 'canta-ikon') ?></span>
                    <span class="esya-ad kucuk" aria-hidden="true"><?= e($item['Ad']) ?></span>
                    <span class="esya-durum kusanildi" aria-hidden="true"><?= $kusanili ? t('ortak.kusanildi') : '' ?></span>
                </button>
                <?php if ($katmanYuva !== ''): // önizleme katmanı: sunucuda sabit tablodan üretilir ?>
                <template data-katman-yuva="<?= e($katmanYuva) ?>"><?= $katmanHtml ?></template>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <div class="alt-sayfa-dugmeler">
            <button type="button" class="dugme dugme-birincil" id="kusan-dugme" hidden data-veri-degistirir><?= t('ortak.kusan') ?></button>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/ortak/alt.php'; ?>
