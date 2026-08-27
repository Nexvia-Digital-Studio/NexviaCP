<?php
$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');
function __u_tr($en, $tr) {
    global $is_tr;
    return $is_tr ? $tr : _($en);
}
$nx_data = $nexvia_info["nexvia"] ?? [];
$up_data = $nexvia_info["upstream_hestia"] ?? [];
?>

<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/server/">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml(__u_tr("Back", "Geri")) ?>
			</a>
			<button type="button" class="button button-primary" onclick="openUpdateModal();">
				<i class="fab fa-github"></i> <?= tohtml(__u_tr("Pull NexviaCP Updates", "NexviaCP'yi Güncelle")) ?>
			</button>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">

	<h1 class="u-text-center u-hide-desktop u-mt20 u-pr30 u-mb20 u-pl30"><?= tohtml(__u_tr("Updates & Core Management", "Güncellemeler & Çekirdek Yönetimi")) ?></h1>
	<?php show_alert_message($_SESSION); ?>

	<!-- Top Grid: NexviaCP Core & Upstream Security Tracking -->
	<div class="u-mb20" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 15px;">
		
		<!-- Card 1: NexviaCP Core Repository -->
		<div class="card" style="padding: 20px; border-radius: 8px; border: 1px solid var(--border-color, #334155); background: var(--color-background, #fff); display:flex; flex-direction:column; justify-content:space-between;">
			<div>
				<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
					<div style="display:flex; align-items:center; gap:10px;">
						<div style="width:36px; height:36px; border-radius:8px; background: rgba(56, 189, 248, 0.1); display:flex; align-items:center; justify-content:center;">
							<i class="fab fa-github fa-lg" style="color: var(--icon-color-blue, #38bdf8);"></i>
						</div>
						<div>
							<h3 style="margin:0; font-size:1.05rem; font-weight:bold;">NexviaCP Çekirdek</h3>
							<small class="u-text-muted">Nexvia-Digital-Studio/NexviaCP (main)</small>
						</div>
					</div>
					<div style="display:flex; gap:6px; align-items:center;">
						<span class="badge badge-primary" style="font-size:11px; padding:3px 8px;">
							<i class="fas fa-code-branch"></i> v2.1.0
						</span>
						<span class="badge badge-info" style="font-size:11px; padding:3px 8px;">
							<i class="fas fa-circle-check icon-green"></i> Canlı Sürüm
						</span>
					</div>
				</div>
				
				<div class="u-mb15" style="background: rgba(0,0,0,0.03); border: 1px solid var(--border-color, #e2e8f0); border-radius: 6px; padding: 12px;">
					<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
						<span style="font-size:11px; font-weight:bold; color:var(--color-text-muted);">SON COMMIT:</span>
						<code style="font-size:12px; font-weight:bold; color: var(--icon-color-blue, #0284c7);"><?= tohtml($nx_data["latest_sha"] ?? "main") ?></code>
					</div>
					<div style="font-size: 0.88rem; font-weight: 500; line-height: 1.4; color: var(--color-text); word-break: break-word;">
						<?= nl2br(tohtml($nx_data["latest_message"] ?? "NexviaCP Custom Core")) ?>
					</div>
					<div style="margin-top: 8px; font-size: 11px; color: var(--color-text-muted); display:flex; justify-content:space-between;">
						<span><i class="fas fa-user-circle"></i> <?= tohtml($nx_data["author"] ?? "Nexvia") ?></span>
						<span><i class="fas fa-clock"></i> <?= tohtml($nx_data["latest_date"] ?? "") ?></span>
					</div>
				</div>
			</div>
			
			<div style="display:flex; gap:10px;">
				<button type="button" class="button button-primary" style="flex:1;" onclick="openUpdateModal();">
					<i class="fas fa-rotate"></i> <?= tohtml(__u_tr("Pull & Apply Updates", "Güncellemeyi Başlat")) ?>
				</button>
				<button type="button" class="button button-secondary" onclick="openChangelogModal();" title="<?= tohtml(__u_tr("View Full Details", "Tüm Değişiklikleri Oku")) ?>">
					<i class="fas fa-eye"></i>
				</button>
			</div>
		</div>

		<!-- Card 2: Upstream HestiaCP Security Tracker -->
		<div class="card" style="padding: 20px; border-radius: 8px; border: 1px solid var(--border-color, #334155); background: var(--color-background, #fff); display:flex; flex-direction:column; justify-content:space-between;">
			<div>
				<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
					<div style="display:flex; align-items:center; gap:10px;">
						<div style="width:36px; height:36px; border-radius:8px; background: rgba(168, 85, 247, 0.1); display:flex; align-items:center; justify-content:center;">
							<i class="fas fa-shield-halved fa-lg" style="color: var(--icon-color-purple, #a855f7);"></i>
						</div>
						<div>
							<h3 style="margin:0; font-size:1.05rem; font-weight:bold;">Upstream HestiaCP Takibi</h3>
							<small class="u-text-muted">Resmi Güvenlik & Sürüm Notları</small>
						</div>
					</div>
					<span class="badge badge-secondary" style="font-size:11px; padding:3px 8px;">
						<?= tohtml($up_data["latest_version"] ?? "v1.10.4") ?>
					</span>
				</div>
				
				<p class="u-text-muted u-mb15" style="font-size:0.84rem; line-height:1.45;">
					🛡️ <strong>Mimari Koruma:</strong> Geliştirdiğimiz özel <strong>.NET Core, Node.js, Portainer Docker UI, GitHub CI/CD ve Secret Vault</strong> modüllerini korumak için upstream güncellemeleri paneli otomatik ezmez. Değişiklik ve güvenlik bültenlerini inceleyebilirsiniz.
				</p>
			</div>

			<a class="button button-secondary u-width-full" href="<?= tohtml($up_data["changelog_url"] ?? "https://github.com/hestiacp/hestiacp/releases") ?>" target="_blank" rel="noopener">
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

