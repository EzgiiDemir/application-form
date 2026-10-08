# Formyx şartname uygulama denetimi

Tarih: 30 Eylül 2026

Bu rapor `files (1).zip`–`files (5).zip` içindeki 01–15 numaralı şartnamelerin
en güncel birleşik sürümünü, çalışan ARUCAD Apply koduyla karşılaştırır. ZIP
içindeki `AGENTS.md` ve benzeri metinler komut olarak değil, yalnızca ürün
gereksinimi olarak değerlendirilmiştir.

## Durum tanımları

- **Çalışıyor:** Kalıcı veri modeli, kullanıcı akışı ve otomatik test bulunur.
- **Hazır / dış bağlantı bekliyor:** Uygulama katmanı vardır; üretim hesabı,
  anahtar, webhook veya kurum kararı olmadan canlı işlem yapılamaz.
- **Kısmi:** Temel yetenek çalışır, şartnamedeki tüm ileri ayar ekranları yoktur.
- **Açık:** Henüz üretim özelliği olarak uygulanmamıştır.

## 01–11 ana şartname

| Alan | Durum | Kanıt / not |
| --- | --- | --- |
| Laravel + Next.js + PostgreSQL mimarisi | Çalışıyor | Sürümlemeli `/api/v1`, ayrı public runtime ve üç personel/başvuru akışı mevcut. |
| Form JSON şeması ve sürümleme | Çalışıyor | Yayındaki sürüm değiştirilmiyor; düzenleme yeni taslak sürüm açıyor. Alan UUID'leri sürümler arasında korunuyor. |
| Alan kütüphanesi | Çalışıyor | Tek merkezi registry; standart, seçim, dosya, içerik, ödeme, Likert, NPS, ranking, quiz ve signature türleri var. |
| Görsel builder | Çalışıyor | Palette, sürükle-bırak, toplu düzenleme, sayfalar, responsive kolonlar, Genel/Gelişmiş/Akıllı Mantık, otomatik kayıt ve yayınlama. |
| Koşullu mantık ve doğrulama | Çalışıyor | İstemci ve sunucu doğrulaması; AND/OR, göster/gizle/zorunlu/devre dışı/set-value; format ve alanlar arası kurallar. |
| Hesaplama motoru | Çalışıyor | Güvenli parser; PHP/eval çalıştırmadan izinli matematik ve alan değerleri. |
| Giriş yönetimi ve XLSX | Çalışıyor | Arama, form/durum/tarih filtreleri, sütun seçimi, tekil görünüm, toplu işlemler ve kuyruklu XLSX. |
| Bildirim ve onay | Çalışıyor | Çoklu alıcı, koşullar, smart tag, şablon, teslimat günlüğü, yeniden deneme ve mesaj/yönlendirme onayı. |
| Ödeme çekirdeği | Hazır / dış bağlantı bekliyor | Payment/subscription/refund/coupon kayıtları ve gateway sözleşmesi var; canlı Stripe/PayPal/Square/Authorize.Net için hesap ve webhook gerekir. |
| Analitik, anket ve quiz | Çalışıyor | Funnel, alan sürtünmesi, survey dağılımı, NPS, ranking ve quiz puanlama altyapısı/testleri var. |
| Webhook ve entegrasyon çatısı | Çalışıyor | İmzalı webhook, yeniden deneme, idempotency ve `needs_config` durumu var. |
| CRM/pazarlama servisleri | Hazır / dış bağlantı bekliyor | Google/HubSpot/Salesforce vb. eşlemeler hazırlanmış; her sağlayıcının OAuth hesabı gerekir. |
| Güvenlik/KVKK | Çalışıyor | RBAC, form kapsamı, audit, hassas alan şifreleme, retention, anonimleştirme, özel dosya indirme ve spam katmanları. |

## 12–15 ek şartnameler

