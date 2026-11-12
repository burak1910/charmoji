// view/statik/lonca.js
// Lonca (E40): iç sekmeler, arkadaşlıktan çıkarma (M06), istek kabul/red, istek gönderme
// ve Maceracı bul araması (G05 iskeletle). Tüm işlemler sayfa yenilenmeden yapılır.
(function () {
    'use strict';

    const K = window.Charmoji;
    const m = K.m;
    const V = K.veri || {};
    const ISLEM = '../service/friend_action.php';
    let istekSayisi = Number(V.istekSayisi) || 0;

    function el(etiket, sinif, metin) {
        const x = document.createElement(etiket);
        if (sinif) x.className = sinif;
        if (metin !== undefined) x.textContent = metin;
        return x;
    }

    // ------------------------------------------------------------ iç sekmeler
    const sekmeler = K.sekmeleriBagla(document.getElementById('lonca-sekmeler'));
    document.querySelectorAll('[data-sekmeye]').forEach((a) => a.addEventListener('click', (e) => {
        e.preventDefault();
        sekmeler.sec(a.dataset.sekmeye);
    }));

    // ------------------------------------------------------------ sayaçlar
    function istekRozetleri() {
        const sekme = document.getElementById('sekme-istekler');
        let r = sekme.querySelector('.rozet-sayi');
        if (istekSayisi > 0) {
            if (!r) { r = el('span', 'rozet-sayi'); r.setAttribute('aria-hidden', 'true'); sekme.appendChild(r); }
            r.textContent = istekSayisi > 9 ? '9+' : String(istekSayisi);
            sekme.setAttribute('aria-label', m('lonca.sekme_istekler_sayili', { sayi: istekSayisi }));
        } else {
            if (r) r.remove();
            sekme.removeAttribute('aria-label');
        }
        K.istekRozeti(istekSayisi);
        document.getElementById('gelen-bos').hidden = document.getElementById('gelen-listesi').children.length > 0;
    }
    function arkadasSayaci() {
        const n = document.getElementById('arkadas-listesi').children.length;
        const s = document.getElementById('arkadas-sayisi');
        s.textContent = m('lonca.arkadaslar_sayi', { sayi: n });
        s.hidden = n === 0;
        document.getElementById('arkadas-bos').hidden = n > 0;
    }
    function satiriKaldir(satir, sonra) {
        satir.classList.add('cikiyor');
        window.setTimeout(() => { satir.remove(); if (sonra) sonra(); }, K.azHareket ? 0 : 300);
    }

    // ------------------------------------------------------------ arkadaş satırı ve M06
    function arkadasSatiriBagla(satir) {
        const d = satir.querySelector('.menu-dugme');
        const menu = satir.querySelector('.acilir-menu');
        const kontrol = K.menuBagla(d, menu);
        menu.querySelector('[data-eylem="cikar"]').addEventListener('click', () => {
            kontrol.kapat(true);
            arkadasCikar(satir);
        });
    }
    function arkadasCikar(satir) {
        const ad = satir.dataset.ad;
        K.onayIste({
            baslik: m('lonca.cikar_onay_baslik'),
            aciklama: m('lonca.cikar_onay_aciklama', { ad }),
            birincil: m('lonca.cikar_onay_dugme'),
            tehlikeli: true,
            onay: async () => {
                let y;
                try {
                    y = await K.istek(ISLEM, { remove_friend_id: satir.dataset.arkadaslikId });
                } catch (h) {
                    K.istekHatasiGoster(h, m('lonca.cikar_hata'), () => arkadasCikar(satir));
                    return { kapat: true };
                }
                if (y.success) {
                    satiriKaldir(satir, arkadasSayaci);
                    K.bildirim(m('lonca.cikarildi_bildirim'), { tur: 'basari' });
                    K.duyuru(m('lonca.cikarildi_bildirim'));
                    return { kapat: true };
                }
                if (y.kod === 'artik_yok') {
                    satiriKaldir(satir, arkadasSayaci);
                    K.bildirim(m('lonca.profil_hata_yok'), { tur: 'hata' });
                    return { kapat: true };
                }
                return { hata: m('lonca.cikar_hata') };
            },
        });
    }
    document.querySelectorAll('#arkadas-listesi .kisi').forEach(arkadasSatiriBagla);

    /** Kabul edilen istekten yeni arkadaş satırı (avatar SVG'si istek kartından kopyalanır). */
    function arkadasEkle(kart) {
        const id = kart.dataset.istekId;
        const satir = el('li', 'kisi');
        satir.id = 'arkadas-' + id;
        satir.dataset.arkadaslikId = id;
        satir.dataset.ad = kart.dataset.ad;
        satir.appendChild(kart.querySelector('.kisi-avatar').cloneNode(true));
        const govde = el('div', 'kisi-govde');
        govde.appendChild(el('p', 'kisi-ad', kart.dataset.ad));
        govde.appendChild(el('p', 'kisi-alt', m('ortak.seviye_kisa', { seviye: kart.dataset.seviye })));
        satir.appendChild(govde);
        const d = el('button', 'menu-dugme', '⋯');
        d.type = 'button';
        d.setAttribute('aria-controls', 'arkadas-menu-' + id);
        d.setAttribute('aria-label', m('lonca.arkadas_menu_etiketi', { ad: kart.dataset.ad }));
        satir.appendChild(d);
        const menu = el('ul', 'acilir-menu');
        menu.id = 'arkadas-menu-' + id;
        menu.setAttribute('role', 'menu');
        menu.hidden = true;
        const li = el('li'); li.setAttribute('role', 'none');
        const oge = el('button', 'tehlike', m('lonca.profil_cikar'));
        oge.type = 'button';
        oge.setAttribute('role', 'menuitem');
        oge.dataset.eylem = 'cikar';
        oge.setAttribute('data-veri-degistirir', '');
        li.appendChild(oge); menu.appendChild(li); satir.appendChild(menu);

        const liste = document.getElementById('arkadas-listesi');
        const sonraki = Array.from(liste.children).find((x) => x.dataset.ad.localeCompare(kart.dataset.ad, 'tr') > 0);
        liste.insertBefore(satir, sonraki || null);
        arkadasSatiriBagla(satir);
        arkadasSayaci();
    }

    // ------------------------------------------------------------ gelen istekler (kabul / red)
    document.getElementById('gelen-listesi').addEventListener('click', async (e) => {
        const d = e.target.closest('[data-istek-eylem]');
        if (!d || d.getAttribute('aria-busy') === 'true') return;
        const kart = d.closest('.istek-karti');
        const kabul = d.dataset.istekEylem === 'kabul';
        const dugmeler = kart.querySelectorAll('[data-istek-eylem]');
        K.dugmeYukleniyor(d, true);
        dugmeler.forEach((x) => { x.disabled = true; });
        let y;
        try {
            y = await K.istek(ISLEM, kabul ? { accept_request_id: kart.dataset.istekId } : { reject_request_id: kart.dataset.istekId });
        } catch (h) {
            K.dugmeYukleniyor(d, false);
            dugmeler.forEach((x) => { x.disabled = false; });
            K.istekHatasiGoster(h, m('lonca.istek_hata_islenemedi'), () => d.click());
            return;
        }
        if (y.success || y.kod === 'artik_yok') {
            istekSayisi = Math.max(0, istekSayisi - 1);
            if (y.success && kabul) arkadasEkle(kart);
            satiriKaldir(kart, istekRozetleri);
            const metin = !y.success ? m('lonca.istek_hata_artik_yok') : (kabul ? m('lonca.istek_kabul_bildirim', { ad: kart.dataset.ad }) : m('lonca.istek_red_bildirim'));
            K.bildirim(metin, { tur: y.success ? 'basari' : 'hata' });
            K.duyuru(metin);
            return;
        }
        K.dugmeYukleniyor(d, false);
        dugmeler.forEach((x) => { x.disabled = false; });
        K.bildirim(m('lonca.istek_hata_islenemedi'), { tur: 'hata' });
    });

    // ------------------------------------------------------------ istek gönderme
    function gidenEkle(satir) {
        const li = el('li', 'kisi');
        li.appendChild(satir.querySelector('.kisi-avatar').cloneNode(true));
        const govde = el('div', 'kisi-govde');
        govde.appendChild(el('p', 'kisi-ad', satir.dataset.ad));
        govde.appendChild(el('p', 'kisi-alt', m('ortak.seviye_kisa', { seviye: satir.dataset.seviye })));
        li.appendChild(govde);
        li.appendChild(el('span', 'kisi-eylem soluk-metin', m('lonca.istek_bekliyor')));
        document.getElementById('giden-listesi').appendChild(li);
        document.getElementById('giden-bolum').hidden = false;
    }
    function gonderildi(d) {
        d.textContent = m('lonca.istek_gonderildi');
        d.disabled = true;
        d.removeAttribute('data-veri-degistirir');
        d.setAttribute('aria-label', m('lonca.istek_gonderildi'));
    }
    async function istekGonder(d) {
        if (d.disabled || d.getAttribute('aria-busy') === 'true') return;
        const satir = d.closest('.kisi');
        K.dugmeYukleniyor(d, true);
        let y;
        try {
            y = await K.istek(ISLEM, { action: 'send_request', target_id: satir.dataset.kullaniciId });
        } catch (h) {
            K.dugmeYukleniyor(d, false);
            K.istekHatasiGoster(h, m('lonca.istek_gonder_hata'), () => istekGonder(d));
            return;
        }
        K.dugmeYukleniyor(d, false);
        if (y.success) {
            gonderildi(d);
            gidenEkle(satir);
            K.bildirim(m('lonca.istek_gonderildi_bildirim'), { tur: 'basari' });
            K.duyuru(m('lonca.istek_gonderildi_bildirim'));
        } else if (y.kod === 'zaten_var') {
            gonderildi(d);
            K.bildirim(m('lonca.istek_zaten_var'), { tur: 'hata' });
        } else {
            K.bildirim(m('lonca.istek_gonder_hata'), { tur: 'hata' });
        }
    }
    document.getElementById('panel-bul').addEventListener('click', (e) => {
        const d = e.target.closest('[data-istek-gonder]');
        if (d) istekGonder(d);
    });

    // ------------------------------------------------------------ Maceracı bul araması
    const form = document.getElementById('arama-form');
    const kutu = document.getElementById('arama-kutusu');
    const durum = document.getElementById('arama-durum');
    const sonuclar = document.getElementById('arama-sonuclari');
    const oneriler = document.getElementById('oneriler-bolum');
    let aramaZamani = null, aramaNo = 0;

    function sonucSatiri(k) {
        const li = el('li', 'kisi');
        li.dataset.kullaniciId = k.id;
        li.dataset.ad = k.ad;
        li.dataset.seviye = k.seviye;
        const av = el('span', 'kisi-avatar');
        av.setAttribute('aria-hidden', 'true');
        av.innerHTML = k.avatar; // sunucuda renderAvatar ile üretilmiş piksel büst (sabit görsel yolları)
        li.appendChild(av);
        const govde = el('div', 'kisi-govde');
        govde.appendChild(el('p', 'kisi-ad', k.ad));
        govde.appendChild(el('p', 'kisi-alt', m('ortak.seviye_kisa', { seviye: k.seviye })));
        li.appendChild(govde);
        const d = el('button', 'dugme dugme-ikincil dugme-kucuk kisi-eylem', m('lonca.istek_gonder'));
        d.type = 'button';
        d.setAttribute('data-istek-gonder', '');
        d.setAttribute('data-veri-degistirir', '');
        d.setAttribute('aria-label', m('lonca.istek_gonder_etiketi', { ad: k.ad }));
        li.appendChild(d);
        return li;
    }

    async function ara(metin) {
        const no = ++aramaNo;
        K.iskelet(sonuclar, 3);
        sonuclar.hidden = false;
        oneriler.hidden = true;
        durum.textContent = '';
        let y;
        try {
            y = await K.istek('lonca_ara.php', { q: metin }, { yontem: 'GET' });
        } catch (h) {
            if (no !== aramaNo) return;
            sonuclar.removeAttribute('aria-busy');
            sonuclar.innerHTML = '';
            if (h.tip === 'ag') durum.textContent = m('ortak.istek_basarisiz');
            else if (h.tip !== 'oturum' && h.tip !== 'cevrimdisi') durum.textContent = m('lonca.arama_hata');
            return;
        }
        if (no !== aramaNo) return; // daha yeni bir arama başladı
        sonuclar.removeAttribute('aria-busy');
        sonuclar.innerHTML = '';
        if (!y.success) {
            durum.textContent = y.kod === 'kisa' ? m('lonca.arama_kisa') : m('lonca.arama_hata');
            return;
        }
        if (!y.sonuclar.length) {
            durum.textContent = m('lonca.arama_sonuc_yok', { arama: metin });
            return;
        }
        y.sonuclar.forEach((k) => sonuclar.appendChild(sonucSatiri(k)));
        durum.textContent = m('lonca.arama_sonuc_sayisi', { sayi: y.sonuclar.length });
    }

    form.addEventListener('submit', (e) => e.preventDefault());
    kutu.addEventListener('input', () => {
        window.clearTimeout(aramaZamani);
        const metin = kutu.value.trim();
        if (Array.from(metin).length < 2) {
            aramaNo++;
            sonuclar.hidden = true;
            sonuclar.innerHTML = '';
            oneriler.hidden = false;
            durum.textContent = metin.length === 1 ? m('lonca.arama_kisa') : '';
            return;
        }
        aramaZamani = window.setTimeout(() => ara(metin), 400);
    });
})();
