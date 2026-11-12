// view/statik/avatar.js
// Görünüm (E51): hazır seçenek kutuları (ten, saç, üst giysi), anında önizleme ve kaydetme.
// Çanta (E52): eşya seçme/önizleme ve kuşanma. Eşya ekranda kalıcı olarak ancak sunucu
// onayından sonra kuşanılmış sayılır. Görsel yolları yalnızca sunucunun verdiği sabit
// tablodan (V.katmanlar) ve sunucuda üretilmiş <template> parçalarından gelir.
(function () {
    'use strict';

    const K = window.Charmoji;
    const m = K.m;
    const V = K.veri || {};
    const KATMAN = V.katmanlar || {};
    const BOLGELER = ['ten', 'sac', 'ust'];
    const YUVALAR = ['kolye', 'maske', 'gozluk', 'sapka'];
    const TUR_YUVA = { 'Kolye': 'kolye', 'Maske': 'maske', 'Gözlük': 'gozluk', 'Şapka': 'sapka' };

    // ------------------------------------------------------------ sekmeler + başlık
    const baslik = document.querySelector('.ust-baslik');
    K.sekmeleriBagla(document.getElementById('avatar-sekmeler'), {
        secildi: (sekme) => {
            const metin = sekme.dataset.capa === 'canta' ? m('canta.baslik') : m('gorunum.baslik');
            baslik.textContent = metin;
            document.title = metin + ' - Charmoji';
        },
    });

    const onizleme = document.querySelector('#onizleme .pk');

    /** Katman görselini sabit tablodaki kaynakla değiştirir. */
    function katmanAyarla(kok, kadraj, bolge, n) {
        const k = KATMAN[kadraj] && KATMAN[kadraj][bolge] && KATMAN[kadraj][bolge][n];
        const img = kok && kok.querySelector('.pk-' + bolge);
        if (!k || !img) return;
        img.srcset = k.srcset;
        img.src = k.src;
    }

    // ------------------------------------------------------------ Görünüm (E51)
    const secim = Object.assign({}, V.secenekler || {});
    let kayitli = Object.assign({}, secim);
    const kaydet = document.getElementById('gorunum-kaydet');
    const gruplar = Array.from(document.querySelectorAll('.gorunum-secenekler[role="radiogroup"]'));

    function degistiMi() { return BOLGELER.some((b) => secim[b] !== kayitli[b]); }

    function sec(bolge, n) {
        if (!BOLGELER.includes(bolge)) return;
        secim[bolge] = n;
        const grup = gruplar.find((g) => g.dataset.bolge === bolge);
        let ad = '';
        grup.querySelectorAll('.secenek-kutu').forEach((k) => {
            const evet = Number(k.dataset.n) === n;
            k.setAttribute('aria-checked', evet ? 'true' : 'false');
            k.tabIndex = evet ? 0 : -1;
            if (evet) ad = k.dataset.ad;
        });
        const seciliMetin = document.getElementById('secili-' + bolge);
        if (seciliMetin) seciliMetin.textContent = m('gorunum.secili', { secenek: ad });
        katmanAyarla(onizleme, 'tam', bolge, n);
        // Diğer bölgelerin kutuları oyuncunun güncel seçimini gösterir.
        gruplar.forEach((g) => {
            if (g.dataset.bolge === bolge) return;
            g.querySelectorAll('.secenek-kutu .pk').forEach((pk) => katmanAyarla(pk, 'yakin', bolge, n));
        });
        kaydet.disabled = !degistiMi();
    }

    gruplar.forEach((g) => {
        const kutular = Array.from(g.querySelectorAll('.secenek-kutu'));
        g.addEventListener('click', (e) => {
            const kutu = e.target.closest('.secenek-kutu');
            if (kutu) sec(g.dataset.bolge, Number(kutu.dataset.n));
        });
        g.addEventListener('keydown', (e) => {
            const yon = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[e.key];
            if (!yon) return;
            e.preventDefault();
            const i = kutular.findIndex((k) => k.getAttribute('aria-checked') === 'true');
            const yeni = kutular[(i + yon + kutular.length) % kutular.length];
            sec(g.dataset.bolge, Number(yeni.dataset.n));
            yeni.focus();
        });
    });

    kaydet.addEventListener('click', async () => {
        const gonder = {};
        BOLGELER.forEach((b) => { gonder[b] = secim[b]; });
        K.dugmeYukleniyor(kaydet, true);
        let y;
        try {
            y = await K.istek('ajax_islem.php', { save_avatar_options: 1, secenekler: JSON.stringify(gonder) });
        } catch (h) {
            K.dugmeYukleniyor(kaydet, false);
            K.istekHatasiGoster(h, m('gorunum.hata_kaydedilemedi'), () => kaydet.click());
            return;
        }
        K.dugmeYukleniyor(kaydet, false);
        if (y.success) {
            kayitli = Object.assign({}, gonder);
            kaydet.disabled = true;
            K.bildirim(m('gorunum.kaydedildi_bildirim'), { tur: 'basari' });
        } else if (y.kod === 'gecersiz_secenek') {
            // Reddedilen bölge kayıtlı seçeneğe döner (06 §5.1 madde 4).
            const geri = BOLGELER.includes(y.bolge) ? [y.bolge] : BOLGELER;
            geri.forEach((b) => sec(b, kayitli[b]));
            K.bildirim(m('gorunum.hata_gecersiz_secenek'), { tur: 'hata' });
        } else {
            kaydet.disabled = !degistiMi();
            K.bildirim(m('gorunum.hata_kaydedilemedi'), { tur: 'hata' });
        }
    });

    // ------------------------------------------------------------ Çanta (E52)
    const izgara = document.getElementById('canta-izgara');
    if (!izgara) return;
    const kusanDugme = document.getElementById('kusan-dugme');
    const filtre = document.getElementById('canta-filtre');
    const cipler = Array.from(filtre.querySelectorAll('[data-filtre]'));
    let secili = null;

    // Kayıtlı (kuşanılmış) eşya katmanları: önizleme her seçimde bunlardan yeniden kurulur.
    const kayitliYuvalar = {};
    YUVALAR.forEach((y) => {
        const yer = onizleme.querySelector('.pk-yuva[data-yuva="' + y + '"]');
        kayitliYuvalar[y] = yer ? Array.from(yer.childNodes).map((n) => n.cloneNode(true)) : [];
    });
    function kayitliyaDon() {
        YUVALAR.forEach((y) => {
            const yer = onizleme.querySelector('.pk-yuva[data-yuva="' + y + '"]');
            if (yer) yer.replaceChildren(...kayitliYuvalar[y].map((n) => n.cloneNode(true)));
        });
        sapkaSiniflari();
    }
    /** Şapka yuvasına göre: piksel şapka saçı keser, şapka yoksa üst boşluk kırpılır. */
    function sapkaSiniflari() {
        const yer = onizleme.querySelector('.pk-yuva[data-yuva="sapka"]');
        onizleme.classList.toggle('pk-sapkali', !!(yer && yer.querySelector('img')));
        onizleme.classList.toggle('pk-sapkasiz', !(yer && yer.firstElementChild));
    }
    /** Kartın eşyasını önizlemeye koyar; dönen: [yuva, düğümler] ya da null. */
    function esyaOnizle(kart) {
        kayitliyaDon();
        const sablon = kart.parentElement.querySelector('template[data-katman-yuva]');
        if (!sablon) return null;
        const yuva = sablon.dataset.katmanYuva;
        const yer = YUVALAR.includes(yuva) && onizleme.querySelector('.pk-yuva[data-yuva="' + yuva + '"]');
        if (!yer) return null;
        const dugumler = Array.from(sablon.content.childNodes).map((n) => n.cloneNode(true));
        yer.replaceChildren(...dugumler.map((n) => n.cloneNode(true)));
        sapkaSiniflari();
        return [yuva, dugumler];
    }

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
        esyaOnizle(kart); // yalnızca önizleme
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
            kayitliyaDon();
            K.istekHatasiGoster(h, m('ortak.hata_kusanilamadi'));
            return;
        }
        K.dugmeYukleniyor(kusanDugme, false);
        if (!y.success) {
            kayitliyaDon();
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
        // Sunucu onayladı: önizlemedeki katman artık kayıtlı durumdur.
        const sonuc = esyaOnizle(kart);
        if (sonuc) {
            kayitliYuvalar[sonuc[0]] = sonuc[1];
        } else {
            // Katman çizimi olmayan eşya: aynı türdeki eski eşya çıkarıldığı için yuva boşalır.
            const yuva = TUR_YUVA[kart.dataset.tur];
            if (yuva) {
                kayitliYuvalar[yuva] = [];
                kayitliyaDon();
            }
        }
        dugmeyiGuncelle();
        K.bildirim(m('ortak.kusanildi_bildirim', { esya: kart.dataset.ad }), { tur: 'basari' });
        K.duyuru(m('ortak.kusanildi_bildirim', { esya: kart.dataset.ad }));
    });
})();
