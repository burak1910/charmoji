# tests/guvenlik/test_h_duman.sh  (calistir.sh tarafından source edilir)
# h) Uçtan uca duman testi: kayıt -> giriş -> alışkanlık ekle -> işaretle -> çıkış,
#    uygulama hem kökten hem /charmoji/ alt dizininden servis edilirken.

duman_testi() {
    local kok="$1" etiket="$2" k j ka eposta
    eposta="duman_${etiket}@test.local"
    bolum "h) Duman testi ($etiket: $kok)"
    k=$(yeni_kavanoz "h_$etiket")

    # Ana sayfadaki giriş/kayıt bağlantıları çalışıyor
    istek "$k" "${kok}view/index.php"
    local linkler; linkler=$(printf '%s' "$HTTP_GOVDE" | grep -o 'href="[^"#]*\(login\|register\)\.php"' | sort -u | sed 's/href="//; s/"$//')
    kanit "index.php bağlantıları: $(echo $linkler)"
    local l tum_ok=0
    for l in $linkler; do
        istek "$k" "${kok}view/$l" -L
        [[ "$HTTP_KOD" == "200" ]] || { tum_ok=1; kanit "  $l -> HTTP $HTTP_KOD"; }
    done
    dogrula "index.php'deki giriş/kayıt bağlantıları 200 dönüyor" esit "$tum_ok" "0"

    # Kayıt sayfası view/ altında
    istek "$k" "${kok}view/register.php"
    dogrula "view/register.php açılıyor (HTTP $HTTP_KOD)" esit "$HTTP_KOD" "200"
    istek "$k" "${kok}view/login.php"
    dogrula "view/login.php açılıyor (HTTP $HTTP_KOD)" esit "$HTTP_KOD" "200"

    # Kayıt -> userpage
    kayit_ol "$k" "duman_$etiket" "$eposta" "Sifre123!" "$kok"
    kanit "kayıt sonrası: $HTTP_SON_URL (HTTP $HTTP_KOD)"
    dogrula "kayıt sonrası view/userpage.php açılıyor" bash -c '[[ "$0" == "200" && "$1" == "$2view/userpage.php" ]]' "$HTTP_KOD" "$HTTP_SON_URL" "$kok"

    # Çıkış -> index
    j=$(sayfa_jetonu "$k" "$kok")
    istek "$k" "${kok}service/logout.php" -L -d "csrf_token=$j"
    kanit "çıkış sonrası: $HTTP_SON_URL (HTTP $HTTP_KOD)"
    dogrula "çıkış view/index.php adresine yönleniyor" bash -c '[[ "$0" == "200" && "$1" == "$2view/index.php" ]]' "$HTTP_KOD" "$HTTP_SON_URL" "$kok"

    # Hatalı giriş -> login sayfası (hata mesajıyla)
    local hj; hj=$(jeton_cek "$k" "${kok}${LOGIN_YOLU}")
    istek "$k" "${kok}service/auth.php" -L -d "username=$eposta" -d "password=yanlis" -d "csrf_token=$hj"
    kanit "hatalı giriş sonrası: $HTTP_SON_URL (HTTP $HTTP_KOD)"
    dogrula "hatalı giriş view/login.php'ye döner ve mesaj gösterir" \
        bash -c '[[ "$0" == "200" && "$1" == "$2view/login.php" && "$3" == *"hatalı"* ]]' "$HTTP_KOD" "$HTTP_SON_URL" "$kok" "$HTTP_GOVDE"

    # Giriş -> userpage
    giris_yap "$k" "$eposta" "Sifre123!" "$kok"
    kanit "giriş sonrası: $HTTP_SON_URL (HTTP $HTTP_KOD)"
    dogrula "giriş sonrası view/userpage.php açılıyor" bash -c '[[ "$0" == "200" && "$1" == "$2view/userpage.php" ]]' "$HTTP_KOD" "$HTTP_SON_URL" "$kok"

    # Alışkanlık ekle
    j=$(sayfa_jetonu "$k" "$kok")
    istek "$k" "${kok}view/userpage.php" -L -d "add_habit_btn=1" -d "habit_name=duman_$etiket" -d "habit_stat=Zeka" -d "csrf_token=$j"
    ka=$(sql "SELECT KA.KullaniciAliskanlikID FROM KullaniciAliskanliklari KA JOIN Kullanicilar K ON K.KullaniciID=KA.KullaniciID WHERE K.Eposta='$eposta' LIMIT 1")
    dogrula "alışkanlık eklendi ve sayfada görünüyor" bash -c '[[ -n "$0" && "$1" == *"duman_$2"* ]]' "$ka" "$HTTP_GOVDE" "$etiket"

    # İşaretle
    istek "$k" "${kok}view/ajax_islem.php" -H "X-CSRF-Token: $j" -d "ka_id=${ka:-0}" -d "durum=1"
    kanit "işaretleme: $HTTP_GOVDE"
    dogrula "alışkanlık işaretlendi (success:true, Zeka puanı arttı)" \
        bash -c '[[ "$0" == *"\"success\":true"* && "$1" -gt 0 ]]' "$HTTP_GOVDE" \
        "$(sql "SELECT COALESCE(SUM(S.ToplamPuan),0) FROM KullaniciStatlari S JOIN Kullanicilar K ON K.KullaniciID=S.KullaniciID WHERE K.Eposta='$eposta' AND S.StatTuru='Zeka'")"

    # Market ve avatar sayfaları açılıyor
    istek "$k" "${kok}view/market.php"
    dogrula "view/market.php açılıyor (HTTP $HTTP_KOD)" esit "$HTTP_KOD" "200"
    istek "$k" "${kok}view/avatar.php"
    dogrula "view/avatar.php açılıyor (HTTP $HTTP_KOD)" esit "$HTTP_KOD" "200"

    # Çıkış -> oturum kapalı
    j=$(sayfa_jetonu "$k" "$kok")
    istek "$k" "${kok}service/logout.php" -L -d "csrf_token=$j"
    istek "$k" "${kok}view/userpage.php" -L
    kanit "çıkıştan sonra userpage.php -> $HTTP_SON_URL"
    dogrula "çıkıştan sonra userpage.php view/login.php'ye yönleniyor" bash -c '[[ "$0" == "200" && "$1" == "$2view/login.php" ]]' "$HTTP_KOD" "$HTTP_SON_URL" "$kok"
}

