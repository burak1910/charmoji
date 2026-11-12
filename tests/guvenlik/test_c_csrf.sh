# tests/guvenlik/test_c_csrf.sh  (calistir.sh tarafından source edilir)
# c) CSRF: her form ve AJAX ucu jetonsuz/yanlış jetonlu POST'u reddeder, doğru jetonla çalışır.

bolum "c) CSRF koruması"

YANLIS_JETON="0000000000000000000000000000000000000000000000000000000000000000"

# --- auth.php: kayıt
K=$(yeni_kavanoz c_kayit)
jeton_cek "$K" "${BASE}${REGISTER_YOLU}" > /dev/null   # oturum çerezi al
kayit_dene() { # jeton eposta
    istek "$K" "${BASE}service/auth.php" -d "fullname=csrf_k" --data-urlencode "email=$2" \
        -d "password=Sifre123!" -d "confirm_password=Sifre123!" ${1:+-d "csrf_token=$1"}
}
kayit_dene "" "csrf_k1@test.local"
dogrula "kayıt: jetonsuz POST reddedildi (kullanıcı oluşmadı, HTTP $HTTP_KOD)" bos "$(kullanici_id csrf_k1@test.local)"
kayit_dene "$YANLIS_JETON" "csrf_k2@test.local"
dogrula "kayıt: yanlış jetonlu POST reddedildi (HTTP $HTTP_KOD)" bos "$(kullanici_id csrf_k2@test.local)"
kayit_ol "$K" "csrf_k" "csrf_k3@test.local" "Sifre123!"
dogrula "kayıt: doğru jetonla çalışıyor" dolu "$(kullanici_id csrf_k3@test.local)"

# --- auth.php: giriş
G=$(yeni_kavanoz c_giris)
jeton_cek "$G" "${BASE}${LOGIN_YOLU}" > /dev/null
istek "$G" "${BASE}service/auth.php" -d "username=csrf_k3@test.local" -d "password=Sifre123!"
KOD=$HTTP_KOD
oturum_acik_mi "$G"; ACIK=$?
dogrula "giriş: jetonsuz POST ile oturum açılmadı (HTTP $KOD)" esit "$ACIK" "1"
istek "$G" "${BASE}service/auth.php" -d "username=csrf_k3@test.local" -d "password=Sifre123!" -d "csrf_token=$YANLIS_JETON"
KOD=$HTTP_KOD
oturum_acik_mi "$G"; ACIK=$?
dogrula "giriş: yanlış jetonlu POST ile oturum açılmadı (HTTP $KOD)" esit "$ACIK" "1"
giris_yap "$G" "csrf_k3@test.local" "Sifre123!"
oturum_acik_mi "$G"; ACIK=$?
dogrula "giriş: doğru jetonla oturum açıldı" esit "$ACIK" "0"

# Bundan sonrası için giriş yapmış kullanıcı (G) ve hedef kullanıcı
U_ID=$(kullanici_id csrf_k3@test.local)
J=$(sayfa_jetonu "$G")
H=$(yeni_kavanoz c_hedef)
kayit_ol "$H" "csrf_hedef" "csrf_hedef@test.local" "Sifre123!"
H_ID=$(kullanici_id csrf_hedef@test.local)
# Resmi şemada çift başına tek Arkadasliklar kaydı vardır (UQ_Ark_Cift): her fikstür farklı kişiyle kurulur.
H2=$(yeni_kavanoz c_hedef2); kayit_ol "$H2" "csrf_hedef2" "csrf_hedef2@test.local" "Sifre123!"
H2_ID=$(kullanici_id csrf_hedef2@test.local)
H3=$(yeni_kavanoz c_hedef3); kayit_ol "$H3" "csrf_hedef3" "csrf_hedef3@test.local" "Sifre123!"
H3_ID=$(kullanici_id csrf_hedef3@test.local)
# Satın alma sınaması için kullanıcıya bakiye ver (seed'de eşya fiyatları 100-500 altın)
sql "UPDATE Kullanicilar SET Bakiye = 1000 WHERE KullaniciID = $U_ID"

