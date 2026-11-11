# tests/guvenlik/test_e_hata.sh  (calistir.sh tarafından source edilir)
# e) Hata sızıntısı: kasıtlı veritabanı hatasında yanıtta PHP/SQL hata metni yok, log'da var.

bolum "e) Hata metni sızıntısı"

E_KAV=$(yeni_kavanoz e_kullanici)
kayit_ol "$E_KAV" "hata_e" "hata_e@test.local" "Sifre123!"
E_JETON=$(sayfa_jetonu "$E_KAV")

SIZINTI_KALIPLARI=("mysqli_sql_exception" "Fatal error" "Uncaught" "doesn't exist" "Stack trace" ".php on line" "MarketEsyalari_gecici" "$TEST_DB.")
sizinti_yok() {
    local govde="$1" k
    for k in "${SIZINTI_KALIPLARI[@]}"; do
        [[ "$govde" == *"$k"* ]] && return 1
    done
    return 0
}
log_icerigi() { cat "$HATA_LOGU" "$GECICI"/sunucu_*.log 2>/dev/null; }

# Kasıtlı hata: market tablosunun adını geçici olarak değiştir
sql "RENAME TABLE MarketEsyalari TO MarketEsyalari_gecici"

istek "$E_KAV" "${BASE}view/market.php"
kanit "market.php: HTTP $HTTP_KOD, gövde (ilk 200): $(printf '%s' "$HTTP_GOVDE" | tr -d '\n' | sed 's/<[^>]*>/ /g' | tr -s ' ' | cut -c1-200)"
dogrula "market.php: yanıtta PHP/SQL hata metni yok" sizinti_yok "$HTTP_GOVDE"
dogrula "market.php: HTTP 500 dönüyor" esit "$HTTP_KOD" "500"

ajax "$E_KAV" "$E_JETON" -d "buy_item_id=1"
kanit "ajax buy_item_id: HTTP $HTTP_KOD $HTTP_GOVDE"
dogrula "ajax_islem.php: JSON yanıtta SQL hata metni yok" sizinti_yok "$HTTP_GOVDE"

ajax "$E_KAV" "$E_JETON" -d "equip_item_id=1"
kanit "ajax equip_item_id: HTTP $HTTP_KOD $HTTP_GOVDE"
dogrula "ajax_islem.php (equip): JSON yanıtta SQL hata metni yok" sizinti_yok "$HTTP_GOVDE"

sql "RENAME TABLE MarketEsyalari_gecici TO MarketEsyalari"

LOG=$(log_icerigi)
kanit "log'dan: $(grep -m1 "doesn't exist" <<< "$LOG" | cut -c1-200)"
dogrula "hata ayrıntısı log'a yazıldı" icerir "$LOG" "MarketEsyalari' doesn't exist"

# Bağlantı hatası: olmayan veritabanı adıyla ayrı sunucu (yalnızca yeni kod yapılandırılabilir)
if [[ "$MOD" == "yeni" ]]; then
    KOTU_ENV="$GECICI/kotu_db.env"
    sed "s/^DB_NAME=.*/DB_NAME=charmoji_olmayan_vt_xyz/" "$TEST_ENV" > "$KOTU_ENV"
    sunucu_baslat "$((PORT_TABAN + 4))" "$UYGULAMA" "$KOTU_ENV" "$GECICI/sunucu_kotu_db.log"
    KB="http://127.0.0.1:$((PORT_TABAN + 4))/"
    KK=$(yeni_kavanoz e_kotu)
    KJ=$(jeton_cek "$KK" "${KB}view/login.php")
    istek "$KK" "${KB}service/auth.php" -d "username=x@test.local" -d "password=x" -d "csrf_token=$KJ"
    kanit "olmayan veritabanı: HTTP $HTTP_KOD, gövde: $(printf '%s' "$HTTP_GOVDE" | sed 's/<[^>]*>/ /g' | tr -s ' ' | cut -c1-160)"
    dogrula "bağlantı hatasında yanıtta 'Unknown database'/kullanıcı adı yok" \
        bash -c '[[ "$0" != *"Unknown database"* && "$0" != *"charmoji_olmayan"* && "$0" != *"root"* ]]' "$HTTP_GOVDE"
    dogrula "bağlantı hatası log'a yazıldı" icerir "$(cat "$HATA_LOGU")" "Unknown database"
else
    atla "bağlantı hatası denemesi (eski kodda veritabanı adı sabit, yapılandırılamaz)"
fi
