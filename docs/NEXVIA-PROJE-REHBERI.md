# 📘 NexviaCP — Proje Hazırlık & Yayına Alma Rehberi (Tüm Yığınlar + Docker)

Bu rehber, **NexviaCP** üzerinde site/API kurulacak GitHub repolarının nasıl hazırlanması
gerektiğini teknoloji teknoloji anlatır: klasör yapısı, repoda bulunması gerekenler /
bulunmaması gerekenler, otomasyonların (veritabanı, `.env`, port, domain, snapshot) nasıl
çalıştığı ve işlerinizi kolaylaştıracak alışkanlıklar.

> Kısa özet için `docs/DEVELOPMENT_STANDARDS.md`'e, komut referansı için İngilizce doküman
> setine (`docs/docs/nexvia/git-deploy.md`, `docker-apps.md`, `app-runtimes.md`) bakabilirsiniz.
> Bu belge, o bilgilerin **nasıl/niçin** tarafını derinlemesine ele alır.

---

## 🧭 0. Önce Karar: Hangi Kurulum Kanalı?

NexviaCP'de bir projeyi yayına almanın **iki kanalı** vardır. Repoyu hazırlamadan önce
hangi kanalı kullanacağınıza karar verin, çünkü klasör yapısı kuralları buna göre değişir.

| | **Kanal 1: Git Deploy** (tek süreçli uygulamalar) | **Kanal 2: Docker Compose App** (çoklu servisli) |
|---|---|---|
| **Komut** | `v-deploy-github-repo` / paneldeki "GitHub'dan Site Kur" modalı | `v-add-docker-app` / panelde `/add/docker/` |
| **Ne çalışır** | Kod, sunucuda doğal olarak çalışır: PHP→php-fpm, Node→systemd süreci, .NET→Kestrel, React→statik dosya | Compose dosyasının başlattığı HER şey: birden çok container, istediğiniz dil/sürüm |
| **Dil desteği** | PHP, Laravel, React/Vite (statik), Node.js, .NET (Python vb. yok) | Sınırsız — image olan her şey (Go, Java, Python, Redis, Kafka...) |
| **Veritabanı** | Şema görürse MySQL'i kendisi açar (`schema.sql` kuralı) | Compose içindeki kendi DB'niz (Postgres, MySQL, Mongo...) |
| **Ne zaman** | Tek parça site/API, standart yığın, hızlı kurulum | Çok servisli sistem, özel runtime sürümü, legacy uygulama, DB+API+UI birlikte |
| **Domain** | Kurulum anında verilir, LE sertifikası panelden tek tık | Servis bazlı: `v-add-docker-app-domain APP DOMAIN SERVICE` |

**Pratik kural:** Tek bir süreciniz varsa (bir PHP sitesi, bir Node API'si, bir React SPA)
Kanal 1'i kullanın — sıfır yapılandırmayla kurulur. İki veya daha fazla **ayrı süreç**
birlikte çalışacaksa (API + worker + DB + panel), ya da uygulamanız sunucuda kurulu
olmayan bir runtime'a muhtaçsa Kanal 2.

Her iki kanalda da ortak olan bir şey: **repo GitHub'da olmalı** (private olabilir) ve
**deploy edilebilir dal** (branch) main/master gibi net olmalı.

---

## 📐 1. Tüm Projeler İçin Ortak Kurallar

Bu bölüm PHP/Node/.NET/React/Docker fark etmeksizin her repo için geçerlidir.

### 1.1 Repoda BULUNMASI GEREKENLER (altın checklist)

```
proje/
├── README.md              # ne olduğu, nasıl kurulduğu, giriş noktası
├── .gitignore             # üretilen/gerçek dosyalar dışarıda kalsın
├── .env.example           # TÜM gerekli değişkenlerin ÖRNEK (değer değil anahtar) hali
├── schema.sql             # (DB kullanıyorsa) tablo yapısı + ilk veriler  ← otomasyon tetikleyicisi
└── ...                    # teknolojiye özgü dosyalar (bölüm 2-6)
```

**Neden bu dört dosya önemli:**

- **`README.md`** — Sizden sonra bakan kişi (ya da 6 ay sonraki siz) deploy'ın nasıl
  yapıldığını, `.env`'de ne olması gerektiğini bilmeli. Panelde repo seçerken isim
  kafa karıştırmasın: amaç ve girişler bir paragrafla yazılı olsun.
- **`.gitignore`** — Aşağıdaki "olmamalı" listesinin uygulanmış hâli. Repoda yoksa
  ilk deploy'da ekleyin.
- **`.env.example`** — NexviaCP kurulumda repoda `.env.example` görürse onu `.env`
  olarak kopyalar. Değişken adlarınızı burada **tanımlayın**, değerleri asla yazmayın.
- **`schema.sql`** — Sihirli dosya. Repoda (kökte veya `build/` altında)
  `schema.sql` / `database.sql` / `db.sql` / `install.sql` / `build/schema.sql`
  adlarından biri varsa kurulum sırasında otomatik veritabanı açılır (detay: 1.5).

### 1.2 Repoda BULUNMAMASI GEREKENLER (ve nedenleri)

Bunların her biri canlıda yaşanmış sorunlara dayanır; maddeler niye tehlikeli olduğunu
da açıklar:

| ❌ Dosya/Dizin | Neden olmamalı |
|---|---|
| **Gerçek `.env`** (değerli hali) | Şifreler, API anahtarları GitHub'a sızar. Sadece `.env.example` girsin. Panel kurulumda `.env`'i kendisi oluşturur/yönetir; deploy güncellemeleri mevcut `.env`'i **asla ezmez**. |
| **`vendor/`, `node_modules/`** | Kurulum bunları sunucuda kendisi üretir (`composer install`, `npm install`). Commit'lenmiş hâli depoyu şişirir, platform farkları yüzünden çalışmaz, güncelleme pull'larını yavaşlatır. |
| **`dist/`, `build/` çıktıları** (React/Vite) | Deploy sırasında sunucuda `npm run build` çalıştırılır. Commit'lenen eski `dist`, sunucudaki derlemeden farklı bir site yayınlarsa saatlerce arıza ararsınız. |
| **`bin/`, `obj/`** (.NET) | `dotnet publish` çıktısı zaten sunucuda üretilir; sürüm uyumsuz artifact'ler 502 döndürür. |
| **`*.sql` dökümleri / yedekler** (`backup_2024.sql`, `yedek/`, `*.bak`, `*.old`) | Bir kere gerçek hayatta yaşandı: kökteki `database.sql` tarayıcıdan indirilebildi. Nginx `.(sql\|bak\|old\|log\|ini\|env\|sqlite\|db)$` uzantılarını artık **403/404** ile kapatıyor ama dosya repoda olduğu sürece risk ve şişkinlik devam eder. Yedekler panel Yedekleme özelliğine ya da object storage'a ait. |
| **IDE/OS dosyaları** (`.idea/`, `.vscode/` (shared config hariç), `.DS_Store`) | Gürültü; `.gitignore`'a ekleyin. |
| **Büyük binary/medya** (>10-50 MB) | Clone her deploy'da tekrarlanır (`--depth 1` olsa bile). Büyük dosyalar Git LFS ya da CDN/object storage'da durmalı. |
| **Deploy anahtarları, `.pem`, `id_rsa`, `serviceAccount.json`** | Secret yönetimi repoya değil panele (Domain → Çevre Değişkenleri / Global Vault) ait. |
| **Boş/deneysel dallarda bırakılmış kritik düzeltmeler** | Deploy belirtilen daldan yapılır; "çalışıyor ama hangi dalda?" belirsizliği olmasın. |

**Ek güvenlik notu:** Kurulum sonrası panel dosya izinlerini otomatik sertleştirir —
dizinler `755`, dosyalar `644`, `.env` `600`, `.git/` `700`. Yani `.git` klasörü web'den
indirilemez hâle gelir. Yine de **repo'da gizli dosya tutmamak** tek gerçek güvencedir.

### 1.3 `.env` Düzeni (tüm teknolojiler)

Kurulum/güncelleme sırasında `.env` davranışı şu kurallarla çalışır:

1. Docroot'ta `.env` **yoksa** ve repoda `.env.example` varsa → kopyalanır. İkisi de
   yoksa boş `.env` oluşturulur.
2. `.env` **varsa** → güncelleme deploy'larında aynen korunur (sitenin ayarları
   kaybolmaz).
