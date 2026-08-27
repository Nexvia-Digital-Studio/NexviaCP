<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/server/">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml(__tr("Back to Server", "Sunucu Ayarlarına Dön")) ?>
			</a>

			<!-- Manual Scan Trigger -->
			<form method="post" action="/list/ai-healing/" style="display:inline;" onsubmit="const b = this.querySelector('button'); b.disabled=true; b.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> ' + ('<?= (($_SESSION['language'] ?? '') === 'tr') ? "Taranıyor & Onarılıyor..." : "Scanning & Healing..." ?>');">
				<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
				<input type="hidden" name="run_scan" value="1">
				<button type="submit" class="button button-primary">
					<i class="fas fa-stethoscope"></i> <?= tohtml(__tr("Run AI Diagnostics & Scan Now", "Şimdi AI Teşhis & Onarım Taraması Yap")) ?>
				</button>
			</form>

			<!-- Test Email Alert Modal Trigger -->
			<button type="button" class="button button-secondary" onclick="openTestEmailModal();">
				<i class="fas fa-paper-plane icon-blue"></i> <?= tohtml(__tr("Send Test HTML Alert", "Test HTML Bildirimi Gönder")) ?>
			</button>

			<!-- Settings Modal Trigger -->
			<button type="button" class="button button-secondary" onclick="openSettingsModal();">
				<i class="fas fa-sliders icon-purple"></i> <?= tohtml(__tr("Alert & Healing Settings", "Bildirim & Onarım Ayarları")) ?>
			</button>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">
	<h1 class="u-text-center u-hide-desktop u-mt20 u-pr30 u-mb20 u-pl30">
		<?= tohtml(__tr("AI Ops, Self-Healing Engine & HTML Notification Hub", "AI Ops, Kendi Kendini Onaran Motor & E-Posta Bildirim Merkezi")) ?>
	</h1>

	<?php show_alert_message($_SESSION); ?>

	<!-- Top Stats Overview Grid -->
	<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 25px;">
		<!-- Stat 1: Engine Status -->
		<div class="card" style="padding: 18px; border-radius: 8px; border: 1px solid var(--border-color, #334155); background: var(--color-background, #fff);">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<div>
					<small class="u-text-muted" style="font-size:11px; text-transform:uppercase; font-weight:bold; letter-spacing:0.5px;">
						<?= tohtml(__tr("Self-Healing Engine", "Oto-Onarım Motoru")) ?>
					</small>
					<h2 style="margin:5px 0 0 0; font-size:1.4rem; font-weight:bold; color:<?= ($summary["ENGINE_STATUS"] ?? '') === 'ACTIVE' ? 'var(--icon-color-green, #22c55e)' : 'var(--icon-color-orange, #f97316)' ?>;">
						<?= ($summary["ENGINE_STATUS"] ?? '') === 'ACTIVE' ? '🟢 ' . tohtml(__tr("Active", "Aktif")) : '⏸️ ' . tohtml(__tr("Paused", "Duraklatıldı")) ?>
					</h2>
				</div>
				<div style="width:40px; height:40px; border-radius:8px; background:rgba(34, 197, 94, 0.1); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-heart-pulse fa-lg" style="color:var(--icon-color-green, #22c55e);"></i>
				</div>
			</div>
			<div style="margin-top:10px; font-size:12px; color:var(--color-text-muted);">
				🕒 <?= tohtml(__tr("Last Scan:", "Son Tarama:")) ?> <strong><?= tohtml(substr($summary["LAST_SCAN_TIME"] ?? 'Just now', 0, 19)) ?></strong>
			</div>
		</div>

		<!-- Stat 2: Monitored Services & Apps -->
		<div class="card" style="padding: 18px; border-radius: 8px; border: 1px solid var(--border-color, #334155); background: var(--color-background, #fff);">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<div>
					<small class="u-text-muted" style="font-size:11px; text-transform:uppercase; font-weight:bold; letter-spacing:0.5px;">
						<?= tohtml(__tr("Monitored Daemons & Apps", "İzlenen Servisler")) ?>
					</small>
					<h2 style="margin:5px 0 0 0; font-size:1.6rem; font-weight:bold; color:var(--icon-color-blue, #38bdf8);">
						<?= (int)($summary["ACTIVE_SERVICES_COUNT"] ?? count($services)) ?> <span style="font-size:1rem; color:var(--color-text-muted);">/ <?= (int)($summary["MONITORED_SERVICES_COUNT"] ?? count($services)) ?></span>
					</h2>
				</div>
				<div style="width:40px; height:40px; border-radius:8px; background:rgba(56, 189, 248, 0.1); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-shield-halved fa-lg" style="color:var(--icon-color-blue, #38bdf8);"></i>
				</div>
			</div>
			<div style="margin-top:10px; font-size:12px; color:var(--color-text-muted);">
				🛡️ <?= tohtml(__tr("PHP pools, Node/.NET apps & Nginx proxy", "PHP havuzları, Node/.NET ve Nginx")) ?>
			</div>
		</div>

		<!-- Stat 3: Auto-Healed Incidents -->
		<div class="card" style="padding: 18px; border-radius: 8px; border: 1px solid var(--border-color, #334155); background: var(--color-background, #fff);">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<div>
					<small class="u-text-muted" style="font-size:11px; text-transform:uppercase; font-weight:bold; letter-spacing:0.5px;">
						<?= tohtml(__tr("Auto-Heals (24 Hours)", "Oto-Onarım (Son 24 Saat)")) ?>
					</small>
					<h2 style="margin:5px 0 0 0; font-size:1.6rem; font-weight:bold; color:var(--icon-color-purple, #a855f7);">
						<?= (int)($summary["HEALS_LAST_24H"] ?? 0) ?>
					</h2>
				</div>
				<div style="width:40px; height:40px; border-radius:8px; background:rgba(168, 85, 247, 0.1); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-wand-magic-sparkles fa-lg" style="color:var(--icon-color-purple, #a855f7);"></i>
				</div>
			</div>
			<div style="margin-top:10px; font-size:12px; color:var(--color-text-muted);">
				⚡ <?= tohtml(__tr("Total all-time events:", "Toplam kayıtlı olay:")) ?> <strong><?= (int)($summary["TOTAL_HEALING_EVENTS"] ?? count($events)) ?></strong>
			</div>
		</div>

		<!-- Stat 4: Alert Notification Hub -->
		<div class="card" style="padding: 18px; border-radius: 8px; border: 1px solid var(--border-color, #334155); background: var(--color-background, #fff);">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<div>
					<small class="u-text-muted" style="font-size:11px; text-transform:uppercase; font-weight:bold; letter-spacing:0.5px;">
						<?= tohtml(__tr("HTML Alert Routing", "E-Posta Bildirim Seviyesi")) ?>
					</small>
					<h2 style="margin:5px 0 0 0; font-size:1.4rem; font-weight:bold; color:var(--icon-color-orange, #f97316);">
						<?= tohtml($settings["SYS_NOTIFY_LEVEL"] ?? 'INFO') ?>
					</h2>
				</div>
				<div style="width:40px; height:40px; border-radius:8px; background:rgba(249, 115, 22, 0.1); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-envelope-circle-check fa-lg" style="color:var(--icon-color-orange, #f97316);"></i>
				</div>
			</div>
			<div style="margin-top:10px; font-size:12px; color:var(--color-text-muted); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
				📧 <?= !empty($settings["SYS_NOTIFY_EMAIL"]) ? tohtml($settings["SYS_NOTIFY_EMAIL"]) : tohtml(__tr("No admin email configured", "E-posta tanımlanmadı")) ?>
			</div>
		</div>
	</div>

	<!-- Live Services Radar Status Bar -->
	<div class="card u-mb20" style="padding:15px 20px; border:1px solid var(--border-color, #334155); border-radius:8px; background:var(--color-background, #fff);">
		<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:10px;">
			<h3 style="margin:0; font-size:1rem; font-weight:bold; display:flex; align-items:center; gap:8px;">
				<i class="fas fa-microchip icon-blue"></i> <?= tohtml(__tr("Live Monitored Services & Backend Radar", "Canlı İzlenen Servisler & Arka Plan Radarı")) ?>
			</h3>
			<span style="font-size:12px; color:var(--color-text-muted);">
				<?= count($services) ?> <?= tohtml(__tr("components active in self-healing watch loop", "bileşen koruma döngüsünde aktif")) ?>
			</span>
		</div>

		<div style="display:flex; flex-wrap:wrap; gap:10px;">
			<?php if (empty($services)): ?>
				<p class="u-text-muted" style="font-size:12px; margin:0;"><?= tohtml(__tr("Core services are running normally.", "Tüm servisler normal çalışıyor.")) ?></p>
			<?php else: ?>
				<?php foreach ($services as $svc): 
					$sName = $svc["name"] ?? "";
					$sActive = !empty($svc["active"]);
					$sStatus = $svc["status"] ?? "UNKNOWN";
					$sType = $svc["type"] ?? "core";
					
					$badgeBg = $sActive ? "rgba(34,197,94,0.12)" : "rgba(239,68,68,0.12)";
					$badgeBorder = $sActive ? "rgba(34,197,94,0.4)" : "rgba(239,68,68,0.4)";
					$badgeColor = $sActive ? "#16a34a" : "#dc2626";
					$icon = ($sType === "app") ? "fa-bolt" : (($sType === "php-fpm") ? "fa-cube" : "fa-server");
				?>
					<div style="display:flex; align-items:center; gap:6px; padding:6px 12px; border-radius:6px; background:<?= $badgeBg ?>; border:1px solid <?= $badgeBorder ?>; font-size:12px; color:<?= $badgeColor ?>; font-weight:600;">
						<i class="fas <?= $icon ?>" style="font-size:11px;"></i>
						<span><?= tohtml($sName) ?></span>
						<span style="font-size:10px; opacity:0.85; text-transform:uppercase;">(<?= tohtml($sStatus) ?>)</span>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>

	<!-- Information Collapsible Box -->
	<details class="box-collapse u-mb20">
		<summary class="box-collapse-header">
			<i class="fas fa-circle-info u-mr10"></i><?= tohtml(__tr("How NexviaCP AI Ops & Self-Healing Engine Works", "NexviaCP AI Ops & Kendi Kendini Onarma Motoru Nasıl Çalışır?")) ?>
		</summary>
		<div class="box-collapse-content" style="font-size:0.9rem; line-height:1.5;">
			<p class="u-mb10">
				<?= tohtml(__tr("The Self-Healing Engine continuously monitors the server's vital application channels and automatically heals failures within seconds without requiring manual administrator intervention:", "Oto-Onarım Motoru, sunucunun kritik uygulama kanallarını sürekli izler ve yönetici müdahalesine gerek kalmadan saniyeler içinde arızaları giderir:")) ?>
			</p>
			<ul style="list-style:disc; margin-left:20px; line-height:1.6;">
				<li><strong><?= tohtml(__tr("500 / 502 Bad Gateway Spike Recovery:", "500 / 502 Bad Gateway Dalgası Onarımı:")) ?></strong> <?= tohtml(__tr("Detects upstream connection drops, connection resets, and proxy failures; validates Nginx syntax, safely reloads configuration, and recycles hung upstream backend worker processes.", "Nginx proxy kopmalarını ve 502 hatalarını tespit eder; sözdizimini doğrular, proxy'yi yeniler ve takılan iş parçacıklarını güvenle yeniden başlatır.")) ?></li>
				<li><strong><?= tohtml(__tr("Unresponsive PHP-FPM Pool Healing:", "Kilitlenen PHP-FPM Havuz Kurtarma:")) ?></strong> <?= tohtml(__tr("Identifies pm.max_children saturation, dead Unix sockets, or hung master daemons and recycles worker pools gracefully.", "pm.max_children tıkanmalarını, kilitlenen Unix soketlerini veya çöken master süreçleri tespit edip havuzu sıfırdan ayağa kaldırır.")) ?></li>
				<li><strong><?= tohtml(__tr("Node.js, .NET & App Crash-Loop Healer:", "Node.js, .NET & Uygulama Çökme Kurtarıcısı:")) ?></strong> <?= tohtml(__tr("Detects EADDRINUSE port collisions (killing lingering zombie PIDs), OOM-killer memory events, and unhandled runtime exceptions; resets failed state and restores zero-downtime execution.", "Port çakışmalarını (EADDRINUSE), zombi süreçleri, OOM bellek kısıtlamalarını ve unhandled istisnaları tespit eder; portu boşaltıp servisi ayağa kaldırır.")) ?></li>
				<li><strong><?= tohtml(__tr("Responsive HTML Email Notifications:", "Zengin ve Duyarlı HTML E-Posta Bildirimleri:")) ?></strong> <?= tohtml(__tr("Dispatches beautifully formatted, multi-level (INFO, SUCCESS, WARNING, CRITICAL) diagnostic emails with root cause analysis, action performed, and one-click console access.", "Kök neden teşhisi, yapılan işlem ve doğrudan konsol erişim linki içeren şık HTML e-postalar gönderir.")) ?></li>
			</ul>
		</div>
	</details>

	<!-- Search & Filter Controls -->
	<div class="card u-mb20" style="padding:15px; border:1px solid var(--border-color, #334155); border-radius:8px; background:var(--color-background, #fff);">
		<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
			<div style="flex:1; min-width:240px;">
				<input type="text" id="event-search-input" class="form-control" placeholder="<?= tohtml(__tr("Search service, error cause, domain...", "Servis, hata nedeni veya alan adı ara...")) ?>" onkeyup="filterHealingEvents();" style="width:100%;">
			</div>
			<div style="display:flex; gap:10px; align-items:center;">
				<label class="form-label u-text-bold" style="margin:0; font-size:12px;"><?= tohtml(__tr("Filter Severity:", "Önem Derecesi:")) ?></label>
				<select id="severity-filter-select" class="form-select" onchange="filterHealingEvents();">
					<option value="all"><?= tohtml(__tr("All Incidents", "Tüm Olaylar")) ?> (<?= count($events) ?>)</option>
					<option value="SUCCESS"><?= tohtml(__tr("🟢 Resolved / Healed", "🟢 Çözüldü / Onarıldı")) ?></option>
					<option value="WARNING"><?= tohtml(__tr("🟠 Warnings & Spikes", "🟠 Uyarılar")) ?></option>
					<option value="CRITICAL"><?= tohtml(__tr("🔴 Critical Incidents", "🔴 Kritik Olaylar")) ?></option>
					<option value="INFO"><?= tohtml(__tr("🔵 Informational", "🔵 Bilgilendirme")) ?></option>
				</select>

				<?php if (!empty($events)): ?>
					<form method="post" action="/list/ai-healing/" style="margin:0;" onsubmit="return confirm('<?= (($_SESSION['language'] ?? '') === 'tr') ? "Geçmiş olay günlüğünü temizlemek istediğinize emin misiniz?" : "Are you sure you want to clear the healing event timeline?" ?>');">
						<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
						<input type="hidden" name="clear_events" value="1">
						<button type="submit" class="button button-secondary" title="<?= tohtml(__tr("Clear Event History", "Geçmişi Temizle")) ?>" style="padding:6px 12px; font-size:12px;">
							<i class="fas fa-trash-can"></i> <?= tohtml(__tr("Clear", "Temizle")) ?>
						</button>
					</form>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- Interactive Self-Healing Events Timeline Table -->
	<div class="units-table" style="background:var(--color-background, #fff); border-radius:8px; border:1px solid var(--border-color, #334155); overflow:hidden;">
		<div class="units-table-header" style="background:rgba(0,0,0,0.03); font-weight:bold;">
			<div class="units-table-cell" style="flex: 1.5;"><?= tohtml(__tr("Timestamp & Level", "Zaman & Seviye")) ?></div>
			<div class="units-table-cell" style="flex: 1.8;"><?= tohtml(__tr("Affected Service / Domain", "Etkilenen Servis")) ?></div>
			<div class="units-table-cell" style="flex: 1.5;"><?= tohtml(__tr("Incident Trigger", "Olay Tetikleyicisi")) ?></div>
			<div class="units-table-cell" style="flex: 3;"><?= tohtml(__tr("AI Root Cause & Resolution", "AI Teşhisi & Onarım")) ?></div>
			<div class="units-table-cell u-text-center" style="flex: 1.2;"><?= tohtml(__tr("Result Status", "Sonuç")) ?></div>
			<div class="units-table-cell u-text-center" style="flex: 0.8;"><?= tohtml(__tr("Telemetry", "Telemetri")) ?></div>
		</div>

		<?php if (empty($events)): ?>
			<div class="units-table-row u-text-center u-p20">
				<div style="padding:30px 20px;">
					<i class="fas fa-circle-check fa-3x icon-green" style="margin-bottom:15px;"></i>
					<h3 style="margin:0 0 6px 0;"><?= tohtml(__tr("All Systems Pristine & Healthy", "Tüm Sistemler Sağlıklı ve Sorunsuz")) ?></h3>
					<p class="u-text-muted" style="margin:0; font-size:0.9rem;">
						<?= tohtml(__tr("No crashes, 502 gateway spikes, or hung worker processes detected. The AI Self-Healing engine is actively standing guard.", "Herhangi bir çökme veya 502 hatası tespit edilmedi. Kendi kendini onarma motoru nöbette.")) ?>
					</p>
				</div>
			</div>
		<?php else: ?>
			<?php foreach ($events as $ev): 
				$evId = $ev["id"] ?? uniqid();
				$evTs = $ev["timestamp"] ?? "";
				$evSvc = $ev["service"] ?? "system";
				$evDomain = $ev["domain"] ?? "";
				$evType = $ev["type"] ?? "ANOMALY";
				$evLevel = strtoupper($ev["level"] ?? "INFO");
				$evDiag = $ev["diagnosis"] ?? "";
				$evAction = $ev["action_taken"] ?? "";
				$evStatus = $ev["status"] ?? "RECOVERED";
				$evLogs = $ev["log_snippet"] ?? "";

				$badgeClass = "badge-info";
				$levelColor = "var(--icon-color-blue, #38bdf8)";
				if ($evLevel === "SUCCESS") {
					$badgeClass = "badge-success";
					$levelColor = "var(--icon-color-green, #22c55e)";
				} elseif ($evLevel === "WARNING") {
					$badgeClass = "badge-warning";
					$levelColor = "var(--icon-color-orange, #f97316)";
				} elseif ($evLevel === "CRITICAL") {
					$badgeClass = "badge-danger";
					$levelColor = "#ef4444";
				}
			?>
				<div class="units-table-row healing-event-row" data-search="<?= tohtml(strtolower($evSvc . ' ' . $evDomain . ' ' . $evType . ' ' . $evDiag . ' ' . $evAction)) ?>" data-severity="<?= tohtml($evLevel) ?>">
					<!-- Timestamp & Level -->
					<div class="units-table-cell" style="flex: 1.5;">
						<div style="display:flex; flex-direction:column; gap:4px;">
							<span class="badge <?= $badgeClass ?>" style="font-size:10px; width:max-content; padding:2px 6px;">
								<?= tohtml($evLevel) ?>
							</span>
							<small class="u-text-muted" style="font-size:11px; font-family:monospace;">
								<?= tohtml(substr($evTs, 0, 19)) ?>
							</small>
						</div>
					</div>

					<!-- Affected Service / Domain -->
					<div class="units-table-cell" style="flex: 1.8;">
						<div style="display:flex; align-items:center; gap:8px;">
							<i class="fas fa-server icon-blue"></i>
							<div>
								<span class="u-text-bold" style="color:var(--color-text); font-size:13px;"><?= tohtml($evSvc) ?></span>
								<?php if (!empty($evDomain) && $evDomain !== "system" && $evDomain !== "global"): ?>
									<small class="u-text-muted" style="display:block; font-size:11px;">
										🌐 <?= tohtml($evDomain) ?>
									</small>
								<?php endif; ?>
							</div>
						</div>
					</div>

					<!-- Incident Trigger -->
					<div class="units-table-cell" style="flex: 1.5;">
						<span style="font-size:11px; font-family:monospace; font-weight:bold; color:<?= $levelColor ?>;">
							<?= tohtml($evType) ?>
						</span>
					</div>

					<!-- AI Root Cause & Resolution -->
					<div class="units-table-cell" style="flex: 3;">
						<div style="font-size:12px; line-height:1.4;">
							<div style="color:var(--color-text); margin-bottom:3px;">
								<strong>🔍 <?= tohtml(__tr("Diagnosis:", "Teşhis:")) ?></strong> <?= tohtml($evDiag) ?>
							</div>
							<div class="u-text-muted" style="font-size:11px;">
								<strong>⚡ <?= tohtml(__tr("Healed:", "Onarım:")) ?></strong> <?= tohtml($evAction) ?>
							</div>
						</div>
					</div>

					<!-- Result Status -->
					<div class="units-table-cell u-text-center" style="flex: 1.2;">
						<?php if (strpos($evStatus, 'ONLINE') !== false || strpos($evStatus, 'RECOVERED') !== false || strpos($evStatus, 'HEALTHY') !== false): ?>
							<span class="badge badge-success" style="font-size:10px; padding:3px 7px;">
								🟢 <?= tohtml($evStatus) ?>
							</span>
						<?php else: ?>
							<span class="badge badge-warning" style="font-size:10px; padding:3px 7px;">
								⚠️ <?= tohtml($evStatus) ?>
							</span>
						<?php endif; ?>
					</div>

					<!-- Actions / Telemetry Modal -->
					<div class="units-table-cell u-text-center" style="flex: 0.8;">
						<button type="button" class="button button-secondary button-small" onclick="openTelemetryModal('<?= tohtml($evSvc) ?>', '<?= tohtml($evType) ?>', '<?= tohtml(addslashes($evDiag)) ?>', '<?= tohtml(addslashes($evAction)) ?>', '<?= tohtml(addslashes($evLogs)) ?>');" title="<?= tohtml(__tr("View Raw Journalctl / Logs", "Ham Log ve Telemetriyi İncele")) ?>" style="padding:4px 8px; font-size:11px;">
							<i class="fas fa-terminal"></i>
						</button>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>

