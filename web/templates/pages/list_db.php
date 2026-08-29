<?php
[$http_host, $port] = explode(":", $_SERVER["HTTP_HOST"] . ":");

$pma_host = $http_host;
// In local dev/Docker or IP access, pick the first configured domain or map web port 9080
if (filter_var($http_host, FILTER_VALIDATE_IP) || $http_host === "localhost" || $http_host === "127.0.0.1") {
	$found_domains = glob("/home/" . ($user ?? "admin") . "/web/*");
	if (!empty($found_domains) && is_dir($found_domains[0])) {
		$pma_host = basename($found_domains[0]) . ":9080";
	} elseif ($port == "8083") {
		$pma_host = $http_host . ":9080";
	}
}

$db_myadmin_link = "//" . $pma_host . "/phpmyadmin/";
$db_pgadmin_link = "//" . $pma_host . "/phppgadmin/";

if (!empty($_SESSION["DB_PMA_ALIAS"])) {
	$db_myadmin_link = "//" . $pma_host . "/" . $_SESSION["DB_PMA_ALIAS"] . "/";
}
if (!empty($_SESSION["DB_PGA_ALIAS"])) {
	$db_pgadmin_link = "//" . $pma_host . "/" . $_SESSION["DB_PGA_ALIAS"] . "/";
}

$first_mysql_db = null;
$first_pgsql_db = null;
if (!empty($data) && is_array($data)) {
	foreach ($data as $db_name => $db_info) {
		if (!$first_mysql_db && ($db_info['TYPE'] ?? '') === 'mysql' && ($db_info['SUSPENDED'] ?? '') !== 'yes') {
			$first_mysql_db = $db_name;
		}
		if (!$first_pgsql_db && ($db_info['TYPE'] ?? '') === 'pgsql' && ($db_info['SUSPENDED'] ?? '') !== 'yes') {
			$first_pgsql_db = $db_name;
		}
	}
}

$top_pma_link = $db_myadmin_link;
if ($first_mysql_db && !empty($_SESSION['PHPMYADMIN_KEY']) && !ipUsed()) {
	$time = time();
	$hestia_sso_token = password_hash(
		$first_mysql_db . $user_plain . $_SESSION['user_combined_ip'] . $time . $_SESSION['PHPMYADMIN_KEY'],
		PASSWORD_DEFAULT,
	);
	$top_pma_link = $db_myadmin_link . "hestia-sso.php?" . http_build_query([
		"database" => $first_mysql_db,
		"user" => $user_plain,
		"exp" => $time,
		"hestia_token" => $hestia_sso_token,
	]);
}

$top_pga_link = $db_pgadmin_link;
if ($first_pgsql_db && !empty($_SESSION['PGA_SSO_KEY']) && !ipUsed()) {
	$time = time();
	$hestia_pga_sso_token = password_hash(
		$first_pgsql_db . $user_plain . $_SESSION['user_combined_ip'] . $time . $_SESSION['PGA_SSO_KEY'],
		PASSWORD_DEFAULT,
	);
	$top_pga_link = $db_pgadmin_link . "hestia-sso.php?" . http_build_query([
		"database" => $first_pgsql_db,
		"user" => $user_plain,
		"exp" => $time,
		"token" => $hestia_pga_sso_token,
	]);
}
?>

