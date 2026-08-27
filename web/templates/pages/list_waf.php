<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/web/">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml(__tr("Back to Web", "Web Sitelerine Dön")) ?>
			</a>
			
			<!-- Scan All Domains Form -->
			<form method="post" action="/list/waf/" style="display:inline;" onsubmit="const b = this.querySelector('button'); b.disabled=true; b.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> ' + ('<?= (($_SESSION['language'] ?? '') === 'tr') ? "Tüm Siteler Taranıyor..." : "Scanning All Sites..." ?>');">
				<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
				<input type="hidden" name="action_scan_all" value="1">
				<button type="submit" class="button button-primary">
					<i class="fas fa-virus-slash"></i> <?= tohtml(__tr("Scan All Websites for Malware", "Tüm Siteleri Zararlı Koda Karşı Tara")) ?>
				</button>
			</form>

			<a class="button button-secondary" href="/list/waf/" title="<?= tohtml(__tr("Refresh Security Posture", "Güvenlik Durumunu Yenile")) ?>">
				<i class="fas fa-arrows-rotate icon-green"></i><?= tohtml(__tr("Refresh", "Yenile")) ?>
			</a>

			<a class="button button-secondary" href="/list/firewall/banlist/" title="<?= tohtml(__tr("View Banned IP Addresses", "Engellenen IP Adresleri")) ?>">
				<i class="fas fa-ban icon-red"></i><?= tohtml(__tr("Firewall Banlist", "Güvenlik Duvarı Ban Listesi")) ?>
			</a>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">
	<h1 class="u-text-center u-hide-desktop u-mt20 u-pr30 u-mb20 u-pl30">
		<?= tohtml(__tr("Enterprise Threat Shield & WAF Security Center", "Kurumsal Tehdit Kalkanı & WAF Güvenlik Merkezi")) ?>
	</h1>

	<?php show_alert_message($_SESSION); ?>

	<!-- Top Threat Shield Metrics Overview Grid -->
	<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 15px; margin-bottom: 25px;">
		
		<!-- Stat 1: WAF Protected Domains -->
		<div class="card" style="padding: 18px; border-radius: 8px; border: 1px solid var(--border-color, #334155); background: var(--color-background, #fff);">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<div>
					<small class="u-text-muted" style="font-size:11px; text-transform:uppercase; font-weight:bold; letter-spacing:0.5px;">
						<?= tohtml(__tr("WAF Active Shields", "WAF Kalkan Koruması")) ?>
					</small>
					<h2 style="margin:5px 0 0 0; font-size:1.6rem; font-weight:bold; color:var(--icon-color-blue, #38bdf8);">
						<?= (int)($summary["active_waf_domains"] ?? 0) ?> <span style="font-size:1rem; font-weight:normal; color:var(--color-text-muted);">/ <?= (int)($summary["total_monitored_domains"] ?? 0) ?></span>
					</h2>
				</div>
				<div style="width:42px; height:42px; border-radius:8px; background:rgba(56, 189, 248, 0.12); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-shield-halved fa-xl" style="color:var(--icon-color-blue, #38bdf8);"></i>
				</div>
			</div>
			<div style="margin-top:10px; font-size:12px; color:var(--color-text-muted);">
				🛡️ <strong><?= (int)($summary["shield_health_score"] ?? 100) ?>%</strong> <?= tohtml(__tr("Coverage Rate", "Koruma Kapsamı")) ?>
			</div>
		</div>

		<!-- Stat 2: Threats Blocked -->
		<div class="card" style="padding: 18px; border-radius: 8px; border: 1px solid var(--border-color, #334155); background: var(--color-background, #fff);">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<div>
					<small class="u-text-muted" style="font-size:11px; text-transform:uppercase; font-weight:bold; letter-spacing:0.5px;">
						<?= tohtml(__tr("Blocked Attack Attempts", "Engellenen Saldırı Girişimi")) ?>
					</small>
					<h2 style="margin:5px 0 0 0; font-size:1.6rem; font-weight:bold; color:var(--icon-color-green, #22c55e);">
						<?= (int)($summary["total_waf_blocks"] ?? 0) ?>
					</h2>
				</div>
				<div style="width:42px; height:42px; border-radius:8px; background:rgba(34, 197, 94, 0.12); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-shield-virus fa-xl" style="color:var(--icon-color-green, #22c55e);"></i>
				</div>
			</div>
			<div style="margin-top:10px; font-size:12px; color:var(--color-text-muted);">
				🛑 <?= tohtml(__tr("SQLi, XSS, RFI & Bad Bots Stopped", "SQLi, XSS, RFI ve Botlar Durduruldu")) ?>
			</div>
		</div>

		<!-- Stat 3: Malware Scanner Status -->
		<div class="card" style="padding: 18px; border-radius: 8px; border: 1px solid var(--border-color, #334155); background: var(--color-background, #fff);">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<div>
					<small class="u-text-muted" style="font-size:11px; text-transform:uppercase; font-weight:bold; letter-spacing:0.5px;">
						<?= tohtml(__tr("Malware & Webshells", "Zararlı Kod / Webshell")) ?>
					</small>
					<h2 style="margin:5px 0 0 0; font-size:1.6rem; font-weight:bold; color:<?= ((int)($summary["total_threats_detected"] ?? 0) > 0) ? 'var(--icon-color-red, #ef4444)' : 'var(--icon-color-green, #22c55e)' ?>;">
						<?= (int)($summary["total_threats_detected"] ?? 0) ?>
					</h2>
				</div>
				<div style="width:42px; height:42px; border-radius:8px; background:rgba(239, 68, 68, 0.12); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-bug fa-xl" style="color:var(--icon-color-red, #ef4444);"></i>
				</div>
			</div>
			<div style="margin-top:10px; font-size:12px; color:var(--color-text-muted);">
				🔒 <strong><?= (int)($summary["total_quarantined_files"] ?? 0) ?></strong> <?= tohtml(__tr("Files Quarantined", "Dosya Karantinada")) ?>
			</div>
		</div>

		<!-- Stat 4: GeoIP & Banned IPs -->
		<div class="card" style="padding: 18px; border-radius: 8px; border: 1px solid var(--border-color, #334155); background: var(--color-background, #fff);">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<div>
					<small class="u-text-muted" style="font-size:11px; text-transform:uppercase; font-weight:bold; letter-spacing:0.5px;">
						<?= tohtml(__tr("GeoIP & IP Bans", "GeoIP & Banlanan IP'ler")) ?>
					</small>
					<h2 style="margin:5px 0 0 0; font-size:1.6rem; font-weight:bold; color:var(--icon-color-purple, #a855f7);">
						<?= (int)($summary["active_geoip_domains"] ?? 0) ?> <span style="font-size:1rem; font-weight:normal; color:var(--color-text-muted);">/ <?= (int)($summary["total_banned_ips"] ?? 0) ?> bans</span>
					</h2>
				</div>
				<div style="width:42px; height:42px; border-radius:8px; background:rgba(168, 85, 247, 0.12); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-earth-americas fa-xl" style="color:var(--icon-color-purple, #a855f7);"></i>
				</div>
			</div>
			<div style="margin-top:10px; font-size:12px; color:var(--color-text-muted);">
				🌐 <?= tohtml(__tr("Country Whitelist/Blacklist Active", "Ülke Filtreleme ve IP Engelleme")) ?>
			</div>
		</div>
	</div>

	<!-- Section: Global Security & PR Preview Whitelist -->
	<div class="card u-mb25" style="padding:20px; border-radius:8px; border:1px solid var(--border-color, #334155); background:var(--color-background, #fff);">
		<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom:15px;">
			<div>
				<h2 style="font-size:1.15rem; margin:0; font-weight:bold; display:flex; align-items:center; gap:8px;">
					<i class="fas fa-network-wired icon-purple"></i>
					<?= tohtml(__tr("Global Security & PR Preview IP Whitelist", "Genel Güvenlik & PR Önizleme IP Whitelist")) ?>
				</h2>
				<small class="u-text-muted" style="font-size:12px;">
					<?= tohtml(__tr("Whitelisted IPs automatically bypass WAF rate limits and gain instant private access to PR preview staging environments (pr-*.domain.com).", "Bu listedeki IP'ler tüm PR test ortamlarına (pr-*.domain.com) ve WAF kalkanına otomatik güvenli erişim sağlar.")) ?>
				</small>
			</div>
			<div style="display:flex; align-items:center; gap:10px;">
				<div style="background:rgba(56,189,248,0.1); border:1px solid rgba(56,189,248,0.3); border-radius:6px; padding:6px 12px; font-size:12px;">
					<span class="u-text-muted"><?= tohtml(__tr("Your Detected IP:", "Tespit Edilen IP:")) ?></span>
					<strong style="color:var(--icon-color-blue, #38bdf8); font-family:monospace; margin-left:4px;"><?= tohtml($detected_client_ip) ?></strong>
				</div>
				<form method="post" action="/list/waf/" style="margin:0;">
					<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
					<input type="hidden" name="action_global_whitelist" value="1">
					<input type="hidden" name="whitelist_act" value="add">
					<input type="hidden" name="whitelist_ips" value="<?= tohtml($detected_client_ip) ?>">
					<button type="submit" class="button button-secondary button-small" style="padding:6px 12px; font-size:12px;">
						<i class="fas fa-plus icon-green"></i> <?= tohtml(__tr("Add My IP (1-Click)", "Mevcut IP'mi Ekle (Tek Tıkla)")) ?>
					</button>
				</form>
			</div>
		</div>

		<form method="post" action="/list/waf/" style="margin:0;">
			<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
			<input type="hidden" name="action_global_whitelist" value="1">
			<input type="hidden" name="whitelist_act" value="set">
			<div style="display:flex; gap:12px; align-items:flex-start; flex-wrap:wrap;">
				<div style="flex:1; min-width:280px;">
					<textarea name="whitelist_ips" class="form-control" rows="2" placeholder="85.105.12.34 176.240.10.5 192.168.1.0/24" style="width:100%; font-family:monospace; font-size:12px;"><?= tohtml(implode(" ", $global_whitelist_ips ?? [])) ?></textarea>
					<small class="u-text-muted"><?= tohtml(__tr("Enter your and your partner's IP addresses / CIDR ranges separated by spaces.", "Sizin ve ortağınızın IP adreslerini boşlukla ayırarak giriniz.")) ?></small>
				</div>
				<div>
					<button type="submit" class="button button-primary" style="padding:8px 16px;">
						<i class="fas fa-floppy-disk"></i> <?= tohtml(__tr("Save Global Whitelist", "Whitelist'i Kaydet")) ?>
					</button>
				</div>
			</div>
		</form>
	</div>

	<!-- Section: Domain Threat Shield Matrix -->
	<div style="margin-bottom: 30px;">
		<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
			<h2 style="font-size:1.3rem; margin:0; font-weight:bold; display:flex; align-items:center; gap:8px;">
				<i class="fas fa-shield-halved icon-blue"></i>
				<?= tohtml(__tr("Domain Security & Threat Matrix", "Web Alan Adı Güvenlik & Tehdit Matrisi")) ?>
			</h2>
			<div style="font-size:12px; color:var(--color-text-muted);">
				<?= count($domains) ?> <?= tohtml(__tr("Domains Registered", "Alan Adı Kayıtlı")) ?>
			</div>
		</div>

		<div class="units-table" style="background:var(--color-background, #fff); border-radius:8px; border:1px solid var(--border-color, #334155); overflow:hidden;">
			<div class="units-table-header" style="background:rgba(0,0,0,0.03); font-weight:bold;">
				<div class="units-table-cell" style="flex: 2.2;"><?= tohtml(__tr("Domain & Owner", "Alan Adı & Sahibi")) ?></div>
				<div class="units-table-cell u-text-center" style="flex: 1.8;"><?= tohtml(__tr("WAF Shield Mode", "WAF Kalkan Modu")) ?></div>
				<div class="units-table-cell u-text-center" style="flex: 1.8;"><?= tohtml(__tr("GeoIP & IP Filter", "GeoIP & IP Filtresi")) ?></div>
				<div class="units-table-cell u-text-center" style="flex: 1.8;"><?= tohtml(__tr("Malware Health", "Zararlı Kod Durumu")) ?></div>
				<div class="units-table-cell u-text-center" style="flex: 1.4;"><?= tohtml(__tr("Last Scan", "Son Tarama")) ?></div>
				<div class="units-table-cell u-text-center" style="flex: 1.4;"><?= tohtml(__tr("Quick Actions", "İşlemler")) ?></div>
			</div>

			<?php if (empty($domains)): ?>
				<div class="units-table-row u-text-center u-p20">
					<p class="u-text-muted"><?= tohtml(__tr("No web domains configured yet.", "Henüz yapılandırılmış web sitesi bulunamadı.")) ?></p>
				</div>
			<?php else: ?>
				<?php foreach ($domains as $dname => $dinfo): 
					$waf_mode = strtolower($dinfo["waf_mode"] ?? "off");
					$u_owner = $dinfo["user"] ?? $user;
					$geoip_mode = strtolower($dinfo["geoip_mode"] ?? "off");
					$countries = $dinfo["geoip_countries"] ?? "";
					$ips = $dinfo["geoip_ips"] ?? "";
					$threats = (int)($dinfo["threats_found"] ?? 0);
					$last_scan = $dinfo["last_malware_scan"] ?? "";
					$scan_data = $malware_scans[$dname] ?? null;
				?>
					<div class="units-table-row" style="align-items:center;">
						<!-- Domain Name -->
						<div class="units-table-cell" style="flex: 2.2;">
							<div style="display:flex; align-items:center; gap:8px;">
								<i class="fas fa-globe icon-blue"></i>
								<div>
									<a href="http://<?= tohtml($dname) ?>/" target="_blank" class="u-text-bold" style="color:var(--color-text); text-decoration:none;">
										<?= tohtml($dname) ?>
									</a>
									<small class="u-text-muted" style="display:block; font-size:11px;">
										<i class="fas fa-user u-mr5"></i><?= tohtml($u_owner) ?>
										<?php if (($dinfo['ssl'] ?? '') === 'yes'): ?>
											<span style="color:#22c55e; margin-left:6px;"><i class="fas fa-lock"></i> SSL</span>
										<?php endif; ?>
									</small>
								</div>
							</div>
						</div>

						<!-- WAF Mode Selector -->
						<div class="units-table-cell u-text-center" style="flex: 1.8;">
							<form method="post" action="/list/waf/" style="margin:0;">
								<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
								<input type="hidden" name="action_waf_mode" value="1">
								<input type="hidden" name="waf_user" value="<?= tohtml($u_owner) ?>">
								<input type="hidden" name="waf_domain" value="<?= tohtml($dname) ?>">
								
								<select name="waf_mode" class="form-select" onchange="this.form.submit();" style="font-size:11px; padding:4px 8px; font-weight:bold; border-radius:6px; <?= $waf_mode === 'aggressive' ? 'border-color:#a855f7; background:rgba(168,85,247,0.1); color:#9333ea;' : ($waf_mode === 'block' ? 'border-color:#22c55e; background:rgba(34,197,94,0.1); color:#16a34a;' : ($waf_mode === 'detect' ? 'border-color:#38bdf8; background:rgba(56,189,248,0.1); color:#0284c7;' : 'border-color:#94a3b8; color:#64748b;')) ?>">
									<option value="off" <?= $waf_mode === 'off' ? 'selected' : '' ?>>⚪ <?= tohtml(__tr("Off (Disabled)", "Kapalı (Devre Dışı)")) ?></option>
									<option value="detect" <?= $waf_mode === 'detect' ? 'selected' : '' ?>>🔵 <?= tohtml(__tr("Detect (Monitor Only)", "İzleme (Log Tut)")) ?></option>
									<option value="block" <?= $waf_mode === 'block' ? 'selected' : '' ?>>🟢 <?= tohtml(__tr("Block (Active Defense)", "Aktif Koruma (Blokla)")) ?></option>
									<option value="aggressive" <?= $waf_mode === 'aggressive' ? 'selected' : '' ?>>🟣 <?= tohtml(__tr("Aggressive (Max Shield)", "Maksimum Kalkan (Agresif)")) ?></option>
								</select>
							</form>
						</div>

						<!-- GeoIP Status & Modal Trigger -->
						<div class="units-table-cell u-text-center" style="flex: 1.8;">
							<button type="button" class="button button-secondary" onclick="openGeoipModal('<?= tohtml($dname) ?>', '<?= tohtml($u_owner) ?>', '<?= tohtml($geoip_mode) ?>', '<?= tohtml($countries) ?>', '<?= tohtml($ips) ?>');" style="font-size:11px; padding:3px 8px; border-radius:5px;">
								<?php if ($geoip_mode === 'deny' && !empty($countries)): ?>
									<span style="color:#f97316;"><i class="fas fa-ban"></i> 🚫 <?= tohtml($countries) ?></span>
								<?php elseif ($geoip_mode === 'allow' && !empty($countries)): ?>
									<span style="color:#0ea5e9;"><i class="fas fa-check-circle"></i> 🌐 <?= tohtml($countries) ?></span>
								<?php else: ?>
									<span class="u-text-muted"><i class="fas fa-earth-americas"></i> <?= tohtml(__tr("Off / Unset", "Kapalı")) ?></span>
								<?php endif; ?>
							</button>
						</div>

						<!-- Malware Scan Health -->
						<div class="units-table-cell u-text-center" style="flex: 1.8;">
							<?php if ($threats > 0): ?>
								<span class="badge" style="background:#fee2e2; color:#b91c1c; border:1px solid #f87171; padding:4px 8px; font-size:11px; font-weight:bold; cursor:pointer;" onclick="viewScanReport('<?= tohtml($dname) ?>');">
									⚠️ <?= $threats ?> <?= tohtml(__tr("Threats!", "Tehdit!")) ?>
								</span>
							<?php elseif (!empty($last_scan)): ?>
								<span class="badge" style="background:#dcfce7; color:#15803d; border:1px solid #86efac; padding:4px 8px; font-size:11px; font-weight:bold; cursor:pointer;" onclick="viewScanReport('<?= tohtml($dname) ?>');">
									✅ <?= tohtml(__tr("Clean (0 Threats)", "Temiz (0 Tehdit)")) ?>
								</span>
							<?php else: ?>
								<span class="badge" style="background:#f1f5f9; color:#64748b; border:1px solid #cbd5e1; padding:4px 8px; font-size:11px;">
									⚪ <?= tohtml(__tr("Not Scanned", "Taranmadı")) ?>
								</span>
							<?php endif; ?>
						</div>

						<!-- Last Scan Date -->
						<div class="units-table-cell u-text-center" style="flex: 1.4; font-size:11px; color:var(--color-text-muted);">
							<?= !empty($last_scan) ? tohtml($last_scan) : '-' ?>
						</div>

						<!-- Quick Actions -->
						<div class="units-table-cell u-text-center" style="flex: 1.4; display:flex; justify-content:center; gap:6px;">
							<!-- Scan Single Domain Button -->
							<form method="post" action="/list/waf/" style="display:inline;" onsubmit="const b = this.querySelector('button'); b.disabled=true; b.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i>';">
								<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
								<input type="hidden" name="action_scan_domain" value="1">
								<input type="hidden" name="scan_user" value="<?= tohtml($u_owner) ?>">
								<input type="hidden" name="scan_domain" value="<?= tohtml($dname) ?>">
								<input type="hidden" name="quarantine" value="yes">
								<button type="submit" class="button button-secondary button-small" title="<?= tohtml(__tr("Run Malware Scan with Auto-Quarantine", "Otomatik Karantina ile Malware Tara")) ?>" style="padding:4px 8px; font-size:11px;">
									<i class="fas fa-magnifying-glass icon-blue"></i>
								</button>
							</form>

							<!-- GeoIP settings trigger -->
							<button type="button" class="button button-secondary button-small" onclick="openGeoipModal('<?= tohtml($dname) ?>', '<?= tohtml($u_owner) ?>', '<?= tohtml($geoip_mode) ?>', '<?= tohtml($countries) ?>', '<?= tohtml($ips) ?>');" title="<?= tohtml(__tr("Configure GeoIP & IP Access", "GeoIP ve IP Ayarları")) ?>" style="padding:4px 8px; font-size:11px;">
								<i class="fas fa-globe-americas icon-purple"></i>
							</button>

							<!-- Scan report viewer if scan exists -->
							<?php if (!empty($scan_data)): ?>
								<button type="button" class="button button-secondary button-small" onclick="viewScanReport('<?= tohtml($dname) ?>');" title="<?= tohtml(__tr("View Scan Report Details", "Tarama Raporunu İncele")) ?>" style="padding:4px 8px; font-size:11px;">
									<i class="fas fa-file-lines icon-green"></i>
								</button>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>

	<!-- Section: Live Threat Activity & Block Events -->
	<div style="margin-bottom: 30px;">
		<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
			<h2 style="font-size:1.3rem; margin:0; font-weight:bold; display:flex; align-items:center; gap:8px;">
				<i class="fas fa-bolt icon-orange"></i>
				<?= tohtml(__tr("Live Threat Intelligence & Block Feed", "Canlı Tehdit Algılama & Bloklama Akışı")) ?>
			</h2>
			<div style="font-size:12px; color:var(--color-text-muted);">
				<?= count($recent_threat_events) ?> <?= tohtml(__tr("Recent Security Events", "Son Güvenlik Olayı")) ?>
			</div>
		</div>

		<div class="units-table" style="background:var(--color-background, #fff); border-radius:8px; border:1px solid var(--border-color, #334155); overflow:hidden;">
			<div class="units-table-header" style="background:rgba(0,0,0,0.03); font-weight:bold;">
				<div class="units-table-cell" style="flex: 1.4;"><?= tohtml(__tr("Timestamp", "Zaman")) ?></div>
				<div class="units-table-cell" style="flex: 1.4;"><?= tohtml(__tr("Target Domain", "Hedef Site")) ?></div>
				<div class="units-table-cell" style="flex: 1.2;"><?= tohtml(__tr("Client IP", "Kaynak IP")) ?></div>
				<div class="units-table-cell" style="flex: 2.2;"><?= tohtml(__tr("Threat Classification", "Tehdit Türü")) ?></div>
				<div class="units-table-cell u-text-center" style="flex: 1.4;"><?= tohtml(__tr("Shield Action", "Kalkan Aksiyonu")) ?></div>
			</div>

			<?php if (empty($recent_threat_events)): ?>
				<div class="units-table-row u-text-center u-p20">
					<p class="u-text-muted">
						<i class="fas fa-circle-check icon-green u-mr5"></i>
						<?= tohtml(__tr("No malicious attacks or blocked requests detected in recent logs.", "Son loglarda tespit edilen zararlı saldırı veya engellenen istek yok. Sistem güvende.")) ?>
					</p>
				</div>
			<?php else: ?>
				<?php foreach ($recent_threat_events as $event): ?>
					<div class="units-table-row" style="align-items:center; font-size:12px;">
						<div class="units-table-cell" style="flex: 1.4; font-family:monospace; color:var(--color-text-muted);">
							<?= tohtml($event["timestamp"] ?? "") ?>
						</div>
						<div class="units-table-cell u-text-bold" style="flex: 1.4;">
							<?= tohtml($event["domain"] ?? "") ?>
						</div>
						<div class="units-table-cell" style="flex: 1.2; font-family:monospace;">
							<?= tohtml($event["client_ip"] ?? "") ?>
						</div>
						<div class="units-table-cell" style="flex: 2.2;">
							<span class="badge" style="background:rgba(239,68,68,0.1); color:#ef4444; border:1px solid rgba(239,68,68,0.3); padding:2px 6px; font-size:11px;">
								<?= tohtml($event["threat_type"] ?? "Threat") ?>
							</span>
						</div>
						<div class="units-table-cell u-text-center" style="flex: 1.4;">
							<span class="badge badge-danger" style="font-size:10px; padding:3px 6px; background:#ef4444; color:#fff;">
								🛑 <?= tohtml($event["action"] ?? "BLOCKED") ?>
							</span>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>

	<!-- Section: Active Banned IP Addresses (Fail2ban Integration) -->
	<?php if (!empty($banned_ips)): ?>
		<div style="margin-bottom: 30px;">
			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
				<h2 style="font-size:1.3rem; margin:0; font-weight:bold; display:flex; align-items:center; gap:8px;">
					<i class="fas fa-ban icon-red"></i>
					<?= tohtml(__tr("Active Fail2Ban & Firewall Blocked IPs", "Fail2Ban & Güvenlik Duvarı Aktif Engelli IP'ler")) ?>
				</h2>
			</div>

			<div class="units-table" style="background:var(--color-background, #fff); border-radius:8px; border:1px solid var(--border-color, #334155); overflow:hidden;">
				<div class="units-table-header" style="background:rgba(0,0,0,0.03); font-weight:bold;">
					<div class="units-table-cell" style="flex: 2;"><?= tohtml(__tr("Banned IP Address", "Engellenen IP Adresi")) ?></div>
					<div class="units-table-cell" style="flex: 2;"><?= tohtml(__tr("Jail / Chain", "Kural / Zincir")) ?></div>
					<div class="units-table-cell" style="flex: 2;"><?= tohtml(__tr("Ban Time", "Engelleme Zamanı")) ?></div>
					<div class="units-table-cell u-text-center" style="flex: 1.2;"><?= tohtml(__tr("Action", "İşlem")) ?></div>
				</div>

				<?php foreach ($banned_ips as $bip => $binfo): ?>
					<div class="units-table-row" style="align-items:center; font-size:12px;">
						<div class="units-table-cell u-text-bold" style="flex: 2; font-family:monospace;">
							<?= tohtml($bip) ?>
						</div>
						<div class="units-table-cell" style="flex: 2;">
							<?= tohtml($binfo['CHAIN'] ?? 'HESTIA') ?>
						</div>
						<div class="units-table-cell" style="flex: 2; color:var(--color-text-muted);">
							<?= tohtml(($binfo['DATE'] ?? '') . ' ' . ($binfo['TIME'] ?? '')) ?>
						</div>
						<div class="units-table-cell u-text-center" style="flex: 1.2;">
							<form method="post" action="/list/waf/" style="margin:0;">
								<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
								<input type="hidden" name="action_unban_ip" value="1">
								<input type="hidden" name="banned_ip" value="<?= tohtml($bip) ?>">
								<input type="hidden" name="ban_chain" value="<?= tohtml($binfo['CHAIN'] ?? 'HESTIA') ?>">
								<button type="submit" class="button button-danger button-small" style="padding:3px 8px; font-size:11px;">
									<?= tohtml(__tr("Unban", "Engeli Kaldır")) ?>
								</button>
							</form>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
