<?php
// Cloud Backup Manager Template with AES-256 Encryption & Multi-Cloud Sync
?>

<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a href="/list/backup/" class="button button-secondary button-back js-button-back">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml(__tr("Back to Local Backups", "Yerel Yedeklere Dön")) ?>
			</a>
			<a href="/list/backup/exclusions/" class="button button-secondary">
				<i class="fas fa-folder-minus icon-orange"></i><?= tohtml(__tr("Backup Exclusions", "Yedekleme Hariç Tutmaları")) ?>
			</a>
		</div>
		<div class="toolbar-right">
			<div class="toolbar-buttons">
				<!-- Test Connection Button -->
				<a href="/list/cloud-backup/?test_connection=1&token=<?= tohtml($_SESSION['token']) ?>" class="button button-secondary" title="<?= tohtml(__tr("Test Cloud Connection", "Bağlantıyı Test Et")) ?>">
					<i class="fas fa-plug-circle-check icon-green"></i><?= tohtml(__tr("Test Connection", "Bağlantı Testi")) ?>
				</a>
				<!-- Sync Now Button -->
				<a href="/list/cloud-backup/?sync_now=1&token=<?= tohtml($_SESSION['token']) ?>" class="button button-secondary" title="<?= tohtml(__tr("Sync local backups to cloud", "Yerel yedekleri buluta aktar")) ?>">
					<i class="fas fa-cloud-arrow-up icon-blue"></i><?= tohtml(__tr("Sync to Cloud", "Buluta Senkronize Et")) ?>
				</a>
				<!-- Backup & Sync Now Button -->
				<form action="/list/cloud-backup/" method="post" style="display: inline-block; margin: 0;">
					<input type="hidden" name="token" value="<?= tohtml($_SESSION['token']) ?>">
					<input type="hidden" name="backup_and_sync" value="1">
					<button type="submit" class="button button-primary">
						<i class="fas fa-circle-plus"></i><?= tohtml(__tr("Create & Sync Full Backup", "Yedek Al ve Buluta Yükle")) ?>
					</button>
				</form>
			</div>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container u-mt20">

	<div class="u-flex u-flex-wrap u-justify-between u-items-center u-mb20" style="gap: 15px;">
		<div>
			<h1 class="u-mb5" style="font-size: 1.4rem; font-weight: 700; display: flex; align-items: center; gap: 10px;">
				<i class="fas fa-cloud-arrow-up icon-blue"></i>
				<?= tohtml(__tr("Cloud Backup & Disaster Recovery Manager", "Bulut Yedekleme & Felaket Kurtarma Yöneticisi")) ?>
			</h1>
			<p class="u-text-muted" style="font-size: 13px; margin: 0;">
				<?= tohtml(__tr("Automated off-site disaster backups with zero-knowledge AES-256-CBC encryption to Cloudflare R2, AWS S3, or Google Drive.", "Cloudflare R2, AWS S3 ve Google Drive'a askeri düzeyde AES-256-CBC şifrelemeli otomatik felaket yedekleme.")) ?>
			</p>
		</div>
		<div>
			<button type="button" class="button button-secondary" onclick="toggleConfigPanel()">
				<i class="fas fa-gear icon-orange u-mr5"></i><?= tohtml(__tr("Configure Cloud Settings", "Bulut Ayarlarını Düzenle")) ?>
			</button>
		</div>
	</div>

	<?php show_alert_message($_SESSION); ?>

	<!-- Overview KPI Cards -->
	<div class="u-grid u-grid-cols-4 u-mb20" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px;">
		
		<!-- Provider Card -->
		<div class="card" style="padding: 16px 20px; border-radius: 8px; background: var(--bg-card, #1e293b); border: 1px solid var(--border-color, #334155);">
			<div class="u-text-muted u-mb5" style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #94a3b8;">
				<?= tohtml(__tr("Storage Provider", "Depolama Sağlayıcı")) ?>
			</div>
			<div style="display: flex; align-items: center; justify-content: space-between;">
				<div style="font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
					<?php if ($cloud_settings['PROVIDER'] === 'r2'): ?>
						<i class="fab fa-cloudflare" style="color: #f38020; font-size: 1.3rem;"></i> Cloudflare R2
					<?php elseif ($cloud_settings['PROVIDER'] === 's3'): ?>
						<i class="fab fa-aws" style="color: #ff9900; font-size: 1.3rem;"></i> Amazon S3
					<?php elseif ($cloud_settings['PROVIDER'] === 'gdrive'): ?>
						<i class="fab fa-google-drive" style="color: #4285f4; font-size: 1.3rem;"></i> Google Drive
					<?php else: ?>
						<i class="fas fa-server icon-blue"></i> Custom S3
					<?php endif; ?>
				</div>
				<span class="badge badge-info" style="font-size: 10px;"><?= tohtml(strtoupper($cloud_settings['STATUS'] ?? 'READY')) ?></span>
			</div>
			<div class="u-text-muted u-mt10" style="font-size: 12px;">
				Bucket: <strong><?= tohtml($cloud_settings['BUCKET'] ?: 'nexvia-backups') ?></strong>
			</div>
		</div>

		<!-- Security Card -->
		<div class="card" style="padding: 16px 20px; border-radius: 8px; background: var(--bg-card, #1e293b); border: 1px solid var(--border-color, #334155);">
			<div class="u-text-muted u-mb5" style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #94a3b8;">
				<?= tohtml(__tr("Encryption Security", "Şifreleme Güvenliği")) ?>
			</div>
			<div style="display: flex; align-items: center; justify-content: space-between;">
				<div style="font-size: 1.1rem; font-weight: 700; color: #22c55e; display: flex; align-items: center; gap: 8px;">
					<i class="fas fa-shield-halved"></i> AES-256-CBC
				</div>
				<span class="badge badge-success" style="font-size: 10px;"><?= tohtml(__tr("Zero-Knowledge", "Sıfır Bilgi")) ?></span>
			</div>
			<div class="u-text-muted u-mt10" style="font-size: 12px;">
				<?= tohtml(__tr("PBKDF2 100k Iterations", "100k İterasyon PBKDF2")) ?>
			</div>
		</div>

		<!-- Last Sync Card -->
		<div class="card" style="padding: 16px 20px; border-radius: 8px; background: var(--bg-card, #1e293b); border: 1px solid var(--border-color, #334155);">
			<div class="u-text-muted u-mb5" style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #94a3b8;">
				<?= tohtml(__tr("Last Cloud Sync", "Son Bulut Eşitlemesi")) ?>
			</div>
			<div style="font-size: 1.05rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
				<i class="fas fa-clock-rotate-left icon-purple"></i>
				<?= !empty($cloud_settings['LAST_SYNC']) ? tohtml($cloud_settings['LAST_SYNC']) : tohtml(__tr("Never", "Hiç Yapılmadı")) ?>
			</div>
			<div class="u-text-muted u-mt10" style="font-size: 12px;">
				<?= tohtml(__tr("Schedule:", "Zamanlama:")) ?> <strong><?= tohtml(ucfirst($cloud_settings['AUTO_SYNC'] ?? 'Daily')) ?></strong>
			</div>
		</div>

		<!-- Backups Count Card -->
		<div class="card" style="padding: 16px 20px; border-radius: 8px; background: var(--bg-card, #1e293b); border: 1px solid var(--border-color, #334155);">
			<div class="u-text-muted u-mb5" style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #94a3b8;">
				<?= tohtml(__tr("Stored Copies", "Kayıtlı Kopyalar")) ?>
			</div>
			<div style="font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
				<i class="fas fa-cloud icon-blue"></i>
				<span><?= count($cloud_backups) ?> <?= tohtml(__tr("Cloud", "Bulut")) ?></span>
				<span class="u-text-muted" style="font-weight: 400; font-size: 13px;">/ <?= count($local_backups) ?> <?= tohtml(__tr("Local", "Yerel")) ?></span>
			</div>
			<div class="u-text-muted u-mt10" style="font-size: 12px;">
				<?= tohtml(__tr("Retention Limit:", "Saklama Sınırı:")) ?> <strong><?= tohtml($cloud_settings['RETENTION_COUNT'] ?? 14) ?> <?= tohtml(__tr("versions", "sürüm")) ?></strong>
			</div>
		</div>

	</div>

	<!-- Cloud Configuration Form Panel -->
	<div id="cloud-config-panel" class="card u-mb25" style="padding: 24px; border-radius: 8px; background: var(--bg-card, #1e293b); border: 1px solid var(--border-color, #334155); display: <?= empty($cloud_settings['ACCESS_KEY']) ? 'block' : 'none' ?>;">
		<div class="u-flex u-justify-between u-items-center u-mb20" style="border-bottom: 1px solid var(--border-color, #334155); padding-bottom: 12px;">
			<h2 style="font-size: 1.2rem; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 8px;">
				<i class="fas fa-sliders icon-blue"></i>
				<?= tohtml(__tr("Cloud Storage & AES-256 Encryption Settings", "Bulut Depolama & AES-256 Şifreleme Yapılandırması")) ?>
			</h2>
			<button type="button" class="button button-secondary button-small" onclick="toggleConfigPanel()">
				<i class="fas fa-times"></i>
			</button>
		</div>

		<form action="/list/cloud-backup/" method="post">
			<input type="hidden" name="token" value="<?= tohtml($_SESSION['token']) ?>">
			<input type="hidden" name="save_settings" value="1">

			<!-- Provider Selector -->
			<div class="u-mb20">
				<label class="form-label u-mb10 u-text-bold"><?= tohtml(__tr("Select Cloud Storage Provider", "Bulut Depolama Sağlayıcısını Seçin")) ?></label>
				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
					
					<!-- Cloudflare R2 -->
					<label class="card" style="padding: 14px; border-radius: 6px; cursor: pointer; border: 2px solid <?= $cloud_settings['PROVIDER'] === 'r2' ? '#38bdf8' : 'var(--border-color, #334155)' ?>; display: flex; align-items: center; gap: 10px;">
						<input type="radio" name="provider" value="r2" <?= $cloud_settings['PROVIDER'] === 'r2' ? 'checked' : '' ?> onchange="updateProviderFields('r2')">
						<div>
							<strong style="display: block; font-size: 13px;"><i class="fab fa-cloudflare" style="color: #f38020;"></i> Cloudflare R2</strong>
							<span style="font-size: 11px; color: #94a3b8;"><?= tohtml(__tr("Zero Egress Fees, High Performance S3", "Sıfır Çıkış Ücreti, Hızlı S3")) ?></span>
						</div>
					</label>

					<!-- Amazon AWS S3 -->
					<label class="card" style="padding: 14px; border-radius: 6px; cursor: pointer; border: 2px solid <?= $cloud_settings['PROVIDER'] === 's3' ? '#38bdf8' : 'var(--border-color, #334155)' ?>; display: flex; align-items: center; gap: 10px;">
						<input type="radio" name="provider" value="s3" <?= $cloud_settings['PROVIDER'] === 's3' ? 'checked' : '' ?> onchange="updateProviderFields('s3')">
						<div>
							<strong style="display: block; font-size: 13px;"><i class="fab fa-aws" style="color: #ff9900;"></i> Amazon AWS S3</strong>
							<span style="font-size: 11px; color: #94a3b8;"><?= tohtml(__tr("Industry Standard Object Storage", "Endüstri Standardı S3")) ?></span>
						</div>
					</label>

					<!-- Google Drive -->
					<label class="card" style="padding: 14px; border-radius: 6px; cursor: pointer; border: 2px solid <?= $cloud_settings['PROVIDER'] === 'gdrive' ? '#38bdf8' : 'var(--border-color, #334155)' ?>; display: flex; align-items: center; gap: 10px;">
						<input type="radio" name="provider" value="gdrive" <?= $cloud_settings['PROVIDER'] === 'gdrive' ? 'checked' : '' ?> onchange="updateProviderFields('gdrive')">
						<div>
							<strong style="display: block; font-size: 13px;"><i class="fab fa-google-drive" style="color: #4285f4;"></i> Google Drive</strong>
							<span style="font-size: 11px; color: #94a3b8;"><?= tohtml(__tr("Personal & Workspace Drive", "Bireysel / Kurumsal Drive")) ?></span>
						</div>
					</label>

					<!-- Custom S3 -->
					<label class="card" style="padding: 14px; border-radius: 6px; cursor: pointer; border: 2px solid <?= $cloud_settings['PROVIDER'] === 'custom' ? '#38bdf8' : 'var(--border-color, #334155)' ?>; display: flex; align-items: center; gap: 10px;">
						<input type="radio" name="provider" value="custom" <?= $cloud_settings['PROVIDER'] === 'custom' ? 'checked' : '' ?> onchange="updateProviderFields('custom')">
						<div>
							<strong style="display: block; font-size: 13px;"><i class="fas fa-server icon-blue"></i> Custom S3 / MinIO</strong>
							<span style="font-size: 11px; color: #94a3b8;"><?= tohtml(__tr("Wasabi, Backblaze B2, Self-Hosted", "Wasabi, B2, MinIO Storage")) ?></span>
						</div>
					</label>

				</div>
			</div>

			<!-- Credentials Grid -->
			<div class="u-grid u-grid-cols-2 u-mb20" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
				
				<!-- Bucket Name -->
				<div>
					<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("Bucket / Container Name", "Bucket / Klasör Adı")) ?> *</label>
					<input type="text" class="form-control" name="bucket" value="<?= tohtml($cloud_settings['BUCKET'] ?: 'nexvia-backups') ?>" required placeholder="nexvia-backups">
				</div>

				<!-- Account ID / Endpoint -->
				<div id="field-account-id">
					<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("Cloudflare Account ID / Custom Endpoint", "Cloudflare Account ID / Özel Endpoint")) ?></label>
					<input type="text" class="form-control" name="account_id" value="<?= tohtml($cloud_settings['ACCOUNT_ID']) ?>" placeholder="e.g. 7f938a192b8d...">
				</div>

				<!-- Access Key ID -->
				<div>
					<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("Access Key ID / Client ID", "Access Key ID / Client ID")) ?> *</label>
					<input type="text" class="form-control" name="access_key" value="<?= tohtml($cloud_settings['ACCESS_KEY']) ?>" required placeholder="AKIA... or R2 Access Key">
				</div>

				<!-- Secret Access Key -->
				<div>
					<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("Secret Access Key / Token", "Secret Access Key / Token")) ?> *</label>
					<input type="password" class="form-control" name="secret_key" value="<?= tohtml($cloud_settings['SECRET_KEY']) ?>" required placeholder="••••••••••••••••••••••••">
				</div>

				<!-- Custom Endpoint URL (optional) -->
				<div id="field-endpoint" style="grid-column: 1 / -1;">
					<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("Custom S3 Endpoint URL (Optional)", "Özel S3 Endpoint URL'si (İsteğe Bağlı)")) ?></label>
					<input type="text" class="form-control" name="endpoint" value="<?= tohtml($cloud_settings['ENDPOINT']) ?>" placeholder="https://<account_id>.r2.cloudflarestorage.com or https://s3.wasabisys.com">
				</div>

			</div>

			<!-- Zero-Knowledge AES-256 Encryption Section -->
			<div class="card u-mb20" style="padding: 16px; border-radius: 6px; background: rgba(34, 197, 94, 0.05); border: 1px solid rgba(34, 197, 94, 0.2);">
				<div class="u-flex u-items-center u-justify-between u-mb10">
					<div style="display: flex; align-items: center; gap: 8px;">
						<i class="fas fa-lock" style="color: #22c55e; font-size: 1.1rem;"></i>
						<strong style="font-size: 13.5px;"><?= tohtml(__tr("Zero-Knowledge Military-Grade Encryption (AES-256-CBC)", "Sıfır Bilgi Askeri Düzeyde Şifreleme (AES-256-CBC)")) ?></strong>
					</div>
					<label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 12px; font-weight: 600;">
						<input type="checkbox" name="encryption_enabled" value="yes" <?= $cloud_settings['ENCRYPTION_ENABLED'] === 'yes' ? 'checked' : '' ?>>
						<?= tohtml(__tr("Enable Encryption", "Şifrelemeyi Etkinleştir")) ?>
					</label>
				</div>
				<p style="font-size: 12px; color: #94a3b8; line-height: 1.5; margin-bottom: 12px;">
					<?= tohtml(__tr("When enabled, all backup tarballs are encrypted with OpenSSL AES-256-CBC and PBKDF2 (100,000 iterations) directly on your server BEFORE being transferred to the cloud. Even cloud providers cannot inspect your files or databases.", "Etkinleştirildiğinde, tüm yedekler buluta aktarılmadan ÖNCE sunucunuzda OpenSSL AES-256-CBC ve PBKDF2 (100.000 iterasyon) ile şifrelenir. Bulut sağlayıcılar dahil hiç kimse dosyalarınızı veya veritabanınızı okuyamaz.")) ?>
				</p>
				<div>
					<label class="form-label u-mb5 u-text-bold" style="font-size: 12px;"><?= tohtml(__tr("Master Encryption Passphrase", "Ana Şifreleme Parolası (Master Key)")) ?></label>
					<div style="display: flex; gap: 8px;">
						<input type="password" id="enc-key-input" class="form-control" name="encryption_key" value="<?= tohtml($cloud_settings['ENCRYPTION_KEY']) ?>" placeholder="<?= tohtml(__tr("Enter strong secret master key", "Güçlü bir ana şifreleme parolası girin")) ?>">
						<button type="button" class="button button-secondary" onclick="togglePassVisibility('enc-key-input')" title="<?= tohtml(__tr("Show/Hide", "Göster/Gizle")) ?>">
							<i class="fas fa-eye"></i>
						</button>
						<button type="button" class="button button-secondary" onclick="generateRandomPass('enc-key-input')" title="<?= tohtml(__tr("Generate Secure Key", "Güvenli Anahtar Üret")) ?>">
							<i class="fas fa-dice"></i> <?= tohtml(__tr("Generate", "Üret")) ?>
						</button>
					</div>
				</div>
			</div>

			<!-- Retention & Schedule Grid -->
			<div class="u-grid u-grid-cols-2 u-mb20" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
				<div>
					<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("Automated Sync Frequency", "Otomatik Eşitleme Sıklığı")) ?></label>
					<select class="form-select" name="auto_sync">
						<option value="daily" <?= ($cloud_settings['AUTO_SYNC'] ?? '') === 'daily' ? 'selected' : '' ?>><?= tohtml(__tr("Daily (Every Night at 03:00)", "Günlük (Her Gece 03:00)")) ?></option>
						<option value="weekly" <?= ($cloud_settings['AUTO_SYNC'] ?? '') === 'weekly' ? 'selected' : '' ?>><?= tohtml(__tr("Weekly (Every Sunday)", "Haftalık (Pazar Günleri)")) ?></option>
						<option value="monthly" <?= ($cloud_settings['AUTO_SYNC'] ?? '') === 'monthly' ? 'selected' : '' ?>><?= tohtml(__tr("Monthly (1st of month)", "Aylık (Her Ayın 1'i)")) ?></option>
						<option value="manual" <?= ($cloud_settings['AUTO_SYNC'] ?? '') === 'manual' ? 'selected' : '' ?>><?= tohtml(__tr("Manual Only (Trigger on demand)", "Yalnızca Manuel (İsteğe Bağlı)")) ?></option>
					</select>
				</div>

				<div>
					<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("Cloud Retention Count (Versions to Keep)", "Bulut Saklama Sayısı (Tutulacak Sürüm)")) ?></label>
					<input type="number" class="form-control" name="retention_count" min="1" max="365" value="<?= tohtml($cloud_settings['RETENTION_COUNT'] ?? 14) ?>" placeholder="14">
				</div>
			</div>

			<!-- Form Submit Action -->
			<div class="u-flex u-justify-end" style="gap: 10px;">
				<button type="button" class="button button-secondary" onclick="toggleConfigPanel()">
					<?= tohtml(__tr("Cancel", "İptal")) ?>
				</button>
				<button type="submit" class="button button-primary">
					<i class="fas fa-floppy-disk"></i> <?= tohtml(__tr("Save Cloud Settings", "Bulut Ayarlarını Kaydet")) ?>
				</button>
			</div>

		</form>
	</div>

	<!-- Remote Cloud Backups Table -->
	<div class="units-table js-units-container">
		<div class="units-table-header">
			<div class="units-table-cell"><?= tohtml(__tr("Cloud Backup Archive", "Bulut Yedek Arşivi")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml(__tr("Storage Provider", "Sağlayıcı")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml(__tr("Size", "Boyut")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml(__tr("Encryption", "Şifreleme")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml(__tr("Timestamp", "Tarih")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml(__tr("Actions", "İşlemler")) ?></div>
		</div>

		<?php if (empty($cloud_backups)): ?>
			<div class="card" style="padding: 40px 20px; text-align: center; border-radius: 0 0 6px 6px;">
				<i class="fas fa-cloud-arrow-up fa-3x u-mb15" style="color: var(--icon-color-blue, #38bdf8); opacity: 0.7;"></i>
				<h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 8px;">
					<?= tohtml(__tr("No cloud backups found in bucket", "Bulut alanında henüz kayıtlı yedek bulunmuyor")) ?>
				</h3>
				<p class="u-text-muted" style="max-width: 480px; margin: 0 auto 16px auto; font-size: 13px;">
					<?= tohtml(__tr("Click 'Sync to Cloud' or 'Create & Sync Full Backup' above to upload your first encrypted off-site backup archive.", "İlk şifreli felaket yedeğinizi buluta aktarmak için yukarıdaki 'Buluta Senkronize Et' veya 'Yedek Al ve Buluta Yükle' butonuna tıklayın.")) ?>
				</p>
				<a href="/list/cloud-backup/?sync_now=1&token=<?= tohtml($_SESSION['token']) ?>" class="button button-primary">
					<i class="fas fa-cloud-arrow-up"></i> <?= tohtml(__tr("Run First Sync Now", "İlk Eşitlemeyi Başlat")) ?>
				</a>
			</div>
		<?php else: ?>
			<?php foreach ($cloud_backups as $cb): 
				$cb_name = $cb['Name'] ?? ($cb['Path'] ?? 'backup.tar.aes');
				$cb_size = !empty($cb['Size']) ? humanize_usage_size(round($cb['Size'] / 1024 / 1024)) . ' MB' : '-';
				$cb_date = $cb['ModTime'] ?? date('Y-m-d H:i:s');
				$is_enc = (!empty($cb['Encrypted']) || strpos($cb_name, '.aes') !== false || strpos($cb_name, '.enc') !== false);
			?>
				<div class="units-table-row">
					<div class="units-table-cell units-table-heading-cell u-text-bold">
						<i class="fas fa-file-zipper icon-blue u-mr5"></i>
						<span><?= tohtml($cb_name) ?></span>
					</div>

					<div class="units-table-cell u-text-center">
						<span class="badge badge-secondary" style="font-size: 11px;">
							<i class="fab fa-cloudflare" style="color: #f38020;"></i> <?= tohtml($cb['Provider'] ?? $cloud_settings['PROVIDER']) ?>
						</span>
					</div>

					<div class="units-table-cell u-text-center">
						<strong><?= tohtml($cb_size) ?></strong>
					</div>

					<div class="units-table-cell u-text-center">
						<?php if ($is_enc): ?>
							<span class="badge badge-success" style="font-size: 11px;">
								<i class="fas fa-lock u-mr5"></i>AES-256
							</span>
						<?php else: ?>
							<span class="badge badge-secondary" style="font-size: 11px;">
								<i class="fas fa-unlock u-mr5"></i>Plain
							</span>
						<?php endif; ?>
					</div>

					<div class="units-table-cell u-text-center u-text-muted" style="font-size: 12px;">
						<?= tohtml($cb_date) ?>
					</div>

					<div class="units-table-cell u-text-center">
						<a href="/list/cloud-backup/?restore_file=<?= urlencode($cb_name) ?>&token=<?= tohtml($_SESSION['token']) ?>" class="button button-secondary button-small" title="<?= tohtml(__tr("Download and restore to local server", "İndir ve sunucuya geri yükle")) ?>" onclick="return confirm('<?= tohtml(__tr("Restore this backup to local storage and decrypt it?", "Bu yedeği yerel sunucuya indirip şifresini çözmek istiyor musunuz?")) ?>');">
							<i class="fas fa-cloud-arrow-down icon-green"></i> <?= tohtml(__tr("Restore", "Geri Yükle")) ?>
						</a>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>

	</div>

</div>

<script>
function toggleConfigPanel() {
	const panel = document.getElementById('cloud-config-panel');
	if (panel.style.display === 'none' || panel.style.display === '') {
		panel.style.display = 'block';
		panel.scrollIntoView({ behavior: 'smooth' });
	} else {
		panel.style.display = 'none';
	}
}

function updateProviderFields(provider) {
	const endpointDiv = document.getElementById('field-endpoint');
	const accountDiv = document.getElementById('field-account-id');
	if (provider === 'r2') {
		accountDiv.style.display = 'block';
	} else if (provider === 's3') {
		accountDiv.style.display = 'none';
	}
}

function togglePassVisibility(inputId) {
	const input = document.getElementById(inputId);
	if (input.type === 'password') {
		input.type = 'text';
	} else {
		input.type = 'password';
	}
}

function generateRandomPass(inputId) {
	const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+-=';
	let pass = '';
	for (let i = 0; i < 32; i++) {
		pass += chars.charAt(Math.floor(Math.random() * chars.length));
	}
	const input = document.getElementById(inputId);
	input.value = pass;
	input.type = 'text';
}
</script>
