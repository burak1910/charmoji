// view/statik/avatar.js
// Görünüm (E51) ve Çanta (E52): renk önizleme ve kaydetme, eşya seçme/önizleme ve
// kuşanma. Eşya ekranda kalıcı olarak ancak sunucu onayından sonra kuşanılmış sayılır.
(function () {
    'use strict';

    const K = window.Charmoji;
    const m = K.m;
    const V = K.veri || {};

    // ------------------------------------------------------------ sekmeler + başlık
    const baslik = document.querySelector('.ust-baslik');
    K.sekmeleriBagla(document.getElementById('avatar-sekmeler'), {
        secildi: (sekme) => {
            const metin = sekme.dataset.capa === 'canta' ? m('canta.baslik') : m('gorunum.baslik');
            baslik.textContent = metin;
            document.title = metin + ' - Charmoji';
        },
    });

    // ------------------------------------------------------------ önizleme (SVG)
    const onizleme = document.getElementById('onizleme');
    function boya(idler, renk) {
        idler.forEach((id) => {
            const x = onizleme.querySelector('#' + id);
            if (x) x.setAttribute('fill', renk);
        });
    }
    // Eşya çizimleri: avatar_renderer.php ile aynı sabit SVG parçaları.
    const CIZIMLER = {
        'Büyücü Şapkası': ['hatGroup', '<ellipse cx="0" cy="20" rx="70" ry="15" fill="#4C1D95" /><path d="M-40,20 Q0,-100 40,20" fill="#6D28D9" /><path d="M-40,20 Q0,35 40,20" fill="none" stroke="#4C1D95" stroke-width="2" />'],
        'Kral Tacı': ['hatGroup', '<path d="M-35,25 L-35,-10 L-15,10 L0,-25 L15,10 L35,-10 L35,25 Z" fill="#FACC15" stroke="#B45309" stroke-width="3" stroke-linejoin="round"/><circle cx="0" cy="20" r="3" fill="#B45309" />'],
        'Güneş Gözlüğü': ['glassesGroup', '<g fill="#111827"><rect x="-55" y="-15" width="45" height="25" rx="5" /><rect x="10" y="-15" width="45" height="25" rx="5" /><rect x="-10" y="-10" width="20" height="4" /></g>'],
        'Ninja Maskesi': ['neckGroup', '<path d="M-45,-120 Q0,-90 45,-120 L45,-90 Q0,-60 -45,-90 Z" fill="#1F2937" /><rect x="-46" y="-120" width="92" height="40" rx="10" fill="#1F2937" />'],
        'Altın Kolye': ['neckGroup', '<path d="M-30,10 Q0,50 30,10" fill="none" stroke="#FACC15" stroke-width="4" /><circle cx="0" cy="30" r="8" fill="#FACC15" stroke="#B45309" stroke-width="2" />'],
    };
    function esyaCiz(ad) {
        const c = CIZIMLER[ad];
        if (!c) return;
        const g = onizleme.querySelector('#' + c[0]);
        if (g) g.innerHTML = c[1]; // yalnızca yukarıdaki sabit parçalar
    }

    // ------------------------------------------------------------ Görünüm
    const ALANLAR = { skinColor: ['Skin', 'Neck'], hairColor: ['Hair'], eyeColor: ['EyeLeft', 'EyeRight'], topColor: ['Shirt'] };
    const kaydet = document.getElementById('gorunum-kaydet');
    const girdiler = Object.keys(ALANLAR).map((id) => document.getElementById(id));
    function degistiMi() { return girdiler.some((g) => g.value.toLowerCase() !== g.dataset.ilk.toLowerCase()); }
    girdiler.forEach((g) => g.addEventListener('input', () => {
        boya(ALANLAR[g.id], g.value);
        kaydet.disabled = !degistiMi();
    }));
    kaydet.addEventListener('click', async () => {
        const renkler = {};
        girdiler.forEach((g) => { renkler[g.id] = g.value; });
        K.dugmeYukleniyor(kaydet, true);
        let y;
        try {
            y = await K.istek('ajax_islem.php', { save_avatar_colors: 1, colors: JSON.stringify(renkler) });
        } catch (h) {
            K.dugmeYukleniyor(kaydet, false);
            K.istekHatasiGoster(h, m('gorunum.hata_kaydedilemedi'), () => kaydet.click());
            return;
        }
        K.dugmeYukleniyor(kaydet, false);
        if (y.success) {
            girdiler.forEach((g) => { g.dataset.ilk = g.value; });
            kaydet.disabled = true;
            K.bildirim(m('gorunum.kaydedildi_bildirim'), { tur: 'basari' });
        } else {
            K.bildirim(m('gorunum.hata_kaydedilemedi'), { tur: 'hata' });
        }
    });

    // ------------------------------------------------------------ Çanta
    const izgara = document.getElementById('canta-izgara');
    if (!izgara) return;
    const kusanDugme = document.getElementById('kusan-dugme');
    const filtre = document.getElementById('canta-filtre');
    const cipler = Array.from(filtre.querySelectorAll('[data-filtre]'));
    let secili = null;

    function filtrele(tur) {
        cipler.forEach((c) => c.setAttribute('aria-pressed', c.dataset.filtre === tur ? 'true' : 'false'));
        Array.from(izgara.children).forEach((li) => { li.hidden = !!tur && li.dataset.tur !== tur; });
    }
    cipler.forEach((c) => c.addEventListener('click', () => filtrele(c.dataset.filtre)));
    if (V.tur) filtrele(V.tur);

    function dugmeyiGuncelle() {
        if (!secili) { kusanDugme.hidden = true; return; }
        kusanDugme.hidden = false;
        const kusanili = secili.dataset.kusanili === '1';
        kusanDugme.textContent = kusanili ? m('ortak.kusanildi') : m('ortak.kusan');
        kusanDugme.disabled = kusanili;
    }
    izgara.addEventListener('click', (e) => {
        const kart = e.target.closest('.esya-karti');
        if (!kart) return;
        izgara.querySelectorAll('.esya-karti').forEach((k) => k.setAttribute('aria-pressed', k === kart ? 'true' : 'false'));
        secili = kart;
        esyaCiz(kart.dataset.ad); // yalnızca önizleme
        dugmeyiGuncelle();
    });
    kusanDugme.addEventListener('click', async () => {
        if (!secili || secili.dataset.kusanili === '1') return;
        const kart = secili;
        K.dugmeYukleniyor(kusanDugme, true);
        let y;
        try {
            y = await K.istek('ajax_islem.php', { equip_item_id: kart.dataset.esyaId });
        } catch (h) {
            K.dugmeYukleniyor(kusanDugme, false);
            K.istekHatasiGoster(h, m('ortak.hata_kusanilamadi'));
            return;
        }
        K.dugmeYukleniyor(kusanDugme, false);
        if (!y.success) {
            K.bildirim(m('ortak.hata_kusanilamadi'), { tur: 'hata' });
            dugmeyiGuncelle();
            return;
        }
        izgara.querySelectorAll('.esya-karti').forEach((k) => {
            if (k.dataset.tur !== kart.dataset.tur) return;
            const evet = k === kart;
            k.dataset.kusanili = evet ? '1' : '0';
            k.classList.toggle('secili', evet);
            k.querySelector('.esya-durum').textContent = evet ? m('ortak.kusanildi') : '';
        });
        esyaCiz(kart.dataset.ad);
        dugmeyiGuncelle();
        K.bildirim(m('ortak.kusanildi_bildirim', { esya: kart.dataset.ad }), { tur: 'basari' });
        K.duyuru(m('ortak.kusanildi_bildirim', { esya: kart.dataset.ad }));
    });
})();
