# tests/guvenlik/test_n_gorevler.sh  (calistir.sh tarafından source edilir)
# n) Görev kuralları (Dalga 2 / Aşama 2; docs/ux/03-gorevler.md, 09 S7-S10, S17-S19, S25):
#    kullanıcıya özel görevler, ad benzersizliği, 60 karakter, 20 görev sınırı, düzenleme,
#    silme = arşivleme + geri getirme, sahiplik, tamamla -> geri al -> tamamla serisi,
#    meydan okuma ilerlemesinin türe göre sayılması, yeni uçlarda CSRF.

bolum "n) Görev kuralları"

if [[ "$MOD" != "yeni" ]]; then
    atla "görev kuralları yalnızca yeni kod için (eski kodda bu uçlar yok)"
    return 0 2>/dev/null || true
fi

# Oyun günü (S1): Europe/Istanbul; uygulama ile aynı tarih (veritabanının CURDATE()'i değil).
N_BUGUN=$(php -r 'date_default_timezone_set("Europe/Istanbul"); echo date("Y-m-d");')
N_DUN=$(php -r 'date_default_timezone_set("Europe/Istanbul"); echo date("Y-m-d", strtotime("-1 day"));')
N_ONCEKI=$(php -r 'date_default_timezone_set("Europe/Istanbul"); echo date("Y-m-d", strtotime("-3 day"));')

declare -A NK NJ NU
for k in A B C D; do
    NK[$k]=$(yeni_kavanoz "n_$k")
    kayit_ol "${NK[$k]}" "gorev_n_$k" "gorev_n_$k@test.local" "Sifre123!"
    NU[$k]=$(kullanici_id "gorev_n_$k@test.local")
    NJ[$k]=$(sayfa_jetonu "${NK[$k]}")
done
kanit "A=${NU[A]} B=${NU[B]} C=${NU[C]} D=${NU[D]}  bugün=$N_BUGUN"

n_basari() { printf '%s' "$HTTP_GOVDE" | grep -o '"success":[a-z]*' | head -n1 | cut -d: -f2; }
n_kod()    { printf '%s' "$HTTP_GOVDE" | grep -o '"kod":"[a-z_]*"' | head -n1 | cut -d'"' -f4; }
# n_ekle K AD YETENEK  (AJAX ile görev ekler)
n_ekle()   { ajax "${NK[$1]}" "${NJ[$1]}" -d "gorev_ekle=1" --data-urlencode "habit_name=$2" --data-urlencode "habit_stat=$3"; }
n_id()     { sql "SELECT KullaniciAliskanlikID FROM KullaniciAliskanliklari WHERE KullaniciID=${NU[$1]} AND Ad='$2' AND AktifMi=1"; }
n_puan()   { sql "SELECT ToplamPuan FROM KullaniciStatlari WHERE KullaniciID=${NU[$1]} AND StatTuru='$2'"; }
n_aktif()  { sql "SELECT COUNT(*) FROM KullaniciAliskanliklari WHERE KullaniciID=${NU[$1]} AND AktifMi=1"; }
n_tamamla(){ ajax "${NK[$1]}" "${NJ[$1]}" -d "ka_id=$2" -d "durum=$3" -d "gun=$N_BUGUN"; }

# ------------------------------------------------------------------ ekleme
n_ekle A "Kitap Oku" "Zeka"
dogrula "ekle: AJAX görev ekleme success:true" esit "$(n_basari)" "true"
N_A1=$(n_id A "Kitap Oku")
dogrula "ekle: görev kullanıcıya özel sütunlara yazıldı (Ad, StatTuru, Puan, BaslangicTarihi)" \
    esit "$(sql "SELECT CONCAT(Ad,'|',StatTuru,'|',Puan,'|',BaslangicTarihi) FROM KullaniciAliskanliklari WHERE KullaniciAliskanlikID=${N_A1:-0}")" "Kitap Oku|Zeka|10|$N_BUGUN"
