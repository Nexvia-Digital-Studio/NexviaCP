<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/web/">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml(__tr("Back to Web", "Web Sitelerine Dön")) ?>
			</a>
			<?php if (($_SESSION["userContext"] ?? "") === "admin") { ?>
				<form method="post" action="/list/cache/" style="display:inline;" onsubmit="return confirm('<?= tohtml(__tr("Are you sure you want to flush all Redis databases and Nginx microcache files?", "Tüm Redis veritabanlarını ve Nginx önbellek dosyalarını temizlemek istediğinize emin misiniz?")) ?>');">
					<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
					<input type="hidden" name="purge_all_caches" value="1">
					<button type="submit" class="button button-danger">
						<i class="fas fa-fire-flame-curved"></i> <?= tohtml(__tr("Purge All System Caches", "Tüm Sistem Önbelleğini Temizle")) ?>
					</button>
				</form>
				<?php if (!($summary["SLOW_LOG_ENABLED"] ?? false)) { ?>
					<form method="post" action="/list/cache/" style="display:inline;">
						<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
						<input type="hidden" name="enable_slow_log" value="1">
						<button type="submit" class="button button-secondary">
							<i class="fas fa-bolt icon-orange"></i> <?= tohtml(__tr("Enable Slow Query Log", "Yavaş SQL Günlüğünü Aç")) ?>
						</button>
					</form>
				<?php } ?>
			<?php } ?>
			<a class="button button-secondary" href="/list/cache/">
				<i class="fas fa-rotate"></i> <?= tohtml(__tr("Refresh", "Yenile")) ?>
			</a>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">
	<h1 class="u-text-center u-hide-desktop u-mt20 u-pr30 u-mb20 u-pl30">
		<?= tohtml(__tr("Performance, Cache & Database Optimizer", "Performans, Önbellek & Veritabanı Optimize Edici")) ?>
	</h1>

	<?php show_alert_message($_SESSION); ?>

	<!-- Top KPI Stats Overview Grid -->
	<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 15px; margin-bottom: 25px;">
		
		<!-- Stat 1: Redis Memory & Status -->
		<div class="card" style="padding: 18px; border-radius: 8px; border: 1px solid var(--border-color, #334155); background: var(--color-background, #fff);">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<div>
					<small class="u-text-muted" style="font-size:11px; text-transform:uppercase; font-weight:bold; letter-spacing:0.5px;">
						<?= tohtml(__tr("Redis Object Cache", "Redis Bellek Kullanımı")) ?>
					</small>
					<h2 style="margin:5px 0 0 0; font-size:1.6rem; font-weight:bold; color:var(--icon-color-blue, #38bdf8);">
						<?= tohtml($summary["REDIS_USED_MEMORY_HUMAN"] ?? "0B") ?>
					</h2>
				</div>
				<div style="width:40px; height:40px; border-radius:8px; background:rgba(56, 189, 248, 0.1); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-server fa-lg" style="color:var(--icon-color-blue, #38bdf8);"></i>
				</div>
			</div>
			<div style="margin-top:10px; font-size:12px; color:var(--color-text-muted); display:flex; justify-content:space-between;">
				<span><i class="fas fa-chart-line"></i> <?= tohtml(__tr("Peak:", "Zirve:")) ?> <strong><?= tohtml($summary["REDIS_PEAK_MEMORY_HUMAN"] ?? "0B") ?></strong></span>
				<span><i class="fas fa-plug"></i> <?= (int)($summary["REDIS_CONNECTED_CLIENTS"] ?? 0) ?> <?= tohtml(__tr("clients", "bağlantı")) ?></span>
			</div>
		</div>

		<!-- Stat 2: Hit Rate Ratio -->
		<?php
			$hit_rate = (float)($summary["REDIS_HIT_RATE_PCT"] ?? 100);
			$hit_color = $hit_rate >= 90 ? "var(--icon-color-green, #22c55e)" : ($hit_rate >= 70 ? "var(--icon-color-orange, #f97316)" : "var(--color-danger, #ef4444)");
		?>
		<div class="card" style="padding: 18px; border-radius: 8px; border: 1px solid var(--border-color, #334155); background: var(--color-background, #fff);">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<div>
					<small class="u-text-muted" style="font-size:11px; text-transform:uppercase; font-weight:bold; letter-spacing:0.5px;">
						<?= tohtml(__tr("Cache Hit Ratio", "Önbellek İsabet Oranı")) ?>
					</small>
					<h2 style="margin:5px 0 0 0; font-size:1.6rem; font-weight:bold; color:<?= $hit_color ?>;">
						<?= number_format($hit_rate, 1) ?>%
					</h2>
				</div>
				<div style="width:40px; height:40px; border-radius:8px; background:rgba(34, 197, 94, 0.1); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-bullseye fa-lg" style="color:<?= $hit_color ?>;"></i>
				</div>
			</div>
			<div style="margin-top:10px; font-size:12px; color:var(--color-text-muted); display:flex; justify-content:space-between;">
				<span>🎯 <?= number_format((int)($summary["REDIS_KEYSPACE_HITS"] ?? 0)) ?> <?= tohtml(__tr("Hits", "İsabet")) ?></span>
				<span>❌ <?= number_format((int)($summary["REDIS_KEYSPACE_MISSES"] ?? 0)) ?> <?= tohtml(__tr("Misses", "Kaçırma")) ?></span>
			</div>
		</div>

		<!-- Stat 3: FastCGI Microcache -->
		<div class="card" style="padding: 18px; border-radius: 8px; border: 1px solid var(--border-color, #334155); background: var(--color-background, #fff);">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<div>
					<small class="u-text-muted" style="font-size:11px; text-transform:uppercase; font-weight:bold; letter-spacing:0.5px;">
						<?= tohtml(__tr("FastCGI Microcache", "Nginx Microcache Boyutu")) ?>
					</small>
					<h2 style="margin:5px 0 0 0; font-size:1.6rem; font-weight:bold; color:var(--icon-color-purple, #a855f7);">
						<?= tohtml($summary["FASTCGI_TOTAL_SIZE_HUMAN"] ?? "0B") ?>
					</h2>
				</div>
				<div style="width:40px; height:40px; border-radius:8px; background:rgba(168, 85, 247, 0.1); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-bolt fa-lg" style="color:var(--icon-color-purple, #a855f7);"></i>
				</div>
			</div>
			<div style="margin-top:10px; font-size:12px; color:var(--color-text-muted); display:flex; justify-content:space-between;">
				<span>⚡ <strong><?= (int)($summary["FASTCGI_ENABLED_COUNT"] ?? 0) ?></strong> <?= tohtml(__tr("Domains Active", "Sitede Aktif")) ?></span>
				<span>📁 <?= (int)($summary["FASTCGI_TOTAL_FILES"] ?? 0) ?> <?= tohtml(__tr("cached files", "dosya")) ?></span>
			</div>
		</div>

		<!-- Stat 4: SQL Optimizer & Slow Queries -->
		<div class="card" style="padding: 18px; border-radius: 8px; border: 1px solid var(--border-color, #334155); background: var(--color-background, #fff);">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<div>
					<small class="u-text-muted" style="font-size:11px; text-transform:uppercase; font-weight:bold; letter-spacing:0.5px;">
						<?= tohtml(__tr("SQL Slow Query Analyzer", "Yavaş SQL Sorguları")) ?>
					</small>
					<h2 style="margin:5px 0 0 0; font-size:1.6rem; font-weight:bold; color:var(--icon-color-orange, #f97316);">
						<?= (int)($summary["SLOW_QUERIES_COUNT"] ?? 0) ?>
					</h2>
				</div>
				<div style="width:40px; height:40px; border-radius:8px; background:rgba(249, 115, 22, 0.1); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-database fa-lg" style="color:var(--icon-color-orange, #f97316);"></i>
				</div>
			</div>
			<div style="margin-top:10px; font-size:12px; color:var(--color-text-muted); display:flex; justify-content:space-between;">
				<span>
					<?php if ($summary["SLOW_LOG_ENABLED"] ?? false) { ?>
						<span style="color:var(--icon-color-green, #22c55e); font-weight:bold;">● <?= tohtml(__tr("Log Active (>1.0s)", "Günlük Aktif (>1.0s)")) ?></span>
					<?php } else { ?>
						<span style="color:var(--color-text-muted); font-weight:bold;">○ <?= tohtml(__tr("Log Inactive", "Günlük Kapalı")) ?></span>
					<?php } ?>
				</span>
				<span>💡 <?= tohtml(__tr("AI Index Tuning", "AI İndeks Önerisi")) ?></span>
			</div>
		</div>

	</div>

	<!-- SECTION 1: Domain Cache & Redis Allocation Matrix -->
	<div class="card u-mb30" style="padding: 20px; border-radius: 8px; border: 1px solid var(--border-color, #334155); background: var(--color-background, #fff);">
		<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 18px;">
			<h2 style="font-size:1.25rem; font-weight:bold; margin:0; display:flex; align-items:center; gap:8px;">
				<i class="fas fa-layer-group icon-blue"></i>
				<?= tohtml(__tr("Web Domains Cache & Redis Allocation Matrix", "Web Siteleri Önbellek & Redis DB Matrisi")) ?>
				<span class="badge" style="font-size:11px; background:rgba(56, 189, 248, 0.15); color:var(--icon-color-blue, #38bdf8); padding: 3px 8px; border-radius: 12px;">
					<?= count($domains) ?> <?= tohtml(__tr("Domains", "Site")) ?>
				</span>
			</h2>
			<small class="u-text-muted">
				<?= tohtml(__tr("Zero-collision Redis database isolation & FastCGI microcache", "İzole Redis DB tahsisi & FastCGI microcache")) ?>
			</small>
		</div>

		<?php if (empty($domains)) { ?>
			<div class="u-text-center u-p20 u-text-muted">
				<p><?= tohtml(__tr("No web domains found.", "Henüz kayıtlı web sitesi bulunamadı.")) ?></p>
			</div>
		<?php } else { ?>
			<div style="overflow-x:auto;">
				<table class="units-table" style="width:100%; border-collapse:collapse;">
					<thead>
						<tr class="units-table-row units-table-row--header" style="text-align:left; border-bottom: 2px solid var(--border-color, #334155); font-size:12px; color:var(--color-text-muted); text-transform:uppercase;">
							<th style="padding: 10px 12px;"><?= tohtml(__tr("Domain & User", "Domain & Kullanıcı")) ?></th>
							<th style="padding: 10px 12px;"><?= tohtml(__tr("Dedicated Redis DB", "Ayrılmış Redis DB")) ?></th>
							<th style="padding: 10px 12px;"><?= tohtml(__tr(".env Credentials", ".env Enjeksiyonu")) ?></th>
							<th style="padding: 10px 12px;"><?= tohtml(__tr("FastCGI Cache", "FastCGI Cache")) ?></th>
							<th style="padding: 10px 12px;"><?= tohtml(__tr("Cache Size", "Önbellek Boyutu")) ?></th>
							<th style="padding: 10px 12px; text-align:right;"><?= tohtml(__tr("Actions", "İşlemler")) ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($domains as $dname => $dinfo) { 
							$duser = $dinfo["USER"] ?? $user;
							$rdb = $dinfo["REDIS_DB"];
							$fc_enabled = (($dinfo["FASTCGI_CACHE"] ?? "no") === "yes");
							$fc_duration = $dinfo["FASTCGI_DURATION"] ?? "2m";
							$c_size = $dinfo["CACHE_SIZE_HUMAN"] ?? "0 B";
						?>
							<tr class="units-table-row" style="border-bottom: 1px solid var(--border-color, #334155); font-size:13px;">
								
								<!-- Domain Name -->
								<td style="padding: 12px;">
									<div style="font-weight:bold; font-size:14px;">
										<a href="/edit/web/?domain=<?= urlencode($dname) ?>" style="color:var(--color-text, inherit); text-decoration:none;">
											<?= tohtml($dname) ?>
										</a>
									</div>
									<div style="font-size:11px; color:var(--color-text-muted); margin-top:2px;">
										<i class="fas fa-user"></i> <?= tohtml($duser) ?> &bull; 
										<span style="text-transform:uppercase;"><?= tohtml($dinfo["APP_TYPE"] ?? "php") ?></span>
									</div>
								</td>

								<!-- Redis DB -->
								<td style="padding: 12px;">
									<?php if ($rdb !== null) { ?>
										<span style="display:inline-flex; align-items:center; gap:5px; background:rgba(34, 197, 94, 0.15); color:var(--icon-color-green, #22c55e); font-weight:bold; padding: 4px 10px; border-radius: 6px; font-size:12px;">
											<i class="fas fa-database"></i> DB <?= (int)$rdb ?>
										</span>
									<?php } else { ?>
										<span style="display:inline-flex; align-items:center; gap:5px; background:rgba(148, 163, 184, 0.15); color:var(--color-text-muted); padding: 4px 10px; border-radius: 6px; font-size:12px;">
											<?= tohtml(__tr("Not Assigned", "Atanmadı")) ?>
										</span>
									<?php } ?>
								</td>

								<!-- .env Status -->
								<td style="padding: 12px;">
									<?php if ($dinfo["ENV_HAS_REDIS"] ?? false) { ?>
										<span style="color:var(--icon-color-green, #22c55e); font-size:12px; font-weight:bold; display:inline-flex; align-items:center; gap:4px;">
											<i class="fas fa-shield-halved"></i> REDIS_DB (chmod 600)
										</span>
									<?php } elseif ($dinfo["ENV_EXISTS"] ?? false) { ?>
										<span style="color:var(--icon-color-blue, #38bdf8); font-size:12px;">
											<i class="fas fa-file-code"></i> .env <?= tohtml(__tr("ready", "mevcut")) ?>
										</span>
									<?php } else { ?>
										<span style="color:var(--color-text-muted); font-size:12px;">
											<?= tohtml(__tr("No .env file", ".env yok")) ?>
										</span>
									<?php } ?>
								</td>

								<!-- FastCGI Microcache -->
								<td style="padding: 12px;">
									<?php if ($fc_enabled) { ?>
										<span style="display:inline-flex; align-items:center; gap:5px; background:rgba(168, 85, 247, 0.15); color:var(--icon-color-purple, #a855f7); font-weight:bold; padding: 4px 10px; border-radius: 6px; font-size:12px;">
											<i class="fas fa-bolt"></i> <?= tohtml(__tr("Active", "Aktif")) ?> (<?= tohtml($fc_duration) ?>)
										</span>
									<?php } else { ?>
										<span style="color:var(--color-text-muted); font-size:12px;">
											<?= tohtml(__tr("Disabled", "Kapalı")) ?>
										</span>
									<?php } ?>
								</td>

								<!-- Cache Size on Disk -->
								<td style="padding: 12px; font-weight:bold;">
									<?= tohtml($c_size) ?>
								</td>

								<!-- Actions -->
								<td style="padding: 12px; text-align:right;">
									<div style="display:inline-flex; gap:6px; align-items:center;">
										
										<!-- 1-Click Purge -->
										<form method="post" action="/list/cache/" style="display:inline;" onsubmit="return confirm('<?= tohtml(__tr("Purge cache for", "Önbelleği temizle:")) ?> <?= tohtml($dname) ?>?');">
											<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
											<input type="hidden" name="purge_domain_cache" value="1">
											<input type="hidden" name="domain_user" value="<?= tohtml($duser) ?>">
											<input type="hidden" name="domain_name" value="<?= tohtml($dname) ?>">
											<input type="hidden" name="cache_type" value="all">
											<button type="submit" class="button button-secondary button-small" title="<?= tohtml(__tr("1-Click Purge Cache", "1-Tıkla Önbellek Temizle")) ?>" style="padding: 4px 8px; font-size:12px;">
												<i class="fas fa-trash-can icon-red"></i> <?= tohtml(__tr("Purge", "Temizle")) ?>
											</button>
										</form>

										<!-- Assign / Change Redis DB Modal Trigger -->
										<button type="button" class="button button-secondary button-small" style="padding: 4px 8px; font-size:12px;" onclick="openRedisModal('<?= tohtml($duser) ?>', '<?= tohtml($dname) ?>', '<?= $rdb !== null ? (int)$rdb : 'auto' ?>');" title="<?= tohtml(__tr("Assign Redis Database (0-15)", "Redis DB Ata (0-15)")) ?>">
											<i class="fas fa-database icon-blue"></i> <?= tohtml(__tr("Redis DB", "Redis DB")) ?>
										</button>

										<!-- FastCGI Toggle -->
										<form method="post" action="/list/cache/" style="display:inline;">
											<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
											<input type="hidden" name="toggle_fastcgi" value="1">
											<input type="hidden" name="domain_user" value="<?= tohtml($duser) ?>">
											<input type="hidden" name="domain_name" value="<?= tohtml($dname) ?>">
											<input type="hidden" name="current_status" value="<?= $fc_enabled ? "yes" : "no" ?>">
											<input type="hidden" name="duration" value="10m">
											<button type="submit" class="button button-secondary button-small" title="<?= $fc_enabled ? tohtml(__tr("Disable FastCGI Cache", "FastCGI Kapat")) : tohtml(__tr("Enable FastCGI Cache", "FastCGI Aç")) ?>" style="padding: 4px 8px; font-size:12px;">
												<i class="fas fa-power-off <?= $fc_enabled ? "icon-green" : "icon-dim" ?>"></i>
											</button>
										</form>

									</div>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
		<?php } ?>
	</div>

	<!-- SECTION 2: Redis Isolated Databases Grid (DB0 - DB15) -->
	<div class="card u-mb30" style="padding: 20px; border-radius: 8px; border: 1px solid var(--border-color, #334155); background: var(--color-background, #fff);">
		<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 18px;">
			<h2 style="font-size:1.25rem; font-weight:bold; margin:0; display:flex; align-items:center; gap:8px;">
				<i class="fas fa-cubes icon-green"></i>
				<?= tohtml(__tr("Redis 16 Isolated Databases Grid (DB 0 – DB 15)", "Redis 16 İzole Veritabanı Haritası (DB 0 – DB 15)")) ?>
			</h2>
			<small class="u-text-muted">
				<?= tohtml(__tr("Dedicated keyspaces per domain prevent cross-tenant key pollution", "Her siteye özel keyspace ile tam anahtar izolasyonu")) ?>
			</small>
		</div>

		<div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px;">
			<?php for ($i = 0; $i < 16; $i++) { 
				$db_key = "db" . $i;
				$db_item = $redis_dbs[$db_key] ?? [
					"db_index" => $i,
					"keys_count" => 0,
					"assigned_domain" => null,
					"assigned_user" => null
				];
				$is_assigned = !empty($db_item["assigned_domain"]);
				$keys_num = (int)($db_item["keys_count"] ?? 0);
			?>
				<div style="border: 1px solid <?= $is_assigned ? "var(--icon-color-green, #22c55e)" : "var(--border-color, #334155)" ?>; border-radius: 6px; padding: 10px; background: <?= $is_assigned ? "rgba(34, 197, 94, 0.04)" : "var(--color-background-accent, rgba(0,0,0,0.02))" ?>; text-align:center;">
					<div style="font-weight:bold; font-size:13px; color:<?= $is_assigned ? "var(--icon-color-green, #22c55e)" : "var(--color-text-muted)" ?>;">
						DB <?= $i ?>
					</div>
					<div style="font-size:11px; margin: 4px 0; font-weight:bold; color:var(--color-text);">
						<?= $keys_num ?> <?= tohtml(__tr("keys", "anahtar")) ?>
					</div>
					<div style="font-size:10px; color:var(--color-text-muted); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= tohtml($db_item["assigned_domain"] ?? "") ?>">
						<?= $is_assigned ? tohtml($db_item["assigned_domain"]) : tohtml(__tr("Available", "Boş")) ?>
					</div>
					<?php if ($keys_num > 0) { ?>
						<form method="post" action="/list/cache/" style="margin-top:6px;" onsubmit="return confirm('<?= tohtml(__tr("Flush all keys in DB", "DB")) ?> <?= $i ?> <?= tohtml(__tr("?", "için tüm veriler silinsin mi?")) ?>');">
							<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
							<input type="hidden" name="flush_single_db" value="1">
							<input type="hidden" name="db_index" value="<?= $i ?>">
							<button type="submit" class="button button-danger button-small" style="font-size:9px; padding: 2px 6px; width:100%;" title="<?= tohtml(__tr("Flush DB keys", "DB Temizle")) ?>">
								<?= tohtml(__tr("Flush", "Sıfırla")) ?>
							</button>
						</form>
					<?php } ?>
				</div>
			<?php } ?>
		</div>
	</div>

	<!-- SECTION 3: AI-Powered MariaDB/MySQL Slow Query Analyzer -->
	<div class="card" style="padding: 20px; border-radius: 8px; border: 1px solid var(--border-color, #334155); background: var(--color-background, #fff);">
		<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 18px;">
			<div>
				<h2 style="font-size:1.25rem; font-weight:bold; margin:0; display:flex; align-items:center; gap:8px;">
					<i class="fas fa-wand-magic-sparkles icon-orange"></i>
					<?= tohtml(__tr("AI-Powered Slow MySQL Query Analyzer & Index Recommender", "AI Destekli Yavaş SQL Analizörü & İndeks Öneri Motoru")) ?>
				</h2>
				<small class="u-text-muted" style="display:block; margin-top:3px;">
					<?= tohtml(__tr("Identifies high full-table scans, filesorts, and lock contention to recommend optimal composite indexes.", "Tam tablo taramalarını, filesort darboğazlarını ve kilitlenmeleri tespit ederek akıllı kompozit indeks üretir.")) ?>
				</small>
			</div>
			<div>
				<span class="badge" style="background:rgba(249, 115, 22, 0.15); color:var(--icon-color-orange, #f97316); font-weight:bold; padding: 5px 12px; border-radius: 12px; font-size:12px;">
					<?= (int)($slow_queries["queries_count"] ?? 0) ?> <?= tohtml(__tr("Slow Patterns", "Yavaş Kalıp")) ?>
				</span>
			</div>
		</div>

		<?php 
			$sq_list = $slow_queries["queries"] ?? [];
			if (empty($sq_list)) { 
		?>
			<div style="padding: 25px; text-align:center; background:rgba(56, 189, 248, 0.05); border-radius: 8px; border: 1px dashed var(--border-color, #334155);">
				<i class="fas fa-circle-check fa-2x icon-green u-mb10"></i>
				<h3 style="margin:0 0 5px 0; font-size:1rem; font-weight:bold;">
					<?= tohtml(__tr("Database Queries Running Efficiently", "Tüm Veritabanı Sorguları Hızlı Çalışıyor")) ?>
				</h3>
				<p class="u-text-muted" style="font-size:13px; max-width:600px; margin: 0 auto;">
					<?= tohtml(__tr("No slow queries (>1.0s) have been detected in MariaDB/MySQL. When bottlenecks occur, the optimizer will automatically provide one-click copyable composite INDEX fixes here.", "MariaDB/MySQL üzerinde 1.0 saniyeyi aşan yavaş sorgu tespit edilmedi. Darboğaz oluştuğunda optimizasyon motoru burada otomatik 'CREATE INDEX' önerileri sunacaktır.")) ?>
				</p>
				<?php if (!($summary["SLOW_LOG_ENABLED"] ?? false)) { ?>
					<div style="margin-top:15px;">
						<form method="post" action="/list/cache/" style="display:inline;">
							<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
							<input type="hidden" name="enable_slow_log" value="1">
							<button type="submit" class="button button-primary button-small">
								<i class="fas fa-bolt"></i> <?= tohtml(__tr("Activate Slow Query Logging Now", "Yavaş Sorgu Günlüğünü Aktif Et")) ?>
							</button>
						</form>
					</div>
				<?php } ?>
			</div>
		<?php } else { ?>
			<div style="display:flex; flex-direction:column; gap:15px;">
				<?php foreach ($sq_list as $idx => $q) { 
					$rec = $q["recommendation"] ?? [];
					$suggested_sql = $rec["suggested_sql"] ?? "";
					$ai_reason = $rec["ai_explanation"] ?? "";
				?>
					<div style="border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 15px; background: var(--color-background-accent, rgba(0,0,0,0.02));">
						<div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:10px;">
							<div>
								<span style="font-weight:bold; font-size:14px; color:var(--icon-color-blue, #38bdf8);">
									#<?= ($idx + 1) ?> [<?= tohtml($q["database"] ?? "mysql") ?>] <?= tohtml($q["table"] ?? "") ?>
								</span>
								<span style="font-size:11px; margin-left:8px; color:var(--color-text-muted);">
									<?= (int)$q["count"] ?>x <?= tohtml(__tr("executions", "çalıştırıldı")) ?>
								</span>
							</div>
							<div style="display:flex; gap:8px; font-size:12px;">
								<span class="badge" style="background:rgba(239, 68, 68, 0.15); color:var(--color-danger, #ef4444); padding:3px 8px; border-radius:4px; font-weight:bold;">
									<?= tohtml(__tr("Avg:", "Ort:")) ?> <?= number_format((float)($q["avg_time_sec"] ?? 0), 3) ?>s
								</span>
								<span class="badge" style="background:rgba(249, 115, 22, 0.15); color:var(--icon-color-orange, #f97316); padding:3px 8px; border-radius:4px;">
									<?= tohtml(__tr("Examined:", "Taranan:")) ?> <?= number_format((float)($q["avg_rows_examined"] ?? 0)) ?> <?= tohtml(__tr("rows", "satır")) ?>
								</span>
								<span class="badge" style="background:rgba(34, 197, 94, 0.15); color:var(--icon-color-green, #22c55e); padding:3px 8px; border-radius:4px;">
									<?= tohtml(__tr("Sent:", "Dönen:")) ?> <?= number_format((float)($q["avg_rows_sent"] ?? 0)) ?>
								</span>
							</div>
						</div>

						<!-- Sample Query -->
						<div style="margin: 10px 0; background: #0f172a; color: #e2e8f0; padding: 10px; border-radius: 6px; font-family: monospace; font-size: 12px; overflow-x: auto;">
							<?= tohtml($q["sample_query"] ?? "") ?>
						</div>

						<!-- AI Rationale -->
						<?php if (!empty($ai_reason)) { ?>
							<div style="margin-bottom: 10px; font-size: 12px; color: var(--color-text); background: rgba(249, 115, 22, 0.08); padding: 8px 12px; border-radius: 6px; border-left: 3px solid var(--icon-color-orange, #f97316);">
								💡 <strong><?= tohtml(__tr("AI Root Cause Analysis:", "AI Kök Neden Analizi:")) ?></strong> <?= tohtml($ai_reason) ?>
							</div>
						<?php } ?>

						<!-- Suggested SQL Index Fix -->
						<?php if (!empty($suggested_sql)) { ?>
							<div style="display:flex; align-items:center; justify-content:space-between; background: rgba(34, 197, 94, 0.08); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 6px; padding: 8px 12px; gap:10px;">
								<div style="font-family: monospace; font-size: 12px; font-weight: bold; color: var(--icon-color-green, #22c55e); overflow-x:auto;">
									<?= tohtml($suggested_sql) ?>
								</div>
								<button type="button" class="button button-secondary button-small" onclick="navigator.clipboard.writeText('<?= addslashes($suggested_sql) ?>'); alert('<?= tohtml(__tr("SQL index command copied to clipboard!", "SQL indeks komutu panoya kopyalandı!")) ?>');" style="white-space:nowrap; padding: 4px 10px; font-size:11px;">
									<i class="fas fa-copy"></i> <?= tohtml(__tr("Copy SQL", "Kopyala")) ?>
								</button>
							</div>
						<?php } ?>

					</div>
				<?php } ?>
			</div>
		<?php } ?>
	</div>

</div>

<!-- Assign Redis DB Modal Dialog -->
<div id="redisModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.6); align-items:center; justify-content:center;">
	<div class="card" style="background:var(--color-background, #fff); border-radius:8px; width:90%; max-width:480px; padding:24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3); border:1px solid var(--border-color, #334155);">
		<h3 style="margin:0 0 15px 0; font-size:1.2rem; font-weight:bold; display:flex; align-items:center; gap:8px;">
			<i class="fas fa-database icon-blue"></i>
			<?= tohtml(__tr("Assign Isolated Redis Database", "İzole Redis Veritabanı Tahsisi")) ?>
		</h3>
		<p class="u-text-muted" style="font-size:13px; margin-bottom:15px;">
			<?= tohtml(__tr("Assign a dedicated Redis DB (0-15) to domain. Credentials (REDIS_HOST, REDIS_PORT, REDIS_DB) will be automatically injected into .env with chmod 600.", "Bu site için özel bir Redis DB (0-15) belirleyin. Bağlantı bilgileri .env dosyasına chmod 600 ile otomatik yazılacaktır.")) ?>
		</p>
		<form method="post" action="/list/cache/">
			<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
			<input type="hidden" name="assign_redis_db" value="1">
			<input type="hidden" id="modal_domain_user" name="domain_user" value="">
			<input type="hidden" id="modal_domain_name" name="domain_name" value="">

			<div class="u-mb15">
				<label class="form-label u-mb5 u-text-bold" style="font-size:12px;"><?= tohtml(__tr("Domain:", "Domain:")) ?></label>
				<input type="text" id="modal_display_domain" class="form-control" readonly style="width:100%; opacity:0.8; font-weight:bold;">
			</div>

			<div class="u-mb20">
				<label class="form-label u-mb5 u-text-bold" style="font-size:12px;"><?= tohtml(__tr("Redis Database (0 - 15):", "Redis Veritabanı (0 - 15):")) ?></label>
				<select id="modal_redis_db_select" name="redis_db" class="form-select" style="width:100%; padding:8px; border-radius:6px; border:1px solid var(--border-color, #334155); background:var(--color-background, #fff); color:var(--color-text, inherit);">
					<option value="auto"><?= tohtml(__tr("⚡ Auto-Assign (Next Available DB 0-15)", "⚡ Otomatik Tahsis (İlk Boş DB 0-15)")) ?></option>
					<?php for ($d = 0; $d <= 15; $d++) { ?>
						<option value="<?= $d ?>">DB <?= $d ?></option>
					<?php } ?>
				</select>
			</div>

			<div style="display:flex; justify-content:flex-end; gap:10px;">
				<button type="button" class="button button-secondary" onclick="closeRedisModal();">
					<?= tohtml(__tr("Cancel", "İptal")) ?>
				</button>
				<button type="submit" class="button button-primary">
					<i class="fas fa-check"></i> <?= tohtml(__tr("Save & Inject .env", "Kaydet & .env'ye Yaz")) ?>
				</button>
			</div>
		</form>
	</div>
</div>

<script>
function openRedisModal(user, domain, currentDb) {
	document.getElementById('modal_domain_user').value = user;
	document.getElementById('modal_domain_name').value = domain;
	document.getElementById('modal_display_domain').value = domain + " (" + user + ")";
	var select = document.getElementById('modal_redis_db_select');
	select.value = currentDb;
	document.getElementById('redisModal').style.display = 'flex';
}

function closeRedisModal() {
	document.getElementById('redisModal').style.display = 'none';
}

window.onclick = function(event) {
	var modal = document.getElementById('redisModal');
	if (event.target == modal) {
		closeRedisModal();
	}
}
</script>
