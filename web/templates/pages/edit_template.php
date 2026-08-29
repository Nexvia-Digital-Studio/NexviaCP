<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a href="/list/templates/" class="button button-secondary">
				<i class="fas fa-arrow-left icon-maroon"></i><?= tohtml( _("Tüm Şablonlar")) ?>
			</a>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">
	<form method="post" action="/edit/template/?category=<?= urlencode($category) ?>&name=<?= urlencode($name) ?>">
		<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
		<input type="hidden" name="save" value="1">

		<div class="form-container" style="max-width: 1000px;">
			<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
				<h1 style="margin: 0;">
					<i class="fas fa-code icon-purple u-mr5"></i>
					<?= $is_new ? tohtml( _("Yeni Şablon Oluştur")) : tohtml(sprintf(_("Şablonu Düzenle: %s"), $name)) ?>
				</h1>
				<div>
					<button type="submit" class="button button-primary" style="padding: 8px 20px; font-size: 14px; font-weight: 600;">
						<i class="fas fa-floppy-disk u-mr5"></i><?= tohtml( _("Şablonu Kaydet")) ?>
					</button>
				</div>
			</div>

			<?php show_alert_message($_SESSION); ?>

			<div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px;">
				<div style="flex: 1; min-width: 250px;">
					<label class="form-label" style="font-weight: 600;">Şablon Kategorisi:</label>
					<select class="form-select" name="category" <?= !$is_new ? 'readonly' : '' ?>>
						<option value="web/nginx" <?= $category === "web/nginx" ? "selected" : "" ?>>Nginx Reverse Proxy &amp; Web (web/nginx)</option>
						<option value="web/php-fpm" <?= $category === "web/php-fpm" ? "selected" : "" ?>>PHP-FPM Pools (web/php-fpm)</option>
					</select>
				</div>
				<div style="flex: 1; min-width: 250px;">
					<label class="form-label" style="font-weight: 600;">Şablon Adı:</label>
					<input type="text" class="form-control" name="name" value="<?= tohtml($name) ?>" required <?= !$is_new ? 'readonly' : '' ?> style="font-family: monospace; font-weight: 600;">
				</div>
			</div>

			<!-- Editor File Tabs -->
			<div style="display: flex; gap: 6px; border-bottom: 2px solid #e2e8f0; margin-bottom: 15px;">
				<button type="button" class="button button-secondary active js-tab-btn" id="btnTabTpl" onclick="switchTab('tpl')" style="border-bottom-left-radius: 0; border-bottom-right-radius: 0; font-weight: 600; padding: 8px 16px;">
					<i class="fas fa-file-code u-mr5" style="color: #0284c7;"></i>HTTP Şablonu (.tpl)
				</button>
				<button type="button" class="button button-secondary js-tab-btn" id="btnTabStpl" onclick="switchTab('stpl')" style="border-bottom-left-radius: 0; border-bottom-right-radius: 0; padding: 8px 16px;">
					<i class="fas fa-lock u-mr5" style="color: #10b981;"></i>HTTPS SSL Şablonu (.stpl)
				</button>
				<button type="button" class="button button-secondary js-tab-btn" id="btnTabSh" onclick="switchTab('sh')" style="border-bottom-left-radius: 0; border-bottom-right-radius: 0; padding: 8px 16px;">
					<i class="fas fa-terminal u-mr5" style="color: #d97706;"></i>Tetikleme Scripti (.sh)
				</button>
			</div>

			<!-- Tab 1: .tpl -->
			<div id="tabTpl" class="tpl-editor-tab">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
					<span style="font-size: 13px; color: #64748b;">
						<strong>HTTP Sanal Host Bloğu</strong> — Değişkenler: <code>%ip%</code>, <code>%port%</code>, <code>%domain_idn%</code>, <code>%alias%</code>, <code>%docroot%</code>
					</span>
				</div>
				<textarea class="form-control" name="content_tpl" rows="22" style="font-family: 'Courier New', Courier, monospace; font-size: 13px; line-height: 1.5; background: #0f172a; color: #f8fafc; border-radius: 6px; padding: 12px;"><?= htmlentities($content_tpl) ?></textarea>
			</div>

			<!-- Tab 2: .stpl -->
			<div id="tabStpl" class="tpl-editor-tab" style="display: none;">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
					<span style="font-size: 13px; color: #64748b;">
						<strong>HTTPS SSL Sanal Host Bloğu</strong> — SSL Sertifika Değişkenleri: <code>%ssl_pem%</code>, <code>%ssl_key%</code>
					</span>
				</div>
				<textarea class="form-control" name="content_stpl" rows="22" style="font-family: 'Courier New', Courier, monospace; font-size: 13px; line-height: 1.5; background: #0f172a; color: #f8fafc; border-radius: 6px; padding: 12px;"><?= htmlentities($content_stpl) ?></textarea>
			</div>

			<!-- Tab 3: .sh -->
			<div id="tabSh" class="tpl-editor-tab" style="display: none;">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
					<span style="font-size: 13px; color: #64748b;">
						<strong>Opsiyonel Shell Tetikleme Kancası</strong> — Domain eklendiğinde/güncellendiğinde root yetkisiyle çalışır.
					</span>
				</div>
				<textarea class="form-control" name="content_sh" rows="22" placeholder="#!/bin/bash&#10;# Opsiyonel hook scripti" style="font-family: 'Courier New', Courier, monospace; font-size: 13px; line-height: 1.5; background: #0f172a; color: #f8fafc; border-radius: 6px; padding: 12px;"><?= htmlentities($content_sh) ?></textarea>
			</div>

			<div style="margin-top: 20px; display: flex; justify-content: flex-end;">
				<button type="submit" class="button button-primary" style="padding: 10px 24px; font-size: 14px; font-weight: 600;">
					<i class="fas fa-floppy-disk u-mr5"></i><?= tohtml( _("Değişiklikleri Kaydet")) ?>
				</button>
			</div>
		</div>
	</form>
</div>

<script>
function switchTab(tab) {
	document.querySelectorAll('.js-tab-btn').forEach(b => {
		b.classList.remove('active');
		b.style.fontWeight = 'normal';
	});
	document.querySelectorAll('.tpl-editor-tab').forEach(t => t.style.display = 'none');

	if (tab === 'tpl') {
		document.getElementById('tabTpl').style.display = 'block';
		document.getElementById('btnTabTpl').classList.add('active');
		document.getElementById('btnTabTpl').style.fontWeight = '600';
	} else if (tab === 'stpl') {
		document.getElementById('tabStpl').style.display = 'block';
		document.getElementById('btnTabStpl').classList.add('active');
		document.getElementById('btnTabStpl').style.fontWeight = '600';
	} else if (tab === 'sh') {
		document.getElementById('tabSh').style.display = 'block';
		document.getElementById('btnTabSh').classList.add('active');
		document.getElementById('btnTabSh').style.fontWeight = '600';
	}
}
</script>
