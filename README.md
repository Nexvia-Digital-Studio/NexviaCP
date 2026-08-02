<h1 align="center"><a href="https://github.com/Nexvia-Digital-Studio/NexviaCP">Nexvia Control Panel (NexviaCP)</a></h1>

<p align="center">
  <img src="web/images/logo.webp" alt="NexviaCP Logo" width="220" />
</p>

<h2 align="center">Nexvia Dijital Stüdyo Projelerine Özel Yüksek Performanslı Web Kontrol Paneli</h2>

<p align="center">
  <strong>Geliştirici & Yayıncı:</strong> <a href="https://github.com/Nexvia-Digital-Studio">Nexvia Digital Studio</a> |
  <strong>Lisans:</strong> GPL-3.0
</p>

---

## 🚀 NexviaCP Nedir?

**NexviaCP (Nexvia Control Panel)**, Nexvia Dijital Stüdyo tarafından geliştirilen web siteleri, SaaS çözümleri, kurumsal müşteriler, **.NET 8 / 9 / 10 Web API**, **Node.js** uygulamaları ve **PHP** projeleri için optimize edilmiş açık kaynaklı, hafif ve ultra performanslı bir Linux web kontrol panelidir.

Tek bir VPS sunucusunda **40+ web sitesini** izole bir şekilde barındırma, **Visual Docker UI & Portainer desteği**, **Full Security Hardening (Tam Güvenlik Koruması)**, akıllı kaynak kısıtlama (**cgroups RAM/CPU limitleme**), **Google Drive 2TB otomatik yedekleme**, **Cloudflare DNS otomatik SSL** ve **Private/Public Git Auto-Deploy** gibi ileri seviye modüllere sahiptir.

---

## 🛡️ Full Güvenlik Koruması & İzolasyon (Security Hardening)

NexviaCP, müşterilerinize SSH yetkisi vermeden güvenle hizmet sunabilmeniz için 4 katmanlı güvenlik korumasına sahiptir:

1. **Docker Konteyner İzolasyonu (Container Breakout Prevention):** Müşterilerin çalıştırdığı Docker konteynerleri kök kullanıcı (root) yetkilerinden tamamen arındırılmıştır (`userns-remap`). Konteyner içinden sunucunun ana dosyalarına (`/etc`, `/home` vb.) erişilmesi engellenmiştir.
2. **Linux cgroups RAM & CPU İzolasyonu:** Her kullanıcının ve uygulamanın bellek ve işlemci sınırı (`MemoryHigh`, `CPUQuota`) bağımsızdır. Bir uygulamanın çökmesi veya kilitlenmesi sunucunun kalanına ve diğer sitelere asla zarar veremez.
3. **HMAC SHA-256 Şifreli Webhook Güvenliği:** Otomatik Git güncellemelerinde GitHub `Secret Key` doğrulaması yapılır. Yetkisiz kişilerin sunucuda tetikleme yapması imkansızdır.
4. **Veritabanı İzolasyonu (User Prefix):** PostgreSQL ve MariaDB veritabanları kullanıcı ön ekleri (`musteri_db`) ile ayrıştırılır. Bir müşteri yalnızca kendi veritabanını yönetebilir.

---

## 🌟 Tüm Özellikler & Yapılabilecekler (Full Feature List)

<details open>
<summary><h3>🌐 1. Web & Alan Adı Yönetimi</h3></summary>

