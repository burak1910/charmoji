# tests/guvenlik/test_a_xss.sh  (calistir.sh tarafından source edilir)
# a) Avatar renklerinde depolanan XSS: kayıtta red + veritabanındaki kötü verinin çizimde etkisizliği.

bolum "a) Depolanan XSS (avatar renkleri)"

A_KAV=$(yeni_kavanoz a_kullanici)
kayit_ol "$A_KAV" "xss_a" "xss_a@test.local" "Sifre123!"
A_ID=$(kullanici_id "xss_a@test.local")
A_JETON=$(sayfa_jetonu "$A_KAV")
kanit "kullanıcı id=$A_ID, jeton=${A_JETON:-<yok>}"

avatar_verisi() { sql "SELECT COALESCE(AvatarVerisi,'NULL') FROM Kullanicilar WHERE KullaniciID = $A_ID"; }

# 1. Kayıtta kötü değerler
YUK1='{"skinColor":"\"><script>window.__xss=1</script>","hairColor":"#000000"}'
ajax "$A_KAV" "$A_JETON" --data-urlencode "save_avatar_colors=1" --data-urlencode "colors=$YUK1"
kanit "YÜK1 yanıtı: HTTP $HTTP_KOD $HTTP_GOVDE"
KAYIT=$(avatar_verisi)
kanit "veritabanı: $KAYIT"
dogrula "'\"><script>' içeren renk kayıtta reddedildi (veritabanına yazılmadı)" icermez "$KAYIT" "<script"

YUK2='{"topColor":"#000\" onload=\"window.__xss=2"}'
ajax "$A_KAV" "$A_JETON" --data-urlencode "save_avatar_colors=1" --data-urlencode "colors=$YUK2"
kanit "YÜK2 yanıtı: HTTP $HTTP_KOD $HTTP_GOVDE"
KAYIT=$(avatar_verisi)
dogrula "'\" onload=' içeren renk kayıtta reddedildi" icermez "$KAYIT" "onload"

YUK3='{"skinColor":"#112233","kotuAnahtar":"#000000"}'
ajax "$A_KAV" "$A_JETON" --data-urlencode "save_avatar_colors=1" --data-urlencode "colors=$YUK3"
KAYIT=$(avatar_verisi)
dogrula "izinli listede olmayan anahtar reddedildi" icermez "$KAYIT" "kotuAnahtar"

YUK4=$'{"skinColor":"#112233\\n"}'
ajax "$A_KAV" "$A_JETON" --data-urlencode "save_avatar_colors=1" --data-urlencode "colors=$YUK4"
dogrula "sonunda satır sonu olan renk ('#112233\\n') reddedildi" icermez "$HTTP_GOVDE" '"success":true'

# 2. Meşru kayıt çalışıyor
ajax "$A_KAV" "$A_JETON" --data-urlencode "save_avatar_colors=1" --data-urlencode 'colors={"skinColor":"#112233","hairColor":"#AABBCC","eyeColor":"#000000","topColor":"#6C5CE7"}'
KAYIT=$(avatar_verisi)
kanit "meşru kayıt sonrası veritabanı: $KAYIT"
dogrula "geçerli #RRGGBB renkler kaydedildi" icerir "$KAYIT" "#112233"

# 3. Veritabanına elle yazılmış kötü veri çizimde etkisiz
mysql_komutu "$TEST_DB" <<SQL
UPDATE Kullanicilar SET AvatarVerisi = JSON_OBJECT(
    'skinColor', '"><script>window.__xss=1</script>',
    'topColor',  '#000" onload="window.__xss=2',
    'eyeColor',  'red',
    'hairColor', '#ABCDEF'
) WHERE KullaniciID = $A_ID;
SQL
kanit "elle yazılan: $(avatar_verisi)"

istek "$A_KAV" "${BASE}${ANA_YOLU}"
UP="$HTTP_GOVDE"
dogrula "$ANA_YOLU: kötü değer HTML'e script olarak basılmadı" icermez "$UP" "<script>window.__xss"
dogrula "$ANA_YOLU: kötü değer attribute kaçışı yapamadı (onload yok)" icermez "$UP" 'onload="window.__xss'
dogrula "$ANA_YOLU: geçersiz ten rengi varsayılana (#F3D2C1) düştü" icerir "$UP" 'id="Skin" fill="#F3D2C1"'
dogrula "$ANA_YOLU: geçerli saç rengi (#ABCDEF) korunuyor" icerir "$UP" 'id="Hair" fill="#ABCDEF"'

# Dalga 2: karakter çizen diğer sekmeler (Karakter, Lonca satırları) de aynı veriyi çizer.
if [[ "$MOD" == "yeni" ]]; then
    for A_SAYFA in view/karakter.php view/lonca.php; do
        istek "$A_KAV" "${BASE}${A_SAYFA}"
        dogrula "$A_SAYFA: kötü değer script/attribute olarak basılmadı" \
            bash -c '[[ "$0" == "200" && "$1" != *"<script>window.__xss"* && "$1" != *"onload=\"window.__xss"* ]]' "$HTTP_KOD" "$HTTP_GOVDE"
    done
fi

istek "$A_KAV" "${BASE}view/avatar.php"
AV="$HTTP_GOVDE"
dogrula "avatar.php: kötü değer script olarak basılmadı" icermez "$AV" "<script>window.__xss"
dogrula "avatar.php: kötü değer attribute kaçışı yapamadı" icermez "$AV" 'onload="window.__xss'

# 4. $size parametresi
SIZE_CIKTI=$(cd "$UYGULAMA/view" && php -d display_errors=1 -r '
    require "avatar_renderer.php";
    echo renderAvatar(null, "\"><script>window.__xss=3</script>");')
dogrula "renderAvatar: kötü \$size parametresi kaçış yapamadı" icermez "$SIZE_CIKTI" "<script>window.__xss=3"

# 5. Tarayıcıda (Playwright) script çalışmıyor
if [[ "$PLAYWRIGHT" == "1" ]] && command -v node >/dev/null; then
    PW=$(node "$TEST_DIZINI/xss_tarayici.js" "$BASE" "$LOGIN_YOLU" "xss_a@test.local" "Sifre123!" 2>&1)
    kanit "playwright: $PW"
    dogrula "tarayıcıda oturum açık sayfalar açılırken enjekte script çalışmadı (window.__xss tanımsız)" \
        icerir "$PW" '"tetiklenen":[]'
else
    atla "Playwright testi (PLAYWRIGHT=0 ya da node yok)"
fi
