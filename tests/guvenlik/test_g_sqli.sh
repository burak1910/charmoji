# tests/guvenlik/test_g_sqli.sh  (calistir.sh tarafından source edilir)
# g) İkinci derece SQL enjeksiyonu: habit_stat girişte reddedilir; veritabanındaki kötü
#    StatTuru/EsyaTuru değerleri işaretleme/kuşanma sorgularını bozmaz.

bolum "g) İkinci derece SQL enjeksiyonu"

GA=$(yeni_kavanoz g_a); GB=$(yeni_kavanoz g_b)
kayit_ol "$GA" "sqli_a" "sqli_a@test.local" "Sifre123!"
kayit_ol "$GB" "sqli_b" "sqli_b@test.local" "Sifre123!"
GA_ID=$(kullanici_id sqli_a@test.local); GB_ID=$(kullanici_id sqli_b@test.local)
GA_J=$(sayfa_jetonu "$GA"); GB_J=$(sayfa_jetonu "$GB")   # userpage ziyareti stat satırlarını oluşturur
KOTU_STAT="x' OR '1'='1"

# 1. Girişte reddedilir
istek "$GA" "${BASE}view/userpage.php" -d "add_habit_btn=1" -d "habit_name=sqli_aliskanlik" \
    --data-urlencode "habit_stat=$KOTU_STAT" -d "csrf_token=$GA_J"
KAYITLI=$(sql "SELECT COUNT(*) FROM Aliskanliklar WHERE StatTuru = 'x'' OR ''1''=''1'")
kanit "habit_stat='$KOTU_STAT' gönderildi: HTTP $HTTP_KOD, veritabanında bu StatTuru ile kayıt: $KAYITLI"
dogrula "habit_stat='$KOTU_STAT' girişte reddedildi (kaydedilmedi)" esit "$KAYITLI" "0"

istek "$GA" "${BASE}view/userpage.php" -d "add_habit_btn=1" -d "habit_name=gecerli_aliskanlik" \
    -d "habit_stat=Disiplin" -d "csrf_token=$GA_J"
dogrula "geçerli habit_stat (Disiplin) kabul edildi" esit "$(sql "SELECT StatTuru FROM Aliskanliklar WHERE Ad='gecerli_aliskanlik'")" "Disiplin"

# 2. Veritabanına elle yazılmış kötü StatTuru ile işaretleme
sql "INSERT INTO Aliskanliklar (Ad, StatTuru, Puan) VALUES ('kotu_stat_elle', 'x'' OR ''1''=''1', 10)"
KOTU_A=$(sql "SELECT AliskanlikID FROM Aliskanliklar WHERE Ad='kotu_stat_elle'")
KA=$(sql "INSERT INTO KullaniciAliskanliklari (KullaniciID, AliskanlikID, BaslangicTarihi) VALUES ($GA_ID, $KOTU_A, CURDATE()); SELECT LAST_INSERT_ID();")
B_ONCE=$(sql "SELECT GROUP_CONCAT(CONCAT(StatTuru,'=',ToplamPuan) ORDER BY StatTuru) FROM KullaniciStatlari WHERE KullaniciID=$GB_ID")
A_ONCE=$(sql "SELECT GROUP_CONCAT(CONCAT(StatTuru,'=',ToplamPuan) ORDER BY StatTuru) FROM KullaniciStatlari WHERE KullaniciID=$GA_ID")
ajax "$GA" "$GA_J" -d "ka_id=$KA" -d "durum=1"
kanit "işaretleme yanıtı: HTTP $HTTP_KOD $HTTP_GOVDE"
B_SONRA=$(sql "SELECT GROUP_CONCAT(CONCAT(StatTuru,'=',ToplamPuan) ORDER BY StatTuru) FROM KullaniciStatlari WHERE KullaniciID=$GB_ID")
A_SONRA=$(sql "SELECT GROUP_CONCAT(CONCAT(StatTuru,'=',ToplamPuan) ORDER BY StatTuru) FROM KullaniciStatlari WHERE KullaniciID=$GA_ID")
kanit "B statları: $B_ONCE -> $B_SONRA"
kanit "A statları: $A_ONCE -> $A_SONRA"
dogrula "kötü StatTuru ile işaretleme BAŞKA kullanıcının (B) statlarını değiştirmedi" esit "$B_SONRA" "$B_ONCE"
dogrula "kötü StatTuru ile işaretleme A'nın beş statını toplu değiştirmedi" esit "$A_SONRA" "$A_ONCE"
dogrula "yanıt geçerli JSON ve SQL hatası içermiyor" \
    bash -c 'php -r "exit(json_decode(\$argv[1]) === null ? 1 : 0);" "$0" && [[ "$0" != *"SQL syntax"* ]]' "$HTTP_GOVDE"

# geri alma da güvenli
ajax "$GA" "$GA_J" -d "ka_id=$KA" -d "durum=0"
B_SONRA2=$(sql "SELECT GROUP_CONCAT(CONCAT(StatTuru,'=',ToplamPuan) ORDER BY StatTuru) FROM KullaniciStatlari WHERE KullaniciID=$GB_ID")
kanit "geri alma sonrası B statları: $B_SONRA -> $B_SONRA2"
dogrula "kötü StatTuru ile geri alma B'nin statlarını değiştirmedi" esit "$B_SONRA2" "$B_SONRA"

# 3. Kötü EsyaTuru ile kuşanma (A kuşanınca B'nin eşyası çıkmamalı)
sql "INSERT INTO MarketEsyalari (Ad, Aciklama, Fiyat, GorselURL, EsyaTuru, GerekenSeviye) VALUES ('Kotu Esya', 'test', 0, 'fa-bug', 'Şapka'' OR ''1''=''1', 1)"
KOTU_ESYA=$(sql "SELECT EsyaID FROM MarketEsyalari WHERE Ad='Kotu Esya'")
sql "INSERT INTO KullaniciEnvanteri (KullaniciID, EsyaID, KusandiMi) VALUES ($GB_ID, 3, 1), ($GA_ID, $KOTU_ESYA, 0)"
ajax "$GA" "$GA_J" -d "equip_item_id=$KOTU_ESYA"
kanit "kuşanma yanıtı: HTTP $HTTP_KOD $HTTP_GOVDE"
B_KUSANDI=$(sql "SELECT KusandiMi FROM KullaniciEnvanteri WHERE KullaniciID=$GB_ID AND EsyaID=3")
dogrula "kötü EsyaTuru ile kuşanma B'nin kuşandığı eşyayı çıkarmadı" esit "$B_KUSANDI" "1"
dogrula "A kötü türdeki kendi eşyasını yine de kuşanabildi" esit "$(sql "SELECT KusandiMi FROM KullaniciEnvanteri WHERE KullaniciID=$GA_ID AND EsyaID=$KOTU_ESYA")" "1"

# 4. Kötü StatTuru listede kaçışlanıyor (userpage.php)
istek "$GA" "${BASE}view/userpage.php"
dogrula "userpage.php: StatTuru HTML'e kaçışlanarak basılıyor" icerir "$HTTP_GOVDE" "x&#039; OR &#039;1&#039;=&#039;1"
