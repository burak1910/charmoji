#!/usr/bin/env python3
"""Yeşil zeminli (#00FF00) üretim çıktısını gerçek alfa kanallı RGBA PNG'ye çevirir.

Kullanım:
    python3 assets/araclar/arka-plan-sil.py girdi.png cikti.png [--tolerans N] [--ortala]

Yöntem:
  1. Anahtar rengine (#00FF00) öklid uzaklığı <= tolerans olan pikseller tam saydam yapılır.
     Dıştan bağlantılı zemin değil, görseldeki TÜM anahtar rengi pikselleri silinir. Bu güvenlidir
     çünkü Charmoji paletinde yeşil yoktur (rehber, bölüm 2); özneye ait hiçbir piksel yeşil değildir.
  2. Kenarlarda kontur (#0E0B1E) ile zemin karışmış piksellerin rengi yeşile kayar. Paletteki hiçbir
     rengin yeşil kanalı kırmızı ve mavinin büyüğünü aşmaz; bu yüzden (g - max(r, b)) değeri
     belirgin pozitif olan pikseller karışmış kenar pikseli sayılıp tamamen silinir (--kenar eşiği).
  3. Kalan piksellerde küçük yeşil sızıntı kalırsa yeşil kanal max(r, b) değerine kırpılır (despill).
  4. --ortala: özne sınır kutusuna kırpılır ve en uzun kenarı tuvalin %80'i olacak biçimde kare
     tuvale ortalanır (her kenarda en az %10 boşluk). Özne yeniden ölçeklenmez, piksel boyutu korunur.
"""
import argparse
import math
import sys

from PIL import Image

ANAHTAR = (0, 255, 0)


def arka_plan_sil(img, tolerans=60, kenar=24):
    img = img.convert("RGB")
    w, h = img.size
    src = getattr(img, "get_flattened_data", img.getdata)()
    out = []
    tol2 = tolerans * tolerans
    for r, g, b in src:
        dr, dg, db = r - ANAHTAR[0], g - ANAHTAR[1], b - ANAHTAR[2]
        if dr * dr + dg * dg + db * db <= tol2:
            out.append((0, 0, 0, 0))
            continue
        fazla = g - max(r, b)
        if fazla > kenar:
            out.append((0, 0, 0, 0))  # kontur ile zemin arasında karışmış kenar pikseli
            continue
        if fazla > 0:
            g = max(r, b)  # küçük yeşil sızıntıyı gider
        out.append((r, g, b, 255))
    res = Image.new("RGBA", (w, h))
    res.putdata(out)
    return res


def ortala(img, bosluk=0.10):
    kutu = img.getchannel("A").getbbox()
    if kutu is None:
        return img
    ozne = img.crop(kutu)
    en_uzun = max(ozne.size)
    kenar = max(1, math.ceil(en_uzun / (1 - 2 * bosluk)))
    tuval = Image.new("RGBA", (kenar, kenar), (0, 0, 0, 0))
    tuval.paste(ozne, ((kenar - ozne.width) // 2, (kenar - ozne.height) // 2))
    return tuval


def main():
    ap = argparse.ArgumentParser(description="Yeşil (#00FF00) zemini gerçek alfa kanalına çevirir.")
    ap.add_argument("girdi")
    ap.add_argument("cikti")
    ap.add_argument("--tolerans", type=int, default=60,
                    help="anahtar rengine öklid uzaklığı eşiği (varsayılan 60; saçak kalırsa artır)")
    ap.add_argument("--kenar", type=int, default=24,
                    help="karışmış kenar pikseli için (g - max(r,b)) eşiği (varsayılan 24)")
    ap.add_argument("--ortala", action="store_true",
                    help="özneyi sınır kutusuna kırp, %%10 boşlukla kare tuvale ortala")
    a = ap.parse_args()
    try:
        img = Image.open(a.girdi)
    except OSError as e:
        sys.exit("Girdi açılamadı: %s" % e)
    res = arka_plan_sil(img, a.tolerans, a.kenar)
    if a.ortala:
        res = ortala(res)
    res.save(a.cikti, "PNG")
    print("Yazıldı: %s (%dx%d, RGBA)" % (a.cikti, res.width, res.height))


if __name__ == "__main__":
    main()
