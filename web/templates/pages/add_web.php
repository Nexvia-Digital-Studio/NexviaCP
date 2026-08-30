<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/web/">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml( _("Back")) ?>
			</a>
		</div>
		<div class="toolbar-buttons">
			<?php if (($_SESSION["role"] == "admin" && $accept === "true") || $_SESSION["role"] !== "admin") { ?>
				<button type="submit" class="button" form="main-form">
					<i class="fas fa-floppy-disk icon-purple"></i><?= tohtml( _("Save")) ?>
				</button>
			<?php } ?>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">

	<form id="main-form" name="v_add_web" method="post" class="js-enable-inputs-on-submit">
		<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
		<input type="hidden" name="ok" value="Add">

		<div class="form-container">
			<h1 class="u-mb20"><?= tohtml( _("Add Web Domain")) ?></h1>
			<?php show_alert_message($_SESSION); ?>
			<?php if ($_SESSION["role"] == "admin" && $accept !== "true") { ?>
				<div class="alert alert-danger" role="alert">
					<i class="fas fa-exclamation"></i>
					<p><?= htmlify_trans(sprintf(_("It is strongly advised to {create a standard user account} before adding %s to the server due to the increased privileges the admin account possesses and potential security risks."), _('a web domain')), '</a>', '<a href="/add/user/">') ?></p>
				</div>
			<?php } ?>
			<?php if ($_SESSION["role"] == "admin" && empty($accept)) { ?>
				<div class="u-side-by-side u-mt20">
					<a href="/add/user/" class="button u-width-full u-mr10"><?= tohtml( _("Add User")) ?></a>
					<a href="/add/web/?<?= tohtml(http_build_query(["accept" => 'true'])) ?>" class="button button-danger u-width-full u-ml10"><?= tohtml( _("Continue")) ?></a>
				</div>
			<?php } ?>
			<?php if (($_SESSION["role"] == "admin" && $accept === "true") || $_SESSION["role"] !== "admin") { ?>
				<div class="u-mb10">
					<label for="v_domain" class="form-label"><?= tohtml( _("Domain")) ?></label>
					<input type="text" class="form-control" name="v_domain" id="v_domain" value="<?= tohtml(trim($v_domain, "'")) ?>" required>
				</div>
				<div class="u-mb20">
					<label for="v_ip" class="form-label"><?= tohtml( _("IP Address")) ?></label>
					<select class="form-select" name="v_ip" id="v_ip">
						<?php
							foreach ($ips as $ip => $value) {
								$display_ip = htmlentities(empty($value['NAT']) ? $ip : "{$value['NAT']}");
								$ip_selected = (!empty($v_ip) && $ip == $_POST['v_ip']) ? 'selected' : '';
								echo "\t\t\t\t<option value=\"{$ip}\" {$ip_selected}>{$display_ip}</option>\n";
							}
						?>
					</select>
				</div>
				<div class="u-mb20">
					<label for="v_expiry" class="form-label">
						<i class="fas fa-hourglass-half icon-yellow u-mr5"></i><?= tohtml(function_exists('__tr') ? __tr("Domain Validity & Subscription Duration", "Domain Geçerlilik & Lisans Süresi") : _("Domain Validity Duration")) ?>
					</label>
					<select class="form-select" name="v_expiry" id="v_expiry" onchange="document.getElementById('custom-expiry-wrap').style.display = (this.value === 'custom') ? 'block' : 'none';">
						<option value="1y" selected>📅 <?= tohtml(function_exists('__tr') ? __tr("1 Year (Standard Annual Sale - Default)", "1 Yıl (Standart Yıllık Satış - Varsayılan)") : "1 Year") ?></option>
						<option value="3d">⏳ <?= tohtml(function_exists('__tr') ? __tr("3 Days (Demo / Trial)", "3 Gün (Demo / Deneme)") : "3 Days") ?></option>
						<option value="1m">🗓️ <?= tohtml(function_exists('__tr') ? __tr("1 Month", "1 Ay") : "1 Month") ?></option>
						<option value="3m">🗓️ <?= tohtml(function_exists('__tr') ? __tr("3 Months", "3 Ay") : "3 Months") ?></option>
						<option value="6m">🗓️ <?= tohtml(function_exists('__tr') ? __tr("6 Months", "6 Ay") : "6 Months") ?></option>
						<option value="2y">📅 <?= tohtml(function_exists('__tr') ? __tr("2 Years", "2 Yıl") : "2 Years") ?></option>
						<option value="3y">📅 <?= tohtml(function_exists('__tr') ? __tr("3 Years", "3 Yıl") : "3 Years") ?></option>
						<option value="5y">📅 <?= tohtml(function_exists('__tr') ? __tr("5 Years", "5 Yıl") : "5 Years") ?></option>
						<option value="unlimited">♾️ <?= tohtml(function_exists('__tr') ? __tr("Unlimited (Lifetime / No Expiration)", "Sınırsız / Süresiz Lisans") : "Unlimited") ?></option>
						<option value="custom">✏️ <?= tohtml(function_exists('__tr') ? __tr("Custom Expiration Date…", "Özel Bitiş Tarihi Seçimi…") : "Custom Date") ?></option>
					</select>
					<div id="custom-expiry-wrap" style="display:none; margin-top:10px;">
						<label for="v_expiry_custom_date" class="form-label" style="font-size:12px;"><?= tohtml(function_exists('__tr') ? __tr("Custom Expiry Date (YYYY-MM-DD)", "Özel Bitiş Tarihi") : "Custom Expiry Date") ?></label>
						<input type="date" class="form-control" name="v_expiry_custom_date" id="v_expiry_custom_date" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+1 year')) ?>">
					</div>
					<small class="form-text text-muted u-mt5">
						<?= tohtml(function_exists('__tr') ? __tr("When the duration expires, the site and redirection will automatically stop/suspend. Extending duration later restores it immediately.", "Süre dolduğunda site ve yönlendirmesi otomatik durdurulur/askıya alınır. Panele yeni süre girildiğinde site anında tekrar açılır.") : "Site is suspended when duration ends.") ?>
					</small>
				</div>
				<div class="u-mb20">
					<label for="v_app_preset" class="form-label">
						<i class="fas fa-layer-group icon-purple u-mr5"></i><?= tohtml( _("Application Runtime & Proxy Preset (Uygulama Çalıştırma Tipi)")) ?>
					</label>
					<select class="form-select" name="v_proxy_template" id="v_app_preset">
						<option value="default" selected>🐘 PHP Web Application (PHP-FPM Default)</option>
						<option value="node-js">🟢 Node.js / Express / Next.js (Port 3000)</option>
						<option value="dotnet">🔷 .NET 8 / 9 / 10 ASP.NET Core (Port 5000)</option>
						<option value="websocket">⚡ Live WebSocket & Socket.io (Port 3000)</option>
						<?php if (($_SESSION["userContext"] ?? "") === "admin") { ?>
						<option value="docker-ui">🐳 Docker UI / Portainer Management (Port 9000)</option>
						<?php } ?>
					</select>
					<small class="form-text text-muted u-mt5"><?= tohtml( _("Uygulamanızın çalışma modunu seçin. Seçilen mod Nginx reverse proxy ve SSL yönlendirmesini otomatik ayarlar.")) ?></small>
				</div>
				<?php if (isset($_SESSION["DNS_SYSTEM"]) && !empty($_SESSION["DNS_SYSTEM"]) && $_SESSION["DNS_SYSTEM"] !== "no") { ?>
					<?php if ($panel[$user_plain]["DNS_DOMAINS"] != "0") { ?>
						<div class="form-check u-mb10">
							<input class="form-check-input" type="checkbox" name="v_dns" id="v_dns" <?php if (empty($v_dns) && $panel[$user_plain]["DNS_DOMAINS"] != "0"); ?>>
							<label for="v_dns">
								<?= tohtml( _("DNS Support")) ?>
							</label>
						</div>
					<?php } ?>
				<?php } ?>
				<?php if (isset($_SESSION["IMAP_SYSTEM"]) && !empty($_SESSION["IMAP_SYSTEM"])) { ?>
					<?php if ($panel[$user_plain]["MAIL_DOMAINS"] != "0") { ?>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="v_mail" id="v_mail" <?php if (empty($v_mail) && $panel[$user_plain]["MAIL_DOMAINS"] != "0"); ?>>
							<label for="v_mail">
								<?= tohtml( _("Mail Support")) ?>
							</label>
						</div>
					<?php } ?>
				<?php } ?>
			<?php } ?>
		</div>

	</form>

</div>
