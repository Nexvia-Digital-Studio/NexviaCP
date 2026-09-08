<?php
// Demo sites list — data comes from v-list-demo-sites (see /list/demo/
// handler). $data is keyed by slug; each item has OWNER, SLUG, NAME, REPO,
// BRANCH, SUBDIR, COMMIT, CREATED, UPDATED, STATE, SIZE_MB, URL_PATH.
$v_is_admin = ($_SESSION["userContext"] ?? "") === "admin";
$v_host = $_SERVER["HTTP_HOST"] ?? "";
$v_rows = array_values($data ?? []);
usort($v_rows, fn($a, $b) => strcmp($b["UPDATED"] ?? "", $a["UPDATED"] ?? ""));
?>
<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/web/">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml(_("Back")) ?>
			</a>
		</div>
		<div class="toolbar-buttons">
			<button type="button" class="button button-secondary" onclick="document.getElementById('demo-add-modal').style.display='flex'">
				<i class="fas fa-plus icon-green"></i><?= tohtml(__tr("Publish New Demo", "Yeni Demo Yayınla")) ?>
			</button>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">
	<div class="form-container u-width-full">

		<h1 class="u-mb10">
			<i class="fas fa-flask icon-teal u-mr10"></i><?= tohtml(__tr("Demo Sites", "Demo Siteler")) ?>
			<span class="label label-default u-ml10"><?= count($v_rows) ?></span>
		</h1>
		<?php show_alert_message($_SESSION); ?>

		<p class="u-mb20 text-muted">
			<?= tohtml(__tr(
				"GitHub depolarındaki HTML/PHP siteleri, panel alan adı altında rastgele kodlu gizli URL'lerle DEMO olarak yayınlar (örn. /onlymutfak-a1b2b…c3d/). Bağlantıyı bilen herkes demoya bakabilir; URL'yi bilmeyen bulamaz. Repoya push attığınızda demo otomatik güncellenir.",
				"GitHub depolarındaki HTML/PHP siteleri, panel alan adı altında rastgele kodlu gizli URL'lerle DEMO olarak yayınlar (örn. /onlymutfak-a1b2b…c3d/). Bağlantıyı bilen herkes demoya bakabilir; URL'yi bilmeyen bulamaz. Repoya push attığınızda demo otomatik güncellenir.",
			)) ?>
		</p>

		<div class="units-table u-mb20">
			<div class="units-table-header">
				<div class="units-table-cell"><?= tohtml(__tr("Demo", "Demo")) ?></div>
				<div class="units-table-cell"><?= tohtml(__tr("URL", "URL")) ?></div>
				<div class="units-table-cell"><?= tohtml(__tr("Repo", "Repo")) ?></div>
				<div class="units-table-cell"><?= tohtml(__tr("Güncelleme", "Güncelleme")) ?></div>
				<div class="units-table-cell"><?= tohtml(__tr("Boyut", "Boyut")) ?></div>
				<?php if ($v_is_admin) { ?>
					<div class="units-table-cell"><?= tohtml(__tr("Kullanıcı", "Kullanıcı")) ?></div>
				<?php } ?>
				<div class="units-table-cell"></div>
			</div>

			<?php if (empty($v_rows)) { ?>
				<div class="units-table-row">
					<div class="units-table-cell text-muted">
						<?= tohtml(__tr("Henüz demo sitesi yok. «Yeni Demo Yayınla» ile bir GitHub reposunu saniyeler içinde demoya çevirin.", "Henüz demo sitesi yok. «Yeni Demo Yayınla» ile bir GitHub reposunu saniyeler içinde demoya çevirin.")) ?>
					</div>
				</div>
			<?php } ?>

			<?php foreach ($v_rows as $r) {
				$v_slug = $r["SLUG"] ?? "";
				$v_url = "https://" . $v_host . "/" . $v_slug . "/";
			?>
				<div class="units-table-row js-unit">
					<div class="units-table-cell units-table-heading-cell u-text-bold">
						<span class="u-hide-desktop"><?= tohtml(__tr("Demo", "Demo")) ?>:</span>
						<?= tohtml($r["NAME"] ?? $v_slug) ?>
						<span class="text-muted" style="font-weight:400; font-size:0.85em;">–<?= tohtml($v_slug) ?></span>
					</div>
					<div class="units-table-cell">
						<a href="<?= tohtml($v_url) ?>" target="_blank" rel="noopener"><?= tohtml($v_url) ?></a>
					</div>
					<div class="units-table-cell">
						<?= tohtml(($r["REPO"] ?? "") . "@" . ($r["BRANCH"] ?? "")) ?>
						<?php if (!empty($r["SUBDIR"])) { ?>
							<span class="label label-default">/<?= tohtml($r["SUBDIR"]) ?></span>
						<?php } ?>
					</div>
					<div class="units-table-cell">
						<span class="u-hide-desktop"><?= tohtml(__tr("Güncelleme", "Güncelleme")) ?>:</span>
						<?= tohtml($r["UPDATED"] ?? "") ?>
						<?php if (!empty($r["COMMIT"])) { ?>
							<span class="text-muted">(<?= tohtml($r["COMMIT"]) ?>)</span>
						<?php } ?>
					</div>
					<div class="units-table-cell">
						<span class="u-hide-desktop"><?= tohtml(__tr("Boyut", "Boyut")) ?>:</span>
						<?= tohtml(($r["SIZE_MB"] ?? "0")) ?> MiB
					</div>
					<?php if ($v_is_admin) { ?>
						<div class="units-table-cell">
							<span class="u-hide-desktop"><?= tohtml(__tr("Kullanıcı", "Kullanıcı")) ?>:</span>
							<?= tohtml($r["OWNER"] ?? "") ?>
						</div>
					<?php } ?>
					<div class="units-table-cell">
						<ul class="units-table-row-actions">
							<li class="units-table-row-action">
								<button type="button" class="u-unstyled-button js-demo-copy" data-url="<?= tohtml($v_url) ?>" title="<?= tohtml(__tr("URL'yi kopyala", "URL'yi kopyala")) ?>">
									<i class="fas fa-copy icon-teal"></i>
									<span class="u-hide-desktop"><?= tohtml(__tr("URL'yi kopyala", "URL'yi kopyala")) ?></span>
								</button>
							</li>
							<li class="units-table-row-action">
								<form method="post" style="display:inline">
									<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
									<input type="hidden" name="slug" value="<?= tohtml($v_slug) ?>">
									<input type="hidden" name="action" value="update">
									<button type="submit" class="u-unstyled-button" title="<?= tohtml(__tr("Repodan güncelle", "Repodan güncelle")) ?>">
										<i class="fas fa-arrows-rotate icon-blue"></i>
										<span class="u-hide-desktop"><?= tohtml(__tr("Repodan güncelle", "Repodan güncelle")) ?></span>
									</button>
								</form>
							</li>
							<li class="units-table-row-action">
								<form method="post" style="display:inline" onsubmit="return confirm('<?= tohtml(sprintf(__tr("'%s' demosu silinsin mi? (GitHub deposu etkilenmez)", "'%s' demosu silinsin mi? (GitHub deposu etkilenmez)"), $r["NAME"] ?? $v_slug)) ?>');">
									<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
									<input type="hidden" name="slug" value="<?= tohtml($v_slug) ?>">
									<input type="hidden" name="action" value="delete">
									<button type="submit" class="u-unstyled-button" title="<?= tohtml(__tr("Sil", "Sil")) ?>">
										<i class="fas fa-trash icon-red"></i>
										<span class="u-hide-desktop"><?= tohtml(__tr("Sil", "Sil")) ?></span>
									</button>
								</form>
							</li>
						</ul>
					</div>
				</div>
			<?php } ?>
		</div>

	</div>
