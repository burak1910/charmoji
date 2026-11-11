// tests/guvenlik/duman_tarayici.js
// Kullanım: node duman_tarayici.js BASE EPOSTA
// Gerçek tarayıcıda arayüz üzerinden: kayıt formu -> alışkanlık ekleme formu ->
// onay kutusu (AJAX, X-CSRF-Token) -> market satın alma (AJAX) -> avatar kaydet (AJAX)
// -> çıkış düğmesi (POST formu). Dış kaynaklar (CDN) engellenir.
// Çıktı: her adım için {adim, ok, ayrinti} içeren JSON.
const { chromium } = require('playwright');

(async () => {
    const [base, eposta] = process.argv.slice(2);
    const tarayici = await chromium.launch();
    const sayfa = await tarayici.newPage();
    sayfa.on('dialog', (d) => d.accept().catch(() => {}));
    await sayfa.route('**/*', (r) => (r.request().url().startsWith(base) ? r.continue() : r.abort()));
    const adimlar = [];
    const adim = (ad, ok, ayrinti) => adimlar.push({ adim: ad, ok: !!ok, ayrinti });
    const ajaxBekle = () => sayfa.waitForResponse((r) => r.url().includes('ajax_islem.php'));

    try {
        // Kayıt
        await sayfa.goto(base + 'view/register.php', { waitUntil: 'domcontentloaded' });
        await sayfa.fill('#fullname', 'tarayici_kullanici');
        await sayfa.fill('#email', eposta);
        await sayfa.fill('#password', 'Sifre123!');
        await sayfa.fill('#confirm_password', 'Sifre123!');
        await Promise.all([sayfa.waitForNavigation({ waitUntil: 'domcontentloaded' }), sayfa.click('form button[type=submit]')]);
        adim('kayıt formu', sayfa.url() === base + 'view/userpage.php', sayfa.url());

        // Alışkanlık ekleme (modal form)
        await sayfa.evaluate(() => document.getElementById('habitModal').showModal());
        await sayfa.fill('input[name=habit_name]', 'tarayici_aliskanlik');
        await Promise.all([sayfa.waitForNavigation({ waitUntil: 'domcontentloaded' }), sayfa.click('button[name=add_habit_btn]')]);
        const kutu = sayfa.locator('input.habit-check').first();
        adim('alışkanlık ekleme formu', (await kutu.count()) === 1, sayfa.url());

        // İşaretleme (AJAX)
        const [y1] = await Promise.all([ajaxBekle(), kutu.click()]);
        const j1 = await y1.json();
        adim('onay kutusu ile işaretleme (AJAX)', y1.status() === 200 && j1.success === true, JSON.stringify(j1));

        // Market (AJAX)
        await sayfa.goto(base + 'view/market.php', { waitUntil: 'domcontentloaded' });
        const [y2] = await Promise.all([ajaxBekle(), sayfa.locator('button:has-text("SATIN AL")').first().click()]);
        const j2 = await y2.json();
        adim('market satın alma (AJAX)', y2.status() === 200 && j2.success === true, JSON.stringify(j2));

        // Avatar kaydet (AJAX)
        await sayfa.goto(base + 'view/avatar.php', { waitUntil: 'domcontentloaded' });
        await sayfa.evaluate(() => { document.getElementById('skinColor').value = '#123abc'; });
        const [y3] = await Promise.all([ajaxBekle(), sayfa.click('button:has-text("KAYDET")')]);
        const j3 = await y3.json();
        adim('avatar renk kaydı (AJAX)', y3.status() === 200 && j3.success === true, JSON.stringify(j3));

        // Eşya kuşanma (AJAX)
        await sayfa.click('#btn-items');
        const [y4] = await Promise.all([ajaxBekle(), sayfa.locator('.item-card').first().click()]);
        const j4 = await y4.json();
        adim('envanterden eşya kuşanma (AJAX)', y4.status() === 200 && j4.success === true, JSON.stringify(j4));

        // Çıkış düğmesi
        await sayfa.goto(base + 'view/userpage.php', { waitUntil: 'domcontentloaded' });
        await Promise.all([sayfa.waitForNavigation({ waitUntil: 'domcontentloaded' }), sayfa.click('button[title="Çıkış Yap"]')]);
        adim('çıkış düğmesi (POST)', sayfa.url() === base + 'view/index.php', sayfa.url());
        await sayfa.goto(base + 'view/userpage.php', { waitUntil: 'domcontentloaded' });
        adim('çıkıştan sonra oturum kapalı', sayfa.url() === base + 'view/login.php', sayfa.url());
    } catch (e) {
        adim('beklenmeyen hata', false, e.message.split('\n')[0]);
    }
    console.log(JSON.stringify(adimlar));
    await tarayici.close();
})();
