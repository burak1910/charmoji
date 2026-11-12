// view/statik/charmoji.js
// Ortak bileşenlerin istemci tarafı (docs/ux/00-genel.md §3.3):
//   G02 üst bar canlı güncelleme, G03 bildirim, G04 bağlantı bandı, G05 iskelet,
//   G06 düğme yükleme hâli, G07 onay alt sayfası, G08 oturum sona erdi,
//   ayrıca istek yardımcısı (CSRF + zaman aşımı) ve açılır menü.
// Tarayıcının confirm()/alert() pencereleri hiç kullanılmaz.
(function () {
    'use strict';

    const C = window.CHARMOJI || { metinler: {} };
    const azHareket = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ------------------------------------------------------------ metin ve biçim
    function m(anahtar, degiskenler) {
        let s = (C.metinler && C.metinler[anahtar]) || anahtar;
        if (degiskenler) {
            Object.keys(degiskenler).forEach((k) => { s = s.split('{' + k + '}').join(String(degiskenler[k])); });
        }
        return s;
    }
    const sayiBicim = new Intl.NumberFormat('tr-TR', { maximumFractionDigits: 0 });
    function sayi(n) { return sayiBicim.format(Math.floor(Number(n) || 0)); }
    function carpan(c) { return String(Math.round(c * 100) / 100).replace('.', ','); }

    // ------------------------------------------------------------ ekran okuyucu
    function duyuru(metin, oncelik) {
        const alan = document.getElementById(oncelik === 'assertive' ? 'duyuru-assertive' : 'duyuru-polite');
        if (!alan) return;
        alan.textContent = '';
        window.setTimeout(() => { alan.textContent = metin; }, 50);
    }

    // ------------------------------------------------------------ G03 bildirim
    let bildirimZamani = null;
    function bildirim(metin, secenek) {
        const s = secenek || {};
        const tur = s.tur || 'bilgi';
        const alan = document.getElementById('bildirim-alani');
        if (!alan) return;
        window.clearTimeout(bildirimZamani);
        alan.innerHTML = '';

        const kutu = document.createElement('div');
        kutu.className = 'bildirim ' + tur;
        kutu.setAttribute('role', tur === 'hata' ? 'alert' : 'status');
        const yazi = document.createElement('span');
        yazi.className = 'bildirim-metin';
        yazi.textContent = metin;
        kutu.appendChild(yazi);

        if (s.eylem) {
            const d = document.createElement('button');
            d.type = 'button';
            d.className = 'dugme dugme-metin bildirim-eylem';
            d.textContent = s.eylem.etiket;
            d.addEventListener('click', () => { kapat(); s.eylem.fn(); });
            kutu.appendChild(d);
        }
        const kapatDugme = document.createElement('button');
        kapatDugme.type = 'button';
        kapatDugme.className = 'bildirim-kapat';
        kapatDugme.setAttribute('aria-label', m('ortak.kapat'));
        kapatDugme.innerHTML = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2.5"/></svg>';
        kapatDugme.addEventListener('click', kapat);
        kutu.appendChild(kapatDugme);
        alan.appendChild(kutu);

        function kapat() { window.clearTimeout(bildirimZamani); if (kutu.parentNode) kutu.remove(); }
        const sure = s.sure || ((tur === 'hata' || s.eylem) ? 5000 : 3000);
        bildirimZamani = window.setTimeout(kapat, sure);
        return kapat;
    }

    // ------------------------------------------------------------ G06 düğme yükleme hâli
    function dugmeYukleniyor(dugme, durum) {
        if (!dugme) return;
        if (durum) {
            if (dugme.getAttribute('aria-busy') === 'true') return;
            dugme.setAttribute('aria-busy', 'true');
            dugme.disabled = true;
            const d = document.createElement('span');
            d.className = 'donen';
            d.setAttribute('aria-hidden', 'true');
            dugme.insertBefore(d, dugme.firstChild);
            duyuru(m('ortak.yukleniyor'));
        } else {
            dugme.removeAttribute('aria-busy');
            dugme.disabled = false;
            const d = dugme.querySelector(':scope > .donen');
            if (d) d.remove();
        }
    }

    // ------------------------------------------------------------ G05 iskelet
    function iskelet(kap, adet) {
        kap.innerHTML = '';
        kap.setAttribute('aria-busy', 'true');
        for (let i = 0; i < (adet || 3); i++) {
            const li = document.createElement('li');
            li.className = 'iskelet';
            li.setAttribute('aria-hidden', 'true');
            kap.appendChild(li);
        }
    }

    // ------------------------------------------------------------ G08 oturum sona erdi
    function oturumBitti() {
        const arka = document.getElementById('oturum-arka');
        if (!arka || !arka.hidden) return;
        onayKapat(false);
        arka.hidden = false;
        document.body.classList.add('modal-acik');
        const d = document.getElementById('oturum-giris');
        if (d) d.focus();
        arka.addEventListener('keydown', (e) => { if (e.key === 'Tab') { e.preventDefault(); d.focus(); } });
    }

    // ------------------------------------------------------------ istek
    class IstekHatasi extends Error {
        constructor(tip, veri) { super(tip); this.tip = tip; this.veri = veri || null; }
    }

    /**
     * Sunucuya istek: CSRF başlığı, 10 sn zaman aşımı, oturum kapalıysa G08.
     * Başarılı HTTP yanıtında JSON gövdeyi döndürür (success false olabilir).
     * Hata tipleri: 'cevrimdisi', 'ag' (ulaşmadı / zaman aşımı), 'oturum', 'sunucu'.
     */
    async function istek(url, veri, secenek) {
        const s = secenek || {};
        const yontem = s.yontem || 'POST';
        if (!navigator.onLine) {
            bildirim(m('ortak.cevrimdisi_islem'), { tur: 'hata' });
            throw new IstekHatasi('cevrimdisi');
        }
        const kontrol = new AbortController();
        const zaman = window.setTimeout(() => kontrol.abort(), 10000);
        const params = new URLSearchParams(veri || {});
        let hedef = url;
        const ayar = {
            method: yontem,
            credentials: 'same-origin',
            signal: kontrol.signal,
            headers: { 'Accept': 'application/json', 'X-CSRF-Token': CSRF_JETONU },
        };
        if (yontem === 'POST') {
            ayar.headers['Content-Type'] = 'application/x-www-form-urlencoded';
            ayar.body = params.toString();
        } else if (params.toString()) {
            hedef += (url.includes('?') ? '&' : '?') + params.toString();
        }
        let yanit;
        try {
            yanit = await fetch(hedef, ayar);
        } catch (e) {
            throw new IstekHatasi('ag');
        } finally {
            window.clearTimeout(zaman);
        }
        let json = null;
        try { json = await yanit.json(); } catch (e) { json = null; }
        if (yanit.status === 401 || (json && json.kod === 'oturum_kapali')) {
            oturumBitti();
            throw new IstekHatasi('oturum', json);
        }
        if (!json) throw new IstekHatasi('sunucu');
        return json;
    }

    /** İstek hatasını ortak sözleşmeye göre bildirir (00 §3.4). */
    function istekHatasiGoster(hata, varsayilan, tekrar) {
        const tip = hata && hata.tip;
        if (tip === 'oturum' || tip === 'cevrimdisi') return;
        if (tip === 'ag') {
            bildirim(m('ortak.istek_basarisiz'), {
                tur: 'hata',
                eylem: tekrar ? { etiket: m('ortak.tekrar_dene'), fn: tekrar } : null,
            });
            return;
        }
        bildirim(varsayilan || m('ortak.hata_genel'), { tur: 'hata' });
    }

    // ------------------------------------------------------------ G07 onay alt sayfası
    let onayDurumu = null;
    function onayKapat(sonuc) {
        const arka = document.getElementById('onay-arka');
        if (!arka || arka.hidden || !onayDurumu) return;
        const d = onayDurumu;
        onayDurumu = null;
        arka.hidden = true;
        document.body.classList.remove('modal-acik');
        if (d.odakDon && document.contains(d.odakDon)) d.odakDon.focus();
        d.coz(sonuc);
    }

    /**
     * Onay alt sayfasını açar. secenek: {baslik, aciklama, not, birincil, ikincil,
     * tehlikeli, onay}. onay (isteğe bağlı, async): birincil düğmeye basılınca çalışır;
     * {hata: metin} dönerse sayfa açık kalır ve açıklamanın yerine hata yazılır,
     * aksi hâlde sayfa kapanır. Promise: onaylandıysa true, vazgeçildiyse false.
     */
    function onayIste(secenek) {
        const s = secenek || {};
        const arka = document.getElementById('onay-arka');
        const baslik = document.getElementById('onay-baslik');
        const aciklama = document.getElementById('onay-aciklama');
        const not = document.getElementById('onay-not');
        const birincil = document.getElementById('onay-birincil');
        const ikincil = document.getElementById('onay-ikincil');
        if (onayDurumu) onayKapat(false);

        baslik.textContent = s.baslik || '';
        aciklama.textContent = s.aciklama || '';
        aciklama.classList.remove('hata-metni');
        not.textContent = s.not || '';
        not.hidden = !s.not;
        birincil.textContent = s.birincil || '';
        birincil.className = 'dugme ' + (s.tehlikeli ? 'dugme-tehlike' : 'dugme-birincil');
        birincil.setAttribute('data-veri-degistirir', '');
        dugmeYukleniyor(birincil, false);
        ikincil.textContent = s.ikincil || m('ortak.vazgec');
        ikincil.disabled = false;

        return new Promise((coz) => {
            onayDurumu = { coz, odakDon: document.activeElement, mesgul: false };
            const durum = onayDurumu;
            arka.hidden = false;
            document.body.classList.add('modal-acik');
            ikincil.focus(); // Varsayılan odak güvenli (ikincil) düğmede.

            birincil.onclick = async () => {
                if (durum.mesgul) return;
                if (!s.onay) { onayKapat(true); return; }
                if (!navigator.onLine) { bildirim(m('ortak.cevrimdisi_islem'), { tur: 'hata' }); return; }
                durum.mesgul = true;
                dugmeYukleniyor(birincil, true);
                ikincil.disabled = true;
                let sonuc;
                try { sonuc = await s.onay(); } catch (e) { sonuc = null; }
                durum.mesgul = false;
                if (onayDurumu !== durum) return;
                dugmeYukleniyor(birincil, false);
                ikincil.disabled = false;
                if (sonuc && sonuc.hata) {
                    aciklama.textContent = sonuc.hata;
                    aciklama.classList.add('hata-metni');
                    birincil.focus();
                    return;
                }
                onayKapat(true);
            };
            ikincil.onclick = () => { if (!durum.mesgul) onayKapat(false); };
        });
    }

    function onayKlavye(e) {
        const arka = document.getElementById('onay-arka');
        if (!arka || arka.hidden || !onayDurumu) return;
        if (e.key === 'Escape') { if (!onayDurumu.mesgul) onayKapat(false); return; }
        if (e.key === 'Tab') {
            const odaklar = Array.from(arka.querySelectorAll('button:not([disabled])'));
            if (!odaklar.length) return;
            const ilk = odaklar[0], son = odaklar[odaklar.length - 1];
            if (e.shiftKey && document.activeElement === ilk) { e.preventDefault(); son.focus(); }
            else if (!e.shiftKey && document.activeElement === son) { e.preventDefault(); ilk.focus(); }
        }
    }

    // ------------------------------------------------------------ G02 üst bar
    function altinGuncelle(yeni) {
        const a = document.getElementById('ust-altin');
        if (!a) return;
        const metin = a.querySelector('.ust-altin-metin');
        metin.textContent = sayi(yeni);
        metin.setAttribute('data-altin', String(Math.floor(yeni)));
        a.setAttribute('aria-label', m('ortak.altin_etiketi', { altin: sayi(yeni) }));
        if (!azHareket) { a.classList.remove('parlama'); void a.offsetWidth; a.classList.add('parlama'); }
    }
    function altinOku() {
        const metin = document.querySelector('#ust-altin .ust-altin-metin');
        return metin ? Number(metin.getAttribute('data-altin')) || 0 : 0;
    }
    function seviyeGuncelle(seviye) {
        const a = document.getElementById('ust-seviye');
        if (!a) return;
        a.querySelector('.ust-seviye-metin').textContent = m('ortak.seviye_kisa', { seviye });
        a.setAttribute('aria-label', m('ortak.seviye_rozeti_etiketi', { seviye }));
    }

    // ------------------------------------------------------------ G01 rozetler
    function odulRozeti(var_) {
        const a = document.getElementById('menu-gorevler');
        if (!a) return;
        const ikon = a.querySelector('.alt-menu-ikon');
        let nokta = ikon.querySelector('.rozet-nokta');
        if (var_ && !nokta) {
            nokta = document.createElement('span');
            nokta.className = 'rozet-nokta';
            nokta.setAttribute('aria-hidden', 'true');
            ikon.appendChild(nokta);
        } else if (!var_ && nokta) {
            nokta.remove();
        }
        a.setAttribute('aria-label', m('ortak.menu_gorevler') + (var_ ? ', ' + m('ortak.rozet_odul_etiketi') : ''));
    }
    function istekRozeti(sayiDegeri) {
        const a = document.getElementById('menu-lonca');
        if (!a) return;
        const ikon = a.querySelector('.alt-menu-ikon');
        let r = ikon.querySelector('.rozet-sayi');
        if (sayiDegeri > 0) {
            if (!r) {
                r = document.createElement('span');
                r.className = 'rozet-sayi';
                r.setAttribute('aria-hidden', 'true');
                ikon.appendChild(r);
            }
            r.textContent = sayiDegeri > 9 ? '9+' : String(sayiDegeri);
        } else if (r) {
            r.remove();
        }
        a.setAttribute('aria-label', m('ortak.menu_lonca') + (sayiDegeri > 0 ? ', ' + m('ortak.rozet_istek_etiketi', { sayi: sayiDegeri }) : ''));
    }

    // ------------------------------------------------------------ G04 bağlantı bandı
    let bantZamani = null;
    function baglantiDurumu() {
        const bant = document.getElementById('baglanti-bandi');
        if (!bant) return;
        window.clearTimeout(bantZamani);
        if (!navigator.onLine) {
            document.body.classList.add('cevrimdisi');
            bant.classList.remove('cevrimici');
            bant.textContent = m('ortak.cevrimdisi_bandi');
            bant.hidden = false;
            document.querySelectorAll('[data-veri-degistirir]').forEach((el) => el.setAttribute('aria-disabled', 'true'));
        } else {
            const oncedenKapali = document.body.classList.contains('cevrimdisi');
            document.body.classList.remove('cevrimdisi');
            document.querySelectorAll('[data-veri-degistirir][aria-disabled="true"]').forEach((el) => el.removeAttribute('aria-disabled'));
            if (oncedenKapali) {
                bant.classList.add('cevrimici');
                bant.textContent = m('ortak.cevrimici_geri_geldi');
                bant.hidden = false;
                bantZamani = window.setTimeout(() => { bant.hidden = true; }, 2000);
            }
        }
    }
    // Çevrimdışıyken veri değiştiren denetime dokunma: işlem yapılmaz, bilgi verilir.
    document.addEventListener('click', (e) => {
        if (navigator.onLine) return;
        const hedef = e.target.closest('[data-veri-degistirir]');
        if (!hedef) return;
        e.preventDefault();
        e.stopImmediatePropagation();
        bildirim(m('ortak.cevrimdisi_islem'), { tur: 'hata' });
    }, true);
    document.addEventListener('submit', (e) => {
        if (navigator.onLine) return;
        if (!e.target.matches('[data-veri-degistirir]') && !e.target.querySelector('[type="submit"][data-veri-degistirir]')) return;
        e.preventDefault();
        bildirim(m('ortak.cevrimdisi_islem'), { tur: 'hata' });
    }, true);

    // ------------------------------------------------------------ açılır menü (role=menu)
    function menuBagla(dugme, menu) {
        function kapat(odak) {
            menu.hidden = true;
            dugme.setAttribute('aria-expanded', 'false');
            document.removeEventListener('click', disari, true);
            if (odak) dugme.focus();
        }
        function disari(e) { if (!menu.contains(e.target) && e.target !== dugme && !dugme.contains(e.target)) kapat(false); }
        dugme.setAttribute('aria-haspopup', 'menu');
        dugme.setAttribute('aria-expanded', 'false');
        dugme.addEventListener('click', (e) => {
            e.stopPropagation();
            if (!menu.hidden) { kapat(true); return; }
            document.querySelectorAll('.acilir-menu').forEach((x) => { if (x !== menu) x.hidden = true; });
            menu.hidden = false;
            dugme.setAttribute('aria-expanded', 'true');
            const ilk = menu.querySelector('[role="menuitem"]');
            if (ilk) ilk.focus();
            document.addEventListener('click', disari, true);
        });
        menu.addEventListener('click', (e) => e.stopPropagation());
        menu.addEventListener('keydown', (e) => {
            const ogeler = Array.from(menu.querySelectorAll('[role="menuitem"]'));
            const i = ogeler.indexOf(document.activeElement);
            if (e.key === 'Escape') { e.preventDefault(); kapat(true); }
            else if (e.key === 'ArrowDown') { e.preventDefault(); ogeler[(i + 1) % ogeler.length].focus(); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); ogeler[(i - 1 + ogeler.length) % ogeler.length].focus(); }
            else if (e.key === 'Tab') { kapat(false); }
        });
        return { kapat };
    }

    // ------------------------------------------------------------ iç sekmeler (role=tablist)
    function sekmeleriBagla(liste, secenek) {
        const s = secenek || {};
        const sekmeler = Array.from(liste.querySelectorAll('[role="tab"]'));
        function sec(sekme, odak, adresiGuncelle) {
            sekmeler.forEach((x) => {
                const secili = x === sekme;
                x.setAttribute('aria-selected', secili ? 'true' : 'false');
                x.tabIndex = secili ? 0 : -1;
                const panel = document.getElementById(x.getAttribute('aria-controls'));
                if (panel) panel.hidden = !secili;
            });
            if (odak) sekme.focus();
            if (adresiGuncelle && sekme.dataset.capa) history.replaceState(null, '', '#' + sekme.dataset.capa);
            if (s.secildi) s.secildi(sekme);
        }
        sekmeler.forEach((x) => {
            x.addEventListener('click', () => sec(x, false, true));
            x.addEventListener('keydown', (e) => {
                const i = sekmeler.indexOf(x);
                let yeni = null;
                if (e.key === 'ArrowRight') yeni = sekmeler[(i + 1) % sekmeler.length];
                else if (e.key === 'ArrowLeft') yeni = sekmeler[(i - 1 + sekmeler.length) % sekmeler.length];
                else if (e.key === 'Home') yeni = sekmeler[0];
                else if (e.key === 'End') yeni = sekmeler[sekmeler.length - 1];
                if (yeni) { e.preventDefault(); sec(yeni, true, true); }
            });
        });
        function capadan() {
            const capa = location.hash.replace('#', '');
            const hedef = sekmeler.find((x) => x.dataset.capa === capa) || sekmeler.find((x) => x.getAttribute('aria-selected') === 'true') || sekmeler[0];
            sec(hedef, false, false);
        }
        capadan();
        window.addEventListener('hashchange', capadan);
        return { sec: (capa) => { const h = sekmeler.find((x) => x.dataset.capa === capa); if (h) sec(h, true, true); } };
    }

    // ------------------------------------------------------------ başlangıç
    document.addEventListener('keydown', onayKlavye);
    document.getElementById('onay-arka')?.addEventListener('click', (e) => {
        if (e.target.id === 'onay-arka' && onayDurumu && !onayDurumu.mesgul) onayKapat(false);
    });
    window.addEventListener('online', baglantiDurumu);
    window.addEventListener('offline', baglantiDurumu);
    if (!navigator.onLine) baglantiDurumu();
    if (C.bildirim && C.bildirim.metin) {
        bildirim(C.bildirim.metin, { tur: C.bildirim.tur === 'hata' ? 'hata' : 'basari' });
    }

    window.Charmoji = {
        m, sayi, carpan, azHareket, duyuru, bildirim, dugmeYukleniyor, iskelet, istek, IstekHatasi,
        istekHatasiGoster, onayIste, oturumBitti, altinGuncelle, altinOku, seviyeGuncelle,
        odulRozeti, istekRozeti, menuBagla, sekmeleriBagla, veri: C.veri,
    };
})();