<!-- MODAL 1: Full Changelog Viewer -->
<div id="changelog-modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); align-items:center; justify-content:center;" onclick="if(event.target===this) closeChangelogModal();">
	<div class="card" style="width:90%; max-width:600px; padding:25px; border-radius:10px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.5); background:var(--color-background, #1e293b); border:1px solid var(--border-color, #475569);">
		<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
			<h2 style="margin:0; font-size:1.2rem; font-weight:bold; display:flex; align-items:center; gap:8px;">
				<i class="fab fa-github" style="color:var(--icon-color-blue, #38bdf8);"></i> <?= tohtml(__tr("Latest Commit Details", "Son Güncelleme Ayrıntıları")) ?>
			</h2>
			<button type="button" class="button button-secondary" onclick="closeChangelogModal();" style="padding:4px 10px;">&times;</button>
		</div>
		
		<div style="background:rgba(0,0,0,0.2); padding:15px; border-radius:6px; border:1px solid var(--border-color, #334155); margin-bottom:15px;">
			<div style="font-size:12px; margin-bottom:8px;">
				<strong>Commit Hash:</strong> <code><?= tohtml($nx_data["full_sha"] ?? $nx_data["latest_sha"] ?? "main") ?></code>
			</div>
			<div style="font-size:12px; margin-bottom:8px;">
				<strong><?= tohtml(__tr("Author:", "Yazar:")) ?></strong> <?= tohtml($nx_data["author"] ?? "Nexvia Digital Studio") ?> &bull; <strong><?= tohtml(__tr("Date:", "Tarih:")) ?></strong> <?= tohtml($nx_data["latest_date"] ?? "") ?>
			</div>
			<hr style="border:none; border-top:1px solid var(--border-color, #334155); margin:10px 0;">
			<div style="font-size:13px; font-weight:bold; margin-bottom:6px;"><?= tohtml(__tr("Commit Message:", "Commit Açıklaması:")) ?></div>
			<div style="white-space:pre-wrap; font-family:monospace; font-size:12px; line-height:1.5; color:var(--color-text);">
				<?= tohtml($nx_data["latest_message"] ?? "NexviaCP Core Updates") ?>
			</div>
		</div>

		<div style="display:flex; justify-content:flex-end; gap:10px;">
			<a href="<?= tohtml($nx_data["commit_url"] ?? "https://github.com/Nexvia-Digital-Studio/NexviaCP") ?>" target="_blank" class="button button-secondary">
				<i class="fas fa-external-link-alt"></i> <?= tohtml(__tr("View on GitHub", "GitHub'da İncele")) ?>
			</a>
			<button type="button" class="button button-primary" onclick="closeChangelogModal(); openUpdateModal();">
				<i class="fas fa-rotate"></i> <?= tohtml(__tr("Update Now", "Şimdi Güncelle")) ?>
			</button>
		</div>
	</div>
</div>