# Eski ortak tablo kullanılmadı: AliskanlikID boş (araclar/004 sonrası sütun hiç yoktur).
if [[ "$(sql "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND LOWER(TABLE_NAME)='kullanicialiskanliklari' AND COLUMN_NAME='AliskanlikID'")" == "1" ]]; then
    dogrula "ekle: eski ortak tablo kullanılmadı (AliskanlikID NULL, Aliskanliklar'a satır eklenmedi)" \
        esit "$(sql "SELECT CONCAT(IFNULL(AliskanlikID,'NULL'),'|',(SELECT COUNT(*) FROM Aliskanliklar WHERE Ad='Kitap Oku')) FROM KullaniciAliskanliklari WHERE KullaniciAliskanlikID=${N_A1:-0}")" "NULL|0"
fi
dogrula "ekle: yanıtta sunucuda çizilmiş kart HTML'i var" icerir "$HTTP_GOVDE" "gorev-karti"

# Ad kuralları (S17): kırpma, boş, 60 karakter (çok baytlı harflerle)
n_ekle A "   Su İç   " "Guc"
dogrula "ad: baştaki/sondaki boşluk kırpılır" dolu "$(n_id A "Su İç")"
n_ekle A "      " "Guc"
dogrula "ad: yalnızca boşluk -> ad_bos" esit "$(n_kod)" "ad_bos"
ALTMIS=$(printf 'ş%.0s' $(seq 1 60)); ALTMISBIR="${ALTMIS}ş"
n_ekle A "$ALTMISBIR" "Guc"
dogrula "ad: 61 karakter -> ad_uzun, eklenmedi" \
    bash -c '[[ "$0" == "ad_uzun" && "$1" == "0" ]]' "$(n_kod)" "$(sql "SELECT COUNT(*) FROM KullaniciAliskanliklari WHERE KullaniciID=${NU[A]} AND CHAR_LENGTH(Ad) > 60")"
n_ekle A "$ALTMIS" "Guc"
dogrula "ad: tam 60 karakter (çok baytlı) kabul edildi" esit "$(n_basari)" "true"
n_ekle A "Geçersiz Yetenek" "Büyü"
dogrula "yetenek: geçersiz yetenek -> yetenek_yok" esit "$(n_kod)" "yetenek_yok"

# Ad benzersizliği (S9): aktif görevler arasında, Türkçe büyük/küçük harf duyarsız
n_ekle A "KİTAP OKU" "Guc"
dogrula "benzersiz: 'KİTAP OKU' ('Kitap Oku' varken) -> ad_ayni" esit "$(n_kod)" "ad_ayni"
istek "${NK[A]}" "${BASE}${GOREV_YOLU}" -d "add_habit_btn=1" --data-urlencode "habit_name=kitap oku" -d "habit_stat=Guc" -d "csrf_token=${NJ[A]}"
dogrula "benzersiz: form (betiksiz) yolu da aynı adı reddediyor" \
    esit "$(sql "SELECT COUNT(*) FROM KullaniciAliskanliklari WHERE KullaniciID=${NU[A]} AND Ad='Kitap Oku'")" "1"
n_ekle A "Şınav Çek" "Guc"; n_ekle A "Sınav Çek" "Zeka"
dogrula "benzersiz: Türkçe harfler ayrı sayılır ('Şınav Çek' ve 'Sınav Çek' ikisi de var)" \
    esit "$(sql "SELECT COUNT(*) FROM KullaniciAliskanliklari WHERE KullaniciID=${NU[A]} AND AktifMi=1 AND Ad IN ('Şınav Çek','Sınav Çek')")" "2"

# ------------------------------------------------------------------ kullanıcıya özel (eski hata)
# B "Ortak Ad"ı Güç ile önce ekler; A aynı adı Zeka ile ekler. A'nın görevi Zeka kalmalı.
n_ekle B "Ortak Ad" "Guc"
n_ekle A "Ortak Ad" "Zeka"
N_A_ORTAK=$(n_id A "Ortak Ad"); N_B_ORTAK=$(n_id B "Ortak Ad")
dogrula "kullanıcıya özel: A'nın 'Ortak Ad' görevi A'nın seçtiği yetenekte (Zeka), B'ninki Güç" \
    esit "$(sql "SELECT GROUP_CONCAT(StatTuru ORDER BY KullaniciID) FROM KullaniciAliskanliklari WHERE KullaniciAliskanlikID IN (${N_A_ORTAK:-0}, ${N_B_ORTAK:-0})")" "Zeka,Guc"
