#!/usr/bin/env bash
# tests/guvenlik/calistir.sh
# Güvenlik doğrulama betiklerini çalıştırır. Ayrıntı: tests/guvenlik/README.md
#
# Kullanım:
#   tests/guvenlik/calistir.sh                 # yeni kod, tüm testler (a..n)
#   tests/guvenlik/calistir.sh a c g           # yalnızca seçilen testler
#   tests/guvenlik/calistir.sh --eski          # açıkları ESKİ kodda yeniden üret
#   tests/guvenlik/calistir.sh --eski REF b    # eski kod olarak REF (git commit) kullan
#
# Ortam değişkenleri (varsayılanlar):
#   TEST_DB=charmoji_ozellik  TEST_DB_HOST=localhost  TEST_DB_USER=root  TEST_DB_PASSWORD=
#   PORT_TABAN=8150  (8150..8154 kullanılır)
#   PLAYWRIGHT=1     (0 yapılırsa tarayıcı testi atlanır)
#
# UYARI: TEST_DB veritabanındaki tablolar her çalıştırmada silinip yeniden kurulur.

set -uo pipefail

TEST_DIZINI="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
KOK="$(cd "$TEST_DIZINI/../.." && pwd)"

export TEST_DB="${TEST_DB:-charmoji_ozellik}"
export TEST_DB_HOST="${TEST_DB_HOST:-localhost}"
export TEST_DB_USER="${TEST_DB_USER:-root}"
export TEST_DB_PASSWORD="${TEST_DB_PASSWORD:-}"
PORT_TABAN="${PORT_TABAN:-8150}"
PLAYWRIGHT="${PLAYWRIGHT:-1}"
ESKI_REF_VARSAYILAN="9f065c1"   # Güvenlik dalgası öncesi main

case "$TEST_DB" in
    charmoji_vt|charmoji_kk|charmoji)
        echo "HATA: '$TEST_DB' veritabanı bu testlerde kullanılamaz (tablolar silinir)." >&2
        exit 2 ;;
esac

MOD="yeni"
ESKI_REF=""
SECILENLER=()
while [[ $# -gt 0 ]]; do
    case "$1" in
        --eski)
            MOD="eski"
            if [[ $# -gt 1 && ! "$2" =~ ^[a-n]$ ]]; then ESKI_REF="$2"; shift; fi ;;
        -h|--help)
            sed -n '2,20p' "$0"; exit 0 ;;
        [a-n]) SECILENLER+=("$1") ;;
        *) echo "Bilinmeyen argüman: $1" >&2; exit 2 ;;
    esac
    shift