duman_testi "$BASE" "kok"
duman_testi "$BASE_ALT" "altdizin"

# Gerçek tarayıcıda arayüz üzerinden (formlar, AJAX çağrıları, çıkış düğmesi)
bolum "h) Tarayıcı duman testi (Playwright, /charmoji/ alt dizini)"
if [[ "$MOD" != "yeni" ]]; then
    atla "tarayıcı duman testi yalnızca yeni kod için (eski kodda sayfa yolları farklı)"
elif [[ "$PLAYWRIGHT" == "1" ]] && command -v node >/dev/null; then
    PW=$(node "$TEST_DIZINI/duman_tarayici.js" "$BASE_ALT" "tarayici_h@test.local" 2>&1)
    PW_SATIRLAR=$(php -r '
        $a = json_decode($argv[1], true);
        if (!is_array($a)) { echo "0\tPlaywright çıktısı okunamadı: " . $argv[1] . "\n"; exit; }
        foreach ($a as $s) { echo ($s["ok"] ? "1" : "0") . "\t" . $s["adim"] . " -> " . $s["ayrinti"] . "\n"; }' "$PW")
    while IFS=$'\t' read -r ok metin; do
        [[ -z "$metin" ]] && continue
        dogrula "tarayıcı: $metin" esit "$ok" "1"
    done <<< "$PW_SATIRLAR"
else
    atla "tarayıcı duman testi (PLAYWRIGHT=0 ya da node yok)"
fi