<!-- Modal 1: Notification & Healing Settings Modal -->
<div id="settings-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.65); z-index:9999; justify-content:center; align-items:center;" onclick="if(event.target===this) closeSettingsModal();">
	<div class="form-container" style="background:var(--color-background, #fff); max-width:550px; width:92%; border-radius:8px; padding:25px; box-shadow:0 10px 30px rgba(0,0,0,0.5); max-height:90vh; overflow-y:auto;">
		<h2 class="u-mb15"><i class="fas fa-sliders icon-purple"></i> <?= tohtml(__tr("Notification & Self-Healing Hub Settings", "Bildirim & Oto-Onarım Ayarları")) ?></h2>
		<p class="u-text-muted u-mb20" style="font-size:0.88rem;">
			<?= tohtml(__tr("Configure automated responsive HTML email dispatch, alert thresholds, and self-healing daemon status.", "Otomatik HTML e-posta gönderimi, önem derecesi filtreleri ve otomatik onarım tercihlerini belirleyin.")) ?>
		</p>

		<form method="post" action="/list/ai-healing/">
			<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
			<input type="hidden" name="save_settings" value="1">

			<!-- Recipient Email -->
			<div class="u-mb15">
				<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("Admin Recipient Email Address", "Yönetici E-Posta Adresi")) ?></label>
				<input type="email" name="notify_email" class="form-control" value="<?= tohtml($settings["SYS_NOTIFY_EMAIL"] ?? "") ?>" placeholder="admin@domain.com" required style="width:100%;">
				<small class="u-text-muted"><?= tohtml(__tr("Receives self-healing reports, error spike alerts, and critical incidents.", "Oto-onarım raporları ve kritik bildirimlerin gönderileceği e-posta adresi.")) ?></small>
			</div>

			<!-- Alert Severity Threshold Level -->
			<div class="u-mb15">
				<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("Alert Severity Filter Threshold", "Bildirim Önem Derecesi Filtresi")) ?></label>
				<?php $curLvl = $settings["SYS_NOTIFY_LEVEL"] ?? "INFO"; ?>
				<select name="notify_level" class="form-select" style="width:100%;">
					<option value="INFO" <?= $curLvl === "INFO" ? "selected" : "" ?>>🔵 INFO (<?= tohtml(__tr("All events: Info, Heals, Warnings & Critical", "Tüm olaylar: Bilgi, Onarım, Uyarı ve Kritik")) ?>)</option>
					<option value="SUCCESS" <?= $curLvl === "SUCCESS" ? "selected" : "" ?>>🟢 SUCCESS (<?= tohtml(__tr("Successful Heals, Warnings & Critical incidents", "Başarılı Onarımlar, Uyarılar ve Kritik olaylar")) ?>)</option>
					<option value="WARNING" <?= $curLvl === "WARNING" ? "selected" : "" ?>>🟠 WARNING (<?= tohtml(__tr("502 Spikes, Worker Stalls & Critical errors only", "Sadece 502 Dalgalanmaları ve Kritik arızalar")) ?>)</option>
					<option value="CRITICAL" <?= $curLvl === "CRITICAL" ? "selected" : "" ?>>🔴 CRITICAL (<?= tohtml(__tr("Only irrecoverable crash loops & service outages", "Sadece kurtarılamayan çökmeler ve kesintiler")) ?>)</option>
					<option value="OFF" <?= $curLvl === "OFF" ? "selected" : "" ?>>⏸️ OFF (<?= tohtml(__tr("Mute all automated email alerts", "Tüm e-posta bildirimlerini sustur")) ?>)</option>
				</select>
			</div>

			<!-- Sender Name -->
			<div class="u-mb15">
				<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("Mail Sender Display Name", "Gönderici Görünen Adı")) ?></label>
				<input type="text" name="notify_sender_name" class="form-control" value="<?= tohtml($settings["SYS_NOTIFY_SENDER_NAME"] ?? "NexviaCP AI Healing Engine") ?>" placeholder="NexviaCP AI Healing Engine" style="width:100%;">
			</div>

			<!-- Sender Email -->
			<div class="u-mb15">
				<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("Mail Sender From Address", "Gönderici 'Kimden' Adresi")) ?></label>
				<input type="text" name="notify_sender_email" class="form-control" value="<?= tohtml($settings["SYS_NOTIFY_SENDER_EMAIL"] ?? "") ?>" placeholder="noreply@<?= tohtml(get_hostname() ?: 'domain.com') ?>" style="width:100%;">
			</div>

			<!-- Toggles -->
			<div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:20px;">
				<div style="padding:12px; border:1px solid var(--border-color, #334155); border-radius:6px;">
					<label class="form-label u-text-bold" style="display:flex; align-items:center; gap:8px; cursor:pointer;">
						<input type="checkbox" name="notify_enabled" value="yes" <?= ($settings["SYS_NOTIFY_ENABLED"] ?? "yes") === "yes" ? "checked" : "" ?>>
						<span><?= tohtml(__tr("Enable Email Alerts", "E-Posta Bildirimleri Açık")) ?></span>
					</label>
				</div>
				<div style="padding:12px; border:1px solid var(--border-color, #334155); border-radius:6px;">
					<label class="form-label u-text-bold" style="display:flex; align-items:center; gap:8px; cursor:pointer;">
						<input type="checkbox" name="healing_enabled" value="yes" <?= ($settings["SYS_HEALING_ENABLED"] ?? "yes") === "yes" ? "checked" : "" ?>>
						<span><?= tohtml(__tr("Auto-Healing Daemon", "Oto-Onarım Motoru Aktif")) ?></span>
					</label>
				</div>
			</div>

			<div style="display:flex; justify-content:flex-end; gap:10px;">
				<button type="button" class="button button-secondary" onclick="closeSettingsModal();">
					<?= tohtml(__tr("Cancel", "İptal")) ?>
				</button>
				<button type="submit" class="button button-primary">
					<i class="fas fa-floppy-disk"></i> <?= tohtml(__tr("Save Preferences", "Tercihleri Kaydet")) ?>
				</button>
			</div>
		</form>
	</div>