# uc_dene ADI DURUM_SORGUSU ISTEK_FONKSIYONU
# İstek fonksiyonu: $1 = jeton ("" ise gönderilmez)
uc_dene() {
    local ad="$1" sorgu="$2" fn="$3" once sonra
    once=$(sql "$sorgu")
    "$fn" ""
    sonra=$(sql "$sorgu")
    dogrula "$ad: jetonsuz POST reddedildi (HTTP $HTTP_KOD, durum değişmedi: $once)" esit "$sonra" "$once"
    "$fn" "$YANLIS_JETON"
    sonra=$(sql "$sorgu")
    dogrula "$ad: yanlış jetonlu POST reddedildi (HTTP $HTTP_KOD, durum değişmedi: $once)" esit "$sonra" "$once"
    "$fn" "$J"
    sonra=$(sql "$sorgu")
    dogrula "$ad: doğru jetonla çalışıyor (durum $once -> $sonra)" farkli "$sonra" "$once"
}
form_post() { # jeton url veri...
    local jeton="$1" url="$2"; shift 2
    istek "$G" "$url" "$@" ${jeton:+-d "csrf_token=$jeton"}
}
ajax_post() { # jeton veri...
    local jeton="$1"; shift
    istek "$G" "${BASE}view/ajax_islem.php" ${jeton:+-H "X-CSRF-Token: $jeton"} "$@"
}

# --- friend_action.php
f_istek()   { form_post "$1" "${BASE}service/friend_action.php" -d "action=send_request" -d "target_id=$H_ID"; }
uc_dene "friend_action (istek gönder)" "SELECT COUNT(*) FROM Arkadasliklar WHERE IstekGonderenID=$U_ID" f_istek
GELEN=$(sql "INSERT INTO Arkadasliklar (IstekGonderenID, IstekAlanID, Durum) VALUES ($H2_ID, $U_ID, 0); SELECT LAST_INSERT_ID();")
f_kabul()   { form_post "$1" "${BASE}service/friend_action.php" -d "accept_request_id=$GELEN"; }
uc_dene "friend_action (kabul)" "SELECT Durum FROM Arkadasliklar WHERE ArkadaslikID=$GELEN" f_kabul
GELEN2=$(sql "INSERT INTO Arkadasliklar (IstekGonderenID, IstekAlanID, Durum) VALUES ($H3_ID, $U_ID, 0); SELECT LAST_INSERT_ID();")
f_red()     { form_post "$1" "${BASE}service/friend_action.php" -d "reject_request_id=$GELEN2"; }
uc_dene "friend_action (red)" "SELECT COUNT(*) FROM Arkadasliklar WHERE ArkadaslikID=$GELEN2" f_red
f_sil()     { form_post "$1" "${BASE}service/friend_action.php" -d "remove_friend_id=$GELEN"; }
uc_dene "friend_action (arkadaş sil)" "SELECT COUNT(*) FROM Arkadasliklar WHERE ArkadaslikID=$GELEN" f_sil

# --- görev (alışkanlık) ekleme formu
h_ekle()    { form_post "$1" "${BASE}${GOREV_YOLU}" -d "add_habit_btn=1" -d "habit_name=csrf_aliskanlik" -d "habit_stat=Zeka"; }
uc_dene "$GOREV_YOLU (görev ekle)" "SELECT COUNT(*) FROM KullaniciAliskanliklari WHERE KullaniciID=$U_ID" h_ekle
KA_ID=$(sql "SELECT KullaniciAliskanlikID FROM KullaniciAliskanliklari WHERE KullaniciID=$U_ID LIMIT 1")

# --- ajax_islem.php uçları
if [[ "$MOD" == "yeni" ]]; then
    a_renk() { ajax_post "$1" --data-urlencode "save_avatar_options=1" --data-urlencode 'secenekler={"ten":3,"sac":2}'; }
    uc_dene "ajax save_avatar_options" "SELECT COALESCE(AvatarVerisi,'NULL') FROM Kullanicilar WHERE KullaniciID=$U_ID" a_renk
else
    a_renk() { ajax_post "$1" --data-urlencode "save_avatar_colors=1" --data-urlencode 'colors={"skinColor":"#123456"}'; }
    uc_dene "ajax save_avatar_colors" "SELECT COALESCE(AvatarVerisi,'NULL') FROM Kullanicilar WHERE KullaniciID=$U_ID" a_renk
fi
a_al()      { ajax_post "$1" -d "buy_item_id=1"; }
uc_dene "ajax buy_item_id" "SELECT COUNT(*) FROM KullaniciEnvanteri WHERE KullaniciID=$U_ID" a_al
a_kusan()   { ajax_post "$1" -d "equip_item_id=1"; }
uc_dene "ajax equip_item_id" "SELECT COALESCE(SUM(KusandiMi),0) FROM KullaniciEnvanteri WHERE KullaniciID=$U_ID" a_kusan
a_isaret()  { ajax_post "$1" -d "ka_id=$KA_ID" -d "durum=1"; }
uc_dene "ajax alışkanlık işaretleme" "SELECT COUNT(*) FROM TakipKayitlari WHERE KullaniciAliskanlikID=$KA_ID" a_isaret
GOREV=$(sql "SELECT KG.KullaniciGorevID FROM KullaniciGunlukGorevleri KG JOIN GorevTanimlari GT ON GT.GorevTanimID=KG.GorevTanimID WHERE KG.KullaniciID=$U_ID AND GT.Baslik='Günlük Giriş' LIMIT 1")
a_odul()    { ajax_post "$1" -d "claim_quest_id=$GOREV"; }
uc_dene "ajax claim_quest_id" "SELECT OdulAlindiMi FROM KullaniciGunlukGorevleri WHERE KullaniciGorevID=${GOREV:-0}" a_odul
a_sil()     { ajax_post "$1" -d "ka_id=$KA_ID" -d "is_delete=1"; }
uc_dene "ajax is_delete" "SELECT COUNT(*) FROM KullaniciAliskanliklari WHERE KullaniciAliskanlikID=$KA_ID" a_sil

