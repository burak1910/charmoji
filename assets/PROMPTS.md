# Charmoji Prompt Kaydı

Tüm görseller `docs/stil-rehberi.md`'ye göre üretilir. Her prompt'un sonundaki paragraf rehberdeki Ortak Stil Bloğu'dur (kelimesi kelimesine aynı). Prompt'lar kopyalanıp yapıştırılmaya hazırdır. Her çalıştırma 4 varyasyon üretir ve kredi harcar; bir prompt'u değiştirmeden yeniden çalıştırma.

Durum değerleri: `bekliyor`, `üretildi`, `onaylandı`, `reddedildi`.

Boş alanları (üretim kimliği, seçilen varyasyon, tarih, not) üretimden sonra yönetici doldurur.

---

## D01 - Temel karakter

- Hedef dosya: `assets/deneme/deneme-01-temel-karakter.png`
- Amaç / kullanım yeri: ana ekranın merkezindeki büyük karakter; eşyasız, varsayılan renkler. Büst kadrajı, kafa büyük.
- Model: `gpt-image-2.5-sunburst`
- Durum: bekliyor
- Üretim kimliği:
- Seçilen varyasyon:
- Tarih:
- Not:

```
Game asset for a mobile RPG habit-tracker app: the default player character sprite, shown large in the center of the home screen, on a fully transparent background.

Subject: a friendly young adventurer shown as a bust, head, shoulders and upper chest only, ending in a clean flat horizontal bottom edge with an outline, kept inside the transparent margin like a floating bust, not touching the canvas edge. The head is large and rounded, about 55% of the artwork height, in a cute chibi proportion. Body and face look straight at the viewer, perfectly symmetrical.

Details: skin #F3D2C1 with shade #D9A58F and highlight #FBE6DA. Short, slightly messy hair in #2D3436 with shade #1B2022 and highlight #4A5456, leaving the forehead clearly visible so a hat can sit on top later. Small ears visible on both sides of the head. Eyes are simple dark vertical ovals about 2 pixels wide and 3 pixels tall, colored #0E0B1E, each with a single #F5F1FF glint pixel on the upper left, placed on one horizontal line at about the middle of the head. A tiny neutral smile of 4 to 5 pixels below the eyes in #0E0B1E, no eyebrows, no nose line beyond a single shade pixel. Visible neck in skin color with shade, so a necklace can sit on it later. A plain crew-neck t-shirt in #6C5CE7 with shade #4A3DB8 and highlight #9A8DF5, with a simple rounded collar line, no pattern, no logo, no print.

Constraints: the character wears no hat, no glasses, no mask, no necklace and holds nothing. The face area is kept clean and uncluttered. No hands, no arms below the shoulders, no background elements, no ground.

Art style for a mobile RPG game interface sprite: low-resolution pixel art drawn on a 64x64 logical pixel grid, shown upscaled with crisp nearest-neighbor hard edges, no anti-aliasing, no blur, no gradients, no dithering noise. Every shape has a solid 1-pixel dark outline in #0E0B1E, the only outline color, the same thickness everywhere, never pure black. Flat color blocks with exactly two extra tones per material: one darker shade placed on the lower-right side and one lighter highlight placed on the upper-left side, with the light always coming from the top-left. Use only this palette: outline #0E0B1E; skin #F3D2C1 with shade #D9A58F and highlight #FBE6DA; hair #2D3436 with shade #1B2022 and highlight #4A5456; shirt #6C5CE7 with shade #4A3DB8 and highlight #9A8DF5; gold #F2C14E with shade #B8832B and highlight #FFE08A; wizard purple #6D28D9 with shade #4C1D95 and highlight #9F67F0; dark fabric #2B2F4A with shade #181B2E; steel #C9CEDB with shade #8A90A6; wood #8A5A33 with shade #5C3A20 and highlight #B07A47; accent red #E5484D; white glint #F5F1FF. The background is fully transparent (PNG alpha channel), with no backdrop, no floor, no ground shadow, no glow and no frame. Square 1:1 canvas with a single subject viewed straight from the front, centered both horizontally and vertically, filling about 80% of the canvas with at least 10% empty transparent margin on every side. The image contains no text, no letters, no numbers, no watermark and no signature.
```