</div>

<!-- Modal 1: GeoIP Configuration Modal -->
<div id="geoipModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.6); align-items:center; justify-content:center;">
	<div style="background:var(--color-background, #fff); width:90%; max-width:560px; border-radius:10px; border:1px solid var(--border-color, #334155); box-shadow:0 20px 25px -5px rgba(0,0,0,0.3); overflow:hidden;">
		<div style="padding:16px 20px; border-bottom:1px solid var(--border-color, #e2e8f0); display:flex; justify-content:space-between; align-items:center;">
			<h3 style="margin:0; font-size:1.1rem; font-weight:bold; display:flex; align-items:center; gap:8px;">
				<i class="fas fa-earth-americas icon-blue"></i>
				<?= tohtml(__tr("GeoIP & IP Access Control", "GeoIP ve IP Erişim Kuralları")) ?>
			</h3>
			<button type="button" onclick="closeGeoipModal();" style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:var(--color-text-muted);">&times;</button>
		</div>

		<form method="post" action="/list/waf/" style="padding:20px; margin:0;">
			<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
			<input type="hidden" name="action_geoip" value="1">
			<input type="hidden" id="modal_geoip_user" name="geoip_user" value="">
			<input type="hidden" id="modal_geoip_domain" name="geoip_domain" value="">

			<div style="margin-bottom:15px;">
				<label class="form-label u-text-bold" style="font-size:12px; margin-bottom:5px; display:block;">
					<?= tohtml(__tr("Target Domain:", "Hedef Web Sitesi:")) ?> <span id="modal_domain_display" style="color:var(--icon-color-blue, #0284c7);"></span>
				</label>
			</div>

			<div style="margin-bottom:15px;">
				<label class="form-label u-text-bold" style="font-size:12px; margin-bottom:5px; display:block;">
					<?= tohtml(__tr("Filter Policy (Mode)", "Filtreleme Politikası (Mod)")) ?>
				</label>
				<select id="modal_geoip_action" name="geoip_action" class="form-select" style="width:100%;">
					<option value="off">⚪ <?= tohtml(__tr("Disabled (Allow All Traffic)", "Devre Dışı (Tüm Ülkelere İzin Ver)")) ?></option>
					<option value="deny">🚫 <?= tohtml(__tr("Blacklist (Block Selected Countries / IPs)", "Kara Liste (Seçilen Ülkeleri / IP'leri Engelle)")) ?></option>
					<option value="allow">🌐 <?= tohtml(__tr("Whitelist (Allow ONLY Selected Countries / IPs)", "Beyaz Liste (YALNIZCA Seçilen Ülkelere İzin Ver)")) ?></option>
				</select>
			</div>

			<div style="margin-bottom:15px;">
				<label class="form-label u-text-bold" style="font-size:12px; margin-bottom:5px; display:block;">
					<?= tohtml(__tr("Country ISO-2 Codes (Comma Separated):", "Ülke ISO Kodları (Virgülle Ayrılmış):")) ?>
				</label>
				<input type="text" id="modal_geoip_countries" name="geoip_countries" class="form-control" placeholder="e.g. RU, CN, KP, IR" style="width:100%; font-family:monospace;">
				<small class="u-text-muted" style="font-size:11px; display:block; margin-top:4px;">
					<?= tohtml(__tr("Quick presets: RU, CN, KP, IR, VN, BR, IN (High bot traffic regions)", "Örnek hazır kodlar: RU, CN, KP, IR, VN, BR, IN")) ?>
				</small>
			</div>

			<div style="margin-bottom:20px;">
				<label class="form-label u-text-bold" style="font-size:12px; margin-bottom:5px; display:block;">
					<?= tohtml(__tr("IP Addresses / CIDR Blocks (Optional):", "IP Adresleri veya CIDR Blokları (İsteğe Bağlı):")) ?>
				</label>
				<input type="text" id="modal_geoip_ips" name="geoip_ips" class="form-control" placeholder="e.g. 192.168.1.100, 10.0.0.0/8" style="width:100%; font-family:monospace;">
			</div>

			<div style="display:flex; justify-content:flex-end; gap:10px;">
				<button type="button" class="button button-secondary" onclick="closeGeoipModal();">
					<?= tohtml(__tr("Cancel", "İptal")) ?>
				</button>
				<button type="submit" class="button button-primary">
					<i class="fas fa-floppy-disk"></i> <?= tohtml(__tr("Save GeoIP Rules", "Kuralları Kaydet")) ?>
				</button>
			</div>
		</form>
	</div>