3. Otomatik DB açıldıysa bağlantı bilgileri `.env`'e **yazılır/düzeltilir** — hem yaygın
   adlandırma varyantları tek tek güncellenir: `DB_HOST`, `DB_PORT`, `DB_NAME`,
   `DB_DATABASE`, `DB_USER`, `DB_USERNAME`, `DB_PASS`, `DB_PASSWORD`. (Framework'ün
   hangi isimlendirmeyi sevdiği önemli değildir; hepsi aynı doğru değeri alır.)
4. Global Key Vault'taki admin anahtarları (panele ait API key'ler vb.) site `.env`'lerine
   **bilinçli olarak enjekte edilmez** — sitenin kodu, panelin küresel anahtarlarını
   görmemelidir. Siteye özel anahtarları domain'in `.env`'ine elle girin.

**Sizin göreviniz:** kodda tüm yapılandırmayı `.env`'ten okumak ve `.env.example`'ı
tam/dokümante tutmak. Kodda `localhost` şifre `1234` gibi hardcode değer bırakmayın —
kurulum ortamı sizin bilgisayarınız değil.

### 1.4 Otomatik Veritabanı: `schema.sql` Konvansiyonu

DB kullanan bir proje için yapmanız gereken tek şey şemayı doğru yere koymak:

```
proje/
├── schema.sql        ← kök (önerilen)
│   veya build/schema.sql
```

Kurulum bunu görünce:

1. Panelde kullanıcıya ait **izole bir MySQL DB'si ve DB kullanıcısı** açar
   (`kullanıcı_<repoadı>`, güçlü rastgele şifre) ve DB'yi panelin VERİTABANI
   sekmesine ekler (phpMyAdmin'den yönetilebilir olur).
2. `schema.sql`'u bu yeni DB'ye **kendi DB kullanıcısıyla** import eder (root değil).
3. Bağlantı bilgilerini `.env`'e yazar (1.3'teki liste).

**Şema dosyası kuralları:**

- `CREATE TABLE`'lar `IF NOT EXISTS` ile yazılmalı (idempotent) — güncelleme deploy'ları
  aynı şemayı tekrar import edebilmeli.
- `CREATE DATABASE` / `USE` satırı **yazmayın** — DB zaten açıktır, adı ortama göre değişir.
- İlk verileri (seed: kategoriler, admin satırı vb.) aynı dosyaya koyabilirsiniz.
- DB adı kuralı: `kullanıcı_` öneki + repo kimliğinden türetilen en fazla **12 karakterlik**
  ad. Organizasyon repolarında repo adı, link ile kurulan açık kaynak repolarda
  `sahip_repo` kullanılır. (Örn: `admin_mercan_dis` gibi.) Uzun adlar kırpılır —
  benzer adlı iki repo aynı DB adına düşmesin diye repo adlarınızı ayırt edici tutun.

