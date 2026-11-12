// tests/guvenlik/xss_tarayici.js
// Kullanım: node xss_tarayici.js BASE LOGIN_YOLU EPOSTA SIFRE
// Giriş formundan giriş yapar, oturum açık sayfaları (eski kodda userpage.php ve avatar.php;
// yeni kodda beş sekme + Görünüm/Çanta) açar ve
// enjekte edilmiş bir scriptin çalışıp çalışmadığını (window.__xss) ve açılan
// diyalogları raporlar. Dış kaynaklara (CDN) giden istekler engellenir.
const { chromium } = require('playwright');

(async () => {
    const [base, loginYolu, eposta, sifre] = process.argv.slice(2);
    const tarayici = await chromium.launch();
    const sayfa = await tarayici.newPage();
    const diyaloglar = [];
    sayfa.on('dialog', (d) => { diyaloglar.push(d.message()); d.dismiss().catch(() => {}); });
    await sayfa.route('**/*', (r) => (r.request().url().startsWith(base) ? r.continue() : r.abort()));

    await sayfa.goto(base + loginYolu, { waitUntil: 'domcontentloaded' });
    await sayfa.fill('#username', eposta);
    await sayfa.fill('#password', sifre);
    await Promise.all([
        sayfa.waitForNavigation({ waitUntil: 'domcontentloaded' }),
        sayfa.click('form button[type=submit]'),
    ]);

    const sonuc = {};
    const tetiklenen = [];
    const yollar = sayfa.url().includes('view/karargah.php')
        ? ['view/karargah.php', 'view/gorevler.php', 'view/market.php', 'view/lonca.php', 'view/karakter.php', 'view/avatar.php']
        : ['view/userpage.php', 'view/avatar.php'];
    for (const yol of yollar) {
        await sayfa.goto(base + yol, { waitUntil: 'domcontentloaded' });
        await sayfa.waitForTimeout(500);
        const xss = await sayfa.evaluate(() => (typeof window.__xss === 'undefined' ? null : window.__xss));
        sonuc[yol] = { url: sayfa.url(), xss };
        if (xss !== null) tetiklenen.push(yol);
    }
    if (diyaloglar.length) tetiklenen.push('diyalog');
    console.log(JSON.stringify({ tetiklenen, sonuc, diyaloglar }));
    await tarayici.close();
})().catch((e) => { console.log('HATA ' + e.message); process.exit(1); });
