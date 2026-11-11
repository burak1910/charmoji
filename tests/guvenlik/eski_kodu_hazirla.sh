#!/usr/bin/env bash
# tests/guvenlik/eski_kodu_hazirla.sh REF HEDEF_DIZIN
# Açıkları yeniden üretmek için eski kodu (git REF) HEDEF_DIZIN'e çıkarır.
# Eski kodun çalışabilmesi için yalnızca iki uyarlama yapılır:
#   1. service/auth.php içindeki sabit veritabanı adı -> $TEST_DB
#      (kullanıcı/şifre sabit root/boş; TEST_DB_USER/PASSWORD farklıysa onlar da yazılır)
#   2. service/friendmanager.php -> FriendManager.php kopyası (sorun 7; yoksa
#      userpage.php Linux'ta hiç açılmaz ve diğer açıklar gösterilemez)
set -euo pipefail

REF="$1"
HEDEF="$2"
TEST_DIZINI="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
KOK="$(cd "$TEST_DIZINI/../.." && pwd)"

mkdir -p "$HEDEF"
git -C "$KOK" archive "$REF" | tar -x -C "$HEDEF" --exclude='mysql-8.4'

AUTH="$HEDEF/service/auth.php"
sed -i "s/\$dbname = \"charmoji\";/\$dbname = \"${TEST_DB:-charmoji_ozellik}\";/" "$AUTH"
sed -i "s/\$db_username = \"root\";/\$db_username = \"${TEST_DB_USER:-root}\";/" "$AUTH"
sed -i "s/\$db_password = \"\";/\$db_password = \"${TEST_DB_PASSWORD:-}\";/" "$AUTH"
sed -i "s/\$servername = \"localhost\";/\$servername = \"${TEST_DB_HOST:-localhost}\";/" "$AUTH"

if [[ -f "$HEDEF/service/friendmanager.php" && ! -f "$HEDEF/service/FriendManager.php" ]]; then
    cp "$HEDEF/service/friendmanager.php" "$HEDEF/service/FriendManager.php"
fi
