# tests/guvenlik/test_j_kimlik.sh  (calistir.sh tarafından source edilir)
# j) Giriş/kayıt kimliği: B-1 (başkasının e-postasıyla girişi engelleme, yalnızca e-posta ile giriş,
#    e-posta biçimi) ve B-3 (kayıtta çift kaçış: kesme işaretli ad/e-posta).

bolum "j) Giriş yalnızca e-postayla, e-posta doğrulama, kesme işaretli kayıt"

# --- B-1: Ad = kurbanın e-postası olan saldırgan kurbanı kilitleyemez
J_KURBAN="kurban_j@test.local"
kayit_ol "$(yeni_kavanoz j_kurban_kayit)" "Kurban Kisi" "$J_KURBAN" "Sifre123!"
kayit_ol "$(yeni_kavanoz j_saldirgan_kayit)" "$J_KURBAN" "saldirgan_j@test.local" "Sifre123!"
kanit "kullanıcılar: $(sql "SELECT COUNT(*) FROM Kullanicilar WHERE Ad = '$J_KURBAN' OR Eposta = '$J_KURBAN'") satır (Ad veya Eposta = kurban e-postası)"

JK=$(yeni_kavanoz j_kurban_giris)
giris_yap "$JK" "$J_KURBAN" "Sifre123!"
oturum_acik_mi "$JK"; ACIK=$?
kanit "kurban e-postasıyla giriş sonrası: $HTTP_SON_URL (HTTP $HTTP_KOD)"
dogrula "B-1: saldırgan Ad=<kurban e-postası> ile kayıt olsa da kurban e-postasıyla giriş yapabiliyor" esit "$ACIK" "0"
GIRIS_EPOSTA=$(sql "SELECT Eposta FROM Kullanicilar WHERE Ad = 'Kurban Kisi'")
dogrula "B-1: kurban hesabı yerinde (Ad 'Kurban Kisi' -> $J_KURBAN)" esit "$GIRIS_EPOSTA" "$J_KURBAN"

JA=$(yeni_kavanoz j_ad_ile)
giris_yap "$JA" "Kurban Kisi" "Sifre123!"
oturum_acik_mi "$JA"; ACIK=$?
dogrula "B-1: ad (kullanıcı adı) ile giriş artık mümkün değil" esit "$ACIK" "1"

# --- B-1: geçersiz e-posta kayıtta reddedilir
for GECERSIZ in "gecersiz-eposta" "a@b" "bosluk var@test.local" "x@@test.local"; do
    kayit_ol "$(yeni_kavanoz j_gecersiz)" "Gecersiz Kisi" "$GECERSIZ" "Sifre123!"
    SAYI=$(sql "SELECT COUNT(*) FROM Kullanicilar WHERE Ad = 'Gecersiz Kisi'")
    dogrula "B-1: geçersiz e-posta '$GECERSIZ' ile kayıt reddedildi" esit "$SAYI" "0"
done
JG=$(yeni_kavanoz j_gecersiz_oturum)
kayit_ol "$JG" "Gecersiz Kisi" "gecersiz-eposta" "Sifre123!"
oturum_acik_mi "$JG"; ACIK=$?
dogrula "B-1: geçersiz e-postayla kayıt sonrası oturum açılmadı" esit "$ACIK" "1"
kayit_ol "$(yeni_kavanoz j_gecerli)" "Gecerli Kisi" "gecerli_j@test.local" "Sifre123!"
dogrula "B-1: geçerli e-postayla kayıt çalışıyor" dolu "$(kullanici_id gecerli_j@test.local)"

# --- B-3: kesme işaretli ad ve e-posta çift kaçışlanmıyor
OB_AD="O'Brien"
OB_EPOSTA="o'brien@ornek.test"
JO=$(yeni_kavanoz j_obrien)
kayit_ol "$JO" "$OB_AD" "$OB_EPOSTA" "Sifre123!"
OB_AD_DB=$(sql "SELECT Ad FROM Kullanicilar WHERE Eposta LIKE '%brien@ornek.test'")
OB_EP_DB=$(sql "SELECT Eposta FROM Kullanicilar WHERE Eposta LIKE '%brien@ornek.test'")
kanit "veritabanı: Ad=[$OB_AD_DB] Eposta=[$OB_EP_DB]"
dogrula "B-3: veritabanındaki ad ters eğik çizgisiz ($OB_AD)" esit "$OB_AD_DB" "$OB_AD"
dogrula "B-3: veritabanındaki e-posta ters eğik çizgisiz ($OB_EPOSTA)" esit "$OB_EP_DB" "$OB_EPOSTA"
oturum_acik_mi "$JO"; ACIK=$?
kanit "kayıt sonrası: $HTTP_SON_URL (HTTP $HTTP_KOD)"
dogrula "B-3: kayıt sonrası otomatik giriş çalışıyor (userpage.php açık)" esit "$ACIK" "0"
JO2=$(yeni_kavanoz j_obrien_elle)
giris_yap "$JO2" "$OB_EPOSTA" "Sifre123!"
oturum_acik_mi "$JO2"; ACIK=$?
kanit "elle giriş sonrası: $HTTP_SON_URL (HTTP $HTTP_KOD)"
dogrula "B-3: o'brien@ornek.test ile elle giriş çalışıyor" esit "$ACIK" "0"