- **40+ İzole Site Barındırma:** Tek VPS üzerinde onlarca PHP, Node.js ve .NET Core sitesini performans kaybı olmadan izole çalıştırma.
- **Görsel Docker UI / Portainer:** SSH erişimi olmayan kullanıcıların Docker konteynerlerini arayüzden yönetebilmesi için `docker-ui` şablonu.
- **.NET 8 / 9 / 10 & ASP.NET Core Desteği:** Kestrel sunucusu üzerinden çalışan .NET Web API ve MVC uygulamalarını Nginx reverse proxy ile yayınlama (`dotnet` şablonu).
- **Node.js & Reverse Proxy:** Express.js, Next.js, NestJS ve Fastify uygulamalarını yayınlama (`node-js` şablonu).
- **Canlı WebSocket Desteği:** Socket.io ve canlı bildirim/mesajlaşma uygulamaları için Nginx Upgrade şablonları (`websocket` şablonu).
- **Nginx + PHP-FPM Hibrit Performans:** Statik dosyalar için yüksek hızlı Nginx, dinamik kodlar için izole PHP-FPM havuzları.
- **Çoklu PHP Sürümü:** Aynı sunucuda PHP 7.4, 8.0, 8.1, 8.2, 8.3 ve 8.4 sürümlerini site bazlı seçebilme.
- **Otomatik WebP Görsel Optimizasyonu:** Yüklenen görselleri sunucu seviyesinde otomatik `.webp` formatına dönüştürme.
- **Domain & Subdomain:** Sınırsız alan adı, alt alan adı (subdomain), takma ad (alias) ve HTTP yönlendirmeleri.

</details>

<details open>
<summary><h3>⚙️ 2. Kaynak Yönetimi & cgroups (RAM / CPU / Storage)</h3></summary>

- **Linux cgroups Limitleme:** Kullanıcı ve site bazında `MemoryHigh`, `CPUQuota` ve `MemorySwapMax` kısıtlaması.
- **Kolay Arayüzle RAM Ayarı:** Kutucuğa `256M`, `512M`, `1G`, `2G` yazarak veya `∞ (Sınırsız)` butonuna basarak RAM belirleme.
- **CPU Kotası:** Yüzde cinsinden işlemci kısıtlama (`%50` yarım çekirdek, `%100` tam çekirdek, `%200` çift çekirdek).
- **Akıllı Dinamik RAM Yükseltme:** Organik trafik yoğunluğuna göre sitelerin RAM limitini kademeli (256MB -> 512MB -> 2GB) artırma ve otomatik düşürme.
- **Paket (Package) Yönetimi:** Farklı kotalara ve limitlere sahip şablon paketler oluşturup sitelere tek tıkla atama.

</details>

<details open>
<summary><h3>🔄 3. Otomasyon & Continuous Deployment (Private / Public Git)</h3></summary>

- **Private & Public Git Auto-Deploy:** Gizli (Private) veya açık repolar için Otomatik Git yayınlama (`deploy.sh`).
- **SSH Deploy Keys & Access Tokens:** Private repolar için güvenli SSH Deploy Key veya Personal Access Token (PAT) desteği.
- **Zero-Downtime Reload:** Güncelleme sırasında PM2 veya systemd ile sitelerin kesintisiz (0ms çökme) güncellenmesi.
- **Otomatik Bağımlılık Yükleme:** `git pull` sonrası `dotnet publish`, `npm install` veya `composer install` süreçleri.

</details>

<details open>
<summary><h3>☁️ 4. Bulut Yedekleme & Veri Güvenliği (Restic & Google Drive)</h3></summary>

- **Google Drive 2TB Entegrasyonu:** `rclone` ve `restic` kullanarak 40 sitenin tüm dosya ve veritabanlarını şifreli olarak Google Drive'a yedekleme.
- **Artımlı (Incremental) Yedekleme:** Yalnızca değişen dosyaları yedekleyerek alan ve zaman tasarrufu sağlama.
- **Çoklu Bulut Desteği:** AWS S3, Backblaze B2, SFTP ve Google Drive yedekleme hedefleri.
- **Tek Tıkla Geri Yükleme (Restore):** Arayüzden istenen günün yedeğini anında geri yükleyebilme.

</details>

<details open>
<summary><h3>🛡️ 5. Güvenlik & SSL Yönetimi</h3></summary>