A_ZEKA=$(n_puan A Zeka); A_GUC=$(n_puan A Guc); B_GUC=$(n_puan B Guc)
n_tamamla A "$N_A_ORTAK" 1
dogrula "kullanıcıya özel: A tamamlayınca XP A'nın Zeka'sına gitti (+10), A'nın ve B'nin Güç'ü değişmedi" \
    esit "$(n_puan A Zeka),$(n_puan A Guc),$(n_puan B Guc)" "$((A_ZEKA + 10)),$A_GUC,$B_GUC"

# ------------------------------------------------------------------ sahiplik
A_SATIR() { sql "SELECT CONCAT(Ad,'|',StatTuru,'|',AktifMi,'|',Seri) FROM KullaniciAliskanliklari WHERE KullaniciAliskanlikID=$1"; }
ONCE=$(A_SATIR "$N_A1")
ajax "${NK[B]}" "${NJ[B]}" -d "gorev_duzenle=1" -d "ka_id=$N_A1" -d "habit_name=Ele Gecirildi" -d "habit_stat=Guc"
dogrula "sahiplik: B, A'nın görevini düzenleyemez (success:false, veri aynı)" \
    bash -c '[[ "$0" == "false" && "$1" == "$2" ]]' "$(n_basari)" "$(A_SATIR "$N_A1")" "$ONCE"
ajax "${NK[B]}" "${NJ[B]}" -d "is_delete=1" -d "ka_id=$N_A1"
dogrula "sahiplik: B, A'nın görevini silemez/arşivleyemez (success:false, aktif)" \
    bash -c '[[ "$0" == "false" && "$1" == "$2" ]]' "$(n_basari)" "$(A_SATIR "$N_A1")" "$ONCE"
n_tamamla B "$N_A1" 1
dogrula "sahiplik: B, A'nın görevini tamamlayamaz (success:false, takip kaydı yok)" \
    bash -c '[[ "$0" == "false" && "$1" == "0" ]]' "$(n_basari)" "$(sql "SELECT COUNT(*) FROM TakipKayitlari WHERE KullaniciAliskanlikID=$N_A1")"
n_tamamla B "$N_A_ORTAK" 0
dogrula "sahiplik: B, A'nın tamamladığı görevi geri alamaz (success:false, takip kaydı duruyor)" \
    bash -c '[[ "$0" == "false" && "$1" == "1" ]]' "$(n_basari)" "$(sql "SELECT COUNT(*) FROM TakipKayitlari WHERE KullaniciAliskanlikID=$N_A_ORTAK AND Tarih='$N_BUGUN'")"
# A kendi görevini arşivler; B geri getirmeye çalışır
ajax "${NK[A]}" "${NJ[A]}" -d "is_delete=1" -d "ka_id=$N_A_ORTAK"
dogrula "arşiv: A kendi görevini sildi (success:true)" esit "$(n_basari)" "true"
ajax "${NK[B]}" "${NJ[B]}" -d "gorev_geri_getir=1" -d "ka_id=$N_A_ORTAK"
dogrula "sahiplik: B, A'nın silinmiş görevini geri getiremez (success:false, arşivde kalır)" \
    bash -c '[[ "$0" == "false" && "$1" == "0" ]]' "$(n_basari)" "$(sql "SELECT AktifMi FROM KullaniciAliskanliklari WHERE KullaniciAliskanlikID=$N_A_ORTAK")"

# ------------------------------------------------------------------ silme = arşivleme (S18)
dogrula "arşiv: silme XP'yi değiştirmedi (A Zeka hâlâ +10), takip kaydı duruyor" \
    esit "$(n_puan A Zeka),$(sql "SELECT COUNT(*) FROM TakipKayitlari WHERE KullaniciAliskanlikID=$N_A_ORTAK")" "$((A_ZEKA + 10)),1"
