<?php
// view/avatar.php - Karakter sekmesinin alt ekranı: Görünüm (E51) ve Çanta (E52)
// (docs/ux/06-karakter.md §5, §6). Bu aşamada görünüm hâlâ serbest renk seçimidir;
// piksel seçenekler (S27) ve eşya çıkarma (S23) sonraki aşamadadır.
require_once __DIR__ . '/ortak/cekirdek.php';

$ortak = sayfa_baslat();
$conn = $ortak['conn'];
$kullaniciID = $ortak['id'];

// Renkler doğrulanır (izinli anahtar + #RRGGBB); geçersiz değer varsayılana düşer
// ve HTML'e basılmadan önce kaçışlanır.
$avatarData = array_map(
    fn($renk) => htmlspecialchars($renk, ENT_QUOTES, 'UTF-8'),
    avatar_renklerini_temizle($ortak['avatar'] ?? null)
);

$envanter = vt_satirlar($conn,
    "SELECT KE.KusandiMi, M.EsyaID, M.Ad, M.GorselURL, M.EsyaTuru
       FROM KullaniciEnvanteri KE
       JOIN MarketEsyalari M ON KE.EsyaID = M.EsyaID
      WHERE KE.KullaniciID = ?
      ORDER BY M.EsyaTuru, M.Ad", "i", [$kullaniciID]);
$kusanilanlar = array_values(array_filter($envanter, fn($item) => (int) $item['KusandiMi'] === 1));
$baslangicTuru = is_string($_GET['tur'] ?? null) && isset(ESYA_TURLERI[$_GET['tur']]) ? $_GET['tur'] : '';

$renkAlanlari = [
    'skinColor' => 'gorunum.ten',
    'hairColor' => 'gorunum.sac',
    'eyeColor'  => 'gorunum.goz',
    'topColor'  => 'gorunum.kiyafet',
];

$sayfa = ['sekme' => 'karakter', 'baslik' => metin('gorunum.baslik'), 'geri' => 'karakter.php'];
$sayfaBetigi = 'avatar.js';
$sayfaMetinleri = [
    'gorunum.baslik', 'canta.baslik', 'gorunum.kaydedildi_bildirim', 'gorunum.hata_kaydedilemedi',
    'gorunum.hata_gecersiz_secenek', 'ortak.kusan', 'ortak.kusanildi', 'ortak.kusanildi_bildirim', 'ortak.hata_kusanilamadi',
];
$sayfaVerisi = ['sayfa' => 'avatar', 'tur' => $baslangicTuru];

require __DIR__ . '/ortak/ust.php';
?>
<section class="bolum" style="position:sticky;top:var(--ust-bar);z-index:5;background:var(--zemin);padding:8px 0;margin:-16px 0 0">
    <div class="karakter-kutu" id="onizleme" role="img" aria-label="<?= t('karakter.karakter_etiketi', ['ad' => $ortak['ad'], 'seviye' => $ortak['seviye'], 'esyalar' => $kusanilanlar === [] ? 'hiçbiri' : implode(', ', array_column($kusanilanlar, 'Ad'))]) ?>">
        <span aria-hidden="true"><?= renderAvatar($ortak['avatar'], '180px', $kusanilanlar) ?></span>
    </div>
</section>

<div class="ic-sekmeler" role="tablist" aria-label="<?= t('karakter.baslik') ?>" id="avatar-sekmeler" style="margin-top:8px">
    <button type="button" role="tab" class="ic-sekme" id="sekme-gorunum" aria-controls="panel-gorunum" data-capa="gorunum" aria-selected="true"><?= t('gorunum.baslik') ?></button>
    <button type="button" role="tab" class="ic-sekme" id="sekme-canta" aria-controls="panel-canta" data-capa="canta" aria-selected="false" tabindex="-1"><?= t('canta.baslik') ?></button>
</div>

<!-- E51 Görünüm -->
<section role="tabpanel" id="panel-gorunum" aria-labelledby="sekme-gorunum">
    <?php foreach ($renkAlanlari as $alan => $anahtar): ?>
    <div class="kart" style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:8px">
        <label for="<?= e($alan) ?>" style="font-weight:600"><?= t($anahtar) ?></label>
        <input type="color" id="<?= e($alan) ?>" value="<?= $avatarData[$alan] ?>" data-ilk="<?= $avatarData[$alan] ?>"
               style="width:64px;height:48px;border:2px solid var(--kenarlik-etkin);border-radius:8px;background:var(--zemin);padding:2px;cursor:pointer">
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
                $kusanili = (int) $item['KusandiMi'] === 1; ?>
            <li data-tur="<?= e($item['EsyaTuru']) ?>">
                <button type="button" class="esya-karti item-card<?= $kusanili ? ' secili' : '' ?>" style="width:100%;min-height:112px"
                        aria-pressed="false" data-esya-id="<?= (int) $item['EsyaID'] ?>" data-ad="<?= e($item['Ad']) ?>"
                        data-tur="<?= e($item['EsyaTuru']) ?>" data-kusanili="<?= $kusanili ? '1' : '0' ?>"
                        aria-label="<?= e($item['Ad'] . ', ' . tur_adi((string) $item['EsyaTuru']) . '.' . ($kusanili ? ' ' . metin('ortak.kusanildi') : '')) ?>">
                    <span class="esya-ikon-kutu" style="height:64px" aria-hidden="true"><i class="fa-solid <?= e($item['GorselURL']) ?>" style="font-size:28px"></i></span>
                    <span class="esya-ad kucuk" aria-hidden="true"><?= e($item['Ad']) ?></span>
                    <span class="esya-durum kusanildi" aria-hidden="true"><?= $kusanili ? t('ortak.kusanildi') : '' ?></span>
                </button>
            </li>
            <?php endforeach; ?>
        </ul>
        <div class="alt-sayfa-dugmeler">
            <button type="button" class="dugme dugme-birincil" id="kusan-dugme" hidden data-veri-degistirir><?= t('ortak.kusan') ?></button>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/ortak/alt.php'; ?>