- **Cloudflare DNS API Entegrasyonu:** Cloudflare kullanarak `*.siteniz.com` şeklinde ücretsiz Wildcard SSL sertifikası üretimi.
- **Otomatik Let's Encrypt SSL:** Standart ve Wildcard SSL sertifikalarını otomatik alma ve süresi dolmadan yenileme.
- **Dahili Saldırı Koruması:** `iptables`, `fail2ban` ve `ipset` ile kaba kuvvet (brute-force) ve IP banlama sistemi.
- **Gelişmiş Giriş Güvenliği:** 2FA (İki Faktörlü Doğrulama) ve SSH IP kısıtlaması.

</details>

<details open>
<summary><h3>⚡ 6. Veritabanı & Nesne Önbellekleme (Caching)</h3></summary>

- **MariaDB / MySQL & PostgreSQL:** Çoklu veritabanı desteği ve veritabanı boyut takibi.
- **phpMyAdmin & pgMyAdmin SSO:** Arayüzden şifre girmeden tek tıkla veritabanı yönetimine geçiş.
- **Redis & Memcached:** Veri tabanı yükünü %90 azaltan uçan hafıza (In-Memory Caching) altyapısı.

</details>

<details open>
<summary><h3>📊 7. Sistem Yönetimi & Dosya Yöneticisi</h3></summary>

- **Dahili Web Dosya Yöneticisi:** Tarayıcı üzerinden dosya yükleme, sürükle-bırak, arşivden çıkarma ve düzenleme.
- **Web Terminali:** SSH istemcisine ihtiyaç duymadan arayüzden Linux terminal komutları çalıştırabilme.
- **RRDtool Sistem Grafikleri:** CPU, RAM, Disk, Ağ ve Veri trafiğini canlı izleme göstergeleri.
- **White-Label Markalama:** Özel logo, favicon, tema ve panel ismi tanımlayabilme.

</details>

---

## 🐳 Görsel Docker UI / Portainer Entegrasyonu (SSH'sız Yönetim)

SSH yetkisi vermediğiniz müşterilerin Docker konteynerlerini arayüzden görsel olarak yönetebilmesi için:

1. Sunucuda Portainer konteyneri başlatın:

```bash
docker run -d -p 9000:9000 --name portainer --restart=always -v /var/run/docker.sock:/var/run/docker.sock portainer/portainer-ce
```

2. NexviaCP arayüzünde `WEB` sekmesinden `portainer.siteniz.com` alan adını ekleyin.
3. **Proxy Template** alanından **`docker-ui`** şablonunu seçin ve **SSL (Let's Encrypt)** aktif edin.
4. Müşteriniz tarayıcısından `https://portainer.siteniz.com` adresine girerek kendi Docker konteynerlerini görsel olarak yönetebilir.

---

## 🛠️ Kurulum Adımları

### Adım 1: Sunucuya SSH ile Bağlanın

```bash
ssh root@sunucu-ip-adresiniz
```

### Adım 2: Kurulum Script'ini İndirin

```bash
wget https://raw.githubusercontent.com/Nexvia-Digital-Studio/NexviaCP/main/install/hst-install.sh
```

### Adım 3: Kurulumu Başlatın

```bash
bash hst-install.sh --nginx yes --phpfpm yes --apache no --multiphp yes --mysql yes --force
```

---

## 🎨 Marka & Telif Hakkı

**NexviaCP**, [HestiaCP](https://www.hestiacp.com/) ve [VestaCP](https://vestacp.com/) projelerini temel alarak [Nexvia Digital Studio](https://github.com/Nexvia-Digital-Studio) için özelleştirilmiş ve geliştirilmiştir. **GPL v3** lisansı altında dağıtılmaktadır.

---

<p align="center">
  <sub>Developed with ❤️ by <a href="https://github.com/Nexvia-Digital-Studio">Nexvia Digital Studio</a></sub>
</p>