ajax "${NK[A]}" "${NJ[A]}" -d "gorev_geri_getir=1" -d "ka_id=$N_A_ORTAK"
dogrula "arşiv: 'Geri al' görevi serisi ve bugünkü durumuyla geri getirdi" \
    bash -c '[[ "$0" == "true" && "$1" == "1|1|1" ]]' "$(n_basari)" \
    "$(sql "SELECT CONCAT(AktifMi,'|',Seri,'|',(SELECT COUNT(*) FROM TakipKayitlari WHERE KullaniciAliskanlikID=$N_A_ORTAK AND Tarih='$N_BUGUN')) FROM KullaniciAliskanliklari WHERE KullaniciAliskanlikID=$N_A_ORTAK")"
ajax "${NK[A]}" "${NJ[A]}" -d "is_delete=1" -d "ka_id=$N_A_ORTAK"
n_ekle A "ortak ad" "Sosyallik"
dogrula "arşiv: silinen görevin adıyla yeni görev eklenebilir (ad yalnızca aktifler arasında benzersiz)" esit "$(n_basari)" "true"
ajax "${NK[A]}" "${NJ[A]}" -d "gorev_geri_getir=1" -d "ka_id=$N_A_ORTAK"
dogrula "arşiv: aynı adlı aktif görev varken eskisi geri getirilemez (ad_ayni)" esit "$(n_kod)" "ad_ayni"
N_ESKI=$(n_id A "Su İç")
ajax "${NK[A]}" "${NJ[A]}" -d "is_delete=1" -d "ka_id=$N_ESKI"
sql "UPDATE KullaniciAliskanliklari SET ArsivZamani = ArsivZamani - INTERVAL 1 HOUR WHERE KullaniciAliskanlikID=$N_ESKI"
ajax "${NK[A]}" "${NJ[A]}" -d "gorev_geri_getir=1" -d "ka_id=$N_ESKI"
dogrula "arşiv: geri alma süresi dolunca geri getirilemez (sure_doldu, arşivde kalır)" \
    bash -c '[[ "$0" == "sure_doldu" && "$1" == "0" ]]' "$(n_kod)" "$(sql "SELECT AktifMi FROM KullaniciAliskanliklari WHERE KullaniciAliskanlikID=$N_ESKI")"
istek "${NK[A]}" "${BASE}${GOREV_YOLU}"
dogrula "arşiv: silinen görev Görevler listesinde görünmüyor" icermez "$HTTP_GOVDE" "id=\"gorev-$N_ESKI\""

# ------------------------------------------------------------------ düzenleme (S19)
N_D=$(n_id A "Kitap Oku")
sql "UPDATE KullaniciAliskanliklari SET Seri=4, SonIslemTarihi='$N_DUN' WHERE KullaniciAliskanlikID=$N_D"
A_ZEKA=$(n_puan A Zeka); A_GUC=$(n_puan A Guc)
n_tamamla A "$N_D" 1
ajax "${NK[A]}" "${NJ[A]}" -d "gorev_duzenle=1" -d "ka_id=$N_D" --data-urlencode "habit_name=Kitap Oku 30 sayfa" -d "habit_stat=Guc"
dogrula "düzenle: ad ve yetenek değişti, seri ve bugünkü tamamlama korundu" \
    bash -c '[[ "$0" == "true" && "$1" == "Kitap Oku 30 sayfa|Guc|5|1" ]]' "$(n_basari)" \
    "$(sql "SELECT CONCAT(Ad,'|',StatTuru,'|',Seri,'|',(SELECT COUNT(*) FROM TakipKayitlari WHERE KullaniciAliskanlikID=$N_D AND Tarih='$N_BUGUN')) FROM KullaniciAliskanliklari WHERE KullaniciAliskanlikID=$N_D")"
dogrula "düzenle (S19): geçmiş XP eski yetenekte kaldı (Zeka +10, Güç değişmedi)" esit "$(n_puan A Zeka),$(n_puan A Guc)" "$((A_ZEKA + 10)),$A_GUC"
n_tamamla A "$N_D" 0
dogrula "düzenle (S19): yetenek değiştikten sonra geri alma XP'yi kazanıldığı yetenekten (Zeka) düşer" \
    esit "$(n_puan A Zeka),$(n_puan A Guc)" "$A_ZEKA,$A_GUC"
