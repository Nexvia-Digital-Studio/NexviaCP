<?php
$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');
$total_srv_mem = 0;
$total_srv_cpu = 0;
if (!empty($services)) {
	foreach ($services as $s) {
		$total_srv_mem += (float)($s["MEM"] ?? 0);
		$total_srv_cpu += (float)($s["CPU"] ?? 0);
	}
}
?>

<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/web/">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml(__tr("Back to Web", "Web Sitelerine Dön")) ?>
			</a>
			<form method="post" action="/list/resources/" style="display:inline;" onsubmit="const b = this.querySelector('button'); b.disabled=true; b.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> ' + ('<?= $is_tr ? "Optimize Ediliyor..." : "Optimizing..." ?>');">
				<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
				<input type="hidden" name="tune_all" value="1">
				<button type="submit" class="button button-primary" title="<?= tohtml($is_tr ? "Tüm sitelerin trafik ve kaynak profillerini analiz et ve akıllı limitleri uygula" : "Auto-tune all domains") ?>">
					<i class="fas fa-wand-magic-sparkles"></i> <?= tohtml(__tr("Auto-Tune & Optimize Now", "Şimdi Akıllı Optimize Et")) ?>
				</button>
			</form>
			<a href="/list/rrd/" class="button button-secondary">
				<i class="fas fa-chart-line icon-green"></i> <?= tohtml($is_tr ? "Sistem Grafikleri (RRD)" : "Telemetry Charts") ?>
			</a>
		</div>
		<div class="toolbar-right">
			<span style="font-size: 11px; color: var(--color-text-muted, #94a3b8);">
				<i class="fas fa-microchip icon-blue"></i>
				<?= tohtml($is_tr ? "Canlı Kaynak & Süreç Yönetim Merkezi" : "Live Resource & Process Governance Hub") ?>
			</span>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container" style="max-width: 1400px; padding: 20px 24px; margin: 0 auto;">

	<!-- Page Title Header -->
	<div style="margin-bottom: 22px;">
		<h1 style="font-size: 1.5rem; font-weight: 700; margin: 0 0 6px 0; color: var(--color-text, #fff); display: flex; align-items: center; gap: 10px;">
			<i class="fas fa-gauge-high" style="color: var(--icon-color-blue, #38bdf8);"></i>
			<?= tohtml($is_tr ? "Anlık Kaynak Tüketimi & Süreç Kontrol Portalı" : "Live Resource Monitoring & Process Control") ?>
		</h1>
		<p style="color: var(--color-text-muted, #94a3b8); margin: 0; font-size: 13px; line-height: 1.5;">
			<?= tohtml($is_tr ? "Web siteleri, API servisleri ve sistem arka plan servislerinin anlık RAM / CPU tüketimlerini tek bir ekranda izleyebilir; istediğiniz siteyi veya servisi tek tıkla durdurup başlatabilirsiniz." : "Monitor live per-domain, API app, and system service memory/CPU usage and control processes with 1-click.") ?>
		</p>
	</div>

	<?php show_alert_message($_SESSION); ?>

	<!-- Top Stats Overview Grid -->
	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 15px; margin-bottom: 24px;">
		
		<!-- Stat 1: Total System RAM -->
		<div style="background: var(--color-background, #282828); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<div>
					<div style="font-size:11px; text-transform:uppercase; font-weight:700; color: var(--color-text-muted, #94a3b8); letter-spacing:0.5px;">
						<?= tohtml($is_tr ? "Fiziksel Bellek (RAM)" : "Total System RAM") ?>
					</div>
					<h2 style="margin:6px 0 0 0; font-size:1.5rem; font-weight:bold; color:var(--color-text, #fff);">
						<?= round(($live_ram["used_mb"] ?? 3072) / 1024, 1) ?> GB <span style="font-size:12px; font-weight:normal; color:var(--color-text-muted, #94a3b8);">/ <?= round(($live_ram["total_mb"] ?? 15974) / 1024, 1) ?> GB (<?= $live_ram["used_pct"] ?? 19 ?>%)</span>
					</h2>
				</div>
				<div style="width:40px; height:40px; border-radius:8px; background:rgba(34, 197, 94, 0.12); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-memory fa-lg" style="color:var(--icon-color-green, #22c55e);"></i>
				</div>
			</div>
			<div style="margin-top:10px; font-size:11.5px; color:var(--icon-color-green, #22c55e);">
				🟢 <?= tohtml($is_tr ? "Boş/Kullanılabilir: " . round(($live_ram["free_mb"] ?? 12902) / 1024, 1) . " GB" : "Available: " . round(($live_ram["free_mb"] ?? 12902) / 1024, 1) . " GB") ?>
			</div>
		</div>

		<!-- Stat 2: Monitored Web Domains -->
		<div style="background: var(--color-background, #282828); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<div>
					<div style="font-size:11px; text-transform:uppercase; font-weight:700; color: var(--color-text-muted, #94a3b8); letter-spacing:0.5px;">
						<?= tohtml($is_tr ? "Web Siteleri" : "Web Domains") ?>
					</div>
					<h2 style="margin:6px 0 0 0; font-size:1.5rem; font-weight:bold; color:var(--icon-color-blue, #38bdf8);">
						<?= (int)($summary["TOTAL_DOMAINS"] ?? count($domains)) ?> <span style="font-size:12px; font-weight:normal; color:var(--color-text-muted, #94a3b8);"><?= tohtml($is_tr ? "Site" : "Sites") ?></span>
					</h2>
				</div>
				<div style="width:40px; height:40px; border-radius:8px; background:rgba(56, 189, 248, 0.12); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-earth-americas fa-lg" style="color:var(--icon-color-blue, #38bdf8);"></i>
				</div>
			</div>
			<div style="margin-top:10px; font-size:11.5px; color:var(--color-text-muted, #94a3b8);">
				⚡ <?= (int)($summary["AUTO_MANAGED_COUNT"] ?? 0) ?> <?= tohtml($is_tr ? "Akıllı Otomatik Yönetilen" : "Auto-Managed") ?>
			</div>
		</div>

		<!-- Stat 3: Eco-Throttled / Idle Savings -->
		<div style="background: var(--color-background, #282828); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<div>
					<div style="font-size:11px; text-transform:uppercase; font-weight:700; color: var(--color-text-muted, #94a3b8); letter-spacing:0.5px;">
						<?= tohtml($is_tr ? "Eco-Uyku Tasarrufu" : "Eco-Idle Savings") ?>
					</div>
					<h2 style="margin:6px 0 0 0; font-size:1.5rem; font-weight:bold; color:var(--icon-color-purple, #a855f7);">
						~<?= (int)($summary["ESTIMATED_SAVED_RAM_MB"] ?? 0) ?> MB
					</h2>
				</div>
				<div style="width:40px; height:40px; border-radius:8px; background:rgba(168, 85, 247, 0.12); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-leaf fa-lg" style="color:var(--icon-color-purple, #a855f7);"></i>
				</div>
			</div>
			<div style="margin-top:10px; font-size:11.5px; color:var(--color-text-muted, #94a3b8);">
				💤 <?= (int)(($summary["IDLE_COUNT"] ?? 0) + ($summary["THROTTLED_COUNT"] ?? 0)) ?> <?= tohtml($is_tr ? "uykuda / kısılan site" : "idle sites throttled") ?>
			</div>
		</div>

		<!-- Stat 4: System Services -->
		<div style="background: var(--color-background, #282828); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<div>
					<div style="font-size:11px; text-transform:uppercase; font-weight:700; color: var(--color-text-muted, #94a3b8); letter-spacing:0.5px;">
						<?= tohtml($is_tr ? "Sistem Servisleri" : "System Daemons") ?>
					</div>
					<h2 style="margin:6px 0 0 0; font-size:1.5rem; font-weight:bold; color:var(--icon-color-orange, #f97316);">
						<?= count($services) ?> <span style="font-size:12px; font-weight:normal; color:var(--color-text-muted, #94a3b8);"><?= tohtml($is_tr ? "Servis" : "Services") ?></span>
					</h2>
				</div>
				<div style="width:40px; height:40px; border-radius:8px; background:rgba(249, 115, 22, 0.12); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-server fa-lg" style="color:var(--icon-color-orange, #f97316);"></i>
				</div>
			</div>
			<div style="margin-top:10px; font-size:11.5px; color:var(--color-text-muted, #94a3b8);">
				⚙️ Nginx, SQL, PHP-FPM, Redis
			</div>
		</div>

	</div>

	<!-- Navigation Tabs & Search Controls -->
	<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 20px;">
		
		<!-- Category Filter Tabs -->
		<div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
			<button type="button" class="button button-primary res-tab-btn active" onclick="switchResTab('domains', this);" style="font-size: 12.5px;">
				<i class="fas fa-earth-americas u-mr5"></i> <?= tohtml($is_tr ? "Web Siteleri & Domainler" : "Web Domains") ?> (<?= count($domains) ?>)
			</button>
			<button type="button" class="button button-secondary res-tab-btn" onclick="switchResTab('apps', this);" style="font-size: 12.5px;">
				<i class="fas fa-cube u-mr5"></i> <?= tohtml($is_tr ? "API & Uygulamalar (Docker / Node)" : "API & Apps") ?> (<?= count($docker_apps) ?>)
			</button>
			<button type="button" class="button button-secondary res-tab-btn" onclick="switchResTab('services', this);" style="font-size: 12.5px;">
				<i class="fas fa-sliders u-mr5"></i> <?= tohtml($is_tr ? "Sistem Servisleri" : "System Services") ?> (<?= count($services) ?>)
			</button>
		</div>

		<!-- Search Input -->
		<div style="min-width: 260px;">
			<input type="text" id="res-search-input" class="form-control" placeholder="<?= tohtml($is_tr ? "🔍 İsim veya servis ara…" : "🔍 Search domain or service…") ?>" onkeyup="filterResourceItems();" style="font-size: 13px; padding: 7px 12px; width: 100%;">
		</div>

	</div>

	<!-- SECTION 1: WEB DOMAINS & SITES TABLE -->
	<div id="section-domains" class="res-content-section" style="display: block;">
		<div style="background: var(--color-background, #282828); border: 1px solid var(--border-color, #334155); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
			<div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color, #334155); display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.15);">
				<h3 style="margin: 0; font-size: 1rem; color: var(--color-text, #fff); display: flex; align-items: center; gap: 8px;">
					<i class="fas fa-globe icon-blue"></i>
					<?= tohtml($is_tr ? "Web Siteleri & Domain Kaynak Kullanımı" : "Web Domain Resource Consumption") ?>
				</h3>
				<span style="font-size: 11px; color: var(--color-text-muted, #94a3b8);">
					<?= count($domains) ?> <?= tohtml($is_tr ? "domain izleniyor" : "domains monitored") ?>
				</span>
			</div>

			<div style="overflow-x: auto; width: 100%;">
				<table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
					<thead>
						<tr style="background: rgba(0, 0, 0, 0.25); border-bottom: 2px solid var(--border-color, #334155);">
							<th style="padding: 12px 14px; color: var(--color-text-muted, #94a3b8); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "Domain Adı & Sahibi" : "Domain & Owner") ?></th>
							<th style="padding: 12px 14px; width: 140px; color: var(--color-text-muted, #94a3b8); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "Anlık RAM Tüketimi" : "Live RAM Usage") ?></th>
							<th style="padding: 12px 14px; width: 130px; color: var(--color-text-muted, #94a3b8); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "CPU / Limit" : "CPU Quota") ?></th>
							<th style="padding: 12px 14px; width: 140px; color: var(--color-text-muted, #94a3b8); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "Talep / Durum" : "Demand State") ?></th>
							<th style="padding: 12px 14px; width: 150px; color: var(--color-text-muted, #94a3b8); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "Öncelik Kademe" : "Priority Tier") ?></th>
							<th style="padding: 12px 16px; width: 230px; text-align: right; color: var(--color-text-muted, #94a3b8); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "Süreç Kontrolleri" : "Actions") ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($domains)): ?>
							<tr>
								<td colspan="6" style="padding: 40px; text-align: center; color: var(--color-text-muted, #94a3b8);">
									<?= tohtml($is_tr ? "Henüz kayıtlı web sitesi bulunamadı." : "No web domains found.") ?>
								</td>
							</tr>
						<?php else: ?>
							<?php foreach ($domains as $d_key => $d_val): 
								$d_name = $d_val["DOMAIN"] ?? $d_key;
								$d_user = $d_val["USER"] ?? $user_plain;
								$d_mem = (int)($d_val["MEMORY_USAGE_MB"] ?? 0);
								$d_high = $d_val["MEMORY_HIGH"] ?? "256M";
								$d_cpu = $d_val["CPU_QUOTA"] ?? "100%";
								$d_status = $d_val["STATUS"] ?? "active";
								$d_prio = (int)($d_val["PRIORITY"] ?? 0);
								$d_workers = (int)($d_val["PHP_WORKERS"] ?? 0);
								$d_suspended = $d_val["SUSPENDED"] ?? "no";
								$d_app_type = $d_val["APP_TYPE"] ?? "";
								$is_standalone = in_array($d_app_type, ["dotnet", "node-js", "python", "docker", "api"]) || ($d_mem >= 200);
							?>
								<tr class="res-item-row" data-name="<?= tohtml(strtolower($d_name . ' ' . $d_user . ' ' . $d_app_type)) ?>" style="border-bottom: 1px solid var(--border-color, #334155);">
									
									<!-- Domain & Owner -->
									<td style="padding: 12px 14px; vertical-align: middle;">
										<div style="display: flex; align-items: center; gap: 10px;">
											<i class="fas <?= $is_standalone ? 'fa-cube' : 'fa-earth-americas' ?>" style="font-size: 16px; color: <?= ($d_suspended === 'yes') ? 'var(--color-danger, #ef4444)' : ($is_standalone ? 'var(--icon-color-purple, #a855f7)' : 'var(--icon-color-blue, #38bdf8)') ?>; flex-shrink: 0;"></i>
											<div>
												<div style="display: flex; align-items: center; gap: 6px;">
													<strong style="color: var(--color-text, #fff); font-size: 13.5px;"><?= tohtml($d_name) ?></strong>
													<?php if (!empty($d_app_type)): ?>
														<span class="badge badge-purple" style="font-size: 9.5px; padding: 1px 5px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.3px;">
															<?= tohtml($d_app_type) ?>
														</span>
													<?php endif; ?>
													<a href="http://<?= tohtml($d_name) ?>" target="_blank" rel="noopener" style="color: var(--color-text-muted, #94a3b8); font-size: 11px;">
														<i class="fas fa-arrow-up-right-from-square"></i>
													</a>
												</div>
												<small style="color: var(--color-text-muted, #94a3b8); font-size: 11px;">
													<i class="fas fa-user u-mr5"></i><?= tohtml($d_user) ?>
													<?php if ($d_workers > 0): ?>
														· <span style="color: var(--icon-color-green, #22c55e);"><i class="fas fa-microchip"></i> <?= $d_workers ?> worker</span>
													<?php endif; ?>
												</small>
											</div>
										</div>
									</td>

									<!-- Live RAM Usage -->
									<td style="padding: 12px 14px; vertical-align: middle; white-space: nowrap;">
										<span style="font-weight: 700; font-size: 13.5px; color: <?= $d_mem > 512 ? 'var(--icon-color-orange, #f97316)' : 'var(--color-text, #fff)' ?>;">
											<?= $d_mem > 0 ? $d_mem . " MB" : "<10 MB" ?>
										</span>
										<div style="font-size: 10.5px; color: var(--color-text-muted, #94a3b8);" title="<?= tohtml($is_tr ? ($is_standalone ? "Uygulama için tahsis edilen akıllı bellek tavanı" : "Tek PHP isteği için izin verilen tavan memory_limit") : "Allocated memory limit") ?>">
											Limit: <?= tohtml($d_high) ?> <?= $is_standalone ? "" : "/ istek" ?>
										</div>
									</td>

									<!-- CPU Quota -->
									<td style="padding: 12px 14px; vertical-align: middle; white-space: nowrap; font-size: 12.5px; color: var(--color-text, #fff);">
										<?= tohtml($d_cpu) ?>
									</td>

									<!-- Demand Status -->
									<td style="padding: 12px 14px; vertical-align: middle; white-space: nowrap;">
										<?php if ($d_suspended === 'yes'): ?>
											<span class="badge badge-danger" style="font-size: 11px; padding: 4px 8px;">
												<i class="fas fa-ban u-mr5"></i><?= tohtml($is_tr ? "Site Kapalı (Askıda)" : "Suspended") ?>
											</span>
										<?php elseif ($d_status === 'idle' || $d_status === 'throttled'): ?>
											<span class="badge badge-secondary" style="font-size: 11px; padding: 4px 8px; color: var(--icon-color-green, #22c55e);">
												<i class="fas fa-leaf u-mr5"></i><?= tohtml($is_tr ? "Eco-Uyku (64M)" : "Eco-Idle") ?>
											</span>
										<?php elseif ($d_status === 'boosted' || $d_status === 'vip'): ?>
											<span class="badge badge-purple" style="font-size: 11px; padding: 4px 8px;">
												<i class="fas fa-rocket u-mr5"></i><?= tohtml($is_tr ? "Güçlendirilmiş" : "Boosted") ?>
											</span>
										<?php else: ?>
											<span class="badge badge-info" style="font-size: 11px; padding: 4px 8px;">
												<i class="fas fa-bolt u-mr5"></i><?= tohtml($is_tr ? "Aktif" : "Active") ?>
											</span>
										<?php endif; ?>
									</td>

									<!-- Priority Tier Selection -->
									<td style="padding: 12px 14px; vertical-align: middle; white-space: nowrap;">
										<form method="post" action="/list/resources/" style="display:inline;">
											<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
											<input type="hidden" name="change_priority" value="1">
											<input type="hidden" name="prio_user" value="<?= tohtml($d_user) ?>">
											<input type="hidden" name="prio_domain" value="<?= tohtml($d_name) ?>">
											<select name="prio_level" class="form-select" onchange="this.form.submit();" style="font-size: 11.5px; padding: 3px 6px; height: 28px;">
												<option value="0" <?= $d_prio === 0 ? "selected" : "" ?>>⚡ <?= tohtml($is_tr ? "0 (Akıllı Otomatik)" : "0 (Smart Auto)") ?></option>
												<option value="1" <?= $d_prio === 1 ? "selected" : "" ?>>🟢 <?= tohtml($is_tr ? "1 (Düşük / Eco)" : "1 (Low / Eco)") ?></option>
												<option value="2" <?= $d_prio === 2 ? "selected" : "" ?>>🔵 <?= tohtml($is_tr ? "2 (Standart)" : "2 (Standard)") ?></option>
												<option value="3" <?= $d_prio === 3 ? "selected" : "" ?>>🟣 <?= tohtml($is_tr ? "3 (Yüksek Öncelik)" : "3 (High Priority)") ?></option>
												<option value="4" <?= $d_prio === 4 ? "selected" : "" ?>>🟠 <?= tohtml($is_tr ? "4 (Kritik)" : "4 (Critical)") ?></option>
												<option value="5" <?= $d_prio === 5 ? "selected" : "" ?>>👑 <?= tohtml($is_tr ? "5 (VIP Maksimum)" : "5 (VIP Maximum)") ?></option>
											</select>
										</form>
									</td>

									<!-- Live Process Controls (Stop/Start, Flush PHP) -->
									<td style="padding: 12px 16px; vertical-align: middle; text-align: right; white-space: nowrap;">
										<div style="display: inline-flex; gap: 6px; align-items: center; justify-content: flex-end;">
											
											<!-- Flush PHP Memory -->
											<form method="post" action="/list/resources/" style="display:inline;" onsubmit="return confirm('<?= tohtml($is_tr ? $d_name . " için PHP-FPM iş parçacıkları ve bellek havuzu sıfırlansın mı?" : "Flush PHP workers for " . $d_name . "?") ?>');">
												<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
												<input type="hidden" name="flush_php_workers" value="1">
												<input type="hidden" name="flush_domain" value="<?= tohtml($d_name) ?>">
												<input type="hidden" name="flush_user" value="<?= tohtml($d_user) ?>">
												<button type="submit" class="button button-secondary" style="font-size: 11px; padding: 4px 8px;" title="<?= tohtml($is_tr ? "PHP Worker Belleğini Sıfırla" : "Flush PHP Workers") ?>">
													<i class="fas fa-broom"></i> <?= tohtml($is_tr ? "Belleği Boşalt" : "Flush RAM") ?>
												</button>
											</form>

											<!-- Site Open / Close Toggle -->
											<?php if ($d_suspended === 'yes'): ?>
												<form method="post" action="/list/resources/" style="display:inline;">
													<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
													<input type="hidden" name="toggle_domain_status" value="1">
													<input type="hidden" name="toggle_user" value="<?= tohtml($d_user) ?>">
													<input type="hidden" name="toggle_domain" value="<?= tohtml($d_name) ?>">
													<input type="hidden" name="toggle_action" value="unsuspend">
													<button type="submit" class="button button-secondary" style="font-size: 11px; padding: 4px 8px; color: var(--icon-color-green, #22c55e); border-color: rgba(34, 197, 94, 0.4);" title="<?= tohtml($is_tr ? "Siteyi ve Yönlendirmeyi Aç / Yayına Al" : "Open / Unsuspend Site") ?>">
														<i class="fas fa-play u-mr5"></i><?= tohtml($is_tr ? "Siteyi Aç" : "Open") ?>
													</button>
												</form>
											<?php else: ?>
												<form method="post" action="/list/resources/" style="display:inline;" onsubmit="return confirm('<?= tohtml($is_tr ? $d_name . " sitesini kapatıp askıya almak istediğinize emin misiniz?" : "Suspend " . $d_name . "?") ?>');">
													<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
													<input type="hidden" name="toggle_domain_status" value="1">
													<input type="hidden" name="toggle_user" value="<?= tohtml($d_user) ?>">
													<input type="hidden" name="toggle_domain" value="<?= tohtml($d_name) ?>">
													<input type="hidden" name="toggle_action" value="suspend">
													<button type="submit" class="button button-secondary" style="font-size: 11px; padding: 4px 8px; color: var(--color-danger, #ef4444); border-color: rgba(239, 68, 68, 0.4);" title="<?= tohtml($is_tr ? "Siteyi ve Yönlendirmeyi Kapat / Askıya Al" : "Close / Suspend Site") ?>">
														<i class="fas fa-pause u-mr5"></i><?= tohtml($is_tr ? "Siteyi Kapat" : "Close") ?>
													</button>
												</form>
											<?php endif; ?>

										</div>
									</td>

								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<!-- SECTION 2: API & BACKEND APPS (DOCKER / NODE / PYTHON) -->
	<div id="section-apps" class="res-content-section" style="display: none;">
		<div style="background: var(--color-background, #282828); border: 1px solid var(--border-color, #334155); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
			<div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color, #334155); display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.15);">
				<h3 style="margin: 0; font-size: 1rem; color: var(--color-text, #fff); display: flex; align-items: center; gap: 8px;">
					<i class="fas fa-cube icon-purple"></i>
					<?= tohtml($is_tr ? "API'ler & Arka Plan Konteyner Uygulamaları" : "API & Container Backend Apps") ?>
				</h3>
				<span style="font-size: 11px; color: var(--color-text-muted, #94a3b8);">
					<?= count($docker_apps) ?> <?= tohtml($is_tr ? "uygulama kayıtlı" : "apps configured") ?>
				</span>
			</div>

			<div style="overflow-x: auto; width: 100%;">
				<table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
					<thead>
						<tr style="background: rgba(0, 0, 0, 0.25); border-bottom: 2px solid var(--border-color, #334155);">
							<th style="padding: 12px 14px; color: var(--color-text-muted, #94a3b8); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "Uygulama / API Adı" : "App / API Name") ?></th>
							<th style="padding: 12px 14px; width: 140px; color: var(--color-text-muted, #94a3b8); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "Tip & Şablon" : "Type") ?></th>
							<th style="padding: 12px 14px; width: 140px; color: var(--color-text-muted, #94a3b8); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "Bellek Limiti" : "Memory High") ?></th>
							<th style="padding: 12px 14px; width: 130px; text-align: center; color: var(--color-text-muted, #94a3b8); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "Durum" : "State") ?></th>
							<th style="padding: 12px 16px; width: 220px; text-align: right; color: var(--color-text-muted, #94a3b8); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "İşlemler" : "Actions") ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($docker_apps)): ?>
							<tr>
								<td colspan="5" style="padding: 40px; text-align: center; color: var(--color-text-muted, #94a3b8);">
									<i class="fas fa-cube fa-2x u-mb10" style="display:block; opacity:0.5;"></i>
									<?= tohtml($is_tr ? "Şu an çalışan özel Docker konteyneri veya bağımsız API uygulaması bulunmuyor." : "No standalone Docker apps configured yet.") ?>
								</td>
							</tr>
						<?php else: ?>
							<?php foreach ($docker_apps as $a_name => $a_info):
								$a_status = $a_info["STATUS"] ?? "running";
								$a_type = $a_info["TEMPLATE"] ?? "docker";
								$a_mem = $a_info["MEMORY_HIGH"] ?? "512M";
							?>
								<tr class="res-item-row" data-name="<?= tohtml(strtolower($a_name)) ?>" style="border-bottom: 1px solid var(--border-color, #334155);">
									<td style="padding: 12px 14px; vertical-align: middle;">
										<strong style="color: var(--color-text, #fff); font-size: 13.5px;"><i class="fas fa-cube icon-purple u-mr5"></i><?= tohtml($a_name) ?></strong>
									</td>
									<td style="padding: 12px 14px; vertical-align: middle; color: var(--color-text-muted, #94a3b8);">
										<?= tohtml($a_type) ?>
									</td>
									<td style="padding: 12px 14px; vertical-align: middle; font-weight: 700; color: var(--color-text, #fff);">
										<?= tohtml($a_mem) ?>
									</td>
									<td style="padding: 12px 14px; vertical-align: middle; text-align: center;">
										<span class="badge badge-success" style="font-size: 11px; padding: 4px 8px;">
											🟢 <?= tohtml($a_status) ?>
										</span>
									</td>
									<td style="padding: 12px 16px; vertical-align: middle; text-align: right; white-space: nowrap;">
										<form method="post" action="/list/resources/" style="display:inline;">
											<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
											<input type="hidden" name="app_action" value="1">
											<input type="hidden" name="app_name" value="<?= tohtml($a_name) ?>">
											<input type="hidden" name="app_action_type" value="restart">
											<button type="submit" class="button button-secondary" style="font-size: 11px; padding: 4px 8px;">
												<i class="fas fa-arrows-rotate"></i> <?= tohtml($is_tr ? "Yeniden Başlat" : "Restart") ?>
											</button>
										</form>
										<form method="post" action="/list/resources/" style="display:inline;" onsubmit="return confirm('<?= tohtml($is_tr ? $a_name . " uygulamasını durdurmak istediğinize emin misiniz?" : "Stop " . $a_name . "?") ?>');">
											<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
											<input type="hidden" name="app_action" value="1">
											<input type="hidden" name="app_name" value="<?= tohtml($a_name) ?>">
											<input type="hidden" name="app_action_type" value="stop">
											<button type="submit" class="button button-secondary" style="font-size: 11px; padding: 4px 8px; color: var(--color-danger, #ef4444);">
												<i class="fas fa-stop"></i> <?= tohtml($is_tr ? "Durdur" : "Stop") ?>
											</button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<!-- SECTION 3: SYSTEM DAEMONS & SERVICES TABLE -->
	<div id="section-services" class="res-content-section" style="display: none;">
		<div style="background: var(--color-background, #282828); border: 1px solid var(--border-color, #334155); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
			<div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color, #334155); display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.15);">
				<h3 style="margin: 0; font-size: 1rem; color: var(--color-text, #fff); display: flex; align-items: center; gap: 8px;">
					<i class="fas fa-sliders icon-orange"></i>
					<?= tohtml($is_tr ? "Sistem Servisleri Kaynak Tüketimi & Yönetimi" : "System Daemon Resource Allocation & Control") ?>
				</h3>
				<span style="font-size: 11px; color: var(--color-text-muted, #94a3b8);">
					<?= count($services) ?> <?= tohtml($is_tr ? "servis aktif" : "services running") ?>
				</span>
			</div>

			<div style="overflow-x: auto; width: 100%;">
				<table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
					<thead>
						<tr style="background: rgba(0, 0, 0, 0.25); border-bottom: 2px solid var(--border-color, #334155);">
							<th style="padding: 12px 14px; color: var(--color-text-muted, #94a3b8); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "Servis Adı & Tanımı" : "Service Name") ?></th>
							<th style="padding: 12px 14px; width: 140px; color: var(--color-text-muted, #94a3b8); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "Bellek (RAM)" : "Memory (RAM)") ?></th>
							<th style="padding: 12px 14px; width: 120px; color: var(--color-text-muted, #94a3b8); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "CPU (%)" : "CPU (%)") ?></th>
							<th style="padding: 12px 14px; width: 140px; text-align: center; color: var(--color-text-muted, #94a3b8); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "Durum" : "State") ?></th>
							<th style="padding: 12px 14px; width: 130px; color: var(--color-text-muted, #94a3b8); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "Çalışma Süresi" : "Uptime") ?></th>
							<th style="padding: 12px 16px; width: 220px; text-align: right; color: var(--color-text-muted, #94a3b8); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "Servis Kontrolleri" : "Actions") ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($services)): ?>
							<tr>
								<td colspan="6" style="padding: 40px; text-align: center; color: var(--color-text-muted, #94a3b8);">
									<?= tohtml($is_tr ? "Sistem servisleri okunamadı." : "No services found.") ?>
								</td>
							</tr>
						<?php else: ?>
							<?php foreach ($services as $s_name => $s_val):
								$s_state = $s_val["STATE"] ?? "running";
								$s_cpu = $s_val["CPU"] ?? "0";
								$s_mem = (int)($s_val["MEM"] ?? 0);
								$s_rtime = $s_val["RTIME"] ?? "0";
								$is_running = ($s_state === "running" || $s_state === "active");
							?>
								<tr class="res-item-row" data-name="<?= tohtml(strtolower($s_name)) ?>" style="border-bottom: 1px solid var(--border-color, #334155);">
									
									<!-- Service Name -->
									<td style="padding: 12px 14px; vertical-align: middle;">
										<div style="display: flex; align-items: center; gap: 8px;">
											<i class="fas fa-gear <?= $is_running ? 'icon-green' : 'icon-red' ?>" style="font-size: 14px;"></i>
											<strong style="color: var(--color-text, #fff); font-size: 13.5px;"><?= tohtml($s_name) ?></strong>
										</div>
									</td>

									<!-- Memory -->
									<td style="padding: 12px 14px; vertical-align: middle; white-space: nowrap; font-weight: 700; color: <?= $s_mem > 500 ? 'var(--icon-color-orange, #f97316)' : 'var(--color-text, #fff)' ?>;">
										<?= $s_mem > 0 ? $s_mem . " MB" : "<1 MB" ?>
									</td>

									<!-- CPU -->
									<td style="padding: 12px 14px; vertical-align: middle; white-space: nowrap; font-size: 12.5px; color: var(--color-text, #fff);">
										<?= tohtml($s_cpu) ?>%
									</td>

									<!-- State Badge -->
									<td style="padding: 12px 14px; vertical-align: middle; text-align: center; white-space: nowrap;">
										<?php if ($is_running): ?>
											<span class="badge badge-success" style="font-size: 11px; padding: 4px 8px;">
												🟢 <?= tohtml($is_tr ? "Aktif / Çalışıyor" : "Running") ?>
											</span>
										<?php else: ?>
											<span class="badge badge-danger" style="font-size: 11px; padding: 4px 8px;">
												🔴 <?= tohtml($is_tr ? "Durduruldu" : "Stopped") ?>
											</span>
										<?php endif; ?>
									</td>

									<!-- Uptime -->
									<td style="padding: 12px 14px; vertical-align: middle; color: var(--color-text-muted, #94a3b8); font-size: 12px; white-space: nowrap;">
										<?= tohtml($s_rtime) ?> <?= tohtml($is_tr ? "dk" : "min") ?>
									</td>

									<!-- Actions (Restart, Stop, Start) -->
									<td style="padding: 12px 16px; vertical-align: middle; text-align: right; white-space: nowrap;">
										<div style="display: inline-flex; gap: 6px; align-items: center; justify-content: flex-end;">
											
											<!-- Reload / Restart -->
											<form method="post" action="/list/resources/" style="display:inline;">
												<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
												<input type="hidden" name="service_action" value="1">
												<input type="hidden" name="srv_name" value="<?= tohtml($s_name) ?>">
												<input type="hidden" name="srv_action" value="restart">
												<button type="submit" class="button button-secondary" style="font-size: 11px; padding: 4px 8px;" title="<?= tohtml($is_tr ? "Servisi Yeniden Başlat / Graceful Reload" : "Restart Service") ?>">
													<i class="fas fa-arrows-rotate"></i> <?= tohtml($is_tr ? "Yeniden Başlat" : "Restart") ?>
												</button>
											</form>

											<!-- Stop / Start -->
											<?php if ($is_running): ?>
												<form method="post" action="/list/resources/" style="display:inline;" onsubmit="return confirm('<?= tohtml($is_tr ? $s_name . " servisini durdurmak istediğinize emin misiniz?" : "Stop service " . $s_name . "?") ?>');">
													<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
													<input type="hidden" name="service_action" value="1">
													<input type="hidden" name="srv_name" value="<?= tohtml($s_name) ?>">
													<input type="hidden" name="srv_action" value="stop">
													<button type="submit" class="button button-secondary" style="font-size: 11px; padding: 4px 8px; color: var(--color-danger, #ef4444);" title="<?= tohtml($is_tr ? "Servisi Durdur" : "Stop Service") ?>">
														<i class="fas fa-stop"></i> <?= tohtml($is_tr ? "Durdur" : "Stop") ?>
													</button>
												</form>
											<?php else: ?>
												<form method="post" action="/list/resources/" style="display:inline;">
													<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
													<input type="hidden" name="service_action" value="1">
													<input type="hidden" name="srv_name" value="<?= tohtml($s_name) ?>">
													<input type="hidden" name="srv_action" value="start">
													<button type="submit" class="button button-secondary" style="font-size: 11px; padding: 4px 8px; color: var(--icon-color-green, #22c55e);" title="<?= tohtml($is_tr ? "Servisi Başlat" : "Start Service") ?>">
														<i class="fas fa-play"></i> <?= tohtml($is_tr ? "Başlat" : "Start") ?>
													</button>
												</form>
											<?php endif; ?>

										</div>
									</td>

								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>

</div>

<script>
function switchResTab(tabId, btn) {
	document.querySelectorAll('.res-tab-btn').forEach(b => {
		b.className = 'button button-secondary res-tab-btn';
	});
	btn.className = 'button button-primary res-tab-btn active';

	document.querySelectorAll('.res-content-section').forEach(s => {
		s.style.display = 'none';
	});
	const targetSection = document.getElementById('section-' + tabId);
	if (targetSection) {
		targetSection.style.display = 'block';
	}
	filterResourceItems();
}

function filterResourceItems() {
	const query = document.getElementById('res-search-input').value.toLowerCase().trim();
	const activeSection = document.querySelector('.res-content-section[style*="display: block"], .res-content-section:not([style*="display: none"])');
	if (!activeSection) return;

	const rows = activeSection.querySelectorAll('.res-item-row');
	rows.forEach(row => {
		const name = row.getAttribute('data-name') || '';
		if (!query || name.includes(query)) {
			row.style.display = '';
		} else {
			row.style.display = 'none';
		}
	});
}
</script>