---

## D02 - Büyücü Şapkası

- Hedef dosya: `assets/deneme/deneme-02-sapka-buyucu-sapkasi.png`
- Amaç / kullanım yeri: karakterin üstüne CSS ile bindirilecek şapka eşyası (tek başına nesne olarak). Market kartında da kullanılır.
- Model: `gpt-image-2.5-sunburst`
- Durum: bekliyor
- Üretim kimliği:
- Seçilen varyasyon:
- Tarih:
- Not:

```
Game asset for a mobile RPG habit-tracker app: a hat item sprite that will later be overlaid on a character head with CSS, drawn alone on a fully transparent background.

Subject: a classic wizard hat, shown alone, straight from the front, upright, not worn by anyone. A tall cone with the tip softly bent over to the right, a wide round brim seen as a flat horizontal ellipse-like band at the bottom (front view, no top-down perspective), the brim spanning about 80% of the canvas width. The hat as a whole is centered and the brim sits in the lower part of the artwork.

Details: hat body in wizard purple #6D28D9 with shade #4C1D95 on the right side and highlight #9F67F0 on the upper-left side. Brim in the same purple family, with the underside edge in #4C1D95. A band of gold #F2C14E (shade #B8832B, highlight #FFE08A) wrapped around the base of the cone, with a small gold five-point star in the center of the band. Two or three small star shapes in #FFE08A on the cone, each only 2 to 3 pixels in size, kept sparse.

Constraints: no head, no hair, no person, no hands, no shadow under the hat, no stand or mannequin. The hat has a clean silhouette so it can be positioned on a head.

Art style for a mobile RPG game interface sprite: low-resolution pixel art drawn on a 64x64 logical pixel grid, shown upscaled with crisp nearest-neighbor hard edges, no anti-aliasing, no blur, no gradients, no dithering noise. Every shape has a solid 1-pixel dark outline in #0E0B1E, the only outline color, the same thickness everywhere, never pure black. Flat color blocks with exactly two extra tones per material: one darker shade placed on the lower-right side and one lighter highlight placed on the upper-left side, with the light always coming from the top-left. Use only this palette: outline #0E0B1E; skin #F3D2C1 with shade #D9A58F and highlight #FBE6DA; hair #2D3436 with shade #1B2022 and highlight #4A5456; shirt #6C5CE7 with shade #4A3DB8 and highlight #9A8DF5; gold #F2C14E with shade #B8832B and highlight #FFE08A; wizard purple #6D28D9 with shade #4C1D95 and highlight #9F67F0; dark fabric #2B2F4A with shade #181B2E; steel #C9CEDB with shade #8A90A6; wood #8A5A33 with shade #5C3A20 and highlight #B07A47; accent red #E5484D; white glint #F5F1FF. The background is fully transparent (PNG alpha channel), with no backdrop, no floor, no ground shadow, no glow and no frame. Square 1:1 canvas with a single subject viewed straight from the front, centered both horizontally and vertically, filling about 80% of the canvas with at least 10% empty transparent margin on every side. The image contains no text, no letters, no numbers, no watermark and no signature.
```

---

## D03 - Güneş Gözlüğü

- Hedef dosya: `assets/deneme/deneme-03-gozluk-gunes-gozlugu.png`
- Amaç / kullanım yeri: karakterin gözlerinin üstüne CSS ile bindirilecek yüz eşyası. Hizalama riskinin en yüksek olduğu tür.
- Model: `gpt-image-2.5-sunburst`
- Durum: bekliyor
- Üretim kimliği:
- Seçilen varyasyon:
- Tarih:
- Not:

```
Game asset for a mobile RPG habit-tracker app: a face accessory sprite that will later be overlaid on a character face with CSS, drawn alone on a fully transparent background.

Subject: a pair of sunglasses, shown alone, straight from the front, unfolded, not worn by anyone, centered on the canvas both horizontally and vertically. The glasses span about 80% of the canvas width and are only about 25% of the canvas height, so most of the canvas above and below stays empty and transparent.

Details: two wide rounded-rectangle lenses of equal size, dark fabric tone #2B2F4A with shade #181B2E on the lower-right part of each lens, and a diagonal glint made of 3 to 4 pixels in #F5F1FF on the upper-left of each lens. Frame and bridge in #181B2E, a short straight bridge connecting the two lenses at the top center, and two short temple stubs of 3 to 4 pixels going outwards from the outer top corners of the lenses, to the left and the right, flat in the same plane, no depth. All parts are symmetrical left to right.

Constraints: no head, no eyes, no face, no hands, no shadow, no reflection on the floor. Do not draw the temples folded back or in perspective.

Art style for a mobile RPG game interface sprite: low-resolution pixel art drawn on a 64x64 logical pixel grid, shown upscaled with crisp nearest-neighbor hard edges, no anti-aliasing, no blur, no gradients, no dithering noise. Every shape has a solid 1-pixel dark outline in #0E0B1E, the only outline color, the same thickness everywhere, never pure black. Flat color blocks with exactly two extra tones per material: one darker shade placed on the lower-right side and one lighter highlight placed on the upper-left side, with the light always coming from the top-left. Use only this palette: outline #0E0B1E; skin #F3D2C1 with shade #D9A58F and highlight #FBE6DA; hair #2D3436 with shade #1B2022 and highlight #4A5456; shirt #6C5CE7 with shade #4A3DB8 and highlight #9A8DF5; gold #F2C14E with shade #B8832B and highlight #FFE08A; wizard purple #6D28D9 with shade #4C1D95 and highlight #9F67F0; dark fabric #2B2F4A with shade #181B2E; steel #C9CEDB with shade #8A90A6; wood #8A5A33 with shade #5C3A20 and highlight #B07A47; accent red #E5484D; white glint #F5F1FF. The background is fully transparent (PNG alpha channel), with no backdrop, no floor, no ground shadow, no glow and no frame. Square 1:1 canvas with a single subject viewed straight from the front, centered both horizontally and vertically, filling about 80% of the canvas with at least 10% empty transparent margin on every side. The image contains no text, no letters, no numbers, no watermark and no signature.
```

---

## D04 - Günlük sandık (kapalı)

- Hedef dosya: `assets/deneme/deneme-04-gunluk-sandik-kapali.png`
- Amaç / kullanım yeri: günün tüm görevleri bitince açılan günlük sandığın kapalı hali (ana ekran, ödül ekranı).
- Model: `gpt-image-2.5-sunburst`
- Durum: bekliyor
- Üretim kimliği:
- Seçilen varyasyon:
- Tarih:
- Not:

```
Game asset for a mobile RPG habit-tracker app: the daily reward chest shown in its closed state, on a fully transparent background. It is displayed in the center of the screen when all tasks of the day are completed.

Subject: a small wooden treasure chest, closed, viewed straight from the front. The body is a wide rectangle with a rounded barrel lid on top. The chest is centered and fills about 80% of the canvas width.

Details: wood planks in #8A5A33 with shade #5C3A20 on the lower-right and highlight #B07A47 on the upper-left, with two or three horizontal plank lines. Gold #F2C14E (shade #B8832B, highlight #FFE08A) metal bands: one band along the rim between the lid and the body, and two vertical bands near the left and right ends, with 4 small rivet pixels. A gold lock plate in the center on the front, with a dark keyhole in #0E0B1E. One small red gem #E5484D with a #F5F1FF glint pixel set in the center of the lid band. Two tiny sparkle crosses in #F5F1FF, 3 to 5 pixels each, placed near the upper-right and upper-left of the chest, not touching it.

Constraints: the chest stays closed, no coins or items showing, no open lid, no shadow on the ground, no glow, no key, no chain, no stand.

Art style for a mobile RPG game interface sprite: low-resolution pixel art drawn on a 64x64 logical pixel grid, shown upscaled with crisp nearest-neighbor hard edges, no anti-aliasing, no blur, no gradients, no dithering noise. Every shape has a solid 1-pixel dark outline in #0E0B1E, the only outline color, the same thickness everywhere, never pure black. Flat color blocks with exactly two extra tones per material: one darker shade placed on the lower-right side and one lighter highlight placed on the upper-left side, with the light always coming from the top-left. Use only this palette: outline #0E0B1E; skin #F3D2C1 with shade #D9A58F and highlight #FBE6DA; hair #2D3436 with shade #1B2022 and highlight #4A5456; shirt #6C5CE7 with shade #4A3DB8 and highlight #9A8DF5; gold #F2C14E with shade #B8832B and highlight #FFE08A; wizard purple #6D28D9 with shade #4C1D95 and highlight #9F67F0; dark fabric #2B2F4A with shade #181B2E; steel #C9CEDB with shade #8A90A6; wood #8A5A33 with shade #5C3A20 and highlight #B07A47; accent red #E5484D; white glint #F5F1FF. The background is fully transparent (PNG alpha channel), with no backdrop, no floor, no ground shadow, no glow and no frame. Square 1:1 canvas with a single subject viewed straight from the front, centered both horizontally and vertically, filling about 80% of the canvas with at least 10% empty transparent margin on every side. The image contains no text, no letters, no numbers, no watermark and no signature.
```