n_tamamla A "$N_D" 1
dogrula "düzenle (S19): sonraki tamamlamanın XP'si yeni yeteneğe (Güç) gider" esit "$(n_puan A Zeka),$(n_puan A Guc)" "$A_ZEKA,$((A_GUC + 10))"
ajax "${NK[A]}" "${NJ[A]}" -d "gorev_duzenle=1" -d "ka_id=$N_D" --data-urlencode "habit_name=şınav çek" -d "habit_stat=Guc"
dogrula "düzenle: başka aktif görevin adına (büyük/küçük harf farkıyla) çevrilemez (ad_ayni)" esit "$(n_kod)" "ad_ayni"
ajax "${NK[A]}" "${NJ[A]}" -d "gorev_duzenle=1" -d "ka_id=$N_D" --data-urlencode "habit_name=KİTAP OKU 30 SAYFA" -d "habit_stat=Guc"
dogrula "düzenle: kendi adının büyük/küçük harf değişikliği kabul edilir" esit "$(n_basari)" "true"
ajax "${NK[A]}" "${NJ[A]}" -d "gorev_duzenle=1" -d "ka_id=$N_D" --data-urlencode "habit_name=$ALTMISBIR" -d "habit_stat=Guc"
dogrula "düzenle: 61 karakter -> ad_uzun" esit "$(n_kod)" "ad_uzun"
ajax "${NK[A]}" "${NJ[A]}" -d "gorev_duzenle=1" -d "ka_id=$N_ESKI" -d "habit_name=Arsivdeki" -d "habit_stat=Guc"
dogrula "düzenle: arşivlenmiş görev düzenlenemez (bulunamadi)" esit "$(n_kod)" "bulunamadi"

# ------------------------------------------------------------------ seri: tamamla -> geri al -> tamamla
n_ekle C "Seri Gorevi" "Disiplin"
N_S=$(n_id C "Seri Gorevi")
sql "UPDATE KullaniciAliskanliklari SET Seri=5, SonIslemTarihi='$N_DUN' WHERE KullaniciAliskanlikID=$N_S"
seri_durumu() { sql "SELECT CONCAT(Seri,'|',IFNULL(SonIslemTarihi,'NULL')) FROM KullaniciAliskanliklari WHERE KullaniciAliskanlikID=$N_S"; }
C_DIS=$(n_puan C Disiplin)
n_tamamla C "$N_S" 1
dogrula "seri: dün tamamlanan 5 günlük seri bugün 6 olur" esit "$(seri_durumu)" "6|$N_BUGUN"
n_tamamla C "$N_S" 0
dogrula "seri: geri alınca seri ve son tarih tamamlama öncesine döner (5, dün)" esit "$(seri_durumu)" "5|$N_DUN"
n_tamamla C "$N_S" 1
dogrula "seri: aynı gün yeniden tamamlanınca seri yine artar (6) [eski hata: 5'te kalıyordu]" esit "$(seri_durumu)" "6|$N_BUGUN"
dogrula "seri: XP tam bir kez kazanıldı (+10)" esit "$(n_puan C Disiplin)" "$((C_DIS + 10))"
n_tamamla C "$N_S" 1
dogrula "seri: ikinci kez tamamlama zaten_tamamlandi döner, XP değişmez" \
    bash -c '[[ "$0" == "zaten_tamamlandi" && "$1" == "$2" ]]' "$(n_kod)" "$(n_puan C Disiplin)" "$((C_DIS + 10))"
n_tamamla C "$N_S" 0; n_tamamla C "$N_S" 0
dogrula "seri: tamamlanmamış görevi geri alma tamamlanmadi döner" esit "$(n_kod)" "tamamlanmadi"
# 7. gün: çarpan ×1,25 -> round(12,5) = 13 XP; geri alma kazanılanı (13) düşer
sql "UPDATE KullaniciAliskanliklari SET Seri=6, SonIslemTarihi='$N_DUN' WHERE KullaniciAliskanlikID=$N_S"
C_DIS=$(n_puan C Disiplin)
n_tamamla C "$N_S" 1
dogrula "seri bonusu: 7. gün ×1,25 -> 13 XP, carpan_yukseldi" \
    bash -c '[[ "$0" == "$1" && "$2" == *"\"carpan_yukseldi\":true"* ]]' "$(n_puan C Disiplin)" "$((C_DIS + 13))" "$HTTP_GOVDE"
