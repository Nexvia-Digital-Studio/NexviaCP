<?php
$v_webmail_alias = "webmail";
if (!empty($_SESSION["WEBMAIL_ALIAS"])) {
	$v_webmail_alias = $_SESSION["WEBMAIL_ALIAS"];
}
?>
<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/mail/">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml( _("Back")) ?>
			</a>
			<?php if ($read_only !== "true") { ?>
				<a href="/add/mail/?<?= tohtml(http_build_query(["domain" => $_GET["domain"]])) ?>" class="button button-secondary js-button-create">
					<i class="fas fa-circle-plus icon-green"></i><?= tohtml( _("Add Mail Account")) ?>
				</a>
				<a href="/edit/mail/?<?= tohtml(http_build_query(["domain" => $_GET["domain"]])) ?>" class="button button-secondary js-button-create">
					<i class="fas fa-pencil icon-blue"></i><?= tohtml( _("Edit Mail Domain")) ?>
				</a>
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
					<li data-entity="sort-date" data-sort-as-int="1">
						<span class="name <?php if ($_SESSION['userSortOrder'] === 'date') { echo 'active'; } ?>"><?= tohtml( _("Date")) ?> <i class="fas fa-arrow-down-a-z"></i></span><span class="up"><i class="fas fa-arrow-up-a-z"></i></span>
					</li>
					<li data-entity="sort-disk" data-sort-as-int="1">
						<span class="name"><?= tohtml( _("Disk")) ?> <i class="fas fa-arrow-down-a-z"></i></span><span class="up"><i class="fas fa-arrow-up-a-z"></i></span>
					</li>
					<li data-entity="sort-name">
						<span class="name <?php if ($_SESSION['userSortOrder'] === 'name') { echo 'active'; } ?>"><?= tohtml( _("Name")) ?> <i class="fas fa-arrow-down-a-z"></i></span><span class="up"><i class="fas fa-arrow-up-a-z"></i></span>
					</li>
					<li data-entity="sort-quota" data-sort-as-int="1">
						<span class="name"><?= tohtml( _("Quota")) ?> <i class="fas fa-arrow-down-a-z"></i></span><span class="up"><i class="fas fa-arrow-up-a-z"></i></span>
					</li>
				</ul>
				<?php if ($read_only !== "true") { ?>
					<form x-data x-bind="BulkEdit" action="/bulk/mail/" method="post">
						<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
						<input type="hidden" value="<?= tohtml($_GET["domain"]) ?>" name="domain">
						<select class="form-select" name="action">
							<option value=""><?= tohtml( _("Apply to selected")) ?></option>
							<option value="suspend"><?= tohtml( _("Suspend")) ?></option>
							<option value="unsuspend"><?= tohtml( _("Unsuspend")) ?></option>
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

<div class="container" x-data="{
	modalOpen: false,
	selectedAccount: '',
	selectedDomain: '<?= tohtml($_GET['domain'] ?? '') ?>',
	webmailAlias: '<?= tohtml($v_webmail_alias ?? 'webmail') ?>',
	token: '<?= tohtml($_SESSION['token'] ?? '') ?>',
	newPassword: '',
	showPassword: true,
	copied: false,
	openModal(acc) {
		this.selectedAccount = acc;
		this.newPassword = '';
		this.modalOpen = true;
		this.copied = false;
	},
	copyText(txt) {
		navigator.clipboard.writeText(txt);
		this.copied = true;
		setTimeout(() => { this.copied = false; }, 2000);
	},
	generatePass() {
		const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#$%&*';
		let pass = '';
		for (let i = 0; i < 14; i++) {
			pass += chars.charAt(Math.floor(Math.random() * chars.length));
		}
		this.newPassword = pass;
	}
}">

	<h1 class="u-text-center u-hide-desktop u-mt20 u-pr30 u-mb20 u-pl30"><?= tohtml( _("Mail Accounts")) ?></h1>

	<div class="units-table js-units-container">
		<div class="units-table-header">
			<div class="units-table-cell">
				<input type="checkbox" class="js-toggle-all-checkbox" title="<?= tohtml( _("Select all")) ?>" <?= tohtml($display_mode) ?>>
			</div>
			<div class="units-table-cell"><?= tohtml( _("Name")) ?></div>
			<div class="units-table-cell"></div>
			<div class="units-table-cell u-text-center"><?= tohtml( _("Disk")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml( _("Quota")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml( _("Aliases")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml( _("Forwarding")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml( _("Auto Reply")) ?></div>
		</div>

		<!-- Begin mail account list item loop -->
		<?php
			foreach ($data as $key => $value) {
				++$i;
				if ($data[$key]['SUSPENDED'] == 'yes') {
					$status = 'suspended';
					$spnd_action = 'unsuspend';
					$spnd_action_title = _('Unsuspend');
					$spnd_icon = 'fa-play';
					$spnd_icon_class = 'icon-green';
					$spnd_confirmation = _('Are you sure you want to unsuspend %s?');
					if ($data[$key]['ALIAS'] == '') {
						$alias_icon = 'fa-circle-minus';
						$alias_title = _('No aliases');
					} else {
						$alias_icon = 'fa-circle-check';
						$alias_title = _('Aliases used');
					}
					if ($data[$key]['FWD'] == '') {
						$fwd_icon = 'fa-circle-minus';
						$fwd_title = _('Disabled');
					} else {
						$fwd_icon = 'fa-circle-check';
						$fwd_title = _('Enabled');
					}
					if ($data[$key]['AUTOREPLY'] == 'no') {
						$autoreply_icon = 'fa-circle-minus';
						$autoreply_title = _('Disabled');
					} else {
						$autoreply_icon = 'fa-circle-check';
						$autoreply_title = _('Enabled');
					}
				} else {
					$status = 'active';
					$spnd_action = 'suspend';
					$spnd_action_title = _('Suspend');
					$spnd_icon = 'fa-pause';
					$spnd_icon_class = 'icon-highlight';
					$spnd_confirmation = _('Are you sure you want to suspend %s?');
					if ($data[$key]['ALIAS'] == '') {
						$alias_icon = 'fa-circle-minus';
						$alias_title = _('No aliases');
					} else {
						$alias_icon = 'fa-circle-check icon-green';
						$alias_title = _('Aliases used');
					}
					if ($data[$key]['FWD'] == '') {
						$fwd_icon = 'fa-circle-minus';
						$fwd_title = _('Disabled');
					} else {
						$fwd_icon = 'fa-circle-check icon-green';
						$fwd_title = _('Enabled');
					}
					if ($data[$key]['AUTOREPLY'] == 'no') {
						$autoreply_icon = 'fa-circle-minus';
						$autoreply_title = _('Disabled');
					} else {
						$autoreply_icon = 'fa-circle-check icon-green';
						$autoreply_title = _('Enabled');
					}
				}
			?>
			<div class="units-table-row <?php if ($status == 'suspended') echo 'disabled'; ?> js-unit"
				data-sort-date="<?= tohtml(strtotime($data[$key]['DATE'].' '.$data[$key]['TIME'])) ?>"
				data-sort-name="<?= tohtml($key) ?>"
				data-sort-disk="<?= tohtml($data[$key]["U_DISK"]) ?>"
				data-sort-quota="<?= tohtml($data[$key]["QUOTA"]) ?>">
				<div class="units-table-cell">
					<div>
						<input id="check<?= tohtml($i) ?>" class="js-unit-checkbox" type="checkbox" title="<?= tohtml( _("Select")) ?>" name="account[]" value="<?= tohtml($key) ?>" <?= tohtml($display_mode) ?>>
						<label for="check<?= tohtml($i) ?>" class="u-hide-desktop"><?= tohtml( _("Select")) ?></label>
					</div>
				</div>
				<div class="units-table-cell units-table-heading-cell u-text-bold">
					<span class="u-hide-desktop"><?= tohtml( _("Name")) ?>:</span>
					<?php if ($read_only === "true" || $data[$key]["SUSPENDED"] == "yes") { ?>
						<?= tohtml($key . "@" . $_GET["domain"]) ?>
					<?php } else { ?>
						<a href="/edit/mail/?<?= tohtml(http_build_query(["domain" => $_GET['domain'], "account" => $key, "token" => $_SESSION['token']])) ?>" title="<?= tohtml( _("Edit Mail Account")) ?>: <?= tohtml($key) ?>@<?= tohtml($_GET['domain']) ?>">
							<?= tohtml($key . "@" . $_GET['domain']) ?>
						</a>
					<?php } ?>
				</div>
				<div class="units-table-cell">
					<ul class="units-table-row-actions">
						<?php if ($read_only === "true") { ?>
							<!-- Restrict the ability to edit, delete, or suspend domain items when impersonating 'admin' account -->
							<?php if ($data[$key]["SUSPENDED"] != "yes") { ?>
								<li class="units-table-row-action" data-key-action="href">
									<a
										class="units-table-row-action-link"
										href="https://<?= tohtml($v_webmail_alias) ?>.<?= tohtml($_GET["domain"]) ?>/?<?= tohtml(http_build_query(["_user" => $key . '@' . $_GET["domain"], "email" => $key . '@' . $_GET["domain"]])) ?>"
										target="_blank"
										title="<?= tohtml( _("Open Webmail")) ?>"
									>
										<i class="fas fa-envelope-open-text icon-maroon"></i>
										<span class="u-hide-desktop"><?= tohtml( _("Open Webmail")) ?></span>
									</a>
								</li>
							<?php } ?>
						<?php } else { ?>
							<?php if ($data[$key]["SUSPENDED"] == "no") { ?>
								<?php if ($_SESSION["WEBMAIL_SYSTEM"]) { ?>
									<?php if (!empty($data[$key]["WEBMAIL"])) { ?>
										<li class="units-table-row-action" data-key-action="href">
											<a
												class="units-table-row-action-link"
												href="https://<?= tohtml($v_webmail_alias) ?>.<?= tohtml($_GET["domain"]) ?>/?<?= tohtml(http_build_query(["_user" => $key . '@' . $_GET["domain"], "email" => $key . '@' . $_GET["domain"]])) ?>"
												target="_blank"
												title="<?= tohtml( _("Open Webmail (Otomatik Giriş / Doldurulmuş)")) ?>"
											>
												<i class="fas fa-envelope-open-text icon-maroon"></i>
												<span class="u-hide-desktop"><?= tohtml( _("Open Webmail")) ?></span>
											</a>
										</li>
									<?php } ?>
								<?php } ?>
								<li class="units-table-row-action" data-key-action="js">
									<button
										type="button"
										class="units-table-row-action-link"
										style="background:none; border:none; padding:0 8px; cursor:pointer; display:inline-flex; align-items:center;"
										x-on:click="openModal('<?= tohtml($key) ?>'); $refs.dialog.showModal()"
										title="<?= tohtml( _("Şifre Değiştir & SMTP Bilgileri")) ?>"
									>
										<i class="fas fa-key icon-blue"></i>
										<span class="u-hide-desktop"><?= tohtml( _("Şifre & SMTP")) ?></span>
									</button>
								</li>
								<li class="units-table-row-action shortcut-enter" data-key-action="href">
									<a
										class="units-table-row-action-link"
										href="/edit/mail/?<?= tohtml(http_build_query(["domain" => $_GET["domain"], "account" => $key, "token" => $_SESSION["token"]])) ?>"
										title="<?= tohtml( _("Edit Mail Account")) ?>"
									>
										<i class="fas fa-pencil icon-orange"></i>
										<span class="u-hide-desktop"><?= tohtml( _("Edit Mail Account")) ?></span>
									</a>
								</li>
							<?php } ?>
							<li class="units-table-row-action shortcut-s" data-key-action="js">
								<a
									class="units-table-row-action-link data-controls js-confirm-action"
									href="/<?= tohtml($spnd_action) ?>/mail/?<?= tohtml(http_build_query(["domain" => $_GET["domain"], "account" => $key, "token" => $_SESSION["token"]])) ?>"
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
									href="/delete/mail/?<?= tohtml(http_build_query(["domain" => $_GET["domain"], "account" => $key, "token" => $_SESSION["token"]])) ?>"
									title="<?= tohtml( _("Delete")) ?>"
									data-confirm-title="<?= tohtml( _("Delete")) ?>"
									data-confirm-message="<?= tohtml(sprintf(_("Are you sure you want to delete %s?"), $key)) ?>"
								>
									<i class="fas fa-trash icon-red"></i>
									<span class="u-hide-desktop"><?= tohtml( _("Delete")) ?></span>
								</a>
							</li>
						<?php } ?>
					</ul>
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
					<span class="u-hide-desktop u-text-bold"><?= tohtml( _("Quota")) ?>:</span>
					<span class="u-text-bold">
						<?= tohtml(humanize_usage_size($data[$key]["QUOTA"])) ?>
					</span>
					<span class="u-text-small">
						<?= tohtml(humanize_usage_measure($data[$key]["QUOTA"])) ?>
					</span>
				</div>
				<div class="units-table-cell u-text-center-desktop">
					<span class="u-hide-desktop u-text-bold"><?= tohtml( _("Aliases")) ?>:</span>
					<i class="fas <?= tohtml($alias_icon) ?>" title="<?= tohtml($alias_title) ?>"></i>
				</div>
				<div class="units-table-cell u-text-center-desktop">
					<span class="u-hide-desktop u-text-bold"><?= tohtml( _("Forwarding")) ?>:</span>
					<i class="fas <?= tohtml($fwd_icon) ?>" title="<?= tohtml($fwd_title) ?>"></i>
				</div>
				<div class="units-table-cell u-text-center-desktop">
					<span class="u-hide-desktop u-text-bold"><?= tohtml( _("Auto Reply")) ?>:</span>
					<i class="fas <?= tohtml($autoreply_icon) ?>" title="<?= tohtml($autoreply_title) ?>"></i>
				</div>
			</div>
		<?php } ?>
	</div>

	<div class="units-table-footer">
		<p>
			<?php printf(ngettext("%d mail account", "%d mail accounts", $i), $i); ?>
		</p>
	</div>

	<!-- Native Mail & SMTP Connection Dialog -->
	<dialog x-ref="dialog" class="shortcuts" style="max-width: 600px; width: 92%; padding: 0;">
		<div class="shortcuts-header">
			<div class="shortcuts-title" style="display: flex; align-items: center; gap: 8px;">
				<i class="fas fa-envelope icon-lightblue"></i>
				<span x-text="selectedAccount + '@' + selectedDomain"></span>
			</div>
			<div
				x-on:click="$refs.dialog.close()"
				class="shortcuts-close"
				style="cursor: pointer;"
			>
				<i class="fas fa-xmark"></i>
			</div>
		</div>

		<div class="shortcuts-inner" style="padding: 24px;">
			<!-- Şifre Değiştirme -->
			<form method="post" :action="'/edit/mail/?domain=' + encodeURIComponent(selectedDomain) + '&account=' + encodeURIComponent(selectedAccount)" class="u-mb20">
				<input type="hidden" name="token" :value="token">
				<input type="hidden" name="save" value="save">
				<input type="hidden" name="v_account" :value="selectedAccount">
				<input type="hidden" name="v_domain" :value="selectedDomain">

				<h2 class="u-text-H3 u-mb10"><?= _("Change Password") ?></h2>
				<div class="u-mb10">
					<label class="form-label" for="modal_v_password"><?= _("New Password") ?></label>
					<div style="display: flex; gap: 8px;">
						<div class="u-pos-relative" style="flex: 1;">
							<input
								:type="showPassword ? 'text' : 'password'"
								name="v_password"
								id="modal_v_password"
								x-model="newPassword"
								class="form-control"
								placeholder="<?= _("Enter new password") ?>"
								required
							>
						</div>
						<button
							type="button"
							x-on:click="generatePass()"
							class="button button-secondary"
							title="<?= _("Generate") ?>"
						>
							<i class="fas fa-arrows-rotate icon-green"></i>
						</button>
						<button type="submit" class="button">
							<i class="fas fa-floppy-disk icon-purple"></i> <?= _("Save") ?>
						</button>
					</div>
				</div>
			</form>

			<h2 class="u-text-H3 u-mb10"><?= _("Common Account Settings") ?></h2>
			<div class="u-mb10">
				<label class="form-label"><?= _("Username") ?></label>
				<div class="clipboard">
					<input type="text" class="form-control clipboard-input js-copy-input" :value="selectedAccount + '@' + selectedDomain" readonly>
					<button type="button" class="clipboard-button" x-on:click="copyText(selectedAccount + '@' + selectedDomain)" title="<?= _("Copy to clipboard") ?>">
						<i class="fas fa-copy"></i>
					</button>
				</div>
			</div>

			<div class="u-mb20">
				<label class="form-label"><?= _("Hostname") ?></label>
				<div class="clipboard">
					<input type="text" class="form-control clipboard-input js-copy-input" :value="'mail.' + selectedDomain" readonly>
					<button type="button" class="clipboard-button" x-on:click="copyText('mail.' + selectedDomain)" title="<?= _("Copy to clipboard") ?>">
						<i class="fas fa-copy"></i>
					</button>
				</div>
			</div>

			<h2 class="u-text-H3 u-mb10"><?= _("SMTP Settings") ?></h2>
			<ul class="values-list u-mb20">
				<li class="values-list-item">
					<span class="values-list-label"><?= _("Authentication") ?></span>
					<span class="values-list-value"><?= _("Normal password") ?></span>
				</li>
				<li class="values-list-item">
					<span class="values-list-label">STARTTLS</span>
					<span class="values-list-value"><?= _("Port") ?> 587</span>
				</li>
				<li class="values-list-item">
					<span class="values-list-label">SSL/TLS</span>
					<span class="values-list-value"><?= _("Port") ?> 465</span>
				</li>
			</ul>

			<h2 class="u-text-H3 u-mb10"><?= _("App Configuration (.env)") ?></h2>
			<div class="clipboard u-mb20">
				<textarea
					class="form-control clipboard-input js-copy-input"
					rows="4"
					readonly
					style="font-family: monospace; font-size: 11px; resize: none;"
					x-text="'SMTP_HOST=mail.' + selectedDomain + '\nSMTP_PORT=587\nSMTP_USERNAME=' + selectedAccount + '@' + selectedDomain + '\nSMTP_SECURE=tls\nSMTP_FROM_EMAIL=' + selectedAccount + '@' + selectedDomain"
				></textarea>
				<button
					type="button"
					class="clipboard-button"
					x-on:click="copyText('SMTP_HOST=mail.' + selectedDomain + '\nSMTP_PORT=587\nSMTP_USERNAME=' + selectedAccount + '@' + selectedDomain + '\nSMTP_SECURE=tls\nSMTP_FROM_EMAIL=' + selectedAccount + '@' + selectedDomain)"
					title="<?= _("Copy to clipboard") ?>"
				>
					<i class="fas fa-copy"></i>
				</button>
			</div>

			<div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-top: 20px;">
				<a
					class="button button-secondary"
					:href="'https://' + (webmailAlias || 'webmail') + '.' + selectedDomain + '/?_user=' + encodeURIComponent(selectedAccount + '@' + selectedDomain) + '&email=' + encodeURIComponent(selectedAccount + '@' + selectedDomain)"
					target="_blank"
				>
					<i class="fas fa-envelope-open-text icon-maroon"></i> <?= _("Open Webmail") ?>
				</a>
				<button
					type="button"
					class="button button-secondary"
					x-on:click="$refs.dialog.close()"
				>
					<?= _("Close") ?>
				</button>
			</div>
		</div>
	</dialog>

</div>


