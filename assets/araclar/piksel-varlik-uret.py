#!/usr/bin/env python3
"""assets/deneme/ altındaki büyük (≈1050–1175 px) piksel sanat kaynaklarından uygulamanın
sunduğu küçük, keskin kenarlı PNG'leri üretir (view/statik/gorsel/).

Kullanım (depo kökünden):
    python3 assets/araclar/piksel-varlik-uret.py [--onizleme DIZIN]

Gereksinim: Python 3, Pillow, numpy. Çıktı her çalıştırmada aynıdır (rastgelelik yok).

Yöntem
  1. Sınıflandırma: kaynaktaki her opak piksel, görselin malzeme renklerinden (PROMPTS.md
     paleti; ten/saç/üst giysi/kontur/parıltı ...) en yakınına atanır. Merkezler birkaç
     k-ortalama adımıyla kaynağın gerçek tonlarına oturtulur (üretilmiş görsel palete tam
     uymaz), sonra 3x3 çoğunluk süzgeciyle kenarlardaki karışık pikseller temizlenir.
  2. Katmanlar (karakter): her kontur pikseli en yakın malzemeye bağlanır; böylece ten
     (yüz, kulak, boyun, göz, ağız), saç ve üst giysi ayrı, birbirini tamamlayan katmanlardır.
  3. Renk seçenekleri: bir malzemenin ana + gölge + ışık tonu birlikte yeni üçlüyle
     değiştirilir (SECENEKLER). Kontur ve parıltı hiç değişmez.
  4. Eşyalar (şapka, gözlük) karakter kaynağının koordinatlarına ölçeklenip yerleştirilir
     (YERLESIM); hepsi aynı tuvalde, üst üste konunca hizalı olur.
  5. Küçültme: etiket haritası alan ağırlıklı çoğunlukla küçültülür (kontur hafif
     öncelikli); ara renk oluşmaz, kenarlar sert kalır. PNG'ler paletli (P) kaydedilir.
"""
import argparse
import os
import sys

import numpy as np
from PIL import Image

KOK = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
KAYNAK = os.path.join(KOK, 'assets', 'deneme')
HEDEF = os.path.join(KOK, 'view', 'statik', 'gorsel')


def hex3(h):
    h = h.lstrip('#')
    return tuple(int(h[i:i + 2], 16) for i in (0, 2, 4))


KONTUR = '#0E0B1E'
PARILTI = '#F5F1FF'

# ---------------------------------------------------------------------------
# Renk seçenekleri (docs/ux/06-karakter.md §5, S27). Her biri (ana, gölge, ışık).
# 1 numaralı seçenekler PROMPTS.md'deki varsayılan palettir.
# ---------------------------------------------------------------------------
SECENEKLER = {
    'ten': [
        ('#F3D2C1', '#D9A58F', '#FBE6DA'),  # 1 Açık
        ('#E6B48A', '#C48A5E', '#F3CFA8'),  # 2 Buğday
        ('#C08458', '#9A6038', '#D69E72'),  # 3 Esmer
        ('#8A5636', '#683C22', '#A26C49'),  # 4 Koyu
    ],
    'sac': [
        ('#2D3436', '#1B2022', '#4A5456'),  # 1 Siyah
        ('#6B4226', '#4A2A16', '#8C5E3A'),  # 2 Kahverengi
        ('#E2B54C', '#B7862C', '#F6D77E'),  # 3 Sarı
        ('#C0502A', '#8E3418', '#E07848'),  # 4 Kızıl
        ('#3D5FC4', '#283F8E', '#6F8FE6'),  # 5 Mavi
        ('#E4E6EF', '#B5B9CC', '#FFFFFF'),  # 6 Kar beyazı
    ],
    'ust': [
        ('#6C5CE7', '#4A3DB8', '#9A8DF5'),  # 1 Mor
        ('#3B82D6', '#2860A8', '#74AAEC'),  # 2 Mavi
        ('#3E9E62', '#2A7346', '#6CC48C'),  # 3 Yeşil
        ('#D9454B', '#A52F35', '#EE7A7E'),  # 4 Kırmızı
    ],
}

