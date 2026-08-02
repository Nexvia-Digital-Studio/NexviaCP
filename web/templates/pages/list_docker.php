<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/web/">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml( _("Back")) ?>
			</a>
		</div>
		<div class="toolbar-buttons">
			<a href="https://<?= $_SERVER['HTTP_HOST'] ?>:9000" target="_blank" class="button">
				<i class="fab fa-docker icon-blue"></i><?= tohtml( _("Open Portainer Full UI")) ?>
			</a>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">
	<div class="form-container u-width-full">
		<h1 class="u-mb20">
			<i class="fab fa-docker icon-blue u-mr10"></i><?= tohtml( _("Docker Container Manager (Portainer)")) ?>
		</h1>
		<p class="u-mb20 text-muted">
			<?= tohtml( _("Tüm Docker konteynerlerinizi canlı olarak izleyebilir, başlatıp durdurabilir ve kaynak tüketimlerini grafiklerle takip edebilirsiniz.")) ?>
		</p>

		<div class="alert alert-info u-mb20" role="alert">
			<i class="fas fa-circle-info u-mr5"></i>
			<span><strong><?= tohtml( _("Görsel Docker Yönetimi:")) ?></strong> <?= tohtml( _("Pencereden konteynerlerinize tek tıkla Start / Stop yapabilir ve RAM/CPU kullanımlarını canlı görebilirsiniz.")) ?></span>
		</div>

		<div class="docker-iframe-container" style="width:100%; height:750px; border:1px solid #ddd; border-radius:8px; overflow:hidden;">
			<iframe src="https://<?= $_SERVER['HTTP_HOST'] ?>:9000" style="width:100%; height:100%; border:none;"></iframe>
		</div>
	</div>
</div>