<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<?php if ($read_only !== "true") { ?>
				<a href="/add/db/" class="button button-secondary js-button-create">
					<i class="fas fa-circle-plus icon-green"></i><?= tohtml( _("Add Database")) ?>
				</a>
				<?php if ($_SESSION["DB_SYSTEM"] === "mysql" || $_SESSION["DB_SYSTEM"] === "mysql,pgsql" || $_SESSION["DB_SYSTEM"] === "pgsql,mysql") { ?>
					<a class="button button-secondary <?= tohtml(ipUsed() ? "button-suspended" : "") ?>" href="<?= tohtml($top_pma_link) ?>" target="_blank" title="<?= tohtml($first_mysql_db && !empty($_SESSION['PHPMYADMIN_KEY']) ? "phpMyAdmin (SSO: " . $first_mysql_db . ")" : "phpMyAdmin") ?>">
						<i class="fas fa-database icon-orange"></i>phpMyAdmin
					</a>
				<?php } ?>
				<?php if ($_SESSION["DB_SYSTEM"] === "pgsql" || $_SESSION["DB_SYSTEM"] === "mysql,pgsql" || $_SESSION["DB_SYSTEM"] === "pgsql,mysql") { ?>
					<a class="button button-secondary <?= tohtml(ipUsed() ? "button-suspended" : "") ?>" href="<?= tohtml($top_pga_link) ?>" target="_blank" title="<?= tohtml($first_pgsql_db && !empty($_SESSION['PGA_SSO_KEY']) ? "phpPgAdmin (SSO: " . $first_pgsql_db . ")" : "phpPgAdmin") ?>">
						<i class="fas fa-database icon-orange"></i>phpPgAdmin
					</a>
				<?php } ?>
				<!-- Auto-Sync & Discover Databases Button -->
				<form method="post" action="/list/db/" style="display:inline;" onsubmit="const b = this.querySelector('button'); b.disabled=true; b.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> ' + ('<?= (($_SESSION['language'] ?? '') === 'tr') ? "Taranıyor..." : "Scanning..." ?>');">
					<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
					<input type="hidden" name="action_sync_db" value="1">
					<button type="submit" class="button button-secondary" title="<?= tohtml(__tr("Scan MySQL/Postgres for unmapped databases and link them", "Ekli olmayan tüm veritabanlarını otomatik tara ve bağla")) ?>">
						<i class="fas fa-arrows-rotate icon-blue"></i> <?= tohtml(__tr("Auto-Sync DBs", "Veritabanlarını Tara")) ?>
					</button>
				</form>
				<?php if (ipUsed()) { ?>
					<a target="_blank" href="https://hestiacp.com/docs/server-administration/databases.html#why-i-can-t-use-http-ip-phpmyadmin">
						<i class="fas fa-circle-question"></i>
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
					<li data-entity="sort-charset">
						<span class="name"><?= tohtml( _("Charset")) ?> <i class="fas fa-arrow-down-a-z"></i></span><span class="up"><i class="fas fa-arrow-up-a-z"></i></span>
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
					<li data-entity="sort-server">
						<span class="name"><?= tohtml( _("Host")) ?> <i class="fas fa-arrow-down-a-z"></i></span><span class="up"><i class="fas fa-arrow-up-a-z"></i></span>
					</li>
					<li data-entity="sort-user">
						<span class="name"><?= tohtml( _("Username")) ?> <i class="fas fa-arrow-down-a-z"></i></span><span class="up"><i class="fas fa-arrow-up-a-z"></i></span>
					</li>
				</ul>
				<?php if ($read_only !== "true") { ?>
					<form x-data x-bind="BulkEdit" action="/bulk/db/" method="post">
						<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
						<select class="form-select" name="action">
							<option value=""><?= tohtml( _("Apply to selected")) ?></option>
							<?php if ($_SESSION["userContext"] === "admin") { ?>
								<option value="rebuild"><?= tohtml( _("Rebuild All")) ?></option>
								<option value="suspend"><?= tohtml( _("Suspend All")) ?></option>
								<option value="unsuspend"><?= tohtml( _("Unsuspend All")) ?></option>
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
					<input type="search" class="form-control js-search-input" name="q" value="<?= tohtml($_POST['q'] ?? '') ?>" title="<?= tohtml( _("Search")) ?>">
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

	<h1 class="u-text-center u-hide-desktop u-mt20 u-pr30 u-mb20 u-pl30"><?= tohtml( _("Databases")) ?></h1>

	<div class="units-table js-units-container">
		<div class="units-table-header">
			<div class="units-table-cell">
				<input type="checkbox" class="js-toggle-all-checkbox" title="<?= tohtml( _("Select all")) ?>" <?= tohtml($display_mode) ?>>
			</div>
			<div class="units-table-cell"><?= tohtml( _("Name")) ?></div>
			<div class="units-table-cell"></div>
			<div class="units-table-cell u-text-center"><?= tohtml( _("Disk")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml( _("Type")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml( _("Username")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml( _("Hostname")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml( _("Charset")) ?></div>
		</div>

		<!-- Begin database list item loop -->
		<?php
			list($http_host, $port) = explode(':', $_SERVER["HTTP_HOST"].":");
			foreach ($data as $key => $value) {
				++$i;
				if ($data[$key]['SUSPENDED'] == 'yes') {
					$status = 'suspended';
					$spnd_action = 'unsuspend';
					$spnd_action_title = _('Unsuspend');
					$spnd_icon = 'fa-play';
					$spnd_icon_class = 'icon-green';
					$spnd_confirmation = _('Are you sure you want to unsuspend database %s?') ;
				} else {
					$status = 'active';
					$spnd_action = 'suspend';
					$spnd_action_title = _('Suspend');
					$spnd_icon = 'fa-pause';
					$spnd_icon_class = 'icon-highlight';
					$spnd_confirmation = _('Are you sure you want to suspend database %s?') ;
				}
				if ($data[$key]['HOST'] != 'localhost' ) $http_host = $data[$key]['HOST'];
				if ($data[$key]['TYPE'] == 'mysql') $db_admin = "phpMyAdmin";
				if ($data[$key]['TYPE'] == 'mysql') $db_admin_link = "https://".$http_host."/phpmyadmin/";
				if (($data[$key]['TYPE'] == 'mysql') && (!empty($_SESSION['DB_PMA_ALIAS']))) $db_admin_link = $_SESSION['DB_PMA_ALIAS'];
				if ($data[$key]['TYPE'] == 'pgsql') $db_admin = "phpPgAdmin";
				if ($data[$key]['TYPE'] == 'pgsql') $db_admin_link = "https://".$http_host."/phppgadmin/";
				if (($data[$key]['TYPE'] == 'pgsql') && (!empty($_SESSION['DB_PGA_ALIAS']))) $db_admin_link = $_SESSION['DB_PGA_ALIAS'];
			?>
			<div class="units-table-row <?php if ($data[$key]['SUSPENDED'] == 'yes') echo 'disabled'; ?> js-unit"
				data-sort-date="<?= tohtml(strtotime($data[$key]['DATE'].' '.$data[$key]['TIME'])) ?>"
				data-sort-name="<?= tohtml($key) ?>"
				data-sort-disk="<?= tohtml($data[$key]["U_DISK"]) ?>"
				data-sort-user="<?= tohtml($data[$key]["DBUSER"]) ?>"
				data-sort-server="<?= tohtml($data[$key]["HOST"]) ?>"
				data-sort-charset="<?= tohtml($data[$key]["CHARSET"]) ?>">
				<div class="units-table-cell">
					<div>
						<input id="check<?= tohtml($i) ?>" class="js-unit-checkbox" type="checkbox" title="<?= tohtml( _("Select")) ?>" name="database[]" value="<?= tohtml($key) ?>" <?= tohtml($display_mode) ?>>
						<label for="check<?= tohtml($i) ?>" class="u-hide-desktop"><?= tohtml( _("Select")) ?></label>
					</div>
				</div>
				<div class="units-table-cell units-table-heading-cell u-text-bold">
					<span class="u-hide-desktop"><?= tohtml( _("Name")) ?>:</span>
					<?php if ($read_only === "true" || $data[$key]["SUSPENDED"] == "yes") { ?>
						<?= tohtml($key) ?>
					<?php } else { ?>
						<a href="/edit/db/?<?= tohtml(http_build_query(["database" => $key, "token" => $_SESSION["token"]])) ?>" title="<?= tohtml( _("Edit Database")) ?>: <?= tohtml($key) ?>">
							<?= tohtml($key) ?>
						</a>
					<?php } ?>
				</div>
				<div class="units-table-cell">
					<?php if (!$read_only) { ?>
						<ul class="units-table-row-actions">
							<?php if ($data[$key]["SUSPENDED"] == "no") { ?>
								<li class="units-table-row-action shortcut-enter" data-key-action="href">
									<a
										class="units-table-row-action-link"
											href="/edit/db/?<?= tohtml(http_build_query(["database" => $key, "token" => $_SESSION["token"]])) ?>"
										title="<?= tohtml( _("Edit Database")) ?>"
									>
										<i class="fas fa-pencil icon-orange"></i>
										<span class="u-hide-desktop"><?= tohtml( _("Edit Database")) ?></span>
									</a>
								</li>
							<?php } ?>
								<?php if ($data[$key]['TYPE'] == 'mysql' && isset($_SESSION['PHPMYADMIN_KEY']) && $_SESSION['PHPMYADMIN_KEY'] != '' && !ipUsed()) { $time = time(); ?>
									<?php
										$hestia_sso_token = password_hash(
											$key . $user_plain . $_SESSION['user_combined_ip'] . $time . $_SESSION['PHPMYADMIN_KEY'],
											PASSWORD_DEFAULT,
										);
										$hestia_sso_url = $db_myadmin_link . "hestia-sso.php?" . http_build_query([
											"database" => $key,
											"user" => $user_plain,
											"exp" => $time,
											"hestia_token" => $hestia_sso_token,
										]);
									?>
									<li class="units-table-row-action shortcut-enter" data-key-action="href">
									<a
										class="units-table-row-action-link"
										href="<?= tohtml($hestia_sso_url) ?>"
										title="phpMyAdmin" target="_blank"
									>
									<i class="fas fa-right-to-bracket icon-orange"></i>
									<span class="u-hide-desktop">phpMyAdmin</span>
								</a>
							</li>
						<?php } ?>
							<?php if ($data[$key]['TYPE'] == 'pgsql' && isset($_SESSION['PGA_SSO_KEY']) && $_SESSION['PGA_SSO_KEY'] != '' && !ipUsed()) { $time = time(); ?>
								<?php
									$hestia_pga_sso_token = password_hash(
										$key . $user_plain . $_SESSION['user_combined_ip'] . $time . $_SESSION['PGA_SSO_KEY'],
										PASSWORD_DEFAULT,
									);
									$hestia_pga_sso_url = $db_pgadmin_link . "hestia-sso.php?" . http_build_query([
										"database" => $key,
										"user" => $user_plain,
										"exp" => $time,
										"token" => $hestia_pga_sso_token,
									]);
								?>
								<li class="units-table-row-action shortcut-enter" data-key-action="href">
									<a
										class="units-table-row-action-link"
										href="<?= tohtml($hestia_pga_sso_url) ?>"
										title="phpPgAdmin" target="_blank"
									>
									<i class="fas fa-right-to-bracket icon-orange"></i>
									<span class="u-hide-desktop">phpPgAdmin</span>
								</a>
							</li>
						<?php } ?>
							<li class="units-table-row-action" data-key-action="js">
								<button type="button" class="units-table-row-action-link" style="background:none; border:none; cursor:pointer; padding:0; display:flex; align-items:center; gap:4px;" onclick="openDbStudio('<?= tohtml(addslashes($key)) ?>');" title="<?= tohtml(__tr("Explore Database & View Tables (Nexvia DB Studio)", "Tabloları ve Verileri İncele (DB Studio)")) ?>">
									<i class="fas fa-table-columns icon-purple"></i>
									<span class="u-hide-desktop"><?= tohtml(__tr("Explore", "İncele")) ?></span>
								</button>
							</li>
							<li class="units-table-row-action shortcut-enter" data-key-action="href">
								<a
									class="units-table-row-action-link"
									href="/download/database/?<?= tohtml(http_build_query(["database" => $key, "token" => $_SESSION["token"]])) ?>"
									title="<?= tohtml( _("Download Database")) ?>"
								>
									<i class="fas fa-download icon-orange"></i>
									<span class="u-hide-desktop"><?= tohtml( _("Download Database")) ?></span>
								</a>
							</li>
							<li class="units-table-row-action shortcut-s" data-key-action="js">
								<a
									class="units-table-row-action-link data-controls js-confirm-action"
									href="/<?= tohtml($spnd_action) ?>/db/?<?= tohtml(http_build_query(["database" => $key, "token" => $_SESSION["token"]])) ?>"
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
									href="/delete/db/?<?= tohtml(http_build_query(["database" => $key, "token" => $_SESSION["token"]])) ?>"
									title="<?= tohtml( _("Delete")) ?>"
									data-confirm-title="<?= tohtml( _("Delete")) ?>"
									data-confirm-message="<?= tohtml(sprintf(_("Are you sure you want to delete database %s?"), $key)) ?>"
								>
									<i class="fas fa-trash icon-red"></i>
									<span class="u-hide-desktop"><?= tohtml( _("Delete")) ?></span>
								</a>
							</li>
						</ul>
					<?php } ?>
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
					<span class="u-hide-desktop u-text-bold"><?= tohtml( _("Type")) ?>:</span>
					<?= tohtml($data[$key]["TYPE"]) ?>
				</div>
				<div class="units-table-cell u-text-bold u-text-center-desktop">
					<span class="u-hide-desktop"><?= tohtml( _("Username")) ?>:</span>
					<?= tohtml($data[$key]["DBUSER"]) ?>
				</div>
				<div class="units-table-cell u-text-bold u-text-center-desktop">
					<span class="u-hide-desktop"><?= tohtml( _("Hostname")) ?>:</span>
					<?= tohtml($data[$key]["HOST"]) ?>
				</div>
				<div class="units-table-cell u-text-center-desktop">
					<span class="u-hide-desktop u-text-bold"><?= tohtml( _("Charset")) ?>:</span>
					<?= tohtml($data[$key]["CHARSET"]) ?>
				</div>
			</div>
		<?php } ?>
	</div>

	<div class="units-table-footer">
		<p>
			<?php printf(ngettext("%d database", "%d databases", $i), $i); ?>
		</p>
	</div>

</div>

<!-- Nexvia DB Studio Modal -->
<div id="db-studio-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.75); z-index:99999; justify-content:center; align-items:center;" onclick="if(event.target===this) closeDbStudio();">
	<div style="background:var(--color-background, #1e222d); width:95%; max-width:1250px; height:85vh; border-radius:10px; border:1px solid var(--border-color, #334155); display:flex; flex-direction:column; overflow:hidden; box-shadow:0 20px 50px rgba(0,0,0,0.6);">
		
		<!-- Modal Header -->
		<div style="padding:14px 20px; background:rgba(0,0,0,0.15); border-bottom:1px solid var(--border-color, #334155); display:flex; justify-content:space-between; align-items:center;">
			<div style="display:flex; align-items:center; gap:10px;">
				<div style="width:32px; height:32px; border-radius:6px; background:rgba(168,85,247,0.15); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-table-columns icon-purple"></i>
				</div>
				<div>
					<h3 style="margin:0; font-size:1.1rem; font-weight:bold; display:flex; align-items:center; gap:8px;">
						Nexvia DB Studio: <span id="studio-db-title" style="color:var(--icon-color-blue, #38bdf8);"></span>
					</h3>
					<small class="u-text-muted" style="font-size:11px;"><?= tohtml(__tr("Lightweight In-Panel Database & Table Explorer", "Dahili Veritabanı ve Tablo İnceleme Gezgini")) ?></small>
				</div>
			</div>
			<div style="display:flex; gap:10px; align-items:center;">
				<button type="button" class="button button-secondary button-small" onclick="loadDbSchema();" title="<?= tohtml(__tr("Refresh Schema", "Yenile")) ?>">
					<i class="fas fa-arrows-rotate"></i>
				</button>
				<button type="button" class="button button-secondary button-small" onclick="closeDbStudio();" style="padding:5px 10px;">
					<i class="fas fa-xmark"></i>
				</button>
			</div>
		</div>

		<!-- Modal Body (Two-Column Layout) -->
		<div style="display:flex; flex:1; overflow:hidden;">
			
			<!-- Left Pane: Tables List -->
			<div style="width:280px; border-right:1px solid var(--border-color, #334155); background:rgba(0,0,0,0.05); display:flex; flex-direction:column;">
				<div style="padding:10px 12px; border-bottom:1px solid var(--border-color, #334155);">
					<input type="text" id="studio-table-search" class="form-control" placeholder="<?= tohtml(__tr("Filter tables...", "Tablo filtrele...")) ?>" onkeyup="filterStudioTables();" style="width:100%; font-size:12px; padding:6px 8px;">
				</div>
				<div id="studio-tables-container" style="flex:1; overflow-y:auto; padding:6px;">
					<div style="padding:20px; text-align:center; color:var(--color-text-muted);">
						<i class="fas fa-spinner fa-spin fa-lg"></i>
					</div>
				</div>
			</div>

			<!-- Right Pane: Table Inspector & Data Grid -->
			<div style="flex:1; display:flex; flex-direction:column; background:var(--color-background, #1e222d); overflow:hidden;">
				
				<!-- Right Header & Tabs -->
				<div style="padding:10px 18px; border-bottom:1px solid var(--border-color, #334155); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
					<div style="display:flex; align-items:center; gap:8px;">
						<i class="fas fa-table icon-blue"></i>
						<strong id="studio-active-table-name" style="font-size:1.05rem;">--</strong>
						<span id="studio-active-table-badge" class="badge badge-info" style="font-size:11px;">0 <?= tohtml(__tr("rows", "satır")) ?></span>
					</div>
					<div style="display:flex; gap:6px;">
						<button type="button" id="tab-btn-data" class="button button-primary button-small" onclick="switchStudioTab('data');" style="padding:4px 10px; font-size:12px;">
							<i class="fas fa-table-cells"></i> <?= tohtml(__tr("Data (50 Rows)", "Veri Önizleme")) ?>
						</button>
						<button type="button" id="tab-btn-schema" class="button button-secondary button-small" onclick="switchStudioTab('schema');" style="padding:4px 10px; font-size:12px;">
							<i class="fas fa-diagram-project"></i> <?= tohtml(__tr("Structure", "Tablo Yapısı")) ?>
						</button>
						<button type="button" id="tab-btn-sql" class="button button-secondary button-small" onclick="switchStudioTab('sql');" style="padding:4px 10px; font-size:12px;">
							<i class="fas fa-terminal"></i> SQL
						</button>
					</div>
				</div>

				<!-- Tab 1: Data Grid View -->
				<div id="studio-view-data" style="flex:1; overflow:auto; padding:12px;">
					<div id="studio-data-table-wrapper" style="min-width:100%;">
						<p class="u-text-muted" style="text-align:center; margin-top:40px;">
							<?= tohtml(__tr("Select a table from the left sidebar to inspect records.", "Kayıtları incelemek için soldaki listeden bir tablo seçin.")) ?>
						</p>
					</div>
				</div>

				<!-- Tab 2: Schema Columns View -->
				<div id="studio-view-schema" style="flex:1; overflow:auto; padding:12px; display:none;">
					<div id="studio-schema-table-wrapper">
						<p class="u-text-muted" style="text-align:center; margin-top:40px;">
							<?= tohtml(__tr("Select a table to view column structure.", "Sütun yapısını görmek için bir tablo seçin.")) ?>
						</p>
					</div>
				</div>

				<!-- Tab 3: Custom SQL Query Console -->
				<div id="studio-view-sql" style="flex:1; display:flex; flex-direction:column; padding:12px; display:none; gap:10px;">
					<div style="display:flex; gap:10px;">
						<textarea id="studio-custom-sql-input" class="form-control" rows="3" placeholder="SELECT * FROM table_name WHERE id > 0 LIMIT 25;" style="flex:1; font-family:monospace; font-size:12px;"></textarea>
						<button type="button" class="button button-primary" onclick="runStudioCustomSql();" style="padding:10px 18px;">
							<i class="fas fa-play"></i> <?= tohtml(__tr("Run", "Çalıştır")) ?>
						</button>
					</div>
					<div id="studio-sql-results-wrapper" style="flex:1; overflow:auto; border:1px solid var(--border-color, #334155); border-radius:6px; padding:8px;">
						<p class="u-text-muted" style="text-align:center; margin-top:20px;">
							<?= tohtml(__tr("Write a read-only SELECT query and press Run.", "Okuma amaçlı bir SELECT sorgusu yazıp Çalıştır'a basın.")) ?>
						</p>
					</div>
				</div>

			</div>
		</div>

	</div>
</div>

<script>
let currentStudioDb = '';
let currentStudioTable = '';
let currentStudioData = null;
let studioTables = [];

function openDbStudio(dbName) {
	currentStudioDb = dbName;
	currentStudioTable = '';
	document.getElementById('studio-db-title').innerText = dbName;
	document.getElementById('studio-active-table-name').innerText = '--';
	document.getElementById('studio-active-table-badge').innerText = '0 rows';
	document.getElementById('db-studio-modal').style.display = 'flex';
	loadDbSchema();
}

function closeDbStudio() {
	document.getElementById('db-studio-modal').style.display = 'none';
}

function loadDbSchema(selectedTable = '') {
	const container = document.getElementById('studio-tables-container');
	container.innerHTML = '<div style="padding:20px; text-align:center; color:var(--color-text-muted);"><i class="fas fa-spinner fa-spin fa-lg"></i></div>';

	const formData = new FormData();
	formData.append('action_explore_db', '1');
	formData.append('db_name', currentStudioDb);
	formData.append('table_name', selectedTable);
	formData.append('token', '<?= tohtml($_SESSION["token"]) ?>');

	fetch('/list/db/', { method: 'POST', body: formData })
		.then(r => r.json())
		.then(data => {
			currentStudioData = data;
			renderStudioTablesList(data.tables || []);
			if (selectedTable) {
				renderStudioTableData(data);
			} else if (data.tables && data.tables.length > 0) {
				selectTable(0);
			} else {
				document.getElementById('studio-data-table-wrapper').innerHTML = '<p class="u-text-muted" style="text-align:center; margin-top:30px;"><?= tohtml(__tr("No tables found in this database.", "Bu veritabanında henüz tablo bulunmuyor.")) ?></p>';
			}
		})
		.catch(err => {
			container.innerHTML = '<p style="color:#ef4444; padding:10px; font-size:12px;">Hata: ' + escapeHtml(err) + '</p>';
		});
}

function renderStudioTablesList(tables) {
	studioTables = tables;
	const container = document.getElementById('studio-tables-container');
	if (tables.length === 0) {
		container.innerHTML = '<p class="u-text-muted" style="font-size:12px; padding:10px; text-align:center;"><?= tohtml(__tr("No tables", "Tablo yok")) ?></p>';
		return;
	}

	let html = '<ul style="list-style:none; margin:0; padding:0;">';
	tables.forEach((t, i) => {
		const isActive = t.name === currentStudioTable;
		html += `
			<li class="studio-table-item" data-table="${escapeHtml(t.name.toLowerCase())}" style="margin-bottom:2px;">
				<a href="javascript:void(0);" onclick="selectTable(${i});" style="display:flex; justify-content:space-between; align-items:center; padding:7px 10px; border-radius:6px; font-size:12px; font-weight:${isActive ? 'bold' : 'normal'}; text-decoration:none; color:var(--color-text); background:${isActive ? 'rgba(56,189,248,0.15)' : 'transparent'};">
					<span style="display:flex; align-items:center; gap:6px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
						<i class="fas fa-table" style="color:${isActive ? 'var(--icon-color-blue, #38bdf8)' : 'var(--color-text-muted)'}; font-size:11px;"></i>
						${escapeHtml(t.name)}
					</span>
					<span style="font-size:10px; font-family:monospace; background:rgba(0,0,0,0.15); padding:1px 5px; border-radius:4px; color:var(--color-text-muted);">${escapeHtml(t.rows)}</span>
				</a>
			</li>
		`;
	});
	html += '</ul>';
	container.innerHTML = html;
}

function selectTable(tableIndex) {
	const table = studioTables[tableIndex];
	if (!table) return;
	const tableName = table.name;
	currentStudioTable = tableName;
	document.getElementById('studio-active-table-name').innerText = tableName;
	loadDbSchema(tableName);
}

function renderStudioTableData(data) {
	document.getElementById('studio-active-table-badge').innerText = (data.total_rows || 0) + ' <?= tohtml(__tr("rows shown", "satır gösteriliyor")) ?>';

	// 1. Render Data Grid
	const dataWrapper = document.getElementById('studio-data-table-wrapper');
	if (!data.rows || data.rows.length === 0) {
		dataWrapper.innerHTML = '<p class="u-text-muted" style="text-align:center; padding:30px;"><?= tohtml(__tr("Table is empty (0 records).", "Tabloda kayıt bulunmuyor (0 kayıt).")) ?></p>';
	} else {
		let tableHtml = '<table class="table" style="width:100%; font-size:12px; border-collapse:collapse;"><thead><tr style="background:rgba(0,0,0,0.1);">';
		data.headers.forEach(h => {
			tableHtml += `<th style="padding:6px 10px; text-align:left; border:1px solid var(--border-color, #334155); font-weight:bold; white-space:nowrap;">${escapeHtml(h)}</th>`;
		});
		tableHtml += '</tr></thead><tbody>';
		data.rows.forEach(r => {
			tableHtml += '<tr style="border-bottom:1px solid var(--border-color, #334155);">';
			data.headers.forEach(h => {
				const val = r[h] !== null && r[h] !== undefined ? r[h] : '<span style="color:var(--color-text-muted); font-style:italic;">NULL</span>';
				tableHtml += `<td style="padding:6px 10px; border:1px solid var(--border-color, #334155); max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${escapeHtml(String(val))}</td>`;
			});
			tableHtml += '</tr>';
		});
		tableHtml += '</tbody></table>';
		dataWrapper.innerHTML = tableHtml;
	}

	// 2. Render Schema Structure
	const schemaWrapper = document.getElementById('studio-schema-table-wrapper');
	if (data.columns && data.columns.length > 0) {
		let sHtml = '<table class="table" style="width:100%; font-size:12px; border-collapse:collapse;"><thead><tr style="background:rgba(0,0,0,0.1);">';
		sHtml += '<th style="padding:6px 10px; text-align:left; border:1px solid var(--border-color, #334155);"><?= tohtml(__tr("Field", "Sütun Adı")) ?></th>';
		sHtml += '<th style="padding:6px 10px; text-align:left; border:1px solid var(--border-color, #334155);"><?= tohtml(__tr("Type", "Veri Tipi")) ?></th>';
		sHtml += '<th style="padding:6px 10px; text-align:left; border:1px solid var(--border-color, #334155);"><?= tohtml(__tr("Null", "Null")) ?></th>';
		sHtml += '<th style="padding:6px 10px; text-align:left; border:1px solid var(--border-color, #334155);"><?= tohtml(__tr("Key", "Anahtar")) ?></th>';
		sHtml += '<th style="padding:6px 10px; text-align:left; border:1px solid var(--border-color, #334155);"><?= tohtml(__tr("Default", "Varsayılan")) ?></th>';
		sHtml += '<th style="padding:6px 10px; text-align:left; border:1px solid var(--border-color, #334155);"><?= tohtml(__tr("Extra", "Ekstra")) ?></th>';
		sHtml += '</tr></thead><tbody>';
		data.columns.forEach(c => {
			sHtml += `<tr>
				<td style="padding:6px 10px; border:1px solid var(--border-color, #334155); font-weight:bold;">${escapeHtml(c.field)}</td>
				<td style="padding:6px 10px; border:1px solid var(--border-color, #334155); font-family:monospace; color:var(--icon-color-blue, #38bdf8);">${escapeHtml(c.type)}</td>
				<td style="padding:6px 10px; border:1px solid var(--border-color, #334155);">${escapeHtml(c.null)}</td>
				<td style="padding:6px 10px; border:1px solid var(--border-color, #334155);">${c.key ? `<span class="badge badge-warning" style="font-size:10px;">${escapeHtml(c.key)}</span>` : ''}</td>
				<td style="padding:6px 10px; border:1px solid var(--border-color, #334155);">${escapeHtml(c.default || '')}</td>
				<td style="padding:6px 10px; border:1px solid var(--border-color, #334155); color:var(--color-text-muted);">${escapeHtml(c.extra || '')}</td>
			</tr>`;
		});
		sHtml += '</tbody></table>';
		schemaWrapper.innerHTML = sHtml;
	}
}

function switchStudioTab(tab) {
	document.getElementById('studio-view-data').style.display = tab === 'data' ? 'block' : 'none';
	document.getElementById('studio-view-schema').style.display = tab === 'schema' ? 'block' : 'none';
	document.getElementById('studio-view-sql').style.display = tab === 'sql' ? 'flex' : 'none';

	document.getElementById('tab-btn-data').className = tab === 'data' ? 'button button-primary button-small' : 'button button-secondary button-small';
	document.getElementById('tab-btn-schema').className = tab === 'schema' ? 'button button-primary button-small' : 'button button-secondary button-small';
	document.getElementById('tab-btn-sql').className = tab === 'sql' ? 'button button-primary button-small' : 'button button-secondary button-small';
}

function runStudioCustomSql() {
	const sqlInput = document.getElementById('studio-custom-sql-input').value.trim();
	if (!sqlInput) return;

	const resWrapper = document.getElementById('studio-sql-results-wrapper');
	resWrapper.innerHTML = '<div style="padding:20px; text-align:center;"><i class="fas fa-spinner fa-spin fa-lg"></i></div>';

	const formData = new FormData();
	formData.append('action_explore_db', '1');
	formData.append('db_name', currentStudioDb);
	formData.append('custom_sql', sqlInput);
	formData.append('token', '<?= tohtml($_SESSION["token"]) ?>');

	fetch('/list/db/', { method: 'POST', body: formData })
		.then(r => r.json())
		.then(data => {
			if (data.status === 'error') {
				resWrapper.innerHTML = `<div class="alert alert-danger" style="margin:10px 0; padding:10px; font-size:12px;"><i class="fas fa-circle-exclamation u-mr5"></i> ${escapeHtml(data.error || 'SQL Error')}</div>`;
				return;
			}
			if (!data.rows || data.rows.length === 0) {
				resWrapper.innerHTML = '<p class="u-text-muted" style="text-align:center; padding:20px;"><?= tohtml(__tr("Query executed successfully. 0 rows returned.", "Sorgu başarıyla çalıştırıldı. 0 satır döndü.")) ?></p>';
				return;
			}
			let tableHtml = '<table class="table" style="width:100%; font-size:12px; border-collapse:collapse;"><thead><tr style="background:rgba(0,0,0,0.1);">';
			data.headers.forEach(h => {
				tableHtml += `<th style="padding:6px 10px; text-align:left; border:1px solid var(--border-color, #334155); font-weight:bold; white-space:nowrap;">${escapeHtml(h)}</th>`;
			});
			tableHtml += '</tr></thead><tbody>';
			data.rows.forEach(r => {
				tableHtml += '<tr>';
				data.headers.forEach(h => {
					tableHtml += `<td style="padding:6px 10px; border:1px solid var(--border-color, #334155); max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${escapeHtml(String(r[h] || ''))}</td>`;
				});
				tableHtml += '</tr>';
			});
			tableHtml += '</tbody></table>';
			resWrapper.innerHTML = tableHtml;
		})
		.catch(err => {
			resWrapper.innerHTML = '<p style="color:#ef4444; padding:10px; font-size:12px;">Hata: ' + escapeHtml(err) + '</p>';
		});
}

function filterStudioTables() {
	const query = document.getElementById('studio-table-search').value.toLowerCase().trim();
	document.querySelectorAll('.studio-table-item').forEach(el => {
		const tName = el.getAttribute('data-table') || '';
		el.style.display = (!query || tName.includes(query)) ? 'block' : 'none';
	});
}

function escapeHtml(str) {
	return String(str).replace(/[&<>"']/g, function(m) {
		return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
	});
}

document.addEventListener('keydown', function(e) {
	if (e.key === 'Escape') closeDbStudio();
});
</script>