# ---------------------------------------------------------------------------
# Kaynaklar ve malzeme merkezleri
# ---------------------------------------------------------------------------
KARAKTER = {
    'dosya': 'deneme-01-temel-karakter-v2.png',
    'merkezler': {
        'kontur': KONTUR,
        'ten': '#F3D2C1', 'ten_g': '#D9A58F', 'ten_i': '#FBE6DA',
        'sac': '#2D3436', 'sac_g': '#1B2022', 'sac_i': '#4A5456',
        'ust': '#6C5CE7', 'ust_g': '#4A3DB8', 'ust_i': '#9A8DF5',
        'parilti': PARILTI,
    },
}
SAPKA = {
    'dosya': 'deneme-02-sapka-buyucu-sapkasi.png',
    'merkezler': {
        'kontur': KONTUR,
        'mor': '#6D28D9', 'mor_g': '#4C1D95', 'mor_i': '#9F67F0',
        'altin': '#F2C14E', 'altin_g': '#B8832B', 'altin_i': '#FFE08A',
    },
}
GOZLUK = {
    'dosya': 'deneme-03-gozluk-gunes-gozlugu.png',
    'merkezler': {'kontur': KONTUR, 'koyu': '#2B2F4A', 'koyu_g': '#181B2E', 'parilti': PARILTI},
}
SANDIK = {
    'dosya': 'deneme-04-gunluk-sandik-kapali.png',
    'merkezler': {
        'kontur': KONTUR,
        'ahsap': '#8A5A33', 'ahsap_g': '#5C3A20', 'ahsap_i': '#B07A47',
        'altin': '#F2C14E', 'altin_g': '#B8832B', 'altin_i': '#FFE08A',
        'kirmizi': '#E5484D', 'parilti': PARILTI,
    },
}
PARA = {
    'dosya': 'deneme-05-altin-para.png',
    'merkezler': {
        'kontur': KONTUR, 'altin': '#F2C14E', 'altin_g': '#B8832B', 'altin_i': '#FFE08A', 'parilti': PARILTI,
    },
}

# Eşyaların karakter kaynağı koordinatlarındaki yerleşimi: ölçek ve kaynaktaki bir
# bağlantı noktasının karakterdeki karşılığı. Değerler görüntülere bakılarak ayarlandı
# (--onizleme ile üretilen birleşik görsellerle denetlenir).
YERLESIM = {
    # şapka: siperin alt kenarının ortası -> (x, y); saç kâkülü siperin altında görünür kalır
    'sapka': {'olcek': 0.80, 'hedef': (531, 330)},
    # gözlük: iki camın merkezlerinin ortası -> iki gözün ortası (gözler görüntüden bulunur)
    'gozluk': {'olcek': 0.50},
}

# Tuvaller (karakter kaynağı koordinatlarında kare bölge) ve çıktı boyları.
# tam  : şapka için üstte boşluk bırakır (Karargah, Karakter, Görünüm, Çanta)
# yakin: yalnızca büst (liste satırları, seçenek kutuları); eşya çizilmez
SAC_EN_ALT = 700   # karakter kaynağında saçın inebileceği en alt satır (kulak altı)
UST_EN_UST = 740   # üst giysinin başlayabileceği en üst satır (boyun)
ADA_ESIGI = 120  # kaynak pikseli; bir mantıksal piksel ≈ 150–250 kaynak pikselidir

CIKTI = {
    'tam': [480, 240],
    'yakin': [128, 64],
}


# ---------------------------------------------------------------------------
# Yardımcılar
# ---------------------------------------------------------------------------
def yukle(ad):
    a = np.asarray(Image.open(os.path.join(KAYNAK, ad)).convert('RGBA')).astype(np.int32)
    return a[:, :, :3], a[:, :, 3] > 127


