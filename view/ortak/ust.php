<?php
// view/ortak/ust.php
// Sayfa başı: <head>, G02 üst bar, G04 bağlantı bandı ve <main> açılışı.
// Beklenen değişkenler:
//   $ortak : sayfa_baslat() sonucu
//   $sayfa : ['sekme' => karargah|gorevler|market|lonca|karakter,
//             'baslik' => sekme başlığı (düz metin),
//             'geri'   => (isteğe bağlı) alt ekranlarda geri düğmesinin adresi]
if (!defined('CHARMOJI_SAYFA')) {
    http_response_code(404);
    exit();
}
$geriAdresi = $sayfa['geri'] ?? null;
?><!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#17142A">
    <title><?= e($sayfa['baslik']) ?> - Charmoji</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Pixelify+Sans:wght@500;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="statik/charmoji.css">
</head>
<body class="sekme-<?= e($sayfa['sekme']) ?>">
<a class="atla-baglanti" href="#icerik">İçeriğe geç</a>

<header class="ust-bar" id="ust-bar">
    <div class="ust-bar-ic">
        <?php if ($geriAdresi !== null): ?>
            <a class="ust-geri" href="<?= e($geriAdresi) ?>" aria-label="<?= t('ortak.geri') ?>">
                <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="square"/></svg>
            </a>
        <?php else: ?>
            <a class="ust-seviye" href="karakter.php" id="ust-seviye"
               aria-label="<?= t('ortak.seviye_rozeti_etiketi', ['seviye' => $ortak['seviye']]) ?>">
                <span aria-hidden="true" class="ust-seviye-metin"><?= t('ortak.seviye_kisa', ['seviye' => $ortak['seviye']]) ?></span>
            </a>
        <?php endif; ?>
        <h1 class="ust-baslik"><?= e($sayfa['baslik']) ?></h1>
        <a class="ust-altin" href="market.php" id="ust-altin"
           aria-label="<?= t('ortak.altin_etiketi', ['altin' => sayi_tr($ortak['altin'])]) ?>">
            <span class="altin-ikon" aria-hidden="true"></span>
            <span aria-hidden="true" class="ust-altin-metin" data-altin="<?= (int) $ortak['altin'] ?>"><?= e(sayi_tr($ortak['altin'])) ?></span>
        </a>
    </div>
    <div class="baglanti-bandi" id="baglanti-bandi" role="status" aria-live="polite" hidden></div>
</header>

<main id="icerik" class="icerik" tabindex="-1">
