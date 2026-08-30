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
						<span class="top-bar-usage-item" title="<?= _("Logged in as") ?>: <?= htmlspecialchars($panel[$user]["NAME"]) ?>">
							<i class="fas <?= $user_icon ?>"></i>
							<span class="u-text-bold"><?= htmlspecialchars($user) ?></span>
						</span>
						<span class="top-bar-usage-item" title="<?= _("Disk") ?>: <?= humanize_usage_size($panel[$user]["U_DISK"]) ?> <?= humanize_usage_measure($panel[$user]["U_DISK"]) ?>">
							<i class="fas fa-hard-drive"></i>
							<span class="u-text-bold"><?= humanize_usage_size($panel[$user]["U_DISK"]) ?></span> <?= humanize_usage_measure($panel[$user]["U_DISK"]) ?>
							<span class="top-bar-usage-sep">/</span>
							<span class="u-text-bold"><?= ($panel[$user]["DISK_QUOTA"] == "unlimited" || $panel[$user]["DISK_QUOTA"] == "0") ? "∞" : humanize_usage_size($panel[$user]["DISK_QUOTA"]) . " " . humanize_usage_measure($panel[$user]["DISK_QUOTA"]) ?></span>
						</span>
						<span class="top-bar-usage-item" title="<?= _("Bandwidth") ?>: <?= humanize_usage_size($panel[$user]["U_BANDWIDTH"]) ?> <?= humanize_usage_measure($panel[$user]["U_BANDWIDTH"]) ?>">
							<i class="fas fa-right-left"></i>
							<span class="u-text-bold"><?= humanize_usage_size($panel[$user]["U_BANDWIDTH"]) ?></span> <?= humanize_usage_measure($panel[$user]["U_BANDWIDTH"]) ?>
							<span class="top-bar-usage-sep">/</span>
							<span class="u-text-bold"><?= ($panel[$user]["BANDWIDTH"] == "unlimited" || $panel[$user]["BANDWIDTH"] == "0") ? "∞" : humanize_usage_size($panel[$user]["BANDWIDTH"]) . " " . humanize_usage_measure($panel[$user]["BANDWIDTH"]) ?></span>
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
				<nav class="top-bar-menu">
					<div class="top-bar-menu-panel">
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

							<!-- GitHub & CI/CD Integration — admin only -->
							<?php if (($_SESSION["userContext"] ?? "") === "admin") { ?>
							<li class="top-bar-menu-item">
								<a title="<?= function_exists('__tr') ? __tr("GitHub & CI/CD Integration", "GitHub & CI/CD Entegrasyonu") : _("GitHub & CI/CD") ?>" class="top-bar-menu-link <?php if ($TAB == "GITHUB" || ($TAB == "SERVER" && strpos($_SERVER['REQUEST_URI'] ?? '', '/edit/server/github') !== false)) { echo "active"; } ?>" href="/edit/server/github/">
									<svg viewBox="0 0 16 16" width="18" height="18" fill="currentColor" style="color: #38bdf8; display: inline-block; vertical-align: middle;">
										<path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"></path>
									</svg>
									<span class="top-bar-menu-link-label u-hide-desktop"><?= function_exists('__tr') ? __tr("GitHub & CI/CD", "GitHub & CI/CD") : _("GitHub & CI/CD") ?></span>
								</a>
							</li>
							<!-- Docker Manager (Portainer) — admin only -->
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
		@font-face {
			font-family: 'Font Awesome 6 Brands';
			font-style: normal;
			font-weight: 400;
			src: url('/webfonts/fa-brands-400.woff2') format('woff2'),
			     url('/webfonts/fa-brands-400.ttf') format('truetype');
			font-display: swap;
		}
		.fab {
			font-family: 'Font Awesome 6 Brands' !important;
		}
		/* Expanded Header & Top Bar Layout */
		.app-header {
			padding-top: 54px !important;
		}
		.top-bar {
			height: 54px !important;
			background: var(--top-bar-background, #171b26) !important;
			border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
			box-shadow: 0 2px 12px rgba(0, 0, 0, 0.25) !important;
			display: flex !important;
			align-items: center !important;
			z-index: 100 !important;
		}
		.top-bar-inner {
			max-width: 100% !important;
			padding: 0 24px !important;
			display: flex !important;
			align-items: center !important;
			justify-content: space-between !important;
			width: 100% !important;
		}
		.top-bar-left {
			display: flex !important;
			align-items: center !important;
			gap: 18px !important;
		}
		.top-bar-logo {
			display: flex !important;
			align-items: center !important;
			margin-right: 0 !important;
		}
		.top-bar-logo img {
			height: 32px !important;
			width: auto !important;
		}
		.top-bar-usage {
			display: flex !important;
			align-items: center !important;
		}
		.top-bar-usage-inner {
			display: flex !important;
			align-items: center !important;
			gap: 14px !important;
			background: rgba(255, 255, 255, 0.05) !important;
			border: 1px solid rgba(255, 255, 255, 0.09) !important;
			padding: 5px 14px !important;
			border-radius: 20px !important;
			font-size: 12.5px !important;
			color: #e2e8f0 !important;
		}
		.top-bar-usage-item {
			display: inline-flex !important;
			align-items: center !important;
			gap: 6px !important;
			margin-right: 0 !important;
			white-space: nowrap !important;
		}
		.top-bar-usage-item i {
			font-size: 12px !important;
			opacity: 0.85 !important;
		}
		.top-bar-usage-sep {
			opacity: 0.35 !important;
			margin: 0 2px !important;
		}
		.top-bar-right {
			display: flex !important;
			align-items: center !important;
			gap: 8px !important;
		}
		.top-bar-menu-list {
			display: flex !important;
			align-items: center !important;
			gap: 5px !important;
			list-style: none !important;
			margin: 0 !important;
			padding: 0 !important;
		}
		.top-bar-menu-item {
			display: flex !important;
			align-items: center !important;
		}
		.top-bar-menu-link {
			display: inline-flex !important;
			align-items: center !important;
			justify-content: center !important;
			width: 34px !important;
			height: 34px !important;
			padding: 0 !important;
			border-radius: 8px !important;
			border: 1px solid rgba(255, 255, 255, 0.06) !important;
			background: rgba(255, 255, 255, 0.03) !important;
			color: rgba(255, 255, 255, 0.8) !important;
			font-size: 14.5px !important;
			transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1) !important;
			text-decoration: none !important;
		}
		.top-bar-menu-link:hover {
			background: rgba(255, 255, 255, 0.13) !important;
			border-color: rgba(255, 255, 255, 0.2) !important;
			color: #fff !important;
			transform: translateY(-1px) !important;
			box-shadow: 0 3px 10px rgba(0, 0, 0, 0.3) !important;
		}
		.top-bar-menu-link.active {
			background: rgba(255, 59, 119, 0.18) !important;
			border-color: rgba(255, 59, 119, 0.45) !important;
			color: #ff5e92 !important;
			box-shadow: 0 0 12px rgba(255, 59, 119, 0.22) !important;
		}
		.top-bar-menu-link-logout {
			background: rgba(239, 68, 68, 0.12) !important;
			border-color: rgba(239, 68, 68, 0.28) !important;
			color: #f87171 !important;
		}
		.top-bar-menu-link-logout:hover {
			background: rgba(239, 68, 68, 0.28) !important;
			border-color: rgba(239, 68, 68, 0.55) !important;
			color: #fff !important;
		}

		/* Clean, Simple & Flat Main Menu */
		.main-menu {
			background: var(--color-background-menu, #1a1e2a) !important;
			border-top: 1px solid rgba(255, 255, 255, 0.05) !important;
			border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
			padding: 0 !important;
			width: 100% !important;
		}
		.main-menu .container {
			max-width: 100% !important;
			display: flex !important;
			justify-content: center !important;
			padding: 0 15px !important;
		}
		.main-menu-toggle {
			display: none !important;
		}
		.main-menu-list {
			display: flex !important;
			justify-content: center !important;
			align-items: stretch !important;
			margin: 0 auto !important;
			padding: 0 !important;
			width: 100% !important;
			max-width: 1700px !important;
			gap: 0 !important;
			list-style: none !important;
		}
		.main-menu-item {
			flex: 1 1 auto !important;
			min-width: 85px !important;
			max-width: 155px !important;
			text-align: center !important;
			display: flex !important;
		}
		.main-menu-item-link {
			display: flex !important;
			flex-direction: column !important;
			align-items: center !important;
			justify-content: center !important;
			padding: 10px 10px !important;
			width: 100% !important;
			height: 100% !important;
			text-align: center !important;
			border-radius: 0 !important;
			background: transparent !important;
			border: none !important;
			border-bottom: 3px solid transparent !important;
			transition: all 0.15s ease-in-out !important;
			text-decoration: none !important;
			box-shadow: none !important;
		}
		.main-menu-item-link:hover {
			background: rgba(255, 255, 255, 0.04) !important;
			border-bottom-color: rgba(255, 255, 255, 0.25) !important;
			transform: none !important;
			box-shadow: none !important;
		}
		.main-menu-item-link.active {
			background: rgba(255, 59, 119, 0.07) !important;
			border-bottom-color: #ff3b77 !important;
			box-shadow: none !important;
		}
		.main-menu-item-link.active .main-menu-item-label {
			color: #ff3b77 !important;
		}
		.main-menu-item-label {
			white-space: nowrap !important;
			font-size: 11.5px !important;
			font-weight: 700 !important;
			letter-spacing: 0.4px !important;
			display: flex !important;
			align-items: center !important;
			justify-content: center !important;
			gap: 6px !important;
			margin: 0 0 3px 0 !important;
			color: #e2e8f0 !important;
		}
		.main-menu-item-label i, .main-menu-item-label .fas {
			font-size: 12px !important;
			opacity: 0.85 !important;
		}
		.main-menu-stats {
			font-size: 10.5px !important;
			line-height: 1.35 !important;
			text-align: center !important;
			margin: 0 !important;
			padding: 0 !important;
			list-style: none !important;
			color: #94a3b8 !important;
		}
		.main-menu-stats li {
			white-space: nowrap !important;
			overflow: hidden !important;
			text-overflow: ellipsis !important;
			max-width: 145px !important;
		}
	</style>

	<nav class="main-menu">
		<div class="container">
			<ul class="main-menu-list">

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

				<?php
					/* NexviaCP: docker-app backed API services & databases
					   (sidebar counters; --fast skips live container stats) */
					$nx_api_extra = 0;
					$nx_db_extra = 0;
					exec(HESTIA_CMD . "v-list-web-domain-apps --fast json 2>/dev/null", $nx_a_out, $nx_a_rc);
					if ($nx_a_rc === 0) {
						$nx_a = json_decode(implode("", $nx_a_out) ?: "[]", true);
						if (is_array($nx_a)) {
							$nx_api_extra = count($nx_a);
						}
					}
					unset($nx_a_out);
					exec(HESTIA_CMD . "v-list-docker-app-databases json 2>/dev/null", $nx_d_out, $nx_d_rc);
					if ($nx_d_rc === 0) {
						$nx_d = json_decode(implode("", $nx_d_out) ?: "[]", true);
						if (is_array($nx_d)) {
							foreach ($nx_d as $nx_one) {
								$nx_owners = array_map("trim", explode(",", (string)($nx_one["OWNER"] ?? "")));
								if (($_SESSION["userContext"] ?? "") === "admin" || in_array($user, $nx_owners, true)) {
									$nx_db_extra++;
								}
							}
						}
					}
					unset($nx_d_out);
				?>

				<!-- 5. Databases tab -->
				<?php if (isset($_SESSION["DB_SYSTEM"]) && !empty($_SESSION["DB_SYSTEM"])) { ?>
					<?php if ($panel[$user]["DATABASES"] != "0") { ?>
						<li class="main-menu-item">
							<a class="main-menu-item-link <?php if ($TAB == "DB") {
       	echo "active";
       } ?>" href="/list/db/" title="<?= _("Databases") ?>: <?= (int)$panel[$user]["U_DATABASES"] + $nx_db_extra ?>&#13;<?= _("Limit") ?>: <?= $panel[$user]["DATABASES"] == "unlimited"
	? "∞"
	: $panel[$user]["DATABASES"] ?>&#13;<?= _("Suspended") ?>: <?= $panel[$user]["SUSPENDED_DB"] ?>">
								<p class="main-menu-item-label"><?= _("DB") ?><i class="fas fa-database"></i></p>
								<ul class="main-menu-stats">
									<li>
										<?= _("Databases") ?>: <?= (int)$panel[$user]["U_DATABASES"] + $nx_db_extra ?> / <?= $panel[$user]["DATABASES"] == "unlimited" ? "<span class=\"u-text-bold\">∞</span>" : $panel[$user]["DATABASES"] ?>
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
									<?= $is_panel_tr ? "servisler" : _("Services") ?>: <?= htmlspecialchars($panel[$user]["U_API_SERVICES"] ?? (count(glob('/etc/systemd/system/hestia-app-*.service') ?: []) + $nx_api_extra)) ?> / <span class="u-text-bold">∞</span>
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