</div>

<!-- Modal 2: Send Test HTML Alert Modal -->
<div id="test-email-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.65); z-index:9999; justify-content:center; align-items:center;" onclick="if(event.target===this) closeTestEmailModal();">
	<div class="form-container" style="background:var(--color-background, #fff); max-width:480px; width:92%; border-radius:8px; padding:25px; box-shadow:0 10px 30px rgba(0,0,0,0.5);">
		<h2 class="u-mb15"><i class="fas fa-paper-plane icon-blue"></i> <?= tohtml(__tr("Send Test HTML Alert Email", "Test HTML Bildirimi Gönder")) ?></h2>
		<p class="u-text-muted u-mb20" style="font-size:0.88rem;">
			<?= tohtml(__tr("Test and verify email layout rendering, responsive styles, and SMTP / sendmail transport.", "E-posta şablonunu, responsive tasarımı ve SMTP/sendmail aktarımını test edin.")) ?>
		</p>

		<form method="post" action="/list/ai-healing/">
			<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
			<input type="hidden" name="send_test_alert" value="1">

			<div class="u-mb15">
				<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("Target Email Address", "Hedef E-Posta Adresi")) ?></label>
				<input type="email" name="test_email" class="form-control" value="<?= tohtml($settings["SYS_NOTIFY_EMAIL"] ?? "") ?>" placeholder="admin@example.com" style="width:100%;">
				<small class="u-text-muted"><?= tohtml(__tr("Leave empty to use configured admin email.", "Yapılandırılmış yönetici e-postasını kullanmak için boş bırakın.")) ?></small>
			</div>

			<div class="u-mb20">
				<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("Test Alert Severity Theme", "Test Bildirim Teması")) ?></label>
				<select name="test_level" class="form-select" style="width:100%;">
					<option value="INFO">🔵 INFO - Modern Blue Theme (Bilgilendirme)</option>
					<option value="SUCCESS">🟢 SUCCESS - Emerald Green Theme (Başarılı Onarım)</option>
					<option value="WARNING">🟠 WARNING - Amber Orange Theme (Uyarı)</option>
					<option value="CRITICAL">🔴 CRITICAL - Crimson Red Theme (Kritik Olay)</option>
				</select>
			</div>

			<div style="display:flex; justify-content:flex-end; gap:10px;">
				<button type="button" class="button button-secondary" onclick="closeTestEmailModal();">
					<?= tohtml(__tr("Cancel", "İptal")) ?>
				</button>
				<button type="submit" class="button button-primary">
					<i class="fas fa-paper-plane"></i> <?= tohtml(__tr("Dispatch Alert Now", "Şimdi Gönder")) ?>
				</button>
			</div>
		</form>
	</div>
