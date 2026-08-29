<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a href="/list/server/" class="button button-secondary">
				<i class="fas fa-arrow-left icon-maroon"></i><?= tohtml( _("Back to Server")) ?>
			</a>
			<a href="/edit/template/?category=web/nginx&name=custom-app&new=1" class="button button-primary">
				<i class="fas fa-plus icon-green"></i><?= tohtml( _("Yeni Şablon Ekle")) ?>
			</a>
		</div>
		<div class="toolbar-right">
			<div class="toolbar-search">
				<input type="search" class="form-control js-search-input" placeholder="<?= tohtml( _("Şablon ara...")) ?>" id="tplSearch">
			</div>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">
	<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
		<div>
			<h1 class="u-mb5" style="display: flex; align-items: center; gap: 10px;">
				<i class="fas fa-layer-group icon-purple"></i>
				<?= tohtml( _("Web, Proxy & Runtime Şablon Yöneticisi")) ?>
			</h1>
			<p class="u-text-muted" style="margin: 0; font-size: 14px;">
				Node.js, .NET, WebSocket, Docker UI, PHP-FPM ve Nginx sanal host şablonlarını doğrudan panelden görüntüleyin ve düzenleyin.
			</p>
		</div>
		<div>
			<span class="badge badge-info" style="font-size: 13px; padding: 6px 12px;">
				Toplam <?= count($templates_data) ?> Şablon
			</span>
		</div>
	</div>

	<!-- Category Filter Pills -->
	<div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;" id="categoryFilter">
		<button type="button" class="button button-secondary active js-filter-btn" data-filter="all" onclick="filterCategory('all', this)" style="border-radius: 20px; padding: 6px 16px; font-weight: 600;">
			<i class="fas fa-list u-mr5"></i>Tümü (<?= count($templates_data) ?>)
		</button>
		<button type="button" class="button button-secondary js-filter-btn" data-filter="web/nginx" onclick="filterCategory('web/nginx', this)" style="border-radius: 20px; padding: 6px 16px;">
			<i class="fab fa-cloudflare icon-orange u-mr5"></i>Nginx Proxy &amp; App Presets
		</button>
		<button type="button" class="button button-secondary js-filter-btn" data-filter="web/php-fpm" onclick="filterCategory('web/php-fpm', this)" style="border-radius: 20px; padding: 6px 16px;">
			<i class="fab fa-php icon-blue u-mr5"></i>PHP-FPM Backend Pools
		</button>
	</div>

	<!-- Templates Grid -->
	<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px;" id="templatesGrid">
		<?php foreach ($templates_data as $tpl) { 
			$cat = $tpl["category"] ?? "";
			$tname = $tpl["name"] ?? "";
			$desc = $tpl["description"] ?? "";
			$port = $tpl["port"] ?? "";
			$has_tpl = !empty($tpl["has_tpl"]);
			$has_stpl = !empty($tpl["has_stpl"]);
			$has_sh = !empty($tpl["has_sh"]);

			// Choose icon & badge colors
			$icon = "fa-file-code";
			$icon_color = "#64748b";
			$accent_bg = "#f8fafc";
			$badge_tag = "Nginx VirtualHost";

			if ($cat === "web/php-fpm") {
				$icon = "fa-php fab";
				$icon_color = "#8b5cf6";
				$accent_bg = "#faf5ff";
				$badge_tag = "PHP-FPM Pool";
			} elseif ($tname === "node-js") {
				$icon = "fa-node-js fab";
				$icon_color = "#10b981";
				$accent_bg = "#ecfdf5";
				$badge_tag = "Node.js Runtime";
			} elseif ($tname === "dotnet") {
				$icon = "fa-microsoft fab";
				$icon_color = "#0284c7";
				$accent_bg = "#f0f9ff";
				$badge_tag = ".NET Core Runtime";
			} elseif ($tname === "websocket") {
				$icon = "fa-bolt fas";
				$icon_color = "#f59e0b";
				$accent_bg = "#fffbeb";
				$badge_tag = "WebSocket Proxy";
			} elseif ($tname === "docker-ui") {
				$icon = "fa-docker fab";
				$icon_color = "#0284c7";
				$accent_bg = "#f0f9ff";
				$badge_tag = "Docker UI Proxy";
			} elseif ($tname === "caching") {
				$icon = "fa-gauge-high fas";
				$icon_color = "#10b981";
				$accent_bg = "#ecfdf5";
				$badge_tag = "FastCGI Cache";
			} elseif ($tname === "hosting") {
				$icon = "fa-server fas";
				$icon_color = "#6366f1";
				$accent_bg = "#eef2ff";
				$badge_tag = "Shared Hosting";
			}
		?>
		<div class="card tpl-card" data-category="<?= tohtml($cat) ?>" data-name="<?= tohtml(strtolower($tname . ' ' . $desc)) ?>" style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background: #fff; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: transform 0.15s ease, box-shadow 0.15s ease;">
			<div style="padding: 18px;">
				<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
					<div style="display: flex; align-items: center; gap: 12px;">
						<div style="width: 42px; height: 42px; border-radius: 8px; background: <?= $accent_bg ?>; display: flex; align-items: center; justify-content: center; font-size: 22px; color: <?= $icon_color ?>; border: 1px solid rgba(0,0,0,0.05);">
							<i class="<?= $icon ?>"></i>
						</div>
						<div>
							<h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #1e293b;">
								<?= tohtml($tname) ?>
							</h3>
							<span style="font-size: 11px; font-weight: 600; color: #64748b; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">
								<?= $badge_tag ?>
							</span>
						</div>
					</div>
					<?php if (!empty($port)) { ?>
						<span class="badge" style="background: #e0f2fe; color: #0369a1; font-family: monospace; font-size: 12px; padding: 3px 8px; border-radius: 4px; font-weight: 600;">
							:<?= tohtml($port) ?>
						</span>
					<?php } ?>
				</div>

				<p style="margin: 0 0 14px 0; font-size: 13px; color: #475569; line-height: 1.4; min-height: 36px;">
					<?= tohtml($desc) ?>
				</p>

				<div style="display: flex; gap: 6px; flex-wrap: wrap;">
					<?php if ($has_tpl) { ?>
						<span style="font-size: 11px; font-weight: 600; color: #0284c7; background: #f0f9ff; border: 1px solid #bae6fd; padding: 2px 8px; border-radius: 4px;">
							<i class="fas fa-check u-mr5"></i>.tpl (HTTP)
						</span>
					<?php } ?>
					<?php if ($has_stpl) { ?>
						<span style="font-size: 11px; font-weight: 600; color: #059669; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 2px 8px; border-radius: 4px;">
							<i class="fas fa-lock u-mr5"></i>.stpl (SSL)
						</span>
					<?php } ?>
					<?php if ($has_sh) { ?>
						<span style="font-size: 11px; font-weight: 600; color: #d97706; background: #fffbeb; border: 1px solid #fde68a; padding: 2px 8px; border-radius: 4px;">
							<i class="fas fa-terminal u-mr5"></i>.sh (Hook)
						</span>
					<?php } ?>
				</div>
			</div>

			<div style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 12px 18px; display: flex; justify-content: space-between; align-items: center;">
				<code style="font-size: 11px; color: #64748b; font-family: monospace;">
					templates/<?= tohtml($cat) ?>/<?= tohtml($tname) ?>
				</code>
				<a href="/edit/template/?category=<?= urlencode($cat) ?>&name=<?= urlencode($tname) ?>" class="button button-secondary" style="padding: 4px 12px; font-size: 12px; font-weight: 600; background: #ffffff; color: #0284c7; border: 1px solid #cbd5e1;">
					<i class="fas fa-pen-to-square u-mr5"></i>Düzenle
				</a>
			</div>
		</div>
		<?php } ?>
	</div>
</div>

<script>
function filterCategory(cat, btn) {
	document.querySelectorAll('.js-filter-btn').forEach(b => {
		b.classList.remove('active');
		b.style.fontWeight = 'normal';
	});
	btn.classList.add('active');
	btn.style.fontWeight = '600';

	const cards = document.querySelectorAll('.tpl-card');
	cards.forEach(card => {
		if (cat === 'all' || card.getAttribute('data-category') === cat) {
			card.style.display = 'flex';
		} else {
			card.style.display = 'none';
		}
	});
}

document.getElementById('tplSearch')?.addEventListener('input', function(e) {
	const q = e.target.value.toLowerCase().trim();
	document.querySelectorAll('.tpl-card').forEach(card => {
		const text = card.getAttribute('data-name');
		if (!q || text.includes(q)) {
			card.style.display = 'flex';
		} else {
			card.style.display = 'none';
		}
	});
});
</script>