> Not: Şema yoksa DB de açılmaz — kodu DB'siz çalışan bir statik site/SPA olarak
> kuruluma bırakabilirsiniz; DB'yi sonradan panelden elle açıp `.env`'e yazmak da
> her zaman mümkündür.

### 1.5 Dal (Branch) Stratejisi ve Deploy Edilen Sürüm

- Deploy, belirttiğiniz **dalın o anki uç komiti**nden yapılır (`--depth 1` klon).
  Dal belirtilmezse `main` denenir, yoksa repo varsayılan dalı kullanılır.
- Panel modalında **linkle kurulumda** `https://github.com/kullanici/proje/tree/dev`
  gibi bir bağlantı yapıştırırsanız `dev` dalı otomatik seçilir.
- Öneri: `main` = canlıya çıkan kod. Denemeler feature dallarında; PR önizleme
  ortamı (aşağıda) zaten dallar için otomatik test alanı üretir.

### 1.6 Güncelleme, Snapshot ve Geri Alma (rollback) — İşleri Kolaylaştıranlar

Bu araçları bilmek deploy korkusunu bitirir:

- **Otomatik güncelleme (webhook):** GitHub → Settings → Webhooks →
  `https://panel.alanadiniz.com/webhook/github/` (HMAC imzalı). Push yaptığınızda site
  kendi kendine güncellenir. Her domain için ayrı webhook secret otomatik üretilir.
- **Tek domain elle güncelle:** Panelde satırdaki 🔄 güncelle butonu ya da
  `v-update-web-domain-git USER DOMAIN` (pull + yeniden derleme).
- **Toplu senkron:** `v-sync-github-repos` — bağlı tüm domainleri paralel günceller.
- **Snapshot'lar:** Her deploy'dan önce docroot'un tam kopyası arşivlenir
  (`data/users/USER/web/DOMAIN/releases/release-<zaman>.tar.gz`, en yeni **5** tutulur).
- **Geri alma:** `v-rollback-web-domain-deploy USER DOMAIN previous` — bir önceki
  sürüme saniyeler içinde dönün; rollback öncesi de snapshot alınır, yani geri alma
  bile geri alınabilir. Liste: `v-list-web-domain-deploys USER DOMAIN`.
- **PR önizleme:** `v-deploy-github-pr USER taban-domain PR_NO` — her pull request'e
  korumalı geçici alt domain; merge olunca temizlenir. Web listesinde 🧪 rozetiyla görünür.

**Alışkanlık önerisi:** Riskli değişikliklerden önce elle snapshot şart değil (otomatik
alınıyor) ama deploy sonrası ilk iş sitenin kritik 2-3 sayfasını açmak; sorun görünürse
tek komutla `previous`'a dönmek. Bu döngü hızlandığında "canlıya çıkma korkusu" kaybolur.

---

## 🐘 2. PHP ve Laravel

### 2.1 Kurulum sırasında ne olur?

1. Kod docroot'a kopyalanır; `composer.json` varsa `composer install --no-dev -o`.
2. `artisan` dosyası varsa **Laravel** olarak algılanır: `php artisan key:generate --force`
   çalıştırılır, APP_KEY üretilir.