n_tamamla C "$N_S" 0
dogrula "seri bonusu: geri alma kazanılan 13 XP'yi düşer" esit "$(n_puan C Disiplin)" "$C_DIS"
# Seri bozulduysa (S12) bir sonraki tamamlama 1'den başlar
sql "UPDATE KullaniciAliskanliklari SET Seri=9, SonIslemTarihi='$N_ONCEKI' WHERE KullaniciAliskanlikID=$N_S"
istek "${NK[C]}" "${BASE}${GOREV_YOLU}"
dogrula "seri bozuldu (S12): kartta 'Önceki serin 9 gündü' notu" icerir "$HTTP_GOVDE" "Önceki serin 9 gündü"
n_tamamla C "$N_S" 1
dogrula "seri bozuldu (S12): tamamlanınca seri 1'den başlar" esit "$(seri_durumu)" "1|$N_BUGUN"
n_tamamla C "$N_S" 0
dogrula "seri bozuldu: geri alınca önceki (bozuk) seri ve tarihi geri gelir" esit "$(seri_durumu)" "9|$N_ONCEKI"
# Gün değiştiyse (sayfa dünden kalmış) değişiklik yapılmaz
n_tamamla C "$N_S" 1
ajax "${NK[C]}" "${NJ[C]}" -d "ka_id=$N_S" -d "durum=0" -d "gun=$N_DUN"
dogrula "gün sınırı: dünkü sayfadan geri alma gun_bitti döner, kayıt duruyor" \
    bash -c '[[ "$0" == "gun_bitti" && "$1" == "1" ]]' "$(n_kod)" "$(sql "SELECT COUNT(*) FROM TakipKayitlari WHERE KullaniciAliskanlikID=$N_S AND Tarih='$N_BUGUN'")"

# Seviye düşebilir (S8)
n_tamamla C "$N_S" 0
sql "UPDATE KullaniciStatlari SET ToplamPuan=95 WHERE KullaniciID=${NU[C]} AND StatTuru='Disiplin'"
n_tamamla C "$N_S" 1
dogrula "seviye: 95 + 10 XP -> Disiplin seviye 2" bash -c '[[ "$0" == *"\"yetenek_seviye_sonra\":2"* ]]' "$HTTP_GOVDE"
n_tamamla C "$N_S" 0
dogrula "seviye (S8): geri almada yetenek seviyesi 2 -> 1 düşer, puan 95" \
    bash -c '[[ "$0" == *"\"yetenek_seviye_once\":2"* && "$0" == *"\"yetenek_seviye_sonra\":1"* && "$1" == "95" ]]' "$HTTP_GOVDE" "$(n_puan C Disiplin)"

# ------------------------------------------------------------------ 20 görev sınırı (S10)
for i in $(seq 2 20); do n_ekle C "Sinir $i" "Zeka"; done
dogrula "sınır: 20 aktif görev eklenebildi" esit "$(n_aktif C)" "20"
n_ekle C "Sinir 21" "Zeka"
dogrula "sınır: 21. görev sinir_dolu ile reddedildi" bash -c '[[ "$0" == "sinir_dolu" && "$1" == "20" ]]' "$(n_kod)" "$(n_aktif C)"
istek "${NK[C]}" "${BASE}${GOREV_YOLU}" -d "add_habit_btn=1" -d "habit_name=Sinir Form" -d "habit_stat=Zeka" -d "csrf_token=${NJ[C]}"
dogrula "sınır: form (betiksiz) yolu da 21. görevi reddediyor" esit "$(n_aktif C)" "20"
istek "${NK[C]}" "${BASE}${GOREV_YOLU}"
dogrula "sınır: 'Görev ekle' düğmesi kilitli çiziliyor" icerir "$HTTP_GOVDE" 'data-kilitli="1"'
N_SIL=$(n_id C "Sinir 20")
ajax "${NK[C]}" "${NJ[C]}" -d "is_delete=1" -d "ka_id=$N_SIL"
n_ekle C "Sinir 21" "Zeka"
dogrula "sınır: biri silinince yenisi eklenebilir" bash -c '[[ "$0" == "true" && "$1" == "20" ]]' "$(n_basari)" "$(n_aktif C)"
ajax "${NK[C]}" "${NJ[C]}" -d "gorev_geri_getir=1" -d "ka_id=$N_SIL"
dogrula "sınır: sınır doluyken silinen görev geri getirilemez (sinir_dolu)" \
    bash -c '[[ "$0" == "sinir_dolu" && "$1" == "20" ]]' "$(n_kod)" "$(n_aktif C)"

