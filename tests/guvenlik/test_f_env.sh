# tests/guvenlik/test_f_env.sh  (calistir.sh tarafından source edilir)
# f) Yapılandırma: kodda sabit DB bilgisi yok; .env yokken anlaşılır hata, .env ile çalışma.

bolum "f) .env yapılandırması"

# 1. Kodda sabit veritabanı bilgisi kalmadı
SABIT=$(grep -rnE '(\$(servername|db_username|db_password|dbname)[[:space:]]*=)|new mysqli\([[:space:]]*["'"'"']' \
    "$UYGULAMA/service" "$UYGULAMA/view" "$UYGULAMA/config" 2>/dev/null)
kanit "${SABIT:-sabit DB bilgisi bulunamadı}"
dogrula "service/, view/, config/ içinde sabit DB bilgisi yok" bos "$SABIT"
dogrula ".env.example takipli örnek dosya mevcut" test -f "$UYGULAMA/.env.example"

# 2. Uygulamanın .env içermeyen kopyası (varsayılan konum: proje kökü/.env)
KOPYA="$GECICI/env_kopya"
mkdir -p "$KOPYA"
tar -C "$UYGULAMA" --exclude=./.git --exclude=./mysql-8.4 --exclude=./tests --exclude=./.env -cf - . | tar -C "$KOPYA" -xf -
rm -f "$KOPYA/.env"

F_PORT=$((PORT_TABAN + 2))
env -u CHARMOJI_ENV_FILE -u DB_HOST -u DB_NAME -u DB_USER -u DB_PASSWORD -u DB_PORT -u APP_LOG_FILE \
    php -d session.save_path="$GECICI/oturumlar" -S "127.0.0.1:$F_PORT" -t "$KOPYA" $( [[ -f "$KOPYA/sunucu.php" ]] && printf '%s' "$KOPYA/sunucu.php" ) > "$GECICI/sunucu_env.log" 2>&1 &
SUNUCU_PIDLERI+=($!)
for _ in $(seq 1 50); do curl -s -o /dev/null "http://127.0.0.1:$F_PORT/" && break; sleep 0.1; done
FB="http://127.0.0.1:$F_PORT/"

FK=$(yeni_kavanoz f_envsiz)
istek "$FK" "${FB}${LOGIN_YOLU}"
kanit ".env yok: HTTP $HTTP_KOD, gövde: $(printf '%s' "$HTTP_GOVDE" | sed 's/<[^>]*>/ /g' | tr -s ' \n' ' ' | cut -c1-260)"
dogrula ".env yokken anlaşılır hata (HTTP 500 ve '.env' anılıyor)" \
    bash -c '[[ "$0" == "500" && "$1" == *".env"* ]]' "$HTTP_KOD" "$HTTP_GOVDE"
dogrula ".env yokken hata metninde PHP/SQL ayrıntısı yok" \
    bash -c '[[ "$0" != *"Fatal error"* && "$0" != *"mysqli"* ]]' "$HTTP_GOVDE"

# 3. .env yazılınca çalışıyor (sunucu yeniden başlatılmadan)
grep -v '^APP_LOG_FILE=' "$TEST_ENV" > "$KOPYA/.env"
echo "APP_LOG_FILE=$GECICI/env_kopya.log" >> "$KOPYA/.env"
FK2=$(yeni_kavanoz f_envli)
kayit_ol "$FK2" "env_f" "env_f@test.local" "Sifre123!" "$FB"
oturum_acik_mi "$FK2" "$FB"; ACIK=$?
kanit ".env var: kayıt sonrası $HTTP_SON_URL (HTTP $HTTP_KOD)"
dogrula ".env ile kayıt + oturum çalışıyor (veritabanı ayarları .env'den okundu)" esit "$ACIK" "0"
dogrula ".env'deki DB_NAME kullanıldı (kullanıcı $TEST_DB içinde)" dolu "$(kullanici_id env_f@test.local)"
