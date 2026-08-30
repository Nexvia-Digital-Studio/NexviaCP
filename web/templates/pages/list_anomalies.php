<?php
$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');
$summary = $anomaly_data["summary"] ?? [];
$anomalies = $anomaly_data["anomalies"] ?? [];
$domains = $anomaly_data["domains"] ?? [];
$metrics = $anomaly_data["metrics"] ?? [];
?>

<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/server/">
				<i class="fas fa-arrow-left icon-blue"></i><?= $is_tr ? "Geri" : "Back" ?>
			</a>
					<button type="button" class="button button-primary" onclick="collectMetricsNow();" id="btn-collect">
						<i class="fas fa-satellite-dish"></i> <?= $is_tr ? "Şimdi Tara" : "Scan Now" ?>
					</button>
			<a href="/list/rrd/" class="button button-secondary">
				<i class="fas fa-chart-area icon-green"></i> <?= $is_tr ? "Performans Grafikleri" : "Performance Graphs" ?>
			</a>
		</div>
		<div class="toolbar-right">
			<!-- Period Filter -->
			<a class="toolbar-link<?= $period_filter === '24h' ? ' selected' : '' ?>" href="?period=24h<?= $domain_filter !== 'all' ? '&domain=' . urlencode($domain_filter) : '' ?>"><?= $is_tr ? "Son 24 Saat" : "Last 24h" ?></a>
			<a class="toolbar-link<?= $period_filter === '7d' ? ' selected' : '' ?>" href="?period=7d<?= $domain_filter !== 'all' ? '&domain=' . urlencode($domain_filter) : '' ?>"><?= $is_tr ? "Son 7 Gün" : "Last 7d" ?></a>
			<a class="toolbar-link<?= $period_filter === '30d' ? ' selected' : '' ?>" href="?period=30d<?= $domain_filter !== 'all' ? '&domain=' . urlencode($domain_filter) : '' ?>"><?= $is_tr ? "Son 30 Gün" : "Last 30d" ?></a>
			<a class="toolbar-link<?= $period_filter === '90d' ? ' selected' : '' ?>" href="?period=90d<?= $domain_filter !== 'all' ? '&domain=' . urlencode($domain_filter) : '' ?>"><?= $is_tr ? "Son 90 Gün" : "Last 90d" ?></a>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container" style="max-width: 1400px; padding: 15px 20px;">

	<!-- Summary Hero Cards -->
	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 25px;">

		<!-- Total Anomalies -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8); letter-spacing: 0.5px;">
						<?= $is_tr ? "Toplam Anomali" : "Total Anomalies" ?>
					</span>
					<h3 style="margin: 4px 0 0; font-size: 1.8rem; font-weight: bold; color: var(--color-text, #f8fafc);">
						<?= (int)($summary["total"] ?? 0) ?>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(168,85,247,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-triangle-exclamation" style="font-size: 16px; color: #a855f7;"></i>
				</div>
			</div>
		</div>

		<!-- Critical -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid <?= ($summary["critical"] ?? 0) > 0 ? '#ef4444' : 'var(--border-color, #334155)' ?>; border-radius: 8px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8);">
						<?= $is_tr ? "Kritik Seviye" : "Critical" ?>
					</span>
					<h3 style="margin: 4px 0 0; font-size: 1.8rem; font-weight: bold; color: <?= ($summary["critical"] ?? 0) > 0 ? '#ef4444' : 'var(--color-text, #f8fafc)' ?>;">
						<?= (int)($summary["critical"] ?? 0) ?>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(239,68,68,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-circle-xmark" style="font-size: 16px; color: #ef4444;"></i>
				</div>
			</div>
		</div>

		<!-- Warning -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8);">
						<?= $is_tr ? "Uyarı Seviye" : "Warning" ?>
					</span>
					<h3 style="margin: 4px 0 0; font-size: 1.8rem; font-weight: bold; color: #eab308;">
						<?= (int)($summary["warning"] ?? 0) ?>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(234,179,8,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-exclamation-triangle" style="font-size: 16px; color: #eab308;"></i>
				</div>
			</div>
		</div>

		<!-- Unresolved -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8);">
						<?= $is_tr ? "Çözülmemiş" : "Unresolved" ?>
					</span>
					<h3 style="margin: 4px 0 0; font-size: 1.8rem; font-weight: bold; color: #f97316;">
						<?= (int)($summary["unresolved"] ?? 0) ?>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(249,115,22,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-clock-rotate-left" style="font-size: 16px; color: #f97316;"></i>
				</div>
			</div>
		</div>

		<!-- Most Affected Domain -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8);">
						<?= $is_tr ? "En Çok Etkilenen" : "Most Affected" ?>
					</span>
					<h3 style="margin: 4px 0 0; font-size: 1rem; font-weight: bold; color: var(--color-text, #f8fafc); word-break: break-all;">
						<?= htmlspecialchars($summary["most_affected"] ?? ($is_tr ? "Yok" : "None")) ?>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(56,189,248,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-bullseye" style="font-size: 16px; color: #38bdf8;"></i>
				</div>
			</div>
		</div>

	</div>

	<!-- Domain Filter Bar -->
	<?php if (!empty($domains)): ?>
	<div style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; border-bottom: 1px solid var(--border-color, #334155); padding-bottom: 12px;">
		<span style="font-size: 12px; font-weight: 600; color: var(--color-text-muted, #94a3b8); margin-right: 4px;">
			<i class="fas fa-filter"></i> <?= $is_tr ? "Domain:" : "Domain:" ?>
		</span>
		<a href="?period=<?= urlencode($period_filter) ?>" class="button button-small <?= $domain_filter === 'all' ? 'button-primary' : 'button-secondary' ?>" style="font-size: 11px;">
			<?= $is_tr ? "Tümü" : "All" ?> (<?= count($domains) ?>)
		</a>
		<?php foreach ($domains as $d): ?>
		<a href="?domain=<?= urlencode($d) ?>&period=<?= urlencode($period_filter) ?>" class="button button-small <?= $domain_filter === $d ? 'button-primary' : 'button-secondary' ?>" style="font-size: 11px;">
			<?= htmlspecialchars($d) ?>
		</a>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<?php if (empty($anomalies)): ?>
	<!-- Empty State -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 40px; text-align: center; margin-bottom: 20px;">
		<i class="fas fa-shield-halved" style="font-size: 48px; color: #22c55e; margin-bottom: 15px;"></i>
		<h3 style="color: var(--color-text, #f8fafc); margin: 0 0 8px;">
			<?= $is_tr ? "Anomali Tespit Edilmedi" : "No Anomalies Detected" ?>
		</h3>
		<p style="color: var(--color-text-muted, #94a3b8); font-size: 13px; margin: 0 0 15px;">
			<?= $is_tr
				? "Seçili dönemde tüm domainler normal davranış parametreleri içinde. Sistem saat başı otomatik tarama yapar veya \"Şimdi Tara\" butonuyla anlık kontrol başlatabilirsiniz."
				: "All domains are operating within normal behavioral parameters for the selected period." ?>
		</p>
		<button type="button" class="button button-primary" onclick="collectMetricsNow();">
			<i class="fas fa-satellite-dish"></i> <?= $is_tr ? "Manuel Tarama Başlat" : "Run Manual Scan" ?>
		</button>
	</div>
	<?php else: ?>

	<!-- Anomaly Timeline Table -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; overflow: hidden; margin-bottom: 20px;">
		<div style="padding: 15px 20px; border-bottom: 1px solid var(--border-color, #334155); display: flex; justify-content: space-between; align-items: center;">
			<h3 style="margin: 0; font-size: 1rem; color: var(--color-text, #f8fafc);">
				<i class="fas fa-timeline icon-purple"></i> <?= $is_tr ? "Anomali Zaman Çizelgesi" : "Anomaly Timeline" ?>
			</h3>
			<span style="font-size: 11px; color: var(--color-text-muted, #94a3b8);">
				<?= count($anomalies) ?> <?= $is_tr ? "kayıt" : "entries" ?>
			</span>
		</div>

		<div style="overflow-x: auto;">
			<table style="width: 100%; border-collapse: collapse; font-size: 13px;">
				<thead>
					<tr style="background: rgba(0,0,0,0.2);">
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Şiddet" : "Severity" ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Zaman" : "Time" ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Domain" : "Domain" ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Metrik" : "Metric" ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Yön" : "Direction" ?></th>
						<th style="padding: 10px 15px; text-align: right; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Beklenen" : "Expected" ?></th>
						<th style="padding: 10px 15px; text-align: right; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Gerçekleşen" : "Actual" ?></th>
						<th style="padding: 10px 15px; text-align: center; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;">Z-Score</th>
						<th style="padding: 10px 15px; text-align: center; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Durum" : "Status" ?></th>
						<th style="padding: 10px 15px; text-align: center; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Detay" : "Details" ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($anomalies as $idx => $a): 
						$sev_color = ($a["severity"] ?? "") === "critical" ? "#ef4444" : "#eab308";
						$sev_bg = ($a["severity"] ?? "") === "critical" ? "rgba(239,68,68,0.1)" : "rgba(234,179,8,0.1)";
						$sev_label = $is_tr ? ($a["severity_tr"] ?? "UYARI") : strtoupper($a["severity"] ?? "warning");
						$dir_icon = ($a["direction"] ?? "") === "spike" ? "fa-arrow-trend-up" : "fa-arrow-trend-down";
						$dir_color = ($a["direction"] ?? "") === "spike" ? "#ef4444" : "#38bdf8";
						$dir_label = $is_tr ? ($a["direction_tr"] ?? "") : ucfirst($a["direction"] ?? "");
						$is_resolved = !empty($a["resolved"]);

						// Format timestamp
						$ts = $a["timestamp"] ?? "";
						$ts_display = "";
						if ($ts) {
							try {
								$dt = new DateTime($ts);
								$ts_display = $dt->format("d/m H:i");
							} catch (Throwable $e) {
								$ts_display = htmlspecialchars($ts);
							}
						}
					?>
					<tr style="border-bottom: 1px solid var(--border-color, #334155); background: <?= $sev_bg ?>;">
						<td style="padding: 10px 15px; white-space: nowrap;">
							<span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; background: <?= $sev_color ?>22; color: <?= $sev_color ?>; border: 1px solid <?= $sev_color ?>44;">
								<i class="fas <?= ($a["severity"] ?? "") === "critical" ? "fa-circle-xmark" : "fa-exclamation-triangle" ?>" style="font-size: 9px;"></i>
								<?= htmlspecialchars($sev_label) ?>
							</span>
						</td>
						<td style="padding: 10px 15px; color: var(--color-text, #f8fafc); white-space: nowrap; font-family: monospace; font-size: 12px;">
							<?= htmlspecialchars($ts_display) ?>
						</td>
						<td style="padding: 10px 15px; color: var(--color-text, #f8fafc); font-weight: 600; white-space: nowrap;">
							<?= htmlspecialchars($a["domain"] ?? "") ?>
						</td>
						<td style="padding: 10px 15px; color: var(--color-text-muted, #cbd5e1); white-space: nowrap;">
							<?= htmlspecialchars($is_tr ? ($a["label_tr"] ?? "") : ($a["label_en"] ?? "")) ?>
						</td>
						<td style="padding: 10px 15px; white-space: nowrap;">
							<span style="color: <?= $dir_color ?>; font-weight: 600; font-size: 12px;">
								<i class="fas <?= $dir_icon ?>" style="font-size: 11px;"></i>
								<?= htmlspecialchars($dir_label) ?>
							</span>
						</td>
						<td style="padding: 10px 15px; text-align: right; color: var(--color-text-muted, #94a3b8); font-family: monospace; font-size: 12px;">
							<?= number_format((float)($a["baseline_mean"] ?? 0), 1) ?>
						</td>
						<td style="padding: 10px 15px; text-align: right; color: <?= $sev_color ?>; font-weight: bold; font-family: monospace; font-size: 12px;">
							<?= number_format((float)($a["current_value"] ?? 0), 1) ?>
						</td>
						<td style="padding: 10px 15px; text-align: center; font-family: monospace; font-size: 12px; font-weight: bold; color: <?= $sev_color ?>;">
							<?= number_format((float)($a["z_score"] ?? 0), 1) ?>
						</td>
						<td style="padding: 10px 15px; text-align: center; white-space: nowrap;">
							<?php if ($is_resolved): ?>
							<span style="color: #22c55e; font-size: 11px; font-weight: 600;">
								<i class="fas fa-circle-check"></i> <?= $is_tr ? "Çözüldü" : "Resolved" ?>
								<?php if (!empty($a["duration_minutes"])): ?>
								<br><small style="color: var(--color-text-muted, #94a3b8);"><?= (int)$a["duration_minutes"] ?>dk</small>
								<?php endif; ?>
							</span>
							<?php else: ?>
							<span style="color: #f97316; font-size: 11px; font-weight: 600;">
								<i class="fas fa-clock"></i> <?= $is_tr ? "Aktif" : "Active" ?>
							</span>
							<?php endif; ?>
						</td>
						<td style="padding: 10px 15px; text-align: center;">
							<button type="button" class="button button-secondary button-small" onclick="showAnomalyDetail(<?= $idx ?>);" style="font-size: 10px; padding: 4px 8px;">
								<i class="fas fa-magnifying-glass"></i>
							</button>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>

	<?php endif; ?>

	<!-- Metric Context Cards (for domains with metrics) -->
	<?php if (!empty($metrics)): ?>
	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(580px, 1fr)); gap: 20px; margin-bottom: 20px;">
		<?php foreach ($metrics as $domain_name => $domain_metrics): 
			if (empty($domain_metrics)) continue;
			$last_m = end($domain_metrics);
			$avg_requests = array_sum(array_column($domain_metrics, 'requests')) / max(1, count($domain_metrics));
		?>
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid var(--border-color, #334155); padding-bottom: 10px;">
				<div style="display: flex; align-items: center; gap: 10px;">
					<div style="width: 32px; height: 32px; border-radius: 6px; background: rgba(56,189,248,0.1); display: flex; align-items: center; justify-content: center;">
						<i class="fas fa-chart-line" style="font-size: 14px; color: #38bdf8;"></i>
					</div>
					<div>
						<h3 style="margin: 0; font-size: 1rem; font-weight: bold; color: var(--color-text, #f8fafc);">
							<?= htmlspecialchars($domain_name) ?>
						</h3>
						<small style="font-size: 11px; color: var(--color-text-muted, #94a3b8);">
							<?= $is_tr ? "Son " . count($domain_metrics) . " saatlik veri" : "Last " . count($domain_metrics) . " hours of data" ?>
						</small>
					</div>
				</div>
			</div>

			<!-- Mini Metric Tiles -->
			<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;">
				<div style="background: rgba(0,0,0,0.15); border-radius: 6px; padding: 10px; text-align: center;">
					<div style="font-size: 10px; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; font-weight: 600;">
						<?= $is_tr ? "Son İstekler" : "Last Requests" ?>
					</div>
					<div style="font-size: 1.2rem; font-weight: bold; color: var(--color-text, #f8fafc); margin-top: 3px;">
						<?= number_format((int)($last_m["requests"] ?? 0)) ?>
					</div>
					<div style="font-size: 10px; color: var(--color-text-muted, #94a3b8);">
						<?= $is_tr ? "ort:" : "avg:" ?> <?= number_format($avg_requests, 0) ?>
					</div>
				</div>
				<div style="background: rgba(0,0,0,0.15); border-radius: 6px; padding: 10px; text-align: center;">
					<div style="font-size: 10px; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; font-weight: 600;">
						<?= $is_tr ? "Hata Oranı" : "Error Rate" ?>
					</div>
					<div style="font-size: 1.2rem; font-weight: bold; color: <?= ($last_m["error_rate"] ?? 0) > 5 ? '#ef4444' : '#22c55e' ?>; margin-top: 3px;">
						%<?= number_format((float)($last_m["error_rate"] ?? 0), 1) ?>
					</div>
				</div>
				<div style="background: rgba(0,0,0,0.15); border-radius: 6px; padding: 10px; text-align: center;">
					<div style="font-size: 10px; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; font-weight: 600;">
						<?= $is_tr ? "Bant Genişliği" : "Bandwidth" ?>
					</div>
					<div style="font-size: 1.2rem; font-weight: bold; color: var(--color-text, #f8fafc); margin-top: 3px;">
						<?php 
						$bw = (float)($last_m["bandwidth_kb"] ?? 0);
						echo $bw >= 1024 ? number_format($bw / 1024, 1) . " MB" : number_format($bw, 0) . " KB";
						?>
					</div>
				</div>
				<div style="background: rgba(0,0,0,0.15); border-radius: 6px; padding: 10px; text-align: center;">
					<div style="font-size: 10px; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; font-weight: 600;">
						<?= $is_tr ? "Benzersiz IP" : "Unique IPs" ?>
					</div>
					<div style="font-size: 1.2rem; font-weight: bold; color: var(--color-text, #f8fafc); margin-top: 3px;">
						<?= number_format((int)($last_m["unique_ips"] ?? 0)) ?>
					</div>
				</div>
			</div>

			<!-- Mini Sparkline: Request trend -->
			<div style="margin-top: 12px;">
				<div style="font-size: 10px; color: var(--color-text-muted, #94a3b8); margin-bottom: 5px; font-weight: 600;">
					<?= $is_tr ? "İstek Trendi (Son " . min(24, count($domain_metrics)) . " saat)" : "Request Trend (Last " . min(24, count($domain_metrics)) . "h)" ?>
				</div>
				<div style="display: flex; align-items: flex-end; gap: 2px; height: 40px;">
					<?php 
					$recent = array_slice($domain_metrics, -24);
					$max_req = max(1, max(array_column($recent, 'requests')));
					foreach ($recent as $rm):
						$h = max(2, round(($rm["requests"] / $max_req) * 36));
						$bar_color = ($rm["error_rate"] ?? 0) > 10 ? '#ef4444' : (($rm["error_rate"] ?? 0) > 5 ? '#eab308' : '#38bdf8');
					?>
					<div style="flex: 1; height: <?= $h ?>px; background: <?= $bar_color ?>; border-radius: 2px 2px 0 0; min-width: 3px;" title="<?= htmlspecialchars(($rm["timestamp"] ?? "") . ": " . ($rm["requests"] ?? 0) . " req") ?>"></div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

</div>

<!-- Anomaly Detail Modal -->
<div id="anomalyDetailModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;">
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 10px; width: 90%; max-width: 700px; max-height: 85vh; overflow-y: auto; padding: 25px; box-shadow: 0 20px 60px rgba(0,0,0,0.5);">
		<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
			<h3 style="margin: 0; color: var(--color-text, #f8fafc);">
				<i class="fas fa-magnifying-glass-chart icon-purple"></i> <?= $is_tr ? "Anomali Detayı" : "Anomaly Detail" ?>
			</h3>
			<button type="button" onclick="closeAnomalyDetail();" style="background: none; border: none; color: var(--color-text-muted, #94a3b8); cursor: pointer; font-size: 18px;">
				<i class="fas fa-xmark"></i>
			</button>
		</div>
		<div id="anomalyDetailContent"></div>
	</div>
</div>

<script>
// Store anomaly data for detail modal
var anomalyData = <?= json_encode($anomalies, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;

function esc(s) {
	return String(s == null ? '' : s).replace(/[&<>"']/g, function(c) {
		return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
	});
}

function showAnomalyDetail(idx) {
	var a = anomalyData[idx];
	if (!a) return;

	var isTr = <?= $is_tr ? 'true' : 'false' ?>;
	var sevColor = a.severity === 'critical' ? '#ef4444' : '#eab308';
	var sevLabel = isTr ? a.severity_tr : a.severity.toUpperCase();
	var dirLabel = isTr ? a.direction_tr : (a.direction === 'spike' ? 'Spike' : 'Drop');
	var metricLabel = isTr ? a.label_tr : a.label_en;

	// Context timeline: prefer enriched before/after points, fall back to raw context_before
	var contextHtml = '';
	var hasTimeline = a.timeline && (a.timeline.before || a.timeline.after);
	if (hasTimeline) {
		var before = a.timeline.before || [];
		var after = a.timeline.after || [];
		var allVals = before.map(function(p) { return p.v; })
			.concat([a.current_value])
			.concat(after.map(function(p) { return p.v; }));
		var maxV = Math.max.apply(null, allVals.map(Math.abs)) || 1;
		contextHtml = '<div style="display: flex; gap: 8px; align-items: flex-end; height: 50px; margin: 10px 0;">';
		before.forEach(function(p) {
			var h = Math.max(4, Math.round((Math.abs(p.v) / maxV) * 44));
			contextHtml += '<div style="flex:1; height:' + h + 'px; background:#38bdf8; border-radius:3px 3px 0 0;" title="' + esc(p.t) + ': ' + Number(p.v).toFixed(1) + '"></div>';
		});
		var curH = Math.max(4, Math.round((Math.abs(a.current_value) / maxV) * 44));
		contextHtml += '<div style="flex:1; height:' + curH + 'px; background:' + sevColor + '; border-radius:3px 3px 0 0; border: 2px solid #fff;" title="' + (isTr ? 'Anomali Anı' : 'Anomaly') + ': ' + a.current_value + '"></div>';
		after.forEach(function(p) {
			var h = Math.max(4, Math.round((Math.abs(p.v) / maxV) * 44));
			contextHtml += '<div style="flex:1; height:' + h + 'px; background:#22c55e; border-radius:3px 3px 0 0;" title="' + esc(p.t) + ': ' + Number(p.v).toFixed(1) + '"></div>';
		});
		contextHtml += '</div>';
		contextHtml += '<div style="display:flex; justify-content:space-between; font-size:10px; color:var(--color-text-muted,#94a3b8);">' +
			'<span>' + (isTr ? 'Öncesi' : 'Before') + '</span>' +
			'<span style="color:' + sevColor + '; font-weight:bold;">' + (isTr ? 'Anomali Anı' : 'Anomaly') + '</span>' +
			'<span style="color:#22c55e;">' + (isTr ? 'Sonrası' : 'After') + '</span></div>';
	} else if (a.context_before && a.context_before.length > 0) {
		contextHtml = '<div style="display: flex; gap: 8px; align-items: flex-end; height: 50px; margin: 10px 0;">';
		var allVals = a.context_before.concat([a.current_value]);
		var maxV = Math.max.apply(null, allVals) || 1;
		a.context_before.forEach(function(v) {
			var h = Math.max(4, Math.round((v / maxV) * 44));
			contextHtml += '<div style="flex:1; height:'+h+'px; background:#38bdf8; border-radius:3px 3px 0 0;" title="'+(isTr?'Önceki':'Before')+': '+v.toFixed(1)+'"></div>';
		});
		var curH = Math.max(4, Math.round((a.current_value / maxV) * 44));
		contextHtml += '<div style="flex:1; height:'+curH+'px; background:'+sevColor+'; border-radius:3px 3px 0 0; border: 2px solid #fff;" title="'+(isTr?'Anomali':'Anomaly')+': '+a.current_value+'"></div>';
		contextHtml += '</div>';
		contextHtml += '<div style="display:flex; justify-content:space-between; font-size:10px; color:var(--color-text-muted,#94a3b8);"><span>'+(isTr?'Öncesi':'Before')+'</span><span style="color:'+sevColor+'; font-weight:bold;">'+(isTr?'Anomali Anı':'Anomaly')+'</span></div>';
	}

	// Incident meta: duration and recurrence info
	var metaHtml = '';
	if (a.resolved && a.duration_minutes != null) {
		var durH = Math.floor(a.duration_minutes / 60);
		var durM = a.duration_minutes % 60;
		var durStr = durH > 0 ? durH + 's ' + durM + 'dk' : durM + 'dk';
		metaHtml += '<span style="color:#22c55e;"><i class="fas fa-circle-check"></i> ' + (isTr ? 'Süre' : 'Duration') + ': <b>' + durStr + '</b></span>';
	} else if (!a.resolved) {
		metaHtml += '<span style="color:#f97316;"><i class="fas fa-clock"></i> ' + (isTr ? 'Devam ediyor' : 'Ongoing') + '</span>';
	}
	if (a.occurrences > 1) {
		metaHtml += '<span style="color:var(--color-text-muted,#94a3b8);">' + (isTr ? 'Tekrar' : 'Occurrences') + ': <b>' + a.occurrences + '×</b></span>';
	}
	if (a.last_seen && a.last_seen !== a.timestamp) {
		metaHtml += '<span style="color:var(--color-text-muted,#94a3b8);">' + (isTr ? 'Son görülme' : 'Last seen') + ': ' + esc(a.last_seen) + '</span>';
	}
	var metaBlock = metaHtml ? '<div style="display:flex; gap:15px; flex-wrap:wrap; font-size:12px; margin-bottom:15px; padding:10px 12px; background:rgba(0,0,0,0.15); border-radius:6px;">' + metaHtml + '</div>' : '';

	// Status snapshot
	var statusHtml = '';
	if (a.status_snapshot) {
		statusHtml = '<div style="display:grid; grid-template-columns: repeat(4,1fr); gap:6px; margin-top:10px;">';
		Object.keys(a.status_snapshot).forEach(function(code) {
			var count = a.status_snapshot[code];
			var c = code >= 500 ? '#ef4444' : (code >= 400 ? '#eab308' : '#22c55e');
			statusHtml += '<div style="background:rgba(0,0,0,0.2); border-radius:4px; padding:6px; text-align:center;"><div style="font-size:10px; color:var(--color-text-muted,#94a3b8);">HTTP '+esc(code)+'</div><div style="font-size:14px; font-weight:bold; color:'+c+';">'+esc(count)+'</div></div>';
		});
		statusHtml += '</div>';
	}

	var html = ''
		+ '<div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:20px;">'
		+ '<div style="background:rgba(0,0,0,0.15); border-radius:6px; padding:12px;">'
		+ '<div style="font-size:10px; color:var(--color-text-muted,#94a3b8); text-transform:uppercase; font-weight:600;">'+(isTr?'Domain':'Domain')+'</div>'
		+ '<div style="font-size:14px; font-weight:bold; color:var(--color-text,#f8fafc); margin-top:3px;">'+esc(a.domain)+'</div></div>'
		+ '<div style="background:rgba(0,0,0,0.15); border-radius:6px; padding:12px;">'
		+ '<div style="font-size:10px; color:var(--color-text-muted,#94a3b8); text-transform:uppercase; font-weight:600;">'+(isTr?'Zaman':'Time')+'</div>'
		+ '<div style="font-size:14px; font-weight:bold; color:var(--color-text,#f8fafc); margin-top:3px;">'+esc(a.timestamp)+'</div></div>'
		+ '<div style="background:rgba(0,0,0,0.15); border-radius:6px; padding:12px;">'
		+ '<div style="font-size:10px; color:var(--color-text-muted,#94a3b8); text-transform:uppercase; font-weight:600;">'+(isTr?'Metrik':'Metric')+'</div>'
		+ '<div style="font-size:14px; font-weight:bold; color:var(--color-text,#f8fafc); margin-top:3px;">'+esc(metricLabel)+'</div></div>'
		+ '<div style="background:rgba(0,0,0,0.15); border-radius:6px; padding:12px;">'
		+ '<div style="font-size:10px; color:var(--color-text-muted,#94a3b8); text-transform:uppercase; font-weight:600;">'+(isTr?'Şiddet & Yön':'Severity & Direction')+'</div>'
		+ '<div style="font-size:14px; font-weight:bold; color:'+sevColor+'; margin-top:3px;">'+esc(sevLabel)+' — '+esc(dirLabel)+'</div></div>'
		+ '</div>'

		// Comparison
		+ '<div style="background: rgba(0,0,0,0.15); border-left: 3px solid '+sevColor+'; border-radius: 0 6px 6px 0; padding: 12px 15px; margin-bottom:15px;">'
		+ '<div style="display:flex; justify-content:space-between; align-items:center;">'
		+ '<div><div style="font-size:10px; color:var(--color-text-muted,#94a3b8);">'+(isTr?'7 Günlük Ortalama (Beklenen)':'7-Day Average (Expected)')+'</div>'
		+ '<div style="font-size:1.4rem; font-weight:bold; color:var(--color-text,#f8fafc);">'+a.baseline_mean.toFixed(1)+'</div></div>'
		+ '<div style="font-size:24px; color:var(--color-text-muted,#94a3b8);"><i class="fas fa-arrow-right"></i></div>'
		+ '<div style="text-align:right;"><div style="font-size:10px; color:var(--color-text-muted,#94a3b8);">'+(isTr?'Gerçekleşen (Anomali)':'Actual (Anomaly)')+'</div>'
		+ '<div style="font-size:1.4rem; font-weight:bold; color:'+sevColor+';">'+a.current_value.toFixed(1)+'</div></div>'
		+ '</div>'
		+ '<div style="text-align:center; margin-top:8px; font-size:12px; color:var(--color-text-muted,#94a3b8);">'
		+ 'Z-Score: <b style="color:'+sevColor+';">'+a.z_score.toFixed(1)+'</b> · Std Dev: '+a.baseline_std.toFixed(2)
		+ '</div></div>'

		// Incident duration / recurrence
		+ metaBlock

		// Context before & after
		+ '<div style="margin-bottom:15px;"><div style="font-size:12px; font-weight:600; color:var(--color-text,#f8fafc); margin-bottom:5px;"><i class="fas fa-clock-rotate-left icon-blue"></i> '+(isTr?'Anomali Öncesi & Sonrası Bağlam':'Pre & Post-Anomaly Context')+'</div>'
		+ contextHtml + '</div>'

		// Status codes
		+ '<div><div style="font-size:12px; font-weight:600; color:var(--color-text,#f8fafc); margin-bottom:5px;"><i class="fas fa-signal icon-green"></i> '+(isTr?'O Andaki HTTP Durum Kodları':'HTTP Status Snapshot')+'</div>'
		+ statusHtml + '</div>';

	document.getElementById('anomalyDetailContent').innerHTML = html;
	document.getElementById('anomalyDetailModal').style.display = 'flex';
}

function closeAnomalyDetail() {
	document.getElementById('anomalyDetailModal').style.display = 'none';
}

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
	if (e.key === 'Escape') closeAnomalyDetail();
});

// Close modal on backdrop click
document.getElementById('anomalyDetailModal').addEventListener('click', function(e) {
	if (e.target === this) closeAnomalyDetail();
});

// Collect metrics now
function collectMetricsNow() {
	var btn = document.getElementById('btn-collect');
	var origText = btn.innerHTML;
	btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?= $is_tr ? "Taranıyor..." : "Scanning..." ?>';
	btn.disabled = true;

	fetch('/list/anomalies/', {
		method: 'POST',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body: 'action=collect_metrics&token=<?= htmlspecialchars($_SESSION["token"] ?? "") ?>'
	})
	.then(function(r) { return r.json(); })
	.then(function(data) {
		btn.innerHTML = '<i class="fas fa-circle-check"></i> <?= $is_tr ? "Tamamlandı!" : "Done!" ?>';
		setTimeout(function() { location.reload(); }, 1000);
	})
	.catch(function() {
		btn.innerHTML = origText;
		btn.disabled = false;
	});
}
</script>
