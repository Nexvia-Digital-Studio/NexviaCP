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

NexviaCP, müşterilerinize SSH yetkisi vermeden güvenle hizmet sunabilmeniz için 5 katmanlı güvenlik korumasına sahiptir:

1. **Docker Konteyner İzolasyonu (Container Breakout Prevention):** Müşterilerin çalıştırdığı Docker konteynerleri kök kullanıcı (root) yetkilerinden tamamen arındırılmıştır (`userns-remap`). Portainer Agent, `docker.sock`'u doğrudan mount etmez; CE arayüzü `portainer-agent-net` ağı üzerinden haberleşir.
2. **Portainer Admin-Only Kilit (3 Katmanlı Savunma):** Portainer portları **sadece 127.0.0.1'e** bağlıdır (dışarıdan doğrudan erişim yok). Tek giriş yolu olan `docker-ui` nginx vhostu **HTTP basic auth** şifresi ister (katman 2), ardından Portainer'ın kendi şifresi (katman 3). Panelde Docker menüsü ve `docker-ui` şablonu **yalnızca admin rolündeki kullanıcılar** görür; müşteriler erişemez.
3. **Linux cgroups İzolasyonu:** **PHP siteleri** için per-site RAM sınırları (`memory_limit × max_children`, gerçek peak hesabı) + per-user kernel CPU limiti (`user-<uid>.slice CPUQuota`). **Node.js / .NET / Next.js** uygulamaları için ise **her siteye ayrı systemd birimi** ayrılarak gerçek per-site kernel-seviye `MemoryMax` + `CPUQuota` cgroup izolasyonu uygulanır (`v-add-web-domain-app`). Bir sitenin çökmesi veya kilitlenmesi diğerlerini etkilemez.
4. **HMAC SHA-256 Şifreli Webhook Güvenliği:** Otomatik Git güncellemelerinde GitHub `Secret Key` doğrulaması yapılır. `deploy.php` dosyası `chmod 640` ile izole edilmiştir — başka müşteriler webhook secret'ınızı okuyamaz.
5. **Veritabanı İzolasyonu (User Prefix):** PostgreSQL ve MariaDB veritabanları kullanıcı ön ekleri (`musteri_db`) ile ayrıştırılır. Bir müşteri yalnızca kendi veritabanını yönetebilir.

> **Dürüst not (PHP CPU izolasyonu):** Tüm PHP siteleri tek FPM master altında paylaşıldığı için per-site CPU kotası mimari olarak imkânsızdır; bu nedenle CPU kısıtlaması kullanıcı bazında (kernel), RAM kısıtlaması ise site bazında (PHP + app backends için kernel) uygulanır. Node.js / .NET backends her biri ayrı systemd biriminde olduğu için tam kernel-seviye per-site CPU+RAM izolasyonu alır.

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
- **Çoklu PHP Sürümü:** Aynı sunucuda PHP 7.4, 8.0, 8.1, 8.2, 8.3, 8.4 ve 8.5 sürümlerini site bazlı seçebilme.
- **Domain & Subdomain:** Sınırsız alan adı, alt alan adı (subdomain), takma ad (alias) ve HTTP yönlendirmeleri.

</details>

<details open>
<summary><h3>⚙️ 2. Kaynak Yönetimi & cgroups (RAM / CPU / Storage)</h3></summary>

- **Linux cgroups Limitleme (Per-Site):** Her site/domain için `MemoryHigh` (baseline), `MemoryMax` (peak) ve `CPUQuota` ayrı ayrı ayarlanabilir; değerler PHP-FPM pool'a (`memory_limit`, `pm.max_children`) otomatik yansır.
- **Kolay Arayüzle RAM Ayarı:** Kutucuğa `256M`, `512M`, `1G`, `2G` yazarak veya `∞ (Sınırsız)` butonuna basarak RAM belirleme.
- **CPU Kotası:** Yüzde cinsinden işlemci kısıtlama (`%50` yarım çekirdek, `%100` tam çekirdek, `%200` çift çekirdek).
- **Akıllı Dinamik RAM Yükseltme (memory.pressure bazlı):** Her site için belirlenen baseline ↔ peak aralığında, çekirdeğin `memory.pressure` değerini okuyarak RAM limitini kademeli olarak yükseltip düşüren gerçek zamanlı izleyici (`v-monitor-memory-pressure`).
- **Paket (Package) Yönetimi:** Farklı kotalara ve limitlere sahip şablon paketler oluşturup sitelere tek tıkla atama.

</details>

<details open>
<summary><h3>🔄 3. Otomasyon & Continuous Deployment (Private / Public Git)</h3></summary>