# --- logout.php
istek "$G" "${BASE}service/logout.php"
KOD=$HTTP_KOD; oturum_acik_mi "$G"; ACIK=$?
dogrula "logout: GET ile çıkış yapılamıyor (HTTP $KOD, oturum açık)" esit "$ACIK" "0"
istek "$G" "${BASE}service/logout.php" -X POST
KOD=$HTTP_KOD; oturum_acik_mi "$G"; ACIK=$?
dogrula "logout: jetonsuz POST reddedildi (HTTP $KOD, oturum açık)" esit "$ACIK" "0"
istek "$G" "${BASE}service/logout.php" -d "csrf_token=$YANLIS_JETON"
KOD=$HTTP_KOD; oturum_acik_mi "$G"; ACIK=$?
dogrula "logout: yanlış jetonlu POST reddedildi (HTTP $KOD, oturum açık)" esit "$ACIK" "0"
J=$(sayfa_jetonu "$G")
istek "$G" "${BASE}service/logout.php" -d "csrf_token=$J"
oturum_acik_mi "$G"; ACIK=$?
dogrula "logout: doğru jetonlu POST ile çıkış yapıldı" esit "$ACIK" "1"

# --- Dalga 2: friend_action.php JSON kipi (Lonca sekmesi AJAX ile çağırır) aynı CSRF denetiminden geçer
if [[ "$MOD" == "yeni" ]]; then
    G2=$(yeni_kavanoz c_json); kayit_ol "$G2" "csrf_json" "csrf_json@test.local" "Sifre123!"
    G2_ID=$(kullanici_id csrf_json@test.local); J2=$(sayfa_jetonu "$G2")
    H4=$(yeni_kavanoz c_hedef4); kayit_ol "$H4" "csrf_hedef4" "csrf_hedef4@test.local" "Sifre123!"
    H4_ID=$(kullanici_id csrf_hedef4@test.local)
    json_istek() { # jeton
        istek "$G2" "${BASE}service/friend_action.php" -H "Accept: application/json" ${1:+-H "X-CSRF-Token: $1"} \
            -d "action=send_request" -d "target_id=$H4_ID"
    }
    SAY="SELECT COUNT(*) FROM Arkadasliklar WHERE IstekGonderenID=$G2_ID"
    json_istek ""
    dogrula "friend_action JSON kipi: jetonsuz istek 403 + JSON ile reddedildi, kayıt yok" \
        bash -c '[[ "$0" == "403" && "$1" == *"\"success\":false"* && "$2" == "0" ]]' "$HTTP_KOD" "$HTTP_GOVDE" "$(sql "$SAY")"
    json_istek "$YANLIS_JETON"
    dogrula "friend_action JSON kipi: yanlış jetonla reddedildi (HTTP $HTTP_KOD)" esit "$(sql "$SAY")" "0"
    json_istek "$J2"
    dogrula "friend_action JSON kipi: doğru jetonla çalışıyor ({success:true})" \
        bash -c '[[ "$0" == *"\"success\":true"* && "$1" == "1" ]]' "$HTTP_GOVDE" "$(sql "$SAY")"
    # Oturumsuz JSON isteği: 401 + oturum_kapali (arayüz "Oturumun sona erdi" gösterir)
    istek "$(yeni_kavanoz c_json_oturumsuz)" "${BASE}service/friend_action.php" -H "Accept: application/json" -d "action=send_request" -d "target_id=$H4_ID"
    dogrula "friend_action JSON kipi: oturumsuz istek 401 + oturum_kapali" \
        bash -c '[[ "$0" == "401" && "$1" == *"oturum_kapali"* ]]' "$HTTP_KOD" "$HTTP_GOVDE"
    istek "$(yeni_kavanoz c_ajax_oturumsuz)" "${BASE}view/ajax_islem.php" -d "buy_item_id=1"
    dogrula "ajax_islem.php: oturumsuz istek 401 + oturum_kapali" \
        bash -c '[[ "$0" == "401" && "$1" == *"oturum_kapali"* ]]' "$HTTP_KOD" "$HTTP_GOVDE"
fi