3. `schema.sql` kuralı geçerliyse DB açılır ve `.env`'e `DB_DATABASE/DB_USERNAME/DB_PASSWORD`
   yazılır (Laravel'in beklediği adlar otomatik doldurulur).
4. nginx php-fpm `default` şablonu bağlanır: `try_files $uri $uri/ /index.php?$args` —
   yani ön denetleyici (front controller) deseni hazır gelir.

### 2.2 Önerilen klasör yapısı — sade PHP

```
proje/
├── index.php               # TEK giriş noktası; tüm istekler buraya akmalı
├── src/  veya  app/        # sınıflar, iş mantığı
├── assets/                 # css/js/img (statik, public)
├── schema.sql              # DB kullanıyorsa (tablo + seed)
├── .env.example            # örnek: DB_*, APP_ENV=production, MAIL_* ...
├── composer.json           # bağımlılık kullanıyorsanız (+ composer.lock commit'te!)
└── .gitignore              # vendor/, .env, *.sql dump'ları
```

### 2.3 Önerilen klasör yapısı — Laravel

```
proje/
├── artisan
├── public/                 # Laravel'in gerçek web kökü
│   ├── index.php
│   └── .htaccess
├── app/  resources/  routes/  config/  ...
├── composer.json + composer.lock
├── schema.sql              # migrations İLK kurulumda değil, sıfır kurulumda işe yarar
├── .env.example            # APP_* boş bırakın (key:generate dolar), DB_* zaten otomatik
└── .gitignore              # vendor/, storage/*.key, .env
```

> **⚠️ Önemli tuzak — `public/` dizini:** Kurulum docroot'u repo kökü yapar; Laravel'in
> girişi ise `public/index.php`'dir. Siteniz `domain.com/public/...` şeklinde açılıyorsa
> ya da ana sayfa 404 veriyorsa iki temiz çözüm var:
>
> **A (önerilen, dokunmasız):** Reponun köküne, `/public`'ı kökten sunan küçük bir
> yönlendirici `index.php` koyun:
> ```php
> <?php // kök index.php — tüm istekleri Laravel'e aktarır
> $_SERVER['SCRIPT_NAME'] = '/index.php';
> require __DIR__ . '/public/index.php';
> ```
> **B:** Sunucu tarafında docroot'u `public/`'a çevirmek için panelden destek isteyin
> ( elle nginx conf güncellemesi gerekir; A her zaman yeterlidir).

### 2.4 Olması / olmaması gerekenler (PHP özeti)

- ✅ `composer.lock` **commit'te olsun** (sürümler sabitlenir; `--no-dev -o` ile kurulum yapılır).
- ✅ `storage/` ve `bootstrap/cache` yazma izinleri kurulum sonrası otomatik 755'e çekilir;
  Laravel "permission denied" log yazarsa panelden dosya yöneticisiyle o iki dizini kontrol edin.
- ❌ `vendor/`, gerçek `.env`, `*.sql` dökümleri, `storage/logs/*.log` — repoda olmasın.
- 💡 Kolaylaştırıcı: Şemanızı `schema.sql`'a da basitleştirilmiş hâlde koyun — yeni bir
  ortama 5 dakikada sıfırdan kurulan repo = stres yok.

---

## ⚛️ 3. React / Vite / Statik SPA

### 3.1 Kurulum sırasında ne olur?

1. Repo taranır: `dist/` varsa (derlenmiş çıktı) doğrudan docroot köküne kopyalanır.
   `dist/` yoksa ve `package.json` varsa `npm run build` çalıştırılır, çıkan `dist/`
   köke taşınır. (Modalda mod olarak "React/Vite" seçmek algılamayı zorlar.)
2. Statik dosya olarak sunulur (PHP backend'i devreye girmez; `index.html` kök landığı
   için `/` direkt açılır).

### 3.2 Önerilen klasör yapısı

```
proje/
├── package.json            # "build": "vite build" (ya da react-scripts build)
├── package-lock.json       # commit'te olsun (node_modules DEĞİL)
├── vite.config.js          # base: './' önerisi (alt dizin/CDN uyumu)
├── index.html
├── src/                    # kaynak kod
├── public/                 # statikler (favicon vb.)
├── .env.example            # VITE_API_URL=...  ← build-time değişkenleri
└── .gitignore              # node_modules/, dist/
```

### 3.3 Bilinmesi gereken iki davranış

**a) Ortam değişkenleri DERLEME anına gömülür.** SPA tarayıcıda çalıştığı için `.env`
dosyası çalışma anında okunamaz; `VITE_*` / `REACT_APP_*` değerleri `npm run build`
sırasında JS'e gömülür. Sonuç: panelde `.env` değiştirmek SPA'yı **değiştirmez** —
değişikliği repoya (ya da build arg'larına) koyup "GitHub'dan Güncelle" ile yeniden
derletin. API adresi gibi şeyler için mutlak URL'leri build öncesi netleştirin.

**b) Derin linkler (client-side routing).** Sunucu `try_files $uri $uri/ /index.php`
ile cevap verir; `site.com/hakkimizda` gibi SPA yolları sunucuda dosya olmadığından
404 riski taşır. Çözüm köke tek satırlık bir `index.php` fallback koymaktır (JS devrey
girer, route'u kendisi çözer):

```php
<?php // SPA fallback — derin linkleri React Router'a bırakır
readfile(__DIR__ . '/index.html');
```

Bu dosya `dist/`'e girmeli (Vite'ta `public/index.php` koyarsanız build çıktısına kopyalanır).

### 3.4 Olması / olmaması gerekenler

- ❌ `node_modules/`, commit'lenmiş `dist/` (eski çıktı canlıya sızar), gerçek anahtarlar,
  production sourcemap'leri (`build.sourcemap: false`).
- ✅ `package-lock.json`, net `build` script'i, `.env.example` (değerler build-time).
- 💡 Sunucudaki Node sürümü sabittir; çok yeni/çok eski bir Node isteyen projelerde
  dist'i CI'da üretip yayınlamak ya da Kanal 2 (Docker) mantıklıdır.

---

## 🟢 4. Node.js (Express / NestJS / Fastify / Next.js SSR)

### 4.1 Kurulum sırasında ne olur?

1. `package.json` varsa Node uygulaması olarak algılanır: `npm install --production`.
2. Giriş dosyası şu öncelikle seçilir: **`server.js` → `app.js` → `index.js`**.
   (Başka isim kullandıysanız dosyanızı buna göre adlandırın ya da `server.js`
   içinden require edin.)
3. Uygulama **izole bir systemd servisi** olarak, kendi portunda `127.0.0.1`'e
   dinleyecek şekilde ayağa kaldırılır; nginx önünde `node-js` şablonuyla proxy yapar.
   Sürece şu ortam değişkenleri verilir:
   - `PORT` — uygulamanın DİNLEMESİ GEREKEN port (sistem atar, önceden bilinmez)
   - `NODE_ENV=production`
4. Port her güncellemede aynı kalır (domain kaydına yazılır) — URL'ler/monitörler şaşmaz.

### 4.2 Kritik kural: `PORT`'u dinleyin

En sık hata: uygulamanın `app.listen(3000)` ile portu **sabit** dinlemesi. Sistem
farklı bir port verdiğinde nginx boş adrese proxy yapar → **502 Bad Gateway**.
Doğrusu:

```js
const port = process.env.PORT || 3000;   // PORT sistemden gelir
app.listen(port, () => console.log(`:${port} dinleniyor`));
```

`127.0.0.1`'e bağlanan Express default'u yeterlidir (nginx aynı makineden bağlanır).

### 4.3 Önerilen klasör yapısı

```
proje/
├── server.js               # giriş (ya da app.js / index.js)
├── src/  veya  routes/     # iş mantığı
├── package.json            # "start": "node server.js" + engines: { "node": ">=20" }
├── package-lock.json       # commit'te
├── .env.example            # PORT yazmayın (sistem verir); DB_*, JWT_SECRET...
├── schema.sql              # API arkasında MySQL varsa (otomatik DB açılır)
└── .gitignore              # node_modules/, .env, logs
```

