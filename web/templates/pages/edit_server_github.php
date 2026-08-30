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
							value=""
							placeholder="<?= (!empty($v_github_token) || !empty($_SESSION["GITHUB_TOKEN_SET"])) ? tohtml(__tr("Token saved — enter a new one only to replace it", "Kayıtlı — yalnızca değiştirmek isterseniz yeni token girin")) : 'github_pat_xxxx...' ?>"
						>
						<small class="u-text-muted" style="display:block; margin-top:4px;">
							<?= tohtml(__tr("Leave empty to keep the current token. Generate a token on GitHub (Settings -> Developer Settings -> Personal Access Tokens) with 'Contents: Read' permissions.", "Mevcut token'ı korumak için boş bırakın. GitHub profilinizden (Settings -> Developer Settings -> Personal Access Tokens) 'Contents: Read' yetkisine sahip bir belirteç üretin.")) ?>
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
				<?= tohtml(__tr("Secrets defined here are root-isolated and write-only, managed in the secure server vault. They are never exposed in user .env files or readable by unprivileged accounts.", "Buraya eklediğiniz API anahtarları sunucuda root yetkisinde izole edilir ve panelde asla düz metin olarak okunamaz. Güvenlik gereği kullanıcıların .env dosyalarına sızdırılmaz, yalnızca sunucu ve yetkili servisler tarafından kullanılır.")) ?>
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
								<form method="post" action="/edit/server/github/" style="margin:0; display:inline;" onsubmit="return confirm('<?= tohtml(__tr("Are you sure you want to delete this secret?", "Bu secret anahtarını silmek istediğinize emin misiniz?")) ?>');">
									<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
									<input type="hidden" name="delete_vault" value="1">
									<input type="hidden" name="key" value="<?= tohtml($vname) ?>">
									<button type="submit" class="button button-danger button-small" title="<?= tohtml(__tr("Delete Secret", "Secret'ı Sil")) ?>" style="padding: 3px 8px; font-size:11px;">
										<i class="fas fa-trash-can"></i>
									</button>
								</form>
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
				<?= tohtml(__tr("Add this Webhook URL and Secret to your GitHub repository (Settings -> Webhooks) to automatically update and rebuild all deployed websites and APIs whenever you push commits.", "Bu Webhook URL ve Secret bilgilerini GitHub deponuza (Settings -> Webhooks) eklediğinizde, depoya her 'git push' yaptığınızda bağlı tüm siteler (PHP, Laravel, Node.js, .NET) sunucuda otomatik çekilir, derlenir ve sıfır kesintiyle güncellenir.")) ?>
			</p>
			<?php
				$webhook_url = "https://" . ($_SERVER["HTTP_HOST"] ?? "panel.nexvia.test") . "/webhook/github/";
			?>
			<div class="card u-mb15" style="padding:15px; border: 1px solid var(--border-color, #334155); border-radius:6px; background: rgba(0,0,0,0.02);">
				<!-- 1. Payload URL -->
				<div class="u-mb15">
					<label class="form-label u-mb5 u-text-bold" style="font-size:12px;"><?= tohtml(__tr("1. Payload URL (GitHub'a Eklenecek URL):", "1. Payload URL (GitHub'a Eklenecek URL):")) ?></label>
					<div style="display:flex; gap:10px; align-items:center;">
						<input type="text" class="form-control" value="<?= tohtml($webhook_url) ?>" readonly id="webhook-url-input" style="font-family:monospace; font-weight:bold;">
						<button type="button" class="button button-secondary" onclick="navigator.clipboard.writeText(document.getElementById('webhook-url-input').value); alert('<?= tohtml(__tr("Webhook URL copied to clipboard!", "Webhook URL kopyalandı!")) ?>');">
							<i class="fas fa-copy"></i> <?= tohtml(__tr("Copy", "Kopyala")) ?>
						</button>
					</div>
				</div>

				<!-- 2. Webhook Secret -->
				<div class="u-mb15">
					<label class="form-label u-mb5 u-text-bold" style="font-size:12px;"><?= tohtml(__tr("2. Secret (HMAC-SHA256 Güvenlik Anahtarı):", "2. Secret (HMAC-SHA256 Güvenlik Anahtarı):")) ?></label>
					<?php if (!empty($webhook_secret)): ?>
						<div style="display:flex; gap:10px; align-items:center;">
							<input type="text" class="form-control" value="<?= tohtml($webhook_secret) ?>" readonly id="webhook-secret-input" style="font-family:monospace; font-weight:bold;">
							<button type="button" class="button button-secondary" onclick="navigator.clipboard.writeText(document.getElementById('webhook-secret-input').value); alert('<?= tohtml(__tr("Webhook Secret copied to clipboard!", "Webhook Secret kopyalandı!")) ?>');">
								<i class="fas fa-copy"></i> <?= tohtml(__tr("Copy", "Kopyala")) ?>
							</button>
						</div>
					<?php else: ?>
						<form method="post" action="/edit/server/github/" style="margin:0;">
							<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
							<input type="hidden" name="save_webhook_secret" value="1">
							<input type="hidden" name="generate_random" value="1">
							<div style="display:flex; gap:10px; align-items:center;">
								<span class="badge badge-warning" style="padding:6px 10px; font-size:11px;">⚠️ <?= tohtml(__tr("Webhook secret not set yet", "Henüz secret tanımlanmadı")) ?></span>
								<button type="submit" class="button button-primary">
									<i class="fas fa-key"></i> <?= tohtml(__tr("Generate Secure Secret Now", "Otomatik Güvenli Secret Üret & Kaydet")) ?>
								</button>
							</div>
						</form>
					<?php endif; ?>
				</div>

				<!-- 3. Instructions -->
				<div class="u-mt10" style="font-size: 0.85rem; color: var(--color-text); line-height: 1.5; background: rgba(56, 189, 248, 0.06); padding: 10px; border-radius: 6px; border-left: 3px solid var(--icon-color-blue, #38bdf8);">
					<strong>📌 <?= tohtml(__tr("GitHub Webhook Setup Steps:", "GitHub Webhook Kurulum Adımları:")) ?></strong><br>
					1. GitHub'da deponuza gidin: <strong>Settings &rarr; Webhooks &rarr; Add webhook</strong><br>
					2. <strong>Payload URL:</strong> Yukarıdaki URL'yi yapıştırın.<br>
					3. <strong>Content type:</strong> <code>application/json</code> seçin.<br>
					4. <strong>Secret:</strong> Yukarıdaki Secret anahtarını yapıştırın.<br>
					5. <strong>Which events would you like to trigger this webhook?</strong> &rarr; <em>Just the push event</em> seçin ve <strong>Add webhook</strong> butonuna tıklayın.
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