# ------------------------------------------------------------------ meydan okuma türü (S25, S7)
# Tür başlıktan değil Tur alanından okunur: başlığında "görev" geçmeyen bir 'gorev_tamamlama'
# ilerler; başlığında "alışkanlık" geçen bir 'giris' girişte tamamlanır, görevle ilerlemez.
sql "INSERT INTO GorevTanimlari (Baslik, Aciklama, OdulPara, HedefSayi, Ikon, Tur) VALUES
     ('n_ikili_hedef', 'tur testi', 7, 2, 'fa-star', 'gorev_tamamlama'),
     ('n_alışkanlık_baslikli_giris', 'tur testi', 3, 4, 'fa-door-open', 'giris')"
N_T1=$(sql "SELECT GorevTanimID FROM GorevTanimlari WHERE Baslik='n_ikili_hedef'")
N_T2=$(sql "SELECT GorevTanimID FROM GorevTanimlari WHERE Baslik='n_alışkanlık_baslikli_giris'")
# D'nin bugünkü satırları kayıtta oluştu; yeni tanımlarla yeniden oluşsun diye silinir.
sql "DELETE FROM KullaniciGunlukGorevleri WHERE KullaniciID=${NU[D]} AND Tarih='$N_BUGUN'"
istek "${NK[D]}" "${BASE}${ANA_YOLU}"
meydan() { sql "SELECT CONCAT(MevcutIlerleme,'|',TamamlandiMi,'|',OdulAlindiMi) FROM KullaniciGunlukGorevleri WHERE KullaniciID=${NU[D]} AND Tarih='$N_BUGUN' AND GorevTanimID=$1"; }
dogrula "tür: 'giris' türü (başlığında 'alışkanlık' geçse de) girişte tamamlandı" esit "$(meydan "$N_T2")" "4|1|0"
dogrula "tür: 'gorev_tamamlama' henüz 0" esit "$(meydan "$N_T1")" "0|0|0"
n_ekle D "D bir" "Zeka"; n_ekle D "D iki" "Guc"
D1=$(n_id D "D bir"); D2=$(n_id D "D iki")
n_tamamla D "$D1" 1
dogrula "tür: görev tamamlama 'gorev_tamamlama'yı 1 artırdı (başlıkta 'görev' geçmiyor)" esit "$(meydan "$N_T1")" "1|0|0"
dogrula "tür: görev tamamlama 'giris' türünü değiştirmedi" esit "$(meydan "$N_T2")" "4|1|0"
dogrula "tür: yanıt değişen meydan okumaları içeriyor" icerir "$HTTP_GOVDE" '"meydan":[{'
n_tamamla D "$D2" 1
dogrula "tür: hedef 2'ye ulaşınca tamamlandı" esit "$(meydan "$N_T1")" "2|1|0"
n_tamamla D "$D2" 0
dogrula "S7: geri almada ilerleme 1 azalır, ödül alınmadıysa 'devam ediyor'a döner" esit "$(meydan "$N_T1")" "1|0|0"
n_tamamla D "$D2" 1
N_KG=$(sql "SELECT KullaniciGorevID FROM KullaniciGunlukGorevleri WHERE KullaniciID=${NU[D]} AND Tarih='$N_BUGUN' AND GorevTanimID=$N_T1")
ajax "${NK[D]}" "${NJ[D]}" -d "claim_quest_id=$N_KG"
dogrula "S7: ödül alındı" esit "$(meydan "$N_T1")" "2|1|1"
D_ALTIN=$(sql "SELECT Bakiye FROM Kullanicilar WHERE KullaniciID=${NU[D]}")
n_tamamla D "$D2" 0
dogrula "S7: ödül alındıysa geri alma ilerlemeyi ve ödülü değiştirmez" \
    esit "$(meydan "$N_T1")|$(sql "SELECT Bakiye FROM Kullanicilar WHERE KullaniciID=${NU[D]}")" "2|1|1|$D_ALTIN"
