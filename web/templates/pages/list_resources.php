<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/web/">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml(__tr("Back to Web", "Web Sitelerine Dön")) ?>
			</a>
			<form method="post" action="/list/resources/" style="display:inline;" onsubmit="const b = this.querySelector('button'); b.disabled=true; b.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> ' + ('<?= (($_SESSION['language'] ?? '') === 'tr') ? "Optimize Ediliyor..." : "Optimizing..." ?>');">
				<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
				<input type="hidden" name="tune_all" value="1">
				<button type="submit" class="button button-primary">
					<i class="fas fa-wand-magic-sparkles"></i> <?= tohtml(__tr("Auto-Tune & Optimize Now", "Şimdi Akıllı Optimize Et")) ?>
				</button>
			</form>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">
	<h1 class="u-text-center u-hide-desktop u-mt20 u-pr30 u-mb20 u-pl30">
		<?= tohtml(__tr("Smart Resource Governance & Priority Matrix", "Akıllı Kaynak Yönetimi & Öncelik Matrisi")) ?>
	</h1>

	<?php show_alert_message($_SESSION); ?>

	<!-- Top Stats Overview Grid -->
	<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 25px;">
		<!-- Stat 1: Total Domains -->
		<div class="card" style="padding: 18px; border-radius: 8px; border: 1px solid var(--border-color, #334155); background: var(--color-background, #fff);">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<div>
					<small class="u-text-muted" style="font-size:11px; text-transform:uppercase; font-weight:bold; letter-spacing:0.5px;">
						<?= tohtml(__tr("Monitored Domains", "İzlenen Siteler")) ?>
					</small>
					<h2 style="margin:5px 0 0 0; font-size:1.6rem; font-weight:bold;">
						<?= (int)($summary["TOTAL_DOMAINS"] ?? 0) ?>
					</h2>
				</div>
				<div style="width:40px; height:40px; border-radius:8px; background:rgba(56, 189, 248, 0.1); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-globe fa-lg" style="color:var(--icon-color-blue, #38bdf8);"></i>
				</div>
			</div>
			<div style="margin-top:10px; font-size:12px; color:var(--color-text-muted);">
				⚡ <strong><?= (int)($summary["AUTO_MANAGED_COUNT"] ?? 0) ?></strong> <?= tohtml(__tr("Auto-Adaptive", "Otomatik Yönetilen")) ?>
			</div>
		</div>

		<!-- Stat 2: Idle & Throttled (Eco-Savings) -->
		<div class="card" style="padding: 18px; border-radius: 8px; border: 1px solid var(--border-color, #334155); background: var(--color-background, #fff);">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<div>
					<small class="u-text-muted" style="font-size:11px; text-transform:uppercase; font-weight:bold; letter-spacing:0.5px;">
						<?= tohtml(__tr("Eco-Idle (Throttled)", "Uykuda / Kısılan Siteler")) ?>
					</small>
					<h2 style="margin:5px 0 0 0; font-size:1.6rem; font-weight:bold; color:var(--icon-color-green, #22c55e);">
						<?= (int)($summary["IDLE_COUNT"] ?? 0) + (int)($summary["THROTTLED_COUNT"] ?? 0) ?>
					</h2>
				</div>
				<div style="width:40px; height:40px; border-radius:8px; background:rgba(34, 197, 94, 0.1); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-leaf fa-lg" style="color:var(--icon-color-green, #22c55e);"></i>
				</div>
			</div>
			<div style="margin-top:10px; font-size:12px; color:var(--color-text-muted);">
				💤 <?= tohtml(__tr("Unused sites deeply throttled to 64M RAM", "Kullanılmayanlar 64M RAM'e kısıldı")) ?>
			</div>
		</div>

		<!-- Stat 3: High-Traffic & Boosted -->
		<div class="card" style="padding: 18px; border-radius: 8px; border: 1px solid var(--border-color, #334155); background: var(--color-background, #fff);">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<div>
					<small class="u-text-muted" style="font-size:11px; text-transform:uppercase; font-weight:bold; letter-spacing:0.5px;">
						<?= tohtml(__tr("High Demand / Boosted", "Yüksek Talep / Güçlendirilen")) ?>
					</small>
					<h2 style="margin:5px 0 0 0; font-size:1.6rem; font-weight:bold; color:var(--icon-color-orange, #f97316);">
						<?= (int)($summary["BOOSTED_COUNT"] ?? 0) + (int)($summary["VIP_COUNT"] ?? 0) ?>
					</h2>
				</div>
				<div style="width:40px; height:40px; border-radius:8px; background:rgba(249, 115, 22, 0.1); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-rocket fa-lg" style="color:var(--icon-color-orange, #f97316);"></i>
				</div>
			</div>
			<div style="margin-top:10px; font-size:12px; color:var(--color-text-muted);">
				🚀 <?= tohtml(__tr("Active spikes receiving burst RAM/CPU", "Yoğun sitelere dinamik kaynak açıldı")) ?>
				<?php if ((int)($summary["BUSY_COUNT"] ?? 0) > 0): ?>
					· 🟣 <?= (int)($summary["BUSY_COUNT"]) ?> <?= tohtml(__tr("busy", "yoğun")) ?>
				<?php endif; ?>
			</div>
		</div>

		<!-- Stat 4: Memory Saved -->
		<div class="card" style="padding: 18px; border-radius: 8px; border: 1px solid var(--border-color, #334155); background: var(--color-background, #fff);">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<div>
					<small class="u-text-muted" style="font-size:11px; text-transform:uppercase; font-weight:bold; letter-spacing:0.5px;">
						<?= tohtml(__tr("Reclaimed RAM Capacity", "Geri Kazanılan Bellek")) ?>
					</small>
					<h2 style="margin:5px 0 0 0; font-size:1.6rem; font-weight:bold; color:var(--icon-color-purple, #a855f7);">
						~<?= (int)($summary["ESTIMATED_SAVED_RAM_MB"] ?? 0) ?> MB
					</h2>
				</div>
				<div style="width:40px; height:40px; border-radius:8px; background:rgba(168, 85, 247, 0.1); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-microchip fa-lg" style="color:var(--icon-color-purple, #a855f7);"></i>
				</div>
			</div>
			<div style="margin-top:10px; font-size:12px; color:var(--color-text-muted);">
				🛡️ <?= tohtml(__tr("Auto-throttling frees RAM for busy apps", "Kısılan siteler aktife alan açtı")) ?>
			</div>
		</div>
	</div>

	<!-- Information Collapsible Box -->
	<details class="box-collapse u-mb20">
		<summary class="box-collapse-header">
			<i class="fas fa-circle-info u-mr10"></i><?= tohtml(__tr("How 5-Tier Smart Governance Works", "5 Kademeli Akıllı Kaynak Yönetimi Nasıl Çalışır?")) ?>
		</summary>
		<div class="box-collapse-content" style="font-size:0.9rem; line-height:1.5;">
			<p class="u-mb10">
				<?= tohtml(__tr("NexviaCP integrates Linux cgroups v2, PSI (Pressure Stall Information) and Nginx traffic log telemetry to automatically scale and throttle per-site resources without manual intervention:", "NexviaCP, Linux cgroups v2, PSI (bellek baskısı) ve Nginx trafik telemetrisini birleştirerek sitelerin kaynaklarını otomatik ölçeklendirir veya kısar:")) ?>
			</p>
			<ul style="list-style:disc; margin-left:20px; line-height:1.6;">
				<li><strong>0 - <?= tohtml(__tr("Auto-Adaptive (Default):", "Akıllı Otomatik (Varsayılan):")) ?></strong> <?= tohtml(__tr("A 0-100 demand score is computed from four live signals: real visitors (unique IPs, bots excluded), dynamic request load (static assets ignored, real response times weighted), deviation from the site's own time-of-day baseline (last 7 days), and memory pressure. Idle sites sleep at 64M RAM / 25% CPU; an active site gets 128M per PHP worker plus a peak ceiling sized for expected concurrent workers. A single visitor generating 99 requests stays \"Active\"; a genuine crowd or a spike well above baseline earns \"Busy\"/\"Boosted\". Hysteresis prevents flapping.", "Son 10 dakikanın dört canlı sinyalinden 0-100 arası bir talep skoru hesaplanır: gerçek ziyaretçiler (botlar hariç eşsiz IP), dinamik istek yükü (statik dosyalar sayılmaz, gerçek yanıt süreleri ağırlıklı), sitenin kendi saat-bazlı geçmiş ortalamasına sapma (son 7 gün) ve bellek baskısı. Boşta olan site 64M RAM / %25 CPU ile uyur; aktif site PHP worker başına 128M ve beklenen eşzamanlı worker sayısına göre tavan alır. Tek ziyaretçinin 99 istek üretmesi \"Aktif\" kalmasına yeter; gerçek kalabalık veya geçmişin çok üzerindeki sıçrama \"Yoğun\"/\"Yüksek Talep\" kazandırır. Histerezis ile durum zıplaması engellenir.")) ?></li>
				<li><strong>1 - <?= tohtml(__tr("Low (Eco-Throttled):", "Düşük (Eco-Kısılmış):")) ?></strong> <?= tohtml(__tr("Test / staging sites. Capped strictly at 64M baseline RAM, 25% CPU quota.", "Test ve geliştirme siteleri. 64M RAM ve %25 CPU ile katı şekilde sınırlandırılır.")) ?></li>
				<li><strong>2 - <?= tohtml(__tr("Normal (Standard):", "Normal (Standart):")) ?></strong> <?= tohtml(__tr("Standard production website. 256M baseline RAM, 50% CPU quota, 100 IO weight.", "Standart web siteleri. 256M normal RAM, %50 CPU ve 100 IO ağırlığı.")) ?></li>
				<li><strong>3 - <?= tohtml(__tr("High Priority:", "Yüksek Öncelik:")) ?></strong> <?= tohtml(__tr("High-traffic stores & portals. 512M baseline RAM, 100% CPU quota (1 core), 300 IO weight.", "Yüksek trafikli portallar. 512M RAM, %100 CPU (1 tam çekirdek) ve 300 IO önceliği.")) ?></li>
				<li><strong>4 - <?= tohtml(__tr("Mission Critical:", "Kritik Öncelik:")) ?></strong> <?= tohtml(__tr("Revenue-generating APIs. 1G baseline RAM, 200% CPU (2 cores), 700 IO weight.", "Kritik API ve e-ticaret sistemleri. 1G RAM, %200 CPU (2 çekirdek) ve 700 IO önceliği.")) ?></li>
				<li><strong>5 - <?= tohtml(__tr("VIP Isolated (Maximum):", "Maksimum VIP İzolasyon:")) ?></strong> <?= tohtml(__tr("Uncapped CPU quota, 2G+ baseline RAM, 1000 Maximum NVMe I/O priority.", "Sınırsız CPU kotası, 2G+ RAM ve 1000 Maksimum NVMe SSD I/O önceliği.")) ?></li>
			</ul>
		</div>
	</details>

	<!-- Search & Filter Controls -->
	<div class="card u-mb20" style="padding:15px; border:1px solid var(--border-color, #334155); border-radius:8px; background:var(--color-background, #fff);">
		<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
			<div style="flex:1; min-width:240px;">
				<input type="text" id="site-search-input" class="form-control" placeholder="<?= tohtml(__tr("Search domain name...", "Alan adı ara...")) ?>" onkeyup="filterDomainRows();" style="width:100%;">
			</div>
			<div style="display:flex; gap:10px; align-items:center;">
				<label class="form-label u-text-bold" style="margin:0; font-size:12px;"><?= tohtml(__tr("Filter Status:", "Durum Filtresi:")) ?></label>
				<select id="status-filter-select" class="form-select" onchange="filterDomainRows();">
					<option value="all"><?= tohtml(__tr("All Sites", "Tüm Siteler")) ?> (<?= count($domains) ?>)</option>
					<option value="idle"><?= tohtml(__tr("💤 Eco-Idle (Throttled)", "💤 Uykuda / Kısılan")) ?></option>
					<option value="active"><?= tohtml(__tr("🔵 Active (Standard)", "🔵 Aktif")) ?></option>
					<option value="busy"><?= tohtml(__tr("🟣 Busy (Steady Traffic)", "🟣 Yoğun")) ?></option>
					<option value="boosted"><?= tohtml(__tr("🟢 High Demand (Boosted)", "🟢 Yüksek Talep")) ?></option>
					<option value="vip"><?= tohtml(__tr("👑 VIP Isolated", "👑 VIP")) ?></option>
				</select>
			</div>
		</div>
	</div>

	<!-- Comparative Sites Matrix Table -->
	<div class="units-table" style="background:var(--color-background, #fff); border-radius:8px; border:1px solid var(--border-color, #334155); overflow-x:auto;">
		<div class="units-table-header" style="background:rgba(0,0,0,0.03); font-weight:bold; min-width:850px;">
			<div class="units-table-cell" style="flex: 2.2; min-width: 220px;"><?= tohtml(__tr("Domain & Stack", "Web Sitesi / Domain")) ?></div>
			<div class="units-table-cell u-text-center" style="flex: 1.8; min-width: 170px;"><?= tohtml(__tr("Priority Tier (0-5)", "Öncelik Kademesi")) ?></div>
			<div class="units-table-cell u-text-center" style="flex: 1.2; min-width: 105px;"><?= tohtml(__tr("Dynamic State", "Dinamik Durum")) ?></div>
			<div class="units-table-cell" style="flex: 1.8; min-width: 140px;"><?= tohtml(__tr("RAM Allocation (High / Peak)", "RAM Tahsisi (High / Tavan)")) ?></div>
			<div class="units-table-cell u-text-center" style="flex: 0.9; min-width: 75px;"><?= tohtml(__tr("CPU Quota", "CPU Kotası")) ?></div>
			<div class="units-table-cell u-text-center" style="flex: 1.4; min-width: 120px;"><?= tohtml(__tr("Traffic & Demand (10m)", "Trafik & Talep (10dk)")) ?></div>
			<div class="units-table-cell u-text-center" style="flex: 1.0; min-width: 85px;"><?= tohtml(__tr("Actions", "İşlem")) ?></div>
		</div>

		<?php if (empty($domains)): ?>
			<div class="units-table-row u-text-center u-p20">
				<p class="u-text-muted"><?= tohtml(__tr("No web domains configured yet.", "Henüz yapılandırılmış web sitesi bulunamadı.")) ?></p>
			</div>
		<?php else: ?>
			<?php foreach ($domains as $dname => $ddata): 
				$prio = (int)($ddata["PRIORITY"] ?? 0);
				$status = $ddata["STATUS"] ?? "active";
				$mem_high = $ddata["MEMORY_HIGH"] ?? "256M";
				$mem_max = $ddata["MEMORY_MAX"] ?? "1G";
				$cpu_q = $ddata["CPU_QUOTA"] ?? "100%";
				$reqs = (int)($ddata["REQ_COUNT_10M"] ?? 0);
				$users = (int)($ddata["ACTIVE_USERS_10M"] ?? 0);
				$score = (int)($ddata["DEMAND_SCORE"] ?? 0);
				$u_owner = $ddata["USER"] ?? $user_plain;
				$is_api_domain = !empty($ddata["APP_TYPE"]);
				$container_mem = (int)($ddata["CONTAINER_MEM_MB"] ?? 0);
				$container_cpu = (float)($ddata["CONTAINER_CPU_PCT"] ?? 0);
				$err_5xx = (int)($ddata["ERR_5XX_10M"] ?? 0);
				$rt_ms = (int)($ddata["AVG_RT_MS"] ?? 0);
				$reasons_list = (array)($ddata["REASONS"] ?? []);
				$reasons_str = !empty($reasons_list) ? implode(", ", $reasons_list) : "";
			?>
				<div class="units-table-row domain-governance-row" data-domain="<?= tohtml(strtolower($dname)) ?>" data-status="<?= tohtml($status) ?>" style="min-width:850px; min-height:64px; padding:8px 0; align-items:center;">
					<!-- Domain Name -->
					<div class="units-table-cell" style="flex: 2.2; min-width: 220px; padding: 6px 12px;">
						<div style="display:flex; align-items:flex-start; gap:10px;">
							<i class="fas fa-globe icon-blue" style="margin-top:3px; font-size:16px; flex-shrink:0;"></i>
							<div style="display:flex; flex-direction:column; gap:4px; min-width:0; line-height:1.3;">
								<a href="http://<?= tohtml($dname) ?>:9080/" target="_blank" class="u-text-bold" style="color:var(--color-text); text-decoration:none; font-size:13px; word-break:break-all;">
									<?= tohtml($dname) ?>
								</a>
								<div style="display:flex; flex-wrap:wrap; align-items:center; gap:5px; font-size:11px; line-height:1.2;">
									<span class="u-text-muted"><?= tohtml($u_owner) ?></span>
									<?php if ($is_api_domain): ?>
										<span class="badge badge-info" style="font-size:9px; padding:1px 5px;" title="<?= tohtml(__tr("API / backend service - never idled, scaled by distress (latency, memory, CPU)", "API / arka plan servisi - asla uyutulmaz; gecikme, bellek, CPU sıkışmasına göre ölçeklenir")) ?>">API</span>
										<?php if ($container_mem > 0): ?><span style="font-size:9px; color:var(--icon-color-blue, #38bdf8); font-weight:600;" title="Container memory">🐳 <?= $container_mem ?> MB</span><?php endif; ?>
										<?php if ($container_cpu > 0): ?><span style="font-size:9px; color:var(--icon-color-orange, #f97316); font-weight:600;" title="Container CPU">⚡ <?= $container_cpu ?>%</span><?php endif; ?>
									<?php endif; ?>
									<?php if ($rt_ms > 0): ?>
										<span style="font-size:9px; color:var(--color-text-muted); font-weight:600;" title="<?= tohtml(__tr("Avg response time", "Ortalama cevap süresi")) ?>">⏱ <?= $rt_ms ?> ms</span>
									<?php endif; ?>
									<?php if ($err_5xx > 0): ?>
										<span class="badge badge-danger" style="font-size:9px; padding:1px 5px;" title="<?= tohtml(__tr("5xx Server Errors in last 10m", "Son 10 dakikadaki 5xx Sunucu Hataları")) ?>">⚠️ <?= $err_5xx ?> err</span>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>

					<!-- Priority Tier (0-5 Interactive Dropdown) -->
					<div class="units-table-cell u-text-center" style="flex: 1.8; min-width: 170px; padding: 6px 8px;">
						<form method="post" action="/list/resources/" style="margin:0;">
							<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
							<input type="hidden" name="change_priority" value="1">
							<input type="hidden" name="prio_user" value="<?= tohtml($u_owner) ?>">
							<input type="hidden" name="prio_domain" value="<?= tohtml($dname) ?>">
							
							<select name="prio_level" class="form-select" onchange="this.form.submit();" style="font-size:11px; padding:4px 6px; font-weight:bold; width:100%; max-width:160px; <?= $prio === 0 ? 'border-color:var(--icon-color-blue, #38bdf8); background:rgba(56,189,248,0.08);' : ($prio >= 4 ? 'border-color:var(--icon-color-orange, #f97316); background:rgba(249,115,22,0.08);' : '') ?>">
								<option value="0" <?= $prio === 0 ? "selected" : "" ?>>⚡ 0 (<?= tohtml(__tr("Auto", "Akıllı Oto")) ?>)</option>
								<option value="1" <?= $prio === 1 ? "selected" : "" ?>>🟢 1 (<?= tohtml(__tr("Low / Eco", "Düşük / Eco")) ?>)</option>
								<option value="2" <?= $prio === 2 ? "selected" : "" ?>>🔵 2 (<?= tohtml(__tr("Normal", "Standart")) ?>)</option>
								<option value="3" <?= $prio === 3 ? "selected" : "" ?>>🟣 3 (<?= tohtml(__tr("High", "Yüksek")) ?>)</option>
								<option value="4" <?= $prio === 4 ? "selected" : "" ?>>🟠 4 (<?= tohtml(__tr("Critical", "Kritik")) ?>)</option>
								<option value="5" <?= $prio === 5 ? "selected" : "" ?>>👑 5 (<?= tohtml(__tr("VIP Max", "Maksimum VIP")) ?>)</option>
							</select>
						</form>
					</div>

					<!-- Dynamic State Badge -->
					<div class="units-table-cell u-text-center" style="flex: 1.2; min-width: 105px; padding: 6px 8px;" title="<?= tohtml($reasons_str ? __tr("Reasons: ", "Nedenler: ") . $reasons_str : "") ?>">
						<?php if ($status === "idle"): ?>
							<span class="badge badge-secondary" style="font-size:11px; padding:4px 8px; background:rgba(100,116,139,0.15); color:var(--color-text-muted); border:1px solid rgba(100,116,139,0.3);">
								💤 <?= tohtml(__tr("Eco-Idle (64M)", "Uykuda (64M)")) ?>
							</span>
						<?php elseif ($status === "busy"): ?>
							<span class="badge" style="font-size:11px; padding:4px 8px; background:rgba(168,85,247,0.15); color:#7c3aed; border:1px solid rgba(168,85,247,0.4);">
								🟣 <?= tohtml(__tr("Busy", "Yoğun")) ?>
							</span>
						<?php elseif ($status === "boosted"): ?>
							<span class="badge badge-warning" style="font-size:11px; padding:4px 8px; background:rgba(249,115,22,0.15); color:#ea580c; border:1px solid rgba(249,115,22,0.4);">
								🚀 <?= tohtml(__tr("Boosted", "Yüksek Talep")) ?>
							</span>
						<?php elseif ($status === "vip"): ?>
							<span class="badge badge-purple" style="font-size:11px; padding:4px 8px; background:rgba(168,85,247,0.15); color:#9333ea; border:1px solid rgba(168,85,247,0.4);">
								👑 VIP
							</span>
						<?php elseif ($status === "throttled"): ?>
							<span class="badge badge-info" style="font-size:11px; padding:4px 8px;">
								🟡 <?= tohtml(__tr("Throttled", "Kısıtlanmış")) ?>
							</span>
						<?php else: ?>
							<span class="badge badge-success" style="font-size:11px; padding:4px 8px; background:rgba(34,197,94,0.12); color:#16a34a; border:1px solid rgba(34,197,94,0.3);">
								🔵 <?= tohtml(__tr("Active", "Aktif")) ?>
							</span>
						<?php endif; ?>
					</div>

					<!-- RAM Allocation -->
					<div class="units-table-cell" style="flex: 1.8; min-width: 140px; padding: 6px 10px;">
						<div style="font-size:12px; font-family:monospace; margin-bottom:4px;">
							<strong style="color:var(--color-text);"><?= tohtml($mem_high) ?></strong> <span class="u-text-muted">/ <?= tohtml($mem_max) ?></span>
						</div>
						<div style="width:100%; height:6px; background:rgba(0,0,0,0.1); border-radius:3px; overflow:hidden;">
							<?php
								if ($status === "vip") $width_pct = 95;
								elseif ($status === "throttled") $width_pct = 15;
								else $width_pct = max(6, $score); // bar = live demand score
							?>
							<div style="width:<?= $width_pct ?>%; height:100%; background:<?= ($score >= 62 || $status === 'boosted') ? 'var(--icon-color-orange, #f97316)' : (($score >= 38 || $status === 'busy') ? '#a855f7' : 'var(--icon-color-blue, #38bdf8)') ?>; border-radius:3px;"></div>
						</div>
					</div>

					<!-- CPU Quota -->
					<div class="units-table-cell u-text-center" style="flex: 0.9; min-width: 75px; padding: 6px 4px;">
						<span class="badge badge-info" style="font-family:monospace; font-size:11px; padding:3px 7px;">
							<?= tohtml($cpu_q) ?>
						</span>
					</div>

					<!-- Traffic & Demand Score (10m) -->
					<div class="units-table-cell u-text-center" style="flex: 1.4; min-width: 120px; padding: 6px 8px;">
						<div style="font-family:monospace; font-size:12px; font-weight:bold; <?= $reqs > 0 ? 'color:var(--color-text);' : 'color:var(--color-text-muted);' ?>">
							👤 <?= $users ?> <span class="u-text-muted">·</span> <?= $reqs ?> <?= tohtml(__tr("req", "istek")) ?>
						</div>
						<div style="margin-top:5px; display:flex; align-items:center; gap:6px;" title="<?= tohtml(__tr("Demand score 0-100 from real visitors, dynamic load, baseline deviation and memory pressure", "Gerçek ziyaretçi, dinamik yük, geçmiş sapma ve bellek baskısından üretilen 0-100 talep skoru")) ?>">
							<div style="flex:1; height:5px; background:rgba(0,0,0,0.1); border-radius:3px; overflow:hidden;">
								<div style="width:<?= max(4, $score) ?>%; height:100%; background:<?= $score >= 62 ? 'var(--icon-color-orange, #f97316)' : ($score >= 38 ? '#a855f7' : 'var(--icon-color-green, #22c55e)') ?>; border-radius:3px;"></div>
							</div>
							<span style="font-size:10px; color:var(--color-text-muted); font-weight:bold; white-space:nowrap;"><?= $score ?>/100</span>
						</div>
					</div>

					<!-- Actions -->
					<div class="units-table-cell u-text-center" style="flex: 1.0; min-width: 85px; padding: 6px 8px;">
						<div style="display:flex; align-items:center; justify-content:center; gap:5px;">
							<form method="post" action="/list/resources/" style="margin:0; display:inline-block;">
								<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
								<input type="hidden" name="tune_single" value="1">
								<input type="hidden" name="tune_user" value="<?= tohtml($u_owner) ?>">
								<input type="hidden" name="tune_domain" value="<?= tohtml($dname) ?>">
								<button type="submit" class="button button-secondary button-small" title="<?= tohtml(__tr("Analyze & Auto-Tune Domain Now", "Bu Domaini Şimdi Analiz Et & Optimize Et")) ?>" style="padding:4px 8px; font-size:11px;">
									<i class="fas fa-bolt" style="color:var(--icon-color-orange, #f97316);"></i>
								</button>
							</form>
							<button type="button" class="button button-secondary button-small" onclick="openCustomTuneModal('<?= tohtml($u_owner) ?>', '<?= tohtml($dname) ?>', '<?= tohtml($mem_high) ?>', '<?= tohtml($mem_max) ?>', '<?= tohtml($cpu_q) ?>');" title="<?= tohtml(__tr("Fine-Tune Limits", "İnce Limit Ayarı")) ?>" style="padding:4px 8px; font-size:11px;">
								<i class="fas fa-sliders"></i>
							</button>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>

