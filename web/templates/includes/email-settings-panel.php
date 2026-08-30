<div class="panel">
	<div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:10px 12px; margin-bottom:16px; display:flex; align-items:center; justify-content:space-between;">
		<span style="font-size:12px; font-weight:700; color:#15803d; display:flex; align-items:center; gap:6px;">
			<i class="fas fa-circle-check"></i> SMTP &amp; IMAP Aktif
		</span>
		<span style="font-size:11px; background:#dcfce7; color:#166534; padding:2px 8px; border-radius:12px; font-weight:700;">Port 587 / 993</span>
	</div>

	<h2 class="u-text-H3 u-mb10"><?= _("Common Account Settings") ?></h2>
	<p class="u-mb10">
		<?= _("Connect to this account using credentials:") ?>
	</p>
	<div class="u-mb10">
		<label for="email_settings_username"><?= _("Username") ?></label>
		<div class="clipboard">
			<input type="text" class="form-control clipboard-input js-copy-input js-account-output" name="email_settings_username" id="email_settings_username" value="<?= htmlentities(trim($v_account, "'")) ?>@<?= htmlentities(trim($v_domain, "'")) ?>" data-postfix="@<?= htmlentities(trim($v_domain, "'")) ?>" readonly>
			<button type="button" class="clipboard-button js-copy-button" title="<?= _("Copy to clipboard") ?>">
				<i class="fas fa-copy"></i>
			</button>
		</div>
	</div>
	<div class="u-mb10">
		<label for="email_settings_password"><?= _("Password") ?></label>
		<div class="clipboard">
			<input type="text" class="form-control clipboard-input js-copy-input js-password-output" name="email_settings_password" id="email_settings_password" placeholder="Yeni şifre belirleyebilirsiniz" readonly>
			<button type="button" class="clipboard-button js-copy-button" title="<?= _("Copy to clipboard") ?>">
				<i class="fas fa-copy"></i>
			</button>
		</div>
		<small style="color:#64748b; font-size:11px; display:block; margin-top:4px;">
			<i class="fas fa-info-circle"></i> Şifrenizi unuttuysanız sol formdan yeni şifre yazıp Kaydet'e basın.
		</small>
	</div>
	<?php if ($_SESSION["WEBMAIL_SYSTEM"]) { ?>
		<div class="u-mb10">
			<label for="email_settings_webmail"><?= _("Webmail") ?></label>
			<div class="clipboard">
				<input type="text" class="form-control clipboard-input js-copy-input" name="email_settings_webmail" id="email_settings_webmail" value="http://<?= htmlentities($v_webmail_alias) ?>" readonly>
				<button type="button" class="clipboard-button js-copy-button" title="<?= _("Copy to clipboard") ?>">
					<i class="fas fa-copy"></i>
				</button>
			</div>
		</div>
	<?php } ?>
	<div class="u-mb20">
		<label for="email_settings_hostname"><?= _("Hostname") ?></label>
		<div class="clipboard">
			<input type="text" class="form-control clipboard-input js-copy-input" name="email_settings_hostname" id="email_settings_hostname" value="mail.<?= htmlentities($v_domain) ?>" readonly>
			<button type="button" class="clipboard-button js-copy-button" title="<?= _("Copy to clipboard") ?>">
				<i class="fas fa-copy"></i>
			</button>
		</div>
	</div>
	<h2 class="u-text-H3 u-mb10"><?= _("IMAP Settings") ?></h2>
	<ul class="values-list u-mb20">
		<li class="values-list-item">
			<span class="values-list-label"><?= _("Authentication") ?></span>
			<span class="values-list-value"><?= _("Normal password") ?></span>
		</li>
		<li class="values-list-item">
			<span class="values-list-label">SSL/TLS</span>
			<span class="values-list-value"><?= _("Port") ?> 993</span>
		</li>
		<li class="values-list-item">
			<span class="values-list-label">STARTTLS</span>
			<span class="values-list-value"><?= _("Port") ?> 143</span>
		</li>
		<li class="values-list-item">
			<span class="values-list-label"><?= _("No encryption") ?></span>
			<span class="values-list-value"><?= _("Port") ?> 143</span>
		</li>
	</ul>
	<h2 class="u-text-H3 u-mb10"><?= _("POP3 Settings") ?></h2>
	<ul class="values-list u-mb20">
		<li class="values-list-item">
			<span class="values-list-label"><?= _("Authentication") ?></span>
			<span class="values-list-value"><?= _("Normal password") ?></span>
		</li>
		<li class="values-list-item">
			<span class="values-list-label">SSL/TLS</span>
			<span class="values-list-value"><?= _("Port") ?> 995</span>
		</li>
		<li class="values-list-item">
			<span class="values-list-label">STARTTLS</span>
			<span class="values-list-value"><?= _("Port") ?> 110</span>
		</li>
		<li class="values-list-item">
			<span class="values-list-label"><?= _("No encryption") ?></span>
			<span class="values-list-value"><?= _("Port") ?> 110</span>
		</li>
	</ul>
	<h2 class="u-text-H3 u-mb10"><?= _("SMTP Settings") ?></h2>
	<ul class="values-list">
		<li class="values-list-item">
			<span class="values-list-label"><?= _("Authentication") ?></span>
			<span class="values-list-value"><?= _("Normal password") ?></span>
		</li>
		<li class="values-list-item">
			<span class="values-list-label">SSL/TLS</span>
			<span class="values-list-value"><?= _("Port") ?> 465</span>
		</li>
		<li class="values-list-item">
			<span class="values-list-label">STARTTLS</span>
			<span class="values-list-value"><?= _("Port") ?> 587</span>
		</li>
		<li class="values-list-item">
			<span class="values-list-label"><?= _("No encryption") ?></span>
			<span class="values-list-value"><?= _("Port") ?> 25</span>
		</li>
	</ul>
	<h2 class="u-text-H3 u-mb10 u-mt20"><?= _("Nexvia / App Config (.env)") ?></h2>
	<div class="clipboard u-mb10">
		<textarea class="form-control clipboard-input js-copy-input" rows="5" readonly style="font-family:monospace; font-size:11px; resize:none;">SMTP_HOST=mail.<?= htmlentities($v_domain) ?>&#10;SMTP_PORT=587&#10;SMTP_USERNAME=<?= htmlentities(trim($v_account, "'")) ?>@<?= htmlentities(trim($v_domain, "'")) ?>&#10;SMTP_SECURE=tls&#10;SMTP_FROM_EMAIL=<?= htmlentities(trim($v_account, "'")) ?>@<?= htmlentities(trim($v_domain, "'")) ?></textarea>
		<button type="button" class="clipboard-button js-copy-button" title="<?= _("Copy to clipboard") ?>">
			<i class="fas fa-copy"></i>
		</button>
	</div>
</div>