`package.json` ipuçları:

```json
{
  "scripts": { "start": "node server.js" },
  "engines": { "node": ">=20" }
}
```

### 4.4 Next.js notları

- SSR/mod SSR Next projeleri bu kanalda çalışır (Node süreci olarak). **`next build` +
  `next start`** akışını `server.js` içinde yönetin ya da `start` script'ini
  `"next start -p $PORT"` düzenine uyarlayın.
- Ağır/canary Next sürümleri, özel sharp/image servisleri, alternatif Node sürümü
  gereksinimleri varsa projeyi **Docker kanalına** (bölüm 6) almak daha kararlıdır —
  image içinde istediğiniz Node sürümünü sabitlersiniz.

### 4.5 Olması / olmaması gerekenler

- ❌ `node_modules/`, `.env`, `logs/`, test coverage dökümleri, sabit `listen(3000)`.
- ✅ lockfile, health endpoint (`GET /healthz` → 200), zarif kapanma (`SIGTERM` handler)
  — systemd restart'larında yarım kalan iş bırakmamak için.
- 💡 Statik çıktıya da inebilen Next/Vite projelerinde (tamamen SPA ise) Kanal 1'in
  React modu en hızlısıdır.

---

## 🟣 5. .NET (ASP.NET Core Web API / MVC)

### 5.1 Kurulum sırasında ne olur?

1. Kökte `*.csproj` veya `*.sln` varsa .NET olarak algılanır; `dotnet publish -c Release
   -o publish` ile derlenir.
2. Çıktıdaki **ilk "kendi" DLL'iniz** bulunur (Microsoft.*/System.* hariç) ve
   `dotnet publish/Uygulama.dll` komutuyla izole systemd servisi olarak başlatılır.
3. nginx önünde `dotnet` şablonu (Kestrel proxy) bağlanır. Sürece:
   - `ASPNETCORE_URLS=http://127.0.0.1:<otomatik-port>` verilir — Kestrel bunu
     doğrudan dinler, `UseUrls("...")` yazmanıza gerek yok (yazarsanız silin).
4. Port domain kaydına yazılır; güncellemelerde aynı port korunur.

### 5.2 Önerilen klasör yapısı

```
proje/
├── Uygulama.csproj          # TEK proje kökte = en sorunsuz algılama
├── Program.cs
├── appsettings.json         # secret YOK; sadece yapılandırma
├── appsettings.Production.json
├── .env.example             # harici servis anahtarları için (opsiyonel)
├── schema.sql               # MySQL kullanıyorsa (otomatik DB + .env)
└── .gitignore               # bin/, obj/, *.user
```

Çok projeli solution'larda (`*.sln` + birden fazla csproj): publish çıktısından
doğru DLL seçilebilmesi için web projesini köke koyun ya da `v-add-web-domain-app`
ile elle başlatma komutu belirtin. En garantisi: **repo kökü = yayınlanacak web
projesi**; class library'ler alt klasörlerde olabilir.

### 5.3 Yapılandırma kuralları