- **Private & Public Git Auto-Deploy:** Gizli (Private) veya açık repolar için Otomatik Git yayınlama (`deploy.sh` + `deploy.php` webhook receiver).
- **HMAC SHA-256 Webhook Güvenliği:** Her site için 64 karakterlik benzersiz secret üretilir; `deploy.php`, gelen isteğin `X-Hub-Signature-256` başlığını HMAC-SHA256 ile doğrular. Yetkisiz tetikleme imkansızdır.
- **SSH Deploy Keys & Access Tokens:** Private repolar için güvenli SSH Deploy Key veya Personal Access Token (PAT) desteği.
- **Zero-Downtime Reload:** Güncelleme sırasında PM2 veya systemd ile sitelerin kesintisiz (0ms çökme) güncellenmesi.
- **Otomatik Bağımlılık Yükleme:** `git pull` sonrası `dotnet publish`, `npm install` veya `composer install` süreçleri.

</details>

<details open>
<summary><h3>☁️ 4. Bulut Yedekleme & Veri Güvenliği (Restic & Google Drive)</h3></summary>

- **Google Drive 2TB Entegrasyonu:** `restic` + `rclone` kullanarak 40 sitenin tüm dosya ve veritabanlarını uçtan uca şifreli olarak Google Drive'a yedekleme.
- **Artımlı (Incremental) Yedekleme:** Yalnızca değişen dosyaları yedekleyerek alan ve zaman tasarrufu sağlama.
- **Geniş Protokol Desteği:** FTP, SFTP, Backblaze B2 ve Rclone üzerinden Google Drive / S3 / R2 / Wasabi hedefleri (Rclone bu servislerin tümunu kapsar).
- **Tek Tıkla Geri Yükleme (Restore):** Arayüzden istenen günün yedeğini anında geri yükleyebilme.

</details>

<details open>
<summary><h3>🛡️ 5. Güvenlik & SSL Yönetimi</h3></summary>

- **Cloudflare DNS API Wildcard SSL:** Sunucu Ayarları'ndan Cloudflare API Token + Zone ID girildiğinde, `*.siteniz.com` wildcard Let's Encrypt sertifikaları için `_acme-challenge` TXT kaydı Cloudflare API'sine otomatik yazılır ve silinir — yerel DNS zone gerektirmez.
- **Otomatik Let's Encrypt SSL:** Standart ve Wildcard SSL sertifikalarını otomatik alma ve süresi dolmadan yenileme.
- **Dahili Saldırı Koruması:** `iptables`, `fail2ban` ve `ipset` ile kaba kuvvet (brute-force) ve IP banlama sistemi.
- **Gelişmiş Giriş Güvenliği:** 2FA (İki Faktörlü Doğrulama) ve SSH IP kısıtlaması.

</details>

<details open>
<summary><h3>⚡ 6. Veritabanı & Nesne Önbellekleme (Caching)</h3></summary>

- **MariaDB / MySQL & PostgreSQL:** Çoklu veritabanı desteği ve veritabanı boyut takibi.
- **phpMyAdmin & phpPgAdmin SSO:** Arayüzden şifre girmeden tek tıkla veritabanı yönetimine geçiş. phpPgAdmin için her tıklamada 60 dakikalık geçici PostgreSQL rolü (TTL) üretilir.
- **Redis & Memcached:** Arayüzden tek tıkla kurulup tüm PHP sürümleri için etkinleştirilen, veri tabanı yükünü %90 azaltan In-Memory Caching altyapısı.

</details>

<details open>
<summary><h3>📊 7. Sistem Yönetimi & Dosya Yöneticisi</h3></summary>

- **Dahili Web Dosya Yöneticisi:** Tarayıcı üzerinden dosya yükleme, sürükle-bırak, arşivden çıkarma ve düzenleme.
- **Web Terminali:** SSH istemcisine ihtiyaç duymadan arayüzden Linux terminal komutları çalıştırabilme.
- **RRDtool Sistem Grafikleri:** CPU, RAM, Disk, Ağ ve Veri trafiğini canlı izleme göstergeleri.
- **White-Label Markalama:** Özel logo, favicon, tema ve panel ismi tanımlayabilme.

</details>

---

## 🐳 Görsel Docker UI / Portainer Entegrasyonu (Admin-Only, Kilitli)

NexviaCP, Portainer CE'yi güvenli (hardened) ve **sadece ana yöneticiye açık** bir yapıyla kurar. Müşterileriniz Portainer'a erişemez.

1. Root olarak NexviaCP'nin Portainer kurulum script'ini çalıştırın (veya kurulumda `--portainer yes` bayrağını kullanın):

```bash
v-add-sys-portainer
```

Bu komut Portainer CE ve Portainer Agent'ı ayrı konteynerler olarak başlatır, `userns-remap` ile Docker hardening uygular, `docker.sock`'u doğrudan mount etmez ve `portainer-agent-net` ağı üzerinden haberleşir. **Portainer portları yalnızca 127.0.0.1'e bağlıdır** — dışarıdan doğrudan erişilemez. Kurulum sonunda size bir **nginx auth_basic kullanıcı adı + şifresi** üretilir (bunları kaydedin).

