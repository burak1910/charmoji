# tests/guvenlik/test_a_xss.sh  (calistir.sh tarafından source edilir)
# a) Avatar verisinde depolanan XSS: kayıtta red + veritabanındaki kötü verinin çizimde etkisizliği.
#    Eski kod: serbest renkler (save_avatar_colors). Yeni kod: piksel büst için hazır görünüm
#    seçenekleri (save_avatar_options; ten 1-4, saç 1-6, üst 1-4); görsel yolları yalnızca
#    sabit tablolardan kurulur, eski renk verisi 1 numaralı seçeneklere düşer (S28).

bolum "a) Depolanan XSS (avatar verisi)"

A_KAV=$(yeni_kavanoz a_kullanici)
kayit_ol "$A_KAV" "xss_a" "xss_a@test.local" "Sifre123!"
A_ID=$(kullanici_id "xss_a@test.local")
A_JETON=$(sayfa_jetonu "$A_KAV")
kanit "kullanıcı id=$A_ID, jeton=${A_JETON:-<yok>}"

avatar_verisi() { sql "SELECT COALESCE(AvatarVerisi,'NULL') FROM Kullanicilar WHERE KullaniciID = $A_ID"; }

if [[ "$MOD" == "yeni" ]]; then
    # 1. Kayıtta geçersiz seçenekler ve enjeksiyon reddedilir (veritabanı değişmez)
    secenek_kaydet() { ajax "$A_KAV" "$A_JETON" --data-urlencode "save_avatar_options=1" --data-urlencode "secenekler=$1"; }
    ONCE=$(avatar_verisi)
    for YUK in \
        '{"ten":5}' '{"ten":0}' '{"sac":7}' '{"ust":-1}' '{"ten":"01"}' '{"ten":"1 "}' '{"ten":1.0}' '{"ten":true}' \
        '{"ten":"1\"><script>window.__xss=1</script>"}' '{"sac":"2\" onload=\"window.__xss=2"}' \
        '{"ten":"../../config/x"}' '{"ten":[1]}' '{"goz":1}' '{"skinColor":"#112233"}' '{"ten":1,"kotuAnahtar":1}' \
        '[1,2,3]' '{}' 'gecersiz-json' '1'; do
        secenek_kaydet "$YUK"
        dogrula "geçersiz seçenek reddedildi: $YUK (HTTP $HTTP_KOD, success:false, kod gecersiz_secenek)" \
            bash -c '[[ "$0" == "400" && "$1" == *"\"success\":false"* && "$1" == *"gecersiz_secenek"* ]]' "$HTTP_KOD" "$HTTP_GOVDE"
    done
    secenek_kaydet '{"sac":9}'
    dogrula "reddedilen bölge yanıtta bildiriliyor (bolge: sac)" icerir "$HTTP_GOVDE" '"bolge":"sac"'
    dogrula "geçersiz seçeneklerden sonra veritabanı değişmedi" esit "$(avatar_verisi)" "$ONCE"

    # 2. Meşru kayıt: yalnızca doğrulanmış tam sayılar yazılır; eksik bölge 1
    secenek_kaydet '{"ten":4,"sac":"6","ust":2}'
    kanit "meşru kayıt yanıtı: HTTP $HTTP_KOD $HTTP_GOVDE"
    dogrula "geçerli seçenekler kaydedildi (metin '6' tam sayıya çevrildi)" esit "$(avatar_verisi)" '{"ten":4,"sac":6,"ust":2}'
    secenek_kaydet '{"sac":3}'
    dogrula "eksik bölgeler 1 numaralı seçeneğe tamamlandı" esit "$(avatar_verisi)" '{"ten":1,"sac":3,"ust":1}'
    istek "$A_KAV" "${BASE}${ANA_YOLU}"
    dogrula "$ANA_YOLU: kaydedilen saç seçeneğinin katmanı çiziliyor (sac-3)" icerir "$HTTP_GOVDE" 'statik/gorsel/karakter/tam/sac-3-480.png'

    # Eski kayıt ucu artık hiçbir şey yazmıyor
    ONCE=$(avatar_verisi)
    ajax "$A_KAV" "$A_JETON" --data-urlencode "save_avatar_colors=1" --data-urlencode 'colors={"skinColor":"#123456"}'
    dogrula "eski save_avatar_colors ucu veriyi değiştirmiyor" esit "$(avatar_verisi)" "$ONCE"
else
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
fi

# 3. Veritabanına elle yazılmış kötü veri çizimde etkisiz. Yeni kodda aynı kayıtta geçerli bir
#    seçenek (ust 2) ve kötü değerli seçenekler (ten, sac) de vardır.
mysql_komutu "$TEST_DB" <<SQL
UPDATE Kullanicilar SET AvatarVerisi = JSON_OBJECT(
    'skinColor', '"><script>window.__xss=1</script>',
    'topColor',  '#000" onload="window.__xss=2',
    'eyeColor',  'red',
    'hairColor', '#ABCDEF',
    'ten', '1"><script>window.__xss=4</script>',
    'sac', '../../../../etc/passwd',
    'ust', 2
) WHERE KullaniciID = $A_ID;
SQL
kanit "elle yazılan: $(avatar_verisi)"