---

## D05 - Altın para ikonu

- Hedef dosya: `assets/deneme/deneme-05-altin-para.png`
- Amaç / kullanım yeri: altın bakiyesi ve fiyat etiketlerinin yanındaki küçük ikon. Küçük boyutta okunmalı.
- Model: `gpt-image-2.5-sunburst`
- Durum: bekliyor
- Üretim kimliği:
- Seçilen varyasyon:
- Tarih:
- Not:

```
Game asset for a mobile RPG habit-tracker app: a small gold coin icon shown next to the gold balance and price labels, so it must read clearly even when displayed at 24 pixels, on a fully transparent background.

Subject: a single round gold coin, viewed straight from the front as a flat disc, perfectly circular, centered and filling about 80% of the canvas.

Details: coin face in gold #F2C14E, an inner circular ring line just inside the rim in shade #B8832B, the rim itself slightly thicker with the lighter highlight #FFE08A on the upper-left arc and the shade #B8832B on the lower-right arc. In the center, a simple bold four-point star emblem in #B8832B with a #FFE08A highlight on its upper-left edge. Two or three #F5F1FF glint pixels on the upper-left of the rim. Very simple shapes, only a few large pixel blocks, no fine detail.

Constraints: one coin only, no stack, no side thickness, no tilt, no motion lines, no sparkles around it, no shadow, no glow, no symbols that look like letters or numbers.

Art style for a mobile RPG game interface sprite: low-resolution pixel art drawn on a 64x64 logical pixel grid, shown upscaled with crisp nearest-neighbor hard edges, no anti-aliasing, no blur, no gradients, no dithering noise. Every shape has a solid 1-pixel dark outline in #0E0B1E, the only outline color, the same thickness everywhere, never pure black. Flat color blocks with exactly two extra tones per material: one darker shade placed on the lower-right side and one lighter highlight placed on the upper-left side, with the light always coming from the top-left. Use only this palette: outline #0E0B1E; skin #F3D2C1 with shade #D9A58F and highlight #FBE6DA; hair #2D3436 with shade #1B2022 and highlight #4A5456; shirt #6C5CE7 with shade #4A3DB8 and highlight #9A8DF5; gold #F2C14E with shade #B8832B and highlight #FFE08A; wizard purple #6D28D9 with shade #4C1D95 and highlight #9F67F0; dark fabric #2B2F4A with shade #181B2E; steel #C9CEDB with shade #8A90A6; wood #8A5A33 with shade #5C3A20 and highlight #B07A47; accent red #E5484D; white glint #F5F1FF. The background is fully transparent (PNG alpha channel), with no backdrop, no floor, no ground shadow, no glow and no frame. Square 1:1 canvas with a single subject viewed straight from the front, centered both horizontally and vertically, filling about 80% of the canvas with at least 10% empty transparent margin on every side. The image contains no text, no letters, no numbers, no watermark and no signature.
```
