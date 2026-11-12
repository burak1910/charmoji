# tests/guvenlik/test_m_ajax.sh  (calistir.sh tarafından source edilir)
# m) K-1: ajax_islem.php başarısız silme/kuşanmada success:false döner ve veri değişmez.
#    Silme = arşivleme (S18): görev satırı kalır, AktifMi = 0 olur.

bolum "m) AJAX: başkasının alışkanlığını silme / sahip olunmayan eşyayı kuşanma"

declare -A MK MJ MU
for k in A B; do
    MK[$k]=$(yeni_kavanoz "m_$k")
    kayit_ol "${MK[$k]}" "ajax_m_$k" "ajax_m_$k@test.local" "Sifre123!"
    MU[$k]=$(kullanici_id "ajax_m_$k@test.local")
    MJ[$k]=$(sayfa_jetonu "${MK[$k]}")
done
kanit "A=${MU[A]} B=${MU[B]}"
basari() { printf '%s' "$HTTP_GOVDE" | grep -o '"success":[a-z]*' | head -n1; }

# --- alışkanlık silme
sql "INSERT INTO KullaniciAliskanliklari (KullaniciID, Ad, StatTuru, Puan, BaslangicTarihi) VALUES
     (${MU[A]}, 'm_alisk_A', 'Zeka', 10, CURDATE()), (${MU[B]}, 'm_alisk_B', 'Guc', 10, CURDATE())"
KA_A=$(sql "SELECT KullaniciAliskanlikID FROM KullaniciAliskanliklari WHERE KullaniciID=${MU[A]} AND Ad='m_alisk_A'")
KA_B=$(sql "SELECT KullaniciAliskanlikID FROM KullaniciAliskanliklari WHERE KullaniciID=${MU[B]} AND Ad='m_alisk_B'")

ajax "${MK[B]}" "${MJ[B]}" -d "is_delete=1" -d "ka_id=$KA_A"
kanit "B, A'nın alışkanlığını siliyor: $HTTP_GOVDE"
dogrula "K-1: başkasının alışkanlığını silme success:false döner" esit "$(basari)" '"success":false'
dogrula "K-1: A'nın alışkanlığı duruyor (aktif)" esit "$(sql "SELECT AktifMi FROM KullaniciAliskanliklari WHERE KullaniciAliskanlikID=$KA_A")" "1"

ajax "${MK[B]}" "${MJ[B]}" -d "is_delete=1" -d "ka_id=99999999"
dogrula "K-1: olmayan alışkanlığı silme success:false döner" esit "$(basari)" '"success":false'

ajax "${MK[B]}" "${MJ[B]}" -d "is_delete=1" -d "ka_id=$KA_B"
kanit "B kendi alışkanlığını siliyor: $HTTP_GOVDE"
dogrula "K-1: meşru silme success:true döner" esit "$(basari)" '"success":true'
dogrula "K-1: meşru silmede görev listeden çıktı (arşivlendi: AktifMi=0, ArsivZamani dolu)" \
    esit "$(sql "SELECT CONCAT(AktifMi, ',', ArsivZamani IS NOT NULL) FROM KullaniciAliskanliklari WHERE KullaniciAliskanlikID=$KA_B")" "0,1"
ajax "${MK[B]}" "${MJ[B]}" -d "is_delete=1" -d "ka_id=$KA_B"
dogrula "K-1: arşivlenmiş görevi yeniden silme success:false döner" esit "$(basari)" '"success":false'

# --- eşya kuşanma: Kral Tacı yalnızca A'da; B'nin kendi eşyaları var
ESYA_SAPKA1=$(sql "SELECT EsyaID FROM MarketEsyalari WHERE Ad='Büyücü Şapkası'")
ESYA_SAPKA2=$(sql "SELECT EsyaID FROM MarketEsyalari WHERE Ad='Kral Tacı'")
ESYA_GOZLUK=$(sql "SELECT EsyaID FROM MarketEsyalari WHERE Ad='Güneş Gözlüğü'")
kanit "eşyalar: şapka1=$ESYA_SAPKA1 şapka2=$ESYA_SAPKA2 gözlük=$ESYA_GOZLUK"
sql "INSERT INTO KullaniciEnvanteri (KullaniciID, EsyaID, KusandiMi) VALUES
     (${MU[B]}, $ESYA_SAPKA1, 1), (${MU[B]}, $ESYA_GOZLUK, 0), (${MU[A]}, $ESYA_SAPKA2, 0)"
kusanilan() { sql "SELECT COALESCE(GROUP_CONCAT(EsyaID ORDER BY EsyaID),'-') FROM KullaniciEnvanteri WHERE KullaniciID=$1 AND KusandiMi=1"; }

ONCE_B=$(kusanilan "${MU[B]}"); ONCE_A=$(kusanilan "${MU[A]}")
ajax "${MK[B]}" "${MJ[B]}" -d "equip_item_id=$ESYA_SAPKA2"
kanit "B, sahip olmadığı Kral Tacı'nı kuşanıyor: $HTTP_GOVDE"
dogrula "K-1: sahip olunmayan eşyayı kuşanma success:false döner" esit "$(basari)" '"success":false'
dogrula "K-1: B'nin kuşanılmış eşyaları değişmedi (şapka çıkarılmadı)" esit "$(kusanilan "${MU[B]}")" "$ONCE_B"
dogrula "K-1: A'nın kuşanma durumu değişmedi" esit "$(kusanilan "${MU[A]}")" "$ONCE_A"

ajax "${MK[B]}" "${MJ[B]}" -d "equip_item_id=99999999"
dogrula "K-1: olmayan eşyayı kuşanma success:false döner" esit "$(basari)" '"success":false'
dogrula "K-1: olmayan eşyada B'nin kuşanma durumu değişmedi" esit "$(kusanilan "${MU[B]}")" "$ONCE_B"

ajax "${MK[B]}" "${MJ[B]}" -d "equip_item_id=$ESYA_GOZLUK"
dogrula "K-1: sahip olunan eşyayı kuşanma success:true döner" esit "$(basari)" '"success":true'
dogrula "K-1: meşru kuşanmada gözlük kuşanıldı, farklı türdeki şapka yerinde" \
    esit "$(kusanilan "${MU[B]}")" "$(printf '%s\n%s' "$ESYA_SAPKA1" "$ESYA_GOZLUK" | sort -n | paste -sd, -)"

# Meşru kuşanma, aynı türdeki eşyayı çıkarır (Şapka -> Şapka)
sql "INSERT INTO KullaniciEnvanteri (KullaniciID, EsyaID, KusandiMi) VALUES (${MU[B]}, $ESYA_SAPKA2, 0)"
ajax "${MK[B]}" "${MJ[B]}" -d "equip_item_id=$ESYA_SAPKA2"
dogrula "K-1: sahip olunan ikinci şapkayı kuşanma birinciyi çıkarır" \
    esit "$(kusanilan "${MU[B]}")" "$(printf '%s\n%s' "$ESYA_SAPKA2" "$ESYA_GOZLUK" | sort -n | paste -sd, -)"