<!-- Modal: Fine-Grained Resource Limits Modal -->
<div id="custom-tune-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.65); z-index:9999; justify-content:center; align-items:center;" onclick="if(event.target===this) closeCustomTuneModal();">
	<div class="form-container" style="background:var(--color-background, #fff); max-width:500px; width:90%; border-radius:8px; padding:25px; box-shadow:0 10px 30px rgba(0,0,0,0.5);">
		<h2 class="u-mb15"><i class="fas fa-sliders icon-purple"></i> <?= tohtml(__tr("Fine-Tune Domain Limits", "Özel Limit Ayarı")) ?></h2>
		<p class="u-text-muted u-mb20" style="font-size:0.88rem;">
			<span id="modal-domain-label" class="u-text-bold"></span>
		</p>

		<form method="post" action="/list/resources/">
			<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
			<input type="hidden" name="save_custom_cgroup" value="1">
			<input type="hidden" name="custom_user" id="modal-input-user" value="">
			<input type="hidden" name="custom_domain" id="modal-input-domain" value="">

			<div class="u-mb15">
				<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("Baseline RAM (MemoryHigh)", "Normal Çalışma Sınırı (Baseline)")) ?></label>
				<input type="text" name="custom_high" id="modal-input-high" class="form-control" placeholder="256M" required style="width:100%;">
				<small class="u-text-muted"><?= tohtml(__tr("e.g. 64M, 256M, 512M, 1G", "Örn: 64M, 256M, 512M, 1G")) ?></small>
			</div>

			<div class="u-mb15">
				<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("Peak RAM Ceiling (MemoryMax)", "Maksimum Tavan Sınırı (Peak)")) ?></label>
				<input type="text" name="custom_max" id="modal-input-max" class="form-control" placeholder="1G" required style="width:100%;">
				<small class="u-text-muted"><?= tohtml(__tr("e.g. 256M, 1G, 2G, 4G, unlimited", "Örn: 256M, 1G, 2G, 4G, unlimited")) ?></small>
			</div>

			<div class="u-mb20">
				<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("CPU Quota (%)", "CPU Kotası (%)")) ?></label>
				<input type="text" name="custom_cpu" id="modal-input-cpu" class="form-control" placeholder="100%" required style="width:100%;">
				<small class="u-text-muted"><?= tohtml(__tr("25% = eco, 100% = 1 core, 200% = 2 cores, unlimited", "25% = eco, 100% = 1 çekirdek, 200% = 2 çekirdek, unlimited")) ?></small>
			</div>

			<div style="display:flex; justify-content:flex-end; gap:10px;">
				<button type="button" class="button button-secondary" onclick="closeCustomTuneModal();">
					<?= tohtml(__tr("Cancel", "İptal")) ?>
				</button>
				<button type="submit" class="button button-primary">
					<i class="fas fa-floppy-disk"></i> <?= tohtml(__tr("Save Limits", "Limitleri Kaydet")) ?>
				</button>
			</div>
		</form>
	</div>
