<?php
$v_app_name = $data["_name"] ?? $_GET["app"] ?? "";
$v_state = $data["state"] ?? "unknown";
$state_labels = [
	"running" => ["label" => _("çalışıyor"), "class" => "label-success"],
	"deploying" => ["label" => _("kuruluyor…"), "class" => "label-info"],
	"failed" => ["label" => _("hata"), "class" => "label-danger"],
	"suspended" => ["label" => _("durduruldu"), "class" => "label-warning"],
];
$v_state_label = $state_labels[$v_state] ?? ["label" => $v_state, "class" => "label-default"];

// Parse MAPPINGS ("svc:target:host ...") into structured rows.
$v_mappings = [];
foreach (explode(" ", (string) ($data["mappings"] ?? "")) as $m) {
	if (substr_count($m, ":") === 2) {
		[$svc, $target, $host] = explode(":", $m);
		$v_mappings[] = ["service" => $svc, "target" => $target, "host" => $host];
	}
}

// Parse DOMAINS ("user@domain:svc:host ...").
$v_domains = [];
foreach (explode(" ", (string) ($data["domains"] ?? "")) as $e) {
	if (substr_count($e, ":") === 2 && strpos($e, "@") !== false) {
		[$ud, $svc, $host] = explode(":", $e);
		$v_domains[] = [
			"user" => explode("@", $ud)[0],
			"domain" => explode("@", $ud)[1] ?? "",
			"service" => $svc,
			"host" => $host,
		];
	}
}