def siniflandir(rgb, opak, merkezler, adim=6):
    """Etiket haritası: 0 = saydam, i+1 = merkezler listesindeki i. malzeme."""
    adlar = list(merkezler)
    c = np.array([hex3(merkezler[a]) for a in adlar], dtype=np.float64)
    px = rgb[opak].astype(np.float64)
    for _ in range(adim):
        d = ((px[:, None, :] - c[None, :, :]) ** 2).sum(2)
        i = d.argmin(1)
        for k in range(len(c)):
            sec = i == k
            # Kontur ve parıltı sabit tutulur (kaynağı kirli olabilir, palet değeri kesin).
            if sec.sum() > 50 and adlar[k] not in ('kontur', 'parilti'):
                c[k] = px[sec].mean(0)
    d = ((px[:, None, :] - c[None, :, :]) ** 2).sum(2)
    etiket = np.zeros(opak.shape, dtype=np.int32)
    etiket[opak] = d.argmin(1) + 1
    return cogunluk(etiket, len(adlar) + 1), adlar


def kaydir(a, dy, dx, dolgu=0):
    h, w = a.shape
    out = np.full_like(a, dolgu)
    ys = slice(max(dy, 0), h + min(dy, 0))
    yd = slice(max(-dy, 0), h + min(-dy, 0))
    xs = slice(max(dx, 0), w + min(dx, 0))
    xd = slice(max(-dx, 0), w + min(-dx, 0))
    out[ys, xs] = a[yd, xd]
    return out


def cogunluk(etiket, n, tekrar=2):
    """3x3 çoğunluk süzgeci (kenarlardaki karışık pikselleri komşularına katar)."""
    for _ in range(tekrar):
        sayac = np.zeros((n,) + etiket.shape, dtype=np.int16)
        for dy in (-1, 0, 1):
            for dx in (-1, 0, 1):
                k = kaydir(etiket, dy, dx)
                for i in range(n):
                    sayac[i] += (k == i)
        yeni = sayac.argmax(0).astype(np.int32)
        # Yalnızca açık çoğunluk (>=5/9) varsa değiştir; ince çizgiler korunur.
        en = sayac.max(0)
        etiket = np.where(en >= 5, yeni, etiket)
    return etiket


def bilesenler(maske):
    """4-komşulu bağlı bileşenler (satır parçaları + birleşim-bul; scipy gerektirmez).
    Dönen: (etiket haritası, bileşen boyutları dizisi); 0 = arka plan."""
    h, w = maske.shape
    ebeveyn = [0]

    def bul(i):
        while ebeveyn[i] != i:
            ebeveyn[i] = ebeveyn[ebeveyn[i]]
            i = ebeveyn[i]
        return i

    parcalar = []  # (y, x0, x1, kimlik)
    onceki = []
    for y in range(h):
        satir = maske[y]
        d = np.diff(np.concatenate(([0], satir.view(np.int8), [0])))
        basla = np.nonzero(d == 1)[0]
        bit = np.nonzero(d == -1)[0]
        simdiki = []
        j = 0
        for x0, x1 in zip(basla.tolist(), bit.tolist()):
            kimlik = len(ebeveyn)
            ebeveyn.append(kimlik)
            while j < len(onceki) and onceki[j][1] <= x0:
                j += 1
            k = j
            while k < len(onceki) and onceki[k][0] < x1:
                a, b = bul(onceki[k][2]), bul(kimlik)
                if a != b:
                    ebeveyn[max(a, b)] = min(a, b)
                k += 1
            simdiki.append((x0, x1, kimlik))
            parcalar.append((y, x0, x1, kimlik))
        onceki = simdiki
    harita = np.zeros((h, w), dtype=np.int32)
    for y, x0, x1, kimlik in parcalar:
        harita[y, x0:x1] = bul(kimlik)
    boyut = np.bincount(harita.ravel())
    boyut[0] = 0
    return harita, boyut


def kucuk_adalari_temizle(etiket, grup_kimlikleri, en_az):
    """Bir malzeme grubunun (ör. saç tonları) ana kütleden kopuk küçük parçalarını
    çevresindeki en sık etikete katar. Kaynaktaki karışık kenar pikselleri, saç
    rengi değişince kulakta/yüzde kirli nokta olarak görünmesin diye."""
    maske = np.isin(etiket, grup_kimlikleri)
    harita, boyut = bilesenler(maske)
    kucuk = np.nonzero((boyut > 0) & (boyut < en_az))[0]
    if len(kucuk) == 0:
        return etiket, 0
    return yeniden_ata(etiket, np.isin(harita, kucuk), grup_kimlikleri), len(kucuk)


