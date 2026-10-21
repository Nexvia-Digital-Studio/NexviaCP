# PHP güvenlik güncellemesi - 21 Ekim 2026

CVE-2026-109618 ve CVE-2026-109619 PHP motorunda kimlik doğrulamasız
kod çalıştırmaya yol açabilir. Uygulama kodu çalışmadan tetiklenebildiği için
`ini_set()`, `.user.ini` veya bir uygulama kontrolü kesin çözüm değildir.
Resmî duyuru: https://www.php.net/archive/2026.php#2026-10-21-1

Yamalı asgari sürümler: **8.2.35, 8.3.36, 8.4.27, 8.5.12**.
PHP 8.1 ve daha eski dallar için iki CVE'yi de giderdiğini açıkça belgeleyen
üretici paketi veya desteklenen dala test edilmiş geçiş gerekir.

## Sunucuda uygulama

Önce yapılandırma/paket envanterini ve geri dönüş yedeğini alın.

```sh
python3 ops/php-security.py
sudo python3 ops/php-security.py --update
```

`--update` Debian/Ubuntu'da kurulu PHP dallarının CLI/FPM/CGI, Apache modülü,
eklenti ve `hestia-php` paketlerini depodan günceller. Dal değiştirmez, paket
kaldırmaz. Önceden aktif PHP-FPM, Apache ve panel servislerini yeniden başlatır;
kısa bağlantı kesintisi olabilir. Depoda yama yoksa işlem sonundaki denetim
başarısız kalır. Özel derleme ve konteynerler otomatik güncellenmez.
Denetim salt okunurdur; 0 gözlenen disk sürümleri yamalı, 1 eski/bilinmeyen
sürüm veya motor bulunamadı, 2 çalıştırma hatasıdır. Geri taşınmış üretici
paketlerini sürümden kanıtlayamaz, bunları ihtiyatla başarısız sayar.

## Yayın doğrulaması

- CLI sonucundan bağımsız olarak her domainin bağlı olduğu gerçek FPM/Apache
  sürümünü doğrulayın. Eski işçilerin tamamını yeniden başlatın.
- NexviaCP paneli `/usr/local/hestia/php` altında ayrı PHP taşır. Kaynaktaki
  sürüm değişikliği ancak `hestia-php` paketi derlenip kurulunca etkili olur.
- PHP içeren Docker imajlarını yamalı tabandan `--pull --no-cache` ile yeniden
  derleyin, konteynerleri yeniden oluşturun ve içlerindeki motoru doğrulayın.
  FrankenPHP/LiteSpeed ve uzak sunucular ayrıca denetlenmelidir.
- Ana sayfa, giriş, dosya yükleme ve varsa ödeme/bildirim callback akışlarını
  kontrol edin. Kamuya açık `phpinfo()` veya tanılama endpoint'i bırakmayın.

`session.upload_progress.enabled=0` ancak sunucu başlangıç ayarı ve tüm işçiler
restart edilirse bilinen bir saldırı yolunu azaltır; iki açığın yaması değildir.
Bellek limitini sınırsız yapma veya dosya yüklemeyi kapatma otomatik uygulanmaz.
CI yamalı PHP ile sürüm ve sözdizimi kontrol eder; **main pushu veya yeşil CI,
canlı sunucunun PHP paketinin güncellendiğini kanıtlamaz**.
