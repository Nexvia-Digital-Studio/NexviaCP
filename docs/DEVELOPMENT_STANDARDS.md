# 🚀 NexviaCP — Geliştirici & Proje Standartları Kılavuzu

Bu kılavuz, **Nexvia Digital Studio** bünyesinde geliştirilen tüm Web Sitelerinin ve API projelerinin (PHP, Node.js, .NET Core, React, Python) **NexviaCP** üzerinde sıfır yapılandırmayla (zero-config) tek tıkla kurulması, otomatik veritabanı açılması, `.env` secret yönetimi ve GitHub CI/CD ile canlıya alınması için gereken standartları içerir.

---

## 📁 1. `.env` ve Secret (Ortam Değişkenleri) Standartları

NexviaCP, Zero-Knowledge güvenlik modeli kullanır. GitHub'dan bir proje kurulduğunda veya güncellendiğinde:
* `.env` dosyası **projenin ana dizinine (`public_html/.env`)** otomatik olarak `chmod 600` yetkisiyle oluşturulur.
* `git pull` veya GitHub Release güncellemelerinde `.env` dosyası **asla silinmez veya ezilmez**.
* Paneldeki **Global Key Vault** anahtarları (örn: `GEMINI_API_KEY`, `GOOGLE_MAPS_KEY`) yeni kurulan her sitenin `.env` dosyasına otomatik enjekte edilir.

### 🔹 Teknolojilere Göre `.env` Okuma Şablonları

#### 🐘 1. PHP / Laravel / Vanilla Projeler

```php
// Yöntem A: Doğrudan getenv / $_ENV ile okuma
$geminiKey = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? '');
$dbHost    = getenv('DB_HOST') ?: '127.0.0.1';
$dbName    = getenv('DB_NAME') ?: 'veritabani_adi';
$dbUser    = getenv('DB_USER') ?: 'root';
$dbPass    = getenv('DB_PASSWORD') ?: (getenv('DB_PASS') ?: '');

// Yöntem B: Dotenv kütüphanesiyle (Önerilen)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();
```

#### 🟢 2. Node.js / Express / Next.js / Fastify

```javascript
// dotenv ile ana dizindeki .env otomatik okunur
require('dotenv').config();

const port = process.env.PORT || 3000;
const geminiApiKey = process.env.GEMINI_API_KEY;
const dbConfig = {
  host: process.env.DB_HOST || '127.0.0.1',
  database: process.env.DB_NAME,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
};
```

#### 🟣 3. .NET 8 / 9 / 10 ASP.NET Core & Web API

```csharp
// Program.cs — .NET Core varsayılan olarak Environment değişkenlerini otomatik okur
var builder = WebApplication.CreateBuilder(args);

var geminiKey = builder.Configuration["GEMINI_API_KEY"] 
             ?? Environment.GetEnvironmentVariable("GEMINI_API_KEY");

var connString = $"Server={builder.Configuration["DB_HOST"] ?? "127.0.0.1"};" +
                 $"Database={builder.Configuration["DB_NAME"]};" +
                 $"User={builder.Configuration["DB_USER"]};" +
                 $"Password={builder.Configuration["DB_PASSWORD"]};";
```

#### 🐍 4. Python / Django / FastAPI / Flask

```python
import os
from dotenv import load_dotenv

load_dotenv() # .env dosyasını yükler

GEMINI_API_KEY = os.environ.get("GEMINI_API_KEY")
DB_HOST = os.environ.get("DB_HOST", "127.0.0.1")
DB_NAME = os.environ.get("DB_NAME")
DB_USER = os.environ.get("DB_USER")
DB_PASSWORD = os.environ.get("DB_PASSWORD")
```

---

## 🗄️ 2. Otomatik Veritabanı (MySQL) Standartları

NexviaCP, reponuzda veritabanı şeması gördüğünde **sıfır dokunuşla**:
1. Panelde kullanıcı adına izole bir veritabanı (`admin_proje`) ve şifre oluşturur.
2. Veritabanını paneldeki **`VERİTABANI`** sekmesine ekler (phpMyAdmin ile yönetilebilir hale getirir).
3. SQL dosyasını otomatik içe aktarır (import).
4. Veritabanı bağlantı bilgilerini projenin `.env` dosyasına otomatik yazar:
   ```env
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=admin_projeadi
   DB_USER=admin_projeadi
   DB_PASSWORD=otomatik_guclu_sifre
   ```

### 📌 Yapmanız Gereken Tek Şey

Reponuzun ana dizinine veya `build/` klasörüne şu dosyalardan birini koymaktır:
* `schema.sql` veya `database.sql` veya `build/schema.sql`

---

## ⚡ 3. GitHub CI/CD Otomatik Güncelleme (Webhook)

GitHub'da kod güncellediğinizde veya yeni bir Release yayınladığınızda sitenin otomatik güncellenmesi için:
1. GitHub Reponuz -> **Settings** -> **Webhooks** -> **Add webhook**
2. **Payload URL:** `https://<panel-domaininiz>:8083/webhook/github/`
3. **Content type:** `application/json`
4. **Events:** `Just the push event` veya `Releases`

---

## 🏗️ 4. Proje Türlerine Göre Dizin Standartları

| Teknoloji | Giriş Noktası (Entry Point) | Derleme / Çalıştırma |
| :--- | :--- | :--- |
| **PHP / Laravel** | `index.php` veya `public/index.php` | `composer install --no-dev -o` (Otomatik) |
| **React / Vue** | `index.html` ve `dist/` | `npm run build` (Otomatik) |
| **Node.js** | `server.js` veya `app.js` veya `index.js` | `npm install --production` (Otomatik Port & Systemd) |
| **.NET Core** | `*.csproj` veya `*.sln` | `dotnet publish -c Release` (Otomatik Port & Kestrel) |
| **Python** | `main.py` veya `app.py` | `pip install -r requirements.txt` |
