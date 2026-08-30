<?php $admin_overview = is_admin_overview(); ?>
<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<?php if ($read_only !== "true") { ?>
				<a href="/add/web/" class="button button-secondary js-button-create" title="<?= tohtml( _("Add Web Domain")) ?>">
					<i class="fas fa-circle-plus icon-green"></i><?= tohtml((($_SESSION['language'] ?? '') === 'tr') ? "Domain Ekle" : _("Add Domain")) ?>
				</a>
				<?php if (($_SESSION["userContext"] ?? "") === "admin") { ?>
					<button type="button" class="button button-secondary" onclick="document.getElementById('github-web-modal').style.display='flex'" title="<?= tohtml((($_SESSION['language'] ?? '') === 'tr') ? "GitHub'dan Site Kur" : _("Deploy from GitHub")) ?>">
						<i class="fab fa-github icon-blue"></i><?= tohtml((($_SESSION['language'] ?? '') === 'tr') ? "GitHub Kur" : "GitHub Deploy") ?>
					</button>
					<a href="/list/waf/" class="button button-secondary" title="<?= tohtml((($_SESSION['language'] ?? '') === 'tr') ? "Tehdit Kalkanı & WAF" : _("Threat Shield & WAF")) ?>">
						<i class="fas fa-shield-halved icon-blue"></i><?= tohtml((($_SESSION['language'] ?? '') === 'tr') ? "WAF & Kalkan" : "WAF") ?>
					</a>
					<a href="/list/domain-expiry/" class="button button-secondary" title="<?= tohtml((($_SESSION['language'] ?? '') === 'tr') ? "Domain Süreleri ve Lisans Takip Paneli" : _("Domain Expirations")) ?>">
						<i class="fas fa-hourglass-half icon-yellow"></i><?= tohtml((($_SESSION['language'] ?? '') === 'tr') ? "Süre Takip" : "Expirations") ?>
					</a>
				<?php } ?>
			<?php } ?>
		</div>
		<div class="toolbar-right">
			<div class="toolbar-sorting">
				<button class="toolbar-sorting-toggle js-toggle-sorting-menu" type="button" title="<?= tohtml( _("Sort items")) ?>">
					<?= tohtml( _("Sort by")) ?>:
					<span class="u-text-bold">
							<?php if ($_SESSION['userSortOrder'] === 'name') { $label = _('Name'); } else { $label = _('Date'); } ?>
						<?= tohtml($label) ?> <i class="fas fa-arrow-down-a-z"></i>
					</span>
				</button>
				<ul class="toolbar-sorting-menu js-sorting-menu u-hidden">
					<li data-entity="sort-bandwidth" data-sort-as-int="1">
						<span class="name"><?= tohtml( _("Bandwidth")) ?> <i class="fas fa-arrow-down-a-z"></i></span><span class="up"><i class="fas fa-arrow-up-a-z"></i></span>
					</li>
					<li data-entity="sort-date" data-sort-as-int="1">
						<span class="name <?php if ($_SESSION['userSortOrder'] === 'date') { echo 'active'; } ?>"><?= tohtml( _("Date")) ?> <i class="fas fa-arrow-down-a-z"></i></span><span class="up"><i class="fas fa-arrow-up-a-z"></i></span>
					</li>
					<li data-entity="sort-disk" data-sort-as-int="1">
						<span class="name"><?= tohtml( _("Disk")) ?> <i class="fas fa-arrow-down-a-z"></i></span><span class="up"><i class="fas fa-arrow-up-a-z"></i></span>
					</li>
					<li data-entity="sort-name">
						<span class="name <?php if ($_SESSION['userSortOrder'] === 'name') { echo 'active'; } ?>"><?= tohtml( _("Name")) ?> <i class="fas fa-arrow-down-a-z"></i></span><span class="up"><i class="fas fa-arrow-up-a-z"></i></span>
					</li>
					<li data-entity="sort-ip" data-sort-as-int="1">
						<span class="name"><?= tohtml( _("IP Address")) ?> <i class="fas fa-arrow-down-a-z"></i></span><span class="up"><i class="fas fa-arrow-up-a-z"></i></span>
					</li>
				</ul>
				<?php if ($read_only !== "true") { ?>
					<form x-data x-bind="BulkEdit" action="/bulk/web/" method="post">
						<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
						<select class="form-select" name="action">
							<option value=""><?= tohtml( _("Apply to selected")) ?></option>
							<?php if ($_SESSION["userContext"] === "admin") { ?>
								<option value="rebuild"><?= tohtml( _("Rebuild")) ?></option>
							<?php } ?>
							<option value="suspend"><?= tohtml( _("Suspend")) ?></option>
							<option value="unsuspend"><?= tohtml( _("Unsuspend")) ?></option>
							<?php if ($_SESSION["PROXY_SYSTEM"] == "nginx" || $_SESSION["WEB_SYSTEM"] == "nginx") { ?>
								<option value="purge"><?= tohtml( _("Purge Nginx Cache")) ?></option>
							<?php } ?>
							<option value="delete"><?= tohtml( _("Delete")) ?></option>
						</select>
						<button type="submit" class="toolbar-input-submit" title="<?= tohtml( _("Apply to selected")) ?>">
							<i class="fas fa-arrow-right"></i>
						</button>
					</form>
				<?php } ?>
			</div>
			<div class="toolbar-search">
				<form action="/search/" method="get">
					<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
					<input type="search" class="form-control js-search-input" name="q" value="<?= tohtml($_GET['q'] ?? '') ?>" title="<?= tohtml( _("Search")) ?>">
					<button type="submit" class="toolbar-input-submit" title="<?= tohtml( _("Search")) ?>">
						<i class="fas fa-magnifying-glass"></i>
					</button>
				</form>
			</div>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">

	<h1 class="u-text-center u-hide-desktop u-mt20 u-pr30 u-mb20 u-pl30"><?= tohtml( _("Web Domains")) ?></h1>

	<?php
		/* Subdomain classification: hide domains that live under another
		   domain of the same owner behind a dedicated tab. */
		$nx_domains_by_owner = [];
		foreach ($data as $nx_key => $nx_val) {
			$nx_owner = $nx_val['_owner'] ?? $user;
			$nx_domains_by_owner[$nx_owner][] = $nx_key;
		}
		$nx_main_count = 0;
		$nx_sub_count = 0;
		foreach ($data as $nx_key => $nx_val) {
			$nx_owner = $nx_val['_owner'] ?? $user;
			$nx_parent = '';
			foreach ($nx_domains_by_owner[$nx_owner] as $nx_candidate) {
				if ($nx_candidate !== $nx_key && substr($nx_key, -strlen('.' . $nx_candidate)) === '.' . $nx_candidate) {
					$nx_parent = $nx_candidate;
					break;
				}
			}
			$data[$nx_key]['_subdomain_of'] = $nx_parent;
			if ($nx_parent) { $nx_sub_count++; } else { $nx_main_count++; }
		}
	?>
	<?php if ($nx_sub_count > 0): ?>
	<style>
		.nx-is-sub { display: none !important; }
		body.nx-sub-view .nx-is-sub { display: revert !important; }
		body.nx-sub-view .nx-is-main { display: none !important; }
	</style>
	<div style="display:flex; gap:8px; align-items:center; margin: 16px 0 14px 0;">
		<button type="button" class="button button-primary js-nx-subtab" data-target="main">
			<i class="fas fa-globe"></i> <?= tohtml(__tr("Main Domains", "Ana Domainler")) ?> (<?= $nx_main_count ?>)
		</button>
		<button type="button" class="button button-secondary js-nx-subtab" data-target="sub">
			<i class="fas fa-sitemap"></i> <?= tohtml(__tr("Subdomains", "Subdomainler")) ?> (<?= $nx_sub_count ?>)
		</button>
	</div>
	<script>
		(function () {
			var btns = document.querySelectorAll(".js-nx-subtab");
			btns.forEach(function (btn) {
				btn.addEventListener("click", function () {
					var showSub = btn.getAttribute("data-target") === "sub";
					document.body.classList.toggle("nx-sub-view", showSub);
					btns.forEach(function (b) {
						b.classList.toggle("button-primary", b === btn);
						b.classList.toggle("button-secondary", b !== btn);
					});
				});
			});
		})();
	</script>
	<?php endif; ?>

	<div class="units-table js-units-container">
		<div class="units-table-header">
				<div class="units-table-cell">
					<input type="checkbox" class="js-toggle-all-checkbox" title="<?= tohtml( _("Select all")) ?>"<?= $display_mode === "disabled" ? " disabled" : "" ?>>
				</div>
			<?php if (!empty($admin_overview)) { ?><div class="units-table-cell"><?= tohtml(__tr("User", "Kullanıcı")) ?></div><?php } ?>
			<div class="units-table-cell"><?= tohtml( _("Name")) ?></div>
			<div class="units-table-cell"></div>
			<div class="units-table-cell u-text-center"><?= tohtml( _("IP Address")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml( _("Disk")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml( _("Bandwidth")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml( _("SSL")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml( _("Statistics")) ?></div>
		</div>

		<!-- Begin web domain list item loop -->
		<?php
			foreach ($data as $key => $value) {
				++$i;
				if ($data[$key]['SUSPENDED'] == 'yes') {
					$status = 'suspended';
					$spnd_action = 'unsuspend';
					$spnd_action_title = _('Unsuspend');
					$spnd_icon = 'fa-play';
					$spnd_icon_class = 'icon-green';
					$spnd_confirmation = _('Are you sure you want to unsuspend domain %s?');
				} else {
					$status = 'active';
					$spnd_action = 'suspend';
					$spnd_action_title = _('Suspend');
					$spnd_icon = 'fa-pause';
					$spnd_icon_class = 'icon-highlight';
					$spnd_confirmation = _('Are you sure you want to suspend domain %s?');
				}
				if (!empty($data[$key]['SSL_HOME'])) {
					if ($data[$key]['SSL_HOME'] == 'same') {
						$ssl_home = 'public_html';
					} else {
						$ssl_home = 'public_shtml';
					}
				} else {
					$ssl_home = '';
				}
				$web_stats='no';
				if (!empty($data[$key]['STATS'])) {
					$web_stats=$data[$key]['STATS'];
				}
				$ftp_user='no';
				if (!empty($data[$key]['FTP_USER'])) {
					$ftp_user=$data[$key]['FTP_USER'];
				}
				if (strlen($ftp_user) > 24 ) {
					$ftp_user = str_replace(':', ', ', $ftp_user);
					$ftp_user = substr($ftp_user, 0, 24);
					$ftp_user = trim($ftp_user, ":");
					$ftp_user = str_replace(':', ', ', $ftp_user);
					$ftp_user = $ftp_user.", ...";
				} else {
					$ftp_user = str_replace(':', ', ', $ftp_user);
				}

				$backend_support='no';
				if (!empty($data[$key]['BACKEND'])) {
					$backend_support='yes';
				}

				$proxy_support='no';
				if (!empty($data[$key]['PROXY'])) {
					$proxy_support='yes';
				}
				if (strlen($data[$key]['PROXY_EXT']) > 24 ) {
					$proxy_ext_title = str_replace(',', ', ', $data[$key]['PROXY_EXT']);
					$proxy_ext = substr($data[$key]['PROXY_EXT'], 0, 24);
					$proxy_ext = trim($proxy_ext, ",");
					$proxy_ext = str_replace(',', ', ', $proxy_ext);
					$proxy_ext = $proxy_ext.", ...";
				} else {
					$proxy_ext_title = '';
					$proxy_ext = str_replace(',', ', ', $data[$key]['PROXY_EXT']);
				}
				if ($data[$key]['SUSPENDED'] === 'yes') {
					if ($data[$key]['SSL'] == 'no') {
						$icon_ssl = 'fas fa-circle-xmark';
						$title_ssl = _('Disabled');
					}
					if ($data[$key]['SSL'] == 'yes') {
						$icon_ssl = 'fas fa-circle-check';
						$title_ssl = _('Enabled');
					}
					if ($web_stats == 'no') {
						$icon_webstats = 'fas fa-circle-xmark';
						$title_webstats = _('Disabled');
					} else {
						$icon_webstats = 'fas fa-circle-check';
						$title_webstats = _('Enabled');
					}
				} else {
					if ($data[$key]['SSL'] == 'no') {
						$icon_ssl = 'fas fa-circle-xmark icon-red';
						$title_ssl = _('Disabled');
					}
					if ($data[$key]['SSL'] == 'yes') {
						$icon_ssl = 'fas fa-circle-check icon-green';
						$title_ssl = _('Enabled');
					}
					if ($web_stats == 'no') {
						$icon_webstats = 'fas fa-circle-xmark icon-red';
						$title_webstats = _('Disabled');
					} else {
						$icon_webstats = 'fas fa-circle-check icon-green';
						$title_webstats = _('Enabled');
					}
				}
				$has_ssl = filter_var($data[$key]['SSL'], FILTER_VALIDATE_BOOL);
				$vstats_scheme = $has_ssl ? 'https' : 'http';
			?>
			<div class="units-table-row <?php if ($data[$key]['SUSPENDED'] == 'yes') echo 'disabled'; ?> js-unit <?= $data[$key]['_subdomain_of'] ? "nx-is-sub" : "nx-is-main" ?>"
				data-sort-ip="<?= tohtml(str_replace(".", "", $data[$key]["IP"])) ?>"
				data-sort-date="<?= tohtml(strtotime($data[$key]["DATE"] . " " . $data[$key]["TIME"])) ?>"
				data-sort-name="<?= tohtml($key) ?>"
				data-sort-bandwidth="<?= tohtml($data[$key]["U_BANDWIDTH"]) ?>"
				data-sort-disk="<?= tohtml($data[$key]["U_DISK"]) ?>">
				<div class="units-table-cell">
					<div>
						<input id="check<?= tohtml($i) ?>" class="js-unit-checkbox" type="checkbox" title="<?= tohtml( _("Select")) ?>" name="domain[]" value="<?= tohtml($key) ?>"<?= $display_mode === "disabled" ? " disabled" : "" ?>>
						<label for="check<?= tohtml($i) ?>" class="u-hide-desktop"><?= tohtml( _("Select")) ?></label>
					</div>
				</div>
				<?= !empty($admin_overview) ? '<div class="units-table-cell u-text-bold">' . tohtml($value["_owner"] ?? "") . '</div>' : "" ?>
				<div class="units-table-cell units-table-heading-cell u-text-bold">
					<span class="u-hide-desktop"><?= tohtml( _("Name")) ?>:</span>
					<?php if ($read_only === "true") { ?>
						<?= tohtml($key) ?>
					<?php } else {
						$aliases = explode(',', $data[$key]['ALIAS']);
						$alias_new = array();
						foreach($aliases as $alias){
							if ($alias != 'www.'.$key) {
								$alias_new[] = trim($alias);
							}
						}
						?>
						<?php
							$d_prio = (int)($data[$key]['RESOURCE_PRIORITY'] ?? 0);
							$prio_badges = [
								0 => '<span class="badge badge-info" style="font-size:10px; margin-left:6px; padding:2px 5px;" title="Smart Auto-Adaptive (Dinamik Kaynak Ölçekleme)">⚡ Auto</span>',
								1 => '<span class="badge badge-secondary" style="font-size:10px; margin-left:6px; padding:2px 5px;" title="Low (Eco-Kısılmış: 64M RAM)">🟢 Low</span>',
								2 => '<span class="badge badge-info" style="font-size:10px; margin-left:6px; padding:2px 5px;" title="Normal Standart (256M RAM)">🔵 Normal</span>',
								3 => '<span class="badge badge-purple" style="font-size:10px; margin-left:6px; padding:2px 5px;" title="Yüksek Öncelik (512M RAM / 1 Çekirdek)">🟣 High</span>',
								4 => '<span class="badge badge-warning" style="font-size:10px; margin-left:6px; padding:2px 5px;" title="Kritik (1G RAM / 2 Çekirdek)">🟠 Critical</span>',
								5 => '<span class="badge badge-purple" style="font-size:10px; margin-left:6px; padding:2px 5px;" title="VIP Sınırsız (2G+ RAM / Maksimum IO)">👑 VIP</span>'
							];
						?>
						<a href="/edit/web/?<?= tohtml(http_build_query(["domain" => $key, "user" => ($value["_owner"] ?? ""), "token" => $_SESSION['token']])) ?>" title="<?= tohtml( _("Edit Domain")) ?>: <?= tohtml($key) ?>">
							<?= tohtml($key) ?>
						</a>
						<?php
							$d_expiry = $data[$key]['EXPIRY_DATE'] ?? '';
							$d_created = $data[$key]['DATE'] ?? '';
							if (empty($d_expiry)) {
								if (!empty($d_created)) {
									$d_expiry = date('Y-m-d', strtotime($d_created . ' + 1 year'));
								} else {
									$d_expiry = date('Y-m-d', strtotime('+1 year'));
								}
							}
							$d_exp_badge = '';
							if ($d_expiry === 'unlimited') {
								$d_exp_badge = '<a href="/list/domain-expiry/" style="text-decoration:none;"><span class="badge badge-purple" style="font-size:10px; margin-left:4px; padding:2px 6px;" title="' . ($is_tr ? "Sınırsız Lisans" : "Unlimited Lifetime") . '">♾️ Süresiz</span></a>';
							} else {
								$d_exp_ts = strtotime($d_expiry);
								$d_today_ts = strtotime(date('Y-m-d'));
								$d_days = (int)round(($d_exp_ts - $d_today_ts) / 86400);
								if ($d_days < 0 || $data[$key]['SUSPENDED'] === 'yes') {
									$d_exp_badge = '<a href="/list/domain-expiry/" style="text-decoration:none;"><span class="badge badge-danger" style="font-size:10px; margin-left:4px; padding:2px 6px;" title="' . ($is_tr ? "Süresi Doldu / Yayın Kapalı ($d_expiry)" : "Expired ($d_expiry)") . '">🔴 ' . ($is_tr ? "Süresi Doldu" : "Expired") . '</span></a>';
								} elseif ($d_days <= 30) {
									$d_exp_badge = '<a href="/list/domain-expiry/" style="text-decoration:none;"><span class="badge badge-warning" style="font-size:10px; margin-left:4px; padding:2px 6px;" title="' . ($is_tr ? "Bitiş Tarihi: $d_expiry (Kritik)" : "Expires: $d_expiry") . '">⏳ ' . $d_days . 'g kaldı</span></a>';
								} else {
									$d_exp_badge = '<a href="/list/domain-expiry/" style="text-decoration:none;"><span class="badge badge-secondary" style="font-size:10px; margin-left:4px; padding:2px 6px; color: var(--icon-color-green, #22c55e);" title="' . ($is_tr ? "Bitiş Tarihi: $d_expiry" : "Expires: $d_expiry") . '">🟢 ' . $d_days . 'g</span></a>';
								}
							}
						?>
						<?php if (($_SESSION["userContext"] ?? "") === "admin"): ?>
							<?= $d_exp_badge ?>
						<?php endif; ?>
						<?php if (strpos($key, 'pr-') === 0): ?>
							<span class="badge badge-warning" style="font-size:10px; margin-left:4px; padding:2px 5px;" title="<?= tohtml(__tr("GitHub PR Preview Staging Environment (Protected Access)", "GitHub PR Test Önizleme Ortamı (Korumalı Erişim)")) ?>">
								<i class="fas fa-code-pull-request"></i> PR
							</span>
						<?php endif; ?>
						<?php if (!empty($data[$key]['_subdomain_of'])): ?>
							<span class="badge badge-secondary" style="font-size:10px; margin-left:4px; padding:2px 5px;" title="<?= tohtml(__tr("Subdomain of", "Şunun subdomaini")) ?>: <?= tohtml($data[$key]['_subdomain_of']) ?>">
								<i class="fas fa-sitemap"></i> <?= tohtml($data[$key]['_subdomain_of']) ?>
							</span>
						<?php endif; ?>
						<?php if (($_SESSION["userContext"] ?? "") === "admin"): ?>
							<a href="/list/resources/" style="text-decoration:none;">
								<?= $prio_badges[$d_prio] ?? $prio_badges[0] ?>
							</a>
						<?php endif; ?>
						<?php
							if (!empty($alias_new) && !empty($data[$key]['ALIAS'])) {
								$aliases = implode(', ', $alias_new);
								echo "<p class='hint u-max-width300 u-text-truncate'>(" . tohtml($aliases) . ")</p>";
							}
						?>
						<?php } ?>
				</div>
				<div class="units-table-cell">
					<ul class="units-table-row-actions">
						<?php if (!empty($data[$key]["STATS"])) { ?>
							<li class="units-table-row-action shortcut-w" data-key-action="href">
								<a
									class="units-table-row-action-link"
									href="<?= tohtml($vstats_scheme) ?>://<?= tohtml($key) ?>/vstats/"
									target="_blank"
									rel="noopener"
									title="<?= tohtml( _("Statistics")) ?>"
								>
									<i class="fas fa-chart-bar icon-maroon"></i>
									<span class="u-hide-desktop"><?= tohtml( _("Statistics")) ?></span>
								</a>
							</li>
						<?php } ?>
						<?php
							$web_port_suffix = "";
							if (strpos($_SERVER['HTTP_HOST'] ?? '', ':8083') !== false || ($_SERVER['SERVER_PORT'] ?? '') == '8083') {
								$web_port_suffix = ":9080";
							}
							$is_git_domain = file_exists(($_SESSION['HOMEDIR'] ?? '/home') . '/' . $user_plain . '/web/' . $key . '/public_html/.git') || file_exists('/home/' . $user_plain . '/web/' . $key . '/public_html/.git');
						?>
						<li class="units-table-row-action" data-key-action="href">
							<a
								class="units-table-row-action-link"
								href="http://<?= tohtml($key) ?><?= $web_port_suffix ?>/"
								target="_blank"
								rel="noopener"
								title="<?= tohtml( _("Visit")) ?>"
							>
								<i class="fas fa-square-up-right icon-lightblue"></i>
								<span class="u-hide-desktop"><?= tohtml( _("Visit")) ?></span>
							</a>
						</li>
						<?php if ($read_only !== "true") { ?>
							<?php if ($data[$key]["SUSPENDED"] == "no") { ?>
								<?php if ($is_git_domain) { ?>
									<li class="units-table-row-action" data-key-action="href">
										<a
											class="units-table-row-action-link"
											href="/list/web/?git_update=1&domain=<?= urlencode($key) ?>&token=<?= tohtml($_SESSION["token"]) ?>"
											title="<?= tohtml((($_SESSION['language'] ?? '') === 'tr') ? "GitHub'dan Güncelle (Pull & Build)" : _("Pull & Update from GitHub")) ?>"
										>
											<i class="fab fa-github icon-blue"></i>
											<span class="u-hide-desktop"><?= tohtml((($_SESSION['language'] ?? '') === 'tr') ? "Git Güncelle" : _("Git Update")) ?></span>
										</a>
									</li>
								<?php } ?>
								<li class="units-table-row-action shortcut-enter" data-key-action="href">
									<a
										class="units-table-row-action-link"
										href="/edit/web/?<?= tohtml(http_build_query(["domain" => $key, "user" => ($value["_owner"] ?? ""), "token" => $_SESSION["token"]])) ?>"
										title="<?= tohtml( _("Edit Domain")) ?>"
									>
										<i class="fas fa-pencil icon-orange"></i>
										<span class="u-hide-desktop"><?= tohtml( _("Edit Domain")) ?></span>
									</a>
								</li>
								<li class="units-table-row-action" data-key-action="href">
									<a
										class="units-table-row-action-link"
										href="/download/site/?<?= tohtml(http_build_query(["site" => $key, "user" => ($value["_owner"] ?? ""), "token" => $_SESSION["token"]])) ?>"
										title="<?= tohtml( _("Download Site")) ?>"
									>
										<i class="fas fa-download icon-orange"></i>
										<span class="u-hide-desktop"><?= tohtml( _("Download Site")) ?></span>
									</a>
								</li>
							<?php } ?>
							<li class="units-table-row-action shortcut-l" data-key-action="href">
								<a
									class="units-table-row-action-link"
									href="/list/logs/?<?= tohtml(http_build_query(["domain" => $key, "type" => "access"])) ?>"
									title="<?= tohtml(function_exists('__tr') ? __tr("Live Stream Logs", "Canlı Günlük Akışı") : _("Live Logs")) ?>"
								>
									<i class="fas fa-terminal icon-purple"></i>
									<span class="u-hide-desktop"><?= tohtml( _("Live Logs")) ?></span>
								</a>
							</li>
							<li class="units-table-row-action shortcut-s" data-key-action="js">
								<a
									class="units-table-row-action-link data-controls js-confirm-action"
									href="/<?= tohtml($spnd_action) ?>/web/?<?= tohtml(http_build_query(["domain" => $key, "user" => ($value["_owner"] ?? ""), "token" => $_SESSION["token"]])) ?>"
									title="<?= tohtml($spnd_action_title) ?>"
									data-confirm-title="<?= tohtml($spnd_action_title) ?>"
									data-confirm-message="<?= tohtml(sprintf($spnd_confirmation, $key)) ?>"
								>
									<i class="fas <?= tohtml($spnd_icon) ?> <?= tohtml($spnd_icon_class) ?>"></i>
									<span class="u-hide-desktop"><?= tohtml($spnd_action_title) ?></span>
								</a>
							</li>
							<li class="units-table-row-action shortcut-delete" data-key-action="js">
								<a
									class="units-table-row-action-link data-controls js-confirm-action"
									href="/delete/web/?<?= tohtml(http_build_query(["domain" => $key, "user" => ($value["_owner"] ?? ""), "token" => $_SESSION["token"]])) ?>"
									title="<?= tohtml( _("Delete")) ?>"
									data-confirm-title="<?= tohtml( _("Delete")) ?>"
									data-confirm-message="<?= tohtml(sprintf(_("Are you sure you want to delete domain %s?"), $key)) ?>"
								>
									<i class="fas fa-trash icon-red"></i>
									<span class="u-hide-desktop"><?= tohtml( _("Delete")) ?></span>
								</a>
							</li>
						<?php } ?>
					</ul>
				</div>
				<div class="units-table-cell u-text-center-desktop">
					<span class="u-hide-desktop u-text-bold"><?= tohtml( _("IP Address")) ?>:</span>
					<?= tohtml(empty($ips[$data[$key]["IP"]]["NAT"]) ? $data[$key]["IP"] : "{$ips[$data[$key]["IP"]]["NAT"]}") ?>
				</div>
				<div class="units-table-cell u-text-center-desktop">
					<span class="u-hide-desktop u-text-bold"><?= tohtml( _("Disk")) ?>:</span>
					<span class="u-text-bold">
						<?= tohtml(humanize_usage_size($data[$key]["U_DISK"])) ?>
					</span>
					<span class="u-text-small">
						<?= tohtml(humanize_usage_measure($data[$key]["U_DISK"])) ?>
					</span>
				</div>
				<div class="units-table-cell u-text-center-desktop">
					<span class="u-hide-desktop u-text-bold"><?= tohtml( _("Bandwidth")) ?>:</span>
					<span class="u-text-bold">
						<?= tohtml(humanize_usage_size($data[$key]["U_BANDWIDTH"])) ?>
					</span>
					<span class="u-text-small">
						<?= tohtml(humanize_usage_measure($data[$key]["U_BANDWIDTH"])) ?>
					</span>
				</div>
				<div class="units-table-cell u-text-center-desktop">
					<span class="u-hide-desktop u-text-bold"><?= tohtml( _("SSL")) ?>:</span>
					<i class="fas <?= tohtml($icon_ssl) ?>" title="<?= tohtml($title_ssl) ?>"></i>
				</div>
				<div class="units-table-cell u-text-center-desktop">
					<span class="u-hide-desktop u-text-bold"><?= tohtml( _("Statistics")) ?>:</span>
					<i class="fas <?= tohtml($icon_webstats) ?>" title="<?= tohtml($title_webstats) ?>"></i>
				</div>
			</div>
		<?php } ?>
	</div>

	<div class="units-table-footer">
		<p>
			<?php printf(ngettext("%d web domain", "%d web domains", $i), $i); ?>
		</p>
	</div>

</div>

<!-- Modal: GitHub Web Site Deploy -->
<?php if (($_SESSION["userContext"] ?? "") === "admin"): ?>
<div id="github-web-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.65); z-index:9999; justify-content:center; align-items:center;" onclick="if(event.target===this) this.style.display='none';">
	<div class="form-container" style="background:var(--color-background, #fff); max-width:580px; width:92%; max-height:92vh; overflow-y:auto; border-radius:8px; padding:25px 30px; box-shadow:0 10px 30px rgba(0,0,0,0.5);">
		<h2 class="u-mb15"><i class="fab fa-github icon-blue"></i> <?= tohtml(__tr("Deploy Web Site from GitHub", "GitHub'dan Web Sitesi Kur")) ?></h2>
		<p class="u-text-muted u-mb20" style="font-size:0.9rem; line-height:1.4;">
			<?= tohtml(__tr("Select a repository from your GitHub organization, or pick the last option to deploy any public open-source GitHub repository by pasting its link (PHP, HTML, React, Node.js, .NET).", "GitHub organizasyonunuzdan bir site seçin ya da son seçenekle istediğiniz açık kaynak GitHub reposunun linkini girin (PHP, HTML, React, Node.js, .NET). Otomatik olarak kurulup yayına alınacaktır.")) ?>
		</p>

		<form method="post" action="/list/web/" onsubmit="if(!wzPrepare(this)) return false; const b = this.querySelector('button[type=submit]'); b.disabled=true; b.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> ' + ('<?= (($_SESSION['language'] ?? '') === 'tr') ? "Kuruluyor..." : "Deploying..." ?>');">
			<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
			<input type="hidden" name="deploy_repo" value="1">

			<div class="u-mb15">
				<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("GitHub Repository", "GitHub Deposu (Repo)")) ?></label>
				<select name="deploy_repo_name" id="deploy-repo-select" class="form-select" required style="width:100%;">
					<?php if (empty($github_repos) || isset($github_repos["error"])): ?>
						<option value=""><?= tohtml(__tr("-- No repos found / Token not set --", "-- Repo bulunamadı / Token ayarlanmadı --")) ?></option>
					<?php else: ?>
						<option value=""><?= tohtml(__tr("-- Select Repository --", "-- Depo Seçiniz --")) ?></option>
						<?php foreach ($github_repos as $rname => $rdata): ?>
							<option value="<?= tohtml($rname) ?>">
								<?= tohtml($rdata["NAME"] ?? $rname) ?> (<?= tohtml($rdata["LANGUAGE"] ?? "Web") ?>) <?= ($rdata["PRIVATE"] ?? "") === "yes" ? "🔒" : "" ?>
							</option>
						<?php endforeach; ?>
					<?php endif; ?>
					<option value="__custom__">🌍 <?= tohtml(__tr("Any public GitHub repo — paste link…", "İstediğim açık kaynak GitHub reposu — linkini gireceğim…")) ?></option>
				</select>
			</div>

			<div class="u-mb15" id="deploy-repo-url-wrap" style="display:none;">
				<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("Public GitHub Repository URL", "Açık Kaynak GitHub Repo Linki")) ?></label>
				<input type="text" name="deploy_repo_url" id="deploy-repo-url" placeholder="https://github.com/kullanici/proje" class="form-control" style="width:100%;">
				<small class="u-text-muted" style="display:block; margin-top:4px;">
					💡 <?= tohtml(__tr("Public (open-source) repositories only. A branch link like /tree/dev also sets the branch.", "Sadece herkese açık (open source) repolar. /tree/dal gibi bir link yapıştırırsanız dal da otomatik seçilir.")) ?>
				</small>
			</div>

			<div class="u-mb15" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; align-items:end;">
				<div>
					<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("Branch (optional)", "Dal (opsiyonel)")) ?></label>
					<input type="text" name="deploy_branch" id="deploy-branch" placeholder="main" class="form-control" style="width:100%;">
				</div>
				<div>
					<button type="button" class="button button-secondary" id="wz-analyze-btn" onclick="wzAnalyze()" style="width:100%;">
						<i class="fas fa-magic"></i> <?= tohtml(__tr("Analyze Repository", "Repoyu Analiz Et")) ?>
					</button>
				</div>
			</div>

			<div id="wz-result" class="u-mb15" style="display:none; max-height:38vh; overflow-y:auto;"></div>

			<input type="hidden" name="deploy_channel" id="deploy-channel" value="git">
			<input type="hidden" name="deploy_compose" id="deploy-compose" value="docker-compose.yml">
			<input type="hidden" name="deploy_env" id="deploy-env" value="">
			<input type="hidden" name="deploy_env_required" id="deploy-env-required" value="">

			<div class="u-mb15" id="wz-docker-name" style="display:none;">
				<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("Docker App Name", "Docker Uygulama Adı")) ?></label>
				<input type="text" name="deploy_app_name" id="deploy-app-name" class="form-control" placeholder="proje-adi" style="width:100%;">
				<small class="u-text-muted" style="display:block; margin-top:4px;">
					🐳 <?= tohtml(__tr("This project is a Docker Compose stack. It will be deployed as a multi-service Docker app; you will map domains to its services right after install. Required .env values are asked for after the install.", "Bu proje bir Docker Compose yığını. Çoklu servisli Docker uygulaması olarak kurulacak; kurulumdan hemen sonra servislerine domain eşleyeceksiniz. Gerekli .env değerleri kurulumdan sonra hatırlatılacak.")) ?>
				</small>
			</div>

			<div id="wz-git-fields">
				<div class="u-mb15">
					<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("Domain Name", "Alan Adı (Domain)")) ?></label>
					<input type="text" name="deploy_domain" id="deploy-domain" placeholder="neredeyasanir.localhost" required class="form-control" style="width:100%;">
					<small class="u-text-muted" style="display:block; margin-top:4px;">
						💡 <?= tohtml(__tr("For local testing, you can use domain.localhost (e.g. site.localhost:9080).", "Yerel test için alanadı.localhost (örn: neredeyasanir.localhost) yazabilirsiniz. Tarayıcınızda doğrudan http://neredeyasanir.localhost:9080 üzerinden açılır!")) ?>
					</small>
				</div>

				<div class="u-mb15" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
					<div>
						<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("User", "Kullanıcı")) ?></label>
						<input type="text" name="deploy_user" value="<?= tohtml($user_plain) ?>" class="form-control" style="width:100%;" readonly>
					</div>
					<div>
						<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("App Mode", "Uygulama Modu")) ?></label>
						<select name="deploy_mode" id="deploy-mode" class="form-select" style="width:100%;">
							<option value="auto"><?= tohtml(__tr("⚡ Auto-Detect (Smart)", "⚡ Otomatik Algıla (Akıllı)")) ?></option>
							<option value="php"><?= tohtml(__tr("🐘 PHP / HTML / Laravel", "🐘 PHP / HTML / Laravel")) ?></option>
							<option value="react"><?= tohtml(__tr("⚛️ React / Vite (SPA Dist)", "⚛️ React / Vite (SPA Dist)")) ?></option>
							<option value="node"><?= tohtml(__tr("🟢 Node.js / Next.js", "🟢 Node.js / Next.js")) ?></option>
							<option value="dotnet"><?= tohtml(__tr("🟣 .NET Core Web API / MVC", "🟣 .NET Core Web API / MVC")) ?></option>
						</select>
					</div>
				</div>
			</div>

			<div class="u-mt20" style="display:flex; justify-content:flex-end; gap:10px;">
				<button type="button" class="button button-secondary" onclick="document.getElementById('github-web-modal').style.display='none'">
					<?= tohtml(__tr("Cancel", "İptal")) ?>
				</button>
				<button type="submit" class="button button-primary">
					<i class="fas fa-rocket"></i> <?= tohtml(__tr("Deploy & Launch", "Kur & Yayına Al")) ?>
				</button>
			</div>
		</form>
	</div>
