<?php
// view/ortak/gorev_karti.php
// Görev kartı bileşeni (docs/ux/03-gorevler.md §3) ve seri bilgisi alt sayfası (M02, §7).
// Karargah'ta menüsüz, Görevler'de menülü ("Düzenle", "Sil") kullanılır. Kart, sayfa
// çiziminde ve AJAX yanıtlarında (view/ajax_islem.php) aynı fonksiyonla üretilir.
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
    $bozuk = $g['bozukSeri'] > 0 && !$g['tamam'];
    if ($bozuk) {
        $etiket .= ' ' . metin('gorevler.kart_seri_bozuldu', ['onceki' => $g['bozukSeri']]);
    }
    $id = (int) $g['id'];
    $carpan = carpan_tr($g['carpan']);

    ob_start(); ?>
<li class="gorev-karti<?= e($sinif) ?>" id="gorev-<?= $id ?>"
    data-gorev-id="<?= $id ?>" data-ad="<?= e($g['ad']) ?>" data-yetenek="<?= e($g['yetenek']) ?>"
    data-yetenek-adi="<?= e($yetenekAdi) ?>" data-xp="<?= (int) $g['xp'] ?>" data-taban="<?= (int) $g['taban'] ?>"
    data-tamam="<?= $g['tamam'] ? '1' : '0' ?>" data-etkin-seri="<?= (int) $g['etkinSeri'] ?>">
    <button type="button" class="gorev-onay" role="checkbox" aria-checked="<?= $g['tamam'] ? 'true' : 'false' ?>"
            aria-label="<?= e($etiket) ?>" data-veri-degistirir>
        <span class="gorev-onay-kutu"><?= GOREV_TIK_SVG ?></span>
    </button>
    <div class="gorev-govde">
        <p class="gorev-ad"><?= e($g['ad']) ?></p>
        <div class="gorev-bilgi">
            <span class="yetenek-etiket" aria-hidden="true"><?= e($yetenekAdi) ?></span>
            <?php if ($g['tamam']): ?>
                <span class="gorev-tamam-etiket" aria-hidden="true"><i class="fa-solid fa-check"></i> <?= t('gorevler.kart_tamamlandi') ?></span>
                <span class="gorev-odul" aria-hidden="true"><?= t('gorevler.kart_kazanildi', ['xp' => $g['xp']]) ?></span>
            <?php else: ?>
                <span class="gorev-odul" aria-hidden="true"><?= t('gorevler.kart_odul', ['xp' => $g['xp']]) ?></span>
            <?php endif; ?>
            <?php // Seri ve bonus tek düğmedir: M02'yi açar, kartı tamamlamaz (03 §3.1). ?>
            <button type="button" class="gorev-seri-dugme"
                    aria-label="<?= t('gorevler.kart_seri_etiketi', ['seri' => $g['etkinSeri'], 'carpan' => $carpan]) ?>">
                <span class="gorev-seri" aria-hidden="true"><i class="fa-solid fa-fire"></i>
                    <?= $g['etkinSeri'] > 0 ? t('gorevler.kart_seri', ['seri' => $g['etkinSeri']]) : t('gorevler.kart_seri_yok') ?></span>
                <?php if ($g['carpan'] > 1): ?>
                    <span class="gorev-bonus" aria-hidden="true"><?= t('gorevler.kart_bonus', ['carpan' => $carpan]) ?></span>
                <?php endif; ?>
            </button>
        </div>
        <?php if ($bozuk): ?>
            <p class="gorev-not" aria-hidden="true"><?= t('gorevler.kart_seri_bozuldu', ['onceki' => $g['bozukSeri']]) ?></p>
        <?php endif; ?>
    </div>
    <?php if ($menu): ?>
        <button type="button" class="menu-dugme" aria-controls="gorev-menu-<?= $id ?>"
                aria-label="<?= t('gorevler.kart_menu_etiketi', ['gorev' => $g['ad']]) ?>">&#8943;</button>
        <ul class="acilir-menu" id="gorev-menu-<?= $id ?>" role="menu" hidden>
            <li role="none"><button type="button" role="menuitem" data-eylem="duzenle" data-veri-degistirir><?= t('gorevler.kart_menu_duzenle') ?></button></li>
            <li role="none"><button type="button" role="menuitem" class="tehlike" data-eylem="sil" data-veri-degistirir><?= t('gorevler.kart_menu_sil') ?></button></li>
        </ul>
    <?php endif; ?>
