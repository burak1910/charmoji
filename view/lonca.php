<?php
// view/lonca.php - Lonca sekmesi (E40, docs/ux/05-lonca.md)
// İç sekmeler: Sıralama (E40a; sonraki aşama, şimdilik yer tutucu), Arkadaşlar (E40b,
// arkadaşlıktan çıkarma M06), İstekler (E40c), Maceracı bul (E40d, arama + öneriler).
// Arkadaş profili (E44) sonraki aşamadadır.
require_once __DIR__ . '/ortak/cekirdek.php';
require_once __DIR__ . '/../service/FriendManager.php';

$ortak = sayfa_baslat();
$conn = $ortak['conn'];
$kullaniciID = $ortak['id'];

$arkadaslar = FriendManager::getFriends($kullaniciID);
$gelenler = FriendManager::getPendingRequests($kullaniciID);
$gidenler = FriendManager::getSentRequests($kullaniciID);
$oneriler = array_slice(FriendManager::getCommunitySuggestions($kullaniciID), 0, 10);
$seviyeler = karakter_seviyeleri($conn, array_merge(
    array_column($arkadaslar, 'KullaniciID'), array_column($gelenler, 'KullaniciID'),
    array_column($gidenler, 'KullaniciID'), array_column($oneriler, 'KullaniciID')
));
$sv = fn(array $k): int => $seviyeler[(int) $k['KullaniciID']] ?? 1;
$istekSayisi = count($gelenler);

/** Arkadaş satırı (E40b). Satırın kendisi E44 gelene kadar bağlantı değildir. */
function arkadas_satiri_html(array $a, int $seviye): string
{
    $id = (int) $a['ArkadaslikID'];
    ob_start(); ?>
<li class="kisi" id="arkadas-<?= $id ?>" data-arkadaslik-id="<?= $id ?>" data-ad="<?= e($a['Ad']) ?>">
    <span class="kisi-avatar" aria-hidden="true"><?= renderAvatar($a['AvatarVerisi'] ?? null, '48px') ?></span>
    <div class="kisi-govde">
        <p class="kisi-ad"><?= e($a['Ad']) ?></p>
        <p class="kisi-alt"><?= t('ortak.seviye_kisa', ['seviye' => $seviye]) ?></p>
    </div>
    <button type="button" class="menu-dugme" aria-controls="arkadas-menu-<?= $id ?>"
            aria-label="<?= t('lonca.arkadas_menu_etiketi', ['ad' => $a['Ad']]) ?>">&#8943;</button>
    <ul class="acilir-menu" id="arkadas-menu-<?= $id ?>" role="menu" hidden>
        <li role="none"><button type="button" role="menuitem" class="tehlike" data-eylem="cikar" data-veri-degistirir><?= t('lonca.profil_cikar') ?></button></li>
    </ul>
</li>
<?php
    return (string) ob_get_clean();
}

$sayfa = ['sekme' => 'lonca', 'baslik' => metin('lonca.baslik')];
$sayfaBetigi = 'lonca.js';
$sayfaMetinleri = [
    'ortak.seviye_kisa', 'lonca.sekme_istekler', 'lonca.sekme_istekler_sayili', 'lonca.arkadaslar_sayi',
    'lonca.arkadas_menu_etiketi', 'lonca.profil_cikar', 'lonca.istek_kabul_bildirim', 'lonca.istek_red_bildirim',
    'lonca.istek_hata_islenemedi', 'lonca.istek_hata_artik_yok', 'lonca.istek_bekliyor',
    'lonca.arama_kisa', 'lonca.arama_sonuc_yok', 'lonca.arama_sonuc_sayisi', 'lonca.arama_hata',
    'lonca.istek_gonder', 'lonca.istek_gonder_etiketi', 'lonca.istek_gonderildi', 'lonca.istek_gonderildi_bildirim',
    'lonca.istek_gonder_hata', 'lonca.istek_zaten_var', 'lonca.cikar_onay_baslik', 'lonca.cikar_onay_aciklama',
    'lonca.cikar_onay_dugme', 'lonca.cikarildi_bildirim', 'lonca.cikar_hata', 'lonca.profil_hata_yok',
];
$sayfaVerisi = ['sayfa' => 'lonca', 'istekSayisi' => $istekSayisi];

