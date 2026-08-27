<?php
// Translation helper for Turkish & Multilingual support
$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');
function __t($en, $tr) {
    global $is_tr;
    return $is_tr ? $tr : _($en);
}
?>

<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<button type="button" class="button button-secondary js-button-create" onclick="document.getElementById('deploy-modal').style.display='flex'">
				<i class="fas fa-circle-plus icon-green"></i><?= tohtml(__t("Deploy Standalone API", "Yeni API Servisi Kur")) ?>
			</button>
			<a href="/edit/server/github/" class="button button-secondary">
				<i class="fab fa-github icon-blue"></i><?= tohtml(__t("GitHub & CI/CD Settings", "GitHub & CI/CD")) ?>
			</a>
		</div>
		<div class="toolbar-right">
			<div class="toolbar-search">
				<form action="/search/" method="get">
					<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
					<input type="search" class="form-control js-search-input" name="q" value="<?= tohtml($_GET['q'] ?? '') ?>" placeholder="<?= tohtml(__t("Search", "Ara...")) ?>">
					<button type="submit" class="toolbar-input-submit" title="<?= tohtml(__t("Search", "Ara")) ?>">
						<i class="fas fa-magnifying-glass"></i>
					</button>
				</form>
			</div>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">

	<h1 class="u-text-center u-hide-desktop u-mt20 u-pr30 u-mb20 u-pl30">
		<?= tohtml(__t("API & Backend Services", "API & Arka Plan Servisleri")) ?>
	</h1>

	<?php show_alert_message($_SESSION); ?>

	<?php if (empty($api_services)): ?>
		<!-- Empty state card -->
		<div class="card u-mt20" style="padding: 40px 20px; text-align: center; border-radius: 6px; border: 1px solid var(--border-color, #334155);">
			<div class="u-mb15">
				<i class="fas fa-server fa-3x" style="color: var(--icon-color-blue, #38bdf8); opacity: 0.8;"></i>
			</div>
			<h3 class="u-mb10" style="font-size: 1.1rem; font-weight: 600;">
				<?= tohtml(__t("No standalone API services or backend apps configured yet.", "Henüz yapılandırılmış bağımsız API servisi bulunmuyor.")) ?>
			</h3>
			<p class="u-text-muted u-mb20" style="max-width: 550px; margin-left: auto; margin-right: auto; line-height: 1.5;">
				<?= tohtml(__t("Deploy your .NET 8/9/10 Web API, Node.js, Express, Fastify, Python FastAPI or Go backend services with isolated dynamic ports (9100-9999), kernel cgroups resource limits, and Let's Encrypt SSL.", "Özel .NET 8/9/10 Web API, Node.js, Express, Fastify, Python FastAPI veya Go API servislerinizi izole portlar (9100-9999), cgroups kaynak sınırları ve SSL ile kolayca yayına alabilirsiniz.")) ?>
			</p>
			<button type="button" class="button button-primary" onclick="document.getElementById('deploy-modal').style.display='flex'">
				<i class="fas fa-circle-plus"></i> <?= tohtml(__t("Deploy New API from GitHub", "GitHub'dan Yeni API Kur")) ?>
			</button>
		</div>
	<?php else: ?>
		<!-- Units Table -->
		<div class="units-table js-units-container">
			<div class="units-table-header">
				<div class="units-table-cell">
					<input type="checkbox" class="js-toggle-all-checkbox" title="<?= tohtml(__t("Select all", "Tümünü seç")) ?>">
				</div>
				<div class="units-table-cell"><?= tohtml(__t("Domain / Endpoint", "Alan Adı / Endpoint")) ?></div>
				<div class="units-table-cell"></div>
				<div class="units-table-cell u-text-center"><?= tohtml(__t("User", "Kullanıcı")) ?></div>
				<div class="units-table-cell u-text-center"><?= tohtml(__t("Stack", "Teknoloji")) ?></div>
				<div class="units-table-cell u-text-center"><?= tohtml(__t("Internal Port", "Dahili Port")) ?></div>
				<div class="units-table-cell u-text-center"><?= tohtml(__t("Memory", "Bellek")) ?></div>
				<div class="units-table-cell u-text-center"><?= tohtml(__t("Status", "Durum")) ?></div>
			</div>

			<?php $i = 0; foreach ($api_services as $dName => $svc): ++$i; 
				$is_running = (($svc["STATUS"] ?? "") === "running");
			?>
				<div class="units-table-row js-unit <?= $is_running ? '' : 'disabled' ?>">
					<div class="units-table-cell">
						<div>
							<input id="check<?= $i ?>" class="js-unit-checkbox" type="checkbox" name="service[]" value="<?= tohtml($svc["DOMAIN"]) ?>">
							<label for="check<?= $i ?>" class="u-hide-desktop"><?= tohtml(__t("Select", "Seç")) ?></label>
						</div>
					</div>
					<div class="units-table-cell units-table-heading-cell u-text-bold">
						<span class="u-hide-desktop"><?= tohtml(__t("Domain", "Alan Adı")) ?>:</span>
						<i class="fas <?= $is_running ? 'fa-circle-check icon-green' : 'fa-circle-xmark icon-red' ?> u-mr5"></i>
						<a class="unit-link" href="https://<?= tohtml($svc["DOMAIN"]) ?>" target="_blank" rel="noopener">
							https://<?= tohtml($svc["DOMAIN"]) ?> <i class="fas fa-arrow-up-right-from-square" style="font-size:11px;"></i>
						</a>
					</div>
					<div class="units-table-cell">
						<ul class="units-table-row-actions">
							<li class="units-table-row-action">
								<a class="units-table-row-action-link" href="/list/api/?restart=1&user=<?= urlencode($svc["USER"]) ?>&domain=<?= urlencode($svc["DOMAIN"]) ?>&token=<?= tohtml($_SESSION["token"]) ?>" title="<?= tohtml(__t("Restart", "Yeniden Başlat")) ?>">
									<i class="fas fa-rotate-right icon-blue"></i>
									<span class="u-hide-desktop"><?= tohtml(__t("Restart", "Yeniden Başlat")) ?></span>
								</a>
							</li>
						</ul>
					</div>
					<div class="units-table-cell u-text-bold u-text-center-desktop">
						<span class="u-hide-desktop"><?= tohtml(__t("User", "Kullanıcı")) ?>:</span>
						<?= tohtml($svc["USER"]) ?>
					</div>
					<div class="units-table-cell u-text-bold u-text-center-desktop">
						<span class="u-hide-desktop"><?= tohtml(__t("Stack", "Teknoloji")) ?>:</span>
						<span class="badge badge-info" style="font-size:11px; padding: 2px 6px;">
							<?= tohtml(strtoupper($svc["TYPE"])) ?>
						</span>
					</div>
					<div class="units-table-cell u-text-bold u-text-center-desktop">
						<span class="u-hide-desktop"><?= tohtml(__t("Port", "Port")) ?>:</span>
						<code>:<?= tohtml($svc["PORT"]) ?></code>
					</div>
					<div class="units-table-cell u-text-bold u-text-center-desktop">
						<span class="u-hide-desktop"><?= tohtml(__t("Memory", "Bellek")) ?>:</span>
						<?= tohtml($svc["MEMORY"] ?? "0 MB") ?>
					</div>
					<div class="units-table-cell u-text-bold u-text-center-desktop">
						<span class="u-hide-desktop"><?= tohtml(__t("Status", "Durum")) ?>:</span>
						<?php if ($is_running): ?>
							<span style="color: var(--icon-color-green);"><i class="fas fa-circle-check icon-green"></i> <?= tohtml(__t("Running", "Çalışıyor")) ?></span>
						<?php elseif (($svc["STATUS"] ?? "") === "failed"): ?>
							<span style="color: var(--icon-color-red);"><i class="fas fa-circle-xmark icon-red"></i> <?= tohtml(__t("Failed", "Hata")) ?></span>
						<?php else: ?>
							<span style="color: var(--color-text);"><i class="fas fa-circle-pause icon-dim"></i> <?= tohtml(__t("Inactive", "Pasif")) ?></span>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>

