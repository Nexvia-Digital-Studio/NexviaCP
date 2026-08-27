<?php
$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');
function __u_tr($en, $tr) {
    global $is_tr;
    return $is_tr ? $tr : _($en);
}
?>

<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/server/">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml(__u_tr("Back", "Geri")) ?>
			</a>
			<a class="button button-secondary" href="/list/updates/?update_nexvia=1&token=<?= tohtml($_SESSION["token"]) ?>" onclick="return confirm('<?= tohtml(__u_tr("Are you sure you want to pull and apply the latest updates from Nexvia-Digital-Studio/NexviaCP?", "NexviaCP ana deposundan en güncel çekirdek kodlarını çekip uygulamak istediğinize emin misiniz?")) ?>');">
				<i class="fab fa-github icon-blue"></i><?= tohtml(__u_tr("Pull NexviaCP Updates", "NexviaCP'yi GitHub'dan Güncelle")) ?>
			</a>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">

	<h1 class="u-text-center u-hide-desktop u-mt20 u-pr30 u-mb20 u-pl30"><?= tohtml(__u_tr("Updates & Core Management", "Güncellemeler & Çekirdek Yönetimi")) ?></h1>
	<?php show_alert_message($_SESSION); ?>

	<!-- Top Grid: NexviaCP Core & Upstream Security Tracking -->
	<div class="u-mb20" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 15px;">
		
		<!-- Card 1: NexviaCP Core Repository -->
		<div class="card" style="padding: 20px; border-radius: 6px; border: 1px solid var(--border-color, #334155); background: var(--color-background, #fff);">
			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
				<div style="display:flex; align-items:center; gap:8px;">
					<i class="fab fa-github fa-2x" style="color: var(--icon-color-blue, #38bdf8);"></i>
					<div>
						<h3 style="margin:0; font-size:1.05rem; font-weight:bold;">NexviaCP Çekirdek</h3>
						<small class="u-text-muted">Nexvia-Digital-Studio/NexviaCP (main)</small>
					</div>
				</div>
				<span class="badge badge-info" style="font-size:11px; padding:3px 8px;">
					<i class="fas fa-circle-check icon-green"></i> Canlı Sürüm
				</span>
			</div>
			
			<p class="u-text-muted u-mb15" style="font-size:0.88rem; line-height:1.4;">
				Son Commit: <code><?= tohtml($nexvia_info["nexvia"]["latest_sha"] ?? "main") ?></code><br>
				<span style="font-size:11px;"><?= tohtml($nexvia_info["nexvia"]["latest_message"] ?? "NexviaCP Custom Core") ?></span>
			</p>
			
			<a class="button button-primary u-width-full" href="/list/updates/?update_nexvia=1&token=<?= tohtml($_SESSION["token"]) ?>" onclick="return confirm('NexviaCP ana deposundan en güncel çekirdek kodlarını çekip uygulamak istediğinize emin misiniz?');">
				<i class="fas fa-rotate"></i> <?= tohtml(__u_tr("Pull & Apply Latest Commits", "En Güncel Sürümü Çek & Uygula")) ?>
			</a>
		</div>

		<!-- Card 2: Upstream HestiaCP Security Tracker -->
		<div class="card" style="padding: 20px; border-radius: 6px; border: 1px solid var(--border-color, #334155); background: var(--color-background, #fff);">
			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
				<div style="display:flex; align-items:center; gap:8px;">
					<i class="fas fa-shield-halved fa-2x" style="color: var(--icon-color-purple, #a855f7);"></i>
					<div>
						<h3 style="margin:0; font-size:1.05rem; font-weight:bold;">Upstream HestiaCP Takibi</h3>
						<small class="u-text-muted">Resmi Güvenlik & Sürüm Notları</small>
					</div>
				</div>
				<span class="badge badge-secondary" style="font-size:11px; padding:3px 8px;">
					<?= tohtml($nexvia_info["upstream_hestia"]["latest_version"] ?? "v1.9.3") ?>
				</span>
			</div>
			
			<p class="u-text-muted u-mb15" style="font-size:0.84rem; line-height:1.4;">
				🛡️ <strong>Mimari Koruma:</strong> Özel Nexvia altyapımızı (.NET, Node, Portainer, GitHub CI/CD, Secret Vault) korumak için upstream güncellemeleri otomatik ezilmez. Sürüm notlarını inceleyip kontrollü entegre edebilirsiniz.
			</p>

			<a class="button button-secondary u-width-full" href="<?= tohtml($nexvia_info["upstream_hestia"]["changelog_url"] ?? "https://github.com/hestiacp/hestiacp/releases") ?>" target="_blank" rel="noopener">
				<i class="fas fa-arrow-up-right-from-square"></i> <?= tohtml(__u_tr("Inspect Upstream Changelog", "Upstream Sürüm Notlarını İncele")) ?>
			</a>
		</div>

	</div>

	<!-- System Packages Table -->
	<h3 class="u-mb10" style="font-size:1rem; font-weight:600;"><?= tohtml(__u_tr("Installed System Packages", "Yüklü Sistem Paketleri")) ?></h3>
	<div class="units-table js-units-container">
		<div class="units-table-header">
			<div class="units-table-cell"><?= tohtml(__u_tr("Package Name", "Paket Adı")) ?></div>
			<div class="units-table-cell"><?= tohtml(__u_tr("Description", "Açıklama")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml(__u_tr("Version", "Sürüm")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml(__u_tr("Status", "Durum")) ?></div>
		</div>

		<!-- Begin update list item loop -->
		<?php
			$i = 0;
			foreach ($data as $key => $value) {
				++$i;

				if (($data[$key]['UPDATED'] ?? '') == 'yes') {
					$status = 'active';
				} else {
					$status = 'suspended';
				}
			?>
			<div class="units-table-row <?php if ($status == 'suspended') echo 'disabled'; ?> js-unit">
				<div class="units-table-cell units-table-heading-cell u-text-bold">
					<span class="u-hide-desktop"><?= tohtml(__u_tr("Package Name", "Paket Adı")) ?>:</span>
					<?= tohtml($key) ?>
				</div>
				<div class="units-table-cell">
					<span class="u-hide-desktop u-text-bold"><?= tohtml(__u_tr("Description", "Açıklama")) ?>:</span>
					<?= tohtml(_($data[$key]["DESCR"] ?? '')) ?>
				</div>
				<div class="units-table-cell u-text-center-desktop">
					<span class="u-hide-desktop u-text-bold"><?= tohtml(__u_tr("Version", "Sürüm")) ?>:</span>
					<?= tohtml($data[$key]["VERSION"] ?? '') ?> (<?= tohtml($data[$key]["ARCH"] ?? '') ?>)
				</div>
				<div class="units-table-cell u-text-center-desktop">
					<span class="u-hide-desktop u-text-bold"><?= tohtml(__u_tr("Status", "Durum")) ?>:</span>
					<?php if (($data[$key]['UPDATED'] ?? '') == 'no'): ?>
						<i class="fas fa-triangle-exclamation icon-orange" title="<?= tohtml(__u_tr("Update available", "Güncelleme mevcut")) ?>"></i>
					<?php else: ?>
						<i class="fas fa-circle-check icon-green" title="<?= tohtml(__u_tr("Package up-to-date", "Paket güncel")) ?>"></i>
					<?php endif; ?>
				</div>
			</div>
		<?php } ?>
	</div>

</div>