def yeniden_ata(etiket, secili, haric):
    """secili pikselleri, komşularındaki (haric listesinde ve saydam olmayan) en sık
    etiketle doldurur; birkaç turda kenardan içe doğru ilerler."""
    etiket = etiket.copy()
    secili = secili.copy()
    for _ in range(40):
        if not secili.any():
            break
        n = int(etiket.max()) + 1
        sayac = np.zeros((n,) + etiket.shape, dtype=np.int16)
        for dy in (-1, 0, 1):
            for dx in (-1, 0, 1):
                k = kaydir(etiket, dy, dx)
                gecerli = ~kaydir(secili, dy, dx, True)
                for i in range(1, n):
                    if i not in haric:
                        sayac[i] += ((k == i) & gecerli)
        en = sayac.max(0)
        yeni = sayac.argmax(0)
        doldur = secili & (en > 0)
        etiket[doldur] = yeni[doldur]
        secili &= ~doldur
    return etiket


def konturu_bagla(etiket, kontur_id, malzeme_grubu, tekrar=40):
    """Her kontur pikselini en yakın malzeme grubuna bağlar. Dönen: grup haritası (0 = yok)."""
    grup = np.zeros_like(etiket)
    for eid, g in malzeme_grubu.items():
        grup[etiket == eid] = g
    bos = (etiket == kontur_id)
    for _ in range(tekrar):
        if not (bos & (grup == 0)).any():
            break
        for dy, dx in ((0, 1), (0, -1), (1, 0), (-1, 0)):
            k = kaydir(grup, dy, dx)
            al = bos & (grup == 0) & (k > 0)
            grup[al] = k[al]
    return grup


def alan_kucult(etiket, kutu, boy, agirlik):
    """etiket haritasının kutu=(x0,y0,x1,y1) bölgesini boy x boy'a alan ağırlıklı
    çoğunlukla küçültür. Kutu kaynağın dışına taşabilir (saydam sayılır)."""
    x0, y0, x1, y1 = kutu
    h, w = etiket.shape
    bolge = np.zeros((y1 - y0, x1 - x0), dtype=np.int32)
    sx0, sy0, sx1, sy1 = max(x0, 0), max(y0, 0), min(x1, w), min(y1, h)
    bolge[sy0 - y0:sy1 - y0, sx0 - x0:sx1 - x0] = etiket[sy0:sy1, sx0:sx1]
    ids = np.unique(bolge)
    en_iyi = np.full((boy, boy), -1.0)
    sonuc = np.zeros((boy, boy), dtype=np.int32)
    for i in ids:
        kat = Image.fromarray((bolge == i).astype(np.float32), 'F').resize((boy, boy), Image.BOX)
        p = np.asarray(kat) * agirlik.get(int(i), 1.0)
        daha = p > en_iyi
        en_iyi[daha] = p[daha]
        sonuc[daha] = i
    return sonuc


def renklendir(etiket, renkler):
    """etiket -> RGBA; renkler: {etiket_id: '#RRGGBB'}; listede olmayan saydam."""
    out = np.zeros(etiket.shape + (4,), dtype=np.uint8)
    for i, h in renkler.items():
        sec = etiket == i
        out[sec, :3] = hex3(h)
        out[sec, 3] = 255
    return Image.fromarray(out, 'RGBA')


def kaydet(img, yol):
    os.makedirs(os.path.dirname(yol), exist_ok=True)
    # Paletli PNG: renk sayısı az, dosya küçük; saydamlık tek indeksle.
    a = np.asarray(img)
    renkler = sorted({tuple(p) for p in a[a[:, :, 3] > 0][:, :3].tolist()})
    if len(renkler) > 255:
        raise SystemExit('çok renk: ' + yol)
    pal = [(0, 0, 0)] + renkler
    idx = {c: i for i, c in enumerate(pal)}
    p = np.zeros(a.shape[:2], dtype=np.uint8)
    opak = a[:, :, 3] > 0
    if opak.any():
        p[opak] = [idx[tuple(c)] for c in a[opak][:, :3].tolist()]
    pim = Image.fromarray(p, 'P')
    pim.putpalette([v for c in pal for v in c])
    pim.save(yol, optimize=True, transparency=0)