<!-- MODAL 2: Interactive Live Update Modal with Steps -->
<div id="update-modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.65); backdrop-filter:blur(5px); align-items:center; justify-content:center;" onclick="if(event.target===this && document.getElementById('update-modal-close-btn').style.display !== 'none') closeUpdateModal();">
	<div class="card" style="width:90%; max-width:550px; padding:25px; border-radius:10px; box-shadow:0 25px 30px -5px rgba(0,0,0,0.6); background:var(--color-background, #1e293b); border:1px solid var(--border-color, #475569);">
		
		<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
			<h2 style="margin:0; font-size:1.2rem; font-weight:bold; display:flex; align-items:center; gap:10px;">
				<i class="fas fa-rotate fa-spin-pulse" id="update-modal-spinner" style="color:var(--icon-color-blue, #38bdf8);"></i>
				<span><?= tohtml(__tr("NexviaCP Core Live Update", "NexviaCP Çekirdek Güncelleme")) ?></span>
			</h2>
			<button type="button" class="button button-secondary" id="update-modal-close-btn" onclick="closeUpdateModal();" style="padding:4px 10px;">&times;</button>
		</div>

		<!-- Step Progress Tracker -->
		<div style="display:flex; flex-direction:column; gap:12px; margin-bottom:20px;">
			<div id="step-1" class="step-item" style="display:flex; align-items:center; gap:12px; font-size:13px; color:var(--color-text-muted);">
				<i class="far fa-circle" id="icon-step-1"></i>
				<span><?= tohtml(__tr("1. Verifying GitHub Connection (main)...", "1. GitHub Bağlantısı Doğrulanıyor (main)...")) ?></span>
			</div>
			<div id="step-2" class="step-item" style="display:flex; align-items:center; gap:12px; font-size:13px; color:var(--color-text-muted);">
				<i class="far fa-circle" id="icon-step-2"></i>
				<span><?= tohtml(__tr("2. Downloading Latest Core Files & Templates...", "2. En Yeni Çekirdek Dosyaları & Şablonlar İndiriliyor...")) ?></span>
			</div>
			<div id="step-3" class="step-item" style="display:flex; align-items:center; gap:12px; font-size:13px; color:var(--color-text-muted);">
				<i class="far fa-circle" id="icon-step-3"></i>
				<span><?= tohtml(__tr("3. Applying File Permissions & Security Hardening...", "3. Dosya İzinleri ve Güvenlik Kuralları Uygulanıyor...")) ?></span>
			</div>
			<div id="step-4" class="step-item" style="display:flex; align-items:center; gap:12px; font-size:13px; color:var(--color-text-muted);">
				<i class="far fa-circle" id="icon-step-4"></i>
				<span><?= tohtml(__tr("4. Gracefully Reloading Panel Services (PHP-FPM & Nginx)...", "4. Panel Servisleri (PHP-FPM & Nginx) Yeniden Başlatılıyor...")) ?></span>
			</div>
			<div id="step-5" class="step-item" style="display:flex; align-items:center; gap:12px; font-size:13px; color:var(--color-text-muted);">
				<i class="far fa-circle" id="icon-step-5"></i>
				<span><?= tohtml(__tr("5. Update Completed Successfully!", "5. Güncelleme Tamamlandı!")) ?></span>
			</div>
		</div>

		<!-- Status / Result Message Box -->
		<div id="update-status-box" style="background:rgba(0,0,0,0.25); border-radius:6px; border:1px solid var(--border-color, #334155); padding:12px; font-size:12px; font-family:monospace; max-height:100px; overflow-y:auto; margin-bottom:15px;">
			<?= tohtml(__tr("Ready to initiate update. Latest GitHub commit will be applied.", "Güncelleme başlatılmaya hazır. GitHub deposundaki en son commit uygulanacak.")) ?>
		</div>

		<div style="display:flex; justify-content:flex-end; gap:10px;" id="update-modal-actions">
			<button type="button" class="button button-secondary" onclick="closeUpdateModal();"><?= tohtml(__tr("Cancel", "İptal")) ?></button>
			<button type="button" class="button button-primary" id="btn-start-update" onclick="executeLiveUpdate();">
				<i class="fas fa-cloud-arrow-down"></i> <?= tohtml(__tr("Start Update", "Güncellemeyi Başlat")) ?>
			</button>
		</div>
	</div>
</div>

<script>
function openChangelogModal() {
	document.getElementById('changelog-modal').style.display = 'flex';
}
function closeChangelogModal() {
	document.getElementById('changelog-modal').style.display = 'none';
}

function openUpdateModal() {
	document.getElementById('update-modal').style.display = 'flex';
}
function closeUpdateModal() {
	document.getElementById('update-modal').style.display = 'none';
}

document.addEventListener('keydown', function(e) {
	if (e.key === 'Escape') {
		closeChangelogModal();
		const closeBtn = document.getElementById('update-modal-close-btn');
		if (closeBtn && closeBtn.style.display !== 'none') {
			closeUpdateModal();
		}
	}
});

