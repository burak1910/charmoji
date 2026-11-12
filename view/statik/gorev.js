// view/statik/gorev.js
// Görev kartı davranışı (03 §3, §4, §8) Karargah (E10) ve Görevler (E20) için:
// tamamlama / geri alma, seri bilgisi (M02). Görevler'e özgü: geri alma onayı (M04),
// görev formu (E21: ekle ve düzenle, M07), silme = arşivleme (M03) + "Geri al",
// meydan okuma ödülü (K08). Kutlama animasyonları (K01-K05) sonraki aşamadadır.
//
// Kartın içeriği her zaman sunucunun çizdiği HTML'dir (yanıttaki kart_html); seri,
// ödül ve çarpan hesabı tek yerde (sunucuda) yapılır. Kurallar (ad, sınır, sahiplik)
// sunucuda uygulanır; buradaki denetimler yalnızca erken geri bildirimdir.
(function () {
    'use strict';

    const K = window.Charmoji;
    const m = K.m;
    const V = K.veri || {};
    const C = window.CHARMOJI || {};
    const karargahMi = V.sayfa === 'karargah';
    const KART_TIPI = karargahMi ? 'karargah' : 'gorevler';
    const ETIKET_SURESI = 1200; // "Tamamlandı" hâlinde bekleme (03 §2)
    const SINIR = Number(V.sinir) || 20;
    const AD_EN_UZUN = Number(V.adEnUzun) || 60;

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
    function seriCarpani(seri) { return Math.min(2, 1 + Math.floor(Math.max(0, seri) / 7) * 0.25); }

    function kartVerisi(kart) {
        return {
            id: Number(kart.dataset.gorevId),
            ad: kart.dataset.ad,
            yetenekKod: kart.dataset.yetenek,
            yetenek: kart.dataset.yetenekAdi,
            xp: Number(kart.dataset.xp),
            taban: Number(kart.dataset.taban) || 10,
            tamam: kart.dataset.tamam === '1',
            etkinSeri: Number(kart.dataset.etkinSeri) || 0,
        };
    }

    /** Sunucunun çizdiği kart HTML'inden öğe üretir (HTML sunucuda kaçışlanmıştır). */
    function htmlden(html) {
        const sablon = document.createElement('template');
        sablon.innerHTML = String(html || '').trim();
        return sablon.content.firstElementChild;
    }

    /** Sunucudan gelen kart HTML'ini var olan <li>'ye uygular; öğe ve konumu korunur. */
    function kartiYenile(kart, html) {
        const yeni = htmlden(html);
        if (!yeni) return kart;
        kart.className = yeni.className;
        Array.from(kart.attributes).forEach((a) => { if (a.name.startsWith('data-')) kart.removeAttribute(a.name); });
        Array.from(yeni.attributes).forEach((a) => { if (a.name.startsWith('data-')) kart.setAttribute(a.name, a.value); });
        kart.replaceChildren(...Array.from(yeni.childNodes));
        menuyuBagla(kart);
        return kart;
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

    /** Kartı listeye oluşturulma sırasına göre (kimliğe göre) ya da en başa yerleştirir. */
    function siraliEkle(liste, kart, basa) {
        if (basa) { liste.insertBefore(kart, liste.firstChild); return; }
        const id = Number(kart.dataset.gorevId);
        const sonraki = Array.from(liste.children).find((x) => x !== kart && Number(x.dataset.gorevId) > id);
        liste.insertBefore(kart, sonraki || null);
    }

    function aktifGorevSayisi() {
        return document.querySelectorAll('#liste-yapilacaklar > .gorev-karti:not(.siliniyor), #liste-tamamlananlar > .gorev-karti:not(.siliniyor)').length;
    }

    /** "Görev ekle" düğmelerinin kilidi (S10: sınırda kilit ikonu, dokununca bildirim). */
    function ekleKilidiGuncelle() {
        const kilitli = aktifGorevSayisi() >= SINIR;
        document.querySelectorAll('[data-ekle-ac]').forEach((d) => {
            if (kilitli) d.dataset.kilitli = '1'; else delete d.dataset.kilitli;
            const i = d.querySelector('i.fa-solid');
            if (i) { i.classList.toggle('fa-lock', kilitli); i.classList.toggle('fa-plus', !kilitli); }
        });
    }

    function gorevlerSayaclari() {
        const yap = document.getElementById('liste-yapilacaklar');
        const tam = document.getElementById('liste-tamamlananlar');
        if (!yap || !tam) return;
        const ny = yap.querySelectorAll(':scope > .gorev-karti:not(.siliniyor)').length;
        const nt = tam.querySelectorAll(':scope > .gorev-karti:not(.siliniyor)').length;
        const toplam = ny + nt;
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
        // Boş durum (03 §13): özet ve listeler gizlenir; meydan okumalar görünür kalır.
        document.getElementById('gorev-bos').hidden = toplam > 0;
        document.getElementById('gorev-ozet').hidden = toplam === 0;
        ekleKilidiGuncelle();
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

    // ------------------------------------------------------------ meydan okumalar (E20)
    function meydanEylemi(q, kartEl) {
        if (q.tamam && !q.alindi) {
            const d = el('button', 'dugme dugme-birincil dugme-kucuk', m('gorevler.meydan_odulu_al'));
            d.type = 'button';
            d.style.minWidth = '96px';
            d.dataset.odulAl = String(q.id);
            d.dataset.odul = kartEl.dataset.odul || '0';
            d.setAttribute('data-veri-degistirir', '');
            return d;
        }
        if (q.alindi) {
            const t = el('span', 'gorev-tamam-etiket');
            t.appendChild(ikon('fa-check'));
            t.appendChild(document.createTextNode(' ' + m('gorevler.meydan_alindi')));
            return t;
        }
        return el('span', 'ikincil-metin kucuk', m('gorevler.meydan_devam'));
    }

    /** Tamamlama / geri alma sonrası "görev tamamlama" meydan okumalarını günceller (S25, S7). */
    function meydanGuncelle(liste) {
        if (!Array.isArray(liste)) return;
        liste.forEach((q) => {
            const kartEl = document.querySelector('[data-meydan-id="' + Number(q.id) + '"]');
            if (!kartEl) return;
            const hedef = Math.max(1, Number(q.hedef) || 1);
            const mevcut = Math.min(hedef, Number(q.mevcut) || 0);
            const etiket = m('gorevler.meydan_odul_etiketi', { baslik: kartEl.dataset.baslik, mevcut, hedef, altin: K.sayi(kartEl.dataset.odul) });
            kartEl.setAttribute('aria-label', etiket);
            const metin = kartEl.querySelector('.meydan-ilerleme-metin');
            if (metin) metin.textContent = m('gorevler.meydan_ilerleme', { mevcut, hedef });
            const bar = kartEl.querySelector('.meydan-bar');
            if (bar) {
                bar.setAttribute('aria-valuenow', mevcut);
                bar.setAttribute('aria-label', etiket);
                bar.querySelector('.bar-dolgu').style.width = (mevcut / hedef * 100) + '%';
            }
            const eylem = kartEl.querySelector('.meydan-eylem');
            const odakVardi = eylem && eylem.contains(document.activeElement);
            if (eylem) eylem.replaceChildren(meydanEylemi(q, kartEl));
            if (odakVardi && eylem.firstElementChild && eylem.firstElementChild.tagName === 'BUTTON') eylem.firstElementChild.focus();
        });
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
            y = await K.istek('ajax_islem.php', { ka_id: v.id, durum: yeniDurum ? 1 : 0, gun: C.bugun || '', kart_tipi: KART_TIPI });
        } catch (h) {
            hata = h;
        }
        window.clearTimeout(yukleme);
        onay.removeAttribute('aria-busy');
        delete kart.dataset.mesgul;

        const varsayilan = yeniDurum ? m('gorevler.hata_tamamlanamadi') : m('gorevler.hata_geri_alinamadi');
        if (hata) {
            onay.setAttribute('aria-checked', v.tamam ? 'true' : 'false');
            K.istekHatasiGoster(hata, varsayilan, () => tamamla(kart, yeniDurum));
            return;
        }
        if (!y.success) {
            if (y.kod === 'gun_bitti') {
                onay.setAttribute('aria-checked', v.tamam ? 'true' : 'false');
                K.bildirim(m('gorevler.hata_gun_bitti'), { tur: 'hata' });
                window.setTimeout(() => window.location.reload(), 2000);
                return;
            }
            // Zaten tamamlanmış / tamamlanmamış: kart sunucudaki hâle döner.
            if (y.kart_html) {
                const once = kart.dataset.tamam;
                kartiYenile(kart, y.kart_html);
                if (kart.dataset.tamam !== once) {
                    durum.tamamlanan += kart.dataset.tamam === '1' ? 1 : -1;
                    if (karargahMi) karargahGuncelle();
                    kartiTasi(kart);
                }
            } else {
                onay.setAttribute('aria-checked', v.tamam ? 'true' : 'false');
            }
            K.bildirim(y.kod === 'zaten_tamamlandi' ? m('gorevler.zaten_tamamlandi') : varsayilan, { tur: 'hata' });
            return;
        }

        kartiYenile(kart, y.kart_html);
        yetenekGuncelle(y.stat_type, y.stat_data);
        K.seviyeGuncelle(y.global_level);
        if (typeof y.odul_bekliyor === 'boolean') K.odulRozeti(y.odul_bekliyor);
        meydanGuncelle(y.meydan);

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
            let okuyucu = m('gorevler.okuyucu_geri_alindi', { gorev: v.ad, xp: y.xp });
            // S8: seviye sessizce düşer; yalnızca ekran okuyucuya duyurulur.
            if (Number(y.yetenek_seviye_sonra) < Number(y.yetenek_seviye_once)) {
                okuyucu += ' ' + m('gorevler.okuyucu_seviye_dustu', { yetenek: y.stat_adi || v.yetenek, seviye: y.yetenek_seviye_sonra });
            }
            K.duyuru(okuyucu);
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
        const seriDugme = e.target.closest('.gorev-seri-dugme');
        if (seriDugme) { seriAc(seriDugme.closest('.gorev-karti'), seriDugme); return; }
        const kart = e.target.closest('.gorev-karti');
        if (!kart) return;
        if (e.target.closest('.menu-dugme, .acilir-menu')) return;
        kartTiklandi(kart);
    });

    // ------------------------------------------------------------ seri bilgisi (M02, 03 §7)
    const seriArka = document.getElementById('seri-arka');
    let seriOdakDon = null;
    function seriAc(kart, dugme) {
        if (!seriArka || !kart) return;
        const seri = kartVerisi(kart).etkinSeri; // "şu anki" değerler etkin seriye göre
        const carpan = seriCarpani(seri);
        document.getElementById('seri-gorev-serisi').textContent = m('seri.modal_gorev_serisi', { seri });
        document.getElementById('seri-simdiki').textContent = m('seri.modal_simdiki_bonus', { carpan: K.carpan(carpan) });
        const kalan = 7 * (Math.floor(seri / 7) + 1) - seri;
        document.getElementById('seri-sonraki').textContent = seri >= 28
            ? m('seri.modal_en_yuksek')
            : m('seri.modal_sonraki_bonus', { kalan, sonraki_carpan: K.carpan(seriCarpani(seri + kalan)) });
        const simdikiSatir = Math.min(4, Math.floor(seri / 7));
        seriArka.querySelectorAll('[data-seri-satir]').forEach((tr) => {
            if (Number(tr.dataset.seriSatir) === simdikiSatir) tr.setAttribute('aria-current', 'true');
            else tr.removeAttribute('aria-current');
        });
        seriOdakDon = dugme || document.activeElement;
        seriArka.hidden = false;
        document.body.classList.add('modal-acik');
        document.getElementById('seri-kapat').focus();
    }
    function seriKapat() {
        if (!seriArka || seriArka.hidden) return;
        seriArka.hidden = true;
        if (!document.querySelector('.alt-sayfa-arka:not([hidden])')) document.body.classList.remove('modal-acik');
        if (seriOdakDon && document.contains(seriOdakDon)) seriOdakDon.focus();
    }
    if (seriArka) {
        document.getElementById('seri-kapat').addEventListener('click', seriKapat);
        seriArka.addEventListener('click', (e) => { if (e.target === seriArka) seriKapat(); });
        seriArka.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); seriKapat(); }
            else if (e.key === 'Tab') { e.preventDefault(); document.getElementById('seri-kapat').focus(); }
        });
    }

    // ------------------------------------------------------------ kart menüsü (Düzenle / Sil)
    // Görev formu yalnızca Görevler'de vardır; aşağıda (form bölümünde) tanımlanır.
    let formAc = () => {};
    let formuKapat = () => {};
    function menuyuBagla(kart) {
        const d = kart.querySelector('.menu-dugme');
        if (!d) return;
        const menu = kart.querySelector('.acilir-menu');
        if (!menu) return;
        const kontrol = K.menuBagla(d, menu);
        const duzenle = menu.querySelector('[data-eylem="duzenle"]');
        if (duzenle) duzenle.addEventListener('click', () => { kontrol.kapat(false); formAc('duzenle', kart); });
        menu.querySelector('[data-eylem="sil"]').addEventListener('click', () => {
            kontrol.kapat(true);
            gorevSil(kart);
        });
    }
    document.querySelectorAll('.gorev-karti').forEach(menuyuBagla);

    // ------------------------------------------------------------ silme = arşivleme (M03, S18)
    function gorevSil(kart, formdan) {
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
                if (formdan) formuKapat(true);
                const liste = kart.parentElement;
                kart.classList.add('gizleniyor', 'siliniyor');
                gorevlerSayaclari();
                window.setTimeout(() => { if (kart.classList.contains('siliniyor')) kart.remove(); }, K.azHareket ? 0 : 300);
                K.bildirim(m('gorevler.silindi_bildirim'), {
                    tur: 'basari',
                    eylem: { etiket: m('ortak.geri_al'), fn: () => silmeyiGeriAl(v, kart, liste) },
                });
                K.duyuru(m('gorevler.silindi_bildirim'));
                return { kapat: true };
            },
        });
    }

    async function silmeyiGeriAl(v, kart, liste) {
        let y;
        try {
            y = await K.istek('ajax_islem.php', { gorev_geri_getir: 1, ka_id: v.id, kart_tipi: KART_TIPI });
        } catch (h) {
            K.istekHatasiGoster(h, m('yerel.silme_geri_alinamadi'), () => silmeyiGeriAl(v, kart, liste));
            return;
        }
        if (!y.success) {
            const metin = y.kod === 'sinir_dolu' ? m('gorevler.sinir_dolu', { sinir: SINIR })
                : y.kod === 'ad_ayni' ? m('gorev_form.hata_ad_ayni')
                : m('yerel.silme_geri_alinamadi');
            K.bildirim(metin, { tur: 'hata' });
            return;
        }
        // Görev aynı yerine (oluşturulma sırasına), serisi ve bugünkü durumuyla döner.
        kart.classList.remove('gizleniyor', 'siliniyor');
        kartiYenile(kart, y.kart_html);
        const hedef = document.getElementById(kart.dataset.tamam === '1' ? 'liste-tamamlananlar' : 'liste-yapilacaklar') || liste;
        if (hedef) siraliEkle(hedef, kart, false);
        gorevlerSayaclari();
        K.bildirim(m('gorevler.silme_geri_alindi'), { tur: 'basari' });
        K.duyuru(m('gorevler.silme_geri_alindi'));
    }

    // ------------------------------------------------------------ meydan okuma ödülü (K08)
    document.addEventListener('click', async (e) => {
        const d = e.target.closest('[data-odul-al]');
        if (!d) return;
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

    // Yenilenmeye kalan süre (dakikada bir)
    const yenilenme = document.getElementById('meydan-yenilenme');
    if (yenilenme && V.gunSonuSn) {
        const bitis = Date.now() + V.gunSonuSn * 1000;
        window.setInterval(() => {
            const kalan = Math.max(0, Math.floor((bitis - Date.now()) / 1000));
            yenilenme.textContent = m('gorevler.meydan_yenilenme', { saat: Math.floor(kalan / 3600), dakika: Math.floor((kalan % 3600) / 60) });
        }, 60000);
    }

    // ------------------------------------------------------------ görev formu (E21, 03 §5)
    const formArka = document.getElementById('ekle-arka');
    const form = document.getElementById('ekle-form');
    const fd = {}; // form durumu
    if (form) {
        const ad = document.getElementById('habit_name');
        const sayac = document.getElementById('ad-sayac');
        const sayacUyari = document.getElementById('ad-sayac-uyari');
        const adHata = document.getElementById('ad-hata');
        const yHata = document.getElementById('yetenek-hata');
        const yUyari = document.getElementById('yetenek-uyari');
        const onizleme = document.getElementById('odul-onizleme');
        const sunucuHata = document.getElementById('form-hata');
        const kaydet = document.getElementById('form-kaydet');
        const silDugme = document.getElementById('form-sil');
        const baslik = document.getElementById('ekle-baslik');
        const oneri = document.getElementById('oneri-alani');
        const radyolar = Array.from(form.querySelectorAll('input[name="habit_stat"]'));
        const sunucuYetenegi = (radyolar.find((r) => r.checked) || {}).value || '';

        const uzunluk = () => Array.from(ad.value.trim()).length;
        const seciliYetenek = () => (radyolar.find((r) => r.checked) || {}).value || '';
        const yetenekAdi = (kod) => {
            const r = radyolar.find((x) => x.value === kod);
            return r ? form.querySelector('label[for="' + r.id + '"] .yetenek-etiket').textContent : '';
        };
        const anlik = () => ({ ad: ad.value.trim(), yetenek: seciliYetenek() });
        const degistiMi = () => {
            const s = anlik();
            return s.ad !== fd.ilk.ad || s.yetenek !== fd.ilk.yetenek;
        };

        let sonUyariUzunlugu = 0;
        const sayacGuncelle = () => {
            const n = Array.from(ad.value).length;
            sayac.textContent = m('gorev_form.ad_sayac', { uzunluk: n });
            // Sayaç her tuşta okunmaz; yalnızca 50 karakteri geçince bir kez (03 §11).
            if (n > 50 && sonUyariUzunlugu <= 50) sayacUyari.textContent = m('gorev_form.ad_sayac', { uzunluk: n });
            sonUyariUzunlugu = n;
        };
        const onizlemeGuncelle = () => {
            const secili = seciliYetenek();
            onizleme.hidden = !secili;
            if (secili) onizleme.textContent = m('gorev_form.odul_onizleme', { yetenek: yetenekAdi(secili) });
            // S19: düzenlemede yetenek değişirse geçmiş XP'nin yerinde kaldığı söylenir.
            const uyar = fd.kip === 'duzenle' && secili && secili !== fd.ilk.yetenek;
            yUyari.hidden = !uyar;
            yUyari.textContent = uyar ? m('gorev_form.yetenek_degisti_uyari', { yetenek: yetenekAdi(secili) }) : '';
        };
        const kaydetDurumu = () => {
            // Düzenlemede değişiklik yokken "Kaydet" devre dışı (03 §4.3).
            if (fd.mesgul) return;
            kaydet.disabled = fd.kip === 'duzenle' && !degistiMi();
        };
        const hataGoster = (alan, kutu, metin) => {
            kutu.textContent = metin || '';
            kutu.hidden = !metin;
            if (alan) alan.setAttribute('aria-invalid', metin ? 'true' : 'false');
        };
        const sunucuHatasi = (metin) => { sunucuHata.textContent = metin || ''; sunucuHata.hidden = !metin; };
        const adDogrula = () => {
            if (!ad.value.trim()) return m('gorev_form.hata_ad_bos');
            if (uzunluk() > AD_EN_UZUN) return m('gorev_form.hata_ad_uzun');
            return '';
        };
        const salt = (durum) => {
            fd.mesgul = durum;
            ad.readOnly = durum;
            radyolar.forEach((r) => { r.disabled = durum; });
            silDugme.disabled = durum;
            form.querySelectorAll('[data-oneri-ad]').forEach((c) => { c.disabled = durum; });
            K.dugmeYukleniyor(kaydet, durum);
            if (!durum) kaydetDurumu();
        };

        formAc = function (kip, kart, onYetenek) {
            if (kip === 'ekle' && aktifGorevSayisi() >= SINIR) {
                K.bildirim(m('gorevler.sinir_dolu', { sinir: SINIR }), { tur: 'hata' });
                return;
            }
            fd.kip = kip;
            fd.kart = kart || null;
            // Kapanınca odak tetikleyene döner (düzenlemede menü kapandığı için menü düğmesine).
            fd.odakDon = kart ? kart.querySelector('.menu-dugme') : document.activeElement;
            sunucuHatasi('');
            hataGoster(ad, adHata, '');
            hataGoster(null, yHata, '');
            if (kip === 'duzenle') {
                const v = kartVerisi(kart);
                baslik.textContent = m('gorev_form.duzenle_baslik');
                kaydet.textContent = m('gorev_form.kaydet_duzenle');
                oneri.hidden = true;
                silDugme.hidden = false;
                ad.value = v.ad;
                radyolar.forEach((r) => { r.checked = r.value === v.yetenekKod; });
            } else {
                baslik.textContent = m('gorev_form.ekle_baslik');
                kaydet.textContent = m('gorev_form.kaydet_ekle');
                oneri.hidden = false;
                silDugme.hidden = true;
                ad.value = '';
                // Yetenek önceden seçili değil (bilinçli seçim); E50'den gelindiyse o yetenek.
                radyolar.forEach((r) => { r.checked = !!onYetenek && r.value === onYetenek; });
            }
            fd.ilk = anlik();
            sonUyariUzunlugu = Array.from(ad.value).length;
            sayacGuncelle(); onizlemeGuncelle(); kaydetDurumu();
            formArka.hidden = false;
            document.body.classList.add('modal-acik');
            // Tarayıcı geri tuşu formu kapatsın (03 §5.2).
            history.pushState({ gorevFormu: true }, '');
            fd.gecmiste = true;
            ad.focus();
        };

        /** Formu kapatır. zorla: değişiklik olsa da M07 sormadan. */
        formuKapat = function (zorla) {
            if (formArka.hidden) return;
            if (!zorla && fd.mesgul) return;
            if (!zorla && degistiMi()) {
                // M07: birincil "Düzenlemeye dön", ikincil "Kaydetmeden çık".
                K.onayIste({
                    baslik: m('ortak.kaydedilmedi_baslik'),
                    aciklama: m('ortak.kaydedilmedi_aciklama'),
                    birincil: m('ortak.kaydedilmedi_kal'),
                    ikincil: m('ortak.kaydedilmedi_cik'),
                    ikincilFn: () => formuKapat(true),
                });
                return;
            }
            formArka.hidden = true;
            if (!document.querySelector('.alt-sayfa-arka:not([hidden])')) document.body.classList.remove('modal-acik');
            if (fd.gecmiste) {
                fd.gecmiste = false;
                fd.gecmisGeriAliniyor = true;
                history.back();
            }
            if (fd.odakDon && document.contains(fd.odakDon)) fd.odakDon.focus();
        };

        window.addEventListener('popstate', () => {
            if (fd.gecmisGeriAliniyor) { fd.gecmisGeriAliniyor = false; return; }
            if (formArka.hidden || !fd.gecmiste) return;
            fd.gecmiste = false;
            if (degistiMi() || fd.mesgul) {
                // Geri tuşu: değişiklik varsa form açık kalır, M07 sorulur.
                history.pushState({ gorevFormu: true }, '');
                fd.gecmiste = true;
                formuKapat(false);
                return;
            }
            formuKapat(true);
        });

        ad.addEventListener('input', () => { sayacGuncelle(); kaydetDurumu(); if (ad.getAttribute('aria-invalid') === 'true') hataGoster(ad, adHata, adDogrula()); });
        ad.addEventListener('blur', () => { if (ad.value !== '' && !formArka.hidden) hataGoster(ad, adHata, adDogrula()); });
        radyolar.forEach((r) => r.addEventListener('change', () => { onizlemeGuncelle(); kaydetDurumu(); hataGoster(null, yHata, ''); }));
        form.querySelectorAll('[data-oneri-ad]').forEach((c) => c.addEventListener('click', () => {
            ad.value = c.dataset.oneriAd;
            const r = radyolar.find((x) => x.value === c.dataset.oneriYetenek);
            if (r) r.checked = true;
            sayacGuncelle(); onizlemeGuncelle(); kaydetDurumu();
            hataGoster(ad, adHata, ''); hataGoster(null, yHata, '');
        }));

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (fd.mesgul) return;
            const adSorun = adDogrula();
            const yetenekSorun = seciliYetenek() ? '' : m('gorev_form.hata_yetenek_yok');
            hataGoster(ad, adHata, adSorun);
            hataGoster(null, yHata, yetenekSorun);
            sunucuHatasi('');
            if (adSorun || yetenekSorun) {
                (adSorun ? ad : radyolar[0]).focus();
                return;
            }
            const veri = { habit_name: ad.value.trim(), habit_stat: seciliYetenek(), kart_tipi: KART_TIPI };
            if (fd.kip === 'duzenle') { veri.gorev_duzenle = 1; veri.ka_id = kartVerisi(fd.kart).id; }
            else veri.gorev_ekle = 1;

            salt(true);
            let y;
            try {
                y = await K.istek('ajax_islem.php', veri);
            } catch (h) {
                salt(false);
                // İstek ulaşmadı: form açık, girilenler korunur (03 §4.2).
                K.istekHatasiGoster(h, m('gorev_form.hata_kaydedilemedi'), () => form.requestSubmit());
                return;
            }
            salt(false);
            if (!y.success) {
                if (y.kod === 'ad_bos' || y.kod === 'ad_uzun' || y.kod === 'ad_ayni') {
                    hataGoster(ad, adHata, y.kod === 'ad_ayni' ? m('gorev_form.hata_ad_ayni')
                        : m(y.kod === 'ad_bos' ? 'gorev_form.hata_ad_bos' : 'gorev_form.hata_ad_uzun'));
                    ad.focus();
                } else if (y.kod === 'yetenek_yok') {
                    hataGoster(null, yHata, m('gorev_form.hata_yetenek_yok'));
                    radyolar[0].focus();
                } else if (y.kod === 'sinir_dolu') {
                    sunucuHatasi(m('gorevler.sinir_dolu', { sinir: SINIR }));
                } else {
                    sunucuHatasi(m('gorev_form.hata_kaydedilemedi'));
                }
                return;
            }
            if (fd.kip === 'duzenle') {
                kartiYenile(fd.kart, y.kart_html);
                formuKapat(true);
                K.bildirim(m('gorevler.guncellendi_bildirim'), { tur: 'basari' });
                K.duyuru(m('gorevler.guncellendi_bildirim'));
                return;
            }
            const kart = htmlden(y.kart_html);
            const liste = document.getElementById('liste-yapilacaklar');
            if (kart && liste) {
                siraliEkle(liste, kart, false);
                menuyuBagla(kart);
                if (!K.azHareket) kart.classList.add('yeni-kart');
                window.setTimeout(() => kart.classList.remove('yeni-kart'), 1000);
            }
            gorevlerSayaclari();
            formuKapat(true);
            K.bildirim(m('gorevler.eklendi_bildirim'), { tur: 'basari' });
            K.duyuru(m('gorevler.eklendi_bildirim'));
        });

        silDugme.addEventListener('click', () => { if (fd.kart) gorevSil(fd.kart, true); });
        form.querySelector('[data-ekle-kapat]').addEventListener('click', () => formuKapat(false));
        formArka.addEventListener('click', (e) => { if (e.target === formArka) formuKapat(false); });
        formArka.addEventListener('keydown', (e) => {
            // stopPropagation: belgedeki G07 Esc işleyicisi yeni açılan M07'yi hemen kapatmasın.
            if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); formuKapat(false); return; }
            if (e.key === 'Tab') {
                const odaklar = Array.from(form.querySelectorAll('button:not([disabled]), input:not([type="hidden"]):not([disabled])'))
                    .filter((x) => x.offsetParent !== null)
                    .filter((x) => x.type !== 'radio' || x.checked || !radyolar.some((r) => r.checked));
                const ilk = odaklar[0], son = odaklar[odaklar.length - 1];
                if (e.shiftKey && document.activeElement === ilk) { e.preventDefault(); son.focus(); }
                else if (!e.shiftKey && document.activeElement === son) { e.preventDefault(); ilk.focus(); }
            }
        });

        document.querySelectorAll('[data-ekle-ac]').forEach((d) => d.addEventListener('click', () => {
            if (d.dataset.kilitli === '1' || aktifGorevSayisi() >= SINIR) {
                K.bildirim(m('gorevler.sinir_dolu', { sinir: SINIR }), { tur: 'hata' });
                return;
            }
            formAc('ekle', null, '');
        }));
        // Karakter (E50) / Karargah'tan "Görev ekle" ile gelindiyse (…#ekle, ?yetenek=…).
        if (location.hash === '#ekle') {
            history.replaceState(null, '', location.pathname + location.search);
            formAc('ekle', null, sunucuYetenegi);
        }
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
