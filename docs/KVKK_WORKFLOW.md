# KVKK Talep Akışı

KVKK talepleri sistemde oluşturulur ve denetlenebilir kayıtlar olarak saklanır. İlk sürümde talepler, kimlik doğrulaması yapılmadan otomatik işleme alınmaz; yetkili personel **Admin → Raporlama → KVKK talepleri** ekranından talebi aday kaydıyla eşleştirerek oluşturur.

## İşleyiş

1. Talep e-posta, dilekçe veya kurumun doğrulanmış iletişim kanalından alınır.
2. Personel talep sahibinin kimliğini kurum prosedürüne göre doğrular.
3. Aday, talep türü (erişim/dışa aktarma, silme, anonimleştirme veya düzeltme), iletişim adresi ve notlar sisteme kaydedilir.
4. Dışa aktarma talepleri JSON paketine dönüştürülür. Silme ve anonimleştirme talepleri açık bir onay adımıyla işlenir.
5. İşlemi yapan kullanıcı, zaman ve sonuç denetim kaydında tutulur.

Kamuya açık bir “KVKK talebi gönder” formu daha sonra eklenebilir; bu uç kimlik doğrulama, e-posta teyidi, hız sınırlama ve kötüye kullanım koruması tamamlanmadan doğrudan veri silme/anonimleştirme işlemi başlatmamalıdır.