</div>

<!-- Modal 2: Malware Scan Findings Viewer Modal -->
<div id="scanReportModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.6); align-items:center; justify-content:center;">
	<div style="background:var(--color-background, #fff); width:92%; max-width:800px; max-height:85vh; border-radius:10px; border:1px solid var(--border-color, #334155); box-shadow:0 20px 25px -5px rgba(0,0,0,0.3); display:flex; flex-direction:column; overflow:hidden;">
		<div style="padding:16px 20px; border-bottom:1px solid var(--border-color, #e2e8f0); display:flex; justify-content:space-between; align-items:center;">
			<h3 style="margin:0; font-size:1.1rem; font-weight:bold; display:flex; align-items:center; gap:8px;">
				<i class="fas fa-bug icon-red"></i>
				<span id="report_modal_title"><?= tohtml(__tr("Malware Scan Report", "Zararlı Kod Tarama Raporu")) ?></span>
			</h3>
			<button type="button" onclick="closeScanReportModal();" style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:var(--color-text-muted);">&times;</button>
		</div>

		<div id="report_modal_content" style="padding:20px; overflow-y:auto; flex:1;">
			<!-- Injected via JavaScript -->
		</div>

		<div style="padding:12px 20px; border-top:1px solid var(--border-color, #e2e8f0); display:flex; justify-content:flex-end;">
			<button type="button" class="button button-secondary" onclick="closeScanReportModal();">
				<?= tohtml(__tr("Close", "Kapat")) ?>
			</button>
		</div>
	</div>
