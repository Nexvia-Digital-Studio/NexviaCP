# NexviaCP — Düzeltme ve Özellik Listesi (29.08.2026, canlı sunucu maceralarından)

## Repoda düzeltilecekler

1. **v-change-sys-port env bug'ı:** Script `NGINX_CONFIG="$HESTIA/nginx/conf/nginx.conf"` satırını
   hestia.conf source'lanmadan ÖNCE tanımlıyor. `sudo` altında HESTIA boş gelince
   "/nginx/conf/nginx.conf: No such file" ile ÇAKILIYOR: hestia.conf (BACKEND_PORT) ve firewall
   2083'e geçiyor ama nginx 8083'te kalıyor → panel dışarıdan erişilemez oluyor (yaşandı).
   Çözüm: satırı source'lardan sonra hesapla.

2. **v-add-mail-account / Dovecot 2.4 dalı:** passwd'a home olarak
   `$HOMEDIR/$user/mail/$domain/$account` yazıyor; kurulu exim şablonu `$HOMEDIR/$user` bekliyor
   → exim çift yol deniyor (`.../info/mail/nexviastudio.com`) ve teslimat sürekli defer oluyor
   (yaşandı: bildirim maili kutuya düşmedi, kuyruk dondu).
   Sunucudaki /usr/local/hestia/bin/v-add-mail-account yamalı — repoya aynen taşı
   (satır 88: `$HOMEDIR/$user:${quota}:userdb_quota_storage_size=...`).

3. **CF MX kontrolü içerik baksın:** v-add-remote-dns-mail-cloudflare "MX var mı" diye bakıp
   atlıyor. Eski Google MX (`smtp.google.com`) dururken bizim MX eklenemedi (yaşandı).
   Kontrol: "bizim içerikli MX var mı" — yoksa yabancı MX'i değiştir/uyar.

4. **SPF birleştirme:** Mevcut SPF varsa atlamak yerine `ip4:<sunucu>` token'ı mevcut kayda ekle.

5. **Mail LE sonrası nginx reload:** v-add-letsencrypt-domain (mail) bitince nginx otomatik
   reload etmiyor; webmail eski/self-signed sertifikayla servis ediliyor (yaşandı, elle reload edildi).

6. **v-add-cron-sys-healing /etc/cron.d'e yazsın:** hestiaweb crontab'ına yazmaya çalışıyor →
   "Permission denied" (yaşandı). Sistem cron dosyası kullan:
   `/etc/cron.d/nexvia-healing` (root olarak çalıştır). Sunucuda manuel kurulu.

7. **v-list-sys-config named uyarısı:** BIND kaldırılmışsunucuda `named: command not found`
   basıyor (satır 313 civarı) — varlık kontrolü ekle.

8. **Zone oluşturulunca NS bildirimi:** v-add-remote-dns-host-cloudflare zone yarattığında
   atanan NS'leri logluyor ama kimse görmüyor. Ekle:
   - `v-add-user-notification admin ...` (panel zili): "Kayıt firmasında NS'leri girin: X, Y"
   - `v-send-sys-notification ...` (mail): aynı bilgi, WARNING seviyesi.
   Vaka: anilarimguvende.com finley/monroe atandı (eric/reza değil!) — NS'yi öğrenmek için
   CF paneline bakmak gerekti.

