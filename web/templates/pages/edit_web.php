<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/web/">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml( _("Back")) ?>
			</a>
		</div>
		<div class="toolbar-buttons">
			<?php if (!empty($v_is_git) || !empty($v_git_repo)) { ?>
				<a href="/edit/web/?<?= tohtml(http_build_query(array_filter(["domain" => $v_domain, "user" => ($_GET["user"] ?? ""), "git_pull" => "1", "token" => $_SESSION["token"]]))) ?>" class="button button-secondary" title="<?= tohtml(__tr("Pull & Update from GitHub", "GitHub'dan Güncelle (Pull & Rebuild)")) ?>">
					<i class="fab fa-github icon-blue"></i><?= tohtml(__tr("Git Pull", "Git Pull")) ?>
				</a>
			<?php } ?>
			<a href="/delete/web/cache/?<?= tohtml(http_build_query(["domain" => $v_domain, "token" => $_SESSION['token']])) ?>" class="button button-secondary js-clear-cache-button <?php if (!($v_nginx_cache == 'yes' || (($v_proxy_template == 'caching' || is_int(strpos($v_proxy_template, 'caching-'))) && $_SESSION['PROXY_SYSTEM'] == 'nginx'))) { echo "u-hidden"; } ?>">
				<i class="fas fa-trash icon-red"></i><?= tohtml( _("Purge NGINX Cache")) ?>
			</a>
			<?php if ($_SESSION["PLUGIN_APP_INSTALLER"] !== "false") { ?>
				<a href="/add/webapp/?<?= tohtml(http_build_query(["domain" => $v_domain])) ?>" class="button button-secondary">
					<i class="fas fa-magic icon-blue"></i><?= tohtml( _("Quick Install App")) ?>
				</a>
			<?php } ?>
			<button type="submit" class="button" form="main-form">
				<i class="fas fa-floppy-disk icon-purple"></i><?= tohtml( _("Save")) ?>
			</button>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">
	<?php
		$web_x_data = [
			"statsAuthEnabled" => !empty($v_stats_user),
			"redirectEnabled" => !empty($v_redirect),
			"sslEnabled" => $v_ssl == "yes",
			"letsEncryptEnabled" => $v_letsencrypt == "yes" || $v_letsencrypt == "on",
			"showCertificates" => !($v_letsencrypt == "yes" || $v_letsencrypt == "on"),
			"showAdvanced" => false,
			"nginxCacheEnabled" => $v_nginx_cache == "yes",
			"proxySupportEnabled" => !empty($v_proxy),
			"customDocumentRootEnabled" => !empty($v_custom_doc_root),
		];
	?>

	<form
		x-data="<?= tohtml(json_encode($web_x_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_THROW_ON_ERROR)) ?>"
		id="main-form"
		name="v_edit_web"
		method="post"
		class="<?= tohtml($v_status) ?> js-enable-inputs-on-submit"
	>
		<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
		<input type="hidden" name="save" value="save">

		<div class="form-container">
			<h1 class="u-mb20"><?= tohtml( _("Edit Web Domain")) ?></h1>
			<?php show_alert_message($_SESSION); ?>

			<?php if (!empty($cf_dns_enabled)) { ?>
				<div class="card u-mb20" style="border-left: 4px solid #f48120;">
					<div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 14px 18px; border-bottom: 1px solid #d3d3d3; flex-wrap: wrap; background: #fbfaf8;">
						<div style="display: flex; align-items: center; gap: 10px; min-width: 0; flex-wrap: wrap;">
							<i class="fab fa-cloudflare fa-lg" style="color: #f48120;"></i>
							<span style="font-weight: 700; font-size: 14px; color: #1f2937;">Cloudflare DNS &amp; NS Durumu</span>
							<span style="font-size: 12px; font-weight: 700; background: #1e293b; color: #f8fafc; padding: 3px 12px; border-radius: 99px; letter-spacing: 0.3px;"><?= tohtml(trim($v_domain, "'")) ?></span>
						</div>
						<a href="/edit/web/?domain=<?= tohtml(urlencode(trim($v_domain, "'"))) ?>&token=<?= tohtml($_SESSION['token']) ?>&check_cf_zone=1" class="button button-secondary" style="font-size: 12px; font-weight: 600;">
							<i class="fas fa-arrows-rotate u-mr5"></i>Zone Geldi mi? Test Et
						</a>
					</div>
					<div style="padding: 16px 18px;">
						<?php if ($cf_zone_status && !empty($cf_zone_status["success"])) { 
							$is_active = ($cf_zone_status["zone_status"] ?? "") === "active";
							$is_delegated = !empty($cf_zone_status["is_delegated"]);
						?>
							<?php if ($is_delegated) { ?>
								<div class="alert alert-success u-mb15" style="display: flex; align-items: center; gap: 10px; padding: 10px 14px;">
									<i class="fas fa-circle-check fa-lg" style="color: #16a34a;"></i>
									<div><strong>DNS Doğru Bağlı:</strong> Domain nameserver kayıtlarınız Cloudflare ile başarıyla eşleşti ve aktif.</div>
								</div>
							<?php } else { ?>
								<div class="alert alert-warning u-mb15" style="padding: 12px 14px;">
									<div style="display: flex; align-items: flex-start; gap: 10px;">
										<i class="fas fa-triangle-exclamation fa-lg u-mt5" style="color: #d97706;"></i>
										<div>
											<strong style="font-size: 14px;">Nameserver (NS) Yönlendirmesi Bekleniyor</strong>
											<p style="margin: 4px 0 0 0; font-size: 13px; line-height: 1.5;">
												Domain kayıt firmanızın (GoDaddy, Namecheap, Natro, İHS vb.) yönetim paneline girip <strong>DNS / Nameserver</strong> ayarlarını aşağıdaki 2 adresle güncelleyin:
											</p>
										</div>
									</div>
								</div>
							<?php } ?>

							<div style="display: flex; gap: 24px; flex-wrap: wrap; margin-bottom: 14px; background: #ffffff; padding: 12px 16px; border-radius: 6px; border: 1px solid #d3d3d3;">
								<div>
									<small style="color: #64748b; display: block; font-weight: 600; margin-bottom: 3px;">Zone Durumu</small>
									<?php if ($is_active) { ?>
										<span style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 99px; font-weight: 700; font-size: 12px;"><i class="fas fa-circle-check u-mr5"></i>Active (Aktif)</span>
									<?php } else { ?>
										<span style="background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 99px; font-weight: 700; font-size: 12px;"><i class="fas fa-clock u-mr5"></i>Pending (Bekleniyor)</span>
									<?php } ?>
								</div>
								<div>
									<small style="color: #64748b; display: block; font-weight: 600; margin-bottom: 3px;">NS Delegasyon Durumu</small>
									<?php if ($is_delegated) { ?>
										<span style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 99px; font-weight: 700; font-size: 12px;"><i class="fas fa-check u-mr5"></i>Doğrulandı</span>
									<?php } else { ?>
										<span style="background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 99px; font-weight: 700; font-size: 12px;"><i class="fas fa-triangle-exclamation u-mr5"></i>Yönlendirilmemiş</span>
									<?php } ?>
								</div>
								<?php if (!empty($cf_zone_status["resolved_ip"])) { ?>
								<div>
									<small style="color: #64748b; display: block; font-weight: 600; margin-bottom: 3px;">Çözümlenen IP (Cloudflare Edge)</small>
									<code style="font-weight: 700; color: #1e293b;"><?= tohtml($cf_zone_status["resolved_ip"]) ?></code>
								</div>
								<?php } ?>
							</div>

							<?php if (!empty($cf_zone_status["assigned_nameservers"])) { ?>
								<div class="u-mb10">
									<label class="form-label" style="font-size: 13px; font-weight: 700; margin-bottom: 8px; display: block;">
										<i class="fas fa-server u-mr5" style="color: #ea580c;"></i>Atanan Cloudflare Nameserver (NS) Adresleri
									</label>
									<div style="display: flex; gap: 12px; flex-wrap: wrap;">
										<?php $ns_idx = 1; foreach ($cf_zone_status["assigned_nameservers"] as $ns_item) { ?>
											<div style="background: #ffffff; border: 1px solid #d3d3d3; padding: 8px 14px; border-radius: 6px; display: inline-flex; align-items: center; gap: 10px;">
												<span style="font-size: 11px; font-weight: 700; color: #c2410c; background: #ffedd5; padding: 2px 8px; border-radius: 99px;">NS <?= $ns_idx++ ?></span>
												<code style="font-size: 14px; font-weight: 700; color: #1e293b; font-family: monospace;"><?= tohtml($ns_item) ?></code>
												<button type="button" class="button button-secondary" style="padding: 3px 10px; font-size: 11px; font-weight: 600;" onclick="navigator.clipboard.writeText('<?= tohtml($ns_item) ?>'); this.innerText='Kopyalandı!'; setTimeout(() => this.innerText='Kopyala', 2000);">
													<i class="fas fa-copy u-mr5"></i>Kopyala
												</button>
											</div>
										<?php } ?>
									</div>
								</div>
							<?php } ?>

							<?php if (!empty($cf_zone_status["live_nameservers_str"])) { ?>
								<div style="margin-top: 10px; font-size: 12px; color: #64748b;">
									<strong>Dünyanın Gördüğü Mevcut NS:</strong> <code><?= tohtml($cf_zone_status["live_nameservers_str"]) ?></code>
								</div>
							<?php } ?>
						<?php } else { ?>
							<p style="color: #666; margin: 0; font-size: 13px;">
								<i class="fas fa-circle-info u-mr5"></i>Cloudflare Zone durumu sorgulanıyor veya zone henüz açılmadı.
							</p>
						<?php } ?>
					</div>
				</div>
				<?php } ?>

			<!-- GitHub Integration & Git Deployment Card -->
			<div class="card u-mb20" style="border-left: 4px solid #2563eb;">
				<div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 14px 18px; border-bottom: 1px solid #d3d3d3; flex-wrap: wrap; background: #f8fafc;">
					<div style="display: flex; align-items: center; gap: 10px; min-width: 0; flex-wrap: wrap;">
						<i class="fab fa-github fa-xl" style="color: #2563eb;"></i>
						<span style="font-weight: 700; font-size: 14px; color: #1e293b;">
							<?= tohtml(__tr("GitHub Integration & Git Deployment", "GitHub Entegrasyonu & Git Dağıtımı")) ?>
						</span>
						<?php if (!empty($v_is_git) || !empty($v_git_repo)) { ?>
							<span class="badge" style="background:#dcfce7; color:#166534; font-size:12px; font-weight:700; padding:3px 10px; border-radius:99px;">
								<i class="fas fa-circle-check u-mr5"></i><?= tohtml(__tr("Connected", "Bağlı & Aktif")) ?>
							</span>
						<?php } else { ?>
							<span class="badge" style="background:#f1f5f9; color:#64748b; font-size:12px; font-weight:700; padding:3px 10px; border-radius:99px;">
								<i class="fas fa-circle-xmark u-mr5"></i><?= tohtml(__tr("Not Connected", "Bağlı Değil")) ?>
							</span>
						<?php } ?>
					</div>
					<?php if (!empty($v_is_git) || !empty($v_git_repo)) { ?>
						<a href="/edit/web/?<?= tohtml(http_build_query(array_filter(["domain" => $v_domain, "user" => ($_GET["user"] ?? ""), "git_pull" => "1", "token" => $_SESSION["token"]]))) ?>" class="button button-primary" style="background:#2563eb; color:#ffffff; font-size: 12px; font-weight: 600; padding: 6px 14px; border-radius:6px; display:inline-flex; align-items:center; gap:6px;" onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> <?= tohtml(__tr("Pulling...", "Çekiliyor...")) ?>';">
							<i class="fab fa-github"></i><?= tohtml(__tr("Pull & Rebuild", "Manuel Git Pull Tetikle")) ?>
						</a>
					<?php } ?>
				</div>
				<div style="padding: 16px 18px;">
					<?php if (!empty($v_is_git) || !empty($v_git_repo)) { 
						$repo_clean = preg_replace('#^https?://([^@]+@)?#', 'https://', $v_git_repo);
						$repo_slug = preg_replace('#^https?://(www\.)?github\.com/#i', '', $repo_clean);
						$repo_slug = rtrim($repo_slug, '.git');
					?>
						<div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 12px; background: #ffffff; padding: 14px 16px; border-radius: 6px; border: 1px solid #e2e8f0;">
							<div style="min-width: 220px; flex: 1;">
								<small style="color: #64748b; display: block; font-weight: 600; margin-bottom: 4px;"><?= tohtml(__tr("Connected Repository", "Bağlı GitHub Deposu")) ?></small>
								<a href="<?= tohtml($repo_clean) ?>" target="_blank" rel="noopener" style="font-weight: 700; font-size: 14px; color: #2563eb; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
									<i class="fab fa-github"></i> <?= tohtml($repo_slug ?: $v_git_repo) ?>
									<i class="fas fa-arrow-up-right-from-square" style="font-size: 11px; opacity: 0.7;"></i>
								</a>
							</div>
							<div>
								<small style="color: #64748b; display: block; font-weight: 600; margin-bottom: 4px;"><?= tohtml(__tr("Branch", "Aktif Dal (Branch)")) ?></small>
								<span class="badge badge-info" style="font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 99px;">
									<i class="fas fa-code-branch u-mr5"></i><?= tohtml($v_git_branch ?: "main") ?>
								</span>
							</div>
							<?php if (!empty($v_git_dirty)) { ?>
								<div>
									<small style="color: #64748b; display: block; font-weight: 600; margin-bottom: 4px;"><?= tohtml(__tr("Local State", "Yerel Durum")) ?></small>
									<span class="badge badge-warning" style="font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 99px;">
										<i class="fas fa-triangle-exclamation u-mr5"></i><?= tohtml(__tr("Modified files", "Yerel Değişiklik Var")) ?>
									</span>
								</div>
							<?php } ?>
						</div>

						<?php if (!empty($v_git_commit_hash)) { ?>
							<div style="background: rgba(0,0,0,0.02); border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; margin-bottom: 14px;">
								<div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
									<small style="color: #64748b; font-weight: 700;"><?= tohtml(__tr("Last Deployed Commit:", "Son Dağıtılan Commit:")) ?></small>
									<code style="background: #1e293b; color: #38bdf8; padding: 2px 7px; border-radius: 4px; font-size: 12px; font-weight: 700;"><?= tohtml($v_git_commit_hash) ?></code>
									<span style="font-weight: 600; font-size: 13px; color: #1e293b;"><?= tohtml($v_git_commit_msg) ?></span>
								</div>
								<?php if (!empty($v_git_commit_date) || !empty($v_git_commit_author)) { ?>
									<div style="font-size: 11px; color: #64748b; margin-top: 5px;">
										<?php if (!empty($v_git_commit_author)) { ?>
											<span><i class="fas fa-user u-mr5"></i><?= tohtml($v_git_commit_author) ?></span>
										<?php } ?>
										<?php if (!empty($v_git_commit_date)) { ?>
											<span style="margin-left: 10px;"><i class="fas fa-clock u-mr5"></i><?= tohtml($v_git_commit_date) ?></span>
										<?php } ?>
									</div>
								<?php } ?>
							</div>
						<?php } ?>

						<!-- Webhook Information -->
						<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 14px; margin-bottom: 14px;">
							<div style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center; margin-bottom: 8px;">
								<div style="flex: 1; min-width: 250px;">
									<label class="form-label u-mb5 u-text-bold" style="font-size: 12px; color: #475569;">
										<i class="fas fa-link u-mr5"></i>Webhook URL (deploy.php)
									</label>
									<div style="display: flex; gap: 6px; align-items: center;">
										<input type="text" class="form-control" value="<?= tohtml($v_git_webhook_url) ?>" readonly style="font-family: monospace; font-size: 12px; background: #f8fafc;">
										<button type="button" class="button button-secondary" style="padding: 6px 12px; font-size: 12px; white-space: nowrap;" onclick="navigator.clipboard.writeText('<?= tohtml($v_git_webhook_url) ?>'); this.innerText='<?= tohtml(__tr('Copied!', 'Kopyalandı!')) ?>'; setTimeout(() => this.innerText='<?= tohtml(__tr('Copy', 'Kopyala')) ?>', 2000);">
											<i class="fas fa-copy u-mr5"></i><?= tohtml(__tr("Copy", "Kopyala")) ?>
										</button>
									</div>
								</div>
								<?php if (!empty($v_git_secret)) { ?>
									<div style="flex: 1; min-width: 250px;">
										<label class="form-label u-mb5 u-text-bold" style="font-size: 12px; color: #475569;">
											<i class="fas fa-key u-mr5"></i>Webhook Secret (HMAC-SHA256)
										</label>
										<div style="display: flex; gap: 6px; align-items: center;">
											<input type="password" id="webhook-secret-input" class="form-control" value="<?= tohtml($v_git_secret) ?>" readonly style="font-family: monospace; font-size: 12px; background: #f8fafc;">
											<button type="button" class="button button-secondary" style="padding: 6px 10px; font-size: 12px;" onclick="var s = document.getElementById('webhook-secret-input'); s.type = s.type === 'password' ? 'text' : 'password';">
												<i class="fas fa-eye"></i>
											</button>
											<button type="button" class="button button-secondary" style="padding: 6px 12px; font-size: 12px; white-space: nowrap;" onclick="navigator.clipboard.writeText('<?= tohtml($v_git_secret) ?>'); this.innerText='<?= tohtml(__tr('Copied!', 'Kopyalandı!')) ?>'; setTimeout(() => this.innerText='<?= tohtml(__tr('Copy', 'Kopyala')) ?>', 2000);">
												<i class="fas fa-copy u-mr5"></i><?= tohtml(__tr("Copy", "Kopyala")) ?>
											</button>
										</div>
									</div>
								<?php } ?>
							</div>
							<p style="font-size: 12px; color: #64748b; margin: 0; line-height: 1.4;">
								💡 <strong><?= tohtml(__tr("Auto-Deploy:", "Otomatik Dağıtım:")) ?></strong>
								<?= tohtml(__tr("Add this Webhook URL to GitHub (Repo > Settings > Webhooks) with 'application/json' format to trigger automatic deployments on git push.", "GitHub repository sayfanızda (Settings > Webhooks) bu adresi ve secret'ı ekleyin; her 'git push' yapıldığında siteniz otomatik olarak güncellenir.")) ?>
							</p>
						</div>

						<!-- Action row -->
						<div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
							<a href="/edit/web/?<?= tohtml(http_build_query(array_filter(["domain" => $v_domain, "user" => ($_GET["user"] ?? ""), "git_pull" => "1", "token" => $_SESSION["token"]]))) ?>" class="button button-primary" style="background:#2563eb; color:#ffffff; font-size: 13px; font-weight: 600; padding: 8px 18px; border-radius:6px; display:inline-flex; align-items:center; gap:8px;" onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> <?= tohtml(__tr("Pulling from GitHub...", "GitHub\'dan Çekiliyor...")) ?>';">
								<i class="fab fa-github fa-lg"></i> <?= tohtml(__tr("Manual Git Pull & Rebuild", "Manuel Git Pull & Rebuild Çalıştır")) ?>
							</a>
							<button type="button" class="button button-secondary" style="font-size: 13px;" onclick="var el = document.getElementById('git-manage-settings'); el.style.display = el.style.display === 'none' ? 'block' : 'none';">
								<i class="fas fa-sliders u-mr5"></i><?= tohtml(__tr("Change Repo / Branch / Disconnect", "Repo & Dal Ayarlarını Değiştir")) ?>
							</button>
						</div>

						<!-- Collapsible Git Configuration Form -->
						<div id="git-manage-settings" style="display: none; margin-top: 14px; padding: 14px; background: #ffffff; border: 1px solid #d3d3d3; border-radius: 6px;">
							<h4 style="margin: 0 0 10px 0; font-size: 13px; font-weight: 700; color: #1e293b;">
								<?= tohtml(__tr("Git Integration Configuration", "Git Deposu ve Dal Ayarları")) ?>
							</h4>
							<div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 10px;">
								<div style="flex: 2; min-width: 250px;">
									<label class="form-label u-mb5" style="font-size: 12px; font-weight: 600;"><?= tohtml(__tr("Repository URL", "GitHub Repo URL")) ?></label>
									<input type="text" class="form-control" name="v_git_repo" value="<?= tohtml($v_git_repo) ?>" placeholder="https://github.com/owner/repo.git">
								</div>
								<div style="flex: 1; min-width: 140px;">
									<label class="form-label u-mb5" style="font-size: 12px; font-weight: 600;"><?= tohtml(__tr("Branch", "Dal (Branch)")) ?></label>
									<input type="text" class="form-control" name="v_git_branch" value="<?= tohtml($v_git_branch ?: 'main') ?>" placeholder="main">
								</div>
							</div>
							<div class="form-check" style="margin-top: 10px;">
								<input class="form-check-input" type="checkbox" name="v_git_disable" id="v_git_disable" value="yes">
								<label class="form-check-label" for="v_git_disable" style="color: #dc2626; font-size: 12px; font-weight: 600;">
									<?= tohtml(__tr("Disconnect Git repository from this domain (disable webhook auto-deploy)", "Bu domainin Git bağlantısını sonlandır (otomatik webhook dağıtımını kapat)")) ?>
								</label>
							</div>
							<p class="hint u-mt5" style="font-size: 11px;">
								<?= tohtml(__tr("Click 'Save' at the top or bottom of the page to apply configuration changes.", "Değişikliklerin kaydedilmesi için sayfanın üst veya altındaki 'Kaydet' butonuna basınız.")) ?>
							</p>
						</div>

					<?php } else { ?>
						<p class="u-text-muted" style="margin: 0 0 12px 0; font-size: 13px; line-height: 1.5;">
							<?= tohtml(__tr("This domain is not connected to a Git repository yet. You can connect a public or private GitHub repository to enable webhook auto-deployment and 1-click manual pulls.", "Bu alan adı henüz bir Git deposuna bağlı değil. Bir GitHub deposu bağlayarak webhook ile otomatik güncellemeleri ve tek tıkla manuel pull & derleme özelliğini aktif edebilirsiniz.")) ?>
						</p>
						<div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 8px;">
							<div style="flex: 2; min-width: 250px;">
								<label class="form-label u-mb5" style="font-size: 12px; font-weight: 600;"><?= tohtml(__tr("GitHub Repository URL", "GitHub Repo URL")) ?></label>
								<input type="text" class="form-control" name="v_git_repo" value="" placeholder="https://github.com/owner/repository.git">
								<small class="hint" style="font-size: 11px;"><?= tohtml(__tr("Example: https://github.com/username/project.git (For private repos include PAT or configure organization token)", "Örn: https://github.com/kullanici/proje.git (Özel repo ise PAT token kullanabilirsiniz)")) ?></small>
							</div>
							<div style="flex: 1; min-width: 140px;">
								<label class="form-label u-mb5" style="font-size: 12px; font-weight: 600;"><?= tohtml(__tr("Branch", "Dal (Branch)")) ?></label>
								<input type="text" class="form-control" name="v_git_branch" value="main" placeholder="main">
								<small class="hint" style="font-size: 11px;"><?= tohtml(__tr("Default: main", "Varsayılan: main")) ?></small>
							</div>
						</div>
						<p class="hint u-mt5" style="font-size: 11px;">
							<?= tohtml(__tr("Enter the repository details and click 'Save' to initialize the repository for this domain.", "Repo bilgilerini girdikten sonra 'Kaydet' butonuna basarak depoyu bu domaine bağlayabilirsiniz.")) ?>
						</p>
					<?php } ?>
				</div>
			</div>

			<div class="u-mb10">
				<label for="v_domain" class="form-label"><?= tohtml( _("Domain")) ?></label>
				<input type="text" class="form-control" name="v_domain" id="v_domain" value="<?= tohtml(trim($v_domain, "'")) ?>" disabled required>
				<input type="hidden" name="v_domain" value="<?= tohtml(trim($v_domain, "'")) ?>">
			</div>
			<div class="u-mb10">
				<label for="v_aliases" class="form-label"><?= tohtml( _("Aliases")) ?></label>
				<textarea class="form-control" name="v_aliases" id="v_aliases"><?= tohtml(trim($v_aliases, "'")) ?></textarea>
			</div>
			<?php if ($v_letsencrypt == "yes" || $v_letsencrypt == "on") { ?>
				<div class="alert alert-info u-mb10" role="alert">
					<i class="fas fa-exclamation"></i>
					<p><?= tohtml( _("If the aliases changes, Let's Encrypt will obtain a new SSL certificate.")) ?></p>
				</div>
			<?php } ?>
			<div class="u-mb20">
				<label for="v_ip" class="form-label"><?= tohtml( _("IP Address")) ?></label>
				<select class="form-select" name="v_ip" id="v_ip">
					<?php
						foreach ($ips as $ip => $value) {
							$display_ip = htmlentities(empty($value['NAT']) ? $ip : "{$value['NAT']}");
							$ip_selected = ((!empty($v_ip) && $ip == $v_ip) || $v_ip == "'{$ip}'")	? 'selected' : '';
							echo "\n\t\t\t\t\t\t\t\t\t\t\t\t<option value=\"{$ip}\" {$ip_selected}>{$display_ip}</option>\n";
						}
					?>
				</select>
			</div>

			<?php if (($_SESSION["userContext"] ?? "") === "admin") { ?>
			<div class="u-mb20">
				<label for="v_app_preset" class="form-label">
					<i class="fas fa-layer-group icon-purple u-mr5"></i><?= tohtml( _("Application Runtime & Proxy Preset (Uygulama Çalıştırma Tipi)")) ?>
				</label>
				<select class="form-select" name="v_proxy_template_preset" id="v_app_preset" onchange="if(document.getElementById('v_proxy_template')) document.getElementById('v_proxy_template').value = this.value;">
					<option value="default" <?php if (empty($v_proxy_template) || $v_proxy_template == 'default' || $v_proxy_template == "'default'") echo 'selected'; ?>>🐘 PHP Web Application (PHP-FPM Default)</option>
					<option value="node-js" <?php if ($v_proxy_template == 'node-js' || $v_proxy_template == "'node-js'") echo 'selected'; ?>>🟢 Node.js / Express / Next.js (Port 3000)</option>
					<option value="dotnet" <?php if ($v_proxy_template == 'dotnet' || $v_proxy_template == "'dotnet'") echo 'selected'; ?>>🔷 .NET 8 / 9 / 10 ASP.NET Core (Port 5000)</option>
					<option value="websocket" <?php if ($v_proxy_template == 'websocket' || $v_proxy_template == "'websocket'") echo 'selected'; ?>>⚡ Live WebSocket & Socket.io (Port 3000)</option>
					<option value="docker-ui" <?php if ($v_proxy_template == 'docker-ui' || $v_proxy_template == "'docker-ui'") echo 'selected'; ?>>🐳 Docker UI / Portainer Management (Port 9000)</option>
				</select>
				<small class="form-text text-muted u-mt5"><?= tohtml( _("Uygulamanızın çalışma modunu seçin. Seçilen mod Nginx reverse proxy ve SSL yönlendirmesini otomatik ayarlar.")) ?></small>
			</div>
			<?php } ?>

			<div class="u-mb10">
				<label for="v_stats" class="form-label"><?= tohtml( _("Web Statistics")) ?></label>
				<select class="form-select js-stats-select" name="v_stats" id="v_stats">
					<?php
						foreach ($stats as $key => $value) {
							$svalue = "'".$value."'";
							echo "\t\t\t\t<option value=\"".htmlentities($value)."\"";
							if (empty($v_stats)) $v_stats = 'none';
							if (( $value == $v_stats ) || ($svalue == $v_stats )){
								echo ' selected' ;
							}
						echo ">". htmlentities(_($value)) ."</option>\n";
						}
					?>
				</select>
			</div>

			<?php if (($_SESSION["userContext"] ?? "") === "admin") { ?>
			<div class="u-mb10 js-stats-auth" style="<?php if ($v_stats == "none") { ?>display:none<?php } ?>">
				<div class="form-check">
					<input x-model="statsAuthEnabled" class="form-check-input" type="checkbox" name="v_stats_auth" id="v_stats_auth">
					<label for="v_stats_auth">
						<?= tohtml( _("Statistics Authorization")) ?>
					</label>
				</div>
			</div>
			<div class="u-pl30 js-stats-auth">
				<div x-cloak x-show="statsAuthEnabled" name="v-add-web-domain-stats-user">
					<div class="u-mb10">
						<label for="v_stats_user" class="form-label"><?= tohtml( _("Username")) ?></label>
						<input type="text" class="form-control" name="v_stats_user" id="v_stats_user" value="<?= tohtml(trim($v_stats_user, "'")) ?>">
					</div>
					<div class="u-mb20">
						<label for="v_password" class="form-label">
							<?= tohtml( _("Password")) ?>
							<button type="button" title="<?= tohtml( _("Generate")) ?>" class="u-unstyled-button u-ml5 js-generate-password">
								<i class="fas fa-arrows-rotate icon-green"></i>
							</button>
						</label>
						<div class="u-pos-relative">
							<input type="text" class="form-control js-password-input" name="v_stats_password" id="v_password" value="<?= tohtml(trim($v_stats_password, "'")) ?>">
						</div>
					</div>
				</div>
			</div>
			<?php } ?>
			<div class="form-check u-mb10">
				<input x-model="redirectEnabled" class="form-check-input" type="checkbox" name="v-redirect-checkbox" id="v-redirect-checkbox">
				<label for="v-redirect-checkbox">
					<?= tohtml( _("Enable domain redirection")) ?>
				</label>
			</div>
			<div x-cloak x-show="redirectEnabled" id="v_redirect" class="u-pl30 u-mb10">
				<div class="form-check">
					<input class="form-check-input js-redirect-custom-value" type="radio" name="v-redirect" id="v-redirect-radio-1" value="<?= tohtml('www.'.$v_domain) ?>" <?php if ($v_redirect == "www.".$v_domain) echo 'checked'; ?>>
					<label for="v-redirect-radio-1">
						<?= tohtml(sprintf(_("Redirect visitors to %s"), "www." . $v_domain)) ?>
					</label>
				</div>
				<div class="form-check">
					<input class="form-check-input js-redirect-custom-value" type="radio" name="v-redirect" id="v-redirect-radio-2" value="<?= tohtml($v_domain) ?>" <?php if ( $v_redirect == $v_domain) echo 'checked'; ?>>
					<label for="v-redirect-radio-2">
						<?= tohtml(sprintf(_("Redirect visitors to %s"), $v_domain)) ?>
					</label>
				</div>
				<div class="form-check">
					<input class="form-check-input js-redirect-custom-value" type="radio" name="v-redirect" id="v-redirect-radio-3" value="custom" <?php if ( !empty($v_redirect_custom)) echo 'checked'; ?>>
					<label for="v-redirect-radio-3">
						<?= tohtml( _("Redirect visitors to a custom domain or web address")) ?>
					</label>
				</div>
				<div class="u-pl30 js-custom-redirect-fields <?php if (empty($v_redirect_custom)) { echo 'u-hidden'; } ?>">
					<div class="u-mt15 u-mb10">
						<label for="v-redirect-custom" class="form-label"><?= tohtml( _("Target domain or URL")) ?></label>
						<input type="text" class="form-control" name="v-redirect-custom" id="v-redirect-custom" value="<?= tohtml($v_redirect_custom) ?>">
					</div>
					<div class="u-mb20">
						<label for="v-redirect-code" class="form-label"><?= tohtml( _("Status code")) ?>:</label>
						<select class="form-select" name="v-redirect-code" id="v-redirect-code">
							<?php foreach ($redirect_code_options as $status_code): ?>
								<option value="<?= tohtml($status_code) ?>" <?php if ((int) $v_redirect_code === (int) $status_code) echo 'selected="selected"'; ?>>
								<?= tohtml($status_code) ?>
							</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			</div>
			<div class="form-check u-mb10">
				<input x-model="sslEnabled" class="form-check-input" type="checkbox" name="v_ssl" id="v_ssl">
				<label for="v_ssl">
					<?= tohtml( _("Enable SSL for this domain")) ?>
				</label>
			</div>
			<div x-cloak x-show="sslEnabled" class="u-pl30">
				<div class="form-check u-mb10">
					<input x-model="letsEncryptEnabled" class="form-check-input js-toggle-lets-encrypt" type="checkbox" name="v_letsencrypt" id="v_letsencrypt">
					<label for="v_letsencrypt">
						<?= tohtml( _("Use Let's Encrypt to obtain SSL certificate")) ?>
					</label>
				</div>
				<div class="form-check u-mb10">
					<input class="form-check-input" type="checkbox" name="v_ssl_forcessl" id="v_ssl_forcessl" <?php if ($v_ssl_forcessl == 'yes') echo 'checked' ?>>
					<label for="v_ssl_forcessl">
						<?= tohtml( _("Enable automatic HTTPS redirection")) ?>
					</label>
				</div>
				<div class="form-check u-mb20">
					<input class="form-check-input" type="checkbox" name="v_ssl_hsts" id="ssl_hsts" <?php if ($v_ssl_hsts == 'yes') echo 'checked' ?>>
					<label for="ssl_hsts">
						<?= tohtml( _("Enable HTTP Strict Transport Security (HSTS)")) ?>
						<a href="https://en.wikipedia.org/wiki/HTTP_Strict_Transport_Security" target="_blank">
							<i class="fas fa-question-circle"></i>
						</a>
					</label>
				</div>
				<div x-cloak x-show="showCertificates" class="js-ssl-details">
					<div class="u-mb10">
						<label for="ssl_crt" class="form-label">
							<?= tohtml( _("SSL Certificate")) ?>
							<span id="generate-csr"> / <a class="form-link" target="_blank" href="/generate/ssl/?<?= tohtml(http_build_query(["domain" => $v_domain])) ?>"><?= tohtml( _("Generate Self-Signed SSL Certificate")) ?></a></span>
						</label>
						<textarea class="form-control u-min-height100 u-console" name="v_ssl_crt" id="ssl_crt"><?= tohtml(trim($v_ssl_crt, "'")) ?></textarea>
					</div>
					<div class="u-mb10">
						<label for="v_ssl_key" class="form-label"><?= tohtml( _("SSL Private Key")) ?></label>
						<textarea class="form-control u-min-height100 u-console" name="v_ssl_key" id="v_ssl_key"><?= tohtml(trim($v_ssl_key, "'")) ?></textarea>
					</div>
					<div class="u-mb20">
						<label for="v_ssl_ca" class="form-label">
							<?= tohtml( _("SSL Certificate Authority / Intermediate")) ?> <span class="optional">(<?= tohtml( _("Optional")) ?>)</span>
						</label>
						<textarea class="form-control u-min-height100 u-console" name="v_ssl_ca" id="v_ssl_ca"><?= tohtml(trim($v_ssl_ca, "'")) ?></textarea>
					</div>
				</div>
				<?php if ($v_ssl != "no") { ?>
					<ul class="values-list">
						<li class="values-list-item">
							<span class="values-list-label"><?= tohtml( _("Issued To")) ?></span>
							<span class="values-list-value"><?= tohtml($v_ssl_subject) ?></span>
						</li>
						<?php if ($v_ssl_aliases) {
							$v_ssl_aliases = str_replace(",", ", ", $v_ssl_aliases); ?>
							<li class="values-list-item">
								<span class="values-list-label"><?= tohtml( _("Alternate")) ?></span>
								<span class="values-list-value"><?= tohtml($v_ssl_aliases) ?></span>
							</li>
						<?php } ?>
						<li class="values-list-item">
							<span class="values-list-label"><?= tohtml( _("Not Before")) ?></span>
							<span class="values-list-value"><?= tohtml($v_ssl_not_before) ?></span>
						</li>
						<li class="values-list-item">
							<span class="values-list-label"><?= tohtml( _("Not After")) ?></span>
							<span class="values-list-value"><?= tohtml($v_ssl_not_after) ?></span>
						</li>
						<li class="values-list-item">
							<span class="values-list-label"><?= tohtml( _("Signature")) ?></span>
							<span class="values-list-value"><?= tohtml($v_ssl_signature) ?></span>
						</li>
						<li class="values-list-item">
							<span class="values-list-label"><?= tohtml( _("Key Size")) ?></span>
							<span class="values-list-value"><?= tohtml($v_ssl_pub_key) ?></span>
						</li>
						<li class="values-list-item">
							<span class="values-list-label"><?= tohtml( _("Issued By")) ?></span>
							<span class="values-list-value"><?= tohtml($v_ssl_issuer) ?></span>
						</li>
						<p x-cloak x-show="letsEncryptEnabled" id="letsinfo">
							<button
								type="button"
								class="form-link"
								x-on:click="showCertificates = !showCertificates"
								x-text="showCertificates ? <?= json_encode(_("Hide Certificate"), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?> : <?= json_encode(_("Show Certificate"), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>">
								<?= tohtml( _("Show Certificate")) ?>
							</button>
						</p>
					</ul>
				<?php } ?>
			</div>
			<div class="u-mt15 u-mb20">
				<button x-on:click="showAdvanced = !showAdvanced" type="button" class="button button-secondary">
					<?= tohtml( _("Advanced Options")) ?>
				</button>
			</div>
			<div x-cloak x-show="showAdvanced">
				<?php if ($_SESSION["userContext"] === "admin" || ($_SESSION["userContext"] === "user" && $_SESSION["POLICY_USER_EDIT_WEB_TEMPLATES"] === "yes")) { ?>
					<div class="u-mb10">
						<label for="v_template" class="form-label">
							<?= tohtml( _("Web Template")) ?> <span class="optional"><?= tohtml(strtoupper($_SESSION["WEB_SYSTEM"])) ?></span>
						</label>
						<select class="form-select" name="v_template" id="v_template">
							<?php
								foreach ($templates as $key => $value) {
									echo "\t\t\t\t<option value=\"".htmlentities($value)."\"";
									$svalue = "'".$value."'";
									if ((!empty($v_template)) && ( $value == $v_template ) || ($svalue == $v_template)){
										echo ' selected' ;
									}
									echo ">".htmlentities($value)."</option>\n";
								}
							?>
						</select>
					</div>
					<?php if ($_SESSION["WEB_SYSTEM"] == "nginx") { ?>
						<div class="form-check u-mb10">
							<input x-model="nginxCacheEnabled" class="form-check-input" type="checkbox" name="v_nginx_cache_check" id="v_nginx_cache_check">
							<label for="v_nginx_cache_check">
								<?= tohtml( _("Enable FastCGI cache")) ?>
								<a href="https://hestiacp.com/docs/server-administration/web-templates.html#nginx-fastcgi-cache" target="_blank" class="u-ml5">
									<i class="fas fa-circle-question"></i>
								</a>
							</label>
						</div>
						<div x-cloak x-show="nginxCacheEnabled" id="v_nginx_duration" class="u-pl30">
							<div class="u-mb10">
								<label for="v_nginx_cache_duration" class="form-label">
									<?= tohtml( _("Cache Duration")) ?> <span class="optional">(<?= tohtml( _("For example")) ?>: 30s, 10m or 1d)</span>
								</label>
								<input type="text" class="form-control" name="v_nginx_cache_duration" id="v_nginx_cache_duration" value="<?= tohtml(trim($v_nginx_cache_duration, "'")) ?>">
							</div>
						</div>
					<?php } ?>
					<?php if (!empty($_SESSION["WEB_BACKEND"])) { ?>
						<div class="u-mb10">
								<label for="v_backend_template" class="form-label">
									<?= tohtml( _("Backend Template")) ?> <span class="optional"><?= tohtml(strtoupper($_SESSION["WEB_BACKEND"])) ?></span>
								</label>
							<select class="form-select" name="v_backend_template" id="v_backend_template">
								<?php
									foreach ($backend_templates as $key => $value) {
										echo "\t\t\t\t<option value=\"".tohtml($value)."\"";
										$svalue = "'".$value."'";
										if ((!empty($v_backend_template)) && (($value == $v_backend_template) || ($svalue == $v_backend_template))) {
											echo ' selected' ;
										}
										if ((empty($v_backend_template)) && ($value == 'default')){
											echo ' selected' ;
										}
										echo ">".tohtml($value)."</option>\n";
									}
								?>
							</select>
						</div>
					<?php } ?>
					<?php if (!empty($_SESSION["PROXY_SYSTEM"])) { ?>
						<div style="display: none;">
							<div class="form-check u-mb10">
								<input x-model="proxySupportEnabled" class="form-check-input" type="checkbox" name="v_proxy" id="v_proxy">
									<label for="v_proxy">
										<?= tohtml( _("Proxy Support")) ?> <span class="optional"><?= tohtml(strtoupper($_SESSION["PROXY_SYSTEM"])) ?></span>
									</label>
							</div>
						</div>
						<div x-cloak x-show="proxySupportEnabled" id="proxytable">
							<div class="u-mb10">
								<label for="v_proxy_template" class="form-label"><?= tohtml( _("Proxy Template")) ?></label>
								<select class="form-select js-proxy-template-select" name="v_proxy_template" id="v_proxy_template">
									<?php
										foreach ($proxy_templates as $key => $value) {
											echo "\t\t\t\t<option value=\"".tohtml($value)."\"";
											$svalue = "'".$value."'";
											if ((!empty($v_proxy_template)) && (($value == $v_proxy_template) || ($svalue == $v_proxy_template))) {
												echo ' selected' ;
											}
											if ((empty($v_proxy_template)) && ($value == 'default')){
												echo ' selected' ;
											}
											echo ">".tohtml($value)."</option>\n";
										}
									?>
								</select>
							</div>
							<div class="u-mb10">
								<label for="v_proxy_ext" class="form-label"><?= tohtml( _("Proxy Extensions")) ?></label>
								<textarea class="form-control u-min-height100" name="v_proxy_ext" id="v_proxy_ext"><?php if (!empty($v_proxy_ext)) { echo tohtml(trim($v_proxy_ext, "'"));} else { echo 'jpg, jpeg, gif, png, ico, svg, css, zip, tgz, gz, rar, bz2, exe, pdf, doc, xls, ppt, txt, odt, ods, odp, odf, tar, bmp, rtf, js, mp3, avi, mpeg, flv, html, htm'; } ?></textarea>
							</div>
						</div>
					<?php } ?>
				<?php } ?>
				<div class="form-check u-mb10">
					<input x-model="customDocumentRootEnabled" class="form-check-input" type="checkbox" name="v_custom_doc_root_check" id="v_custom_doc_root_check">
					<label for="v_custom_doc_root_check">
						<?= tohtml( _("Custom document root")) ?>
					</label>
				</div>
				<div x-cloak x-show="customDocumentRootEnabled" id="v_custom_doc_root" class="u-pl30">
					<div class="u-mb10">
						<label for="v-custom-doc-domain" class="form-label"><?= tohtml( _("Point to")) ?></label>
						<input type="hidden" class="js-custom-docroot-prepath" name="v-custom-doc-root_prepath" value="<?= tohtml($v_custom_doc_root_prepath) ?>">
						<select class="form-select js-custom-docroot-domain" name="v-custom-doc-domain" id="v-custom-doc-domain">
							<?php foreach ($user_domains as $domain): ?>
							<option value="<?= tohtml($domain) ?>"
								<?php if ($v_custom_doc_domain === $domain || (empty($v_custom_doc_domain) && $domain === $v_domain)) echo 'selected="selected"'; ?>>
								<?= tohtml($domain) ?>
							</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="u-mb10">
						<label for="v-custom-doc-folder" class="form-label">
							<?= tohtml( _("Directory")) ?> <span class="optional">(<?= tohtml( _("Optional")) ?>)</span>
						</label>
						<input type="text" class="form-control js-custom-docroot-dir" name="v-custom-doc-folder" id="v-custom-doc-folder" value="<?= tohtml(trim($v_custom_doc_folder, "'")) ?>">
						<small class="js-custom-docroot-hint"></small>
					</div>
				</div>
				<?php if (in_array($_SESSION["FTP_SYSTEM"], ["vsftpd", "proftpd"])) { ?>
					<div class="form-check u-mb10">
						<input class="form-check-input js-toggle-ftp-accounts" type="checkbox" name="v_ftp" id="v_ftp" <?php if (!empty($v_ftp_user)) echo 'checked' ?>>
						<label for="v_ftp">
							<?= tohtml( _("Additional FTP account(s)")) ?>
						</label>
					</div>
					<div class="js-active-ftp-accounts">
						<?php foreach ($v_ftp_users as $i => $ftp_user): ?>
						<?php
							$v_ftp_user     = $ftp_user['v_ftp_user'];
							$v_ftp_password = $ftp_user['v_ftp_password'];
							$v_ftp_path     = $ftp_user['v_ftp_path'];
							$v_ftp_email    = $ftp_user['v_ftp_email'];
							$v_ftp_pre_path = $ftp_user['v_ftp_pre_path'];
						?>
						<div class="js-ftp-account js-ftp-account-nrm" name="v_add_domain_ftp" style="<?php if (empty($v_ftp_user)) { echo 'display: none;'; } ?>">
							<div class="u-mb10">
								<?= tohtml( _("FTP")) ?> #<span class="js-ftp-user-number"><?= tohtml($i + 1) ?></span>
								<button type="button" class="form-link form-link-danger u-ml5 js-delete-ftp-account"><?= tohtml( _("Delete")) ?></button>
								<input type="hidden" class="js-ftp-user-deleted" name="v_ftp_user[<?= tohtml($i) ?>][delete]" value="0">
								<input type="hidden" class="js-ftp-user-is-new" name="v_ftp_user[<?= tohtml($i) ?>][is_new]" value="<?= tohtml($ftp_user['is_new']) ?>">
							</div>
							<div class="u-pl30 u-mb10">
								<label for="v_ftp_user[<?= tohtml($i) ?>][v_ftp_user]" class="form-label">
									<?= tohtml( _("Username")) ?><br>
									<span style="color:#777;"><?= tohtml(sprintf(_('Prefix %s will be added to username automatically'),$user_plain."_")) ?></span>
								</label>
								<input type="text" class="form-control js-ftp-user"<?= $ftp_user['is_new'] != 1 ? ' disabled="disabled"' : '' ?>
								name="v_ftp_user[<?= tohtml($i) ?>][v_ftp_user]" id="v_ftp_user[<?= tohtml($i) ?>][v_ftp_user]" value="<?= tohtml(trim($v_ftp_user, "'")) ?>">
								<small class="hint js-ftp-user-hint"></small>
							</div>
							<div class="u-pl30 u-mb10">
								<label for="v_ftp_user[<?= tohtml($i) ?>][v_ftp_password]" class="form-label">
									<?= tohtml( _("Password")) ?>
									<button type="button" title="<?= tohtml( _("Generate")) ?>" class="u-unstyled-button u-ml5 js-ftp-password-generate">
										<i class="fas fa-arrows-rotate icon-green"></i>
									</button>
								</label>
								<input type="text" class="form-control js-ftp-user-psw" name="v_ftp_user[<?= tohtml($i) ?>][v_ftp_password]" id="v_ftp_user[<?= tohtml($i) ?>][v_ftp_password]" value="<?= tohtml(trim($v_ftp_password, "'")) ?>">
							</div>
							<div class="u-pl30 u-mb10">
								<label for="v_ftp_user[<?= tohtml($i) ?>][v_ftp_path]" class="form-label"><?= tohtml( _("Path")) ?></label>
								<input type="hidden" name="v_ftp_pre_path" value="<?= tohtml(!empty($v_ftp_pre_path) ? trim($v_ftp_pre_path, "'") : '/') ?>">
								<input type="hidden" name="v_ftp_user[<?= tohtml($i) ?>][v_ftp_path_prev]" value="<?php if (!empty($v_ftp_path)) echo tohtml(($v_ftp_path[0] != '/' ? '/' : '') . trim($v_ftp_path, "'")); ?>">
								<input type="text" class="form-control js-ftp-path" name="v_ftp_user[<?= tohtml($i) ?>][v_ftp_path]" id="v_ftp_user[<?= tohtml($i) ?>][v_ftp_path]" value="<?php if (!empty($v_ftp_path)) echo tohtml(($v_ftp_path[0] != '/' ? '/' : '') . trim($v_ftp_path, "'")); ?>">
								<span class="hint-prefix"><?= tohtml(trim($v_ftp_pre_path, "'")) ?></span><span class="hint js-ftp-path-hint"></span>
							</div>
							<?php if ($ftp_user['is_new'] == 1): ?>
								<div class="u-pl30 u-mb10">
									<label for="v_ftp_user[<?= tohtml($i) ?>][v_ftp_email]" class="form-label"><?= tohtml( _("Send FTP credentials to email")) ?></label>
									<input type="email" class="form-control js-email-alert-on-psw" name="v_ftp_user[<?= tohtml($i) ?>][v_ftp_email]" id="v_ftp_user[<?= tohtml($i) ?>][v_ftp_email]" value="<?= tohtml(trim($v_ftp_email, "'")) ?>">
								</div>
							<?php endif; ?>
						</div>
						<?php endforeach; ?>
					</div>

					<button type="button" class="form-link u-mt20 js-add-ftp-account" style="<?php if (empty($v_ftp_user)) echo 'display: none;' ?>">
						<?= tohtml( _("Add FTP account")) ?>
					</button>
				<?php } ?>
			</div>

			
		</div>

	</form>

	<!-- Domain-Level .env Secrets (Zero-Knowledge) -->
	<?php if (($_SESSION["userContext"] ?? "") === "admin") { ?>
	<details class="collapse u-mt20" id="web-env-secrets" open>
		<summary class="collapse-header">
			<i class="fas fa-shield-halved icon-purple u-mr5"></i><?= tohtml(__tr("Environment Variables & Secrets (.env)", "Ortam Değişkenleri & Secret Yönetimi (.env)")) ?> (<?= count($domain_env_secrets ?? []) ?>)
		</summary>
		<div class="collapse-content">
			<p class="u-text-muted u-mb15" style="font-size:0.88rem; line-height:1.4;">
				🛡️ <strong><?= tohtml(__tr("Zero-Knowledge Security Model:", "Sıfır Bilgi (Zero-Knowledge) Güvenlik Modeli:")) ?></strong>
				<?= tohtml(__tr("Secrets defined here (e.g. GEMINI_API_KEY, DB_PASSWORD) are written directly to public_html/.env with chmod 600. For security reasons, values cannot be read in plaintext via UI/API; they can only be updated or deleted. Secrets are preserved across git deployments.", "Bu site için tanımladığınız API anahtarları (örn: GEMINI_API_KEY, DB_PASSWORD) sunucuda doğrudan public_html/.env dosyasına chmod 600 ile kaydedilir. Güvenlik gereği arayüzde değerler asla düz metin olarak okunamaz; yalnızca güncellenebilir veya silinebilir. GitHub'dan her güncelleme yapıldığında bu dosya korunur.")) ?>
			</p>

			<!-- Add or Update Secret Form -->
			<form method="post" action="/edit/web/?domain=<?= urlencode($v_domain) ?>">
				<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
				<input type="hidden" name="save_env_secret" value="1">
				<div class="card u-mb20" style="padding:15px; border: 1px solid var(--border-color, #334155); border-radius:6px; background: rgba(0,0,0,0.02);">
					<div style="display:flex; flex-wrap:wrap; gap: 10px; align-items: flex-end;">
						<div style="flex:1; min-width:200px;">
							<label class="form-label u-mb5 u-text-bold" style="font-size:12px;"><?= tohtml(__tr("Secret Key Name", "Anahtar Adı (KEY)")) ?></label>
							<input type="text" name="env_key" placeholder="GEMINI_API_KEY" required class="form-control" style="font-family:monospace; text-transform:uppercase;">
						</div>
						<div style="flex:1; min-width:200px;">
							<label class="form-label u-mb5 u-text-bold" style="font-size:12px;"><?= tohtml(__tr("Secret Value (Write-Only)", "Gizli Değer (Yalnızca Yazılabilir)")) ?></label>
							<input type="password" name="env_value" placeholder="••••••••••••••••••••" required class="form-control">
						</div>
						<div>
							<button type="submit" class="button button-secondary">
								<i class="fas fa-plus icon-green"></i> <?= tohtml(__tr("Save Secret", "Kaydet / Güncelle")) ?>
							</button>
						</div>
					</div>
				</div>
			</form>

			<!-- List Existing Domain Secrets -->
			<?php if (empty($domain_env_secrets)): ?>
				<p class="u-text-muted u-text-center" style="padding: 10px 0; font-size:0.9rem;">
					<?= tohtml(__tr("No custom environment variables (.env) defined for this domain yet.", "Bu alan adı için henüz özel bir ortam değişkeni (.env) tanımlanmadı.")) ?>
				</p>
			<?php else: ?>
				<div class="units-table" style="margin-top: 5px;">
					<div class="units-table-header">
						<div class="units-table-cell"><?= tohtml(__tr("Secret Key", "Anahtar Adı")) ?></div>
						<div class="units-table-cell u-text-center"><?= tohtml(__tr("Stored Value", "Kayıtlı Değer")) ?></div>
						<div class="units-table-cell u-text-center"><?= tohtml(__tr("Protected Location", "Korumalı Konum")) ?></div>
						<div class="units-table-cell u-text-center"><?= tohtml(__tr("Action", "İşlem")) ?></div>
					</div>
					<?php foreach ($domain_env_secrets as $ekey => $edata): ?>
						<div class="units-table-row">
							<div class="units-table-cell units-table-heading-cell u-text-bold" style="font-family:monospace;">
								<i class="fas fa-key icon-yellow u-mr5"></i> <?= tohtml($ekey) ?>
							</div>
							<div class="units-table-cell u-text-center">
								<code style="letter-spacing:2px; opacity:0.7;">••••••••••••••••</code>
							</div>
							<div class="units-table-cell u-text-center">
								<span class="badge badge-info" style="font-size:11px; padding: 2px 6px;">
									.env (chmod 600)
								</span>
							</div>
							<div class="units-table-cell u-text-center">
								<a class="button button-danger button-small" href="/edit/web/?domain=<?= urlencode($v_domain) ?>&delete_env=1&key=<?= urlencode($ekey) ?>&token=<?= tohtml($_SESSION["token"]) ?>" title="<?= tohtml(__tr("Delete Secret", "Secret'ı Sil")) ?>" onclick="return confirm('<?= tohtml(__tr("Are you sure you want to delete this secret?", "Bu ortam değişkenini silmek istediğinize emin misiniz?")) ?>');" style="padding: 3px 8px; font-size:11px;">
									<i class="fas fa-trash-can"></i>
								</a>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</details>
	<?php } ?>