2. NexviaCP arayüzünde `WEB` sekmesinden `portainer.siteniz.com` alan adını ekleyin. (`docker-ui` şablonu panelde **sadece admin'e** görünür.)
3. **Proxy Template** alanından **`docker-ui`** şablonunu seçin ve **SSL (Let's Encrypt)** aktif edin.
4. `https://portainer.siteniz.com` adresine girerken **önce nginx şifresi** (kurulumda üretilen), **ardından Portainer şifresi** istenir. Müşterilerinizin panelinde Docker menüsü hiç görünmez.

---

## 🛠️ Kurulum Adımları

> **Önemli:** Kurulum **iki adımlıdır**. 1. adım temel Hestia paketlerini ve tüm sistem servislerini (nginx, PHP-FPM, MariaDB, PostgreSQL, mail, DNS, Docker...) kurar; 2. adım NexviaCP'ye özel kodu (Node.js/.NET app yöneticisi, cgroup limitleri, Portainer entegrasyonu, arayüz) panelin üzerine uygular. 2. adımı atlamayın — aksi halde panelde NexviaCP özellikleri görünmez.

**Gereksinimler:** Ubuntu 22.04 / 24.04 (veya Debian 12), en az 2 GB RAM (tüm özelliklerle 4 GB önerilir), temiz bir sunucu, root erişimi.

### Adım 1: Depoyu Klonlayın ve Temel Kurulumu Başlatın

```bash
apt-get update && apt-get install -y git
git clone https://github.com/Nexvia-Digital-Studio/NexviaCP.git /root/NexviaCP
cd /root/NexviaCP

# Tüm NexviaCP özellikleri (önerilen, test edilmiş komut):
bash install/hst-install.sh \
  --apache no --phpfpm yes --multiphp yes \
  --mysql yes --postgresql yes \
  --vsftpd yes --named yes \
  --exim yes --dovecot yes --clamav no --spamassassin no \
  --iptables yes --fail2ban yes \
  --resourcelimit yes \
  --docker yes --portainer yes --redis yes \
  --hostname panel.siteniz.com \
  --email admin@siteniz.com \
  --interactive no --force
```

**Sadece temel PHP + MySQL istiyorsanız:**

```bash
bash install/hst-install.sh --apache no --phpfpm yes --mysql yes --force
```

> **Notlar:**
> - `--hostname` bir FQDN olmalı ve en az iki nokta içermelidir (`panel.siteniz.com` gibi; `sunucu` veya `siteniz.com` reddedilir).
> - `--portainer yes` otomatik olarak `--docker yes`'i de etkinleştirir.
> - `.NET SDK'ları` (`--dotnet yes`) yalnızca dağıtımınızda gerçekten mevcut olan sürümler kurulur (ör. Ubuntu 24.04: 8.0 + 10.0). Microsoft repo'su codename'iniz için yayınlanmamışsa dağıtım paketleri kullanılır; eksik sürüm pinlemesi kurulumu artık kırmaz.
> - Minimal bulut imajlarında `fail2ban` başlamazsa: `rsyslog`'un çalıştığından ve `/var/log/auth.log` dosyasının mevcut olduğundan emin olun.

### Adım 2: NexviaCP Kaynağını Panele Uygulayın

Kurulum bitince **aynı sunucuda**, aynı depo dizininden:

```bash
cd /root/NexviaCP
bash install/nexvia-apply-source.sh . --docker --portainer --redis
```

Bu betik NexviaCP'nin `bin/`, `func/`, `web/` ve şablon dosyalarını `/usr/local/hestia` üzerine kopyalar, arayüz varlıklarını (npm ile) derler, paneli yeniden başlatır ve istenen eklentileri (Docker hardening, Portainer, Redis, Memcached, phpPgAdmin SSO) devreye alır. Portainer'ın nginx `auth_basic` şifresi konsolda görüntülenir — kaydedin.

#### Tüm NexviaCP bayrakları

| Bayrak | Açıklama | Varsayılan |
|---|---|---|
| `--redis` | Redis object cache | no |
| `--memcached` | Memcached object cache | no |
| `--docker` | Docker engine + userns-remap hardening | no |
| `--portainer` | Portainer CE (admin-only, 127.0.0.1) | no |
| `--dotnet` | Mevcut .NET SDK'ları (8.0 / 9.0 / 10.0) | no |
| `--postgresql` | PostgreSQL | no |
| `--multiphp` | Çoklu PHP sürümleri (7.4-8.5) | no |
| `--resourcelimit` | Kullanıcı bazlı RAM/CPU limit arayüzü | no |

#### Kaynak güncellemesi (repo değiştiğinde)

Repoda yapılan değişiklikleri kurulmuş sunucuya tekrar uygulamak için Adım 2'yi yeniden çalıştırmanız yeterlidir (`git pull` sonrası `bash install/nexvia-apply-source.sh .`).

---

## 🎨 Marka & Telif Hakkı

**NexviaCP**, [HestiaCP](https://www.hestiacp.com/) ve [VestaCP](https://vestacp.com/) projelerini temel alarak [Nexvia Digital Studio](https://github.com/Nexvia-Digital-Studio) için özelleştirilmiş ve geliştirilmiştir. **GPL v3** lisansı altında dağıtılmaktadır.

---

<p align="center">
  <sub>Developed with ❤️ by <a href="https://github.com/Nexvia-Digital-Studio">Nexvia Digital Studio</a></sub>
</p>