require __DIR__ . '/ortak/ust.php';
?>
<div class="ic-sekmeler" role="tablist" aria-label="<?= t('lonca.baslik') ?>" id="lonca-sekmeler">
    <button type="button" role="tab" class="ic-sekme" id="sekme-siralama" aria-controls="panel-siralama" data-capa="siralama" aria-selected="true"><?= t('lonca.sekme_siralama') ?></button>
    <button type="button" role="tab" class="ic-sekme" id="sekme-arkadaslar" aria-controls="panel-arkadaslar" data-capa="arkadaslar" aria-selected="false" tabindex="-1"><?= t('lonca.sekme_arkadaslar') ?></button>
    <button type="button" role="tab" class="ic-sekme" id="sekme-istekler" aria-controls="panel-istekler" data-capa="istekler" aria-selected="false" tabindex="-1"
            <?= $istekSayisi > 0 ? 'aria-label="' . t('lonca.sekme_istekler_sayili', ['sayi' => $istekSayisi]) . '"' : '' ?>>
        <?= t('lonca.sekme_istekler') ?><?php if ($istekSayisi > 0): ?><span class="rozet-sayi" aria-hidden="true"><?= e($istekSayisi > 9 ? '9+' : $istekSayisi) ?></span><?php endif; ?>
    </button>
    <button type="button" role="tab" class="ic-sekme" id="sekme-bul" aria-controls="panel-bul" data-capa="bul" aria-selected="false" tabindex="-1"><?= t('lonca.sekme_bul') ?></button>
</div>

<!-- E40a Sıralama: haftalık sıralama sonraki aşamada; şimdilik yer tutucu -->
<section role="tabpanel" id="panel-siralama" aria-labelledby="sekme-siralama" tabindex="0">
    <h2 class="bolum-baslik"><?= t('lonca.siralama_baslik') ?></h2>
    <div class="bos-durum">
        <span class="bos-durum-ikon" aria-hidden="true"><i class="fa-solid fa-flag"></i></span>
        <p><?= t('yerel.siralama_yakinda') ?></p>
        <p class="soluk-metin"><?= t('lonca.siralama_kural') ?></p>
        <a class="dugme dugme-ikincil" href="#bul" data-sekmeye="bul"><?= t('lonca.siralama_tek_dugme') ?></a>
    </div>
</section>

<!-- E40b Arkadaşlar -->
<section role="tabpanel" id="panel-arkadaslar" aria-labelledby="sekme-arkadaslar" tabindex="0" hidden>
    <p class="ikincil-metin" id="arkadas-sayisi" <?= $arkadaslar === [] ? 'hidden' : '' ?>><?= t('lonca.arkadaslar_sayi', ['sayi' => count($arkadaslar)]) ?></p>
    <ul class="liste" id="arkadas-listesi">
        <?php foreach ($arkadaslar as $a) { echo arkadas_satiri_html($a, $sv($a)); } ?>
    </ul>
    <div class="bos-durum" id="arkadas-bos" <?= $arkadaslar === [] ? '' : 'hidden' ?>>
        <span class="bos-durum-ikon" aria-hidden="true"><i class="fa-solid fa-flag"></i></span>
        <h2><?= t('lonca.arkadaslar_bos_baslik') ?></h2>
        <p><?= t('lonca.arkadaslar_bos_aciklama') ?></p>
        <a class="dugme dugme-birincil" href="#bul" data-sekmeye="bul"><?= t('lonca.arkadaslar_bos_dugme') ?></a>
    </div>
</section>

