# tests/guvenlik/test_i_lint.sh  (calistir.sh tarafından source edilir)
# i) php -l tüm PHP dosyalarında temiz.

bolum "i) php -l"

LINT_HATALI=0
LINT_SAYI=0
while IFS= read -r -d '' dosya; do
    LINT_SAYI=$((LINT_SAYI + 1))
    if ! cikti=$(php -l "$dosya" 2>&1); then
        LINT_HATALI=$((LINT_HATALI + 1))
        kanit "$cikti"
    fi
done < <(find "$UYGULAMA" -name '*.php' -not -path '*/mysql-8.4/*' -not -path '*/.git/*' -print0)
kanit "$LINT_SAYI PHP dosyası denetlendi"
dogrula "tüm PHP dosyaları php -l denetiminden geçti ($LINT_SAYI dosya)" esit "$LINT_HATALI" "0"
