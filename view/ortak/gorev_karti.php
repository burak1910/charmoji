<?php
// view/ortak/gorev_karti.php
// Görev kartı bileşeni (docs/ux/03-gorevler.md §3). Karargah'ta menüsüz, Görevler'de
// menülü (yalnızca "Sil"; "Düzenle" sonraki aşamada) kullanılır.
if (!defined('CHARMOJI_SAYFA')) {
    http_response_code(404);
    exit();
}

/** Onay kutusunun tik ikonu. */
const GOREV_TIK_SVG = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M4 12l5 5L20 6" fill="none" stroke="#17142A" stroke-width="3"/></svg>';

/**
 * @param array $g    gorev_kart_verisi() çıktısı
 * @param bool  $menu kart menüsü (⋯) gösterilsin mi
 */
function gorev_karti_html(array $g, bool $menu): string
{
    $yetenekAdi = yetenek_adi($g['yetenek']);
    $sinif = isset(YETENEKLER[$g['yetenek']]) ? ' y-' . YETENEKLER[$g['yetenek']] : '';
    $etiket = $g['tamam']
        ? metin('gorevler.kart_tamamlandi_etiketi', ['gorev' => $g['ad'], 'xp' => $g['xp']])
        : metin('gorevler.kart_onay_etiketi', ['gorev' => $g['ad'], 'xp' => $g['xp'], 'yetenek' => $yetenekAdi]);
    if ($g['bozukSeri'] > 0 && !$g['tamam']) {
        $etiket .= ' ' . metin('gorevler.kart_seri_bozuldu', ['onceki' => $g['bozukSeri']]);
    }
    $id = (int) $g['id'];

    ob_start(); ?>
<li class="gorev-karti<?= e($sinif) ?>" id="gorev-<?= $id ?>"
    data-gorev-id="<?= $id ?>" data-ad="<?= e($g['ad']) ?>" data-yetenek="<?= e($g['yetenek']) ?>"
    data-yetenek-adi="<?= e($yetenekAdi) ?>" data-xp="<?= (int) $g['xp'] ?>" data-taban="<?= (int) $g['taban'] ?>" data-tamam="<?= $g['tamam'] ? '1' : '0' ?>">
    <button type="button" class="gorev-onay" role="checkbox" aria-checked="<?= $g['tamam'] ? 'true' : 'false' ?>"
            aria-label="<?= e($etiket) ?>" data-veri-degistirir>
        <span class="gorev-onay-kutu"><?= GOREV_TIK_SVG ?></span>
    </button>
    <div class="gorev-govde">
        <p class="gorev-ad"><?= e($g['ad']) ?></p>
        <div class="gorev-bilgi" aria-hidden="true">
            <span class="yetenek-etiket"><?= e($yetenekAdi) ?></span>
            <?php if ($g['tamam']): ?>
                <span class="gorev-tamam-etiket"><i class="fa-solid fa-check"></i> <?= t('gorevler.kart_tamamlandi') ?></span>
                <span class="gorev-odul"><?= t('gorevler.kart_kazanildi', ['xp' => $g['xp']]) ?></span>
            <?php else: ?>
                <span class="gorev-odul"><?= t('gorevler.kart_odul', ['xp' => $g['xp']]) ?></span>
            <?php endif; ?>
            <span class="gorev-seri"><i class="fa-solid fa-fire"></i>
                <?= $g['etkinSeri'] > 0 ? t('gorevler.kart_seri', ['seri' => $g['etkinSeri']]) : t('gorevler.kart_seri_yok') ?></span>
            <?php if ($g['carpan'] > 1): ?>
                <span class="gorev-bonus"><?= t('gorevler.kart_bonus', ['carpan' => carpan_tr($g['carpan'])]) ?></span>
            <?php endif; ?>
        </div>
        <?php if ($g['bozukSeri'] > 0 && !$g['tamam']): ?>
            <p class="gorev-not" aria-hidden="true"><?= t('gorevler.kart_seri_bozuldu', ['onceki' => $g['bozukSeri']]) ?></p>
        <?php endif; ?>
    </div>
    <?php if ($menu): ?>
        <button type="button" class="menu-dugme" aria-controls="gorev-menu-<?= $id ?>"
                aria-label="<?= t('gorevler.kart_menu_etiketi', ['gorev' => $g['ad']]) ?>">&#8943;</button>
        <ul class="acilir-menu" id="gorev-menu-<?= $id ?>" role="menu" hidden>
            <li role="none"><button type="button" role="menuitem" class="tehlike" data-eylem="sil" data-veri-degistirir><?= t('gorevler.kart_menu_sil') ?></button></li>
        </ul>
    <?php endif; ?>
</li>
<?php
    return (string) ob_get_clean();
}
