<?php
// view/ortak/alt.php
// Sayfa sonu: <main> kapanışı, G01 alt menü, G03 bildirim alanı, G07 onay alt sayfası,
// G08 oturum bitti penceresi, ekran okuyucu duyuru alanı ve ortak betik.
// Beklenen değişkenler: $ortak, $sayfa (bkz. ust.php). İsteğe bağlı:
//   $sayfaMetinleri : JS'nin ihtiyaç duyduğu ek metin anahtarları (dizi)
//   $sayfaBetigi    : sayfaya özel betik dosyası (statik/ altında, ör. 'gorevler.js')
//   $sayfaVerisi    : sayfa betiğine aktarılacak veri (JSON'a çevrilir)
if (!defined('CHARMOJI_SAYFA')) {
    http_response_code(404);
    exit();
}

$menu = [
    'karargah' => ['adres' => 'karargah.php', 'ikon' => '<path d="M3 21V9l3-2v3h3V5l3-2 3 2v5h3V7l3 2v12h-7v-5h-4v5z"/>'],
    'gorevler' => ['adres' => 'gorevler.php', 'ikon' => '<path d="M5 3h12l2 2v16H7l-2-2zm4 5v2h8V8zm0 4v2h8v-2zm0 4v2h5v-2z"/>'],
    'market'   => ['adres' => 'market.php',   'ikon' => '<path d="M2 9l2-6h16l2 6v2h-2v10H4V11H2zm6 4v6h3v-6zm5 0v3h5v-3z"/>'],
    'lonca'    => ['adres' => 'lonca.php',    'ikon' => '<path d="M5 2h2v20H5zm3 1h12l-3 5 3 5H8z"/>'],
    'karakter' => ['adres' => 'karakter.php', 'ikon' => '<path d="M6 10a6 6 0 0 1 12 0v4h-3v-3H9v3H6zm-2 6h16v6H4z"/>'],
];
// Bekleyen istek varsa Lonca rozetinden gelen oyuncu doğrudan İstekler'e iner (05 §2).
if ($ortak['rozetIstek'] > 0) {
    $menu['lonca']['adres'] = 'lonca.php#istekler';
}

$jsMetinAnahtarlari = array_merge([
    'ortak.kapat', 'ortak.vazgec', 'ortak.geri_al', 'ortak.tekrar_dene', 'ortak.yukleniyor',
    'ortak.hata_genel', 'ortak.istek_basarisiz', 'ortak.cevrimdisi_bandi', 'ortak.cevrimici_geri_geldi',
    'ortak.cevrimdisi_islem', 'ortak.altin_etiketi', 'ortak.seviye_kisa', 'ortak.seviye_rozeti_etiketi',
    'ortak.rozet_odul_etiketi', 'ortak.rozet_istek_etiketi', 'ortak.menu_gorevler', 'ortak.menu_lonca',
], $sayfaMetinleri ?? []);
$jsMetinler = [];
foreach (array_unique($jsMetinAnahtarlari) as $anahtar) {
    $jsMetinler[$anahtar] = metin($anahtar);
}
$jsonBayraklari = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
</main>

<nav class="alt-menu" aria-label="<?= t('ortak.alt_menu_etiketi') ?>">
    <ul>
        <?php foreach ($menu as $anahtar => $oge):
            $etkin = $sayfa['sekme'] === $anahtar;
            $ekEtiket = '';
            $rozet = '';
            if ($anahtar === 'gorevler' && $ortak['rozetOdul']) {
                $ekEtiket = ', ' . metin('ortak.rozet_odul_etiketi');
                $rozet = '<span class="rozet-nokta" aria-hidden="true"></span>';
            } elseif ($anahtar === 'lonca' && $ortak['rozetIstek'] > 0) {
                $ekEtiket = ', ' . metin('ortak.rozet_istek_etiketi', ['sayi' => $ortak['rozetIstek']]);
                $rozet = '<span class="rozet-sayi" aria-hidden="true">' . e($ortak['rozetIstek'] > 9 ? '9+' : $ortak['rozetIstek']) . '</span>';
            }
        ?>
        <li>
            <a href="<?= e($oge['adres']) ?>" class="alt-menu-oge<?= $etkin ? ' etkin' : '' ?>" id="menu-<?= e($anahtar) ?>"
               <?= $etkin ? 'aria-current="page"' : '' ?>
               aria-label="<?= e(metin('ortak.menu_' . $anahtar) . $ekEtiket) ?>">
                <span class="alt-menu-ikon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><?= $oge['ikon'] ?></svg>
                    <?= $rozet ?>
                </span>
                <span class="alt-menu-etiket" aria-hidden="true"><?= t('ortak.menu_' . $anahtar) ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</nav>

<!-- G03 bildirim (tek seferde bir tane) -->
<div class="bildirim-alani" id="bildirim-alani"></div>

<!-- Ekran okuyucu duyuruları -->
<div class="sr-only" id="duyuru-polite" aria-live="polite"></div>
<div class="sr-only" id="duyuru-assertive" aria-live="assertive"></div>

<!-- G07 onay alt sayfası -->
<div class="alt-sayfa-arka" id="onay-arka" hidden>
    <div class="alt-sayfa" id="onay-sayfa" role="dialog" aria-modal="true" aria-labelledby="onay-baslik" aria-describedby="onay-aciklama">
        <h2 class="alt-sayfa-baslik" id="onay-baslik"></h2>
        <p class="alt-sayfa-aciklama" id="onay-aciklama"></p>
        <p class="alt-sayfa-not" id="onay-not" hidden></p>
        <div class="alt-sayfa-dugmeler">
            <button type="button" class="dugme dugme-birincil" id="onay-birincil"></button>
            <button type="button" class="dugme dugme-ikincil" id="onay-ikincil"></button>
        </div>
    </div>
</div>

<!-- G08 oturum sona erdi -->
<div class="alt-sayfa-arka" id="oturum-arka" hidden>
    <div class="alt-sayfa" role="alertdialog" aria-modal="true" aria-labelledby="oturum-baslik" aria-describedby="oturum-aciklama">
        <h2 class="alt-sayfa-baslik" id="oturum-baslik"><?= t('ortak.oturum_bitti_baslik') ?></h2>
        <p class="alt-sayfa-aciklama" id="oturum-aciklama"><?= t('ortak.oturum_bitti_aciklama') ?></p>
        <div class="alt-sayfa-dugmeler">
            <a class="dugme dugme-birincil" href="login.php" id="oturum-giris"><?= t('ortak.tekrar_giris_yap') ?></a>
        </div>
    </div>
</div>

<script>
    // AJAX istekleri için CSRF jetonu (X-CSRF-Token başlığında gönderilir).
    const CSRF_JETONU = <?= json_encode(csrf_jetonu()) ?>;
    window.CHARMOJI = {
        metinler: <?= json_encode($jsMetinler, $jsonBayraklari) ?>,
        bildirim: <?= json_encode($ortak['bildirim'], $jsonBayraklari) ?>,
        bugun: <?= json_encode($ortak['bugun']) ?>,
        veri: <?= json_encode($sayfaVerisi ?? null, $jsonBayraklari) ?>
    };
</script>
<script src="statik/charmoji.js"></script>
<?php if (!empty($sayfaBetigi)): ?>
<script src="statik/<?= e($sayfaBetigi) ?>"></script>
<?php endif; ?>
</body>
</html>
