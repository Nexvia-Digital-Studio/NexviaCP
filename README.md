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

**NexviaCP (Nexvia Control Panel)**, Nexvia Dijital Stüdyo tarafından geliştirilen web siteleri, SaaS çözümleri, kurumsal müşteriler, Node.js uygulamaları ve PHP projeleri için optimize edilmiş açık kaynaklı, hafif ve ultra performanslı bir Linux web kontrol panelidir. 

Tek bir VPS sunucusunda **40+ web sitesini** izole bir şekilde barındırma, **Node.js & WebSocket desteği**, akıllı kaynak kısıtlama (**cgroups RAM/CPU limitleme**), **Google Drive 2TB otomatik yedekleme**, **Cloudflare DNS otomatik SSL** ve **Otomatik Git Deploy** gibi ileri seviye modüllere sahiptir.

---

## ⚡ Temel Özellikler & Nexvia Gelişmiş Modülleri

- **Çoklu Site Mimarisi (40+ Site Desteği):** Tek VPS üzerinde onlarca siteyi tamamen izole PHP-FPM havuzlarında performans kaybı olmadan çalıştırır.
- **Node.js & WebSocket Proxy Şablonları:** Express.js, Next.js, NestJS ve Socket.io projelerini tek tıkla varsayılan Nginx şablonlarıyla yayınlama.
- **cgroups (Control Groups) RAM & CPU Sınırlandırma:** Kullanıcı ve web sitesi bazında `MemoryHigh`, `CPUQuota` ve `MemorySwapMax` limitleri tanımlayarak sunucunun çökmesini engeller.
- **Dinamik Kademeli RAM Ölçeklendirme:** Organik trafik yoğunluğuna göre sitelerin RAM kullanımını kademeli olarak (örn. 256MB -> 512MB -> 1GB -> 2GB) artırır ve trafik normale dönünce kaynakları serbest bırakır.
- **Otomatik WebP Görsel Dönüştürme:** Yüklenen görselleri sunucu seviyesinde `.webp` formatına dönüştüren Nginx yapılandırma şablonları.
- **Otomatik Git Deploy (GitHub Webhook):** GitHub'a kod push edildiğinde sunucudaki projenin otomatik çekilmesi ve güncellenmesi (`deploy.sh`).
- **Google Drive 2TB Bulut Yedekleme:** `rclone` ve `restic` entegrasyonu ile tüm sitelerin ve veritabanlarının şifreli olarak Google Drive hesabına otomatik yedeklenmesi.
- **Cloudflare DNS & Wildcard SSL:** Cloudflare API ile `*.siteniz.com` dahil ücretsiz Let's Encrypt Wildcard SSL sertifikası üretimi.
- **Redis & Memcached Caching:** PHP-FPM ve Node.js için uçan hafıza veri tabanı önbellek altyapısı.

---

## 💻 Desteklenen İşletim Sistemleri

* **Ubuntu:** 24.04 LTS, 22.04 LTS (Önerilen)
* **Debian:** 12 (Bookworm), 11 (Bullseye)

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

## 🔧 Gelişmiş Özelliklerin Yapılandırılması

### 1. cgroups (RAM & CPU Sınırlandırması) Aktif Etme
```bash
/usr/local/hestia/bin/v-add-sys-cgroups
```

### 2. Node.js Uygulaması Yayınlama (Reverse Proxy)
1. Node.js projenizi PM2 ile başlatın: `pm2 start app.js --name "my-app"` (Port: 3000)
2. NexviaCP arayüzünde `WEB` sekmesinden sitenizin **Proxy Template** alanından `node-js` veya `websocket` şablonunu seçin.

### 3. Google Drive 2TB Otomatik Bulut Yedekleme Ayarı
1. Sunucuda Rclone yapılandırın:
   ```bash
   rclone config
   # Google Drive seçeneğini belirleyip 2TB hesabınızı yetkilendirin (Remote adı: gdrive)
   ```
2. NexviaCP Restic yedekleme servisine Google Drive'ı ekleyin:
   ```bash
   /usr/local/hestia/bin/v-add-backup-host-restic rclone gdrive:nexvia-backups
   ```

### 4. Cloudflare DNS API ile Wildcard SSL (`*.siteniz.com`)
1. Sunucuya Cloudflare API bilgilerinizi tanımlayın:
   ```bash
   export CLOUDFLARE_API_KEY="your-cloudflare-api-token"
   export CLOUDFLARE_EMAIL="info@nexviadigital.com"
   ```
2. Wildcard SSL oluşturun:
   ```bash
   /usr/local/hestia/bin/v-add-letsencrypt-domain admin siteniz.com "" "yes"
   ```

### 5. Otomatik Git Deploy (GitHub Webhook)
Siteniz için otomatik Git yayınlamasını aktif edin:
```bash
/usr/local/hestia/bin/v-add-web-domain-git admin siteniz.com https://github.com/Nexvia-Digital-Studio/proje.git main
```
*GitHub'da reponuzun Settings > Webhooks kısmına `https://siteniz.com/deploy.php` adresini eklemeniz yeterlidir.*

---

## 🎨 Marka & Telif Hakkı

**NexviaCP**, [HestiaCP](https://www.hestiacp.com/) ve [VestaCP](https://vestacp.com/) projelerini temel alarak [Nexvia Digital Studio](https://github.com/Nexvia-Digital-Studio) için özelleştirilmiş ve geliştirilmiştir. **GPL v3** lisansı altında dağıtılmaktadır.

---

<p align="center">
  <sub>Developed with ❤️ by <a href="https://github.com/Nexvia-Digital-Studio">Nexvia Digital Studio</a></sub>
</p>