- Dinlenecek adresi **kodda sabitlemeyin** (`UseUrls`/`appsettings`'te `urls` yok).
  `ASPNETCORE_URLS` sistemden gelir; çakışma 502'nin birinci sebebidir.
- `ASPNETCORE_ENVIRONMENT` production sürümünde `Production`'dır; `appsettings.Production.json`
  ile ortam farkını yönetin.
- DB bağlantısı: `schema.sql` kuralı işlediyse `DB_SERVER/DB_DATABASE/DB_USER/DB_PASSWORD`
  değerleri `.env`'e yazılır — .NET tarafında bunları `AddEnvironmentVariable()` ile
  okuyun (ConnectionStrings'i env'den dolduran ufak bir builder satırı yeterli).

### 5.4 Olması / olmaması gerekenler

- ❌ `bin/`, `obj/`, `*.user`, `appsettings.json` içinde **gerçek bağlantı string'i/anahtar**,
  publish çıktıları.
- ✅ csproj kökte, `Program.cs` minimal, health endpoint (`/health`), lockfile benzeri
  `packages.lock.json` (NuGet lock mode) kullanıyorsanız commit'te.
- 💡 Çok runtime'lı ya da Windows-bağımlılıklı değil ama özel SDK isteyen projelerde
  Docker kanalı (bölüm 6) her zaman yedek çözümdür.

---

## 🐳 6. Docker Compose Çoklu Servis Uygulamaları

Bu bölüm **birden çok container'ın birlikte çalıştığı sistemler** içindir: API + DB +
önyüz, worker + kuyruk + panel, ya da elinize geçmiş herhangi bir compose projesi.
Kanal 1'in "tek süreç" sınırı burada kalkar: compose dosyanız **tek doğruluk kaynağıdır**,
dili/teknojiyi serbestçe seçersiniz (Go, Java, Python, Redis, MinIO...).

### 6.1 Nasıl çalışır? (kurulumdan yayına akış)

```
GitHub repo (docker-compose.yml)
        │   v-add-docker-app uygulama REPO [DAL] [COMPOSE_FILE] [FORCE] [DEPLOY_CMD]
        ▼
/usr/local/hestia/data/docker-apps/<uygulama>/
    repo/                  git klonu (token'lı, private repo olur)
    app.conf               kaynak repo, dal, compose dosyası, DEPLOY_CMD...
    nexvia-override.yml    OTOMATİK üretilen güvenlik katmanı (aşağıda)
    .env                   compose değişkenleri — panelde düzenlenir
    deploy.log             son deploy'ın tam çıktısı (arıza teşhisinin ilk durağı)
        │   docker compose --env-file .env -f repo/compose.yml -f nexvia-override.yml
        │                    -p nexvia-<uygulama> up -d --build --remove-orphans
        ▼
   container'lar çalışır; yayınlanan her port 127.0.0.1'e sabitlenmiş hâlde
        │   v-add-docker-app-domain uygulama DOMAIN SERVIS[:PORT]
        ▼
   servis, LE sertifikalı domain ile dünyaya açılır (nginx proxy)
```

Deploy arka planda, kilitli (aynı anda tek deploy) ve durum makinelidir:
`deploying → running | failed`; panelde canlı görürsünüz.

### 6.2 Repo yapısı: compose kökte, her şey net

```
proje/
├── docker-compose.yml      # KÖKTE (başka yerdeyse kurulumda COMPOSE_FILE argümanı verin)
├── .env.example            # compose değişkenlerinin şablonu (değer yok, anahtar var)
├── api/                    # servis 1 — build context + Dockerfile
│   ├── Dockerfile
│   └── src/...
├── web/                    # servis 2 — Dockerfile
│   └── Dockerfile
├── scripts/
│   └── deploy-smart.sh     # (opsiyonel) özel deploy akışı → DEPLOY_CMD kancası
└── README.md               # hangi servis neyi yayınlar, hangi port, hangi env
```

Örnek — iyi huylu bir compose dosyası:

```yaml
services:
  api:
    build: ./api
    restart: unless-stopped          # sunucu yeniden başladığında kendi kendine döner
    env_file: .env                   # panelden yönetilen .env'i servis alır
    ports: ["8080:8080"]             # publish ŞART (bkz. kural 6) — dışa açılmaz!
    depends_on:
      db:
        condition: service_healthy  # DB hazır olmadan API başlamasın
    healthcheck:
      test: ["CMD", "curl", "-fsS", "http://localhost:8080/health"]
      interval: 30s
      timeout: 5s
      retries: 3

  web:
    build: ./web
    restart: unless-stopped
    ports: ["8081:80"]

  db:
    image: postgres:16.4            # etiketi SABİTLEYİN, latest DEĞİL
    restart: unless-stopped
    environment:
      POSTGRES_DB: ${POSTGRES_DB}
      POSTGRES_USER: ${POSTGRES_USER}
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD}   # değerler .env'de, repo'da değil!
    volumes:
      - dbdata:/var/lib/postgresql/data          # veri named volume'da yaşar
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${POSTGRES_USER}"]
      interval: 10s
      retries: 5

volumes:
  dbdata:
```

**Compose altın kuralları (hepsi yaşanmış hatalardan):**

1. **Image etiketlerini sabitleyin** (`postgres:16.4`, `node:20.11-alpine`). `latest`
   kullanan servis bir gün güncellenir ve sisteminiz davranış değiştirir.
2. **`restart: unless-stopped`** her uzun ömürlü serviste olsun — sunucu restart'ında
   yığın kendi kendine kalkar.
3. **`healthcheck` + `depends_on.condition`** — DB ayağa kalkmadan API'nin ilk isteklerde
   patlamasını engeller.
4. **Kalıcı veri = named volume.** Bağlama (bind mount) yerine volume kullanın; güncelleme
   deploy'ları kodu değiştirir, volume'lara dokunmaz.
5. **Secret'lar `.env`'de**, repoda değil. Compose `${DEGISKEN}` substitution'ı panelin
   yazdığı `.env`'ten beslenir (bkz 6.4).
6. **Domain'e açacağınız servislerde `ports:` yazın; dünyaya açılmaktan korkmayın.**
   Domain eşlemesi yalnızca **publish edilmiş** portları görür: `expose:` ile yetinirseniz
   servis panelde eşleşmez (`service publishes no port` hatası). Ama `ports:` yazmak
   dışarıya açılmak demek DEĞİLDİR — güvenlik katmanı her yayını otomatik 127.0.0.1'e
   bağlar (bkz 6.3); erişim yalnızca `v-add-docker-app-domain` ile olur.
7. **`container_name` vermeyin.** Proje öneki (`nexvia-<app>`) ile çakışma riski doğar.

### 6.3 Güvenlik modeli: `nexvia-override.yml` ve port kuralları

Sistem her deploy'da compose envanterinizi tarar ve **tüm yayınlanan (`ports:`) host
portlarını 127.0.0.1'e bağlayan bir override dosyası** üretir. Bunun anlamı:

- Docker'da alışılan "8080:80 yayınla, dünyadan bağlan" deseni **bilinçli olarak
  kapatılmıştır**. Container portları dış arabirimden (0.0.0.0) ASLA yayınlanmaz;
  güvenlik duvarını delip sunucuyu ele geçirme yüzeyi bırakılmaz.
- Servisler dünyaya yalnızca **domain eşlemesiyle** açılır (6.6) — nginx + LE arkasında.

Port davranışları tablosu:

| Durum | Davranış |
|---|---|
| Port zaten `127.0.0.1`'e bağlı ve boşta değil (sizinki çalışıyor) | **Aynı numara korunur** (örn. `127.0.0.1:5002:5000` yerinde kalır) |
| Port 0.0.0.0'a ya da başka adrese bağlı | Override ile 127.0.0.1'e çevrilir; numara korunur |
| Port serbest | Paylaşılan uygulama aralığından (**9100–9999**) çakışmasız atama |
| Güncelleme deploy'ı | (servis, container-portu) → host-portu eşlemesi **kararlı tutulur**; her seferinde yeni port şaşmaz |

**Preflight (ön denetim) reddi:** compose dosyanızda şu tehlikeli desenlerden biri varsa
kurulum ** reddedilir** ve ancak `FORCE=yes` ile geçersiz kılınabilir:

- `privileged: true` (container'a root yetkileri)
- Docker socket bağlama (`/var/run/docker.sock`) — container host'u ele geçirebilir
- `network_mode: host` / `pid: host` (izolasyon tamamen kalkar)

Bunlara gerçekten ihtiyacınız varsa nedenini iyi bilin; normal web/API/DB yığınlarında
hiçbiri gerekmez.

### 6.4 `.env` yönetimi

- Uygulamanın `.env`'i repoda DEĞİL, `data/docker-apps/<app>/.env`'dedir ve **panelden
  düzenlenir** (uygulama detay sayfasındaki editör) ya da `v-save-docker-app-env` ile
  yazılır. Kaydettiğinizde değişkenler yeni deploy'a dek geçerli olur.
- Şablon olarak repoda `.env.example` bulundurun: hangi değişkenlerin zorunlu olduğunu
  belgeleyen tek yer olsun.
- Global panel anahtarları (GHCR token'ı hariç) container'lara otomatik verilmez.

### 6.5 İmaj kaynakları

| Kaynak | Davranış |
|---|---|
| **Build** (`build:`) | Sunucuda derlenir (`up -d --build`). En basit yol — Dockerfile'ınız düzgünse hiç registry uğraşı yok. |
| **Docker Hub / public registry** | Doğrudan çekilir. |
| **GHCR private image** | Panelin GitHub token'ı ile otomatik login yapılır. **Token'da `packages:read` yetkisi olmalı**; "401/denied" alırsanız eksik olan budur. |

### 6.6 Servisleri domain ile yayınlama

```
v-add-docker-app-domain uygulama DOMAIN SERVIS[:CONTAINER_PORT] [KULLANICI]
v-add-docker-app-domain mercan api.mercan.com api          # api servisinin EXPOSE/port'u
v-add-docker-app-domain mercan panel.mercan.com web:8080    # farklı konteyner portu belirtilebilir
```

- nginx, domaini servisin loopback portuna proxy'ler; **LE sertifikası** panel akışıyla
  tek tıkla alınır (uygulama detay sayfasında domain+LE alanı).
- Servis başına ayrı domain verin; tek bir "her şeyi içinde tutan NPM container'ı"
  (80/443 yayınlayan nginx-proxy-manager) yerine bu yerleşik eşlemeyi kullanın.

> **⚠️ NPM tuzağı (yaşanmış):** Compose'a nginx-proxy-manager koyup 80/443 yayınlarsanız
> güvenlik katmanı bu portları da 127.0.0.1'e çeker → NPM kendi LE (http-01) doğrulamasını
> yapamaz, sertifikalar düşer. Çözüm: NPM'i atın, her servisi `v-add-docker-app-domain`
> ile panelden domain'e bağlayın; LE'yi panel yürütür.

### 6.7 Özel deploy akışları: `DEPLOY_CMD` ve blue-green

- Kurulumda 6. argüman **DEPLOY_CMD** ise (örn. `bash scripts/deploy-smart.sh`), compose
  up'tan sonra o komut çalışır. Komut ortamında `NEXVIA_COMPOSE_FILES` değişkeni
  hazırdır (doğru `-f`/`--env-file` setiyle compose'u tekrar çağırmak için):
  ```bash
  # scripts/deploy-smart.sh içinde
  docker compose $NEXVIA_COMPOSE_FILES up -d api_b --no-deps
  ```
- **Blue-green deseni** (kesintisiz güncelleme): `api_a` ve `api_b` diye iki özdeş servis
  tanımlayın; ikisini de sabit loopback portlarına bağlayın (örn. `127.0.0.1:5002`,
  `127.0.0.1:5003` — port koruma kuralı sayesinde numaralar update'lerde sabit kalır).
  Deploy script'i sırayla: eskisini durdur, yenisini kaldır, domain eşlemesini yeni porta
  çevir. Panel domain'i güncellediğinde trafik yeni sürüme akar.

### 6.8 Yaşam döngüsü komutları ve panel

```bash
v-add-docker-app      uygulama REPO [DAL] [COMPOSE_FILE] [FORCE] [DEPLOY_CMD]  # kur
v-list-docker-apps                                                      # liste (panel: /list/docker/)
v-list-docker-app      uygulama [json]                                   # durum/servisler (panel: detay sayfası)
v-update-docker-app    uygulama                                          # git pull + yeniden up (panel/API ile de)
v-restart-docker-app   uygulama                                          # restart
v-suspend-docker-app   uygulama                                          # durdur (fatura cezası/bakım)
v-unsuspend-docker-app uygulama                                          # geri aç
v-list-docker-app-logs uygulama [SERVIS]                                 # loglar (panelde canlı izlenir)
v-list-docker-app-env  uygulama                                          # .env oku
v-save-docker-app-env  uygulama 'KEY=deger KEY2=deger2'                  # .env yaz + redeploy
v-delete-docker-app    uygulama                                          # SİLER — volume'lar dahil!
v-add-docker-app-domain uygulama DOMAIN SERVIS[:PORT] [KULLANICI]        # domain + LE
v-delete-docker-app-domain uygulama DOMAIN
```

Panel tarafında: `/add/docker/` kurulum formu, `/list/docker/` uygulama listesi,
`/list/docker-app/?app=<ad>` detay sayfası (servisler, loglar, domain+LE, .env editörü,
tehlikeli alan: sil/askıya al). API'den de aynı işler yapılır:
`cmd=v-add-docker-app&arg1=...&arg2=...`, durum takibi `cmd=v-list-docker-app`.

### 6.9 İyi huylu Docker repoları için özet checklist

- [ ] Compose dosyası kökte (ya da COMPOSE_FILE argümanı ile yol net)
- [ ] Image etiketleri sabit, `restart: unless-stopped`, healthcheck'ler var
- [ ] Kalıcı veri named volume'larda; `.env.example` mevcut; repoda secret yok
- [ ] Domain'e açılacak servislerin hepsinde `ports:` publish tanımı var (expose değil)
- [ ] Dış erişim yalnızca servis→domain eşlemesiyle planlanmış; `0.0.0.0` beklentisi yok
- [ ] `privileged`/docker.sock/host-network YOK (gerçekten gerekiyorsa FORCE gerekçesi yazılı)
- [ ] README'de: servis → port → domain eşlemesi tablosu, zorunlu `.env` anahtarları

**Operasyonel notlar:** İlk docker-app kurulumunda `/etc/docker/daemon.json`'a log
rotasyonu (container başına 10 MB × 3 dosya) otomatik yazılır — log diski doldurma korkusu yok.
Bir deploy bozulursa ilk bakılacak yer `deploy.log` (compose'un ham çıktısı) ve
`v-list-docker-app-logs`.

---

## 🧰 7. Panelde İşinizi Kolaylaştıran Diğer Araçlar (özet)

| Araç | Ne yapar |
|---|---|
| **GitHub'dan Site Kur modalı** | Organizasyon repoları listelenir; son seçenekle **herhangi bir açık kaynak GitHub reposunun linki** girilerek (`github.com/x/y`, `/tree/dal` destekli) kurulur. Token yoksa bile public repo kurulur. |
| **🔄 GitHub'dan Güncelle** | Satır butonu: pull + bağımlılık + derleme akışını tekrar yürütür. |
| **Webhook otomatiği** | Push → site kendini günceller (HMAC doğrulamalı alıcı). |
| **PR Önizleme (🧪)** | Her PR'a geçici korumalı alt domain. |
| **Snapshot + Rollback** | Her deploy öncesi arşiv (5 sürüm); `previous`'a tek komut dönüş. |
| **Otomatik MySQL** | `schema.sql` görünce DB+user+import+`.env` zinciri. |
| **Talep skoru motoru** | Trafiğe göre PHP bellek/CPU sınırlarını 5 dakikada bir kendisi ayarlar — boş site şişirmez, yoğun siteyi büyütür. |
| **WAF + fail2ban** | Domain bazlı WAF (Enterprise Threat Shield) + 7 fail2ban jail hazır. |
| **phpMyAdmin SSO** | DB sekmesinden tek tık şifresiz yönetim. |
| **LE + Cloudflare API** | Domain ekleme/sertifika/DNS tamamen panelden. |

---

## 🚑 8. Sık Karşılaşılan Sorunlar — Teşhis ve Çözüm

| Belirti | Sebep | Çözüm |
|---|---|---|
| **502 Bad Gateway** (Node/.NET) | App, verilen `PORT`/`ASPNETCORE_URLS` portunu dinlemiyor; sabit port dinliyor | `process.env.PORT` / UseUrls kaldırma (4.2, 5.3) |
| Laravel ana sayfa 404/boş | Docroot repo kökü, giriş `public/` altında | Kök `index.php` yönlendiricisi (2.3) |
| SPA'da derin link 404 | Sunucu rota dosyasını bilmiyor | `public/index.php` fallback shim (3.3b) |
| SPA'da `.env` değişikliği etkisiz | Değişkenler build-time'a gömülü | Repoda değiştir + "GitHub'dan Güncelle" (3.3a) |
| DB bağlantı hatası kuruluptan sonra | Şema yok / `.env` eski | `schema.sql` ekle ya da panelden DB açıp `.env`'e yaz (1.4) |
| İki repo aynı DB'ye düştü | 12 karakterlik DB adı kırpması benzeşmiş | Repo adlarını ayırt edici adlandır; panelden DB adını elle düzelt |
| GHCR image 401/denied | Token'da `packages:read` yok | Token yetkilerine ekle (6.5) |
| Docker app "rejected" | privileged / docker.sock / host network | Kaldır ya da FORCE=yes + gerekçe (6.3) |
| Domain açıldı ama sertifika yok (NPM altında) | 80/443 loopback'e çekilmiş, http-01 çalışmıyor | NPM yerine servis→domain eşlemesi (6.6) |
| Deploy uzun sürüyor / başlamıyor gibi | Build ağır; durum `deploying` | `deploy.log` izle; panelde durum `failed` ise çıktının son satırları sebep gösterir |

---

## ✅ 9. Son Kontrol Listeleri (deploy öncesi 2 dakika)

**Genel:**
- [ ] `.env.example` tam ve değerlerden arındırılmış
- [ ] Repoda secret / dump / `vendor` / `node_modules` / `dist` / `bin` / `obj` yok
- [ ] README'de kuruluma dair 5 satır var
- [ ] Deploy edilecek dal net (`main` önerilir)

**PHP/Laravel:** composer.lock var · Laravel'de kök `index.php` shim · storage yazılabilir
**React/SPA:** build script adı `build` · SPA fallback `public/index.php` · env'ler build-time
**Node:** giriş `server.js`/`app.js`/`index.js` · `PORT` env'den dinleniyor · healthz
**.NET:** csproj kökte · UseUrls yok · appsettings'te secret yok
**Docker:** compose kökte · sabit etiketler · restart policy · healthcheck · volume'lar ·
`.env.example` · preflight'ten geçen güvenli tanım · servis→domain planı

Bu listeleri geçen bir repo, panelde **tek tıkla** kurulur, güncellenir ve gerekirse
tek komutla önceki sürümüne döner.