9. **YENİ ÖZELLİK — Zone durumu ekranı:** Domain eklerken/ekledikten sonra:
   - Atanan NS'ler (kopyalanabilir), delegation durumu (pending/active),
   - "Zone geldi mi?" test butonu (API'den zone status + dig kontrolü),
   - NS doğru girilmediyse net uyarı. (Kullanıcının açık isteği.)

10. **Apex algılama (kozmetik):** İki parçalı uzantılarda (.co.uk gibi) derin subdomain
    (`blog.site.co.uk`) apex sanılıp gereksiz `www.` CNAME ekleniyor.

## Geçmiş bug kayıtları / dersler

- Port değişimi (1. maddede) yarım kaldı; sudo env HESTIA=... ile tamamlandı.
- NexviaCP markalaştırması hiç uygulanmamıştı (nexvia-apply-source.sh çalıştırılmamış),
  panel "Hestia Control Panel" diyordu — script çalıştırılınca düzeldi.
- Sunucu /etc/hosts'unda panel hostname'i 127.0.0.1'e bağlı → sunucu içinden Cloudflare
  testleri `--resolve <cf-ip>` ile yapılmalı.
- Domain yeni CF hesabına taşınınca (kimora/phil → eric/reza) istemci tarafında negatif DNS
  önbelleği: tarayıcıda DNS_PROBE_POSSIBLE. Sunucu tarafı hep sağlamdı; flushdns + 1.1.1.1 çözdü.
- netcup SCP varsayılanı: outgoing SMTP (25/465/587) DROP kuralları — mail gönderimini
  tamamen kilitliyordu; SCP Firewall Policies'ten "netcup Mail block" silinerek açıldı.
- PTR: SCP → sunucu → Network → IPv4 → Reverse DNS = mail.nexviastudio.com (yapıldı,
  yayını bekleniyor — yayına girmezse Gmail spam riski yüksek).
- "Firewall Policies" sayfasına kural girilmez: yanlış DROP = sunucu kilidi.
- anilarimguvende.com Google Workspace'te (info@ kutusu gerçek); CF zone'u API ile açıldı,
  MX=smtp.google.com + SPF=google eklendi, finley/monroe NS Squarespace'te manuel giriliyor.

## 2. tur bulguları (29.08 akşam)

11. **Dovecot 2.4 config canlıya ulaşmıyor:** install/common/dovecot/2.4/conf.d/10-mail.conf
    repoda düzgün duruyor ama nexvia-apply-source.sh /etc/dovecot'a KOYMUYOR — canlıda
    elle uygulandı. apply-source'e dovecot (ve exim şablonları) senkronizasyonu eklensin.
12. **10-mail.conf değişkenleri:** `%{home}` bu Dovecot 2.4 derlemesinde parse edilmiyor,
    `%h` de genişlemiyor. Doğru çözüm GÖRELİ yol (passwd home=/home/$user formatıyla uyumlu):
    mail_home = mail/%{user|domain}/%{user|username}
    mail_path = mail/%{user|domain}/%{user|username}
    (repodaki dosyayı buna göre düzelt — canlıda çalışıyor, test edildi)
13. **Yeni scriptlerde BOM:** v-repair-mail-accounts ve v-check-remote-dns-domain-cloudflare
    UTF-8 BOM ile commit edilmiş → shebang bozuluyor, script sh ile çalışıyor. BOM temizlendi
    (repo + kurulu); commit'le + CI'da grep -rlP '^\xEF\xBB\xBF' kontrolü ekle.
14. **Panel e-posta alanı Türkçe karakter:** kullanıcı "gönderilecek adres" alanına Türkçe ı
    yazınca panel punycode'a çevirmiş (xn--anilarmguvende-bgc.com) → mail unrouteable.
    Panel formunda ascii dışı karakter uyarısı/normalizasyonu eklensin.
15. **"Yeni Kullanıcı" bildirimi 2 kez geldi** (guvende açılışında) — çift tetikleme var,
    kontrol edilsin.
16. **Add Web formunda DNS kutusu:** DNS_SYSTEM kapalıyken "DNS support" kutusu hâlâ aktif —
    işaretlenince "DNS_SYSTEM is not enabled" hatası veriyor, web domain yine oluşuyor ama
    mail adımı sessizce atlanıyor. Kutusu gizlenmeli/devre dışı bırakılmalı (Cloudflare modu),
    hata akışı yeniden tasarlanmalı.
17. **(Fikir) Domain eklenince otomatik LE:** CF otomasyonu açıksa web domain eklendiğinde
    Let's Encrypt otomatik istenebilir (zone active olduktan sonra kuyrukla).
18. **(Fikir) Admin genel bakış:** admin'in WEB/MAIL sekmelerinde "tüm kullanıcılar" filtresi
    ya da kullanıcı başına geçişten bağımsız toplam liste — hoş olur.
19. **File Manager otomatik:** v-add-sys-filemanager, nexvia-apply-source ve hst-install
    sonunda otomatik çalışsın (idempotent); yeni kullanıcı açılınca FM/composer otomatik
    provision (v-add-user kancası). Manuel kurulum YAPILMADI (kullanıcı isteği — repodan gelsin).
20. **Default paket 10GB kota:** DISK_QUOTA paket şablonuna 10000 (MB) yazılsın;
    sert zorlama istenirse kernel quota (v-add-sys-quota) ayrı madde.
21. **Kullanıcı bulut yedek bilgileri:** kullanıcı kendi rclone/S3/B2 bilgilerini girsin,
    yedekleri kendi bulutuna gitsin (v-backup-cloud-sync'i kullanıcı seviyesine indir);
    "ana ekrana atıyor" hatası guvende ile tekrar üretilecek.
22. **Sürüm etiketi:** panel footer "v1.10.4" (upstream deb) ile Updates sayfası "v2.1.0"
    tutarsız — footer fork sürümünü göstersin.
23. **"no restart failed" bildirim konusu** düzeltilsin (anlamsız metin).
24. **Edit sayfası gelişmiş bölümleri** (Git deploy, .env secrets, app preset, kaynak
    limitleri) normal kullanıcıya gizlensin/gate'lensin — admin görüsün ("daha basit" isteği).
25. **v-update-sys-nexvia FM hook yiyor:** FM "ensure" bloğu sadece nexvia-apply-source'ta
    (4.5) — paneleden Güncelle (v-update-sys-nexvia) kendi inline cp -rf uygulamasını yapıyor,
    FM kurulumunu atlıyor. web/fm yoksa panel güncellemesi FM'i KURMAZ. Aynı ensure bloğu
    v-update-sys-nexvia'ya da eklensin (ya da update, apply-source'u çağırsın — tek kod yolu).
    NOT: per-user composer FM için gerekli değil (yalnız admin güncellemesi için); canlıda
    FM 7.14.4 kurulu ve doğrulandı.
26. **FM kök dizini kilidi:** FileGator root'u /home/user'dı — normal kullanıcı web/ dışındaki
    (.ssh, conf, mail...) klasörleri de FM'de görüyordu. Canlıda configuration.php'ye eklendi:
    admin hariç root = /home/<user>/web (SFTP jail). v-add-sys-filemanager içindeki config
    sablonuna da islenmeli — yoksa FM yeniden kurulunca jail kaybolur.
