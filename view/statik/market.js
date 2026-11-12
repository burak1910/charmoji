// view/statik/market.js
// Market (E30): tür filtresi ve satın alma onayı (M05) + başarı (K07'nin bu aşamadaki
// karşılığı: altın güncellenir, kart "Çantada" olur, bildirimde "Kuşan" eylemi).
(function () {
    'use strict';

    const K = window.Charmoji;
    const m = K.m;
    const V = K.veri || {};
    const izgara = document.getElementById('esya-izgara');
    if (!izgara) return;

    // ------------------------------------------------------------ filtre
    const filtre = document.getElementById('market-filtre');
    const cipler = Array.from(filtre.querySelectorAll('[data-filtre]'));
    function filtrele(tur) {
        cipler.forEach((c) => c.setAttribute('aria-pressed', c.dataset.filtre === tur ? 'true' : 'false'));
        let gorunen = 0;
        Array.from(izgara.children).forEach((li) => {
            const goster = !tur || li.dataset.tur === tur;
            li.hidden = !goster;
            if (goster) gorunen++;
        });
        document.getElementById('filtre-bos').hidden = gorunen > 0;
        try { sessionStorage.setItem('charmoji.market.filtre', tur); } catch (e) { /* depolama kapalı olabilir */ }
    }
    cipler.forEach((c) => c.addEventListener('click', () => filtrele(c.dataset.filtre)));
    document.getElementById('filtre-temizle').addEventListener('click', () => filtrele(''));
    let kayitli = '';
    try { kayitli = sessionStorage.getItem('charmoji.market.filtre') || ''; } catch (e) { kayitli = ''; }
    if (cipler.some((c) => c.dataset.filtre === kayitli)) filtrele(kayitli);

    // ------------------------------------------------------------ kart durumları
    function durumYaz(kart, metin, kusanildi) {
        const d = kart.querySelector('.esya-durum');
        d.textContent = metin;
        d.classList.toggle('kusanildi', !!kusanildi);
    }
    /** Altın değişince alınabilir kartlardaki "{eksik} altın eksik" satırlarını yeniler. */
    function eksikleriGuncelle(altin) {
        izgara.querySelectorAll('.esya-karti').forEach((k) => {
            if (k.dataset.sahip === '1' || Number(k.dataset.seviye) > V.seviye) return;
            const eksik = Number(k.dataset.fiyat) - altin;
            durumYaz(k, eksik > 0 ? m('market.kart_eksik', { eksik: K.sayi(eksik) }) : '');
        });
    }
    function sahipYap(kart) {
        kart.dataset.sahip = '1';
        const f = kart.querySelector('.esya-fiyat');
        if (f) f.remove();
        durumYaz(kart, m('market.kart_cantada'));
        const etiket = kart.getAttribute('aria-label').replace(/\.\s.*$/, '.');
        kart.setAttribute('aria-label', etiket + ' ' + m('market.kart_cantada'));
    }

    async function kusan(kart) {
        let y;
        try {
            y = await K.istek('ajax_islem.php', { equip_item_id: kart.dataset.esyaId });
        } catch (h) {
            K.istekHatasiGoster(h, m('ortak.hata_kusanilamadi'));
            return;
        }
        if (!y.success) { K.bildirim(m('ortak.hata_kusanilamadi'), { tur: 'hata' }); return; }
        // Aynı türdeki diğer "Kuşanıldı" kartları "Çantada"ya döner.
        const tur = kart.closest('li').dataset.tur;
        izgara.querySelectorAll('li').forEach((li) => {
            const k = li.querySelector('.esya-karti');
            if (li.dataset.tur === tur && k !== kart && k.dataset.sahip === '1') durumYaz(k, m('market.kart_cantada'));
        });
        durumYaz(kart, m('ortak.kusanildi'), true);
        K.bildirim(m('ortak.kusanildi_bildirim', { esya: kart.dataset.ad }), { tur: 'basari' });
        K.duyuru(m('ortak.kusanildi_bildirim', { esya: kart.dataset.ad }));
    }

    // ------------------------------------------------------------ satın alma (M05)
    function satinAl(kart) {
        const ad = kart.dataset.ad;
        const fiyat = Number(kart.dataset.fiyat);
        const altin = K.altinOku();
        K.onayIste({
            baslik: m('market.satin_al_onay_baslik'),
            aciklama: m('market.satin_al_onay_aciklama', { esya: ad, fiyat: K.sayi(fiyat), kalan: K.sayi(altin - fiyat) }),
            not: m('market.satin_al_onay_not'),
            birincil: m('market.satin_al_onay_dugme'),
            onay: async () => {
                let y;
                try {
                    y = await K.istek('ajax_islem.php', { buy_item_id: kart.dataset.esyaId });
                } catch (h) {
                    K.istekHatasiGoster(h, m('market.hata_satin_alinamadi'), () => satinAl(kart));
                    return { kapat: true };
                }
                if (y.success) {
                    const yeni = Number(y.new_balance);
                    K.altinGuncelle(yeni);
                    sahipYap(kart);
                    eksikleriGuncelle(yeni);
                    K.bildirim(m('market.satin_alindi_bildirim', { esya: ad }), {
                        tur: 'basari',
                        eylem: { etiket: m('ortak.kusan'), fn: () => kusan(kart) },
                    });
                    K.duyuru(m('market.satin_alindi_okuyucu', { esya: ad, kalan: K.sayi(yeni) }));
                    return { kapat: true };
                }
                if (y.kod === 'yetersiz_altin') {
                    if (typeof y.bakiye === 'number') { K.altinGuncelle(y.bakiye); eksikleriGuncelle(y.bakiye); }
                    K.bildirim(m('market.hata_yetersiz_altin', { eksik: K.sayi(y.eksik || 0) }), { tur: 'hata' });
                } else if (y.kod === 'zaten_sahip') {
                    sahipYap(kart);
                    K.bildirim(m('market.hata_zaten_sahip'), { tur: 'hata' });
                } else if (y.kod === 'bulunamadi') {
                    kart.closest('li').remove();
                    K.bildirim(m('market.hata_bulunamadi'), { tur: 'hata' });
                } else {
                    K.bildirim(m('market.hata_satin_alinamadi'), { tur: 'hata' });
                }
                return { kapat: true };
            },
        });
    }

    izgara.addEventListener('click', (e) => {
        const kart = e.target.closest('.esya-karti');
        if (!kart) return;
        if (kart.dataset.sahip === '1') {
            K.bildirim(m('market.hata_zaten_sahip'));
            return;
        }
        const gereken = Number(kart.dataset.seviye);
        if (gereken > V.seviye) {
            K.bildirim(m('market.detay_kilit_aciklama', { gereken, mevcut: V.seviye }));
            return;
        }
        const eksik = Number(kart.dataset.fiyat) - K.altinOku();
        if (eksik > 0) {
            K.bildirim(m('market.hata_yetersiz_altin', { eksik: K.sayi(eksik) }));
            return;
        }
        satinAl(kart);
    });
})();