</div>
<script>
document.addEventListener('keydown', function(e) {
	if (e.key === 'Escape') {
		const m = document.getElementById('github-web-modal');
		if (m) m.style.display = 'none';
	}
});
(function() {
	const sel = document.getElementById('deploy-repo-select');
	const wrap = document.getElementById('deploy-repo-url-wrap');
	const urlInput = document.getElementById('deploy-repo-url');
	if (!sel || !wrap || !urlInput) return;
	function toggleCustomRepoUrl() {
		const custom = sel.value === '__custom__';
		wrap.style.display = custom ? '' : 'none';
		urlInput.required = custom;
	}
	sel.addEventListener('change', toggleCustomRepoUrl);
	toggleCustomRepoUrl();
})();

/* ---- smart wizard: analyze repo, render plan + .env form ---- */
let wzAnalysis = null;
function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
function wzCurrentRepo() {
	const sel = document.getElementById('deploy-repo-select');
	if (!sel) return '';
	return sel.value === '__custom__'
		? document.getElementById('deploy-repo-url').value.trim()
		: sel.value;
}
function wzAnalyze() {
	const repo = wzCurrentRepo();
	const branch = (document.getElementById('deploy-branch').value || '').trim();
	const result = document.getElementById('wz-result');
	const btn = document.getElementById('wz-analyze-btn');
	if (!repo || repo === '') {
		result.style.display = '';
		result.innerHTML = '<div class="u-text-muted">⚠️ <?= tohtml(__tr("Select or paste a repository first.", "Önce bir repo seçin ya da linkini girin.")) ?></div>';
		return;
	}
	btn.disabled = true;
	btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?= tohtml(__tr("Analyzing…", "Analiz ediliyor…")) ?>';
	result.style.display = '';
	result.innerHTML = '<div class="u-text-muted"><i class="fas fa-spinner fa-spin"></i> <?= tohtml(__tr("Cloning & scanning repository…", "Repo klonlanıp taranıyor…")) ?></div>';
	fetch('/list/web/?ajax=analyze_repo&token=' + encodeURIComponent(document.querySelector('#github-web-modal input[name=token]').value) +
		'&repo=' + encodeURIComponent(repo) + '&branch=' + encodeURIComponent(branch || 'main'))
		.then(r => r.json())
		.then(d => {
			btn.disabled = false;
			btn.innerHTML = '<i class="fas fa-magic"></i> <?= tohtml(__tr("Analyze Repository", "Repoyu Analiz Et")) ?>';
			if (!d || d.ok !== true) {
				wzAnalysis = null;
				result.innerHTML = '<div class="u-text-muted">⚠️ <?= tohtml(__tr("Analysis failed. You can still deploy without it.", "Analiz başarısız. Yine de kuruluma devam edebilirsiniz.")) ?></div>';
				return;
			}
			wzAnalysis = d;
			wzRender(d);
		})
		.catch(() => {
			btn.disabled = false;
			btn.innerHTML = '<i class="fas fa-magic"></i> <?= tohtml(__tr("Analyze Repository", "Repoyu Analiz Et")) ?>';
			result.innerHTML = '<div class="u-text-muted">⚠️ <?= tohtml(__tr("Analysis failed. You can still deploy without it.", "Analiz başarısız. Yine de dağıtıma devam edebilirsiniz.")) ?></div>';
		});
}
function wzRender(d) {
	const result = document.getElementById('wz-result');
	const plat = d.platform || {};
	const isDocker = plat.channel === 'docker';
	let h = '<div style="border:1px solid #d8dee9; border-radius:8px; padding:12px 14px; background:rgba(0,0,0,0.02);">';
	h += '<div style="font-size:1.05rem; font-weight:600; margin-bottom:6px;">' + esc(plat.icon || '📦') + ' ' + esc(plat.name) +
		' <span class="u-text-muted" style="font-weight:400; font-size:0.85rem;">· ' + esc(d.summary_tr || '') + '</span></div>';
	/* components */
	(d.components || []).forEach(c => {
		const ports = (c.ports || []).map(p => ':' + p.published).join('');
		h += '<div style="margin:3px 0;">' + esc(c.icon || '•') + ' <b>' + esc(c.name) + '</b>' +
			' <span class="u-text-muted">(' + esc(c.type) + (c.tech ? ' · ' + esc(String(c.tech).split(' / ')[0]) : '') + (c.entry && c.entry !== 'index.php' ? ' · giriş: ' + esc(c.entry) : '') + (ports ? ' · port ' + esc(ports) : '') + ')</span>' +
			(c.healthcheck ? ' <span title="healthcheck var" style="color:#43a047;">✔</span>' : '') + '</div>';
	});
	/* communication map */
	if ((d.communication || []).length) {
		h += '<div style="margin-top:8px; font-size:0.88rem;"><b>🔗 <?= tohtml(__tr("Communication", "İletişim")) ?></b>';
		d.communication.slice(0, 12).forEach(e => {
			h += '<div class="u-text-muted">' + esc(e.from) + ' → <b>' + esc(e.to) + '</b> <span style="opacity:.75;">(' + esc(e.via) + ')</span></div>';
		});
		h += '</div>';
	}
	/* database + seeds */
	if (d.database && d.database.needed) {
		h += '<div style="margin-top:8px;">🗄️ <b><?= tohtml(__tr("Database", "Veritabanı")) ?>:</b> ' + esc(d.database.engine) +
			' — ' + esc(d.database.provision) + (d.database.auto ? ' <span style="color:#43a047;">(<?= tohtml(__tr("automatic", "otomatik")) ?>)</span>' : '') + '</div>';
	}
	if ((d.seeds || []).length) {
		h += '<div>🌱 <b><?= tohtml(__tr("Seed data", "Seed verisi")) ?>:</b> ' + d.seeds.map(s => esc(s)).join(', ') + '</div>';
	}
	/* warnings */
	(d.warnings || []).slice(0, 10).forEach(w => {
		const colors = { error: ['#ffebee', '#c62828'], warn: ['#fff8e1', '#ef6c00'], info: ['#eceff1', '#546e7a'] };
		const c = colors[w.level] || colors.info;
		h += '<div style="margin-top:6px; padding:6px 10px; border-radius:6px; background:' + c[0] + '; color:' + c[1] + '; font-size:0.85rem;">' +
			(w.level === 'error' ? '⛔' : w.level === 'warn' ? '⚠️' : 'ℹ️') + ' <b>' + esc(w.message) + '</b>' +
			(w.hint ? '<br><span style="opacity:.85;">' + esc(w.hint) + '</span>' : '') + '</div>';
	});
	/* env summary (no inline inputs — asked again post-deploy) + stash required keys */
	const vars = (d.env_template && d.env_template.vars) || [];
	const reqKeys = vars.filter(v => v.required && !v.auto).map(v => v.key);
	document.getElementById('deploy-env-required').value = reqKeys.join(',');
	if (vars.length) {
		const autos = vars.filter(v => v.auto).length;
		h += '<div style="margin-top:8px; font-size:0.88rem; border-top:1px dashed #cfd8dc; padding-top:8px;">🔑 <b>.env</b>' +
			(reqKeys.length
				? ' — ' + reqKeys.length + ' <?= tohtml(__tr("required value(s)", "zorunlu değer")) ?>: ' + reqKeys.slice(0, 6).map(esc).join(', ') + (reqKeys.length > 6 ? '…' : '')
				: ' — <?= tohtml(__tr("no required values", "zorunlu değer yok")) ?>') +
			(autos ? ' · ' + autos + ' <?= tohtml(__tr("auto-filled", "otomatik dolacak")) ?>' : '') +
			'<br><span class="u-text-muted"><?= tohtml(__tr("You will be reminded to fill these right after the install.", "Kurulumdan sonra hangilerini dolduracağın hatırlatılacak.")) ?></span></div>';
	}
	h += '</div>';
	result.innerHTML = h;
	/* channel switch */
	document.getElementById('deploy-channel').value = isDocker ? 'docker' : 'git';
	document.getElementById('wz-git-fields').style.display = isDocker ? 'none' : '';
	document.getElementById('wz-docker-name').style.display = isDocker ? '' : 'none';
	document.getElementById('deploy-domain').required = !isDocker;
	if (isDocker && plat.compose) document.getElementById('deploy-compose').value = plat.compose;
	if (isDocker && !document.getElementById('deploy-app-name').value) {
		const m = wzCurrentRepo().match(/([A-Za-z0-9_.-]+)(?:\.git)?\/?$/);
		if (m) document.getElementById('deploy-app-name').value = m[1].toLowerCase().replace(/[^a-z0-9-]+/g, '-');
	}
	if (!isDocker && plat.mode && plat.mode !== 'python-unsupported') {
		document.getElementById('deploy-mode').value = plat.mode;
	}
}
function wzPrepare(form) {
	/* env values are NOT collected here anymore — post-deploy notice lists them */
	document.getElementById('deploy-env').value = '';
	/* if docker channel was detected but user left app name empty, block */
	if (document.getElementById('deploy-channel').value === 'docker') {
		const name = document.getElementById('deploy-app-name').value.trim();
		if (!name) { alert('<?= tohtml(__tr("Docker app name required", "Docker uygulama adı gerekli")) ?>'); return false; }
		if (!document.getElementById('deploy-app-name').value.match(/^[a-zA-Z0-9-]{1,32}$/)) {
			alert('<?= tohtml(__tr("App name: letters, digits and dashes only", "Uygulama adı: sadece harf, rakam ve tire")) ?>');
			return false;
		}
	}
	return true;
}
</script>
<?php endif; ?>