<!-- E40c İstekler -->
<section role="tabpanel" id="panel-istekler" aria-labelledby="sekme-istekler" tabindex="0" hidden>
    <h2 class="bolum-baslik"><?= t('lonca.istekler_gelen_baslik') ?></h2>
    <ul class="liste" id="gelen-listesi">
        <?php foreach ($gelenler as $g): ?>
        <li class="kisi istek-karti" id="istek-<?= (int) $g['IstekID'] ?>" data-istek-id="<?= (int) $g['IstekID'] ?>"
            data-ad="<?= e($g['Ad']) ?>" data-seviye="<?= $sv($g) ?>" style="display:grid;grid-template-columns:48px 1fr">
            <span class="kisi-avatar" aria-hidden="true"><?= renderAvatar($g['AvatarVerisi'] ?? null, '48px') ?></span>
            <div class="kisi-govde">
                <p style="margin:0;overflow-wrap:anywhere"><?= t('lonca.istek_metni', ['ad' => $g['Ad']]) ?></p>
                <p class="kisi-alt"><?= t('ortak.seviye_kisa', ['seviye' => $sv($g)]) ?></p>
            </div>
            <div class="istek-dugmeler" style="grid-column:1 / -1">
                <button type="button" class="dugme dugme-birincil" data-istek-eylem="kabul" data-veri-degistirir
                        aria-label="<?= t('lonca.istek_kabul_etiketi', ['ad' => $g['Ad']]) ?>"><?= t('lonca.istek_kabul') ?></button>
                <button type="button" class="dugme dugme-ikincil" data-istek-eylem="red" data-veri-degistirir
                        aria-label="<?= t('lonca.istek_reddet_etiketi', ['ad' => $g['Ad']]) ?>"><?= t('lonca.istek_reddet') ?></button>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
    <div class="bos-durum" id="gelen-bos" <?= $gelenler === [] ? '' : 'hidden' ?>>
        <span class="bos-durum-ikon" aria-hidden="true"><i class="fa-solid fa-envelope-open"></i></span>
        <p><?= t('lonca.istekler_bos') ?></p>
    </div>

    <div class="bolum" id="giden-bolum" <?= $gidenler === [] ? 'hidden' : '' ?>>
        <h2 class="bolum-baslik"><?= t('lonca.istekler_giden_baslik') ?></h2>
        <ul class="liste" id="giden-listesi">
            <?php foreach ($gidenler as $g): ?>
            <li class="kisi">
                <span class="kisi-avatar" aria-hidden="true"><?= renderAvatar($g['AvatarVerisi'] ?? null, '48px') ?></span>
                <div class="kisi-govde">
                    <p class="kisi-ad"><?= e($g['Ad']) ?></p>
                    <p class="kisi-alt"><?= t('ortak.seviye_kisa', ['seviye' => $sv($g)]) ?></p>
                </div>
                <span class="kisi-eylem soluk-metin"><?= t('lonca.istek_bekliyor') ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<!-- E40d Maceracı bul -->
<section role="tabpanel" id="panel-bul" aria-labelledby="sekme-bul" tabindex="0" hidden>
    <form class="alan" role="search" id="arama-form">
        <label for="arama-kutusu"><?= t('lonca.arama_etiketi') ?></label>
        <input class="girdi" type="search" id="arama-kutusu" name="q" autocomplete="off" maxlength="100"
               placeholder="<?= t('lonca.arama_ipucu') ?>" aria-describedby="arama-durum">
        <span class="soluk-metin" id="arama-durum" aria-live="polite"></span>
    </form>
    <ul class="liste" id="arama-sonuclari" hidden aria-label="<?= t('lonca.arama_etiketi') ?>"></ul>

    <div id="oneriler-bolum">
        <h2 class="bolum-baslik"><?= t('lonca.oneriler_baslik') ?></h2>
        <?php if ($oneriler === []): ?>
            <p class="ikincil-metin"><?= t('lonca.oneriler_bos') ?></p>
        <?php else: ?>
        <ul class="liste" id="oneri-listesi">
            <?php foreach ($oneriler as $o): ?>
            <li class="kisi" data-kullanici-id="<?= (int) $o['KullaniciID'] ?>" data-ad="<?= e($o['Ad']) ?>" data-seviye="<?= $sv($o) ?>">
                <span class="kisi-avatar" aria-hidden="true"><?= renderAvatar($o['AvatarVerisi'] ?? null, '48px') ?></span>
                <div class="kisi-govde">
                    <p class="kisi-ad"><?= e($o['Ad']) ?></p>
                    <p class="kisi-alt"><?= t('ortak.seviye_kisa', ['seviye' => $sv($o)]) ?></p>
                </div>
                <button type="button" class="dugme dugme-ikincil dugme-kucuk kisi-eylem" data-istek-gonder data-veri-degistirir
                        aria-label="<?= t('lonca.istek_gonder_etiketi', ['ad' => $o['Ad']]) ?>"><?= t('lonca.istek_gonder') ?></button>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/ortak/alt.php'; ?>