// Index live service states by name.
$v_service_states = [];
foreach (($data["services"] ?? []) as $svc) {
	$v_service_states[$svc["name"]] = $svc;
}
?>
<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/docker/">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml(_("Back")) ?>
			</a>
		</div>
		<div class="toolbar-buttons">
			<form method="post" style="display:inline">
				<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
				<input type="hidden" name="app" value="<?= tohtml($v_app_name) ?>">
				<input type="hidden" name="action" value="update">
				<button type="submit" class="button button-secondary">
					<i class="fas fa-arrows-rotate icon-blue"></i><?= tohtml(_("Güncelle")) ?>
				</button>
			</form>
			<a
				class="button button-danger js-confirm-action"
				href="/delete/docker/?<?= tohtml(http_build_query(["app" => $v_app_name, "token" => $_SESSION["token"]])) ?>"
				data-confirm-title="<?= tohtml(_("Sil")) ?>"
				data-confirm-message="<?= tohtml(sprintf(_("'%s' uygulaması ve bağlı domainleri silinsin mi? (Docker volume'leri korunur)"), $v_app_name)) ?>"
			>
				<i class="fas fa-trash icon-red"></i><?= tohtml(_("Sil")) ?>
			</a>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">
	<div class="form-container u-width-full">

		<h1 class="u-mb10">
			<i class="fab fa-docker icon-blue u-mr10"></i><?= tohtml($v_app_name) ?>
			<span class="label <?= tohtml($v_state_label["class"]) ?> u-ml10"><?= tohtml($v_state_label["label"]) ?></span>
		</h1>
		<?php show_alert_message($_SESSION); ?>

		<p class="u-mb20 text-muted">
			<?= tohtml($data["repo"] ?? "") ?>
			(<strong><?= tohtml($data["branch"] ?? "") ?></strong>)
			&middot; compose: <code><?= tohtml($data["compose_file"] ?? "") ?></code>
			<?php if (!empty($data["update_time"])) { ?>
				&middot; son deploy: <?= tohtml(date("Y-m-d H:i", (int) $data["update_time"])) ?>
			<?php } ?>
			<br>
			<a href="/list/docker-app/?<?= tohtml(http_build_query(["app" => $v_app_name, "logs" => "_deploy"])) ?>"><?= tohtml(_("Kurulum/güncelleme kaydını gör")) ?></a>
		</p>

		<?php if ($v_logs ?? "") { ?>
			<div class="u-mb20">
				<h2 class="u-mb10">
					<?= $v_logs === "_deploy" ? tohtml(_("Deploy kaydı")) : tohtml(sprintf(_("%s servisi logları"), $v_logs)) ?>
					<a class="u-ml10 button button-secondary" href="/list/docker-app/?<?= tohtml(http_build_query(["app" => $v_app_name])) ?>"><?= tohtml(_("Kapat")) ?></a>
				</h2>
				<pre class="u-p10" style="max-height:480px;overflow:auto;background:#0b1021;color:#cfe3ff;border-radius:8px;font-size:12px;"><?= tohtml($v_log_output) ?></pre>
			</div>
		<?php } ?>

		<!-- ===================== Services ===================== -->
		<h2 class="u-mb10"><?= tohtml(_("Servisler")) ?></h2>
		<div class="units-table u-mb20">
			<div class="units-table-header">
				<div class="units-table-cell"><?= tohtml(_("Servis")) ?></div>
				<div class="units-table-cell u-text-center"><?= tohtml(_("Durum")) ?></div>
				<div class="units-table-cell"><?= tohtml(_("Port (127.0.0.1)")) ?></div>
				<div class="units-table-cell"></div>
			</div>
			<?php foreach (($data["services"] ?? []) as $svc) {
				$svc_state = $svc["state"] ?? "unknown";
				$svc_running = $svc_state === "running";
			?>
				<div class="units-table-row js-unit">
					<div class="units-table-cell units-table-heading-cell u-text-bold">
						<span class="u-hide-desktop"><?= tohtml(_("Servis")) ?>:</span>
						<?= tohtml($svc["name"]) ?>
					</div>
					<div class="units-table-cell u-text-center-desktop">
						<span class="label <?= $svc_running ? "label-success" : ($svc_state === "exited" ? "label-warning" : "label-default") ?>">
							<?= tohtml($svc_state) ?>
						</span>
					</div>
					<div class="units-table-cell">
						<span class="u-hide-desktop u-text-bold"><?= tohtml(_("Port")) ?>:</span>
						<?php
						$host_ports = [];
						foreach ($v_mappings as $m) {
							if ($m["service"] === $svc["name"]) {
								$host_ports[] = $m["host"] . " → :" . $m["target"];
							}
						}
						?>
						<?= tohtml($host_ports ? implode(", ", $host_ports) : "—") ?>
					</div>
					<div class="units-table-cell">
						<ul class="units-table-row-actions">
							<li class="units-table-row-action">
								<a href="/list/docker-app/?<?= tohtml(http_build_query(["app" => $v_app_name, "logs" => $svc["name"]])) ?>" title="<?= tohtml(_("Loglar")) ?>">
									<i class="fas fa-file-lines icon-blue"></i>
									<span class="u-hide-desktop"><?= tohtml(_("Loglar")) ?></span>
								</a>
							</li>
							<li class="units-table-row-action">
								<form method="post" style="display:inline">
									<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
									<input type="hidden" name="app" value="<?= tohtml($v_app_name) ?>">
									<input type="hidden" name="service" value="<?= tohtml($svc["name"]) ?>">
									<input type="hidden" name="action" value="restart-service">
									<button type="submit" class="u-unstyled-button" title="<?= tohtml(_("Yeniden başlat")) ?>">
										<i class="fas fa-rotate icon-orange"></i>
										<span class="u-hide-desktop"><?= tohtml(_("Yeniden başlat")) ?></span>
									</button>
								</form>
							</li>
						</ul>
					</div>
				</div>
			<?php } ?>
		</div>

		<!-- ===================== Domains ===================== -->
		<h2 class="u-mb10"><?= tohtml(_("Domainler")) ?></h2>
		<div class="units-table u-mb10">
			<div class="units-table-header">
				<div class="units-table-cell"><?= tohtml(_("Domain")) ?></div>
				<div class="units-table-cell"><?= tohtml(_("Kullanıcı")) ?></div>
				<div class="units-table-cell"><?= tohtml(_("Servis")) ?></div>
				<div class="units-table-cell"></div>
			</div>
			<?php if (empty($v_domains)) { ?>
				<div class="units-table-row">
					<div class="units-table-cell text-muted">
						<?= tohtml(_("Henüz alan adı bağlı değil. Servisi yayına açmak için aşağıdaki formu kullanın (DNS kaydının bu sunucuyu gösterdiğinden emin olun).")) ?>
					</div>
				</div>
			<?php } ?>
			<?php foreach ($v_domains as $d) { ?>
				<div class="units-table-row js-unit">
					<div class="units-table-cell units-table-heading-cell u-text-bold">
						<a href="http://<?= tohtml($d["domain"]) ?>" target="_blank" rel="noopener"><?= tohtml($d["domain"]) ?></a>
					</div>
					<div class="units-table-cell"><?= tohtml($d["user"]) ?></div>
					<div class="units-table-cell"><?= tohtml($d["service"]) ?> <span class="text-muted">(127.0.0.1:<?= tohtml($d["host"]) ?>)</span></div>
					<div class="units-table-cell">
						<ul class="units-table-row-actions">
							<li class="units-table-row-action">
								<form method="post" style="display:inline">
									<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
									<input type="hidden" name="app" value="<?= tohtml($v_app_name) ?>">
									<input type="hidden" name="v_domain" value="<?= tohtml($d["domain"]) ?>">
									<input type="hidden" name="action" value="delete-domain">
									<button type="submit" class="u-unstyled-button" title="<?= tohtml(_("Domaini kaldır")) ?>">
										<i class="fas fa-trash icon-red"></i>
										<span class="u-hide-desktop"><?= tohtml(_("Domaini kaldır")) ?></span>
									</button>
								</form>
							</li>
						</ul>
					</div>
				</div>
			<?php } ?>
		</div>

		<form class="u-mb20" method="post">
			<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
			<input type="hidden" name="app" value="<?= tohtml($v_app_name) ?>">
			<input type="hidden" name="action" value="add-domain">
			<div class="u-side-by-side u-mb10">
				<div class="u-mb10 u-mr10" style="flex:2">
					<label for="v_domain" class="form-label"><?= tohtml(_("Domain")) ?></label>
					<input type="text" class="form-control" name="v_domain" id="v_domain" placeholder="api.ornek.com">
				</div>
				<div class="u-mb10 u-mr10" style="flex:1">
					<label for="v_service" class="form-label"><?= tohtml(_("Servis")) ?></label>
					<select class="form-select" name="v_service" id="v_service">
						<?php foreach ($v_mappings as $m) { ?>
							<option value="<?= tohtml($m["service"] . ":" . $m["target"]) ?>">
								<?= tohtml($m["service"] . " (:" . $m["target"] . ")") ?>
							</option>
						<?php } ?>
					</select>
				</div>
				<div class="u-mb10 u-mr10" style="flex:1">
					<label for="v_domain_user" class="form-label"><?= tohtml(_("Kullanıcı")) ?></label>
					<select class="form-select" name="v_domain_user" id="v_domain_user">
						<?php
						$domain_users = array_keys($users);
						usort($domain_users, fn($a, $b) => ($b === "admin") <=> ($a === "admin"));
						foreach ($domain_users as $u) { ?>
							<option value="<?= tohtml($u) ?>"><?= tohtml($u) ?></option>
						<?php } ?>
					</select>
				</div>
			</div>
			<label class="form-label u-mb10">
				<input type="checkbox" name="v_ssl" value="1" checked>
				<?= tohtml(_("Let's Encrypt SSL kur")) ?>
			</label>
			<button type="submit" class="button">
				<i class="fas fa-globe icon-green"></i><?= tohtml(_("Domaini bağla")) ?>
			</button>
		</form>

		<!-- ===================== .env ===================== -->
		<h2 class="u-mb10"><?= tohtml(_(".env (compose değişkenleri)")) ?></h2>
		<p class="text-muted u-mb10">
			<?= tohtml(_("Compose dosyasındaki ${DEGISKEN} yerine koymaları buradan yönetilir (ör. DB şifreleri). Kaydettikten sonra 'Güncelle' ile yeniden dağıtın.")) ?>
		</p>
		<form class="u-mb20" method="post">
			<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
			<input type="hidden" name="app" value="<?= tohtml($v_app_name) ?>">
			<input type="hidden" name="action" value="save-env">
			<textarea class="form-control u-mb10" name="v_env" rows="10" style="font-family:monospace"><?= tohtml($v_env) ?></textarea>
			<button type="submit" class="button button-secondary">
				<i class="fas fa-floppy-disk icon-purple"></i><?= tohtml(_(".env kaydet")) ?>
			</button>
		</form>

		<!-- ===================== Danger zone ===================== -->
		<h2 class="u-mb10 u-text-danger"><?= tohtml(_("Tehlikeli alan")) ?></h2>
		<form method="post" onsubmit="return confirm('<?= tohtml(sprintf(_("'%s' uygulaması, volume'leri DAHİL silinsin mi? Veritabanı verileri geri getirilemez!"), $v_app_name)) ?>');">
			<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
			<input type="hidden" name="app" value="<?= tohtml($v_app_name) ?>">
			<input type="hidden" name="action" value="delete-volumes">
			<button type="submit" class="button button-danger">
				<i class="fas fa-bomb icon-red"></i><?= tohtml(_("Volume'lerle birlikte sil")) ?>
			</button>
		</form>

	</div>
</div>