</div>

<script>
// JSON reports cache
const scanReports = <?= json_encode($malware_scans, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

function openGeoipModal(domain, user, action, countries, ips) {
	document.getElementById('modal_geoip_domain').value = domain;
	document.getElementById('modal_geoip_user').value = user;
	document.getElementById('modal_domain_display').textContent = domain;
	document.getElementById('modal_geoip_action').value = action || 'off';
	document.getElementById('modal_geoip_countries').value = countries || '';
	document.getElementById('modal_geoip_ips').value = ips || '';
	document.getElementById('geoipModal').style.display = 'flex';
}

function closeGeoipModal() {
	document.getElementById('geoipModal').style.display = 'none';
}

function viewScanReport(domain) {
	const report = scanReports[domain];
	const contentDiv = document.getElementById('report_modal_content');
	document.getElementById('report_modal_title').textContent = domain + ' - ' + ('<?= (($_SESSION['language'] ?? '') === 'tr') ? "Zararlı Kod Raporu" : "Malware Scan Report" ?>');

	if (!report) {
		contentDiv.innerHTML = '<p class="u-text-muted"><?= tohtml(__tr("No scan data available for this domain.", "Bu alan adı için henüz tarama verisi bulunmuyor.")) ?></p>';
	} else {
		let html = '<div style="margin-bottom:15px; display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:10px;">';
		html += '<div style="padding:10px; background:rgba(0,0,0,0.03); border-radius:6px;"><strong><?= tohtml(__tr("Scanned Files:", "Taranan Dosyalar:")) ?></strong> ' + (report.scanned_files || 0) + '</div>';
		html += '<div style="padding:10px; background:rgba(0,0,0,0.03); border-radius:6px;"><strong><?= tohtml(__tr("Threats Found:", "Bulunan Tehditler:")) ?></strong> <span style="color:' + (report.threats_found > 0 ? '#ef4444' : '#22c55e') + '; font-weight:bold;">' + (report.threats_found || 0) + '</span></div>';
		html += '<div style="padding:10px; background:rgba(0,0,0,0.03); border-radius:6px;"><strong><?= tohtml(__tr("Quarantined:", "Karantinaya Alınan:")) ?></strong> ' + (report.quarantined || 0) + '</div>';
		html += '<div style="padding:10px; background:rgba(0,0,0,0.03); border-radius:6px;"><strong><?= tohtml(__tr("Scan Time:", "Tarama Tarihi:")) ?></strong> ' + (report.scan_time || '-') + '</div>';
		html += '</div>';

		if (report.findings && report.findings.length > 0) {
			html += '<h4 style="margin:15px 0 10px 0; font-weight:bold; color:#b91c1c;"><?= tohtml(__tr("Detected Security Threats & Signatures:", "Tespit Edilen Güvenlik Tehditleri:")) ?></h4>';
			html += '<div style="display:flex; flex-direction:column; gap:12px;">';
			report.findings.forEach((f, idx) => {
				html += '<div style="padding:12px; border-radius:6px; border:1px solid #fca5a5; background:#fef2f2;">';
				html += '<div style="display:flex; justify-content:space-between; margin-bottom:5px;">';
				html += '<strong style="color:#b91c1c;">#' + (idx + 1) + ' ' + (f.description || f.threat_type) + '</strong>';
				html += '<span class="badge" style="background:#ef4444; color:#fff; font-size:10px;">' + (f.severity || 'CRITICAL') + '</span>';
				html += '</div>';
				html += '<div style="font-size:12px; font-family:monospace; margin-bottom:4px;"><strong><?= tohtml(__tr("File:", "Dosya:")) ?></strong> ' + (f.file || f.relative_file) + ' (Line: ' + f.line + ')</div>';
				if (f.snippet) {
					html += '<div style="font-size:11px; font-family:monospace; background:rgba(0,0,0,0.06); padding:6px; border-radius:4px; margin-top:4px; overflow-x:auto;">' + f.snippet + '</div>';
				}
				if (f.quarantined) {
					html += '<div style="font-size:11px; color:#15803d; margin-top:5px;">🔒 <strong><?= tohtml(__tr("Status:", "Durum:")) ?></strong> <?= tohtml(__tr("File safely quarantined with permissions 000.", "Dosya 000 yetkisi ile güvenli karantinaya alındı.")) ?></div>';
				}
				html += '</div>';
			});
			html += '</div>';
		} else {
			html += '<div style="padding:20px; text-align:center; background:#f0fdf4; border:1px solid #86efac; border-radius:6px; color:#15803d;">';
			html += '<h4><i class="fas fa-circle-check"></i> <?= tohtml(__tr("Clean! No malware or webshell signatures detected.", "Temiz! Hiçbir zararlı kod veya webshell imzası bulunamadı.")) ?></h4>';
			html += '</div>';
		}

		contentDiv.innerHTML = html;
	}

	document.getElementById('scanReportModal').style.display = 'flex';
}

function closeScanReportModal() {
	document.getElementById('scanReportModal').style.display = 'none';
}

// Close modals when clicking outside
window.onclick = function(event) {
	const gModal = document.getElementById('geoipModal');
	const sModal = document.getElementById('scanReportModal');
	if (event.target === gModal) gModal.style.display = 'none';
	if (event.target === sModal) sModal.style.display = 'none';
};
</script>
