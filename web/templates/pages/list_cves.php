<?php
$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');
$security_updates = (int)($cve_data["security_updates"] ?? 0);
$total_updates = (int)($cve_data["total_updates"] ?? 0);
$security_packages = is_array($cve_data["security_packages"] ?? null) ? $cve_data["security_packages"] : [];
$upgradable = is_array($cve_data["upgradable"] ?? null) ? $cve_data["upgradable"] : [];
$services = is_array($cve_data["services"] ?? null) ? $cve_data["services"] : [];
$error_notes = is_array($cve_data["error_notes"] ?? null) ? $cve_data["error_notes"] : [];
$apt_update_ran = !empty($cve_data["apt_update_ran"]);
$generated_at = (string)($cve_data["generated_at"] ?? "");

$scan_display = $is_tr ? "Hiç" : "Never";
if ($generated_at !== "") {
	try {
		$dt = new DateTime($generated_at);
		$scan_display = $dt->format("d.m.Y H:i");
	} catch (Throwable $e) {
		$scan_display = htmlspecialchars($generated_at);
	}
}

$display_cap = 100;
$upgradable_shown = array_slice($upgradable, 0, $display_cap);
$upgradable_hidden = max(0, count($upgradable) - count($upgradable_shown));
?>

<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/server/">
				<i class="fas fa-arrow-left icon-blue"></i><?= $is_tr ? "Geri" : "Back" ?>
			</a>
			<label class="button button-secondary" style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 400;">
				<input type="checkbox" id="cve-update-cache" style="margin: 0;">
				<?= $is_tr ? "Paket önbelleğini yenile" : "Refresh apt cache" ?>
			</label>
			<button type="button" class="button button-primary" onclick="rescanCves();" id="btn-cve-rescan">
				<i class="fas fa-shield-virus"></i> <?= $is_tr ? "Yeniden Tara" : "Rescan" ?>
			</button>
			<a href="/list/updates/" class="button button-secondary">
				<i class="fas fa-arrow-up-right-dots icon-green"></i> <?= $is_tr ? "Güncellemeler" : "Updates" ?>
			</a>
		</div>
		<div class="toolbar-right">
			<span class="toolbar-link" style="cursor: default;">
				<i class="fas fa-clock"></i>
				<?= $is_tr ? "Son tarama:" : "Last scan:" ?> <?= $scan_display ?>
			</span>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container" style="max-width: 1400px; padding: 15px 20px;">

	<?php if (!empty($error_notes)): ?>
	<!-- Scan Notes -->
	<div style="background: rgba(234,179,8,0.08); border: 1px solid #eab30855; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px;">
		<div style="font-size: 12px; font-weight: 600; color: #eab308; margin-bottom: 6px;">
			<i class="fas fa-circle-info"></i> <?= $is_tr ? "Tarama Notları" : "Scan Notes" ?>
		</div>
		<ul style="margin: 0; padding-left: 20px; color: var(--color-text-muted, #94a3b8); font-size: 12px;">
			<?php foreach ($error_notes as $note): ?>
			<li><?= htmlspecialchars($note) ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php endif; ?>

	<!-- Summary Hero Cards -->
	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 25px;">

		<!-- Security Updates -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid <?= $security_updates > 0 ? '#ef4444' : 'var(--border-color, #334155)' ?>; border-radius: 8px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8); letter-spacing: 0.5px;">
						<?= $is_tr ? "Güvenlik Güncellemesi" : "Security Updates" ?>
					</span>
					<h3 style="margin: 4px 0 0; font-size: 1.8rem; font-weight: bold; color: <?= $security_updates > 0 ? '#ef4444' : '#22c55e' ?>;">
						<?= $security_updates ?>
					</h3>
					<?php if ($security_updates > 0): ?>
					<span style="display: inline-flex; align-items: center; gap: 5px; margin-top: 6px; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; background: #ef444422; color: #ef4444; border: 1px solid #ef444444;">
						<i class="fas fa-triangle-exclamation" style="font-size: 9px;"></i>
						<?= $is_tr ? "Eylem Gerekli" : "Action Required" ?>
					</span>
					<?php endif; ?>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: <?= $security_updates > 0 ? 'rgba(239,68,68,0.15)' : 'rgba(34,197,94,0.15)' ?>; display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-shield-halved" style="font-size: 16px; color: <?= $security_updates > 0 ? '#ef4444' : '#22c55e' ?>;"></i>
				</div>
			</div>
		</div>

		<!-- Total Updates -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8); letter-spacing: 0.5px;">
						<?= $is_tr ? "Toplam Güncelleme" : "Total Updates" ?>
					</span>
					<h3 style="margin: 4px 0 0; font-size: 1.8rem; font-weight: bold; color: var(--color-text, #f8fafc);">
						<?= $total_updates ?>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(56,189,248,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-arrow-up-right-dots" style="font-size: 16px; color: #38bdf8;"></i>
				</div>
			</div>
		</div>

		<!-- Last Scan -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8); letter-spacing: 0.5px;">
						<?= $is_tr ? "Son Tarama" : "Last Scan" ?>
					</span>
					<h3 style="margin: 4px 0 0; font-size: 1rem; font-weight: bold; color: var(--color-text, #f8fafc); white-space: nowrap;">
						<?= $scan_display ?>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(168,85,247,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-clock-rotate-left" style="font-size: 16px; color: #a855f7;"></i>
				</div>
			</div>
		</div>

		<!-- Apt Cache -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8); letter-spacing: 0.5px;">
						<?= $is_tr ? "Paket Önbelleği" : "Apt Cache" ?>
					</span>
					<h3 style="margin: 4px 0 0; font-size: 1rem; font-weight: bold; color: <?= $apt_update_ran ? '#22c55e' : 'var(--color-text, #f8fafc)' ?>;">
						<?= $apt_update_ran
							? ($is_tr ? "Taramada yenilendi" : "Refreshed during scan")
							: ($is_tr ? "Yenilenmedi" : "Not refreshed") ?>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(34,197,94,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-database" style="font-size: 16px; color: #22c55e;"></i>
				</div>
			</div>
		</div>

	</div>

	<?php if ($total_updates === 0 && empty($error_notes)): ?>
	<!-- Empty State -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 40px; text-align: center; margin-bottom: 20px;">
		<i class="fas fa-shield-heart" style="font-size: 48px; color: #22c55e; margin-bottom: 15px;"></i>
		<h3 style="color: var(--color-text, #f8fafc); margin: 0 0 8px;">
			<?= $is_tr ? "Bekleyen Güncelleme Yok" : "No Pending Updates" ?>
		</h3>
		<p style="color: var(--color-text-muted, #94a3b8); font-size: 13px; margin: 0 0 15px;">
			<?= $is_tr
				? "İşletim sistemi paketleri güncel görünüyor. Güncel sonuçlar için \"Paket önbelleğini yenile\" seçeneğiyle yeniden tarayabilirsiniz."
				: "All OS packages appear to be up to date. Enable \"Refresh apt cache\" and rescan for the freshest results." ?>
		</p>
		<button type="button" class="button button-primary" onclick="rescanCves();">
			<i class="fas fa-shield-virus"></i> <?= $is_tr ? "Yeniden Tara" : "Rescan" ?>
		</button>
	</div>
	<?php endif; ?>

	<?php if (!empty($security_packages)): ?>
	<!-- Security Packages Table -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid #ef444455; border-radius: 8px; overflow: hidden; margin-bottom: 20px;">
		<div style="padding: 15px 20px; border-bottom: 1px solid var(--border-color, #334155); display: flex; justify-content: space-between; align-items: center;">
			<h3 style="margin: 0; font-size: 1rem; color: var(--color-text, #f8fafc);">
				<i class="fas fa-bug" style="color: #ef4444;"></i>
				<?= $is_tr ? "Güvenlik Güncellemesi Bekleyen Paketler" : "Packages with Pending Security Updates" ?>
			</h3>
			<span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; background: #ef444422; color: #ef4444; border: 1px solid #ef444444;">
				<?= count($security_packages) ?>
			</span>
		</div>
		<div style="overflow-x: auto;">
			<table style="width: 100%; border-collapse: collapse; font-size: 13px;">
				<thead>
					<tr style="background: rgba(0,0,0,0.2);">
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Paket" : "Package" ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Yeni Sürüm" : "New Version" ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($security_packages as $pkg): ?>
					<tr style="border-bottom: 1px solid var(--border-color, #334155); background: rgba(239,68,68,0.04);">
						<td style="padding: 10px 15px; color: var(--color-text, #f8fafc); font-weight: 600; white-space: nowrap;">
							<i class="fas fa-box" style="font-size: 11px; color: #ef4444; margin-right: 6px;"></i><?= htmlspecialchars($pkg["name"] ?? "") ?>
						</td>
						<td style="padding: 10px 15px; color: var(--color-text-muted, #cbd5e1); font-family: monospace; font-size: 12px;">
							<?= htmlspecialchars($pkg["version"] ?? "") ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php endif; ?>

	<?php if (!empty($upgradable)): ?>
	<!-- Full Upgradable List (collapsible) -->
	<details style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; margin-bottom: 20px;">
		<summary style="padding: 15px 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; list-style: none;">
			<h3 style="margin: 0; font-size: 1rem; color: var(--color-text, #f8fafc); display: inline;">
				<i class="fas fa-boxes-packing icon-blue"></i>
				<?= $is_tr ? "Tüm Güncellenebilir Paketler" : "All Upgradable Packages" ?>
			</h3>
			<span style="font-size: 11px; color: var(--color-text-muted, #94a3b8);">
				<?= count($upgradable) ?> <?= $is_tr ? "paket" : "packages" ?> — <?= $is_tr ? "genişletmek için tıklayın" : "click to expand" ?>
			</span>
		</summary>
		<div style="overflow-x: auto; border-top: 1px solid var(--border-color, #334155);">
			<table style="width: 100%; border-collapse: collapse; font-size: 13px;">
				<thead>
					<tr style="background: rgba(0,0,0,0.2);">
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Paket" : "Package" ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Yeni Sürüm" : "New Version" ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Depo" : "Suite" ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($upgradable_shown as $pkg): ?>
					<tr style="border-bottom: 1px solid var(--border-color, #334155);">
						<td style="padding: 8px 15px; color: var(--color-text, #f8fafc); font-weight: 600; white-space: nowrap;">
							<?= htmlspecialchars($pkg["package"] ?? "") ?>
						</td>
						<td style="padding: 8px 15px; color: var(--color-text-muted, #cbd5e1); font-family: monospace; font-size: 12px;">
							<?= htmlspecialchars($pkg["version"] ?? "") ?>
						</td>
						<td style="padding: 8px 15px; white-space: nowrap;">
							<?php $suite = (string)($pkg["suite"] ?? ""); ?>
							<span style="padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; background: <?= str_contains($suite, "-security") ? '#ef444422' : 'rgba(56,189,248,0.12)' ?>; color: <?= str_contains($suite, "-security") ? '#ef4444' : '#38bdf8' ?>;">
								<?= htmlspecialchars($suite) ?>
							</span>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ($upgradable_hidden > 0): ?>
			<div style="padding: 10px 15px; color: var(--color-text-muted, #94a3b8); font-size: 12px; border-top: 1px solid var(--border-color, #334155);">
				<i class="fas fa-circle-info"></i>
				<?= $is_tr ? "İlk " . count($upgradable_shown) . " paket gösteriliyor (toplam " . count($upgradable) . ")." : "Showing first " . count($upgradable_shown) . " of " . count($upgradable) . " packages." ?>
			</div>
			<?php endif; ?>
		</div>
	</details>
	<?php endif; ?>

	<?php if (!empty($services)): ?>
	<!-- Service Versions -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; overflow: hidden; margin-bottom: 20px;">
		<div style="padding: 15px 20px; border-bottom: 1px solid var(--border-color, #334155); display: flex; justify-content: space-between; align-items: center;">
			<h3 style="margin: 0; font-size: 1rem; color: var(--color-text, #f8fafc);">
				<i class="fas fa-gears icon-green"></i> <?= $is_tr ? "Kurulu Servis Sürümleri" : "Installed Service Versions" ?>
			</h3>
			<span style="font-size: 11px; color: var(--color-text-muted, #94a3b8);">
				<?= $is_tr ? "bilgi amaçlı" : "informational" ?>
			</span>
		</div>
		<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 12px; padding: 15px 20px;">
			<?php foreach ($services as $svc): ?>
			<div style="background: rgba(0,0,0,0.15); border-radius: 6px; padding: 12px 14px;">
				<div style="font-size: 10px; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">
					<?= htmlspecialchars($svc["name"] ?? "") ?>
				</div>
				<div style="font-size: 12px; color: var(--color-text, #f8fafc); font-family: monospace; margin-top: 4px; word-break: break-all;">
					<?= htmlspecialchars($svc["version"] ?? "") ?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>

</div>

<script>
function rescanCves() {
	var btn = document.getElementById('btn-cve-rescan');
	var origText = btn.innerHTML;
	var updateCache = document.getElementById('cve-update-cache').checked;

	if (updateCache && !confirm(<?= json_encode($is_tr
		? "Paket önbelleği yenilenecek (apt-get update). Bu işlem 2 dakikaya kadar sürebilir. Devam edilsin mi?"
		: "The apt package cache will be refreshed (apt-get update). This may take up to 2 minutes. Continue?") ?>)) {
		return;
	}

	btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?= $is_tr ? "Taranıyor (uzun sürebilir)..." : "Scanning (may take a while)..." ?>';
	btn.disabled = true;

	var body = 'action=rescan&token=' + encodeURIComponent('<?= htmlspecialchars($_SESSION["token"] ?? "") ?>');
	if (updateCache) {
		body += '&update_cache=1';
	}

	fetch('/list/cves/', {
		method: 'POST',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body: body
	})
	.then(function(r) { return r.json(); })
	.then(function(data) {
		btn.innerHTML = data.ok
			? '<i class="fas fa-circle-check"></i> <?= $is_tr ? "Tamamlandı!" : "Done!" ?>'
			: '<i class="fas fa-circle-exclamation"></i> <?= $is_tr ? "Hata (bkz. notlar)" : "Error (see notes)" ?>';
		setTimeout(function() { location.reload(); }, 1000);
	})
	.catch(function() {
		btn.innerHTML = origText;
		btn.disabled = false;
	});
}
</script>
