<?php
$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');
$report = $maintenance_data["report"] ?? null;
$history = $maintenance_data["history"] ?? [];
$steps = $report["steps"] ?? [];

$ok_count = 0;
$failed_count = 0;
$skipped_count = 0;
foreach ($steps as $s) {
	$status = $s["status"] ?? "";
	if ($status === "ok") {
		$ok_count++;
	} elseif ($status === "failed") {
		$failed_count++;
	} elseif ($status === "skipped") {
		$skipped_count++;
	}
}

// Pretty timestamps
$fmt_date = function (string $ts): string {
	try {
		return (new DateTime($ts))->format("d.m.Y H:i:s");
	} catch (Throwable $e) {
		return $ts;
	}
};
$last_run_display = $is_tr ? "Hiç çalıştırılmadı" : "Never run";
if (!empty($report["finished"])) {
	$last_run_display = htmlspecialchars($fmt_date((string)$report["finished"])) . " UTC";
}
?>

<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/server/">
				<i class="fas fa-arrow-left icon-blue"></i><?= $is_tr ? "Geri" : "Back" ?>
			</a>
			<button type="submit" form="run-maintenance-form" class="button button-primary" id="btn-run-maintenance">
				<i class="fas fa-screwdriver-wrench"></i> <?= $is_tr ? "Bakımı Çalıştır" : "Run Maintenance" ?>
			</button>
			<a href="/list/anomalies/" class="button button-secondary">
				<i class="fas fa-satellite-dish icon-purple"></i> <?= $is_tr ? "Anomali İzleme" : "Anomaly Monitor" ?>
			</a>
		</div>
		<div class="toolbar-right">
			<span class="toolbar-link">
				<i class="fas fa-clock-rotate-left icon-blue"></i>
				<?= $last_run_display ?>
			</span>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container" style="max-width: 1400px; padding: 15px 20px;">

	<!-- Run form -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 20px; margin-bottom: 25px;">
		<form method="post" action="/list/maintenance/" id="run-maintenance-form">
			<input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION["token"] ?? "") ?>">
			<input type="hidden" name="action" value="run">
			<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
				<div>
					<h3 style="margin: 0 0 6px; font-size: 1rem; color: var(--color-text, #f8fafc);">
						<i class="fas fa-screwdriver-wrench icon-blue"></i>
						<?= $is_tr ? "Sistem Bakımı Çalıştır" : "Run System Maintenance" ?>
					</h3>
					<p style="margin: 0; color: var(--color-text-muted, #94a3b8); font-size: 13px;">
						<?= $is_tr
							? "nginx önbellek temizliği, restic budama, journal vakumlama, logrotate, yetim dosya temizliği ve disk kullanımı raporu tek seferde çalıştırılır."
							: "Flushes nginx cache, prunes restic backups, vacuums the journal, force-runs logrotate, cleans orphan files and reports disk usage in one pass." ?>
					</p>
				</div>
				<div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
					<label style="display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: var(--color-text, #f8fafc); cursor: pointer;">
						<input type="checkbox" name="security_updates" value="yes" style="width: 16px; height: 16px; cursor: pointer;">
						<?= $is_tr ? "Güvenlik güncellemelerini de kur (apt)" : "Also install security updates (apt)" ?>
					</label>
					<button type="submit" class="button button-primary">
						<i class="fas fa-play"></i> <?= $is_tr ? "Çalıştır" : "Run" ?>
					</button>
				</div>
			</div>
			<p style="margin: 12px 0 0; font-size: 12px; color: #eab308;">
				<i class="fas fa-hourglass-half"></i>
				<?= $is_tr
					? "Not: Çalıştırma, özellikle güvenlik güncellemeleri seçiliyse birkaç dakika sürebilir; sayfa tamamlanana kadar yüklenmeye devam edecektir."
					: "Note: A run may take several minutes, especially with security updates enabled; this page keeps loading until it finishes." ?>
			</p>
		</form>
	</div>

	<?php if ($run_error !== null): ?>
	<!-- Run failure banner -->
	<div style="background: rgba(239,68,68,0.1); border: 1px solid #ef4444; border-radius: 8px; padding: 14px 18px; margin-bottom: 25px; color: #ef4444; font-size: 13px;">
		<i class="fas fa-circle-xmark"></i>
		<?= $is_tr
			? "Bakım komutu başarısız oldu (çıkış kodu: " . (int)$run_error . "). Rapor yazılamamış olabilir; CLI ile v-run-sys-maintenance json komutunu çalıştırıp çıktıyı kontrol edin."
			: "Maintenance command failed (exit code: " . (int)$run_error . "). The report may not have been written; run v-run-sys-maintenance json from the CLI and check the output." ?>
	</div>
	<?php elseif (!empty($run_result)): ?>
	<!-- Run success banner -->
	<div style="background: rgba(34,197,94,0.1); border: 1px solid #22c55e; border-radius: 8px; padding: 14px 18px; margin-bottom: 25px; color: #22c55e; font-size: 13px;">
		<i class="fas fa-circle-check"></i>
		<?= $is_tr ? "Bakım çalıştırması tamamlandı (" : "Maintenance run completed (" ?>
		<?= (int)$ok_count ?> <?= $is_tr ? "başarılı" : "ok" ?>,
		<?= (int)$failed_count ?> <?= $is_tr ? "başarısız" : "failed" ?>,
		<?= (int)$skipped_count ?> <?= $is_tr ? "atlandı" : "skipped" ?>)
	</div>
	<?php endif; ?>

	<?php if (empty($report)): ?>
	<!-- Empty State -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 40px; text-align: center; margin-bottom: 20px;">
		<i class="fas fa-screwdriver-wrench" style="font-size: 48px; color: #38bdf8; margin-bottom: 15px;"></i>
		<h3 style="color: var(--color-text, #f8fafc); margin: 0 0 8px;">
			<?= $is_tr ? "Henüz Bakım Raporu Yok" : "No Maintenance Report Yet" ?>
		</h3>
		<p style="color: var(--color-text-muted, #94a3b8); font-size: 13px; margin: 0;">
			<?= $is_tr
				? "\"Bakımı Çalıştır\" ile ilk çalıştırmayı başlatabilir veya sunucuda v-run-sys-maintenance komutunu çalıştırabilirsiniz."
				: "Use \"Run Maintenance\" to start the first run, or execute v-run-sys-maintenance on the server." ?>
		</p>
	</div>
	<?php else: ?>

	<!-- Summary Cards -->
	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">

		<!-- Last Run -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8); letter-spacing: 0.5px;">
						<?= $is_tr ? "Son Çalıştırma" : "Last Run" ?>
					</span>
					<h3 style="margin: 4px 0 0; font-size: 1rem; font-weight: bold; color: var(--color-text, #f8fafc);">
						<?= $last_run_display ?>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(56,189,248,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-clock-rotate-left" style="font-size: 16px; color: #38bdf8;"></i>
				</div>
			</div>
		</div>

		<!-- Duration -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8);">
						<?= $is_tr ? "Süre" : "Duration" ?>
					</span>
					<h3 style="margin: 4px 0 0; font-size: 1.8rem; font-weight: bold; color: var(--color-text, #f8fafc);">
						<?= (int)($report["duration_s"] ?? 0) ?>s
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(168,85,247,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-stopwatch" style="font-size: 16px; color: #a855f7;"></i>
				</div>
			</div>
		</div>

		<!-- OK -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8);">
						<?= $is_tr ? "Başarılı" : "OK" ?>
					</span>
					<h3 style="margin: 4px 0 0; font-size: 1.8rem; font-weight: bold; color: #22c55e;">
						<?= (int)$ok_count ?>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(34,197,94,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-circle-check" style="font-size: 16px; color: #22c55e;"></i>
				</div>
			</div>
		</div>

		<!-- Failed -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid <?= $failed_count > 0 ? '#ef4444' : 'var(--border-color, #334155)' ?>; border-radius: 8px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8);">
						<?= $is_tr ? "Başarısız" : "Failed" ?>
					</span>
					<h3 style="margin: 4px 0 0; font-size: 1.8rem; font-weight: bold; color: <?= $failed_count > 0 ? '#ef4444' : 'var(--color-text, #f8fafc)' ?>;">
						<?= (int)$failed_count ?>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(239,68,68,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-circle-xmark" style="font-size: 16px; color: #ef4444;"></i>
				</div>
			</div>
		</div>

		<!-- Skipped -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8);">
						<?= $is_tr ? "Atlandı" : "Skipped" ?>
					</span>
					<h3 style="margin: 4px 0 0; font-size: 1.8rem; font-weight: bold; color: #94a3b8;">
						<?= (int)$skipped_count ?>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(148,163,184,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-forward" style="font-size: 16px; color: #94a3b8;"></i>
				</div>
			</div>
		</div>

	</div>

	<!-- Steps Table -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; overflow: hidden; margin-bottom: 20px;">
		<div style="padding: 15px 20px; border-bottom: 1px solid var(--border-color, #334155); display: flex; justify-content: space-between; align-items: center;">
			<h3 style="margin: 0; font-size: 1rem; color: var(--color-text, #f8fafc);">
				<i class="fas fa-list-check icon-green"></i> <?= $is_tr ? "Bakım Adımları" : "Maintenance Steps" ?>
			</h3>
			<span style="font-size: 11px; color: var(--color-text-muted, #94a3b8);">
				<?= htmlspecialchars((string)($report["started"] ?? "")) ?> → <?= htmlspecialchars((string)($report["finished"] ?? "")) ?>
			</span>
		</div>

		<div style="overflow-x: auto;">
			<table style="width: 100%; border-collapse: collapse; font-size: 13px;">
				<thead>
					<tr style="background: rgba(0,0,0,0.2);">
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Adım" : "Step" ?></th>
						<th style="padding: 10px 15px; text-align: center; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Durum" : "Status" ?></th>
						<th style="padding: 10px 15px; text-align: right; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Süre" : "Time" ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= $is_tr ? "Detay" : "Detail" ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$step_labels = [
						"disk_snapshot_before" => $is_tr ? "Disk anlık görüntüsü (önce)" : "Disk snapshot (before)",
						"nginx_cache_flush" => $is_tr ? "nginx önbellek temizliği" : "nginx cache flush",
						"restic_prune" => $is_tr ? "restic yedek budama" : "restic backup prune",
						"journal_vacuum" => $is_tr ? "journal vakumlama" : "journal vacuum",
						"logrotate_force" => $is_tr ? "logrotate zorla çalıştır" : "logrotate force-run",
						"orphan_cleanup" => $is_tr ? "yetim dosya temizliği" : "orphan cleanup",
						"security_updates" => $is_tr ? "güvenlik güncellemeleri" : "security updates",
						"disk_snapshot_after" => $is_tr ? "Disk anlık görüntüsü (sonra)" : "Disk snapshot (after)",
					];
					foreach ($steps as $s):
						$s_status = $s["status"] ?? "skipped";
						if ($s_status === "ok") {
							$st_color = "#22c55e";
							$st_icon = "fa-circle-check";
							$st_label = $is_tr ? "Başarılı" : "OK";
						} elseif ($s_status === "failed") {
							$st_color = "#ef4444";
							$st_icon = "fa-circle-xmark";
							$st_label = $is_tr ? "Başarısız" : "Failed";
						} else {
							$st_color = "#94a3b8";
							$st_icon = "fa-forward";
							$st_label = $is_tr ? "Atlandı" : "Skipped";
						}
						$s_name = (string)($s["name"] ?? "");
						$s_label = $step_labels[$s_name] ?? $s_name;
					?>
					<tr style="border-bottom: 1px solid var(--border-color, #334155);">
						<td style="padding: 10px 15px; color: var(--color-text, #f8fafc); font-weight: 600; white-space: nowrap;">
							<?= htmlspecialchars($s_label) ?>
						</td>
						<td style="padding: 10px 15px; text-align: center; white-space: nowrap;">
							<span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; background: <?= $st_color ?>22; color: <?= $st_color ?>; border: 1px solid <?= $st_color ?>44;">
								<i class="fas <?= $st_icon ?>" style="font-size: 9px;"></i>
								<?= htmlspecialchars($st_label) ?>
							</span>
						</td>
						<td style="padding: 10px 15px; text-align: right; color: var(--color-text-muted, #94a3b8); font-family: monospace; font-size: 12px; white-space: nowrap;">
							<?= (int)($s["seconds"] ?? 0) ?>s
						</td>
						<td style="padding: 10px 15px; color: var(--color-text-muted, #cbd5e1); font-size: 12px; word-break: break-word;">
							<?= htmlspecialchars((string)($s["detail"] ?? "")) ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>

	<!-- Disk Before / After -->
	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; margin-bottom: 20px;">
		<?php
		$disk_before = $report["disk_before"] ?? [];
		$disk_after = $report["disk_after"] ?? [];
		$disk_mounts = array_unique(array_merge(array_keys($disk_before), array_keys($disk_after)));
		foreach ($disk_mounts as $mount):
			$b = $disk_before[$mount] ?? null;
			$a = $disk_after[$mount] ?? null;
		?>
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 20px;">
			<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
				<h3 style="margin: 0; font-size: 1rem; color: var(--color-text, #f8fafc);">
					<i class="fas fa-hard-drive icon-blue"></i>
					<?= $is_tr ? "Disk Kullanımı" : "Disk Usage" ?> — <?= htmlspecialchars((string)$mount) ?>
				</h3>
			</div>
			<div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; background: rgba(0,0,0,0.15); border-radius: 6px; padding: 12px;">
				<div style="text-align: center; flex: 1;">
					<div style="font-size: 10px; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; font-weight: 600;"><?= $is_tr ? "Önce" : "Before" ?></div>
					<div style="font-size: 1.1rem; font-weight: bold; color: var(--color-text, #f8fafc); margin-top: 3px;">
						<?= htmlspecialchars(($b["used"] ?? "?") . " / " . ($b["size"] ?? "?")) ?>
					</div>
					<div style="font-size: 11px; color: var(--color-text-muted, #94a3b8);"><?= htmlspecialchars((string)($b["use_pct"] ?? "?")) ?></div>
				</div>
				<i class="fas fa-arrow-right" style="color: var(--color-text-muted, #94a3b8);"></i>
				<div style="text-align: center; flex: 1;">
					<div style="font-size: 10px; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; font-weight: 600;"><?= $is_tr ? "Sonra" : "After" ?></div>
					<div style="font-size: 1.1rem; font-weight: bold; color: var(--color-text, #f8fafc); margin-top: 3px;">
						<?= htmlspecialchars(($a["used"] ?? "?") . " / " . ($a["size"] ?? "?")) ?>
					</div>
					<div style="font-size: 11px; color: var(--color-text-muted, #94a3b8);"><?= htmlspecialchars((string)($a["use_pct"] ?? "?")) ?></div>
				</div>
			</div>
		</div>
		<?php endforeach; ?>
	</div>

	<?php endif; ?>

	<!-- History -->
	<?php if (!empty($history)): ?>
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; overflow: hidden; margin-bottom: 20px;">
		<div style="padding: 15px 20px; border-bottom: 1px solid var(--border-color, #334155); display: flex; justify-content: space-between; align-items: center;">
			<h3 style="margin: 0; font-size: 1rem; color: var(--color-text, #f8fafc);">
				<i class="fas fa-clock-rotate-left icon-blue"></i> <?= $is_tr ? "Çalıştırma Geçmişi" : "Run History" ?>
			</h3>
			<span style="font-size: 11px; color: var(--color-text-muted, #94a3b8);"><?= $is_tr ? "son" : "last" ?> <?= count($history) ?></span>
		</div>
		<div style="padding: 10px 20px;">
			<?php foreach ($history as $h_line): ?>
			<div style="font-family: monospace; font-size: 12px; color: var(--color-text-muted, #cbd5e1); padding: 4px 0; border-bottom: 1px solid var(--border-color, #33415533);">
				<?= htmlspecialchars((string)$h_line) ?>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>

	<!-- Cron hint -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 20px; margin-bottom: 20px;">
		<h3 style="margin: 0 0 8px; font-size: 1rem; color: var(--color-text, #f8fafc);">
			<i class="fas fa-calendar-check icon-green"></i> <?= $is_tr ? "Önerilen Cron Zamanlaması" : "Suggested Cron Schedule" ?>
		</h3>
		<p style="margin: 0 0 10px; color: var(--color-text-muted, #94a3b8); font-size: 13px;">
			<?= $is_tr
				? "Haftalık bir çalıştırma önerilir (ör. pazar 04:00). Herhangi bir cron girdisi otomatik kurulmaz; isterseniz aşağıdaki satırı root crontab'ına ekleyin:"
				: "A weekly run is recommended (e.g. Sunday 04:00). No cron entry is installed automatically; add the line below to root's crontab if desired:" ?>
		</p>
		<code style="display: block; background: rgba(0,0,0,0.25); border-radius: 6px; padding: 10px 14px; font-size: 12px; color: #38bdf8; overflow-x: auto;">0 4 * * 0 /usr/local/hestia/bin/v-run-sys-maintenance plain</code>
	</div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
	var form = document.getElementById('run-maintenance-form');
	if (!form) return;
	form.addEventListener('submit', function () {
		var btn = document.getElementById('btn-run-maintenance');
		if (btn) {
			btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?= $is_tr ? "Çalışıyor... (birkaç dakika sürebilir)" : "Running... (may take minutes)" ?>';
			btn.disabled = true;
		}
	});
});
</script>
