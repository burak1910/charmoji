# tests/guvenlik/test_g_sqli.sh  (calistir.sh tarafından source edilir)
# g) İkinci derece SQL enjeksiyonu: habit_stat girişte reddedilir; veritabanındaki kötü
#    StatTuru/EsyaTuru değerleri işaretleme/kuşanma sorgularını bozmaz ve kaçışlanarak basılır.

bolum "g) İkinci derece SQL enjeksiyonu"

GA=$(yeni_kavanoz g_a); GB=$(yeni_kavanoz g_b)
kayit_ol "$GA" "sqli_a" "sqli_a@test.local" "Sifre123!"
kayit_ol "$GB" "sqli_b" "sqli_b@test.local" "Sifre123!"
GA_ID=$(kullanici_id sqli_a@test.local); GB_ID=$(kullanici_id sqli_b@test.local)
GA_J=$(sayfa_jetonu "$GA"); GB_J=$(sayfa_jetonu "$GB")   # ana sayfa ziyareti stat satırlarını oluşturur
KOTU_STAT="x' OR '1'='1"

# Şema kısıtları geçersiz StatTuru'yu reddeder; önce bunu doğrularız.
HATA=$(sql "INSERT INTO KullaniciAliskanliklari (KullaniciID, Ad, StatTuru, BaslangicTarihi) VALUES ($GA_ID, 'kotu_kisit', 'x'' OR ''1''=''1', CURDATE())" 2>&1)
dogrula "şema: KullaniciAliskanliklari.StatTuru kötü değeri reddediyor (CHK_KA_StatTuru)" icerir "$HATA" "CHK_KA_StatTuru"
HATA=$(sql "INSERT INTO KullaniciStatlari (KullaniciID, StatTuru) VALUES ($GA_ID, 'x'' OR ''1''=''1')" 2>&1)
dogrula "şema: KullaniciStatlari.StatTuru kötü değeri reddediyor (CHK_KS_StatTuru)" icerir "$HATA" "CHK_KS_StatTuru"
HATA=$(sql "INSERT INTO TakipKayitlari (KullaniciAliskanlikID, Tarih, StatTuru) VALUES (0, CURDATE(), 'x'' OR ''1''=''1')" 2>&1)
dogrula "şema: TakipKayitlari.StatTuru kötü değeri reddediyor (CHK_TK_StatTuru)" icerir "$HATA" "CHK_TK_StatTuru"

# Koddaki savunmayı (giriş doğrulaması, bağlanan parametreler, kaçışlama) kısıttan bağımsız
# sınamak için iki kısıt bu bölüm boyunca GEÇİCİ olarak düşürülür ve sonunda geri eklenir.
# (Uygulama artık StatTuru'yu yalnızca kullanıcıya özel sütundan okur; eski ortak tablodaki
# kısıtsız yol kullanılmıyor.)
sql "ALTER TABLE KullaniciAliskanliklari DROP CHECK CHK_KA_StatTuru; ALTER TABLE TakipKayitlari DROP CHECK CHK_TK_StatTuru"

# 1. Girişte reddedilir (kısıt yokken de)
istek "$GA" "${BASE}${GOREV_YOLU}" -d "add_habit_btn=1" -d "habit_name=sqli_aliskanlik" \
    --data-urlencode "habit_stat=$KOTU_STAT" -d "csrf_token=$GA_J"
KAYITLI=$(sql "SELECT COUNT(*) FROM KullaniciAliskanliklari WHERE Ad = 'sqli_aliskanlik' OR StatTuru = 'x'' OR ''1''=''1'")
kanit "habit_stat='$KOTU_STAT' gönderildi (form): HTTP $HTTP_KOD, veritabanında kayıt: $KAYITLI"
dogrula "habit_stat='$KOTU_STAT' girişte reddedildi (form; kaydedilmedi)" esit "$KAYITLI" "0"
ajax "$GA" "$GA_J" -d "gorev_ekle=1" -d "habit_name=sqli_aliskanlik" --data-urlencode "habit_stat=$KOTU_STAT"
KAYITLI=$(sql "SELECT COUNT(*) FROM KullaniciAliskanliklari WHERE Ad = 'sqli_aliskanlik' OR StatTuru = 'x'' OR ''1''=''1'")
dogrula "habit_stat='$KOTU_STAT' girişte reddedildi (AJAX; yetenek_yok, kaydedilmedi)" \
    bash -c '[[ "$0" == *"yetenek_yok"* && "$1" == "0" ]]' "$HTTP_GOVDE" "$KAYITLI"