</li>
<?php
    return (string) ob_get_clean();
}

/** Görev kartı betiğinin (statik/gorev.js) ihtiyaç duyduğu metin anahtarları. */
const GOREV_KARTI_METINLERI = [
    'gorevler.tamamlandi_bildirim', 'gorevler.geri_alindi_bildirim', 'gorevler.hata_tamamlanamadi',
    'gorevler.hata_geri_alinamadi', 'gorevler.hata_gun_bitti', 'gorevler.zaten_tamamlandi',
    'gorevler.okuyucu_tamamlandi', 'gorevler.okuyucu_geri_alindi', 'gorevler.okuyucu_seviye_dustu',
    'seri.modal_gorev_serisi', 'seri.modal_simdiki_bonus', 'seri.modal_sonraki_bonus', 'seri.modal_en_yuksek',
];

/**
 * Seri bilgisi alt sayfası (M02, 03 §7). Sabit kısımlar (kurallar, bonus tablosu)
 * burada çizilir; görevin serisine bağlı satırları statik/gorev.js doldurur.
 * Tablo, seri_carpani() kuralından üretilir (1 + floor(seri/7) × 0,25, en fazla ×2).
 */
function seri_modal_html(): string
{
    $satirlar = [];
    for ($i = 0; $i <= 4; $i++) {
        $alt = max(1, $i * 7);
        $aralik = $i < 4 ? $alt . '–' . ($i * 7 + 6) : $alt . '+';
        $carpan = seri_carpani($i * 7);
        $satirlar[] = ['aralik' => $aralik, 'carpan' => carpan_tr($carpan), 'xp' => seri_xp(GOREV_TABAN_XP, $i * 7)];
    }
    ob_start(); ?>
<div class="alt-sayfa-arka" id="seri-arka" hidden>
    <div class="alt-sayfa" id="seri-sayfa" role="dialog" aria-modal="true" aria-labelledby="seri-baslik" aria-describedby="seri-ozet">
        <div class="alt-sayfa-ust">
            <h2 class="alt-sayfa-baslik" id="seri-baslik"><?= t('seri.modal_baslik') ?></h2>
            <button type="button" class="kapat-dugme" id="seri-kapat" aria-label="<?= t('ortak.kapat') ?>">
                <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2.5"/></svg>
            </button>
        </div>
        <div class="seri-ozet" id="seri-ozet">
            <span class="seri-alev-buyuk" aria-hidden="true"><i class="fa-solid fa-fire"></i></span>
            <div>
                <p id="seri-gorev-serisi"></p>
                <p id="seri-simdiki"></p>
                <p id="seri-sonraki" class="ikincil-metin"></p>
            </div>
        </div>
        <ul class="seri-kurallar">
            <li><?= t('seri.modal_kural_1') ?></li>
            <li><?= t('seri.modal_kural_2') ?></li>
            <li><?= t('seri.modal_kural_3') ?></li>
        </ul>
        <table class="seri-tablo">
            <caption><?= t('seri.modal_tablo_baslik') ?></caption>
            <thead>
                <tr>
                    <th scope="col"><?= t('seri.tablo_sutun_seri') ?></th>
                    <th scope="col"><?= t('seri.tablo_sutun_bonus') ?></th>
                    <th scope="col"><?= t('seri.tablo_sutun_xp') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($satirlar as $i => $s): ?>
                <tr data-seri-satir="<?= $i ?>">
                    <th scope="row"><?= e($s['aralik']) ?></th>
                    <td>×<?= e($s['carpan']) ?></td>
                    <td><?= (int) $s['xp'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
    return (string) ob_get_clean();
}