</div>

<script>
function openCustomTuneModal(user, domain, high, max, cpu) {
	document.getElementById('modal-input-user').value = user;
	document.getElementById('modal-input-domain').value = domain;
	document.getElementById('modal-domain-label').innerText = domain + ' (' + user + ')';
	document.getElementById('modal-input-high').value = high;
	document.getElementById('modal-input-max').value = max;
	document.getElementById('modal-input-cpu').value = cpu;
	document.getElementById('custom-tune-modal').style.display = 'flex';
}

function closeCustomTuneModal() {
	document.getElementById('custom-tune-modal').style.display = 'none';
}

document.addEventListener('keydown', function(e) {
	if (e.key === 'Escape') {
		closeCustomTuneModal();
	}
});

function filterDomainRows() {
	const searchVal = document.getElementById('site-search-input').value.toLowerCase().trim();
	const statusVal = document.getElementById('status-filter-select').value;
	const rows = document.querySelectorAll('.domain-governance-row');

	rows.forEach(row => {
		const dname = row.getAttribute('data-domain') || '';
		const dstatus = row.getAttribute('data-status') || '';

		const matchesSearch = !searchVal || dname.includes(searchVal);
		const matchesStatus = statusVal === 'all' || dstatus === statusVal || (statusVal === 'idle' && (dstatus === 'idle' || dstatus === 'throttled'));

		if (matchesSearch && matchesStatus) {
			row.style.display = 'flex';
		} else {
			row.style.display = 'none';
		}
	});
}
</script>
