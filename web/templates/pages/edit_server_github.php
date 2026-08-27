<?php
$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');
function __tr($en, $tr) {
    global $is_tr;
    return $is_tr ? $tr : _($en);
}
?>

<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a href="/edit/server/" class="button button-secondary" id="btn-back">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml(__tr("Back", "Geri")) ?>
			</a>
			<a href="/list/ip/" class="button button-secondary">
				<i class="fas fa-ethernet icon-blue"></i><?= tohtml(__tr("Network", "Ağ")) ?>
			</a>
			<a href="/edit/server/whitelabel/" class="button button-secondary">
				<i class="fas fa-paint-brush icon-blue"></i><?= tohtml(__tr("White Label", "Özelleştirme")) ?>
			</a>
		</div>
		<div class="toolbar-buttons">
			<button type="submit" class="button" form="main-form">
				<i class="fas fa-floppy-disk icon-purple"></i><?= tohtml(__tr("Save", "Kaydet")) ?>
			</button>
		</div>
	</div>
</div>
<!-- End toolbar -->

<!-- Begin form -->
<div class="container">
	<form id="main-form" name="v_configure_github" method="post">
		<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
		<input type="hidden" name="save" value="save">

		<div class="form-container">
			<h1 class="u-mb20">
				<i class="fab fa-github u-mr10"></i><?= tohtml(__tr("GitHub & CI/CD Integration", "GitHub & CI/CD Entegrasyonu")) ?>
			</h1>
			<?php show_alert_message($_SESSION); ?>

			<!-- Connection Status Banner -->
			<?php if ($gh_status === "connected"): ?>
				<div class="card u-mb20" style="padding: 15px 20px; background: rgba(16, 185, 129, 0.1); border-left: 4px solid var(--icon-color-green, #10b981); border-radius: 4px;">
					<div style="display:flex; align-items:center; gap: 10px;">
						<i class="fas fa-circle-check" style="color: var(--icon-color-green, #10b981); font-size: 1.2rem;"></i>
						<div>
							<strong style="color: var(--icon-color-green, #10b981);"><?= tohtml(__tr("GitHub Connected Successfully", "GitHub Bağlantısı Başarılı")) ?></strong>
							<p class="u-text-muted" style="margin: 3px 0 0 0; font-size: 0.85rem;">
								<?= tohtml(__tr("Connected Organization: ", "Bağlı Organizasyon: ")) ?> <strong><?= tohtml($v_github_org) ?></strong> &bull; <?= count($gh_repos) ?> <?= tohtml(__tr("repositories found.", "depo bulundu.")) ?>
							</p>
						</div>
					</div>
				</div>
			<?php elseif ($gh_status === "error"): ?>
				<div class="card u-mb20" style="padding: 15px 20px; background: rgba(239, 68, 68, 0.1); border-left: 4px solid var(--icon-color-red, #ef4444); border-radius: 4px;">
					<div style="display:flex; align-items:center; gap: 10px;">
						<i class="fas fa-circle-exmark" style="color: var(--icon-color-red, #ef4444); font-size: 1.2rem;"></i>
						<div>
							<strong style="color: var(--icon-color-red, #ef4444);"><?= tohtml(__tr("GitHub Connection Failed", "GitHub Bağlantısı Kurulamadı")) ?></strong>
							<p class="u-text-muted" style="margin: 3px 0 0 0; font-size: 0.85rem;">
								<?= tohtml(__tr("Please verify your GitHub Personal Access Token (PAT) permissions and organization name.", "Lütfen GitHub Token (PAT) yetkilerinizi ve organizasyon adını kontrol ediniz.")) ?>
								<?php if (!empty($gh_error_detail)): ?>
									<br><code style="color: var(--icon-color-red); font-size: 11px;"><?= tohtml($gh_error_detail) ?></code>
								<?php endif; ?>
							</p>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<!-- GitHub Credentials Section -->
			<details class="box-collapse u-mb20" open>
				<summary class="box-collapse-header">
					<i class="fas fa-key u-mr10"></i><?= tohtml(__tr("GitHub Account & Token", "GitHub Hesap ve Erişim Belirteci")) ?>
				</summary>
				<div class="box-collapse-content">
					<div class="u-mb15">
						<label for="v_github_org" class="form-label u-text-bold">
							<?= tohtml(__tr("GitHub Organization or Username", "GitHub Organizasyonu veya Kullanıcı Adı")) ?>
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
							<?= tohtml(__tr("Private and public repositories from this organization or user will be available for 1-click deployment across all web domains & APIs.", "Bu organizasyon altındaki repolar tüm PHP, Node.js, React, Django/Python, .NET siteleri ve API'ler için tek tıkla kurulabilir olacaktır.")) ?>
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
							value="<?= tohtml($v_github_token) ?>"
							placeholder="github_pat_xxxx..."
							required
						>
						<small class="u-text-muted" style="display:block; margin-top:4px;">
							<?= tohtml(__tr("Generate a token on GitHub (Settings -> Developer Settings -> Personal Access Tokens) with 'Repository: Read-only' permissions.", "GitHub profilinizden (Settings -> Developer Settings -> Personal Access Tokens) 'Contents: Read' yetkisine sahip bir belirteç üretin.")) ?>
						</small>
					</div>
				</div>
			</details>

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
					<div class="card u-mb20" style="padding:15px; border: 1px solid var(--border-color, #334155); border-radius:6px; background: rgba(0,0,0,0.02);">
						<div style="display:grid; grid-template-columns: 1fr 1fr auto; gap: 10px; align-items: flex-end;">
							<div>
								<label class="form-label u-mb5 u-text-bold" style="font-size:12px;"><?= tohtml(__tr("Secret Key Name", "Anahtar Adı (KEY)")) ?></label>
								<input type="text" form="vault-form" name="vault_key" placeholder="GEMINI_API_KEY" required class="form-control" style="font-family:monospace; text-transform:uppercase;">
							</div>
							<div>
								<label class="form-label u-mb5 u-text-bold" style="font-size:12px;"><?= tohtml(__tr("Secret Value (Write-Only)", "Gizli Değer (Yalnızca Yazılabilir)")) ?></label>
								<input type="password" form="vault-form" name="vault_value" placeholder="••••••••••••••••••••" required class="form-control">
							</div>
							<div>
								<button type="submit" form="vault-form" class="button button-secondary">
									<i class="fas fa-plus icon-green"></i> <?= tohtml(__tr("Save Secret", "Gizli Anahtarı Ekle")) ?>
								</button>
							</div>
						</div>
					</div>

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
									<div class="units-table-cell u-text-center">
										<code style="letter-spacing:2px; opacity:0.7;">••••••••••••••••</code>
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
							<button type="button" class="button button-secondary" onclick="navigator.clipboard.writeText(document.getElementById('webhook-url-input').value); alert('Webhook URL kopyalandı!');">
								<i class="fas fa-copy"></i> <?= tohtml(__tr("Copy", "Kopyala")) ?>
							</button>
						</div>
						<div class="u-mt10" style="font-size: 0.85rem; color: var(--color-text);">
							<strong>GitHub Yapılandırması:</strong> Content type: <code>application/json</code> &bull; Events: <code>Pushes</code> veya <code>Releases</code>
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
	</form>
	<form id="vault-form" method="post" action="/edit/server/github/">
		<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
		<input type="hidden" name="save_vault" value="1">
	</form>
</div>