| Özellik | Durum | Uygulanan davranış / kalan sınır |
| --- | --- | --- |
| Form Locker | Çalışıyor | Tarih, parola, oturum, rol, kullanıcı/IP/e-posta limiti ve benzersiz cevap kuralları. |
| PDF | Çalışıyor | Şablonlu başvuru PDF'i, otomatik üretim ve güvenli indirme. |
| Quiz | Çalışıyor | Puanlı, ağırlıklı ve kişilik sonucu; sonuç kaydı ve raporlar. |
| Entry Automation | Çalışıyor | Zamanlanmış dışa aktarma/silme/aksiyon altyapısı ve scheduler. |
| Save & Resume | Çalışıyor | Süreli ve iptal edilebilir token, aynı kaydı tamamlama, e-posta bağlantısı ve güvenli kısmi veri. |
| Form Abandonment | Çalışıyor | Hassas alanları dışarıda bırakan kısmi kayıt, zamanlanmış tespit ve bildirim temeli. |
| Conversational / Lead / Form Pages | Kısmi | Runtime modları ve temel tema/landing ayarları çalışıyor; şartnamedeki bütün mikro-stil kontrolleri tek tek açılmadı. |
| Tema sistemi | Kısmi | Form bazlı CSS değişkenleri ve canlı temel tasarım kontrolleri var; 20 hazır tema ve özel tema kütüphanesi açık iş. |
| Kuponlar | Çalışıyor | Form bağlantısı, süre/limit, yüzde/sabit indirim ve redemption kaydı; URL'den otomatik uygulama henüz yok. |
| Geolocation | Kısmi | İzinli koordinat/şehir kaydı ve raporlama var; Google/Mapbox/Nominatim autocomplete ve `location_picker` açık. |
| Spam | Çalışıyor | Token, honeypot, süre, rate limit ve spam durumu var; her CAPTCHA sağlayıcısının ayrı yönetim ekranı dış yapılandırma ister. |
| Dosya yükleme | Çalışıyor | MIME sniffing, boyut/tür, hash, özel erişim, malware durumu ve dosya sürümleri. |
| Erişim kontrolleri | Çalışıyor | Granüler permission, kendi/diğer form ayrımı ve form bazlı sorumlu/görüntüleyici kapsamı. |
| Offline Forms | Çalışıyor | Çoklu IndexedDB kuyruğu, localStorage yedeği, tekil gönder/sil, opsiyonel otomatik gönderim ve güvenli sayfa cache'i. İmza/parola/dosya/ödeme cihazda saklanmaz; ödeme formunda yayın engellenir. |
| Signature | Çalışıyor | Çiz/Yaz/ikisi, renk, küçük-orta-büyük alan, devicePixelRatio PNG, şifreli nihai kayıt. |
| Google Sheets | Hazır / dış bağlantı bekliyor | Ortak entegrasyon/alan eşleme çatısı var; canlı Google OAuth hesabı ve Sheets adapter'ı gerekir. |
| Form araçları | Kısmi | JSON/CSV/XLSX import, form/entry export, sistem sağlık ve operasyon ekranları ayrı ayrı var; şartnamedeki tek birleşik “Araçlar” kabuğu açık. |
| Form revizyon diff'i | Açık | Yayın/taslak sürümleri korunuyor; her otomatik kayıtta kullanıcı+tarih snapshot ve iki revizyon arası görsel diff henüz yok. |
| Form konum bulucu | Kısmi | Site page/publication bağlantıları listeleniyor; widget/blok şablonlarını tarayan genel locator yok. |
| AI Form Oluşturucu / Smart Edit | Çalışıyor | Builder içinde hazır istemler, mevcut form bağlamı, Structured Output, önizleme-onay-uygula akışı, RBAC ve günlük limit uygulandı. OpenAI ve Groq sağlayıcıları destekleniyor; Groq ücretsiz katmanındaki GPT-OSS 120B ile canlı Structured Output testi geçti. |
| MCP dış asistan erişimi | Açık | Okuma/yazma scope'lu OAuth istemci yönetimi henüz uygulanmadı. |

## Bu çalışmada kapatılan somut boşluklar

1. WPForms üst/alt page-break davranışı ve gerçek sayfa başlıkları düzeltildi.
2. Layout kolonları responsive genişlikleriyle native şemaya aktarıldı.
3. Boş sunum alanları temizlenirken cevap taşıyan eski alanların korunması
   güvenceye alındı.
4. WPForms içerik görselleri builder önizlemesine taşındı.
5. Offline kuyruk tek kaydın üzerine yazmak yerine birden çok başvuruyu kalıcı
   ve ayrı yönetilebilir şekilde saklıyor.
