# tests/guvenlik/ortak.sh
# Güvenlik doğrulama betiklerinin ortak yardımcıları. Doğrudan çalıştırılmaz;
# calistir.sh tarafından "source" edilir.
#
# calistir.sh şu değişkenleri ayarlar:
#   UYGULAMA      : test edilen uygulamanın kök dizini (yeni kod ya da eski kod kopyası)
#   MOD           : "yeni" ya da "eski"
#   BASE          : uygulamanın kökten servis edildiği adres (sonu / ile biter)
#   BASE_ALT      : uygulamanın /charmoji/ alt dizininden servis edildiği adres
#   LOGIN_YOLU    : giriş sayfası (yeni: view/login.php, eski: service/login.php)
#   REGISTER_YOLU : kayıt sayfası (yeni: view/register.php, eski: service/register.php)
#   GECICI        : bu çalıştırmaya ait geçici dizin
#   TEST_DB, TEST_DB_USER, TEST_DB_PASSWORD, TEST_DB_HOST

GECEN=${GECEN:-0}
KALAN=${KALAN:-0}
ATLANAN=${ATLANAN:-0}
KALAN_LISTE=("${KALAN_LISTE[@]+"${KALAN_LISTE[@]}"}")
BOLUM=${BOLUM:-}

# ---------------------------------------------------------------- çıktı
bolum() {
    BOLUM="$1"
    printf '\n=== %s ===\n' "$1"
}

kanit() {
    printf '        %s\n' "$*"
}

# dogrula "açıklama" komut [argümanlar...]
dogrula() {
    local aciklama="$1"; shift
    if "$@"; then
        GECEN=$((GECEN + 1))
        printf '  [GEÇTİ] %s\n' "$aciklama"
    else
        KALAN=$((KALAN + 1))
        printf '  [KALDI] %s\n' "$aciklama"
        KALAN_LISTE+=("$BOLUM: $aciklama")
    fi
}

atla() {
    ATLANAN=$((ATLANAN + 1))
    printf '  [ATLANDI] %s\n' "$*"
}

esit()    { [[ "$1" == "$2" ]]; }
farkli()  { [[ "$1" != "$2" ]]; }
icerir()  { [[ "$1" == *"$2"* ]]; }
icermez() { [[ "$1" != *"$2"* ]]; }
bos()     { [[ -z "$1" ]]; }
dolu()    { [[ -n "$1" ]]; }
biter()   { [[ "$1" == *"$2" ]]; }

# ---------------------------------------------------------------- veritabanı
mysql_komutu() {
    local args=(-h"${TEST_DB_HOST}" -u"${TEST_DB_USER}")
    if [[ -n "${TEST_DB_PASSWORD}" ]]; then
        args+=(-p"${TEST_DB_PASSWORD}")
    fi
    mysql "${args[@]}" --default-character-set=utf8mb4 "$@"
}

# sql "SORGU" -> sonuç (başlıksız, sekmeyle ayrılmış)
sql() {
    mysql_komutu -N -B "$TEST_DB" -e "$1"
}

kullanici_id() {
    sql "SELECT KullaniciID FROM Kullanicilar WHERE Eposta = '$1'"
}

# ---------------------------------------------------------------- HTTP
# yeni_kavanoz AD -> çerez dosyası yolu
yeni_kavanoz() {
    local dosya="$GECICI/cerez_$1.txt"
    : > "$dosya"
    printf '%s' "$dosya"
}

# istek KAVANOZ URL [curl argümanları...]
# Sonuç: HTTP_KOD, HTTP_SON_URL, HTTP_GOVDE
istek() {
    local kavanoz="$1"; shift
    local cikti
    cikti=$(curl -sS -b "$kavanoz" -c "$kavanoz" --max-time 20 \
        -w $'\n__KOD__%{http_code} __URL__%{url_effective}' "$@" 2>&1)
    HTTP_KOD=${cikti##*__KOD__}
    HTTP_KOD=${HTTP_KOD%% *}
    HTTP_SON_URL=${cikti##*__URL__}
    HTTP_GOVDE=${cikti%$'\n'__KOD__*}
}

# jeton_cek KAVANOZ TAM_URL -> sayfadaki CSRF jetonu (eski kodda boş döner)
jeton_cek() {
    local govde jeton
    govde=$(curl -sS -b "$1" -c "$1" --max-time 20 "$2")
    jeton=$(printf '%s' "$govde" | grep -o 'name="csrf_token" value="[0-9a-f]*"' | head -n1 | grep -o '[0-9a-f]\{64\}')
    if [[ -z "$jeton" ]]; then
        jeton=$(printf '%s' "$govde" | grep -o 'CSRF_JETONU = "[0-9a-f]*"' | head -n1 | grep -o '[0-9a-f]\{64\}')
    fi
    printf '%s' "$jeton"
}

# kayit_ol KAVANOZ AD EPOSTA SIFRE [BASE]
kayit_ol() {
    local kok="${5:-$BASE}" jeton
    jeton=$(jeton_cek "$1" "${kok}${REGISTER_YOLU}")
    istek "$1" "${kok}service/auth.php" -L \
        --data-urlencode "fullname=$2" --data-urlencode "email=$3" \
        --data-urlencode "password=$4" --data-urlencode "confirm_password=$4" \
        --data-urlencode "csrf_token=$jeton"
}

# giris_yap KAVANOZ EPOSTA SIFRE [BASE]
giris_yap() {
    local kok="${4:-$BASE}" jeton
    jeton=$(jeton_cek "$1" "${kok}${LOGIN_YOLU}")
    istek "$1" "${kok}service/auth.php" -L \
        --data-urlencode "username=$2" --data-urlencode "password=$3" \
        --data-urlencode "csrf_token=$jeton"
}

# sayfa_jetonu KAVANOZ [BASE] -> giriş yapılmış kullanıcının userpage jetonu
sayfa_jetonu() {
    jeton_cek "$1" "${2:-$BASE}view/userpage.php"
}

# ajax KAVANOZ JETON [curl argümanları...]  (jeton X-CSRF-Token başlığında)
ajax() {
    local kavanoz="$1" jeton="$2"; shift 2
    istek "$kavanoz" "${BASE}view/ajax_islem.php" -H "X-CSRF-Token: $jeton" "$@"
}

# oturum_acik_mi KAVANOZ [BASE] -> userpage.php 200 ve login'e yönlenmiyorsa 0
oturum_acik_mi() {
    istek "$1" "${2:-$BASE}view/userpage.php" -L
    [[ "$HTTP_KOD" == "200" && "$HTTP_SON_URL" == *"view/userpage.php" ]]
}
