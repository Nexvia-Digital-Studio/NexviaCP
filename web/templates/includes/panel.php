<div id="token" token="<?= $_SESSION["token"] ?>"></div>

<header class="app-header">

	<div class="top-bar">
		<div class="container top-bar-inner">

			<!-- Logo / Usage Statistics wrapper -->
			<div class="top-bar-left">

				<!-- Logo / Home Button -->
				<a href="/" class="top-bar-logo" title="<?= htmlentities($_SESSION["APP_NAME"]) ?>">
					<img src="/images/logo.webp" alt="<?= htmlentities($_SESSION["APP_NAME"]) ?>" height="32">
				</a>

				<!-- Usage Statistics -->
				<div class="top-bar-usage">
					<?php if ($_SESSION["look"] !== "") {
     	$user_icon = "fa-binoculars";
     } elseif ($_SESSION["userContext"] === "admin") {
     	$user_icon = "fa-user-tie";
     } else {
     	$user_icon = "fa-user";
     } ?>
					<div class="top-bar-usage-inner">
						<span class="top-bar-usage-item">
							<i class="fas <?= $user_icon ?>" title="<?= _("Logged in as") ?>: <?= htmlspecialchars($panel[$user]["NAME"]) ?>"></i>
							<span class="u-text-bold">
								<?= htmlspecialchars($user) ?>
							</span>
						</span>
						<span class="top-bar-usage-item">
							<i class="fas fa-hard-drive" title="<?= _("Disk") ?>: <?= humanize_usage_size($panel[$user]["U_DISK"]) ?> <?= humanize_usage_measure($panel[$user]["U_DISK"]) ?>"></i>
							<span class="u-text-bold">
								<?= humanize_usage_size($panel[$user]["U_DISK"]) ?>
							</span>
							<?= humanize_usage_measure($panel[$user]["U_DISK"]) ?>
							/
							<span class="u-text-bold">
							<?= humanize_usage_size($panel[$user]["DISK_QUOTA"]) ?>
							</span>
							<?= humanize_usage_measure($panel[$user]["DISK_QUOTA"]) ?>
						</span>
						<span class="top-bar-usage-item">
							<i class="fas fa-right-left" title="<?= _("Bandwidth") ?>: <?= humanize_usage_size($panel[$user]["U_BANDWIDTH"]) ?> <?= humanize_usage_measure($panel[$user]["U_BANDWIDTH"]) ?>"></i>
							<span class="u-text-bold">
								<?= humanize_usage_size($panel[$user]["U_BANDWIDTH"]) ?>
							</span>
							<?= humanize_usage_measure($panel[$user]["U_BANDWIDTH"]) ?>
							/
							<span class="u-text-bold">
								<?= humanize_usage_size($panel[$user]["BANDWIDTH"]) ?>
							</span>
							<?= humanize_usage_measure($panel[$user]["BANDWIDTH"]) ?>
						</span>
					</div>
				</div>

			</div>

			<!-- Notifications / Menu wrapper -->
			<div class="top-bar-right">

				<!-- Notifications -->
				<?php
    $impersonatingAdmin = $_SESSION["userContext"] === "admin" && ($_SESSION["look"] !== "" && $user == "admin");
    // Do not show notifications panel when impersonating 'admin' user
    if (!$impersonatingAdmin) { ?>
					<div x-data="notifications" class="top-bar-notifications">
						<button
							x-on:click="toggle()"
							x-bind:class="open && 'active'"
							class="top-bar-menu-link"
							type="button"
							title="<?= _("Notifications") ?>"
						>
							<i
								x-bind:class="{
									'animate__animated animate__swing icon-orange': (!initialized && <?= $panel[$user]["NOTIFICATIONS"] == "yes" ? "true" : "false" ?>) || notifications.length != 0,
									'fas fa-bell': true
								}"
							></i>
							<span class="u-hidden"><?= _("Notifications") ?></span>
						</button>
						<div
							x-cloak
							x-show="open"
							x-on:click.outside="open = false"
							class="top-bar-notifications-panel"
						>
							<template x-if="!initialized">
								<div class="top-bar-notifications-empty">
									<i class="fas fa-circle-notch fa-spin icon-dim"></i>
									<p><?= _("Loading...") ?></p>
								</div>
							</template>
							<template x-if="initialized && notifications.length == 0">
								<div class="top-bar-notifications-empty">
									<i class="fas fa-bell-slash icon-dim"></i>
									<p><?= _("No notifications") ?></p>
								</div>
							</template>
							<template x-if="initialized && notifications.length > 0">
								<ul>
									<template x-for="notification in notifications" :key="notification.ID">
										<li
											x-bind:id="`notification-${notification.ID}`"
											x-bind:class="notification.ACK && 'unseen'"
											class="top-bar-notification-item"
											x-data="{ open: true }"
											x-show="open"
											x-collapse
										>
											<div class="top-bar-notification-inner">
												<div class="top-bar-notification-header">
													<p x-text="notification.TOPIC" class="top-bar-notification-title"></p>
													<button
														x-on:click="open = false; setTimeout(() => remove(notification.ID), 300);"
														type="button"
														class="top-bar-notification-delete"
														title="<?= _("Delete notification") ?>"
													>
														<i class="fas fa-xmark"></i>
														<span class="u-hidden-visually"><?= _("Delete notification") ?></span>
													</button>
												</div>
												<div class="top-bar-notification-content" x-html="notification.NOTICE"></div>
												<p class="top-bar-notification-timestamp">
													<time
														:datetime="`${notification.TIMESTAMP_ISO}`"
														x-bind:title="`${notification.TIMESTAMP_TITLE}`"
														x-text="`${notification.TIMESTAMP_TEXT}`"
													></time>
												</p>
											</div>
										</li>
									</template>
								</ul>
							</template>
							<template x-if="initialized && notifications.length > 2">
								<button
									x-on:click="removeAll()"
									type="button"
									class="top-bar-notifications-delete-all"
								>
									<i class="fas fa-check"></i>
									<?= _("Delete all notifications") ?>
								</button>
							</template>
						</div>
					</div>
				<?php }
    ?>

				<!-- Menu -->
				<nav x-data="{ open: false }" class="top-bar-menu">

					<button
						type="button"
						class="top-bar-menu-link u-hide-tablet"
						x-on:click="open = !open">
						<i class="fas fa-bars"></i>
						<span class="u-hidden" x-text="open ? '<?= _("Close menu") ?>' : '<?= _("Open menu") ?>'">
							<?= _("Open menu") ?>
						</span>
					</button>

					<div x-cloak x-show="open" x-on:click.outside="open = false" class="top-bar-menu-panel">
						<ul class="top-bar-menu-list">

							<!-- File Manager -->
							<?php if (isset($_SESSION["FILE_MANAGER"]) && !empty($_SESSION["FILE_MANAGER"]) && $_SESSION["FILE_MANAGER"] == "true") { ?>
								<?php if ($_SESSION["userContext"] === "admin" && $_SESSION["look"] === "admin" && $_SESSION["POLICY_SYSTEM_PROTECTED_ADMIN"] == "yes") { ?>
									<!-- Hide file manager when impersonating admin-->
								<?php } else { ?>
									<li class="top-bar-menu-item">
										<a title="<?= _("File manager") ?>" class="top-bar-menu-link <?php if ($TAB == "FM") {
	echo "active";
} ?>" href="/fm/">
											<i class="fas fa-folder-open"></i>
											<span class="top-bar-menu-link-label u-hide-desktop"><?= _("File manager") ?></span>
										</a>
									</li>
								<?php } ?>
							<?php } ?>

							<!-- Web Terminal -->
							<?php if (isset($_SESSION["WEB_TERMINAL"]) && !empty($_SESSION["WEB_TERMINAL"]) && $_SESSION["WEB_TERMINAL"] == "true") { ?>
								<?php if ($_SESSION["userContext"] === "admin" && $_SESSION["look"] === "admin" && $_SESSION["POLICY_SYSTEM_PROTECTED_ADMIN"] == "yes") { ?>
									<!-- Hide web terminal when impersonating admin -->
								<?php } elseif ($_SESSION["login_shell"] != "nologin") { ?>
									<li class="top-bar-menu-item">
										<a title="<?= _("Web terminal") ?>" class="top-bar-menu-link <?php if ($TAB == "TERMINAL") {
	echo "active";
} ?>" href="/list/terminal/">
											<i class="fas fa-terminal"></i>
											<span class="top-bar-menu-link-label u-hide-desktop"><?= _("Web terminal") ?></span>
										</a>
									</li>
								<?php } ?>
							<?php } ?>

							<!-- Log Search (all users; non-admin scope is limited to own domains) -->
							<li class="top-bar-menu-item">
								<a title="<?= function_exists('__tr') ? __tr("Log Search", "Log Arama") : _("Log Search") ?>" class="top-bar-menu-link <?php if ($TAB == "LOG_SEARCH") {
									echo "active";
								} ?>" href="/list/log-search/">
									<i class="fas fa-magnifying-glass"></i>
									<span class="top-bar-menu-link-label u-hide-desktop"><?= function_exists('__tr') ? __tr("Log Search", "Log Arama") : _("Log Search") ?></span>
								</a>
							</li>

							<!-- Docker Manager (Portainer) — admin only -->
							<?php if (($_SESSION["userContext"] ?? "") === "admin") { ?>
							<li class="top-bar-menu-item">
								<a title="<?= _("Docker Manager (Portainer)") ?>" class="top-bar-menu-link <?php if ($TAB == "DOCKER") { echo "active"; } ?>" href="/list/docker/">
									<i class="fas fa-cubes icon-blue"></i>
									<span class="top-bar-menu-link-label u-hide-desktop"><?= _("Docker Manager") ?></span>
								</a>
							</li>
							<li class="top-bar-menu-item">
								<a title="<?= function_exists('__tr') ? __tr("Web & Proxy Templates", "Web & Proxy Şablonları") : _("Web & Proxy Templates") ?>" class="top-bar-menu-link <?php if ($TAB == "TEMPLATES") { echo "active"; } ?>" href="/list/templates/">
									<i class="fas fa-layer-group icon-teal"></i>
									<span class="top-bar-menu-link-label u-hide-desktop"><?= function_exists('__tr') ? __tr("Templates", "Şablonlar") : _("Templates") ?></span>
								</a>
							</li>

							<!-- AI Ops & Self-Healing Hub — admin only -->
							<li class="top-bar-menu-item">
								<a title="<?= _("AI Ops & Self-Healing Hub") ?>" class="top-bar-menu-link <?php if ($TAB == "AI_HEALING") { echo "active"; } ?>" href="/list/ai-healing/">
									<i class="fas fa-heart-pulse icon-green"></i>
									<span class="top-bar-menu-link-label u-hide-desktop"><?= _("AI Self-Healing") ?></span>
								</a>
							</li>

							<!-- Domain Anomaly Monitor — admin only -->
							<li class="top-bar-menu-item">
								<a title="<?= function_exists('__tr') ? __tr("Domain Anomaly Monitor", "Domain Anomali İzleme") : _("Domain Anomaly Monitor") ?>" class="top-bar-menu-link <?php if ($TAB == "ANOMALIES") {
									echo "active";
								} ?>" href="/list/anomalies/">
									<i class="fas fa-satellite-dish icon-purple"></i>
									<span class="top-bar-menu-link-label u-hide-desktop"><?= function_exists('__tr') ? __tr("Domain Anomaly Monitor", "Domain Anomali İzleme") : _("Domain Anomaly Monitor") ?></span>
								</a>
							</li>

							<!-- Health Monitor & Certificates — admin only -->
							<li class="top-bar-menu-item">
								<a title="<?= function_exists('__tr') ? __tr("Health & Certificates", "Sağlık ve Sertifikalar") : _("Health & Certificates") ?>" class="top-bar-menu-link <?php if ($TAB == "HEALTH") {
									echo "active";
								} ?>" href="/list/health/">
									<i class="fas fa-tower-broadcast icon-lightblue"></i>
									<span class="top-bar-menu-link-label u-hide-desktop"><?= function_exists('__tr') ? __tr("Health & Certificates", "Sağlık ve Sertifikalar") : _("Health & Certificates") ?></span>
								</a>
							</li>

							<!-- Notification Channels — admin only -->
							<li class="top-bar-menu-item">
								<a title="<?= function_exists('__tr') ? __tr("Notification Channels", "Bildirim Kanalları") : _("Notification Channels") ?>" class="top-bar-menu-link <?php if ($TAB == "NOTIFY") {
									echo "active";
								} ?>" href="/list/notify/">
									<i class="fas fa-bell icon-orange"></i>
									<span class="top-bar-menu-link-label u-hide-desktop"><?= function_exists('__tr') ? __tr("Notification Channels", "Bildirim Kanalları") : _("Notification Channels") ?></span>
								</a>
							</li>

							<!-- Security & CVE Scan — admin only -->
							<li class="top-bar-menu-item">
								<a title="<?= function_exists('__tr') ? __tr("Security & CVE Scan", "Güvenlik ve CVE Taraması") : _("Security & CVE Scan") ?>" class="top-bar-menu-link <?php if ($TAB == "CVES") {
									echo "active";
								} ?>" href="/list/cves/">
									<i class="fas fa-shield-halved icon-red"></i>
									<span class="top-bar-menu-link-label u-hide-desktop"><?= function_exists('__tr') ? __tr("Security & CVE Scan", "Güvenlik ve CVE Taraması") : _("Security & CVE Scan") ?></span>
								</a>
							</li>

							<!-- System Maintenance — admin only -->
							<li class="top-bar-menu-item">
								<a title="<?= function_exists('__tr') ? __tr("System Maintenance", "Sistem Bakımı") : _("System Maintenance") ?>" class="top-bar-menu-link <?php if ($TAB == "MAINTENANCE") {
									echo "active";
								} ?>" href="/list/maintenance/">
									<i class="fas fa-screwdriver-wrench icon-maroon"></i>
									<span class="top-bar-menu-link-label u-hide-desktop"><?= function_exists('__tr') ? __tr("System Maintenance", "Sistem Bakımı") : _("System Maintenance") ?></span>
								</a>
							</li>

							<!-- Mail Queue — admin only -->
							<li class="top-bar-menu-item">
								<a title="<?= function_exists('__tr') ? __tr("Mail Queue", "Mail Kuyruğu") : _("Mail Queue") ?>" class="top-bar-menu-link <?php if ($TAB == "MAIL_QUEUE") {
									echo "active";
								} ?>" href="/list/mail_queue/">
									<i class="fas fa-envelope icon-blue"></i>
									<span class="top-bar-menu-link-label u-hide-desktop"><?= function_exists('__tr') ? __tr("Mail Queue", "Mail Kuyruğu") : _("Mail Queue") ?></span>
								</a>
							</li>

							<!-- API Audit & Rate Limiting — admin only -->
							<li class="top-bar-menu-item">
								<a title="<?= function_exists('__tr') ? __tr("API Audit", "API Denetimi") : _("API Audit") ?>" class="top-bar-menu-link <?php if ($TAB == "API_AUDIT") {
									echo "active";
								} ?>" href="/list/api-audit/">
									<i class="fas fa-key icon-purple"></i>
									<span class="top-bar-menu-link-label u-hide-desktop"><?= function_exists('__tr') ? __tr("API Audit", "API Denetimi") : _("API Audit") ?></span>
								</a>
							</li>

							<!-- Remote Servers — admin only -->
							<li class="top-bar-menu-item">
								<a title="<?= function_exists('__tr') ? __tr("Remote Servers", "Uzak Sunucular") : _("Remote Servers") ?>" class="top-bar-menu-link <?php if ($TAB == "SERVERS") {
									echo "active";
								} ?>" href="/list/servers/">
									<i class="fas fa-server icon-teal"></i>
									<span class="top-bar-menu-link-label u-hide-desktop"><?= function_exists('__tr') ? __tr("Remote Servers", "Uzak Sunucular") : _("Remote Servers") ?></span>
								</a>
							</li>
							<?php } ?>

							<!-- Server Settings -->
							<?php if (($_SESSION["userContext"] === "admin" && $_SESSION["POLICY_SYSTEM_HIDE_SERVICES"] !== "yes") || $_SESSION["user"] === $_SESSION['ROOT_USER']) { ?>
								<?php if ($_SESSION["userContext"] === "admin" && $_SESSION["look"] !== "") { ?>
									<!-- Hide 'Server Settings' button when impersonating 'admin' or other users -->
								<?php } else { ?>
									<li class="top-bar-menu-item">
										<a title="<?= _("Server settings") ?>" class="top-bar-menu-link <?php if (in_array($TAB, ["SERVER", "IP", "RRD", "FIREWALL"])) {
	echo "active";
} ?>" href="/list/server/">
											<i class="fas fa-gear"></i>
											<span class="top-bar-menu-link-label u-hide-desktop"><?= _("Server settings") ?></span>
										</a>
									</li>
								<?php } ?>
							<?php } ?>

							<!-- Edit User -->
							<?php if ($_SESSION["userContext"] === "admin" && ($_SESSION["look"] !== "" && $user == "admin")) { ?>
								<!-- Hide 'edit user' entry point from other administrators for default 'admin' account-->
								<li class="top-bar-menu-item">
									<a title="<?= _("Logs") ?>" class="top-bar-menu-link <?php if ($TAB == "LOG") {
	echo "active";
} ?>" href="/list/log/">
										<i class="fas fa-clock-rotate-left"></i>
										<span class="top-bar-menu-link-label u-hide-desktop"><?= _("Logs") ?></span>
									</a>
								</li>
								<li class="top-bar-menu-item">
									<a title="<?= function_exists('__tr') ? __tr("Live Stream Logs", "Canlı Günlük Akışı") : _("Live Logs") ?>" class="top-bar-menu-link" href="/list/logs/">
										<i class="fas fa-terminal icon-green"></i>
										<span class="top-bar-menu-link-label u-hide-desktop"><?= _("Live Logs") ?></span>
									</a>
								</li>
							<?php } else { ?>
								<?php if ($panel[$user]["SUSPENDED"] === "no") { ?>
									<li class="top-bar-menu-item">
										<a title="<?= htmlspecialchars($user) ?> (<?= htmlspecialchars($panel[$user]["NAME"]) ?>)" class="top-bar-menu-link" href="/edit/user/?user=<?= $user ?>&token=<?= $_SESSION["token"] ?>">
											<i class="fas fa-circle-user"></i>
											<span class="top-bar-menu-link-label u-hide-desktop"><?= htmlspecialchars($user) ?> (<?= htmlspecialchars($panel[$user]["NAME"]) ?>)</span>
										</a>
									</li>
								<?php } ?>
							<?php } ?>

							<!-- Statistics -->
							<li class="top-bar-menu-item">
								<a title="<?= _("Statistics") ?>" class="top-bar-menu-link <?php if ($TAB == "STATS") {
	echo "active";
} ?>" href="/list/stats/">
									<i class="fas fa-chart-line"></i>
									<span class="top-bar-menu-link-label u-hide-desktop"><?= _("Statistics") ?></span>
								</a>
							</li>
							<?php if ($_SESSION["HIDE_DOCS"] !== "yes") { ?>
								<!-- Help / Documentation -->
								<li class="top-bar-menu-item">
									<a title="<?= _("Help") ?>" class="top-bar-menu-link" href="https://hestiacp.com/docs/" target="_blank" rel="noopener">
										<i class="fas fa-circle-question"></i>
										<span class="top-bar-menu-link-label u-hide-desktop"><?= _("Help") ?></span>
									</a>
								</li>
							<?php } ?>
							<!-- Logout -->
							<?php if (isset($_SESSION["look"]) && !empty($_SESSION["look"])) { ?>
								<li class="top-bar-menu-item">
									<a title="<?= _("Log out") ?> (<?= $user ?>)" class="top-bar-menu-link top-bar-menu-link-logout" href="/logout/?token=<?= $_SESSION["token"] ?>">
										<i class="fas fa-circle-up"></i>
										<span class="top-bar-menu-link-label u-hide-desktop"><?= _("Log out") ?> (<?= $user ?>)</span>
									</a>
								</li>
							<?php } else { ?>
								<li class="top-bar-menu-item">
									<a title="<?= _("Log out") ?>" class="top-bar-menu-link top-bar-menu-link-logout" href="/logout/?token=<?= $_SESSION["token"] ?>">
										<i class="fas fa-right-from-bracket"></i>
										<span class="top-bar-menu-link-label u-hide-desktop"><?= _("Log out") ?></span>
									</a>
								</li>
							<?php } ?>

						</ul>
					</div>
				</nav>

			</div>

		</div>
	</div>

	<style>
		.main-menu {
			background: var(--color-background-menu, #1e222d);
			border-top: 1px solid var(--border-color, #2b303c);
			border-bottom: 1px solid var(--border-color, #2b303c);
		}
		.main-menu .container {
			max-width: 100% !important;
			display: flex;
			justify-content: center;
			padding: 0 15px;
		}
		.main-menu-list {
			display: flex !important;
			justify-content: center !important;
			align-items: stretch !important;
			flex-wrap: wrap !important;
			margin: 0 auto !important;
			padding: 0 !important;
			width: 100% !important;
			max-width: 1550px !important;
			gap: 2px 6px;
			list-style: none;
		}
		.main-menu-item {
			flex: 0 1 auto !important;
			min-width: 78px;
			max-width: 125px;
			text-align: center;
			display: flex;
		}
		.main-menu-item-link {
			display: flex !important;
			flex-direction: column;
			align-items: center;
			justify-content: flex-start;
			padding: 8px 8px !important;
			width: 100%;
			height: 100%;
			text-align: center;
			border-radius: 4px;
			transition: background 0.15s ease-in-out;
			text-decoration: none;
		}
		.main-menu-item-label {
			white-space: nowrap !important;
			font-size: 11px !important;
			font-weight: 600 !important;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 5px;
			margin: 0 0 3px 0 !important;
		}
		.main-menu-stats {
			font-size: 10px !important;
			line-height: 1.3 !important;
			text-align: center !important;
			margin: 0 !important;
			padding: 0 !important;
			list-style: none;
		}
		.main-menu-stats li {
			white-space: nowrap !important;
			overflow: hidden;
			text-overflow: ellipsis;
			max-width: 110px;
		}
	</style>

	<nav x-data="{ open: false }" class="main-menu">
		<div class="container">
			<button x-on:click="open = !open" type="button" class="main-menu-toggle">
				<i class="fas fa-bars"></i>
				<span
					x-text="open ? '<?= _("Collapse main menu") ?>' : '<?= _("Expand main menu") ?>'"
					class="main-menu-toggle-label"
				>
					<?= _("Expand main menu") ?>
				</span>
			</button>
			<ul x-cloak x-show="open" class="main-menu-list">

				<!-- 1. Users tab -->
				<?php if ($_SESSION["userContext"] == "admin" && $_SESSION["look"] === "") { ?>
					<?php if ($_SESSION["user"] !== "admin" && $_SESSION["POLICY_SYSTEM_HIDE_ADMIN"] === "yes") {
     	$user_count = $panel[$user]["U_USERS"] - 1;
     } else {
     	$user_count = $panel[$user]["U_USERS"];
     } ?>
					<li class="main-menu-item">
						<a class="main-menu-item-link <?php if (in_array($TAB, ["USER", "LOG"])) {
      	echo "active";
      } ?>" href="/list/user/" title="<?= _("Users") ?>: <?= $user_count ?>&#13;<?= _("Suspended") ?>: <?= $panel[$user]["SUSPENDED_USERS"] ?>">
							<p class="main-menu-item-label"><?= _("USER") ?><i class="fas fa-users"></i></p>
							<ul class="main-menu-stats">
								<li>
									<?= _("Users") ?>: <?= htmlspecialchars($user_count) ?>
								</li>
								<li>
									<?= _("Suspended") ?>: <?= $panel[$user]["SUSPENDED_USERS"] ?>
								</li>
							</ul>
						</a>
					</li>
				<?php } ?>

				<!-- 2. Web tab -->
				<?php if (isset($_SESSION["WEB_SYSTEM"]) && !empty($_SESSION["WEB_SYSTEM"])) { ?>
					<?php if ($panel[$user]["WEB_DOMAINS"] != "0") { ?>
						<li class="main-menu-item">
							<a class="main-menu-item-link <?php if ($TAB == "WEB") {
       	echo "active";
       } ?>" href="/list/web/" title="<?= _("Domains") ?>: <?= $panel[$user]["U_WEB_DOMAINS"] ?>&#13;<?= _("Aliases") ?>: <?= $panel[$user]["U_WEB_ALIASES"] ?>&#13;<?= _("Limit") ?>: <?= $panel[
	$user
]["WEB_DOMAINS"] == "unlimited"
	? "∞"
	: $panel[$user]["WEB_DOMAINS"] ?>&#13;<?= _("Suspended") ?>: <?= $panel[$user]["SUSPENDED_WEB"] ?>">
								<p class="main-menu-item-label"><?= _("WEB") ?><i class="fas fa-earth-americas"></i></p>
								<ul class="main-menu-stats">
									<li>
										<?= _("Domains") ?>: <?= $panel[$user]["U_WEB_DOMAINS"] ?> / <?= $panel[$user]["WEB_DOMAINS"] == "unlimited" ? "<span class=\"u-text-bold\">∞</span>" : $panel[$user]["WEB_DOMAINS"] ?>
									</li>
									<li>
										<?= _("Aliases") ?>: <?= $panel[$user]["U_WEB_ALIASES"] ?>
									</li>
								</ul>
							</a>
						</li>
					<?php } ?>
				<?php } ?>

				<!-- 3. DNS tab -->
				<?php if (isset($_SESSION["DNS_SYSTEM"]) && !empty($_SESSION["DNS_SYSTEM"]) && $_SESSION["DNS_SYSTEM"] !== "no") { ?>
					<?php if ($panel[$user]["DNS_DOMAINS"] != "0") { ?>
						<li class="main-menu-item">
							<a class="main-menu-item-link <?php if ($TAB == "DNS") {
       	echo "active";
       } ?>" href="/list/dns/" title="<?= _("Domains") ?>: <?= $panel[$user]["U_DNS_DOMAINS"] ?>&#13;<?= _("Limit") ?>: <?= $panel[$user]["DNS_DOMAINS"] == "unlimited"
	? "∞"
	: $panel[$user]["DNS_DOMAINS"] ?>&#13;<?= _("Suspended") ?>: <?= $panel[$user]["SUSPENDED_DNS"] ?>">
								<p class="main-menu-item-label"><?= _("DNS") ?><i class="fas fa-book-atlas"></i></p>
								<ul class="main-menu-stats">
									<li>
										<?= _("Zones") ?>: <?= $panel[$user]["U_DNS_DOMAINS"] ?> / <?= $panel[$user]["DNS_DOMAINS"] == "unlimited" ? "<span class=\"u-text-bold\">∞</span>" : $panel[$user]["DNS_DOMAINS"] ?>
									</li>
									<li>
										<?= _("Records") ?>: <?= $panel[$user]["U_DNS_RECORDS"] ?>
									</li>
								</ul>
							</a>
						</li>
					<?php } ?>
				<?php } ?>

				<!-- 4. Mail tab -->
				<?php if (isset($_SESSION["MAIL_SYSTEM"]) && !empty($_SESSION["MAIL_SYSTEM"])) { ?>
					<?php if ($panel[$user]["MAIL_DOMAINS"] != "0") { ?>
						<li class="main-menu-item">
							<a class="main-menu-item-link <?php if ($TAB == "MAIL") {
       	echo "active";
       } ?>" href="/list/mail/" title="<?= _("Domains") ?>: <?= $panel[$user]["U_MAIL_DOMAINS"] ?>&#13;<?= _("Limit") ?>: <?= $panel[$user]["MAIL_DOMAINS"] == "unlimited"
	? "∞"
	: $panel[$user]["MAIL_DOMAINS"] ?>&#13;<?= _("Suspended") ?>: <?= $panel[$user]["SUSPENDED_MAIL"] ?>">
								<p class="main-menu-item-label"><?= _("MAIL") ?><i class="fas fa-envelopes-bulk"></i></p>
								<ul class="main-menu-stats">
									<li>
										<?= _("Domains") ?>: <?= $panel[$user]["U_MAIL_DOMAINS"] ?> / <?= $panel[$user]["MAIL_DOMAINS"] == "unlimited" ? "<span class=\"u-text-bold\">∞</span>" : $panel[$user]["MAIL_DOMAINS"] ?>
									</li>
									<li>
										<?= _("Accounts") ?>: <?= $panel[$user]["U_MAIL_ACCOUNTS"] ?>
									</li>
								</ul>
							</a>
						</li>
					<?php } ?>
				<?php } ?>

				<!-- 5. Databases tab -->
				<?php if (isset($_SESSION["DB_SYSTEM"]) && !empty($_SESSION["DB_SYSTEM"])) { ?>
					<?php if ($panel[$user]["DATABASES"] != "0") { ?>
						<li class="main-menu-item">
							<a class="main-menu-item-link <?php if ($TAB == "DB") {
       	echo "active";
       } ?>" href="/list/db/" title="<?= _("Databases") ?>: <?= $panel[$user]["U_DATABASES"] ?>&#13;<?= _("Limit") ?>: <?= $panel[$user]["DATABASES"] == "unlimited"
	? "∞"
	: $panel[$user]["DATABASES"] ?>&#13;<?= _("Suspended") ?>: <?= $panel[$user]["SUSPENDED_DB"] ?>">
								<p class="main-menu-item-label"><?= _("DB") ?><i class="fas fa-database"></i></p>
								<ul class="main-menu-stats">
									<li>
										<?= _("Databases") ?>: <?= $panel[$user]["U_DATABASES"] ?> / <?= $panel[$user]["DATABASES"] == "unlimited" ? "<span class=\"u-text-bold\">∞</span>" : $panel[$user]["DATABASES"] ?>
									</li>
								</ul>
							</a>
						</li>
					<?php } ?>
				<?php } ?>

				<!-- 6. Cron tab -->
				<?php if (isset($_SESSION["CRON_SYSTEM"]) && !empty($_SESSION["CRON_SYSTEM"])) { ?>
					<?php if ($panel[$user]["CRON_JOBS"] != "0") { ?>
						<li class="main-menu-item">
							<a class="main-menu-item-link <?php if ($TAB == "CRON") {
       	echo "active";
       } ?>" href="/list/cron/" title="<?= _("Jobs") ?>: <?= $panel[$user]["U_WEB_DOMAINS"] ?>&#13;<?= _("Limit") ?>: <?= $panel[$user]["CRON_JOBS"] == "unlimited"
	? "∞"
	: $panel[$user]["CRON_JOBS"] ?>&#13;<?= _("Suspended") ?>: <?= $panel[$user]["SUSPENDED_CRON"] ?>">
								<p class="main-menu-item-label"><?= _("CRON") ?><i class="fas fa-clock"></i></p>
								<ul class="main-menu-stats">
									<li>
										<?= _("Jobs") ?>: <?= $panel[$user]["U_CRON_JOBS"] ?> / <?= $panel[$user]["CRON_JOBS"] == "unlimited" ? "<span class=\"u-text-bold\">∞</span>" : $panel[$user]["CRON_JOBS"] ?>
									</li>
								</ul>
							</a>
						</li>
					<?php } ?>
				<?php } ?>

				<!-- 7. Backups tab -->
				<?php if (isset($_SESSION["BACKUP_SYSTEM"]) && !empty($_SESSION["BACKUP_SYSTEM"])) { ?>
					<?php if ($panel[$user]["BACKUPS"] != "0" || $panel[$user]["U_BACKUPS"] != "0" || $panel[$user]["BACKUPS_INCREMENTAL"] == "yes") { ?>
						<li class="main-menu-item">
							<a class="main-menu-item-link <?php if ($TAB == "BACKUP") {
       	echo "active";
       } ?>" href="/list/backup/" title="<?= _("Backups") ?>: <?= $panel[$user]["U_BACKUPS"] ?>&#13;<?= _("Limit") ?>: <?= $panel[$user]["BACKUPS"] == "unlimited" ? "∞" : $panel[$user]["BACKUPS"] ?>">
								<p class="main-menu-item-label"><?= _("BACKUP") ?><i class="fas fa-file-zipper"></i></p>
								<ul class="main-menu-stats">
									<li>
										<?= _("Backups") ?>: <?= $panel[$user]["U_BACKUPS"] ?> / <?= $panel[$user]["BACKUPS"] == "unlimited" ? "<span class=\"u-text-bold\">∞</span>" : $panel[$user]["BACKUPS"] ?>
									</li>
								</ul>
							</a>
						</li>
					<?php } ?>
				<?php } ?>

				<!-- 8. API tab (Admin Only) -->
				<?php if (($_SESSION["userContext"] ?? "") === "admin") {
					$is_panel_tr = (($_SESSION["language"] ?? "") === "tr" || ($_SESSION["LANGUAGE"] ?? "") === "tr");
				?>
					<li class="main-menu-item">
						<a class="main-menu-item-link <?php if ($TAB == "API_SERVICES" || $TAB == "API") { echo "active"; } ?>" href="/list/api/" title="<?= $is_panel_tr ? "API ve Arka Plan Servisleri" : _("API & Backend Services") ?>">
							<p class="main-menu-item-label"><?= _("API") ?><i class="fas fa-bolt"></i></p>
							<ul class="main-menu-stats">
								<li>
									<?= $is_panel_tr ? "servisler" : _("Services") ?>: <?= htmlspecialchars($panel[$user]["U_API_SERVICES"] ?? (count(glob('/etc/systemd/system/hestia-app-*.service') ?: []))) ?> / <span class="u-text-bold">∞</span>
								</li>
								<li>
									<?= $is_panel_tr ? "durum" : _("Status") ?>: <?= $is_panel_tr ? "aktif" : _("Active") ?>
								</li>
							</ul>
						</a>
					</li>

					<!-- 9. Smart Resource Governance Tab (Admin Only) -->
					<li class="main-menu-item">
						<a class="main-menu-item-link <?php if ($TAB == "RESOURCES" || $TAB == "GOVERNANCE") { echo "active"; } ?>" href="/list/resources/" title="<?= $is_panel_tr ? "Akıllı Kaynak Yönetişimi & Öncelik Matrisi" : _("Smart Resource Governance & Priority Matrix") ?>">
							<p class="main-menu-item-label"><?= $is_panel_tr ? "KAYNAK" : _("RESOURCES") ?><i class="fas fa-microchip"></i></p>
							<ul class="main-menu-stats">
								<li>
									<?= $is_panel_tr ? "öncelik" : _("Priority") ?>: <?= $is_panel_tr ? "5 Kademe" : _("5-Tier") ?>
								</li>
								<li>
									<?= $is_panel_tr ? "akıllı" : _("Auto-Tune") ?>: <?= $is_panel_tr ? "aktif" : _("Active") ?>
								</li>
							</ul>
						</a>
					</li>

					<!-- 10. Cache & Performance Optimizer Tab (Admin Only) -->
					<li class="main-menu-item">
						<a class="main-menu-item-link <?php if ($TAB == "CACHE" || $TAB == "PERFORMANCE") { echo "active"; } ?>" href="/list/cache/" title="<?= $is_panel_tr ? "Performans, Redis & Veritabanı Optimize Edici" : _("Performance, Cache & Database Optimizer") ?>">
							<p class="main-menu-item-label"><?= $is_panel_tr ? "ÖNBELLEK" : _("CACHE") ?><i class="fas fa-gauge-high"></i></p>
							<ul class="main-menu-stats">
								<li>
									<?= $is_panel_tr ? "redis" : _("Redis") ?>: <?= $is_panel_tr ? "izole" : _("Isolated") ?>
								</li>
								<li>
									<?= $is_panel_tr ? "ai sql" : _("AI SQL") ?>: <span class="u-text-bold">⚡</span>
								</li>
							</ul>
						</a>
					</li>

					<!-- 11. Threat Shield & WAF Security Tab (Admin Only) -->
					<li class="main-menu-item">
						<a class="main-menu-item-link <?php if ($TAB == "WAF" || $TAB == "SECURITY" || $TAB == "THREATS") { echo "active"; } ?>" href="/list/waf/" title="<?= $is_panel_tr ? "Kurumsal Tehdit Kalkanı, WAF & Zararlı Kod Tarayıcı" : _("Enterprise Threat Shield, WAF & Malware Scanner") ?>">
							<p class="main-menu-item-label"><?= $is_panel_tr ? "GÜVENLİK" : _("SECURITY") ?><i class="fas fa-shield-halved"></i></p>
							<ul class="main-menu-stats">
								<li>
									<?= $is_panel_tr ? "waf" : _("WAF") ?>: <?= $is_panel_tr ? "aktif" : _("Active") ?>
								</li>
								<li>
									<?= $is_panel_tr ? "tarayıcı" : _("Scanner") ?>: <?= $is_panel_tr ? "hazır" : _("Ready") ?>
								</li>
							</ul>
						</a>
					</li>

					<!-- 12. AI Ops & Self-Healing Hub Tab (Admin Only) -->
					<li class="main-menu-item">
						<a class="main-menu-item-link <?php if ($TAB == "AI_HEALING" || $TAB == "HEALING") { echo "active"; } ?>" href="/list/ai-healing/" title="<?= $is_panel_tr ? "AI Ops, Oto-Onarım & E-Posta Bildirim Merkezi" : _("AI Ops, Self-Healing Engine & HTML Notification Hub") ?>">
							<p class="main-menu-item-label"><?= $is_panel_tr ? "AI ONARIM" : _("AI HEALING") ?><i class="fas fa-heart-pulse"></i></p>
							<ul class="main-menu-stats">
								<li>
									<?= $is_panel_tr ? "durum" : _("Status") ?>: <?= $is_panel_tr ? "aktif" : _("Active") ?>
								</li>
								<li>
									<?= $is_panel_tr ? "bildirim" : _("Alerts") ?>: <?= $is_panel_tr ? "HTML" : _("HTML") ?>
								</li>
							</ul>
						</a>
					</li>
				<?php } ?>

			</ul>
		</div>
	</nav>

</header>

<main class="app-content">
