# PHP2Shell canlı güncelleme kaydı

21 Ekim 2026: CVE-2026-109618 ve CVE-2026-109619 için sunucu güncellemesi.

## Kurulan sürümler

| Motor | Güncelleme sonrası |
| --- | --- |
| Panel sunucusu PHP 8.1 | Sury 8.1.34-9, iki CVE için geri taşıma |
| Panel sunucusu PHP 8.2 | 8.2.35 |
| Panel sunucusu PHP 8.3 | 8.3.36 |
| Panel sunucusu PHP 8.4 | 8.4.27 |
| Panel sunucusu PHP 8.5 | 8.5.12 |
| NexviaCP'nin ayrı motoru | hestia-php 8.5.12-1+debian13 |
| Eski Digituk sunucusu | Ubuntu 8.1.2-1ubuntu2.28, iki CVE için geri taşıma |

Sury PHP 8.1 paketinin değişiklik kaydı iki CVE'yi açıkça listeler.
Ubuntu kanıtları: [CVE-2026-109618](https://ubuntu.com/security/CVE-2026-109618)
ve [CVE-2026-109619](https://ubuntu.com/security/CVE-2026-109619).

## Panelin ayrı motoru

Hestia deposunda aday paket 8.5.9'da kaldığından 8.5.12 resmî kaynak paketi,
mevcut motorla aynı configure seçenekleriyle sunucuda derlendi.
Modül listeleri birebir eşleşti; yeni FPM motoru mevcut yapılandırma testini geçti.
Mevcut panel yapılandırması korunarak Debian paketi kuruldu ve servis yeniden başlatıldı.

- Kaynak: `https://www.php.net/distributions/php-8.5.12.tar.xz`
- Kaynak SHA-256: `08bc2eba2a588654ade1357174aa646b761556c862dda0678345ee29ed481209`
- Üretilen paket SHA-256: `d900c33cd6e5f2601bbc65dc89509bcf71908d669568685a6ac9a3df8ffa06bb`

Paket, kaynak, derleme günlükleri ve önceki motorun arşivi panel sunucusunda
`/root/php-security-20261021/` altında tutulur. Eski Digituk sunucusunda da
aynı dizinde yapılandırma yedeği ve güncelleme kanıtı vardır.

## Kontrol kapsamı

Panel sunucusunda 13 PHP CLI/FPM dosyası, eski Digituk sunucusunda 2 dosya
yamaya uygun bulundu. FPM servisleri yeniden başlatıldı; çalışan master
süreçlerinin `/proc/<pid>/exe -v` çıktıları da yeni sürümlerle doğrulandı.
Bu kontrol yalnızca diskteki paket sürümüne dayanmaz.

Panel girişi, Digituk, Wedding, Takı, Ece Hukuk, Gökhan Menajerlik ve
We Are Clean ana sayfaları HTTP 200; Hub HTTP 302 giriş yönlendirmesi döndürdü.
Nginx yapılandırma testi ve tüm ilgili servislerin aktiflik kontrolü başarılıdır.
Bu doğrulama ödeme, SMS veya oturum gerektiren iş akışlarının uçtan uca testi değildir.