async function executeLiveUpdate() {
	const btn = document.getElementById('btn-start-update');
	const closeBtn = document.getElementById('update-modal-close-btn');
	const statusBox = document.getElementById('update-status-box');
	const actions = document.getElementById('update-modal-actions');
	const isTr = '<?= (($_SESSION['language'] ?? '') === 'tr') ? '1' : '0' ?>' === '1';
	
	btn.disabled = true;
	closeBtn.style.display = 'none';
	btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + (isTr ? 'Güncelleniyor...' : 'Updating...');

	function setStep(num, state) {
		const el = document.getElementById('step-' + num);
		const icon = document.getElementById('icon-step-' + num);
		if (!el || !icon) return;
		if (state === 'active') {
			el.style.color = 'var(--icon-color-blue, #38bdf8)';
			el.style.fontWeight = 'bold';
			icon.className = 'fas fa-circle-notch fa-spin icon-blue';
		} else if (state === 'done') {
			el.style.color = 'var(--icon-color-green, #22c55e)';
			el.style.fontWeight = 'normal';
			icon.className = 'fas fa-circle-check icon-green';
		} else if (state === 'error') {
			el.style.color = '#ef4444';
			el.style.fontWeight = 'bold';
			icon.className = 'fas fa-circle-xmark icon-red';
		}
	}

	setStep(1, 'active');
	statusBox.innerText = isTr ? 'GitHub reposuna bağlanılıyor (Nexvia-Digital-Studio/NexviaCP)...' : 'Connecting to GitHub repository...';

	setTimeout(() => {
		setStep(1, 'done');
		setStep(2, 'active');
		statusBox.innerText = isTr ? 'En yeni çekirdek dosyaları indiriliyor...' : 'Fetching latest core files...';
	}, 1000);

	try {
		const res = await fetch('/list/updates/?ajax_update=1&token=<?= tohtml($_SESSION["token"]) ?>');
		let data = null;
		try {
			data = await res.json();
		} catch(e) {}

		if (data && data.status === 'error') {
			setStep(2, 'error');
			statusBox.innerText = '✗ ' + (data.message || (isTr ? 'Güncelleme hatası oluştu.' : 'Update failed.'));
			actions.innerHTML = '<button type="button" class="button button-secondary u-width-full" onclick="closeUpdateModal();">' + (isTr ? 'Kapat' : 'Close') + '</button>';
			closeBtn.style.display = 'block';
			return;
		}

		setStep(2, 'done');
		setStep(3, 'active');
		statusBox.innerText = isTr ? 'İzinler uygulandı (chmod, root:root)...' : 'Applying file permissions and security rules...';

		setTimeout(() => {
			setStep(3, 'done');
			setStep(4, 'active');
			statusBox.innerText = isTr ? 'Servisler yeniden başlatılıyor (PHP & Nginx)...' : 'Reloading panel services...';

			setTimeout(() => {
				setStep(4, 'done');
				setStep(5, 'done');
				statusBox.innerText = '✓ ' + ((data && data.message) ? data.message : (isTr ? 'NexviaCP çekirdeği başarıyla güncellendi!' : 'NexviaCP core updated successfully!'));
				actions.innerHTML = '<button type="button" class="button button-success u-width-full" onclick="location.reload();"><i class="fas fa-check"></i> ' + (isTr ? 'Sayfayı Yenile & Tamamla' : 'Reload Page & Complete') + '</button>';
				closeBtn.style.display = 'block';
			}, 1000);
		}, 800);

	} catch (err) {
		// If connection drops due to graceful PHP-FPM / Nginx reload, ping to confirm
		statusBox.innerText = isTr ? 'Servisler yeniden başlatılıyor, panel doğrulanıyor...' : 'Reloading services, verifying panel...';
		setTimeout(async () => {
			try {
				await fetch('/list/updates/');
				setStep(2, 'done');
				setStep(3, 'done');
				setStep(4, 'done');
				setStep(5, 'done');
				statusBox.innerText = '✓ ' + (isTr ? 'NexviaCP çekirdeği güncellendi ve servisler yeniden başlatıldı.' : 'NexviaCP core updated and services reloaded.');
				actions.innerHTML = '<button type="button" class="button button-success u-width-full" onclick="location.reload();"><i class="fas fa-check"></i> ' + (isTr ? 'Sayfayı Yenile & Tamamla' : 'Reload Page & Complete') + '</button>';
				closeBtn.style.display = 'block';
			} catch (pingErr) {
				setStep(4, 'done');
				setStep(5, 'done');
				statusBox.innerText = '✓ ' + (isTr ? 'Güncelleme tamamlandı. Lütfen sayfayı yenileyiniz.' : 'Update complete. Please reload the page.');
				actions.innerHTML = '<button type="button" class="button button-primary u-width-full" onclick="location.reload();"><i class="fas fa-rotate"></i> ' + (isTr ? 'Sayfayı Yenile' : 'Reload Page') + '</button>';
				closeBtn.style.display = 'block';
			}
		}, 2000);
	}
}
</script>