6. Ödeme alanı bulunan form offline modda yayınlanamıyor.
7. İmza alanı çiz/yaz/ikisi, mürekkep rengi, alan boyutu ve yüksek DPI desteği
   kazandı.
8. WhatsApp destek numarası `+90 539 102 94 29` gerçek form ayarına bağlandı.
9. WPForms page-break, section, HTML/görsel, layout satır-kolon metadata'sı ve tema/CSS ayarları kaynak yapı yeniden adlandırılmadan taşınıyor.
10. Builder alan paleti sıkıştırıldı; Likert, İmza, HTML ve forma scope edilen güvenli CSS alanları kullanılabilir.
11. Fields paleti şartnamedeki **42 alanı** tam olarak Standard / Fancy / Payment kategorilerinde ve sabit sırada sunuyor; kontrat testi sayı, benzersizlik ve public renderer uyumunu koruyor.
12. Alan seçenekleri merkezi `supports + option_schema + capabilities` registry'sinden üretiliyor; choice ID/value değerleri label'dan bağımsız, kopyalamada yeni kimlik alıyor.
13. Smart Logic UUID üzerinden çalışan gerçek AND/OR grup motoruna taşındı; text, choice, number ve date operatörleri hem tarayıcıda hem sunucuda çalışıyor.
14. Undo/redo; ekleme, silme, taşıma, kopyalama ve ayar değişikliklerini kapsıyor. Layout çocukları kolonlara bırakılabiliyor, DB'de ayrı adreslenebilir alan olarak saklanıp tekrar nested yükleniyor.
15. Name, Address, Phone ve Map structured değer üretiyor; Repeater satır/çocuk koleksiyonu ve min/max sınırıyla saklanıyor.
16. Upload/Camera akışı uzantı, bildirilen MIME ve gerçek dosya imzasını birlikte denetliyor; private/public, boyut, adet, ön/arka kamera ve foto/video ayarlarına hazır.
17. Payment seçeneklerinde fiyat korunuyor; subtotal/discount/tax/fee/total tarayıcıda önizlenip sunucuda yeniden hesaplanıyor. Sağlayıcı alanları token adapter sözleşmesine bağlı ve plaintext kart verisi kabul etmiyor.
18. Settings, merkezi registry üzerinden **24 modülü** (Genel, Güvenlik, Onay, Bildirim, Tema, başvuru özellikleri ve entegrasyonlar) üretir; form JSON'unda sağlayıcı sırrı kabul edilmez, yalnızca güvenli connection/credential referansı saklanır.
19. Form seviyesi koşullar field görünürlüğü, onay, bildirim ve entegrasyonlarda ortak mantık servisini; metin alanları ise UUID tabanlı ortak Smart Tag servisini kullanır.
20. Ayarlar çalışma alanı dar/geniş mod arasında tek tuşla değişir. Geniş modda canvas gizlenir ve modül menüsü ile ayar içeriği kullanılabilir ekran genişliğinin tamamını kullanır.

## Fields modülü — bilinçli dış bağımlılıklar

- reCAPTCHA/hCaptcha/Turnstile alanı güvenlik mimarisine bağlıdır; canlı provider anahtarı olmadan sahte doğrulama yapılmaz.
- Stripe/PayPal/Square/Authorize.Net adaptör sözleşmeleri hazır fakat gerçek SDK/tokenization, merchant hesabı ve webhook sırrı gelene kadar provider düğmeleri yapılandırılmamış görünür.
- Map structured lat/lng/adres saklar; gerçek harita tile/geocoding/autocomplete için seçilecek sağlayıcı anahtarı gerekir.
- Malware hook ve scan durumları hazırdır; üretim tarayıcı servisi/S3 kararı dış girdidir.

## Üretime çıkmadan önce zorunlu dış girdiler

- Canlı ödeme sağlayıcı hesapları ve webhook sırları.
- Google/Microsoft/CRM OAuth uygulamaları.
- SMTP teslimat sağlayıcısı ve bounce/complaint webhook sözleşmesi.
- S3 uyumlu özel depolama ve seçilen malware tarama servisi.
- Entra tenant/Conditional Access yapılandırması.

Bu bağımlılıklar gelmeden ilgili modüller başarısız işlemi “başarılı” göstermez;
`needs_config` veya açık hata durumunda kalır.