done
[[ ${#SECILENLER[@]} -eq 0 ]] && SECILENLER=(a b c d e f g h i j k l m n)
ESKI_REF="${ESKI_REF:-$ESKI_REF_VARSAYILAN}"

GECICI="$(mktemp -d "${TMPDIR:-/tmp}/charmoji-guvenlik.XXXXXX")"
export GECICI MOD
mkdir -p "$GECICI/oturumlar"   # sunucuların oturum dosyaları her çalıştırmada ayrı
SUNUCU_PIDLERI=()

temizle() {
    for pid in "${SUNUCU_PIDLERI[@]+"${SUNUCU_PIDLERI[@]}"}"; do
        kill "$pid" 2>/dev/null
    done
    # test_e tablo adını değiştirir; yarıda kalırsa geri al
    mysql_komutu -N -B "$TEST_DB" -e "RENAME TABLE MarketEsyalari_gecici TO MarketEsyalari" 2>/dev/null
    if [[ "${GECICI_SAKLA:-0}" != "1" ]]; then rm -rf "$GECICI"; else echo "Geçici dizin: $GECICI"; fi
}
trap temizle EXIT

source "$TEST_DIZINI/ortak.sh"

# yonlendirici_yolu DOCROOT -> uygulamanın sunucu.php yönlendiricisi (varsa; eski kodda yoktur).
# Gerçek kurulumdaki gibi gizli yollar kapatılır. Docroot bir symlink altdizinse UYGULAMA kullanılır.
yonlendirici_yolu() {
    if [[ -f "$UYGULAMA/sunucu.php" ]]; then printf '%s' "$UYGULAMA/sunucu.php"; fi
}

# sunucu_baslat PORT DOCROOT ENV_DOSYASI GUNLUK [ek env atamaları...]
sunucu_baslat() {
    local port="$1" kok="$2" envdosyasi="$3" gunluk="$4"; shift 4
    env -u DB_HOST -u DB_NAME -u DB_USER -u DB_PASSWORD -u DB_PORT -u APP_LOG_FILE \
        CHARMOJI_ENV_FILE="$envdosyasi" "$@" \
        php -d session.save_path="$GECICI/oturumlar" -S "127.0.0.1:$port" -t "$kok" $(yonlendirici_yolu "$kok") > "$gunluk" 2>&1 &
    SUNUCU_PIDLERI+=($!)
    for _ in $(seq 1 50); do
        if curl -s -o /dev/null "http://127.0.0.1:$port/"; then return 0; fi
        sleep 0.1
    done
    echo "HATA: 127.0.0.1:$port üzerinde sunucu başlamadı (port dolu olabilir; PORT_TABAN değiştirin)." >&2
    cat "$gunluk" >&2
    exit 2
}

# ---------------------------------------------------------------- hazırlık
if [[ "$MOD" == "eski" ]]; then
    UYGULAMA="$GECICI/eski_kod"
    bash "$TEST_DIZINI/eski_kodu_hazirla.sh" "$ESKI_REF" "$UYGULAMA" || exit 2
    LOGIN_YOLU="service/login.php"
    REGISTER_YOLU="service/register.php"
    ANA_YOLU="view/userpage.php"      # giriş sonrası açılan sayfa
    GOREV_YOLU="view/userpage.php"    # görev ekleme formu + görev ve meydan okuma listesi
else
    UYGULAMA="$KOK"
    LOGIN_YOLU="view/login.php"
    REGISTER_YOLU="view/register.php"
    # Dalga 2: tek sayfalık userpage.php beş sekmeye bölündü (eski adres Karargah'a yönlenir).
    ANA_YOLU="view/karargah.php"
    GOREV_YOLU="view/gorevler.php"
fi
export UYGULAMA LOGIN_YOLU REGISTER_YOLU ANA_YOLU GOREV_YOLU

echo "Mod: $MOD   Uygulama: $UYGULAMA   Veritabanı: $TEST_DB"
[[ "$MOD" == "eski" ]] && echo "Eski kod: $ESKI_REF (yalnızca DB adı ve FriendManager dosya adı uyarlandı)"

# Resmi şema: veritabanı sıfırlanır, database/schema.sql + database/seed.sql kurulur.
mysql_komutu -e "DROP DATABASE IF EXISTS \`$TEST_DB\`; CREATE DATABASE \`$TEST_DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci" \
    || { echo "HATA: test veritabanı oluşturulamadı." >&2; exit 2; }
mysql_komutu "$TEST_DB" < "$KOK/database/schema.sql" || { echo "HATA: database/schema.sql kurulamadı." >&2; exit 2; }
mysql_komutu "$TEST_DB" < "$KOK/database/seed.sql" || { echo "HATA: database/seed.sql kurulamadı." >&2; exit 2; }

# Testlerin kullandığı .env (gerçek .env'e dokunulmaz; CHARMOJI_ENV_FILE ile gösterilir)
TEST_ENV="$GECICI/test.env"
HATA_LOGU="$GECICI/php-hata.log"
: > "$HATA_LOGU"
cat > "$TEST_ENV" <<EOF
DB_HOST=$TEST_DB_HOST
DB_NAME=$TEST_DB
DB_USER=$TEST_DB_USER
DB_PASSWORD=$TEST_DB_PASSWORD
APP_LOG_FILE=$HATA_LOGU
EOF
export TEST_ENV HATA_LOGU

# Kökten servis
sunucu_baslat "$PORT_TABAN" "$UYGULAMA" "$TEST_ENV" "$GECICI/sunucu_kok.log"
# /charmoji/ alt dizininden servis
mkdir -p "$GECICI/altdizin_kok"
ln -s "$UYGULAMA" "$GECICI/altdizin_kok/charmoji"
sunucu_baslat "$((PORT_TABAN + 1))" "$GECICI/altdizin_kok" "$TEST_ENV" "$GECICI/sunucu_alt.log"

BASE="http://127.0.0.1:$PORT_TABAN/"
BASE_ALT="http://127.0.0.1:$((PORT_TABAN + 1))/charmoji/"
export BASE BASE_ALT PORT_TABAN PLAYWRIGHT TEST_DIZINI

# ---------------------------------------------------------------- testler
for harf in "${SECILENLER[@]}"; do
    dosya=$(ls "$TEST_DIZINI"/test_"$harf"_*.sh 2>/dev/null | head -n1)
    if [[ -z "$dosya" ]]; then echo "Test bulunamadı: $harf" >&2; continue; fi
    source "$dosya"
done

# ---------------------------------------------------------------- özet
printf '\n=== ÖZET (%s kod) ===\n' "$MOD"
printf 'Geçen: %d   Kalan: %d   Atlanan: %d\n' "$GECEN" "$KALAN" "$ATLANAN"
if [[ $KALAN -gt 0 ]]; then
    printf 'Kalanlar:\n'
    printf '  - %s\n' "${KALAN_LISTE[@]}"
    exit 1
fi
exit 0