dogrula "seed: '3 Görev Tamamla' (gorev_tamamlama) da sayıldı" \
    esit "$(sql "SELECT KG.MevcutIlerleme FROM KullaniciGunlukGorevleri KG JOIN GorevTanimlari GT ON GT.GorevTanimID=KG.GorevTanimID WHERE KG.KullaniciID=${NU[D]} AND KG.Tarih='$N_BUGUN' AND GT.Baslik='3 Görev Tamamla'")" "1"
dogrula "seed: eski terimli başlık kalmadı ('alışkanlık' içeren meydan okuma yok, test satırı hariç)" \
    esit "$(sql "SELECT COUNT(*) FROM GorevTanimlari WHERE Baslik LIKE '%alışkanlık%' AND Baslik NOT LIKE 'n\\_%'")" "0"
sql "DELETE FROM KullaniciGunlukGorevleri WHERE GorevTanimID IN ($N_T1, $N_T2); DELETE FROM GorevTanimlari WHERE GorevTanimID IN ($N_T1, $N_T2)"

# ------------------------------------------------------------------ yeni uçlarda CSRF
YANLIS="0000000000000000000000000000000000000000000000000000000000000000"
n_csrf() { # ad sorgu veri...
    local ad="$1" sorgu="$2" once; shift 2
    once=$(sql "$sorgu")
    istek "${NK[A]}" "${BASE}view/ajax_islem.php" "$@"
    dogrula "CSRF: $ad jetonsuz reddedildi (HTTP $HTTP_KOD, durum aynı)" esit "$(sql "$sorgu")" "$once"
    istek "${NK[A]}" "${BASE}view/ajax_islem.php" -H "X-CSRF-Token: $YANLIS" "$@"
    dogrula "CSRF: $ad yanlış jetonla reddedildi (HTTP $HTTP_KOD, durum aynı)" esit "$(sql "$sorgu")" "$once"
}
n_csrf "gorev_ekle" "SELECT COUNT(*) FROM KullaniciAliskanliklari WHERE KullaniciID=${NU[A]}" -d "gorev_ekle=1" -d "habit_name=csrf_n" -d "habit_stat=Zeka"
n_csrf "gorev_duzenle" "SELECT Ad FROM KullaniciAliskanliklari WHERE KullaniciAliskanlikID=$N_D" -d "gorev_duzenle=1" -d "ka_id=$N_D" -d "habit_name=csrf_n" -d "habit_stat=Zeka"
ajax "${NK[A]}" "${NJ[A]}" -d "is_delete=1" -d "ka_id=$N_D"
n_csrf "gorev_geri_getir" "SELECT AktifMi FROM KullaniciAliskanliklari WHERE KullaniciAliskanlikID=$N_D" -d "gorev_geri_getir=1" -d "ka_id=$N_D"
ajax "${NK[A]}" "${NJ[A]}" -d "gorev_geri_getir=1" -d "ka_id=$N_D"
dogrula "CSRF: doğru jetonla geri getirme çalışıyor" esit "$(sql "SELECT AktifMi FROM KullaniciAliskanliklari WHERE KullaniciAliskanlikID=$N_D")" "1"

# ------------------------------------------------------------------ kaçışlama
n_ekle A '<img src=x onerror=alert(1)>"' "Zeka"
dogrula "kaçış: AJAX yanıtındaki kart HTML'inde görev adı kaçışlı" \
    bash -c '[[ "$0" == *"&lt;img src=x onerror=alert(1)&gt;&quot;"* && "$0" != *"<img src=x"* ]]' "$(printf '%s' "$HTTP_GOVDE" | php -r '$j = json_decode(stream_get_contents(STDIN), true); echo $j["kart_html"] ?? "";')"
istek "${NK[A]}" "${BASE}${GOREV_YOLU}"
dogrula "kaçış: Görevler sayfasında görev adı kaçışlı (ham <img yok)" icermez "$HTTP_GOVDE" "<img src=x onerror"
