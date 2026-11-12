// tests/guvenlik/duman_tarayici.js
// Kullanım: node duman_tarayici.js BASE EPOSTA
// Gerçek tarayıcıda (390 px mobil genişlik) arayüz üzerinden: kayıt formu -> Görevler'de
// görev ekleme formu (E21) -> görev kartı onay kutusu (AJAX, X-CSRF-Token) -> Market'te
// satın alma onayı (G07/M05, AJAX) -> Görünüm seçeneği kaydet (AJAX) -> Çanta'dan kuşanma (AJAX)
// -> Lonca'da arama -> Karakter'de çıkış onayı (M08, POST formu). Dış kaynaklar (CDN)
// engellenir. Tarayıcının confirm()/alert() pencereleri hiç açılmamalıdır; beş sekmede
// sayfa betiği hatası (pageerror) olmamalıdır.
// Çıktı: her adım için {adim, ok, ayrinti} içeren JSON.
const { chromium } = require('playwright');

(async () => {
    const [base, eposta] = process.argv.slice(2);
    const tarayici = await chromium.launch();
    const sayfa = await tarayici.newPage({ viewport: { width: 390, height: 844 } });
    const diyaloglar = [];
    const betikHatalari = [];
    sayfa.on('dialog', (d) => { diyaloglar.push(d.type() + ': ' + d.message()); d.dismiss().catch(() => {}); });
    // Yalnızca oturum açık sekmeler denetlenir (giriş/kayıt sayfaları CDN'e bağlı, CDN engelli).
    const SEKME = /view\/(karargah|gorevler|market|lonca|karakter|avatar)\.php/;
    sayfa.on('pageerror', (e) => { if (SEKME.test(sayfa.url())) betikHatalari.push(sayfa.url() + ' :: ' + e.message); });
    await sayfa.route('**/*', (r) => (r.request().url().startsWith(base) ? r.continue() : r.abort()));
    const adimlar = [];
    const adim = (ad, ok, ayrinti) => adimlar.push({ adim: ad, ok: !!ok, ayrinti });
    const yanitBekle = (parca) => sayfa.waitForResponse((r) => r.url().includes(parca));

    try {
        // Kayıt -> Karargah
        await sayfa.goto(base + 'view/register.php', { waitUntil: 'domcontentloaded' });
        await sayfa.fill('#fullname', 'tarayici_kullanici');
        await sayfa.fill('#email', eposta);
        await sayfa.fill('#password', 'Sifre123!');
        await sayfa.fill('#confirm_password', 'Sifre123!');
        await Promise.all([sayfa.waitForNavigation({ waitUntil: 'domcontentloaded' }), sayfa.click('form button[type=submit]')]);
        adim('kayıt formu -> Karargah', sayfa.url() === base + 'view/karargah.php', sayfa.url());

        // Beş sekme alt menüden açılıyor (G01: etkin sekme aria-current="page")
        for (const [sekme, dosya] of [['Görevler', 'gorevler'], ['Market', 'market'], ['Lonca', 'lonca'], ['Karakter', 'karakter'], ['Karargah', 'karargah']]) {
            await Promise.all([sayfa.waitForNavigation({ waitUntil: 'domcontentloaded' }), sayfa.click('nav.alt-menu a[href^="' + dosya + '.php"]')]);
            const etkin = await sayfa.getAttribute('nav.alt-menu [aria-current="page"]', 'href');
            adim('alt menü: ' + sekme, sayfa.url().startsWith(base + 'view/' + dosya + '.php') && etkin.startsWith(dosya + '.php'), sayfa.url());
        }

        // Görev ekleme formu (E21)
        await sayfa.goto(base + 'view/gorevler.php', { waitUntil: 'domcontentloaded' });
        await sayfa.click('[data-ekle-ac]');
        await sayfa.fill('#habit_name', 'tarayici_gorev');
        await sayfa.click('label[for="yetenek-zeka"]');
        await Promise.all([sayfa.waitForNavigation({ waitUntil: 'domcontentloaded' }), sayfa.click('#ekle-form button[type=submit]')]);
        const kutu = sayfa.locator('.gorev-karti .gorev-onay').first();
        adim('görev ekleme formu', (await kutu.count()) === 1, sayfa.url());

        // Görev tamamlama (AJAX)
        const [y1] = await Promise.all([yanitBekle('ajax_islem.php'), kutu.click()]);
        const j1 = await y1.json();
        adim('görev kartı ile tamamlama (AJAX)', y1.status() === 200 && j1.success === true && j1.degisti === true, JSON.stringify(j1));
        await sayfa.waitForSelector('#bildirim-alani .bildirim');
        adim('tamamlama bildirimi (G03)', (await sayfa.textContent('#bildirim-alani')).includes('Tamamlandı'), await sayfa.textContent('#bildirim-alani'));

        // Market: karta dokun -> onay alt sayfası (G07/M05) -> satın al (AJAX)
        await sayfa.goto(base + 'view/market.php', { waitUntil: 'domcontentloaded' });
        await sayfa.evaluate(() => { try { sessionStorage.clear(); } catch (e) { /* yok */ } });
        await sayfa.click('.esya-karti[data-fiyat="0"], .esya-karti[data-sahip="0"][data-seviye="1"]');
        await sayfa.waitForSelector('#onay-arka:not([hidden])');
        const odak = await sayfa.evaluate(() => document.activeElement && document.activeElement.id);
        adim('satın alma onayı açıldı, odak Vazgeç düğmesinde', odak === 'onay-ikincil', odak);
        const [y2] = await Promise.all([yanitBekle('ajax_islem.php'), sayfa.click('#onay-birincil')]);
        const j2 = await y2.json();
        adim('market satın alma (AJAX)', y2.status() === 200 && j2.success === true, JSON.stringify(j2));

        // Görünüm kaydet (AJAX)
        await sayfa.goto(base + 'view/avatar.php#gorunum', { waitUntil: 'domcontentloaded' });
        await sayfa.click('.secenek-kutu[data-bolge="sac"][data-n="4"]');
        const onizlemeSac = await sayfa.getAttribute('#onizleme .pk-sac', 'src');
        adim('görünüm seçeneği önizlemede (saç 4)', /karakter\/tam\/sac-4-480\.png$/.test(onizlemeSac || ''), onizlemeSac);
        const [y3] = await Promise.all([yanitBekle('ajax_islem.php'), sayfa.click('#gorunum-kaydet')]);
        const j3 = await y3.json();
        adim('görünüm seçeneği kaydı (AJAX)', y3.status() === 200 && j3.success === true && j3.secenekler && j3.secenekler.sac === 4, JSON.stringify(j3));

        // Çanta: eşya seç -> Kuşan (AJAX)
        await sayfa.click('#sekme-canta');
        await sayfa.locator('#canta-izgara .esya-karti').first().click();
        const [y4] = await Promise.all([yanitBekle('ajax_islem.php'), sayfa.click('#kusan-dugme')]);
        const j4 = await y4.json();
        adim('çantadan eşya kuşanma (AJAX)', y4.status() === 200 && j4.success === true, JSON.stringify(j4));

        // Lonca: Maceracı bul araması (GET, JSON)
        await sayfa.goto(base + 'view/lonca.php#bul', { waitUntil: 'domcontentloaded' });
        const [y5] = await Promise.all([yanitBekle('lonca_ara.php'), sayfa.fill('#arama-kutusu', 'Deneme')]);
        const j5 = await y5.json();
        adim('Maceracı bul araması', y5.status() === 200 && j5.success === true && !JSON.stringify(j5).includes('@'), JSON.stringify(j5).slice(0, 120));

        // Karakter: Çıkış yap -> onay (M08) -> çıkış
        await sayfa.goto(base + 'view/karakter.php', { waitUntil: 'domcontentloaded' });
        await sayfa.click('#cikis-dugme');
        await sayfa.waitForSelector('#onay-arka:not([hidden])');
        adim('çıkış onayı açıldı (M08), çıkış henüz yapılmadı', sayfa.url() === base + 'view/karakter.php', sayfa.url());
        await Promise.all([sayfa.waitForNavigation({ waitUntil: 'domcontentloaded' }), sayfa.click('#onay-birincil')]);
        adim('çıkış onayı (POST)', sayfa.url() === base + 'view/index.php', sayfa.url());
        await sayfa.goto(base + 'view/karargah.php', { waitUntil: 'domcontentloaded' });
        adim('çıkıştan sonra oturum kapalı', sayfa.url() === base + 'view/login.php', sayfa.url());
    } catch (e) {
        adim('beklenmeyen hata', false, e.message.split('\n')[0]);
    }
    adim('tarayıcı confirm()/alert() penceresi açılmadı', diyaloglar.length === 0, diyaloglar.join(' | ') || 'yok');
    adim('sekmelerde sayfa betiği hatası yok', betikHatalari.length === 0, betikHatalari.join(' | ') || 'yok');
    console.log(JSON.stringify(adimlar));
    await tarayici.close();
})();