</div>

<!-- Modal 3: Raw Telemetry & Diagnostic Logs Viewer Modal -->
<div id="telemetry-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.65); z-index:9999; justify-content:center; align-items:center;" onclick="if(event.target===this) closeTelemetryModal();">
	<div class="form-container" style="background:var(--color-background, #fff); max-width:680px; width:92%; border-radius:8px; padding:25px; box-shadow:0 10px 30px rgba(0,0,0,0.5); max-height:85vh; display:flex; flex-direction:column;">
		<h2 class="u-mb10"><i class="fas fa-terminal icon-blue"></i> <?= tohtml(__tr("Incident Telemetry & Diagnostic Trace", "Olay Telemetrisi & Teşhis Detayı")) ?></h2>
		<p class="u-text-muted u-mb15" style="font-size:0.88rem;">
			<span id="tel-svc-label" class="u-text-bold"></span> &mdash; <span id="tel-type-label" style="font-family:monospace; color:var(--icon-color-orange, #f97316);"></span>
		</p>

		<div class="u-mb15" style="font-size:0.88rem; line-height:1.4;">
			<p style="margin:0 0 6px 0;"><strong>🔍 <?= tohtml(__tr("AI Diagnosis:", "AI Teşhisi:")) ?></strong> <span id="tel-diag-text"></span></p>
			<p style="margin:0;"><strong>⚡ <?= tohtml(__tr("Remediation Performed:", "Uygulanan Onarım:")) ?></strong> <span id="tel-action-text"></span></p>
		</div>

		<div style="flex:1; overflow-y:auto; background:#090d16; color:#38bdf8; font-family:monospace; font-size:11px; padding:15px; border-radius:6px; border:1px solid #1e293b; white-space:pre-wrap; word-break:break-all; min-height:180px;" id="tel-logs-content">
		</div>

		<div style="display:flex; justify-content:flex-end; margin-top:15px;">
			<button type="button" class="button button-secondary" onclick="closeTelemetryModal();">
				<?= tohtml(__tr("Close", "Kapat")) ?>
			</button>
		</div>
	</div>
