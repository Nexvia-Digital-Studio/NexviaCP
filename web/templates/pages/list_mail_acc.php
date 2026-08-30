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
										x-on:click="openModal('<?= tohtml($key) ?>')"
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

	<!-- Interactive Mail & SMTP Connection Modal with Direct Password Change -->
	<div
		x-cloak
		x-show="modalOpen"
		x-transition:enter="transition ease-out duration-200"
		x-transition:enter-start="opacity-0"
		x-transition:enter-end="opacity-100"
		x-transition:leave="transition ease-in duration-150"
		x-transition:leave-start="opacity-100"
		x-transition:leave-end="opacity-0"
		style="position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(15,23,42,0.65); backdrop-filter:blur(4px); z-index:9999; display:flex; align-items:center; justify-content:center; padding:20px;"
		x-on:click.self="modalOpen = false"
		x-on:keydown.escape.window="modalOpen = false"
	>
		<div
			style="background:#ffffff; width:100%; max-width:620px; border-radius:16px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); border:1px solid #e2e8f0; overflow:hidden; font-family:inherit; color:#1e293b;"
			x-transition:enter="transition ease-out duration-200"
			x-transition:enter-start="opacity-0 transform scale-95"
			x-transition:enter-end="opacity-100 transform scale-100"
		>
			<!-- Header -->
			<div style="background:#0f172a; color:#ffffff; padding:20px 24px; display:flex; align-items:center; justify-content:space-between; border-bottom:3px solid #3b82f6;">
				<div style="display:flex; align-items:center; gap:12px;">
					<div style="background:rgba(59,130,246,0.2); width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#60a5fa; font-size:20px;">
						<i class="fas fa-envelope"></i>
					</div>
					<div>
						<h3 style="margin:0; font-size:18px; font-weight:700; color:#ffffff;" x-text="selectedAccount + '@' + selectedDomain"></h3>
						<span style="font-size:12px; color:#94a3b8;">E-Posta Yönetimi, Şifre Değiştirme &amp; SMTP</span>
					</div>
				</div>
				<button type="button" x-on:click="modalOpen = false" style="background:none; border:none; color:#94a3b8; font-size:20px; cursor:pointer; padding:4px;" title="Kapat">
					<i class="fas fa-xmark"></i>
				</button>
			</div>

			<!-- Body -->
			<div style="padding:24px; max-height:75vh; overflow-y:auto;">
				
				<!-- Şifre Değiştirme Formu (Doğrudan Bu Ekranda) -->
				<form method="post" :action="'/edit/mail/?domain=' + encodeURIComponent(selectedDomain) + '&account=' + encodeURIComponent(selectedAccount)">
					<input type="hidden" name="token" :value="token">
					<input type="hidden" name="save" value="save">
					<input type="hidden" name="v_account" :value="selectedAccount">
					<input type="hidden" name="v_domain" :value="selectedDomain">

					<div style="background:#eff6ff; border:1.5px solid #bfdbfe; border-radius:12px; padding:16px; margin-bottom:20px;">
						<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
							<label style="font-weight:700; font-size:13px; color:#1e40af;">
								<i class="fas fa-key" style="color:#2563eb;"></i> Şifremi Unuttum / Yeni Şifre Belirle
							</label>
							<button type="button" x-on:click="generatePass()" style="background:#ffffff; border:1px solid #bfdbfe; border-radius:6px; padding:4px 10px; font-size:11px; font-weight:700; cursor:pointer; color:#2563eb;">
								<i class="fas fa-arrows-rotate"></i> Rastgele Şifre Üret
							</button>
						</div>
						<div style="display:flex; gap:8px;">
							<div style="position:relative; flex:1;">
								<input
									:type="showPassword ? 'text' : 'password'"
									name="v_password"
									x-model="newPassword"
									placeholder="Yeni şifrenizi buraya yazın..."
									class="form-control"
									style="width:100%; box-sizing:border-box; padding-right:36px; font-family:monospace; font-size:13px;"
									required
								>
								<button type="button" x-on:click="showPassword = !showPassword" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:none; border:none; color:#64748b; cursor:pointer;">
									<i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
								</button>
							</div>
							<button type="submit" class="button" style="background:#2563eb; color:#ffffff; border:none; padding:0 16px; font-weight:700; border-radius:8px; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
								<i class="fas fa-floppy-disk"></i> Şifreyi Kaydet
							</button>
						</div>
					</div>
				</form>

				<!-- Durum Rozeti -->
				<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:20px;">
					<div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:12px 14px; display:flex; align-items:center; gap:10px;">
						<i class="fas fa-circle-check" style="color:#16a34a; font-size:18px;"></i>
						<div>
							<strong style="font-size:13px; color:#15803d; display:block;">SMTP Sunucusu</strong>
							<span style="font-size:11px; color:#475569;">Port 587 (TLS) / 465 (SSL)</span>
						</div>
					</div>
					<div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:12px 14px; display:flex; align-items:center; gap:10px;">
						<i class="fas fa-circle-check" style="color:#16a34a; font-size:18px;"></i>
						<div>
							<strong style="font-size:13px; color:#15803d; display:block;">IMAP / POP3</strong>
							<span style="font-size:11px; color:#475569;">Port 993 (IMAP) / 995 (POP3)</span>
						</div>
					</div>
				</div>

				<!-- Parametre Tablosu -->
				<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; font-size:13px;">
					<div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:10px; border-bottom:1px solid #e2e8f0; margin-bottom:10px;">
						<span style="color:#64748b; font-weight:600;">SMTP Host (Sunucu):</span>
						<div style="display:flex; align-items:center; gap:8px;">
							<code style="background:#ffffff; padding:4px 8px; border-radius:6px; border:1px solid #cbd5e1; font-weight:700; color:#0f172a;" x-text="'mail.' + selectedDomain"></code>
							<button type="button" x-on:click="copyText('mail.' + selectedDomain)" style="background:#ffffff; border:1px solid #cbd5e1; border-radius:6px; padding:4px 8px; cursor:pointer;" title="Kopyala">
								<i class="fas fa-copy"></i>
							</button>
						</div>
					</div>

					<div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:10px; border-bottom:1px solid #e2e8f0; margin-bottom:10px;">
						<span style="color:#64748b; font-weight:600;">Kullanıcı Adı:</span>
						<div style="display:flex; align-items:center; gap:8px;">
							<code style="background:#ffffff; padding:4px 8px; border-radius:6px; border:1px solid #cbd5e1; font-weight:700; color:#0f172a;" x-text="selectedAccount + '@' + selectedDomain"></code>
							<button type="button" x-on:click="copyText(selectedAccount + '@' + selectedDomain)" style="background:#ffffff; border:1px solid #cbd5e1; border-radius:6px; padding:4px 8px; cursor:pointer;" title="Kopyala">
								<i class="fas fa-copy"></i>
							</button>
						</div>
					</div>

					<div style="display:flex; justify-content:space-between; align-items:center;">
						<span style="color:#64748b; font-weight:600;">Şifreleme &amp; Port:</span>
						<strong style="color:#0f172a;">STARTTLS (Port 587) / SSL (Port 465)</strong>
					</div>
				</div>

				<!-- Hızlı Kod Kopyalama (.env) -->
				<div>
					<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
						<label style="font-weight:700; font-size:13px; color:#1e293b;"><i class="fas fa-code"></i> Siteniz İçin .env / Config Satırları</label>
						<span x-show="copied" x-cloak style="font-size:12px; color:#16a34a; font-weight:700;"><i class="fas fa-check"></i> Kopyalandı!</span>
					</div>
					<div style="position:relative;">
						<textarea
							rows="4"
							readonly
							style="width:100%; font-family:monospace; font-size:11px; background:#1e293b; color:#93c5fd; padding:12px; border-radius:8px; border:none; resize:none; line-height:1.5; box-sizing:border-box;"
							x-text="'SMTP_HOST=mail.' + selectedDomain + '\nSMTP_PORT=587\nSMTP_USERNAME=' + selectedAccount + '@' + selectedDomain + '\nSMTP_SECURE=tls\nSMTP_FROM_EMAIL=' + selectedAccount + '@' + selectedDomain"
						></textarea>
						<button
							type="button"
							x-on:click="copyText('SMTP_HOST=mail.' + selectedDomain + '\nSMTP_PORT=587\nSMTP_USERNAME=' + selectedAccount + '@' + selectedDomain + '\nSMTP_SECURE=tls\nSMTP_FROM_EMAIL=' + selectedAccount + '@' + selectedDomain)"
							style="position:absolute; top:8px; right:8px; background:#3b82f6; color:#ffffff; border:none; border-radius:6px; padding:6px 12px; font-size:12px; font-weight:700; cursor:pointer;"
						>
							<i class="fas fa-copy"></i> Kopyala
						</button>
					</div>
				</div>

			</div>

			<!-- Footer -->
			<div style="background:#f8fafc; padding:16px 24px; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
				<a
					:href="'https://' + (webmailAlias || 'webmail') + '.' + selectedDomain + '/?_user=' + encodeURIComponent(selectedAccount + '@' + selectedDomain) + '&email=' + encodeURIComponent(selectedAccount + '@' + selectedDomain)"
					target="_blank"
					style="background:#d97706; color:#ffffff; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:8px;"
				>
					<i class="fas fa-envelope-open-text"></i> Webmail'e Git (Otomatik Kullanıcı Adı)
				</a>
				<button type="button" x-on:click="modalOpen = false" class="button button-secondary" style="padding:8px 18px;">
					Kapat
				</button>
			</div>
		</div>
	</div>

</div>


