# tests/guvenlik/test_k_cikti.sh  (calistir.sh tarafından source edilir)
# k) B-2: günlük görev Baslik/Ikon değerleri (GorevTanimlari) userpage'de kaçışlanır.

bolum "k) Depolanan XSS (günlük görev Baslik/Ikon)"

K_BASLIK="X<img src=x onerror=\"window.__xss='baslik';alert(1)\">"
K_IKON="fa-x\"><img src=x onerror=\"window.__xss='ikon';alert(2)\">"
sql_kacis() { printf '%s' "$1" | sed "s/'/''/g"; }
sql "INSERT INTO GorevTanimlari (Baslik, Aciklama, OdulPara, HedefSayi, Ikon) VALUES ('$(sql_kacis "$K_BASLIK")', 'k testi', 5, 1, '$(sql_kacis "$K_IKON")')"
K_TANIM=$(sql "SELECT MAX(GorevTanimID) FROM GorevTanimlari")

K_KAV=$(yeni_kavanoz k_kullanici)
kayit_ol "$K_KAV" "xss_k" "xss_k@test.local" "Sifre123!"
istek "$K_KAV" "${BASE}view/userpage.php" -L
K_SAYFA="$HTTP_GOVDE"
dogrula "B-2: userpage ham '<img src=x onerror' içermiyor" icermez "$K_SAYFA" "<img src=x onerror"
dogrula "B-2: userpage Baslik'i kaçışlı gösteriyor (X&lt;img)" icerir "$K_SAYFA" "X&lt;img src=x"
dogrula "B-2: userpage Ikon'u kaçışlı gösteriyor (fa-x&quot;&gt;)" icerir "$K_SAYFA" "fa-x&quot;&gt;"

if [[ "$PLAYWRIGHT" == "1" ]] && command -v node >/dev/null; then
    PW=$(node "$TEST_DIZINI/xss_tarayici.js" "$BASE" "$LOGIN_YOLU" "xss_k@test.local" "Sifre123!" 2>&1)
    kanit "playwright: $PW"
    dogrula "B-2: tarayıcıda görev Baslik/Ikon script çalıştırmıyor (diyalog yok, window.__xss tanımsız)" \
        icerir "$PW" '"tetiklenen":[]'
else
    atla "B-2 Playwright testi (PLAYWRIGHT=0 ya da node yok)"
fi

# Diğer testleri etkilemesin
sql "DELETE FROM KullaniciGunlukGorevleri WHERE GorevTanimID = $K_TANIM; DELETE FROM GorevTanimlari WHERE GorevTanimID = $K_TANIM"