def olcekle_etiket(etiket, olcek):
    h, w = etiket.shape
    yeni = (max(1, round(w * olcek)), max(1, round(h * olcek)))
    # Etiket ölçekleme: alan ağırlıklı çoğunluk (ince kontur korunsun diye ağırlıklı).
    ids = np.unique(etiket)
    en_iyi = np.full((yeni[1], yeni[0]), -1.0)
    sonuc = np.zeros((yeni[1], yeni[0]), dtype=np.int32)
    for i in ids:
        kat = Image.fromarray((etiket == i).astype(np.float32), 'F').resize(yeni, Image.BOX)
        p = np.asarray(kat) * (1.6 if i == 1 else 1.0)  # 1 = kontur
        daha = p > en_iyi
        en_iyi[daha] = p[daha]
        sonuc[daha] = i
    return sonuc


def yapistir(tuval, parca, x, y):
    """parca etiketlerini tuvale (x, y) sol üst köşeyle yapıştırır (0 = saydam atlanır)."""
    H, W = tuval.shape
    h, w = parca.shape
    x0, y0 = max(x, 0), max(y, 0)
    x1, y1 = min(x + w, W), min(y + h, H)
    if x1 <= x0 or y1 <= y0:
        return
    alt = parca[y0 - y:y1 - y, x0 - x:x1 - x]
    hedef = tuval[y0:y1, x0:x1]
    hedef[alt > 0] = alt[alt > 0]


# ---------------------------------------------------------------------------
# Ana iş
# ---------------------------------------------------------------------------
def karakter_analizi():
    rgb, opak = yukle(KARAKTER['dosya'])
    etiket, adlar = siniflandir(rgb, opak, KARAKTER['merkezler'])
    eid = {a: i + 1 for i, a in enumerate(adlar)}
    # Kopuk küçük parçalar (karışık kenar pikselleri) çevreye katılır.
    for on in ('sac', 'ten', 'ust'):
        etiket, n = kucuk_adalari_temizle(etiket, [eid[on], eid[on + '_g'], eid[on + '_i']], ADA_ESIGI)
        print(f'{on}: {n} küçük parça temizlendi')
    # Bölge sınırları: saç kulak altına inmez, üst giysi boyun altından başlar. Bunun
    # dışındaki saç/üst tonları (ör. büstün alt konturundaki karışık satır) komşulara katılır.
    yy = np.arange(etiket.shape[0])[:, None]
    for on, yasak in (('sac', yy > SAC_EN_ALT), ('ust', yy < UST_EN_UST)):
        ids = [eid[on], eid[on + '_g'], eid[on + '_i']]
        hata = np.isin(etiket, ids) & yasak
        etiket = yeniden_ata(etiket, hata, ids)
        print(f'{on}: bölge dışı {int(hata.sum())} piksel düzeltildi')
    # Parıltı yalnızca gözlerde olur; başka yerdeki parıltı rengi ten ışığıdır.
    harita, boyut = bilesenler(etiket == eid['parilti'])
    buyukler = np.argsort(boyut)[::-1][:2]
    yanlis = (etiket == eid['parilti']) & ~np.isin(harita, buyukler)
    etiket[yanlis] = eid['ten_i']
    print('gözler dışındaki parıltı pikseli:', int(yanlis.sum()))
    grup_ad = {'ten': 1, 'sac': 2, 'ust': 3}
    malzeme = {}
    for a, i in eid.items():
        if a.startswith('ten') or a == 'parilti':
            malzeme[i] = 1
        elif a.startswith('sac'):
            malzeme[i] = 2
        elif a.startswith('ust'):
            malzeme[i] = 3
    grup = konturu_bagla(etiket, eid['kontur'], malzeme)
    # Göz: yüzün ortasındaki kontur renkli küçük bölgeler; gözlük yerleşimi için merkezleri.
    h, w = etiket.shape
    return {'etiket': etiket, 'eid': eid, 'grup': grup, 'grup_ad': grup_ad, 'boyut': (w, h), 'opak': opak}