<!-- Modal: Deploy Standalone API -->
<div id="deploy-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.65); z-index:9999; justify-content:center; align-items:center;">
	<div class="form-container" style="background:var(--color-background, #fff); max-width:580px; width:92%; border-radius:8px; padding:25px 30px; box-shadow:0 10px 30px rgba(0,0,0,0.5);">
		<h2 class="u-mb15"><i class="fas fa-circle-plus icon-green"></i> <?= tohtml(__t("Deploy Standalone API", "Yeni API Servisi Kur")) ?></h2>
		<p class="u-text-muted u-mb20" style="font-size:0.9rem; line-height:1.4;"><?= tohtml(__t("Select an API repository to deploy as a standalone backend service on an isolated dynamic port with Let's Encrypt SSL.", "Bağımsız bir API deposu seçerek dinamik izole port ve SSL sertifikasıyla doğrudan yayına alabilirsiniz.")) ?></p>
		
		<form method="post" action="/list/api/">
			<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
			<input type="hidden" name="deploy_repo" value="1">
			
			<div class="u-mb15">
				<label class="form-label u-mb5 u-text-bold"><?= tohtml(__t("GitHub Repository", "GitHub Deposu (Repo)")) ?></label>
				<select name="deploy_repo_name" class="form-select" required style="width:100%;">
					<?php if (empty($github_repos) || isset($github_repos["error"])): ?>
						<option value=""><?= tohtml(__t("-- No repos found / Token not configured in Server Settings --", "-- Repo bulunamadı / Token Sunucu Ayarları'nda tanımlı değil --")) ?></option>
					<?php else: ?>
						<option value=""><?= tohtml(__t("-- Select Repository --", "-- Depo Seçiniz --")) ?></option>
						<?php foreach ($github_repos as $rname => $rdata): ?>
							<option value="<?= tohtml($rname) ?>">
								<?= tohtml($rdata["NAME"] ?? $rname) ?> (<?= tohtml($rdata["LANGUAGE"] ?? "API") ?>) <?= ($rdata["PRIVATE"] ?? "") === "yes" ? "🔒" : "" ?>
							</option>
						<?php endforeach; ?>
					<?php endif; ?>
				</select>
			</div>

			<div class="u-mb15">
				<label class="form-label u-mb5 u-text-bold"><?= tohtml(__t("API Domain / Endpoint URL", "API Alan Adı / Endpoint")) ?></label>
				<input type="text" name="deploy_domain" placeholder="api.siteniz.com" required class="form-control" style="width:100%;">
			</div>

			<div class="u-mb15" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
				<div>
					<label class="form-label u-mb5 u-text-bold"><?= tohtml(__t("Owner User", "Sahip Kullanıcı")) ?></label>
					<select name="deploy_user" class="form-select" style="width:100%;">
						<?php foreach ($users_list as $uname => $udata): ?>
							<option value="<?= tohtml($uname) ?>" <?= $uname === "admin" ? "selected" : "" ?>>
								<?= tohtml($uname) ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label class="form-label u-mb5 u-text-bold"><?= tohtml(__t("API Stack Type", "API Teknolojisi")) ?></label>
					<select name="deploy_mode" class="form-select" style="width:100%;">
						<option value="api"><?= tohtml(__t("🌐 Standalone API (Auto-Detect)", "🌐 Bağımsız API (Otomatik Algıla)")) ?></option>
						<option value="dotnet"><?= tohtml(__t("🟣 .NET 8 / 9 / 10 Web API", "🟣 .NET 8 / 9 / 10 Web API")) ?></option>
						<option value="node"><?= tohtml(__t("🟢 Node.js / Express / Fastify", "🟢 Node.js / Express / Fastify")) ?></option>
						<option value="php"><?= tohtml(__t("🐘 PHP Microservice", "🐘 PHP Microservice")) ?></option>
					</select>
				</div>
			</div>

			<div class="u-mt20" style="display:flex; justify-content:flex-end; gap:10px;">
				<button type="button" class="button button-secondary" onclick="document.getElementById('deploy-modal').style.display='none'">
					<?= tohtml(__t("Cancel", "İptal")) ?>
				</button>
				<button type="submit" class="button button-primary">
					<i class="fas fa-rocket"></i> <?= tohtml(__t("Deploy & Launch API", "API'yi Başlat & Yayına Al")) ?>
				</button>
			</div>
		</form>
	</div>
</div>