</div>

<div class="u-hidden js-ftp-account-template">
	<div class="js-ftp-account js-ftp-account-nrm" name="v_add_domain_ftp">
		<div class="u-mb10">
			<?= tohtml( _("FTP")) ?> #<span class="js-ftp-user-number"></span>
			<button type="button" class="form-link form-link-danger u-ml5 js-delete-ftp-account"><?= tohtml( _("Delete")) ?></button>
			<input type="hidden" class="js-ftp-user-deleted" name="v_ftp_user[%INDEX%][delete]" value="0">
			<input type="hidden" class="js-ftp-user-is-new" name="v_ftp_user[%INDEX%][is_new]" value="1">
		</div>
		<div class="u-pl30 u-mb10">
			<label for="v_ftp_user[%INDEX%][v_ftp_user]" class="form-label">
				<?= tohtml( _("Username")) ?><br>
				<span style="color:#777;"><?= tohtml(sprintf(_("Prefix %s will be added to username automatically"), $user_plain . "_")) ?></span>
			</label>
			<input type="text" class="form-control js-ftp-user" name="v_ftp_user[%INDEX%][v_ftp_user]" id="v_ftp_user[%INDEX%][v_ftp_user]" value="">
			<small class="hint js-ftp-user-hint"></small>
		</div>
		<div class="u-pl30 u-mb10">
			<label for="v_ftp_user[%INDEX%][v_ftp_password]" class="form-label">
				<?= tohtml( _("Password")) ?>
				<button type="button" title="<?= tohtml( _("Generate")) ?>" class="u-unstyled-button u-ml5 js-ftp-password-generate">
					<i class="fas fa-arrows-rotate icon-green"></i>
				</button>
			</label>
			<input type="text" class="form-control js-ftp-user-psw" name="v_ftp_user[%INDEX%][v_ftp_password]" id="v_ftp_user[%INDEX%][v_ftp_password]">
		</div>
		<div class="u-pl30 u-mb10">
			<label for="v_ftp_user[%INDEX%][v_ftp_path]" class="form-label"><?= tohtml( _("Path")) ?></label>
			<input type="hidden" name="v_ftp_pre_path" value="">
			<input type="text" class="form-control js-ftp-path" name="v_ftp_user[%INDEX%][v_ftp_path]" id="v_ftp_user[%INDEX%][v_ftp_path]" value="">
			<span class="hint-prefix"><?= tohtml(trim($v_ftp_pre_path_new_user, "'")) ?></span><span class="hint js-ftp-path-hint"></span>
		</div>
		<div class="u-pl30 u-mb10">
			<label for="v_ftp_user[%INDEX%][v_ftp_email]" class="form-label"><?= tohtml( _("Send FTP credentials to email")) ?></label>
			<input type="email" class="form-control js-email-alert-on-psw" name="v_ftp_user[%INDEX%][v_ftp_email]" id="v_ftp_user[%INDEX%][v_ftp_email]" value="">
		</div>
	</div>
</div>
