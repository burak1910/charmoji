// view/statik/gorev.js
// Görev kartı davranışı (03 §3, §4, §8) Karargah (E10) ve Görevler (E20) için;
// Görevler'e özgü: silme onayı (M03), geri alma onayı (M04), görev ekleme formu (E21),
// meydan okuma ödülü (K08). Kutlama animasyonları (K01-K05) sonraki aşamadadır.
(function () {
    'use strict';

    const K = window.Charmoji;
    const m = K.m;
    const V = K.veri || {};
    const karargahMi = V.sayfa === 'karargah';
    const ETIKET_SURESI = 1200; // "Tamamlandı" hâlinde bekleme (03 §2)

    // ------------------------------------------------------------ yardımcılar
    function el(etiket, sinif, metin) {
        const x = document.createElement(etiket);
        if (sinif) x.className = sinif;
        if (metin !== undefined) x.textContent = metin;
        return x;
    }
    function ikon(sinif) {
        const i = el('i', 'fa-solid ' + sinif);
        i.setAttribute('aria-hidden', 'true');
        return i;
    }
    function seriCarpani(seri) { return Math.min(2, 1 + Math.floor(seri / 7) * 0.25); }

    function kartVerisi(kart) {
        return {
            id: Number(kart.dataset.gorevId),
            ad: kart.dataset.ad,
            yetenek: kart.dataset.yetenekAdi,
            xp: Number(kart.dataset.xp),
            taban: Number(kart.dataset.taban) || 10,
            tamam: kart.dataset.tamam === '1',
        };
    }

    /** Kartı tamamlandı / yapılacak hâlinde yeniden çizer (sunucu yanıtıyla). */
    function kartiCiz(kart, tamam, xp, seri) {
        const v = kartVerisi(kart);
        kart.dataset.tamam = tamam ? '1' : '0';
        kart.dataset.xp = String(xp);
        const onay = kart.querySelector('.gorev-onay');
        onay.setAttribute('aria-checked', tamam ? 'true' : 'false');
        onay.setAttribute('aria-label', tamam
            ? m('gorevler.kart_tamamlandi_etiketi', { gorev: v.ad, xp })
            : m('gorevler.kart_onay_etiketi', { gorev: v.ad, xp, yetenek: v.yetenek }));

        const bilgi = kart.querySelector('.gorev-bilgi');
        const yetenekEtiketi = bilgi.querySelector('.yetenek-etiket');
        bilgi.innerHTML = '';
        bilgi.appendChild(yetenekEtiketi);
        if (tamam) {
            const t = el('span', 'gorev-tamam-etiket');
            t.appendChild(ikon('fa-check'));
            t.appendChild(document.createTextNode(' ' + m('gorevler.kart_tamamlandi')));
            bilgi.appendChild(t);
            bilgi.appendChild(el('span', 'gorev-odul', m('gorevler.kart_kazanildi', { xp })));
        } else {
            bilgi.appendChild(el('span', 'gorev-odul', m('gorevler.kart_odul', { xp })));
        }
        const s = el('span', 'gorev-seri');
        s.appendChild(ikon('fa-fire'));
        s.appendChild(document.createTextNode(' ' + (seri > 0 ? m('gorevler.kart_seri', { seri }) : m('gorevler.kart_seri_yok'))));
        bilgi.appendChild(s);
        const c = seriCarpani(seri);
        if (c > 1) bilgi.appendChild(el('span', 'gorev-bonus', '×' + K.carpan(c)));
        const not = kart.querySelector('.gorev-not');
        if (not) not.remove();
    }

    /** Yetenek barlarını (Karargah mini barları) sunucu verisiyle günceller. */
    function yetenekGuncelle(kod, veri) {
        const bar = document.getElementById('mini-bar-' + kod);
        if (!bar || !veri) return;
        bar.querySelector('.bar-dolgu').style.width = veri.percent + '%';
        bar.setAttribute('aria-valuenow', veri.currentXP);
        bar.setAttribute('aria-valuemax', veri.neededXP);
        const ad = bar.closest('a').querySelector('.yetenek-etiket').textContent;
        bar.setAttribute('aria-label', m('ortak.yetenek_bar_etiketi', { yetenek: ad, seviye: veri.level, mevcut: veri.currentXP, gereken: veri.neededXP }));
        const sv = document.getElementById('mini-seviye-' + kod);
        if (sv) { sv.textContent = m('ortak.seviye_kisa', { seviye: veri.level }); sv.dataset.seviye = veri.level; }

        // Sonraki karakter seviyesi (02 §2.1 madde 4)
        const seviyeler = Array.from(document.querySelectorAll('[id^="mini-seviye-"]')).map((x) => Number(x.dataset.seviye) || 1);
        if (seviyeler.length === 5) {
            const toplam = seviyeler.reduce((a, b) => a + b, 0);
            const seviye = Math.floor(toplam / 5);
            const sonraki = document.getElementById('sonraki-seviye');
            if (sonraki) sonraki.textContent = m('ortak.sonraki_seviye', { sonraki: seviye + 1, kalan: 5 * (seviye + 1) - toplam });
            const ks = document.getElementById('karakter-seviye');
            if (ks) ks.textContent = m('ortak.seviye_kisa', { seviye });
        }
    }

    // ------------------------------------------------------------ sayaçlar ve listeler
    const durum = { toplam: Number(V.toplam) || 0, tamamlanan: Number(V.tamamlanan) || 0 };

    function karargahGuncelle() {
        const metin = m('karargah.bugun_ilerleme', { tamamlanan: durum.tamamlanan, toplam: durum.toplam });
        const pm = document.getElementById('ilerleme-metin');
        if (pm) pm.textContent = metin;
        const bar = document.getElementById('ilerleme-bar');
        if (bar) {
            bar.setAttribute('aria-valuenow', durum.tamamlanan);
            bar.setAttribute('aria-label', metin);
            bar.querySelector('.bar-dolgu').style.width = (durum.toplam ? durum.tamamlanan / durum.toplam * 100 : 0) + '%';
        }
        // Ruh hali (02 §2.2)
        let ruh = 'hazir';
        if (durum.toplam === 0) ruh = 'gorev_yok';
        else if (durum.tamamlanan >= durum.toplam) ruh = 'zafer';
        else if (durum.tamamlanan > 0) ruh = 'neseli';
        else if (V.ruhHali === 'uykulu') ruh = 'uykulu';
        V.ruhHali = ruh;
        if (!balonGecici) balonYaz(m('karargah.ruh_hali_' + ruh));
    }

    /** Karargah: görünür (tamamlanmamış) kartlar en fazla 5; boşsa "hepsi tamam". */
    function karargahListesi() {
        const liste = document.getElementById('siradaki-liste');
        if (!liste) return;
        let gorunen = 0;
        Array.from(liste.children).forEach((kart) => {
            const goster = kart.dataset.tamam !== '1' && gorunen < 5;
            kart.hidden = !goster;
            kart.classList.remove('gizleniyor');
            if (goster) gorunen++;
        });
        const bos = document.getElementById('hepsi-tamam');
        if (bos) bos.hidden = gorunen > 0;
    }

    function siraliEkle(liste, kart, basa) {
        if (basa) { liste.insertBefore(kart, liste.firstChild); return; }
        const id = Number(kart.dataset.gorevId);
        const sonraki = Array.from(liste.children).find((x) => Number(x.dataset.gorevId) > id);
        liste.insertBefore(kart, sonraki || null);
    }

    function gorevlerSayaclari() {
        const yap = document.getElementById('liste-yapilacaklar');
        const tam = document.getElementById('liste-tamamlananlar');
        if (!yap || !tam) return;
        const ny = yap.children.length, nt = tam.children.length, toplam = ny + nt;
        document.getElementById('baslik-yapilacaklar').textContent = m('gorevler.yapilacaklar_baslik', { sayi: ny });
        document.getElementById('baslik-tamamlananlar').textContent = m('gorevler.tamamlananlar_baslik', { sayi: nt });
        document.getElementById('bolum-yapilacaklar').hidden = ny === 0;
        document.getElementById('bolum-tamamlananlar').hidden = nt === 0;
        const ozet = m('gorevler.bugun_ozet', { tamamlanan: nt, toplam });
        document.getElementById('ozet-metin').textContent = ozet;
        const bar = document.getElementById('ozet-bar');
        bar.setAttribute('aria-valuenow', nt);
        bar.setAttribute('aria-valuemax', toplam);
        bar.setAttribute('aria-label', ozet);
        bar.querySelector('.bar-dolgu').style.width = (toplam ? nt / toplam * 100 : 0) + '%';
        if (toplam === 0) window.location.reload(); // boş durum sunucuda çizilir
    }

    function kartiTasi(kart) {
        if (karargahMi) {
            if (kart.dataset.tamam === '1') {
                kart.classList.add('gizleniyor');
                window.setTimeout(() => { if (kart.dataset.tamam === '1') karargahListesi(); }, K.azHareket ? 0 : 300);
            } else {
                karargahListesi();
            }
            return;
        }
        const hedef = document.getElementById(kart.dataset.tamam === '1' ? 'liste-tamamlananlar' : 'liste-yapilacaklar');
        if (hedef && kart.parentElement !== hedef) siraliEkle(hedef, kart, kart.dataset.tamam === '1');
        gorevlerSayaclari();
    }

    // ------------------------------------------------------------ tamamla / geri al
    async function tamamla(kart, yeniDurum) {
        if (kart.dataset.mesgul === '1') return;
        const v = kartVerisi(kart);
        const onay = kart.querySelector('.gorev-onay');
        kart.dataset.mesgul = '1';
        onay.setAttribute('aria-checked', yeniDurum ? 'true' : 'false'); // basılma tepkisi
        const yukleme = window.setTimeout(() => onay.setAttribute('aria-busy', 'true'), 400);

        let y = null, hata = null;
        try {
            y = await K.istek('ajax_islem.php', { ka_id: v.id, durum: yeniDurum ? 1 : 0 });
        } catch (h) {
            hata = h;
        }
        window.clearTimeout(yukleme);
        onay.removeAttribute('aria-busy');
        delete kart.dataset.mesgul;

        const varsayilan = yeniDurum ? m('gorevler.hata_tamamlanamadi') : m('gorevler.hata_geri_alinamadi');
        if (hata || !y.success) {
            onay.setAttribute('aria-checked', v.tamam ? 'true' : 'false');
            if (hata) K.istekHatasiGoster(hata, varsayilan, () => tamamla(kart, yeniDurum));
            else K.bildirim(varsayilan, { tur: 'hata' });
            return;
        }

        const seri = Number(y.new_streak) || 0;
        const xp = yeniDurum ? Number(y.xp) : Math.round(v.taban * seriCarpani(seri));
        kartiCiz(kart, yeniDurum, yeniDurum ? (y.degisti ? Number(y.xp) : v.xp) : xp, seri);
        yetenekGuncelle(y.stat_type, y.stat_data);
        K.seviyeGuncelle(y.global_level);
        if (typeof y.odul_bekliyor === 'boolean') K.odulRozeti(y.odul_bekliyor);

        if (y.degisti) {
            durum.tamamlanan += yeniDurum ? 1 : -1;
            const sd = y.stat_data || {};
            if (yeniDurum) {
                K.bildirim(m('gorevler.tamamlandi_bildirim', { gorev: v.ad }), {
                    tur: 'basari',
                    eylem: { etiket: m('ortak.geri_al'), fn: () => tamamla(kart, false) },
                });
                K.duyuru(m('gorevler.okuyucu_tamamlandi', { gorev: v.ad, xp: y.xp, yetenek: v.yetenek, mevcut: sd.currentXP, gereken: sd.neededXP }));
                if (karargahMi && !K.azHareket) {
                    const g = document.getElementById('karakter-govde');
                    if (g) { g.classList.remove('sekme-hop'); void g.offsetWidth; g.classList.add('sekme-hop'); }
                }
            } else {
                K.bildirim(m('gorevler.geri_alindi_bildirim', { xp: y.xp }));
                K.duyuru(m('gorevler.okuyucu_geri_alindi', { gorev: v.ad, xp: y.xp }));
            }
        }
        if (karargahMi) karargahGuncelle();
        if (yeniDurum) window.setTimeout(() => { if (kart.dataset.tamam === '1') kartiTasi(kart); }, K.azHareket ? 0 : ETIKET_SURESI);
        else kartiTasi(kart);
    }

    function kartTiklandi(kart) {
        const v = kartVerisi(kart);
        if (!v.tamam) { tamamla(kart, true); return; }
        if (karargahMi) return; // Karargah'ta tamamlanan kart listede kalmaz
        // E20'de tamamlanmış karta dokunma → M04 onayı
        K.onayIste({
            baslik: m('gorevler.geri_al_onay_baslik'),
            aciklama: m('gorevler.geri_al_onay_aciklama', { gorev: v.ad, xp: v.xp }),
            birincil: m('gorevler.geri_al_onay_dugme'),
            onay: () => tamamla(kart, false),
        });
    }

    document.addEventListener('click', (e) => {
        const kart = e.target.closest('.gorev-karti');
        if (!kart) return;
        if (e.target.closest('.menu-dugme, .acilir-menu')) return;
        kartTiklandi(kart);
    });

    // ------------------------------------------------------------ silme (M03)
    function gorevSil(kart) {
        const v = kartVerisi(kart);
        K.onayIste({
            baslik: m('gorevler.sil_onay_baslik'),
            aciklama: m('gorevler.sil_onay_aciklama', { gorev: v.ad }),
            birincil: m('gorevler.sil_onay_dugme'),
            tehlikeli: true,
            onay: async () => {
                let y;
                try {
                    y = await K.istek('ajax_islem.php', { ka_id: v.id, is_delete: 1 });
                } catch (h) {
                    K.istekHatasiGoster(h, m('gorevler.hata_silinemedi'));
                    return { kapat: true };
                }
                if (!y.success) return { hata: m('gorevler.hata_silinemedi') };
                kart.classList.add('gizleniyor');
                window.setTimeout(() => { kart.remove(); gorevlerSayaclari(); }, K.azHareket ? 0 : 300);
                K.bildirim(m('gorevler.silindi_bildirim'), { tur: 'basari' });
                K.duyuru(m('gorevler.silindi_bildirim'));
                return { kapat: true };
            },
        });
    }
    document.querySelectorAll('.gorev-karti .menu-dugme').forEach((d) => {
        const menu = document.getElementById(d.getAttribute('aria-controls'));
        if (!menu) return;
        const kontrol = K.menuBagla(d, menu);
        menu.querySelector('[data-eylem="sil"]').addEventListener('click', () => {
            kontrol.kapat(true);
            gorevSil(d.closest('.gorev-karti'));
        });
    });

    // ------------------------------------------------------------ meydan okuma ödülü (K08)
    document.querySelectorAll('[data-odul-al]').forEach((d) => {
        d.addEventListener('click', async () => {
            if (d.getAttribute('aria-busy') === 'true') return;
            K.dugmeYukleniyor(d, true);
            let y;
            try {
                y = await K.istek('ajax_islem.php', { claim_quest_id: d.dataset.odulAl });
            } catch (h) {
                K.dugmeYukleniyor(d, false);
                K.istekHatasiGoster(h, m('gorevler.meydan_hata_alinamadi'), () => d.click());
                return;
            }
            const alindi = () => {
                const t = el('span', 'gorev-tamam-etiket');
                t.appendChild(ikon('fa-check'));
                t.appendChild(document.createTextNode(' ' + m('gorevler.meydan_alindi')));
                d.replaceWith(t);
            };
            if (y.success) {
                alindi();
                K.altinGuncelle(Number(y.new_balance));
                if (typeof y.odul_bekliyor === 'boolean') K.odulRozeti(y.odul_bekliyor);
                K.duyuru(m('gorevler.meydan_odul_alindi_okuyucu', { altin: K.sayi(y.odul), toplam: K.sayi(y.new_balance) }));
                return;
            }
            K.dugmeYukleniyor(d, false);
            if (y.kod === 'zaten_alindi') { alindi(); K.bildirim(m('gorevler.meydan_hata_zaten'), { tur: 'hata' }); }
            else if (y.kod === 'tamamlanmadi') K.bildirim(m('gorevler.meydan_hata_tamamlanmadi'), { tur: 'hata' });
            else K.bildirim(m('gorevler.meydan_hata_alinamadi'), { tur: 'hata' });
        });
    });

    // Yenilenmeye kalan süre (dakikada bir)
    const yenilenme = document.getElementById('meydan-yenilenme');
    if (yenilenme && V.gunSonuSn) {
        const bitis = Date.now() + V.gunSonuSn * 1000;
        window.setInterval(() => {
            const kalan = Math.max(0, Math.floor((bitis - Date.now()) / 1000));
            yenilenme.textContent = m('gorevler.meydan_yenilenme', { saat: Math.floor(kalan / 3600), dakika: Math.floor((kalan % 3600) / 60) });
        }, 60000);
    }

    // ------------------------------------------------------------ görev ekleme formu (E21)
    const ekleArka = document.getElementById('ekle-arka');
    const form = document.getElementById('ekle-form');
    let ekleOdakDon = null;
    function ekleAc() {
        if (!ekleArka) return;
        ekleOdakDon = document.activeElement;
        ekleArka.hidden = false;
        document.body.classList.add('modal-acik');
        document.getElementById('ekle-baslik').setAttribute('tabindex', '-1');
        document.getElementById('habit_name').focus();
    }
    function ekleKapat() {
        if (!ekleArka || ekleArka.hidden) return;
        ekleArka.hidden = true;
        document.body.classList.remove('modal-acik');
        if (location.hash === '#ekle') history.replaceState(null, '', location.pathname + location.search);
        if (ekleOdakDon && document.contains(ekleOdakDon)) ekleOdakDon.focus();
    }
    if (form) {
        const ad = document.getElementById('habit_name');
        const sayac = document.getElementById('ad-sayac');
        const adHata = document.getElementById('ad-hata');
        const yHata = document.getElementById('yetenek-hata');
        const onizleme = document.getElementById('odul-onizleme');
        const radyolar = Array.from(form.querySelectorAll('input[name="habit_stat"]'));

        const sayacGuncelle = () => { sayac.textContent = m('gorev_form.ad_sayac', { uzunluk: Array.from(ad.value).length }); };
        const onizlemeGuncelle = () => {
            const secili = radyolar.find((r) => r.checked);
            onizleme.hidden = !secili;
            if (secili) onizleme.textContent = m('gorev_form.odul_onizleme', { yetenek: form.querySelector('label[for="' + secili.id + '"] .yetenek-etiket').textContent });
        };
        const hataGoster = (alan, kutu, metin) => {
            kutu.textContent = metin || '';
            kutu.hidden = !metin;
            if (alan) alan.setAttribute('aria-invalid', metin ? 'true' : 'false');
        };
        const adDogrula = () => {
            const deger = ad.value.trim();
            if (!deger) return m('gorev_form.hata_ad_bos');
            if (Array.from(deger).length > 60) return m('gorev_form.hata_ad_uzun');
            return '';
        };

        ad.addEventListener('input', sayacGuncelle);
        ad.addEventListener('blur', () => { if (ad.value !== '') hataGoster(ad, adHata, adDogrula()); });
        radyolar.forEach((r) => r.addEventListener('change', () => { onizlemeGuncelle(); hataGoster(null, yHata, ''); }));
        form.querySelectorAll('[data-oneri-ad]').forEach((c) => c.addEventListener('click', () => {
            ad.value = c.dataset.oneriAd;
            const r = radyolar.find((x) => x.value === c.dataset.oneriYetenek);
            if (r) r.checked = true;
            sayacGuncelle(); onizlemeGuncelle();
            hataGoster(ad, adHata, ''); hataGoster(null, yHata, '');
        }));
        form.addEventListener('submit', (e) => {
            const adSorun = adDogrula();
            const yetenekSorun = radyolar.some((r) => r.checked) ? '' : m('gorev_form.hata_yetenek_yok');
            hataGoster(ad, adHata, adSorun);
            hataGoster(null, yHata, yetenekSorun);
            if (adSorun || yetenekSorun) {
                e.preventDefault();
                (adSorun ? ad : radyolar[0]).focus();
                return;
            }
            const gonder = form.querySelector('[type="submit"]');
            window.setTimeout(() => K.dugmeYukleniyor(gonder, true), 0);
        });
        form.querySelector('[data-ekle-kapat]').addEventListener('click', ekleKapat);
        ekleArka.addEventListener('click', (e) => { if (e.target === ekleArka) ekleKapat(); });
        ekleArka.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') { e.preventDefault(); ekleKapat(); return; }
            if (e.key === 'Tab') {
                const odaklar = Array.from(form.querySelectorAll('button:not([disabled]), input:not([type="hidden"]):not([disabled])'))
                    .filter((x) => x.type !== 'radio' || x.checked || !radyolar.some((r) => r.checked));
                const ilk = odaklar[0], son = odaklar[odaklar.length - 1];
                if (e.shiftKey && document.activeElement === ilk) { e.preventDefault(); son.focus(); }
                else if (!e.shiftKey && document.activeElement === son) { e.preventDefault(); ilk.focus(); }
            }
        });
        sayacGuncelle(); onizlemeGuncelle();
        document.querySelectorAll('[data-ekle-ac]').forEach((d) => d.addEventListener('click', ekleAc));
        if (location.hash === '#ekle') ekleAc();
    }

    // ------------------------------------------------------------ Karargah: karaktere dokunma (02 §3.4)
    const balon = document.getElementById('konusma-balonu');
    let balonGecici = false, balonZamani = null, sonSoz = 0, sonDokunma = 0;
    function balonYaz(metin) { if (balon) balon.textContent = metin; }
    const karakter = document.getElementById('karakter-dugme');
    if (karakter) {
        karakter.addEventListener('click', () => {
            const simdi = Date.now();
            if (simdi - sonDokunma < 1000) return;
            sonDokunma = simdi;
            let n;
            do { n = 1 + Math.floor(Math.random() * 5); } while (n === sonSoz);
            sonSoz = n;
            balonGecici = true;
            balonYaz(m('karargah.dokunma_sozu_' + n));
            const g = document.getElementById('karakter-govde');
            if (g && !K.azHareket) { g.classList.remove('sekme-hop'); void g.offsetWidth; g.classList.add('sekme-hop'); }
            window.clearTimeout(balonZamani);
            balonZamani = window.setTimeout(() => { balonGecici = false; balonYaz(m('karargah.ruh_hali_' + V.ruhHali)); }, 4000);
        });
    }
})();
