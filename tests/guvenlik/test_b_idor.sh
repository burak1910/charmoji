# tests/guvenlik/test_b_idor.sh  (calistir.sh tarafından source edilir)
# b) Arkadaşlık IDOR: B, A ile C arasındaki kaydı silemez/kabul edemez; meşru işlemler çalışır.

bolum "b) Arkadaşlık kayıtlarında IDOR"

declare -A KAV JET UID_
for k in A B C D; do
    KAV[$k]=$(yeni_kavanoz "b_$k")
    kayit_ol "${KAV[$k]}" "idor_$k" "idor_$k@test.local" "Sifre123!"
    UID_[$k]=$(kullanici_id "idor_$k@test.local")
    JET[$k]=$(sayfa_jetonu "${KAV[$k]}")
done
kanit "A=${UID_[A]} B=${UID_[B]} C=${UID_[C]} D=${UID_[D]}"
# Not: resmi şemada çift başına tek kayıt vardır (UQ_Ark_Cift). Bekleyen istek A-C çiftindedir;
# kabul edilmiş arkadaşlık için ayrı bir çift (A-D) kullanılır. B her ikisinde de üçüncü kişidir.

# arkadas_islemi KISI alan=deger
arkadas_islemi() {
    istek "${KAV[$1]}" "${BASE}service/friend_action.php" -d "csrf_token=${JET[$1]}" -d "$2"
}
kayit_durumu() {
    local d; d=$(sql "SELECT Durum FROM Arkadasliklar WHERE ArkadaslikID = $1")
    printf '%s' "${d:-SILINDI}"
}
istek_ekle() { # GONDEREN ALAN DURUM -> yeni ArkadaslikID
    sql "INSERT INTO Arkadasliklar (IstekGonderenID, IstekAlanID, Durum) VALUES ($1, $2, $3); SELECT LAST_INSERT_ID();"
}

# A, uygulama üzerinden C'ye istek gönderir (meşru)
arkadas_islemi A "action=send_request&target_id=${UID_[C]}"
BEKLEYEN=$(sql "SELECT ArkadaslikID FROM Arkadasliklar WHERE IstekGonderenID=${UID_[A]} AND IstekAlanID=${UID_[C]}")
kanit "A -> C bekleyen istek id=$BEKLEYEN"
dogrula "meşru istek gönderme çalışıyor" dolu "$BEKLEYEN"
[[ -z "$BEKLEYEN" ]] && BEKLEYEN=$(istek_ekle "${UID_[A]}" "${UID_[C]}" 0)

# Saldırı 1: B, A->C bekleyen isteğini reddetmeye çalışır
arkadas_islemi B "reject_request_id=$BEKLEYEN"
D=$(kayit_durumu "$BEKLEYEN"); kanit "B reject sonrası kayıt durumu: $D"
dogrula "B, A->C bekleyen isteğini reddedemedi (kayıt duruyor)" esit "$D" "0"
[[ "$D" == "SILINDI" ]] && BEKLEYEN=$(istek_ekle "${UID_[A]}" "${UID_[C]}" 0)

# Saldırı 2: B, A->C isteğini kabul etmeye çalışır
arkadas_islemi B "accept_request_id=$BEKLEYEN"
D=$(kayit_durumu "$BEKLEYEN"); kanit "B accept sonrası kayıt durumu: $D"
dogrula "B, A->C isteğini kabul edemedi (Durum hâlâ 0)" esit "$D" "0"

# Saldırı 3: B, A ile D arasındaki KABUL EDİLMİŞ arkadaşlığı silmeye çalışır
ARKADAS=$(istek_ekle "${UID_[A]}" "${UID_[D]}" 1)
arkadas_islemi B "reject_request_id=$ARKADAS"
D=$(kayit_durumu "$ARKADAS"); kanit "B reject_request_id (arkadaşlık) sonrası: $D"
dogrula "B, A-D arkadaşlığını reject_request_id ile silemedi" esit "$D" "1"
[[ "$D" == "SILINDI" ]] && ARKADAS=$(istek_ekle "${UID_[A]}" "${UID_[D]}" 1)
arkadas_islemi B "remove_friend_id=$ARKADAS"
D=$(kayit_durumu "$ARKADAS"); kanit "B remove_friend_id sonrası: $D"
dogrula "B, A-D arkadaşlığını remove_friend_id ile silemedi" esit "$D" "1"
[[ "$D" == "SILINDI" ]] && ARKADAS=$(istek_ekle "${UID_[A]}" "${UID_[D]}" 1)

# Saldırı 4: İsteği GÖNDEREN (A) kendi gönderdiği isteği "kabul" edemez
arkadas_islemi A "accept_request_id=$BEKLEYEN"
D=$(kayit_durumu "$BEKLEYEN")
dogrula "gönderen (A) kendi isteğini kabul edemedi" esit "$D" "0"

# Meşru: C (alıcı) bekleyen isteği reddeder
arkadas_islemi C "reject_request_id=$BEKLEYEN"
D=$(kayit_durumu "$BEKLEYEN"); kanit "C reject sonrası: $D"
dogrula "meşru reddetme (alıcı C, bekleyen istek) çalışıyor" esit "$D" "SILINDI"

# Meşru: C (alıcı) yeni bir bekleyen isteği kabul eder
YENI=$(istek_ekle "${UID_[A]}" "${UID_[C]}" 0)
arkadas_islemi C "accept_request_id=$YENI"
D=$(kayit_durumu "$YENI"); kanit "C accept sonrası: $D"
dogrula "meşru kabul (alıcı C) çalışıyor" esit "$D" "1"
sql "DELETE FROM Arkadasliklar WHERE ArkadaslikID = $YENI"

# Meşru: taraflardan biri (A) arkadaşlığı siler
arkadas_islemi A "remove_friend_id=$ARKADAS"
D=$(kayit_durumu "$ARKADAS"); kanit "A remove_friend_id sonrası: $D"
dogrula "meşru arkadaş silme (taraf A) çalışıyor" esit "$D" "SILINDI"

# FriendManager dönüş değerleri (birim; CLI)
if grep -q "function rejectRequest" "$UYGULAMA/service/FriendManager.php" 2>/dev/null; then
    BIRIM=$(CHARMOJI_ENV_FILE="$TEST_ENV" php "$TEST_DIZINI/arkadas_birim.php" "$UYGULAMA" "${UID_[A]}" "${UID_[B]}" "${UID_[C]}" "${UID_[D]}" 2>&1)
    while IFS= read -r satir; do
        case "$satir" in
            "[GEÇTİ] "*) dogrula "FriendManager: ${satir#\[GEÇTİ\] }" true ;;
            "[KALDI] "*) dogrula "FriendManager: ${satir#\[KALDI\] }" false ;;
            *) kanit "$satir" ;;
        esac
    done <<< "$BIRIM"
else
    dogrula "FriendManager: rejectRequest/removeFriend sahiplik kontrollü ayrı metotlar mevcut" false
fi
