// view/statik/karakter.js
// Karakter (E50): yetenek kartlarını aç/kapa, çıkış onayı (M08).
(function () {
    'use strict';

    const K = window.Charmoji;
    const m = K.m;

    // Yetenek kartları (aynı anda birden fazla açık olabilir)
    document.querySelectorAll('.yetenek-karti-baslik').forEach((d) => {
        d.addEventListener('click', () => {
            const acik = d.getAttribute('aria-expanded') === 'true';
            d.setAttribute('aria-expanded', acik ? 'false' : 'true');
            document.getElementById(d.getAttribute('aria-controls')).hidden = acik;
        });
    });
    // Karargah'taki mini bardan gelindiyse ilgili kart açık gelir (06 §2).
    if (location.hash.startsWith('#yetenek-')) {
        const kart = document.getElementById(location.hash.slice(1));
        const d = kart && kart.querySelector('.yetenek-karti-baslik');
        if (d) { d.click(); kart.scrollIntoView({ block: 'center' }); }
    }

    // Çıkış onayı (M08): onaylanınca CSRF jetonlu POST formu gönderilir.
    const form = document.getElementById('cikis-form');
    let onaylandi = false;
    form.addEventListener('submit', (e) => {
        if (onaylandi) return;
        e.preventDefault();
        K.onayIste({
            baslik: m('karakter.cikis_onay_baslik'),
            aciklama: m('karakter.cikis_onay_aciklama'),
            birincil: m('karakter.cikis_onay_dugme'),
            onay: () => {
                onaylandi = true;
                form.submit();
                return new Promise(() => {}); // sayfa değişene kadar düğme yükleniyor kalır
            },
        });
    });
})();