istek "$GA" "${BASE}${GOREV_YOLU}" -d "add_habit_btn=1" -d "habit_name=gecerli_aliskanlik" \
    -d "habit_stat=Disiplin" -d "csrf_token=$GA_J"
dogrula "geçerli habit_stat (Disiplin) kabul edildi" esit "$(sql "SELECT StatTuru FROM KullaniciAliskanliklari WHERE KullaniciID=$GA_ID AND Ad='gecerli_aliskanlik'")" "Disiplin"

# 2. Veritabanına elle yazılmış kötü StatTuru ile işaretleme
if [[ "$MOD" == "eski" ]]; then
    # Eski kod StatTuru'yu ortak Aliskanliklar tablosundan JOIN ile okur.
    sql "INSERT INTO Aliskanliklar (Ad, StatTuru, Puan) VALUES ('kotu_stat_elle', 'x'' OR ''1''=''1', 10)"
    KA=$(sql "INSERT INTO KullaniciAliskanliklari (KullaniciID, AliskanlikID, Ad, StatTuru, BaslangicTarihi)
              SELECT $GA_ID, AliskanlikID, Ad, StatTuru, CURDATE() FROM Aliskanliklar WHERE Ad='kotu_stat_elle'; SELECT LAST_INSERT_ID();")
else
    KA=$(sql "INSERT INTO KullaniciAliskanliklari (KullaniciID, Ad, StatTuru, BaslangicTarihi) VALUES ($GA_ID, 'kotu_stat_elle', 'x'' OR ''1''=''1', CURDATE()); SELECT LAST_INSERT_ID();")
fi
dogrula "fikstür: kötü StatTuru'lu görev eklendi (sınama boş geçmiyor)" \
    esit "$(sql "SELECT StatTuru FROM KullaniciAliskanliklari WHERE KullaniciAliskanlikID=${KA:-0}")" "x' OR '1'='1"
B_ONCE=$(sql "SELECT GROUP_CONCAT(CONCAT(StatTuru,'=',ToplamPuan) ORDER BY StatTuru) FROM KullaniciStatlari WHERE KullaniciID=$GB_ID")
A_ONCE=$(sql "SELECT GROUP_CONCAT(CONCAT(StatTuru,'=',ToplamPuan) ORDER BY StatTuru) FROM KullaniciStatlari WHERE KullaniciID=$GA_ID")
ajax "$GA" "$GA_J" -d "ka_id=$KA" -d "durum=1"
kanit "işaretleme yanıtı: HTTP $HTTP_KOD $(printf '%s' "$HTTP_GOVDE" | head -c 300)"
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

# 4. Kötü StatTuru görev listesinde kaçışlanıyor
istek "$GA" "${BASE}${GOREV_YOLU}"
dogrula "$GOREV_YOLU: StatTuru HTML'e kaçışlanarak basılıyor" icerir "$HTTP_GOVDE" "x&#039; OR &#039;1&#039;=&#039;1"
dogrula "$GOREV_YOLU: StatTuru ham (kaçışsız) basılmıyor" icermez "$HTTP_GOVDE" "x' OR '1'='1"
if [[ "$MOD" == "yeni" ]]; then
    istek "$GA" "${BASE}${ANA_YOLU}"
    dogrula "$ANA_YOLU (sıradaki görevler): StatTuru kaçışlanarak basılıyor" \
        bash -c '[[ "$0" == *"x&#039; OR &#039;1&#039;=&#039;1"* && "$0" != *"x'"'"' OR '"'"'1'"'"'='"'"'1"* ]]' "$HTTP_GOVDE"
fi

# Geçici olarak düşürülen kısıtlar geri eklenir (kötü satır silinir; takip kayıtları CASCADE).
sql "DELETE FROM KullaniciAliskanliklari WHERE KullaniciAliskanlikID=${KA:-0}"
sql "ALTER TABLE KullaniciAliskanliklari ADD CONSTRAINT CHK_KA_StatTuru CHECK ((StatTuru COLLATE utf8mb4_0900_bin) IN ('Zeka','Guc','Sosyallik','Disiplin','Yaraticilik'));
     ALTER TABLE TakipKayitlari ADD CONSTRAINT CHK_TK_StatTuru CHECK ((StatTuru IS NULL) OR ((StatTuru COLLATE utf8mb4_0900_bin) IN ('Zeka','Guc','Sosyallik','Disiplin','Yaraticilik')))"
dogrula "test sonrası: geçici düşürülen CHK_KA_StatTuru ve CHK_TK_StatTuru geri eklendi" \
    esit "$(sql "SELECT COUNT(*) FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_NAME IN ('CHK_KA_StatTuru','CHK_TK_StatTuru')")" "2"
