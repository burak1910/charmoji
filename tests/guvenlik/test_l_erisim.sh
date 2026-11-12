# tests/guvenlik/test_l_erisim.sh  (calistir.sh tarafından source edilir)
# l) B-4: .env ve depo dosyaları web'den okunamaz (yönlendirici), uygulama dosyaları servis edilir.
#    Uygulamanın geçici bir kopyasına tanıtım ("canary") içerikli gizli dosyalar konur; sunucu
#    gerçek kurulumdaki gibi (varsa sunucu.php yönlendiricisiyle) başlatılır. Kökten ve /charmoji/ altından denenir.

bolum "l) Gizli dosyalar web'den okunamaz"

L_KOPYA="$GECICI/erisim_kopya"
mkdir -p "$L_KOPYA"
tar -C "$UYGULAMA" --exclude=./.git --exclude=./mysql-8.4 --exclude=./tests -cf - . | tar -C "$L_KOPYA" -xf -
mkdir -p "$L_KOPYA/.git" "$L_KOPYA/database" "$L_KOPYA/tests/guvenlik" "$L_KOPYA/config" "$L_KOPYA/docs" "$L_KOPYA/assets/araclar"
SIR="CANARY_SIR_$$"
printf 'DB_PASSWORD=%s\n' "$SIR" > "$L_KOPYA/.env"
printf '[core] %s\n' "$SIR" > "$L_KOPYA/.git/config"
printf -- '-- %s\n' "$SIR" > "$L_KOPYA/database/schema.sql"
printf '%s\n' "$SIR" > "$L_KOPYA/tests/guvenlik/README.md"
printf '%s\n' "$SIR" > "$L_KOPYA/docs/not.md"
printf '%s\n' "$SIR" > "$L_KOPYA/assets/araclar/arac.txt"
printf '%s\n' "$SIR" > "$L_KOPYA/hata.log"
printf '%s\n' "$SIR" > "$L_KOPYA/yedek.sh"
[[ -f "$L_KOPYA/config/env.php" ]] || printf '<?php // %s\n' "$SIR" > "$L_KOPYA/config/env.php"
# Eski kodda sunucu.php yoksa yönlendirici de yoktur: kök servis edilirken bu dosya talep edilirse
# sızacak içerik olsun diye canary konur (yeni kodda gerçek yönlendirici kopyalanmıştır, dokunulmaz).
[[ -f "$L_KOPYA/sunucu.php" ]] || printf '<?php // %s\n' "$SIR" > "$L_KOPYA/sunucu_yok.txt"

mkdir -p "$GECICI/erisim_alt"
ln -s "$L_KOPYA" "$GECICI/erisim_alt/charmoji"
UYGULAMA_ONCE="$UYGULAMA"; UYGULAMA="$L_KOPYA"   # yonlendirici_yolu kopyadaki sunucu.php'yi kullansın
sunucu_baslat "$((PORT_TABAN + 3))" "$L_KOPYA" "$TEST_ENV" "$GECICI/sunucu_erisim_kok.log"
sunucu_baslat "$((PORT_TABAN + 5))" "$GECICI/erisim_alt" "$TEST_ENV" "$GECICI/sunucu_erisim_alt.log"
UYGULAMA="$UYGULAMA_ONCE"
L_KOK="http://127.0.0.1:$((PORT_TABAN + 3))/"
L_ALT="http://127.0.0.1:$((PORT_TABAN + 5))/charmoji/"
L_KAV=$(yeni_kavanoz l_erisim)

gizli_yol_denetimi() { # KOK ETIKET
    local kok="$1" etiket="$2" yol
    for yol in .env .git/config database/schema.sql tests/guvenlik/README.md config/env.php docs/not.md \
               assets/araclar/arac.txt hata.log yedek.sh sunucu.php .htaccess; do
        istek "$L_KAV" "${kok}${yol}"
        kanit "$etiket /$yol -> HTTP $HTTP_KOD"
        dogrula "$etiket: /$yol 404 dönüyor ve sır sızdırmıyor" bash -c '[[ "$0" == "404" && "$1" != *CANARY_SIR* ]]' "$HTTP_KOD" "$HTTP_GOVDE"
    done
    istek "$L_KAV" "${kok}%2eenv"
    dogrula "$etiket: kodlanmış /%2eenv 404 ve sır sızdırmıyor" bash -c '[[ "$0" == "404" && "$1" != *CANARY_SIR* ]]' "$HTTP_KOD" "$HTTP_GOVDE"
    for yol in view/index.php "$LOGIN_YOLU" style/custom.css; do
        istek "$L_KAV" "${kok}${yol}"
        dogrula "$etiket: /$yol normal servis ediliyor (HTTP 200)" esit "$HTTP_KOD" "200"
    done
    # Dalga 2: ortak sayfa parçaları yalnızca include edilir; statik css/js sunulur.
    if [[ -d "$L_KOPYA/view/ortak" ]]; then
        for yol in view/ortak/cekirdek.php view/ortak/ust.php view/ortak/alt.php view/ortak/metinler.php \
                   view/ortak/gorev_karti.php view/ortak/gorev_islemleri.php view/Ortak/cekirdek.php view/ortak/; do
            istek "$L_KAV" "${kok}${yol}"
            dogrula "$etiket: /$yol 404 dönüyor (parça doğrudan çağrılamaz)" \
                bash -c '[[ "$0" == "404" && "$1" != *"<html"* && "$1" != *"<?php"* ]]' "$HTTP_KOD" "$HTTP_GOVDE"
        done
        for yol in view/statik/charmoji.css view/statik/charmoji.js; do
            istek "$L_KAV" "${kok}${yol}"
            dogrula "$etiket: /$yol normal servis ediliyor (HTTP 200)" esit "$HTTP_KOD" "200"
        done
    fi
}
gizli_yol_denetimi "$L_KOK" "kök"
gizli_yol_denetimi "$L_ALT" "alt dizin"
