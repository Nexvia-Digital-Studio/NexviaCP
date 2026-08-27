<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/server/">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml(__tr("Back", "Geri")) ?>
			</a>
			<button type="submit" form="main-form" class="button button-primary">
				<i class="fas fa-floppy-disk"></i> <?= tohtml(__tr("Save GitHub Settings", "GitHub Ayarlarını Kaydet")) ?>
			</button>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">
	<h1 class="u-text-center u-hide-desktop u-mt20 u-pr30 u-mb20 u-pl30">
		<?= tohtml(__tr("GitHub & CI/CD Integration", "GitHub & CI/CD Entegrasyonu")) ?>
	</h1>

	<?php show_alert_message($_SESSION); ?>

	<!-- Status Card -->
	<div class="card u-mb20" style="padding: 20px; border-radius: 8px; border: 1px solid var(--border-color, #334155); background: var(--color-background, #fff);">
		<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
			<div style="display:flex; align-items:center; gap:12px;">
				<div style="width:40px; height:40px; border-radius:8px; background: rgba(56, 189, 248, 0.1); display:flex; align-items:center; justify-content:center;">
					<i class="fab fa-github fa-2x" style="color: var(--icon-color-blue, #38bdf8);"></i>
				</div>
				<div>
					<h3 style="margin:0; font-size:1.1rem; font-weight:bold;">
						<?= tohtml(__tr("GitHub Organization / User Account", "GitHub Organizasyon / Kullanıcı Hesabı")) ?>
					</h3>
					<small class="u-text-muted">
						<?= !empty($v_github_org) ? "Bağlı Organizasyon: <strong>" . tohtml($v_github_org) . "</strong>" : __tr("Not Connected", "Bağlantı Kurulmadı") ?>
					</small>
				</div>
			</div>
			<div>
				<?php if ($is_connected): ?>
					<span class="badge badge-success" style="padding:6px 12px; font-size:12px;">
						<i class="fas fa-circle-check"></i> <?= tohtml(__tr("GitHub Connected (Active)", "GitHub Bağlantısı Aktif")) ?>
					</span>
				<?php else: ?>
					<span class="badge badge-secondary" style="padding:6px 12px; font-size:12px;">
						<i class="fas fa-circle-xmark"></i> <?= tohtml(__tr("Disconnected", "Bağlantı Yok")) ?>
					</span>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- Form: GitHub Integration Settings -->
	<form id="main-form" method="post" action="/edit/server/github/">
		<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
		<input type="hidden" name="save" value="1">

		<div class="form-container" style="max-width: 100%;">
			<!-- GitHub Credentials Section -->
			<details class="box-collapse u-mb20" open>
				<summary class="box-collapse-header">
					<i class="fas fa-key u-mr10"></i><?= tohtml(__tr("GitHub Account Credentials", "GitHub Hesap ve Erişim Bilgileri")) ?>
				</summary>
				<div class="box-collapse-content">
					<div class="u-mb15">
						<label for="v_github_org" class="form-label u-text-bold">
							<?= tohtml(__tr("Organization or Username", "Organizasyon veya Kullanıcı Adı")) ?>
						</label>
						<input
							type="text"
							class="form-control"
							name="v_github_org"
							id="v_github_org"
							value="<?= tohtml($v_github_org) ?>"
							placeholder="Nexvia-Digital-Studio"
							required
						>
						<small class="u-text-muted" style="display:block; margin-top:4px;">
							<?= tohtml(__tr("Your GitHub organization name (e.g. Nexvia-Digital-Studio) or personal username.", "Projelerinizin bulunduğu GitHub organizasyonu veya kullanıcı adınız.")) ?>
						</small>
					</div>

					<div class="u-mb15">
						<label for="v_github_token" class="form-label u-text-bold">
							<?= tohtml(__tr("Fine-Grained Personal Access Token (PAT)", "Kişisel Erişim Belirteci (Token / PAT)")) ?>
						</label>
						<input
							type="password"
							class="form-control"
							name="v_github_token"
							id="v_github_token"
							value="<?= !empty($v_github_token) ? '••••••••••••••••••••••••••••••••' : '' ?>"
							placeholder="github_pat_xxxx..."
							required
						>
						<small class="u-text-muted" style="display:block; margin-top:4px;">
							<?= tohtml(__tr("Generate a token on GitHub (Settings -> Developer Settings -> Personal Access Tokens) with 'Repository: Read-only' permissions.", "GitHub profilinizden (Settings -> Developer Settings -> Personal Access Tokens) 'Contents: Read' yetkisine sahip bir belirteç üretin.")) ?>
						</small>
					</div>
				</div>
			</details>
		</div>
	</form>

	<!-- Global Key Vault Section (Zero-Knowledge Secret Management) -->
	<details class="box-collapse u-mb20" open>
		<summary class="box-collapse-header">
			<i class="fas fa-shield-halved u-mr10"></i><?= tohtml(__tr("Global Key Vault (Auto-Injected Secrets)", "Global Key Vault (Otomatik Enjekte Edilen Gizli Anahtarlar)")) ?> (<?= count($global_vault) ?>)
		</summary>
		<div class="box-collapse-content">
			<p class="u-text-muted u-mb15" style="font-size:0.88rem; line-height:1.4;">
				🛡️ <strong><?= tohtml(__tr("Zero-Knowledge Security:", "Sıfır Bilgi (Zero-Knowledge) Güvenlik Modeli:")) ?></strong>
				<?= tohtml(__tr("Secrets defined here are write-only and cannot be read in plaintext by anyone (even administrators) via the UI or API. They are automatically injected into the .env file of all newly deployed websites and APIs.", "Buraya eklediğiniz API anahtarları (örn: GEMINI_API_KEY, GOOGLE_MAPS_KEY) sunucuda chmod 600 ile izole edilir. Güvenlik gereği arayüzde asla düz metin olarak okunamaz, yalnızca güncellenebilir veya silinebilir. Kurulan tüm sitelerin .env dosyasına otomatik enjekte edilir.")) ?>
			</p>

			<!-- Add New Global Secret Form -->
			<form method="post" action="/edit/server/github/">
				<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
				<input type="hidden" name="save_vault" value="1">
				<div class="card u-mb20" style="padding:15px; border: 1px solid var(--border-color, #334155); border-radius:6px; background: rgba(0,0,0,0.02);">
					<div style="display:flex; flex-wrap:wrap; gap: 10px; align-items: flex-end;">
						<div style="flex:1; min-width:200px;">
							<label class="form-label u-mb5 u-text-bold" style="font-size:12px;"><?= tohtml(__tr("Secret Key Name", "Anahtar Adı (KEY)")) ?></label>
							<input type="text" name="vault_key" placeholder="GEMINI_API_KEY" required class="form-control" style="font-family:monospace; text-transform:uppercase;">
						</div>
						<div style="flex:1; min-width:200px;">
							<label class="form-label u-mb5 u-text-bold" style="font-size:12px;"><?= tohtml(__tr("Secret Value (Write-Only)", "Gizli Değer (Yalnızca Yazılabilir)")) ?></label>
							<input type="password" name="vault_value" placeholder="••••••••••••••••••••" required class="form-control">
						</div>
						<div>
							<button type="submit" class="button button-secondary">
								<i class="fas fa-plus icon-green"></i> <?= tohtml(__tr("Save Secret", "Gizli Anahtarı Ekle")) ?>
							</button>
						</div>
					</div>
				</div>
			</form>

			<!-- Existing Global Secrets List -->
			<?php if (empty($global_vault)): ?>
				<p class="u-text-muted u-text-center" style="padding: 10px 0; font-size:0.9rem;">
					<?= tohtml(__tr("No global secrets configured yet.", "Henüz global bir secret tanımlanmadı.")) ?>
				</p>
			<?php else: ?>
				<div class="units-table" style="margin-top: 5px;">
					<div class="units-table-header">
						<div class="units-table-cell"><?= tohtml(__tr("Secret Key", "Anahtar Adı")) ?></div>
						<div class="units-table-cell u-text-center"><?= tohtml(__tr("Stored Value", "Kayıtlı Değer")) ?></div>
						<div class="units-table-cell u-text-center"><?= tohtml(__tr("Scope", "Kapsam")) ?></div>
						<div class="units-table-cell u-text-center"><?= tohtml(__tr("Action", "İşlem")) ?></div>
					</div>
					<?php foreach ($global_vault as $vname => $vdata): ?>
						<div class="units-table-row">
							<div class="units-table-cell units-table-heading-cell u-text-bold" style="font-family:monospace;">
								<i class="fas fa-key icon-yellow u-mr5"></i> <?= tohtml($vname) ?>
							</div>
							<div class="units-table-cell u-text-center" style="font-family:monospace; letter-spacing:2px; color: var(--color-text-muted);">
								••••••••••••••••
							</div>
							<div class="units-table-cell u-text-center">
								<span class="badge badge-info" style="font-size:11px; padding: 2px 6px;">
									<?= tohtml(__tr("All Sites & APIs", "Tüm Siteler & API'ler")) ?>
								</span>
							</div>
							<div class="units-table-cell u-text-center">
								<a class="button button-danger button-small" href="/edit/server/github/?delete_vault=1&key=<?= urlencode($vname) ?>&token=<?= tohtml($_SESSION["token"]) ?>" title="<?= tohtml(__tr("Delete Secret", "Secret'ı Sil")) ?>" onclick="return confirm('<?= tohtml(__tr("Are you sure you want to delete this secret?", "Bu secret anahtarını silmek istediğinize emin misiniz?")) ?>');" style="padding: 3px 8px; font-size:11px;">
									<i class="fas fa-trash-can"></i>
								</a>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</details>

	<!-- CI/CD Webhook Integration Section -->
	<details class="box-collapse u-mb20" open>
		<summary class="box-collapse-header">
			<i class="fas fa-bolt icon-yellow u-mr10"></i><?= tohtml(__tr("GitHub CI/CD Webhook (Zero-Downtime Auto Deploy)", "GitHub Otomatik Güncelleme Webhook'u (Sıfır Kesinti CI/CD)")) ?>
		</summary>
		<div class="box-collapse-content">
			<p class="u-text-muted u-mb15" style="font-size:0.88rem; line-height:1.4;">
				<?= tohtml(__tr("Add this Webhook URL to your GitHub repository (or organization) to automatically update all deployed websites and APIs whenever you push commits or publish releases.", "Bu Webhook URL'sini GitHub'da deponuza eklediğinizde, her yeni commit/push veya yeni Release yayınlandığında ona bağlı olan tüm siteler sunucu tarafından otomatik güncellenir.")) ?>
			</p>
			<?php
				$webhook_url = "https://" . ($_SERVER["HTTP_HOST"] ?? "panel.nexvia.test") . "/webhook/github/";
			?>
			<div class="card u-mb15" style="padding:15px; border: 1px solid var(--border-color, #334155); border-radius:6px; background: rgba(0,0,0,0.02);">
				<label class="form-label u-mb5 u-text-bold" style="font-size:12px;"><?= tohtml(__tr("Your Server Webhook Payload URL:", "Sunucu Webhook URL'niz (GitHub'a Eklenecek):")) ?></label>
				<div style="display:flex; gap:10px; align-items:center;">
					<input type="text" class="form-control" value="<?= tohtml($webhook_url) ?>" readonly id="webhook-url-input" style="font-family:monospace; font-weight:bold;">
					<button type="button" class="button button-secondary" onclick="navigator.clipboard.writeText(document.getElementById('webhook-url-input').value); alert('<?= tohtml(__tr("Webhook URL copied to clipboard!", "Webhook URL kopyalandı!")) ?>');">
						<i class="fas fa-copy"></i> <?= tohtml(__tr("Copy", "Kopyala")) ?>
					</button>
				</div>
				<div class="u-mt10" style="font-size: 0.85rem; color: var(--color-text);">
					<strong><?= tohtml(__tr("GitHub Configuration:", "GitHub Yapılandırması:")) ?></strong> Content type: <code>application/json</code> &bull; Events: <code>Pushes</code> <?= tohtml(__tr("or", "veya")) ?> <code>Releases</code>
				</div>
			</div>
		</div>
	</details>

	<!-- Repositories List Preview -->
	<?php if (!empty($gh_repos)): ?>
		<details class="box-collapse u-mb20" open>
			<summary class="box-collapse-header">
				<i class="fas fa-code-branch u-mr10"></i><?= tohtml(__tr("Available Repositories for Auto-Deployment", "Otomatik Dağıtıma Hazır Depolar")) ?> (<?= count($gh_repos) ?>)
			</summary>
			<div class="box-collapse-content">
				<div class="units-table" style="margin-top: 10px;">
					<div class="units-table-header">
						<div class="units-table-cell"><?= tohtml(__tr("Repository", "Depo Adı")) ?></div>
						<div class="units-table-cell u-text-center"><?= tohtml(__tr("Language / Stack", "Dil / Teknoloji")) ?></div>
						<div class="units-table-cell u-text-center"><?= tohtml(__tr("Branch", "Ana Dal")) ?></div>
						<div class="units-table-cell u-text-center"><?= tohtml(__tr("Access", "Erişim")) ?></div>
					</div>
					<?php foreach ($gh_repos as $rname => $rdata): ?>
						<div class="units-table-row">
							<div class="units-table-cell units-table-heading-cell u-text-bold">
								<i class="fab fa-github u-mr5"></i> <?= tohtml($rdata["NAME"] ?? $rname) ?>
							</div>
							<div class="units-table-cell u-text-center">
								<span class="badge badge-info" style="font-size:11px; padding: 2px 6px;">
									<?= tohtml($rdata["LANGUAGE"] ?? "App") ?>
								</span>
							</div>
							<div class="units-table-cell u-text-center">
								<code><?= tohtml($rdata["BRANCH"] ?? "main") ?></code>
							</div>
							<div class="units-table-cell u-text-center">
								<?= ($rdata["PRIVATE"] ?? "") === "yes" ? "🔒 " . tohtml(__tr("Private", "Özel")) : "🌐 " . tohtml(__tr("Public", "Açık")) ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</details>
	<?php endif; ?>
</div>