def goz_merkezleri(kar):
    """İki gözün (parıltılı koyu oval) merkezleri: parıltı piksellerinin kümeleri."""
    e = kar['etiket']
    p = np.argwhere(e == kar['eid']['parilti'])
    if len(p) == 0:
        raise SystemExit('göz parıltısı bulunamadı')
    xorta = np.median(p[:, 1])
    sol = p[p[:, 1] < xorta]
    sag = p[p[:, 1] >= xorta]
    # Parıltı gözün sol üstündedir; gözün kendisi kontur renkli bölgedir: parıltının
    # çevresindeki kontur piksellerinin sınır kutusu.
    merk = []
    for grp in (sol, sag):
        cy, cx = grp.mean(0)
        y0, y1, x0, x1 = int(cy) - 60, int(cy) + 90, int(cx) - 50, int(cx) + 80
        alt = e[y0:y1, x0:x1] == kar['eid']['kontur']
        ys, xs = np.nonzero(alt)
        merk.append(((xs.min() + xs.max()) / 2 + x0, (ys.min() + ys.max()) / 2 + y0))
    return merk


def esya_etiketi(tanim):
    rgb, opak = yukle(tanim['dosya'])
    etiket, adlar = siniflandir(rgb, opak, tanim['merkezler'])
    return etiket, adlar


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--onizleme', help='birleşik önizleme görsellerinin yazılacağı dizin')
    args = ap.parse_args()

    kar = karakter_analizi()
    e, eid, grup = kar['etiket'], kar['eid'], kar['grup']
    kw, kh = kar['boyut']
    gozler = goz_merkezleri(kar)
    goz_orta = ((gozler[0][0] + gozler[1][0]) / 2, (gozler[0][1] + gozler[1][1]) / 2)
    goz_arasi = gozler[1][0] - gozler[0][0]
    by, bx = np.nonzero(kar['opak'])
    bust = (bx.min(), by.min(), bx.max() + 1, by.max() + 1)
    print('büst kutusu', bust, 'gözler', [tuple(round(v) for v in g) for g in gozler])

    # Karakter etiketleri tek haritada: her (malzeme grubu, ton) ayrı kimlik.
    # kimlik = grup*16 + ton; ton: 1 kontur, 2 ana, 3 gölge, 4 ışık, 5 parıltı
    TON = {'kontur': 1, 'parilti': 5}
    for a in eid:
        if a.endswith('_g'):
            TON[a] = 3
        elif a.endswith('_i'):
            TON[a] = 4
        elif a not in TON:
            TON[a] = 2
    birlesik = np.zeros_like(e)
    for a, i in eid.items():
        sec = e == i
        birlesik[sec] = grup[sec] * 16 + TON[a]

    # --- şapka
    se, sadlar = esya_etiketi(SAPKA)
    s_ol = olcekle_etiket(se, YERLESIM['sapka']['olcek'])
    ys, xs = np.nonzero(s_ol > 0)
    siper_alt = ys.max()
    s_x = int(round(YERLESIM['sapka']['hedef'][0] - (xs.min() + xs.max()) / 2))
    s_y = int(round(YERLESIM['sapka']['hedef'][1] - siper_alt))
    # --- gözlük
    ge, gadlar = esya_etiketi(GOZLUK)
    g_ol = olcekle_etiket(ge, YERLESIM['gozluk']['olcek'])
    gc = cam_merkezleri(g_ol, gadlar)
    g_x = int(round(goz_orta[0] - (gc[0][0] + gc[1][0]) / 2))
    g_y = int(round(goz_orta[1] - (gc[0][1] + gc[1][1]) / 2))
    print('gözlük cam merkezleri', gc, 'göz arası', goz_arasi, 'cam arası', gc[1][0] - gc[0][0])

    # Tuval: tam (şapka için üstte boşluk), yakin (büst)
    sapka_ust = s_y + np.nonzero(s_ol > 0)[0].min()
    alt = bust[3] + 8
    ust = min(sapka_ust, bust[1]) - 8
    kenar = alt - ust
    cx = (bust[0] + bust[2]) // 2
    tuval_tam = (cx - kenar // 2, ust, cx - kenar // 2 + kenar, alt)
    yk = (bust[3] - bust[1]) + 24
    tuval_yakin = (cx - yk // 2, bust[3] + 12 - yk, cx - yk // 2 + yk, bust[3] + 12)
    print('tuval tam', tuval_tam, 'yakın', tuval_yakin)

    # Eşya etiketlerini karakter koordinatlı tuvallere yerleştir
    sapka_harita = np.zeros((kh + 2000, kw), dtype=np.int32)  # üstte taşma payı: y + 1000
    PAY = 1000
    yapistir(sapka_harita, s_ol, s_x, s_y + PAY)
    gozluk_harita = np.zeros((kh, kw), dtype=np.int32)
    yapistir(gozluk_harita, g_ol, g_x, g_y)

    def kutu_pay(k, pay):
        return (k[0], k[1] + pay, k[2], k[3] + pay)

    sonuc_bilgi = {}
    for kadraj, boylar in CIKTI.items():
        tuval = tuval_tam if kadraj == 'tam' else tuval_yakin
        for boy in boylar:
            agirlik = {gid * 16 + 1: 1.5 for gid in (1, 2, 3)}
            agirlik.update({gid * 16 + 5: 2.0 for gid in (1, 2, 3)})
            lab = alan_kucult(birlesik, tuval, boy, agirlik)
            for bolge, gid in kar['grup_ad'].items():
                for n, (ana, gol, isik) in enumerate(SECENEKLER[bolge], 1):
                    renk = {gid * 16 + 1: KONTUR, gid * 16 + 2: ana, gid * 16 + 3: gol, gid * 16 + 4: isik,
                            gid * 16 + 5: PARILTI}
                    kaydet(renklendir(lab, renk), os.path.join(HEDEF, 'karakter', kadraj, f'{bolge}-{n}-{boy}.png'))
            if kadraj == 'tam':
                sl = alan_kucult(sapka_harita, kutu_pay(tuval, PAY), boy, {1: 1.5})
                kaydet(renklendir(sl, {i + 1: SAPKA['merkezler'][a] for i, a in enumerate(sadlar)}),
                       os.path.join(HEDEF, 'esya', f'sapka-buyucu-katman-{boy}.png'))
                gl = alan_kucult(gozluk_harita, tuval, boy, {1: 1.5, 4: 2.0})
                kaydet(renklendir(gl, {i + 1: GOZLUK['merkezler'][a] for i, a in enumerate(gadlar)}),
                       os.path.join(HEDEF, 'esya', f'gozluk-gunes-katman-{boy}.png'))
                # Şapka takılıyken saç bu satırın üstünde gizlenir: charmoji.css'teki
                # .pk-sapkali .pk-sac clip-path değeri bu yüzdedir (değişirse orayı da güncelleyin).
                siper_ust_uc = siper_ust_cizgisi(s_ol) + s_y
                sonuc_bilgi['sac_kesme_yuzde'] = round((siper_ust_uc - tuval[1]) / (tuval[3] - tuval[1]) * 100, 2)

    # İkonlar (market kartı, çanta, yuva): nesnenin kendi tuvali, kare, kenarlarda pay.
    for ad, tanim, cikti in (('sapka-buyucu-ikon', SAPKA, [192, 96]), ('gozluk-gunes-ikon', GOZLUK, [192, 96]),
                             ('altin', PARA, [64, 32, 16]), ('sandik-kapali', SANDIK, [256, 128, 64])):
        et, adl = esya_etiketi(tanim)
        ys, xs = np.nonzero(et > 0)
        x0, x1, y0, y1 = xs.min(), xs.max() + 1, ys.min(), ys.max() + 1
        cx2, cy2 = (x0 + x1) // 2, (y0 + y1) // 2
        for boy in cikti:
            # Çok küçük boylarda (altın 16/32) payı ve kontur önceliğini azaltmak, yıldızın
            # kesik kesik kontur yerine dolu kalmasını sağlıyor (görüntülere bakılarak seçildi).
            kucuk = boy <= 32
            kenar = max(x1 - x0, y1 - y0)
            kenar += 2 * round(kenar * (0.0 if kucuk else 0.04 if ad == 'altin' else 0.06))
            kutu = (cx2 - kenar // 2, cy2 - kenar // 2, cx2 - kenar // 2 + kenar, cy2 - kenar // 2 + kenar)
            ag = {1: {16: 1.0, 32: 1.3}.get(boy, 1.5)}
            if 'parilti' in adl:
                ag[adl.index('parilti') + 1] = 1.0 if kucuk else 1.5
            lab = alan_kucult(et, kutu, boy, ag)
            alt_dizin = 'esya' if ad.endswith('-ikon') else 'nesne'
            kaydet(renklendir(lab, {i + 1: tanim['merkezler'][a] for i, a in enumerate(adl)}),
                   os.path.join(HEDEF, alt_dizin, f'{ad}-{boy}.png'))

    print('bilgi', sonuc_bilgi)

    if args.onizleme:
        onizleme(args.onizleme, sonuc_bilgi)


def siper_ust_cizgisi(s_ol):
    """Siperin (şapkanın en geniş kısmı) uçlarındaki üst kenar: saç bu çizginin üstünde gizlenir."""
    ys, xs = np.nonzero(s_ol > 0)
    sol, sag = xs.min(), xs.max()
    uc = []
    for x in (sol + 4, sag - 4):
        uc.append(ys[xs == x].min())
    return max(uc)


def cam_merkezleri(g, adlar):
    koyu = np.isin(g, [adlar.index('koyu') + 1, adlar.index('koyu_g') + 1, adlar.index('parilti') + 1])
    ys, xs = np.nonzero(koyu)
    orta = (xs.min() + xs.max()) / 2
    m = []
    for sec in (xs < orta, xs >= orta):
        m.append(((xs[sec].min() + xs[sec].max()) / 2, (ys[sec].min() + ys[sec].max()) / 2))
    return m


def onizleme(dizin, bilgi):
    os.makedirs(dizin, exist_ok=True)
    zemin = (0x17, 0x14, 0x2A, 255)
    for boy in CIKTI['tam']:
        for kombin in (((1, 1, 1), ''), ((2, 3, 2), 'sapka'), ((3, 4, 3), 'gozluk'), ((4, 6, 4), 'sapka+gozluk'),
                       ((1, 2, 1), 'sapka+gozluk'), ((2, 5, 3), '')):
            (t, s, u), esya = kombin
            img = Image.new('RGBA', (boy, boy), zemin)
            for yol in (f'karakter/tam/ten-{t}-{boy}.png', f'karakter/tam/ust-{u}-{boy}.png'):
                img.alpha_composite(Image.open(os.path.join(HEDEF, yol)).convert('RGBA'))
            sac = Image.open(os.path.join(HEDEF, f'karakter/tam/sac-{s}-{boy}.png')).convert('RGBA')
            if 'sapka' in esya:  # arayüzdeki clip-path ile aynı kesme
                kes = int(round(boy * bilgi['sac_kesme_yuzde'] / 100))
                sac.paste((0, 0, 0, 0), (0, 0, boy, kes))
            img.alpha_composite(sac)
            if 'gozluk' in esya:
                img.alpha_composite(Image.open(os.path.join(HEDEF, f'esya/gozluk-gunes-katman-{boy}.png')).convert('RGBA'))
            if 'sapka' in esya:
                img.alpha_composite(Image.open(os.path.join(HEDEF, f'esya/sapka-buyucu-katman-{boy}.png')).convert('RGBA'))
            img.resize((480, 480), Image.NEAREST).save(os.path.join(dizin, f'onizleme-{boy}-{t}{s}{u}-{esya or "yok"}.png'))


if __name__ == '__main__':
    sys.exit(main())
