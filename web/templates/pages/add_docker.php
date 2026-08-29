<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/docker/">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml(_("Back")) ?>
			</a>
		</div>
		<div class="toolbar-buttons">
			<button type="submit" class="button" form="main-form">
				<i class="fas fa-floppy-disk icon-purple"></i><?= tohtml(_("Kaydet")) ?>
			</button>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">

	<form id="main-form" name="v_add_docker" method="post">
		<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
		<input type="hidden" name="ok" value="Add">

		<div class="form-container">
			<h1 class="u-mb20">
				<i class="fab fa-docker icon-blue u-mr10"></i><?= tohtml(_("Docker Uygulaması Ekle")) ?>
			</h1>
			<?php show_alert_message($_SESSION); ?>

			<p class="u-mb20 text-muted">
				<?= tohtml(_("Tek repoda docker-compose bulunduran projeler için (ör. PostgreSQL + API + admin panel). Dil fark etmeksizin compose dosyasındaki her servis çalışır; yayınladığı portlar otomatik olarak yalnız 127.0.0.1'e bağlanır ve nginx üzerinden alan adlarıyla açılır.")) ?>
			</p>

			<div class="u-mb10">
				<label for="v_app" class="form-label"><?= tohtml(_("Uygulama adı")) ?></label>
				<input type="text" class="form-control" name="v_app" id="v_app" value="<?= tohtml($v_app) ?>" placeholder="mercanadisyon">
				<small class="hint"><?= tohtml(_("Küçük harf, rakam, - ve _ (en fazla 40 karakter). Compose projesi 'nexvia-<ad>' olarak kurulur.")) ?></small>
			</div>

			<div class="u-mb10">
				<label for="v_repo" class="form-label"><?= tohtml(_("Repository")) ?></label>
				<input type="text" class="form-control" name="v_repo" id="v_repo" value="<?= tohtml($v_repo) ?>" placeholder="https://github.com/kullanici/MercanAdisyon.git">
				<small class="hint"><?= tohtml(_("Tam git URL'si veya org/user/repo (GitHub token yapılandırılmışsa).")) ?></small>
			</div>

			<div class="u-mb10">
				<label for="v_branch" class="form-label"><?= tohtml(_("Branch")) ?></label>
				<input type="text" class="form-control" name="v_branch" id="v_branch" value="<?= tohtml($v_branch) ?>">
			</div>

			<div class="u-mb20">
				<label for="v_compose" class="form-label"><?= tohtml(_("Compose dosyası (opsiyonel)")) ?></label>
				<input type="text" class="form-control" name="v_compose" id="v_compose" value="<?= tohtml($v_compose) ?>" placeholder="docker-compose.yml">
				<small class="hint"><?= tohtml(_("Boş bırakılırsa otomatik bulunur: docker-compose.yml / docker-compose.yaml / compose.yml / compose.yaml")) ?></small>
			</div>

			<div class="u-mb20">
				<label for="v_deploy_cmd" class="form-label"><?= tohtml(_("Özel deploy komutu (opsiyonel)")) ?></label>
				<input type="text" class="form-control" name="v_deploy_cmd" id="v_deploy_cmd" value="<?= tohtml($v_deploy_cmd) ?>" placeholder="bash scripts/deploy-smart.sh">
				<small class="hint"><?= tohtml(_("Boşsa `docker compose up -d --build` kullanılır. Blue-green gibi kendi deploy script'iniz varsa yazın; repo dizininden çalışır, $NEXVIA_COMPOSE_FILES hazır compose dosya setini içerir.")) ?></small>
			</div>

			<div class="u-mb20">
				<label class="form-label">
					<input type="checkbox" name="v_force" value="1">
					<?= tohtml(_("Riskli compose yapılandırmalarını onaylıyorum")) ?>
				</label>
				<small class="hint"><?= tohtml(_("privileged, docker.sock mount, network_mode: host gibi yapılar içeriyorsa ve bilinçli kullanıyorsanız işaretleyin.")) ?></small>
			</div>
		</div>
	</form>
</div>