istek "$A_KAV" "${BASE}${ANA_YOLU}"
UP="$HTTP_GOVDE"
dogrula "$ANA_YOLU: kötü değer HTML'e script olarak basılmadı" icermez "$UP" "<script>window.__xss"
dogrula "$ANA_YOLU: kötü değer attribute kaçışı yapamadı (onload yok)" icermez "$UP" 'onload="window.__xss'
if [[ "$MOD" == "yeni" ]]; then
    dogrula "$ANA_YOLU: geçersiz ten seçeneği 1'e düştü (ten-1 katmanı)" icerir "$UP" 'statik/gorsel/karakter/tam/ten-1-480.png'
    dogrula "$ANA_YOLU: geçersiz saç seçeneği 1'e düştü, yol kullanıcı verisinden kurulmadı" \
        bash -c '[[ "$0" == *"karakter/tam/sac-1-480.png"* && "$0" != *"passwd"* ]]' "$UP"
    dogrula "$ANA_YOLU: geçerli üst giysi seçeneği (2) korunuyor" icerir "$UP" 'statik/gorsel/karakter/tam/ust-2-480.png'

    # S28: yalnızca eski serbest renk verisi olan hesap her bölgede 1 numaralı seçenekle çizilir
    sql "UPDATE Kullanicilar SET AvatarVerisi = '{\"skinColor\":\"#112233\",\"hairColor\":\"#AABBCC\",\"eyeColor\":\"#000000\",\"topColor\":\"#6C5CE7\"}' WHERE KullaniciID = $A_ID"
    istek "$A_KAV" "${BASE}${ANA_YOLU}"
    dogrula "S28: eski renk verisi olan hesap 1 numaralı seçeneklere düştü (ten-1, sac-1, ust-1)" \
        bash -c '[[ "$0" == *"karakter/tam/ten-1-480.png"* && "$0" == *"karakter/tam/sac-1-480.png"* && "$0" == *"karakter/tam/ust-1-480.png"* ]]' "$HTTP_GOVDE"
    istek "$A_KAV" "${BASE}view/avatar.php"
    dogrula "S28: Görünüm ekranında her bölgede 1 numaralı seçenek seçili" \
        bash -c '[[ $(tr "\n" " " <<<"$0" | grep -o "aria-checked=\"true\"[^>]*data-n=\"1\"" | wc -l) -eq 3 ]]' "$HTTP_GOVDE"
    mysql_komutu "$TEST_DB" <<SQL
UPDATE Kullanicilar SET AvatarVerisi = JSON_OBJECT(
    'skinColor', '"><script>window.__xss=1</script>',
    'ten', '1"><script>window.__xss=4</script>',
    'sac', '2" onload="window.__xss=2'
) WHERE KullaniciID = $A_ID;
SQL
    # Seçenek doğrulaması birim düzeyinde (avatar_renderer.php)
    BIRIM=$(cd "$UYGULAMA/view" && php -d display_errors=1 -r '
        require "avatar_renderer.php";
        $s = avatar_secenekleri_temizle("{\"ten\":\"4\",\"sac\":\"3x\",\"ust\":9}");
        echo json_encode($s), "|";
        echo json_encode(avatar_secenekleri_dogrula(["ten" => 2, "sac" => "<b>"])), "|";
        echo json_encode(avatar_secenekleri_temizle(str_repeat("x", 5000)));')
    kanit "birim: $BIRIM"
    dogrula "avatar_secenekleri_temizle: '4' kabul, '3x' ve 9 -> 1" icerir "$BIRIM" '{"ten":4,"sac":1,"ust":1}|'
    dogrula "avatar_secenekleri_dogrula: geçersiz saç değeri bölgesiyle reddedildi" icerir "$BIRIM" '{"secenekler":null,"hatali":"sac"}|'
    dogrula "avatar_secenekleri_temizle: bozuk/uzun veri varsayılana düştü" icerir "$BIRIM" '|{"ten":1,"sac":1,"ust":1}'
else
    dogrula "$ANA_YOLU: geçersiz ten rengi varsayılana (#F3D2C1) düştü" icerir "$UP" 'id="Skin" fill="#F3D2C1"'
    dogrula "$ANA_YOLU: geçerli saç rengi (#ABCDEF) korunuyor" icerir "$UP" 'id="Hair" fill="#ABCDEF"'
fi

# Dalga 2: karakter çizen diğer sekmeler (Karakter, Lonca satırları) de aynı veriyi çizer.
if [[ "$MOD" == "yeni" ]]; then
    for A_SAYFA in view/karakter.php view/lonca.php view/market.php; do
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
