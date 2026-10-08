# WPForms yenileme ve kayıt mutabakatı — 6 Ekim 2026

## Kaynaklar ve uygulanan işlem

- `wpforms-form-export-10-06-2026.json`: 119 form tanımı, **entry içermez**. 118 ID mevcut yerel WPForms formlarıyla eşleşti. 117 tanım değişmemişti; 1272 numaralı taslak form yerinde güncellendi. 3733 numaralı yeni form boş olduğundan içe alınmadı.
- Verilen PHP Cinematic Seeds şablonu çalıştırılmadı; veri olarak incelendi. Export'taki 1122 numaralı formdan aktif, yeniden kullanılabilir uygulama şablonu oluşturuldu. Kaynakta 31 WPForms nesnesi; uygulama şablonunda 3 sayfa ve 24 görüntülenen alan vardır. Sayfa sonu/düzen kapsayıcıları ayrı cevap alanı değildir; düzen bilgisi alan ayarlarına ve kaynak metadata'sına taşınır.
- 118 form silinmedi: mevcut başvurular, alan kimlikleri, URL'ler ve yayın ilişkileri korunmalıydı. Koşullu kuralların WPForms alan ID'leri uygulama alan adlarına çevrildi; ilk düzeltmede 216 alan yenilendi, tekrar çalıştırmada 0 değişiklik.
- `wpforms-3587-2026-09-28.csv`: 143 entry, 17 cevap sütununun tamamı eşleştirilerek aktarıldı. Ad/soyad parçaları tek bileşik cevapta saklandı. 143 benzersiz Entry ID, 143 aday adı, 143 e-posta ve 143 dosya **referansı** doğrulandı. Dosya ikilileri henüz yerel depoya kopyalanmadı.

## Yerel durum (WordPress'in canlı durumunun teyidi değildir)

| Ölçü | Sonuç |
| --- | ---: |
| WPForms kaynaklı yerel form | 118 |
| Açık / yaklaşan / kapalı / yayınlanmamış form | 18 / 0 / 0 / 100 |
| Açık / yaklaşan / kapalı yayın kartı | 17 / 0 / 0 |
| 1788 formu entry | 230 |
| 3587 formu entry | 143 |
| Toplam yerel WPForms entry | 373 |

Katalog API'si kapalı kartları (`visible_when_closed` açık değilse) ve boş kategorileri göstermez. Canlı katalog kontrolünde 5 dolu kategori ve 24 açık kart vardı; bunların bir bölümü WPForms dışı formlardır.

## Tekrarlanabilir işlem

Kaynak dosyaları sırasıyla `backend/storage/app/migration-source/forms` ve `backend/storage/app/migration-source/entries` içine koyun. `backend` dizininden:

```text
php artisan migration:refresh-wpforms-export wpforms-form-export-10-06-2026.json
php artisan migration:refresh-wpforms-export wpforms-form-export-10-06-2026.json --apply
php artisan migration:import-wpforms-entry-csv 3587 wpforms-3587-2026-09-28.csv
php artisan migration:import-wpforms-entry-csv 3587 wpforms-3587-2026-09-28.csv --apply
```

İlk komutların varsayılanı kuru çalıştırmadır. Korunan/yayınlanmış bir formun kaynak tanımı değişmişse form yenileme tamamını durdurur. Entry importu bilinmeyen cevap sütunları varsa yazmayı reddeder ve Entry ID ile tekrar çalıştırılabilir.

## Açık kalan doğrulama

- Form export'u WordPress yayın durumu, zaman planı veya entry içermez. WordPress servis hesabı form okuma çağrısında HTTP 401 döndürüyor. Bu nedenle 6 Ekim itibarıyla WordPress'teki **tüm** formların gerçek aktif/yaklaşan/pasif durumunu ve 28 Eylül'den sonraki entry'leri teyit edemiyoruz. Yetkili WPForms API erişimi veya bütün formların güncel Entries export'ları gerekir.
- 3587 için 143 dosya URL referansı aktarıldı; dosya içeriği/virüs taraması ancak yüklemeler arşivi veya kaynak depolama erişimiyle tamamlanabilir.
- Uygulamadaki kaynak düzen ve içerik korunur; WordPress eklentilerinin birebir piksel düzeni, ödeme sağlayıcısı ve kaynak bildirim ayarları otomatik olarak canlı hale getirilmez. Bildirim/confirmation tanımları inceleme için kaynak metadata'sında saklıdır.
- Tam backend paketi: 368 geçti, 1 atlandı, 4 başarısız. Başarısızlar bu import akışında değil; PostgreSQL legacy test şeması/bağlantısı ve göç yönetimi sayfasındaki metin beklentisiyle ilgili. Bu üç test dosyası tek başına çalıştırılınca 12 testin tamamı geçiyor; dolayısıyla paket sırası/izolasyonu ayrıca incelenmeli. İlgili import/katalog testleri geçti; frontend production build ve 130 frontend testi de geçti.