</div>

<script>
function openSettingsModal() {
	document.getElementById('settings-modal').style.display = 'flex';
}
function closeSettingsModal() {
	document.getElementById('settings-modal').style.display = 'none';
}

function openTestEmailModal() {
	document.getElementById('test-email-modal').style.display = 'flex';
}
function closeTestEmailModal() {
	document.getElementById('test-email-modal').style.display = 'none';
}

function openTelemetryModal(svc, type, diag, action, logs) {
	document.getElementById('tel-svc-label').innerText = svc;
	document.getElementById('tel-type-label').innerText = type;
	document.getElementById('tel-diag-text').innerText = diag;
	document.getElementById('tel-action-text').innerText = action;
	document.getElementById('tel-logs-content').innerText = logs || '<?= (($_SESSION['language'] ?? '') === 'tr') ? "Kayıtlı ek log çıktısı yok. Servis başarıyla kurtarıldı." : "No raw crash logs recorded. Service was recovered cleanly." ?>';
	document.getElementById('telemetry-modal').style.display = 'flex';
}
function closeTelemetryModal() {
	document.getElementById('telemetry-modal').style.display = 'none';
}

document.addEventListener('keydown', function(e) {
	if (e.key === 'Escape') {
		closeSettingsModal();
		closeTestEmailModal();
		closeTelemetryModal();
	}
});

function filterHealingEvents() {
	const searchVal = document.getElementById('event-search-input').value.toLowerCase().trim();
	const sevVal = document.getElementById('severity-filter-select').value;
	const rows = document.querySelectorAll('.healing-event-row');

	rows.forEach(row => {
		const rowText = row.getAttribute('data-search') || '';
		const rowSev = row.getAttribute('data-severity') || '';

		const matchesSearch = !searchVal || rowText.includes(searchVal);
		const matchesSev = (sevVal === 'all') || (rowSev === sevVal);

		if (matchesSearch && matchesSev) {
			row.style.display = 'flex';
		} else {
			row.style.display = 'none';
		}
	});
}
</script>
