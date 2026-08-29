<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/web/">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml(_("Back")) ?>
			</a>
		</div>
		<div class="toolbar-buttons">
			<?php if (!empty($portainer_domain)) { ?>
				<a href="https://<?= tohtml($portainer_domain) ?>/" target="_blank" rel="noopener" class="button button-secondary">
					<i class="fas fa-chart-simple icon-blue"></i><?= tohtml(_("Portainer")) ?>
				</a>
			<?php } ?>
			<a href="/add/docker/" class="button button-secondary js-button-create">
				<i class="fas fa-circle-plus icon-green"></i><?= tohtml(_("Uygulama Ekle")) ?>
			</a>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">

	<h1 class="u-text-center u-hide-desktop u-mt20 u-pr30 u-mb20 u-pl30">
		<i class="fab fa-docker icon-blue u-mr10"></i><?= tohtml(_("Docker Uygulamaları")) ?>
	</h1>

	<p class="u-mb20 text-muted">
		<?= tohtml(_("Tek repoluk docker-compose projeleri (ör. PostgreSQL + API + admin panel). Kod push'ından sonra GitHub Actions workflow'unuz panel API'sine v-update-docker-app çağrısı yapar; servislere alt alan adlarıyla /list/docker-app/ üzerinden alan açılır.")) ?>
	</p>

	<div class="units-table">
		<div class="units-table-header">
			<div class="units-table-cell"><?= tohtml(_("Uygulama")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml(_("Durum")) ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml(_("Servisler")) ?></div>
			<div class="units-table-cell"><?= tohtml(_("Domainler")) ?></div>
			<div class="units-table-cell"><?= tohtml(_("Repo")) ?></div>
			<div class="units-table-cell"></div>
		</div>

		<?php if (empty($data)) { ?>
			<div class="units-table-row">
				<div class="units-table-cell text-muted">
					<?= tohtml(_("Henüz docker uygulaması yok. 'Uygulama Ekle' ile compose repo'nuzu bağlayın.")) ?>
				</div>
			</div>
		<?php } ?>

		<!-- Begin docker app list item loop -->
		<?php foreach ($data as $app => $value) {
			$svc_total = count($value["services"] ?? []);
			$svc_running = 0;
			foreach (($value["services"] ?? []) as $svc) {
				if (($svc["state"] ?? "") === "running") {
					$svc_running++;
				}
			}
			$state = $value["state"] ?? "unknown";
			$state_labels = [
				"running" => ["label" => _("çalışıyor"), "class" => "label-success"],
				"deploying" => ["label" => _("kuruluyor…"), "class" => "label-info"],
				"failed" => ["label" => _("hata"), "class" => "label-danger"],
				"suspended" => ["label" => _("durduruldu"), "class" => "label-warning"],
				"new" => ["label" => _("yeni"), "class" => "label-default"],
			];
			$state_label = $state_labels[$state] ?? ["label" => $state, "class" => "label-default"];

			$domain_links = [];
			foreach (explode(" ", (string) ($value["domains"] ?? "")) as $entry) {
				if (strpos($entry, "@") === false) {
					continue;
				}
				$domain_links[] = explode(":", $entry)[0];
			}
		?>
			<div class="units-table-row js-unit">
				<div class="units-table-cell units-table-heading-cell u-text-bold">
					<span class="u-hide-desktop"><?= tohtml(_("Uygulama")) ?>:</span>
					<a href="/list/docker-app/?<?= tohtml(http_build_query(["app" => $app])) ?>" title="<?= tohtml(_("Detay")) ?>: <?= tohtml($app) ?>">
						<i class="fab fa-docker icon-blue u-mr5"></i><?= tohtml($app) ?>
					</a>
				</div>
				<div class="units-table-cell u-text-center-desktop">
					<span class="u-hide-desktop u-text-bold"><?= tohtml(_("Durum")) ?>:</span>
					<span class="label <?= tohtml($state_label["class"]) ?>"><?= tohtml($state_label["label"]) ?></span>
				</div>
				<div class="units-table-cell u-text-center-desktop">
					<span class="u-hide-desktop u-text-bold"><?= tohtml(_("Servisler")) ?>:</span>
					<?= sprintf("%d / %d", $svc_running, $svc_total) ?>
				</div>
				<div class="units-table-cell">
					<?php if (!empty($domain_links)) { ?>
						<?php foreach ($domain_links as $dl) { ?>
							<a href="http://<?= tohtml($dl) ?>" target="_blank" rel="noopener"><?= tohtml($dl) ?></a><br>
						<?php } ?>
					<?php } else { ?>
						<span class="text-muted">—</span>
					<?php } ?>
				</div>
				<div class="units-table-cell">
					<span class="u-hide-desktop u-text-bold"><?= tohtml(_("Repo")) ?>:</span>
					<?php
					$repo_short = preg_replace('~^https?://[^/]+/~', '', (string) ($value["repo"] ?? ""));
					$repo_short = preg_replace('~\.git$~', '', $repo_short);
					?>
					<?= tohtml($repo_short) ?>
					<span class="text-muted">(<?= tohtml($value["branch"] ?? "") ?>)</span>
				</div>
				<div class="units-table-cell">
					<ul class="units-table-row-actions">
						<li class="units-table-row-action" data-key-action="js">
							<form method="post" style="display:inline">
								<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
								<input type="hidden" name="app" value="<?= tohtml($app) ?>">
								<input type="hidden" name="action" value="update">
								<button type="submit" class="u-unstyled-button" title="<?= tohtml(_("Güncelle (git pull + rebuild)")) ?>">
									<i class="fas fa-arrows-rotate icon-blue"></i>
									<span class="u-hide-desktop"><?= tohtml(_("Güncelle")) ?></span>
								</button>
							</form>
						</li>
						<li class="units-table-row-action" data-key-action="js">
							<form method="post" style="display:inline">
								<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
								<input type="hidden" name="app" value="<?= tohtml($app) ?>">
								<input type="hidden" name="action" value="<?= $state === "suspended" ? "unsuspend" : "suspend" ?>">
								<button type="submit" class="u-unstyled-button" title="<?= $state === "suspended" ? tohtml(_("Başlat")) : tohtml(_("Durdur")) ?>">
									<i class="fas <?= $state === "suspended" ? "fa-play icon-green" : "fa-pause icon-highlight" ?>"></i>
									<span class="u-hide-desktop"><?= $state === "suspended" ? tohtml(_("Başlat")) : tohtml(_("Durdur")) ?></span>
								</button>
							</form>
						</li>
						<li class="units-table-row-action shortcut-delete" data-key-action="js">
							<a
								class="units-table-row-action-link data-controls js-confirm-action"
								href="/delete/docker/?<?= tohtml(http_build_query(["app" => $app, "token" => $_SESSION["token"]])) ?>"
								title="<?= tohtml(_("Sil")) ?>"
								data-confirm-title="<?= tohtml(_("Sil")) ?>"
								data-confirm-message="<?= tohtml(sprintf(_("'%s' uygulaması ve bağlı domainleri silinsin mi? (Docker volume'leri korunur)"), $app)) ?>"
							>
								<i class="fas fa-trash icon-red"></i>
								<span class="u-hide-desktop"><?= tohtml(_("Sil")) ?></span>
							</a>
						</li>
					</ul>
				</div>
			</div>
		<?php } ?>
	</div>

	<div class="units-table-footer">
		<p><?php printf(ngettext("%d docker uygulaması", "%d docker uygulaması", count($data)), count($data)); ?></p>
	</div>

</div>