</div>

<!-- Modal: Publish New Demo -->
<div id="demo-add-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.65); z-index:9999; justify-content:center; align-items:center;" onclick="if(event.target===this) this.style.display='none';">
	<div class="form-container" style="background:var(--color-background, #fff); max-width:580px; width:92%; max-height:92vh; overflow-y:auto; border-radius:8px; padding:25px 30px; box-shadow:0 10px 30px rgba(0,0,0,0.5);">
		<h2 class="u-mb15"><i class="fas fa-flask icon-teal"></i> <?= tohtml(__tr("Yeni Demo Yayınla", "Yeni Demo Yayınla")) ?></h2>
		<p class="u-text-muted u-mb20" style="font-size:0.9rem; line-height:1.4;">
			<?= tohtml(__tr(
				"Düz HTML veya PHP siteler (kurulum gerektirmeyen) saniyeler içinde gizli bir demo URL'sinde yayınlanır. PHP demoları veritabanısız çalışır — sayfalar arası gezinti ve statik işlevler yeterlidir.",
				"Düz HTML veya PHP siteler (kurulum gerektirmeyen) saniyeler içinde gizli bir demo URL'sinde yayınlanır. PHP demoları veritabanısız çalışır — sayfalar arası gezinti ve statik işlevler yeterlidir.",
			)) ?>
		</p>

		<form method="post" action="/list/demo/" onsubmit="const b=this.querySelector('button[type=submit]'); b.disabled=true; b.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> <?= tohtml(__tr('Yayınlanıyor…', 'Yayınlanıyor…')) ?>';">
			<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
			<input type="hidden" name="action" value="add">

			<?php if ($v_is_admin && !empty($github_repos) && !isset($github_repos["error"])) { ?>
				<div class="u-mb15">
					<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("GitHub Repository", "GitHub Deposu (Repo)")) ?></label>
					<select name="demo_repo_name" id="demo-repo-select" class="form-select" style="width:100%;">
						<option value="__custom__">🌍 <?= tohtml(__tr("İstediğim GitHub reposu — linkini gireceğim…", "İstediğim GitHub reposu — linkini gireceğim…")) ?></option>
						<?php foreach ($github_repos as $rname => $rdata) { ?>
							<option value="<?= tohtml($rname) ?>">
								<?= tohtml($rdata["NAME"] ?? $rname) ?> (<?= tohtml($rdata["LANGUAGE"] ?? "Web") ?>)<?= ($rdata["PRIVATE"] ?? "") === "yes" ? " 🔒" : "" ?>
							</option>
						<?php } ?>
					</select>
				</div>
				<div class="u-mb15" id="demo-repo-url-wrap" style="display:none;">
			<?php } else { ?>
				<div class="u-mb15" id="demo-repo-url-wrap">
			<?php } ?>
				<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("GitHub Repo Linki", "GitHub Repo Linki")) ?></label>
				<input type="text" name="demo_repo_url" id="demo-repo-url" placeholder="https://github.com/kullanici/proje" class="form-control" style="width:100%;">
				<small class="u-text-muted" style="display:block; margin-top:4px;">
					💡 <?= tohtml(__tr("/tree/dal linki yapıştırırsanız dal da otomatik seçilir.", "/tree/dal linki yapıştırırsanız dal da otomatik seçilir.")) ?>
				</small>
			</div>

			<div class="u-mb15" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
				<div>
					<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("Dal (opsiyonel)", "Dal (opsiyonel)")) ?></label>
					<input type="text" name="demo_branch" placeholder="main" class="form-control" style="width:100%;">
				</div>
				<div>
					<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("Demo adı (opsiyonel)", "Demo adı (opsiyonel)")) ?></label>
					<input type="text" name="demo_name" placeholder="onlymutfak" class="form-control" style="width:100%;">
				</div>
			</div>

			<div class="u-mb15">
				<label class="form-label u-mb5 u-text-bold"><?= tohtml(__tr("Alt klasör (opsiyonel, gelişmiş)", "Alt klasör (opsiyonel, gelişmiş)")) ?></label>
				<input type="text" name="demo_subdir" placeholder="web" class="form-control" style="width:100%;">
				<small class="u-text-muted" style="display:block; margin-top:4px;">
					<?= tohtml(__tr("Sitenin repo kökünde değil de bir klasörün içinde olduğu durumlarda o klasörü yazın (örn. web, dist).", "Sitenin repo kökünde değil de bir klasörün içinde olduğu durumlarda o klasörü yazın (örn. web, dist).")) ?>
				</small>
			</div>

			<div class="u-mt20" style="display:flex; justify-content:flex-end; gap:10px;">
				<button type="button" class="button button-secondary" onclick="document.getElementById('demo-add-modal').style.display='none'">
					<?= tohtml(__tr("İptal", "İptal")) ?>
				</button>
				<button type="submit" class="button button-primary">
					<i class="fas fa-rocket"></i> <?= tohtml(__tr("Demoyu Yayınla", "Demoyu Yayınla")) ?>
				</button>
			</div>
		</form>
	</div>
</div>

<script>
document.addEventListener('keydown', function(e) {
	if (e.key === 'Escape') {
		const m = document.getElementById('demo-add-modal');
		if (m) m.style.display = 'none';
	}
});
(function() {
	const sel = document.getElementById('demo-repo-select');
	const wrap = document.getElementById('demo-repo-url-wrap');
	if (!sel || !wrap) return;
	sel.addEventListener('change', function() {
		wrap.style.display = this.value === '__custom__' ? 'block' : 'none';
		const url = document.getElementById('demo-repo-url');
		if (url) url.required = (this.value === '__custom__');
	});
})();
document.querySelectorAll('.js-demo-copy').forEach(function(btn) {
	btn.addEventListener('click', function() {
		const url = this.dataset.url;
		const done = function() {
			const old = btn.title;
			btn.title = '<?= tohtml(__tr("Kopyalandı!", "Kopyalandı!")) ?>';
			setTimeout(function() { btn.title = old; }, 1500);
		};
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(url).then(done);
		} else {
			const ta = document.createElement('textarea');
			ta.value = url;
			document.body.appendChild(ta);
			ta.select();
			document.execCommand('copy');
			document.body.removeChild(ta);
			done();
		}
	});
});
</script>
