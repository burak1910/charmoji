# tests/guvenlik/test_d_oturum.sh  (calistir.sh tarafından source edilir)
# d) Oturum: giriş öncesi/sonrası oturum kimliği farklı; çerez HttpOnly ve SameSite taşır.

bolum "d) Oturum güvenliği"

D_KAV=$(yeni_kavanoz d_kayit)
kayit_ol "$D_KAV" "oturum_d" "oturum_d@test.local" "Sifre123!"

cerez_degeri() { awk '$6 == "PHPSESSID" { print $7 }' "$1" | tail -n1; }

# 1. Giriş öncesi ve sonrası kimlik
K=$(yeni_kavanoz d_giris)
BASLIK_ONCE=$(curl -sS -b "$K" -c "$K" -D - -o "$GECICI/d_login.html" "${BASE}${LOGIN_YOLU}")
ONCE=$(cerez_degeri "$K")
JETON=$(grep -o 'name="csrf_token" value="[0-9a-f]*"' "$GECICI/d_login.html" | grep -o '[0-9a-f]\{64\}')
BASLIK_SONRA=$(curl -sS -b "$K" -c "$K" -D - -o /dev/null --data-urlencode "username=oturum_d@test.local" \
    --data-urlencode "password=Sifre123!" --data-urlencode "csrf_token=$JETON" "${BASE}service/auth.php")
SONRA=$(cerez_degeri "$K")
kanit "giriş öncesi: $ONCE"
kanit "giriş sonrası: $SONRA"
dogrula "giriş öncesi ve sonrası oturum kimliği farklı" farkli "$ONCE" "$SONRA"
oturum_acik_mi "$K"; ACIK=$?
dogrula "yeni kimlikle oturum açık" esit "$ACIK" "0"

ESKI_KAV="$GECICI/cerez_d_eski.txt"
printf '127.0.0.1\tFALSE\t/\tFALSE\t0\tPHPSESSID\t%s\n' "$ONCE" > "$ESKI_KAV"
oturum_acik_mi "$ESKI_KAV"; ACIK=$?
dogrula "giriş öncesi (eski) kimlikle oturuma erişilemiyor" esit "$ACIK" "1"

# 2. Çerez bayrakları
SETCOOKIE=$(printf '%s\n%s\n' "$BASLIK_ONCE" "$BASLIK_SONRA" | grep -i '^Set-Cookie: PHPSESSID' | tr -d '\r')
kanit "${SETCOOKIE//$'\n'/ | }"
dolu "$SETCOOKIE" || SETCOOKIE="(Set-Cookie yok)"
dogrula "oturum çerezi HttpOnly taşıyor" bash -c 'grep -qi "httponly" <<< "$0" && ! grep -vqi "httponly" <<< "$0"' "$SETCOOKIE"
dogrula "oturum çerezi SameSite taşıyor" bash -c 'grep -qi "samesite=\(lax\|strict\)" <<< "$0" && ! grep -vqi "samesite" <<< "$0"' "$SETCOOKIE"

# 3. Oturum sabitleme: saldırganın uydurduğu kimlik kabul edilmiyor (strict mode)
SALDIRGAN="saldirgan$(date +%s%N)abc"
F="$GECICI/cerez_d_sabit.txt"
printf '127.0.0.1\tFALSE\t/\tFALSE\t0\tPHPSESSID\t%s\n' "$SALDIRGAN" > "$F"
FJETON=$(jeton_cek "$F" "${BASE}${LOGIN_YOLU}")
istek "$F" "${BASE}service/auth.php" --data-urlencode "username=oturum_d@test.local" \
    --data-urlencode "password=Sifre123!" --data-urlencode "csrf_token=$FJETON"
FSONRA=$(cerez_degeri "$F")
kanit "saldırgan kimliği: $SALDIRGAN, giriş sonrası: $FSONRA"
dogrula "saldırganın verdiği kimlik girişten sonra kullanılmıyor" farkli "$FSONRA" "$SALDIRGAN"
SK="$GECICI/cerez_d_saldirgan.txt"
printf '127.0.0.1\tFALSE\t/\tFALSE\t0\tPHPSESSID\t%s\n' "$SALDIRGAN" > "$SK"
oturum_acik_mi "$SK"; ACIK=$?
dogrula "saldırgan kendi bildiği kimlikle kurbanın oturumuna giremiyor" esit "$ACIK" "1"
